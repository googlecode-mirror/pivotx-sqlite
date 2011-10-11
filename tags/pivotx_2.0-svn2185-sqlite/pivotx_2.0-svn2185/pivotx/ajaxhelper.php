<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: ajaxhelper.php 2170 2009-10-13 19:34:23Z pivotlog $
//
// ---------------------------------------------------------------------------

/**
 * The Pivot ajax helper script. This file contains the several functions
 * to dynamically load data into an existing page.
 */


// For security reasons, this script will only allow calling the following functions:
$allowed_functions = array('getAllTags', 'getTagSuggest', 'getEntries', 'setConfig',
    'addConfig', 'delConfig', 'view', 'save', 'updateWeblog', 'loadSubWeblogs',
    'rebuildIndex', 'rebuildSearchIndex', 'rebuildTagIndex', 'autoComplete', 'getPivotxNews',
    'getTagFeed', 'fileSelector');


// When developing, you can uncomment the following line. Then the script will allow
// $_GET parameters, for easier testing.
$_POST = array_merge($_POST, $_GET);


// Check if the function exists, else we die().
if ( ($_POST['function']=="") || (!in_array($_POST['function'], $allowed_functions)) ) {
    // Can't do that!
    echo "Sorry, but you're not allowed to call '".$_POST['function']."'.";
    die();
} else {

    require_once(dirname(__FILE__).'/lib.php');

    initializePivotX();

    header("status: 200"); 
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/html; charset=utf-8');
    $_POST['function']();

    die();
}



/**
 * Get all tags, to display in 'suggested tags' when editing an entry, or
 * when inserting a tag.
 */
function getAllTags() {

    $minsize=11;
    $maxsize=19;
    $amount = get_default($_POST['amount'], 20);


    $tagcosmos = getTagCosmos($amount);

    foreach($tagcosmos['tags'] as $key => $value)   {

        // Calculate the size, depending on value.
        $nSize = $minsize + ( ($value-$tagcosmos['minvalue']) / ($tagcosmos['maxvalue']-$tagcosmos['minvalue']) ) * ($maxsize - $minsize); 

        // Write the tags, we add events to them using jquery.
        $htmllinks[$key] = sprintf("<a style=\"font-size:%1.1fpx;\" rel=\"tag\" title=\"%s: %s, %s %s\">%s</a>\n",
            $nSize,
            __('Tag'),
            $key,
            $value,
            __('Entries'),
            str_replace("+","_",$key)
        );
    }

    $output = __("Suggestions") . ": ";
    $output .= implode(" ", $htmllinks);
    
    if ($tagcosmos['amount']>$amount){ 
        $output .= sprintf("(<a onclick='getAllTags(%s);'>%s</a>)", $amount*2, __("Show more tags") );
    }
    
    echo $output;


}



function getTagSuggest() {
    
    $tag = safe_string($_GET['q']);
    
    $tagcosmos = getTagCosmos(50, '', $tag);
    
    $output = "";
    
    if (is_array($tagcosmos) && !empty($tagcosmos)) {
        foreach($tagcosmos['tags'] as $key => $value)   {
            $output .= $key."\n";
        }
    }
    
    echo $output;
    
}

/**
 * Get a number of entries, to show in the ajaxy overview screens.
 */
