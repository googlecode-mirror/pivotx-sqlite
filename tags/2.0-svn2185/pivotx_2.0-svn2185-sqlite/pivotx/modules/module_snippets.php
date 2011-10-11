<?php
/**
 *
 * Contains all of the old snippets. Most will be moved/ported to module_smarty.php
 * but some will remain here as fallback for people using old 1.x templates
 *
 */

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_snippets.php 2185 2009-10-22 15:09:06Z pivotlog $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }


/**
 * Inserts an image. Just a wrapper for backwards compatibility.
 */
function snippet_image( $filename,$alt='',$meta='',$compl=0 ) {
    $class = '';
    $id = '';
    $border = '';

    // do we need to clean compl?
    if ( $meta=='id' ) {
       $id = $compl;
    } else if ( $meta == 'class' ) {
       $class = $compl;
    } else {
        // Preserve left/right alignment
        if (($meta == 'left') || ($meta == 'right')) {
            $align = $meta;
        }
        if(( '' == $compl )||( !is_numeric( $compl ))) {
            $border = 0;
        } else {
            $border = $compl;
        }
    }

    return smarty_image(array(
        'file' => $filename,
        'alt' => $alt,
        'align' => $align,
        'border' => $border,
        'class' => $class,
        'id' => $id
    ));


}


/**
 * Insert a popup to an image.. Just a wrapper for backwards compatibility.
 */
function snippet_popup ($filename, $thumbname='', $alt='', $align='center', $border='') {
    global $PIVOTX;

    // To avoid forcing people, who are switching from Pivot to PivotX, to edit all
    // their entries with wrong syntax in the popup tag, we have kept these 
    // lines (which were part of Pivot too).
    if (is_numeric($align)) {
        // the border and align properties were swapped, so we need
        // to compensate for the wrong ones.
        $tmp = $border;
        $border = $align;
        $align = $tmp;
    }
    
    return smarty_popup( array(
        'file' => $filename,
        'description' => $thumbname,
        'alt' => $alt,
        'align' => $align,
        'border' => $border
    ), $PIVOTX['template']);


}



/**
 * Inserts a link to a downloadable file... Just a wrapper for backwards 
 * compatibility.
 *
 * @param string $filename
 * @param string $icon Insert a suitable icon if set to "icon"
 * @param string $text The text of the download link.
 * @param string $title The text of the title attribue of the link.
 */
function snippet_download( $filename,$icon,$text,$title ) {
    global $PIVOTX;
    
    return smarty_download( array(
        'file' => $filename,
        'icon' => $icon,
        'text' => $text,
        'title' => $title,
    ), $PIVOTX['template']);

}



/**
 * Creates a link to a file. This snippet has changed meaning in PivotX.
 *
 * The snippet will check the parent driectory of Pivot
 * and the upload directory for a file with the given name.
 *
 * @param string $filename
 * @param string $name (Link text)
 * @return string
 */
function snippet_link($filename, $name) {
    global $PIVOTX;

    debug("The 'link' template tag in PivotX don't link to a file - use 'download' in stead.");

    return smarty_download( array(
        'file' => $filename,
        'icon' => 'text',
        'text' => $name,
    ), $PIVOTX['template']);

}



/**
 * Returns the title (name) of the current weblog. Just a wrapper for 
 * backwards compatibility.
 *
 * @param string $strip if equal to 'strip' all HTML tags will be removed.
 * @return string
 */
function snippet_weblogtitle($strip = '') {

    return smarty_weblogtitle(array('strip' => $strip));

}


function snippet_subweblog ($sub='', $count='', $order='lasttofirst') {
    renderErrorpage("'subweblog' is a block in PivotX", 
        "Read the <a href='http://book.pivotx.net/?page=4-1#anchor-step-4-setting-up-templates'>documentation</a>.");
}


function snippet_weblog ($sub='', $count='') {
    renderErrorpage("'weblog' is a block in PivotX", 
        "Read the <a href='http://book.pivotx.net/?page=4-1#anchor-step-4-setting-up-templates'>documentation</a>.");
}


