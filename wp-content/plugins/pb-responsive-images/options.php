<?php

// Options Object
class RIPOptions
{
	private static $_defaults;
	
	public static function options()
	{
		if(!isset(self::$_defaults)){
			self::$_defaults = new RIPOptions();
		}
		return self::$_defaults;
	}
	
	/* End Static Methods */
	
	private $_options = array();
	private $_modified = false;

	public function __isset($key){
		return isset($this->_options);
	}

	public function __unset($key){
		unset($this->_options[$key]);
	}
	
	public function __get($key) {

		if($key == 'modified') return $_modified;
		
		if (array_key_exists($key, $this->_options)){
			$value = $this->_options[$key];
			
			//Filter Values

			switch($key){
				case 'slir_base':
					$value = $this->filterFileNameOut($value);
					break;
			}
			
			return $value;
		}
		throw new ErrorException(sprintf('Undefined property via __get() [%1$s]',$key));
	}
	
    public function __set($key, $value) {

    	// Filter Input
    	$defaults = $this->getDefaults();
		
		switch($key){
			case 'formats':
				if(!is_array($value)) throw new ErrorException('options->formats must be an array');
				foreach ($value as $format_key => $source) {
					$format = (object)wp_parse_args($source, array(
						'media'=>'',
						'query'=>'',
						'fallback'=>false
						));
					
					// Browser prefixes....yum
					$format->media = $this->filterBrowserPrefixesIn($format->media,array('webkit','moz','o'),'min-device-pixel-ratio');

					$value[$format_key] = $format;
				}
				break;
			case 'sources':
				if(!is_array($value)) throw new ErrorException('options->sources must be an array');
				foreach ($value as $source_key => $source) {
					$source = (object)wp_parse_args($source, array(
						'media'=>'',
						'src'=>'',
						'attributes'=>array(),
						'fallback'=>false
						));
					
					// Browser prefixes....yum
					$source->media = $this->filterBrowserPrefixesIn($source->media,array('webkit','moz','o'),'min-device-pixel-ratio');
					
					$value[$source_key] = $source;
				}
				break;
			case 'slir_base':
				if($value == '') $value = $defaults['slir_base'];
				$value = $this->filterFileNameIn($value);
				break;
			case 'disable_wordpress_resize':
			case 'enable_scripts':
				if($value !== false) $value = true;
				break;
			default:
				throw new ErrorException(sprintf('Undefined property via __set() [%1$s]',$key));
				break;
		}
        $this->_options[$key] = $value;
		$this->_modified = true;
    }
	
	public function shutdown()
	{
		if($this->_modified && $this === self::$_defaults){
			global $wp_rewrite;
			if(is_admin()) $wp_rewrite->flush_rules();

			update_option('rip_options',$this->_options);
		}
	}

	public function getDefaults()
	{
		// Need to make sure the fallback load image (preferrably smallest) is flagged "fallback", when set externally 
		// Order is important, similar to css. It will display the last matched image
		// These formats are optimized for the Twenty Eleven theme's layout, for demonstration purposes
		global $wp_rewrite;

		return array(
			"enable_scripts"=>true,
			"disable_wordpress_resize"=>false,
			"formats"=>array(
				(object)array("media"=>"all" ,"query"=>"w300","fallback"=>true),
				(object)array("media"=>"(min-width:420px)" ,"query"=>"w470",),
				(object)array("media"=>"(min-width:600px) and (max-width:800px)","query"=>"w584"),
				(object)array("media"=>"(min-width:885px)","query"=>"w584"),
				),
			"slir_base"=>'',
			);
	}
	
	public function setDefaults()
	{
		$this->_options = $this->getDefaults();
		$this->_modified = true;
	}
	
	public function filterFileNameOut(&$name)
	{
		$name = str_replace(
			array('{plugin}','{theme}','{plugin-url}','{base-url}'),
			array(dirname(__FILE__),get_theme_root(),plugins_url('',RIP_FILE),site_url()),
			$name);
		return $name;
	}
	
	public function filterFileNameIn(&$name)
	{
		$name = str_replace(
			array(dirname(__FILE__),get_theme_root(),plugins_url('',RIP_FILE),site_url()),
			array('{plugin}','{theme}','{plugin-url}','{base-url}'),
			$name);
		return $name;
	}

	public function filterBrowserPrefixesOut($source,$prefixes,$rule)
	{
		$source = explode(',', $source);
		$match = sprintf('/-(%1$s)-%2$s/',implode('|', $prefixes),$rule);
		$output = array();

		// Filter out any source that has a browser prefix
		foreach ($source as $key => $value) {
			if(!preg_match($match, $value)) $output []= $value;
		}

		return implode(',', $output);
	}

	public function filterBrowserPrefixesIn($source,$prefixes,$rule)
	{
		$match = sprintf('/-(%1$s)-%2$s/',implode('|', $prefixes),$rule);
		$search = sprintf('#(.*)(%1$s:\d+)(.*)#i',$rule);
		$replace = sprintf('$1$2$3, $1-%1$s-$2$3',implode('-$2$3, $1-', $prefixes));

		if(!preg_match($match, $source)) $source = preg_replace($search, $replace, $source);

		return $source;
	}
	
	public function __construct()
	{
		$this->_options = get_option('rip_options');
		if(count($this->_options) == 0)
			$this->setDefaults();
		
		add_action('shutdown',array(&$this,'shutdown'));
	}
	
	/* End Public Methods */
}