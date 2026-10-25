<?php
// Resultados de la búsqueda: páginas del sitio y documentos (ver inc/busqueda.php).
get_header();
$q    = trim( get_search_query( false ) );
// Filtros que envía la biblioteca de la portada: apartado (sección) y año (periodo). Con filtros solo se listan documentos.
$sec  = isset( $_GET['a'] ) ? sanitize_title( wp_unslash( $_GET['a'] ) ) : '';
$gr   = isset( $_GET['g'] ) ? sanitize_text_field( wp_unslash( $_GET['g'] ) ) : '';
$docs = uleam_buscar_docs( $q, $sec, $gr );
$hay  = $q && ! $sec && ! $gr && have_posts();
$term = $sec ? get_term_by( 'slug', $sec, 'seccion' ) : null;
$filtros = trim( ( $term ? $term->name : '' ) . ( $gr ? ' · ' . preg_replace( '/^Año\s*/u', '', $gr ) : '' ), ' ·' );
$sub  = $q ? str_replace( '{q}', $q, doc_txt( 'bus_sub' ) ) . ( $filtros ? ' · ' . $filtros : '' ) : $filtros;
$chips = array_filter( array(
	$q ? array( 'search', '«' . $q . '»' ) : null,
	$filtros ? array( 'archive', $filtros ) : null,
	$hay ? array( 'file-text', $GLOBALS['wp_query']->found_posts . ' ' . ( 1 === (int) $GLOBALS['wp_query']->found_posts ? 'página' : 'páginas' ) ) : null,
	$docs['total'] ? array( 'folder', $docs['total'] . ' ' . ( 1 === $docs['total'] ? 'documento' : 'documentos' ) ) : null,
) );
uleam_hero( doc_txt( 'bus_titulo' ), $sub, $chips );
?>
<main class="pagina-cont">
<?php if ( $hay ) : ?>
  <div class="bus-grupo"><h2><?php echo esc_html( doc_txt( 'bus_paginas' ) ); ?></h2></div>
  <ul class="enlaces">
  <?php while ( have_posts() ) : the_post(); ?>
    <li><a class="card enlace-card" href="<?php the_permalink(); ?>">
      <span class="icono"><?php echo uleam_icon( 'file-text' ); ?></span>
      <span class="enlace-t"><b><?php the_title(); ?></b><?php if ( has_excerpt() ) : ?><small><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></small><?php endif; ?></span>
      <span class="enlace-ir"><?php echo uleam_icon( 'arrow-right' ); ?></span>
    </a></li>
  <?php endwhile; ?>
  </ul>
<?php endif; ?>

<?php if ( $docs['filas'] ) : ?>
  <div class="bus-grupo"><h2><?php echo esc_html( doc_txt( 'bus_docs' ) ); ?> <span class="tag"><?php echo (int) $docs['total']; ?></span></h2></div>
  <div class="docs-todo listo"><div class="doc-lista"><ul class="doc-filas">
  <?php foreach ( $docs['filas'] as $i => $r ) : ?>
    <li class="doc-fila" style="--i:<?php echo (int) ( $i % 30 ); ?>">
      <?php echo doc_arch( $r['u'] ); ?>
      <span class="doc-t"><span><?php echo esc_html( $r['ti'] ); ?></span><small><?php echo esc_html( trim( $r['et'] . ' · ' . $r['g'], ' ·' ) ); ?></small></span>
      <span class="doc-acciones">
        <a class="btn pq doc-ver" href="<?php echo esc_url( $r['u'] ); ?>" target="_blank" rel="noopener"><?php echo uleam_icon( 'eye' ); ?><span><?php echo esc_html( doc_txt( 'doc_ver' ) ); ?></span></a>
        <a class="btn azul pq doc-bajar" href="<?php echo esc_url( $r['u'] ); ?>" download><?php echo uleam_icon( 'download' ); ?><span><?php echo esc_html( doc_txt( 'doc_descargar' ) ); ?></span></a>
      </span>
    </li>
  <?php endforeach; ?>
  </ul></div></div>
  <?php if ( $docs['total'] > count( $docs['filas'] ) ) : ?>
    <p class="bus-nota"><?php echo esc_html( str_replace( array( '{n}', '{m}' ), array( $docs['total'], count( $docs['filas'] ) ), doc_txt( 'bus_mas' ) ) ); ?></p>
  <?php endif; ?>
<?php endif; ?>

<?php if ( ! $hay && ! $docs['filas'] ) : ?>
  <p class="bus-nota"><?php echo esc_html( doc_txt( 'bus_vacio' ) ); ?></p>
<?php endif; ?>
</main>
<?php get_footer();
