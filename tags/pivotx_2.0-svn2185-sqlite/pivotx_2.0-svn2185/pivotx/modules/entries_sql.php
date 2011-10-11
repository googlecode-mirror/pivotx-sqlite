<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: entries_sql.php 2185 2009-10-22 15:09:06Z pivotlog $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";

require_once(dirname(__FILE__)."/module_sql.php");


class EntriesSql {

    // the name of the log
    var $logname;

    // the data for the current entry
    var $entry;

    // a nice and big array with all the dates.
    var $date_index;
    var $indexline;

    // a somewhat smaller array for the entries that share the same
    // directory as the current entry
    var $update_mode;
    var $updated;
    var $entry_index;
    var $entry_index_filename;

    // pointer to where we are..
    var $pointer;

    // some names and stuff..
    var $weblog;
    var $entriestable;
    var $commentstable;
    var $trackbackstable;
    var $tagstable;
    var $categoriestable;


    // public functions

    function EntriesSql($loadindex=TRUE, $allow_write=TRUE) {
        global $PIVOTX, $dbversion;

        static $initialisationchecks;

        //init vars..

        // Logname will be phased out eventually, since all will be based on categories.
        $this->logname = "standard";

        $this->entry = Array('code' => '', 'id' => '',  'template' => '',  'date' => '',  'user' => '',  'title' => '',  'subtitle' => '',  'introduction' => '',  'body' => '',  'media' => '',  'links' => '',  'url' => '',  'filename' => '',  'category' => '');

        $this->update_mode=TRUE;

        // Set the names for the tables we use.
        $this->entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
        $this->commentstable = safe_string($PIVOTX['config']->get('db_prefix')."comments", true);
        $this->trackbackstable = safe_string($PIVOTX['config']->get('db_prefix')."trackbacks", true);
        $this->tagstable = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);
        $this->categoriestable = safe_string($PIVOTX['config']->get('db_prefix')."categories", true);
        $this->pagestable = safe_string($PIVOTX['config']->get('db_prefix')."pages", true);
        $this->chapterstable = safe_string($PIVOTX['config']->get('db_prefix')."chapters", true);
        $this->extrafieldstable = safe_string($PIVOTX['config']->get('db_prefix')."extrafields", true);

        // Set up DB connection
        $this->sql = new sql('mysql',
            $PIVOTX['config']->get('db_databasename'),
            $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'),
            $PIVOTX['config']->get('db_password')
        );

