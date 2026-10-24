<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header>
  <div class="topbar">
    <div class="wrap">
      <?php if ( $m = get_theme_mod( 'uleam_email', ULEAM_CORREO ) ) : ?>
        <a href="mailto:<?php echo esc_attr( $m ); ?>"><?php echo uleam_icon( 'mail' ); ?><?php echo esc_html( $m ); ?></a>
      <?php else : ?><span></span><?php endif; ?>
      <div class="sociales">
        <?php $redes = uleam_redes( true ); if ( $redes ) echo '<span>' . esc_html( get_theme_mod( 'uleam_siguenos', ULEAM_SIGUENOS ) ) . '</span>'; ?>
        <?php foreach ( $redes as $k => $r ) : ?>
          <a href="<?php echo esc_url( $r[0] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $r[1] . ' (se abre en otra pestaña)' ); ?>"><?php echo uleam_icon( $k ); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="marca">
    <div class="wrap">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" aria-label="Inicio">
        <?php if ( has_custom_logo() ) echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'alt' => get_bloginfo( 'name' ) ) ); ?>
        <span class="nombre"><?php bloginfo( 'name' ); ?></span>
      </a>
      <form class="buscar" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" <?php $b = get_theme_mod( 'uleam_buscar', ULEAM_BUSCAR ); ?>placeholder="<?php echo esc_attr( $b ); ?>" aria-label="<?php echo esc_attr( $b ); ?>">
        <button type="submit" aria-label="Buscar"><?php echo uleam_icon( 'search' ); ?></button>
      </form>
    </div>
  </div>
  <div class="navbar-wrap">
    <div class="wrap navbar">
      <nav aria-label="Principal">
        <?php wp_nav_menu( array( 'theme_location' => 'principal', 'container' => false, 'menu_class' => 'menu', 'menu_id' => 'menu', 'fallback_cb' => false ) ); ?>
      </nav>
      <button class="iconbtn menu-btn" id="toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu"><?php echo uleam_icon( 'menu' ); ?></button>
    </div>
  </div>
</header>