function getEntries() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(1);

    $base_params = array('full'=>false);

    $absmax = $PIVOTX['db']->get_entries_count();
    $show = (isset($_POST['show']) && ($_POST['show']!=0)) ? $_POST['show'] : $PIVOTX['config']->get('overview_entriesperpage') ;
    $offset = (isset($_POST['offset'])) ? $_POST['offset'] : 0 ;

    if (isset($_POST['first'])) { 
        $offset = $absmax - $show; 
    }

    $base_params['show'] = $show;
    $base_params['offset'] = $offset;

    //Sort entries change
    //set initial values for sort values
    $base_params['order'] = 'desc';
    if(isset($_POST['sort'])) {
        $base_params['orderby'] = $_POST['sort'];
        if (isset($_POST['reverse'])) {
            $base_params['order'] = 'asc';
        }
    }

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
    $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);
        
    // Check if we need to 'force' a user filter, based on the
    // 'show_only_own_userlevel' settings..
    if ( $currentuserlevel <= $PIVOTX['config']->get('show_only_own_userlevel') ) {
        $base_params['user'] = $currentuser['username'];
        $force_user = true;
    } else {
        $force_user = false;
    }
    
    if (isset($_POST['filtercat']) && ($_POST['filtercat']!="")) {
        $params = $base_params;
        $params['cats'] = $_POST['filtercat'];
        $overview_arr = $PIVOTX['db']->read_entries($params);
        $filtertitle = str_replace('%name%', $_POST['filtercat'], __('filter on (%name%)') );

    } else if (isset($_POST['filteruser']) && ($_POST['filteruser']!="") && !$force_user) {
        $params = $base_params;
        $params['user'] = $_POST['filteruser'];
        $overview_arr = $PIVOTX['db']->read_entries($params);
        $filtertitle = str_replace('%name%', $_POST['filteruser'], __('filter on (%name%)') );

    } else if ( (isset($_POST['search'])) && (strlen($_POST['search'])>1) ) {
        include_once("modules/module_search.php");
        $overview_arr = searchEntries($_POST['search']);
        $filtertitle = str_replace('%name%', '&hellip;', __('filter on (%name%)') );
        $offset =  0;
        $absmax = $show = 1;

    } else {
        $overview_arr = $PIVOTX['db']->read_entries($base_params);
        $filter = "";
        $filtertitle = str_replace('%name%', '&hellip;', __('filter on (%name%)') );
    }

    if ($offset<($absmax-$show)) {
        $prevlink = sprintf('<a href="javascript:loadEntries(%s,%s)">&laquo; %s</a>&nbsp;&nbsp;',
        ($absmax-$show), $show, __('first'));
        $prevlink .= sprintf('<a href="javascript:loadEntries(%s,%s)">&lsaquo; %s</a>',
        $offset+$show, $show, __('previous'));
    } else {
        $prevlink="&nbsp;";
    }

    if ($offset>0) {
        $next=max(0, $offset-$show);
        $nextlink = sprintf('<a href="javascript:loadEntries(%s,%s)">&rsaquo; %s</a>&nbsp;&nbsp;',
        $next, $show, __('next'));
        $nextlink .= sprintf('<a href="javascript:loadEntries(0,%s)">&raquo; %s</a>',
        $show, __('last'));
    } else {
        $nextlink="&nbsp;";
    }


    // make the html for the paginator..
    $numofpages = (int)ceil(($absmax / abs($show)));
    if ($numofpages > 1) {
        for($i = 0; $i < $numofpages; $i++) {
            $init = $i * abs($show) ;
            $pages_arr[] = sprintf("<option value=\"%s\">%s -  %s</option>", $init, $init, $i+1);
        }

        $title = str_replace('%num%', ceil($offset / abs($show))+1, __('jump to page (%num%)') );
        $pages = "<select name='selectedPage' onchange='loadEntries(this.value,0);'     class='input'>";
        $pages .= sprintf("<option value='' selected='selected'>%s</option>", $title  );
        $pages .= implode ("\n", $pages_arr) ;
        $pages .= "</select>";
    }


    // make the HTML for the filter box
    if ((isset($_POST['filtercat'])) || (isset($_POST['filteruser'])) ) {
        $pages_arr = array( sprintf("<option value=\"javascript:clearFilter();\">%s</option>",
        __('filter off')) );
    } else {
        $pages_arr = array();
    }


    // Add filters for the categories.
    $cats = $PIVOTX['categories']->getCategories();
    $pages_arr[] = "<option value=''>".__('Category')."</option>";
    foreach ($cats as $cat) {
        $pages_arr[] = sprintf("<option value=\"setFilter('cat', '%s');\"> - %s</option>", $cat['name'], $cat['display']);
    }

    // Add filters for users, but only if we didn't 'force' a user. 
    if ($force_user=="") {
        $users = new Users();
        $usernames = $PIVOTX['users']->getUsernames();
        $pages_arr[] = "<option value=''>".__('Author')."</option>";
        foreach ($usernames as $username) {
            $user = $PIVOTX['users']->getUser($username);
            $pages_arr[] = sprintf("<option value=\"setFilter('user', '%s')\"> - %s</option>", $username, $user['nickname']);
        }
    }

    $pages .= "<select name='selectedFilter' onchange='eval(this.form.selectedFilter.value)' class='input'>";
    $pages .= sprintf("<option value='' selected='selected'>%s</option>", $filtertitle );
    $pages .= implode ("\n", $pages_arr) ;
    $pages .= "</select>";

    $searchval = (isset($_POST['search'])) ? $_POST['search'] : 'search';

    $pages .= "<input type='text' name='search' value='".$searchval."' class='input' onfocus='this.select();' onblur='setSearch(this.value);' />";


    printf("<form name='form1' method='post' action='index.php?page=entries'>\n", $myurl);

    echo "<table cellspacing='0' class='tabular' border='0' style='width:99%;'>\n";
    echo "<tr><th colspan='11' style='padding: 0;'>\n";

    echo '<table cellspacing="0" cellpadding="0" class="tabular-nav" border="0" width="100%"><tr>';
    printf('<td>%s&nbsp;</td>', $prevlink);
    printf('<td align="center">%s</td>', $pages);
    printf('<td align="right" class="tabular_nav">&nbsp;%s</td></tr></table>', $nextlink);

    echo "\n</th></tr><tr'><th width='15'>&nbsp;</th>";
    echo '<th width="15">'. __('ID') .'</th>';
    echo '<th width="30"><a href="javascript:setSort(\'status\');">'. __('Status') .'</a></th>';
    echo '<th width="400"><a href="javascript:setSort(\'title\');">'. __('Title') .'</a></th>';
    echo '<th width="70"><a href="javascript:setSort(\'category\');">'. __('Category') .'</a></th>';
    echo '<th width="70"><a href="javascript:setSort(\'user\');">'. __('Author') .'</a></th>';
    echo '<th width="100"><a href="javascript:setSort(\'date\');">'. __('Date') .'</a></th>';
    echo '<th width="40"><a href="javascript:setSort(\'commcount\');">'. __('#C') .'</a></th>';
    echo '<th width="40"><a href="javascript:setSort(\'trackcount\');">'. __('#T') .'</a></th>';
    echo '<th>&nbsp;</th>';
    echo '<th>&nbsp;</th>';
    echo '</tr>';
    //End Sort Entry Changes



    foreach ($overview_arr as $overview_line) {
        getEntriesPrintrow($overview_line);
    }


    $user = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    echo '<tr><th colspan="11"><img src="pics/arrow_ltr.gif" width="29" height="14" border="0" alt="" />';
    echo '<a href="#" onclick=\'rowSelectAll(); return false;\'>'. __('Check All') .'</a> / ';
    echo '<a href="#" onclick=\'rowSelectNone(); return false;\'>'. __('Uncheck All') .'</a>';
    echo '&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;'. __('With the checked entries, do:');
    echo '<select name="action" id="entriesaction" class="input">
    <option value="" selected="selected">'. __('- select an option -') .'</option>';

    if($user['userlevel']>=2) { // Only for advanced users..
        echo '<option value="publish">'. __('Set Status to "publish"') .'</option>';
        echo '<option value="hold" >'. __('Set Status to "hold"') .'</option>';
    }

    if($user['userlevel']>=3) { // Only for admins..
        echo '<option value="delete">'. __('Delete them') .'</option>';
    }
    echo '   </select>';
    echo '<input type="hidden" name="pivotxsession" value="' . $PIVOTX['session']->getCSRF() . '" />';
    echo '&nbsp;&nbsp;<input type="submit" value="'. __('Go!') .'" class="button" onclick="return entriesActionSubmit();" /></th></tr>';


    echo '</table></form>';


}





