<?php
/**
 * Class to manage the admin menu of plugin
 */

require_once ( CLASSES_PATH . "replace/class.links.php" );
require_once ( DB_TABLE_PATH . "class.dbWhiteList.php" );
require_once ( MODEL_PATH . "model.links.php" );

class notedLinksAdmin
{

    private $oSettings;
    private $bDebug = 0;

    public function __construct() {

        $this->oSettings = Factory::create("settings");
        $aSettings = $this->oSettings->getSetting();
        //debug mode?
        $this->bDebug = $aSettings["process_page"]["debugging"];

        if ($this->bDebug != 0) {
            echo "<!-- Init constructor notedLinksAdmin class-->\n";
            echo "<!-- [notedLinksAdmin] Initial Settings: -->\n<!--";
            print_r($this->oSettings);
            echo "-->\n";
        }

        //activating admin menu of plugin
        add_action('admin_menu', array($this, 'menu'));

        if ($this->bDebug != 0) {
            echo "<!-- [notedLinksAdmin] Added action admin_menu with notedLinksAdmin object -->\n";
        }
    }

    /**
     * install: function to install the plugin options in DB. This function is executed when plugin is activated
     * @return [type] [description]
     */
    public function install ()
    {
        if ($this->bDebug != 0) {
            echo "<!-- [notedLinksAdmin] Init install: plugin activation -->\n";
        }

        $this->oSettings->initSettings();
        $aSettings = $this->oSettings->getSetting();

        if ( $aSettings['process_page']['api_key'] != false ) {
            //if it is not first activation then calls to update status of Plugin
            $this->updateStatusPlugin(true, $aSettings['process_page']['api_key']);
        }

        if ($this->bDebug != 0) {
            echo "<!-- [notedLinksAdmin] install: settings created -->\n";
        }
    }

    /**
     * nl_deactivation: set options of plugin when this one is disabled
     * @return [type] [description]
     */
    public function nl_deactivation()
    {
        global $current_user;

        $user_id = $current_user->ID;

        if ( get_user_meta($user_id, 'admin_notice_nl') ) {
            delete_user_meta( $user_id, 'admin_notice_nl');
        }

        $aSettings = $this->oSettings->getSetting();

        if( $aSettings['process_page']['api_key'] != false )
        {
            $this->updateStatusPlugin(false, $aSettings['process_page']['api_key']);
        }
    }

