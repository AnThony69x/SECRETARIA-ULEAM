<?php
// Administración de los slides del hero: caja "Diseño del slide" (foto de fondo, imagen de frente, tono azul, lado de la tarjeta)
// y lista con vista previa y orden por arrastre. Lo que se guarda se aplica en index.php y assets/css/22-hero-variantes.css.
// Meta: frente_id (PNG de frente), tono ('' automático, 0 sin tono, 1 suave, 2 intenso), lado ('' alterna, der, izq). Fondo = imagen destacada.

const SLIDE_TONOS = array(
	''  => array( 'Automático', 'Azul suave si hay imagen de frente; sin tono si no' ),
	'0' => array( 'Sin tono', 'La foto se ve con sus colores' ),
	'1' => array( 'Azul suave', 'Resalta un poco la imagen de frente' ),
	'2' => array( 'Azul intenso', 'Fondo casi monocromo azul' ),
);
const SLIDE_LADOS = array(
	''    => array( 'Alternar', 'Derecha, izquierda, derecha… según el orden' ),
	'der' => array( 'Tarjeta a la derecha', 'La imagen queda a la izquierda' ),
	'izq' => array( 'Tarjeta a la izquierda', 'La imagen queda a la derecha' ),
);

// Editor clásico para los slides: así las cajas de diseño y de datos quedan a la vista (en el editor de bloques se pliegan abajo).
add_filter( 'use_block_editor_for_post_type', fn( $usa, $tipo ) => 'slide' === $tipo ? false : $usa, 10, 2 );

// Imagen de fondo/frente: tarjeta con vista previa y botones (el JS está en assets/admin/slides.js).
function slide_selector( $nombre, $titulo, $icono, $id, $ayuda, $dama = false ) {
	echo '<div class="us-card"><h4><span class="dashicons ' . esc_attr( $icono ) . '"></span>' . esc_html( $titulo ) . '</h4>';
	echo '<div class="us-prev' . ( $dama ? ' us-damas' : '' ) . '" data-prev>' . ( $id ? wp_get_attachment_image( $id, 'large' ) : '<span class="us-vacio"><span class="dashicons dashicons-format-image"></span>Sin imagen</span>' ) . '</div>';
	echo '<input type="hidden" name="' . esc_attr( $nombre ) . '" value="' . (int) $id . '">';
	echo '<p class="description">' . esc_html( $ayuda ) . '</p>';
	echo '<p class="us-acc"><button type="button" class="button button-primary" data-elegir>' . ( $id ? 'Cambiar imagen' : 'Elegir imagen' ) . '</button> <button type="button" class="button-link button-link-delete" data-quitar' . ( $id ? '' : ' hidden' ) . '>Quitar</button></p></div>';
}

function slide_opciones( $nombre, $opciones, $actual, $clase ) {
	echo '<div class="us-opts">';
	foreach ( $opciones as $v => $o ) {
		echo '<label class="us-opt"><input type="radio" name="' . esc_attr( $nombre ) . '" value="' . esc_attr( $v ) . '"' . checked( (string) $actual, (string) $v, false ) . '>';
		echo '<span class="us-sw ' . esc_attr( $clase . '-' . ( '' === (string) $v ? 'auto' : $v ) ) . '"><i></i><i></i></span><b>' . esc_html( $o[0] ) . '</b><small>' . esc_html( $o[1] ) . '</small></label>';
	}
	echo '</div>';
}

add_action( 'add_meta_boxes_slide', function () {
	remove_meta_box( 'postimagediv', 'slide', 'side' ); // la foto de fondo se elige en nuestra caja
	add_meta_box( 'slide_diseno', 'Diseño del slide', function ( $post ) {
		wp_nonce_field( 'slide_diseno', 'slide_diseno_nonce' );
		$fondo = (int) get_post_thumbnail_id( $post );
		$fr    = (int) get_post_meta( $post->ID, 'frente_id', true );
		echo '<p class="us-intro"><span class="dashicons dashicons-welcome-learn-more"></span> Elige las imágenes y cómo se ve el slide. Los textos de la tarjeta se escriben arriba (título y párrafo) y en «Datos del slide».</p>';
		echo '<div class="us-grid">';
		slide_selector( 'fondo_id', 'Foto de fondo', 'dashicons-format-image', $fondo, 'Foto horizontal, de 1600 px o más de ancho. Va detrás de todo.' );
		slide_selector( 'frente_id', 'Imagen de frente (sin fondo)', 'dashicons-groups', $fr, 'PNG con transparencia: personas, logo o figuras. Va delante de la foto de fondo.', true );
		echo '</div>';
		echo '<h4 class="us-t"><span class="dashicons dashicons-admin-appearance"></span> Tono azul del fondo</h4>';
		slide_opciones( 'tono', SLIDE_TONOS, (string) get_post_meta( $post->ID, 'tono', true ), 'us-tono' );
		echo '<h4 class="us-t"><span class="dashicons dashicons-align-pull-left"></span> Posición de la tarjeta</h4>';
		slide_opciones( 'lado', SLIDE_LADOS, (string) get_post_meta( $post->ID, 'lado', true ), 'us-lado' );
		echo '<p class="description">El orden de los slides se cambia arrastrándolos en <a href="' . esc_url( admin_url( 'edit.php?post_type=slide' ) ) . '">la lista de slides</a>.</p>';
	}, 'slide', 'normal', 'high' );
} );

