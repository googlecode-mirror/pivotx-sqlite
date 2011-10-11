<?php
// - Extension: Hierarchical menus
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=hierarchical_menus
// - Description: Add hierarchical menus to your website
// - Date: 2009-01-12


/**
 * This extension will allow you to add hierarchical menus to your PivotX site.
 *
 * Let's suppose you have the following page structure:
 *
 * Chapter 'pages':
 * - page 'first page'
 * - page 'second page'
 * - page 'third page'
 *
 * Chapter 'second page'
 * - page 'first sub-item'
 * - page 'second sub-item'
 *
 * Usage: [[ menu firstchapter="pages" ]]
 *
 * Output:
 *
 * - first page
 * - second page
 *   - first sub-item
 *   - second sub-item
 * - third page
 *
 * You can use the following parameters:
 *
 * [[ menu
 *      firstchapter="chaptername"
 *      toplevelbegin="<strong>%chaptername%</strong><br /><small>%description%</small><ul>"
 *      toplevelitem="<li %active%><a href='%link%'>%title%</a>%sub%</li>"
 *      toplevelend="</ul>"
 *      sublevelbegin="  <ul>"
 *      sublevelitem="  <li %active%><a href='%link%'>%title%</a>%sub%</li>"
 *      sublevelend="  </ul>"
 *      isactive="class='activemenu'"
 *      exclude="pages,to,exclude"
 *      sort="title"
 * ]]
 * 
 *
 * You can also use %counter% to keep track of the number of menus:
 * toplevelitem="<li class='menu-%counter% %active%'><a href='%link%'>%title%</a></li>"
 */



// Register 'menu' as a smarty tag.
$PIVOTX['template']->register_function('menu', 'smarty_menu');


/**
 * Output a hierarchical menu
 *
 *
 * @param array $params
 * @return string
 */
function smarty_menu($params, &$smarty) {
    global $PIVOTX;

    
    $params = clean_params($params);

    $firstchapter = get_default($params['firstchapter'], "1", true);
    $toplevelbegin = get_default($params['toplevelbegin'], "<strong>%chaptername%</strong><br /><small>%description%</small><ul>\n", true);
    $toplevelitem = get_default($params['toplevelitem'], "<li %active%><a href='%link%'>%title%</a>%sub%</li>\n");
    $toplevelend = get_default($params['toplevelend'], "</ul>\n", true);
    $sublevelbegin = get_default($params['sublevelbegin'], "\t<ul>\n", true);
    $sublevelitem = get_default($params['sublevelitem'], "\t<li %active%><a href='%link%'>%title%</a>%sub%</li>\n", true);
    $sublevelend = get_default($params['sublevelend'], "\t</ul>\n", true);
    // Parameters 'sort' and 'exclude' are used below..

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


    $chapters = $PIVOTX['pages']->getIndex();

    $output = "";
    $counter = 0;

    // Iterate through the chapters, find the one we need to start with
    foreach ($chapters as $chapter) {
        if ($chapter['uid']==$firstchapter || makeURI($chapter['chaptername'])==makeURI($firstchapter)) {
            $thischapter = $chapter;
            break;
        }
    }
    
    if (empty($thischapter)) {
        debug("No suitable toplevel chapter found for '$firstchapter'.");
        return "<!-- No suitable toplevel chapter found for '$firstchapter'. -->";
    }

    // Add the toplevelbegin to output
    $temp_output = $toplevelbegin;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output = $temp_output;

    if($params['sort'] == "title") {
        asort($thischapter['pages']);
    }
    
    // Iterate through the pages
    foreach ($thischapter['pages'] as $page) {
    
    	// Increase the counter, that keeps track of the number of menus
    	$counter++;

        if(in_array($page['uri'], explode(",", $params['exclude']))) {
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
        $temp_output = $toplevelitem;
        $temp_output = str_replace("%title%", $page['title'], $temp_output);
        $temp_output = str_replace("%subtitle%", $page['subtitle'], $temp_output);
        $temp_output = str_replace("%user%", $page['user'], $temp_output); // To do: filter this to nickname, email, etc.
        $temp_output = str_replace("%date%", $page['date'], $temp_output); // To do: allow output formatting.
        $temp_output = str_replace("%link%", $pagelink, $temp_output);
        $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
        $temp_output = str_replace("%active%", $thisactive, $temp_output);
        $temp_output = str_replace("%counter%", $counter, $temp_output);

        // Check if the current page has a uri that matches another chapter. If so, add a submenu
        foreach($chapters as $chapter) {
            if ( makeURI($chapter['chaptername'])==makeURI($page['uri']) ) {
                // Get the submenu..
                $sub_output = __menu_helper($chapters, makeURI($page['uri']), $params['exclude'],
                            $sublevelbegin, $sublevelitem, $sublevelend, $isactive, $pageuri);
                
                // Insert or append it, dependant on whether %sub% is in the temp_output..
                if (strpos($temp_output, "%sub%")>0) {
                    $temp_output = str_replace("%sub%", $sub_output, $temp_output);
                } else {
                    $temp_output .= $sub_output;
                }
            }
        }
        
        $temp_output = str_replace("%sub%", "", $temp_output);
        $output .= $temp_output;

    }

    // Add the toplevelend to output
    $temp_output = $toplevelend;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output .= $temp_output;

    return $output;

}



function __menu_helper($chapters, $chaptername, $exclude, $begin, $item, $end, $isactive, $pageuri) {
    global $PIVOTX;
    
    // Iterate through the chapters, find the one we need to start with
    foreach ($chapters as $chapter) {
        if ($chapter['uid']==$chaptername || makeURI($chapter['chaptername'])==$chaptername) {
            $thischapter = $chapter;
            break;
        }
    }
    
    if (empty($thischapter)) {
        debug("No suitable submenu chapter found for '$thischapter'. This should never happen.");
        return "<!-- No suitable submenu chapter found for '$thischapter'. This should never happen. -->";
    }
    
    // Add the toplevelbegin to output
    $temp_output = $begin;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output = $temp_output;
    
    // Iterate through the pages
    foreach ($thischapter['pages'] as $page) {

        if(in_array($page['uri'], explode(",", $exclude))) {
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
        $temp_output = $item;
        $temp_output = str_replace("%title%", $page['title'], $temp_output);
        $temp_output = str_replace("%subtitle%", $page['subtitle'], $temp_output);
        $temp_output = str_replace("%user%", $page['user'], $temp_output); // To do: filter this to nickname, email, etc.
        $temp_output = str_replace("%date%", $page['date'], $temp_output); // To do: allow output formatting.
        $temp_output = str_replace("%link%", $pagelink, $temp_output);
        $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
        $temp_output = str_replace("%active%", $thisactive, $temp_output);
        
        // Check if the current page has a uri that matches another chapter. If so, add (another) submenu
        foreach($chapters as $chapter) {
            if ( makeURI($chapter['chaptername'])==makeURI($page['uri']) ) {
                
                // Get the submenu..
                $sub_output = __menu_helper($chapters, makeURI($page['uri']), $params['exclude'],
                            $begin, $item, $end, $isactive, $pageuri);
                
                // Insert or append it, dependant on whether %sub% is in the temp_output..
                if (strpos($temp_output, "%sub%")>0) {
                    $temp_output = str_replace("%sub%", $sub_output, $temp_output);
                } else {
                    $temp_output .= $sub_output;
                }                    
            }
        }
        
        $temp_output = str_replace("%sub%", "", $temp_output);
        $output .= $temp_output;

    }

    // Add the sublevelend to output
    $temp_output = $end;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output .= $temp_output;

    return $output;
    
    
}

?>