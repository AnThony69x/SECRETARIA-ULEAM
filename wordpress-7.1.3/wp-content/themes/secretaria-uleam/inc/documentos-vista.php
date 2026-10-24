<?php
// Vista de documentos: shortcode [consejo] y páginas que lo usan. Datos, admin y API en inc/documentos.php.

// Textos de la interfaz, editables en Apariencia → Personalizar.
const DOC_TEXTOS = array(
	'doc_buscar' => array( 'Buscador de documentos (texto de ayuda)', 'Buscar por nombre, número o año (ej. 2021, resolución 105)…' ),
	'doc_ver'       => array( 'Botón para ver el documento', 'Ver' ),
	'doc_descargar' => array( 'Botón para descargar el documento', 'Descargar' ),
	'doc_vacio'  => array( 'Mensaje sin resultados', 'No se encontraron documentos con esos filtros.' ),
	'doc_mas'    => array( 'Botón para cargar más', 'Mostrar más' ),
	'doc_l_anio' => array( 'Etiqueta del filtro de año', 'Año o periodo' ),
	'doc_l_tipo' => array( 'Etiqueta del filtro de tipo', 'Tipo' ),
	'doc_limpiar' => array( 'Botón para limpiar filtros', 'Limpiar' ),
	'doc_l_orden'  => array( 'Etiqueta del orden', 'Orden' ),
	'doc_anio_act' => array( 'Etiqueta del año en curso (desplegado arriba)', 'Año en curso' ),
	'doc_anio_rec' => array( 'Etiqueta del año más reciente cuando aún no hay documentos del año en curso', 'Año más reciente' ),
	'doc_sin'      => array( 'Aviso de una sección sin documentos', 'Aún no hay documentos publicados en esta sección.' ),
	'doc_anios_ant' => array( 'Título de la lista de años anteriores al año en curso (recogidos)', 'Histórico' ),
	'hist_titulo'  => array( 'Archivo histórico: título del aviso', 'Archivo histórico' ),
	'hist_desc'    => array( 'Archivo histórico: descripción ({año} = año de corte)', 'Actas, resoluciones y documentos anteriores a {año}.' ),
	'hist_ver'     => array( 'Archivo histórico: botón para entrar', 'Ver archivo histórico' ),
	'hist_buscar'  => array( 'Archivo histórico: enlace cuando una búsqueda no encuentra nada', 'Buscar también en el archivo histórico' ),
	'hist_volver'  => array( 'Archivo histórico: botón para volver', 'Volver a los documentos recientes' ),
	'ci_ver'     => array( 'Botón para desplegar la integración del Consejo', 'Ver integrantes' ),
	'ci_ocultar' => array( 'Botón para recoger la integración del Consejo', 'Ocultar integrantes' ),
);
add_action( 'customize_register', function ( $c ) {
	foreach ( DOC_TEXTOS as $k => $v ) {
		$c->add_setting( "uleam_$k", array( 'default' => $v[1], 'sanitize_callback' => 'sanitize_text_field' ) );
		$c->add_control( "uleam_$k", array( 'section' => 'uleam', 'label' => $v[0], 'type' => 'text' ) );
	}
}, 20 );

function doc_txt( $k ) {
	return get_theme_mod( "uleam_$k", DOC_TEXTOS[ $k ][1] ) ?: DOC_TEXTOS[ $k ][1];
}

