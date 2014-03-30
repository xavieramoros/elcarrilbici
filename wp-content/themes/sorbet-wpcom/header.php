<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package Sorbet
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php wp_title( '|', true, 'right' ); ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<?php do_action( 'before' ); ?>
	<header id="masthead" class="site-header" role="banner">
		<?php if ( get_header_image() ) : ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php header_image(); ?>" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="">
		</a>
		<?php endif; // End header image check. ?>
		<div class="site-header-wrapper">
			<div class="site-branding">
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
			</div>

			<div class="toggles">
				<div id="menu-toggle" class="toggle" title="<?php esc_attr_e( 'Menu', 'sorbet' ); ?>"><span class="screen-reader-text"><?php _e( 'Menu', 'sorbet' ); ?></span></div>
				<?php if ( is_active_sidebar( 'sidebar-2' ) || is_active_sidebar( 'sidebar-3' ) || is_active_sidebar( 'sidebar-4' ) ) : ?>
					<div id="sidebar-toggle" class="toggle" title="<?php esc_attr_e( 'Widgets', 'sorbet' ); ?>"><span class="screen-reader-text"><?php _e( 'Widgets', 'sorbet' ); ?></span></div>
				<?php endif; ?>
				<?php if ( has_nav_menu( 'social' ) ) : ?>
					<div id="social-links-toggle" class="toggle" title="<?php esc_attr_e( 'Social Links', 'sorbet' ); ?>"><span class="screen-reader-text"><?php _e( 'Social Links', 'sorbet' ); ?></span></div>
				<?php endif; ?>
				<div id="search-toggle" class="toggle" title="<?php esc_attr_e( 'Search', 'sorbet' ); ?>"><span class="screen-reader-text"><?php _e( 'Search', 'sorbet' ); ?></span></div>
			</div>
		</div>
	</header><!-- #masthead -->
	<div id="menu-toggle-nav" class="panel">
		<nav id="site-navigation" class="main-navigation" role="navigation">
			<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'sorbet' ); ?></a>

			<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
		</nav><!-- #site-navigation -->
	</div>
	<?php if ( has_nav_menu( 'social' ) ) : ?>
		<div id="social-links-toggle-nav" class="panel">
			<?php wp_nav_menu( array( 'theme_location' => 'social', 'depth' => 1, 'link_before' => '<span class="screen-reader-text">', 'link_after' => '</span>', 'container_class' => 'social-links', ) ); ?>
		</div>
	<?php endif; ?>
	<?php if ( is_active_sidebar( 'sidebar-2' ) || is_active_sidebar( 'sidebar-3' ) || is_active_sidebar( 'sidebar-4' ) ) : ?>
		<?php get_sidebar( 'header' ); ?>
	<?php endif; ?>
	<div id="search-toggle-nav" class="panel">
		<div class="search-wrapper">
			<?php get_search_form(); ?>
		</div>
	</div>
	<div id="content" class="site-content">
