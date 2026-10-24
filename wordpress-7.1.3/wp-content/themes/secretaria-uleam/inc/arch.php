<?php
// Icono por formato de archivo (hoja con la esquina doblada y las letras del formato: PDF, XLS, DOC…). Se dibuja con CSS
// (assets/css/19-arch.css) a partir de data-ext; JS recibe el código en cada fila de la API (campo "e").

// Código de 3 letras del formato según la dirección del archivo; vacío si no tiene extensión reconocible.
function doc_ext( $url ) {
	$e    = strtolower( pathinfo( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
	$mapa = array( 'xlsx' => 'XLS', 'xlsm' => 'XLS', 'docx' => 'DOC', 'pptx' => 'PPT', 'jpeg' => 'JPG', 'webp' => 'IMG', 'html' => 'WEB' );
	return preg_match( '/^[a-z0-9]{2,4}$/', $e ) ? ( $mapa[ $e ] ?? strtoupper( substr( $e, 0, 3 ) ) ) : '';
}

function doc_arch( $url = '' ) {
	return '<span class="arch" aria-hidden="true" data-ext="' . esc_attr( doc_ext( $url ) ) . '"></span>';
}
