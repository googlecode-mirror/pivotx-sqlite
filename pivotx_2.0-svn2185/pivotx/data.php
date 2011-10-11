<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: data.php 2079 2009-08-30 06:53:18Z hansfn $
//
// ---------------------------------------------------------------------------


/**
 * Set up the menus that are used in the PivotX interface.
 *
 */
function getMenus() {
    global $PIVOTX, $form_titles;

    if (!is_array($form_titles)) {$form_titles = array(); }

    if (count($modqueue)>0) {
        // __('Moderate Comments'));
        $queuemsg = __('There are %1 comment(s) waiting to be approved.');
        $queuemsg = str_replace("%1", count($modqueue), $queuemsg);
    } else {
        $queuemsg = __('No comments are waiting to be approved.');
    }

    if (isset($PIVOTX['session'])) {
        $currentuser = $PIVOTX['session']->currentUser();
        $currentuserlevel = $currentuser['userlevel'];
    } else {
        $currentuserlevel = 0;
    }

    if ($currentuserlevel >= 3) {

        // Admin and Superadmin see all options
        $menu = array(
            'entries' => array(__('Entries &amp; Pages'), __('Overview of Entries')),
            'media' => array(__('Manage Media'), __('Manage and Upload Media')),
            'administration' => array(__('Administration'), __('Overview of Administrative functions')),
            'extensions' => array(__('Extensions'), __('Manage installed Extensions')),
            //'maintenance' => array(__('Maintenance'), __('Perform routine maintenance on Pivot\'s files')),
        );

    } else if ($currentuserlevel >= 1) {

        // Normal and advanced users see only 'entries' and 'media'
        $menu = array(
            'entries' => array(__('Entries'), __('Overview of Entries')),
            'media' => array(__('Manage Media'), __('Manage and Upload Media')),
        );

    } else {

        // Not logged in users only see 'login'
        $menu = array(
            'login' => array(__('Log in')),
        );

    }

    // Here we construct the submenu's.. The first two values of each item are
    // the name and title. The third boolean parameter determines if the item is
    // shown smaller and nest to the previous one.
    // The fourth boolean parameter inserts a seperator.
    // If an item is left blank, nothing will be shown in the menu. This is useful
    // for tracking the 'active' page, without showing anything in the menu.
    $submenu = array(
        'entries' => array(
            'entries' => array(__('Entries'), __('Overview of Entries')),
            'entry' => array(__('New Entry'), __('Write and Publish a new Entry'), true, true),
            'pagesoverview' => array(__('Pages'), __('Overview of Pages')),
            'page' => array(__('New Page'), __('Write and Publish a new Page'), true, true),
            'comments' => array(__('Moderate Comments'), $queuemsg),
            'trackbacks' => array(__('Trackbacks'), __('Overview of Last Trackbacks'))
        ),

        'administration' => array(
            'configuration' => array(__('Configuration'), __('Edit the Configuration file')),
            'advconfiguration' => array(__('Advanced Configuration'), __('Edit, Add and Delete advanced Configuration options'), true, true),
            'users' => array(__('Users'), __('Create, edit and delete Users')),
            'categories' => array(__('Categories'), __('Create, edit and delete the Categories')),
            'weblogs' => array(__('Weblogs'), __('Create, edit and delete Weblogs'), false, false),
            'weblogedit' => array(),
            'weblognew' => array(),
            'visitors' => array(__('Registered Visitors'), __('View and edit Registered Visitors')),
            
            // these were previously under their own 'maintenance' header, but putting them under
            // 'administration' might be more logical..
            'spamprotection' => array(__('Spam Protection'), __('Overview of the various tools to keep your weblogs spam-free')),

            'backup' => array(__('Backup'), __('Download a zip file containing your configuration files, templates or entries database')),
            'spamconfig' => array(),
            'spamlog' => array(),
            
        ),

        'extensions' => array(
            'extensions' => array(__('Extensions'), __('Manage installed Extensions'), false, true),
            'widgets' => array(__('Widgets'), __('Manage installed Widgets'), false, true),

        ),

        'spamprotection' => array(
            'spamconfig' => array(__('Spam configuration'), __('Configure Spam Protection tools (like HashCash and SpamQuiz).')),
            //'ignoreddomains' => array(__('Blocked Phrases'), __('View and Edit the Blocked Phrases to combat spam.')),
            //'ignoreddomains_update' => array(__('Update the Global Phrases list from pivotlog.net'), __('Update the Global Phrases list from pivotlog.net')),
            //'spamwasher' => array(__('Spam Washer'), __('Search for spam, and delete all of it from your entries and trackbacks.')),
            //'ipblocks' => array(__('IP blocks'), __('View and Edit the blocked IP addresses.')),
            'spamlog' => array(__('Spam Log'), __('View and Reset the Spam Log.')),
        ),
        'configuration' => array(
            'configuration' => array(__('Configuration'), __('Edit the Configuration file')),
            'advconfiguration' => array(__('Advanced Configuration'), __('Edit, Add and Delete advanced Configuration options')),
            'backupconfig' => array(__('Backup of Configuration Files'), __('This will let you download a zip file containing your configuration files')),
            'spamconfig' => array(),
            'ignoreddomains' => array(),
            'spamwasher' => array(),
            'ipblocks' => array(),
            'spamlog' => array(),
            
        ),


    );

    if ($currentuserlevel >= 2) {
        $submenu['media']['media'] = array(__('Manage Media'), __('Manage and Upload Media')); 
    }
    
    if ($currentuserlevel >= 3) {
        $submenu['media']['templates'] = array(__('Templates'), __('Create, edit and delete Templates'), false, true);
        $submenu['media']['fileexplore'] = array(__('Explore Database Files'), __('View files (both text and database files)'));
        $submenu['media']['homeexplore'] = array(__('Explore Files'), __('View files in the site\'s root'));
    }

    if (isset($PIVOTX['config'])) {

        // If 'browse_blog_folder' is set, we show the menu option to browse it as well.
        if ($PIVOTX['config']->get('browse_blog_folder')==1) {
            $submenu['media']['homeexplore'] = array(__('Explore Home folder'), 
            __('View files (both text and database files)'));
        }

        // Add 'build index', if we're using flat files..
        if ($PIVOTX['config']->get('db_model')=="flat") {
            $submenu['administration']['buildindex'] = array(__('Rebuild the Index'), 
                __('Rebuild the index of your Database'));
            $submenu['administration']['buildsearchindex'] = array(__('Rebuild Search Index'), 
                __('Rebuild the Searchindex, to allow searching in entries and pages'));
            $submenu['administration']['buildtagindex'] = array(__('Rebuild Tag Index'), 
                __('Rebuild the Tagindex, to display tag clouds and tags below entries'));
        }

    }

    // Add installed admin extensions to the menu.
    if ($currentuserlevel >= 3) {
        $form_titles = $PIVOTX['extensions']->getAdminScreenNames();
    }
    
    foreach($form_titles as $key=>$title) {
        if (!is_numeric($key)) {
            $submenu['extensions'][$key] = array($title, '');
        }
    }

    // Build the array with children of the current menus
    $menuchildren = array();
    foreach ($submenu as $mainmenu=>$submenuitems) {
        foreach ($submenuitems as $submenuitem=>$dummy) {
            $menuchildren[$mainmenu][]=$submenuitem;
        }        
    }

    $PIVOTX['template']->assign("menu", $menu);
    $PIVOTX['template']->assign("submenu", $submenu);
    $PIVOTX['template']->assign("menuchildren", $menuchildren);

}


