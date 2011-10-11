<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_smarty.php 2185 2009-10-22 15:09:06Z pivotlog $
//
// ---------------------------------------------------------------------------

// Lamer protection
$currentfile = basename(__FILE__);
require_once(dirname(dirname(__FILE__))."/lamer_protection.php");

// Include Smarty libraries
require_once($pivotx_path."modules/smarty/Smarty.class.php");


/**
 * For now we should keep all tags in one file, less clutter.
 *
 */

global $PIVOTX;

$PIVOTX['template'] = new Smarty;

if(defined('PIVOTX_INWEBLOG')) {
    $PIVOTX['template']->template_dir   = $pivotx_path.'templates/';
} else {
    $PIVOTX['template']->template_dir   = $pivotx_path.'templates_internal/';
}

$PIVOTX['template']->secure_dir = array(
    $PIVOTX['template']->template_dir,
    $pivotx_path.'extensions/'
); 

// When we get here, the $config object is not yet defined. So, we're going
// to set $smarty to just do everything, and perhaps modify settings as caching,
// compiling and directories later on, before it actually starts parsing.
$PIVOTX['template']->caching = false;
$PIVOTX['template']->force_compile = true;

$PIVOTX['template']->compile_dir    = $pivotx_path.'db/cache/';
//$PIVOTX['template']->config_dir   = $pivotx_path.'includes/smarty/configs/';
$PIVOTX['template']->cache_dir      = $pivotx_path.'db/cache/';
$PIVOTX['template']->left_delimiter = "[[";
$PIVOTX['template']->right_delimiter = "]]";

$PIVOTX['template']->debugging = (false && $PIVOTX['config']->get('debug'));
$PIVOTX['template']->cache_lifetime = 900; // 15 minutes for now.


/**
 * Register the resource name "db". This allows recursive parsing of templates..
 */
$PIVOTX['template']->register_resource("db", array("db_get_template", "db_get_timestamp", "db_get_secure", "db_get_trusted"));

/**
 * Set our own Smarty Cache Handlers. This allows us to:
 * - Have better control over when we purge items from the cache
 * - Execute our after_parse hooks, and cache the results
 */
$PIVOTX['template']->cache_handler_func = 'pivotx_cache_handler'; 

// Smarty functions..
$PIVOTX['template']->register_function('archive_list', 'smarty_archive_list');
$PIVOTX['template']->register_function('atombutton', 'smarty_atombutton');
$PIVOTX['template']->register_function('backtrace', 'smarty_backtrace');
$PIVOTX['template']->register_function('body', 'smarty_body');
$PIVOTX['template']->register_function('category', 'smarty_category');
$PIVOTX['template']->register_function('category_link', 'smarty_category_link');
$PIVOTX['template']->register_function('category_list', 'smarty_category_list');
$PIVOTX['template']->register_function('chaptername', 'smarty_chaptername');
$PIVOTX['template']->register_function('charset', 'smarty_charset');
$PIVOTX['template']->register_function('commcount', 'smarty_commcount');
$PIVOTX['template']->register_function('commentform', 'smarty_commentform');
$PIVOTX['template']->register_function('commentlink', 'smarty_commentlink');
$PIVOTX['template']->register_function('content', 'smarty_content');
$PIVOTX['template']->register_function('count', 'smarty_count'); 
$PIVOTX['template']->register_function('date', 'smarty_date');
$PIVOTX['template']->register_function('download', 'smarty_download');
$PIVOTX['template']->register_function('editlink', 'smarty_editlink');
$PIVOTX['template']->register_function('emotpopup', 'smarty_emotpopup');
$PIVOTX['template']->register_function('explode', 'smarty_explode');
$PIVOTX['template']->register_function('extensions_dir', 'smarty_extensions_dir');
$PIVOTX['template']->register_function('extensions_url', 'smarty_extensions_url');
$PIVOTX['template']->register_function('filedescription', 'smarty_filedescription');
$PIVOTX['template']->register_function('getpagelist', 'smarty_getpagelist');
$PIVOTX['template']->register_function('getpage', 'smarty_getpage');
$PIVOTX['template']->register_function('home', 'smarty_home');
$PIVOTX['template']->register_function('hook', 'smarty_hook');
$PIVOTX['template']->register_function('image', 'smarty_image');
$PIVOTX['template']->register_function('implode', 'smarty_implode');
$PIVOTX['template']->register_function('introduction', 'smarty_introduction');
$PIVOTX['template']->register_function('lang', 'smarty_lang');
$PIVOTX['template']->register_function('latest_comments', 'smarty_latest_comments');
$PIVOTX['template']->register_function('link', 'smarty_link');
$PIVOTX['template']->register_function('link_list', 'smarty_link_list');
$PIVOTX['template']->register_function('log_dir', 'smarty_log_dir');
$PIVOTX['template']->register_function('log_url', 'smarty_log_url');
$PIVOTX['template']->register_function('more', 'smarty_more');
$PIVOTX['template']->register_function('nextentry', 'smarty_nextentry');
$PIVOTX['template']->register_function('pagelist', 'smarty_pagelist');
$PIVOTX['template']->register_function('paging', 'smarty_paging');
$PIVOTX['template']->register_function('permalink', 'smarty_permalink');
$PIVOTX['template']->register_function('pivotxbutton', 'smarty_pivotxbutton');
$PIVOTX['template']->register_function('pivotx_dir', 'smarty_pivotx_dir');
$PIVOTX['template']->register_function('pivotx_path', 'smarty_pivotx_path');
$PIVOTX['template']->register_function('pivotx_url', 'smarty_pivotx_url');
$PIVOTX['template']->register_function('popup', 'smarty_popup');
$PIVOTX['template']->register_function('previousentry', 'smarty_previousentry');
$PIVOTX['template']->register_function('print_r', 'smarty_print_r');
$PIVOTX['template']->register_function('register_as_visitor_link', 'smarty_register_as_visitor_link');
$PIVOTX['template']->register_function('registered', 'smarty_registered');
$PIVOTX['template']->register_function('rssbutton', 'smarty_rssbutton');
$PIVOTX['template']->register_function('remember', 'smarty_remember');
$PIVOTX['template']->register_function('resetpage', 'smarty_resetpage');
$PIVOTX['template']->register_function('search', 'smarty_search');
$PIVOTX['template']->register_function('self', 'smarty_self');
$PIVOTX['template']->register_function('sitename', 'smarty_sitename');
$PIVOTX['template']->register_function('spamquiz', 'smarty_spamquiz');
$PIVOTX['template']->register_function('subtitle', 'smarty_subtitle');
$PIVOTX['template']->register_function('tagcloud', 'smarty_tagcloud');
$PIVOTX['template']->register_function('tags', 'smarty_tags');
$PIVOTX['template']->register_function('tt', 'smarty_tt');
$PIVOTX['template']->register_function('template_dir', 'smarty_template_dir');
$PIVOTX['template']->register_function('textilepopup', 'smarty_textilepopup');
$PIVOTX['template']->register_function('title', 'smarty_title');
$PIVOTX['template']->register_function('trackbacklink', 'smarty_trackbacklink');
$PIVOTX['template']->register_function('tracklink', 'smarty_tracklink');
$PIVOTX['template']->register_function('trackcount', 'smarty_trackcount');
$PIVOTX['template']->register_function('tracknames', 'smarty_tracknames');
$PIVOTX['template']->register_function('upload_dir', 'smarty_upload_dir');
$PIVOTX['template']->register_function('user', 'smarty_user');
$PIVOTX['template']->register_function('via', 'smarty_via');
$PIVOTX['template']->register_function('weblog_list', 'smarty_weblog_list');
$PIVOTX['template']->register_function('weblogid', 'smarty_weblogid');
$PIVOTX['template']->register_function('weblogsubtitle', 'smarty_weblogsubtitle');
$PIVOTX['template']->register_function('weblogtitle', 'smarty_weblogtitle');
$PIVOTX['template']->register_function('widgets', 'smarty_widgets');
$PIVOTX['template']->register_function('yesno', 'smarty_yesno');

// Block functions..
$PIVOTX['template']->register_block('button', 'smarty_button');
$PIVOTX['template']->register_block('comments', 'smarty_comments');
$PIVOTX['template']->register_block('trackbacks', 'smarty_trackbacks');
$PIVOTX['template']->register_block('feed', 'smarty_feed');
$PIVOTX['template']->register_block('subweblog', 'smarty_weblog');
$PIVOTX['template']->register_block('weblog', 'smarty_weblog');

// Some tags that are deprecated, but kept around for backwards compatibility
$PIVOTX['template']->register_function('entrylink', 'smarty_entrylink'); // smarty_entrylink is deprecated..
$PIVOTX['template']->register_function('pagelink', 'smarty_link');  // smarty_pagelink is deprecated..
$PIVOTX['template']->register_function('singlepermalink', 'smarty_link'); // smarty_singlepermalink is deprecated
$PIVOTX['template']->register_function('weblogname', 'smarty_weblogtitle'); // keep [[ weblogname ]] for compatibility..
$PIVOTX['template']->register_function('last_comments', 'smarty_latest_comments');




/**
 * Inserts a linked list to the archives.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_archive_list($params, &$smarty) {
    global $Archive_array, $PIVOTX;

    $params = clean_params($params);


    $Current_weblog = get_default($params['weblog'], $PIVOTX['weblogs']->getCurrent());

    $format = get_default($params['format'], "<a href=\"%url%\">%st_day% %st_monname% - %en_day% %en_monname% %st_year% </a><br />");
    $unit = get_default($params['unit'], "month");
    $separator = get_default($params['separator'], "");

    // if not yet done, load / make the array of archive filenames (together
    // with at least one date)
    if (!isset($Archive_array)) { makeArchiveArray(false, $unit); }

    $output = array();

    if( is_array( $Archive_array[$Current_weblog] )) {
        
        // maybe flip and reverse it.
        if($params['order'] == 'descending' || $params['order'] == 'desc') {
            $mylist = $Archive_array[$Current_weblog];
        } else {
            $mylist = array_reverse($Archive_array[$Current_weblog]);
        }

        // Iterate over the list, formatting output as we go.
        foreach($mylist as $date) {
            $filelink = makeArchiveLink($date, $unit, $params['weblog']);

            // fix the rest of the string..
            list($start_date, $stop_date) = getdaterange($date, $unit);
            $this_output = format_date_range($start_date, $stop_date, $format);

            $this_output = str_replace("%url%" , $filelink, $this_output);

            $output[] = "\n".$this_output;
        }
    }



    return implode($separator, $output);

}




/**
 * Insert a button with a link to the Atom XML feed.
 *
 * @return string
 */
function smarty_atombutton() {
    global $PIVOTX ;

    // if we've disabled the Atom feed for this weblog, return nothing.
    if ($PIVOTX['weblogs']->get('', 'rss')!=1) {
        return "";
    }

    // else we continue as usual..

    $filename = makeFeedLink("atom");

    $image    = $PIVOTX['paths']['pivotx_url'].'pics/atombutton.png' ;
    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/atombutton.png' ) ;
    $alttext  = __('XML: Atom Feed') ;

    $output   = '<a href="'.$filename.'" title="'.$alttext.'" rel="nofollow" class="badge">';
    $output  .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'"' ;
    $output  .= ' alt="'.$alttext.'" class="badge" longdesc="'.$filename.'" /></a>' ;

    return $output;
}



/**
 * Smarty tag for [[ body ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_body($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    if (!$params['noanchor']) {
        $body = '<a id="body"></a>';
    }

    $body .= parse_intro_or_body($vars['body'], $params['strip'], $vars['convert_lb']);


    return $body;

}


/**
 * Creates a button with the given text.
 *
 * @param array $params
 * @param string $text
 * @param object $smarty
 * @return string
 */
function smarty_button($params, $text, &$smarty) {

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }
    
    $params = clean_params($params);

    if ($params['icon']!="") {
        $img = "<img src=\"./pics/".$params['icon']."\" alt=\"\" />\n";
    } else {
        $img = "";
    }


    if ($params['tabindex']!="") {
        $tabindex = " tabindex=\"".$params['tabindex']."\"";
    } else {
        $tabindex = "";
    }

    if ($text!="") {
        $label = $text;
    } else {
        $label = __("ok");
    }

    if ($params['link']!="") {
        $link = str_replace("&", "&amp;", $params['link']);
    } else {
        $link = "#";
    }

    if ($params['class']!="") {
        $class = " class=\"".$params['class']."\"";
    } else {
        $class = "";
    }



    $output = sprintf("
        <a href=\"%s\"%s%s>
            %s%s
        </a>",
        $link,
        $tabindex,
        $class,
        $img,
        $label
    );

    return $output;

}