function getEntriesPrintrow($entry) {
    global $linecount, $PIVOTX;

    // Get the current user and author (user) of entry.
    $user = $PIVOTX['session']->currentUser();   
    $entryuser = $PIVOTX['users']->getUser($entry['user']);

    if($entry['code']=="") { return; }

    if (!isset($linecount)) {
        $linecount=1;
    } else {
        $linecount++;
    }

    if (($linecount % 2)==0) {
        $bg_color="even";
    } else {
        $bg_color="odd";
    }



    printf("    <tr class='%s' id='row-%s'>\n", $bg_color, $entry['code']);

    /**
     * It'd be much better if we could attach the events for selecting the row after page,
     * initialisation, but since the content is dynamically loaded that won't work
     *
     */

    printf("    <td width='1'><input type='checkbox' name='check[%s]' id='check-%s' onclick='rowSelect(%s)' /></td>",
        $entry['code'], $entry['code'], $entry['code']);

    printf("    <td width='1'><small>&#8470;&nbsp;%s</small></td>",
        $entry['code']);


    if ( $entry['status']=='publish' ) {
        $link = makeFilelink($entry, '', '');
        printf("       <td><a href=\"%s\">%s</a>&nbsp;&nbsp;</td>\n", $link, __('Published'));
    } else if ( $entry['status']=='timed' ) {
        printf("        <td>%s&nbsp;&nbsp;</td>\n", __("Timed"));
    } else {
        printf("        <td>%s&nbsp;&nbsp;</td>\n", __("Held"));
    }


    
    if ( $PIVOTX['users']->allowEdit('entry', $entry['user']) ) {
        
        // If current user is allowed to edit this entry.. 
        $editurl=sprintf("index.php?page=entry&amp;uid=%s", $entry['code']);
        printf("        <td class='entriesclip'><div class='clip' style='width:300px'><a href='%s' title='%s'><strong>%s</strong></a> - %s</div></td>\n",
                $editurl,
                __('edit this entry'),
                trimtext($entry['title'], 50, TRUE),
                preg_replace("/(\w)/", "\\1&#173;", $entry['excerpt'])
            );
    } else {
        
        // If current user is not allowed to edit this entry.. 
        printf("        <td class='entriesclip'><div class='clip' style='width:300px'><strong>%s</strong> - %s</div></td>\n",
                trimtext($entry['title'], 50, TRUE),
                preg_replace("/(\w)/", "\\1&#173;", $entry['excerpt'])
            );
        
    }

    // Handle category display
    if (!is_array($entry['category'])) {
        $entry['category'] = array($entry['category']);
    } 
    $mycat = array();
    foreach($entry['category'] as $eachcat) {
        $cat = $PIVOTX['categories']->getCategory($eachcat);
        if (isset($cat['display'])) {
            $mycat[] = $cat['display'];
        } else {
            if ($eachcat == '') {
                $mycat[] = __("(none)");
            } else {
                $mycat[] = $eachcat;
            }
        }
    }
    $mycat = implode(", ", $mycat);
    if (count($entry['category'])>1 ) {
        printf("        <td class='tabular'><acronym title='%s'>%s %s</acronym></td>\n", 
            $mycat, count($entry['category']), __('categories') );
    } else {
        printf("        <td class='tabular'>%s</td>\n", trimtext($mycat,24));
    }
    
    if (isset($entryuser['nickname'])) {
        printf("        <td class='tabular'>%s</td>\n", $entryuser['nickname']);
    } else {
        printf("        <td class='tabular'>%s</td>\n", $entry['user']);
    }
    $date= format_date($entry['date'], "%day%-%month%-'%ye% %hour24%:%minute%");
    printf("        <td class='tabular'>%s</td>\n", $date);


    if ($entry['commcount']>0) {
        // You're only allowed to edit comments for your own entries if you're userlevel 2,
        // or for other entries if you're admin.
        if ( ($user['username']==$entry['user']) || ( $user['userlevel']> $entryuser['userlevel']) || ($user['userlevel']>=3) ) {
            $commurl=sprintf("index.php?page=comments&amp;uid=%s", $entry['code']);
            printf("        <td class='tabular'><a href='%s' title=\"%s\">%s%s</a> </td>\n", 
                $commurl, $entry['cnames'], $entry['commcount'], __('c'));
        } else {
            printf("        <td class='tabular'>%s%s </td>\n", $entry['commcount'], __('c'));
        }
    } else {
        printf("        <td class='tabular'>0%s </td>\n", __('c'));
    }


    if ($entry['trackcount']>0) {

        // You're only allowed to edit comments for your own entries if you're userlevel 2,
        // or for other entries if you're admin.
        if ( ($user['username']==$entry['user']) || ( $user['userlevel']> $entryuser['userlevel']) || ($user['userlevel']>=3) ) {
            $trackurl=sprintf("index.php?page=trackbacks&amp;uid=%s", $entry['code']);
            printf("        <td class='tabular'><a href='%s' title=\"%s\">%s%s</a> </td>\n", 
                $trackurl, $entry['cnames'], $entry['trackcount'], __('t'));
        } else {
            printf("        <td class='tabular'>%s%s </td>\n", $entry['trackcount'], __('t'));
        }
    } else {
        printf("        <td class='tabular'>0%s </td>\n", __('t'));
    }

    if ( $PIVOTX['users']->allowEdit('entry', $entry['user']) ) {
        
        printf('<td width=\'1\'><a href="index.php?page=entry&amp;uid=%s"><img src="pics/page_edit.png" width="16" height="16" alt="edit"></a></td>',
            $entry['code']);
        printf('<td width=\'1\'><a href="#" onclick="return confirmme(\'index.php?page=entries&amp;del=%s\', \'%s\');"><img src="pics/page_delete.png" width="16" height="16" alt="delete"></a></td>',
            $entry['code'],
            htmlentities(__("Are your sure you wish to delete this entry?"), ENT_COMPAT, 'UTF-8')
        );

    } else {
        
        printf("<td width='1'><img src='pics/page_edit_dim.png' width='16' height='16' alt='-'></td>");
        printf("<td width='1'><img src='pics/page_delete_dim.png' width='16' height='16' alt='-'></td>");
        
    }

    printf("    </tr>\n\n");

}



