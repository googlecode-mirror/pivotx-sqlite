<?php
// - Extension: BBclone hook
// - Version: 1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=bbclone
// - Description: A small hook extension that allows tracking your pages in BBclone.
// - Date: 2008-05-12
// - Identifier: bbclone



/**
 * A small hook extension that allows tracking your pages in BBclone.
 *
 * This extension assumes that BBclone is installed in a folder called 'bbclone',
 * which should be in the same folder as your 'pivot' folder.
 *
 */


// Make sure we have jQuery..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

// Register 'hello' as a smarty tag.
$this->addHook('after_parse', 'callback', 'bbcloneHook');




/**
 * Mark the page visit in BBclone.
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function bbcloneHook(&$html) {
    global $PIVOTX;

    $bbclone_path = fixPath($PIVOTX['paths']['pivotx_path'].'../bbclone/');

    if(!file_exists($bbclone_path)) {
        debug("bbclone directory not found - expected location: $bbclone_path");
        return;
    }
    
    $vars = $PIVOTX['template']->get_template_vars();

    if ($vars['pagetype']=="entry" || $vars['pagetype']=="page") {
        $title = smarty_title(array('strip' => 1), $PIVOTX['template']);
    } elseif ($vars['pagetype']=="weblog") {
        $title = smarty_weblogtitle(array('strip' => 1), $PIVOTX['template']);
    } else {
        $title = smarty_sitename(array(), $PIVOTX['template']);
    }
        
    $output = "<script type='text/javascript'>\n";
    $output .= "jQuery(function($) {\n";
    $output .= sprintf("\tjQuery.get('%sbbclone/mark.php', { title: '%s', request: '%s', referer: '%s', hash: '%s' });\n",
            $PIVOTX['paths']['extensions_url'],
            addslashes($title),
            base64_encode($_SERVER['REQUEST_URI']),
            base64_encode($_SERVER['HTTP_REFERER']),
            md5($_SERVER['REQUEST_URI'] . $_SERVER['HTTP_REFERER'])
        );
    $output .= "});\n";
    $output .= "</script>\n";

    $html = preg_replace("/<\/head/si", $output."</head", $html);


}

?>
