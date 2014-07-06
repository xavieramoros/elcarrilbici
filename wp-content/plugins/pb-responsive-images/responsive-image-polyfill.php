<?php
/*
 * Plugin Name: PB Responsive Images
 * Plugin URI: http://wordpress.org/extend/plugins/pb-responsive-images/
 * Description: Adds support for the proposed responsive image format in post content, and helper functions for theme authors.
 * Author: Phenomblue
 * Version: 1.4.1
 * Author URI: http://www.phenomblue.com/
 *
 * -------------------------------------
 *
 * @package PB Responsive Images
 * @category Plugin
 * @author Jacob Dunn
 * @link http://www.phenomblue.com/ Phenomblue
 * @version 1.4.1
 *
 * -------------------------------------
 * 
 * For Further information, see http://www.w3.org/community/respimg/
 *
 * -------------------------------------
 *
 * PB Responsive Images is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* LESS EDITING BELOW */

define('RIP_VERSION', '1.4.1');
define('RIP_FILE', __FILE__);
define('RIP_BASENAME', plugin_basename(RIP_FILE));
define('RIP_PATH', plugin_dir_path(RIP_FILE));

// Activation/Deactivation
register_activation_hook( RIP_FILE, 'RIP::activate' );
register_deactivation_hook( RIP_FILE, 'RIP::deactivate' );

// Includes
require_once(dirname(__FILE__).'/options.php');
require_once(dirname(__FILE__).'/shortcode.php');
require_once(dirname(__FILE__).'/config.php');

// Initialize
RIP::get_manager();

class RIP{
	private static $_singleton;
	
	public static function get_manager()
	{
		if(!isset(self::$_singleton)){
			self::$_singleton = new RIP();
		}
		return self::$_singleton;
	}

	public static function get_picture($image,$options = false)
	{
		$manager = self::get_manager();

		/* We can pass in an options object as the primary argument,
		 * but only if we're providing sources.
		 *
		 * In that case, the second argument is our main image wrapper attributes
		 */

		if(is_a($image,'RIPOptions') || (is_array($image) && isset($image[0]['src']))){
			$attributes = $options;
			$options = $image;
			$image = false;
		}

		$options = $manager->parse_options($options);
		$image = $manager->parse_image($image);

		return ($image === false && isset($options->sources))
			? $manager->format_sources($options,$attributes)
			: $manager->format_content_image($image,$options)->output;
	}

	public static function set_options($options)
	{
		$manager = self::get_manager();
		$manager->_set_options($options);
	}

	public static function reset_options()
	{
		$manager = self::get_manager();
		$manager->_reset_options();
	}
	
	public static function activate()
	{
		add_option('rip_options',array(),'','yes');
	}

	public static function deactivate()
	{
 		global $wp_rewrite;
		delete_option('rip_options');
		$wp_rewrite->flush_rules();
	}
	
	/* End Static Methods */

	public function init()
	{
		add_filter('the_content',array(&$this,'the_content'));

		if(RIPOptions::options()->disable_wordpress_resize){
			add_filter('option_large_size_h',array(&$this,'disable_resize_options'));
			add_filter('option_large_size_w',array(&$this,'disable_resize_options'));
			add_filter('option_medium_size_h',array(&$this,'disable_resize_options'));
			add_filter('option_medium_size_w',array(&$this,'disable_resize_options'));
			add_filter('option_thumbnail_size_h',array(&$this,'disable_resize_options'));
			add_filter('option_thumbnail_size_w',array(&$this,'disable_resize_options'));
		}
	}

	public function wp_enqueue_scripts()
	{
		wp_register_script( 'matchmedia',
			plugins_url('scripts/matchmedia.js',RIP_FILE),
			array(),
			RIP_VERSION,
			false);

		wp_register_script( 'picturefill',
			plugins_url('scripts/picturefill.js',RIP_FILE),
			array('matchmedia'),
			RIP_VERSION,
			false);

		if(RIPOptions::options()->enable_scripts)
			wp_enqueue_script('picturefill');
	}

	public function disable_resize_options($value)
	{
		// If this is the media page, return the value - we don't want to overwrite what's stored in the database
		if(function_exists('get_current_screen') && get_current_screen()->id == 'options-media') return $value;
		
		// Returning 0 for every set size forces WordPress to not resize images
		return 0;
	}