// Displays information about an entry. Can only be used in an entry.
// [[entry_data:word:image:download]]
// bob's function changed by JM
// 2004/11/25 =*=*= JM - minor corrections
function snippet_entry_data( $word='',$image='',$download='' ) {
  global $PIVOTX;
  $output = array();
  // count words - only if OK
  if( '' != $word ) {
    $total = str_word_count(strip_tags($PIVOTX['db']->entry['title']." ".$PIVOTX['db']->entry['introduction']." ".$PIVOTX['db']->entry['body'])) ;
    if( '*' == $word ) {
       $output[] = ' '.$total.' '.__('words');
    } else {
      $output[] = $total.' '.$word;
    }
  }
  // count images - only if OK
  if( '' != $image ) {
    preg_match_all("/(<img|\[\[image|\[\[popup)/mi", $PIVOTX['db']->entry['introduction'].$PIVOTX['db']->entry['body'], $match );
    $total = count( $match[0] );
    if( $total > 0 ) {
      if( '*' == $image ) {
        // single/plural
        if( 1 == $total ) {
          $output[] = '1 '.__('image');
        } else {
          $output[] = $total.' '.__('images');
        }
      } else {
        $output[] = $total.' '.$image;
      }
    }
  }
  // count downloads - only if OK
  if( '' != $download ) {
    preg_match_all("/(\[\[download)/mi", $PIVOTX['db']->entry['introduction'].$PIVOTX['db']->entry['body'], $match );
    $total = count( $match[0] );
    if( $total > 0 ) {
      if( '*' == $download ) {
      // single/plural
        if( 1 == $total ) {
          $output[] = '1 '.__('file');
        } else {
          $output[] = $total.' '.__('files');
        }
      } else {
        $output[] = $total.' '.$download;
      }
    }
  }
  return implode( ', ',$output );
}




function snippet_code() {
    global $PIVOTX;

    $output=$PIVOTX['db']->entry['code'];

    return $output;
}


function snippet_uid() {
    global $PIVOTX;

    $output=$PIVOTX['db']->entry['code'];

    return $output;
}


function snippet_id_anchor($name = '') {
    global $PIVOTX;

    if ($name=='') { $name="e"; }

    $output="<span id=\"".$name.$PIVOTX['db']->entry['code']."\"></span>";

    return $output;
}

function snippet_even_odd() {
    global $even_odd;

    if ($even_odd) {
        return "even";
    } else {
        return "odd";
    }

}


/**
 * Returns the title of the current entry.
 *
 * @param string $action if equal to 'strip' all HTML tags will be removed.
 * @return string
 */
function snippet_title($action = '') {
    global $PIVOTX;

    if ($action == 'strip') {
        $params['strip'] = 1;
    }

    return smarty_title($params, $PIVOTX['template']);
}


/**
 * Returns the subtitle of the current entry.
 *
 * @param string $action if equal to 'strip' all HTML tags will be removed.
 * @return string
 */
function snippet_subtitle($action = '') {
    global $PIVOTX;

    if ($action == 'strip') {
        $params['strip'] = 1;
    }

    return smarty_subtitle($params, $PIVOTX['template']);
}


/**
 * Wrapper for smarty_introduction
 *
 * @uses smarty_introduction
 */
function snippet_introduction($strip='') {
    global $PIVOTX;

    return smarty_introduction(array('strip'=>$strip),$PIVOTX['template']);

}

/**
 * Wrapper for smarty_body
 *
 * @uses smarty_body
 */
function snippet_body($strip='') {
    global $PIVOTX;

    return smarty_body(array('strip'=>$strip), $PIVOTX['template']);
}

/**
 * Wrapper for smarty_date
 *
 * @uses smarty_date
 */
function snippet_date($format='') {
    global $PIVOTX;

    return smarty_date(array('format'=>$format), $PIVOTX['template']);
}

/**
 * Wrapper for smarty_date
 *
 * @uses smarty_date
 */
