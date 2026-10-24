<?php
$pie = fn( $k ) => get_theme_mod( "uleam_$k", ULEAM_PIE_COLS[ $k ][1] ) ?: ULEAM_PIE_COLS[ $k ][1];
$dir = ha_mod( 'ha_direccion', "Av. Circunvalación – Vía a San Mateo\nManta – Manabí – Ecuador" );
?>
<footer class="pie-sitio">
  <div class="pie-g">
    <section class="pie-col pie-sobre">
      <h2><?php echo esc_html( $pie( 'pie_t_sobre' ) ); ?></h2>
      <p><?php echo esc_html( $pie( 'pie_sobre' ) ); ?></p>
    </section>
    <?php if ( has_nav_menu( 'pie' ) ) : ?>
    <nav class="pie-col" aria-label="<?php echo esc_attr( $pie( 'pie_t_enlaces' ) ); ?>">
      <h2><?php echo esc_html( $pie( 'pie_t_enlaces' ) ); ?></h2>
      <?php wp_nav_menu( array( 'theme_location' => 'pie', 'container' => false, 'menu_class' => 'pie-enlaces', 'depth' => 1, 'fallback_cb' => false ) ); ?>
    </nav>
    <?php endif; ?>
    <section class="pie-col">
      <h2><?php echo esc_html( $pie( 'pie_t_contacto' ) ); ?></h2>
      <ul class="pie-contacto">
        <li><?php echo uleam_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( get_theme_mod( 'uleam_email', ULEAM_CORREO ) ); ?>"><?php echo esc_html( get_theme_mod( 'uleam_email', ULEAM_CORREO ) ); ?></a></li>
        <li><?php echo uleam_icon( 'map-pin' ); ?><span><?php echo nl2br( esc_html( $dir ) ); ?></span></li>
      </ul>
    </section>
    <section class="pie-col">
      <h2><?php echo esc_html( get_theme_mod( 'uleam_siguenos', ULEAM_SIGUENOS ) ); ?></h2>
      <div class="pie-redes">
        <?php foreach ( uleam_redes() as $icono => $r ) : ?>
          <a href="<?php echo esc_url( $r[0] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $r[1] . ' (se abre en otra pestaña)' ); ?>"><?php echo uleam_icon( $icono ); ?></a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
  <p class="pie-texto"><?php echo nl2br( esc_html( str_replace( '{año}', wp_date( 'Y' ), get_theme_mod( 'uleam_pie_texto', ULEAM_PIE ) ) ) ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
