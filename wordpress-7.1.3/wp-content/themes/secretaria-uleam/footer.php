<footer class="pie-sitio">
  <h2><?php echo esc_html( get_theme_mod( 'uleam_siguenos', ULEAM_SIGUENOS ) ); ?></h2>
  <div class="pie-redes">
    <?php foreach ( uleam_redes() as $icono => $r ) : ?>
      <a href="<?php echo esc_url( $r[0] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $r[1] . ' (se abre en otra pestaña)' ); ?>"><?php echo uleam_icon( $icono ); ?></a>
    <?php endforeach; ?>
  </div>
  <p class="pie-texto"><?php echo nl2br( esc_html( str_replace( '{año}', wp_date( 'Y' ), get_theme_mod( 'uleam_pie_texto', ULEAM_PIE ) ) ) ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
