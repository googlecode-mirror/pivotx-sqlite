<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_tags.php 2148 2009-09-24 19:16:14Z hansfn $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }



/**
 * Get the TagCosmos. Wrapper for either getTagCosmosFlat or getTagCosmosMysql,
 * Depending on the DB model that's being used.
 *
 * Returns an array with the following elements:
 * 'minvalue' => minimum value of a tag
 * 'maxvalue' => maximum value of a tag
 * 'amount' => number of tags
 * 'tags' => array of the tags. The indices are the tags, the values the number of occurences
 *
 * @param integer $max
 * @param string $weblogname
 * @return array
 * @see getTagCosmosFlat
 * @see getTagCosmosMysql
 *
 */
function getTagCosmos($max=0, $weblogname='', $match='',$exclude= array()) {
    global $PIVOTX;

    if (($weblogname== '') || (count($PIVOTX['weblogs']->getWeblogNames()) == 1)) {
        $weblogname = '_all_';
    }

    if ($PIVOTX['config']->get('db_model')=="flat") {
        return getTagCosmosFlat($max, $weblogname, $match, $exclude);
    } else {
        return getTagCosmosMysql($max, $weblogname, $match, $exclude);
    }

}


/**
 * Get the TagCosmos. Flat file version.
 *
 * Preferably use the cached version, otherwise just make it
 * on the fly. (and then we store the cached version)
 *
 * The $weblogname parameter is used to return tags for a single weblog.
 * A value of '_all_' returns the tags for all weblogs combined.
 * If $max is given, it will return at most that many tags, ordered by size.
 *
 * Returns an array with the following elements:
 * 'minvalue' => minimum value of a tag
 * 'maxvalue' => maximum value of a tag
 * 'amount' => number of tags
 * 'tags' => array of the tags. The indices are the tags, the values the number of occurences
 *
 * @param integer $max
 * @param string $weblogname
 * @return array
 * @see getTagCosmos
 *
 */
function getTagCosmosFlat($max=0,$weblogname,$match,$exclude=array()) {
    global $PIVOTX;

    // If the cached version is fresh enough, we restore that
    if ( (file_exists($PIVOTX['paths']['db_path']."ser_tags.php"))  &&
        (filectime($PIVOTX['paths']['db_path']."ser_tags.php") > (time() - (60 * $PIVOTX['config']->get('tag_cache_timeout') ))) ) {

        // Just load it..
        $data = load_serialize($PIVOTX['paths']['db_path']."ser_tags.php");
        $tagcosmos = $data[$weblogname];

    } else {

        // We have to compute it..

        $tagcosmos = array();
        
        $multi_weblog = false;
        if (count($PIVOTX['weblogs']->getWeblogNames()) > 1) {
            $multi_weblog = true;
            foreach ($PIVOTX['weblogs']->getWeblogNames() as $name) {
                $tagcosmos[$name] = array();
            }
            $temp_db = new db(FALSE);
        }

        $tagdir = dir($PIVOTX['paths']['db_path']."tagdata/");

        // Read all tags, build the tag index and save it or later.
        while (false !== ($entry = $tagdir->read())) {
            if (getextension($entry)=="tag") {
                list($tagname) = explode(".", $entry);
                $tagname = urldecode($tagname);
                $tagfile = implode("",file($PIVOTX['paths']['db_path']."tagdata/".$entry));
                $tagfile = explode(",", $tagfile);
                if(!in_array($tagname, $exclude)) {
                    if ($tagname!="") {
                        $tagcosmos['_all_']['tags'][$tagname] = count($tagfile);
                        if ($multi_weblog) {
                            foreach ($tagfile as $entrycode) {
                                $temp_entry = $temp_db->read_entry($entrycode);
                                $cat_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($temp_entry['category']);
                                foreach ($cat_weblogs as $cat_weblog) {
                                    $tagcosmos[$cat_weblog]['tags'][$tagname]++;
                                }
                            }
                        }
                    }
                }
            }
        }

        $tagdir->close();

        save_serialize($PIVOTX['paths']['db_path']."ser_tags.php", $tagcosmos);

        $tagcosmos = $tagcosmos[$weblogname];
    }

    $tagcosmos['amount'] = count($tagcosmos['tags']);

    // if $max is given, we need to filter out the smaller tags, until the required size is reached.
    if (($max!=0) && ($max<count($tagcosmos['tags']))) {
        arsort($tagcosmos['tags']);
        $tagcosmos['tags'] = array_slice($tagcosmos['tags'], 0, $max);
    }

    ksort($tagcosmos['tags']);

    $tagcosmos['minvalue'] = 1000;
    $tagcosmos['maxvalue'] = 0;

    // We determine what the min and max-value in the cosmos is.
    foreach($tagcosmos['tags'] as $key => $value)   {
        $tagcosmos['maxvalue'] = max($tagcosmos['maxvalue'], $value);
        $tagcosmos['minvalue'] = min($tagcosmos['minvalue'], $value);
    }

    return $tagcosmos;

}