/**
 * List the categories of the current entry. Optionally links them to
 * the matching pages with entries in that category.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];

    $mycats = $entry['category'];

    // See if we need to ignore some categories..
    if (!empty($params['ignore'])) {
        $ignore = explode(",", $params['ignore']);
        $ignore = array_map("trim", $ignore);    
    } else {
        $ignore = array();
    }
    
    // See if we need to list just some of the categories..
    if (!empty($params['only'])) {
        $only = explode(",", $params['only']);
        $only = array_map("trim", $only);
    } else {
        $only = array();
    }    
    
    if (is_array($mycats)) {

        $output = array();

        foreach($mycats as $key=>$value) {
            
            $thiscat = $PIVOTX['categories']->getCategory($value);

            // Skip it, if it's in $ignore..
            if (in_array($thiscat['name'], $ignore) || in_array($thiscat['display'], $ignore) ) {
                continue;
            }
            
            // Skip it, if it's not in $only
            if (!in_array($thiscat['name'], $only) && !empty($only)) {
                continue;
            }
            
            // Skip it, if $thiscat['display'] is empty (likely, it has since been deleted)
            if (empty($thiscat['display'])){
                continue;
            }
            
            if (!$params['link']) {                
                $output[] = $thiscat['display'];
            } else {                
                $output[] = sprintf("<a href='%s'>%s</a>", makeCategoryLink($value, $params['weblog']), htmlentities($thiscat['display'], ENT_QUOTES, "utf-8"));
            }
            
        }

   
        return implode(", ", $output);
    } else {
        return '';
    }
}



/**
 * Create a link to a given category.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category_link($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $cat = $PIVOTX['categories']->getCategory($params['name']);
    
    if (!empty($cat)) {
        $output = sprintf("<a href='%s'>%s</a>", makeCategoryLink($cat['name']), $cat['display']);
    }
    
    return $output;

}





/**
 * Inserts a linked list to the the pages with the different categories.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category_list($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $mycats = $PIVOTX['weblogs']->getCategories();

    $modifier = $PIVOTX['parser']->modifier;
    $modifiercats = explode(",", $modifier['category']);
        
    $format = get_default($params['format'], "<a href=\"%url%\">%display%</a><br />");

    $output = '';

    // See if we need to ignore some categories..
    if (!empty($params['ignore'])) {
        $ignore = explode(",", $params['ignore']);
        $ignore = array_map("trim", $ignore);    
    } else {
        $ignore = array();
    }
    
    // See if we need to list just some of the categories..
    if (!empty($params['only'])) {
        $only = explode(",", $params['only']);
        $only = array_map("trim", $only);    
    } else {
        $only = array();
    }

    if( is_array( $mycats )) {
        
        // Iterate over the list, formatting output as we go.
        foreach($mycats as $cat) {
            
            $filelink = makeCategoryLink($cat, $params['weblog']);

            $catinfo = $PIVOTX['categories']->getCategory($cat);

            // Skip it, if it's in $ignore, or if it's marked 'hidden'
            if (in_array($catinfo['name'], $ignore) || in_array($catinfo['display'], $ignore) || ($catinfo['hidden']==1) ) {
                continue;
            }
			
            // Skip it, if it's not in $only
            if (!in_array($catinfo['name'], $only) && !empty($only)) {
                continue;
            }

            // Check if it's the active one..
            if (in_array($catinfo['name'], $modifiercats)) {
                $active = $params['isactive'];
            } else {
                $active = "";
            }

            // fix the rest of the string..
            $this_output = str_replace("%url%" , $filelink, $format);
            $this_output = str_replace("%name%" , $catinfo['name'], $this_output);
            $this_output = str_replace("%display%" , $catinfo['display'], $this_output);
            $this_output = str_replace("%internal%" , $catinfo['name'], $this_output);
            $this_output = str_replace("%active%" , $active, $this_output);

            $output .= "\n".$this_output;
        }
    }
    
    return stripslashes($output);

}





/**
 * Outputs the current charset of the weblog / page
 *
 * This function always return 'utf-8' since we
 * chosen that as the only charset.
 *
 * @return string
 */
function smarty_charset() {
    return "utf-8";
}


function smarty_commcount($params, &$smarty) {
    global $temp_comment, $PIVOTX;

    $params = clean_params($params);

    $commcount=$PIVOTX['db']->entry['commcount'];

    // if we have a $temp_comment, we have to add one
    if (!empty($temp_comment)) { $commcount++; }

    $text0 = get_default($params['text0'], __("No comments"), true);
    $text1 = get_default($params['text1'], __("One comment"));
    $textmore = get_default($params['textmore'], __("%num% comments"));

    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) &&  ($PIVOTX['db']->entry['allow_comments'] == 0) )  {
        return "";
    }

    $output_arr = array($text0, $text1, $textmore);
    $output = $output_arr[min(2,$commcount)];

    $output = str_replace("%num%", $PIVOTX['locale']->getNumber($commcount), $output);
    $output = str_replace("%n%", $commcount, $output);

    return $output;
}



/**
 * Displays a commentform if commenting is allowed and
 * remote IP isn't blocked.
 *
 * @param string $template
 * @return string
 */
function smarty_commentform($params) {
    global $PIVOTX;

    // This tag is only allowed on entrypages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = clean_params($params);

    $template = get_default($params['template'], "_sub_commentform.html");

    // check for entry's allow_comments, blocked IP address or subweblog comments..
    if ( (isset($PIVOTX['db']->entry['allow_comments']) && ($PIVOTX['db']->entry['allow_comments']==0)) || 
            (ip_check_block($_SERVER['REMOTE_ADDR']))  ) {

        // if allow_comments set to 0, or current visitor has his ip blocked, then don't show form
        $output = "";

    }   else {
        // else show it
        if(file_exists($PIVOTX['paths']['templates_path'].$template)) {

            $output = parse_intro_or_body("[[include file='$template' ]]");

        } else {

            $output = "[!-- Pivot couldn't include '$template' --]";

        }

    }

    if ($PIVOTX['config']->get('hashcash')==1) {
        $output = add_hashcash($output);
    }

    return $output;

}


function smarty_commentlink($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    $link = makeFilelink($vars['entry'], $params['weblog']);

    $commcount=intval($vars['commcount']);

    $text0 = get_default($params['text0'], __("No comments"), true);
    $text1 = get_default($params['text1'], __("One comment"));
    $textmore = get_default($params['textmore'], __("%num% comments"));

    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) &&  ($vars['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$commcount)];
    $commcount = $PIVOTX['locale']->getNumber($commcount);
    $commcount = str_replace("%num%", $commcount, $text);
    $commcount = ucfirst(str_replace("%n%", $vars['commcount'], $commcount));

    $commnames=$vars['commnames'];

    $weblog = $PIVOTX['weblogs']->getWeblog();
    if ($weblog['comment_pop']==1) {

        $output = sprintf("<a href='%s' ", $link);
        $output.= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $link, $weblog['comment_width'], $weblog['comment_height']);
        $output.= sprintf(" title=\"%s\">%s</a>",$commnames, $commcount);

    } else {

        $output=sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, $commnames, $commcount);

    }

    return $output;
}


/**
 * Outputs the list of comments for an entry based on the supplied format.
 */
function smarty_comments($params, $format, &$smarty) {
    global $PIVOTX, $temp_comment;

    if (isset($format)) {

        $params = clean_params($params);

        if (trim($format)=="") {
            $format = "%anchor%
            <p class='comment'>%comment%</p>
            <cite class='comment'><strong>%name%</strong> %url% - %date% %editlink%</cite>";
        }

        $order = get_default($params['order'], "ascending");
        $entrydate = get_default($params['date'], "%day%-%month%-&rsquo;%ye% %hour24%:%minute%");
        $default_gravatar = get_default($params['default_gravatar'], "http://pivotx.net/p64.gif");
        $gravatar_size = get_default($params['gravatar_size'], 64);


        // If %editlink% is not present, insert it right after %date%..
        if (strpos($format, "%editlink%")==0){
            $format = str_replace("%date%", "%date% %editlink%", $format);
        }

        $last_comment="";

        $vars = $smarty->get_template_vars();

        // Sometimes $PIVOTX['db']->entry['comments'] isn't set correctly. This is slight
        // hack to make sure we get it right..
        $comments = get_default($PIVOTX['db']->entry['comments'], $vars['entry']['comments']);

        // Make sure $comments is an array..
        if (!is_array($comments)) { $comments = array(); }

        // Perhaps we're previewing a comment. Add it..
        if ( !empty($temp_comment) &&  is_array($temp_comment) ) {
           $comments[] = $temp_comment;
        }

        if (count($comments)>0) {

            // first, make a list of comment-on-comments..
            $crosslink = array();

            foreach ($comments as $count => $temp_row) {
                if(preg_match("/\[(.*):([0-9]*)\]/Ui", $temp_row['comment'], $matches)) {
                    $crosslink[$count+1] = $matches[2];
                    // remove [name:1] from comment..
                    $PIVOTX['db']->entry['comments'][$count]['comment'] = str_replace($matches[0], "", $comments[$count]['comment']);
                }
            }

            $last_count = count($comments) - 1;

            foreach ($comments as $count => $temp_row) {

                /**
                 * If we get here, this is a record we have to output in some form..
                 */
                $temp_row['name'] = strip_tags($temp_row['name']);
                $temp_row['email'] = strip_tags($temp_row['email']);
                $temp_row['url'] = strip_tags($temp_row['url']);

                // Set the flag to display the 'awaiting moderation' text.
                if ($temp_row["moderate"]==1) {
                    $awaiting_moderation = true;
                }

                // Check if the comment is different than the last one, if the author's
                // IP isn't blocked, and if the comment isn't waiting for moderation.
                if ( ($temp_row["ip"].$temp_row["comment"]!=$last_comment) &&
                        (!(ip_check_block($temp_row["ip"]))) &&
                        ( ($temp_row["moderate"]!=1) || ($temp_row['showpreview']==1) )  ){



                    /**
                     * make email link..
                     */
                    if (isemail($temp_row["email"]) && !$temp_row["discreet"]) {
                        $email_format = "(".encodemail_link($temp_row["email"], __('Email'), $temp_row["name"]).")";
                        $emailtoname = encodemail_link($temp_row["email"], $temp_row["name"], $temp_row["name"]);
                    } else {
                        $email_format = "";
                        $emailtoname = $temp_row["name"];
                    }


                    if (isemail($temp_row["email"])) {
                        $gravatar = "http://www.gravatar.com/avatar.php?gravatar_id=" . md5($temp_row["email"]) .
                                            "&amp;default=" . urlencode($default_gravatar) . "&amp;size=" . $gravatar_size;
                    } else {
                        $gravatar = $default_gravatar;
                    }


                    /**
                     * make url link..
                     */
                    if (isurl($temp_row["url"])) {
                        if (strpos($temp_row["url"], "ttp://") < 1 ) {
                            $temp_row["url"]="http://".$temp_row["url"];
                        }

                        $target = "";

                        $temp_row["url_title"]= str_replace('http://', '', $temp_row["url"]);

                        //perhaps redirect the link..
                        if ($PIVOTX['weblogs']->get('', 'lastcomm_redirect')==1 ) {
                            $target .= " rel=\"nofollow\" ";
                        }

                        $url_format = sprintf("(<a href='%s' $target title='%s'>%s</a>)",
                                $temp_row["url"], cleanAttributes($temp_row["url_title"]), __('URL'));
                        $urltoname = sprintf("<a href='%s' $target title='%s'>%s</a>",
                                $temp_row["url"], cleanAttributes($temp_row["url_title"]), $temp_row['name']);
                    } else {
                        $url_format = "";
                        $urltoname = $temp_row["name"];
                    }


                    /**
                     * Make 'edit' and 'delete' links..
                     */
                    $editlink = get_editcommentlink($PIVOTX['db']->entry['code'], $count);


                    /**
                     * make a 'registered user' span..
                     */
                    if ($temp_row['registered']==1) {
                        $name = "<span class='registered'>" . $temp_row["name"] . "</span>";
                    } else {
                        $name = $temp_row["name"];
                    }

                    /**
                     * make quote link..
                     */
                    $quote = sprintf("<a href='#form' onclick='javascript:var pv=document.getElementsByName(\"piv_comment\");pv[0].value=\"[%s:%s] \"+pv[0].value;'>%s</a>",
                        $temp_row["name"], $count+1, $format_reply );

                    // make backward link..
                    if (isset($crosslink[$count+1])) {
                        $to = $PIVOTX['db']->entry['comments'][ ($crosslink[$count+1] - 1) ];
                        $backward_text = str_replace("%name%", $to['name'], $format_backward);
                        $backward_anchor = safe_string($to["name"],TRUE) ."-". format_date($to["date"],"%ye%%month%%day%%hour24%%minute%");
                        $backward_link = sprintf("<a href='#%s'>%s</a>", $backward_anchor, $backward_text);
                    } else {
                        $backward_link = "";
                    }

                    /**
                     * make forward link..
                     */
                    $forward_link = "";
                    foreach ($crosslink as $key => $val) {
                        if (($val-1) == $count) {
                            $from = $PIVOTX['db']->entry['comments'][ ($key-1) ];
                            $forward_text = str_replace("%name%", $from['name'], $format_forward);
                            $forward_anchor = safe_string($from["name"],TRUE) ."-". format_date($from["date"],"%ye%%month%%day%%hour24%%minute%");
                            $forward_link .= sprintf("<a href='#%s'>%s</a> ", $forward_anchor, $forward_text);
                        }
                    }

                    /**
                     * make anchors
                     */
                    $id = safe_string($temp_row["name"], true) ."-". format_date($temp_row["date"],"%ye%%month%%day%%hour24%%minute%");
                    $anchor = "<a id=\"$id\"></a>";

                    $date = format_date($temp_row["date"],$entrydate);
                    $datelink = "<a href=\"#$id\">$date</a>"; 

                    /**
                     * substite all of the parameters into the comment, and add it to the output.
                     */
                    $this_tag= $format;
                    $this_tag= str_replace("%quote%", $quote, $this_tag);
                    $this_tag= str_replace("%quoted-back%", $backward_link, $this_tag);
                    $this_tag= str_replace("%quoted-forward%", $forward_link, $this_tag);
                    $this_tag= str_replace("%count%", $count+1, $this_tag);
                    $this_tag= str_replace("%code%", $PIVOTX['db']->entry['code'], $this_tag);
                    $this_tag= str_replace("%even-odd%", ( (($count)%2) ? 'even' : 'odd' ), $this_tag);
                    $this_tag= str_replace("%ip%", $temp_row["ip"], $this_tag);
                    $this_tag= str_replace("%date%", $date, $this_tag);
                    $this_tag= str_replace("%datelink%", $datelink, $this_tag);
                    $this_tag= str_replace("%comment%", comment_format($temp_row["comment"]), $this_tag);
                    $this_tag= str_replace("%comment-nolinebreaks%", comment_format($temp_row["comment"], true), $this_tag);
                    $this_tag= str_replace("%name%", $name, $this_tag);
                    $this_tag= str_replace("%name_attr%", cleanAttributes($name), $this_tag);
                    $this_tag= str_replace("%email%", $email_format, $this_tag);
                    $this_tag= str_replace("%url%", $url_format, $this_tag);
                    $this_tag= str_replace("%anchor%", $anchor, $this_tag);
                    $this_tag= str_replace("%url-to-name%", $urltoname, $this_tag);
                    $this_tag= str_replace("%email-to-name%", $emailtoname, $this_tag);
                    $this_tag= str_replace("%gravatar%", $gravatar, $this_tag);
                    $this_tag= str_replace("%editlink%", $editlink, $this_tag);
                    if ( ($count==$last_count) && (!isset($params['skipanchor']))) {
                        $this_tag = '<a id="lastcomment"></a>'.$this_tag;
                    }

                    $last_comment=$temp_row["ip"].$temp_row["comment"];
                    // Outputting according to order:


                    if ($order == 'ascending') {
                        $output .= $this_tag."\n";
                    } elseif ($order == 'descending') {
                        $output = $this_tag."\n".$output;
                    }
                }
            }
        }

        // If there are comments waiting for moderation, append a note saying so.
        if ($awaiting_moderation) {
            $output .= sprintf("<p id='moderate_queue_waiting'>%s</p>", __('One or more comments are waiting for approval by an editor.'));
        }

        if (!isset($params['skipanchor'])) {
                $output = '<a id="comm"></a>'."\n".$output;
        }

        return $output;

    }


}


