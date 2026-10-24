<?php
// Datos básicos para buscadores y para compartir en redes: descripción, Open Graph y Twitter Card.
// El icono del navegador es el "Icono del sitio" de WordPress (Apariencia → Personalizar → Identidad del sitio).

add_action( 'customize_register', function ( $c ) {
	$c->add_setting( 'uleam_meta_desc', array( 'default' => ULEAM_META_DESC, 'sanitize_callback' => 'sanitize_text_field' ) );
	$c->add_control( 'uleam_meta_desc', array( 'section' => 'uleam', 'label' => 'Descripción del sitio para buscadores y redes (se usa si la página no tiene extracto)', 'type' => 'textarea' ) );
}, 20 );
const ULEAM_META_DESC = 'Dirección de Secretaría General de la Universidad Laica Eloy Alfaro de Manabí (ULEAM).';

add_action( 'wp_head', function () {
	$desc = is_singular() && has_excerpt() ? get_the_excerpt() : ( get_bloginfo( 'description' ) ?: get_theme_mod( 'uleam_meta_desc', ULEAM_META_DESC ) );
	$desc = wp_strip_all_tags( $desc );
	$img  = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : ( get_site_icon_url( 512 ) ?: ( has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '' ) );
	$url  = is_singular() ? get_permalink() : home_url( '/' );
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	foreach ( array(
		'og:locale'      => 'es_EC',
		'og:type'        => 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => wp_get_document_title(),
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $img,
		'twitter:card'   => 'summary',
	) as $k => $v ) {
		if ( $v ) {
			echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
}, 5 );
