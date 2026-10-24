<?php
// Tema Secretaría ULEAM: menús, slides del hero editables y accesos rápidos.

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 278, 'width' => 912, 'flex-width' => true, 'flex-height' => true ) );
	register_nav_menus( array(
		'principal' => 'Menú principal',
		'accesos'   => 'Accesos rápidos (el nombre del icono va en la Descripción del enlace)',
	) );
} );

// Iconos SVG en línea (trazos tipo Lucide). Se usan por nombre en el código y en los menús.
function uleam_icon( $name ) {
	static $p = array(
		'mail'           => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'search'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'chevron-left'   => '<path d="m15 18-6-6 6-6"/>',
		'chevron-right'  => '<path d="m9 18 6-6-6-6"/>',
		'arrow-right'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'pause'          => '<rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/>',
		'play'           => '<polygon points="6 3 20 12 6 21 6 3"/>',
		'menu'           => '<path d="M4 12h16M4 6h16M4 18h16"/>',
		'users'          => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'graduation-cap' => '<path d="M21.42 10.92a1 1 0 0 0 0-1.84L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.83l8.57 3.91a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
		'monitor'        => '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/>',
		'archive'        => '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
		'bar-chart'      => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
		'file-text'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8"/>',
		'scale'          => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1zM2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1zM7 21h10M12 3v18M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
		'target'         => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
		'eye'            => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
		'x'              => '<path d="M4 4l11.7 16H20L8.3 4z"/><path d="M4 20l6.8-6.8M13.2 10.8L20 4"/>',
		'youtube'        => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
		'shield'         => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
		'star'           => '<path d="M11.5 2.3a.5.5 0 0 1 1 0l2.3 4.7 5.2.8a.5.5 0 0 1 .3.85l-3.8 3.7.9 5.2a.5.5 0 0 1-.73.53L12 15.6l-4.65 2.45a.5.5 0 0 1-.73-.53l.9-5.2-3.8-3.7a.5.5 0 0 1 .3-.85l5.2-.8z"/>',
		'facebook'       => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'instagram'      => '<rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/>',
			'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
			'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
			'folder'         => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/>',
			'calendar'       => '<path d="M8 2v4M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
		'tiktok'         => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
		// Organigrama (áreas)
		'edificio'       => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
		'maletin'        => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
		'chip'           => '<rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2M15 20v2M2 15h2M2 9h2M20 15h2M20 9h2M9 2v2M9 20v2"/>',
		'herramienta'    => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>',
		'engranaje'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
		'bandeja'        => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
		'lapiz'          => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
		'ayuda'          => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.93 4.93 4.24 4.24M14.83 14.83l4.24 4.24M14.83 9.17l4.24-4.24M4.93 19.07l4.24-4.24"/>',
	);
	return isset( $p[ $name ] ) ? '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . $p[ $name ] . '</svg>' : '';
}

// Datos de contacto y redes editables en Apariencia → Personalizar → Secretaría ULEAM.
// Cada red: etiqueta, URL por defecto (cuentas oficiales ULEAM) y si sale en la barra superior por defecto. El pie muestra todas las que tengan URL.
const REDES = array(
	'facebook'  => array( 'Facebook', 'https://www.facebook.com/UleamEc/', true ),
	'x'         => array( 'X', 'https://x.com/UleamEcuador', false ),
	'youtube'   => array( 'YouTube', 'https://www.youtube.com/channel/UCOferC0PjpEUegrVq8Re-TQ', false ),
	'instagram' => array( 'Instagram', 'https://www.instagram.com/uleam_ecuador_oficial/', false ),
	'tiktok'    => array( 'TikTok', '', false ),
);
const ULEAM_CORREO = 'secretaria.general@uleam.edu.ec';
const ULEAM_SIGUENOS = 'Síguenos';
const ULEAM_BUSCAR   = 'Buscar en el sitio...';
const ULEAM_PIE    = "ULEAM © Copyright {año}, Todos los derechos reservados - Universidad Laica Eloy Alfaro de Manabí\nDirección: Av. Circunvalación - Vía a San Mateo\nManta - Manabí - Ecuador";

// Redes con URL; $barra = true filtra las marcadas para la barra superior.
function uleam_redes( $barra = false ) {
	$out = array();
	foreach ( REDES as $k => $r ) {
		$u = get_theme_mod( "uleam_$k", $r[1] );
		if ( $u && ( ! $barra || get_theme_mod( "uleam_{$k}_barra", $r[2] ) ) ) {
			$out[ $k ] = array( $u, $r[0] );
		}
	}
	return $out;
}