// Página única: [consejo] (JS en assets/js/documentos*.js). Las pestañas son las secciones con documentos (Documentos → Secciones:
// nombre, descripción, orden e icono). Opcional: [consejo secciones="slug,slug"] para elegir cuáles y en qué orden;
// [consejo organo="slug-padre"] para las secciones hijas de un órgano (ej. Consejo de Postgrado), incluidas las vacías;
// vista="historico" (años anteriores al corte) o vista="todo" (sin corte, todos los años recogidos).
add_shortcode( 'consejo', function ( $a ) {
	$a     = shortcode_atts( array( 'secciones' => '', 'vista' => 'actual', 'organo' => '' ), $a );
	$vista = in_array( $a['vista'], array( 'historico', 'todo' ), true ) ? $a['vista'] : 'actual';
	if ( $a['secciones'] ) {
		$terms = array_values( array_filter( array_map( fn( $s ) => get_term_by( 'slug', trim( $s ), 'seccion' ), explode( ',', $a['secciones'] ) ) ) );
	} else {
		if ( $a['organo'] ) {
			$padre = get_term_by( 'slug', sanitize_title( $a['organo'] ), 'seccion' );
			$terms = $padre ? get_terms( array( 'taxonomy' => 'seccion', 'hide_empty' => false, 'parent' => $padre->term_id ) ) : array();
		} else { // primer nivel sin hijas: los términos padre (órganos) agrupan secciones de otras páginas
			$terms = array_filter( get_terms( array( 'taxonomy' => 'seccion', 'hide_empty' => true, 'parent' => 0 ) ), fn( $t ) => ! get_term_children( $t->term_id, 'seccion' ) );
		}
		$terms = is_wp_error( $terms ) ? array() : $terms;
		usort( $terms, fn( $x, $y ) => ( (int) ( get_term_meta( $x->term_id, 'orden', true ) ?: 99 ) <=> (int) ( get_term_meta( $y->term_id, 'orden', true ) ?: 99 ) ) ?: strcmp( $x->name, $y->name ) );
	}
	$todas = $terms;
	$res = array();
	foreach ( doc_resumen() as $sl => $gs ) {
		$res[ $sl ] = array_filter( $gs, fn( $g ) => doc_en_vista( $g, $vista ), ARRAY_FILTER_USE_KEY );
	}
	if ( ! $a['organo'] ) { // en un órgano se ven también las secciones vacías, con su aviso
		$terms = array_values( array_filter( $terms, fn( $t ) => ! empty( $res[ $t->slug ] ) ) );
	}
	// Aviso que enlaza con la otra vista (archivo histórico ↔ documentos recientes).
	$corte  = doc_corte();
	$pag    = (int) get_theme_mod( 'uleam_doc_hist_pagina', 0 ) ?: (int) get_option( 'uleam_doc_hist_pagina_id' );
	$aviso  = '';
	$hist_url = '';
	if ( 'actual' === $vista && $pag && 'publish' === get_post_status( $pag ) ) {
		$hay = 0;
		foreach ( $todas as $t ) { // solo las secciones de esta página
			foreach ( doc_resumen()[ $t->slug ] ?? array() as $g => $c ) {
				$hay += doc_en_vista( $g, 'historico' ) ? array_sum( $c ) : 0;
			}
		}
		$hist_url = get_permalink( $pag );
		if ( $hay ) {
			$aviso = '<a class="doc-hist" href="' . esc_url( get_permalink( $pag ) ) . '"><span class="icono suave">' . uleam_icon( 'archive' ) . '</span><span><b>' . esc_html( doc_txt( 'hist_titulo' ) ) . '</b><small>' . esc_html( str_replace( '{año}', $corte, doc_txt( 'hist_desc' ) ) ) . ' ' . $hay . ' documentos.</small></span><span class="doc-hist-btn">' . esc_html( doc_txt( 'hist_ver' ) ) . uleam_icon( 'arrow-right' ) . '</span></a>';
		}
	} elseif ( 'historico' === $vista && ( $padre = wp_get_post_parent_id( get_the_ID() ) ) ) {
		$aviso = '<a class="doc-hist doc-hist-volver" href="' . esc_url( get_permalink( $padre ) ) . '"><span class="doc-hist-btn">' . uleam_icon( 'chevron-left' ) . esc_html( doc_txt( 'hist_volver' ) ) . '</span></a>';
	}
	$o   = '<div class="docs-todo" data-vista="' . $vista . '" data-t-actual="' . esc_attr( doc_txt( 'doc_anio_act' ) ) . '" data-t-ant="' . esc_attr( doc_txt( 'doc_anios_ant' ) ) . '" data-t-rec="' . esc_attr( doc_txt( 'doc_anio_rec' ) ) . '" data-mes2="' . (int) get_theme_mod( 'uleam_doc_mes_p2', 7 ) . '" data-t-sin="' . esc_attr( doc_txt( 'doc_sin' ) ) . '" data-api="' . esc_url( rest_url( 'uleam/v1/documentos' ) ) . '">';
	$o  .= 'historico' === $vista ? $aviso : ''; // "Volver" arriba en el archivo histórico
	$o  .= '<template class="doc-tpl"><li class="doc-fila"><span class="icono">' . uleam_icon( 'file-text' ) . '</span><span class="doc-t"><span></span><small></small></span>'
		. '<span class="doc-acciones"><a class="btn pq doc-ver" target="_blank" rel="noopener">' . uleam_icon( 'eye' ) . '<span>' . esc_html( doc_txt( 'doc_ver' ) ) . '</span></a>'
		. '<a class="btn azul pq doc-bajar" download>' . uleam_icon( 'download' ) . '<span>' . esc_html( doc_txt( 'doc_descargar' ) ) . '</span></a></span></li></template>';
	$o .= '<div class="doc-tabs" role="tablist" aria-label="Apartados">';
	foreach ( $terms as $i => $t ) {
		$o .= '<button type="button" role="tab" id="tab-' . esc_attr( $t->slug ) . '" aria-controls="' . esc_attr( $t->slug ) . '" aria-selected="false" tabindex="-1" data-nombre="' . esc_attr( $t->name ) . '">'
			. '<span class="doc-tab-num">' . sprintf( '%02d', $i + 1 ) . '</span><span class="icono suave">' . uleam_icon( doc_icono( $t ) ) . '</span>'
			. '<span class="doc-tab-txt"><b>' . esc_html( $t->name ) . '</b>' . ( $t->description ? '<small>' . esc_html( $t->description ) . '</small>' : '' ) . '<span class="doc-tab-n"></span></span></button>';
	}
	$o .= '</div>';
	$o .= '<div class="doc-herramientas"><label class="doc-buscar">' . uleam_icon( 'search' ) . '<span class="sr">Buscar documento por nombre, número o año</span><input type="search" placeholder="' . esc_attr( doc_txt( 'doc_buscar' ) ) . '"></label>';
	$o .= '<label class="doc-anio"><span class="doc-lbl">' . esc_html( doc_txt( 'doc_l_anio' ) ) . '</span><select></select></label><label class="doc-tipo"><span class="doc-lbl">' . esc_html( doc_txt( 'doc_l_tipo' ) ) . '</span><select><option value="">Todos los tipos</option><option value="acta">Solo actas</option><option value="resolucion">Solo resoluciones</option><option value="otro">Otros (anexos, reglamentos…)</option></select></label>'
		. '<label class="doc-orden"><span class="doc-lbl">' . esc_html( doc_txt( 'doc_l_orden' ) ) . '</span><select><option value="">Recientes primero</option><option value="asc">Antiguos primero</option></select></label><button type="button" class="doc-limpiar" hidden><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' . esc_html( doc_txt( 'doc_limpiar' ) ) . '</button></div>';
	$o .= '<div class="doc-activos" aria-label="Filtros activos"></div><p class="doc-contador" role="status" aria-live="polite"></p>';
	foreach ( $terms as $t ) {
		$gs   = $res[ $t->slug ] ?? array();
		$g0   = $gs && ( count( $gs ) > 2 || 0 === strpos( array_key_first( $gs ), 'Año' ) ) ? array_key_first( $gs ) : ''; // grupos por año: vista por años
		$ini  = array_values( array_filter( doc_filas( array( 'tax_query' => doc_tax( $t->slug ) ) + ( $g0 ? array( 'meta_query' => array( array( 'key' => 'grupo', 'value' => $g0 ) ) ) : array() ) ), fn( $f ) => doc_en_vista( $f['g'], $vista ) ) );
		$o   .= '<section class="doc-seccion" id="' . esc_attr( $t->slug ) . '" role="tabpanel" aria-labelledby="tab-' . esc_attr( $t->slug ) . '" hidden'
			. ' data-g0="' . esc_attr( $g0 ) . '" data-resumen="' . esc_attr( wp_json_encode( array_map( null, array_keys( $gs ), array_values( $gs ) ) ) ) . '" data-ini="' . esc_attr( wp_json_encode( $ini ) ) . '">'
			. '<div class="doc-lista"><ul class="doc-filas"></ul><button type="button" class="btn pq doc-mas" data-txt="' . esc_attr( doc_txt( 'doc_mas' ) ) . '" hidden></button></div></section>';
	}
	return $o . '<p class="doc-vacio" hidden>' . esc_html( doc_txt( 'doc_vacio' ) ) . ( $hist_url ? ' <a class="doc-vacio-hist" href="' . esc_url( $hist_url ) . '">' . esc_html( doc_txt( 'hist_buscar' ) ) . '</a>' : '' ) . '</p>' . ( 'actual' === $vista ? $aviso : '' ) . '</div>';
} );

