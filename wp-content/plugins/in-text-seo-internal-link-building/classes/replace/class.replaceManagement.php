<?php

/**
 * Clase que hace de manager, trabaja con whitelist y topics y utiliza los settings
 */
require_once (CLASSES_PATH . "topics/class.whitelist.php");
//require_once (CLASSES_PATH . "topics/class.topic.php");
require_once (CLASSES_PATH . "system/class.utils.php");

class replaceManagement {

    private $sServerName;
    private $aTopics;
    private $oTopic;
    private $oSetting;
    private $aSettings;
    private $oWhiteList;
    private $bDebug = 0;
    
    /**
     * REFERENCIA A ARRAY QUE ALMACENA KEYWORDS SELECCIONADOS
     */
    private $aTotalKeywords = array();

    /**
     * REFERENCIA A ARRAY QUE ALMACENA LOS HREF SELECCIONADOS
     */
    private $aTotalHref = array();

    /**
     * CONTADOR DE LOS LINKS SUBRAYADOS
     */
    private $iTotalLinks = 0;

    /**
     * MAXIMUM DE LINKS POR PAGINA VIENE POR EL SETTING, EN ESTA CLASE FORZAMOS LAS COMPROBACIONES PARA QUE SEA NUMERICO ETC..
     */
    private $iMaxLinks = 0;

    /**
     * MAXIMUM DE LINKS POR PAGINA CON EL MISMO KEYWORD VIENE POR EL SETTING, EN ESTA CLASE FORZAMOS LAS COMPROBACIONES PARA QUE SEA NUMERICO ETC..
     */
    private $iMaxSingle = 0;

    /**
     * MAXIMUM DE LINKS POR PAGINA CON EL MISMO HREF VIENE POR EL SETTING, EN ESTA CLASE FORZAMOS LAS COMPROBACIONES PARA QUE SEA NUMERICO ETC..
     */
    private $iMaxSingleUrl = 0;

