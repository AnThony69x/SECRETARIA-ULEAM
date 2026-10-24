<?php
// Documentos PDF agrupados por periodo/año. Se suben en el admin "Documentos" y se muestran con [consejo].

const DOC_SECCIONES = array(
	'miembros'              => 'Miembros',
	'sesiones-ordinarias'   => 'Sesiones Ordinarias',
	'sesiones-extraordinarias' => 'Sesiones Extraordinarias',
);

add_action( 'init', function () {
	register_post_type( 'documento', array(
		'labels'    => array( 'name' => 'Documentos', 'singular_name' => 'Documento', 'add_new_item' => 'Añadir documento' ),
		'public'    => false,
		'show_ui'   => true,
		'menu_icon' => 'dashicons-media-document',
		'supports'  => array( 'title' ),
	) );
	register_taxonomy( 'seccion', 'documento', array(
		'labels'            => array( 'name' => 'Secciones', 'singular_name' => 'Sección' ),
		'hierarchical'      => true,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
	) );
	foreach ( DOC_SECCIONES as $slug => $nombre ) {
		if ( ! term_exists( $slug, 'seccion' ) ) {
			wp_insert_term( $nombre, 'seccion', array( 'slug' => $slug ) );
		}
	}
} );

function doc_url( $id ) {
	$pdf = (int) get_post_meta( $id, 'pdf_id', true );
	$url = $pdf ? wp_get_attachment_url( $pdf ) : '';
	if ( ! $url && ( $rel = get_post_meta( $id, 'pdf_ruta', true ) ) ) { // importados: ruta dentro de uploads
		$url = content_url( 'uploads/' . $rel );
	}
	return $url;
}

// Listado del admin: columnas Periodo/Año y PDF, y filtro por sección (son ~2.600 documentos).
add_filter( 'manage_documento_posts_columns', function ( $c ) {
	return array_slice( $c, 0, 2 ) + array( 'grupo' => 'Periodo / Año', 'pdf' => 'PDF' ) + $c;
} );
add_action( 'manage_documento_posts_custom_column', function ( $col, $id ) {
	if ( 'grupo' === $col ) {
		echo esc_html( get_post_meta( $id, 'grupo', true ) );
	} elseif ( 'pdf' === $col && ( $u = doc_url( $id ) ) ) {
		echo '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">Ver</a>';
	}
}, 10, 2 );
add_action( 'restrict_manage_posts', function ( $tipo ) {
	if ( 'documento' === $tipo ) {
		wp_dropdown_categories( array( 'taxonomy' => 'seccion', 'name' => 'seccion', 'value_field' => 'slug', 'show_option_none' => 'Todas las secciones', 'option_none_value' => '', 'selected' => sanitize_key( $_GET['seccion'] ?? '' ), 'hide_empty' => false ) );
	}
} );

// Campos extra de cada sección (pestaña): orden e icono.
const DOC_ICONOS_LISTA = array( 'users' => 'Personas', 'file-text' => 'Documento', 'scale' => 'Balanza', 'folder' => 'Carpeta', 'archive' => 'Archivo', 'calendar' => 'Calendario', 'graduation-cap' => 'Birrete', 'shield' => 'Escudo', 'star' => 'Estrella', 'bar-chart' => 'Gráfico', 'target' => 'Objetivo', 'eye' => 'Ojo' );

