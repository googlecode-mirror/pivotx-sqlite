<?php
/**
 * Contains the Class that manages Messages and Warnings.
 *
 * Messages like 'The entry has been saved', will be shown on any page. Warnings
 * like 'Please make sure all files in /pivotx/db/ are writable' will only be
 * shown on the dashboard page.
 *
 * @package pivotx
 * @subpackage extensions
 */


// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_messages.php 1963 2009-05-24 12:06:51Z hansfn $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";


/**
 * The class that renders pages and handles caching, for all the pages that are
 * seen on the 'front side'.
 *
 */
class Messages {

    var $filelist;
    var $messages;
    var $warnings;

    /**
     * Initialise the Messages object.
     *
     */
    function Messages() {

        $this->messages = array();
        $this->warnings = array();
        $this->filelist = array();

        $this->badextensions = explode(",", "php,php3,php4,php5,pl,cgi,asp,exe,xpi,cab,dmg,vbs,com,bat,sh,pif,scr,dll");

    }


    /**
     * Add a message to the $message array
     *
     * @param string $message
     */
    function addMessage($message) {

        $this->messages[] = wordwrap($message, 46, " ", true);

    }



    /**
     * Get an array of the messages.
     *
     * @return array
     */
    function getMessages() {
        global $PIVOTX;


        $PIVOTX['extensions']->executeHook('before_getmessages', $this);


        return $this->messages;

    }


    /**
     * Add a warning to the $warning array
     *
     * @param string $warning
     */
    function addWarning($warning, $additionalinfo="") {

        if (empty($additionalinfo)) {
            $this->warnings[] = "<p>" . $warning . "</p>";
        } else {
            $this->warnings[] = "<p><strong>" . $warning . "</strong><br />" . $additionalinfo . "</p>";
        }


    }


