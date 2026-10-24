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
	return $url ?: (string) get_post_meta( $id, 'pdf_url', true ); // documento alojado en otro sitio (ej. normativas)
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
		echo '<p><label><strong>o URL externa del documento</strong><br><input type="url" name="pdf_url" class="widefat" placeholder="https://…" value="' . esc_attr( get_post_meta( $post->ID, 'pdf_url', true ) ) . '"></label><br><small>Solo si el archivo está publicado en otro sitio y no se sube aquí.</small></p>';
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
	update_post_meta( $id, 'pdf_url', esc_url_raw( wp_unslash( $_POST['pdf_url'] ?? '' ) ) );
	$tipo = sanitize_key( $_POST['tipo'] ?? '' );
	update_post_meta( $id, 'tipo', isset( DOC_TIPOS[ $tipo ] ) ? $tipo : '' );
} );

add_action( 'customize_register', function ( $c ) {
	$c->add_setting( 'uleam_doc_anios_atras', array( 'default' => 2, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'uleam_doc_anios_atras', array( 'section' => 'uleam', 'label' => 'Documentos: años anteriores al actual que se ven en la página principal (el resto pasa al archivo histórico)', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 50 ) ) );
	$c->add_setting( 'uleam_doc_mes_p2', array( 'default' => 7, 'sanitize_callback' => fn( $v ) => min( 12, max( 2, absint( $v ) ) ) ) );
	$c->add_control( 'uleam_doc_mes_p2', array( 'section' => 'uleam', 'label' => 'Documentos: mes en que empieza el periodo 2 de cada año (1 = enero … 12 = diciembre)', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ) );
	$c->add_setting( 'uleam_doc_hist_pagina', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'uleam_doc_hist_pagina', array( 'section' => 'uleam', 'label' => 'Archivo histórico: página donde se muestra', 'type' => 'dropdown-pages' ) );
}, 21 );

// Año de corte móvil: el actual menos los años que el administrador deja en la página principal (cada enero avanza solo).
function doc_corte() {
	return (int) wp_date( 'Y' ) - (int) get_theme_mod( 'uleam_doc_anios_atras', 2 );
}

// Vista "actual" (año >= corte) o "historico" (año < corte). Solo cuentan los grupos por año ("Año 2021"):
// los periodos de Miembros ("Periodo 2016 - 2018") siempre se quedan en la página principal.
function doc_en_vista( $g, $vista ) {
	if ( 'actual' !== $vista && 'historico' !== $vista ) {
		return true;
	}
	$h = preg_match( '/^Año (\d{4})/', $g, $m ) && (int) $m[1] < doc_corte();
	return 'historico' === $vista ? $h : ! $h;
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
			$out[] = array( 's' => $s[0]->slug, 'g' => get_post_meta( $d->ID, 'grupo', true ) ?: 'Sin periodo', 't' => doc_tipo( $d ), 'ti' => get_the_title( $d ), 'u' => $u, 'e' => doc_ext( $u ), 'img' => (string) wp_get_attachment_image_url( (int) get_post_meta( $d->ID, 'portada_id', true ), 'large' ) ); // 'img': portada opcional (se muestra como miniatura)
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
