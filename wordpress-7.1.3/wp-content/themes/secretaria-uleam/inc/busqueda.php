<?php
// Búsqueda del sitio y página 404. La búsqueda de WordPress solo mira páginas: aquí se añaden los documentos (PDF)
// reutilizando doc_api() de inc/documentos.php. Plantillas: search.php y 404.php. Textos editables en Personalizar (DOC_TEXTOS).

// La búsqueda del sitio solo devuelve páginas (los documentos se buscan aparte).
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$q->set( 'post_type', 'page' );
	}
} );

// Encabezado de las páginas internas (mismo aspecto que page.php): la última palabra va resaltada.
function uleam_hero( $titulo, $sub = '' ) {
	$t = explode( ' ', $titulo );
	$u = array_pop( $t );
	echo '<section class="pag-hero"><div class="wrap"><h1>' . esc_html( implode( ' ', $t ) ) . ' <span>' . esc_html( $u ) . '</span></h1>' . ( $sub ? '<p>' . esc_html( $sub ) . '</p>' : '' ) . '</div></section>';
}

// Documentos que coinciden con la búsqueda: total y las primeras 50 filas, cada una con su etiqueta de sección.
function uleam_buscar_docs( $q, $sec = '', $gr = '' ) {
	$rows = mb_strlen( $q ) >= 2 || $sec || $gr ? doc_api( array( 'q' => $q, 'seccion' => $sec, 'grupo' => $gr ) ) : array();
	$rows = is_wp_error( $rows ) ? array() : $rows;
	$et   = array(); // etiqueta por sección: "Órgano · Sección" si la sección es hija de un órgano
	$out  = array();
	foreach ( array_slice( $rows, 0, 50 ) as $r ) {
		if ( ! isset( $et[ $r['s'] ] ) ) {
			$t  = get_term_by( 'slug', $r['s'], 'seccion' );
			$p  = $t && $t->parent ? get_term( $t->parent, 'seccion' ) : null;
			$et[ $r['s'] ] = $t ? ( $p && ! is_wp_error( $p ) ? $p->name . ' · ' : '' ) . $t->name : '';
		}
		$out[] = $r + array( 'et' => $et[ $r['s'] ] );
	}
	return array( 'total' => count( $rows ), 'filas' => $out );
}