function snippet_edit_date($format='') {
    global $PIVOTX;

    return smarty_date(array('format'=>$format, 'use'=>'edit_date'), $PIVOTX['template']);
}

/**
 * Wrapper for smarty_date
 *
 * @uses smarty_date
 */
function snippet_fulldate($format='') {
    global $PIVOTX;

    return smarty_date(array('format'=>$format), $PIVOTX['template']);

}

/**
 * Wrapper for smarty_date
 *
 * @uses smarty_date
 */
function snippet_diffdate() {
    global $PIVOTX;

    return smarty_date(array('format'=>$format, 'diffonly'=>1), $PIVOTX['template']);

}




function snippet_jscookies() {

    $output = "<script type='text/javascript'>
//<![CDATA[
function readCookie(name) { var cookieValue = ''; var search = name + '='; if(document.cookie.length > 0) {  offset = document.cookie.indexOf(search); if (offset != -1) {  offset += search.length; end =  document.cookie.indexOf(';', offset); if (end == -1) end = document.cookie.length; cookieValue = unescape(document.cookie.substring(offset, end)) } } return cookieValue.replace(/\+/gi, ' '); }
function getNames() { if (document.getElementsByName('piv_name')) { elt = document.getElementsByName('piv_name'); elt[0].value=readCookie('piv_name'); } if (document.getElementsByName('piv_email')) { elt = document.getElementsByName('piv_email'); elt[0].value=readCookie('piv_email');  } if (document.getElementsByName('piv_url')) { elt = document.getElementsByName('piv_url'); elt[0].value=readCookie('piv_url');  } if (document.getElementsByName('piv_rememberinfo')) { elt = document.getElementsByName('piv_rememberinfo'); if (readCookie('piv_rememberinfo') == 'yes') { elt[0].checked = true; } } }
var oldEvt_readCookie = window.onload; window.onload = function() { if (oldEvt_readCookie) oldEvt_readCookie(); setTimeout('getNames()', 500); }
//]]>
</script>";

    return $output;

}

function snippet_nick() {
    return snippet_user("nick");
}


/**
 * Wrapper for smarty_user
 *
 * @uses smarty_user
 */
function snippet_user($field) {
    global $PIVOTX;

    return smarty_user(array('field'=>$field), $PIVOTX['template']);
}

/**
 * Encrypts the given email address using JavaScript. Wrapper for smarty_link.
 *
 * If "Encode Email Address" is not selected for the current
 * weblog, the output will be a plain mailto-link.
 *
 * @uses smarty_link
 * @param string $email
 * @param string $display Text of the mailto-link.
 * @param string $title Title of the mailto-link.
 * @return string
 */
function snippet_encrypt_mail($email, $display, $title='' ) {
    global $PIVOTX;

    return smarty_link(array(
        'mail' => $email,
        'text' => $display,
        'title' => $title
    ), $PIVOTX['template']);

}


// for backwards compatibility
function snippet_email() {

    return snippet_email_to_nick();
}


/**
 * Wrapper for smarty_user
 *
 * @return string
 */
function snippet_email_to_nick() {
    global $PIVOTX;

    return smarty_user(array('field'=>'emailtonick'), $PIVOTX['template']);

}


/**
 * Wrapper for smarty_more
 *
 * @uses smarty_more
 * @param string $title
 * @return string
 */
function snippet_more( $title='' ) {
    global $PIVOTX;

    $output = smarty_more(array('title'=>$title), $PIVOTX['template'] );

    return $output;
}


function snippet_via($text='') {

    return smarty_via(array('format'=>$text));

}

/**
 * Displays the keywords for the entry.
 *
 * @return string The text to display.
 * @param string $text The output format. The default
 *  values is "%keywords%".
 * @param string $sep The separator between the keywords.
 *  The default value is comma. The value "clear" will output
 * the keywords exactly as it was inserted with the entry.
 */
