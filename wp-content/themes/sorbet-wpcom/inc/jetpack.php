<?php
/**
 * Jetpack Compatibility File
 * See: http://jetpack.me/
 *
 * @package Sorbet
 */

/**
 * Add theme support for Infinite Scroll.
 * See: http://jetpack.me/support/infinite-scroll/
 */
function sorbet_jetpack_setup() {
	add_theme_support( 'infinite-scroll', array(
		'container'      => 'main',
		'footer'         => 'content',
	) );

}
add_action( 'after_setup_theme', 'sorbet_jetpack_setup' );

function sorbet_infinite_scroll_footer_widgets() {
	if ( has_nav_menu( 'secondary' ) || jetpack_is_mobile() && is_active_sidebar( 'sidebar-1' ) )
		return true;
	
	return false;
}
add_action( 'infinite_scroll_has_footer_widgets', 'sorbet_infinite_scroll_footer_widgets' );