add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'uleam', array( 'title' => 'Secretaría ULEAM', 'priority' => 30 ) );
	$c->add_setting( 'uleam_email', array( 'default' => ULEAM_CORREO, 'sanitize_callback' => 'sanitize_email' ) );
	$c->add_control( 'uleam_email', array( 'section' => 'uleam', 'label' => 'Correo de la barra superior', 'type' => 'email' ) );
	foreach ( REDES as $k => $r ) {
		$c->add_setting( "uleam_$k", array( 'default' => $r[1], 'sanitize_callback' => 'esc_url_raw' ) );
		$c->add_control( "uleam_$k", array( 'section' => 'uleam', 'label' => $r[0] . ' (URL, vacío = no se muestra)', 'type' => 'url' ) );
		$c->add_setting( "uleam_{$k}_barra", array( 'default' => $r[2], 'sanitize_callback' => 'rest_sanitize_boolean' ) );
		$c->add_control( "uleam_{$k}_barra", array( 'section' => 'uleam', 'label' => 'Mostrar ' . $r[0] . ' en la barra superior', 'type' => 'checkbox' ) );
	}
	$c->add_setting( 'uleam_siguenos', array( 'default' => ULEAM_SIGUENOS, 'sanitize_callback' => 'sanitize_text_field' ) );
	$c->add_control( 'uleam_siguenos', array( 'section' => 'uleam', 'label' => 'Texto junto a las redes (barra superior y pie)', 'type' => 'text' ) );
	$c->add_setting( 'uleam_buscar', array( 'default' => ULEAM_BUSCAR, 'sanitize_callback' => 'sanitize_text_field' ) );
	$c->add_control( 'uleam_buscar', array( 'section' => 'uleam', 'label' => 'Texto de ayuda del buscador', 'type' => 'text' ) );
	$c->add_setting( 'uleam_pie_texto', array( 'default' => ULEAM_PIE, 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$c->add_control( 'uleam_pie_texto', array( 'section' => 'uleam', 'label' => 'Texto del pie ({año} = año actual, se actualiza solo)', 'type' => 'textarea' ) );
} );

// CSS y JS: ver inc/recursos.php (un archivo por componente en assets/css y assets/js).
require get_template_directory() . '/inc/recursos.php';

// --- Slides del hero ---
add_action( 'init', function () {
	register_post_type( 'slide', array(
		'labels'       => array( 'name' => 'Slides del hero', 'singular_name' => 'Slide', 'add_new_item' => 'Añadir slide' ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-images-alt2',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'show_in_rest' => true,
	) );
} );

// Título = h1, contenido = párrafo, orden = "Atributos → Orden", imagen destacada = foto de fondo.
const SLIDE_CAMPOS = array(
	'etiqueta'  => 'Etiqueta (ej. Bienvenida)',
	'boton_txt' => 'Texto del botón',
	'boton_url' => 'URL del botón',
	'franja'    => 'Franja destacada (ej. TALENTO HUMANO · EQUIPO)',
);

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'slide_campos', 'Datos del slide', function ( $post ) {
		wp_nonce_field( 'slide_campos', 'slide_nonce' );
		foreach ( SLIDE_CAMPOS as $k => $label ) {
			$v = get_post_meta( $post->ID, $k, true );
			echo '<p><label><strong>' . esc_html( $label ) . '</strong><br>';
			echo '<input type="text" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '" style="width:100%">';
			echo '</label></p>';
		}
	}, 'slide' );
} );

add_action( 'save_post_slide', function ( $id ) {
	if ( ! isset( $_POST['slide_nonce'] ) || ! wp_verify_nonce( $_POST['slide_nonce'], 'slide_campos' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	foreach ( array_keys( SLIDE_CAMPOS ) as $k ) {
		update_post_meta( $id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
	}
} );

// Marca el enlace de la página actual con la clase "activo" del CSS.
add_filter( 'nav_menu_link_attributes', function ( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'principal' === $args->theme_location && $item->current && false === strpos( $item->url, '#' ) ) {
		$atts['class'] = 'activo';
	}
	return $atts;
}, 10, 3 );

// --- Páginas internas: título = h1 del banner, extracto = subtítulo, etiquetas = chips del banner (con ícono fijo). Todo editable. ---
add_action( 'init', function () {
	add_post_type_support( 'page', 'excerpt' );
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'pagina_etiquetas', 'Etiquetas del banner', function ( $post ) {
		wp_nonce_field( 'pagina_etiquetas', 'pagina_nonce' );
		echo '<p><input type="text" name="etiquetas" value="' . esc_attr( get_post_meta( $post->ID, 'etiquetas', true ) ) . '" style="width:100%" placeholder="Una, Dos, Tres"><br><small>Separadas por coma. El subtítulo del banner es el "Extracto" de la página.</small></p>';
	}, 'page', 'side' );
} );

add_action( 'save_post_page', function ( $id ) {
	if ( ! isset( $_POST['pagina_nonce'] ) || ! wp_verify_nonce( $_POST['pagina_nonce'], 'pagina_etiquetas' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'etiquetas', sanitize_text_field( wp_unslash( $_POST['etiquetas'] ?? '' ) ) );
} );

// Documentos PDF (miembros, actas y resoluciones).
require get_template_directory() . "/inc/documentos.php";
require get_template_directory() . "/inc/documentos-vista.php";

// Horario de atención editable.
require get_template_directory() . "/inc/horario.php";

// Organigrama de personal editable (Personal / Áreas del organigrama).
require get_template_directory() . "/inc/organigrama.php";