/**
 * Sets a config key/value pair via an AJAX call.
 *
 * @return void
 */
function setConfig() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(3);

    // Check against CSRF exploits..
    $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

    // If we come from 'advanced config' we need to unentify the returned value.
    if ($_POST['unentify']==1) {
         $_POST['value'] = @html_entity_decode($_POST['value'], ENT_COMPAT, 'UTF-8');
    }

    // If the id contains '[]' we remove it, since those were added by pivotX to 
    // allow for multiple select, but should be stored without.
    $_POST['id'] = str_replace('[]', '', $_POST['id']);

    if ($_POST['id']!="") {
        $PIVOTX['config']->set($_POST['id'], $_POST['value']);

        $PIVOTX['events']->add('edit_config', safe_string($_POST['id']), safe_string($_POST['value']));
    }

    echo htmlentities($_POST['value'], ENT_COMPAT, "UTF-8");


}



/**
 * Adds a config key/value pair via an AJAX call.
 *
 * @return void
 */
function addConfig() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(3);

    // Check against CSRF exploits..
    $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

    if ($_POST['key']!="") {
        $PIVOTX['config']->set($_POST['key'], $_POST['value']);
    
        $PIVOTX['events']->add('edit_config', safe_string($_POST['key']), safe_string($_POST['value']));
        
    }

    echo "OK!";


}



/**
 * Deletes a config key via an AJAX call.
 *
 * @return void
 */
function delConfig() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(3);

    // Check against CSRF exploits..
    $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

    if ($_POST['key']!="") {
        $key = urldecode($_POST['key']);
        $PIVOTX['config']->del($key);
        
        $PIVOTX['events']->add('delete_config', safe_string($_POST['key']));

    }

    echo "OK!";


}


/**
 * Show / Edit a file in the ajaxy editor..
 *
 */
