<?php

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once( DB_TABLE_PATH . 'class.dbWhiteList.php' );

//referencia a conexion DB con WP

class modelWhiteList {

    //referencia a tabla de topics
    private $oDbWhiteList;
    private $bDebug = 0;
    private $oSettings;
    private $oKeywords = NULL;
    private $oWhitelist = NULL;
    /**
     * Constructor de whitelist model
     */
    public function __construct() {
        $this->oSettings = Factory::create("settings");
        $aSettings = $this->oSettings->getSetting();
        $this->bDebug = $aSettings["process_page"]["debugging"];
        if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Class contructor: creating DB Whitelist obj \$this->oDbWhiteList -->\n";
        $this->oDbWhiteList = Factory::create("dbWhiteList");
        $this->aKeywords = array();
    }

    /**
     * Funcion que actualiza / crea la tabla con whitelist
     * @param array - $aTopics array con topics. 
     * $aTopics = array(  
     *  [name] => blogging,
     *  [type] => 1,
     *  [lang] => es,
     *  [resource] => Jordi_Roura,
     *  [altname] => alternative txt,
     *  [context] => context
     * );
     */
    public function updateWhiteList($aWhiteList) {
        try {
            if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Init updateWhiteList function -->\n";
            $sQuery = "INSERT INTO " . $this->oDbWhiteList->getTableName();
            if ($this->bDebug != 0) echo "<!-- [modelWhiteList] insert query: $sQuery -->\n";
            $sValues = "";
            $sColumns = "";
            global $wpdb;
            
            //si tabla no existe se crea
            //if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Executing query to create table: dbDelta(\$this->oDbWhiteList->getCreateTable()); -->\n";
            //dbDelta($this->oDbWhiteList->getCreateTable());
            
            /**$aStatus = $wpdb->get_results("SHOW TABLE STATUS WHERE Name = 'notedlinks_whiteList'");
            if(isset($aStatus["Engine"]) && $aStatus["Engine"] != "MyISAM") {
                $wpdb->query("ALTER TABLE notedlinks_whiteList ENGINE = MyISAM");
                $wpdb->query("ALTER TABLE notedlinks_whiteList ADD FULLTEXT INDEX `idx_domain` (`domain` ASC)");
            }**/
            
            if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Executing query to delete whitelist table: \$wpdb->query(\$this->oDbWhiteList->getClearTable()); -->\n";
            //truncate whitelist table
            $wpdb->query($this->oDbWhiteList->getClearTable());
            
            //inserting values
            if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Creating query to insert whitelist content in BD table --> \n";
            foreach ($aWhiteList as $aValue) 
            {
                if($aValue->name == "") continue;
                
                if ($this->bDebug != 0) echo "<!-- [modelWhiteList] For each \$aValue of \$aWhiteList insert: -->\n";
                foreach ($aValue as $key => $value) {
                    //comprobar si value es integer
                    if (is_numeric($value))
                        $sAux = $value;
                    else
                        $sAux = "'" . addslashes($value) . "'";
                    if (strlen($sValues) > 0)
                        $sValues .= "," . $sAux;
                    else
                        $sValues = $sAux;
                    if (strlen($sColumns) > 0)
                        $sColumns .= "," . $key;
                    else
                        $sColumns = $key;
                }
                $sColumns.=", domain";
                $sValues.=",'$_SERVER[HTTP_HOST]'";
                if ($this->bDebug != 0) echo "<!-- [modelWhiteList] Insert query: ".$sQuery . "(" . $sColumns . ") VALUES (" . $sValues . ") -->\n";
                $wpdb->query($sQuery . "(" . $sColumns . ") VALUES (" . $sValues . ")");
                $sValues = "";
                $sColumns = "";
            }
            if ($this->bDebug != 0) echo "<!-- [modelWhiteList] End updateWhiteList function -->\n";
        } catch (Exception $e) {
            print_r("<!--Error update Whitelist!: " . $e->getMessage()." -->");
        }
    }

    /**
     * Funcion que devuelve un array con los datos de la whitelist
     * @return array - $aWhiteList: array
     */
    public function getWhiteList() {
        global $wpdb;
        if ($this->bDebug != 0){ 
            echo "<!-- [modelWhiteList] Init getWhiteList function -->\n";
            echo "<!-- [modelWhiteList] Query: SELECT * FROM " . $this->oDbWhiteList->getTableName() . " WHERE domain = '$_SERVER[HTTP_HOST]' -->\n";
        }
        
        $this->oWhitelist = $wpdb->get_results("SELECT * FROM " . $this->oDbWhiteList->getTableName() . " WHERE domain = '$_SERVER[HTTP_HOST]' ORDER BY url_origen DESC");

        if ($this->bDebug != 0){ 
            echo "<!-- [modelWhiteList] Query response: \n";
            var_dump($aAux);
            echo " -->\n";
        }
        return $this->oWhitelist;
    }

    
    /**
     * getKeywords: gets and returns all keywords and theirs urls from DB Whitelist table
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @return array array of keywords and urls of the whitelist
     */
    public function getKeywords ()
    {
        if ( $this->oWhitelist === NULL ) {
            $this->getWhiteList();
        }

        if (!empty($this->oWhitelist)) {
            foreach ($this->oWhitelist as $key => $oRule) {
                $keywordMd5 = md5($oRule->name);
                if ( !array_key_exists ( $keywordMd5, $this->aKeywords ) ) {
                    $this->aKeywords[$keywordMd5] = array("keyword" => $oRule->name);
                }

                $this->aKeywords[$keywordMd5][] = array("url_wp" => $oRule->{"url_origen"}, "url_to" => $oRule->resource);
            }
        }

        return $this->aKeywords;
    }
}