<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: data.php 4042 2012-01-06 11:04:41Z marcelfw $
//
// ---------------------------------------------------------------------------


/**
 * Set up the menus that are used in the PivotX interface. (version 2)
 *
 * Version 2 now has full seperation of form and content.
 */
function getMenus() {
    global $PIVOTX;

    // !! (note to Bob/Hans, this doesn't work because $modqueue is local)
    if (count($modqueue)>0) {
        // __('Moderate Comments'));
        $queuemsg = __('There are %1 comment(s) waiting to be approved.');
        $queuemsg = str_replace("%1", count($modqueue), $queuemsg);
    } else {
        $queuemsg = __('No comments are waiting to be approved.');
    }

    // determine user-level
    if (isset($PIVOTX['session'])) {
        $currentuser = $PIVOTX['session']->currentUser();
        $currentuserlevel = $currentuser['userlevel'];
    } else {
        $currentuserlevel = PIVOTX_UL_NOBODY;
    }


    // create a basic menu structure

    if ($currentuserlevel <= PIVOTX_UL_NOBODY) {
        $raw_menu = array(
            array(
                'uri' => 'login',
                'name' => __('Log in')
            )
        );
    } else {
        $raw_menu = array(
            array(
                'sortorder' => 1000,
                'uri' => 'dashboard',
                'name' => __('Dashboard'),
                'description' => '',
                'menu' => array(
                    array(
                        'uri' => 'dashboard',
                        'name' => __('Back to dashboard'),
                        'description' => ''
                    )
                )
            ),
            array(
                'sortorder' => 2000,
                'uri' => 'entries',
                'name' => __('Entries &amp; Pages'),
                'description' => __('Overview of Entries'),
                'level' => PIVOTX_UL_NORMAL,
                'menu' => array(
                    array(
                        'sortorder' => 1000,
                        'uri' => 'entries',
                        'name' => __('Entries'),
                        'description' => __('Overview of Entries')
                    ),
                    array(
                        'sortorder' => 2000,
                        'uri' => 'entry',
                        'name' => __('New Entry'),
                        'description' => __('Write and Publish a new Entry')
                    ),
                    array(
                        'sortorder' => 3000,
                        'is_divider' => true
                    ),
                    array(
                        'sortorder' => 4000,
                        'uri' => 'pagesoverview',
                        'name' => __('Pages'),
                        'description' => __('Overview of Pages')
                    ),
                    array(
                        'sortorder' => 5000,
                        'uri' => 'page',
                        'name' => __('New Page'),
                        'description' => __('Write and Publish a new Page')
                    ),
                    array(
                        'sortorder' => 6000,
                        'is_divider' => true
                    ),
                    array(
                        'sortorder' => 7000,
                        'uri' => 'moderatecomments',
                        'name' => __('Moderate Comments'),
                        'description' => $queuemsg
                    ),
                    array(
                        'sortorder' => 7100,
                        'uri' => 'comments',
                        'name' => __('Comments'),
                        'description' => __('Overview of Comments')
                    ),
                    array(
                        'sortorder' => 8000,
                        'uri' => 'trackbacks',
                        'name' => __('Trackbacks'),
                        'description' => __('Overview of Latest Trackbacks')
                    ),
                ),
            ),
            array(
                'sortorder' => 3000,
                'uri' => 'media',
                'name' => __('Manage Media'),
                'description' => __('Manage and Upload Media'),
                'level' => PIVOTX_UL_ADVANCED,
                'menu' => array(
                    array(
                        'uri' => 'media',
                        'name' => __('Manage Media'),
                        'description' => __('Manage and Upload Media'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'uri' => 'templates',
                        'name' => __('Templates'),
                        'description' => __('Create, edit and delete Templates'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'fileexplore',
                        'name' => __('Explore Database Files'),
                        'description' => __('View files (both text and database files)'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'uri' => 'homeexplore',
                        'name' => __('Explore Files'),
                        'description' => __('View files in the site\'s root'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                ),
            ),
            array(
                'sortorder' => 4000,
                'uri' => 'administration',
                'name' => __('Administration'),
                'description' => __('Overview of Administrative functions'),
                'level' => PIVOTX_UL_ADMIN,
                'menu' => array(
                    array(
                        'uri' => 'configuration',
                        'name' => __('Configuration'),
                        'description' => __('Edit the Configuration file')
                    ),
                    array(
                        'uri' => 'advconfiguration',
                        'name' => __('Advanced Configuration'),
                        'description' => __('Edit, Add and Delete advanced Configuration options')
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'users',
                        'name' => __('Users'),
                        'description' => __('Create, edit and delete Users')
                    ),
                    array(
                        'uri' => 'categories',
                        'name' => __('Categories'),
                        'description' => __('Create, edit and delete the Categories')
                    ),
                    array(
                        'uri' => 'weblogs',
                        'name' => __('Weblogs'),
                        'description' => __('Create, edit and delete Weblogs')
                    ),
                    array(
                        'uri' => 'visitors',
                        'name' => __('Registered Visitors'),
                        'description' => __('View and edit Registered Visitors')
                    ),
                    array(
                        'uri' => 'maintenance',
                        'name' => __('Maintenance'),
                        'description' => __('Perform routine maintenance on PivotX\'s files'),
                        'level' => PIVOTX_UL_ADMIN,
                        'menu' => array(
                            array(
                                'uri' => 'spamprotection',
                                'name' => __('Spam Protection'),
                                'description' => __('Overview of the various tools to keep your weblogs spam-free'),
                                'menu' => array(
                                    array(
                                        'uri' => 'spamconfig',
                                        'name' => __('Spam Configuration'),
                                        'description' => __('Configure Spam Protection tools (like HashCash and SpamQuiz).')
                                    ),
                                    array(
                                        'uri' => 'spamlog',
                                        'name' => __('Spam Log'),
                                        'description' => __('View and Reset the Spam Log.')
                                    ),
                                /* 'ignoreddomains' => array(__('Blocked Phrases'), 
                                       __('View and Edit the Blocked Phrases to combat spam.')),
                                   'ignoreddomains_update' => array(__('Update the Global Phrases list from pivotlog.net'), 
                                       __('Update the Global Phrases list from pivotlog.net')),
                                   'spamwasher' => array(__('Spam Washer'), 
                                       __('Search for spam, and delete all of it from your entries and trackbacks.')),
                                   'ipblocks' => array(__('IP blocks'), __('View and Edit the blocked IP addresses.')),
                                 */
                                )
                            ),
                            array(
                                'uri' => 'backup',
                                'name' => __('Backup'),
                                'description' => __('Download a zip file containing your configuration files, templates or entries database')
                            ),
                            array(
                                'uri' => 'emptycache',
                                'name' => __('Empty Cache'),
                                'description' => __('Clear PivotX\'s internal cache for stored files.')
                            ),
                        )
                    ),
                )
            ),
            array(
                'sortorder' => 5000,
                'uri' => 'extensions',
                'name' => __('Extensions'),
                'description' => __('Manage installed Extensions'),
                'level' => PIVOTX_UL_ADMIN,
                'menu' => array(
                    array(
                        'uri' => 'extensions',
                        'name' => __('Extensions'),
                        'description' => __('Manage installed Extensions')
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'widgets',
                        'name' => __('Widgets'),
                        'description' => __('Manage installed Widgets')
                    ),
                )
            ),
        );
    }


    // specific pivotx modifications to the menu

    if ($currentuserlevel > PIVOTX_UL_NOBODY) {
        $weblogarray = $PIVOTX['weblogs']->getWeblogs();

        $weblogarray_menu = array();
        $cnt = 0;
        foreach($weblogarray as $wa) {
            $cnt++;
            $weblogarray_menu[] = array(
                'uri' => 'weblog.'.$cnt,
                'href' => $wa['link'],
                'name' => __('view') . ' ' . $wa['name'],
                'target_blank' => ($PIVOTX['config']->get('front_end_links_same_window') ? false : true), 
                'description' => $wa['name'] . ' - ' . $wa['payoff']
            );
        }

        if (count($weblogarray) > 2) {
            $weblog_menu = array(
                'menu' => array(
                    array(
                        'uri' => 'weblogs',
                        'name' => __('View weblog'),
                        'description' => '',
                        'menu' => $weblogarray_menu
                    )
                )
            );
            modifyMenu($raw_menu, 'dashboard', $weblog_menu);
        }
        else {
            modifyMenu($raw_menu, 'dashboard', array('menu'=>$weblogarray_menu));
        }
    }

    if ($currentuserlevel >= PIVOTX_UL_ADMIN) {
        $items = $PIVOTX['extensions']->getAdminScreenNames();

        $extensions_menu = array();
        foreach($items as $uri => $name) {
            $extensions_menu[] = array ( 'uri' => $uri, 'name' => $name );
        }
        if (count($extensions_menu) > 0) {
            // we have extensions, we need to add the configure extensions anchor and the extensions themselves

            $cfgext_menu = array(
                array(
                    'is_divider' => true
                ),
                array(
                    'uri' => 'cfgextensions',
                    'name' => __('Configure Extensions'),
                    'description' => __('Configure Extensions')
                )
            );

            modifyMenu($raw_menu, 'extensions', array('menu'=>$cfgext_menu));
            modifyMenu($raw_menu, 'extensions/cfgextensions', array('menu'=>$extensions_menu));
        }
    }

    if (isset($PIVOTX['config'])) {

        // If 'browse_blog_folder' is set, we show the menu option to browse it as well.
        if ($PIVOTX['config']->get('browse_blog_folder')==1) {
            modifyMenu($raw_menu, 'media', array(
                'menu' => array(
                    array(
                        'uri' => 'homeexplore',
                        'name' => __('Explore Home folder'),
                        'description' => __('View files (both text and database files)'),
                    )
                )
            ));
        }

        // Add 'build index', if we're using flat files..
        if ($PIVOTX['config']->get('db_model')=="flat") {
            modifyMenu($raw_menu, 'administration/maintenance', array(
                'menu' => array(
                    array(
                        'uri' => 'buildindex',
                        'name' => __('Rebuild the Index'),
                        'description' => __('Rebuild the index of your database'),
                    ),
                    array(
                        'uri' => 'buildsearchindex',
                        'name' => __('Rebuild Search Index'),
                        'description' => __('Rebuild the Searchindex, to allow searching in entries and pages'),
                    ),
                    array(
                        'uri' => 'buildtagindex',
                        'name' => __('Rebuild Tag Index'),
                        'description' => __('Rebuild the Tagindex, to display tag clouds and tags below entries')
                    )
                )
            ));
        }

    }


    // Extension modifications
    if (!empty($PIVOTX['extensions'])) {
        $args = array(&$raw_menu);
        $PIVOTX['extensions']->executeHook('modify_pivotx_menu', $args);
    }

    // now prepare menu for output

    $menu = organizeMenuLevel($raw_menu, $currentuserlevel);

    $PIVOTX['template']->assign('menu',$menu);
}

/**
 * Modify menu items
 *
 * @param array &$menu    root of the menu
 * @param string $path    the 'uri-path' to the menu item to modify
 * @param array $item     the modifications
 */
function modifyMenu(&$menu, $path, $item) {
    $ptrmenu = &$menu;
    $ptr     = false;

    if ($path != '') {
        $parts = explode('/',$path);
        $found_parts = 0;
        foreach($parts as $part) {
            $idx = false;
            for($i=0; $i < count($ptrmenu); $i++) {
                if ($ptrmenu[$i]['uri'] == $part) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx !== false) {
                $found_parts++;
                if ((!isset($ptrmenu[$idx]['menu'])) || (!is_array($ptrmenu[$idx]['menu']))) {
                    $ptrmenu[$idx]['menu'] = array();
                }
                $ptr     = &$ptrmenu[$idx];
                $ptrmenu = &$ptr['menu'];
            }
        }

        if ($found_parts != count($parts)) {
            // we searched but didn't find enough parts
            
            unset($ptrmenu);
            unset($ptr);
            $ptrmenu = false;
            $ptr     = false;
        }

        if ($ptr !== false) {
            // modify the menu item with everything except menu items
            foreach($item as $key => $value) {
                if ($key != 'menu') {
                    $ptr[$key] = $value;
                }
            }
        }
    }

    if (isset($item['menu'])) {
        // modify the menu item with menu items
        if ($ptrmenu == false) {
            if (!isset($ptr['menu'])) {
                $ptr['menu'] = array();
                $ptrmenu     = &$ptr['menu'];
            }
        }
        foreach($item['menu'] as $subitem) {
            $idx = false;
            for($i=0; $i < count($ptrmenu); $i++) {
                if ($ptrmenu[$i]['uri'] == $subitem['uri']) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx === false) {
                $ptrmenu[] = $subitem;
            }
            else {
                $ptrmenu[$i] = $subitem;
            }
        }
    }
}

/**
 * Add a menu to pivotx top-menu
 *
 * Creates the menu if it doesn't exist.
 * If top is given as a 'name' (= string) it is converted to a menu-item.
 * If menu-item is not complete, it is automatically filled:
 * - missing 'sortorder', defaults to 2500
 * - missing 'uri', defaults to lowercased name and anything other than alphanumeric characters are stripped
 * - missing 'description', defaults to name
 * - missing 'href', is given the value of the first item (href or uri-variant)
 *
 * @param array &$menu    the menu to edit
 * @param mixed $top      either a menu name or an array of a menu-item
 * @param array $items    the items to add to the top-menu
 */
function addtoTopMenu(&$menu, $top, $items)
{
    if (is_scalar($top)) {
        $top = array(
            'name' => $top,
            'level' => PIVOTX_UL_NORMAL
        );
    }

    if (!isset($top['sortorder'])) {
        $top['sortorder'] = 2500;
    }
    if (!isset($top['uri'])) {
        $top['uri'] = preg_replace('|[^a-z0-9]|','',strtolower($top['name']));
    }
    if (!isset($top['description'])) {
        $top['description'] = $top['name'];
    }
    if (!isset($top['href'])) {
        foreach($items as $item) {
            if (isset($item['href'])) {
                $top['href'] = $item['href'];
                break;
            }
            if (isset($item['uri'])) {
                $top['href'] = '?page='.$item['uri'];
                break;
            }
        }
    }

    $have = false;
    foreach($menu as $sm) {
        if ($sm['uri'] == $top['uri']) {
            $have = true;
            break;
        }
    }

    if (!$have) {
        modifyMenu($menu,false,array('menu'=>array($top)));
    }

    modifyMenu($menu,$top['uri'],array('menu'=>$items));
}

/**
 * Compare two menu items
 *
 * @param array &$a
 * @param array &$b
 * @return
 */
function compareMenuItem(&$a,&$b) {
    if ($a['sortorder'] < $b['sortorder']) {
        return -1;
    }
    if ($a['sortorder'] > $b['sortorder']) {
        return +1;
    }
    return 0;
}

/**
 * Organize a single menu level of the menu structure
 *
 * - sorts the level
 * - applies user-level restrictions
 * - converts uri's to href's
 * - removes 'disabled' items
 * - create 'have_menu' booleans for menu's with subs
 *
 * @param array $in                menu level (and subs)
 & @param array $currentuserlevel
 */
function organizeMenuLevel($in,$currentuserlevel,$path=false,$level=0) {
    $out = array();

    if (!is_array($path)) {
        $path = array();
    }

    foreach($in as $item) {
        if (isset($item['level']) && ($currentuserlevel < $item['level'])) {
            continue;
        }
        if (isset($item['disabled']) && $item['disabled']) {
            continue;
        }

        if (!isset($item['href'])) {
            if ($item['uri'] == 'dashboard') {
                $item['href'] = makeAdminPageLink();
            } else {
                $item['href'] = makeAdminPageLink($item['uri']);
            }
        }

        if (!isset($item['is_divider'])) {
            $item['is_divider'] = false;
        }
        $all_pages = array();
        if (isset($item['uri'])) {
            $all_pages[] = $item['uri'];
        }
        if ((isset($item['menu'])) && (count($item['menu']) > 0)) {
            $item['have_menu'] = true;
            $item['menu'] = organizeMenuLevel($item['menu'],$currentuserlevel,$item['path'],$level+1);
            foreach($item['menu'] as $i2) {
                if (isset($i2['uri'])) {
                    $all_pages[] = $i2['uri'];
                }
                if (isset($i2['all_pages']) && is_array($i2['all_pages'])) {
                    $all_pages = array_merge($all_pages,$i2['all_pages']);
                }
            }
        } else {
            $item['have_menu'] = false;
        }
        
        $item['all_pages'] = $all_pages;

        $out[] = $item;
    }

    $highest_sortorder = 1;
    foreach($out as $item) {
        if ((!isset($item['sortorder'])) && ($item['sortorder'] > $highest_sortorder)) {
            $highest_sortorder = $item['sortorder'];
        }
    }

    for($i=0; $i < count($out); $i++) {
        if (!isset($out[$i]['sortorder'])) {
            $out[$i]['sortorder'] = $highest_sortorder++;
        }
    }

    usort($out, 'compareMenuItem');

    return $out;
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
            'display' => __('Default'),
            'users' => array (
                    0 => $username,
                ),
            'order' => '100',
            'hidden' => -1,
        ),
        1 => array (
            'name' => 'linkdump',
            'display' => __('Linkdump'),
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
    global $dbversion;

    $config = array (
        'allow_comments' => '1',
        'allow_paragraphs' => '0',
        'chmod' => '0644',
        'cookie_length' => '1814400',
        'db_version' => $dbversion,
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
    global $PIVOTX;
    
    // Use the skinny/skinny.theme as the template for the new Weblog.
    $weblog = loadSerialize(dirname(__FILE__)."/templates/skinny/skinny.theme");
    $weblog['language'] = $PIVOTX['config']->get('language');
    $weblog['payoff'] = __('Welcome to your new online presence!');

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
            'chaptername' => __('Pages'),
            'description' => __('Add some pages here, or start a new chapter.'),
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
        'widget-lg' => __('Light gray'),
        'widget-dg' => __('Dark gray'),
        'widget-min' => __('Minimally styled')
    );

    return $styles;

}
?>
