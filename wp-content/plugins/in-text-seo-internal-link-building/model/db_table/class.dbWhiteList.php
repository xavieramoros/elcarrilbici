<?php

class dbWhiteList {

    private $aTabla;
    private $sTableName = 'notedlinks_whiteList';
    private $bDebug = 0;
    private $oSettings;
    
    /**
     * constructor
     */
    public function __construct() {
        $this->oSettings = Factory::create("settings");
        $aSettings = $this->oSettings->getSetting();
        $this->bDebug = $aSettings["process_page"]["debugging"];
        if ($this->bDebug != 0)
            echo "<!-- [dbWhiteList] Class contructor: -->\n";
    }

    public function getTableName() {
        if ($this->bDebug != 0) echo "<!-- [dbWhiteList] getTableName function: getting table name $this->sTableName -->\n";
        return $this->sTableName;
    }

    /**
     * Funcion que devuelve la query para crear la tabla
     */
    public function getCreateTable() 
    {
        try {
            if ($this->bDebug != 0) echo "<!-- [dbWhiteList] getCreateTable function: return create table query -->\n";
            $sQuery = "CREATE TABLE IF NOT EXISTS `notedlinks_whiteList` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `type` int(11) NOT NULL DEFAULT '0',
                      `lang` varchar(5) NOT NULL DEFAULT 'en',
                      `resource` varchar(150) NOT NULL,
                      `url_origen` varchar(150),
                      `name` varchar(150) NOT NULL,
                      `altname` varchar(150) NOT NULL,
                      `context` varchar(150) NOT NULL,
                      `domain` varchar(150),
                      PRIMARY KEY (`id`),
                      FULLTEXT KEY `domain_idx` (`domain`)
                    ) ENGINE=MyISAM AUTO_INCREMENT=848 DEFAULT CHARSET=utf8";
            
            if ( $this->bDebug != 0 ) {
              echo "<!-- [dbWhiteList] Query: \n $sQuery -->\n";
            }

            return $sQuery;

        } catch (Exception $e) {
            print_r("<!--Error creating table!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * Funcion que devuelve la query para borrar los topics con dominio = HTTP_HOST
     */
    public function getClearTable() {
        if ($this->bDebug != 0){ 
            echo "<!-- [dbWhiteList] getClearTable function: -->\n";
            echo "<!-- [dbWhiteList] delete query: DELETE FROM " . $this->sTableName. " WHERE domain = '$_SERVER[HTTP_HOST]'; -->\n";
        }
        return "DELETE FROM " . $this->sTableName. " WHERE domain = '$_SERVER[HTTP_HOST]'";
    }

}