    /**
     * Check for common misconfigurations, filerights, and whatnot.
     *
     */
    function checkWarnings() {
        global $minrequiredphp, $dbversion, $PIVOTX;

        $this->filelist = array();

        // Check if there are any hooks to execute..
        $PIVOTX['extensions']->executeHook('before_checkwarnings', $dummy);

        // We should only check these warnings when logged in.. Whilst displaying
        // them isn't a direct security problem, we should be careful about
        // giving Teh scr1ptk1ddi3zz any pointers.
        if (!$PIVOTX['session']->isLoggedIn()) {
            return;
        }

        // Check files in pivotx/db/
        $this->_checkFilerights($PIVOTX['paths']['db_path'], "db/", false );
        if (!empty($this->filelist)) {
            $this->_makeFileWarning("db/");
        }

        // Check files in pivotx/templates/
        $this->_checkFilerights($PIVOTX['paths']['templates_path'], "templates/", true );
        if (!empty($this->filelist)) {
            $this->_makeFileWarning("templates/");
        }

        // Check files in pivotx/images/
        $this->_checkFilerights($PIVOTX['paths']['upload_base_path'], basename($PIVOTX['paths']['upload_base_path'])."/", true );
        if (!empty($this->filelist)) {
            $this->_makeFileWarning(basename($PIVOTX['paths']['upload_base_path'])."/");
        }

        // Check minimum PHP version.
        if (!check_version(phpversion(), $minrequiredphp)) {
            $thiswarning = sprintf(__("The current version of PHP on the server is %s, which is an older version than PivotX requires (%s). PivotX will most likely not work correctly, until the server is updated to a newer version."), phpversion(), $minrequiredphp);
            $this->warnings[] = "<p>". $thiswarning ."</p>";
        }

        // Check Safe Mode
        if( ini_get('safe_mode') && (!$PIVOTX['config']->get('ignore_safe_mode')) ) {
            $thiswarning = __("This webserver has safe_mode enabled. This doesn't actually make things any 'safer', just more annoying. Please ask your hosting provider to turn it off. See the documentation for more info: <a href='http://docs.pivotx.net/doku.php?id=dealing_with_safe_mode'>Dealing with safe_mode</a>.");
            $this->warnings[] = "<p>". $thiswarning ."</p>";
        }

        // Check PivotX Setup
        if( (file_exists($PIVOTX['paths']['pivotx_path'].'../pivotx-setup-safemode.php') || 
                file_exists($PIVOTX['paths']['pivotx_path'].'../pivotx-setup.php')) && (!$PIVOTX['config']->get('ignore_setupscript')) ) {
            $thiswarning = __('The PivotX installer script "pivotx-setup.php" is still present in the parent folder. You should be aware that this is a potential security risk. We advise you to remove it, or to set an empty password inside it, so that it can\'t be executed by people with bad intentions.');
            $this->warnings[] = "<p>". $thiswarning ."</p>";
        }

        // Check (old) Pivot Setup - message can't be ignored
        if( file_exists($PIVOTX['paths']['pivotx_path'].'../pivot-setup-safemode.php') || file_exists($PIVOTX['paths']['pivotx_path'].'../pivot-setup.php')) {
            $thiswarning = __('The old Pivot installer script "pivot-setup.php" is still present in the parent folder. Please remove it immediately since it\'s not used for PivotX and it is a potential security risk.');

            $this->warnings[] = "<p>". $thiswarning ."</p>";
        }

        /* -- Commented this out for now. Takes up to two seconds for some servers,
           -- Which is very bad, considering it's called every time on the dashboard page.
           -- Perhaps move this check to scheduler.php?
               
        // Check if we have 'mod rewrite' enabled, but no .htaccess 
        if( ($PIVOTX['config']->get('mod_rewrite')>0) && (!$PIVOTX['config']->get('ignore_modrewrite_check')) && function_exists('get_headers') ) {
                    
            // Get the headers for a web page that we know always exists
            $url = $PIVOTX['paths']['host'].$PIVOTX['paths']['site_url']."search/modrewritecheck";
            
            $headers = get_headers($url);
            
            // $headers[0] should look like 'HTTP/1.1 200 OK', else give warning
            if (strpos($headers[0], "200 OK")===false) {
                $thiswarning = __('\'Mod rewrite\' is enabled, but it seems like the webserver is not set up correctly to serve pages with non-crufty URLs. You should copy the <tt>example.htaccess</tt> from the PivotX distribution to <tt>.htaccess</tt>. Until you\'ve done this, most pages on your site will give a 404-not-found error.');
                $this->warnings[] = "<p>". $thiswarning ."</p>";
            }
            
            
        }  */      

        // Check if magic_quotes_runtime is enabled - Warning is commented out for now
        // because we _should_ be able to handle both cases transparently for the user.
        //if( get_magic_quotes_runtime() && (!$PIVOTX['config']->get('ignore_magic_quotes')) {
        //    $thiswarning = __('Your server has a PHP option set that\'s called "Magic quotes" enabled. This might cause Pivot to run sub-optimally.  Look on <a href="http://docs.pivotx.net/doku.php?id=servers_with_magic_quotes">this page</a> to remedy the situation.');
        //    $this->warnings[] = "<p>". $thiswarning ."</p>";
        //}        
        
        
        /**
         * Commented this out. since PivotX doesn't need to write files in a higher
         * dir than pivotx/, there is no problem with open_basedir..
         */
        // Check Open Basedir
        //        if( ini_get('open_basedir') ) {
        //            $thiswarning = __("This webserver has open_basedir enabled. You'll have a hard time running PivotX in the current configuration. Please ask your hosting provider to turn it off. See the documentation for more info: <a href='http://docs.pivotx.net/doku.php?id=dealing_with_safe_mode'>Dealing with safe_mode</a>.");
        //            $this->warnings[] = "<p>". $thiswarning ."</p>";
        //        }

        // Check Register Globals
        if( ini_get('register_globals') && (!$PIVOTX['config']->get('ignore_register_globals')) ) {
            $thiswarning = __("This webserver has register_globals enabled. This is a serious potential security issue. Please ask your hosting provider to turn it off. See the PHP documentation for more info: <a href='http://php.net/register_globals'>Register Globals</a>.");
            $this->warnings[] = "<p>". $thiswarning ."</p>";
        }

        // Check if the DB has the right version (if we're using MySQL that is)
        if ( ($PIVOTX['config']->get('db_model') == "mysql") && ($PIVOTX['config']->get('db_version') < $dbversion) ) {
            $this->checkDBVersion();
        }

        $user = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
        
        // Check if the password is properly salted.
        if ($user['salt']=="") {
            $thiswarning = __("Your password is not fully encrypted yet. Please go to %myinfo%, and set your password again.");
            $link = sprintf("<a href=\"index.php?page=myinfo\">%s</a>", __("My Info"));
            $thiswarning = str_replace('%myinfo%', $link, $thiswarning);
            $this->warnings[] = "<p>". $thiswarning ."</p>"; 
        }

        // Check if there are any hooks to execute..
        $PIVOTX['extensions']->executeHook('after_checkwarnings', $dummy);


    }


