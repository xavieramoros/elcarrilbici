<?php

/**
 * To handle statistics of links to be created by NotedLinks' plugin
 *
 * @category   Plugin
 * @package    classes
 * @subpackage replace
 * @copyright  Copyright 2015 Incubio (http://www.incubio.com)
 * @license    http://incubio.com/license
 * @version    Release: 0.8
 * @author     Juan Vargas <juan.vargas@incubio.com>
 * @author     Jairo Sarabia <jairo.sarabia@incubio.com>
 * @since      2014-01-29
 */

require_once ( MODEL_PATH . "model.links.php" );
require_once ( MODEL_PATH . "class.modelWhiteList.php" );
require_once ( CLASSES_PATH . "settings/class.settings.php");

class LinkStatistics 
{

    ///////////////
    // Attributes
    ///////////////
    
    private $modelLinks;
    private $modelWhiteList;
    private $posts      = array();
    private $urls       = array();
    private $aKeywords  = array();
    private $aStats     = array();
    private $oSettings  = NULL;

    ////////////////
    // Constructor
    ////////////////
    
    public function __construct()
    {
        $this->modelLinks = Factory::create("modelLinks");
        $this->modelWhiteList = Factory::create("modelWhiteList");
        $this->oSettings = Factory::create("settings");
    }
    
    //////////////
    // Services
    //////////////
    
    /**
     * To get and prepare posts data to search keyword
     * 
     * @author Juan Vargas Cracolici <juan.vargas@incubio.com>
     * @since 2014-01-29
     * @version Release: 0.8
     */
    public function getPosts() 
    {
        $posts = $this->modelLinks->getContentPosts();
        
        if($posts || !empty($posts)) {
            
            foreach ($posts as $key => $value) {
                
                if(!empty($value["post_content"]) && isset($value["guid"])) {

                    $this->posts[md5($value["guid"])] = array(
                                                            "url" => get_permalink($value["ID"]), 
                                                            "content" => $value["post_content"],
                                                            "title" => $value["post_title"]
                                    );
                    
                    $this->urls[md5($value["guid"])] = 0;
                }
            }
        }
    }
    
    /**
     * This function will look for ocurrencies of keywords in posts,
     * no href, not title (<h>). 
     * 
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @author Juan Vargas <juan.vargas@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @return array $this->aStats   array of keywords' statistics with ocurrences
     */
    public function getLinksOfKeywords() 
    {
        $this->aKeywords     = $this->modelWhiteList->getKeywords();
        $aSettings           = $this->oSettings->getSetting();
        $iMaxLinkSameKWxPost = $aSettings["appearance_limit"]["keyword_max"];
        $iMaxLinkInPost      = $aSettings["appearance_limit"]["page_max"];
        $aIgnorePages        = Utils::explode_trim(";", $aSettings["ignore_page"]);  
        
        if( empty($this->aKeywords) ) return false;
        
        //Getting last modified posts
        $this->getPosts();
        
        foreach ($this->aKeywords as $keywordMd5 => $keywordData) {

            $this->aStats[$keywordMd5]["keyword"] = $keywordData["keyword"];
            $this->aStats[$keywordMd5]["ocu"] = 0;
            unset($keywordData["keyword"]);

            foreach ($keywordData as $key => $keywordUrls) {

                //URL ORIGIN, only search ocurrencies in this post
                if ( !empty($keywordUrls["url_wp"]) ) {
            
                    $postData = $this->modelLinks->getPostByUrl($keywordUrls["url_wp"]);
                    
                    if ( !empty($postData) ) {
                        
                        // blacklist post by title
                        if(in_array($postData[0]["post_title"], $aIgnorePages)) continue;
                                
                        $occurrences = $this->occurrencesOfKeyword($this->aStats[$keywordMd5]["keyword"], $postData[0]["post_content"]);

                        // filter Max Links same Keyword per post, if '0' -> unlimited, always max $ocurrences
                        if ( $occurrences > $iMaxLinkSameKWxPost && $iMaxLinkSameKWxPost != 0)  $occurrences = $iMaxLinkSameKWxPost;

                        if( !isset($this->urls[md5($keywordUrls["url_wp"])]) ) $this->urls[md5($keywordUrls["url_wp"])] = 0;
                        
                        // if '0' -> unlimited, always true
                        $conditionMaxLinkInPost = $this->urls[md5($keywordUrls["url_wp"])] < $iMaxLinkInPost;
                        if($iMaxLinkInPost == 0)  $conditionMaxLinkInPost = true;

                        if ( $occurrences > 0  && $conditionMaxLinkInPost) {
                                                        
                            // filter Max Links by Post
                            $this->urls[md5($keywordUrls["url_wp"])] += $occurrences;
                            
                            if($iMaxLinkInPost > 0) {
                                while($this->urls[md5($keywordUrls["url_wp"])] > $iMaxLinkInPost)  {

                                    $this->urls[md5($keywordUrls["url_wp"])]--;
                                    $occurrences--;
                                }
                            
                            }
                    
                            $this->aStats[$keywordMd5]["urls"][] = array(   "url_wp" => $keywordUrls["url_wp"], 
                                                                            "url_to" => $keywordUrls["url_to"], 
                                                                            "ocu" => $occurrences
                                                                        );
                            $this->aStats[$keywordMd5]["ocu"] +=  $occurrences;
                        }  
                    }
     
                // ALL POSTS, search ocurrencies
                } else { 
                    
                    $this->matchesOfKeywordInLastPosts($this->aStats[$keywordMd5]["keyword"], $keywordUrls["url_to"], 
                            $iMaxLinkSameKWxPost, $aIgnorePages, $iMaxLinkInPost);
                }
            }

            //check if there is any data to send
            if ( !array_key_exists("urls", $this->aStats[$keywordMd5]) || !array_key_exists("ocu", $this->aStats[$keywordMd5]) ) {
                
                unset($this->aStats[$keywordMd5]);
                
            } 
        }
        unset($this->posts);
        return $this->aStats;
    }

