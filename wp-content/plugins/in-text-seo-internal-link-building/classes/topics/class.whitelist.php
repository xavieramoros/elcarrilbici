<?php
//MODEL_PATH DB_TABLE_PATH
require_once (MODEL_PATH . "class.modelWhiteList.php");

class whitelist 
{
    //REFERENCIA A MODELO DE WHITELIST
    private $oModelWhiteList;
    private $bDebug = 0;
    private $oSettings;
    /**
     * CONSTRUCTOR
     */
    public function __construct() {
        $this->oSettings = Factory::create("settings");
        $aSettings = $this->oSettings->getSetting();
        $this->bDebug = $aSettings["process_page"]["debugging"];
        if ($this->bDebug != 0) echo "<!-- [whitelist] class constructor: -->\n";
        $this->oModelWhiteList = Factory::create("modelWhiteList");
        if ($this->bDebug != 0) echo "<!-- [whitelist] Created modelWhitelist \$this->oModelWhiteList -->\n";
    }

    /**
     * Funcion que comprueba si hace falta actualizar whitelist
     * @param $aSettingsWhitelist - array: setttings de whitelist (last_update, hash_update)
     * @return $aAux - array: array de whitelist actualizada actualizada
     */
    public function checkWhiteList($aSettingsWhitelist) {
        if ($this->bDebug != 0) { 
            echo "<!-- [whitelist] checkWhiteList function -->\n";
            echo "<!-- [whitelist] settings whitelist: -->\n<!-- ";
            print_r($aSettingsWhitelist);
            echo " -->\n";
        }
        
        $aAux = $aSettingsWhitelist;
        $aSetting = $this->oSettings->getSetting();
        
        if ($this->bDebug != 0) echo "<!-- [whitelist] Updating whitelist if (date('U') - \$aAux['last_update']) > \$aAux['interval_update'] -->\n";
        
        if ((date('U') - $aAux['last_update']) > $aAux['interval_update']) {
            if ($this->bDebug != 0) echo "<!-- [whitelist] calling actualizaWhiteList(\$aAux['hash_update']) -->\n";
            $aAux['hash_update'] = $this->actualizaWhiteList($aAux['hash_update'], $aSetting["process_page"]['api_key']);
            if ($this->bDebug != 0) echo "<!-- [whitelist] new hash_update = ".$aAux['hash_update']." -->\n";
            $aAux['last_update'] = date('U');
            if ($this->bDebug != 0) echo "<!-- [whitelist] new last_update = ".$aAux['last_update']." -->\n";
        }
        
        if ($this->bDebug != 0 && !in_array($GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php'))){ 
            echo "<!-- [whitelist] Returning new settings whitelist: -->\n<!-- ";
            print_r($aAux);
            echo " -->\n";
        }
        return $aAux;
    }

    /**
     * Funcion que actualiza manualmente la whitelist
     * @param $aSettingsWhitelist - array: setttings de whitelist (last_update, hash_update)
     * @return $aAux - array: array de whitelist actualizada
     */
    public function actualizaManual($aSettingsWhitelist) 
    {
        $aSetting = $this->oSettings->getSetting();
        if ($this->bDebug != 0) echo "<!-- [whitelist] actualizaManual function -->\n";
        $aAux = $aSettingsWhitelist;
        if ($this->bDebug != 0) echo "<!-- [whitelist] calling \$this->actualizaWhiteList() -->\n";
        $aAux['hash_update'] = $this->actualizaWhiteList(0,$aSetting['process_page']['api_key']);
        if ($this->bDebug != 0) echo "<!-- [whitelist] new hash_update = ".$aAux['hash_update']." -->\n";
        $aAux['last_update'] = date('U');
        if ($this->bDebug != 0) echo "<!-- [whitelist] new last_update = ".$aAux['last_update']." -->\n";
        
        return $aAux;
    }

    /**
     * Function que actualiza DB con los topics del servidor
     * @param $sServerName - string: server name del cliente, es nombre de nuestro fichero csv
     * @param $sLastActualizacion - string: el hash de ultima actualizacion - si es 0 no lo hace comprobacion de hash
     */
    private function actualizaWhiteList($sLastActualizacion = 0, $sApiKey) 
    {
        try 
        {
            if ($this->bDebug != 0) echo "<!-- [whitelist] actualizaWhiteList function -->\n";
            $sResultadoLastActualizacion = $sLastActualizacion;
            //cuando llamamos pasamos el hash de comprobacion de actualizacion = SH256(ultima modif + filesize)
            $aParameters = array(
                "serverName"    => str_replace("www.", "", $_SERVER["SERVER_NAME"]),
                "hash"          => $sLastActualizacion,
                "nlkey"         => $sApiKey,
                "serverWeb"     => WEB_NOTEDLINKS
            );
            if ($this->bDebug != 0) { 
                echo "<!-- [whitelist] curl request parameters: -->\n <!--";
                print_r($aParameters);
                echo " -->\n";
            }
            if ($this->bDebug != 0) echo "<!-- [whitelist] init curl request to ".URL_NOTEDLINKS." -->\n";
            $sToken = uniqid();
            $ch = curl_init(URL_NOTEDLINKS."/".$sToken);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $returndata = curl_exec($ch);
            curl_close($ch);

            if ($this->bDebug != 0) {
                echo "<!-- [whitelist] Whitelist data response: -->\n<!-- ";
                print_r($returndata);
                echo " -->\n";
            }
            
            if (null != $returndata && strpos($returndata, "@@") !== false) {
                if ($this->bDebug != 0) {
                    echo "<!-- [whitelist] \$returndata != null (no empty) && strpos(\$returndata, '@@') !== false ('@@' into response) -->\n";
                    echo "<!-- [whitelist] Exploding data response by '@@' separator: -->\n";
                }
                $aRespuesta = explode("@@", $returndata);
                if ($this->bDebug != 0) {
                    echo "<!-- [whitelist] Response: \n";
                    print_r($aRespuesta);
                    echo " -->\n";
                }
                
                if (sizeof($aRespuesta) > 1) {
                    if ($this->bDebug != 0) echo "<!-- [whitelist] Update whitelist: \$this->oModelWhiteList->updateWhiteList(json_decode($aRespuesta[0])); -->\n";
                    $this->oModelWhiteList->updateWhiteList(json_decode($aRespuesta[0]));
                    
                }
                $sResultadoLastActualizacion = sizeof($aRespuesta) > 1 ? $aRespuesta[1] : $sResultadoLastActualizacion;
                if ($this->bDebug != 0) {
                    echo "<!-- [whitelist] Last Update result: \n";
                    print_r($sResultadoLastActualizacion);
                    echo " -->\n";
                }
            }
            //falta actualizar pagecache
            return $sResultadoLastActualizacion;
        } catch (Exception $e) {
            print_r("<!--Error actualiza Whitelist!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * FUNCTION QUE DEVUELVE EL ARRAY CON WHITE LIST
     * @param array - $aWhiteList 
     */
    public function getWhitelist() {
        return $this->oModelWhiteList->getWhiteList();
    }
}
?>