/**
 * Outputs the list of trackbacks for an entry based on the supplied format.
 */
function smarty_trackbacks($params, $format, &$smarty) {
    global $PIVOTX;

    if (isset($format)) {

        $params = clean_params($params);

        if (trim($format)=="") {
            $format = "%anchor%
            <p class='comment'><strong>%title%</strong><br />%excerpt%</p>
            <cite class='comment'>Sent on %date%, via %url% %editlink%</cite>";
        }

        $order = get_default($params['order'], "ascending");
        $entrydate = get_default($params['date'], "%day%-%month%-&rsquo;%ye% %hour24%:%minute%");
        // $entrydate=$PIVOTX['weblogs']->get('', 'fulldate_format');

        // If %editlink% is not present, insert it right after %date%..
        if (strpos($format, "%editlink%")==0){
            $format = str_replace("%date%", "%date% %editlink%", $format);
        }

        $last_trackback="";
        $output='';

        $vars = $smarty->get_template_vars();

        // Sometimes $PIVOTX['db']->entry['trackbacks'] isn't set correctly. This is slight
        // hack to make sure we get it right..
        $trackbacks = get_default($PIVOTX['db']->entry['trackbacks'], $vars['entry']['trackbacks']);

        // Make sure $comments is an array..
        if (!is_array($trackbacks)) { $trackbacks = array(); }

        foreach ($trackbacks as $count => $temp_row) {
            
            // Skip all trackbacks from blocked IPs.
            if (ip_check_block($temp_row["ip"])){
                continue;
            }

            if (isurl($temp_row["url"])) {
                if (strpos($temp_row["url"], "ttp://") < 1 ) {
                    $temp_row["url"]="http://".$temp_row["url"];
                }
                $url = '<a href="'.$temp_row["url"].'" rel="nofollow">' . $temp_row["name"] . '</a>';
            } else {
                $url = $temp_row["url"];
            }

            $editlink = get_edittrackbacklink($PIVOTX['db']->entry['code'],$count);

            $id = safe_string($temp_row["name"], true) ."-". format_date($temp_row["date"],"%ye%%month%%day%%hour24%%minute%");
            $anchor = "<a id=\"$id\"></a>";

            $date = format_date($temp_row["date"],$entrydate);
            $datelink = "<a href=\"#$id\">$date</a>"; 

            /**
             * substite all of the parameters into the trackback, and add it to the output.
             */
            $this_tag = $format;
            $this_tag = str_replace("%code%", $PIVOTX['db']->entry['code'], $this_tag);
            $this_tag = str_replace("%count%", $count+1, $this_tag);
            $this_tag = str_replace("%even-odd%", ( (($count)%2) ? 'even' : 'odd' ), $this_tag);
            $this_tag = str_replace("%ip%", $temp_row["ip"], $this_tag);
            $this_tag = str_replace("%date%", format_date($temp_row["date"],$entrydate), $this_tag);
            $this_tag = str_replace("%datelink%", $datelink, $this_tag);
            $this_tag = str_replace("%excerpt%", comment_format($temp_row["excerpt"]), $this_tag);
            $this_tag = str_replace("%title%", $temp_row["title"], $this_tag);
            $this_tag = str_replace("%url%", $url, $this_tag);
            $this_tag = str_replace("%anchor%", $anchor, $this_tag);
            $this_tag = str_replace("%editlink%", $editlink, $this_tag);

            // Outputting according to order:
            if ($order == 'ascending') {
                $output .= $this_tag."\n";
            } elseif ($order == 'descending') {
                $output = $this_tag."\n".$output;
            } else {
                debug("What?");
            }

            $last_trackback = $temp_row["ip"].$temp_row["excerpt"];
        }
    }

    if (!isset($params['skipanchor'])) {
        $output = '<a id="track"></a>'."\n".$output;
    }

    return $output;


}



/**
 * Replace the [[ content ]] tag in the 'extra template' with
 * the desired content from $smarty
 *
 * @return string
 */
function smarty_content($params, &$smarty) {
    global $PIVOTX, $oldpage;

    $vars = $smarty->get_template_vars();
    $content = $vars['content'];

    return $content;
}



/**
 * Smarty function to count the elements on which the modifier is applied.
 * 
 * @param array $params
 * @return string
 */
function smarty_count($params) {
    
    if (!is_array($params['array'])) {
        return false;
    } else {
        return count($params['array']);
    }
    
}



/**
 * Output the current date, or the date given in $params['date'].
 * The result is formatted according to $params['format'].
 *
 * if $params['use'] is set, we use that date to display, you can use
 * 'date', 'publish_date', 'edit_date', or an other variable that is known
 * from within the smarty scope, as long as it uses the mysql date format.
 * (ie: 2007-06-11 22:10:45)
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_date($params, &$smarty) {

    // Keep track of the last output date.
    static $last_output;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    // Set the format.
    if (!empty($params['format'])) {
        $format = $params['format'];
    } else {
        $format = "%day% %month% %ye% - %hour24%:%minute%";
    }

    // If we have a $params['use'], we take that value from the smarty object,
    // else we check if $params['date'] is set, and use that.
    // Then we check if there's a 'date' set in the smarty object.
    // As a last resort we use '', which is evaluated as being 'now()'
    if (!empty($params['use'])) {
        $date = $vars[ $params['use'] ];
    } else if (!empty($params['date'])) {
        $date = $params['date'];
    } else if (!empty($vars['date'])) {
        $date = $vars['date'];
    } else {
        $date = '';
    }

    $output = format_date($date, $format);

    // Check if we only want 'different dates':
    if ( ($params['diffonly']==1) && ($output == $last_output) ) {
        return "";
    }

    // Store output for the next time.
    $last_output = $output;

    return $output;

}


function smarty_editlink($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    $format = get_default($params['format'], __('Edit'));
    $prefix = get_default($params['prefix'], "");
    $postfix = get_default($params['postfix'], "");

    $pagetype = $PIVOTX['parser']->modifier['pagetype'];

    if (($pagetype == "entry") || isset($vars['entry'])) {
        // We are on an entry page or in a subweblog 
        $output = get_editlink($format, $vars['entry']['uid'], $prefix, $postfix, "entry");        
    } else {
        $output = get_editlink($format, $vars['page']['uid'], $prefix, $postfix, "page");
    }

    return $output;

}


/**
 * Insert a link to open the emoticons reference window
 *
 * @param array $params
 * @return string
 */
function smarty_emotpopup($params) {
    global $PIVOTX, $emoticon_window, $emoticon_window_width, $emoticon_window_height;

    $params = clean_params($params);

    $title = get_default($params['title'], __('Emoticons') );

    if ($PIVOTX['weblogs']->get('', 'emoticons')==1) {

        if ($emoticon_window != '') {

            $url = $PIVOTX['paths']['pivotx_url']."includes/emoticons/".$emoticon_window;

            $onclick = sprintf("window.open('%s','emot','width=%s,height=%s,directories=no,location=no,menubar=no,scrollbars=no,status=yes,toolbar=no,resizable=yes');return false",
                        $url,
                        $emoticon_window_width,
                        $emoticon_window_height
                    );

            $output = sprintf("<a href='#' onmouseover=\"window.status='(Emoticons Reference)';return true;\" onmouseout=\"window.status='';return true;\" title='Open Emoticons Reference' onclick=\"%s\">%s</a>",
                        $onclick,
                        $title
                    );
        }

    } else {
        $output='';
    }

    return $output;

}

/**
 * Return an exploded version of $params['string'], using $params['glue']
 * as the seperator for each item.
 *
 * If return is set, it will return the results as that smarty variable. Otherwise, 
 * it will just output the results.
 *
 * Example: 
 * [[ explode string="this, is, a, string" glue=", " return=explodedstring ]]
 * [[ print_r var=$explodedstring ]]
 *
 * @param string $params
 * @param object $smarty
 * @return string
 */
function smarty_explode($params, &$smarty) {

    $return = get_default($params['return'],false);
    
    if (isset($params['glue'])) {
        $glue = $params['glue'];
    } else {
        $glue = ",";
    }

    if (is_string($params['string'])) {
        $output = explode($glue, $params['string']);
    } else {
        $output = $params['string'];
    }

    if($return && is_string($return)) {
        $smarty->assign($return, $output);
    } else {
        return $output;
    }
}

/**
 * This function is here to provide backwards compatibility for the [[ entrylink ]] tag.
 * In your templates you should use [[ link hrefonly=1 ]] to get the same results.
 *
 * THIS FUNCTION IS DEPRECATED, and will be removed eventually!!
 * 
 * @see smarty_link
 * 
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_entrylink($params, &$smarty) {

    $params['hrefonly'] = 1;
    return _smarty_link_entry($params, $smarty);

}



/**
 * Fetches an RSS or Atom feed, and displays it on a page.
 *
 * Example:
 * <pre>
 * [[ feed url="http://api.flickr.com/services/feeds/photos_public.gne?id=26205235@N02&lang=en-us&format=rss_200"
 *   amount=8 dateformat="%dayname% %day% %monthname%" allowtags="<img><a><strong><em>" ]]
 * <p><strong><a href="%link%">%title%</a></strong><br/></p>
 * <p>%description% (%date%)</p>
 * [[ /feed ]]
 * </pre>
 *
 * In addition to the standard formatting tags (%title%, %link%, %description%,
 * %content%, %author%, %date%, and %id%), you can use any key defined in feed 
 * (by using %keyname%). Upto two-level arrays with keys are supported (as 
 * "%keyname->subkeyname->subsubkeyname%")
 *
 * @param array $params
 * @param string $text
 * @param object $smarty
 * @return string
 */
function smarty_feed($params, $text, &$smarty) {
    global $PIVOTX;
    
    $params = clean_params($params);

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }
    
    if(!isset($params['url'])) { return __("You need to specify an URL to a feed"); }
    $amount = get_default($params['amount'], 8);
    $dateformat = get_default($params['dateformat'], "%dayname% %day% %monthname% %year%");
    $trimlength = get_default($params['trimlength'], 10000);

    include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');
    
    // Parse it
    $rss = fetch_rss($params['url']);
    
    $output = "";


    if (count($rss->items)>0) {

        // Slice it, so no more than '$amount' items will be shown.
        $rss->items = array_slice($rss->items, 0, $amount);
    
        foreach($rss->items as $feeditem) {
            
            $item = $text;

            // If the feed has authors on an entry-level, override the author name..
            if ($author = $feeditem['author']) {
                $authorname = $feeditem['author'];
            }

            $date = format_date(date("Y-m-d H-i-s", $item['date_timestamp']), $dateformat);

            // Get the title, description and content, since we might want to do some
            // parsing on it..
            $title = $feeditem['title'];
            $description = $feeditem['description'];
            $content =get_default($feeditem['atom_content'], $feeditem['summary']);
            
            // Do some parsing: stripping tags, trimming length, stuff like that.
            if (!empty($params['allowtags'])) {
                $title = strip_tags_attributes($title, $params['allowtags']);
                $description = strip_tags_attributes($description, $params['allowtags']);
                $content = strip_tags_attributes($content, $params['allowtags']);
            } else {
                $title = trimtext(strip_tags_attributes($title, "<>"), $trimlength);
                $description = trimtext(strip_tags_attributes($description, "<>"), $trimlength);
                $content = trimtext(strip_tags_attributes($content, "<>"), $trimlength);
            } 

            $item = str_replace('%title%', $title, $item);
            $item = str_replace('%link%', $feeditem['link'], $item);
            $item = str_replace('%description%', $description, $item);
            $item = str_replace('%content%', $content, $item);
            $item = str_replace('%author%', $authorname, $item);
            $item = str_replace('%date%', $date, $item);
            $item = str_replace('%id%', $feeditem['id'], $item);

            // Supporting upto two level arrays in item elements.
            foreach ($feeditem as $key => $value) {
                if (is_string($value)) {
                    if ($key == "link" || $trimlength==-1) {
                        $value = trim($value);
                    } else {
                        $value = trimtext(trim($value), $trimlength);
                    }
                    $item = str_replace("%$key%", $value, $item);
                } else if (is_array($value)) {
                    foreach ($value as $arrkey => $arrvalue) {
                        if (is_string($arrvalue)) {
                            $arrvalue = trim($arrvalue);
                            if ($trimlength!=-1) {
                                $arrvalue = trimtext($arrvalue, $trimlength);
                            }
                            $item = str_replace("%$key".'->'."$arrkey%", $arrvalue, $item);
                        } else if (is_array($arrvalue)) {
                            foreach ($arrvalue as $subarrkey => $subarrvalue) {
                                if (is_string($subarrvalue)) {
                                    $subarrvalue = trim($subarrvalue);
                                    if ($trimlength!=-1) {
                                        $subarrvalue = trimtext($subarrvalue, $trimlength);
                                    }
                                    $item = str_replace("%$key".'->'."$arrkey".'->'."$subarrkey%", 
                                        $subarrvalue, $item);
                                }
                            }
                        }
                    }
                }
            }

            // Remove any unused formatting tags.
            $item = preg_replace("/%[^%]+%/", "", $item);

            $output .= $item;

        }

    } else {
        debug("<p>Oops! I'm afraid I couldn't read the the feed.</p>");
        echo "<p>" . __("Oops! I'm afraid I couldn't read the feed.") . "</p>";
        debug(magpie_error());
    }

    return $output;
    
    
    
}



/**
 * Executes a hook, from within a template
 *
 * @param array $params
 * @param object $smarty
 * @return mixed
 */