function view() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(3);

    // TODO: Check if the file is writable before showing the editor.

    // TODO: make sure we don't try to pass stupid things here!!
    $filename = base64_decode($_POST['basedir']) . "/" . $_POST['file'];

    if ($contents = load_serialize($filename)) {

        // Get the output in a buffer..
        ob_start();
        print_r($contents);
        $contents = ob_get_contents();
        ob_end_clean();

        echo "<pre>\n";
        echo htmlentities($contents, ENT_QUOTES, "UTF-8");
        echo "</pre>\n";

    } else {

        $extension = getextension($filename);

        $contents = implode("", file( $filename ));

        $contents = preg_replace('/<textarea/i','<*textarea', $contents);
        $contents = preg_replace('/<\/textarea/i','<*/textarea', $contents);


        echo "<form id='editor' class='formclass' method='post' action='' style='border: 0px;'>";
        echo "<input type='hidden' value='".$_POST['basedir']."' id='editBasedir'>";
        echo "<input type='hidden' value='".$_POST['file']."' id='editFile'>";
        echo "<textarea style='width: 759px; border: 1px inset #999; height: 380px;' id='editContents' name='editContents' class='Editor' >";
        echo htmlentities($contents, ENT_QUOTES, 'UTF-8');
        echo "</textarea>";

        if (in_array($extension, array('html','htm','tpl','xml','css'))) {
            echo '<script language="javascript" type="text/javascript">' . "\n";
            echo 'jQuery(function($) {' . "\n";
            echo '  $("#editContents").markItUp(markituphtml);' . "\n";
            echo '});' . "\n";
            echo '</script>' . "\n";
        } else {
            echo '<script language="javascript" type="text/javascript">' . "\n";
            echo 'jQuery(function($) {' . "\n";
            echo '  $("#editContents").css("height", "384px");' . "\n";
            echo '});' . "\n";
            echo '</script>' . "\n";
        }


        printf('<p class="buttons" style="margin: 0 0 6px 0; clear: both;"><a href="#" onclick="saveEdit();"><img src="pics/accept.png" alt="" />%s</a>',
            __('Save') );
        printf('<a href="#" onclick="saveEditAndContinue();"><img src="pics/accept.png" alt="" />%s</a>',
            __('Save and continue editing'));
        printf('<a href="#" onclick="closeEdit();" class="negative" style="margin-left: 20px;"><img src="pics/delete.png" alt="" />%s</a></p>',
            __('Cancel'));

        if($PIVOTX['config']->get('smarty_cache') || $PIVOTX['config']->get('minify_frontend')) {
            $msg = __("You have Caching and/or Minify enabled. If your changes do not show up immediately, %click here% and disable Caching and Minify while you're working on your site.");
            $msg = preg_replace('/%(.*)%/i', "<a href='index.php?page=configuration#section-1'>\\1</a>", $msg);
            echo "\n\n<p class='small' style='width: 500px;clear: both;'>" . $msg . "</p>\n";
        }

        echo "</form>";

    }

}


/**
 * Save an edited file in the ajaxy editor..
 *
 */
function save() {
    global $PIVOTX;


    $PIVOTX['session']->minLevel(3);

    // Check against CSRF exploits..
    $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

    // TODO: make sure we don't try to pass stupid things here!!
    $filename = base64_decode($_POST['basedir']) . $_POST['file'];

    if (is_writable($filename)) {

        $contents = $_POST['contents'];
        $contents = preg_replace('/<\*textarea/i','<textarea', $contents);
        $contents = preg_replace('/<\*\/textarea/i','</textarea', $contents);

        if (!$handle = fopen($filename, 'wb')) {
            echo "Cannot open file ".$_POST['file'];
            exit;
        }

        // Write $somecontent to our opened file.
        if (fwrite($handle, $contents) === FALSE) {
            echo "Cannot write to file ".$_POST['file'];
            exit;
        }

        echo "Wrote contents to file ".$_POST['file'];

        fclose($handle);

        $PIVOTX['events']->add('save_file', "", safe_string($_POST['file'], false, "/"));
        $PIVOTX['template']->clear_cache($_POST['file']);

    } else {
        echo "The file ".$_POST['file']." is not writable";
    }

}


/**
 * Update a weblog's settings..
 *
 */
function updateWeblog() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(3);

    // Check against CSRF exploits..
    $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

    $PIVOTX['weblogs']->set($_POST['weblog'], $_POST['key'], $_POST['value']);

    $PIVOTX['events']->add('edit_weblog', "", safe_string($_POST['weblog']));

    echo "ok";
}


/**
 * Dynamically load the settings screen for 'subweblogs'. We need to do this
 * dynamically, because the settings are dependant on what it set for the
 * 'frontpage' template.
 *
 */
function loadSubWeblogs() {

    $form = getWeblogForm3($_POST['weblog']);

    $html = $form->fetch();

    echo $html;

}


/**
 * Rebuild the entry index
 */
function rebuildIndex() {
    global $PIVOTX, $output;

    $PIVOTX['session']->minLevel(1);

    $output = "";

    $PIVOTX['db']->generate_index();

    $output .= "<br />\n<b>".str_replace("%num%", timetaken(), __('Finished! Generating index took %num% seconds'))."</b><br />\n";

    echo $output;

}



/**
 * Rebuild the tag index
 */
