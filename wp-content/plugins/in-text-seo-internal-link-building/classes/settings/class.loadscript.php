<?php

/*
 * Class for load js files in wordpress
 */

class Loadscript {

    static $add_script;

    public function init() {
        //add_shortcode('myjavascript', array(__CLASS__, 'handle_javascript'));
        //add_action('init', array(__CLASS__, 'register_script'));
        add_action('wp_enqueue_scripts', 'register_script');
        //add_action('wp_enqueue_scripts', array(__CLASS__, 'print_script'));
        add_action('wp_enqueue_scripts', 'print_script');
    }

    static function handle_javascript($atts) {
        echo "Hello handle script<br>";
        self::$add_script = true;
    }

    public function register_script() {
        try {
            echo "Register scripts...";
            wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.js', FALSE, '');
            //wp_register_script('jquery', plugins_url('../../js/jquery.js', __FILE__), array(), '1.0', true);
            wp_register_script('piwik', 'http://stats.notedlinks.com/piwik.js', FALSE, '');
            //wp_register_script('piwik', plugins_url('../../js/piwik.js', __FILE__), array('jquery'), '1.0', true);
            wp_register_script('stats', JS_PATH . 'stats.js', array('jquery'), '');
            //wp_register_script('stats', plugins_url('../../js/stats.js', __FILE__), array('jquery'), '1.0', true);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function print_script() {
        // if (!self::$add_script)
        //   return;

        echo "Enqueuing scripts...";
        wp_enqueue_scripts('jquery', JS_PATH . 'jquery.js', array());
        wp_enqueue_scripts('piwik', JS_PATH . 'piwik.js', array('jquery'));
        wp_enqueue_scripts('stats', JS_PATH . 'stats.js', array('jquery'));
    }

}

?>