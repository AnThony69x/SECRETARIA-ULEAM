<?php
get_header();

$slides = new WP_Query( array( 'post_type' => 'slide', 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => 10 ) );
?>
<?php if ( $slides->have_posts() ) : ?>
<div class="hero" id="inicio">
  <div class="slider" id="slider">
  <?php while ( $slides->have_posts() ) : $slides->the_post(); ?>
    <div class="slide">
      <?php the_post_thumbnail( 'full', array( 'alt' => '' ) ); ?>
      <div class="wrap">
        <div class="tarjeta">
          <?php if ( $e = get_post_meta( get_the_ID(), 'etiqueta', true ) ) : ?><span class="etiq"><?php echo esc_html( $e ); ?></span><?php endif; ?>
          <h1><?php the_title(); ?></h1>
          <?php if ( $f = get_post_meta( get_the_ID(), 'franja', true ) ) : ?><div class="franja"><?php echo esc_html( $f ); ?></div><?php endif; ?>
          <?php the_content(); ?>
          <?php if ( ( $t = get_post_meta( get_the_ID(), 'boton_txt', true ) ) && ( $u = get_post_meta( get_the_ID(), 'boton_url', true ) ) ) : ?>
            <a class="btn" href="<?php echo esc_url( $u ); ?>"><?php echo esc_html( $t ); ?> <?php echo uleam_icon( 'arrow-right' ); ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; wp_reset_postdata(); ?>
  </div>
  <div class="ctrl">
    <button id="pausa" aria-label="Pausar carrusel"><?php echo uleam_icon( 'pause' ); ?></button>
    <button id="prev" aria-label="Banner anterior"><?php echo uleam_icon( 'chevron-left' ); ?></button>
    <div class="puntos" id="puntos"></div>
    <button id="next" aria-label="Banner siguiente"><?php echo uleam_icon( 'chevron-right' ); ?></button>
  </div>
</div>
<?php endif; ?>

<?php // Contenido de la portada bajo los accesos: página "Contenido de la portada" (bloques y shortcodes), editable en wp-admin → Páginas.
if ( $ini = get_page_by_path( 'inicio-contenido' ) ) : ?>
<main class="pagina-cont inicio-cont"><?php echo apply_filters( 'the_content', $ini->post_content ); ?></main>
<?php endif; ?>

<?php get_footer();
