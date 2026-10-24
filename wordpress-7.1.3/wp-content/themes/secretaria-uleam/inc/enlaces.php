<?php
// [enlaces menu="slug"]: tarjetas de acceso con los enlaces de un menú (Apariencia → Menús). Título, URL, Descripción
// (texto de la tarjeta) y "Abrir en pestaña nueva" se editan allí; el icono se elige de una lista fija del tema.

const ENL_ICONOS = DOC_ICONOS_LISTA + array( 'monitor' => 'Pantalla', 'lapiz' => 'Lápiz', 'search' => 'Lupa', 'mail' => 'Correo' );

// Selector de icono en cada enlace del editor de menús.
add_action( 'wp_nav_menu_item_custom_fields', function ( $id, $item ) {
	$v   = get_post_meta( $id, '_uleam_icono', true ) ?: 'monitor';
	$sel = '';
	foreach ( ENL_ICONOS as $k => $l ) {
		$sel .= '<option value="' . esc_attr( $k ) . '"' . selected( $v, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '<p class="description description-wide"><label>Icono (tarjetas de [enlaces])<br><select name="uleam_icono[' . (int) $id . ']">' . $sel . '</select></label></p>';
}, 10, 2 );
add_action( 'wp_update_nav_menu_item', function ( $menu, $id ) { // el formulario de menús ya verifica nonce
	if ( current_user_can( 'edit_theme_options' ) && isset( $_POST['uleam_icono'][ $id ] ) && isset( ENL_ICONOS[ $_POST['uleam_icono'][ $id ] ] ) ) {
		update_post_meta( $id, '_uleam_icono', $_POST['uleam_icono'][ $id ] );
	}
}, 10, 2 );

add_shortcode( 'enlaces', function ( $a ) {
	$a     = shortcode_atts( array( 'menu' => '' ), $a );
	$items = $a['menu'] ? wp_get_nav_menu_items( $a['menu'] ) : array();
	$items = array_filter( (array) $items, fn( $it ) => ! $it->menu_item_parent );
	if ( ! $items ) {
		return current_user_can( 'edit_theme_options' ) ? '<p class="enlaces-vacio">Crea el menú "' . esc_html( $a['menu'] ) . '" con sus enlaces en Apariencia → Menús.</p>' : '';
	}
	$o = '<ul class="enlaces">';
	foreach ( $items as $it ) {
		$icono = get_post_meta( $it->ID, '_uleam_icono', true );
		$fuera = '_blank' === $it->target;
		$o    .= '<li><a class="card enlace-card" href="' . esc_url( $it->url ) . '"' . ( $fuera ? ' target="_blank" rel="noopener"' : '' ) . '>'
			. '<span class="icono">' . uleam_icon( isset( ENL_ICONOS[ $icono ] ) ? $icono : 'monitor' ) . '</span><span class="enlace-t"><b>' . esc_html( $it->title ) . '</b>'
			. ( $it->description ? '<small>' . esc_html( $it->description ) . '</small>' : '' ) . '</span>'
			. '<span class="enlace-ir">' . uleam_icon( 'arrow-right' ) . ( $fuera ? '<span class="sr">(se abre en una pestaña nueva)</span>' : '' ) . '</span></a></li>';
	}
	return $o . '</ul>';
} );