/**
 * Get the default categories. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * @return array
 *
 */
function getDefaultCategories() {
    global $PIVOTX;

    $userdata = $PIVOTX['users']->getUsers();
    $username = $userdata[0]['username'];


    $categories = array (
        0 => array (
            'name' => 'default',
            'display' => 'Default',
            'users' => array (
                    0 => $username,
                ),
            'order' => '100',
            'hidden' => -1,
        ),
        1 => array (
            'name' => 'linkdump',
            'display' => 'Linkdump',
            'users' => array (
                 0 => $username,
                ),
            'order' => '101',
            'hidden' => -1,
        ),
    );

    return $categories;

}


/**
 * Get the default configuration. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * We also use this to check if the required values haven't been deleted accidentily.
 *
 * @return array
 *
 */
function getDefaultConfig() {

    $config = array (
        'allow_comments' => '1',
        'allow_paragraphs' => '0',
        'chmod' => '0644',
        'cookie_length' => '1814400',
        'db_version' => 1,
        'debug' => 0,
        'default_category' => 'default',
        'diffdate_format' => '%ordday% %monname% \'%ye% - ',
        'emoticons' => '1',
        'encode_email_addresses' => '0',
        'entrydate_format' => '%hour24%:%minute%',
        'extensions_path' => 'extensions/',
        'fulldate_format' => '%ordday% %monthname% \'%ye% - %hour24%:%minute%',
        'hardened_trackback' => '0',
        'hashcash' => '0',
        'ignore_magic_quotes' => '0',
        'ignore_register_globals' => '0',
        'ignore_setupscript' => '0',
        'installed' => '0',
        'language' => 'eng',
        'lastcomm_amount_max' => '60',
        'limit_feed_items' => '15',
        'log' => '0',
        'maxhrefs' => '3',
        'mod_rewrite' => '0',
        'moderate_comments' => 0,
        'overview_entriesperpage' => '20',
        'ping' => '0',
        'ping_urls' => 'rpc.pingomatic.com',
        'pivotx_url' => '/pivotx/',
        'rebuild_threshold' => '28',
        'search_index' => '1',
        'selfreg' => 0,
        'sitename' => 'PivotX Powered',
        'spampingurl' => '',
        'spamquiz' => '0',
        'spamthreshold' => '5',
        'tag_fetcher_amount' => '8',
        'tag_fetcher_enabled' => '1',
        'tag_flickr_amount' => '8',
        'tag_flickr_enabled' => '1',
        'tag_cloud_amount' => '30',
        'tag_max_font' => '17',
        'tag_min_font' => '9',
        'text_processing' => '1',
        'timeoffset' => '0',
        'timeoffset_unit' => 'h',
        'unlink' => '0',
        'upload_accept' => 'image/gif, image/jpeg, image/png, text/html, text/plain, text/xml, application/pdf, video/x-msvideo, application/x-shockwave-flash, video/x-msvideo, video/x-ms-wmv, video/mp4, video/mpeg, video/quicktime, application/octet-stream, application/x-zip-compressed, application/x-bittorrent, text/css, application/x-javascript',
        'upload_autothumb' => '1',
        'upload_extension' => '.jpg',
        'upload_file_name' => 'userfile',
        'upload_make_safe' => '0',
        'upload_max_filesize' => '5000000',
        'upload_path' => 'images/%year%-%month%/',
        'upload_save_mode' => '2',
        'upload_thumb_height' => '100',
        'upload_thumb_quality' => '78',
        'upload_thumb_width' => '350',
        'wysiwyg_editor' => '1',
        'xmlrpc' => 0,

        'db_model' => 'flat',
        'db_username' => "",
        'db_password' => "",
        'db_hostname' => "localhost",
        'db_databasename' => "",
        'db_prefix' => "pivotx_",

    );


    return $config;

}


