<?php

/**
 * Model of links used in statistics of links
 *
 * @category   Plugin
 * @package    classes
 * @subpackage model
 * @copyright  Copyright 2015 Incubio (http://www.incubio.com)
 * @license    http://incubio.com/license
 * @version    Release: 0.8
 * @author     Juan Vargas <juan.vargas@incubio.com>
 * @author     Jairo Sarabia <jairo.sarabia@incubio.com>
 * @since      2014-01-29
 */

class modelLinks 
{

    private $linkStatsTable = "nl_link_stats";
    private $sColumns = array("key", "keyword", "occurrences", "links_data", "domain");
    private $bDebug = 0;

    public function __construct()
    {
    }

    /**
     * Model to get all posts, order by date, to search keywords
     * 
     * @author Juan Vargas Cracolici <juan.vargas@incubio.com>
     * @since 2014-01-29
     * @version Release: 0.8
     * 
     * @global objectDB $wpdb   object DB in WP
     * @param  int $limit       max. results
     * @return mixed            array with results, in case of error, false will be returned
     */
    public function getContentPosts($limit = 1000)
    {
       global  $wpdb;
       
       $query = "SELECT * FROM " . $wpdb->posts . " WHERE post_status = 'publish' ORDER BY post_modified DESC LIMIT $limit";
   
       return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * getPostByUrl: model to get data of a post by its url
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @param  string $url url of post to search for
     * @return array      data of post
     */
    public function getPostByUrl ($url) 
    {
       global  $wpdb;

       $query = "SELECT * FROM " . $wpdb->posts . " WHERE guid = '" . $url . "'";

       return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * createLinksTable: construction of query to create table of links in DB of Wordpress
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @return string  query to create table of links statistics
     */
    public function createLinksTable ()
    {
        try {

            if ($this->bDebug != 0) echo "<!-- [modelLinks] createLinksTable function: return create table query -->\n";
            
            $sQuery = "CREATE TABLE IF NOT EXISTS " . $this->linkStatsTable . " (
                      `id` INT(11) NOT NULL AUTO_INCREMENT ,
                      `key` varchar(255) NOT NULL,
                      `keyword` VARCHAR(255) NOT NULL ,
                      `occurrences` INT(11) UNSIGNED NOT NULL DEFAULT 0 ,
                      `links_data` MEDIUMTEXT NULL DEFAULT NULL ,
                      `domain` VARCHAR(255) NOT NULL ,
                      PRIMARY KEY (`id`) ,
                      FULLTEXT INDEX `keyword_idx` (`keyword` ASC) ,
                      FULLTEXT KEY `link_data_idx` (`links_data`),
                      FULLTEXT KEY `key_idx` (`key`))
                        ENGINE = MyISAM AUTO_INCREMENT=848 DEFAULT CHARSET=utf8";
            
            if ($this->bDebug != 0) {
                echo "<!-- [modelLinks] Query: \n $sQuery -->\n";
            }
            
            return $sQuery;

        } catch (Exception $e) {
            print_r("<!--Error creating table!: " . $e->getMessage() . " -->");
        }
    }

    /**
     * getLinkStatsOfKeyword: selects and returns all statistics of links from nl_link_stats table
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @param  string $keyword keyword to search for in links' table
     * @return array          $keyword statistics of links
     */
    public function getLinkStatsOfKeyword ( $keyword )
    {
        global $wpdb;

       $query = "SELECT * FROM `" . $wpdb->dbname . "`.`" . $this->linkStatsTable . "` WHERE keyword = '" . $keyword . "' AND domain = '" . $_SERVER['HTTP_HOST'] . "'";

       return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * updateLinkStats: updates statistics of a keyword into DB table of links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     *
     * @param  string $keywordMd5    md5 of keyword (key) to be updated
     * @param  int $occurrences new number of links to be created
     * @param  string $linksData   stats of links for $keyword keyword
     * @return  mixed  number of rows affected or false
     */
    public function updateLinkStats ( $keywordMd5, $occurrences, $linksData )
    {
        global $wpdb;

        $sQuery = "UPDATE `" . $wpdb->dbname . "`.`" . $this->linkStatsTable . "` SET occurrences = " . $occurrences . ", links_data = '" . $linksData . "' WHERE key = '" . $keywordMd5 . "' AND domain = '" . $_SERVER['HTTP_HOST'] . "'";

        return $wpdb->query($sQuery);
    }

    /**
     * insertLinkStats: inserts statistics of a keyword into DB table of links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-30
     * @version Release: 0.8
     * 
     * @param  string $sValues values of statistics to insert separated by commas
     * @return  mixed  number of rows affected or false
     */
    public function insertLinkStats ( $skey, $keyword, $occurrences, $statsData ) 
    {
        global $wpdb;

        return $wpdb->insert( "nl_link_stats", 
                       array( $this->sColumns[0] => $skey, 
                              $this->sColumns[1] => $keyword, 
                              $this->sColumns[2] => $occurrences, 
                              $this->sColumns[3] => $statsData, 
                              $this->sColumns[4] => $_SERVER['HTTP_HOST'] 
                            ), 
                       array( '%s', '%s', '%d', '%s', '%s' )
                    );
    }

    /**
     * deleteLinkStats: deletes statistics of a keyword from DB table of links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @param  string $keyword keyword to be deleted
     * @return  mixed  number of rows affected or false
     */
    public function deleteLinkStats ()
    {
        global $wpdb;

        $sQuery = "DELETE FROM `" . $wpdb->dbname . "`.`" . $this->linkStatsTable . "` WHERE domain = '" . $_SERVER['HTTP_HOST'] . "'";

        return $wpdb->query($sQuery);
    }

    /**
     * numberOfLinkStats: it returns number of rows in DB Table of Links to check if there is any data previously saved in WP DB
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return int number of rows in Links table
     */
    public function numberOfLinkStats ()
    {
        global $wpdb;

       $query = "SELECT COUNT(*) FROM `" . $wpdb->dbname . "`.`" . $this->linkStatsTable . "` WHERE domain = '" . $_SERVER['HTTP_HOST'] . "'";

       return $wpdb->get_var($query);
    }


    /**
     * getAllLinkStats: it returns all rows of Link stats in WP DB table of Links
     *
     * @author Jairo Sarabia <jairo.sarabia@incubio.com>
     * @since 2014-01-31
     * @version Release: 0.8
     * 
     * @return array stats of links in DB table of Links
     */
    public function getAllLinkStats () 
    {
        global $wpdb;

       $query = "SELECT * FROM `" . $wpdb->dbname . "`.`" . $this->linkStatsTable . "` WHERE domain = '" . $_SERVER['HTTP_HOST'] . "'";

       return $wpdb->get_results($query, ARRAY_A);
    }
}