function rebuildTagIndex() {
    global $output, $PIVOTX;

    $PIVOTX['session']->minLevel(1);

    $output = "";


    // initialise the threshold.. Initially it's set to 10 * the rebuild_threshold,
    // roughly assuming we index 10 entries per second.
    if ($PIVOTX['config']->get('rebuild_threshold')>4) {
        $chunksize = (10 * $PIVOTX['config']->get('rebuild_threshold'));
    } else {
        $chunksize = 280;
    }

    @set_time_limit(0);

    include_once("modules/module_tags.php");

    $start = (isset($_POST['start'])) ? $_POST['start'] : 0;
    $stop = $start + $chunksize;
    $time = (isset($_POST['time'])) ? $_POST['time'] : 0;

    if($start==0) { $PIVOTX['db']->clearIndex('tags');  }

    $continue = writeTagIndex($start, $stop, $time);

    $time = (isset($_POST['time'])) ? $_POST['time'] : 0;
    $time += timetaken('int');

    if($continue) {

        $myurl = sprintf("ajaxhelper.php?function=rebuildTagIndex&start=%s&time=%s",
        $stop, $time);
        header("location: $myurl");

    } else {
        $output .= "<br />\n<b>".str_replace("%num%", $time, __('Finished! Generating index took %num% seconds'))."</b><br />\n";
    }

    echo $output;

}

/**
 * Rebuild the search index
 */
function rebuildSearchIndex() {
    global $output, $PIVOTX;

    $PIVOTX['session']->minLevel(1);

    $output = "";

    // initialise the threshold.. Initially it's set to 10 * the rebuild_threshold,
    // roughly assuming we index 10 entries per second.
    if ($PIVOTX['config']->get('rebuild_threshold')>4) {
        $chunksize = (10 * $PIVOTX['config']->get('rebuild_threshold'));
    } else {
        $chunksize = 280;
    }

    @set_time_limit(0);

    include_once("modules/module_search.php");

    $start = (isset($_POST['start'])) ? $_POST['start'] : 0;
    $stop = $start + $chunksize;
    $time = (isset($_POST['time'])) ? $_POST['time'] : 0;

    if($start==0) { $PIVOTX['db']->clearIndex('search');  }

    $continue = startIndex($start, $stop, $time);

    writeIndex(FALSE);

    $time = (isset($_POST['time'])) ? $_POST['time'] : 0;
    $time += timetaken('int');

    if($continue) {

        $myurl = sprintf("ajaxhelper.php?function=rebuildSearchIndex&start=%s&time=%s",
        $stop, $time);
        header("location: $myurl");

    } else {
        $output .= "<br />\n<b>".str_replace("%num%", $time, __('Finished! Generating index took %num% seconds'))."</b><br />\n";
    }


    echo $output;

}


/**
 * Used to get the filenames when using the autocomplete function in the image popup/insert
 * dialog window.
 *
 *
 */
function autoComplete() {
    global $PIVOTX;

    $uploadpath = $PIVOTX['paths']['upload_base_path'];

    $files = autoCompleteFindFiles($uploadpath, '', $_GET['q']);

    sort($files);

    foreach ($files as $file) {
        $imagesize = getimagesize($uploadpath."/".$file);
        $filesize = formatFilesize(filesize($uploadpath."/".$file));
        printf("%s|%s &times; %s, %s.|%s\n", $file, $imagesize[0], $imagesize[1], $filesize, trimtext($file, 44));
    }

}

/**
 * Helper function for autoComplete()
 *
 * @param string $path
 * @param string $additional_path
 * @param string $match
 * @return array
 */
function autoCompleteFindFiles($path, $additional_path, $match) {

    $allowed = array("gif", "jpg", "jpeg", "png");
    $path = fixPathSlash($path);

    $files = array();

    $dir = dir($path);
    while (false !== ($entry = $dir->read())) {
        $entries[] = $entry;
    }
    $dir->close();

    foreach ($entries as $entry) {
        $fullname = $path . $entry;
        if ($entry != '.' && $entry != '..' && is_dir($fullname)) {

            // Recursively parse the folder below it.
            $files = array_merge($files, autoCompleteFindFiles($fullname, $additional_path.$entry."/", $match));

        } else if (is_file($fullname) && (strpos($fullname, $match)!==false) &&
                (in_array(getextension($entry), $allowed)) && (strpos($fullname, ".thumb.")===false) ) {

            // Add the file to our array of matches.
            $files[] = $additional_path.$entry;

        }
    }

    return $files;

}

/**
 * Ajax helper function to get the latest news from PivotX.net.
 *
 * You can change the URL by setting the 'notifier_url' to a valid URL in
 * Advanced Configuration.
 *
 * @return string
 *
 */
