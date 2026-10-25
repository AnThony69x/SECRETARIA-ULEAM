<?php
// [equipo areas="slug1,slug2"]: la autoridad principal sola + carrusel dinámico con el resto del personal (tipo "persona").
// Sin "areas" usa todas. Tarjetas = las del organigrama (oc_tarjeta). Nombre, foto, cargo y orden se editan en wp-admin → Personal.

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'persona_extra', 'Cargo y destacado', function ( $post ) {
		wp_nonce_field( 'persona_extra', 'persona_extra_nonce' );
		echo '<p><label>Cargo<br><input type="text" name="cargo" class="widefat" value="' . esc_attr( get_post_meta( $post->ID, 'cargo', true ) ) . '" placeholder="Si lo dejas vacío se muestra el área"></label></p>'
			. '<p><label><input type="checkbox" name="principal" value="1"' . checked( get_post_meta( $post->ID, 'principal', true ), '1', false ) . '> Autoridad principal (tarjeta destacada)</label></p>';
	}, 'persona', 'side' );
} );
add_action( 'save_post_persona', function ( $id ) {
	if ( ! isset( $_POST['persona_extra_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['persona_extra_nonce'] ) ), 'persona_extra' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'cargo', sanitize_text_field( wp_unslash( $_POST['cargo'] ?? '' ) ) );
	update_post_meta( $id, 'principal', empty( $_POST['principal'] ) ? '' : '1' );
} );

add_shortcode( 'equipo', function ( $a ) {
	$a     = shortcode_atts( array( 'areas' => '' ), $a );
	$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $a['areas'] ) ) );
	$args  = array( 'post_type' => 'persona', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	if ( $slugs ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $slugs, 'include_children' => false ) );
	}
	$gente = get_posts( $args );
	if ( ! $gente ) {
		return current_user_can( 'edit_posts' ) ? '<p class="doc-vacio">Añade personas en wp-admin → Personal.</p>' : '';
	}
	$tarjeta = function ( $p, $cls = '' ) {
		$area  = wp_get_post_terms( $p->ID, 'area' )[0] ?? null;
		$color = $area ? ( get_term_meta( $area->term_id, 'color', true ) ?: '#005689' ) : '#005689';
		$ins   = $area && $cls ? get_term_meta( $area->term_id, 'insignia', true ) : '';
		return oc_tarjeta( $p, $cls, $ins, $color, get_post_meta( $p->ID, 'cargo', true ) ?: ( $area->name ?? '' ) ); // cargo, o el área si no hay
	};
	$solo = null; // solo se destaca la primera persona marcada como autoridad principal
	foreach ( $gente as $i => $p ) {
		if ( '1' === get_post_meta( $p->ID, 'principal', true ) ) {
			$solo = $p;
			unset( $gente[ $i ] );
			break;
		}
	}
	// Orden por color: las personas de una misma área (y sus subáreas) quedan juntas, siguiendo el orden de las áreas; dentro, el orden de siempre.
	$clave = function ( $p ) {
		$area = wp_get_post_terms( $p->ID, 'area' )[0] ?? null;
		if ( ! $area ) {
			return array( 999, 999 );
		}
		$raiz = get_ancestors( $area->term_id, 'area', 'taxonomy' );
		$raiz = $raiz ? end( $raiz ) : $area->term_id;
		return array( (int) ( get_term_meta( $raiz, 'orden', true ) ?: 99 ), (int) ( get_term_meta( $area->term_id, 'orden', true ) ?: 99 ) );
	};
	usort( $gente, fn( $x, $y ) => $clave( $x ) <=> $clave( $y ) ); // estable: conserva menu_order y título dentro de cada área
	$li = '';
	foreach ( $gente as $p ) {
		$li .= '<li>' . $tarjeta( $p ) . '</li>';
	}
	return '<div class="eq' . ( $solo ? ' eq-con-solo' : '' ) . '">'
		. ( $solo ? '<div class="eq-solo">' . $tarjeta( $solo, 'oc-main' ) . '</div>' : '' )
		. '<div class="eq-carr"><button type="button" class="carrusel-btn carrusel-prev" aria-label="Anterior">' . uleam_icon( 'chevron-left' ) . '</button>'
		. '<ul class="eq-pista" tabindex="0" aria-label="Equipo de trabajo">' . $li . '</ul>'
		. '<button type="button" class="carrusel-btn carrusel-next" aria-label="Siguiente">' . uleam_icon( 'chevron-right' ) . '</button>'
		. '<button type="button" class="eq-pausa" aria-label="Pausar carrusel" aria-pressed="false">' . uleam_icon( 'pause' ) . '</button></div></div>';
} );
