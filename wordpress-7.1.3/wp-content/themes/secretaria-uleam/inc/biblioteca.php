<?php
// [biblioteca organo="archivo-general" pagina="archivo-general"]: tarjeta "Biblioteca" con buscador (texto + apartado + año) que abre la página de
// resultados de búsqueda del sitio (?s=&a=&g=, ver inc/busqueda.php) y la lista de documentos recientes. Los apartados son las secciones del
// órgano (Documentos → Secciones) y los años salen de los periodos "Año …" de sus documentos.

add_shortcode( 'biblioteca', function ( $a ) {
	$a     = shortcode_atts( array( 'organo' => '', 'pagina' => '', 'titulo' => 'Biblioteca de archivos', 'enlace' => 'Explorar todo el archivo', 'buscar' => 'Nombre o número del documento…', 'boton' => 'Buscar', 'recientes' => 8 ), $a );
	$padre = get_term_by( 'slug', sanitize_title( $a['organo'] ), 'seccion' );
	$pag   = get_page_by_path( sanitize_title( $a['pagina'] ) );
	$terms = $padre ? get_terms( array( 'taxonomy' => 'seccion', 'hide_empty' => false, 'parent' => $padre->term_id ) ) : array();
	if ( is_wp_error( $terms ) || ! $terms ) {
		return current_user_can( 'edit_posts' ) ? '<p class="doc-vacio">Indica un órgano con secciones: [biblioteca organo="..." pagina="..."].</p>' : '';
	}
	usort( $terms, fn( $x, $y ) => ( (int) ( get_term_meta( $x->term_id, 'orden', true ) ?: 99 ) <=> (int) ( get_term_meta( $y->term_id, 'orden', true ) ?: 99 ) ) ?: strcmp( $x->name, $y->name ) );
	$res    = doc_resumen();
	$url    = $pag ? get_permalink( $pag ) : '';
	$anios  = array(); // por sección, y la unión para "todos los años"
	$nombre = array();
	$tipos  = '';
	foreach ( $terms as $t ) {
		$anios[ $t->slug ]  = array_values( array_filter( array_keys( $res[ $t->slug ] ?? array() ), fn( $g ) => 0 === strpos( $g, 'Año' ) ) );
		$nombre[ $t->slug ] = $t->name;
		$tipos             .= '<option value="' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . '</option>';
	}
	$todos = array_unique( array_merge( ...array_values( $anios ) ) );
	rsort( $todos, SORT_NATURAL );
	$opt_anios = '';
	foreach ( $todos as $g ) {
		$opt_anios .= '<option value="' . esc_attr( $g ) . '">' . esc_html( preg_replace( '/^Año\s*/u', '', $g ) ) . '</option>';
	}
	// Recientes: primero los de periodos por año (más nuevo antes), luego el resto; cada uno abre su archivo.
	$filas = array_filter( doc_filas( array( 'tax_query' => array( array( 'taxonomy' => 'seccion', 'field' => 'term_id', 'terms' => wp_list_pluck( $terms, 'term_id' ) ) ) ) ), fn( $f ) => isset( $nombre[ $f['s'] ] ) );
	usort( $filas, fn( $x, $y ) => (int) ( 0 === strpos( $y['g'], 'Año' ) ) <=> (int) ( 0 === strpos( $x['g'], 'Año' ) ) ); // estable: conserva el orden por periodo
	$rec = '';
	foreach ( array_slice( $filas, 0, max( 0, (int) $a['recientes'] ) ) as $f ) {
		$rec .= '<li><a class="bib-doc" href="' . esc_url( $f['u'] ) . '" target="_blank" rel="noopener">' . doc_arch( $f['u'] )
			. '<span class="bib-dt"><b>' . esc_html( $f['ti'] ) . '</b><small>' . esc_html( $nombre[ $f['s'] ] . ( 0 === strpos( $f['g'], 'Año' ) ? ' · ' . preg_replace( '/^Año\s*/u', '', $f['g'] ) : '' ) ) . '</small></span></a></li>';
	}
	return '<form class="bib" action="' . esc_url( home_url( '/' ) ) . '" method="get" role="search" aria-label="Buscar en la biblioteca de archivos" data-anios="' . esc_attr( wp_json_encode( $anios ) ) . '">'
		. '<div class="bib-cab"><h3>' . esc_html( $a['titulo'] ) . '</h3>' . ( $padre ? '<a class="bib-todo" href="' . esc_url( add_query_arg( array( 's' => '', 'a' => $padre->slug ), home_url( '/' ) ) ) . '">' . esc_html( $a['enlace'] ) . uleam_icon( 'arrow-right' ) . '</a>' : '' ) . '</div>'
		. '<div class="bib-campos"><label class="bib-buscar">' . uleam_icon( 'search' ) . '<span class="sr">Buscar documento por nombre o número</span><input type="search" name="s" placeholder="' . esc_attr( $a['buscar'] ) . '"></label>'
		. '<label class="bib-campo"><span class="sr">Apartado</span><select name="a"><option value="">Todos los apartados</option>' . $tipos . '</select></label>'
		. '<label class="bib-campo"><span class="sr">Año</span><select name="g"><option value="">Todos los años</option>' . $opt_anios . '</select></label>'
		. '<button type="submit" class="btn azul bib-ir">' . uleam_icon( 'search' ) . esc_html( $a['boton'] ) . '</button></div>'
		. ( $rec ? '<p class="bib-lbl">Documentos recientes</p><ul class="bib-rec">' . $rec . '</ul>' : '' ) . '</form>';
} );
