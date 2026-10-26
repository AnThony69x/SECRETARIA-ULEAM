<?php
// Organigrama editable desde wp-admin: personas (CPT "persona") agrupadas en áreas (taxonomía jerárquica "area").
// Área principal = nivel (nodo numerado); área hija = subnivel (3.1, 3.2...). Se muestra con el shortcode [organigrama].

const OC_ICONOS = array(
	'edificio' => 'Edificio', 'maletin' => 'Maletín', 'chip' => 'Computadora / chip', 'herramienta' => 'Herramienta',
	'graduation-cap' => 'Birrete', 'engranaje' => 'Engranaje', 'bandeja' => 'Bandeja de entrada', 'file-text' => 'Documento',
	'lapiz' => 'Lápiz', 'bar-chart' => 'Gráfico', 'archive' => 'Archivador', 'scale' => 'Balanza', 'users' => 'Personas',
	'ayuda' => 'Ayuda', 'shield' => 'Escudo', 'star' => 'Estrella',
);
const OC_ESTILOS = array( '' => 'Normal', 'grande' => 'Tarjetas un poco más grandes', 'principal' => 'Principal (tarjeta destacada con insignia)' );

add_action( 'init', function () {
	register_post_type( 'persona', array(
		'labels'       => array( 'name' => 'Personal', 'singular_name' => 'Persona', 'add_new_item' => 'Añadir persona', 'edit_item' => 'Editar persona', 'featured_image' => 'Foto', 'set_featured_image' => 'Elegir foto', 'remove_featured_image' => 'Quitar foto' ),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-groups',
		'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
	) );
	register_taxonomy( 'area', 'persona', array(
		'labels'            => array( 'name' => 'Áreas del organigrama', 'singular_name' => 'Área', 'add_new_item' => 'Añadir área', 'parent_item' => 'Nivel al que pertenece (déjalo vacío si es un nivel principal)' ),
		'hierarchical'      => true,
		'public'            => false,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
	) );
} );

// El título de la persona es su nombre completo con título académico (ej. "Ing. Alex Molina").
add_filter( 'enter_title_here', function ( $t, $post ) {
	return 'persona' === $post->post_type ? 'Nombre con título (ej. Ing. Alex Molina)' : $t;
}, 10, 2 );

