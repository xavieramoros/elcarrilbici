<?php
/*
 * Shortcode Parser
 *
 * -------------------------------------
 *
 * @package PB Responsive Images
 * @category Plugin
 * @author Jacob Dunn
 * @link http://www.phenomblue.com/ Phenomblue
 * @version 1.4
 *
 * -------------------------------------
 *
 * Parses shortcodes of either:
 *
 * [RIPImage src="/images/an-image.png" alt="Alt text"]
 *
 * or
 *
 * [RIPImage src="/images/an-image.png" alt="Alt text" ]
 *	 [RIPFormat media="(max-width:100px)" query="w70" fallback="true"]
 *	 [RIPFormat media="(max-width:200px)" query="w170"]
 *	 [RIPFormat media="(max-width:300px)" query="w270"]
 * [/RIPImage]
 *
 * or
 *
 * [RIPImage alt="Alt text" ]
 *	 [RIPSource src="/images/an-image_1.png" media="(max-width:100px)" fallback="true"]
 *	 [RIPSource src="/images/an-image_2.png" media="(max-width:200px)"]
 *	 [RIPSource src="/images/an-image_3.png" media="(max-width:300px)"]
 * [/RIPImage]
 *
 */

class RIPShortcodes{
	static $attributes;

	public static function register()
	{
		add_shortcode('RIPImage','RIPShortcodes::RIPImage');
		add_shortcode('RIPSource','RIPShortcodes::RIPSource');
		add_shortcode('RIPFormat','RIPShortcodes::RIPFormat');
	}

	public static function RIPImage($attributes, $content = ''){
		$rip = RIP::get_manager();

		if($content === '') return RIP::get_picture($attributes);

		$content = preg_replace('!(<br[^>]*/?>|\n|\r)!','',$content);
		self::$attributes = $attributes;
		
		if(preg_match('!RIPSource!i', $content)){
			$options = '['.trim(do_shortcode($content)," \t\n\r\0\x0b,").']';
			$options = json_decode($options,true);
			return RIP::get_picture($options,$attributes);
		}else if(preg_match('!RIPFormat!i',$content)){
			$options = '['.trim(do_shortcode($content)," \t\n\r\0\x0b,").']';
			$options = json_decode($options,true);
			return RIP::get_picture($attributes,$options);
		}

		return sprintf('<pre>%1$s</pre>',$content);//__('Invalid Content passed into RIPImage');
	}

	public static function RIPFormat($attributes, $content = ''){
		return json_encode($attributes).',';
	}

	public static function RIPSource($attributes, $content = ''){
		$source = array();
		$additional = array();
		$valid = array('src','media','fallback');

		foreach ($attributes as $key => $value) {
			if(in_array($key, $valid)) $source[$key] = $value;
			else $additional[$key] = $value;
		}

		$source['attributes'] = $additional;

		return json_encode($source).',';
	}
}

RIPShortcodes::register();