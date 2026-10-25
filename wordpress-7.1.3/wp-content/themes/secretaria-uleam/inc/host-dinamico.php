<?php
// Enlaces guardados en la base con http://localhost:8000: se reescriben al host real de la
// petición (túnel, IP de la red, dominio), sin tocar la base de datos.
function uleam_reescribir_host( $html ) {
	return str_replace(
		array( 'http://localhost:8000', 'http:\/\/localhost:8000' ),
		array( home_url(), str_replace( '/', '\/', home_url() ) ),
		$html
	);
}
add_action( 'template_redirect', function () {
	if ( ! is_admin() && ! is_feed() ) {
		ob_start( 'uleam_reescribir_host' );
	}
}, 0 );
