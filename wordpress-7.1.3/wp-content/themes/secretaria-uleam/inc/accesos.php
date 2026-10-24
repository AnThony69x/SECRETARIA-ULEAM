<?php
// [accesos]: tarjetas de acceso rápido con los enlaces del menú "Accesos rápidos" (Apariencia → Menús). El nombre del icono va en la
// Descripción del enlace; el texto de la tarjeta, en "Atributo de título" o, si falta, en el extracto de la página enlazada.

add_shortcode( 'accesos', function ( $atts ) {
	$texto = shortcode_atts( array( 'texto' => 'Ingresar' ), $atts )['texto']; // etiqueta del enlace de cada tarjeta, editable en el bloque
	if ( ! has_nav_menu( 'accesos' ) ) {
		return current_user_can( 'edit_theme_options' ) ? '<p class="enlaces-vacio">Asigna un menú a «Accesos rápidos» en Apariencia → Menús.</p>' : '';
	}
	$tarjetas = wp_nav_menu( array( 'theme_location' => 'accesos', 'container' => false, 'echo' => false, 'items_wrap' => '<div class="accesos">%3$s</div>', 'walker' => new class( $texto ) extends Walker_Nav_Menu {
		public function __construct( private string $texto ) {}
		// Enlaces sueltos sin <li>: el CSS espera <a class="acceso"> hijos directos de .accesos.
		public function start_lvl( &$o, $d = 0, $a = null ) {}
		public function end_lvl( &$o, $d = 0, $a = null ) {}
		public function start_el( &$o, $item, $depth = 0, $args = null, $id = 0 ) {
			$txt = $item->attr_title ?: get_post_field( 'post_excerpt', 'post_type' === $item->type ? $item->object_id : url_to_postid( $item->url ) );
			$o  .= '<a class="acceso" href="' . esc_url( $item->url ) . '"' . ( '_blank' === $item->target ? ' target="_blank" rel="noopener"' : '' ) . '>'
				. '<span class="icono">' . uleam_icon( $item->description ) . '</span><b>' . esc_html( $item->title ) . '</b>'
				. ( $txt ? '<small>' . esc_html( $txt ) . '</small>' : '' )
				. '<span class="acceso-ir">' . esc_html( $this->texto ) . uleam_icon( 'arrow-right' ) . '</span></a>';
		}
		public function end_el( &$o, $item, $depth = 0, $args = null ) {}
	} ) );
	return '<div class="accesos-wrap">' . $tarjetas . '</div>';
} );