    /**
     * menu: function to create option menu of plugin in WP admin space
     * @return [type] [description]
     */
    public function menu ()
    {
        if ($this->bDebug != 0) {
            echo "<!-- [notedLinksAdmin] Init menu: creation of Notedlinks option in Admin space -->\n";
        }

        $aSettings = $this->oSettings->getSetting();
        //adding main menu of NL Plugin to admin space
        add_menu_page("NotedLinks", "NotedLinks", 'manage_options', 'menu-notedlinks.php', array($this, 'handle_validation'));
        // if user's email exists then adding settings and dashboard menus
        if ( !empty($aSettings['process_page']['nle']) ) {
            //add submenu of settings and associates this submenu wit handle_options function
            add_submenu_page('menu-notedlinks.php', 'Settings', 'Settings', 'manage_options', 'notedlinks.php', array($this, 'handle_options'));
            //add submenu of dashboard and associates this submenu wit handle_dashboard function
            add_submenu_page('menu-notedlinks.php', 'Dashboard', 'Dashboard', 'manage_options', 'dashboard.php', array($this, 'handle_dashboard'));
            add_action('admin_notices', array($this,'admin_notice_nl'),3);

            //if user's account is not activated then adding Active account menu
            if( !$aSettings['process_page']['active'] ) {
                //add submenu of Active account and associates this submenu wit handle_validation function
                add_submenu_page('menu-notedlinks.php', 'Activate account', 'Activate account', 'manage_options', 'activate.php', array($this, 'handle_validation'));
            }


            /** creating tables of the plugin **/
            //table for whitelist
            $oDbWhiteList = Factory::create("dbWhiteList");
            dbDelta($oDbWhiteList->getCreateTable());
            //table for stats of the links
            $oModelLinks = Factory::create("modelLinks");
            dbDelta($oModelLinks->createLinksTable());


            // CONNECT SLT ACCOUNT WITH PLUGIN ACCOUNT @version: 0.8  - 18/12/2013
            // if no active or not api_key. curl to get from server if client connected the plugin with SLT account
            if(!$aSettings['process_page']['active'] || !$aSettings['process_page']['api_key']) {

                $aParameters = array(
                    "nle" => $aSettings['process_page']['nle'],
                    "nld" => $_SERVER['HTTP_HOST'],
                    "wp_version" => get_bloginfo("version")
                );

                $ch = curl_init(URL_CHECK_ACTIVACION);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $jsonResponse = curl_exec($ch);
                $result = json_decode($jsonResponse, true);

                //closing curl request
                curl_close($ch);

                if ($this->bDebug != 0) {
                    echo "<!-- [notedLinksAdmin] Connect account SLT to Plugin: ".$result['msg']." -->\n";
                }

                if (isset($result['res']) && $result['res'] !== FALSE)
                {
                    $aSettings['process_page']['api_key'] = (isset($result["key"]))     ? $result["key"]    : false;
                    $aSettings['process_page']['active']  = (isset($result["active"]))  ? $result["active"] : false;
                    $aSettings['process_page']['id_widget']  = (isset($result["widgetId"]))  ? $result["widgetId"] : false;
                    $aSettings['process_page']['id_site']  = (isset($result["siteId"]))  ? $result["siteId"] : false;
                    //first activation = 1 (confirmed in SERVER)
                    $aSettings['process_page']['first_activation'] = (isset($result["activation_date"]))  ? $result["activation_date"] : 0;
                    //updating wordpress version in settings of plugin
                    $aSettings["wp_version"] = $aParameters["wp_version"];
                    $this->oSettings->actualizaAllSettings($aSettings);
                }
            }

        } else { // if user's email doesn't exist then adding only Activate account menu
            //add submenu of Active account and associates this submenu wit handle_validation function
            add_submenu_page('menu-notedlinks.php', 'Activate account', 'Activate account', 'manage_options', 'activate.php', array($this, 'handle_validation'));
        }
        //removing main menu of NL Plugin from admin space
        remove_submenu_page('menu-notedlinks.php', 'menu-notedlinks.php');

        if ($this->bDebug != 0) {
            echo "<!-- [notedLinksAdmin] menu: added in submenu page (admin.php) notedlinks.php plugin file and handle options -->\n";
        }
    }

    /**
     * This function returns an array with all checkboxes' values of form of the Settings' page
     * @param array  $aConfig array of settings
     * @return array  $aCheckBox array of checkboxes' values
     */
    private function getCheckBox ( $aConfig )
    {
        if ($this->bDebug != 0){
            echo "<!-- [notedLinksAdmin] Init getCheckBox -->\n";
        }

        $aCheckBox = array(
            'content_scraping' => $aConfig['process_page']['the_content']['content_scraping'],
            'comment_scraping' => $aConfig['process_page']['comment_text']['comment_scraping'],
            'external_nofollow' => $aConfig['external']['external_nofollow'],
            'external_target' => $aConfig['external']['external_target'],
            'debugging' => $aConfig['process_page']['debugging']
        );

        if ($this->bDebug != 0){
            echo "<!-- [notedLinksAdmin] Returning Check Boxes of admin form: -->\n<!--";
            print_r($aCheckBox);
            echo "-->\n";
        }

        return $aCheckBox;
    }

