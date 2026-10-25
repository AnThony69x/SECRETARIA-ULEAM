<?php
// Arma las dos hojas del PDF de la ficha (inc/ficha-pdf.php) y lo entrega con un enlace firmado por solicitud.

function ficha_pdf_url( $id ) {
	return add_query_arg( array( 'action' => 'uleam_ficha_pdf', 'id' => (int) $id, 'sig' => wp_hash( "ficha-pdf|$id" ) ), admin_url( 'admin-ajax.php' ) );
}

const FICHA_AZ = '#005689';
const FICHA_CL = '#e6f0f8';
const FICHA_GR = '#5f6b85';

// Membrete institucional (logo, universidad, dirección) y cuadro de control del formulario; devuelve la y donde sigue el contenido.
function ficha_pdf_cab( $p, $pag, $codigo ) {
	$logo = get_theme_mod( 'custom_logo' ) ? get_attached_file( get_theme_mod( 'custom_logo' ) ) : '';
	if ( $logo && ! $p->img ) {
		$p->logo( $logo );
	}
	$p->imagen( 40, 28, 133, 40.5 ); // proporción 912x278 del logo
	$p->texto( 555, 44, mb_strtoupper( ficha_mod( 'ficha_inst' ) ), 10.5, true, FICHA_AZ, true );
	$p->texto( 555, 59, get_bloginfo( 'name' ), 9, false, FICHA_GR, true );
	$p->linea( 40, 78, 555, 78, FICHA_AZ, 2 );
	$p->linea( 40, 81, 555, 81, '#DB1D1F', .8 );
	$y = 94;
	$p->caja( 40, $y, 515, 62, FICHA_AZ );
	$p->linea( 380, $y, 380, $y + 62, FICHA_AZ );
	$p->linea( 40, $y + 31, 380, $y + 31, FICHA_GR, .4 );
	$p->linea( 380, $y + 31, 555, $y + 31, FICHA_GR, .4 );
	$p->caja( 40, $y, 515, 62, FICHA_AZ );
	$p->texto( 48, $y + 12, 'NOMBRE DEL DOCUMENTO', 7, true, FICHA_GR );
	$p->texto( 48, $y + 25, mb_strtoupper( ficha_mod( 'ficha_nombre' ) ), 9, true, FICHA_AZ );
	$p->texto( 48, $y + 43, 'PROCEDIMIENTO', 7, true, FICHA_GR );
	$p->parrafo( 48, $y + 54, 324, ficha_mod( 'ficha_proc' ), 7.5, false, '#1e2a44', 1.15 );
	$p->texto( 388, $y + 12, 'CÓDIGO', 7, true, FICHA_GR );
	$p->texto( 388, $y + 25, $codigo, 9, true, FICHA_AZ );
	$p->texto( 388, $y + 43, 'REVISIÓN ' . ficha_mod( 'ficha_revision' ) . '  ·  PÁGINA ' . $pag . ' DE 2', 7.5, true, FICHA_AZ );
	return $y + 62;
}

// Pie de página: datos de contacto y fecha de emisión.
function ficha_pdf_pie( $p, $emitido ) {
	$dir = str_replace( array( "\r", "\n" ), ' · ', trim( ha_mod( 'ha_direccion', 'Av. Circunvalación – Vía a San Mateo, Manta – Manabí – Ecuador' ) ) );
	$p->linea( 40, 800, 555, 800, FICHA_AZ, .8 );
	$p->texto( 40, 813, $dir . ' · ' . ha_mod( 'ha_correo', 'secretaria.general@uleam.edu.ec' ), 7.5, false, FICHA_GR );
	$p->texto( 555, 824, 'Documento generado en línea · ' . $emitido, 7.5, false, FICHA_GR, true );
}

function ficha_pdf_centro( $p, $y, $s, $t, $color = '#1e2a44' ) {
	$p->texto( 297.5 - $p->ancho( $s, $t, true ) / 2, $y, $s, $t, true, $color );
}

// Fila de tabla: etiqueta sombreada y valor.
function ficha_pdf_fila( $p, $y, $etq, $val, $h = 22, $x = 40, $w = 515, $we = 190 ) {
	$p->caja( $x, $y, $we, $h, FICHA_GR, FICHA_CL );
	$p->caja( $x + $we, $y, $w - $we, $h, FICHA_GR );
	$p->texto( $x + 8, $y + $h / 2 + 3, $etq, 8.5, true, FICHA_AZ );
	$p->texto( $x + $we + 8, $y + $h / 2 + 3.5, $val, 10 );
}

