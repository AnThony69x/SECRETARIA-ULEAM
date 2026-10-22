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
      <?php if ( $m = get_theme_mod( 'uleam_email' ) ) : ?>
        <a href="mailto:<?php echo esc_attr( $m ); ?>"><?php echo uleam_icon( 'mail' ); ?><?php echo esc_html( $m ); ?></a>
      <?php else : ?><span></span><?php endif; ?>
      <div class="sociales">
        <?php $hay = false; foreach ( REDES as $k => $l ) { if ( $u = get_theme_mod( "uleam_$k" ) ) { $hay = true; } } if ( $hay ) echo 'Síguenos'; ?>
        <?php foreach ( REDES as $k => $l ) : if ( $u = get_theme_mod( "uleam_$k" ) ) : ?>
          <a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $k ) ); ?>"><?php echo uleam_icon( $k ); ?></a>
        <?php endif; endforeach; ?>
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
        <input type="search" name="s" placeholder="Buscar en el sitio..." aria-label="Buscar en el sitio">
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