add_action( 'save_post_slide', function ( $id ) {
	if ( ! isset( $_POST['slide_diseno_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slide_diseno_nonce'] ) ), 'slide_diseno' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$fondo = absint( $_POST['fondo_id'] ?? 0 );
	$fondo && wp_attachment_is_image( $fondo ) ? set_post_thumbnail( $id, $fondo ) : delete_post_thumbnail( $id );
	$fr = absint( $_POST['frente_id'] ?? 0 );
	update_post_meta( $id, 'frente_id', wp_attachment_is_image( $fr ) ? $fr : 0 );
	$tono = sanitize_text_field( wp_unslash( $_POST['tono'] ?? '' ) );
	update_post_meta( $id, 'tono', isset( SLIDE_TONOS[ $tono ] ) ? $tono : '' );
	$lado = sanitize_key( wp_unslash( $_POST['lado'] ?? '' ) );
	update_post_meta( $id, 'lado', isset( SLIDE_LADOS[ $lado ] ) ? $lado : '' );
} );

// Estilos y scripts solo en las pantallas de slides.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$s = get_current_screen();
	if ( ! $s || 'slide' !== $s->post_type || ! in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_media();
	wp_enqueue_style( 'uleam-slides-admin', "$uri/assets/admin/slides.css", array(), filemtime( "$dir/assets/admin/slides.css" ) );
	wp_enqueue_script( 'uleam-slides-admin', "$uri/assets/admin/slides.js", array( 'jquery', 'jquery-ui-sortable' ), filemtime( "$dir/assets/admin/slides.js" ), true );
	wp_localize_script( 'uleam-slides-admin', 'uleamSlides', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'slide_orden' ) ) );
} );

// Lista: siempre por orden del carrusel, con vista previa, ajustes y manija de arrastre.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'slide' === $q->get( 'post_type' ) && ! isset( $_GET['orderby'] ) ) {
		$q->set( 'orderby', 'menu_order' );
		$q->set( 'order', 'ASC' );
	}
} );
// Sin acciones en lote ni casillas: el orden se guarda solo y «Aplicar» solo confundía (cada fila conserva Editar y Papelera).
add_filter( 'bulk_actions-edit-slide', '__return_empty_array' );
add_filter( 'manage_slide_posts_columns', fn() => array( 'orden' => 'Orden', 'vista' => 'Vista previa', 'title' => 'Título', 'ajustes' => 'Ajustes' ) );
add_action( 'manage_slide_posts_custom_column', function ( $col, $id ) {
	if ( 'orden' === $col ) {
		echo '<span class="us-mover"><button type="button" class="button-link" data-sube aria-label="Subir"><span class="dashicons dashicons-arrow-up-alt2"></span></button><span class="us-num" data-num>' . ( (int) get_post_field( 'menu_order', $id ) + 1 ) . '</span><button type="button" class="button-link" data-baja aria-label="Bajar"><span class="dashicons dashicons-arrow-down-alt2"></span></button></span><span class="dashicons dashicons-menu us-asa" title="Arrastra para ordenar"></span>';
	} elseif ( 'vista' === $col ) {
		$f  = (int) get_post_meta( $id, 'frente_id', true );
		$bg = get_the_post_thumbnail_url( $id, 'medium' );
		echo '<span class="us-mini-vista">' . ( $bg ? '<img src="' . esc_url( $bg ) . '" alt="">' : '' ) . ( $f ? wp_get_attachment_image( $f, 'medium', false, array( 'class' => 'us-mini-frente' ) ) : '' ) . '</span>';
	} elseif ( 'ajustes' === $col ) {
		$t = (string) get_post_meta( $id, 'tono', true );
		$l = (string) get_post_meta( $id, 'lado', true );
		echo '<span class="us-chip">' . esc_html( SLIDE_TONOS[ $t ][0] ?? SLIDE_TONOS[''][0] ) . '</span> <span class="us-chip">' . esc_html( SLIDE_LADOS[ $l ][0] ?? SLIDE_LADOS[''][0] ) . '</span> ' . ( get_post_meta( $id, 'frente_id', true ) ? '<span class="us-chip us-chip-on">Con imagen de frente</span>' : '' );
	}
}, 10, 2 );
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( $s && 'edit-slide' === $s->id ) {
		echo '<div class="notice notice-info us-aviso"><p><span class="dashicons dashicons-sort"></span> <strong>Orden del carrusel:</strong> arrastra las filas (o usa las flechas) y se guarda solo. La fila 1 es la primera que se ve en la portada.</p></div>';
	}
} );

// Guarda el nuevo orden (lista de IDs en el orden de la tabla).
add_action( 'wp_ajax_uleam_slide_orden', function () {
	check_ajax_referer( 'slide_orden', 'nonce' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( null, 403 );
	}
	foreach ( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) as $i => $id ) {
		if ( 'slide' === get_post_type( $id ) ) {
			wp_update_post( array( 'ID' => $id, 'menu_order' => $i ) );
		}
	}
	wp_send_json_success();
} );