    /**
     * Check if the current version of the DB is updated to the latest version,
     * and update it if it isn't..
     *
     */
    function checkDBVersion() {
        global $PIVOTX;

        // Set up first DB factory
        $sqlFactory1 = new sqlFactory($PIVOTX['config']->get('db_model'),
          													 $PIVOTX['config']->get('db_databasename'),
            												 $PIVOTX['config']->get('db_hostname'),
            												 $PIVOTX['config']->get('db_username'),
            												 $PIVOTX['config']->get('db_password')
        									);

        // Set up first DB connection
        $db1 = $sqlFactory1->getSqlInstance();

        // Set up second DB factory
        $sqlFactory2 = new sqlFactory($PIVOTX['config']->get('db_model'),
          													 $PIVOTX['config']->get('db_databasename'),
            												 $PIVOTX['config']->get('db_hostname'),
            												 $PIVOTX['config']->get('db_username'),
            												 $PIVOTX['config']->get('db_password')
        									);

        // Set up second DB connection
        $db2 = $sqlFactory2->getSqlInstance();

        $db_version = $PIVOTX['config']->get('db_version');

        $entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
        $categoriestable = safe_string($PIVOTX['config']->get('db_prefix')."categories", true);
        $commentstable = safe_string($PIVOTX['config']->get('db_prefix')."comments", true);
        $pagestable = safe_string($PIVOTX['config']->get('db_prefix')."pages", true);
        $extratable = safe_string($PIVOTX['config']->get('db_prefix')."extrafields", true);

        // DB changes from PivotX 2.0 alpha 2 to alpha 3.
        if (intval($db_version) < 1) {
            debug("now updating DB to version 1..");

            // We need to set the URI's for all entries in the DB.
            $db1->query("SELECT uid,title FROM $entriestable");

            while ($entry = $db1->fetch_row()) {
                $uri = makeURI($entry['title']);
                $db2->query("UPDATE $entriestable SET uri=". $db2->quote($uri) . " WHERE uid= ". $entry['uid'] ." LIMIT 1;");
            }

            // Add fultext search for entries and pages..
            $db1->query("ALTER TABLE $entriestable ADD FULLTEXT(title, subtitle, introduction, body);");
            $db1->query("ALTER TABLE $pagestable ADD FULLTEXT(title, subtitle, introduction, body);");

            debug("Updated DB to version 1");
            $PIVOTX['config']->set('db_version', 1);
        }


        // DB changes introduced between Alpha 4 and Beta 1.
        if (intval($db_version) < 3) {
            debug("now updating DB to version 3..");

            // Add extrafields field for entries and pages..
            $db1->query("CREATE TABLE IF NOT EXISTS `$extratable` (
                `uid` int(11) NOT NULL auto_increment,
                `contenttype` tinytext character set utf8 collate utf8_unicode_ci NOT NULL,
                `target_uid` int(11) NOT NULL default '0',
                `fieldkey` tinytext character set utf8 collate utf8_unicode_ci NOT NULL,
                `value` text character set utf8 collate utf8_unicode_ci NOT NULL,
                PRIMARY KEY  (`uid`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

            debug("Updated DB to version 3");
            $PIVOTX['config']->set('db_version', 3);
        }
    

        // DB changes from PivotX 2.0 beta 1 to beta 2.
        if (intval($db_version) < 4) {
            debug("now updating DB to version 4..");

            // Add fultext search for entries and pages..
            $db1->query("ALTER TABLE `$entriestable` DROP INDEX `title`;");
            $db1->query("ALTER TABLE `$pagestable` DROP INDEX `title`;");
            $db1->query("ALTER TABLE `$entriestable` ADD FULLTEXT(title, subtitle, introduction, body, keywords);");
            $db1->query("ALTER TABLE `$pagestable` ADD FULLTEXT(title, subtitle, introduction, body, keywords);");

            debug("Updated DB to version 4");
            $PIVOTX['config']->set('db_version', 4);
        }

        // DB changes for PivotX 2.0 RC 1d and up.
        if (intval($db_version) < 5) {
            
            // Add indices to speed up JOINs..
            $db1->query("ALTER TABLE `$categoriestable` ADD KEY `target_uid` (`target_uid`);");
            $db1->query("ALTER TABLE `$commentstable` ADD KEY `entry_uid` (`entry_uid`);");
            $db1->query("ALTER TABLE `$commentstable` ADD KEY `date` (`date`);");
            
            debug("Updated DB to version 5");
            $PIVOTX['config']->set('db_version', 5);
        }

    }


    /**
     * Returns an array containing the HTML for showing the user. (if any warnings were
     * triggered, either by 'checkWarnings()' or directly)
     */
    function getWarnings() {
        global $PIVOTX;

        $PIVOTX['extensions']->executeHook('before_getwarnings', $this);

        return $this->warnings;


    }


    /**
     * Recursively check a folder for non-writable files. Store the results
     * in $this->filelist
     *
     * @param string $folder
     * @param string $base
     * @return boolean
     */
    function _checkFilerights($folder, $base, $checkexecutables=true) {

        // If the folder is not writable, don't check the files in it..
        if (!is_writable($folder) || (!is_readable($folder)) ) {
            $this->filelist[] = __("folder ") . "  " . $base;
            return false;
        }

        $dir = dir($folder);

        // Iterate over the files and folders inside $folder..
        while (false !== ($entry = $dir->read())) {
            // We can skip '.' and '..', and we don't have to warn about non-writable 'index.html' files.
            if ($entry!=".." && $entry!="." && $entry!=".svn"  && $entry!=".htaccess" && $entry!="index.html" ) {
                if ( (!is_writable($folder.$entry)) || (!is_readable($folder.$entry)) ) {
                    // if it's not writable, add it to the array..
                    if (is_dir($folder.$entry)) {
                        $this->filelist[] = __("folder ") . "  " . $base.$entry."/";
                    } else {
                        $this->filelist[] = $base.$entry;
                    }
                } else {

                    // If it's a folder, check it recursively for non-writable files.
                    if (is_dir($folder.$entry)) {
                        $this->_checkFilerights($folder.$entry."/", $base.$entry."/", $checkexecutables);
                    }

                }

                // Check if it's an executable file, and if it is, warn the user.
                if ($checkexecutables && (in_array(strtolower(getextension($entry)), $this->badextensions)) ) {
                    $warning = sprintf( __("There's an executable file in one of the 'open' folders. You should remove this file immediately: <tt>%s</tt>."),
                            basename(dirname($folder))."/".basename($folder)."/".$entry
                        );
                    $warning .= "<br />" . __("If you do not know where this file came from, please report this incident on our Forum. Please keep a back-up copy of the file for our inspection.");

                    $this->addWarning( $warning );


                }


            }
        }

        return true;

    }


    /**
     * Make the HTML for the warning, listing the unwritable files.
     *
     * @param string $folder
     */
    function _makeFileWarning($folder) {

        $thiswarning = "<p>" . __("One or more files inside the <tt>'%s'</tt> folder are not readable or writable by PivotX.") ." \n";
        $thiswarning .= __("Please log in using your FTP or Shell client, and change the filerights (chmod) so that PivotX has both read and write access to these files.") ." \n";
        $thiswarning = str_replace("%s", $folder, $thiswarning);

        $thiswarning .= "</p>\n<ul>\n";

        if (count($this->filelist)<6) {

            foreach($this->filelist as $key=>$value) {
                $this->filelist[$key] = wordwrap($value, 20, "&shy;", true);
            }

            $thiswarning .= "<li><tt>".implode("</tt></li>\n<li><tt>", $this->filelist)."</tt></li>\n";
        } else {

            // We use '4' on purpose, even though '5' might seem more logical. This way we
            // can be sure that there's at least '2 more' files, which makes translation
            // easier than '1 file or more files'
            $slice = array_slice($this->filelist, 0, 4);

            foreach($slice as $key=>$value) {
                $slice[$key] = wordwrap($value, 10, "<span> </span>", true);
            }

            $thiswarning .= "<li><tt>".implode("</tt></li>\n<li><tt>", $slice)."</tt></li>\n";
            $thiswarning .= "<li><em>" . __(".. and %s more files.") . "</em></li>\n";

            $thiswarning = str_replace("%s", (count($this->filelist)-4), $thiswarning);

        }

        $thiswarning .= "</ul>\n\n";

        $this->warnings[] = $thiswarning;

        // Empty the filelist, in case we're going to check more folders.
        $this->filelist = array();

    }


}


?>
