<?php
// Ficha de requerimiento: recibe el formulario ([ficha], inc/ficha.php), lo valida, lo guarda como "Solicitud" (solo administradores)
// con número consecutivo y entrega el PDF de la ficha. Responde JSON si viene por AJAX y redirige si viene sin JavaScript.

add_action( 'init', function () {
	$admin = 'manage_options'; // los datos incluyen cédulas: solo administradores ven y editan las solicitudes
	register_post_type( 'solicitud', array(
		'labels' => array( 'name' => 'Solicitudes', 'singular_name' => 'Solicitud', 'menu_name' => 'Solicitudes de ficha', 'edit_item' => 'Ver solicitud', 'not_found' => 'Aún no hay solicitudes.' ),
		'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'exclude_from_search' => true, 'publicly_queryable' => false, 'menu_icon' => 'dashicons-clipboard', 'menu_position' => 26, 'supports' => array( 'title' ),
		'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts', 'publish_posts', 'read_private_posts', 'delete_private_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'delete_published_posts' ), $admin ) + array( 'create_posts' => 'do_not_allow' ),
	) );
} );

// Siguiente número de ficha del año (vuelve a 1 cada enero), sin repetirse aunque lleguen dos envíos a la vez.
function ficha_siguiente() {
	global $wpdb;
	$opt = 'uleam_ficha_n_' . wp_date( 'Y' );
		add_option( $opt, 0, '', 'no' );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID(option_value + 1) WHERE option_name = %s", $opt ) );
	$n = (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' );
	wp_cache_delete( $opt, 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	return $n;
}

// Texto libre corto (facultad, carrera, otro documento): letras, números y signos comunes.
const FICHA_TXT = '/^[\p{L}\p{N}][\p{L}\p{N}\s.,()&\/\'’:-]{2,119}$/u';

// Cédula ecuatoriana (10 dígitos, provincia, dígito verificador módulo 10) o pasaporte alfanumérico de 5 a 20.
function ficha_cedula_ok( $c ) {
	if ( ! preg_match( '/^\d{10}$/', $c ) ) {
		return (bool) preg_match( '/^[A-Za-z0-9]{5,20}$/', $c );
	}
	$prov = (int) substr( $c, 0, 2 );
	if ( ( $prov < 1 || $prov > 24 ) && 30 !== $prov || (int) $c[2] > 5 ) {
		return false;
	}
	$suma = 0;
	for ( $i = 0; $i < 9; $i++ ) {
		$v     = (int) $c[ $i ] * ( $i % 2 ? 1 : 2 );
		$suma += $v > 9 ? $v - 9 : $v;
	}
	return ( 10 - $suma % 10 ) % 10 === (int) $c[9];
}

// Valida y limpia lo enviado. Devuelve array( datos, errores ).
function ficha_validar( $p ) {
	$t  = fn( $k, $max ) => mb_substr( sanitize_text_field( wp_unslash( $p[ $k ] ?? '' ) ), 0, $max );
	$d  = array( 'nombres' => $t( 'nombres', 120 ), 'cedula' => $t( 'cedula', 20 ), 'facultad' => $t( 'facultad', 120 ), 'carrera' => $t( 'carrera', 120 ), 'ingreso' => $t( 'ingreso', 4 ), 'egreso' => $t( 'egreso', 4 ), 'otros' => '', 'detalle' => mb_substr( sanitize_textarea_field( wp_unslash( $p['detalle'] ?? '' ) ), 0, 1000 ) );
	$e  = array();
	$yr = (int) wp_date( 'Y' );
	if ( ! preg_match( '/^\p{L}[\p{L}\s.\'’-]{1,118}\p{L}$/u', $d['nombres'] ) || count( preg_split( '/\s+/', $d['nombres'] ) ) < 2 ) {
		$e['nombres'] = 'Escribe tus apellidos y nombres completos, solo con letras.';
	}
	foreach ( array( 'facultad', 'carrera' ) as $k ) {
		if ( ! preg_match( FICHA_TXT, $d[ $k ] ) ) {
			$e[ $k ] = 'Escribe un nombre válido (letras, números y signos comunes).';
		}
	}
	if ( ! ficha_cedula_ok( $d['cedula'] ) ) {
		$e['cedula'] = 'Cédula no válida: revisa los 10 dígitos, o escribe un pasaporte de 5 a 20 letras y números.';
	}
	foreach ( array( 'ingreso', 'egreso' ) as $k ) {
		if ( ! preg_match( '/^\d{4}$/', $d[ $k ] ) || (int) $d[ $k ] < 1950 || (int) $d[ $k ] > $yr ) {
			$e[ $k ] = "Escribe un año entre 1950 y $yr.";
		}
	}
	if ( ! isset( $e['ingreso'], $e['egreso'] ) && (int) $d['egreso'] < (int) $d['ingreso'] ) {
		$e['egreso'] = 'El año de egreso no puede ser anterior al de ingreso.';
	}
	$lista = ficha_docs();
	$docs  = array();
	$claves = array();
	foreach ( $lista as $d_ ) {
		$claves[ ficha_clave( $d_ ) ] = $d_;
	}
	foreach ( (array) ( $p['docs'] ?? array() ) as $c ) { // solo valen las claves de documentos de la lista, nunca texto libre
		if ( is_string( $c ) && isset( $claves[ $c ] ) ) {
			$docs[ $c ] = $claves[ $c ];
		}
	}
	$d['docs']    = array_values( $docs );
	$d['valores'] = array_map( fn( $n ) => ficha_lineas()[ $n ], $d['docs'] ); // el valor vigente queda fijado en la solicitud
	if ( ! $docs ) {
		$e['docs'] = 'Marca al menos un documento.';
	}
	if ( empty( $p['acepto'] ) ) {
		$e['acepto'] = 'Debes aceptar el aviso para generar la solicitud.';
	}
	return array( $d, $e );
}

function ficha_enviar() {
	$ajax = wp_doing_ajax();
	$msj  = fn( $c ) => $ajax ? FICHA_ERRORES[ $c ] : $c; // por AJAX va el texto; sin JavaScript, el código
	$fin  = function ( $ok, $dato, $id = 0 ) use ( $ajax ) {
		if ( $ajax ) {
			wp_send_json( $ok ? array( 'ok' => true, 'n' => $dato, 'num' => ficha_num( $dato ), 'pdf' => ficha_pdf_url( $id ) ) : array( 'ok' => false, 'error' => $dato ), $ok ? 200 : 422 );
		}
		$base = remove_query_arg( array( 'ficha', 'n', 'm' ), wp_get_referer() ?: home_url( '/' ) );
		wp_safe_redirect( ( $ok ? add_query_arg( array( 'ficha' => 'ok', 'n' => $dato, 'pdf' => rawurlencode( ficha_pdf_url( $id ) ) ), $base ) : add_query_arg( array( 'ficha' => 'err', 'm' => $dato ), $base ) ) . '#ficha' );
		exit;
	};
	$p = wp_unslash( $_POST );
	$ts  = (int) ( $p['ts'] ?? 0 );
	$seg = time() - $ts;
	$org = wp_parse_url( $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST ); // el envío debe venir de este sitio
	if ( ! hash_equals( ficha_token( $ts ), (string) ( $p['sig'] ?? '' ) ) || $org !== wp_parse_url( home_url(), PHP_URL_HOST )
		|| ! empty( $p['web'] ) || $seg < 4 || $seg > 7 * DAY_IN_SECONDS ) { // firma falsa, otro origen, trampa para robots o envío demasiado rápido
		$fin( false, $msj( 'x' ) );
	}
	// Topes: 5 por hora por dirección y 60 por hora en total (con proxy o CDN, REMOTE_ADDR es una sola dirección: el tope global evita bloquear a todos y el inundado).
	$llave = 'ficha_' . md5( uleam_ip() );
	$veces = (int) get_transient( $llave );
	$total = (int) get_transient( 'ficha_total' );
	if ( $veces >= 5 || $total >= 60 ) {
		$fin( false, $msj( 'rapido' ) );
	}
	list( $d, $e ) = ficha_validar( wp_slash( $p ) );
	if ( $e ) {
		$fin( false, $ajax ? 'Revisa los datos marcados: ' . implode( ' ', array_unique( $e ) ) : 'datos' );
	}
	set_transient( $llave, $veces + 1, HOUR_IN_SECONDS );
	set_transient( 'ficha_total', $total + 1, HOUR_IN_SECONDS );
	$n  = ficha_siguiente();
	$id = wp_insert_post( array( 'post_type' => 'solicitud', 'post_status' => 'private', 'post_title' => wp_slash( sprintf( 'Ficha No. %s (%s) · %s', ficha_num( $n ), wp_date( 'Y' ), $d['nombres'] ) ) ), true );
	if ( is_wp_error( $id ) ) {
		$fin( false, $msj( 'x' ) );
	}
	update_post_meta( $id, 'ficha_n', $n );
	update_post_meta( $id, 'ficha_anio', wp_date( 'Y' ) );
	update_post_meta( $id, 'ficha_datos', wp_slash( $d ) );
	update_post_meta( $id, 'ficha_consentimiento', array( 'fecha' => current_time( 'mysql' ), 'aviso' => md5( ficha_mod( 'ficha_aviso' ) ) ) ); // cuándo y qué texto aceptó
	$fin( true, $n, $id ); // el PDF se descarga con un enlace firmado (inc/ficha-pdf-hojas.php); no se envía correo
}
foreach ( array( 'admin_post_nopriv_uleam_ficha', 'admin_post_uleam_ficha', 'wp_ajax_nopriv_uleam_ficha', 'wp_ajax_uleam_ficha' ) as $h ) {
	add_action( $h, 'ficha_enviar' );
}

// Lista del administrador: número, solicitante, documentos y fecha.
add_filter( 'manage_solicitud_posts_columns', fn() => array( 'cb' => '<input type="checkbox">', 'n' => 'No.', 'title' => 'Solicitante', 'docs' => 'Documentos', 'date' => 'Fecha' ) );
add_action( 'manage_solicitud_posts_custom_column', function ( $col, $id ) {
	$d = get_post_meta( $id, 'ficha_datos', true ) ?: array();
	echo 'n' === $col ? esc_html( ficha_num( (int) get_post_meta( $id, 'ficha_n', true ) ) ) : esc_html( implode( ', ', array_filter( array_merge( $d['docs'] ?? array(), array( ! empty( $d['otros'] ) ? 'Otros: ' . $d['otros'] : '' ) ) ) ) );
}, 10, 2 );

// Ficha completa de solo lectura dentro de cada solicitud.
add_action( 'add_meta_boxes_solicitud', function () {
	add_meta_box( 'ficha_datos', 'Datos de la solicitud', function ( $post ) {
		$d = get_post_meta( $post->ID, 'ficha_datos', true ) ?: array();
		$e = array( 'nombres' => 'Apellidos y nombres', 'cedula' => 'Cédula / pasaporte', 'facultad' => 'Facultad', 'carrera' => 'Carrera', 'ingreso' => 'Año de ingreso', 'egreso' => 'Año de egreso', 'otros' => 'Otros', 'detalle' => 'Detalle' );
		echo '<table class="widefat striped"><tbody>';
		foreach ( $e as $k => $l ) {
			echo '<tr><th>' . esc_html( $l ) . '</th><td>' . nl2br( esc_html( $d[ $k ] ?? '' ) ) . '</td></tr>';
		}
		echo '<tr><th>Documentos</th><td>' . esc_html( implode( ', ', $d['docs'] ?? array() ) ) . '</td></tr></tbody></table>';
	}, 'solicitud', 'normal', 'high' );
} );

// Privacidad: las solicitudes se eliminan solas tras 365 días (la ficha no guarda correo, por lo que no hay exportador automático: se atiende desde Solicitudes).
add_action( 'wp', function () {
	if ( ! wp_next_scheduled( 'uleam_ficha_limpiar' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'uleam_ficha_limpiar' );
	}
} );
add_action( 'uleam_ficha_limpiar', function () {
	foreach ( get_posts( array( 'post_type' => 'solicitud', 'post_status' => 'any', 'numberposts' => 200, 'date_query' => array( array( 'before' => '365 days ago' ) ), 'fields' => 'ids' ) ) as $id ) {
		wp_delete_post( $id, true );
	}
} );
