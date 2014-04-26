<?php

class Utils {

    public function __construct() {
        
    }

    public static function curPageURL() {
        try {
            $pageURL = 'http';
            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            }
            //echo $pageURL;
            return $pageURL;
        } catch (Exception $e) {
            print_r("<!--Error curlPageUrl!: " . $e->getMessage() . " -->");
        }
    }

    function explode_trim($separator, $text) {
        $arr = explode($separator, $text);
        $ret = array();
        foreach ($arr as $e) {
            $ret[] = trim($e);
        }
        return $ret;
    }

}
?>