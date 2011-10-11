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

 * @version 1.0_JPP
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
        global $return_silent;

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
        
						if ($this->sql_link = sqlite_open("db/".$this->dbase.".sqlite", 0666, $sqlite_error)) { 
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
    		return sqlite_changes();
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
        $value = sqlite_escape_string($value);

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
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
		
		    $userdata = $PIVOTX['users']->getUsers();
		    $username = $userdata[0]['username'];
		
		    $query1 = "CREATE TABLE $tablename (
		    	 uid AUTOINCREMENT INTEGER PRIMARY KEY,
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
					 comment_count TEXT NOT NULL,
					 comment_names TEXT NOT NULL,
					 trackback_count TEXT NOT NULL,
					 trackback_names TEXT  NOT NULL,
					 extrafields TEXT NOT NULL
		    );";
//Questo me lo sono perso per ora:  FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)
// COLLATE utf8_unicode_ci

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
		<p>All text that you write in the ''body'' part of the entry will only appear on the entry''s own page. To see how this works, edit this entry in the PivotX administration by going to ''Entries &amp; Pages'' &raquo; ''Entries'' &raquo; ''Edit''.</p>', 0, 'publish', '%now%-00', '%now%-00', '%now%-00', '$username', 1, 'pivot pivotx', '', '', '1', 'Bob', '0', '', '');";