    /**
     * Search number of occurrences of a keyword in a content of post
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @param  string $keyword     keyword to search for
     * @param  string $postContent content of post
     * @return int              number of ocurrences
     */
    public function occurrencesOfKeyword ($keyword, $postContent) 
    {
        $limit = -1;
        $reg   = '/(?:<(\w+)[^>]*>)?\b' . $keyword . '\b(?!<\/a>)(?!<\/h\d>)(<\/\\1>)?/';

        preg_replace($reg, '!X!', $postContent, $limit, $occurrences); 

        return $occurrences;
    }

    /**
     * To find number of matches of a Keyword in last $limit posts
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @author Juan Vargas <juan.vargas@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @param  string $keyword [description]
     * @param  string $urlTo   [description]
     */
    public function matchesOfKeywordInLastPosts($keyword, $urlTo, $iMaxLinkSameKWxPost, $aIgnorePages, $iMaxLinkInPost)
    {

        if( !empty($this->posts) ) {
            
            $keywordMd5 = md5($keyword);

            foreach ( $this->posts as $postData ) {

                // blacklist post by title
                if(in_array($postData["title"], $aIgnorePages)) continue;
                
                $occurrences = $this->occurrencesOfKeyword($this->aStats[$keywordMd5]["keyword"], $postData["content"]);
                
                // filter Max Links same Keyword per post, if '0' -> unlimited, always max $ocurrences
                if ( $occurrences > $iMaxLinkSameKWxPost && $iMaxLinkSameKWxPost != 0)  $occurrences = $iMaxLinkSameKWxPost;
                
                // if '0' -> unlimited, always true
                $conditionMaxLinkInPost = $this->urls[md5($postData["url"])] < $iMaxLinkInPost;
                if($iMaxLinkInPost == 0)  $conditionMaxLinkInPost = true;
                                        
                if ( $occurrences > 0  && $conditionMaxLinkInPost) {
                    
                    // filter Max Links by Post
                    $this->urls[md5($postData["url"])] += $occurrences;
                    if($iMaxLinkInPost > 0) {
                        while($this->urls[md5($postData["url"])] > $iMaxLinkInPost)  {

                            $this->urls[md5($postData["url"])]--;
                            $occurrences--;
                        }
                    }
                    
                    $this->aStats[$keywordMd5]["urls"][] = array("url_wp" => $postData["url"], "url_to" => $urlTo, "ocu" => $occurrences);
                    $this->aStats[$keywordMd5]["ocu"] +=  $occurrences;
                }
            }
        }
         
    }


