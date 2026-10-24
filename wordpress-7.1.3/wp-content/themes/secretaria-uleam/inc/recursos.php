<?php
// Carga de CSS y JS del tema. Cada componente tiene su archivo en assets/css y assets/js.

// Módulos JS en orden. El primero conserva el handle "secretaria-uleam" porque inc/documentos.php le añade datos (ULEAM_CI).
const ULEAM_JS = array( 'menu', 'slider', 'carrusel', 'documentos-sesiones', 'documentos-anios', 'documentos', 'consejo', 'horario', 'visor', 'organigrama' );

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap', array(), null );

	// CSS: todos los assets/css/NN-*.css por orden de nombre (NN fija la cascada). Un archivo nuevo se carga solo.
	$prev = 'montserrat';
	foreach ( glob( "$dir/assets/css/*.css" ) as $f ) {
		$h = 'uleam-' . basename( $f, '.css' );
		wp_enqueue_style( $h, "$uri/assets/css/" . basename( $f ), array( $prev ), filemtime( $f ) );
		$prev = $h;
	}

	$prev = array();
	foreach ( ULEAM_JS as $i => $n ) {
		$h = $i ? "uleam-$n" : 'secretaria-uleam';
		wp_enqueue_script( $h, "$uri/assets/js/$n.js", $prev, filemtime( "$dir/assets/js/$n.js" ), true );
		$prev = array( $h );
	}
} );