	public function generate_rewrite_rules()
	{
 		global $wp_rewrite;
		$options = RIPOptions::options();

		// Need to check to see if we haven't set a base yet
		if($options->slir_base == ''){
			$options->slir_base = ($wp_rewrite->using_mod_rewrite_permalinks())
				? '{base-url}/slir/'
				: '{plugin-url}/slir/?r=';
		}

		// If we're pointing to our index.php file, exit
		if(preg_match('#/slir/(index.php)?\?r=$#', $options->slir_base)) return;


		if($wp_rewrite->using_mod_rewrite_permalinks()){
			// Get our subfolder, if wordpress is in a subdirectory
			$search = array(
				'#'.preg_quote(get_bloginfo('url')).'#',
				'#^/#'
				);
			$base = preg_replace($search,'',$options->slir_base).'([^/]*)/(.*)$';
			$path = preg_replace($search,'',plugins_url('slir/index.php',RIP_FILE));
			$rules = array($base => sprintf('%1$s?r=$1/$2',$path));

			// printf('<pre>%1$s</pre>',print_r($url,true));
			// printf('<pre>%1$s</pre>',print_r($options->slir_base,true));
			// printf('<pre>%1$s</pre>',print_r($rules,true));

			// exit();

			$wp_rewrite->non_wp_rules = $rules + $wp_rewrite->non_wp_rules;
		}
	}
	
	public function the_content($content,$options = false)
	{
		global $post;

		if(is_feed()) return $content;

		$options = $this->parse_options($options);
		$images = $this->scrub_for_images($content,$options);

		if($images) foreach ($images as $image) {
			$content = str_replace($image->original, $image->output, $content);
		}

		return $content;
	}

	public function parse_image($image){
		// False may be passed in if we're bypassing slir to use our own images
		if($image === false) return false;
		
		// Can pass an image, or an array with image attibutes
		if(is_array($image)){
			// Create an object for formatting
			return (object)array(
				'original'=>sprintf('<img src="%1$s" %2$s >',
					$image['src'],
					$this->attributes_to_string($image)
					),
				'attributes'=>$image
			);
		}else{
			return $this->scrub_image($image);
		}
	}
	
	/* End Public Methods */

	private $options;
	
	private function __construct()
	{
		$this->options = RIPOptions::options();
		
		add_action('init',array(&$this,'init'));
		add_action('wp_enqueue_scripts',array(&$this,'wp_enqueue_scripts'));
		add_action('generate_rewrite_rules',array(&$this,'generate_rewrite_rules'));
	}

	private function _set_options($options)
	{
		$this->options = $this->parse_options($options);
	}

	private function _reset_options()
	{
		$this->options = RIPOptions::options();
	}

	private function register_scripts()
	{
		wp_register_script( 'matchmedia',
			plugins_url('scripts/matchmedia.js',RIP_FILE),
			array(),
			RIP_VERSION,
			false);

		wp_register_script( 'picturefill',
			plugins_url('scripts/picturefill.js',RIP_FILE),
			array('matchmedia'),
			RIP_VERSION,
			false);
	}

	private function scrub_for_images($content,$options)
	{
		$results = preg_match_all('#<img([^\>]*)>(?![^<]*</(noscript|picture)>)#mi', $content, $matches);

		if($results){
			$images = array();
			foreach ($matches[0] as $key => $original) {
				$image = $this->scrub_image($original);

				array_push($images, $this->format_content_image($image,$options));
			}
			return $images;
		}
		return false;
	}

	private function scrub_image($original)
	{
		$image = array('original'=>$original,'attributes'=>array());

		preg_match_all('!([^\s]+)=[\'"]([^\'"]*)[\'"]!mi',$original,$attributes,PREG_SET_ORDER);

		foreach($attributes as $attribute){
			$image['attributes'][$attribute[1]] = $attribute[2];
		}

		return (object)$image;
	}

