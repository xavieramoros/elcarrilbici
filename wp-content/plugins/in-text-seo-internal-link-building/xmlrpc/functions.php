<?php
/*
 * XML RCP extension with functions to update whitelist
 * 
 */
add_filter( 'xmlrpc_methods', 'mynamespace_new_xmlrpc_methods');

/**
 * List of extensiones about XMLRPC. Specifics function
 * @param type $methods
 * @return string
 */
function mynamespace_new_xmlrpc_methods( $oMethods ) 
{
    $oMethods['mynamespace.subtractTwoNumbers']  = 'mynamespace_subtractTwoNumbers';
    $oMethods['mynamespace.exampleLogin']        = 'mynamespace_exampleLogin';
    $oMethods['notedlinks.updatewl']             = 'notedlinks_updatewhitelist';
    return $oMethods;   
}

/**
 * Function to syncronize whitelist from dashboard
 * 
 * @param type $aArgs parametres
 * @return boolean true/false done/error
 */
function notedlinks_updatewhitelist($aArgs)
{
    require_once (CLASSES_PATH . "settings/class.settings.php");
    
    $oSettings  = Factory::create("settings");
    $aSettings  = $oSettings->getSetting();
    $bDebug     = $aSettings["process_page"]["debugging"];
    
    //Check request and content data
    $sSH         = $aArgs[0];
    $sUN         = $aArgs[1];
    $sPW         = $aArgs[2];
    $sWhitelist  = json_decode($aArgs[3]);
    
    $bDebug = 1;
    // Debug mode prints
    if ($bDebug != 0)
    {
        echo "\n"."WP: ".$aSettings['process_page']['nle']." | ".$aSettings['process_page']['api_key']." | ".$_SERVER["HTTP_HOST"]."\n";
        echo "\n"."WP: ".sha1($aSettings['process_page']['nle'])." | ".sha1($aSettings['process_page']['api_key'])." | ".sha1($_SERVER["HTTP_HOST"])."\n";
        echo "DOMAIN: ".sha1($_SERVER["HTTP_HOST"]) ."==". $sSH ."\n";
        echo "EMAIL:" .sha1($aSettings['process_page']['nle']) ."==". $sUN ."\n";
        echo "API KEY: ".sha1($aSettings['process_page']['api_key']) ."==". $sPW ."\n";
    }
   
    // update data
    if(sha1($aSettings['process_page']['nle']) === $sUN && sha1($aSettings['process_page']['api_key']) === $sPW && sha1($_SERVER["HTTP_HOST"]) === $sSH && isset($sWhitelist))
    {
        // update data
        require_once (MODEL_PATH . "class.modelWhiteList.php");
        $oModelWhiteList = Factory::create("modelWhiteList");
        $oModelWhiteList->updateWhiteList($sWhitelist);
        $aSettings['whitelist']['last_update'] = date('U');
        $oSettings->actualizaAllSettings($aSettings);
        return true;
    }
    // not update data
    else
    {
        return false;
    }
}

?>