// Mancava la insert del campo extrafields che ha vincolo NOT NULL		
		
		    $now = date("Y-m-d-H-i", get_current_date());
		    $version = __("Welcome to"). " " . strip_tags($GLOBALS['build']);
		
		    $query2 = str_replace("%version%", $version, $query2);
		    $query2 = str_replace("%uri-version%", makeURI($version), $query2);
		    $query2 = str_replace("%now%", $now, $query2);
		
		    $this->query($query1);
		    $this->query($query2);		
		}


		/**
		 * Create the SQL table for Comments.
		 *
		 * @param link $sql
		 */
		function makeCommentsTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."comments", true);
		
		    $query1 = "CREATE TABLE $tablename (
		    	uid AUTOINCREMENT INTEGER PRIMARY KEY,
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
		      );";
		
		    $query2 = "INSERT INTO $tablename VALUES(1, 1, 'Bob', '', 'http://pivotx.net', '127.0.0.1', '%now%-10', 'Hi! This is what a comment looks like!', 0, 0, 0, 0, 0);";
		
		    $now = date("Y-m-d-H-i", get_current_date());
		    $query2 = str_replace("%now%", $now, $query2);
		
		    $this->query($query1);
		    $this->query($query2);
		}

		/**
		 * Create the SQL table for Trackbacks.
		 *
		 * @param link $sql
		 */
		function makeTrackbacksTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."trackbacks", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid AUTOINCREMENT INTEGER PRIMARY KEY,
		      entry_uid INTEGER(11) NOT NULL DEFAULT 0,
		      name TEXT NOT NULL,
		      title TEXT NOT NULL,
		      url TEXT NOT NULL,
		      ip TEXT NOT NULL,
		      date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		      excerpt TEXT NOT NULL,
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
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid AUTOINCREMENT INTEGER PRIMARY KEY,
		      tag TEXT NOT NULL,
		      contenttype TEXT NOT NULL,
		      target_uid INTEGER(11) NOT NULL DEFAULT 0
		    );";
		
		    $this->query($query1);
		}
    
		/**
		 * Create the SQL table for Categories.
		 *
		 * @param link $sql
		 */
		function makeCategoriesTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."categories", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid AUTOINCREMENT INTEGER PRIMARY KEY,
		      category TEXT NOT NULL,
		      target_uid INTEGER(11) NOT NULL DEFAULT '0'
		    );";
		
		    $this->query($query1);
		    $this->query("INSERT INTO $tablename (uid, category, target_uid) VALUES (1, 'default', 1);");
		    $this->query("INSERT INTO $tablename (uid, category, target_uid) VALUES (2, 'linkdump', 2);");
		}
		
		/**
		 * Create the SQL table for Pages.
		 *
		 * @param link $sql
		 */
		function makePagesTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."pages", true);
		
		    $userdata = $PIVOTX['users']->getUsers();
		    $username = $userdata[0]['username'];
		
		    $query1 = "CREATE TABLE $tablename (
		      uid AUTOINCREMENT INTEGER PRIMARY KEY,
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
				(1, 'About PivotX', 'about', '', '<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>, the coolest free and open tool to power your blog and website. To change this text, edit ''<tt>About PivotX</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</p>', '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice     weblogger to use and complex enough to meet the demands of advanced webmasters.     It can be used to publish a variety of websites from the most basic weblog to very advanced CMS style solutions.</p>\r\n<p>PivotX is - if we do say so ourselves - quite an impressive piece of software. It     is made even better through the use of several external libraries. We thank their     authors for the time taken to develop these very useful tools and for making     them available to others.</p>\r\n<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously     forged ahead thanks to the efforts of a lot     of dedicated and very talented people. The PivotX core team is still very active     but keep in mind that PivotX would not be what it is today without the valuable     contributions made by several other people.</p>', 5, 'skinny/page_template.html', 'publish', '%now%-00', '%now%-00', '%now%-00', 1, 10, '$username', 1, '', ''); ";
		
		    $query3 = "INSERT INTO $tablename (uid, title, uri, subtitle, introduction, body, convert_lb, template, status, date, publish_date, edit_date, chapter, sortorder, user, allow_comments, keywords, extrafields) VALUES
				(2, 'Links', 'links', '', '<p>Some links to sites with more information:</p>\r\n<ul>\r\n<li>PivotX - <a href=\"http://pivotx.net\">The PivotX website</a></li>\r\n<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>\r\n<li>Read <a href=\"http://book.pivotx.net\">the PivotX documentation</a></li>\r\n<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>\r\n<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>\r\n<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>\r\n</ul>\r\n<p><small>To change these links, edit ''<tt>Links</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</small></p>', '', 5, 'skinny/page_template.html', 'publish', '%now%-01', '%now%-01', '%now%-01', 1, 10, '$username', 1, '', '');";
		
		    $now = date("Y-m-d-H-i", get_current_date());
		
		    $query2 = str_replace("%now%", $now, $query2);
		    $query3 = str_replace("%now%", $now, $query3);
		
		    $this->query($query1);
		    $this->query($query2);
		    $this->query($query3);
		}
		
		
		/**
		 * Create the SQL table for Chapters.
		 *
		 * @param link $sql
		 */
		function makeChaptersTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."chapters", true);
		
		    $query1 = "CREATE TABLE $tablename (
		      uid AUTOINCREMENT INTEGER PRIMARY KEY,
		      chaptername TEXT NOT NULL,
		      description TEXT NOT NULL,
		      sortorder INTEGER(11) NOT NULL DEFAULT 0
		    );";
		
		    $query2 = "INSERT INTO $tablename (uid, chaptername, description, sortorder) VALUES
		        														  (1, 'Pages', 'Add some pages here, or start a new chapter.', 10);";
		
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
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."extrafields", true);

// IF [NOT] EXISTS syntax was introduced with 3.3.0 alpha.
//		    $query1 = "CREATE TABLE IF NOT EXISTS $tablename (
		    $query1 = "CREATE TABLE $tablename (
		        uid AUTOINCREMENT INTEGER PRIMARY KEY,
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
//		print_r($matches);
		$q1['select'] = preg_replace("/COUNT\ *\(?\ *DISTINCT(\ *\(([^)]*)\)|\ ([^)]*))\ *\)/i", "COUNT(*) ", $q['select']);

		$q['select'] = "DISTINCT ".$matches[1];
		$explodedQuery = $this->build_select($q);

		$q1['from'] = "( $explodedQuery )";
//		print_r($q1);
		$q = $q1;
//		print_r($q);		
	}

    	$parentSelect = parent::build_select($q);
    	
    	// SUBSTRING is not allowed in sqlite... use SUBSTR instead
    	$query = str_replace("SUBSTRING(", "SUBSTR(", $parentSelect);
    	

        $this->cached_query = $query;

    	return $query;
    }
		
}


?>
