<?php
include (dirname(__FILE__)."/abstractSql.php");


/**
 * Class SQL: a simple DB class.
 *
 * For more information and instructions, see: http://www.twokings.eu/tools/
 *
 * This file is part of A Simple SQL Class. A Simple SQL Class and all its
 * parts are licensed under the GPL version 2. see:
 * http://www.twokings.eu/tools/license for more information.
 * 
 * Tutti i metodi make_xxx_table sono derivati dalle funzioni originariamente presenti in pivotx/data.php 
 * 
 * @version 1.1_JPP
 * @author Jean Paul Piccato, modded version
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 */
class sqlite extends abstractSql {

var $error_msg = "";

		function sqlite($dbase="", $host="", $user="", $pass="") {
			  parent::sql($dbase, $host, $user, $pass);
		}

    /**
     * Set up the Database connection, depending on the selected DB model.
     */
    function connection() {
        global $PIVOTX, $return_silent;

        /**
         * If we had a connection error before, perhaps we should return
         * quietly, to prevent the user's screen from overflowing with SQL
         * errors. We use the global $return_silent, so it works if you have
         * multiple instances of the sql object.
         */
        if ($return_silent == true) {
            return false;
        }

        /**
        * Set up a link for SQLite model
        */

        // Set up the link, if not already done so.
        if ($this->sql_link == 0) {
						if ($this->sql_link = sqlite_open($PIVOTX['paths']['db_path'].$this->dbase.".sqlite", 0666, $sqlite_error)) { 
						     // SELECT tablename.columnname FROM table; 
						     // will cause SQLite to return an array having tablename.field_name as the array index. (e.g. $result['tablename.field_name'])
								 // To let SQLite return an array having only field_name as the array index (e.g. $result['field_name']) you can issue a 'PRAGMA short_column_names = 1' query:
								 // sqlite_query($connection_id, 'PRAGMA short_column_names = 1');
								 // This behaviour is more consistent with the other database extensions.
								 						     
		             $this->query('PRAGMA short_column_names = 1');
						} else { 
		          // We couldn't connect to the database. Print an error.
		          $this->error( "Can't select Database '<tt>". $this->dbase ."</tt>'" , '', $sqlite_error);
		
		          // If silent_after_failed_connect is set, from now on return without errors/warnings
		          if($this->silent_after_failed_connect) {
		              $return_silent = true;
              }
               return false;
						}
				}

        return true;
    }


    /**
     * Close sql link
     */
    function close() {
        sqlite_close($this->sql_link);
    }


    /**
     * Gets the current SQLite library version
     *
     * @return string
     */
    function get_server_info() {
        return sqlite_libversion();
    }

    /**
     */
    function get_internal_error()  {
	  if (sqlite_last_error($this->sql_link) != 0) {
    	  	return sqlite_error_string(sqlite_last_error($this->sql_link));
	  } else {
		return $error_msg;
	  }
    }
    
    /**
    * Non funziona con query del tipo SELECT
    */
    function sql_affected_rows() {
    		return sqlite_changes($this->sql_link);
    }
    
		function sql_doquery ($query, $link_identifier) {
				return sqlite_query($link_identifier, $query, $error_msg);
		}
		
		function sql_errno($link_identifier){
				return sqlite_last_error($link_identifier);
		}


    /**
     * Get the last inserted id
     *
     * @param  none
     */
    function get_last_id() {
        return sqlite_last_insert_rowid($this->sql_link);
    }

    /**
     * Gets the number of selected rows
     */
    function num_rows()  {
        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        return sqlite_num_rows($this->sql_result );
    }


    /**
     * Quote variable to make safe to use in a SQL query. If you pass
     * $skipquotes as true, the string will just have added slashes, otherwise it
     * will be wrapped in quotes for convenience
     *
     * @param string $value to quote
     * @param boolean $skipquotes  to skip adding quotes
     * @return string quoted value
     */
    function quote($value, $skipquotes=false) {
//    		if (!is_array($value)) {
        	$value = sqlite_escape_string($value);
//        } else {
//        	debug("ww".implode($value));
//        }

        if(!$skipquotes) {
            $value = "'" . $value . "'";
        }

        return $value;
    }


    /**
     * Fetch a single row from the last results.
     *
     * @param string $getnames
     * @return array row
     *
     */
    function fetch_row($getnames="with_names") {
    
        if ( $this->num_rows() > 0 ) {

            if ($getnames != "no_names") {
								$sql_array = sqlite_fetch_array( $this->sql_result, SQLITE_ASSOC);
            } else {
								$sql_array = sqlite_fetch_array( $this->sql_result, SQLITE_NUM);
            }

            if (!is_array( $sql_array )) {
                return false;
            } else {
                return $sql_array;
            }
        } else {

            return false;

        }
    }