    /**
     * CONSTRUCTOR
     * @param $sServerName - string: server name del cliente, es nombre de nuestro fichero csv
     */
    public function __construct() {
        try {
            $this->oSetting = Factory::create("settings");
            $this->aSettings = $this->oSetting->getSetting();
            $this->bDebug = $this->aSettings["process_page"]["debugging"];
            
            if ($this->bDebug != 0)
                echo "<!-- Init constructor replaceManagement class-->\n";
            //$this->oTopic = Factory::create("topic");
            if ($this->bDebug != 0) {
                echo "<!-- [replaceManagement] constructor: Topic object created-->\n";
            }
            $this->oWhiteList = Factory::create("whitelist");
            if ($this->bDebug != 0) {
                echo "<!-- [replaceManagement] constructor: Whitelist object created-->\n";
            }
            if ($this->bDebug != 0) {
                echo "<!-- [replaceManagement] constructor: Settings object created \n Getting settings: -->\n";
            }

            if ($this->bDebug != 0 && !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
                echo "<!-- [replaceManagement] constructor: Setting values: -->\n<!--";
                print_r($this->aSettings);
                echo "-->\n";
            }
            //$this->aTopics = $this->oTopic->getTopic();
            if ($this->bDebug != 0) {
                echo "<!-- [replaceManagement] constructor: Getting topics: \n";
                //print_r($this->aTopics);
                echo " -->\n";
            }
//OJO ACTUALIZANDO WHITELIST TAMBIEN SE ACTUALIZAN LOS TOPICS, ACTUALIZAR - WHITELIST = CLEAN ALL TABLE 
            $aAux = $this->aSettings["whitelist"];
            if ($this->bDebug != 0)
                echo "<!-- [replaceManagement] constructor: checking whitelist -->\n";
            $aAuxResultado = $this->oWhiteList->checkWhiteList($aAux);

            if ($this->bDebug != 0 && !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
                echo "<!-- [replaceManagement] constructor: Result of checking whitelist: \n";
                print_r($aAuxResultado);
                echo " -->\n";
            }
            if ($aAux["hash_update"] != $aAuxResultado["hash_update"]) {
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] constructor: old hash_update != new hash_update => update whitelist settings with new values -->\n";

                $this->aSettings["whitelist"] = $aAuxResultado;
                $this->oSetting->actualizaAllSettings($this->aSettings);
            }
//LIMITES DE LINKS TENIENDO EN CUENTA SI ES NUEMRICO Y MAYOR DE 0
            $this->iMaxLinks = 0;
            $this->iMaxSingle = 0;
            $this->iMaxSingleUrl = 0;
            if ($this->bDebug != 0)
                echo "<!-- [replaceManagement] constructor: initializating link variables: iMaxLinks, iMaxSingle, iMaxSingleUrl -->\n";

            if (is_numeric($this->aSettings["appearance_limit"]["page_max"]) && $this->aSettings["appearance_limit"]["page_max"] > 0) {
                $this->iMaxLinks = $this->aSettings["appearance_limit"]["page_max"];
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] constructor: iMaxLinks = $this->iMaxLinks -->\n";
            }
            if (is_numeric($this->aSettings["appearance_limit"]["keyword_max"]) && $this->aSettings["appearance_limit"]["keyword_max"] > 0) {
                $this->iMaxSingle = $this->aSettings["appearance_limit"]["keyword_max"];
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] constructor: iMaxSingle = $this->iMaxSingle -->\n";
            }
            if (is_numeric($this->aSettings["appearance_limit"]["url_max"]) && $this->aSettings["appearance_limit"]["url_max"] > 0) {
                $this->iMaxSingleUrl = $this->aSettings["appearance_limit"]["url_max"];
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] constructor: iMaxSingleUrl = $this->iMaxSingleUrl -->\n";
            }
            if ($this->bDebug != 0 && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')))
                echo "<!-- [replaceManagement] End constructor -->\n";
        } catch (Exception $e) {
            print_r("<!--Error creating replaceManagement object!: " . $e->getMessage() . " -->");
        }
    }
    

    /**
     * Funcion que reemplaza las palabras del texto por enlaces de la whitelist
     * @param $sContent - string: texto donde vamos a reemplazar
     */
    public function replaceText($sContent) {   
        try {
             
            $oCallback = Factory::create("callback");
            $oBurdelaco = array($oCallback, 'handler');

            if ($this->bDebug != 0)
                echo "<!-- [replaceManagement] Init replaceText -->\n";
            
            $sNewContent = $sContent;
            
            $aIgnorePage = Utils::explode_trim(";", $this->aSettings["ignore_page"]);
            if ($this->bDebug != 0) {
                echo "<!-- [replaceManagement] ReplaceText: Pages ignored: -->\n<!--";
                print_r($aIgnorePage);
                echo " -->\n";
            }
            
            //FILTRO 1: comprobar si la pagina no esta excluida
            $bIsSingle = 0;
            $bIsPage = 0;
            
            if (null != is_single($aIgnorePage)) {
                
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] ReplaceText: is_single (\$aIgnorePage) = TRUE => there is some ignored post displayed! -->\n";
                
                $bIsSingle = is_single($aIgnorePage);
                
            }
            
            if (null != is_page($aIgnorePage)) {
                
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] ReplaceText: is_page (\$aIgnorePage) = TRUE => there is some ignored page displayed! -->\n";
                
                $bIsPage = is_page($aIgnorePage);
                
            }
            
            if (!$bIsSingle && !$bIsPage) {
                
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] ReplaceText: There is NOT any ignored post and page displayed => replacing content text -->\n";
                
                //DE MOMENTO SOLO TRABAJAMOS CON WHITELIST
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] ReplaceText: getting whitelist -->\n";
                
                
                $aTopicsWhiteList = $this->oWhiteList->getWhitelist();

                if ($this->bDebug != 0) {
                    
                    echo "<!-- [replaceManagement] Whitelist content: \n";
                    print_r($aTopicsWhiteList);
                    echo ' -->';
                    
                }

                //EXCLUDE HEADING --> quitado
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] ReplaceText: for each topic in whitelist doing the replacement -->\n";
                
                
                $sCurrentUrl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                
                foreach ($aTopicsWhiteList as $element) 
                {
                    
                    if ( !empty($element->name) && 
                         !empty($element->resource) && 
                        ($element->url_origen == NULL || $element->url_origen == $sCurrentUrl || $element->url_origen."/" == $sCurrentUrl))
                    {
                        
                        if ($this->bDebug != 0) 
                        {
                        
                            echo "<!-- [replaceManagement] ReplaceText: topic = --><!--";
                            print_r($element);
                            echo " -->";
                        
                        }

                        //SI HREF ESTA VACIO NO HACEMOS NADA
                        if (null != $element->resource && "" != $element->resource && strlen($element->resource) > 0 &&
                            $element->resource != Utils::curPageURL() &&
                            ($element->type == 2 )) 
                        {
                        
                            if ($this->bDebug != 0)
                                echo "<!-- [replaceManagement] ReplaceText: $element->resource != null && $element->resource != current page && $element->type == 2 -->\n";

                            //CONTEXTO
                            $sAuxContext = '@@name@@';

                            if ($this->bDebug != 0)
                                echo "<!-- [replaceManagement] ReplaceText: looking context of topic = '$element->context'  -->\n";

                            if (null != $element->context && "" != $element->context &&
                                    strlen($element->context) > 0 && strpos($element->context, $element->name) !== false) 
                            {

                                $sAuxContext = str_replace($element->name, '@@name@@', $element->context);

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: context replaced to = '$sAuxContext' -->\n";

                            }

                            //CASE SENSITIVE
                            // $reg_post = $this->aSettings["matching"]['matching_sensitive'] == 1 ? '/(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))(' . $sAuxContext . ')/msU' : '/(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))(' . $sAuxContext . ')/imsU';

                            //if ($this->bDebug != 0)
                            //    echo "<!-- [replaceManagement] ReplaceText: Looking if (case sensitive = 1 ) => reg exp with \b/imsU or \b/msU -->\n";

                            //$reg = $this->aSettings["matching"]['matching_sensitive'] == 1 ? '/(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))\b(' . $sAuxContext . ')\b/msU' : '/(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))\b(' . $sAuxContext . ')\b/imsU';
                            //$reg="/(?!(?:[^<]+>|[^>]+<\/a>))(?<!\p{L})($sAuxContext)(?!\p{L})/imsU";                        
                            //$reg = "/<([\w]+)[^>]*>\b(' . $sAuxContext . ')\b<\/\1>/imsU";
                            //$reg = '/<+\s*\/\s\b('. $sAuxContext .')\b[^>]\/\s>+/i';
                            //$reg = '/<(\w+)[^>]*>\b('.$sAuxContext.')\b<\/\1>/imsU';
                            //$reg = '/(?:<([^>]+)>)?\b('.$sAuxContext.')\b[^[^(<\/\\1>)|<\/a>]]?/imsU';
                            //$reg = '/(?:<(\w+)[^>]*>)?\b' . preg_quote($sAuxContext, '/') . '\b(?!<\/a>)(<\/\\1>)?/msU';

                            // $strpos_fnc = $this->aSettings["matching"]['matching_sensitive'] == 1 ? 'strpos' : 'stripos';
                            //COMPROBACION DE ARRAY

                            if (!array_key_exists($element->name, $this->aTotalKeywords)) 
                            {

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: $element->name is not into keywords list \$this->aTotalKeywords then \$this->aTotalKeywords[$element->name] = 0 -->\n";

                                $this->aTotalKeywords[$element->name] = 0;
                            }

                            if (!array_key_exists($element->resource, $this->aTotalHref)) 
                            {

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: $element->resource is not into links list \$this->aTotalHref then \$this->aTotalHref[$element->resource] = 0 -->\n";

                                $this->aTotalHref[$element->resource] = 0;
                            }

                            
                            if ($this->bDebug != 0)
                                echo "<!-- [replaceManagement] ReplaceText: look how many links to replace -->\n";
                            
                            
                            if ((!$this->iMaxLinks || ($this->iTotalLinks < $this->iMaxLinks)) && //comprobación con filtro max links por pagina
                                    (!$this->iMaxSingle || ($this->aTotalKeywords[$element->name] < $this->iMaxSingle)) && //comprobacion filtro max links para un mismo keyword
                                    (!$this->iMaxSingleUrl || ($this->aTotalHref[$element->resource] < $this->iMaxSingleUrl))) //comprobacion filtro max links para un mismo link
                            {

                                if ($this->bDebug != 0) {
                                    echo "<!-- [replaceManagement] !\$this->iMaxLinks || (\$this->iTotalLinks < \$this->iMaxLinks) && -->\n";
                                    echo "<!-- [replaceManagement] (!\$this->iMaxSingle || (\$this->aTotalKeywords[\$element->name] < \$this->iMaxSingle)) && -->\n";
                                    echo "<!-- [replaceManagement] (!\$this->iMaxSingleUrl || (\$this->aTotalHref[\$element->resource] < \$this->iMaxSingleUrl)) -->\n";
                                }

                                //contador indica LINKS a buscar
                                $iSearchLinks = -1; //unlimited
                                echo "<!-- [replaceManagement]  \$iSearchLinks = $iSearchLinks -->\n";

                                //RESPECTO A LINKS TOTALES
                                if ($this->iMaxLinks > 0) //si hay limitacion en nº max de links
                                {

                                    if ($this->bDebug != 0)
                                        echo "<!-- [replaceManagement] Updating quantity of links to search: \$iSearchLinks = \$this->iMaxLinks - \$this->iTotalLinks -->\n";

                                    $iSearchLinks = $this->iMaxLinks - $this->iTotalLinks;

                                    if ($this->bDebug != 0)
                                        echo "<!-- [replaceManagement] number of links to search: \$iSearchLinks = $this->iMaxLinks - $this->iTotalLinks = $iSearchLinks -->\n";

                                }
                                    
                                //RESPECTO A LINKS CON MISMO KEYWORD;
                                if ($this->iMaxSingle > 0 && $iSearchLinks != 0) // si hay por linkar y hay limitacion por keyword
                                {

                                    if ($this->bDebug != 0)
                                        echo "<!-- [replaceManagement] links with same keyword: \$this->iMaxSingle > 0 && \$iSearchLinks != 0 -->\n";

                                    if ($iSearchLinks < 0) 
                                    {

                                        if ($this->bDebug != 0)
                                            echo "<!-- [replaceManagement] And \$iSearchLinks < 0 (unlimited) then -->\n";

                                        $iSearchLinks = $this->iMaxSingle;

                                        if ($this->bDebug != 0)
                                            echo "<!-- [replaceManagement] \$iSearchLinks = \$this->iMaxSingle = $iSearchLinks -->\n";

                                    } 
                                    else 
                                    {

                                        if ($this->bDebug != 0)
                                            echo "<!-- [replaceManagement] And \$iSearchLinks > 0 (limited) then -->\n";

                                        $iAuxDiferencia = $this->iTotalLinks + $this->iMaxSingle;


                                        if ($this->bDebug != 0)
                                            echo "<!-- [replaceManagement] \$iAuxDiferencia = $this->iTotalLinks + $this->iMaxSingle = $iAuxDiferencia -->\n";

                                        if ($iAuxDiferencia <= $this->iMaxLinks) 
                                        {

                                            if ($this->bDebug != 0)
                                                echo "<!-- [replaceManagement] (\$iAuxDiferencia <= \$this->iMaxLinks) then -->\n";

                                            $iSearchLinks = $this->iMaxSingle;

                                            if ($this->bDebug != 0)
                                                echo "<!-- [replaceManagement] \$iSearchLinks = \$this->iMaxSingle = $this->iMaxSingle -->\n";

                                        }
                                        else
                                        {

                                            if ($this->bDebug != 0)
                                                echo "<!-- [replaceManagement] (\$iAuxDiferencia > \$this->iMaxLinks) then -->\n";

                                            $iSearchLinks = $this->iMaxLinks - $this->iTotalLinks;

                                            if ($this->bDebug != 0)
                                                echo "<!-- [replaceManagement] \$iSearchLinks = \$this->iTotalLinks = $iSearchLinks -->\n";

                                        }
                                    }
                                }

                                //cantidad de topics que aparecen
                                $iQuantityTopics = 0;

                                //propiedades de links externos
                                $sExternal = "";

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] External link properties: -->\n";

                                if ($this->aSettings["external"]["external_nofollow"] == 1) 
                                {

                                    if ($this->bDebug != 0)
                                        echo "<!-- [replaceManagement] \$this->aSettings[\"external\"][\"external_nofollow\"] == 1 -->\n";

                                    $sExternal = "rel='nofollow'";

                                }

                                if ($this->aSettings["external"]["external_target"] == 1) 
                                {
                                    
                                    if ($this->bDebug != 0)
                                        echo "<!-- [replaceManagement] \$this->aSettings[\"external\"][\"external_target\"] == 1 -->\n";

                                    $sExternal .=" target='_blank'";

                                }

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] FINAL external link properties = $sExternal -->\n"; $replace = str_replace('@@name@@', "<a class='nl_tag' $sExternal id='@@ID@@' hreflang='$element->type' title='$element->altname' href='$element->resource'>$element->name</a>", $sAuxContext); if ($this->bDebug != 0) echo "<!-- [replaceManagement] Replacement: $replace -->\n"; // $regexp = str_replace('@@name@@', $element->name, $reg); if ($this->bDebug != 0) echo "<!-- [replaceManagement] RegExp: $regexp -->\n"; // echo $regexp."|".$replace."|"."<br>"; //$sNewContent = preg_replace($regexp, $replace, $sNewContent, $iSearchLinks, $iQuantityTopics);

                                global $word;

                                $word = preg_quote($element->name,'/'); // to avoid PCRE injection

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: word to replace = $word -->\n";

                                $reg = '/(?:<(\w+)[^>]*>)?\b'.$word.'\b(?!<\/a>)(?!<\/h\d>)(<\/\\1>)?/';

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: reg1 = $reg -->\n";

                                $reg0 = '/<\w[^>]*\b'.$word.'\b[^>]*>/Ui';

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] ReplaceText: reg2 = $reg0 -->\n";

                                $replace  = str_replace($word,'!X!',$replace);

                                $sNewContent = preg_replace_callback($reg0,$oBurdelaco,$sNewContent); // replace "word" for say !X! inside tags   

                                $sContent = preg_replace($reg, $replace, $sNewContent, $iSearchLinks, $iQuantityTopics); // delete "word" elsewhere

                                $sNewContent = str_replace('!X!', $word, $sContent); // put "word" inside tag back

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] Updating \$this->iTotalLinks -->\n";

                                $this->iTotalLinks += $iQuantityTopics;
                                

                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] \$this->iTotalLinks += $iQuantityTopics = $this->iTotalLinks -->\n";

                                
                                //actualizamos array con links reemplazados    
                                if(array_key_exists($element->name, $this->aTotalKeywords))
                                    $this->aTotalKeywords[$element->name] = $this->aTotalKeywords[$element->name] + $iQuantityTopics;
                        
                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] Updating array Total links by Keyword \$this->aTotalKeywords[$element->name] = ".$this->aTotalKeywords[$element->name]." + ".$iQuantityTopics." -->\n";
                        
                                if(array_key_exists($element->resource, $this->aTotalHref))
                                    $this->aTotalHref[$element->resource] = $this->aTotalHref[$element->resource] + $iQuantityTopics;
                        
                                if ($this->bDebug != 0)
                                    echo "<!-- [replaceManagement] Updating array Total links by HREF \$this->aTotalHref[$element->resource] = ".$this->aTotalHref[$element->resource]." + ".$iQuantityTopics." -->\n";
                            }
                            
                        }
   
                    }
                    //si links reemplazados == nº maximo links && nº maximo links limitado
                    if ($this->iTotalLinks == $this->iMaxLinks && $this->iMaxLinks > 0) break;
                }
            }
            
            if ($this->bDebug != 0)
                echo "<!-- [replaceManagement] Replacement of all IDs in text content, calling \this->setLinkId(\$sNewContent) -->\n";
            
            $sNewContent = $this->setLinkId($sNewContent);
            
            if ($this->bDebug != 0)
                echo "<!-- [replaceManagement] FINISH REPLACEMENT -->\n";
            
            return $sNewContent;
            
        } catch (Exception $e) {
            print_r("<!--Error replacing text!: " . $e->getMessage() . " -->");
        }
    }

    
    /**
     * Function para crear los id's de los links unicos
     * @param string - $sContent: el texto
     * @return string - $newContent: devuelve el contenido reemplazado
     */
    private function setLinkId($sContent) {
        
        $newContent = $sContent;
        
        if ($this->bDebug != 0)
            echo "<!-- [replaceManagement] setLinkId(\$sContent) -->\n";
        
        $iMatches = preg_match_all('/@@ID@@/', $newContent, $matches);
        
        for ($i = 0; $i < $iMatches; $i++) {
            
            if (strpos($sContent, "@@ID@@") !== false) {
                
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] setLinkId: \"@@ID@@\" in \$sContent, then replacing it -->\n";
                
                //CREAMOS UN ID UNICO STAMP + CONTADOR
                $iId = uniqid() . $i;
                
                $newContent = preg_replace('/@@ID@@/', $iId, $newContent, 1);
                
                if ($this->bDebug != 0)
                    echo "<!-- [replaceManagement] setLinkId: \"@@ID@@\" replaced by $iId -->\n";
                
            }
            
        }
        
        return $newContent;
    }

   /** private function insertspecialchars($str, $si1 = null, $si2 = null) {
        try {
            $strarr = str_split($str);
            $str = implode("<!---->", $strarr);
            return $str;
        } catch (Exception $e) {
            print_r("<!--Error inserting specialchars!: " . $e->getMessage() . " -->");
        }
    }**/

}

class callback {
    
    public function __construct() {  }
    
    public function handler($m) { return str_replace($GLOBALS["word"],'!X!',$m[0]); }
}