/**
 * Get the default weblog. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * Also, if we're creating a new weblog from scratch, we can use this to do so.
 *
 */
function getDefaultWeblog() {

    // Use the skinny/skinny.theme as the template for the new Weblog.
    $weblog = load_serialize(dirname(__FILE__)."/templates/skinny/skinny.theme");

    return $weblog;

}


/**
 * Get the default pages. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 */
function getDefaultPages() {

    $pages = array(
        0 => array(
            'chaptername' => "Pages",
            'description' => "Add some pages here, or start a new chapter.",
            'pages' => array(),
            'sortorder' => 1,
        )

    );

    return $pages;

}


/**
 * Get the default styles for the widgets.
 *
 * @return array
 */
function getDefaultWidgetStyles() {

    $styles = array(
        'widget-lg' => 'Light gray',
        'widget-dg' => 'Dark gray',
        'widget-min' => 'Minimally styled'
    );

    return $styles;

}

/**
 * Create the SQL table for Pages.
 *
 * @param link $sql
 */
function makePagesTable($sql) {
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
    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }



    $query2 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
(1, 'About PivotX', 'about', '', '<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>, the coolest free and open tool to power your blog and website. To change this text, edit ''<tt>About PivotX</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</p>', '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice     weblogger to use and complex enough to meet the demands of advanced webmasters.     It can be used to publish a variety of websites from the most basic weblog to very advanced CMS style solutions.</p>\r\n<p>PivotX is - if we do say so ourselves - quite an impressive piece of software. It     is made even better through the use of several external libraries. We thank their     authors for the time taken to develop these very useful tools and for making     them available to others.</p>\r\n<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously     forged ahead thanks to the efforts of a lot     of dedicated and very talented people. The PivotX core team is still very active     but keep in mind that PivotX would not be what it is today without the valuable     contributions made by several other people.</p>', 5, 'skinny/page_template.html', 'publish', '%now%-00', '%now%-00', '%now%-00', 1, 10, '$username', 1, ''); ";

    $query3 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
