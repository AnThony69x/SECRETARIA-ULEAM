<?php
// Documentos PDF agrupados por periodo/año. Se suben en el admin "Documentos" y se muestran con [documentos seccion="slug"].

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
} );

// Lista plana de un apartado (filas con su año/periodo). Los filtra assets/main.js.
add_shortcode( 'documentos', function ( $a ) {
	$a    = shortcode_atts( array( 'seccion' => '' ), $a );
	$docs = get_posts( array(
		'post_type'      => 'documento',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'tax_query'      => array( array( 'taxonomy' => 'seccion', 'field' => 'slug', 'terms' => $a['seccion'] ) ),
	) );
	$grupos = array();
	foreach ( $docs as $d ) {
		if ( $url = doc_url( $d->ID ) ) {
			$grupos[ get_post_meta( $d->ID, 'grupo', true ) ?: 'Sin periodo' ][] = array( $d, $url );
		}
	}
	if ( ! $grupos ) {
		return '<p class="doc-vacio">Aún no hay documentos publicados en esta sección.</p>';
	}
	uksort( $grupos, fn( $x, $y ) => strnatcasecmp( $y, $x ) ); // más reciente primero
	$o = '<div class="doc-lista" data-sec="' . esc_attr( $a['seccion'] ) . '"><ul class="doc-filas">';
	foreach ( $grupos as $g => $items ) {
		foreach ( $items as [ $d, $url ] ) {
			$t = get_the_title( $d );
			$o .= '<li class="doc-fila" data-g="' . esc_attr( $g ) . '" data-t="' . ( false !== stripos( $t, 'resoluc' ) ? 'resolucion' : 'acta' ) . '"><span class="icono">' . uleam_icon( 'file-text' ) . '</span><span class="doc-t">' . esc_html( $t ) . '<small>' . esc_html( $g ) . '</small></span><a class="btn azul pq" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . uleam_icon( 'download' ) . 'Ver PDF</a></li>';
		}
	}
	return $o . '</ul><button type="button" class="btn pq doc-mas" hidden></button></div>';
} );

// Página única: [consejo secciones="slug,slug"]. Nombre y descripción de cada pestaña se editan en Documentos → Secciones;
// el orden y qué secciones aparecen, en el atributo "secciones" del shortcode de la página.
const DOC_ICONOS = array( 'miembros' => 'users', 'sesiones-ordinarias' => 'file-text', 'sesiones-extraordinarias' => 'scale' );

add_shortcode( 'consejo', function ( $a ) {
	$a     = shortcode_atts( array( 'secciones' => implode( ',', array_keys( DOC_SECCIONES ) ) ), $a );
	$terms = array_filter( array_map( fn( $s ) => get_term_by( 'slug', trim( $s ), 'seccion' ), explode( ',', $a['secciones'] ) ) );
	$o  = '<div class="docs-todo">';
	$o .= '<div class="doc-tabs" role="tablist" aria-label="Apartados">';
	foreach ( $terms as $i => $t ) {
		$o .= '<button type="button" role="tab" id="tab-' . esc_attr( $t->slug ) . '" aria-controls="' . esc_attr( $t->slug ) . '" aria-selected="false" tabindex="-1" data-nombre="' . esc_attr( $t->name ) . '">'
			. '<span class="doc-tab-num">' . sprintf( '%02d', $i + 1 ) . '</span><span class="icono suave">' . uleam_icon( DOC_ICONOS[ $t->slug ] ?? 'folder' ) . '</span>'
			. '<span class="doc-tab-txt"><b>' . esc_html( $t->name ) . '</b>' . ( $t->description ? '<small>' . esc_html( $t->description ) . '</small>' : '' ) . '<span class="doc-tab-n"></span></span></button>';
	}
	$o .= '</div>';
	$o .= '<div class="doc-herramientas"><label class="doc-buscar">' . uleam_icon( 'search' ) . '<span class="sr">Buscar documento por nombre, número o año</span><input type="search" placeholder="Buscar por nombre, número o año (ej. 2021, resolución 105)…"></label>';
	$o .= '<label class="doc-anio"><span class="sr">Filtrar por año o periodo</span><select></select></label><label class="doc-tipo"><span class="sr">Tipo de documento</span><select><option value="">Actas y resoluciones</option><option value="acta">Solo actas</option><option value="resolucion">Solo resoluciones</option></select></label></div>';
	$o .= '<p class="doc-contador" role="status" aria-live="polite"></p>';
	foreach ( $terms as $t ) {
		$o .= '<section class="doc-seccion" id="' . esc_attr( $t->slug ) . '" role="tabpanel" aria-labelledby="tab-' . esc_attr( $t->slug ) . '" hidden>' . do_shortcode( '[documentos seccion="' . esc_attr( $t->slug ) . '"]' ) . '</section>';
	}
	return $o . '<p class="doc-vacio" hidden>No se encontraron documentos con esos filtros.</p></div>';
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
