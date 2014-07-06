<?php

class settings {

    //ID de opciones en BD de WP
    private $sId_NL_options = "notedlinks_0.8";
    private $bDebug = 0;

    public function __construct() {
        $aSettings = $this->getSetting();
        $this->bDebug = $aSettings["process_page"]["debugging"];
    }

    /**
     * Funcion que instala las opciones en la BD de WP, se invoca la funcion cuando se realiza la activacion del plugin
     */
    public function initSettings() {
        try {
            //una vez los valores estan guardados en BD de WP no se vuelven a insertar valores por defecto
            if ($this->bDebug != 0)
                echo "<!-- [settings] initSettings function: -->\n";
            if ($this->bDebug != 0)
                echo "<!-- [settings] getting options $sId_NL_options -->\n";

            $aAux = get_option($this->sId_NL_options);

            if ($this->bDebug != 0) {
                echo "<!-- [settings] options values: -->\n<!--";
                print_r($aAux);
                echo " -->\n";
            }

            if (null == $aAux && !is_array($aAux)) {
                $bRes = update_option($this->sId_NL_options, $this->getOptions());
                if ($this->bDebug != 0) {
                    echo "<!-- [settings] (options = null) then update_option($this->sId_NL_options, \$this->getOptions()) = -->\n<!--";
                    var_dump($bRes);
                    echo " -->\n";
                }
            }
        } catch (Exception $e) {
            print_r("<!--Error initSettings!: " . $e->getMessage() . " -->\n");
        }
    }