function smarty_hook($params, &$smarty) {
    global $PIVOTX;

    if (!isset($PIVOTX['extensions'])) {
        return;
    }

    $params = clean_params($params);

    $name = $params['name'];
    $value = $params['value'];

    // To show where the hooks go into the HTML..
    if ($_GET['showhooks']==1) {
        return "<span class='visiblehook'>" . $name ."</span>";
    }

    $output = $PIVOTX['extensions']->executeHook('in_pivotx_template', $name, $value );

    return $output;

}


/**
 * Gets the description of a file, to display in the template editor screens
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_filedescription($params, &$smarty) {

    $params = clean_params($params);

    $filename = $params['filename'];
    $extension = getextension($filename);
    
    switch ($extension) {
        
        case "css":
            $output = __("CSS file (stylesheet)");
        
            break;
        
        case "theme":
            $output = __("PivotX weblog theme");
            break;
        
        case "xml":
            $output = __("XML Feed template");
            break;
        
        case "txt":
            $output = __("Text file");
            break;
        
        case "php":
            $output = __("PHP script");

            if (strpos($filename, '%%') !== false) {
                $output = __("PivotX cache file");
            }            

            break;
        
        case "js":
            $output = __("Javascript file");
            break;
        
        case "mpc":
            $output = __("MagpieRSS cache file");
            break;
        
        case "cache":
            $output = __("PivotX cache file");
            break;
        
        case "tag":
            $output = __("PivotX Tag file");
            break;
        
        case "rel":
            $output = __("PivotX Tag relations");
            break;
        
        case "zd":
        case "zg":
            $output = __("Minify cache file");
            break;        

        case "htm":
        case "html":
        case "tpl":
        case "";
            
            $output = __("HTML template");
            
            if (strpos($filename, '_sub_') !== false) {
                $output = __("Include template");
            }
            if (strpos($filename, 'frontpage_') !== false) {
                $output = __("Frontpage template");
            }
            if (strpos($filename, 'archivepage_') !== false) {
                $output = __("Archive template");
            }
            if (strpos($filename, 'entrypage_') !== false) {
                $output = __("Entry template");
            }
            if (strpos($filename, 'page_') !== false) {
                $output = __("Page template");
            }            
            if (strpos($filename, 'searchpage_') !== false) {
                $output = __("Searchpage template");
            }
            if (strpos($filename, 'minify') !== false) {
                $output = __("Minify cache file");
            }    

            break;
        
        default:
            $output = "";
        
    }

    // Some special cases
    switch ($filename) {
        
        case ".htaccess":
            $output = __("Apache configuration");
            break;
        
        case "404.html":
            $output = __("PivotX 'not found' template");
            break;
        
        case "error.html":
            $output = __("PivotX error page");
            break;
        
    }

    return $output;

}



/**
 * Retrieves a list of pages. Useful in combination with [[getpage]]. You can use this
 * To get an array with the URIs of all pages in a chapter, and then iterate
 * through them with [[getpage]]. See this example in the documentation:
 * http://docs.pivotx.net/doku.php?id=template_tags#getpagelist
 *
 * You can use the 'onlychapter' attribute to choose a chapter to get the pages
 * from. If omitted, it will return all pages.
 *
 * The 'var' attribute determines the var in the template that will have the
 * results. Defaults to 'pageslist'. Note: do _not_ include the $ in the var name:
 * var=pagelist is correct, var=$pagelist is not. This is because the 'var'
 * attribute is used a string to set the variable, if you use $pagelist, the
 * _value_ of $pagelist is used, instead of the string 'pagelist'
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_getpagelist($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $var = get_default($params['var'], 'pagelist');

    $chapters = $PIVOTX['pages']->getIndex();

    $result = array();

    // Iterate through the chapters
    foreach ($chapters as $chapter) {

        // If 'onlychapter' is set, we should display only the pages in that chapter,
        // and skip all the others. You can use either the name or the uid.
        if (!empty($params['onlychapter']) && (strtolower($chapter['chaptername'])!=strtolower($params['onlychapter'])) &&
                ($chapter['uid']!=$params['onlychapter']) ) {
            continue; // skip it!
        }

        // Iterate through the pages
        foreach ($chapter['pages'] as $page) {
            if(in_array($page['uri'], explode(",",$params['exclude']))) {
                continue;
            }
            
            $result[] = $page['uri'];
        }
    }
    
    if($params['sort'] == "title") {
        asort($result);
    }

    $smarty->assign($var, $result);

    return "";

}


/**
 * Gets a single page, referenced by it's 'uri'. Set it in the templates,
 * so it can be used like [[ $page.title ]]
 *
 * @see smarty_resetpage
 * @return string
 */
function smarty_getpage($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    // Save the current '$page', so we can later reset it. Distinguish between entries and pages..
    $vars = $smarty->get_template_vars();
    $pagetype = $PIVOTX['parser']->modifier['pagetype'];
    
    if ($pagetype=="page") {
        $smarty->assign('oldpage', $vars['page']);
    } else {
        $smarty->assign('oldpage', $vars['entry']);
    }

    // get the new page, and set it in $smarty
    $page = $PIVOTX['pages']->getPageByUri($params['uri']);
    $page['introduction']= parse_intro_or_body($page['introduction']); 
    $page['body']= parse_intro_or_body($page['body']); 
    foreach($page as $key=>$value) {
        $smarty->assign($key, $value);
    }

    $smarty->assign('page', $page);

    return "";
}


/**
 * Returns the local absolute URL to the (current) weblog frontpage.
 *
 * @return string
 */
function smarty_home() {
    global $PIVOTX;
    return $PIVOTX['paths']['site_url'];
}

/**
 * Returns the link to the body (more-link) for the current entry.
 */
function smarty_more($params, &$smarty) {
    global $PIVOTX;
    
    $params = clean_params($params);

    $weblogdata = $PIVOTX['weblogs']->getWeblog();
    if (isset($smarty)) {
        $vars = $smarty->get_template_vars();
    } else {
        $vars = $PIVOTX['db']->entry;
    }

    $title = cleanAttributes($params['title']);
    if( '' != $title ) {
        $title = 'title="'.$title.'" ';
        $title = str_replace("%title%", $vars['title'], $title);
    }

    $text = get_default($params['text'], get_default($weblogdata['read_more'], __('(more)')));

    if( strlen( $vars['body'] ) >5 ) {
        $morelink = makeFilelink( $vars['code'],'','body');
        $output = "<a href=\"" . $morelink . "\" $title>$text</a>";
        $output = str_replace("%title%", $vars['title'], $output);

        // Perhaps add the pre- and postfix to the output..
        if (!empty($params['prefix'])) {
            $output = $params['prefix'].$output;
        }
        if (!empty($params['postfix'])) {
            $output .= $params['postfix'];
        }

    } else {
        $output = '';
    }
    
    return $output;
}




/**
 * Smarty tag to insert an image.
 * 
 * <pre>
 *  [[image file="somedirectory/somefile.jpg" ]]
 * </pre>
 *
 * Valid parameters are "file", "alt", "align", "class", "id", "width" and 
 * "height". The inserted image will have CSS class "pivotx-image" unless the
 * "class" parameter is set.
 * 
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_image( $params ) {
    global $PIVOTX;

    $params = clean_params($params);

    $filename = $params['file'];
    $alt = cleanAttributes($params['alt']);
    $align = get_default($params['align'], 'center');
    //$border = get_default($params['border'], 0); -- border is deprecated..
    $class = $params['class'];
    $id = $params['id'];
    $width = $params['width'];
    $height = $params['height'];

    $org_filename = $filename;

    if (empty($class)) {
        $class = "pivotx-image";
    }
    
    if ($align=="left" || $align=="right") {
    	$class .= " align-".$align; 
    }

    if (empty($id)) {
        $id = "";
    } else {
        $id = 'id="'.$id.'"';
    }

    // only continue if we have an image
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = fixpath( $PIVOTX['paths']['upload_base_url'].$filename );

        switch( $align) {
            case( 'left' ):
            case( 'right' ):
                $output   = '<img src="'.$filename.'" ';
                $output  .= 'title="'.$alt.'" alt="'.$alt.'"';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }                
                $output  .= ' class="'.$class.'"'.$id.' />';
                break;
                
            case( 'inline' ):
                $output  = '<img src="'.$filename.'" title="'.$alt.'"';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }
                $output .= ' alt="'.$alt.'" class="'.$class.'"'.$id.' />';
                break;

            default:
                $output  = '<div class="pivotx-wrapper">';
                $output .= '<img src="'.$filename.'" title="'.$alt.'" ';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }                
                $output .= ' alt="'.$alt.'" class="'.$class.'" '.$id.' />';
                $output .= '</div>';
        }
    } else {
        debug("Rendering error: could not display image '$org_filename'. File does not exist");
        $output = "<!-- Rendering error: could not display image '$org_filename'. File does not exist -->";
    }
    return $output;
}


/**
 * Smarty tag to insert a link to a downloadable file.
 *
 * <pre>
 *  [[download:filename:icon:text:title]]
 * </pre>
 * @param string $filename
 * @param string $icon Insert a suitable icon if set to "icon"
 * @param string $text The text of the download link.
 * @param string $title The text of the title attribue of the link.
 */
function smarty_download( $params ) {
    global $PIVOTX;

    $params = clean_params($params);

    $filename = $params['file'];
    $icon = $params['icon'];
    $text = $params['text'];
    $title = cleanAttributes($params['title']);

    $org_filename = $filename;

    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = fixpath( $PIVOTX['paths']['upload_base_url'].$filename );
        $ext      = getextension( $filename );
        $middle   = '';

        // We don't have icons for _all_ filetypes, so we group some together..
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'psd', 'eps', 'bmp', 'tiff', 'ai'))) {
            $iconext = "image";
        } else if (in_array($ext, array('doc', 'docx', 'rtf', 'dot', 'dotx'))) {
            $iconext = "doc";
        } else if (in_array($ext, array('mp3', 'aiff', 'ogg', 'wav'))) {
            $iconext = "audio";
        } else if (in_array($ext, array('wmv', 'mpg', 'mov', 'swf', 'flv'))) {
            $iconext = "movie";
        } else if (in_array($ext, array('zip', 'gz', 'tgz', 'rar', 'dmg'))) {
            $iconext = "zip";            
        } else {
            $iconext = $ext;
        }

        switch( $icon ) {
            case( 'icon' ):
                if( file_exists( $PIVOTX['paths']['pivotx_path'].'pics/icon_'.$iconext.'.gif' )) {
                    $image = fixpath( $PIVOTX['paths']['pivotx_url'].'pics/icon_'.$iconext.'.gif' );
                } else {
                    $image = fixpath( $PIVOTX['paths']['pivotx_url'].'pics/icon_generic.gif' );
                }

                if( '' != $image ) {
                    $width = 0; $height = 0;
                    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['host'].$image );
                    $middle = '<img src="'.$image;
                    if( 0 != $width )  { $middle .='" width="'.$width; }
                    if( 0 != $height ) { $middle .='" height="'.$height; }
                    $middle .= '" alt="'.$title.'" class="icon" style="border:0; margin-bottom: -3px;" />';
                }
                $middle .= ' '.$text;
                // all ok... leave
                break;

            case( 'text' ): // fall through
            default:
                $middle = $text;
        }
        
        // Refuse to insert a download without a clickable link. Just use the
        // filename in this case..
        if (empty($middle)) {
            $middle = basename($filename);
        }
        
        $code = '<a href="'.$filename.'" title="'.$title.'" class="pivotx-download">'.$middle.'</a>';
    } else {
        debug("Rendering error: did not make a download link for '$org_filename'. File does not exist");
        $code = '<!-- error: did not make a download link for '.$org_filename.'. File does not exist -->' ;
    }
    return $code;
}


/**
 * Return an imploded version of $params['array'], using $params['glue']
 * in between each item.
 * If 'return' is set, it will return the results as a smarty variable with that name.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_implode($params, &$smarty) {

    $return = get_default($params['return'],false);

    if (isset($params['glue'])) {
        $glue = $params['glue'];
    } else {
        $glue = ", ";
    }

    if (is_array($params['array'])) {
        return implode($glue, $params['array']);
    } else {
        return $params['array'];
    }
    
    if($return && is_string($return)) {
        $smarty->assign($return, $output);
    } else {
        return $output;
    }
}




/**
 * Smarty tag for [[ introduction ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_introduction($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $introduction = parse_intro_or_body($vars['introduction'], $params['strip'], $vars['convert_lb']);

    return $introduction;

}


/**
 * Output the language code for the current weblog/language.
 *
 * The optional $type argument can be either 'html' or 'xml'. The output
 * will then be suitable to use in templates, in the html tag, to set
 * the correct language. (If you are using XHTML 1.0, which is the default
 * for PivotX, you should use both [[lang type='html']] and [[lang type='xml']].)
 *
 * @param array $params
 * @return string
 */
function smarty_lang( $params ) {
    global $PIVOTX;

    $params = clean_params($params);

    $type = $params['type'];

    if (isset($PIVOTX['languages'])) {
        $lang = $PIVOTX['languages']->getCode();
    } else {
        $lang = '';
    }

    if( ''!=$lang ) {
        $output = $lang ;
    } else {
        $output = 'en' ;
    }

    if( !empty($type) ) {
        if ($type == 'html') {
            $output = 'lang="'.$output.'"' ;
        } elseif ($type == 'xml') {
            $output = 'xml:lang="'.$output.'"' ;
        } else {
            $output = '';
        }
    }
    return $output;
}




/**
 * Create a piece of HTML with links to the latest comments.
 *
 * @param array $params
 * @return string
 */
