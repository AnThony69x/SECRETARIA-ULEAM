<?php
// Cambia la dirección del sitio en la base de datos (de la de desarrollo a la del servidor) sin romper datos serializados.
// Uso (desde la carpeta de WordPress en el servidor, DESPUÉS de importar la base de datos):
//   php cambiar-url.php http://localhost:8000 https://TU-DOMINIO --seco   (solo cuenta, no escribe)
//   php cambiar-url.php http://localhost:8000 https://TU-DOMINIO          (aplica el cambio)
// Opciones: --wp=/ruta/a/wordpress (si no se ejecuta desde su carpeta)  --bd=NOMBRE (probar sobre una copia de la base)
//           --autoprueba (comprueba el reemplazo con datos de ejemplo; no toca nada)
// No se cambia la columna guid de las entradas (WordPress recomienda dejarla como está).
// shortcut: los objetos PHP serializados no se recorren (este sitio no guarda ninguno); si hiciera falta, usar WP-CLI search-replace.

function cu_serializado( $v ) {
	return is_string( $v ) && ( 'b:0;' === $v || ( preg_match( '/^[aOsibd]:[0-9]/', $v ) && false !== @unserialize( $v, array( 'allowed_classes' => false ) ) ) );
}

// Reemplaza en textos, arrays y datos serializados (también la variante con barras escapadas de JSON: http:\/\/sitio).
function cu_reemplazar( $v, $a, $b ) {
	if ( is_array( $v ) ) {
		return array_map( fn( $x ) => cu_reemplazar( $x, $a, $b ), $v );
	}
	if ( ! is_string( $v ) ) {
		return $v;
	}
	if ( cu_serializado( $v ) ) {
		return serialize( cu_reemplazar( unserialize( $v, array( 'allowed_classes' => false ) ), $a, $b ) );
	}
	return str_replace( array( $a, str_replace( '/', '\/', $a ) ), array( $b, str_replace( '/', '\/', $b ) ), $v );
}

if ( in_array( '--autoprueba', $argv, true ) ) {
	$a = 'http://localhost:8000';
	$b = 'https://sitio.example.ec';
	$ok = fn( $x, $y ) => $x === $y || exit( "FALLO: " . var_export( $x, true ) . " != " . var_export( $y, true ) . "\n" );
	$ok( cu_reemplazar( 'ver http://localhost:8000/a y http://localhost:8000/b', $a, $b ), 'ver https://sitio.example.ec/a y https://sitio.example.ec/b' );
	$ok( cu_reemplazar( serialize( array( 'u' => 'http://localhost:8000/x', 'n' => array( 1, 'http://localhost:8000' ) ) ), $a, $b ), serialize( array( 'u' => 'https://sitio.example.ec/x', 'n' => array( 1, 'https://sitio.example.ec' ) ) ) ); // el largo de la cadena se recalcula
	$ok( cu_reemplazar( '{"url":"http:\/\/localhost:8000\/f.jpg"}', $a, $b ), '{"url":"https:\/\/sitio.example.ec\/f.jpg"}' );
	$ok( cu_reemplazar( 'a:1:{s:1:"x";s:3:"abc";}', $a, $b ), 'a:1:{s:1:"x";s:3:"abc";}' ); // sin la dirección, queda igual
	$ok( cu_reemplazar( 'texto con a:1: suelto', $a, $b ), 'texto con a:1: suelto' );
	echo "autoprueba: todo correcto\n";
	exit( 0 );
}

$args = array_values( array_filter( array_slice( $argv, 1 ), fn( $x ) => 0 !== strpos( $x, '--' ) ) );
$opt  = function ( $n ) use ( $argv ) { foreach ( $argv as $x ) { if ( 0 === strpos( $x, "--$n=" ) ) { return substr( $x, strlen( $n ) + 3 ); } } return ''; };
$seco = in_array( '--seco', $argv, true );
if ( count( $args ) < 2 || $args[0] === $args[1] ) {
	exit( "Uso: php cambiar-url.php URL_ACTUAL URL_NUEVA [--seco] [--wp=/ruta] [--bd=NOMBRE]\n" );
}
list( $a, $b ) = array( rtrim( $args[0], '/' ), rtrim( $args[1], '/' ) );
if ( ! preg_match( '#^https?://[^/\s]+$#', $b ) ) {
	exit( "La URL nueva debe ser como https://dominio (sin barra final ni ruta).\n" );
}

$ruta_wp = $opt( 'wp' ) ?: ( is_file( getcwd() . '/wp-load.php' ) ? getcwd() : dirname( __DIR__ ) . '/wordpress-7.1.3' ); // no usar $wp: es una variable global de WordPress
if ( ! is_file( "$ruta_wp/wp-load.php" ) ) {
	exit( "No encuentro wp-load.php en $ruta_wp. Usa --wp=/ruta/a/wordpress\n" );
}
$_SERVER['HTTP_HOST'] = parse_url( $a, PHP_URL_HOST ) ?: 'localhost';
require "$ruta_wp/wp-load.php";
global $wpdb;
if ( $opt( 'bd' ) ) {
	$wpdb->select( $opt( 'bd' ) ); // no devuelve nada: se comprueba preguntando qué base quedó en uso
	if ( $wpdb->get_var( 'SELECT DATABASE()' ) !== $opt( 'bd' ) ) {
		exit( "No puedo usar la base " . $opt( 'bd' ) . " (¿existe y el usuario de wp-config tiene permiso?)\n" );
	}
}

$tablas = array( // tabla, clave, columnas
	array( $wpdb->posts, 'ID', array( 'post_content', 'post_excerpt', 'post_title' ) ),
	array( $wpdb->postmeta, 'meta_id', array( 'meta_value' ) ),
	array( $wpdb->options, 'option_id', array( 'option_value' ) ),
	array( $wpdb->usermeta, 'umeta_id', array( 'meta_value' ) ),
	array( $wpdb->termmeta, 'meta_id', array( 'meta_value' ) ),
	array( $wpdb->term_taxonomy, 'term_taxonomy_id', array( 'description' ) ),
);
printf( "%s → %s %s\n", $a, $b, $seco ? '(en seco: no se escribe nada)' : '' );
$total = 0;
foreach ( $tablas as list( $t, $pk, $cols ) ) {
	$like  = '%' . $wpdb->esc_like( $a ) . '%';
	$where = implode( ' OR ', array_map( fn( $c ) => "$c LIKE %s", $cols ) );
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT $pk, " . implode( ',', $cols ) . " FROM $t WHERE $where", array_fill( 0, count( $cols ), $like ) ), ARRAY_A ); // tablas y columnas son fijas, no vienen del usuario
	$n     = 0;
	foreach ( $filas as $f ) {
		$cambios = array();
		foreach ( $cols as $c ) {
			$nuevo = cu_reemplazar( $f[ $c ], $a, $b );
			if ( $nuevo !== $f[ $c ] ) { $cambios[ $c ] = $nuevo; }
		}
		if ( $cambios && ! $seco ) { $wpdb->update( $t, $cambios, array( $pk => $f[ $pk ] ) ); }
		$n += $cambios ? 1 : 0;
	}
	printf( "  %-28s %d filas\n", $t, $n );
	$total += $n;
}
if ( ! $seco ) { wp_cache_flush(); }
echo "Total: $total filas" . ( $seco ? ' por cambiar' : ' cambiadas' ) . ".\n";
