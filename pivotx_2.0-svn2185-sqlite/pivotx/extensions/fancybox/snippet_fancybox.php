<?php
// - Extension: Fancybox
// - Version: 0.3 - development
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=fancybox
// - Description: Replace boring old Thickbox with a FancyBox!!
// - Date: 2009-09-25
// - Identifier: fancybox 



// Register 'fancybox' as a smarty tag, and override 'popup'
$PIVOTX['template']->register_function('fancybox', 'smarty_fancybox');
$PIVOTX['template']->unregister_function('popup');
$PIVOTX['template']->register_function('popup', 'smarty_fancybox');


/**
 * Outputs the Fancybox popup code.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_fancybox($params, &$smarty) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    $params = clean_params($params);

    $filename = $params['file'];
    $thumbname = $params['description'];
    $org_thumbname = $params['description'];
    $alt = $params['alt'];
    $align = get_default($params['align'], "center");
    // $border = get_default($params['border'], 0);

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

        $code = sprintf( "<a href='%s' class=\"fancybox\" title=\"%s\" rel=\"entry-%s\" >%s</a>",
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

    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'fancyboxIncludeCallback');

    return $code;


}

/**
 * Try to insert the includes for fancybox in the <head> section of the HTML
 * that is to be outputted to the browser. Inserts Jquery if not already 
 * included. (This is just the default "thickboxIncludeCallback" function 
 * adapted to Fancybox.)
 *
 * @param string $html
 */
function fancyboxIncludeCallback(&$html) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    $jqueryincluded = false;
    $insert = '';

    if (!preg_match("#<script [^>]*?/jquery[a-z0-9_-]*\.js['\"][^>]*?>\s*</script>#i", $html)) {
        // We need to include Jquery
        $insert .= "\n\t<!-- Main JQuery include -->\n";
        $insert .= sprintf("\t<script type=\"text/javascript\" src=\"%sincludes/js/jquery.js\"></script>\n",
            $PIVOTX['paths']['pivotx_url'] );
        $jqueryincluded = true;
    }

    $path = $PIVOTX['paths']['extensions_url']."fancybox/";

    $insert .= "\n\t<!-- Includes for Fancybox script -->\n";
    $insert .= "\t<link rel=\"stylesheet\" href=\"{$path}fancybox.css\" type=\"text/css\" media=\"screen\" />\n";
    $insert .= "\t<script type=\"text/javascript\" src=\"{$path}jquery.fancybox.js\"></script>\n";
    $insert .= "\t<script type=\"text/javascript\">\n";
    $insert .= "\t\tjQuery.noConflict();\n";
    $insert .= "\t\tjQuery(document).ready(function() { jQuery(\"a.fancybox\").fancybox({ 'overlayShow': true, overlayOpacity: 0.25 }); });\n";
    $insert .= "\t</script>\n";


    // If JQuery was added earlier, we must insert the FB code after that. Else we 
    // insert the code after the meta tag for the charset (since it ought to be first
    // in the header) or if no charset meta tag we insert it at the top of the head section.
    if (!$jqueryincluded) {
        $html = preg_replace("#<script ([^>]*?/jquery[a-z0-9_-]*\.js['\"][^>]*?)>\s*</script>#si", 
            "<script $1></script>\n" . $insert, $html);
    } elseif (preg_match("/<meta http-equiv=['\"]Content-Type/si", $html)) {
        $html = preg_replace("/<meta http-equiv=(['\"]Content-Type[^>]*?)>/si", "<meta http-equiv=$1>\n" . $insert, $html);
    } else {
        $html = preg_replace("/<head([^>]*?)>/si", "<head$1>\n" . $insert, $html);
    }

}



?>