    /**
     * Recursive function adds the POST values sent in form POS request to the array of settings
     *
     * @param array $aConfig array with the current config
     * @param array $aPostElements element's values sent in form request
     * @param array $aCheckBox: array with checkboxes available in settings
     * @return array $aConfig array of updated settings
     */
    private function setSettings ( $aConfig, $aPostElements, $aCheckBox )
    {
        try {
            if ( $this->bDebug != 0 ) {
                echo "<!-- [notedLinksAdmin] Init setSettings: -->\n";
                echo "<!-- Config param: -->\n<!--";
                print_r($aConfig);
                echo "-->\n";
                echo "<!-- Post elements param: -->\n<!--";
                print_r($aPostElements);
                echo "-->\n";
                echo "<!-- Check boxes param: -->\n<!--";
                print_r($aCheckBox);
                echo "-->\n";
            }

            //if there are POST values sent then doing the required action
            if ( NULL !== $aPostElements && sizeof($aPostElements) > 0 ) {

                if ($this->bDebug != 0){
                    echo "<!-- [notedLinksAdmin] setSettings: into 'if' with PostElements != null and sizeof(PostElements) >0 -->\n";
                    echo "<!-- [notedLinksAdmin] setSettings: foreach aConfig value ... -->\n";
                }
                //foreach $v value of config
                foreach ($aConfig as $k => $v) {

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] setSettings: into foreach -->\n";
                        echo "<!-- [notedLinksAdmin] setSettings: key = $k -->\n";
                    }

                    if (is_array($v)) {
                        if ($this->bDebug != 0) {
                            echo "<!-- [notedLinksAdmin] setSettings: value is array: -->\n<!--";
                            print_r($v);
                            echo "-->\n";
                        }
                        //adding new values of setting $k calling recursively to setSetings for array Setting value $v
                        $aConfig[$k] = $this->setSettings($v, $aPostElements, $aCheckBox);

                        if ( $this->bDebug != 0 ) {
                            echo "<!-- [notedLinksAdmin] setSettings: result of recursive setSettings call for \$k $k and his value \$v: -->\n";
                            echo "<!-- \$aConfig[$k] = -->\n<!--";
                            print_r($aConfig[$k]);
                            echo "-->\n";
                        }

                    } else { //if setting $k with value $v is not an array

                        if ( $this->bDebug != 0 ) {
                            echo "<!-- [notedLinksAdmin] setSettings: value is NOT array: -->\n<!--";
                            print_r($v);
                            echo "-->\n";
                        }

                        //initializing value of setting to 0 (zero) if checkbox of this setting exists
                        if ( isset($aCheckBox[$k]) ) {
                            $aConfig[$k] = 0;

                            if ( $this->bDebug != 0 ) {
                                echo "<!-- [notedLinksAdmin] setSettings: \$aCheckBox[$k] != null then \$aCheckBox[$k] = 0 -->\n";
                            }
                        }

                        //if a value is sent in POST request for this setting then updating value of setting $k with the POST value sent
                        if ( isset($aPostElements[$k]) ) {

                            $aConfig[$k] = $aPostElements[$k];

                            if ($this->bDebug != 0) {
                                echo "<!-- [notedLinksAdmin] setSettings: \$aPostElements[$k] != null then \$aCheckBox[$k] = \$aPostElements[$k] -->\n";
                            }
                        }
                    }
                }
            }

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] end setSettings: returning new settings \$aConfig -->\n";
            }

            return $aConfig;

        } catch (Exception $e) {
            print_r("<!--Error set settings!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * handle_options: main function to handle/manage the options of plugin
     * @return [type] [description]
     */
    public function handle_options ()
    {
        try {

            
            //calling to function menu (create menu page)
            $this->menu();
            //getting current settings of plugin
            $aConfig = $this->oSettings->getSetting();
            //if user is activated, then removing validation address message
            if( !$aConfig['process_page']['active'] && !empty($aConfig['process_page']['nle']) && $aConfig['process_page']['api_key'] )
            {
                /**
                 * CHECKING IF USER IS ACTIVE
                 */
                //parameters to send
                $aParameters = array(
                    "nle" => $aConfig['process_page']['nle'],
                    "dom" => $_SERVER['HTTP_HOST'],
                    "apik" => $aConfig['process_page']['api_key']
                );

                //curl calling to check if user is active
                $ch = curl_init(URL_ACCOUNT_VAL);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $jsonResponse = curl_exec($ch);
                $aRes = json_decode($jsonResponse, true);

                //closing curl request
                curl_close($ch);

                if (array_key_exists('res', $aRes)) {
                    //if res is true then sets setting active to true
                    if( $aRes['res'] === true ) {
                        $aConfig['process_page']['active'] = true;
                        //updating settings of plugin
                        $this->oSettings->actualizaAllSettings($aConfig);
                        $this->menu();
                    }
                }
            }

            //if GET request contains d param then reset user's email and call to validate function of user's account
            if ( $_GET != null && key_exists('d', $_GET) ) {
                $aConfig = $this->oSettings->getSetting();
                $aConfig['nle'] = '';
                $this->oSettings->actualizaAllSettings($aConfig);
                $this->menu();
                $this->handle_validation();
                exit();
            }

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] init handle_options: -->\n";
                echo "<!-- [notedLinksAdmin] handle_options: getting current settings -->\n<!--";
                print_r($aConfig);
                echo "-->\n";
            }

            //if there are new data then updating settings
            if ( NULL !== $_POST && sizeof($_POST) > 0 ) {

                if ($this->bDebug != 0) {
                    echo "<!-- [notedLinksAdmin] handle_options: \$_POST != null then there is new data and update settings -->\n";
                }

                /***
                 * Checking Form of settings
                 ***/

                $bUpApiKey = false;

                if ($this->bDebug != 0) {
                    echo "<!-- [notedLinksAdmin] handle_options: calling 'check_admin_referer' to avoid security exploits -->\n";
                }

                //Tests either if the current request carries a valid nonce, or if the current request was referred from an administration screen
                //If true, the current request must be referred from an administration screen
                check_admin_referer('notedlinks_update_settings');
                
                //clicked "reset default settings" button
                if ( isset($_POST['default-settings']) && $_POST['default-settings'] === "Reset Default Settings" ) {
                    //reseting the settings of plugin to default values
                    $aConfig = $this->oSettings->resetSettings();

                //if "save changes" button is clicked then update new values of settings
                } else {

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: \$_POST['actualize'] = false =>setSettings(\$aConfig, \$_POST, \$this->getCheckBox(\$aConfig))-->\n";
                    }

                    $aConfig = $this->setSettings($aConfig, $_POST, $this->getCheckBox($aConfig));

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: settings updated, new \$aConfig = \n";
                        print_r($aConfig);
                        echo " -->\n";
                    }

                }

                /**
                 * After installation, whether we don't have the id_widget or id_site needed to tracking STATISTICS,
                 * so we make first request to server to get it
                 */
                if ( ($aConfig['process_page']['id_widget'] === 0 && $aConfig['process_page']['api_key'] !== NULL )
                    || $aConfig['process_page']['id_site'] === 0 ) {

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: checking ApiKey - curl request to server - to getting idWidget and idSite -->\n";
                    }
                    // parameters to send
                    $aParameters = array(
                        "apiKey" => $aConfig['process_page']['api_key'],
                        "host" => $_SERVER['HTTP_HOST']
                    );

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: params to send in curl request -->\n<!--";
                        print_r($aParameters);
                        echo "-->\n";
                    }

                    // curl request to get id_site and id_widget
                    $ch = curl_init(URL_WIDGET);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $jsonResponse = curl_exec($ch);

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: curl request response json -->\n<!--";
                        print_r($jsonResponse);
                        echo "-->\n";
                    }
                    //decode response of curl
                    $aResponse = json_decode($jsonResponse, true);

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: json response to array -->\n<!--";
                        print_r($aResponse);
                        echo "\n [notedLinksAdmin] handle_options: Closing curl connection -->\n";
                    }

                    //closing curl request
                    curl_close($ch);

                    // if id_widget exists then save it in config/settings of plugin
                    if ($aResponse['id_widget']) {

                        if ($this->bDebug != 0) {
                            echo "<!-- [notedLinksAdmin] handle_options: \$aResponse['id_widget'] =>update settings idwidget value and valid_key = 1 -->\n";
                        }

                        $aConfig['process_page']['id_widget'] = $aResponse['id_widget'];
                        $aConfig["valid_key"] = 1;
                    }

                    // if id_site exists then save it in config/settings of plugin
                    if ($aResponse['id_site']) {

                        if ($this->bDebug != 0) {
                            echo "<!-- [notedLinksAdmin] handle_options: \$aResponse['id_site'] =>update settings idsite value and valid_key = 1 -->\n";
                        }

                        $aConfig['process_page']['id_site'] = $aResponse['id_site'];
                    }

                    // if id_widget doesn't exist then update the setting "valid_key" to 0 (false)
                    if ($aResponse['id_widget'] === NULL) {

                        if ($this->bDebug != 0) {
                            echo "<!-- [notedLinksAdmin] handle_options: \$aResponse['id_widget'] = NULL =>NO update settings values and valid_key = 0 -->\n";
                        }
                        $aConfig["valid_key"] = 0;
                    }
                }

                if ($this->bDebug != 0) {
                    echo "<!-- [notedLinksAdmin] handle_options: updating All settings with new values: -->\n<!-- ";
                    print_r($aConfig);
                    echo "-->\n";
                }
                //FINALLY update all settings of plugin
                $this->oSettings->actualizaAllSettings($aConfig);
            }

            /**
             * Sets all values of Settings Form with current values of plugin's settings
             */
            //Array of checkbox's values
            $aCheckBox = $this->getCheckBox($aConfig);

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: updating admin settings FORM with new values -->\n";
            }

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: First check boxes checked -->\n<!-- ";
                print_r($aCheckBox);
                echo "-->\n";
            }

            //changing values TRUE or FALSE to CHECKED or "" (No checked)
            foreach ($aCheckBox as $k => $v) {

                if ($v == 0) {

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: \$v == 0 then \$aCheckBox[$k] = '' -->\n";
                    }

                    $aCheckBox[$k] = "";
                } else {

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: \$v != 0 then \$aCheckBox[$k] = 'checked' -->\n";
                    }

                    $aCheckBox[$k] = "checked";
                }
            }

            /** ACTION for the POST form of settings **/
            $sAction = Utils::curPageURL();

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: ACTION FOR POST FORM: -->\n";
                echo "<!-- $sAction -->\n";
            }

            //the last update date is updated in setting's template
            $sLastActualization = date('G:i d.m.Y', $aConfig["whitelist"]["last_update"]);

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: Last update time: $sLastActualization -->\n";
            }

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: wp_create_nonce('notedlinks_update_settings') for best security -->\n";
            }

            //"nonce" identification for the security of the WP plugin actions
            $nonce = wp_create_nonce('notedlinks_update_settings');

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] handle_options: nonce = $nonce -->\n";
            }

            //loading (index) template of settings
            require ( TEMPLATES_PATH . "admin/index.html" );

            if ($this->bDebug != 0) {
                echo "<!-- [notedLinksAdmin] End handle_options: loading admin index template -->\n";
            }

        } catch (Exception $e) {
            print_r("<!--Error handling options!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * Description: handle register email page
     */
    public function handle_validation()
    {
        try {

            $aConfig = $this->oSettings->getSetting();

            $sDomain = $_SERVER['HTTP_HOST'];

            $sMsg = false;

            if (null != $_POST && isset($_POST["nle"]))
            {
                $aParameters = array(
                    "nle" => $_POST['nle'],
                    "domain" => $sDomain,
                    "activation_date" => $aConfig["process_page"]["activation_date"],
                    "wp_version" => get_bloginfo("version")
                );

                $ch = curl_init(URL_REGISTER);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $jsonResponse = curl_exec($ch);
                $aRes = json_decode($jsonResponse, true);

                //closing curl request
                curl_close($ch);

                $sAction = Utils::curPageURL();
                if ($aRes['res'] !== FALSE)
                {
                    $aConfig['process_page']['nle'] = ($_POST['nle']) ? $_POST['nle'] : '';
                    $aConfig['process_page']['api_key'] = (isset($aRes["key"])) ? $aRes["key"] : false;
                    $aConfig['process_page']['active'] = (isset($aRes["active"])) ? $aRes["active"] : false;
                    //first activation = 1 (confirmed in SERVER)
                    if ( array_key_exists("activation_date", $aRes) ) {
                        $aConfig['process_page']['first_activation'] = ($aRes["activation_date"] == true) ? 1 : 0;
                    }
                    //updating wordpress version in settings of plugin
                    $aConfig["wp_version"] = $aParameters["wp_version"];

                    $aCheckBox = $this->getCheckBox($aConfig);
                    $sLastActualization = date('G:i d.m.Y', $aConfig["whitelist"]["last_update"]);
                    $this->oSettings->actualizaAllSettings($aConfig);

                    $this->menu();

                    $sMsg = $aRes["msg"];

                    print "<link rel=\"stylesheet\" type=\"text/css\" href=\"../wp-content/plugins/in-text-seo-internal-link-building/css/notedlinks.css\" media=\"screen\" />";
                    print "<div class=\"wrap\"><div class=\"logo\"></div><h2 class=\"main-title\">In-text SEO internal link building</h2>";
                    print "<p><div class=\"msg-notification\">$sMsg</div></p>";
                    print "<p>Allows you to save time by automating link building based on rules. ". 
                    "We also provide analytics data to monitor user clicks and suggest links rules for optimal results.</p>";
                    print "<div class=\"setting-redirect\"><p>If you are a new user, an email have been sent to your inbox to activate your account.</p>";
                    print "<a href='admin.php?page=notedlinks.php'><b>Please click here to define your settings</b></a></div>";
                    exit();
                }

                $sMsg = $aRes["msg"];
                require ( TEMPLATES_PATH . "admin/activate.html" );

            } else if ( null == $_POST ) {
                //if user activated, remove validation adress message
                if(!$aConfig['process_page']['active'] && !empty($aConfig['process_page']['nle']) && $aConfig['process_page']['api_key'])
                {
                    $aParameters = array(
                        "nle" => $aConfig['process_page']['nle'],
                        "dom" => $_SERVER['HTTP_HOST'],
                        "apik" => $aConfig['process_page']['api_key']
                    );

                    $ch = curl_init(URL_ACCOUNT_VAL);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $jsonResponse = curl_exec($ch);
                    $aRes = json_decode($jsonResponse, true);

                    //closing curl request
                    curl_close($ch);

                    if (array_key_exists('res', $aRes))
                    {
                        if($aRes['res'] === true)
                        {
                            $aConfig['process_page']['active'] = true;
                            $this->oSettings->actualizaAllSettings($aConfig);

                            //$this->menu();

                            print "<link rel=\"stylesheet\" type=\"text/css\" href=\"../wp-content/plugins/in-text-seo-internal-link-building/css/notedlinks.css\" media=\"screen\" />";
                            print "<div class=\"wrap\"><h2 class=\"main-title\">In-text SEO internal link building</h2>";
                            print "<p>Allows you to save time by automating link building based on rules. " . 
                            "We also provide analytics data to monitor user clicks and suggest links rules for optimal results.</p>";
                            print "<div class=\"setting-redirect\"><p>Your account is activated, thank you!</p>";
                            print "<a href='admin.php?page=notedlinks.php'> <b>Please click here to begin to define settings</b></a></div>";
                            exit();
                        }
                    }
                }

                $sAction = Utils::curPageURL();
                require ( TEMPLATES_PATH . "admin/activate.html" );
                $nonce = wp_create_nonce('notedlinks_update_activation');
            }
        } catch (Exception $e) {
            print_r("<!--Error handling validation!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * Description: handle dashboard iframe
     */
    public function handle_dashboard()
    {
        try {
            $aConfig = $this->oSettings->getSetting();
            $linkStatsMsg = "";

            if ( NULL !== $_POST && sizeof($_POST) > 0 ) {
                //Button "get link stats" is clicked to update stats of links to be created
                if ( isset($_POST['get-link-stats']) && $_POST['get-link-stats'] == true ) {
                    //Currently DISABLE: if it has spent 24h since last update then calling to update stats of Links
                    //if ( ($aConfig["whitelist"]["last_update_stats"] + $aConfig["whitelist"]["interval_update"])<= date('U') ) {

                        $oLinkStats = Factory::create("LinkStatistics");
                        //make the process to update stats of links
                        $updateRes = $oLinkStats->processUpdateStatsOfLinks();

                        if ( $updateRes == true ) {
                            //updating the time of the last update of links stats
                            $aConfig["whitelist"]["last_update_stats"] = date('U');
                            $this->oSettings->actualizaAllSettings($aConfig);
                            $linkStatsMsg = "Your link analytics have been updated. Go to the Analytics tab.";
                        } else {
                            $linkStatsMsg = "There are no changes in your links. Modify your rules or content to generate new links.";
                        }
                        unset($oLinkStats);
                        unset($updateRes);
                    //}
                //Button "update" clicked to update whitelist manually
                } elseif ( isset($_POST['update']) && $_POST['update'] == true ) {
                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: \$_POST['actualize'] = true =>updating whitelist! -->\n";
                    }

                    require_once (CLASSES_PATH . "topics/class.whitelist.php");

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: creating \$oWhitelist object -->\n";
                    }

                    //create Whitelist object to be updated
                    $oWhitelist = Factory::create("whitelist");
                    $aAux = $aConfig["whitelist"];

                    if ($this->bDebug != 0) {
                        echo "<!-- [notedLinksAdmin] handle_options: \$aAux = \$aConfig['whitelist'] -->\n";
                    }

                    //calling function to update the whitelist manually
                    $aAuxResultado = $oWhitelist->actualizaManual($aAux);

                    $linkStatsMsg = "Your link rules have been applied successfully.";

                    if ($this->bDebug != 0)
                    {
                        echo "<!-- [notedLinksAdmin] handle_options: calling actualizaManual(\$aAux) -->\n";
                        echo "<!-- Result: \n";
                        print_r($aAuxResultado);
                        echo "-->\n";
                    }

                    //if old last update is different to the current last_update then update config/settings of whitelist
                    if ( $aAux["last_update"] != $aAuxResultado["last_update"] )
                    {
                        if ($this->bDebug != 0) {
                            echo "<!-- [notedLinksAdmin] handle_options: \$aAux['last_update'] != \$aAuxResultado['last_update'] then updating \$aConfig['whitelist'] = \$aAuxResultado -->\n";
                        }
                        //sets the config/settings of whitelist
                        $aConfig["whitelist"] = $aAuxResultado;
                    }
                }
            }
            /** ACTION for the POST form of settings **/
            $sAction = Utils::curPageURL();

            $sIframe = "<iframe id=\"nl-iframe\" src ='http://" . WEB_NOTEDLINKS . "/user/login/" . $aConfig["token_access"] . "' width=\"1000px\" height=\"850px\" frameborder=\"0\" allowtransparency=\"true\" style=\"background: #FFFFFF;\"></iframe>";

            require ( TEMPLATES_PATH . "admin/dashboard.html" );

        } catch (Exception $e) {
            print_r("<!--Error handling dashboard!: " . $e->getMessage() . " -->");
        }
    }

    public function admin_notice_nl()
    {
        try{
            $aConfig = $this->oSettings->getSetting();
            if(!$aConfig['process_page']['active'])
            {
                global $current_user ;
                $user_id = $current_user->ID;
                /* Check that the user hasn't already clicked to ignore the message */
                if ( ! get_user_meta($user_id, 'admin_notice_nl') ) {
                    echo '<div class="error"><p>';
                    printf(__('<b>In-text SEO internal link building  alert:</b> Please remember to validate your email address to activate the plugin  If you didn\'t receive an email visit <a href="admin.php?page=index.php">setting page</a> for more instructions.'));
                    echo "</p></div>";
                }
            }

        }  catch (Exception $e) {
            print_r("<!--Error handling admin notice!: " . $e->getMessage() . " -->");
        }
    }


    public function notedlinks_nag_ignore()
    {
       global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['notedlinks_nag_ignore']) && '0' == $_GET['notedlinks_nag_ignore'] ) {
             add_user_meta($user_id, 'admin_notice_nl', 'true', true);
       }
    }

    public function updateStatusPlugin($status, $sApiKey)
    {
        try{
            $aParameters = array("status" => $status,"widget_code" => $sApiKey);

            if ( $status === false ) {
                //add deactivation date
                $aParameters["last_deactivation"] = date("Y-m-d H:i:s");
            }

            $ch = curl_init(URL_STATUS);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $jsonResponse = curl_exec($ch);
            $aRes = json_decode($jsonResponse, true);

            //closing curl request
            curl_close($ch);

            return $aRes;
        } catch (Exception $e) {
            print_r("<!--Error updating status plugin!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * checkUpdateWp: function is executed when a user goes in admin area and it checks if wordpress is updated to send the current wp version to server
     * 
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-02-05
     * @version Release: 0.8
     *
     * @return boolean  the success of the update of wordpress version
     */
    public function checkUpdateWp ()
    {
        try {
            $aSettings = $this->oSettings->getSetting();
            //if current wp version is different to last saved version, update wp version in server
            if ( $aSettings["wp_version"] != get_bloginfo("version") ) {
                $aParameters = array( "widget" => $aSettings["process_page"]["api_key"],
                                      "wp_version" => get_bloginfo("version"));

                $ch = curl_init(URL_WP_VERSION_UPDATE);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $bRes = curl_exec($ch);

                //closing curl request
                curl_close($ch);

                return $bRes;
            }

        } catch ( Exception $e ) {
            print_r("<!--Error updating wordpress version!: " . $e->getMessage() . " -->");
        }
    }
}