function getPivotxNews() {
    global $build, $PIVOTX;

    // do not display the news if SafeMode is enabled.
    if($PIVOTX['extensions']->safemode) {
        echo "<p>" . __("The latest PivotX news is not available as long as safemode is enabled.") . "</p>";
        die();
    }

    // Setting the labels..
    $readon = __("Read on") . " &raquo;";
    $showmore = __("Show more items");

    // Get the latest Pivot news, fresh from the website.

    include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

    $notifier_request = base64_encode(sprintf("%s|%s|%s|%s", $_SERVER['SERVER_NAME'], phpversion(), 
        $PIVOTX['db']->db_type, strip_tags($build)));
    $notifier_url = get_default($PIVOTX['config']->get('notifier_url'), "http://pivotx.net/notifier.xml" ) . 
        "?" . $notifier_request;

    $rss = fetch_rss($notifier_url);

    $news = "";

    if (count($rss->items)>0) {

        // Slice it, so no more than 4 items will be shown.
        $rss->items = array_slice($rss->items, 0, 4);
        
        $count=0;
    
        foreach($rss->items as $item) {
            $news .= sprintf("<h3>%s</h3> <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>\n",
                $item['title'],
                $item['summary'],
                $item['link'],
                $readon
            );

            if (($count++)==1) {
                $news .= "<p id='newsmoreclick'><a onclick='moreNews();'>$showmore</a></p>\n<div id='newsmore'>";
            }

        }

        echo $news;

    } else {
        debug("<p>Oops! I'm afraid I couldn't read the News feed.</p>");
        echo "<p>" . __("Oops! I'm afraid I couldn't read the News feed.") . "</p>";
        debug(magpie_error());
    }

    echo "</div>";

    echo "--split--";

    // If people don't want to see the forum posts, we can end here..
    if ($PIVOTX['config']->get('hide_forumposts')) {
        return;
    }
    $notifier_url = "http://forum.pivotx.net/feed.xml";

    $rss = fetch_rss($notifier_url);

    $news = "";

    if (count($rss->items)>0) {

        // Slice it, so no more than 8 items will be shown.
        $rss->items = array_slice($rss->items, 0, 8);

        $count = 0;

        foreach($rss->items as $item) {
            
            // Get the description, and remove HTML from it..
            $author = $item['dc']['creator'];
            $description = str_replace("\n", " ", str_replace("<br", " <br", $item['summary']));
            $description = strip_tags($author . ": " .$description);
            $description = trimtext($description, 82);
            
            
            $news .= sprintf("<h3>%s</h3> <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>\n",
                $item['title'],
                $description,
                $item['link'],
                $readon
            );


            if (($count++)==2) {
                $news .= "<p id='forumpostsmoreclick'><a onclick='moreForumPosts();'>$showmore</a></p>\n<div id='forumpostsmore'>";
            }


        }

        echo $news;

    } else {
        debug("<p>Oops! I'm afraid I couldn't read the Forum feed.</p>");
        echo "<p>" . __("Oops! I'm afraid I couldn't read the Forum feed.") . "</p>";
        debug(magpie_error());
    }
    
    //echo "Time taken: " . timetaken('int');
    //echo "<br />Mem taken: " . round(memory_get_usage()/1024) . " kb.";

}


/**
 * Fetches tag-information from one of the various social bookmarking websites.
 */
function getTagFeed() {
    global $PIVOTX;
   
    if(isset($_POST["type"]) && isset ($_POST["tag"])) {
    
        $type = safe_string($_POST["type"]);
        $tag = safe_string($_POST["tag"]);
        
        $amount = get_default($PIVOTX['config']->get('tag_fetcher_amount'), 8);
        
        switch($_POST["type"]) {
            case "technorati":
                _getTagFeedHelper('http://feeds.technorati.com/feed/posts/tag/'.str_replace(" ", "+", $tag), 'technorati.com', $tag);
                break;
        
            case "furl":
                _getTagFeedHelper('http://www.furl.net/members/rssPopular.xml?days=6&topic='.str_replace(" ", "+", $tag), 'furl.com', $tag);
                break;
        
            case "tagzania":
                _getTagFeedHelper('http://www.tagzania.com/rss/tag/'.str_replace(" ", "+", $tag), 'tagzania.com', $tag);
                break;
        
            // case "feedmarker":
            //    _getTagFeedHelper('http://www.feedmarker.com/rss/tags/'.str_replace(" ", "+", $tag), 'feedmarker.com', $tag);
            //    break;
        
            case "magnolia":
                _getTagFeedHelper('http://ma.gnolia.com/rss/full/tags/'.str_replace(" ", "+", $tag), 'ma.gnolia.com', $tag);
                break;
        
            // case "feedster":
            //    _getTagFeedHelper('http://feedster.com/search.php?q='.$tag.'&sort=relevance&ie=UTF-8&hl=&content=full&type=rss&limit='.
            //    $amount .'&db='.str_replace(" ", "+", $tag), 'feedster.com', $tag);
            //    break;
        
            case "icerocket":
                _getTagFeedHelper('http://www.icerocket.com/search?tab=blog&q='.str_replace(" ", "+", $tag).'&rss=1', 'icerocket.com', $tag);
                break;
        
            case "google":
                _getTagFeedHelper('http://blogsearch.google.com/blogsearch_feeds?hl=en&q='.str_replace(" ", "+", $tag).'&btnG=Search+Blogs&num='.
                $amount .'&output=rss', 'blogsearch.google.com', $tag);
                break;
        
            // case "shadows":
            //    _getTagFeedHelper('http://www.shadows.com/rss/tag/'.str_replace(" ", "+", $tag), 'shadows.com', $tag);
            //    break;
            
            case "delicious":
                _getTagFeedHelper('http://feeds.delicious.com/rss/tag/'.str_replace(" ", "+", $tag), 'del.icio.us', $tag);
                break;
        
            case "43things":
                _getTagFeedHelper('http://www.43things.com/rss/goals/tag?name='.str_replace(" ", "+", $tag), '43things.com', $tag); break;
        }
    }
}