    /**
     * Funcion que crea y devuelve un array con las opciones por defecto
     * 	@return array - opciones
     */
    public function getOptions() {
        try {
            //Array con valores de configuracion por defecto
            if ($this->bDebug != 0)
                echo "<!-- [settings] getOptions function: -->\n";
            $aConfig = array();
            //-----Opciones process page
            $aProcessPage = array();
            $aProcessPage["active"] = false;
            $aProcessPage["api_key"] = false;
            $aProcessPage["token"] = "n0t3dl1nks";
            $aProcessPage["nle"] = "";
            $aProcessPage["id_widget"] = 0;
            $aProcessPage["id_site"] = 0;
            $aProcessPage["debugging"] = 0;
            $aProcessPage["first_activation"] = 0;
            $aProcessPage["activation_date"] = date("Y-m-d H:i:s");
            $aProcessPage["the_content"] = array("priority" => 99, "content_scraping" => 1);
            $aProcessPage["comment_text"] = array("priority" => 99, "comment_scraping" => 0);
            $aConfig["process_page"] = $aProcessPage;
            //-----Opciones ignored pages, las paginas en las cuales no subrayamos, por defecto está vacío
            $aConfig["ignore_page"] = "";
            //check valid key
            $aConfig["valid_key"] = 0;
            //token access server
            $aConfig["token_access"] = hash('sha256', 'n0t3dl1nks');
            //save wordpress version
            $aConfig["wp_version"] = get_bloginfo("version");
            //-----Opciones de limite de aparicion de links
            $aAppearanceLimit = array();
            //Limit maximum number of links created with the same keyword. Set to 0 for no limit
            $aAppearanceLimit["keyword_max"] = 1;
            //Limit number of same URLs the plugin will link to. Works only when Max Single above is set to 1. Set to 0 for no limit
            $aAppearanceLimit["url_max"] = 0;
            //Maximo numero de links por pagina
            $aAppearanceLimit["page_max"] = 5;
            $aConfig["appearance_limit"] = $aAppearanceLimit;
            //----- Opciones de matching, de momento no disponible
            $aMatchingSettings["matching_sensitive"] = 1;
            $aMatchingSettings["excluding_heading"] = 1;
            $aConfig["matching"] = $aMatchingSettings;
            //----- Opciones de links externos
            $aExternalLink = array();
            //Notedlinks can open external links in new window and add nofollow attribute
            $aExternalLink["external_nofollow"] = 0;
            $aExternalLink["external_target"] = 0;
            $aConfig["external"] = $aExternalLink;
            //array con opciones whitelist
            $aWhiteList = array();
            //initial update time to update plugin
            $aWhiteList["last_update"] = date('U');
            //actualizacion ultima modificacion. FILESIZE por defecto es 0, lo cual fuerza actualizacion automatica
            $aWhiteList["hash_update"] = 0;
            //initial update time of links statistics
            $aWhiteList["last_update_stats"] = date('U') - 86399;
            //interval time to update the plugin automatically --> 24h (86399 seg)
            $aWhiteList["interval_update"] = 86399;
            $aConfig["whitelist"] = $aWhiteList;
            if ($this->bDebug != "0" && $this->bDebug != 0 && !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
                echo "<!-- [settings] Default values: -->\n<!-- ";
                //print_r($aConfig);
                echo " -->\n";
            }
            return $aConfig;
        } catch (Exception $e) {
            print_r("<!--Error get options!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * Funcion que retorna los settings actuales
     * @return $aConfig - array 
     */
    public function getSetting() {
        if ($this->bDebug != 0 && !is_admin() &&!in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')))
        {
            echo "<!-- [settings] getSetting function: -->\n";
        }
        $aConfig = $this->getOptions();
        $aAux = get_option($this->sId_NL_options);
        if (null != $aAux && is_array($aAux))
        {
            $aConfig = $aAux;
        }
        if ($this->bDebug != 0 && !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) 
        {
            echo "<!-- [settings] Current values: -->\n<!-- ";
            print_r($aConfig);
            echo " -->\n";
        }
        return $aConfig;
    }

    /**
     * Funcion que actualiza todos los settings
     * @param $aSettings - array: array con settings
     */
    public function actualizaAllSettings($aSettings) 
    {
        try 
        {
            if ($this->bDebug != 0)
            {
                echo "<!-- [settings] actualizaAllSettings function: -->\n";
            }
            //checking settings values before update them
            $aSettings = $this->checkSettings($aSettings);

            $bRes = update_option($this->sId_NL_options, $aSettings);
            if ($this->bDebug != 0) 
            {   
                echo "<!-- [settings] update_option($this->sId_NL_options, \$aSettings) =  -->\n<!--";
                var_dump($bRes);
                echo " -->\n";
            }
        } 
        catch (Exception $e) 
        {
            print_r("<!--Error actualizaAllSettings!: " . $e->getMessage() . " -->");
        }
    }


    /**
     * Resetea los settings de configuracion del plugin
     * @return $aSettings devuelve el array de settings o una excepcion en caso de error
     * @throws Exception Salta si se produce una excepcion php en la ejecucion del codigo
     */
    public function resetSettings()
    {
        try
        {
            $aSettings = $this->getSetting();

            $aSettings["process_page"]["debugging"] = 0;
            $aSettings["process_page"]["the_content"] = array("priority" => 99, "content_scraping" => 1);
            $aSettings["process_page"]["comment_text"] = array("priority" => 99, "comment_scraping" => 0);

            $aSettings["ignore_page"] = "";

            $aAppearanceLimit = array();
            //Limit maximum number of links created with the same keyword. Set to 0 for no limit
            $aAppearanceLimit["keyword_max"] = 1;
            //Limit number of same URLs the plugin will link to. Works only when Max Single above is set to 1. Set to 0 for no limit
            $aAppearanceLimit["url_max"] = 0;
            //Maximo numero de links por pagina
            $aAppearanceLimit["page_max"] = 5;
            $aSettings["appearance_limit"] = $aAppearanceLimit;
            //----- Opciones de matching, de momento no disponible
            $aMatchingSettings["matching_sensitive"] = 1;
            $aMatchingSettings["excluding_heading"] = 1;
            $aSettings["matching"] = $aMatchingSettings;
            //----- Opciones de links externos
            $aExternalLink = array();
            //Notedlinks can open external links in new window and add nofollow attribute
            $aExternalLink["external_nofollow"] = 0;
            $aExternalLink["external_target"] = 0;
            $aSettings["external"] = $aExternalLink;
            //array con opciones whitelist
            $aWhiteList = array();
            //update time
            $aWhiteList["last_update"] = date('U');
            //actualizacion ultima modificacion. FILESIZE por defecto es 0, lo cual fuerza actualizacion automatica
            $aWhiteList["hash_update"] = 0;
            //Intervalo en el cual se actualiza el plugin 24h (86399 seg)
            $aWhiteList["interval_update"] = 86399;
            $aSettings["whitelist"] = $aWhiteList;

            //$this->actualizaAllSettings($aSettings);

            return $aSettings;
        }
        catch (Exception $e)
        {
            print_r("<!--Error resetSettings!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * checkSettings: this function checks settings values of plugin configuration, before update them
     * @param  array $aSettings settings before check them
     * @return array            settings after check them
     * @throws Exception If fails any settings value
     */
    public function checkSettings($aSettings)
    {
        try
        {
            if(!is_numeric($aSettings["appearance_limit"]["keyword_max"]) 
                || $aSettings["appearance_limit"]["keyword_max"] < 0 
                || $aSettings["appearance_limit"]["keyword_max"]>30 )
            {
                $aSettings["appearance_limit"]["keyword_max"] = 1;
            }

            if($aSettings["appearance_limit"]["page_max"] < 0 
                || !is_numeric($aSettings["appearance_limit"]["page_max"]) )
            {
                $aSettings["appearance_limit"]["page_max"] = 5;
            }
            elseif ($aSettings["appearance_limit"]["page_max"] > 30) 
            {
                $aSettings["appearance_limit"]["page_max"] = 30;
            }

            return $aSettings;

        }
        catch(Exception $e)
        {
            print_r("<!--Error checkSettings!: " . $e->getMessage() . " -->");
        }
    }
}
