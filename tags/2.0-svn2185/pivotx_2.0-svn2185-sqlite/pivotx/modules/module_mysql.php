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
 * @author Bob den Otter, bob@twokings.nl
 * @author Jean Paul Piccato, modded version
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 */
class mysql extends abstractSql {

		function mysql($dbase="", $host="", $user="", $pass="") {
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
        * Set up a link for MySQL model
        */

        // Set up the link, if not already done so.
        if ($this->sql_link == 0) {

            // See if we can connect to the Mysql Database Engine.
            if ($this->sql_link = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpass)) {
               // Yes, so now see if we can select the database.

               if (!mysql_select_db($this->dbase)) {

                  // We couldn't connect to the database. Print an error.
                  $this->error( "Can't select Database '<tt>". $this->dbase ."</tt>'" , '', mysql_errno($this->sql_link) );

                  // If silent_after_failed_connect is set, from now on return without errors/warnings
                  if($this->silent_after_failed_connect) {
                      $return_silent = true;
                  }


                  return false;

               }
               
                // Set the DB to always use UTF-8, if we're on MySQL 4.1 or higher..
                $result = mysql_query("SELECT VERSION() as version;");
                $row = mysql_fetch_assoc($result);

                if (check_version($row['version'], "4.1.0")) {
                    mysql_query("SET CHARACTER SET 'utf8'", $this->sql_link);
                }

            } else {
                // No, couldn't. So we print an error
                $this->error( "Can't connect to MySQL Database Engine", '', '' );

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
     * Close Mysql link
     */
    function close() {
        mysql_close( $this->sql_link );
    }


    /**
     * Gets the current MySQL version
     *
     * @return string
     */
    function get_server_info() {
        $version = mysql_get_server_info();
        list($version) = split("_", $version);

        return $version;
    }

    /**
     */
    function get_internal_error()  {
    	  return mysql_error();
    }
    
    function sql_affected_rows() {
    		return mysql_affected_rows();
    }
    
	function sql_doquery ($query, $link_identifier) {
			return mysql_query($query, $link_identifier);
	}
	
	function sql_errno ($link_identifier){
			return mysql_errno($link_identifier);
	}


    /**
     * Get the last inserted id
     *
     * @param  none
     */
    function get_last_id() {
        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $this->query("SELECT LAST_INSERT_ID() AS id");

        $row = $this->fetch_row();

        return $row['id'];
    }

    /**
     * Gets the number of selected rows
     */
    function num_rows()  {
        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $mysql_rows = mysql_num_rows( $this->sql_result );

        return $mysql_rows;
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

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        // Stripslashes
        //if (get_magic_quotes_gpc()) {
        //    $value = stripslashes($value);
        //}

        //check if this function exists
        if( function_exists( "mysql_real_escape_string" ) ) {
            $value = mysql_real_escape_string( $value );
        }  else   {
            //for PHP version < 4.3.0 use addslashes
            $value = addslashes( $value );
        }

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
                $mysql_array = mysql_fetch_assoc( $this->sql_result );
            } else {
                $mysql_array = mysql_fetch_row( $this->sql_result );
            }

            if (!is_array( $mysql_array )) {
                return false;
            } else {
                return $mysql_array;
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
                while($row = mysql_fetch_assoc( $this->sql_result )) {
                    $results[] = $row;
                }
            } else {
                while($row = mysql_fetch_row( $this->sql_result )) {
                    $results[] = $row;
                }
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
		    $this->sql_result = mysql_query("SHOW TABLES");
			} else {
	    	$this->sql_result = mysql_query("SHOW TABLES LIKE '".$filter."%'");
			}
	    $tables = $this->fetch_all_rows('no_names');
	    $tables = make_valuepairs($tables, '', '0');
	    
	    return $tables;
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `title` tinytext collate utf8_unicode_ci NOT NULL,
		      `uri` tinytext collate utf8_unicode_ci NOT NULL,
		      `subtitle` tinytext collate utf8_unicode_ci NOT NULL,
		      `introduction` mediumtext collate utf8_unicode_ci NOT NULL,
		      `body` mediumtext collate utf8_unicode_ci NOT NULL,
		      `convert_lb` int(11) NOT NULL default '0',
		      `template` tinytext collate utf8_unicode_ci NOT NULL,
		      `status` tinytext collate utf8_unicode_ci NOT NULL,
		      `date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `chapter` int(11) NOT NULL default '0',
		      `sortorder` int(11) NOT NULL default '0',
		      `user` tinytext collate utf8_unicode_ci NOT NULL,
		      `allow_comments` int(11) NOT NULL default '0',
		      `keywords` tinytext collate utf8_unicode_ci NOT NULL,
		      `extrafields` text collate utf8_unicode_ci NOT NULL,
		      PRIMARY KEY  (`uid`),
		      FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)
		    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		    ";
		
		    /**
		     * 'utf8_unicode' (or any charset for that matter) in this way is only
		     * supported in MYSQL 4.1 and higher.
		     * If we're on MySQL 4.0.x, we'll need to do a more generic statement,
		     * which works, but we can't guarantee the proper storage of the more
		     * exotic Characters.
		     *
		     * Perhaps we need to  upgrade users who are on 4.0 now later on.
		     * see http://cvs.drupal.org/viewcvs/drupal/drupal/update.php?rev=1.211&view=markup
		     * for some relevant information.
		     */
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		
		
		    $query2 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
		(1, 'About PivotX', 'about', '', '<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>, the coolest free and open tool to power your blog and website. To change this text, edit ''<tt>About PivotX</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</p>', '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice     weblogger to use and complex enough to meet the demands of advanced webmasters.     It can be used to publish a variety of websites from the most basic weblog to very advanced CMS style solutions.</p>\r\n<p>PivotX is - if we do say so ourselves - quite an impressive piece of software. It     is made even better through the use of several external libraries. We thank their     authors for the time taken to develop these very useful tools and for making     them available to others.</p>\r\n<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously     forged ahead thanks to the efforts of a lot     of dedicated and very talented people. The PivotX core team is still very active     but keep in mind that PivotX would not be what it is today without the valuable     contributions made by several other people.</p>', 5, 'skinny/page_template.html', 'publish', '%now%-00', '%now%-00', '%now%-00', 1, 10, '$username', 1, ''); ";
		
		    $query3 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
		(2, 'Links', 'links', '', '<p>Some links to sites with more information:</p>\r\n<ul>\r\n<li>PivotX - <a href=\"http://pivotx.net\">The PivotX website</a></li>\r\n<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>\r\n<li>Read <a href=\"http://book.pivotx.net\">the PivotX documentation</a></li>\r\n<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>\r\n<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>\r\n<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>\r\n</ul>\r\n<p><small>To change these links, edit ''<tt>Links</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</small></p>', '', 5, 'skinny/page_template.html', 'publish', '%now%-01', '%now%-01', '%now%-01', 1, 10, '$username', 1, '');";
		
		
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `chaptername` tinytext collate utf8_unicode_ci NOT NULL,
		      `description` tinytext collate utf8_unicode_ci NOT NULL,
		      `sortorder` int(11) NOT NULL default '0',
		      PRIMARY KEY  (`uid`)
		    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		    /**
		     * 'utf8_unicode' (or any charset for that matter) in this way is only
		     * supported in MYSQL 4.1 and higher.
		     * If we're on MySQL 4.0.x, we'll need to do a more generic statement,
		     * which works, but we can't guarantee the proper storage of the more
		     * exotic Characters.
		     *
		     * Perhaps we need to  upgrade users who are on 4.0 now later on.
		     * see http://cvs.drupal.org/viewcvs/drupal/drupal/update.php?rev=1.211&view=markup
		     * for some relevant information.
		     */
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		
		    $query2 = "INSERT INTO `$tablename` (`uid`, `chaptername`, `description`, `sortorder`) VALUES
		        (1, 'Pages', 'Add some pages here, or start a new chapter.', 10);
		    ";
		
		    $this->query($query1);
		    $this->query($query2);
		
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `title` tinytext collate utf8_unicode_ci NOT NULL,
		      `uri` tinytext collate utf8_unicode_ci NOT NULL,
		      `subtitle` tinytext collate utf8_unicode_ci NOT NULL,
		      `introduction` mediumtext collate utf8_unicode_ci NOT NULL,
		      `body` mediumtext collate utf8_unicode_ci NOT NULL,
		      `convert_lb` int(11) NOT NULL default '0',
		      `status` tinytext collate utf8_unicode_ci NOT NULL,
		      `date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `user` tinytext collate utf8_unicode_ci NOT NULL,
		      `allow_comments` int(11) NOT NULL default '0',
		      `keywords` tinytext collate utf8_unicode_ci NOT NULL,
		      `via_link` tinytext collate utf8_unicode_ci NOT NULL,
		      `via_title` tinytext collate utf8_unicode_ci NOT NULL,
		      `comment_count` tinytext collate utf8_unicode_ci NOT NULL,
		      `comment_names` mediumtext collate utf8_unicode_ci NOT NULL,
		      `trackback_count` tinytext collate utf8_unicode_ci NOT NULL,
		      `trackback_names` mediumtext collate utf8_unicode_ci NOT NULL,
		      `extrafields` text collate utf8_unicode_ci NOT NULL,
		      PRIMARY KEY  (`uid`),
		      FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		    $query2 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `status`, `date`, `publish_date`, `edit_date`, `user`, `allow_comments`, `keywords`, `via_link`, `via_title`, `comment_count`, `comment_names`, `trackback_count`, `trackback_names`) VALUES
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
		<p>All text that you write in the \'body\' part of the entry will only appear on the entry\'s own page. To see how this works, edit this entry in the PivotX administration by going to \'Entries &amp; Pages\' &raquo; \'Entries\' &raquo; \'Edit\'.</p>', 0, 'publish', '%now%-00', '%now%-00', '%now%-00', '$username', 1, 'pivot pivotx', '', '', '1', 'Bob', '0', '');";
		
		
		