function ficha_pdf( $id ) {
	$d      = get_post_meta( $id, 'ficha_datos', true ) ?: array();
	$n      = (int) get_post_meta( $id, 'ficha_n', true );
	$p      = new Ficha_PDF();
	$c      = ficha_mod( 'ficha_codigo' ); // el código es del formulario y no cambia; el consecutivo va en el No.
	$num    = ficha_num( $n );
	$emitido = wp_date( 'd/m/Y H:i' );

	// Hoja 1: la ficha con los datos del solicitante y los documentos pedidos.
	$y = ficha_pdf_cab( $p, 1, $c ) + 30;
	ficha_pdf_centro( $p, $y, mb_strtoupper( ficha_mod( 'ficha_titulo' ) ), 15, FICHA_AZ );
	ficha_pdf_centro( $p, $y + 20, mb_strtoupper( ficha_mod( 'ficha_nombre' ) ) . " No. $num", 11.5 );
	$y += 36;
	$p->texto( 555, $y + 8, 'Fecha de emisión: ' . wp_date( 'd/m/Y' ), 8.5, false, FICHA_GR, true );
	$p->texto( 40, $y + 8, 'DATOS DEL SOLICITANTE', 9, true, FICHA_AZ );
	$y += 14;
	foreach ( array( 'nombres' => 'Apellidos y nombres', 'cedula' => 'Cédula y/o pasaporte', 'facultad' => 'Facultad', 'carrera' => 'Carrera', 'ingreso' => 'Año de ingreso', 'egreso' => 'Año de egreso' ) as $k => $e ) {
		ficha_pdf_fila( $p, $y, $e, $d[ $k ] ?? '' );
		$y += 22;
	}
	// Documentos: tabla con encabezado y total.
	$y += 16;
	$p->texto( 40, $y, 'DOCUMENTOS SOLICITADOS', 9, true, FICHA_AZ );
	$y += 8;
	$p->caja( 40, $y, 515, 20, FICHA_AZ, FICHA_AZ );
	$p->texto( 48, $y + 13.5, 'Documento', 8.5, true, '#ffffff' );
	$p->texto( 547, $y + 13.5, 'Valor (USD)', 8.5, true, '#ffffff', true );
	$y    += 20;
	$total = 0;
	$filas = (array) ( $d['docs'] ?? array() );
	if ( ! empty( $d['otros'] ) ) {
		$filas[] = 'Otros: ' . $d['otros'];
	}
	foreach ( $filas as $i => $nom ) {
		$v   = $d['valores'][ $i ] ?? null;
		$fin = $p->parrafo( 66, $y + 15, 410, $nom, 9.5, false, '#1e2a44', 1.25 );
		$h   = max( 22, $fin - $y + 4 );
		$p->caja( 40, $y, 515, $h, FICHA_GR );
		$p->caja( 48, $y + $h / 2 - 4.5, 9, 9, FICHA_AZ, FICHA_CL );
		$p->linea( 50, $y + $h / 2 - 2.5, 55, $y + $h / 2 + 2.5, FICHA_AZ, 1.2 );
		$p->linea( 55, $y + $h / 2 - 2.5, 50, $y + $h / 2 + 2.5, FICHA_AZ, 1.2 );
		if ( null !== $v ) {
			$p->texto( 547, $y + $h / 2 + 3.5, number_format( $v, 2 ), 9.5, true, FICHA_AZ, true );
			$total += $v;
		}
		$y += $h;
	}
	if ( $total ) {
		$p->caja( 40, $y, 515, 22, FICHA_GR, FICHA_CL );
		$p->texto( 480, $y + 15, 'TOTAL REFERENCIAL', 8.5, true, FICHA_AZ, true );
		$p->texto( 547, $y + 15, '$' . number_format( $total, 2 ), 10.5, true, FICHA_AZ, true );
		$y += 22;
	}
	$y += 16;
	$p->texto( 40, $y, 'DETALLE DE REQUERIMIENTO', 9, true, FICHA_AZ );
	$p->caja( 40, $y + 6, 515, 62, FICHA_GR );
	$p->parrafo( 48, $y + 20, 499, (string) ( $d['detalle'] ?? '' ), 9 );
	$y = max( $y + 90, 706 );
	foreach ( array( 'Nombre del/la solicitante', 'Nombre de quien recibe' ) as $i => $fila ) { // espacio para firmas
		$yy = $y + $i * 40;
		$p->caja( 40, $yy, 515, 40, FICHA_GR );
		$p->linea( 270, $yy, 270, $yy + 40, FICHA_GR, .4 );
		$p->linea( 420, $yy, 420, $yy + 40, FICHA_GR, .4 );
		$p->texto( 46, $yy + 11, $fila . ':', 7.5, true, FICHA_AZ );
		$p->texto( 276, $yy + 11, 'Firma:', 7.5, true, FICHA_AZ );
		$p->texto( 426, $yy + 11, 'Fecha:', 7.5, true, FICHA_AZ );
	}
	ficha_pdf_pie( $p, $emitido );
	$p->nueva();

	// Hoja 2: instrucciones de pago y entrega.
	$y = ficha_pdf_cab( $p, 2, $c ) + 30;
	ficha_pdf_centro( $p, $y, 'INSTRUCCIONES DE PAGO Y ENTREGA', 15, FICHA_AZ );
	$y += 28;
	ficha_pdf_fila( $p, $y, 'Ficha No.', $num );
	ficha_pdf_fila( $p, $y + 22, 'Solicitante', $d['nombres'] ?? '' );
	ficha_pdf_fila( $p, $y + 44, 'Cédula y/o pasaporte', $d['cedula'] ?? '' );
	$y += 66;
	if ( $total ) {
		$p->caja( 40, $y, 515, 30, FICHA_AZ, FICHA_AZ );
		$p->texto( 48, $y + 19, 'VALOR REFERENCIAL A PAGAR', 9.5, true, '#ffffff' );
		$p->texto( 547, $y + 20, '$' . number_format( $total, 2 ) . ' USD', 13, true, '#ffffff', true );
		$y += 30;
	}
	$y += 24;
	$p->texto( 40, $y, 'CUENTAS PARA EL PAGO', 9, true, FICHA_AZ );
	$y += 8;
	$p->caja( 40, $y, 515, 20, FICHA_AZ, FICHA_AZ );
	foreach ( array( array( 48, 'Entidad bancaria' ), array( 220, 'Titular' ), array( 547, 'N.º de cuenta' ) ) as $i => $col ) {
		$p->texto( $col[0], $y + 13.5, $col[1], 8.5, true, '#ffffff', 2 === $i );
	}
	$y += 20;
	foreach ( ficha_pagos() as $m ) {
		$p->caja( 40, $y, 515, 30, FICHA_GR );
		$p->texto( 48, $y + 19, $m[0], 10, true, FICHA_AZ );
		$p->parrafo( 220, $y + 18, 230, $m[1], 8.5, false, '#1e2a44', 1.2 );
		$p->texto( 547, $y + 19, $m[2], 10, true, '#1e2a44', true );
		$y += 30;
	}
	$y += 24;
	$p->texto( 40, $y, 'ENTREGA DE DOCUMENTOS', 9, true, FICHA_AZ );
	$nota = str_replace( '{correo}', ha_mod( 'ha_correo', 'secretaria.general@uleam.edu.ec' ), ficha_mod( 'ficha_pago_nota' ) );
	$fin  = $p->parrafo( 56, $y + 28, 480, $nota, 10, false, '#1e2a44', 1.6 );
	$p->caja( 40, $y + 8, 515, $fin - $y - 14, FICHA_AZ, '' );
	$p->linea( 41.5, $y + 9.5, 41.5, $fin - 7.5, FICHA_AZ, 3 );
	ficha_pdf_pie( $p, $emitido );
	return $p->salida();
}

// Descarga del PDF: solo con el enlace firmado que se entrega al enviar la solicitud (o desde el administrador).
function ficha_pdf_entregar() {
	$id = (int) ( $_GET['id'] ?? 0 );
	if ( 'solicitud' !== get_post_type( $id ) || ! hash_equals( wp_hash( "ficha-pdf|$id" ), (string) ( $_GET['sig'] ?? '' ) ) ) {
		wp_die( 'Enlace no válido.', '', array( 'response' => 403 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="ficha-' . sprintf( '%03d', (int) get_post_meta( $id, 'ficha_n', true ) ) . '.pdf"' );
	header( 'X-Content-Type-Options: nosniff' );
	echo ficha_pdf( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
add_action( 'wp_ajax_nopriv_uleam_ficha_pdf', 'ficha_pdf_entregar' );
add_action( 'wp_ajax_uleam_ficha_pdf', 'ficha_pdf_entregar' );