    /**
     * Fetch all rows from the last results.
     *
     * @param string $getnames
     * @return array rows
     *
     */
    function fetch_all_rows($getnames="with_names") {
        $results = array();
        if ( $this->num_rows() > 0 ) {
            if ($getnames!="no_names") {
//                while($row = sqlite_fetch_array($this->sql_result, SQLITE_ASSOC)) {
//                    $results[] = $row;
//                }
								$results = sqlite_fetch_all($this->sql_result, SQLITE_ASSOC);
            } else {
//                while($row = sqlite_fetch_array( $this->sql_result, SQLITE_NUM)) {
//                    $results[] = $row;
//                }
								$results = sqlite_fetch_all($this->sql_result, SQLITE_NUM);
            }
            return $results;
        } else {
            return false;
        }
    }
    
		function getTableList($filter="") {
      // If there's no DB connection yet, set one up if we can.
      if(!$this->connection()) {
      	return false;
      }
	
			if ($filter == "") {
				$this->sql_result = sqlite_query($this->sql_link, "SELECT name FROM sqlite_master WHERE type='table'"); #Lists all tables
			} else {
				$this->sql_result = sqlite_query($this->sql_link, "SELECT name FROM sqlite_master WHERE type='table' and name LIKE '".$filter."%'");
			}
	    $tables = $this->fetch_all_rows('no_names');
	    $tables = make_valuepairs($tables, '', '0');
	    
	    return $tables;
		}


		/**
		 * Create the SQL table for Entries.
		 *
		 * @param link $sql
		 */
		function makeEntriesTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
		
		    $userdata = $PIVOTX['users']->getUsers();
		    $username = $userdata[0]['username'];
		
		    $query1 = "CREATE TABLE $tablename (
		    	 uid INTEGER PRIMARY KEY,		    	 
					 title TEXT NOT NULL,
		       uri TEXT NOT NULL,
					 subtitle TEXT NOT NULL,
					 introduction TEXT NOT NULL,
					 body TEXT NOT NULL,
		       convert_lb INTEGER(11) NOT NULL DEFAULT 0,
					 status TEXT NOT NULL,
					 date DATETIME NOT NULL,
					 publish_date DATETIME NOT NULL,
					 edit_date DATETIME NOT NULL,
					 user TEXT NOT NULL,
		       allow_comments INTEGER(11) NOT NULL DEFAULT 0,
					 keywords TEXT NOT NULL,
					 via_link TEXT NOT NULL,
					 via_title TEXT NOT NULL,
					 comment_count INTEGER(11) NOT NULL,
					 comment_names TEXT NOT NULL,
					 trackback_count INTEGER(11) NOT NULL,
					 trackback_names TEXT  NOT NULL,
					 extrafields TEXT NOT NULL
		    );";

		    $query2 = "INSERT INTO $tablename (uid, title, uri, subtitle, introduction, body, convert_lb, status, date, publish_date, edit_date, user, allow_comments, keywords, via_link, via_title, comment_count, comment_names, trackback_count, trackback_names, extrafields) VALUES
				(1, '%version%', '%uri-version%', '', '<p>If you can read this, you have successfully installed [[tt tag=\"PivotX\"]]. Yay!! To help you further on your way, the following links might be of use to you:</p>
		<ul>
		<li>PivotX.net - <a href=\"http://pivotx.net\">The official PivotX website</a></li>
		<li>The online documentation at <a href=\"http://book.pivotx.net\">PivotX Help</a> should be of help.</li>
		<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>
		<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>
		<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>
		<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>
		</ul>
		<p>And, of course: Have fun with PivotX!</p>', '<h3>More</h3>
		<p>All text that you write in the ''body'' part of the entry will only appear on the entry''s own page. To see how this works, edit this entry in the PivotX administration by going to ''Entries &amp; Pages'' &raquo; ''Entries'' &raquo; ''Edit''.</p>', 0, 'publish', '%now%-00', '%now%-00', '%now%-00', '$username', 1, 'pivot pivotx', '', '', 1, 'Bob', 0, '', '');";
// Mancava la insert del campo extrafields che ha vincolo NOT NULL		
		
		    $now = date("Y-m-d-H-i", getCurrentDate());
		    $version = __("Welcome to"). " " . strip_tags($GLOBALS['build']);
		
		    $query2 = str_replace("%version%", $version, $query2);
		    $query2 = str_replace("%uri-version%", makeURI($version), $query2);
		    $query2 = str_replace("%now%", $now, $query2);
		
		    $this->query($query1);
		    $this->query($query2);
// [JAN]		    
//ToDO: Aggiungere alter table per chiavi esterne (vedi module_mysql
// [JAN]
		}


		/**
		 * Create the SQL table for Comments.
		 *
		 * @param link $sql
		 */
		function makeCommentsTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."comments", true);
		
		    $query1 = "CREATE TABLE $tablename (
		    	uid INTEGER PRIMARY KEY,
      		contenttype TEXT NOT NULL,
		      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
		      name TEXT NOT NULL,
		      email TEXT NOT NULL,
		      url TEXT NOT NULL,
		      ip TEXT NOT NULL,
		      useragent TEXT NOT NULL,
		      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      comment TEXT NOT NULL,
		      registered INTEGER(4) NOT NULL DEFAULT 0,
		      notify INTEGER(4) NOT NULL DEFAULT 0,
		      discreet INTEGER(4) NOT NULL DEFAULT 0,
		      moderate INTEGER(4) NOT NULL DEFAULT 0,
		      spamscore INTEGER(4) NOT NULL DEFAULT 0
		      );";
		
		    $query2 = "INSERT INTO $tablename VALUES(1, 'entry', 1, 'Bob', '', 'http://pivotx.net', '127.0.0.1', '', '%now%-10', 'Hi! This is what a comment looks like!', 0, 0, 0, 0, 0);";
		
		    $now = date("Y-m-d-H-i", getCurrentDate());
		    $query2 = str_replace("%now%", $now, $query2);
		
		    $this->query($query1);
		    $this->query($query2);