        // Verify that the entries database tables exist. If not, we create them.
        // We do this only once, regardles of how many $PIVOTX['db']->lowlevel objects
        // are initialised.
        if (!$initialisationchecks) {

            $this->sql->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
            $tables = $this->sql->fetch_all_rows('no_names');
            $tables = make_valuepairs($tables, '', '0');

            if (!in_array($this->entriestable, $tables)) {
                makeEntriesTable($this->sql);
                // If we make the table, we set the DB to the most recent version..
                $PIVOTX['config']->set('db_version', $dbversion);
            }

            if (!in_array($this->commentstable, $tables)) {
                makeCommentsTable($this->sql);
            }

            if (!in_array($this->trackbackstable, $tables)) {
                makeTrackbacksTable($this->sql);
            }

            if (!in_array($this->tagstable, $tables)) {
                makeTagsTable($this->sql);
            }

            if (!in_array($this->categoriestable, $tables)) {
                makeCategoriesTable($this->sql);
            }

            // We also Verify that the pages database tables exist. If not, we create them.
            // It would be slightly more logical to do this in Pages(), but if we do it
            // here, it saves a query on each and every pageview.
            if (!in_array($this->pagestable, $tables)) {
                makePagesTable($this->sql);
            }

            if (!in_array($this->chapterstable, $tables)) {
                makeChaptersTable($this->sql);
            }

            if (!in_array($this->extrafieldstable, $tables)) {
                makeExtrafieldsTable($this->sql);
            }

            $initialisationchecks = true;            
        }
        

    }



 

    /**
     * Gets an array of archives - mysql implementation.
     *
     * In contrast to the flat file implementation, the file 
     * "db/ser-archives.php" isn't used.
     *
     * @param boolean $force ignored, only used by flat file implementation.
     * @param string $unit the unit of the archives.
     * @return array
     */
    function getArchiveArray($force=FALSE, $unit) {
        global $PIVOTX;

        $Archive_array=array();

        // Get an array with the weblognames
        $weblognames = $PIVOTX['weblogs']->getWeblogNames();

        // .. which we'll iterate through to collect all archives
        foreach($weblognames as $weblogname) {

            // Get the categories published in the current weblog
            $categories = $PIVOTX['weblogs']->getCategories($weblogname);
            $categories = array_map('safe_string', $categories);
            $categories = "'".implode("', '", $categories)."'";

            if ($unit=="month" || $unit=="year") {
                $datelength = 7;
            } else {
                $datelength = 10;
            }
            
            // Select all dates of entries in this weblog..
            $this->sql->query("SELECT DISTINCT(LEFT(date, $datelength)) AS date
                FROM " . $this->entriestable . " AS e
                LEFT JOIN " . $this->categoriestable . " AS c ON (c.target_uid = e.uid)
                WHERE c.category IN ($categories) AND e.status='publish'
                ORDER BY date ASC");

            $date_index = $this->sql->fetch_all_rows();

            $date_index = make_valuepairs($date_index, '', 'date');

            // echo nl2br(htmlentities($this->sql->get_last_query()));

            foreach ($date_index as $date) {
                $name = makeArchiveName($date, $weblogname, $unit);
                $Archive_array[$weblogname][$name] = $date;
            }
        }

        // sort the array, to maintain correct order..
        foreach ($Archive_array as $key => $value) {
            krsort($Archive_array[$key]);
        }

        return $Archive_array;

    }





    function disallow_write() {
        $this->allow_write=FALSE;
    }


    function allow_write() {
        $this->allow_write=TRUE;
    }

    /**
     * Gets the number of entries
     * @return int
     */
    function get_entries_count() {

        $this->sql->query("SELECT COUNT(*) AS count FROM " . $this->entriestable . " WHERE 1;");

        $res = $this->sql->fetch_row();

        return $res['count'];

    }

    /**
     * Gets the code of the next entry - mysql implementation.
     *
     * @param int $num
     * @return int
     */
    function get_next_code($num) {


        $offset = max((intval($num)-1),0);

        $this->sql->query("SELECT uid FROM ". $this->entriestable ."
            WHERE date>". $this->sql->quote($this->entry['date']) . "
            ORDER BY date ASC
            LIMIT $offset, 1");

        $res = $this->sql->fetch_row();

        if ($res['uid']>0) {
            return intval($res['uid']);
        } else {
            return false;
        }

    }

    /**
     * Gets the code of the previous entry - mysql implementation.
     *
     * @param int $num
     * @return int
     */
    function get_previous_code($num) {

        $offset = max((intval($num)-1),0);

        $this->sql->query("SELECT uid FROM ". $this->entriestable ."
            WHERE date<". $this->sql->quote($this->entry['date']) . "
            ORDER BY date DESC
            LIMIT $offset, 1");

        $res = $this->sql->fetch_row();

        if ($res['uid']>0) {
            return intval($res['uid']);
        } else {
            return false;
        }


    }

    /**
     * Checks whether the current DB model needs to keep a separate index.
     * The flat file model does, but Mysql doesn't..
     *
     * @return boolean
     */
    function need_index() {

        // the sql file database needs no index.
        return false;

    }

    /**
     * rebuild the index of the Mysql Database. just here for compatibility.
     */
    function generate_index() {

        // Not needed

    }

    /**
     * Tells if the entry exists - mysql implementation.
     *
     * @param int $code The code/id of the entry.
     * @return boolean
     */
    function entry_exists($uid) {

        // Fetch the entry
        $qry = array();

        $qry['select'] = "uid";
        $qry['from'] = $this->entriestable;
        $qry['where'] = "uid=" . $this->sql->quote($uid);
        $qry['limit'] = 1;

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        return ($this->sql->fetch_row());

    }

    /**
     * Gets the date for an entry
     *
     * @param int $code
     * @return string
     */
    function get_date($code) {

        if (isset($this->date_index[$code])) {
            return $this->date_index[$code];
        } else {
            return 0;
        }

    }


    /**
     * Retrieves a full entry as an associative array, and returns it. The $code
     * parameter can be a code/uid or an URI. The optional $date parameter helps
     * to narrow it down, if there's more than one option.
     *
     * @param mixed $code
     * @param string $date
     * @return array
     */
    function read_entry($code, $date="") {
        global $PIVOTX;

        // We need to fetch an entry, but first we see if it's in the entrycache
        // already, else we get it from the DB.

        if ( $PIVOTX['cache']->get("entries", $code) ) {

            // We've already got it!
            $this->entry = $PIVOTX['cache']->get("entries", $code);

            return $this->entry;

        }
        
        // Let's get it from the DB.

        $qry = array();

        $qry['select'] = "*, uid AS code";
        $qry['from'] = $this->entriestable;
        $qry['limit'] = 1;

        if (is_numeric($code)) {
            $qry['where'][] = "uid=" . $this->sql->quote($code);
        } else {
            $qry['where'][] = "uri=" . $this->sql->quote($code);
        }

        if (!empty($date)) {
           $qry['where'][] = "date like '" . $this->sql->quote($date, true) . "%'";
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();
        $this->entry = $this->sql->fetch_row();

        // Set the link..
        $this->entry['link'] = makeFilelink($this->entry, '', '');

        $this->entry['vialink'] = $this->entry['via_link'];
        $this->entry['viatitle'] = $this->entry['via_title'];

        // Next we need to get the categories for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("categories", $code) ) {
            
            $this->entry['category'] = $PIVOTX['cache']->get("categories", $code);
            
        } else {

            $this->sql->query("SELECT category FROM " . $this->categoriestable . " WHERE target_uid=". intval($this->entry['uid']));

            $category = $this->sql->fetch_all_rows();
            $category = make_valuepairs($category, '', 'category');
            $this->entry['category'] = $category;

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("categories", $code, $this->entry['category']);

        }

        // Next we need to get the comments for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("comments", $code) ) {

            $this->entry['comments'] = $PIVOTX['cache']->get("comments", $code);

        } else {

            $this->sql->query("SELECT * FROM " . $this->commentstable . " WHERE entry_uid=". intval($this->entry['uid']) . " ORDER BY date ASC");

            $temp_comments = $this->sql->fetch_all_rows();

            $this->entry['comments'] = array();

            if(is_array($temp_comments)) {
                foreach($temp_comments as $temp_comment) {
                    $this->entry['comments'][ $temp_comment['uid'] ] = $temp_comment;
                }
            }

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("comments", $code, $this->entry['comments']);

        }

        // Next we need to get the extrafields for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("extrafields", $code) ) {

            $this->entry['extrafields'] = $PIVOTX['cache']->get("extrafields", $code);

        } else {

            $this->sql->query("SELECT * FROM " . $this->extrafieldstable . " WHERE contenttype='entry' and target_uid=". intval($this->entry['uid']) . " ORDER BY uid ASC");

            $temp_fields = $this->sql->fetch_all_rows();

            $this->entry['extrafields'] = array();

            if(is_array($temp_fields)) {
                foreach($temp_fields as $temp_field) {
                    
                    // Check if it's a serialised value..
                    if (is_array(unserialize($temp_field['value']))) {
                        $temp_field['value'] = unserialize($temp_field['value']);
                    }
                    
                    $this->entry['extrafields'][ $temp_field['fieldkey'] ] = $temp_field['value'];
                }
            }

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("extrafields", $code, $this->entry['extrafields']);


        }

        $this->entry['commcount'] = count($this->entry['comments']);

        $PIVOTX['cache']->set("entries", $code, $this->entry);


        return $this->entry;
    }



    /**
     * Tries to guess an entry by it's (incomplete) URI and date (if 
     * necessary). The entry is returned as an associative array.
     *
     * @param string $uri
     * @param string $date
     * @return array
     */
    function guess_entry($uri, $date) {

        $qry = array();

        $qry['select'] = "uid";
        $qry['from'] = $this->entriestable;
        $qry['limit'] = 1;

        $qry['where'] = "uri=" . $this->sql->quote($uri);

        $query = $this->sql->build_select($qry);
        $this->sql->query();
        $tempentry = $this->sql->fetch_row();

        // Try again, now with LIKE, and perhaps trailing characters..
        if (empty($tempentry['uid'])) {
            $uri = makeURI($uri);
            $qry['where'] = "uri LIKE '" . $this->sql->quote($uri, true) . "%'";
            
            $query = $this->sql->build_select($qry);
            $this->sql->query();
            $tempentry = $this->sql->fetch_row();
            
        }

        // TODO: Handle multiple matches. Use $date (if given) to select between them.

        if (!empty($tempentry['uid'])) {
            // Looks like we found one! Now get it properly!
            $this->read_entry($tempentry['uid']);
        }

        return $this->entry;

    }


    /**
     * Read a bunch of entries
     *
     * @param array $params
     * @return array
     */
    function read_entries($params) {
        global $PIVOTX;

        $qry = array();

        $qry['select'] = "e.*, e.uid AS code, e.comment_count AS commcount, e.comment_names AS commnames, e.trackback_count AS trackcount, e.trackback_names AS tracknames";
        $qry['from'] = $this->entriestable. " AS e";

        if(!empty($params['offset'])) {
            $params['date'] = "";
            $qry['limit'] = intval($params['offset']) . ", " . $params['show'];
        } else {
            $qry['limit'] = $params['show'];
        }

        if (!empty($params['orderby'])) {
            $orderby = "e.".safe_string($params['orderby'], true);
        } else {
            $orderby = "e.date";
        }

        if ($params['order'] == "random") {
            $qry['order'] = "RAND()";
        } elseif($params['order']=="desc") {
            $qry['order'] = $orderby . " DESC";
        } else {
            $qry['order'] = $orderby . " ASC";

        }

        if(!empty($params['uid'])) {
            $aUids= explode(",",$params['uid']);
            foreach($aUids as $k=>$uid) {
                if(!is_numeric($uid)) {
                    unset($aUids[$k]);
                }
            }
            if(!empty($aUids)) {
                $uids= '"'.implode('","',$aUids).'"';
                $qry['where'][]= "e.uid in (".$uids.")";
            }
            
        } else {
    
            if(!empty($params['start'])) {
                $params['date'] = "";
                $params['start'] = explode("-", $params['start']);
                $start = sprintf("%s-%s-%s %s:%s:00", $params['start'][0], $params['start'][1], 
                    $params['start'][2], $params['start'][3], $params['start'][4]);
                $qry['where'][] = $orderby . " > " . $this->sql->quote($start);
            }
    
            if(!empty($params['end'])) {
                $params['date'] = "";
                $params['end'] = explode("-", $params['end']);
                $end = sprintf("%s-%s-%s %s:%s:00", $params['end'][0], $params['end'][1], 
                    $params['end'][2], $params['end'][3], $params['end'][4]);
                $qry['where'][] = $orderby . " < " . $this->sql->quote($end);
            }
     
            if(!empty($params['date'])) {
                $params['date'] = explode("-", $params['date']);
                $year = (int) $params['date'][0];
                if (count($params['date']) == 1) {
                    $start = sprintf("%s-%s-%s 00:00:00", $year, 1, 1);
                    $year++;
                    $end = sprintf("%s-%s-%s 00:00:00", $year, 1, 1);
                } elseif (count($params['date']) == 2) {
                    $month = (int) $params['date'][1];
                    $start = sprintf("%s-%s-%s 00:00:00", $year, $month, 1);
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                    $end = sprintf("%s-%s-%s 00:00:00", $year, $month, 1);
                } else {
                    $month = (int) $params['date'][1];
                    $day = (int) $params['date'][2];
                    $start = sprintf("%s-%s-%s 00:00:00", $year, $month, $day);
                    $end = sprintf("%s-%s-%s 23:59:00", $year, $month, $day);
                }
                $qry['where'][] = "$orderby > " . $this->sql->quote($start);
                $qry['where'][] = "$orderby < " . $this->sql->quote($end);
            }
             
            // Do not use a limit if a date range is given
            if((!empty($params['start']) && !empty($params['end'])) || !empty($params['date'])) {
                unset($qry['limit']);
            } 
    
            if(!empty($params['status'])) {
                $qry['where'][] = "e.status = " . $this->sql->quote($params['status']);
            }
    
    
            if(!empty($params['user'])) {
                $qry['where'][] = "e.user = " . $this->sql->quote($params['user']);
            }
    
            $qry['group'] = "e.uid";
    
            if( !empty($params['cats']) ) {
                $qry['select'] .= ", c.category";
                $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
                if (is_array($params['cats'])) {
                    $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
                } else {
                    $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
                }
            }
            if( !empty($params['tags']) ) {
                $qry['select'] .= ", t.tag";
                $qry['leftjoin'][$this->tagstable . " AS t"] = "e.uid = t.target_uid";
                
                if(strpos($params['tags'],",") !== false) {
                    $aTags= explode(",",str_replace(" ","",$params['tags']));
                    $tags= implode("', '", $aTags);
                    
                    $qry['where'][] = "t.tag IN ('" . $tags. "')";
                } else {
                    $qry['where'][] = "t.tag= " . $this->sql->quote($params['tags']);
                }

            }
        }
        
        $query = $this->sql->build_select($qry);
        $this->sql->query();

        // echo nl2br(htmlentities($query));

        $rows = $this->sql->fetch_all_rows();
        $entries = array();

        foreach ($rows as $entry) {
            $entries[ $entry['uid'] ] = $entry;
            
            // Make the 'excerpts'..
            $entries[ $entry['uid'] ]['excerpt'] = makeExcerpt($entry['introduction']);
            
            // Set the link..
            $entries[ $entry['uid'] ]['link'] = makeFilelink($entry, '', '');
        }



        if (is_array($entries)) {

            $ids = make_valuepairs($entries, '', 'uid');
            $ids = "'". implode("', '", $ids) . "'";
            
            // Ok, now we need to do a second query to get the correct arrays with all of the categories.
            $this->sql->query("SELECT * FROM ". $this->categoriestable ." AS c WHERE target_uid IN ($ids)");

            $tempcats = $this->sql->fetch_all_rows();

            // group them together by entry.
            foreach($tempcats as $cat) {
                $cats[ $cat['target_uid'] ][] = $cat['category'];
            }

            // Add them to our simple cache, for later retrieval..
            $PIVOTX['cache']->setMultiple("categories", $cats);

            // Now, attach the categories to the entries..
            foreach($cats as $uid=>$cat) {
                foreach($entries as $key=>$entry) {
                    if ($entries[$key]['uid'] == $uid) {
                        $entries[$key]['category'] = $cat;
                        continue;
                    }
                }
            }
            
            // And a third query to get the correct records with all of the extra fields.            
            $this->sql->query("SELECT * FROM ". $this->extrafieldstable ." AS e WHERE contenttype='entry' AND target_uid IN ($ids)");

            $tempfields = $this->sql->fetch_all_rows();

            // Now, attach the tempfields to the entries..
            foreach($tempfields as $tempfield) {
                foreach($entries as $key=>$entry) {
                    if ($entries[$key]['uid'] == $tempfield['target_uid']) {
                        if (!is_array($entries[ $key ]['extrafields'])) {
                            $entries[ $key ]['extrafields'] = array();
                        }
                        
                        // Check if it's a serialised value..
                        if (is_array(unserialize($temp_field['value']))) {
                            $temp_field['value'] = unserialize($temp_field['value']);
                        }
                    
                        $entries[ $key ]['extrafields'][ $tempfield['fieldkey'] ] = $tempfield['value'];
                    }
                }
            }            

        }

        // Add them to our simple cache, for later retrieval..
        $PIVOTX['cache']->setMultiple("entries", $entries);

        return $entries;


    }

    /**
     * Read the latest comments
     *
     * @param array $params
     * @return array
     */
    function read_latestcomments($params) {
        global $PIVOTX;

        $count = get_default($params['count'], 10);

        $qry = array();
        $qry['select'] = "co.*, e.title";
        $qry['from'] = $this->commentstable. " AS co";
        $qry['join'][] = $this->entriestable. " AS e";
        $qry['where'][] = "co.entry_uid = e.uid";
        $qry['where'][] = "co.moderate = 0";
        $qry['where'][] = "e.status = 'publish'";
        $qry['order'] = "co.date DESC";
        $qry['limit'] = intval($count);
        
        if( !empty($params['cats']) ) {
            $qry['select'] .= ", c.category";
            $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
            if (is_array($params['cats'])) {
                $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
            } else {
                $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
            }
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        // echo nl2br(htmlentities($query));


        $comments = $this->sql->fetch_all_rows();
                
        return $comments;
        
    }

    /**
     * Read the last trackbacks
     *
     * @param array $params
     * @return array
     */
    function read_lasttrackbacks($params) {
        global $PIVOTX;

        $count = get_default($params['count'], 10);

        $qry = array();
        $qry['select'] = "tb.*, e.title";
        $qry['from'] = $this->trackbackstable. " AS tb";
        $qry['join'][] = $this->entriestable. " AS e";
        $qry['where'][] = "tb.entry_uid = e.uid";
        $qry['where'][] = "tb.moderate = 0";
        $qry['where'][] = "e.status = 'publish'";
        $qry['order'] = "tb.date DESC";
        $qry['limit'] = intval($count);
        
        if( !empty($params['cats']) ) {
            $qry['select'] .= ", c.category";
            $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
            if (is_array($params['cats'])) {
                $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
            } else {
                $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
            }
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        $trackbacks = $this->sql->fetch_all_rows();
                
        return $trackbacks;
        
    }




    /**
     * Sets the current entry to the contents of $entry - mysql
     * implementation.
     *
     * Returns the inserted entry as it got stored in the database with
     * correct code/id.
     *
     * @param array $entry The entry to be inserted
     * @return array
     */
    function set_entry( $entry ) {

        $this->entry = $entry;

        if ( $this->entry['code'] == '>' ) {
           $this->entry['code'] = '';
        }

        $this->entry['uid'] = $this->entry['code'];

        return $this->entry;
    }

    /**
     * Saves the current entry - mysql implementation.
     *
     * Returns true if successfully saved. Current implementation
     * seems to return true no matter what...
     *
     * @param boolean $update_index Whether to update the date index.
     * @return boolean
     */
    function save_entry($update_index=TRUE) {

        // Set the 'commcount', 'commnames'..
        unset($commnames);
        if (isset($this->entry['comments'])) {

            foreach ($this->entry['comments'] as $comment) {
                if (block_type($comment['ip'])=="none") {
                    if ($comment[moderate]!=1) {
                        $commnames[]=stripslashes($comment['name']);
                    } else {
                        // if moderation is on, we add the name as '-'..
                        $commnames[]='-';
                    }
                }
            }

            if (isset($commnames) && (count($commnames)>0)) {
                $this->entry['comment_names'] = implode(", ",array_unique ($commnames));
                $this->entry['comment_count'] = count($commnames);
            } else {
                $this->entry['comment_names'] = "";
                $this->entry['comment_count'] = 0;
            }

        } else {
            unset ($this->entry['comments']);
            $this->entry['comment_names'] = "";
            $this->entry['comment_count'] = 0;
        }


        // Set the 'trackcount', 'tracknames'..
        unset($tracknames);
        if (isset($this->entry['trackbacks'])) {

            foreach ($this->entry['trackbacks'] as $trackback) {
                $tracknames[]=stripslashes($trackback['name']);
            }

            if (isset($tracknames) && (count($tracknames)>0)) {
                $this->entry['trackback_names'] = implode(", ",array_unique ($tracknames));
                $this->entry['trackback_count'] = count($tracknames);
            } else {
                $this->entry['trackback_names'] = "";
                $this->entry['trackback_count'] = 0;
            }
        } else {
            unset ($this->entry['trackbacks']);
            $this->entry['trackback_names']="";
            $this->entry['trackback_count'] = 0;
        }

        // Make sure we have an URI
        if (empty($this->entry['uri'])) {
            $this->entry['uri'] = makeURI($this->entry['title']); 
        }

        $values = array(
            'title' => $this->entry['title'],
            'uri' => $this->entry['uri'],
            'subtitle' => $this->entry['subtitle'],
            'introduction' => $this->entry['introduction'],
            'body' => $this->entry['body'],
            'convert_lb' => intval($this->entry['convert_lb']),
            'status' => $this->entry['status'],
            'date' => $this->entry['date'],
            'publish_date' => $this->entry['publish_date'],
            'edit_date' => date("Y-m-d H:i:s"),
            'user' => $this->entry['user'],
            'allow_comments' => $this->entry['allow_comments'],
            'keywords' => $this->entry['keywords'],
            'via_link' => $this->entry['vialink'],
            'via_title' => $this->entry['viatitle'],
            'comment_count' => $this->entry['comment_count'],
            'comment_names' => $this->entry['comment_names'],
            'trackback_count' => $this->entry['trackback_count'],
            'trackback_names' => $this->entry['trackback_names']
        );


        // Check if the entry exists
        $this->sql->query("SELECT uid FROM " . $this->entriestable . " WHERE uid=" . intval($this->entry['uid']));

        if (is_array($this->sql->fetch_row())) {

            // It exists, we do an update..

            $qry=array();
            $qry['update'] = $this->entriestable;
            $qry['value'] = $values;
            $qry['where'] = "uid=" . intval($this->entry['uid']);

            $this->sql->build_update($qry);
            $this->sql->query();


        } else {

            // New entry.
            $qry=array();
            $qry['into'] = $this->entriestable;
            $qry['value'] = $values;

            $this->sql->build_insert($qry);
            
            $this->sql->query();
            
            // Set the entries' uid to the last inserted id..
            $this->entry['uid'] = $this->sql->get_last_id();
            
            // A bit of a nasty hack, but needed when we have to insert tags for a new entry,
            // and $db is not yet aware of the new $uid.
            $GLOBALS['db']->entry['uid'] = $this->entry['uid'];

        }


        // We will also need to save the comments and trackbacks.. We should
        // try to prevent doing unneeded queries, so we only insert comments
        // and trackbacks which have no ['uid'] yet. (because these are either
        // new, or are being converted from flat files)
        foreach ($this->entry['comments'] as $comment) {

            if ($comment['uid']=="") {

                // Ah, let's insert it.
                $comment['entry_uid'] = $this->entry['uid'];

                // make sure we don't try to add the 'remember info' field..
                if (isset($comment['rememberinfo'])) { unset($comment['rememberinfo']); }

                // Registered, Notify, etc. have to be integer values.
                $comment['registered'] = intval($comment['registered']);
                $comment['notify'] = intval($comment['notify']);
                $comment['discreet'] = intval($comment['discreet']);
                $comment['moderate'] = intval($comment['moderate']);
                $comment['entry_uid'] = intval($comment['entry_uid']);

                $qry=array();
                $qry['into'] = $this->commentstable;
                $qry['value'] = $comment;

                $this->sql->build_insert($qry);
                $this->sql->query();

            }

        }

        foreach ($this->entry['trackbacks'] as $trackback) {

            if ($trackback['uid']=="") {

                // Ah, let's insert it.
                $trackback['entry_uid'] = $this->entry['uid'];

                $qry=array();
                $qry['into'] = $this->trackbackstable;
                $qry['value'] = $trackback;

                $this->sql->build_insert($qry);
                $this->sql->query();

            }
        }


        // Delete the keywords / tags..
        $qry=array();
        $qry['delete'] = $this->tagstable;
        $qry['where'] = "contenttype='entry' AND target_uid=" . intval($this->entry['uid']);

        $this->sql->build_delete($qry);
        $this->sql->query();

        $tags = getTags(false, $this->entry['introduction'].$this->entry['body'], $this->entry['keywords']);

        // Add the keywords / tags..
        foreach ($tags as $tag) {
            $qry=array();
            $qry['into'] = $this->tagstable;
            $qry['value'] = array(
                'tag' => $tag,
                'contenttype' => 'entry',
                'target_uid' => $this->entry['uid']
            );

            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        // Delete the categories..
        $qry=array();
        $qry['delete'] = $this->categoriestable;
        $qry['where'] = "target_uid=" . intval($this->entry['uid']);

        $this->sql->build_delete($qry);
        $this->sql->query();

        // Add the Categories..
        foreach ($this->entry['category'] as $cat) {
            $qry=array();
            $qry['into'] = $this->categoriestable;
            $qry['value'] = array(
                'category' => safe_string($cat, true),
                'target_uid' => $this->entry['uid']
            );

            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        // Store the 'extra fields'
        if (!is_array($this->entry['extrafields'])) { $this->entry['extrafields'] = array(); }
        $extrakeys = array();
        foreach ($this->entry['extrafields'] as $key => $value) {
            $extrakeys[] = $this->sql->quote($key);        
            
            // No need to store empty values
            if (empty($value)) { unset ($this->entry['extrafields'][$key]); }
            
            // Serialize any arrays..
            if (is_array($value)) {
                $this->entry['extrafields'][$key] = serialize($value);
            }
        }
        
        if (count($extrakeys)>0) {
            $qry=array();
            $qry['delete'] = $this->extrafieldstable;
            $qry['where'][] = "target_uid=" . intval($this->entry['uid']);
            $qry['where'][] = "contenttype='entry'";
            $qry['where'][] = "fieldkey IN (" . implode(", ", $extrakeys) . ")";
            $this->sql->build_delete($qry);
            $this->sql->query();        
        }
        
        foreach ($this->entry['extrafields'] as $key => $value) {
            $qry=array();
            $qry['into'] = $this->extrafieldstable;
            $qry['value'] = array(
                'fieldkey' => safe_string($key, true),
                'value' => $value,
                'contenttype' => 'entry',
                'target_uid' => $this->entry['uid']
            );
            $this->sql->build_insert($qry);
            $this->sql->query();
        }
        
        //echo "<pre>\n"; print_r($extrakeys); echo "</pre>\n";
        //echo "<pre>\n"; print_r($this->entry['extrafields']); echo "</pre>\n";

        return TRUE;

    }


    function delete_entry() {


        $uid = intval($this->entry['uid']);

        $this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid=$uid LIMIT 1;");
        $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE entry_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE entry_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->tagstable . " WHERE contenttype='entry' AND target_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->categoriestable . " WHERE target_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->extrafieldstable . " WHERE contenttype='entry' AND target_uid=$uid;");

    }





    /**
     * Delete one or more entries
     *
     * @param array $ids
     */
    function delete_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE entry_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE entry_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->tagstable . " WHERE target_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->categoriestable . " WHERE target_uid IN ($ids);");

        return true;

    }


    /**
     * Set one or more entries to 'publish'
     *
     * @param array $ids
     */
    function publish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $qry=array();
        $qry['update'] = $this->entriestable;
        $qry['value'] = array('status' => 'publish');
        $qry['where'] = "uid IN ($ids)";

        $query = $this->sql->build_update($qry);

        $this->sql->query();

        return true;


    }


    /**
     * Set one or more entries to 'hold'
     *
     * @param array $ids
     */
    function depublish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $qry=array();
        $qry['update'] = $this->entriestable;
        $qry['value'] = array('status' => 'hold');
        $qry['where'] = "uid IN ($ids)";

        $query = $this->sql->build_update($qry);

        $this->sql->query();

        return true;

    }


    /**
     * Checks if any entries set to 'timed publish' should be published.
     *
     */
    function checkTimedPublish() {
        $this->sql->query("UPDATE `".$this->entriestable."` SET status='publish', date=publish_date
            WHERE status='timed' AND publish_date<NOW();");
    }


    /**
     * Deletes the comment with the given comment ID (uid), updates the 
     * comment count for the associated entry and clears the 
     * related cache items.
     *
     * @param int $uid
     */
    function delete_comment( $uid ) {
        global $PIVOTX;

        // Find the associated entries so we can update comment count and clear the cache.
        $this->sql->query("SELECT entry_uid FROM " . $this->commentstable . " WHERE uid=$uid;");
        $comment = $this->sql->fetch_row();
        $entry_uid = $comment['entry_uid'];
        $PIVOTX['cache']->set("comments", $entry_uid, array());
        $PIVOTX['cache']->set("entries", $entry_uid, array());
        $this->sql->query("UPDATE " . $this->entriestable . " SET comment_count = comment_count -1 WHERE uid=$entry_uid;");

        $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE uid=$uid;");

    }

    /**
     * Deletes the trackback with the given trackback ID (uid), updates the 
     * trackback count for the associated entry and clears the 
     * related cache items.
     *
     * @param int $uid
     */
    function delete_trackback( $uid ) {
        global $PIVOTX;

        // Find the associated entries so we can update trackback count and clear the cache.
        $this->sql->query("SELECT entry_uid FROM " . $this->trackbackstable . " WHERE uid=$uid;");
        $trackback = $this->sql->fetch_row();
        $entry_uid = $trackback['entry_uid'];
        $PIVOTX['cache']->set("trackbacks", $entry_uid, array());
        $PIVOTX['cache']->set("entries", $entry_uid, array());
        $this->sql->query("UPDATE " . $this->entriestable . " SET trackback_count = trackback_count -1 WHERE uid=$entry_uid;");

        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE uid=$uid;");

    }



    // -----------------
    // private functions
    // ------------------



    // Convert a string, so that it only contains alphanumeric and a few others.
    function safestring($name) {
        return preg_replace("/[^-a-zA-Z0-9_.]/", "", $name);
    }

}


?>