function smarty_latest_comments($params) {
    global $PIVOTX;

    $params = clean_params($params);

    $latest_comments_format = get_default($params['format'],
        "<a href='%url%' title='%date%'><b>%name%</b></a> (%title%): %comm%<br />" );
    $latest_comments_length = get_default($params['length'], 100);
    $latest_comments_trim = get_default($params['trim'], 16);
    $latest_comments_count = get_default($params['count'], 6);

    if (!empty($params['category'])) {
        $cats = explode(",",safe_string($params['category']));
        $cats = array_map("trim", $cats);
    } else {
        if ($PIVOTX['db']->db_type == 'flat') {
            $cats = $PIVOTX['weblogs']->getCategories();
        } else {
            // Don't filter on cats by default, as it is _very_
            // bad for SQL performance. 
            $cats = array();
        }
    }
    
    $comments = $PIVOTX['db']->read_latestcomments(array(
        'cats'=>$cats,
        'count'=>$latest_comments_count,
        'moderated'=>1
    ));

    // Adding the filter that we ignored because of SQL performance problems.
    if (empty($params['category']) && ($PIVOTX['db']->db_type != 'flat')) {
        $cats = $PIVOTX['weblogs']->getCategories();
        $com_db = new db(false);
        foreach ($comments as $key => $comment) {
            $entry = $com_db->read_entry($comment['entry_uid']);
            $comments[$key]['category'] = $entry['category'];
        }
    }

    $output='';
    $count=0;
 
    $weblog = $PIVOTX['weblogs']->getWeblog();
    if (count($comments)>0) {
        foreach ($comments as $comment) {
            
            // if it's in a category that's published on the frontpage, and the user is not blocked, we display it.
            if ( ((empty($comment['category'])) || (count(array_intersect($comment['category'], $cats))>0)) &&
                (!(ip_check_block(trim($comment['ip'])))) ) {

                $id = safe_string($comment["name"],TRUE) . "-" .
                    format_date($comment["date"], "%ye%%month%%day%%hour24%%minute%");

                $url=makeFilelink($comment['entry_uid'], '', $id);

                $comment['name'] = trimtext(stripslashes($comment['name']), $latest_comments_trim);
                $comment['title'] = trimtext(stripslashes($comment['title']), $latest_comments_trim);
                $comment['comment'] = comment_format($comment["comment"]);
                // Remove the [name:1] part in the 'latest comments'..
                $comment['comment'] = preg_replace("/\[(.*):([0-9]+)\]/iU", '', $comment['comment']);
                $comment['comment'] = trimtext(stripslashes($comment['comment']), $latest_comments_length);

                if ($weblog['comment_pop']==1) {

                    $popup= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $url, $weblog['comment_width'], $weblog['comment_height']);

                } else {
                    $popup='';
                }

                $thisline=$latest_comments_format;
                $thisline=str_replace("%name%", $comment['name'], $thisline);
                $thisline=str_replace("%date%", $comment['date'], $thisline);
                $thisline=str_replace("%title%", $comment['title'], $thisline);
                $thisline=str_replace("%url%", $url, $thisline);
                $thisline=str_replace("%popup%", $popup, $thisline);
                $thisline=str_replace("%comm%", $comment['comment'], $thisline);

                $thisline=format_date($comment["date"], $thisline);

                $output.= "\n".$thisline;

                $count++;
                if ($count>=$latest_comments_count) {
                    break;
                }
            }
        }
    }
    return entifyAmpersand($output);

}





/**
 * Create a link to an entry, a page or weblog.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_link($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);
    $pagetype = $PIVOTX['parser']->modifier['pagetype'];
    $vars = $smarty->get_template_vars();

    if (!empty($params['page'])) {
        // If a page link is explicitly requested
        return _smarty_link_page($params, $smarty);
    } elseif (!empty($params['entry'])) {
        // If an entry link is explicitly requested
        return _smarty_link_entry($params, $smarty);
    } elseif (!empty($params['weblog'])) {
        // If a weblog link is explicitly requested
        return _smarty_link_weblog($params, $smarty);
    } elseif (!empty($params['mail'])) {
        // If a mail link is explicitly requested
        return _smarty_link_mail($params, $smarty);
    } elseif (isset($vars['entry'])) {
        // If we're in a subweblog - on a page or in a weblog.
        return _smarty_link_entry($params, $smarty);
    } elseif ($pagetype=="page") {
        // If we're in a page (and no page parameter is given).
        return _smarty_link_page($params, $smarty);
    } else {
        // Default is link to entry
        return _smarty_link_entry($params, $smarty);
    }

}

/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_mail($params, &$smarty) {
    global $PIVOTX;

    $text = get_default($params['text'], __("Email"));
    $title = get_default($params['title'], "");
    $encrypt = get_default($params['encrypt'], false);
    $output = encodemail_link( $params['mail'], $text, $title, $encrypt);

    return $output;

}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_page($params, &$smarty) {
    global $PIVOTX;
  
    if (!empty($params['page'])) {
        // Get the page from the DB..
        $page = $PIVOTX['pages']->getPageByUri($params['page']);     
    } else { 
        // Use the current page..
        $page = $PIVOTX['pages']->getCurrentPage();
    }

    if (!empty($page['uid'])) {

        $title = get_default($params['title'], $page['title']);

        $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date']);
        
        if (!empty($params['hrefonly'])) {
            $output = $pagelink;
        } else {
            $output = sprintf("<a href='%s' title='%s'>%s</a>", $pagelink, cleanAttributes($page['title']), $title);
        }
        

        return $output;

    } else {
        debug(sprintf("Can't create page link since uid isn't set. (Page '%s')", $params['page']));
        return '';
    }    
    
}



/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_weblog($params, &$smarty) {
    global $PIVOTX;
  
    $weblog = $PIVOTX['weblogs']->getWeblog($params['weblog']);

    $title = get_default($params['title'], $weblog['name']);

    if (!empty($params['hrefonly'])) {
        $output = $weblog['link'];
    } else {
        $output = sprintf("<a href='%s' title='%s'>%s</a>", $weblog['link'], cleanAttributes($weblog['name']), $title);
    }
    

    return $output;

  
}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_entry($params, &$smarty) {
    global $PIVOTX;
    
    $vars = $smarty->get_template_vars();

    if (!empty($params['entry'])) {
        $params['uid'] = $params['entry'];
    } else {
        $params['uid'] = get_default($params['uid'], $vars['entry']['uid']);
    }

    // Abort immediately if uid isn't set.
    if (empty($params['uid'])) {
        debug("Can't create entry link since uid isn't set.");
        return '';
    }

    $text = get_default($params['text'], "%title%");
    $title = get_default($params['title'], "%title%");

    $link = makeFilelink($params['uid'], $params['weblog'], "");

    if ($params['query'] !='' ) {
        if (strpos($link,"?")>0) {
            $link .= '&amp;'.$params['query'];
        } else {
            $link .= '?'.$params['query'];
        }
    }
    
    if (!empty($params['hrefonly'])) {
        
        $output = $link;
        
    } else {
       
        if (isset($vars['entry']) && ($vars['entry']['code'] == $params['uid'])) {
            $entry = $vars['entry'];
        } else {
            $temp_db = new db(false);
            $entry = $temp_db->read_entry($params['uid']);
        }
    
        $text = str_replace('%title%', $entry['title'], $text);
        $text = str_replace('%subtitle%', $entry['subtitle'], $text);
        $text = format_date($entry['date'], $text );
    
        $title = trim($title);
        if (!empty($title)) {
            $title = str_replace('%title%', $entry['title'], $title);
            $title = str_replace('%subtitle%', $entry['subtitle'], $title);
            $title = format_date($entry['date'], $title );
        }
    
        $output = sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, cleanAttributes($title) ,$text);

    }
    
    return $output;

}



/**
 * Insert the _sub_link_list.html sub-template. Test for older versions as well.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_link_list($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    $templatedir = $vars['templatedir'];

    if ($smarty->template_exists($templatedir."/link_list.html")) {
        $output = $smarty->fetch($templatedir."/link_list.html");
    } else if ($smarty->template_exists($templatedir."/_aux_link_list.html")) {
        $output = $smarty->fetch($templatedir."/_aux_link_list.html");
    } else if ($smarty->template_exists($templatedir."/_sub_link_list.html")) {
        $output = $smarty->fetch($templatedir."/_sub_link_list.html");
    } else {
        $output = "<!-- _sub_link_list.html doesn't exist! -->";
    }

    return $output;

}




/**
 * Returns the local absolute URL to the (current) weblog directory.
 *
 * @return string
 */
function smarty_log_dir() {
    global $PIVOTX;

    return $PIVOTX['weblogs']->get('','link');
}

/**
 * Returns the global absolute URL to the (current) weblog directory.
 *
 * $param string $weblog
 * @return string
 */
function smarty_log_url($weblog = '') {
    global $PIVOTX;

    return $PIVOTX['paths']['host'] . $PIVOTX['weblogs']->get($weblog,'link');
}



/**
 * Link to the next entry
 *
 * @param array $params
 * @return string
 */