// Aggiungere alter table per:
//	      KEY `entry_uid` (`entry_uid`),
//	      KEY `date` (`date`)		    
		}

		/**
		 * Create the SQL table for Trackbacks.
		 *
		 * @param link $sql
		 */
		function makeTrackbacksTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."trackbacks", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid INTEGER PRIMARY KEY,
		      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
		      name TEXT NOT NULL,
		      title TEXT NOT NULL,
		      url TEXT NOT NULL,
		      ip TEXT NOT NULL,
		      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      excerpt TEXT NOT NULL,
		      moderate INTEGER(4) NOT NULL default 0,
		      spamscore INTEGER(4) NOT NULL default 0
		      );";
		
		    $this->query($query1);
		}
		
		/**
		 * Create the SQL table for Tags.
		 *
		 * @param link $sql
		 */
		function makeTagsTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."tags", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid INTEGER PRIMARY KEY,
		      tag TEXT NOT NULL,
		      contenttype TEXT NOT NULL,
		      target_uid INTEGER(11) NOT NULL DEFAULT 0
		    );";
		
		    $this->query($query1);
// Todo: alter table -> index
//    $sql->query("ALTER TABLE `$tablename` ADD INDEX ( `target_uid` ) ;");
//    $sql->query("ALTER TABLE `$tablename` ADD INDEX ( `tag`(32) ) ;");

		}
    
		/**
		 * Create the SQL table for Categories.
		 *
		 * @param link $sql
		 */
		function makeCategoriesTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."categories", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid INTEGER PRIMARY KEY,
          contenttype TEXT NOT NULL,
		      category TEXT NOT NULL,
		      target_uid INTEGER(11) NOT NULL DEFAULT '0'
		    );";

		    $this->query($query1);
		    $this->query("INSERT INTO $tablename (uid, contenttype, category, target_uid) VALUES (1, 'entry', 'default', 1);");
		    $this->query("INSERT INTO $tablename (uid, contenttype, category, target_uid) VALUES (2, 'entry', 'linkdump', 1);");
		}
		
		/**
		 * Create the SQL table for Pages.
		 *
		 * @param link $sql
		 */
		function makePagesTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
		
		    $userdata = $PIVOTX['users']->getUsers();
		    $username = $userdata[0]['username'];
		
		    $query1 = "CREATE TABLE $tablename (
		      uid INTEGER PRIMARY KEY,
		      title TEXT NOT NULL,
		      uri TEXT NOT NULL,
		      subtitle TEXT NOT NULL,
		      introduction TEXT NOT NULL,
		      body TEXT NOT NULL,
		      convert_lb INTEGER(11) NOT NULL DEFAULT 0,
		      template TEXT NOT NULL,
		      status TEXT NOT NULL,
		      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      publish_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      edit_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      chapter INTEGER(11) NOT NULL DEFAULT 0,
		      sortorder INTEGER(11) NOT NULL DEFAULT 0,
		      user TEXT NOT NULL,
		      allow_comments INTEGER(11) NOT NULL DEFAULT 0,
		      keywords TEXT NOT NULL,
      		extrafields TEXT NOT NULL
		    );";
		
		    $query2 = "INSERT INTO $tablename (uid, title, uri, subtitle, introduction, body, convert_lb, template, status, date, publish_date, edit_date, chapter, sortorder, user, allow_comments, keywords, extrafields) VALUES
				(1, '%title%', 'about', '', '<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>, the coolest free and open tool to power your blog and website. To change this text, edit ''<tt>About PivotX</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</p>', '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice     weblogger to use and complex enough to meet the demands of advanced webmasters.     It can be used to publish a variety of websites from the most basic weblog to very advanced CMS style solutions.</p>\r\n<p>PivotX is - if we do say so ourselves - quite an impressive piece of software. It     is made even better through the use of several external libraries. We thank their     authors for the time taken to develop these very useful tools and for making     them available to others.</p>\r\n<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously     forged ahead thanks to the efforts of a lot     of dedicated and very talented people. The PivotX core team is still very active     but keep in mind that PivotX would not be what it is today without the valuable     contributions made by several other people.</p>', 5, '', 'publish', '%now%-00', '%now%-00', '%now%-00', 1, 10, '$username', 1, '', ''); ";