function _getTagFeedHelper($feedurl, $feedname, $tag) {
    global $PIVOTX;

    $amount = get_default($PIVOTX['config']->get('tag_fetcher_amount'), 8);

    include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

    $rss = fetch_rss($feedurl);

    $output = "";

    if (count($rss->items)>0) {

        // Slice it, so no more than '$amount' items will be shown.
        $rss->items = array_slice($rss->items, 0, $amount);

        foreach($rss->items as $item) {
            $output .= sprintf("\n<li><a href='%s'>%s</a><br /><small>%s</small></li> \n", 
                // <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>
                $item['link'],
                $item['title'],
                trimtext($item['summary'], 200),
                $readon
            );

        }

    } else {
        debug("<p>Oops! I'm afraid I couldn't read the Tag feed.</p>");
        echo "<p>" . __("Oops! I'm afraid I couldn't read Tag feed.") . "</p>";
        debug(magpie_error());
    }

    
    
    
    $output = @html_entity_decode($output, ENT_COMPAT, 'UTF-8');
	if($output == '')  {
		if($feedname == 'delicious')  {
			$feedname = 'del.icio.us';
		}
		$output = __('Nothing on') . ' <strong>' . $feedname . '</strong> ' . __('for') .
            ' \'<strong>' . $tag . '</strong>\'';
	} else  {
		$output = __('Latest on') . ' <strong>' . $feedname . '</strong> ' . __('for') .
            ' \'<strong>' . $tag . '</strong>\':<ul class="taggeratilist">' . $output . '</ul>';
	}
	echo $output;
    
}


/**
 * Ajax helper function to facilitate the selection of files from the images/
 * folder.
 *
 */
function fileSelector() {
    global $PIVOTX;
    
    $PIVOTX['session']->minLevel(1);
    
    $path = $PIVOTX['paths']['upload_base_path'];
    $url = $PIVOTX['paths']['upload_base_url'];
    
    if (empty($path) || empty($url) ) {
        echo "Can't continue: paths not set..";
        die();
    }
    
    $breadcrumbs=array("<a href='' onclick=\"fileSelectorChangefolder('')\">".basename($path)."</a>");
    
    if (!empty($_GET['folder'])) {
        $folder = fixPath($_GET['folder'])."/";
        $path .= $folder;
        $url .= $folder;
        
        
        $incrementalpath="";
        foreach(explode("/", $folder) as $item) {
            if (!empty($item)) {
                $incrementalpath = $incrementalpath . $item . "/";
                $breadcrumbs[] = sprintf("<a href='' onclick=\"fileSelectorChangefolder('%s')\">%s</a>", $incrementalpath, $item);
            }    
        }

    }


    $breadcrumbs = implode(" &raquo; ", $breadcrumbs);
   
    
    $files = array();
    $folders = array();
        
    $d = dir($path);

    while (false !== ($filename = $d->read())) {
    
        if (strpos($filename, '.thumb.')!==false || strpos($filename, '._')!==false || $filename==".DS_Store" || $filename=="Thumbs.db" || $filename=="." || $filename==".."  ) {
            // Skip this one..
            continue;
        }        
    
        
        if (is_file($path.$filename)) {
            $files[$filename]['link'] = $url.urlencode($filename);
            $files[$filename]['name'] = trimtext($filename,50);
            
            $ext = strtolower(getextension($filename));
            $files[$filename]['ext'] = $ext;
            $files[$filename]['bytesize'] = filesize($path."/".$filename);
            $files[$filename]['size'] = formatFilesize($files[$filename]['bytesize']);
            if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
                $dim = getimagesize($path."/".$filename);
                $files[$filename]['dimension'] = sprintf('%s &#215; %s', $dim[0], $dim[1]);
            }

            $files[$filename]['path'] = $folder.$filename;

            
        }
        
        if (is_dir($path.$filename)) {
            
            $folders[$filename] = array(
                'link'=> $url.urlencode($filename),
                'name'=> trimtext($filename,50),
                'path'=> $folder.$filename
                );
            

            
        }        
        
    }
    $d->close();

    ksort($folders);
    ksort($files);
    
    echo "<div id='fileselector'>";
    
    printf("<p><strong>%s:</strong> %s </p>", __("Current path"), $breadcrumbs);
    
    foreach($folders as $folder) {    
        printf("<div class='folder'><a href='#' onclick=\"fileSelectorChangefolder('%s'); return false;\">%s</a></div>",
            $folder['path'], $folder['name']);
    }

    foreach($files as $file) {    
        printf("<div class='file'><a href='#' onclick=\"fileSelectorChoose('%s'); return false;\">%s</a> <span>(%s%s)</span></div>",
                $file['path'],   
                $file['name'],
                $file['size'],
                (!empty($file['dimension'])) ? " - ".$file['dimension']." px" : ""
              );
    }

    echo "</div>";
    
    //echo "<pre>\n"; print_r($folders); echo "</pre>";
    //echo "<pre>\n"; print_r($files); echo "</pre>";
    
}

?>