// Crea una sola vez la página; después es editable como cualquier página. Retira las páginas hijas de la versión anterior.
add_action( 'admin_init', function () {
	if ( get_option( 'uleam_docs_paginas_v2' ) ) {
		return;
	}
	$cu = get_page_by_path( 'consejo-universitario' );
	foreach ( array( 'sesiones-ordinarias', 'sesiones-extraordinarias' ) as $r ) {
		if ( $p = get_page_by_path( "consejo-universitario/actas-y-resoluciones/$r" ) ) {
			wp_trash_post( $p->ID );
		}
	}
	foreach ( array( 'consejo-universitario/miembros', 'consejo-universitario/actas-y-resoluciones' ) as $r ) {
		if ( $p = get_page_by_path( $r ) ) {
			wp_trash_post( $p->ID );
		}
	}
	$datos = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Consejo Universitario',
		'post_name'    => 'consejo-universitario',
		'post_excerpt' => 'Integración del Órgano Colegiado Académico Superior',
		'post_content' => '<!-- wp:html --><p>El Órgano Colegiado Académico Superior será el Consejo Universitario y estará integrado por: el Rector(a), quien lo presidirá con voz y voto dirimente en caso de empate; dos Vicerrectores (Académico y Administrativo); diecisiete Decanos/as de Facultades y Extensiones; quince representantes de los profesores titulares o investigadores; ocho representantes de los estudiantes; un representante de los servidores y trabajadores; y un representante de los graduados.</p><!-- /wp:html -->[consejo]',
	);
	if ( $cu ) {
		wp_update_post( array( 'ID' => $cu->ID ) + $datos );
	} else {
		wp_insert_post( $datos );
	}
	update_option( 'uleam_docs_paginas_v2', 1 );
} );

