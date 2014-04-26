<?php

/*
 *	Configuration Page
 *
 *
 */

define('RIP_CONFIG', __FILE__);

class RIPConfig{

	public static $rip_nonce = "rip_update";
	
	private static $_singleton;
	
	public static function get_instance()
	{
		if(!isset(self::$_singleton)){
			self::$_singleton = new RIPConfig();
		}
		return self::$_singleton;
	}
	
	/* End Static Methods */
	private function __construct()
	{
		add_action('admin_init',array(&$this,'admin_init'));
		add_action('admin_menu',array(&$this,'admin_menu'));
	}

	private function save_settings()
	{
		// Verify Nonce
		if ( !isset($_POST['rip_submit']) || !wp_verify_nonce( $_POST[self::$rip_nonce], plugin_basename(RIP_CONFIG) )) return;

		// check permissions
		if ( !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));
		
		$options = RIPOptions::options();
		
		// If we're restoring defaults
		if($_POST['submit'] == 'Restore Defaults'){
			$options->setDefaults();
			$options->shutdown();
			header('Location: options-general.php?page=rip_options&updated=true');
			exit();
		}
		
		// Scrub through all set parameters
		foreach($options->getDefaults() as $parameter => $default){
			// See if it's set
			$value = @$_POST["rip::$parameter"];
			if(!isset($value)) $value = '';

			// Scrub
			switch($parameter){
				case 'disable_wordpress_resize':
				case 'enable_scripts':
					$value = ($value == 'on') ? true : false;
					break;
				case 'slir_base':
					$value = mysql_escape_string($value);
					break;
				default:
					continue 2;
			}
			$options->$parameter = $value;
		}

		// Import all formats
		$formats = array();
		for($int = 0; isset($_POST["rip::format::$int::query"]); $int++){
			$media = @$_POST["rip::format::$int::media"];
			$query = @$_POST["rip::format::$int::query"];
			$fallback = (@$_POST["rip::format::$int::fallback"] == 'on');
			$trashed = (@$_POST["rip::format::$int::trashed"] == 'on');
			$key = @$_POST["rip::format::$int::order"];

			if(!$trashed)
				$formats[$key] = array(
					'media' => $media,
					'query' => $query,
					'fallback' => $fallback
				);
		}

		ksort($formats);
		$options->formats = array_values($formats);
		
		$return_vars = "?page=rip_options&updated=true"; // success

		//redirect
		header('Location: options-general.php'.$return_vars);
		exit();
	}

	/*
	 *	Callbacks
	 */

	// All postback actions here
	public function admin_init()
	{
		// Add our admin scripts
		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {
			add_filter( 'mce_external_plugins',array(&$this,'mce_external_plugins'),30);
		}
		
		//Add to plugins menu
		if ( current_user_can('manage_options') )
			add_filter('plugin_action_links',array(&$this,'plugin_action_links'),10,2);

		// Show if we've made modifications in the media section
	    if(RIPOptions::options()->disable_wordpress_resize){
	    	wp_enqueue_script('jquery');
	    	add_action('admin_footer-options-media.php',array(&$this,'admin_footer_options_media'),20);
	    }

		//Save
		$this->save_settings();
	}

	public function mce_external_plugins($plugin_array)
	{
		$plugin_array['pbeditimage'] = plugins_url('/scripts/editor-button.js', __FILE__);
		return $plugin_array;
	}
	
	public function admin_print_styles()
	{
		wp_enqueue_script( 'jquery-ui-sortable' );
		//TODO: Embed css/javascript here
	}

	public function admin_load()
	{
		require_once(dirname(__FILE__).'/config-tabs.php');
	}

	public function admin_footer_options_media(){
		// Show some addtional info on the media page
		printf('<script type="text/javascript">
			jQuery(document).ready(function($){
				$("form:first").each(function(){
					$("p:first",this).append(" This section has been disabled by the PB Responsive Images Plugin. <a href=\'%1$s\'>Click Here to enable image sizes</a>.");
					$("table.form-table:first input").attr("disabled","disabled");
					$("table.form-table:first").css("opacity",.5);
				});
			});
		</script>','options-general.php?page=rip_options');
	}

	// Add to Menu
	public function admin_menu()
	{
		$page = add_options_page( __('Responsive Images'), __('Responsive Images'), 'manage_options', 'rip_options', array(&$this,'config_page'));
		
		add_action('admin_print_styles-'.$page,array(&$this,'admin_print_styles'));
	    add_action('load-'.$page,array(&$this,'admin_load'));
	}

	// Tag deactivate link to allow for customization, add settings link
	public function plugin_action_links($links, $file) {
		if( $file == RIP_BASENAME ){
			$links['deactivate'] = preg_replace("/(title=)/","id=\"rip_deactivate\" $1",$links['deactivate']);
			$settings_link = '<a href="options-general.php?page=rip_options">Settings</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/*
	 * Config Page
	 */
	 public function config_page()
	 {
		require_once(dirname(__FILE__).'/config-page.php');
	 }
}

RIPConfig::get_instance();