function smarty_nextentry($params) {
    global $PIVOTX;

    // This tag is only allowed on entrypages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = clean_params($params);

    // initialize a temporary db..
    $temp_db = new db(FALSE);

    // we fetch the next one, until we get one that is set to 'publish'
    $get_next_amount = 1;
    do {
        $next_code=$PIVOTX['db']->get_next_code($get_next_amount);
        if ($next_code) {
            $temp_entry = $temp_db->read_entry($next_code);
            $myweblogs = $PIVOTX['weblogs']->getWeblogsWithCat($temp_entry['category']);

            if ($params['incategory']==true) {

                // it's 'ok' if the entry shares at least one category with the current entry.
                if (count(array_intersect($PIVOTX['db']->entry['category'], $temp_entry['category']))>0) {
                    $ok = true;
                } else {
                    $ok = false;
                }

            } else {

                // it's 'ok' if the entry is in the current weblog.
                $myweblogs = $PIVOTX['weblogs']->getWeblogsWithCat($temp_entry['category']);

                if (in_array($PIVOTX['weblogs']->getCurrent(), $myweblogs)) {
                    $ok = true;
                } else {
                    $ok = false;
                }

            }
        }
        $get_next_amount++;

    } while ( !($next_code===FALSE) && !(($temp_entry['status']=="publish") && $ok) );

    unset($temp_db);

    $text = get_default($params['text'], '&nbsp;&nbsp;&raquo; <a href="%link%">%title%</a>');
    $cutoff = get_default($params['cutoff'], 20);

    if ($next_code) {
        $title= (strlen($temp_entry['title'])>2) ? $temp_entry['title'] : substr($temp_entry['introduction'],0,100);
        $link=makeFilelink($temp_entry, "", "");
        $output=$text;
        $output=str_replace("%link%", $link, $output);
        $output=str_replace("%code%", $next_code, $output);
        $output=str_replace("%title%", trimtext($title,$cutoff), $output);
        $output=str_replace("%subtitle%", trimtext($temp_entry['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);

    } else {
        return "";
    }

}





/**
 * Inserts a list of pages
 */
function smarty_pagelist($params, &$smarty) {
    global $PIVOTX;
    
    $params = clean_params($params);

    $chapterbegin = get_default($params['chapterbegin'], "<strong>%chaptername%</strong><br /><small>%description%</small><ul>", true);
    $pageshtml = get_default($params['pages'], "<li %active%><a href='%link%' title='%subtitle%'>%title%</a></li>");
    $chapterend = get_default($params['chapterend'], "</ul>", true);

    // If we use 'isactive', set up the $pageuri and $isactive vars.
    if (!empty($params['isactive'])) {
        // Get the current page uri.
        $smartyvars = $smarty->get_template_vars();
        $pageuri = get_default($smartyvars['pageuri'], "");
        $isactive = $params['isactive'];
    } else {
        $pageuri = "";
        $isactive = "";
    }

    if (isset($params['onlychapter']) && 
            (($params['onlychapter'] != '') || is_numeric($params['onlychapter']))) {
        $onlychapter_bool = true;
        $onlychapter_arr = explode(',', $params['onlychapter']);
        $onlychapter_arr = array_map('trim', $onlychapter_arr);
        $onlychapter_arr = array_map('strtolower', $onlychapter_arr);
    } elseif (isset($params['excludechapter']) &&
            (($params['excludechapter'] != '') || is_numeric($params['excludechapter']))) {
        $excludechapter_bool = true;
        $excludechapter_arr = explode(',', $params['excludechapter']);
        $excludechapter_arr = array_map('trim', $excludechapter_arr);
        $excludechapter_arr = array_map('strtolower', $excludechapter_arr);
    }


    $chapters = $PIVOTX['pages']->getIndex();
    $output = "";

    // Iterate through the chapters
    foreach ($chapters as $key => $chapter) {

        // If there is no pages, we skip this chapter
        if (count($chapter['pages']) == 0) {
            continue;
        }

        // We also skip any orphaned pages
        if (strcmp($key,"orphaned") == 0) {
            continue;
        }

        // If 'onlychapter' is set, we should display only the pages in one of those chapters,
        // and skip all the others. If 'excludechapter' is set, we should exclude all those 
        // chapters. You can use either the name or the uid.
        if ($onlychapter_bool) {
            $continue = true;
            foreach ($onlychapter_arr as $onlychapter) { 
                if ((strtolower($chapter['chaptername'])==$onlychapter) || 
                        (is_numeric($onlychapter) && ($key==$onlychapter))) {
                    $continue = false;
                    break;
                }
            }
            if ($continue) {
                continue; // skip it!
            }
        } elseif ($excludechapter_bool) {
            $continue = false;
            foreach ($excludechapter_arr as $excludechapter) { 
                if ((strtolower($chapter['chaptername'])==$excludechapter) || 
                        (is_numeric($excludechapter) && ($key==$excludechapter))) {
                    $continue = true;
                    break;
                }
            }
            if ($continue) {
                continue; // skip it!
            }
        }


        if($params['sort'] == "title") {
            asort($chapter['pages']);
        }
        
        $pages = array();
        
        // Iterate through the pages
        foreach ($chapter['pages'] as $page) {

            if(in_array($page['uri'], explode(",",$params['exclude']))) {
                continue;
            }

            if ($page['status'] != 'publish') {
                continue; // skip it!
            }

            // Check if the current page is the 'active' one.
            if (!empty($isactive) && ($page['uri']==$pageuri)) {
                $thisactive = $isactive;
            } else {
                $thisactive = "";
            }

            $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date'], $params['weblog']);

            // add the page to output
            $temp_output = $pageshtml;
            $temp_output = str_replace("%title%", $page['title'], $temp_output);
            $temp_output = str_replace("%subtitle%", $page['subtitle'], $temp_output);
            $temp_output = str_replace("%user%", $page['user'], $temp_output); // To do: filter this to nickname, email, etc.
            $temp_output = str_replace("%date%", $page['date'], $temp_output); // To do: allow output formatting.
            $temp_output = str_replace("%link%", $pagelink, $temp_output);
            $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
            $temp_output = str_replace("%active%", $thisactive, $temp_output);
            $pages[ $page['uri'] ] = $temp_output;

        }

        // Only add the chapter if there are any published and (non-excluded) pages.
        if (count($pages) > 0) {        
            if($params['sort'] == "title") {
                ksort($pages);
            }       

            // Add the chapterbegin to output
            $temp_output = $chapterbegin;
            $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
            $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
            $output .= $temp_output;

            // Add the pages
            $output .= (implode("\n", $pages));      

            // Add the chapterend to output
            $temp_output = $chapterend;
            $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
            $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
            $output .= $temp_output;
        }
    }

    return entifyAmpersand($output);

}



/**
 * Creates a way to navigate between pages.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_paging($params, &$smarty) {
    global $PIVOTX, $modifier;

    $params = clean_params($params);

    $action = get_default($params['action'], "digg");
    $showalways = get_default($params['showalways'], false);

    $funcs = new Paging("paging");

    // Check if we are called correctly and on the correct page
    $msg = $funcs->sanity_check($action);
    if ($msg != "" && $showalways==false) {
        return $msg;
    }

    // Currently only finds the offset
    $msg = $funcs->setup($action);
    if ($msg != "" && $showalways==false) {
        return $msg;
    }

    $subweblogs = $PIVOTX['weblogs']->getSubweblogs();

    $num_entries = 0;
    $cats = array();

    // Find the number of entries displayed on the page and the categories, as
    // defined in the weblog configuration, unless specified as a parameter
    if (!empty($params['showme'])) {
        $num_entries = intval($params['showme']);
    } else {
        foreach ($subweblogs as $subweblog) {
            $subweblog = $PIVOTX['weblogs']->getSubweblog('', $subweblog);
            $num_entries = max($subweblog['num_entries'], $num_entries);
            $cats[] = $subweblog['categories'];
        }
    }

    // If we have a 'c=' parameter, use the cats in that to display..
    // To prevent weird things from happening, we only allow changing weblogs
    // with a name like 'default', 'standard', 'main' or 'weblog'.
    // Alternatively, we check if the template specifies the categories to
    // display, like [[ weblog name='weblog' category="default, movies, music" ]]
    if (!empty($modifier['category'])) {
        $cats = explode(",",safe_string($modifier['category']));
        $cats = array(array_map("trim", $cats));
    } else if (!empty($params['category'])) {
        $cats = explode(",",safe_string($params['category']));
        $cats = array(array_map("trim", $cats));
        $params['catsinlink']=true;
    } else {
        // else we just display the categories as they're found in the weblog
        // configuration, in the above section.
        // We have to keep the subweblogs separate, because we need to be able to figure
        // out which subweblog has the most entries, and _not_ the entries combined.
    }

    return $funcs->doit($action, $text, $cats, $num_entries, $params);

}



/**
 * Inserts a nice button with a link to the pivotx website.
 *
 * @return string
 */
function smarty_pivotxbutton() {
    global $PIVOTX, $build;

    list( $width,$height) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/pivotxbutton.png' ) ;
    $image   = $PIVOTX['paths']['pivotx_url'].'pics/pivotxbutton.png' ;
    $alttext = __('Powered by'). " " . strip_tags($build) ;

    $output  = '<a href="http://www.pivotx.net/" title="'.$alttext.'" class="badge">' ;
    $output .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'" alt="'.$alttext.'" ' ;
    $output .= 'class="badge" longdesc="http://www.pivotx.net/" /></a>';

    return $output;
}



/**
 * Creates a permanent link to the current entry (in the archives).
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_permalink($params, &$smarty) {
    
    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    $link = makeArchiveLink()."#e".$vars['code'];

    $text = str_replace('%title%', $vars['title'], $params['text']);
    $text = format_date($vars['date'], $text );
    $title = trim($params['title']);
    if (!empty($title)) {
        $title = str_replace('%title%', strip_tags($vars['title']), $title);
        $title = format_date($vars['date'], $title );
    }

    $code = sprintf('<a href="%s" title="%s">%s</a>', $link, cleanAttributes($title), $text);

    return $code;
}




/**
 * Returns the local absolute URL to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['pivotx_url'];
}


/**
 * Returns the local path to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_path() {
    global $PIVOTX;

    return $PIVOTX['paths']['pivotx_path'];
}


/**
 * Returns the global absolute URL to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_url() {
    global $PIVOTX;

    return $PIVOTX['paths']['host'].$PIVOTX['paths']['pivotx_url'];
}


/**
 * Returns the local absolute URL to the extensions directory.
 *
 * @return string
 */
function smarty_extensions_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['extensions_url'];
}


/**
 * Returns the global absolute URL to the extensions directory.
 *
 * @return string
 */
function smarty_extensions_url() {
    global $PIVOTX;

    return $PIVOTX['paths']['host'].$PIVOTX['paths']['extensions_url'];
}


/**
 * Smarty tag to insert a popup to an image..
 *
 * First we check if we can use Jquery and whether extensions/thickbox/ is
 * present. If so, we use Thickbox. If not, we use the 'old style' popup.
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_popup ($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $filename = $params['file'];
    $thumbname = $params['description'];
    $org_thumbname = $params['description'];
    $alt = cleanAttributes($params['alt']);
    $align = get_default($params['align'], "center");
    // $border = get_default($params['border'], 0); -- border is deprecated

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];

    // Fix Thumbname, perhaps use a thumbname, instead of textual link
    if ( empty($thumbname) || ($thumbname=="(thumbnail)") ) {
        $thumbname = make_thumbname($filename);
    }


    // If the thumbnail exists, make the HTML for it, else just use the text for a link.
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname )) {

        $ext=getextension($thumbname);

        if ( ($ext=="jpg")||($ext=="jpeg")||($ext=="gif")||($ext=="png") ) {
            if ($align=="center") {
                $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class='pivotx-popupimage'/>",
                    $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $alt
                );
            } else {
                $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class='pivotx-popupimage align-%s'/>",
                    $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $alt, $align
                );
            }
        } else {
            $thumbname = $org_thumbname;
        }

    } else {

        if (strlen($org_thumbname)>2) {
            $thumbname = $org_thumbname;
        } else {
            $thumbname = "popup";
        }
    }


    // Prepare the HMTL for the link to the popup..
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = $PIVOTX['paths']['upload_base_url'].$filename ;

        $code = sprintf( "<a href='%s' class=\"thickbox\" title=\"%s\" rel=\"entry-%s\" >%s</a>",
                $filename,
                $alt,
                intval($entry['code']),
                $thumbname
            );

        if( 'center'==$align ) {
            $code = '<div class="pivotx-wrapper">'.$code.'</div>' ;
        }
    } else {
        debug("Rendering error: could not popup '$filename'. File does not exist.");
        $code = "<!-- Rendering error: could not popup '$filename'. File does not exist. -->";
    }


    // If the hook for the thickbox includes in the header was not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'thickboxIncludeCallback');

    return $code;

}



/**
 * Link to the previous entry
 *
 * @param array $params
 * @return string
 */
function smarty_previousentry($params) {
    global $PIVOTX;

    // This tag is only allowed on entrypages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = clean_params($params);

    // initialize a temporary db..
    $temp_db = new db(FALSE);

    // we fetch the next one, until we get one that is set to 'publish'
    $get_prev_amount = 1;
    do {
        $prev_code=$PIVOTX['db']->get_previous_code($get_prev_amount);
        if ($prev_code) {
            $temp_entry = $temp_db->read_entry($prev_code);

            if ($params['incategory']==true) {

                // it's 'ok' if the entry shares at least one category with the current entry.
                if (count(array_intersect($PIVOTX['db']->entry['category'], $temp_entry['category']))>0) {
                    $ok = true;
                } else {
                    $ok = false;
                }

            } else {

                // it's 'ok' if the entry is in the current weblog.
                $myweblogs = $PIVOTX['weblogs']->getWeblogsWithCat($temp_entry['category']);

                if (in_array($PIVOTX['weblogs']->getCurrent(), $myweblogs)) {
                    $ok = true;
                } else {
                    $ok = false;
                }

            }

        }
        $get_prev_amount++;

    } while ( !($prev_code===FALSE) && !(($temp_entry['status']=="publish") && $ok) );

    unset($temp_db);

    $text = get_default($params['text'], '&laquo; <a href="%link%">%title%</a>');
    $cutoff = get_default($params['cutoff'], 20);

    if ($prev_code) {
        $title= (strlen($temp_entry['title'])>2) ? $temp_entry['title'] : substr($temp_entry['introduction'],0,100);
        $link = makeFilelink($temp_entry, "", "");
        $output=$text;
        $output=str_replace("%link%", $link, $output);
        $output=str_replace("%code%", $prev_code, $output);
        $output=str_replace("%title%", trimtext($title,$cutoff), $output);
        $output=str_replace("%subtitle%", trimtext($temp_entry['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);
    } else {
        return "";
    }
}




/**
 * Print_r a variable/array from smarty templates
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_print_r($params, &$smarty) {

    $params = clean_params($params);

    echo "\n<div class='debug-container'>\n<pre>\n";
    print_r($params['var']);
    echo "\n<br />--</pre>\n</div>&nbsp;\n";

}


/**
 * Print a backtrace of called functions from smarty templates
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_backtrace($params, &$smarty) {
    global $PIVOTX;

    if(!function_exists('debug_backtrace')) {
        return 'function debug_backtrace does not exist.';
    }

    $MAXSTRLEN = 30;

    ob_start();

    $my_trace = array_reverse(debug_backtrace());

    foreach($my_trace as $t)    {
        echo '<pre>&raquo; ';
        if(isset($t['file'])) {
            $line = basename(dirname($t['file'])). '/' .basename($t['file']) .", line " . $t['line'];
            printf("%-30s", $line);
        } else {
            // if file was not set, I assumed the functioncall
            // was from PHP compiled source (ie XML-callbacks).
            $line = '<PHP inner-code>';
            printf("%-30s", $line);
        }

        echo "\n    - ";

        if(isset($t['class'])) echo $t['class'] . $t['type'];

        echo $t['function'];

        if (isset($t['args']) && sizeof($t['args']) > 0) {
            $args= array();
            foreach($t['args'] as $arg) {
                if (is_null($arg)) $args[] = 'null';
                else if (is_array($arg)) $args[] = 'Array['.sizeof($arg).']';
                else if (is_object($arg)) $args[] = 'Object:'.get_class($arg);
                else if (is_bool($arg)) $args[] = $arg ? 'TRUE' : 'FALSE';
                else if ( (is_int($arg))||(is_float($arg)) ) $args[] = $arg;
                else {
                    $arg = (string) @$arg;
                    $str = htmlspecialchars(substr($arg,0,$MAXSTRLEN));
                    $str = str_replace("\n", "", str_replace("\r", "", $str));
                    $str = preg_replace("/(\s)/i", " ", $str);
                    if (strlen($arg) > $MAXSTRLEN) $str .= '~';

                    $args[] = "'".$str."'";
                }
            }

            echo '( ' . implode(" , ", $args) .  ' )';
        }   else {
            echo '()';
        }

        echo "\r\n</pre>\n";
    }

    $output = ob_get_contents();
    ob_end_clean();

    echo "\n<div class='debug-backtrace'>\n";
    echo $output;
    echo "\n<br />--</div>&nbsp;\n";
}


/**
 * Inserts previously filled fields for commenting. They can come from either
 * a previous submit (when previewing, or when an error in the form occurred)
 * or from the cookie.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_remember($params, &$smarty) {
    global $PIVOTX, $temp_comment;
    static $default_values;

    $params = clean_params($params);
    $name = $params['name'];

    // Only calculate previous fields once
    if (!is_array($default_values)) {
        $default_values = array();

        // Get the cookies in an array.. (Why aren't we just using $_COOKIE?)
        if (isset($_SERVER['HTTP_COOKIE']))  {
            foreach (explode(";", $_SERVER['HTTP_COOKIE']) as $cookie) {
                list ($key, $value)= explode("=", $cookie);
                $default_values[trim($key)] = urldecode(trim($value));
            }
        } 

        if ( !empty($temp_comment) && is_array($temp_comment) ) {

            $default_values = $temp_comment;

        } else if (!empty($_COOKIE['pivotcomment'])) {

            $cookie = explode('|', $_COOKIE['pivotcomment']);

            $default_values['name'] = $cookie[0];
            $default_values['email'] = $cookie[1];
            $default_values['url'] = $cookie[2];
            $default_values['reguser'] = $cookie[3];
            $default_values['notify'] = $cookie[4];
            $default_values['discreet'] = $cookie[5];
            $default_values['rememberinfo'] = 1;
        } else {
            // Check if this is a logged in registered visitor
            require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
            $visitors = new Visitors();
            if ($visitor = $visitors->isLoggedIn()) {
                $default_values['name'] = $visitor['name'];
                $default_values['email'] = $visitor['email'];
                $default_values['url'] = $visitor['url'];
                $default_values['notify'] = $visitor['notify_default'];
                $default_values['discreet'] = (1 - $visitor['show_address']);
            }
        }

        // Posted values should override cookies since they are newer.
        // (The corresponding posted keys start with "piv_".)
        foreach ($_POST as $key => $value) {
            if (substr($key,0,4) == 'piv_') {
                $default_values[substr($key,4)] = urldecode(trim($value));
            }
        }

        // Execute hooks, if present, and (potentially) override existing values.
        $hookname = "remember"; 
        $hook_values = $PIVOTX['extensions']->executeHook('template', $hookname, $default_values );
        if (is_array($hook_values)) {
            $default_values = $hook_values;
        }
    }

    switch($name) {
        case 'all':
            echo "<h1>koekies</h1><pre>cookies:";
            print_r($_COOKIE);
            echo "</pre>";
            break;
        case 'name':
            return (!empty($default_values['name'])) ? $default_values['name'] : "";
            break;
        case 'email':
            return (!empty($default_values['email'])) ? $default_values['email'] : "";
            break;
        case 'url':
            return (!empty($default_values['url'])) ? $default_values['url'] : "";
            break;
        case 'comment':
            return (!empty($default_values['comment'])) ? $default_values['comment'] : "";
            break;
        case 'rememberinfo':
            return (!empty($default_values['rememberinfo'])) ? "checked='checked'" : "";
            break;
        case 'notify':
            return (!empty($default_values['notify'])) ? "checked='checked'" : "";
            break;
            case 'discreet':
            return (!empty($default_values['discreet'])) ? "checked='checked'" : "";
            break;
        case 'reguser':
            return (!empty($default_values['piv_reguser'])) ? $default_values['piv_reguser'] : "";
            break;
    }


}




/**
 * Resets the [[ $page ]] variable back to what it was, before it was
 * set by [[ getpage ]].
 *
 * @see smarty_getpage
 * @return string;
 */
function smarty_resetpage($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $oldpage = $vars['oldpage'];
        
    // Set the 'page' variable in smarty to 'oldpage', as it was before [[ getpage ]]
    if (is_array($oldpage)) {
        $smarty->assign('page', $oldpage);
        foreach($oldpage as $key=>$value) {
            $smarty->assign($key, $value);
        }
    }

    return "";
}



/**
 * Insert a button with a link to the RSS XML feed.
 *
 * @return string
 */
function smarty_rssbutton() {
    global $PIVOTX ;

    // if we've disabled the Atom feed for this weblog, return nothing.
    if ($PIVOTX['weblogs']->get('', 'rss')!=1) {
        return "";
    }

    $filename = makeFeedLink("rss");

    $image    = $PIVOTX['paths']['pivotx_url'].'pics/rssbutton.png' ;
    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/rssbutton.png' ) ;
    $alttext  = __('XML: RSS Feed') ;

    $output   = '<a href="'.$filename.'" title="'.$alttext.'" rel="nofollow" class="badge">';
    $output  .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'"' ;
    $output  .= ' alt="'.$alttext.'" class="badge" longdesc="'.$filename.'" /></a>' ;

    return $output;
}


/**
 * Displays the search-box
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_search($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $formname = get_default($params['formname'], __('Search for words used in entries on this website'));
    $fieldname = get_default($params['fieldname'], __('Enter the word[s] to search for here:'));
    $placeholder = get_default($params['placeholder'], __('Enter searchterms'));

    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $url = $PIVOTX['paths']['site_url']."index.php?q=";
    } else {
        $prefix = get_default($PIVOTX['config']->get('localised_search_prefix'), "search");
        $url = $PIVOTX['paths']['site_url'].makeURI($prefix);
    }

    if ($params['template']!='') {
        $url .= "?t=".$params['template'];
    }

    $output  = '<form method="post" action="'.$url.'"  class="pivotx-search">'."\n" ;
    $output .= '<fieldset><legend>'.$formname.'</legend>'."\n" ;
    $output .= '<label for="search">'.$fieldname.'</label>'."\n" ;
    $output .= '<input id="search" type="text" name="q" class="searchbox" value="' ;
    $output .= $placeholder.'" onblur="if(this.value==\'\') this.value=\'';
    $output .= $placeholder.'\';" onfocus="if(this.value==\'' .$placeholder;
    $output .= '\') this.value=\'\'; this.select();return true;" />'."\n" ;

    if($params['button'] !== false) {
        $button_name = get_default($params['button'], __('Search!'));
        $output .= '<input type="submit" class="searchbutton" value="'.$button_name.'" />' ;
    }

    $weblog = $PIVOTX['weblogs']->getCurrent();
    if (para_weblog_needed($weblog)) {
        $output .= '<input type="hidden" name="w" value="'.$weblog.'" />'."\n";
    }
    $output .= '</fieldset></form>'."\n" ;

    return $output ;
}




/**
 * Returns a link to the current page.
 *
 * @params array $params
 * @return string
 */
function smarty_self($params) {
    global $PIVOTX;

    $params = clean_params($params);

    if ($params['includehostname']==1) {
        $output = $PIVOTX['paths']['host'];
    } else {
        $output = "";
    }


    $output .= entifyAmpersand($_SERVER['REQUEST_URI']);

    return $output;


}




/**
 * Returns the sitename for the PivotX installation.
 *
 * @return string
 */
function smarty_sitename() {
    global $PIVOTX;

    $output = $PIVOTX['config']->get('sitename');

    return entifyAmpersand($output);
}



/**
 * Returns the HTML for the SpamQuiz (that should go inside the comment form).
 *
 * @return string
 */
function smarty_spamquiz($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    if ($PIVOTX['config']->get("spamquiz") != 1) {
        return "<!-- SpamQuiz spam protection is disabled in Pivot configuration -->";
    }

    $p_sIformat = get_default($params['intro'], "<div id=\"spamquiz\">
\t\t<div class=\"commentform_row\">
\t\t\t<small>%intro%</small><br />");
    

    $p_sQformat = get_default($params['format'], "
\t\t\t<label for=\"spamquiz_answer\"><b>%question%</b></label>
\t\t\t<input type=\"text\" class=\"commentinput\" name=\"spamquiz_answer\" id=\"spamquiz_answer\"  %name_value% />
\t\t</div>
\t</div>");

    require_once($PIVOTX['paths']["pivotx_path"]."modules/module_spamkiller.php");

    // Is the entry old enough?
    $entryDate = substr($PIVOTX['db']->entry['date'], 0, 10);
    $then = strtotime($entryDate);
    $secsPerDay = 60*60*24;
    $now = strtotime('now');
    $diff = $now - $then;
    $dayDiff = ($diff/$secsPerDay);
    $numDaysOld = (int)$dayDiff;

    if($numDaysOld<$PIVOTX['config']->get("spamquiz_age")) {
        return "<!-- SpamQuiz disabled - not old enough entry -->";
    }

    $sTheAnswer = $_COOKIE["spamquiz_answer"];

    if(trim($PIVOTX['config']->get("spamquiz_answer")) != $_COOKIE["spamquiz_answer"]) {
        $sTheAnswer = '';
    }

    $sQuestion = $PIVOTX['config']->get("spamquiz_question");
    $sIntro = $PIVOTX['config']->get("spamquiz_explain");
    $sIntroFormat = stripslashes(str_replace("%intro%", $sIntro, $p_sIformat));
    $sQuestionFormat = stripslashes(str_replace("%question%", $sQuestion, $p_sQformat));
    $sQuestionFormat = str_replace("%name_value%", "value=\"$sTheAnswer\"", $sQuestionFormat);
    $sHTML = $sIntroFormat.$sQuestionFormat;

    return $sHTML;

}


/**
 * Smarty tag for [[ subtitle ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_subtitle($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $subtitle = parse_string($vars['subtitle']);

    // If 'strip=1', we strip html tags from the subtitle.
    if ($params['strip']==1) {
        $title = strip_tags($title);
    }

    return entifyAmpersand($subtitle);

}


/**
 * Smarty tag for [[ chaptername ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_chaptername($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $chaptername = get_default($vars['page']['chaptername'], $vars['chaptername']);

    return entifyAmpersand($chaptername);

}


/**
 * Display a small tagcloud.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_tagcloud($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $minsize = get_default($params['minsize'], $PIVOTX['config']->get('tag_min_font'));
    $maxsize = get_default($params['maxsize'], $PIVOTX['config']->get('tag_max_font'));
    $amount = get_default($params['amount'], $PIVOTX['config']->get('tag_cloud_amount'));
    $sep = get_default($params['sep'], ", " );

    if (!empty($params['exclude'])) {
        $exclude = explode(',', $params['exclude']);
    } else { 
        $exclude = "";
    }

    $tagcosmos = getTagCosmos($amount, '', '', $exclude);

    // This is the factor we need to calculate the EM sizes. $minsize is 1 em,
    // $maxsize will be ($maxsize / $minsize) EM.. Take care if $tagcosmos['maxvalue'] == $tagcosmos['minvalue']
    if ($tagcosmos['maxvalue'] != $tagcosmos['minvalue']) {
        $factor = ($maxsize - $minsize) / ($tagcosmos['maxvalue'] - $tagcosmos['minvalue']) / $minsize;
    } else {
        $factor = 0;
    }

    foreach($tagcosmos['tags'] as $key => $value)   {

        // Calculate the size, depending on value.
        $nSize = sprintf("%0.2f", (1 + ($value - $tagcosmos['minvalue']) * $factor));

        $htmllinks[$key] = sprintf("<a style=\"font-size:%sem;\" href=\"%s\" rel=\"tag\" title=\"%s: %s, %s %s\">%s</a>",
            $nSize,
            tagLink($key,$template),
            __('Tag'),
            $key,
            $value,
            __('Entries'),
            $key
        );
    }

    $output = "<div id='tagcloud' style='font-size: {$minsize}px;'>";
    $output .= implode($sep, $htmllinks);

    /* -- todo: fix this for multiple weblogs..
    $Current_weblog = $PIVOTX['weblogs']->getCurrent();
    if (para_weblog_needed($Current_weblog)) {
        $para .= "?w=".para_weblog($Current_weblog);
        $para .= ($template!='') ? "&amp;t=$template" : '';
    } else {
        $para = ($template!='') ? "?t=$template" : '';
    } */
    $para = "";

    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $link = $PIVOTX['paths']['site_url']."?x=tagpage";
    } else {
        $link = $PIVOTX['paths']['site_url']."tags/";
    }

    if($tagcosmos['amount']>$amount) {
        $output .= sprintf('<em>(<a href="%s%s">%s</a>)</em>',
           $link, $para, __('all')
        );
    }
    
    $output .= "</div>";

    return $output;

}


/**
 * Get a concise list of the entry's tags.
 *
 * @return string The text to display.
 * @param string $text The output format. The default
 *  value is "Used tags: %tags%". (or the localised version thereof)
 * @param string $sep The separator between the tags.
 *  The default value is ", ".
 */
function smarty_tags($params, &$smarty) {

    $params = clean_params($params);

    $text = get_default($params['text'], __('Used tags').": %tags%" );
    $sep = get_default($params['sep'], ", " );
    $prefix = get_default($params['prefix'], "" );
    $postfix = get_default($params['postfix'], "" );

    if ($params['textonly']==true) {
        // Just the tags, no HTML, no links.. 
        $tags = getTags(false);
    } else {
        // Output with links and stuff.
        $tags = getTags(true);
    }

    if (count($tags)>0) {
        $output = implode($sep, $tags);
        $output = str_replace("%tags%", $output, $text);
    } else {
        $output = '';
    }

    return $prefix.$output.$postfix;

}


/**
 * Returns the local absolute URL to the template directory.
 *
 * @param array $params
 * @return string
 */
function smarty_template_dir($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $templatedir = $vars['templatedir'];

    if ( empty($templatedir) || ($templatedir=="/") || ($params['base']==true) ) {
        $path = $PIVOTX['paths']['templates_url'];
    } else {
        $path = $PIVOTX['paths']['templates_url'] . $templatedir . '/' ;
    }

    return $path;
}



/**
 * Adds the textile editor thingamajig to the comments form. It's somewhat
 * confusingly called [[ textilepopup ]] for backwards compatibility.
 *
 * @return string
 */
function smarty_textilepopup() {
    global $PIVOTX;


    if ($PIVOTX['weblogs']->get('', 'comment_textile')==1) {

        $tageditorpath = $PIVOTX['paths']['pivotx_url']."includes/markitup/";

        // If the hook for the jquery includes in the header was not yet
        // installed, do so now..
        $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

        // Insert the link to the CSS file as a Hook extension.
        $PIVOTX['extensions']->addHook(
            'after_parse',
            'insert_before_close_head',
            "\t<link rel='stylesheet' type='text/css' href='{$tageditorpath}markitup-mini.css'/>\n"
            );
        $output .= "<script type='text/javascript' src='{$tageditorpath}jquery.markitup.js'></script>\n";
        $output .= "<script type='text/javascript' src='{$tageditorpath}set.js'></script>\n";
        $output .= "<script language='javascript' type='text/javascript'>\n";
        $output .= "jQuery(function($) {
                        jQuery('#piv_comment').markItUp(markitupminitextile);             
                    });";
        $output .= "</script>";


    } else {
        $output='';
    }

    return $output;

}


/**
 * Smarty tag for [[ title ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_title($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $title = parse_string($vars['title']);

    // If 'strip=1', we strip html tags from the title.
    if ($params['strip']==1) {
        $title = strip_tags($title);
    }

    return entifyAmpersand($title);

}


/**
 * Makes a link to the trackbacks on the current entry.
 */
function smarty_trackbacklink($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();

    $link = makeFilelink($vars['entry'], '', 'track');

    $trackcount=intval($vars['trackcount']);

    $text0 = get_default($params['text0'], __("No trackbacks"));
    $text1 = get_default($params['text1'], __("One trackback"));
    $textmore = get_default($params['textmore'], __("%num% trackbacks"));

    // special case: If comments are disabled, and there are no
    // trackbacks, just return an empty string..
    if ( ($trackcount == 0) &&  ($vars['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$trackcount)];
    $trackcount = $PIVOTX['locale']->getNumber($trackcount);

    $trackcount = str_replace("%num%", $trackcount, $text);
    $trackcount = str_replace("%n%", $vars['trackcount'], $trackcount);

    $tracknames=$vars['tracknames'];

    $weblog = $PIVOTX['weblogs']->getWeblog();
    if ($weblog['comment_pop']==1) {
        $output = sprintf("<a href='%s' ", $link);
        $output.= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $link, $weblog['comment_width'], $weblog['comment_height']);
        $output.= sprintf(" title=\"%s\" >%s</a>",$tracknames, $trackcount);
    } else {
        $output=sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, $tracknames, $trackcount);

    }

    return $output;
}

/**
 * Inserts the trackback URL for the current entry.
 *
 * The classes "pivotx-tracklink-text" and "pivotx-tracklink-url" can be used to style
 * the output.
 */
function smarty_tracklink() {
    global $PIVOTX;

    // check for entry's allow_comments, blocked IP address ...
    if ( (isset($PIVOTX['db']->entry['allow_comments']) && ($PIVOTX['db']->entry['allow_comments']==0)) ||
            (ip_check_block($_SERVER['REMOTE_ADDR']))  ) {
        return "";
    }

    $weblog = $PIVOTX['weblogs']->getWeblog();

    if (strlen($weblog['trackback_link_format'])>1) {
        $format = $weblog['trackback_link_format'];
    } else {
        $format = '<p><span class="pivotx-tracklink-text">Trackback link: </span>' . 
            '<span class="pivotx-tracklink-url">%url%</span></p>';
    }

    $tb_url = $PIVOTX['paths']['host'] . makeFilelink($PIVOTX['db']->entry['code'], '', '');
    $trackback = get_default($PIVOTX['config']->get('localised_trackback_name'), "trackback");
    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $tb_url .= "&amp;$trackback";
        $tb_getkey_url = $tb_url . "&amp;getkey";
    } else {
        $tb_url .= "/$trackback/";
        $tb_getkey_url = $tb_url . "?getkey";
    }
    if ($PIVOTX['config']->get('hardened_trackback') != 1)  {
        $output = str_replace("%url%", $tb_url, $format);
    } else {
        $tb_url = "<span id=\"tbgetter\">".__('Please enable javascript to generate a trackback url')."</span>";
        $tb_url .= "<script type=\"text/javascript\" src=\"$tb_getkey_url\"></script>\n";
        $tburl_gen = "<a href=\"#\"".
            " title=\"".__('Note: The url is valid for only 15 minutes after you opened this page!')."\"".
            " onclick=\"showTBURL(\'tbgetter\'); return false;\">".__('Click to view the trackback url')."</a>";
        $tb_url .= "\n<script type=\"text/javascript\">/*<![CDATA[*/\n".
            "showTBURLgen('tbgetter', '$tburl_gen');\n/*]]>*/</script>\n";

        $output = str_replace("%url%", $tb_url, $format);
    }

    return $output;

}


/**
 * Displays the amount of trackbacks for the current entry.
 */
function smarty_trackcount() {
    global $PIVOTX;

    $weblog = $PIVOTX['weblogs']->getWeblog();

    $trackcount=$PIVOTX['db']->entry['trackcount'];

    $text0 = get_default($params['text0'], __("No trackbacks"));
    $text1 = get_default($params['text1'], __("One trackback"));
    $textmore = get_default($params['textmore'], __("%num% trackbacks"));

    // special case: If comments are disabled, and there are no
    // trackbacks, just return an empty string..
    if ( ($trackcount == 0) && ($PIVOTX['db']->entry['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$trackcount)];
    $trackcount = $PIVOTX['locale']->getNumber($trackcount);

    $trackcount = str_replace("%num%", $trackcount, $text);
    $trackcount = str_replace("%n%", $PIVOTX['db']->entry['trackcount'], $trackcount);

    return $trackcount;
}

/**
 * Inserts a list of the names of people who left a trackback to the current entry.
 */
function smarty_tracknames() {
    global $PIVOTX;

    $tracknames=$PIVOTX['db']->entry['tracknames'];

    return $tracknames;
}



/**
 * Display a Tag, as used in the introduction or body
 *
 * @param string $tag
 * @param string $link
 * @param string $template
 * @return string
 */
function smarty_tt($params) {

    $params = clean_params($params);    
    
    $tag = $params['tag'];
    $link = $params['link'];
    $template = get_default($params['template'],'');

    if(strlen($link) > 0) {
        // If the external link doesn't have a protocol prefix, add it.
        if (strpos($link,"tp://") === false) {
            $link = "http://".$link;
        }
        $tag_url = $link;
    } else {
        $tag_url = tagLink($tag,$template);
    }

    return  '<a rel="tag" class="pivotx-taglink" href="'.$tag_url.'" title="'.
        __('Tagged external link').": $tag\">$tag</a>";

}



/**
 * Returns the local absolute URL to the upload directory.
 *
 * @return string
 */
function smarty_upload_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['upload_base_url'];
}



/**
 * Returns information about the author of the current entry or page. 
 *
 * It takes one optional parameter "field" to select what information to 
 * return. There is one special value "emailtonick" that will produce an 
 * encoded link to the e-mail address.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_user($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();
    $user = $PIVOTX['users']->getUser($vars['user']);
    $field = $params['field'];

    if (!$user) {
        $output = $vars['user'];
    } else if ($field=="") {
        $output = $user['username'];
    } else if ($field=="emailtonick") {
        if ($user['nickname']!="") {
            $output = encodemail_link($user['email'], $user['nickname'] );
        } else {
            $output = encodemail_link($user['email'], $user['username']);
        }
    } else {
        if (isset($user[$field])) {
            $output = $user[$field];
        } else {
            $output = $user['username'];
        }

    }

    return $output;
}


/**
 * Returns the 'via' information from the extended entry form as a link with 
 * a title.
 *
 * @param array $params
 * @return string
 */
function smarty_via($params) {
    global $PIVOTX;

    $params = clean_params($params);
    
    $format = get_default($params['format'], "[<a href='%link%' title='%title%'>via</a>]");
    
    if (strlen($PIVOTX['db']->entry['vialink']) > 4 ) {

        $output = $format;
        $output = str_replace("%link%", $PIVOTX['db']->entry['vialink'], $output);
        $output = str_replace("%title%", cleanAttributes($PIVOTX['db']->entry['viatitle']), $output);

        return $output;

    } else {

        return '';

    }

}


/**
 * Returns the text for a (sub)weblog.
 * 
 * The subweblog tag is a block tag, which means that it always has to 
 * have an accompanying closing subweblog tag. What's inside the tag is used to 
 * render the entries in that weblog. In fact, the contents can be seen as the 
 * template that is used for each entry. PivotX loops over the entries, and 
 * renders each one, using this 'sub template'. 
 *
 * @param array $params
 * @param string $format
 * @param object $smarty
 * @return string
 */
function smarty_weblog($params, $format, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($format)) { return ""; }

    // Store the template variables, so whatever happens in the subweblog
    // can't screw up the rest of the page.
    $templatevars = $smarty->get_template_vars();

    $output = cms_tag_weblog($params, $format);

    if ($output == '') {
        $output = get_default($params['noentries'], '<h2>' . __('No entries found')  . '</h2>', true);
    }

    // Restore the saved template variables..
    $smarty->_tpl_vars = $templatevars;

    return $output;

}





/**
 * Inserts a linked list to the the different weblogs.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_weblog_list($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);
    $aExclude= array();
    if(!empty($params['exclude'])) {
        $aExclude = explode(",", $params['exclude']);
	$aExclude = array_map("trim", $aExclude);
	$aExclude = array_map("safe_string", $aExclude);	
    }
    
    $Current_weblog = $PIVOTX['weblogs']->getCurrent();
    
    $format = get_default($params['format'], "<li %active%><a href='%link%' title='%payoff%'>%display%</a></li>");
    $active = get_default($params['current'], "class='activepage'");

    $output = array();

    $weblogs = $PIVOTX['weblogs']->getWeblogs();
    
    //echo "<pre>\n"; print_r($weblogs); echo "</pre>";

    foreach ($weblogs as $key=>$weblog) {

        if(in_array(safe_string($weblog['name']), $aExclude)) {
            continue;
        }

        $this_output = $format;
        
        $this_output = str_replace("%link%" , $weblog['link'], $this_output);
        $this_output = str_replace("%name%" , $weblog['name'], $this_output);
        $this_output = str_replace("%display%" , $weblog['name'], $this_output);
        $this_output = str_replace("%payoff%" , cleanAttributes($weblog['payoff']), $this_output);
        $this_output = str_replace("%internal%" , $key, $this_output);

        if ($Current_weblog == $key) {
            $this_output = str_replace("%active%" , $active, $this_output); 
        } else {
            $this_output = str_replace("%active%" , "", $this_output);             
        }

        $output[$weblog['name']] .= $this_output;

    }
    
    if($params['sort'] == "title") {
        ksort($output);
    }  
    
    return stripslashes(implode("\n", $output));

}





/**
 * Returns the ID of the current weblog.
 *
 * @return string
 */
function smarty_weblogid() {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->getCurrent();

    return $output;
}




/**
 * Returns the subtitle (payoff) of the current weblog. It takes one optional 
 * parameter "strip" which if equal to one, will remove all HTML tags from the 
 * output.
 *
 * @param array $params
 * @return string
 */
function smarty_weblogsubtitle($params) {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->get('', 'payoff');

    if ($params['strip']==true) {
        $output = strip_tags($output);
    }

    return entifyAmpersand($output);
}


/**
 * Returns the title (name) of the current weblog. It takes one optional 
 * parameter "strip" which if equal to one, will remove all HTML tags from the 
 * output.
 *
 * @param array $params
 * @return string
 */
function smarty_weblogtitle($params) {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->get('', 'name');

    if ($params['strip']==true) {
        $output = strip_tags($output);
    } else if (!empty($params['internal'])) {
        $output = $PIVOTX['weblogs']->getCurrent();
    }

    return entifyAmpersand($output);
}



/**
 * Inserts a block with the enabled widgets.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_widgets($params, &$smarty) {
    global $PIVOTX;

    $output = "";

    $PIVOTX['extensions']->executeHook('widget', $output, array('style'=>$params['forcestyle']));

    return $output;

}

/**
 * Returns the text 'registered' if the current visitor is (logged in and) registered.
 *
 * Useful in templates to set special classes for (logged in) registered 
 * visitors.
 * 
 * @param array $params
 * @return string
 */
function smarty_registered($params) {
    global $PIVOTX;
    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();
    if ($visitors->isLoggedIn()) {
        return 'registered';
    } else {
        return '';
    }
}

/**
 * Returns a link to the "comment user"/"registered visitor" page
 * (with the correct weblog selection). 
 *
 * It takes two (optional) smarty parameter - 'linktext' and 
 * 'linktext_logged_in'.
 *
 * @param array $params
 * @return string
 */
function smarty_register_as_visitor_link($params) {
    $url = makeVisitorPageLink();
    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();
    if ($visitors->isLoggedIn()) {
        $linktext = get_default($params['linktext_logged_in'], __('account'));
    } else {
        $linktext = get_default($params['linktext'], __('register/login'));
    }
    return "<a href='$url' class='pivotx-system-links'>$linktext</a>";
}


/**
 * Return localised 'yes' or 'no' dependant on $params['value']
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_yesno($params, &$smarty) {

    if ($params['value']==1) {
        return __('Yes');
    } else {
        return __('No');
    }

}




/**
 * @see $smarty->register_resource
 */
function db_get_template ($tpl_name, $tpl_source, &$smarty_obj) {

    if (isset($smarty_obj->custom_template[ $tpl_name ])) {
        $tpl_source = $smarty_obj->custom_template[ $tpl_name ];
        return true;
    } else {
        $tpl_source = "";
        return false;
    }

}

/**
 * @see $smarty->register_resource
 */
function db_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
     return true;
}

/**
 * @see $smarty->register_resource
 */
function db_get_secure($tpl_name, &$smarty_obj)
{
    // assume all templates are secure
    return true;
}

/**
 * @see $smarty->register_resource
 */
function db_get_trusted($tpl_name, &$smarty_obj)
{
    // not used for templates
}




/**
 * Handle Caching.
 *
 * Note: Most of the hooks related to caching are called from module_parser.php,
 * parseTemplate(). This is because this function is a smarty callback, and gets
 * called multiple times for each page (seperately for each include as wel as
 * recursive templates like [[body]], [[introduction]] and other block level tags)
 * The only exceptions is clear_cache(), for which we _do_ handle the hooks here.
 *
 * @see: http://www.smarty.net/manual/en/section.template.cache.handler.func.php
 */
function pivotx_cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null) {
    global $PIVOTX, $compressor;
    
    // create unique cache key, if we don't have one yet.
    if (empty($cache_id)) {
        $cache_id = "tpl_" . substr(md5($tpl_file.$cache_id.$compile_id),0,10);
    }

    $basename = removeextension(basename($tpl_file));

    // Set the filename of our cachefile..
    $cachefile = sprintf("%s%s_%s.cache",
        $PIVOTX['paths']['cache_path'],
        $cache_id,
        $basename
        );
        

    switch ($action) {
        
        case 'read':
            // Read a cached page from disk. This is also used for the is_cached() function.

            if (substr($tpl_file, 0, 3)!="db:") {

                if (file_exists($cachefile) && is_readable($cachefile) ) {
                    $cache_content = file_get_contents($cachefile);
                    debug("read cache: $tpl_file, $cache_id");
                    $result = true;
                } else {
                    $result = false;            
                }

            } else {
                $result = false;
            }
    
            break;
        
        case 'write':
            // save cache to database

            if (substr($tpl_file, 0, 3)!="db:") {
                
                // We split what's to be written to the cache in a $meta and $html part
                list($meta, $html) = split("}}", $cache_content);
                
                // Execute the hooks, if present.
                $PIVOTX['extensions']->executeHook('after_parse', $html);
                
                // If speedy_frontend is enabled, we compress our output here.
                if ($PIVOTX['config']->get('minify_frontend')) {
                    $minify = new Minify($html);
                    $html = $minify->minifyURLS();
                }                
               
                // Put $meta and $html back together..
                $cache_content = $meta . "}}" . $html;
                
                // Save the file to disk..    
                $fp = fopen($cachefile, "wb");
                fwrite($fp, $cache_content);
                fclose($fp);
            
                debug("write cache: $tpl_file, $cache_id");
                
                // We set the result to true, because regardless of whether we saved
                // successfully, we _did_ change the $cache_contents.
                $result = true; 
                
            } else {
                // Do not cache db:123456 (these are our own recursive templates)
                $result = false;
            }
            break;
        
        case 'clear':
            
            debug("clear from cache: $tpl_file, $cache_id");
            
            // Execute the cache_clear hook, if present.
            $PIVOTX['extensions']->executeHook('cache_clear', $basename);
            
            $dir = dir($PIVOTX['paths']['cache_path']);
            while (false !== ($file = $dir->read())) {
                if (strpos($file, $basename)>0) {  
                    unlink($PIVOTX['paths']['cache_path'].$file);
                }
            }            
                    

            break;
        
        default:
            // error, unknown action
            $return = false;
            break;
    }
    

    return $result;

}

?>