// --- Campos de cada área: color, ícono, orden, estilo e insignia ---
function oc_campos( $term = null ) {
	$term = is_object( $term ) ? $term : null; // en "Añadir área" WordPress pasa el nombre de la taxonomía
	$v = function ( $k, $def = '' ) use ( $term ) { return $term ? ( get_term_meta( $term->term_id, $k, true ) ?: $def ) : $def; };
	$fila = function ( $label, $input, $ayuda = '' ) use ( $term ) {
		echo $term ? "<tr class='form-field'><th><label>$label</label></th><td>$input" . ( $ayuda ? "<p class='description'>$ayuda</p>" : '' ) . '</td></tr>'
			: "<div class='form-field'><label>$label</label>$input" . ( $ayuda ? "<p>$ayuda</p>" : '' ) . '</div>';
	};
	wp_nonce_field( 'oc_area', 'oc_nonce' );
	$fila( 'Color', '<input type="color" name="oc_color" value="' . esc_attr( $v( 'color', '#005689' ) ) . '">', 'Color del nodo, la línea y las tarjetas del nivel. Los subniveles usan el color de su nivel.' );
	$sel = '';
	foreach ( OC_ICONOS as $k => $l ) $sel .= '<option value="' . esc_attr( $k ) . '"' . selected( $v( 'icono', 'users' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
	$fila( 'Ícono', "<select name='oc_icono'>$sel</select>" );
	$fila( 'Orden', '<input type="number" name="oc_orden" min="0" value="' . esc_attr( $v( 'orden', 0 ) ) . '" style="width:90px">', 'Posición dentro de su nivel (1, 2, 3...). Define el número que se muestra.' );
	$sel = '';
	foreach ( OC_ESTILOS as $k => $l ) $sel .= '<option value="' . esc_attr( $k ) . '"' . selected( $v( 'estilo' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
	$fila( 'Estilo de tarjetas', "<select name='oc_estilo'>$sel</select>" );
	$fila( 'Insignia', '<input type="text" name="oc_insignia" value="' . esc_attr( $v( 'insignia' ) ) . '">', 'Solo para el estilo Principal (ej. "Máxima autoridad administrativa").' );
}
add_action( 'area_add_form_fields', 'oc_campos' );
add_action( 'area_edit_form_fields', 'oc_campos' );

$oc_guardar = function ( $id ) {
	if ( ! isset( $_POST['oc_nonce'] ) || ! wp_verify_nonce( $_POST['oc_nonce'], 'oc_area' ) || ! current_user_can( 'manage_categories' ) ) return;
	update_term_meta( $id, 'color', sanitize_hex_color( wp_unslash( $_POST['oc_color'] ?? '' ) ) ?: '#005689' );
	$ic = sanitize_key( $_POST['oc_icono'] ?? '' );
	update_term_meta( $id, 'icono', isset( OC_ICONOS[ $ic ] ) ? $ic : 'users' );
	update_term_meta( $id, 'orden', absint( $_POST['oc_orden'] ?? 0 ) );
	$es = sanitize_key( $_POST['oc_estilo'] ?? '' );
	update_term_meta( $id, 'estilo', isset( OC_ESTILOS[ $es ] ) ? $es : '' );
	update_term_meta( $id, 'insignia', sanitize_text_field( wp_unslash( $_POST['oc_insignia'] ?? '' ) ) );
};
add_action( 'created_area', $oc_guardar );
add_action( 'edited_area', $oc_guardar );

// --- Shortcode [organigrama] ---
function oc_areas( $padre ) {
	$t = get_terms( array( 'taxonomy' => 'area', 'parent' => $padre, 'hide_empty' => false ) );
	if ( is_wp_error( $t ) ) return array();
	usort( $t, function ( $a, $b ) { return (int) get_term_meta( $a->term_id, 'orden', true ) <=> (int) get_term_meta( $b->term_id, 'orden', true ) ?: strcmp( $a->name, $b->name ); } );
	return $t;
}

// Una tarjeta de persona. $color solo se pasa fuera del organigrama (allí --area-color lo hereda el nivel).
function oc_tarjeta( $p, $cls, $ins, $color = '', $sub = '' ) {
	$foto = get_the_post_thumbnail_url( $p, 'medium_large' );
	return '<div class="oc-c ' . $cls . '"' . ( $color ? ' style="--area-color:' . esc_attr( $color ) . '"' : '' ) . '>' . ( 'oc-main' === $cls && $ins ? '<span class="oc-ins">' . esc_html( $ins ) . '</span>' : '' )
		. '<img class="oc-av' . ( $foto ? '' : ' oc-def' ) . '" src="' . esc_url( $foto ?: get_template_directory_uri() . '/assets/avatar.svg' ) . '" alt="' . esc_attr( $p->post_title ) . '" loading="lazy">'
		. '<div class="oc-tx"><b>' . esc_html( $p->post_title ) . '</b>' . ( $sub ? '<small>' . esc_html( $sub ) . '</small>' : '' ) . '</div></div>';
}

function oc_tarjetas( $area, $estilo ) {
	$gente = get_posts( array(
		'post_type' => 'persona', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query' => array( array( 'taxonomy' => 'area', 'terms' => $area->term_id, 'include_children' => false ) ),
	) );
	$cls = 'principal' === $estilo ? 'oc-main' : ( 'grande' === $estilo ? 'oc-mid' : '' );
	$ins = get_term_meta( $area->term_id, 'insignia', true );
	$o   = '<div class="oc-caj' . ( 'principal' === $estilo ? ' oc-uno' : '' ) . '">';
	foreach ( $gente as $p ) {
		$o .= oc_tarjeta( $p, $cls, $ins );
	}
	return $o . '</div>';
}

add_shortcode( 'organigrama', function () {
	$niveles = oc_areas( 0 );
	if ( ! $niveles ) return current_user_can( 'edit_posts' ) ? '<p class="doc-vacio">Añade áreas y personas en wp-admin → Personal.</p>' : '';
	$o = '';
	foreach ( $niveles as $i => $a ) {
		$color  = get_term_meta( $a->term_id, 'color', true ) ?: '#005689';
		$sig    = isset( $niveles[ $i + 1 ] ) ? ( get_term_meta( $niveles[ $i + 1 ]->term_id, 'color', true ) ?: $color ) : $color;
		$estilo = get_term_meta( $a->term_id, 'estilo', true );
		$cuerpo = '';
		$subs   = oc_areas( $a->term_id );
		foreach ( $subs as $j => $s ) {
			$cuerpo .= '<div class="oc-sub"><h3><span class="oc-ic">' . uleam_icon( get_term_meta( $s->term_id, 'icono', true ) ?: 'users' ) . '</span><b>' . ( $i + 1 ) . '.' . ( $j + 1 ) . '</b>' . esc_html( $s->name ) . '</h3>' . oc_tarjetas( $s, $estilo ) . '</div>';
		}
		if ( ! $subs || $a->count ) $cuerpo = oc_tarjetas( $a, $estilo ) . $cuerpo;
		$o .= '<li class="oc-n" style="--area-color:' . esc_attr( $color ) . ';--next-color:' . esc_attr( $sig ) . '"><span class="oc-num">' . ( $i + 1 ) . '</span>'
			. '<div class="oc-cont"><h2 class="oc-tit"><span class="oc-ic">' . uleam_icon( get_term_meta( $a->term_id, 'icono', true ) ?: 'users' ) . '</span>' . esc_html( $a->name ) . '</h2>' . $cuerpo . '</div></li>';
	}
	return '<div class="oc-wrap"><ol class="oc" id="oc" data-avatar="' . esc_url( get_template_directory_uri() . '/assets/avatar.svg' ) . '">' . $o . '</ol></div>';
} );