    /**
     * processUpdateStatsOfLinks: this function makes all process needed to update stats of Links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return boolean true if process of update success, otherwise false
     */
    public function processUpdateStatsOfLinks () 
    {
        //getting current stats of links
        $this->getLinksOfKeywords();
        
        //getting last stats of links from DB
        $numLinkStats = $this->modelLinks->numberOfLinkStats();

        //compare old stats with current stats to update it
        if ( $numLinkStats > 0 ) { 
            //get previos stats of links from DB
            try {
                $dbLinkStats = $this->modelLinks->getAllLinkStats();

            } catch ( Exception $e ) {
                print_r("<!--Error getting stats from DB!: " . $e->getMessage() . " -->");
            }
            //get only keywords to update, filtering keywords without changes
            $aUpdateStats = $this->getLinksToUpdate($dbLinkStats);

            if ( !empty($aUpdateStats) ) {

                $updateRes = true;
                //try to update stats of changed keywords in DB
                try {
                    $updateRes = $updateRes && $this->updateLinkStats();

                } catch (Exception $e) {
                    print_r("<!--Error updating stats of links!: " . $e->getMessage() . " -->");
                }

                //try to send new stats of links to server
                try {
                    $updateRes = $updateRes && $this->requestToUpdateLinkStats($aUpdateStats);

                    return $updateRes;

                } catch (Exception $e) {
                    print_r("<!--Error updating stats in server!: " . $e->getMessage() . " -->");
                }

            } else {
                //echo "<br>There is NOT links to update!<br>";
            }
            return false;

        //no previous stats, then insert and send to server
        } else { 

            $updateRes = true;
            //try to insert all stats to DB
            try {
                $updateRes = $updateRes && $this->insertLinkStats();

            } catch (Exception $e) {
                print_r("<!--Error inserting stats of links!: " . $e->getMessage() . " -->");
            }

            //try to send the stats data to server
            try {
                $updateRes = $updateRes && $this->requestToUpdateLinkStats($this->aStats);

                return $updateRes;

            } catch (Exception $e) {
                print_r("<!--Error updating stats in server!: " . $e->getMessage() . " -->");
            }

            return false;
        }
    }

    /**
     * requestToUpdateLinkStats: curl request to send updated stats of links to server 
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return boolean if it success true, otherwise false
     */
    public function requestToUpdateLinkStats ( $aUpdateStats ) 
    {
        try {

            $aSettings = $this->oSettings->getSetting();

            //parameters to send
            $aParameters = array(
                "token" => $aSettings["process_page"]["token"],
                "key" => $aSettings["process_page"]["api_key"],
                "data" => json_encode($aUpdateStats)
            );

            //curl calling to update stats of links
            $ch = curl_init(URL_LINK_STATS);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //response of curl request
            $updateRes = curl_exec($ch);
            //closing curl request
            curl_close($ch);
            //var_dump($updateRes);
            if ( $updateRes == "1" ) {
                $updateRes = true;
            } else {
                $updateRes = false;
            }

            return $updateRes;

        } catch (Exception $e) {
            print_r("<!--Error in request to update stats to server!: " . $e->getMessage() . " -->");
        }

    }

    /**
     * insertLinkStats: insert all stats of links in DB
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return boolean if it success true, otherwise false
     */
    public function insertLinkStats ()
    {
        $res = true;

        foreach ($this->aStats as $keywordMd5 => $keywordStats) {
            $res = $res && $this->modelLinks->insertLinkStats($keywordMd5 ,$keywordStats["keyword"], $keywordStats["ocu"], serialize($keywordStats));
        }

        return $res;
    }

    /**
     * getLinksToUpdate: filter all previous stats of keywords in DB with same number of occurrences, updating the rest
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @param  array $dbLinkStats previous stats of keywords in DB
     * @return array              current stats changed or new
     */
    public function getLinksToUpdate( $dbLinkStats ) 
    {
        $aUpdateStats = $this->aStats;
        foreach ($dbLinkStats as $key => $dbKeywordStats) {
            //check if keyword exists in current stats
            if ( array_key_exists($dbKeywordStats["key"], $aUpdateStats) ) {
                //check if both occurrences are the same
                if ( $dbKeywordStats["occurrences"] == $aUpdateStats[$dbKeywordStats["key"]]["ocu"] ) {
                    //filtering this keyword, because there isn't changes
                    unset($aUpdateStats[$dbKeywordStats["key"]]);
                }
            } else { //stats of keyword to delete
                $aUpdateStats[$dbKeywordStats["key"]] = array("keyword" => $dbKeywordStats["keyword"], "ocu" => 0, "urls" => array());
            }
        }

        return $aUpdateStats;
    }

    /**
     * updateLinkStats: update DB with new stats of links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return boolean if success true, otherwise false
     */
    public function updateLinkStats ()
    {
        $res = true;
        try {
            //delete old stats
            $res = $res && $this->modelLinks->deleteLinkStats();
            //insert new stats
            $res = $res && $this->insertLinkStats();

            return $res;

        } catch ( Exception $e ) {
            print_r("<!--Error update Link Stats in DB!: " . $e->getMessage() . " -->");
        }
    }
}