	private function format_content_image($image,$options)
	{
		$image->output = $image->original;

		// If no src
		if(!$image->attributes['src']) return $image;

		// If we have a root-relative link, sub in the site_url
		if(strpos($image->attributes['src'], '/') === 0)
			$image->attributes['src'] = site_url() . $image->attributes['src'];

		// If we have a relative link, figure out the root-relative url
		if(strpos($image->attributes['src'], 'http') !== 0)
			$image->attributes['src'] = $this->convert_to_root_relative($image->attributes['src']);

		// Make sure it's on our server
		if(strpos($image->attributes['src'],site_url()) === false) return $image;

		// Make sure this isn't flagged as non-responsive
		if($image->attributes['class'] && preg_match('/(\s|^)non-responsive(\s|$)/',$image->attributes['class']) === 1) return $image;
		
		// Compile the Picture tag set
		$sources = array();

		// Get our subfolder, if wordpress is in a subdirectory
		$url = parse_url(site_url());
		// Get rid of the apache user dir, if present
		$path = (isset($url['path'])) ? preg_replace('#^/~[^/]+#', '', $url['path']) : '';

		foreach ($options->formats as $key => $format) {

			$attributes = array();
			if(preg_match('#min-device-pixel-ratio:(\d+)#i', $format->media, $pixel_ratio)){
				$pixel_ratio = floatval($pixel_ratio[1]);
				
				// Set the width for this element
				if(preg_match('#w(\d+)#i', $format->query, $dest_width)){
					$dest_width = floatval($dest_width[1]);

					// Make sure the initial image width is not too small for this density
					if(!isset($image->attributes['width']) || $dest_width < intval($image->attributes['width']))
						$attributes['width'] = ($dest_width / $pixel_ratio);
				}
			}

			// Our base path
			$source = sprintf('%1$s%2$s%3$s%4$s',
				$options->slir_base,
				$format->query,
				$path,
				str_replace(site_url(), '', $image->attributes['src'])
				);

			// Our base attributes
			$attributes['src'] = $source;
			$attributes['media'] = $format->media;

			// Add our source
			array_push($sources,sprintf('<span %1$s></span>',$this->attributes_to_string($attributes,'','span')));

			// Add the fallback, if requested
			if($format->fallback)
				array_push($sources,sprintf('<noscript><img src="%1$s" %2$s></noscript>',
					$source,
					$this->attributes_to_string($image->attributes,'src')));
		}

		$image->output = sprintf('<span data-picture data-alt="%3$s" %1$s>%2$s</span>',
			$this->attributes_to_string($image->attributes,'src,alt','span'),
			implode("\n", $sources),
			$image->attributes['alt']);

		return $image;
	}

	private function format_sources($options,$main_attributes = false)
	{
		// Compile the Picture tag set
		$sources = array();

		foreach ($options->sources as $key => $format) {

			array_push($sources,sprintf('<span data-src="%1$s" data-media="%2$s" %3$s></span>',
				$format->src,
				$format->media,
				$this->attributes_to_string($format->attributes,false,'span')));

			if($format->fallback){
				// We need to pull alt in
				$format->attributes['alt'] = $main_attributes['alt'];

				array_push($sources,sprintf('<noscript><img src="%1$s" %2$s></noscript>',
					$format->src,
					$this->attributes_to_string($format->attributes)));
			}
		}

		return sprintf('<span data-picture %2$s>%1$s</span>',
			implode("\n", $sources),
			$this->attributes_to_string($main_attributes,false,'span'));
	}

	private function convert_to_root_relative($src){
		$url_parts = explode('/', trim ( $this->page_url() ,'/' ));
		while(strpos($src, '../') === 0 && $url_parts != null){
			array_pop($url_parts);
			$src = substr($src, 3);
		}
		$src = implode('/', $url_parts) . '/' . $src;
		return $src;
	}

	private function page_url() {
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";

		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	private $valid_attributes = array(
		'global'=>array('accesskey','class','contenteditable','contextmenu','dir','draggable','dropzone','hidden','id','lang','spellcheck','style','tabindex','title'),
		'img'=>array('align','alt','border','crossorigin','height','hspace','ismap','longdesc','src','usemap','vspace','width')
		);

	private function attributes_to_string($attributes,$exclude = false,$element = 'img'){

		$output = array();
		$exclude = ($exclude === false) ? array('src') : explode(',', $exclude);
		foreach ($attributes as $key => $value) {
			$format = (
				in_array($key, $this->valid_attributes['global']) || 
				(isset($this->valid_attributes[$element]) && in_array($key, $this->valid_attributes[$element]))
				)
				? '%1$s="%2$s"'
				: 'data-%1$s="%2$s"';

			if(!in_array($key, $exclude)) array_push($output,sprintf($format,$key,$value));
		}
		return implode(' ', $output);
	}

	private function parse_options($options){
		// Can pass an array of formats, or a RIPOPtions object
		if(is_array($options) && count($options) > 0){
			$rip_options = new RIPOptions();

			if(isset($options[0]['query']))
				$rip_options->formats = $options;
			else if(isset($options[0]['src']))
				$rip_options->sources = $options;

			$options = $rip_options;
		}

		if($options === false || !is_a($options,'RIPOptions'))
			$options = $this->options;

		return $options;
	}
}