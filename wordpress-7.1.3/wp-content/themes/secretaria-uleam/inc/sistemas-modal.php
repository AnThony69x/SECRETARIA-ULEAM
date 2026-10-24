<?php
// Modal "Sistema Histórico de Gestión": al pulsar cualquier enlace a esa página (menú, tarjeta de accesos) se abre un diálogo con los
// sistemas para elegir, en vez de ir a la página. Los sistemas son los enlaces del menú "sistema-historico" ([enlaces], editable en
// Apariencia → Menús); título y subtítulo salen de la página. Sin JavaScript el enlace sigue llevando a la página.

add_action( 'wp_footer', function () {
	$pag = get_page_by_path( 'sistema-historico-de-gestion' );
	if ( ! $pag || 'publish' !== $pag->post_status || ! wp_get_nav_menu_items( 'sistema-historico' ) ) {
		return;
	}
	?>
<dialog class="sis-modal" id="sis-modal" aria-labelledby="sis-titulo" data-url="<?php echo esc_url( get_permalink( $pag ) ); ?>">
  <div class="sis-caja">
    <button type="button" class="sis-cerrar" aria-label="Cerrar"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    <h2 id="sis-titulo"><?php echo esc_html( get_the_title( $pag ) ); ?></h2>
    <?php if ( $pag->post_excerpt ) : ?><p class="sis-sub"><?php echo esc_html( $pag->post_excerpt ); ?></p><?php endif; ?>
    <p class="sis-ayuda">Elige el sistema al que quieres ingresar. Se abre en una pestaña nueva.</p>
    <?php echo do_shortcode( '[enlaces menu="sistema-historico"]' ); ?>
  </div>
</dialog>
	<?php
} );