/**
 * Get the TagCosmos. MySQL database version.
 *
 * Preferably use the cached version, otherwise just make it
 * on the fly. (and then we store the cached version)
 *
 * If $max is given, it will return at most that many tags, ordered by size.
 * If $weblogname is given, only tags for that weblog will be returned.
 *
 * Returns an array with the following elements:
 * 'minvalue' => minimum value of a tag
 * 'maxvalue' => maximum value of a tag
 * 'amount' => number of tags
 * 'tags' => array of the tags. The indices are the tags, the values the number of occurences
 *
 * @param integer $max
 * @param string $weblogname
 * @return array
 * @see getTagCosmos
 *
 */
function getTagCosmosMysql($max=0,$weblogname='', $match='', $exclude= array()) {
    global $PIVOTX;

    $tagtable = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);
    $entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);

    $max = intval($max);

    $tagcosmos = array();

    // Set up DB factory
    $sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
        															 $PIVOTX['config']->get('db_databasename'),
        															 $PIVOTX['config']->get('db_hostname'),
        															 $PIVOTX['config']->get('db_username'),
        															 $PIVOTX['config']->get('db_password')
    												);


    // Set up DB connection
    $database = $sqlFactory->getSqlInstance();

    // Get the total amount of tags.

    $qry = array();
    $qry['select']="COUNT(DISTINCT(tag)) as tagcount";
    $qry['from'] = $tagtable;
    
    $database->build_select($qry);
    $database->query();
    $row = $database->fetch_row();



    $tagcosmos['amount'] = intval($row['tagcount']);

    $qry = array();
    $qry['select']="tag, COUNT(tag) AS tagcount";
    $qry['from'] = $tagtable . " AS t";
    $qry['group'] = "tag";
    $qry['order'] = "tagcount DESC";
    $qry['limit'] = "0, $max";
    $qry['where'][] = "t.contenttype='entry'";

    $qry['leftjoin'][$entriestable . " AS e"] = "e.uid = t.target_uid";
    $qry['where'][] = "e.status='publish'";
    
    if (!empty($match) && strlen($match)>1) {
        $qry['where'][] = 't.tag like "' . $database->quote($match, true) . '%"';  
    }
    
    if(!empty($exclude)) {
        $qry['where'][] = 't.tag NOT IN ( "' . implode('","',$exclude) . '")'; 
    }
    
    $database->build_select($qry);    
    $database->query($query);
  
    //echo nl2br(htmlentities($database->get_last_query()));
      
    
    $rows = $database->fetch_all_rows();

    $tagcosmos['tags'] = make_valuepairs($rows, 'tag', 'tagcount');

    ksort($tagcosmos['tags']);

    $tagcosmos['minvalue'] = 1000;
    $tagcosmos['maxvalue'] = 0;

    // We determine what the min and max-value in the cosmos is.
    foreach($tagcosmos['tags'] as $key => $value)   {
        $tagcosmos['maxvalue'] = max($tagcosmos['maxvalue'], $value);
        $tagcosmos['minvalue'] = min($tagcosmos['minvalue'], $value);
    }

    return $tagcosmos;

}



