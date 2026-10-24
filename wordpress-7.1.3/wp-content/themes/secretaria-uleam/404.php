<?php
// Página no encontrada (ver inc/busqueda.php).
get_header();
uleam_hero( doc_txt( 'e404_titulo' ), doc_txt( 'e404_sub' ) );
?>
<main class="pagina-cont bus-404">
  <p><?php echo esc_html( doc_txt( 'e404_texto' ) ); ?></p>
  <form class="buscar bus-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <input type="search" name="s" placeholder="<?php echo esc_attr( get_theme_mod( 'uleam_buscar', ULEAM_BUSCAR ) ); ?>" aria-label="<?php echo esc_attr( get_theme_mod( 'uleam_buscar', ULEAM_BUSCAR ) ); ?>">
    <button type="submit" aria-label="Buscar"><?php echo uleam_icon( 'search' ); ?></button>
  </form>
  <p><a class="btn azul" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( doc_txt( 'e404_inicio' ) ); ?></a></p>
</main>
<?php get_footer();