function doc_seccion_campos( $term = null ) {
	$orden = $term ? get_term_meta( $term->term_id, 'orden', true ) : '';
	$icono = $term ? doc_icono( $term ) : 'folder';
	$sel   = '';
	foreach ( DOC_ICONOS_LISTA as $k => $l ) {
		$sel .= '<option value="' . esc_attr( $k ) . '"' . selected( $icono, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$f = array(
		'Orden de la pestaña' => '<input type="number" name="doc_orden" value="' . esc_attr( $orden ) . '" min="0" step="1"><p class="description">Menor número = aparece primero. Las secciones sin documentos no se muestran.</p>',
		'Icono'               => '<select name="doc_icono">' . $sel . '</select>',
	);
	foreach ( $f as $label => $campo ) {
		echo $term ? '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td>' . $campo . '</td></tr>' : '<div class="form-field"><label>' . esc_html( $label ) . '</label>' . $campo . '</div>';
	}
}
add_action( 'seccion_add_form_fields', function () { doc_seccion_campos(); } );
add_action( 'seccion_edit_form_fields', 'doc_seccion_campos' );
foreach ( array( 'created_seccion', 'edited_seccion' ) as $h ) {
	add_action( $h, function ( $id ) { // el formulario de términos ya verifica nonce y permisos
		if ( isset( $_POST['doc_orden'] ) ) {
			update_term_meta( $id, 'orden', '' === $_POST['doc_orden'] ? '' : absint( $_POST['doc_orden'] ) );
		}
		if ( isset( $_POST['doc_icono'] ) && isset( DOC_ICONOS_LISTA[ $_POST['doc_icono'] ] ) ) {
			update_term_meta( $id, 'icono', $_POST['doc_icono'] );
		}
	} );
}

function doc_icono( $t ) {
	$i = get_term_meta( $t->term_id, 'icono', true );
	return isset( DOC_ICONOS_LISTA[ $i ] ) ? $i : ( array( 'miembros' => 'users', 'sesiones-ordinarias' => 'file-text', 'sesiones-extraordinarias' => 'scale' )[ $t->slug ] ?? 'folder' );
}

// Tipo de documento (filtro "Solo actas / Solo resoluciones"). Vacío = se deduce del título.
const DOC_TIPOS = array( '' => 'Automático (según el título)', 'acta' => 'Acta', 'resolucion' => 'Resolución', 'otro' => 'Otro' );

function doc_tipo( $d ) {
	$t = get_post_meta( $d->ID, 'tipo', true );
	return $t ?: ( false !== stripos( $d->post_title, 'resoluc' ) ? 'resolucion' : ( false !== stripos( $d->post_title, 'acta' ) ? 'acta' : 'otro' ) );
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'doc_datos', 'Archivo y periodo', function ( $post ) {
		wp_nonce_field( 'doc_datos', 'doc_nonce' );
		wp_enqueue_media();
		$id  = (int) get_post_meta( $post->ID, 'pdf_id', true );
		$url = doc_url( $post->ID );
		global $wpdb;
		$gs = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'grupo' AND meta_value <> '' ORDER BY meta_value DESC" );
		echo '<p><label><strong>Periodo o año</strong> (ej. "Periodo 2018 - 2020" o "Año 2021"). Agrupa los documentos en los filtros de la página.<br><input type="text" name="grupo" list="doc_grupos" value="' . esc_attr( get_post_meta( $post->ID, 'grupo', true ) ) . '" style="width:100%" required></label></p>';
		echo '<datalist id="doc_grupos">';
		foreach ( $gs as $g ) {
			echo '<option value="' . esc_attr( $g ) . '">';
		}
		echo '</datalist>';
		echo '<p><label><strong>Tipo</strong><br><select name="tipo">';
		foreach ( DOC_TIPOS as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( get_post_meta( $post->ID, 'tipo', true ), $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><strong>PDF</strong><br><input type="hidden" name="pdf_id" id="pdf_id" value="' . esc_attr( $id ) . '">';
		echo '<span id="pdf_nombre">' . ( $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( basename( $url ) ) . '</a>' : 'Ninguno' ) . '</span> ';
		echo '<button type="button" class="button" id="pdf_btn">' . ( $url ? 'Reemplazar PDF' : 'Seleccionar / subir PDF' ) . '</button></p>';
		echo '<script>document.getElementById("pdf_btn").onclick=function(){var m=wp.media({library:{type:"application/pdf"},multiple:false});m.on("select",function(){var a=m.state().get("selection").first().toJSON();document.getElementById("pdf_id").value=a.id;document.getElementById("pdf_nombre").textContent=a.filename;});m.open();};</script>';
	}, 'documento' );
} );

add_action( 'save_post_documento', function ( $id ) {
	if ( ! isset( $_POST['doc_nonce'] ) || ! wp_verify_nonce( $_POST['doc_nonce'], 'doc_datos' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'grupo', sanitize_text_field( wp_unslash( $_POST['grupo'] ?? '' ) ) );
	update_post_meta( $id, 'pdf_id', absint( $_POST['pdf_id'] ?? 0 ) );
	$tipo = sanitize_key( $_POST['tipo'] ?? '' );
	update_post_meta( $id, 'tipo', isset( DOC_TIPOS[ $tipo ] ) ? $tipo : '' );
} );

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
add_action( 'customize_register', function ( $c ) {
	$c->add_setting( 'uleam_doc_corte', array( 'default' => 2018, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'uleam_doc_corte', array( 'section' => 'uleam', 'label' => 'Archivo histórico: los años anteriores a este van a la página histórica', 'type' => 'number' ) );
	$c->add_setting( 'uleam_doc_mes_p2', array( 'default' => 7, 'sanitize_callback' => fn( $v ) => min( 12, max( 2, absint( $v ) ) ) ) );
	$c->add_control( 'uleam_doc_mes_p2', array( 'section' => 'uleam', 'label' => 'Documentos: mes en que empieza el periodo 2 de cada año (1 = enero … 12 = diciembre)', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ) );
	$c->add_setting( 'uleam_doc_hist_pagina', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'uleam_doc_hist_pagina', array( 'section' => 'uleam', 'label' => 'Archivo histórico: página donde se muestra', 'type' => 'dropdown-pages' ) );
}, 21 );

// Vista "actual" (año >= corte) o "historico" (año < corte). El año de un periodo es el mayor que contiene ("2016 - 2018" → 2018).
function doc_en_vista( $g, $vista ) {
	if ( 'actual' !== $vista && 'historico' !== $vista ) {
		return true;
	}
	preg_match_all( '/\d{4}/', $g, $m );
	$y = $m[0] ? max( array_map( 'intval', $m[0] ) ) : 0;
	$h = $y && $y < (int) get_theme_mod( 'uleam_doc_corte', 2018 );
	return 'historico' === $vista ? $h : ! $h;
}

function doc_txt( $k ) {
	return get_theme_mod( "uleam_$k", DOC_TEXTOS[ $k ][1] ) ?: DOC_TEXTOS[ $k ][1];
}

// --- Carga bajo demanda: la página solo trae el año inicial de cada pestaña; el resto llega por la API. ---

// Filas compactas para el navegador: s = sección, g = periodo/año, t = tipo, ti = título, u = URL del PDF.
function doc_filas( $args ) {
	$docs = get_posts( $args + array( 'post_type' => 'documento', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true ) );
	$out  = array();
	foreach ( $docs as $d ) {
		$u = doc_url( $d->ID );
		$s = get_the_terms( $d, 'seccion' ); // usa la caché que get_posts ya cargó
		if ( $u && $s && ! is_wp_error( $s ) ) {
			$out[] = array( 's' => $s[0]->slug, 'g' => get_post_meta( $d->ID, 'grupo', true ) ?: 'Sin periodo', 't' => doc_tipo( $d ), 'ti' => get_the_title( $d ), 'u' => $u );
		}
	}
	usort( $out, fn( $x, $y ) => strnatcasecmp( $y['g'], $x['g'] ) ?: strnatcasecmp( $x['ti'], $y['ti'] ) ); // más reciente primero
	return $out;
}

function doc_tax( $slug ) {
	return array( array( 'taxonomy' => 'seccion', 'field' => 'slug', 'terms' => $slug ) );
}

// Conteos por sección → periodo → tipo (para pestañas y selector sin cargar las filas). En caché hasta que cambie un documento.
function doc_resumen() {
	$r = get_transient( 'uleam_doc_resumen' );
	if ( false === $r ) {
		$r = array();
		foreach ( doc_filas( array() ) as $f ) {
			$r[ $f['s'] ][ $f['g'] ][ $f['t'] ] = ( $r[ $f['s'] ][ $f['g'] ][ $f['t'] ] ?? 0 ) + 1;
		}
		foreach ( $r as &$gs ) {
			uksort( $gs, fn( $x, $y ) => strnatcasecmp( $y, $x ) );
		}
		set_transient( 'uleam_doc_resumen', $r, DAY_IN_SECONDS );
	}
	return $r;
}
foreach ( array( 'save_post_documento', 'deleted_post', 'trashed_post', 'untrashed_post', 'edited_seccion' ) as $h ) {
	add_action( $h, fn() => delete_transient( 'uleam_doc_resumen' ) );
}

// GET /wp-json/uleam/v1/documentos?seccion=slug[&grupo=Año 2021]  o  ?q=texto (busca en todas las secciones).
add_action( 'rest_api_init', function () {
	register_rest_route( 'uleam/v1', '/documentos', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true', // documentos públicos
		'args'                => array(
			'seccion' => array( 'sanitize_callback' => 'sanitize_title' ),
			'grupo'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'q'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'vista'   => array( 'sanitize_callback' => 'sanitize_key' ),
		),
		'callback'            => function ( $req ) {
			return array_values( array_filter( doc_api( $req ), fn( $f ) => doc_en_vista( $f['g'], (string) $req['vista'] ) ) );
		},
	) );
} );

function doc_api( $req ) {
			$q = trim( (string) $req['q'] );
			if ( '' !== $q ) {
				if ( mb_strlen( $q ) < 2 ) {
					return array();
				}
				// Título/contenido o el periodo (ej. "2021" encuentra todo el "Año 2021").
				$por_texto = doc_filas( array( 's' => $q ) );
				$por_grupo = doc_filas( array( 'meta_query' => array( array( 'key' => 'grupo', 'value' => $q, 'compare' => 'LIKE' ) ) ) );
				$todo      = array();
				foreach ( array_merge( $por_texto, $por_grupo ) as $f ) {
					$todo[ $f['u'] ] = $f;
				}
				$todo = array_values( $todo );
				usort( $todo, fn( $x, $y ) => strnatcasecmp( $y['g'], $x['g'] ) ?: strnatcasecmp( $x['ti'], $y['ti'] ) );
				return $todo;
			}
			if ( ! $req['seccion'] ) {
				return new WP_Error( 'falta_seccion', 'Indique seccion o q.', array( 'status' => 400 ) );
			}
			$args = array( 'tax_query' => doc_tax( $req['seccion'] ) );
			if ( $req['grupo'] ) {
				$args['meta_query'] = array( array( 'key' => 'grupo', 'value' => $req['grupo'] ) );
			}
			return doc_filas( $args );
}

// Página única: [consejo] (JS en assets/main.js). Las pestañas son las secciones con documentos (Documentos → Secciones: nombre,
// descripción, orden e icono). Opcional: [consejo secciones="slug,slug"] para elegir cuáles y en qué orden.
add_shortcode( 'consejo', function ( $a ) {
	$a     = shortcode_atts( array( 'secciones' => '', 'vista' => 'actual' ), $a );
	$vista = 'historico' === $a['vista'] ? 'historico' : 'actual';
	if ( $a['secciones'] ) {
		$terms = array_values( array_filter( array_map( fn( $s ) => get_term_by( 'slug', trim( $s ), 'seccion' ), explode( ',', $a['secciones'] ) ) ) );
	} else {
		$terms = get_terms( array( 'taxonomy' => 'seccion', 'hide_empty' => true ) );
		usort( $terms, fn( $x, $y ) => ( (int) ( get_term_meta( $x->term_id, 'orden', true ) ?: 99 ) <=> (int) ( get_term_meta( $y->term_id, 'orden', true ) ?: 99 ) ) ?: strcmp( $x->name, $y->name ) );
	}
	$res = array();
	foreach ( doc_resumen() as $sl => $gs ) {
		$res[ $sl ] = array_filter( $gs, fn( $g ) => doc_en_vista( $g, $vista ), ARRAY_FILTER_USE_KEY );
	}
	$terms = array_values( array_filter( $terms, fn( $t ) => ! empty( $res[ $t->slug ] ) ) );
	// Aviso que enlaza con la otra vista (archivo histórico ↔ documentos recientes).
	$corte  = (int) get_theme_mod( 'uleam_doc_corte', 2018 );
	$pag    = (int) get_theme_mod( 'uleam_doc_hist_pagina', 0 ) ?: (int) get_option( 'uleam_doc_hist_pagina_id' );
	$aviso  = '';
	$hist_url = '';
	if ( 'actual' === $vista && $pag && 'publish' === get_post_status( $pag ) ) {
		$hay = 0;
		foreach ( doc_resumen() as $gs ) {
			foreach ( $gs as $g => $c ) {
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
	$o   = '<div class="docs-todo" data-vista="' . $vista . '" data-mes2="' . (int) get_theme_mod( 'uleam_doc_mes_p2', 7 ) . '" data-api="' . esc_url( rest_url( 'uleam/v1/documentos' ) ) . '">';
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
		$g0   = count( $gs ) > 2 ? array_key_first( $gs ) : ''; // con muchos años abre en el más reciente
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