function snippet_keywords($text='',$sep='') {
    global $PIVOTX;

    if ($text=='') { $text = "%keywords%"; }
    if ($sep=='') { $sep = ", "; }

    $keywords = stripslashes($PIVOTX['db']->entry['keywords']);

    if( $sep == 'clear'  ) {
        $output = $keywords;
    } elseif( strlen( trim( $keywords )) > 2 ) {
        // format output..
        preg_match_all('/[^"\', ]+|"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'/i', $keywords, $matches);
        foreach($matches[0] as $match) {
            $output[] = trim(str_replace('"','', str_replace("'",'', $match)));
        }
        $output = implode( $sep,$output );
        $output = str_replace( '%keywords%',$output,$text );
    }    else {
        $output = $PIVOTX['db']->entry['keywords'];
    }
    return $output;
}



/**
 * Wrapper for smarty_comments.
 *
 * @param string $order
 * @return string
 */
function snippet_comments($order = 'ascending') {
    global $PIVOTX;

    return smarty_comments(array('order'=>$order),'',$PIVOTX['template']);
}



/**
 * Placeholder for backwards compatibility
 */
function snippet_nextentry($text='', $cutoff=20) {

    return smarty_nextentry(array('text'=>$text, 'cutoff'=>$cutoff));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_previousentry($text='', $cutoff=20) {

    return smarty_previousentry(array('text'=>$text, 'cutoff'=>$cutoff));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_nextentryincategory($text='', $cutoff='') {

    return smarty_nextentry(array('text'=>$text, 'cutoff'=>$cutoff, 'incategory'=>true));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_previousentryincategory($text='', $cutoff='') {

    return smarty_previousentry(array('text'=>$text, 'cutoff'=>$cutoff, 'incategory'=>true));

}









/**
 * deprecate this!! We need to add these via a hook!
 *
 */
function snippet_trackback_autodiscovery() {

    return snippet_trackautodiscovery();

}


function snippet_last_comments() {

    return smarty_latest_comments();
}





/**
 * Creates a link to the "self registration"/"create account" page
 * (with the correct weblog selection).
 *
 * @param string $linktext
 * @return string
 */
function snippet_create_account_link($linktext = '') {
    global $Current_weblog, $PIVOTX, $Cfg;
    if (!$Cfg['selfreg']) {
        return "<!-- No output from snippet create_account_link because self registration is disabled -->";
    }
    $url = $PIVOTX['paths']['pivotx_url']."selfreg.php?w=".para_weblog($Current_weblog);
    if ($linktext == '') {
        $linktext = __('Self-registration');
    }
    return "<a href='$url' class='pivot-system-links'>$linktext</a>";

}

/**
 * wrapper for smarty_permalink
 *
 * @uses smarty_permalink
 */
function snippet_permalink($text='%title%', $title='%title%') {
    global $PIVOTX;

    $output = smarty_permalink(array(
        'text'=>$text,
        'title'=>$title),$PIVOTX['template']);

    return $output;
}

/**
 * wrapper for smarty_entrylink
 *
 * @uses smarty_entrylink
 * @param string $code
 * @param string $query
 * @param string $weblog
 * @return string
 */
function snippet_entrylink($code='',$query='',$weblog='') {
    global $PIVOTX;

    $output = smarty_entrylink(array(
        'uid'=>$code,
        'query'=>$query,
        'weblog'=>$weblog),$PIVOTX['template']);

    return $output;
}

/**
 * wrapper for smarty_commentlink
 *
 * @uses smarty_commentlink
 */
function snippet_commentlink() {
    global $PIVOTX;

    $output = smarty_commentlink(array(),$PIVOTX['template']);

    return $output;
}


function snippet_inlinecommentlink() {
    global $Cfg, $Current_weblog, $PIVOTX;

    $link=makeFilelink($PIVOTX['db']->entry['code']);

    $commcount=$PIVOTX['db']->entry['commcount'];

    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) && ($PIVOTX['db']->entry['allow_comments'] == 0) ) {
        return '';
    }

    $text = array($Weblogs[$Current_weblog]['comments_text_0'], $Weblogs[$Current_weblog]['comments_text_1'], $Weblogs[$Current_weblog]['comments_text_2']);
    $text = $text[min(2,$commcount)];
    $commcount = lang('numbers', $commcount);
    $commcount = str_replace("%num%", $commcount, $text);
    $commcount = str_replace("%n%", $PIVOTX['db']->entry['commcount'], $commcount);
    $commnames=$PIVOTX['db']->entry['commnames'];

    if ($commcount=='') { $commcount="(undefined)"; }

    $output = sprintf("<a href='%s' id='commentlink_%s' ", $link, $PIVOTX['db']->entry['code']);
    $output .= sprintf("onclick=\"$('#comments_%s').slideToggle('slow'); return false;\"",
        $PIVOTX['db']->entry['code'], $PIVOTX['db']->entry['code']);
    $output .= sprintf(" title=\"%s\" >%s</a>",$commnames, $commcount);

    return $output;
    return htmlentities($output);
}



function snippet_inlinecomments() {
    global $PIVOTX;

    $output .= "\n<div id='comments_".$PIVOTX['db']->entry['code']."' style='display: none; margin: 0; padding: 0; border-top: 1px solid transparent;'>\n";

    $output .= snippet_comments();
    $output .= snippet_commentform();

    $output .= "\n</div>\n";

    return $output;
}


function snippet_inlinemorelink() {
    global $Cfg, $Current_weblog, $PIVOTX;

    if (strlen($PIVOTX['db']->entry['body'])>5) {

        $link=makeFilelink($PIVOTX['db']->entry['code'],'','body');
        $more = $Weblogs[$Current_weblog]['read_more'];
        $text = ( strlen($more) > 1 ) ? $more : "(".__('more').")";


        $output.= sprintf("<a href='%s' id='morelink_%s' ", $link, $PIVOTX['db']->entry['code']);
        $output.= sprintf("onclick=\"$('#body_%s').slideDown('slow'); $('#morelink_%s').hide(); return false;\"",
            $PIVOTX['db']->entry['code'], $PIVOTX['db']->entry['code']);
        $output.= sprintf(" >%s</a>", $text);

        // substitute %title% in the 'more' link.
        $output = str_replace("%title%", $PIVOTX['db']->entry['title'], $output);

        return $output;

    } else {
        return '';
    }

}


/**
 * Inserts a bit of javascript to show the trackbacks on this entry, without
 * leaving the current page.
 */
function snippet_inlinetrackbacklink() {
    global $Cfg, $Current_weblog, $PIVOTX;

    $link=makeFilelink($PIVOTX['db']->entry['code'],'','track');

    $trackcount=$PIVOTX['db']->entry['trackcount'];

/*
    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) && ($PIVOTX['db']->entry['allow_comments'] == 0) ) {
        return '';
    }
*/
    $text = array($Weblogs[$Current_weblog]['trackbacks_text_0'], $Weblogs[$Current_weblog]['trackbacks_text_1'], $Weblogs[$Current_weblog]['trackbacks_text_2']);
    $text = $text[min(2,$trackcount)];
    $trackcount = lang('numbers', $trackcount);
    $trackcount = str_replace("%num%", $trackcount, $text);
    $trackcount = str_replace("%n%", $PIVOTX['db']->entry['trackcount'], $trackcount);
    $tracknames=$PIVOTX['db']->entry['tracknames'];

    if ($trackcount=='') { $trackcount="(undefined)"; }


    $output = sprintf("<scr"."ipt type='text/javascript'>var pivotx_url='%s';</scr"."ipt>", $PIVOTX['paths']['pivotx_url']);
    $output .= sprintf("<a href='%s' ", $link);
    $output.= sprintf("onclick=\"openComments('%s', this); return false\"", $PIVOTX['db']->entry['code']);
    $output.= sprintf(" title=\"%s\" >%s</a>",$tracknames, $trackcount);


    return $output;
}


function snippet_inlinemore() {
    global $PIVOTX;


    $output .= "\n<div id='body_".$PIVOTX['db']->entry['code']."' style='display: none; margin:0; padding: 0; border-top: 1px solid transparent;'>\n";

    $output .= snippet_body();

    $output .= "\n</div>\n";

    return $output;
}





function snippet_commnames() {
    global $PIVOTX, $Current_weblog;

    $this_weblog= $Weblogs[$Current_weblog];

    $commnames=$PIVOTX['db']->entry['commnames'];

    return $commnames;
}





function snippet_vote( $value,$label,$title='',$total='',$group='' ) {
    global $PIVOTX,$Current_weblog ;

    $url  = $PIVOTX['paths']['pivotx_url'];
    $url .= 'submit.php?vote='.urlencode( $value );
    $url .= '&amp;piv_code='.$PIVOTX['db']->entry['code'];
    $url .= '&amp;piv_weblog='.urlencode( $Current_weblog );
    $url .= '&amp;group='.$group;

    if( '' != $total ) {
        $count = @array_count_values( $PIVOTX['db']->entry['votes'] ) ;
        $count = isset( $count[$value] ) ? $count[$value] : 0 ;
        $total = str_replace( '%num%',$count,' '.$total ) ;
    }

    $onclick = "window.open('$url','emot','width=200,height=100,directories=no,location=no,menubar=no,scrollbars=no,status=yes,toolbar=no,resizable=no');return false";
    $output = sprintf("<a href='#' onclick=\"%s\" title=\"%s\">%s</a>%s", $onclick, $title, $label, $total);

    return $output;

}

function snippet_karma($value, $label) {

    $title = __('Vote "%val%" on this entry');
    $title = str_replace('%val%', lang('karma', $value), $title);

    return snippet_vote($value, $label, $title, '%num% ', "k_");

}

function snippet_message($format='') {
    global $weblogmessage;

    if ($format=='') {
        $format="<a id='message'></a><p class='pivotx-message'>%message%</p>\n\n";
    }

    if (!empty($weblogmessage)) {
        $weblogmessage = strip_tags(stripslashes($weblogmessage));
        $output = str_replace("%message%", $weblogmessage, $format);
    } else {
        $output = '';
    }

    return $output;

}


function snippet_close_on_esc() {
    return "<script type='text/javascript'>\ndocument.onkeypress = function esc(e) {\nif(typeof(e) == 'undefined') { e=event; }\nif (e.keyCode == 27) { self.close(); }\n}\n</script>\n";
}




/**
 * wrapper for smarty_lang
 *
 * @uses smarty_lang
 */
function snippet_lang( $type='' ) {

    return smarty_lang( array('type'=>$type));

}



function snippet_editlink($name='') {

    return smarty_editlink( array('name'=>$name) );

}


/**
 * Displays a message when the moderation queue is enabled.
 *
 */
function snippet_moderate_message() {
    global $Cfg;

    if ($Cfg["moderate_comments"] == 1) {
        return sprintf("<p id='moderate_queue_message'>%s</p>", __('Comment moderation is enabled on this site. This means that your comment will not be visible on this site until it has been approved by an editor.'));
    } else {
        return '';
    }

}

/**
 * Called when displaying the tags.php page. if a tag is given,
 * it displays that tag, else the local Tag Cosmos
 *
 * @return string
 */
function snippet_tagpage()  {
    global $PIVOTX;

    // Check if we called "tags.php/tagname" or
    // "tags.php?somevar=somevalue[...]/tagname"
    if (preg_match('#tags.php(\?[^/]*|)/(.+)$#',$_SERVER['REQUEST_URI'], $matches) > 0 ) {
        $_GET['tag'] = $matches[2];
    }

    if((!isset($_GET['tag'])) || ($_GET['tag'] == ''))  {
        $output = printTagCosmos();
    } else {
        $output = printTag($_GET['tag']);
    }

    return $output;

}


/**
 * Wrapper for smarty_introduction
 *
 * @uses smarty_tags
 */
function snippet_tags($text='', $sep='') {
    global $PIVOTX;

    return smarty_tags(array('text'=>$text, 'seperator'=>$sep),$PIVOTX['template']);

}




/**
 * Display a small tagcloud.
 *
 * @param integer $amount
 * @param integer $minsize
 * @param integer $maxsize
 * @param string $template
 * @return string
 */
function snippet_tagcloud($amount=0, $minsize=0, $maxsize=0, $template='') {
    global $PIVOTX, $Current_weblog, $Cfg;

    // very b0rken for now. just return ''
    return '';

    if ($minsize==0) { $minsize=8; }
    if ($maxsize==0) { $maxsize=17; }
    if ($amount==0) { $amount=20; }

    $tagcosmos = getTagCosmos($amount,$Current_weblog);

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

        $htmllinks[$key] = sprintf("<a style=\"font-size:%sem;\" href=\"%s\"
          rel=\"tag\" title=\"%s: %s, %s %s\">%s</a>\n",
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
    $output .= implode(" ", $htmllinks);

    if (para_weblog_needed($Current_weblog)) {
        $para .= "?w=".para_weblog($Current_weblog);
        $para .= ($template!='') ? "&amp;t=$template" : '';
    } else {
        $para = ($template!='') ? "?t=$template" : '';
    }

    if ($Cfg['mod_rewrite']==0) {
        $link = $PIVOTX['paths']['pivotx_url']."tags.php";
    } else {
        $link = $PIVOTX['paths']['log_url']."tags";
    }

    $output .= sprintf('<em>(<a href="%s%s">%s</a>)</em>',
       $link, $para, __('all')
    );

    $output .= "</div>";

    return $output;

}


/**
 * Get detailed info for tags used in an entry
 *
 * @param string $template
 * @return string
 */
function snippet_ttaglist($template='') {

    global $PIVOTX;

    $aTagsList = getTags(false);

    if(sizeof($aTagsList) > 0)  {
        $output = "<div id='tagpage'>\n";
        $output .= "<h3>".__('Tags used in this posting')."</h3>\n";

        $tagLinks = array();
        foreach($aTagsList as $sTag)    {
            makeRelatedTags($sTag, $aTagsList);
            $tagLinks[] = sprintf('<a rel="tag" href="%s" title="tag: %s">%s</a>',
                    tagLink($sTag,$template),
                    $sTag,
                    $sTag
                );
        }

        $output .= "<p>" . implode(", ", $tagLinks) . "</p>\n";

        reset($aTagsList);
        foreach($aTagsList as $sRelated)    {
            $sTheRelatedLinks = getEntriesWithTag($sRelated, $PIVOTX['db']->entry["code"]);
            if(!strlen($sTheRelatedLinks) == 0) {
                $output .= "\n<h3>";
                $output .= __('Other entries about')." '".$sRelated."'</h3>\n";
                $output .=  $sTheRelatedLinks;
            }
        }
        $output .= "\n</div>\n";

    } else  {
        $output = '';
    }

    return $output;
}

/**
 * Treat categories as tags. I'm not quite sure if this is all that useful
 *
 * @param string $filter
 * @return string
 */
function snippet_tcategory($filter='') {
    global $PIVOTX, $Current_weblog, $Current_subweblog;
    $output=$PIVOTX['db']->entry["category"];

    if ( ($filter != '') && (isset($Weblogs[$Current_weblog]['sub_weblog'][$Current_subweblog])) ) {
        $output = array_intersect ( $Weblogs[$Current_weblog]['sub_weblog'][$Current_subweblog]['categories'], $output);
    }

    if (is_array($output)) {
        $count = 0;
        $sOut = '';
        foreach($output as $category)   {
            $sOut .= snippet_tt($category);
            $count++;
            if($count < sizeof($output))    {
                $sOut .= ", ";
            }
        }
        return $sOut;
    } else {
        return '';
    }
}


/**
 * Display a Tag, as used in the introduction or body. Just a wrapper for 
 * backwards compatibility.
 *
 * @param string $tagName
 * @param string $externalLink
 * @param string $template
 * @return string
 */
function snippet_tt($tagName, $externalLink='', $template='') {
    return smarty_tt( array(
        'tag' => $tagName,
        'link' => $externalLink,
        'template' => $template,));
}

?>
