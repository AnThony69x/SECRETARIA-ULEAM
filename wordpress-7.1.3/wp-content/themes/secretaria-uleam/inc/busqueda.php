<?php
// Búsqueda del sitio y página 404. La búsqueda de WordPress solo mira páginas: aquí se añaden los documentos (PDF)
// reutilizando doc_api() de inc/documentos.php. Plantillas: search.php y 404.php. Textos editables en Personalizar (DOC_TEXTOS).

// La búsqueda del sitio solo devuelve páginas (los documentos se buscan aparte).
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$q->set( 'post_type', 'page' );
	}
} );

// Encabezado de las páginas internas (mismo aspecto que page.php): la última palabra va resaltada; $chips = [[icono, texto], …].
function uleam_hero( $titulo, $sub = '', $chips = array() ) {
	$t = explode( ' ', $titulo );
	$u = array_pop( $t );
	echo '<section class="pag-hero"><div class="wrap"><h1>' . esc_html( implode( ' ', $t ) ) . ' <span>' . esc_html( $u ) . '</span></h1>' . ( $sub ? '<p>' . esc_html( $sub ) . '</p>' : '' )
		. ( $chips ? '<div class="chips">' . implode( '', array_map( fn( $c ) => '<span>' . uleam_icon( $c[0] ) . esc_html( $c[1] ) . '</span>', $chips ) ) . '</div>' : '' ) . '</div></section>';
}

// Documentos que coinciden con la búsqueda: total y las primeras N filas (Personalizar), cada una con su etiqueta de sección.
function uleam_buscar_docs( $q, $sec = '', $gr = '' ) {
	$rows = mb_strlen( $q ) >= 2 || $sec || $gr ? doc_api( array( 'q' => $q, 'seccion' => $sec, 'grupo' => $gr ) ) : array();
	$rows = is_wp_error( $rows ) ? array() : $rows;
	// Con texto, doc_api busca en todo el sitio: aquí se aplican el apartado y el año elegidos.
	$hijas = array( $sec ); // el apartado puede ser un órgano completo (enlace "Explorar todo"): vale también cualquiera de sus secciones
	if ( $sec && ( $t = get_term_by( 'slug', $sec, 'seccion' ) ) ) {
		$hijas = array_merge( $hijas, wp_list_pluck( (array) get_terms( array( 'taxonomy' => 'seccion', 'parent' => $t->term_id, 'hide_empty' => false ) ), 'slug' ) );
	}
	$rows = array_values( array_filter( $rows, fn( $r ) => ( ! $sec || in_array( $r['s'], $hijas, true ) ) && ( ! $gr || $r['g'] === $gr ) ) );
	$et   = array(); // etiqueta por sección: "Órgano · Sección" si la sección es hija de un órgano
	$out  = array();
	foreach ( array_slice( $rows, 0, min( 500, max( 10, (int) get_theme_mod( 'uleam_bus_max', 50 ) ) ) ) as $r ) {
		if ( ! isset( $et[ $r['s'] ] ) ) {
			$t  = get_term_by( 'slug', $r['s'], 'seccion' );
			$p  = $t && $t->parent ? get_term( $t->parent, 'seccion' ) : null;
			$et[ $r['s'] ] = $t ? ( $p && ! is_wp_error( $p ) ? $p->name . ' · ' : '' ) . $t->name : '';
		}
		$out[] = $r + array( 'et' => $et[ $r['s'] ] );
	}
	return array( 'total' => count( $rows ), 'filas' => $out );
}
