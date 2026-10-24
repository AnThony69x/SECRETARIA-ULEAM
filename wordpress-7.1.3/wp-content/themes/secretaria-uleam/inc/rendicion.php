<?php
// [rendicion]: informes anuales de rendición de cuentas en tarjetas. Cada informe es un "documento" de la sección
// "Informes anuales" (Documentos → Añadir: título, grupo "Año 2024", PDF e imagen de portada). Solo se muestran para
// ver y descargar (si un informe no tiene PDF, se ve y descarga su imagen). El informe más reciente va destacado.

// Imagen de portada del informe (la tabla firmada, que el visor del tema amplía).
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'doc_portada', 'Imagen de portada (opcional)', function ( $post ) {
		wp_nonce_field( 'doc_portada', 'doc_portada_nonce' );
		wp_enqueue_media();
		$id = (int) get_post_meta( $post->ID, 'portada_id', true );
		echo '<input type="hidden" name="portada_id" id="portada_id" value="' . esc_attr( $id ) . '"><div id="portada_vista">' . ( $id ? wp_get_attachment_image( $id, 'medium' ) : '' ) . '</div>';
		echo '<p><button type="button" class="button" id="portada_btn">Elegir imagen</button> <button type="button" class="button-link" id="portada_quitar">Quitar</button></p>';
		echo '<script>(function(){var v=document.getElementById("portada_vista"),h=document.getElementById("portada_id");document.getElementById("portada_btn").onclick=function(){var m=wp.media({library:{type:"image"},multiple:false});m.on("select",function(){var a=m.state().get("selection").first().toJSON(),i=document.createElement("img");i.src=(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url);i.style.maxWidth="100%";h.value=a.id;v.textContent="";v.appendChild(i);});m.open();};document.getElementById("portada_quitar").onclick=function(){h.value=0;v.textContent="";};})();</script>';
	}, 'documento', 'side' );
} );
add_action( 'save_post_documento', function ( $id ) {
	if ( ! isset( $_POST['doc_portada_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doc_portada_nonce'] ) ), 'doc_portada' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$img = absint( $_POST['portada_id'] ?? 0 );
	update_post_meta( $id, 'portada_id', wp_attachment_is_image( $img ) ? $img : 0 ); // solo imágenes
} );

add_shortcode( 'rendicion', function ( $a ) {
	$a    = shortcode_atts( array( 'seccion' => 'rc-informes' ), $a );
	$docs = get_posts( array( 'post_type' => 'documento', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true, 'tax_query' => doc_tax( sanitize_title( $a['seccion'] ) ) ) );
	if ( ! $docs ) {
		return current_user_can( 'edit_posts' ) ? '<p class="enlaces-vacio">Aún no hay informes. Añádelos en Documentos → Añadir, en la sección "Informes anuales".</p>' : '';
	}
	$anio = fn( $d ) => (int) preg_match( '/\d{4}/', (string) get_post_meta( $d->ID, 'grupo', true ), $m ) ? (int) $m[0] : 0; // "Año 2024" → 2024
	usort( $docs, fn( $x, $y ) => $anio( $y ) <=> $anio( $x ) ); // año más reciente primero

	$tarjeta = function ( $d, $grande ) {
		$pid = (int) get_post_meta( $d->ID, 'portada_id', true );
		$img = $pid ? wp_get_attachment_image_src( $pid, 'full' ) : null;
		$arch = doc_url( $d->ID ) ?: ( $img ? $img[0] : '' ); // sin PDF, el informe es su imagen: se ve y se descarga como imagen
		$t   = get_the_title( $d );
		$h   = $grande ? 'h2' : 'h3';
		$o   = '<article class="card rc-card' . ( $grande ? ' rc-grande' : '' ) . '">';
		if ( $img ) { // src a tamaño completo (sin srcset): el visor amplía exactamente esta imagen
			$o .= '<figure class="wp-block-image rc-img"><img src="' . esc_url( $img[0] ) . '" width="' . (int) $img[1] . '" height="' . (int) $img[2] . '" alt="' . esc_attr( 'Vista previa: ' . $t ) . '" loading="lazy"></figure>';
		}
		$o .= "<div class=\"rc-info\"><$h>" . esc_html( $t ) . "</$h><div class=\"rc-acciones\">";
		if ( $arch ) {
			$o .= '<a class="btn pq doc-ver" href="' . esc_url( $arch ) . '" target="_blank" rel="noopener">' . uleam_icon( 'eye' ) . '<span>' . esc_html( doc_txt( 'doc_ver' ) ) . '</span></a>'
				. '<a class="btn azul pq doc-bajar" href="' . esc_url( $arch ) . '" download>' . uleam_icon( 'download' ) . '<span>' . esc_html( doc_txt( 'doc_descargar' ) ) . '</span></a>';
		} else {
			$o .= '<span class="rc-sin">' . esc_html( doc_txt( 'rc_sin' ) ) . '</span>';
		}
		return $o . '</div></div></article>';
	};

	$o = '<div class="rc"><p class="kicker">' . esc_html( doc_txt( 'rc_ultimo' ) ) . '</p>' . $tarjeta( array_shift( $docs ), true );
	if ( $docs ) {
		$o .= '<div class="rc-hist"><p class="kicker">' . esc_html( doc_txt( 'doc_anios_ant' ) ) . '</p><h2>' . esc_html( doc_txt( 'rc_titulo_hist' ) ) . '</h2></div><div class="rc-grid">';
		foreach ( $docs as $d ) {
			$o .= $tarjeta( $d, false );
		}
		$o .= '</div>';
	}
	return $o . '</div>';
} );