		    $now = date("Y-m-d-H-i", get_current_date());
		    $version = __("Welcome to"). " " . strip_tags($GLOBALS['build']);
		
		    $query2 = str_replace("%version%", $version, $query2);
		    $query2 = str_replace("%uri-version%", makeURI($version), $query2);
		    $query2 = str_replace("%now%", $now, $query2);
		
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
		
		    $query1 = "CREATE TABLE IF NOT EXISTS `$tablename` (
		        `uid` int(11) NOT NULL auto_increment,
		        `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
		        `target_uid` int(11) NOT NULL default '0',
		        `fieldkey` tinytext collate utf8_unicode_ci NOT NULL,
		        `value` text collate utf8_unicode_ci NOT NULL,
		        PRIMARY KEY  (`uid`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		    $this->query($query1);
		}
		
		
		
		
		/**
		 * Create the SQL table for Entries.
		 *
		 * @param link $sql
		 */
		function makeCommentsTable() {
		    global $PIVOTX;
		
		    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."comments", true);
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `entry_uid` int(11) NOT NULL default '0',
		      `name` tinytext collate utf8_unicode_ci NOT NULL,
		      `email` tinytext collate utf8_unicode_ci NOT NULL,
		      `url` tinytext collate utf8_unicode_ci NOT NULL,
		      `ip` tinytext collate utf8_unicode_ci NOT NULL,
		      `date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `comment` mediumtext collate utf8_unicode_ci NOT NULL,
		      `registered` tinyint(4) NOT NULL default '0',
		      `notify` tinyint(4) NOT NULL default '0',
		      `discreet` tinyint(4) NOT NULL default '0',
		      `moderate` tinyint(4) NOT NULL default '0',
		      `spamscore` tinyint(4) NOT NULL default '0',
		      PRIMARY KEY  (`uid`),
		      KEY `entry_uid` (`entry_uid`),
		      KEY `date` (`date`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		    $query2 = "INSERT INTO `$tablename` VALUES(1, 1, 'Bob', '', 'http://pivotx.net', '127.0.0.1', '%now%-10', 'Hi! This is what a comment looks like!', 0, 0, 0, 0, 0);";
		
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `entry_uid` int(11) NOT NULL default '0',
		      `name` tinytext collate utf8_unicode_ci NOT NULL,
		      `title` tinytext collate utf8_unicode_ci NOT NULL,
		      `url` tinytext collate utf8_unicode_ci NOT NULL,
		      `ip` tinytext collate utf8_unicode_ci NOT NULL,
		      `date` datetime NOT NULL default '0000-00-00 00:00:00',
		      `excerpt` mediumtext collate utf8_unicode_ci NOT NULL,
		      `spamscore` tinyint(4) NOT NULL default '0',
		      PRIMARY KEY  (`uid`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `tag` tinytext collate utf8_unicode_ci NOT NULL,
		      `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
		      `target_uid` int(11) NOT NULL default '0',
		      PRIMARY KEY  (`uid`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
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
		
		    $query1 = "CREATE TABLE `$tablename` (
		      `uid` int(11) NOT NULL auto_increment,
		      `category` tinytext collate utf8_unicode_ci NOT NULL,
		      `target_uid` int(11) NOT NULL default '0',
		      PRIMARY KEY  (`uid`),
		      KEY `target_uid` (`target_uid`)
		    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		
		    if ($this->get_server_info() < "4.1") {
		        $query1 = trim_query($query1);
		    }
		
		    $this->query($query1);
		    $this->query("INSERT INTO `$tablename` (`uid`, `category`, `target_uid`) VALUES (1, 'default', 1);");
		    $this->query("INSERT INTO `$tablename` (`uid`, `category`, `target_uid`) VALUES (2, 'linkdump', 2);");
		
		}
}


?>
