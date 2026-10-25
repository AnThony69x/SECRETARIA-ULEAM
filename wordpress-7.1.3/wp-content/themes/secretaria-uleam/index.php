<?php
get_header();

$slides = new WP_Query( array( 'post_type' => 'slide', 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => 10 ) );
?>
<?php if ( $slides->have_posts() ) : ?>
<?php $seg = min( 30, max( 3, (int) get_theme_mod( 'uleam_slide_seg', 6 ) ) ); ?>
<div class="hero" id="inicio" data-seg="<?php echo (int) $seg; ?>" data-auto="<?php echo get_theme_mod( 'uleam_slide_auto', true ) ? 1 : 0; ?>" style="--seg:<?php echo (int) $seg; ?>s">
  <div class="slider" id="slider">
  <?php $n = 0; while ( $slides->have_posts() ) : $slides->the_post(); ?>
    <?php
    // Variantes editables (inc/slide-admin.php): lado de la tarjeta (alterna por defecto) y tono azul del fondo.
    $frente = (int) get_post_meta( get_the_ID(), 'frente_id', true );
    $lado   = get_post_meta( get_the_ID(), 'lado', true );
    $lado   = in_array( $lado, array( 'der', 'izq' ), true ) ? $lado : ( 0 === $n % 2 ? 'der' : 'izq' );
    $tono   = get_post_meta( get_the_ID(), 'tono', true );
    $tono   = in_array( (string) $tono, array( '0', '1', '2' ), true ) ? $tono : ( $frente ? '1' : '0' );
    ++$n;
    ?>
    <div class="slide lado-<?php echo esc_attr( $lado ); ?> tono-<?php echo esc_attr( $tono ); ?><?php echo $frente ? ' con-frente' : ''; ?>">
      <?php the_post_thumbnail( 'full', array( 'alt' => '' ) ); ?>
      <?php if ( $frente ) { echo wp_get_attachment_image( $frente, 'full', false, array( 'class' => 'frente', 'alt' => '' ) ); } ?>
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