(2, 'Links', 'links', '', '<p>Some links to sites with more information:</p>\r\n<ul>\r\n<li>PivotX - <a href=\"http://pivotx.net\">The PivotX website</a></li>\r\n<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>\r\n<li>Read <a href=\"http://book.pivotx.net\">the PivotX documentation</a></li>\r\n<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>\r\n<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>\r\n<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>\r\n</ul>\r\n<p><small>To change these links, edit ''<tt>Links</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</small></p>', '', 5, 'skinny/page_template.html', 'publish', '%now%-01', '%now%-01', '%now%-01', 1, 10, '$username', 1, '');";


    $now = date("Y-m-d-H-i", get_current_date());

    $query2 = str_replace("%now%", $now, $query2);
    $query3 = str_replace("%now%", $now, $query3);

    $sql->query($query1);
    $sql->query($query2);
    $sql->query($query3);


}


/**
 * Create the SQL table for Chapters.
 *
 * @param link $sql
 */
function makeChaptersTable($sql) {
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
    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }


    $query2 = "INSERT INTO `$tablename` (`uid`, `chaptername`, `description`, `sortorder`) VALUES
        (1, 'Pages', 'Add some pages here, or start a new chapter.', 10);
    ";

    $sql->query($query1);
    $sql->query($query2);

}


/**
 * Create the SQL table for Entries.
 *
 * @param link $sql
 */
function makeEntriesTable($sql) {
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

    if ($sql->get_server_info() < "4.1") {
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

    $sql->query($query1);
    $sql->query($query2);

}




/**
 * Create the SQL table for the Extra fields in Entries and Pages.
 *
 * @param link $sql
 */
function makeExtrafieldsTable($sql) {
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

    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }

    $sql->query($query1);


}




/**
 * Create the SQL table for Entries.
 *
 * @param link $sql
 */
function makeCommentsTable($sql) {
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

    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }

    $query2 = "INSERT INTO `$tablename` VALUES(1, 1, 'Bob', '', 'http://pivotx.net', '127.0.0.1', '%now%-10', 'Hi! This is what a comment looks like!', 0, 0, 0, 0, 0);";

    $now = date("Y-m-d-H-i", get_current_date());
    $query2 = str_replace("%now%", $now, $query2);

    $sql->query($query1);
    $sql->query($query2);

}



/**
 * Create the SQL table for Trackbacks.
 *
 * @param link $sql
 */
function makeTrackbacksTable($sql) {
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

    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }

    $sql->query($query1);

}



/**
 * Create the SQL table for Tags.
 *
 * @param link $sql
 */
function makeTagsTable($sql) {
    global $PIVOTX;

    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `tag` tinytext collate utf8_unicode_ci NOT NULL,
      `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
      `target_uid` int(11) NOT NULL default '0',
      PRIMARY KEY  (`uid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }

    $sql->query($query1);

}



/**
 * Create the SQL table for Categories.
 *
 * @param link $sql
 */
function makeCategoriesTable($sql) {
    global $PIVOTX;

    $tablename = safe_string($PIVOTX['config']->get('db_prefix')."categories", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `category` tinytext collate utf8_unicode_ci NOT NULL,
      `target_uid` int(11) NOT NULL default '0',
      PRIMARY KEY  (`uid`),
      KEY `target_uid` (`target_uid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trim_query($query1);
    }

    $sql->query($query1);
    $sql->query("INSERT INTO `$tablename` (`uid`, `category`, `target_uid`) VALUES (1, 'default', 1);");
    $sql->query("INSERT INTO `$tablename` (`uid`, `category`, `target_uid`) VALUES (2, 'linkdump', 2);");

}



?>