/**
 * Get the tags from the current entry as an array. if $link is true,
 * the array will consist of links to the individual tag pages.
 *
 * @param boolean $link
 * @param string $text
 * @param mixed $additional
 * @return array
 */
function getTags($link=true, $text="", $additional=false) {
    global $PIVOTX;

    // If entry is set in the template (Smarty), we use that, else we try 
    // to get it from the database.
    $vars = $PIVOTX['template']->get_template_vars();
    if (isset($vars['entry'])) {
        $entry = $vars['entry'];
    } elseif (isset($PIVOTX['db']->entry['code'])) {
        $entry = $PIVOTX['db']->entry;
    } elseif (empty($text) && empty($additional))  {
        debug('Found no current entry to fetch the tags from');
        return;
    }

    // If text is not empty, we gather tags from that, else we use the current $entry
    if ($text == "") {
        $text = $entry["introduction"].$entry["body"];
    }

    // If additional is not empty, we gather tags from that, else we use the
    // current $PIVOTX['db']->keywords
    if ($additional === false) {
        $additional = $entry["keywords"];
    }

    // Parsing out the tags from the tt snippet in the text, taking into
    // account the optional second URL parameter.
    preg_match_all('/
        \[\[\s?tt                       # Matching the opening "[[tt"
        .*?\stag=([\'"])(.*?)\1.*?      # New syntax
        \s?\]\]                         # Matching the ending "]]"
        /ix', $text, $aTagsList);
    preg_match_all('/
        \[\[\s?tt                       # Matching the opening "[[tt"
        \s?:\s?([^:\]]*)(\s?:[^\]]*)?   # Old syntax
        \s?\]\]                         # Matching the ending "]]"
        /ix', $text, $bTagsList);


    // We don't need the entire result set, only the 'real' matches:
    $aTagsList = array_merge($aTagsList[2],$bTagsList[1]);

    // Add the keywords..
    $keywords = explode(" ", str_replace(",", " ", $additional));

    foreach($keywords as $key => $item) {
        $item = strtolower(trim($item));
        if ($item!="") {
            $aTagsList[] = $item;
        }
    }

    foreach($aTagsList as $key => $value) {
        $aTagsList[$key] = normalizeTag($value);
    }

    $aTagsList = array_unique($aTagsList);
    sort($aTagsList);

    // Make links, perhaps..
    if ($link) {
        foreach($aTagsList as $key => $value) {
            $aTagsList[$key] = sprintf('<a rel="tag" href="%s" title="%s: %s">%s</a>',
                tagLink($value),
                __('Tag'),
                $value,
                $value
            );
        }
    }


    return $aTagsList;

}

/**
 * Normalize tag to avoid duplicate noise.
 *
 * Currently we trim spaces, lowercase, remove quotes and HTML entities, and
 * treat 'star wars', 'star-wars', 'star+wars' and 'star_wars' as similar
 * (with the underscore version as the base case).
 *
 * @todo Since tag is used as filename not all characters should be allowed.
 *
 * @param string $tag
 * @return string
 */
function normalizeTag($tag){
    $tag = trim($tag);
    $tag = decode_text($tag,'special');
    // Decode the rest of the HTML enities if possible.
    $tag = unentify($tag);
    $tag = trim(strtolower($tag));
    $tag = str_replace(array("'",'"'), "", $tag);
    $tag = str_replace(array(" ","-","+"), "_", $tag);
    // Replacing character(s) not allowed in filenames.
    $tag = str_replace("/", "_", $tag);
    // Remove HTML enities we didn't manage to decode.
    $tag = preg_replace("/&([a-z\d]{2,7}|#\d{2,5});/i", "", $tag);
    if (empty($tag)) {
        $tag = "__empty__";
    }
    return $tag;
}

/**
 * Indexes tags in entries in the PivotX database and returns true
 * if there are more entries to index.
 *
 * @param int $start Code for first entry to index
 * @param int $stop Code for last entry to index
 * @param int $time Indexing time.
 * @return boolean
 */
function writeTagIndex ($start, $stop, $time) {
    global $PIVOTX, $output;

    $entries = $PIVOTX['db']->db_lowlevel->date_index;
    $count = 0;
    $date = date( 'Y-m-d-H-i' );

    foreach($entries as $key => $value) {

        if (($count++)<($start)) { continue; }
        if (($count)>($stop)) { break; }

        $entry = $PIVOTX['db']->read_entry( $key );

        // rules: index if all are true:
        // - ( status == 'publish' )or(( status == 'timed')&&( publish_date <= date ))
        // - at least one category is in array of 'not hidden' categories..

        // check status and date
        if (( 'publish'==$entry['status'] ) || (( 'timed'==$entry['status'] )&&( $entry['publish_date'] <= $date ))) {

            if (($count % 50) == 0) {
                $output .= sprintf(__("%1.2f sec: Processed %d entries...")."<br />\n", (timetaken('int')+$time), $count);
                flush();
            }
            writeTags($entry['keywords'], '', $key);
        }
    }

    // decide if we need to do some more.
    if (count($entries) > ($stop)) {
        return true;
    }
}


/**
 * Write out all tags for any given entry..
 *
 * @param array $entry
 */
function writeTags($tags, $oldtags, $code) {

    // Tags are separated by space(s)
    if (is_string($tags)) {
        if ($tags == '') {
            return; // Nothing to do
        } else {
            $tags = preg_split('/,?[ ]+/', $tags);
        }
    }

    // Loop through new tags, and add them..
    if (is_array($tags) && (count($tags)>0)) {
        foreach($tags as $tag) {
            writeTag($tag, $code);
            makeRelatedTags($tag, $tags);
        }
    }

    // Loop through old tags, and delete them if they are no longer present.
    if (is_string($oldtags)) {
        if ($oldtags == '') {
            return; // Nothing to do
        } else {
            $oldtags = preg_split('/,?[ ]+/', $oldtags);
        }
    }
    if (is_array($oldtags) && (count($oldtags)>0)) {
        foreach ($oldtags as $oldtag) {
            if (!in_array($oldtag, $tags)) {
                deleteTag($oldtag, $code);
            }
        }
    }

}


/**
 * Write out a single tag, checking if it doesn't exist already.
 *
 * @param string $tag
 * @param integer $entrycode
 */
function writeTag($tag, $entrycode) {
    global $PIVOTX;

    $tag = normalizeTag($tag);

    if ($tag=="__empty__") {
        debug("PivotX can't save an empty tag! (in entry $entrycode)");
        return "";
    }

    // Ensure that the ser tag file (for flat file db) is updated.
    // TODO - remove just the tag which is to be deleted, from the file.
    @unlink($PIVOTX['paths']['db_path'].'ser_tags.php');

    $sFileName = urlencode($tag).'.tag';

    if(!is_dir($PIVOTX['paths']['db_path']."tagdata"))  {
        return "<b>ERROR: You must create ".$PIVOTX['paths']['db_path']."tagdata and set the permissions to world writable!!! Bailing out.";
    }

    if(file_exists($PIVOTX['paths']['db_path']."tagdata/$sFileName"))   {

        $aFileArr = explode(",",implode("",file($PIVOTX['paths']['db_path']."tagdata/$sFileName")));

        if(!in_array($entrycode, $aFileArr))    {

            $aFileArr[] = $entrycode;
            $sNewFileString = implode(",",$aFileArr);

            write_file($PIVOTX['paths']['db_path']."tagdata/$sFileName", $sNewFileString);

        }

    } else {

        write_file($PIVOTX['paths']['db_path']."tagdata/$sFileName", $entrycode);

    }


}


/**
 * Deletes all tags for any given entry.
 *
 * @param mixed $tags
 * @param integer $entrycode
 * @return void
 */
function deleteTags($tags, $entrycode) {

    // Tags are separated by space(s)
    if (is_string($tags)) {
        $tags = preg_split('/[ ]+/', $tags);
    }

    // Loop through tags, and delete them..
    foreach($tags as $tag) {
        deleteTag($tag, $entrycode);
    }
}


/**
 * Delete a single tag for any given entry.
 *
 * @param string $tag
 * @param integer $entrycode
 */
function deleteTag($tag, $entrycode) {
    global $PIVOTX;

    // Ensure that the ser tag file (for flat file db) is updated.
    // TODO - remove just the tag which is to be deleted, from the file.
    @unlink($PIVOTX['paths']['db_path'].'ser_tags.php');

    $tag = normalizeTag($tag);

    $sFileName = urlencode($tag);

    if(!is_dir($PIVOTX['paths']['db_path']."tagdata"))  {
        return "<b>ERROR: You must create ".$PIVOTX['paths']['db_path']."tagdata and set the permissions to world writable!!! Bailing out.";
    }

    if(file_exists($PIVOTX['paths']['db_path']."tagdata/".$sFileName.".tag"))   {

        $aFileArr = explode(",",implode("",file($PIVOTX['paths']['db_path']."tagdata/".$sFileName.".tag")));

        if(in_array($entrycode, $aFileArr)) {

            foreach ($aFileArr as $key => $value) {
                if ($value=="" || $value==$entrycode) {
                    unset($aFileArr[$key]);
                }
            }

            if (count($aFileArr)==0) {

                // we can remove the empty tag file (and associated files)..
                unlink($PIVOTX['paths']['db_path']."tagdata/".$sFileName.".tag");
                @unlink($PIVOTX['paths']['db_path']."tagdata/".$sFileName."-tagpage.cache");
                @unlink($PIVOTX['paths']['db_path']."tagdata/".$sFileName.".rel");

            } else {

                $sNewFileString = implode(",",$aFileArr);
                write_file($PIVOTX['paths']['db_path']."tagdata/".$sFileName.".tag", $sNewFileString);

            }
        }

    }

}


/**
 * Print out the current Tag Cosmos as a fancy tagcloud in HTML, with smaller
 * and larger tags, dependent on how often they occur. Returns the output as HTML.
 *
 * @return string
 */
function printTagCosmos()   {
    global $PIVOTX;

    $tagcosmos = getTagCosmos(0, $PIVOTX['weblogs']->getCurrent());

    $htmllinks = array();


    // This is the factor we need to calculate the EM sizes. $minsize is 1 em,
    // $maxsize will be ($maxsize / $minsize) EM.. Take care if $tagcosmos['maxvalue'] == $tagcosmos['minvalue']
    if ($tagcosmos['maxvalue'] != $tagcosmos['minvalue']) {
        $factor = ($PIVOTX['config']->get('tag_max_font') - $PIVOTX['config']->get('tag_min_font')) / 
            ($tagcosmos['maxvalue'] - $tagcosmos['minvalue']) / $PIVOTX['config']->get('tag_min_font');
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

    $output = "<div id='tagpage'>\n<h2>".__('Tags')."</h2>\n";
    $output .= "<p>".__('This is the local Tag Cosmos for this weblog. The larger the tag, the more entries on this blog are related to it. The tags are ordered alphabetically. Click on any tag to find out more.<br/><br/>')."</p>\n";
    $output .= "<div id=\"tagcosmos\" style=\"font-size: {".$PIVOTX['config']->get('tag_min_font')."}px;\">\n";

    $output .= implode("\n", $htmllinks);

    $output .= "</div>\n</div>\n\n";

    return $output;
}

/**
 * Print out the tag page for any given tag. Returns the output as HTML.
 *
 * @param string $tag
 * @return string
 */
function printTag($tag) {
    global $PIVOTX;

    $tag = normalizeTag($tag);

    // If the hook for the thickbox includes in the header was not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');


    if($PIVOTX['config']->get('tag_fetcher_enabled')==1)  {
        //$para_weblog = "?w=".para_weblog($Current_weblog);
        
        $output = '            <script type="text/javascript">
            /*<![CDATA[ */
            function doList(type, tag)  {
                jQuery("#tgrrsslist").html(\'<img src="'.$PIVOTX['paths']['pivotx_url'].'pics/loadingAnimation.gif" alt=""/>\');
                var url = "'.$PIVOTX['paths']['pivotx_url'].'ajaxhelper.php?function=getTagFeed";
                jQuery.get( url, { type: type, tag: tag }, function(output) { jQuery("#tgrrsslist").html(output); } );
            }
            /* ]]> */
            </script>
        ';
    }

    $output .= "<div id='tagpage'>\n<h2>".__('Tag overview for: ')." '".str_replace("+"," ",$tag)."'</h2>\n\n";
    $output .= "<h3>".__('Entries on this site with ')." '" . str_replace("+"," ",$tag) . "'</h3>\n\n";
    $output .= getEntriesWithTag($tag);
    
    $output .= "<h3>".__('Related tags')."</h3>\n\n";
    
    $output .= getRelatedTags($tag);
    



    if($PIVOTX['config']->get('tag_fetcher_enabled')==1)  {
        $output .= "\n<div id='feedfetcher'>\n";
        $output .= "<h3>".__('External feeds for')." '" . str_replace("+"," ",$tag) . "'</h3>\n\n";
        $output .= "<p>\n".__('Click icon for a list of links on')." '" . str_replace("+"," ",$tag) . "'</p>\n<p id='tagbuttons'>\n";

        $sites = array('technorati'=>'Technorati', 'delicious'=>'Del.icio.us', 'furl'=>'Furl', 'magnolia'=>'Ma.gnolia',
                 'google'=>'Google', 'icerocket'=>'Icerocket', 'tagzania'=>'TagZania', '43things'=>'43 Things' ); 
                 // These sites seem to have gone belly-up. any good replacements?
                 // 'feedster'=>'Feedster', 'shadows'=>'Shadows' , 'feedmarker'=>'Feedmarker'

        foreach ($sites as $key=>$value) {
            $output .= sprintf("<a href=\"javascript:doList('%s','%s');\"><img src=\"%spics/taggerati/%s.png\" alt=\"%s\" /></a>\n",
                $key,
                str_replace('/','', $tag),
                $PIVOTX['paths']['pivotx_url'],
                $key,
                $value);
        }


        $output .= "</p>";

        $output .= "<div id=\"tgrrsslist\"></div>";
        $output .= "</div>\n";
    }


    if($PIVOTX['config']->get('tag_flickr_enabled'))  {
            $output .= "<h3>".__('Flickr images for')." '" . str_replace("+"," ",$tag) . "'</h3>\n\n";
            $output .= '

    <!-- Start of Flickr Badge -->
    <div id="flickrpics">
    <script type="text/javascript" src="http://www.flickr.com/badge_code_v2.gne?show_name=1&amp;count=' . $PIVOTX['config']->get('tag_flickr_amount') . '&amp;display=latest&amp;size=s&amp;layout=h&amp;source=all_tag&amp;tag='.$tag.'"></script>
    </div>

    ';

    }

    $output .= "</div>\n";


    return $output;
}

/**
 * Make a link to any given $tag.
 *
 * @param string $tag
 * @param string $template
 * @return string
 */
function tagLink($tag, $template="") {
    global $PIVOTX;

    $Current_weblog = $PIVOTX['weblogs']->getCurrent();
    $tag = normalizeTag($tag);

    if ( $PIVOTX['config']->get('mod_rewrite')==0 ) {
        $link = $PIVOTX['paths']['site_url']."?t=".urlencode($tag);

        if (para_weblog_needed($Current_weblog)) {
            $link .= "&amp;w=" . para_weblog($Current_weblog);
        }
        if ($template != "") {
            $link .= "&amp;t=$template";
        } 
    } else {
        $link = $PIVOTX['paths']['site_url']."tag/".urlencode($tag);

        if (para_weblog_needed($Current_weblog)) {
            $link .= "/" . para_weblog($Current_weblog);
        }
        if ($template != "") {
            $link .= "/?t=$template";
        }
    }
    
    return $link;

}

/**
 * Get Entries/Pages with a certain Tag
 *
 * @param string $tag
 * @param integer $skip
 * @return unknown
 */
function getEntriesWithTag($tag, $skip=0)  {
    global $PIVOTX;

    // How the entries are formated in the list
    $format_entry = "<li><a href='%link%'>%title%</a><br /><span>%excerpt%</span></li>\n";

    $filename = urlencode($tag).'.tag';
    $tag = str_replace(" ","+", $tag);
    
    if ($PIVOTX['config']->get('db_model')=="flat") {

        // Getting tags for flat files..    
        if(file_exists($PIVOTX['paths']['db_path']."tagdata/$filename"))  {
            $sEntriesString = file_get_contents($PIVOTX['paths']['db_path']."tagdata/$filename");
        } else  {
            return "";
        }
        
        $aEntries = explode(",",$sEntriesString);
        rsort($aEntries);
    
        $aLinks = array();
    
        foreach($aEntries as $nThisEntry)   {
            $PIVOTX['db']->read_entry($nThisEntry);
            $excerpt = makeExcerpt(parse_intro_or_body($PIVOTX['db']->entry["introduction"]. 
                " " . $PIVOTX['db']->entry['body']), 170);
    
            if($PIVOTX['db']->entry["code"] != $skip)    {
                $aLink = $format_entry;
                $aLink = str_replace("%link%", makeFilelink($PIVOTX['db']->entry["code"],'',''), $aLink);
                $aLink = str_replace("%title%", $PIVOTX['db']->entry["title"], $aLink);
                $aLink = str_replace("%excerpt%", $excerpt, $aLink);
                $aLinks[] = $aLink;
            }
        }

    } else {
        
        // Getting tags for MySQL
        $tagtable = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);
        $entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
        
        
			  // Set up DB factory
			  $sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
			      												 $PIVOTX['config']->get('db_databasename'),
			      												 $PIVOTX['config']->get('db_hostname'),
			      												 $PIVOTX['config']->get('db_username'),
			      												 $PIVOTX['config']->get('db_password')
			  									);
			
			  // Set up DB connection
			  $sql = $sqlFactory->getSqlInstance();             
        $qry = array();
        $qry['select']="t.*";
        $qry['from'] = $tagtable . " AS t";
        $qry['order'] = "target_uid DESC";
        $qry['where'][] = "tag LIKE " . $sql->quote($tag);

        $qry['leftjoin'][$entriestable . " AS e"] = "e.uid = t.target_uid";
        $qry['group'] = "t.target_uid";
        $qry['where'][] = "e.status='publish'";
    
        $sql->build_select($qry);
        $sql->query();

        //echo nl2br(htmlentities($sql->get_last_query())); 
        
        $rows = $sql->fetch_all_rows();
        
        foreach($rows as $row) {
           
            if ($row['contenttype']=="entry") {
                
                $PIVOTX['db']->read_entry($row['target_uid']);
                $excerpt = makeExcerpt(parse_intro_or_body($PIVOTX['db']->entry['introduction']. " " 
                    . $PIVOTX['db']->entry['body']), 170);
                
                if($PIVOTX['db']->entry["code"] != $skip)    {
                    $aLink = $format_entry;
                    $aLink = str_replace("%link%", makeFilelink($PIVOTX['db']->entry["code"],'',''), $aLink);
                    $aLink = str_replace("%title%", $PIVOTX['db']->entry["title"], $aLink);
                    $aLink = str_replace("%excerpt%", $excerpt, $aLink);
                    $aLinks[] = $aLink;
                }    
                
            } else if ($row['contenttype']=="page") {

                $page = $PIVOTX['pages']->getPage($row['target_uid']);
                $title = $page['title'];
                $excerpt = makeExcerpt(parse_intro_or_body($page['introduction']. " " . $page['body']), 170);
                
                $aLinks[] = "<li><a href=\"" . makePagelink($page['uri']) . "\">" . $title . "</a><br />\n$excerpt</li>\n";
                
            }
        }
    }

    if (count($aLinks)>0) {
        $sLinkList = "<ul class='taglist'>\n";
        $sLinkList .= implode("\n", $aLinks);
        $sLinkList .= "</ul>\n";
        return $sLinkList;
    } else {
        return "";
    }


}

/**
 * Get Tags that are related to a certain Tag
 *
 * @param string $tag
 * @return unknown
 */
function getRelatedTags($tag) {
    global $PIVOTX, $paths;
    
    if ($PIVOTX['config']->get('db_model')=="flat") {
        
        // Getting related tags for flat files.. 
        $filename = urlencode($tag).'.rel';
        
        if(file_exists($PIVOTX['paths']['db_path']."tagdata/$filename")) {
            $sTagString = file_get_contents($PIVOTX['paths']['db_path']."tagdata/$filename", "r");
            $taglist = explode(",", $sTagString);
        }
    
    } else {
        
        // Getting tags for MySQL
        $tagtable = safe_string($PIVOTX['config']->get('db_prefix')."tags", true);
        
        // Set up DB factory
        $sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
            															 $PIVOTX['config']->get('db_databasename'),
            															 $PIVOTX['config']->get('db_hostname'),
            															 $PIVOTX['config']->get('db_username'),
            															 $PIVOTX['config']->get('db_password')
        												);

        // Set up DB connection
        $sql = $sqlFactory->getSqlInstance();        
        
        // Getting related tags for MySQL db..
        $sql->query("SELECT DISTINCT(t2.tag)
                    FROM pivot_tags AS t1, pivot_tags AS t2
                    WHERE (t1.tag=".$sql->quote($tag)." AND t1.target_uid=t2.target_uid AND t2.tag!=".$sql->quote($tag).")
                    ORDER BY t2.tag ASC" );     
        $rows = $sql->fetch_all_rows();
            
        $taglist = make_valuepairs($rows, '', 'tag');        
   
   
    }

    if (is_array($taglist)) {
        $output = array();
        foreach($taglist as $thistag) {
            $output[] = "<a href=\"". tagLink(str_replace(" ", "+",$thistag))."\" class=\"taglinkext\">$thistag</a>";
        }
        $output = implode(", \n", $output);
    } else {
        $output .= "\n<p>".__('No related tags')."</p>\n";
    }

    return $output;
    
}




/**
 * automagically determine related tags.
 *
 * @param string $tag
 * @param array $p_aAllTags
 */
function makeRelatedTags($tag, $p_aAllTags)    {
    global $PIVOTX;

    // Only make related tags for flat files..
    if ($PIVOTX['config']->get('db_model')=="flat") {

        $filename = urlencode($tag).'.rel';
    
        if(!file_exists($PIVOTX['paths']['db_path']."tagdata/$filename"))  {
            $aRelTags = array();
            foreach($p_aAllTags as $sTheTag)    {
                if($sTheTag != $tag) {
                    array_push($aRelTags, $sTheTag);
                }
            }
    
            if(sizeof($p_aAllTags) > 1) {
                write_file($PIVOTX['paths']['db_path']."tagdata/$filename", implode(",",$aRelTags) );
            }
    
        } else {
    
            $aRelArray = explode(",", implode("", file($PIVOTX['paths']['db_path']."tagdata/$filename")));
            $bMustWrite = false;
    
            foreach($p_aAllTags as $sThisOne)   {
                if((!in_array($sThisOne, $aRelArray)) && (!in_array($tag, $p_aAllTags))) {
                    array_push($aRelArray, $sThisOne);
                    $bMustWrite = true;
                }
            }
    
            if($bMustWrite) {
                write_file($PIVOTX['paths']['db_path']."tagdata/$filename", implode(",",$aRelArray));
            }
        }
    }
}



?>