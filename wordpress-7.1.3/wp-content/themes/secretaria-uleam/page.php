<?php get_header(); the_post(); ?>
<section class="pag-hero">
  <div class="wrap">
    <?php $t = explode( ' ', get_the_title() ); $u = array_pop( $t ); ?>
    <h1><?php echo esc_html( implode( ' ', $t ) ); ?> <span><?php echo esc_html( $u ); ?></span></h1>
    <?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
    <?php if ( $e = array_filter( array_map( 'trim', explode( ',', get_post_meta( get_the_ID(), 'etiquetas', true ) ) ) ) ) : $ic = array( 'target', 'shield', 'star' ); ?>
      <div class="chips"><?php foreach ( array_values( $e ) as $i => $x ) echo '<span>' . uleam_icon( $ic[ $i % 3 ] ) . esc_html( $x ) . '</span>'; ?></div>
    <?php endif; ?>
  </div>
</section>
<main class="pagina-cont"><?php the_content(); ?></main>
<?php get_footer();
