<footer class="pie-sitio">
  <h2>Síguenos</h2>
  <div class="pie-redes">
    <?php foreach ( array(
		'facebook'  => array( 'https://www.facebook.com/UleamEc/', 'Facebook' ),
		'x'         => array( 'https://x.com/UleamEcuador', 'X' ),
		'youtube'   => array( 'https://www.youtube.com/channel/UCOferC0PjpEUegrVq8Re-TQ', 'YouTube' ),
		'instagram' => array( 'https://www.instagram.com/uleam_ecuador_oficial/', 'Instagram' ),
	) as $icono => $r ) : ?>
      <a href="<?php echo esc_url( $r[0] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $r[1] ); ?>"><?php echo uleam_icon( $icono ); ?></a>
    <?php endforeach; ?>
  </div>
  <p class="pie-texto">ULEAM © Copyright 2020, Todos los derechos reservados - Universidad Laica Eloy Alfaro de Manabí<br>Dirección: Av. Circunvalación - Vía a San Mateo<br>Manta - Manabí - Ecuador</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
