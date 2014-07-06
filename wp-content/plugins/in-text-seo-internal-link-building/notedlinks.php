<?php
/*
  Plugin Name: In-text SEO internal link building
  Plugin URI: http://www.notedlinks.com
  Description: Create rules to add links in your post/articles automatically.
  Author: NotedLinks team
  Version: 0.8
  Author URI: http://www.notedlinks.com
 */

$dirName = dirname(__FILE__);
include_once ("config/config.inc.php");
require_once (CLASSES_PATH . "settings/class.settings.php");
require_once (CLASSES_PATH . "settings/class.loadscript.php");
require_once (CLASSES_PATH . "system/class.factory.php");
require_once (CLASSES_PATH . "system/class.error.php");
require_once (CLASSES_PATH . "system/class.utils.php");
require_once (CLASSES_PATH . "replace/class.replaceManagement.php");
require_once (CLASSES_PATH . "admin/class.notedLinksAdmin.php");
require_once ($dirName."/model/db_table/class.dbWhiteList.php");
require_once ($dirName."/xmlrpc/functions.php");

if(!function_exists('is_user_logged_in')) {
    include(ABSPATH . "wp-includes/pluggable.php"); 
}

//Debug mode?
$oSettings = Factory::create("settings");
$aSettings = $oSettings->getSetting();
$bDebug = $aSettings["process_page"]["debugging"];

header('Access-Control-Allow-Origin: *');

try {
    //getting wp version
    $wpVersion = get_bloginfo("version");
    if ($bDebug != 0) echo "<!-- [index] wp version = $wpVersion -->\n";
    //checking if notedlinksAdmin class exists
    if (class_exists('notedLinksAdmin')) :
        
        if ($bDebug != 0) echo "<!-- [index] class_exists('notedLinksAdmin') then creating notedLinksAdmin obj -->\n";
        //instance of notedlinksAdmin to manage plugin
        $objNotedlinksAdmin = Factory::create("notedLinksAdmin");
        
        if (isset($objNotedlinksAdmin)) 
        {
            //add action to ignore notices
            add_action( 'admin_init', array($objNotedlinksAdmin, 'notedlinks_nag_ignore'), 3 );
            //add action to check if wordpress is updated
            add_action( 'admin_init', array($objNotedlinksAdmin, 'checkUpdateWp'), 3 );
            
            if ($bDebug != 0) echo "<!-- [index] isset(\$objNotedlinksAdmin) then activating plugin and calling 'install' function -->\n";
            
            if ($bDebug != 0) echo "<!-- [index] register_activation_hook(__FILE__, array(\$objNotedlinksAdmin, 'install')) -->\n";
            //function to invoke install function when the plugin is activated
            register_activation_hook(__FILE__, array($objNotedlinksAdmin, 'install'));
            //function to invoke nl_deactivation function when the plugin is disabled
            register_deactivation_hook(__FILE__, array($objNotedlinksAdmin, 'nl_deactivation'));
        }
    endif;

    //function to loading scripts
    function load_scripts() {
        //getting settings of plugin
        $oSettings = Factory::create("settings");
        $aSettings = $oSettings->getSetting();
        $bDebug = $aSettings["process_page"]["debugging"];

        if ($bDebug != 0) echo "<!-- [index] Init Load scripts: -->\n";
        
        if ($bDebug != 0) echo "<!-- [index] Creating settings obj -->\n";

        if ($bDebug != 0) {
            echo "<!-- [index] Getting settings: -->\n<!--";
            print_r($aSettings);
            echo " -->\n";
        }

        $sToken = uniqid();

        if ($bDebug != 0)  echo "<!-- [index] token = $sToken -->\n";
        $sApiKey = $aSettings["process_page"]["api_key"];
        if ($bDebug != 0)  echo "<!-- [index] api key = $sApiKey -->\n";
        $iIdWidget = $aSettings["process_page"]["id_widget"];
        if ($bDebug != 0)  echo "<!-- [index] idwidget = $iIdWidget -->\n";
        $iIdSite = $aSettings["process_page"]["id_site"];
        if ($bDebug != 0)  echo "<!-- [index] id site = $iIdSite -->\n";

        //script needed to tracking stats
        echo "<script language='Javascript' type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.js'></script>".
        "<script language='Javascript' type='text/javascript' src='http://stats.notedlinks.com/piwik.js'></script>".
        "<script language='Javascript' type='application/javascript' src='".URL_NOTEDLINKS."/stats'></script>".
        "<script language='Javascript' type='text/javascript'>
            hash_param.init('$sApiKey', $iIdWidget, $iIdSite, \"nl_wp_" . $sToken . "\");
            hash_piwik.hash_topicStats[\"topics\"] = {};
            hash_core.init();
        </script>";
    }

    //instance of Replace Management
    if ($bDebug != 0) echo "<!-- [index] class_exists('replaceManagement') then creating replaceManagement obj -->\n";

    if ( class_exists('replaceManagement') ) :
        $objReplaceManagement = Factory::create("replaceManagement", $_SERVER["SERVER_NAME"]);
        if ( isset($objReplaceManagement) ) {
           // if ($bDebug != 0) echo "<!-- [index] isset(\$objReplaceManagement) then adding filter to replace and loading scripts -->\n";
            //invoking filters of WP's API to replace the content

            //add_filter('the_excerpt', array($objReplaceManagement, 'replaceText'), $aAux["process_page"]["the_content"]["priority"]);
            //adding filter to the content of posts to execute replaceText function
            if ( intval($aSettings["process_page"]["the_content"]["content_scraping"]) > 0 ) {
                add_filter('the_content', array($objReplaceManagement, 'replaceText'), $aSettings["process_page"]["the_content"]["priority"]);
                //add_filter('the_excerpt', array($objReplaceManagement, 'replaceText'), $aAux["process_page"]["the_content"]["priority"]);
                $iPriority = $aSettings['process_page']['the_content']['priority'];
                if ( $bDebug != 0 ) echo "<!-- [index] Added 'replaceText' to the_content of WP with priority $iPriority -->\n";
            }
            //adding filter to the comments of posts to execute replaceText function
            if ( intval($aSettings["process_page"]["comment_text"]["comment_scraping"]) > 0 ) {
                add_filter('comment_text', array($objReplaceManagement, 'replaceText'), $aSettings["process_page"]["comment_text"]["priority"]);
                $iPriority = $aSettings["process_page"]["comment_text"]["priority"];

                if ($bDebug != 0) echo "<!-- [index] Added 'replaceText' to comment_text of WP with priority $iPriority -->\n";
            }
            //adding execution of load_scripts function in headers of wordpress site
            if ( intval($aSettings["process_page"]["the_content"]["content_scraping"]) > 0 
                || intval($aSettings["process_page"]["comment_text"]["comment_scraping"]) > 0 ) {
                //adding execution of load_scripts function in headers of wordpress site
                add_action('wp_head', 'load_scripts');

                if ( $bDebug != 0 ) {
                    echo "<!-- [index] Added action to execute 'load_scripts' function in 'head' of pages -->\n";
                    echo "<!-- [index] add_action('wp_head', 'load_scripts') -->\n";
                }
            }
        }
    endif;

    if ( $bDebug != 0 && !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php') ) ) echo "<!-- End plugin [index] -->\n";

} catch ( Exception $e ) {
    print_r("<!--Error in plugin index page!: " . $e->getMessage() . " -->");
}