//Mancava il campo NOT NULL extrafields in entrambi i casi
		
		    $query3 = "INSERT INTO $tablename (uid, title, uri, subtitle, introduction, body, convert_lb, template, status, date, publish_date, edit_date, chapter, sortorder, user, allow_comments, keywords, extrafields) VALUES
				(2,  '%title%', 'links', '', '<p>Some links to sites with more information:</p>\r\n<ul>\r\n<li>PivotX - <a href=\"http://pivotx.net\">The PivotX website</a></li>\r\n<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>\r\n<li>Read <a href=\"http://book.pivotx.net\">the PivotX documentation</a></li>\r\n<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>\r\n<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>\r\n<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>\r\n</ul>\r\n<p><small>To change these links, edit ''<tt>Links</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</small></p>', '', 5, '', 'publish', '%now%-01', '%now%-01', '%now%-01', 1, 10, '$username', 1, '', '');";
//Mancava il campo NOT NULL extrafields in entrambi i casi
		
		    $now = date("Y-m-d-H-i", getCurrentDate());
		
		    $query2 = str_replace("%now%", $now, $query2);
		    $query2 = str_replace("%title%", __('About PivotX'), $query2);
		    $query3 = str_replace("%now%", $now, $query3);
		    $query3 = str_replace("%title%", __('Links'), $query3);
		
		    $this->query($query1);
		    $this->query($query2);
		    $this->query($query3);
// Aggiungere alter table per
// 		      FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)	    
		}
		
		
		/**
		 * Create the SQL table for Chapters.
		 *
		 * @param link $sql
		 */
		function makeChaptersTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."chapters", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid INTEGER PRIMARY KEY,
		      chaptername TEXT NOT NULL,
		      description TEXT NOT NULL,
		      sortorder INTEGER(11) NOT NULL DEFAULT 0
		    );";

		    $query2 = "INSERT INTO $tablename (uid, chaptername, description, sortorder) VALUES
			(1, '%name%', '%desc%', 10);";
		
		    $query2 = str_replace("%name%", __('Pages'), $query2);
		    $query2 = str_replace("%desc%", __('Add some pages here, or start a new chapter.'), $query2);

		    $this->query($query1);
		    $this->query($query2);
		}
		
		
		/**
		 * Create the SQL table for the Extra fields in Entries and Pages.
		 *
		 * @param link $sql
		 */
		function makeExtrafieldsTable() {
		    global $PIVOTX;
		
		    $tablename = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

// IF [NOT] EXISTS syntax was introduced with 3.3.0 alpha.
//		    $query1 = "CREATE TABLE IF NOT EXISTS $tablename (
		    $query1 = "CREATE TABLE $tablename (
		        uid INTEGER PRIMARY KEY,
		        contenttype TEXT NOT NULL,
		        target_uid INTEGER(11) NOT NULL DEFAULT 0,
		        fieldkey TEXT NOT NULL,
		        value TEXT NOT NULL
		    );";
		
		    $this->query($query1);
		}

		/*
		*
		* @override
		*/
    function build_select($q) {
	// COUNT(DISTINCT X) is not handled by sqlite
	if (preg_match("/COUNT\ *\(?\ *DISTINCT(\ *\(([^)]*)\)|\ ([^)]*))/i", $q['select'], $matches) != 0) {
//		debug("here I AM ".$q['select']." - ".$matches);
		$q1['select'] = preg_replace("/COUNT\ *\(?\ *DISTINCT(\ *\(([^)]*)\)|\ ([^)]*))\ *\)/i", "COUNT(*) ", $q['select']);

		$q['select'] = "DISTINCT ".$matches[1];
		$explodedQuery = $this->build_select($q);
		// Remove trailing ;
		$explodedQuery = substr($explodedQuery, 0, -1);

		$q1['from'] = "( $explodedQuery )";
//		debug($q1);
		$q = $q1;
//		debug($q);		
	}

    	$parentSelect = parent::build_select($q);
    	
    	// SUBSTRING is not allowed in sqlite... use SUBSTR instead
    	$query = str_replace("SUBSTRING(", "SUBSTR(", $parentSelect);
    	

        $this->cached_query = $query;

    	return $query;
    }

		/*
		*
		* @override
		*
		* If SQLite is not compiled with the SQLITE_ENABLE_UPDATE_DELETE_LIMIT compile-time option, 
		* then the syntax of the DELETE statement cannot be extended by the addition of optional ORDER BY and LIMIT clauses:
		*/
    function build_delete($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "DELETE FROM ". $q['delete'];

        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }

/*
        // eventueel een limit
        if (isset($q['limit'])) {
            $output .= "\nLIMIT  ". $q['limit'];
        }
*/        

        $output .=";";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }


		/**
		 * Check if the current version of the DB is updated to the latest version,
		 * and update it if it isn't..
		 *
		 */
		function checkDBVersion() {
		    global $PIVOTX, $dbversion;
		    if ($PIVOTX['config']->get('db_version') >= $dbversion) {
		        return;
		    }

		    $db_version = $PIVOTX['config']->get('db_version');
		
		    $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
		    $categoriestable = safeString($PIVOTX['config']->get('db_prefix')."categories", true);
		    $commentstable = safeString($PIVOTX['config']->get('db_prefix')."comments", true);
		    $trackbackstable = safeString($PIVOTX['config']->get('db_prefix')."trackbacks", true);
		    $pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
		    $extratable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);
		    $tagstable = safeString($PIVOTX['config']->get('db_prefix')."tags", true);
		
		    // DB changes from PivotX 2.0 alpha 2 to alpha 3.
		    if (intval($db_version) < 1) {
		        debug("now updating DB to version 1..");
		
		        // We need to set the URI's for all entries in the DB.
		        $this->query("SELECT uid,title FROM $entriestable");
		
		        while ($entry = $this->fetch_row()) {
		            $uri = makeURI($entry['title']);
		            $this->query("UPDATE $entriestable SET uri=". $this->quote($uri) . " WHERE uid= ". $entry['uid']);
		        }
		
		        // Add fultext search for entries and pages..
		        // $this->query("ALTER TABLE $entriestable ADD FULLTEXT(title, subtitle, introduction, body);");
		        // $this->query("ALTER TABLE $pagestable ADD FULLTEXT(title, subtitle, introduction, body);");
		
		        debug("Updated DB to version 1");
		        $PIVOTX['config']->set('db_version', 1);
		    }
		
		
		    // DB changes introduced between Alpha 4 and Beta 1.
		    if (intval($db_version) < 3) {
		        debug("now updating DB to version 3..");

		        // Add extrafields field for entries and pages..
		        $this->query("CREATE TABLE $extratable (
		            uid INTEGER PRIMARY KEY,
		            contenttype TEXT NOT NULL,
		            target_uid INTEGER(11) NOT NULL DEFAULT 0,
		            fieldkey TEXT NOT NULL,
		            value TEXT NOT NULL
		          );");
		
		        debug("Updated DB to version 3");
		        $PIVOTX['config']->set('db_version', 3);
		    }
		
		
		    // DB changes from PivotX 2.0 beta 1 to beta 2.
		    if (intval($db_version) < 4) {
		        debug("now updating DB to version 4..");
		
		        // Add fultext search for entries and pages..
		        //$this->query("ALTER TABLE $entriestable DROP INDEX title;");
		        //$this->query("ALTER TABLE $pagestable DROP INDEX title;");
		        //$this->query("ALTER TABLE $entriestable ADD FULLTEXT(title, subtitle, introduction, body, keywords);");
		        //$this->query("ALTER TABLE $pagestable ADD FULLTEXT(title, subtitle, introduction, body, keywords);");
		
		        debug("Updated DB to version 4");
		        $PIVOTX['config']->set('db_version', 4);
		    }
		
		    // DB changes for PivotX 2.0 RC 1d and up.
		    if (intval($db_version) < 5) {
		        
		        // Add indices to speed up JOINs..
		        //$this->query("ALTER TABLE $categoriestable ADD KEY `target_uid` (`target_uid`);");
		        //$this->query("ALTER TABLE $commentstable ADD KEY `entry_uid` (`entry_uid`);");
		        //$this->query("ALTER TABLE $commentstable ADD KEY `date` (`date`);");
		        
		        debug("Updated DB to version 5");
		        $PIVOTX['config']->set('db_version', 5);
		    }
		
		    // DB changes for PivotX 2.1 and up.
		    if (intval($db_version) < 6) {
		        
		        // Add column to store useragent for comments..
//		        $this->query("ALTER TABLE $commentstable  ADD useragent TEXT NOT NULL AFTER ip;");
							$this->query("BEGIN TRANSACTION;");
					    $this->query("CREATE TEMPORARY TABLE t1_backup (
					    	uid INTEGER PRIMARY KEY,
					      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
					      name TEXT NOT NULL,
					      email TEXT NOT NULL,
					      url TEXT NOT NULL,
					      ip TEXT NOT NULL,
					      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					      comment TEXT NOT NULL,
					      registered INTEGER(4) NOT NULL DEFAULT 0,
					      notify INTEGER(4) NOT NULL DEFAULT 0,
					      discreet INTEGER(4) NOT NULL DEFAULT 0,
					      moderate INTEGER(4) NOT NULL DEFAULT 0,
					      spamscore INTEGER(4) NOT NULL DEFAULT 0
					    );");
							$this->query("INSERT INTO t1_backup SELECT uid,entry_uid,name,email,url,ip,date,comment,registered,notify,discreet,moderate,spamscore FROM $commentstable;");
							$this->query("DROP TABLE $commentstable;");
					    $this->query("CREATE TABLE $commentstable (
					    	uid INTEGER PRIMARY KEY,
					      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
					      name TEXT NOT NULL,
					      email TEXT NOT NULL,
					      url TEXT NOT NULL,
					      ip TEXT NOT NULL,
					      useragent TEXT NOT NULL,
					      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					      comment TEXT NOT NULL,
					      registered INTEGER(4) NOT NULL DEFAULT 0,
					      notify INTEGER(4) NOT NULL DEFAULT 0,
					      discreet INTEGER(4) NOT NULL DEFAULT 0,
					      moderate INTEGER(4) NOT NULL DEFAULT 0,
					      spamscore INTEGER(4) NOT NULL DEFAULT 0
					    );");
							$this->query("INSERT INTO $commentstable SELECT uid,entry_uid,name,email,url,ip,'',date,comment,registered,notify,discreet,moderate,spamscore FROM t1_backup;");
							$this->query("DROP TABLE t1_backup;");
							$this->query("COMMIT;");
		
		        debug("Updated DB to version 6");
		        $PIVOTX['config']->set('db_version', 6);
		    }
		
		    if (intval($db_version) < 7) {
		        
		        // Add column to store moderate for trackbacks..
		        //$this->query("ALTER TABLE `$trackbackstable` ADD `moderate` TINYINT NOT NULL AFTER `excerpt` ;");
							$this->query("BEGIN TRANSACTION;");
					    $this->query("CREATE TEMPORARY TABLE t1_backup (
					      uid INTEGER PRIMARY KEY,
					      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
					      name TEXT NOT NULL,
					      title TEXT NOT NULL,
					      url TEXT NOT NULL,
					      ip TEXT NOT NULL,
					      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					      excerpt TEXT NOT NULL,
					      spamscore INTEGER(4) NOT NULL default 0
					    	);");
							$this->query("INSERT INTO t1_backup SELECT uid,entry_uid,name,title,url,ip,date,excerpt,spamscore FROM $trackbackstable;");
							$this->query("DROP TABLE $trackbackstable;");
					    $this->query("CREATE TABLE $trackbackstable (
					      uid INTEGER PRIMARY KEY,
					      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
					      name TEXT NOT NULL,
					      title TEXT NOT NULL,
					      url TEXT NOT NULL,
					      ip TEXT NOT NULL,
					      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					      excerpt TEXT NOT NULL,
					      moderate INTEGER(4) NOT NULL default 0,
					      spamscore INTEGER(4) NOT NULL default 0
					    );");
							$this->query("INSERT INTO $trackbackstable SELECT uid,entry_uid,name,title,url,ip,date,excerpt,0,spamscore FROM t1_backup;");
							$this->query("DROP TABLE t1_backup;");
							$this->query("COMMIT;");
		
		        debug("Updated DB to version 7");
		        $PIVOTX['config']->set('db_version', 7);
		    }
		
		    if (intval($db_version) < 8) {
		        
		        // Add Indices to tags table...
		        //$this->query("ALTER TABLE `$tagstable` ADD INDEX ( `target_uid` ) ;");
		        //$this->query("ALTER TABLE `$tagstable` ADD INDEX ( `tag`(32) ) ;");
		
		        debug("Updated DB to version 8");
		        $PIVOTX['config']->set('db_version', 8);
		    }
		
		    if (intval($db_version) < 9) {
		        
		        // Add Indices to extrafields table...
		        //$this->query("ALTER TABLE `$extratable` ADD INDEX ( `target_uid` ) ;");
		        //$this->query("ALTER TABLE `$extratable` ADD INDEX ( `fieldkey`(16) ) ;");
		
		        // Bob is a moran. Why in the name of sweet jeebus would someone ever define a column name like comment_COUNT as a tinytext. Sheeesh...
//		        $this->query("ALTER TABLE  `$entriestable` CHANGE `comment_count` `comment_count` INT NOT NULL;");
//		        $this->query("ALTER TABLE  `$entriestable` CHANGE `trackback_count` `trackback_count` INT NOT NULL;");
							$this->query("BEGIN TRANSACTION;");
					    $this->query("CREATE TEMPORARY TABLE t1_backup (
					    	 uid INTEGER PRIMARY KEY,		    	 
								 title TEXT NOT NULL,
					       uri TEXT NOT NULL,
								 subtitle TEXT NOT NULL,
								 introduction TEXT NOT NULL,
								 body TEXT NOT NULL,
					       convert_lb INTEGER(11) NOT NULL DEFAULT 0,
								 status TEXT NOT NULL,
								 date DATETIME NOT NULL,
								 publish_date DATETIME NOT NULL,
								 edit_date DATETIME NOT NULL,
								 user TEXT NOT NULL,
					       allow_comments INTEGER(11) NOT NULL DEFAULT 0,
								 keywords TEXT NOT NULL,
								 via_link TEXT NOT NULL,
								 via_title TEXT NOT NULL,
								 comment_count INTEGER(11) NOT NULL,
								 comment_names TEXT NOT NULL,
								 trackback_count INTEGER(11) NOT NULL,
								 trackback_names TEXT  NOT NULL,
								 extrafields TEXT NOT NULL
					    	);");
							$this->query("INSERT INTO t1_backup SELECT uid,title,uri,subtitle,introduction,body,convert_lb,status,date,publish_date,edit_date,user,allow_comments,keywords,via_link,via_title,comment_count,comment_names,trackback_count,trackback_names,extrafields FROM $entriestable;");
							$this->query("DROP TABLE $entriestable;");
					    $this->query("CREATE TABLE $entriestable (
					    	 uid INTEGER PRIMARY KEY,		    	 
								 title TEXT NOT NULL,
					       uri TEXT NOT NULL,
								 subtitle TEXT NOT NULL,
								 introduction TEXT NOT NULL,
								 body TEXT NOT NULL,
					       convert_lb INTEGER(11) NOT NULL DEFAULT 0,
								 status TEXT NOT NULL,
								 date DATETIME NOT NULL,
								 publish_date DATETIME NOT NULL,
								 edit_date DATETIME NOT NULL,
								 user TEXT NOT NULL,
					       allow_comments INTEGER(11) NOT NULL DEFAULT 0,
								 keywords TEXT NOT NULL,
								 via_link TEXT NOT NULL,
								 via_title TEXT NOT NULL,
								 comment_count INTEGER(11) NOT NULL,
								 comment_names TEXT NOT NULL,
								 trackback_count INTEGER(11) NOT NULL,
								 trackback_names TEXT  NOT NULL,
								 extrafields TEXT NOT NULL
					    );");
							$this->query("INSERT INTO $entriestable SELECT uid,title,uri,subtitle,introduction,body,convert_lb,status,date,publish_date,edit_date,user,allow_comments,keywords,via_link,via_title,comment_count,comment_names,trackback_count,trackback_names,extrafields FROM t1_backup;");
							$this->query("DROP TABLE t1_backup;");
							$this->query("COMMIT;");
		
		        debug("Updated DB to version 9");
		        $PIVOTX['config']->set('db_version', 9);
		    }
		
		    if (intval($db_version) < 10) {
		        
		        // Add column to category for entrytypes..
//		        $this->query("ALTER TABLE `$categoriestable` ADD `contenttype` TINYTEXT NOT NULL AFTER `uid` ;");
//		        $this->query("UPDATE `$categoriestable` SET `contenttype` = 'entry' WHERE 1;");
						$this->query("BEGIN TRANSACTION;");
				    $this->query("CREATE TEMPORARY TABLE t1_backup (
				      uid INTEGER PRIMARY KEY,
				      category TEXT NOT NULL,
				      target_uid INTEGER(11) NOT NULL DEFAULT '0'
				    	);");
						$this->query("INSERT INTO t1_backup SELECT uid,category,target_uid FROM $categoriestable;");
						$this->query("DROP TABLE $categoriestable;");
				    $this->query("CREATE TABLE $categoriestable (
				      uid INTEGER PRIMARY KEY,
		          contenttype TEXT NOT NULL,
				      category TEXT NOT NULL,
				      target_uid INTEGER(11) NOT NULL DEFAULT '0'
				    );");
						$this->query("INSERT INTO $categoriestable SELECT uid, 'entry', category, target_uid FROM t1_backup;");
						$this->query("DROP TABLE t1_backup;");
						$this->query("COMMIT;");

		        // Add column to comments for entrytypes..
//		        $this->query("ALTER TABLE `$commentstable` ADD `contenttype` TINYTEXT NOT NULL AFTER `uid` ;");
//		        $this->query("UPDATE `$commentstable` SET `contenttype` = 'entry' WHERE 1;");
						$this->query("BEGIN TRANSACTION;");
				    $this->query("CREATE TEMPORARY TABLE t1_backup (
				    	uid INTEGER PRIMARY KEY,
				      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
				      name TEXT NOT NULL,
				      email TEXT NOT NULL,
				      url TEXT NOT NULL,
				      ip TEXT NOT NULL,
				      useragent TEXT NOT NULL,
				      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				      comment TEXT NOT NULL,
				      registered INTEGER(4) NOT NULL DEFAULT 0,
				      notify INTEGER(4) NOT NULL DEFAULT 0,
				      discreet INTEGER(4) NOT NULL DEFAULT 0,
				      moderate INTEGER(4) NOT NULL DEFAULT 0,
				      spamscore INTEGER(4) NOT NULL DEFAULT 0
				    	);");
						$this->query("INSERT INTO t1_backup SELECT uid,entry_uid,name,email,url,ip,useragent,date,comment,registered,notify,discreet,moderate,spamscore FROM $commentstable;");
						$this->query("DROP TABLE $commentstable;");
				    $this->query("CREATE TABLE $commentstable (
				    	uid INTEGER PRIMARY KEY,
		      		contenttype TEXT NOT NULL,
				      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
				      name TEXT NOT NULL,
				      email TEXT NOT NULL,
				      url TEXT NOT NULL,
				      ip TEXT NOT NULL,
				      useragent TEXT NOT NULL,
				      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				      comment TEXT NOT NULL,
				      registered INTEGER(4) NOT NULL DEFAULT 0,
				      notify INTEGER(4) NOT NULL DEFAULT 0,
				      discreet INTEGER(4) NOT NULL DEFAULT 0,
				      moderate INTEGER(4) NOT NULL DEFAULT 0,
				      spamscore INTEGER(4) NOT NULL DEFAULT 0
				    );");
						$this->query("INSERT INTO $commentstable SELECT uid,'entry',entry_uid,name,email,url,ip,useragent,date,comment,registered,notify,discreet,moderate,spamscore FROM t1_backup;");
						$this->query("DROP TABLE t1_backup;");
						$this->query("COMMIT;");

		        debug("Updated DB to version 10");
		        $PIVOTX['config']->set('db_version', 10);
		    }
		
		    if (intval($db_version) < 11) {
		
		        // Add indexes to extrafields..
		        // This is a huge performance improvement when you query a lot of extrafields
		        //$this->query("ALTER TABLE `$extratable` ADD INDEX (  `target_uid` );");
		        // Most fields differ so we want a fulltext here
		        //$this->query("ALTER TABLE `$extratable` ADD FULLTEXT (`value`);");
		
		        debug("Updated DB to version 11");
		        $PIVOTX['config']->set('db_version', 11);
		    }
		
		}

}

?>