// Crea una sola vez la página del archivo histórico (hija de Consejo Universitario); luego es editable.
add_action( 'admin_init', function () {
	if ( get_option( 'uleam_doc_hist_pagina_id' ) || ! ( $padre = get_page_by_path( 'consejo-universitario' ) ) ) {
		return;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Archivo Histórico del Consejo Universitario',
		'post_name'    => 'archivo-historico',
		'post_parent'  => $padre->ID,
		'post_excerpt' => 'Actas y resoluciones de años anteriores',
		'post_content' => "<!-- wp:shortcode -->\n[consejo vista=\"historico\"]\n<!-- /wp:shortcode -->",
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'uleam_doc_hist_pagina_id', $id );
	}
} );

// Textos del botón que recoge/despliega el bloque "consejo-intro" (lo arma assets/main.js).
add_action( 'wp_enqueue_scripts', function () {
	wp_add_inline_script( 'secretaria-uleam', 'var ULEAM_CI=' . wp_json_encode( array( 'ver' => doc_txt( 'ci_ver' ), 'ocultar' => doc_txt( 'ci_ocultar' ) ) ) . ';', 'before' );
}, 20 );

// Marca <html class="js"> antes de pintar, para que el bloque recogido no se vea desplegado un instante al cargar.
add_action( 'wp_head', function () {
	echo "<script>document.documentElement.classList.add('js')</script>\n";
}, 1 );
