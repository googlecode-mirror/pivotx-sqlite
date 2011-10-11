<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_search.php 2072 2009-08-27 09:11:43Z hansfn $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }

@set_time_limit(0);
//error_reporting(0);

// 2004/10/27 =*=*= JM
$tmp_filtered_words = getFilteredWords();
// Removing accent from (and lower casing) any accented chars
$filtered_words = array();
foreach($tmp_filtered_words as $word) {
    $filtered_words[] = transliterateAccents($word);
}

global $allowed_chars;
// Since we are using transliterateAccents (and strtolower) on both
// filtering words, search words and text to search, we only need
// numbers and lower case US-ascii characters.
// NB! The non-range hyphen *must* be at the end.
$allowed_chars = "0-9a-z!#$%&+@-";


/**
 * Removing accent (and lower casing) any accented chars.
 *
 * Doing ord() on chars to avoid accented chars in source code
 * If not we will forever have mac/pc/unix transcoding problems.
 * The function might be (too) slow.
 */
function transliterateAccents( $theStr ) {
    $r = '' ;
    if( is_string( $theStr )) {

        $t = strlen( $theStr ) ;
        for( $i=0 ; $i < $t ; $i++ ) {


            list($ord,$chars_used) = i18n_ord($theStr,$i);
            $i += ($chars_used-1);

            // what is this
            switch( $ord ) {
                case( 192 ) : // A-grave
                case( 193 ) : // A-acute
                case( 194 ) : // A-circ
                case( 195 ) : // A-tilde
                case( 196 ) : // A-uml
                case( 197 ) : // A-ring
                case( 224 ) : // a-grave
                case( 225 ) : // a-acute
                case( 226 ) : // a-circ
                case( 227 ) : // a-tilde
                case( 228 ) : // a-uml
                case( 229 ) : // a-ring
                $r .= 'a' ; break ;
                // -------------------------
                case( 193 ) : // AE-lig
                case( 230 ) : // ae-lig
                $r .= 'ae' ; break ;
                // -------------------------
                case( 231 ) : // c-cedil
                case( 199 ) : // C-cedil
                $r .= 'c' ; break ;
                // -------------------------
                case( 200 ) : // E-grave
                case( 201 ) : // E-acute
                case( 202 ) : // E-circ
                case( 203 ) : // E-uml
                case( 232 ) : // e-grave
                case( 233 ) : // e-acute
                case( 234 ) : // e-circ
                case( 235 ) : // e-uml
                $r .= 'e' ; break ;
                // -------------------------
                case( 204 ) : // I-grave
                case( 205 ) : // I-acute
                case( 206 ) : // I-circ
                case( 207 ) : // I-uml
                case( 236 ) : // i-grave
                case( 237 ) : // i-acute
                case( 238 ) : // i-circ
                case( 239 ) : // i-uml
                $r .= 'i' ; break ;
                // -------------------------
                case( 241 ) : // n-tilde
                case( 209 ) : // N-tilde
                $r .= 'n' ; break ;
                // -------------------------
                case( 210 ) : // O-grave
                case( 211 ) : // O-acute
                case( 212 ) : // O-circ
                case( 213 ) : // O-tilde
                case( 214 ) : // O-uml
                case( 216 ) : // O-slash
                case( 242 ) : // o-grave
                case( 243 ) : // o-acute
                case( 244 ) : // o-circ
                case( 245 ) : // o-tilde
                case( 246 ) : // o-uml
                case( 248 ) : // o-slash
                $r .= 'o' ; break ;
                // -------------------------
                // NOTE: these don't get thru form?
                case( 338 ) : // OE-lig
                case( 339 ) : // oe-lig
                $r .= 'oe' ; break ;
                // -------------------------
                case( 217 ) : // U-grave
                case( 218 ) : // U-acute
                case( 219 ) : // U-circ
                case( 220 ) : // U-uml
                case( 249 ) : // u-grave
                case( 250 ) : // u-acute
                case( 251 ) : // u-circ
                case( 252 ) : // u-uml
                $r .= 'u' ; break ;
                // -------------------------
                case( 223 ) : // ss-lig
                $r .= 'ss' ; break ;
                // -------------------------
                // NOTE: y-uml don't get thru form?
                case( 255 ) : // Y-uml
                case( 376 ) : // y-uml
                $r .= 'y' ; break ;
                // -------------------------
                // ADD OTHER CHARS HERE...
                // -------------------------
                default :
                    if ($ord > 127) {
                        $r .= 'z';
                    } else {
                        $r .= $theStr[$i] ;
                    }
            }
        }
    }
    return $r ;
}


// ---------- functions for indexing ------------- //

/**
 * Indexes entries and pages in the PivotX database and returns true
 * if there are more entries to index.
 *
 * @param int $start Code for first entry to index
 * @param int $stop Code for last entry to index
 * @param int $time Indexing time.
 * @return boolean
 */
function startIndex ($start, $stop, $time) {
    global $PIVOTX, $output;

    $entries = $PIVOTX['db']->db_lowlevel->date_index;

    $count = 0;

    $date = date( 'Y-m-d-H-i' );

    $searchcats = $PIVOTX['categories']->getSearchCategorynames();

    foreach($entries as $key => $value) {

        if(($count++)<($start)) { continue; }
        if(($count)>($stop)) { break; }

        $entry = $PIVOTX['db']->read_entry( $key );

        // rules: index if all are true:
        // - ( status == 'publish' )or(( status == 'timed')&&( publish_date <= date ))
        // - at least one category is in array of 'not hidden' categories..

        // check status and date
        if(( 'publish'==$entry['status'] )
        ||(( 'timed'==$entry['status'] )&&( $entry['publish_date'] <= $date ))) {

            // Only index the entry if it is in one or more categories that are not hidden.
            if( count(array_intersect($entry['category'], $searchcats))>0 ) {
                if (($count % 50) == 0) {
                    $output .= sprintf(__("%1.2f sec: Processed %d entries...")."<br />\n", (timetaken('int')+$time), $count);
                    flush();
                }
                stripWords( $entry);
            }
        }
    }

    // decide if we need to do some more.
    if(count($entries) > ($stop)) {
        return true;
    }

    // When we are done with the entries, index the pages (assuming there are much less pages than entries).
    $chapters = $PIVOTX['pages']->getIndex();
    $count = 0;

    foreach($chapters as $chapter) {
        foreach($chapter['pages'] as $page) {

            // rules: index if all are true:
            // - ( status == 'publish' )or(( status == 'timed')&&( publish_date <= date ))
            // - at least one category is in array of 'not hidden' categories..

            // check status and date
            if(( 'publish'==$page['status'] ) || (( 'timed'==$page['status'] )&&( $page['publish_date'] <= $date ))) {

                stripWords( $page, 'p');
                $count++;
            }
        }
    }
    $output .= sprintf(__("Processed %d pages...")."<br />\n", $count);
    flush();

    return false;

}


/**
 * Updates the search index for a single entry.
 *
 * @param array $entry The entry to get indexed/updated.
 * @return void
 */
function updateIndex($entry, $type='e') {
    global $master_index, $PIVOTX;

    stripWords($entry, $type);

    foreach($master_index as $key => $index) {
        $filename = $PIVOTX['paths']['db_path'].'search/' . $key . '.php';

        // load the index if it exists..
        if (file_exists($filename)) {
            $temp = load_serialize($filename);
        } else {
            $temp = array();
        }

        // add the new stuff..
        foreach($index as $key=>$val) {
            if(isset($temp[$key])) {
                $occurr = explode("|", $temp[$key]);
                $occurr[] = $val;
                $val = implode("|", array_unique($occurr));
                $temp[$key] = $val;
            } else {
                $temp[$key] = $val;
            }
        }

        //echo("<br />mems1:".memory_get_usage());
        save_serialize($filename, $temp);
        unset($master_index[$key]);
        $wordcount += count($index);
    }

}


/**
 * Parse the input, strip stop/non-words, remove accents, lower case and
 * add to the index.
 *
 * @uses filterWords The function that strips the stop/non-words
 * @uses addToIndex The function that adds the filtered words to the index
 * @return void
 */
function stripWords ($arr, $type='e') {
    global $allowed_chars;

    $words = $arr['title']." ".$arr['subtitle']." ".$arr['keywords']." ".
        parse_intro_or_body($arr['introduction']." ".$arr['body']);
    // Remove (Java)script and PHP code. (In a perfect world
    // we would run the scripts and PHP code... but not even Google
    // parses the Javascript.)
    $regexp = "#(<script[ >].*?</script>)|(<\?php\s.*?\?>)#is";
    $words = preg_replace($regexp, "", $words);

    $words = unentify(strip_tags(str_replace(">", "> ", str_replace("<", " <",$words))));
    $words = transliterateAccents($words);
    $words = strtolower($words);

    $result = preg_split ('/[^'.preg_quote($allowed_chars).']/', $words);

    $filter = filterWords($result);

    addToIndex($filter, $arr['uid'], $type);

}



/**
 * Adds the words in an array to the index.
 *
 * @param array $arr Words to be added to the index
 * @param int $code Associated entry code.
 * @return void
 */
function addToIndex ($arr,$code,$type='e') {
    global $master_index;

    $arr = array_unique ($arr);

    foreach($arr as $string) {
        if(!isset($master_index[ $string{0} ][ $string ])) {
            $master_index[ $string{0} ][ $string ] = "$type$code";
        } else {
            $master_index[ $string{0} ][ $string ] .= "|$type$code";
        }

    }


}

/**
 * Strips stop/non-words from an array of words.
 *
 * @param array $arr Words to be filtered.
 * @return array
 */
function filterWords ($arr) {
    global $filtered_words, $allowed_chars;

    $clean = array();

    foreach($arr as $value) {
        // The input has already passed through transliterateAccents
        // $value = transliterateAccents( $value );
        $value = preg_replace ('/[^'.preg_quote($allowed_chars).']/i','',$value);

        // Do not include same word several times or very short words
        if (in_array($value,$clean) || (strlen($value) <= 2)) {
            continue;
        }
        // Filtering out common (or just unwanted words)
        if (is_array($filtered_words)) {
            if (!in_array($value, $filtered_words)) {
                $clean[] = $value;
            }
        } else {
            $clean[] = $value;
        }
    }

    return $clean;
}

/**
 * Write the index to file (using the global variable $master_index.
 *
 * @param boolean $silent
 * @return void
 */
function writeIndex ($silent=FALSE) {
    global $master_index, $output, $PIVOTX;

    if( is_array( $master_index )) {

        debug("saving ".count($master_index)." indices.");

        if( 0 != count( $master_index )) {

            $wordcount = 0;

            foreach($master_index as $key => $index) {
                $filename = $PIVOTX['paths']['db_path'].'search/' . $key . '.php';

                // load the index if it exists..
                if (file_exists($filename)) {
                    $temp = load_serialize($filename);
                } else {
                    $temp = array();
                }

                // add the new stuff..
                foreach($index as $key=>$val) {
                    if(isset($temp[$key])) {
                        $occurr = explode("|", $temp[$key]);
                        $occurr[] = $val;
                        $val = implode("|", array_unique($occurr));
                        $temp[$key] = $val;
                    } else {
                        $temp[$key] = $val;
                    }
                }

                save_serialize($filename, $temp);
                $wordcount += count($index);
            }

            if($silent!=TRUE) {
                $output .= "<p>A total of ".$wordcount." different words have been indexed.</p>";
            }
        }
    } else {
        debug("nothing to save");
    }
}


/**
 * Searches the index for words.
 *
 * @param array $str_a Contains the display text (index 0) and the search text (index 1).
 * @return string The search results as a list (in HTML code).
 */
function searchIndex ( $str_a ) {
    global $index_file, $matches_entries, $matches_pages, $allowed_chars, $PIVOTX;

    $str_a[1] = trim($str_a[1]);
    $words = explode(" ", $str_a[1]);
    $orig_words = explode(" ", trim($str_a[0]));
    // Ignoring empty strings and removing non-allowed chars from words
    foreach($words as $key=>$val) {
        if(trim($val)=="") {
            unset($words[$key]);
        } else {
            $words[$key] = preg_replace ('/[^'.preg_quote($allowed_chars).']/i','',trim($val));
        }
    }

    // Load the indices for the $words, if we're using flat files..
    loadSearchIndices($words);

    $n = count($words);
    for($i=0; $i < $n; $i++) {
        // getWord sets $matches_entries and $matches_pages used below.
        $res = getWord($words[$i]);
        if($res) {
            $found_words[] = $orig_words[$i];
        }
    }

    // mix 'n match.. If the result set for 'AND' is empty, just lump
    // them together, so we have an 'OR'..
    if(count($matches_entries)==1) {
        $result_entries = $matches_entries[0];
    } else if(count($matches_entries)==2) {
        list($word1,$word2) = $matches_entries;
        $result_entries = array_intersect($word1, $word2);
        if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2); }
    } else if(count($matches_entries)==3) {
        list($word1, $word2, $word3) = $matches_entries;
        $result_entries = array_intersect($word1, $word2, $word3);
        if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2, $word3); }
    } else if(count($matches_entries)>3) {
        list($word1, $word2, $word3, $word4) = $matches_entries;
        $result_entries = array_intersect($word1, $word2, $word3, $word4);
        if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2, $word3, $word4); }
    }

    // Do the same for the results from pages
    if(!empty($matches_pages)) {
        if(count($matches_pages)==1) {
            $result_pages = $matches_pages[0];
        } else if(count($matches_pages)==2) {
            list($word1,$word2) = $matches_pages;
            $result_pages = array_intersect($word1, $word2);
            if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2); }
        } else if(count($matches_pages)==3) {
            list($word1, $word2, $word3) = $matches_pages;
            $result_pages = array_intersect($word1, $word2, $word3);
            if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2, $word3); }
        } else if(count($matches_pages)>3) {
            list($word1, $word2, $word3, $word4) = $matches_pages;
            $result_pages = array_intersect($word1, $word2, $word3, $word4);
            if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2, $word3, $word4); }
        }

    }


    $title = __('Search Results');
    if (strlen($Weblogs[$Current_weblog]['search_format'])>1) {
        list($format_top,$format_summary,$format_start,$format_entry,$format_end) =
        explode("----",$Weblogs[$Current_weblog]['search_format']);
    } else {
        $format_top = "<h2>%search_title%</h2>\n%search_form%\n";
        $format_summary = "<p>%search_summary%</p>\n";
        $format_start = "<ul id='search-results'>\n";
        $format_entry = "<li><!-- %code% --><a href='%link%'>%title%</a><br /><span>%description%</span></li>\n";
        $format_end = "</ul>\n";
    }

    $output = $format_top;

    // First print out the results from the pages..
    if(!empty($result_pages)) {

        rsort($result_pages);
        $result_pages = array_unique($result_pages);

        $count = count($result_pages);
        $name = implode(', ',$found_words);
        $summary = str_replace('%name%', $name, __('Page matches for "%name%":'));
        $output .= str_replace('%search_summary%', $summary, $format_summary);
        $output .= $format_start;

        foreach($result_pages as $hit) {

            $page = $PIVOTX['pages']->getPage($hit);

            $page['link'] = makePagelink($page['uri']);

            // We make a 'description' to get a quick overview to see what the
            // found page is about..
            $page['description'] = strip_tags(parse_intro_or_body($page['introduction']." ".$page['body']));
            $page['description'] = trimtext($page['description'], 200);

            if ($page['title']=="") {
                $page['title'] = substr(strip_tags($page['introduction']),0,50);
            }

            $output .= format_entry($page,$format_entry);


        }
        $output .= "$format_end\n";
    }


    // Then print out the results from the entries..
    if(!empty($result_entries)) {

        rsort($result_entries);
        $result_entries = array_unique($result_entries);

        $count = count($result_entries);
        $name = implode(', ',$found_words);
        $summary = str_replace('%name%', $name, __('Entry matches for "%name%":'));
        $output .= str_replace('%search_summary%', $summary, $format_summary);
        $output .= $format_start;

        foreach($result_entries as $hit) {

            if($PIVOTX['db']->entry_exists($hit)) {

                $entry = $PIVOTX['db']->read_entry($hit);
                $entry['link'] = makeFilelink($entry['code'], "", "");
                $entry['categories'] = implode(', ',$entry['category']);

                // We make a 'description' to get a quick overview to see what the
                // found entry is about..
                $entry['description'] = strip_tags(parse_intro_or_body($entry['introduction']." ".$entry['body']));
                $entry['description'] = trimtext($entry['description'], 200);

                $weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($entry['category']);
                foreach ($weblogs as $key => $value) {
                    $weblogs[$key] = $Weblogs[$value]['name'];
                }

                $entry['weblogs'] = implode(', ',$weblogs);
                if ($entry['title']=="") {
                    $entry['title'] = substr(strip_tags($entry['introduction']),0,50);
                }

                $output .= format_entry($entry,$format_entry);

            }

        }
        $output .= "$format_end\n";
    }


    if (empty($result_pages) && empty($result_entries)) {
        if ($str_a[1] != "") {
            $count = 0;
            $name = $str_a[0];
            $summary = str_replace('%name%', $name, __('No matches found for "%name%". Try something else.')) ;
            $output .= str_replace('%search_summary%', $summary, $format_summary);

        }
    }

    $output = str_replace("%search_term%", $name, $output);
    $output = str_replace("%search_count%", $count, $output);
    //$output = str_replace("%search_summary%", $summary, $output);
    $output = str_replace("%search_title%", $title, $output);
    return $output;
}


/**
 * Searches the index and returns the matching entries in an array.
 *
 * Used in the entries screen/overview search.
 *
 * @param string $str Text/words to search for
 * @return array
 */
function searchEntries ($str) {
    global $index_file, $matches_entries, $search_all, $PIVOTX;

    // Determine if all blogs should be searched.
    if (defined('PIVOTX_INADMIN')) {
        $search_all = true;
    } elseif (($_REQUEST['w'] == "_all_") || ($PIVOTX['config']->get('weblog_count') == 1)) {
        $search_all = true;
    } else {
        $search_all = false;
    }

    $str = transliterateAccents(trim($str));

    $words = explode(" ", $str);
    foreach($words as $key=>$val) {
        if(trim($val)=="") {
            unset($words[$key]);
        } else {
            $words[$key] = trim($val);
        }
    }

    // Load the indices for the $words, if we're using flat files..
    loadSearchIndices($words);

    foreach($words as $word) {
        $res = getWord($word);
        if($res) {
            $found_words[]=$word;
        }
    }

    // mix 'n match.. If the result set for 'AND' is empty, just lump
    // them together, so we have an 'OR'..
    if(count($matches_entries)==1) {
        $result = $matches_entries[0];
    } else if(count($matches_entries)==2) {
        list($word1,$word2) = $matches_entries;
        $result = array_intersect($word1, $word2);
        if(count($result)==0) { $result = array_merge($word1, $word2); }
    } else if(count($matches_entries)==3) {
        list($word1, $word2, $word3) = $matches_entries;
        $result = array_intersect($word1, $word2, $word3);
        if(count($result)==0) { $result = array_merge($word1, $word2, $word3); }
    } else if(count($matches_entries)>3) {
        list($word1, $word2, $word3, $word4) = $matches_entries;
        $result = array_intersect($word1, $word2, $word3, $word4);
        if(count($result)==0) { $result = array_merge($word1, $word2, $word3, $word4); }
    }

    if(isset($found_words) && (count($found_words)>0)) {

        foreach($result as $hit) {

            $entry = $PIVOTX['db']->read_entry($hit);
            if ($entry['title']=="") {
                $entry['title'] = trimtext(strip_tags($entry['introduction']),50);
            }
            $entry['excerpt'] = makeExcerpt($entry['introduction']);
            unset($entry['comments']);
            unset($entry['introduction']);
            unset($entry['body']);
            $output[]=$entry;

        }

        return $output;
    } else {
        return array();
    }
}


function loadSearchIndices($words) {
    global $index_file, $PIVOTX;

    if ( ($PIVOTX['config']->get('db_model')=="flat") && (count($words)>0) ) {
        foreach ($words as $word) {
            $file = $PIVOTX['paths']['db_path'].'search/'.$word[0].'.php';
            if (file_exists($file)) {
                $index_file[ $word[0] ] = load_serialize($file);
            }
        }
    }

}


/**
 * Search for a given word. It branches depending on the selected database model
 *
 * @param string $word Word to search for.
 * @return boolean false if not found, else true.
 */
function getWord($word) {
    global $PIVOTX;

    if ($PIVOTX['config']->get('db_model')=="flat") {
        return getWordFlat($word);
    } else {
        return getWordSql($word);
    }

}


/**
 * Checks if a word is part of the search index and if so sets the global variable
 * $matches_entries to the matching entry codes and the global variable
 * $matches_pages to the matching page codes.
 * 
 * @param string $word Word to search for.
 * @return boolean False if not found, else true.
 */
function getWordFlat($word) {
    global $search_all, $index_file, $PIVOTX, $matches_pages, $matches_entries;

    $found = false;

    if(isset($index_file[ $word[0] ][ $word ])) {
        $tmp_matches = explode("|", $index_file[ $word[0] ][ $word ]);
        // All entries and pages should be searched.
        if ($search_all) {
            if (count($tmp_matches)>0) {
                foreach($tmp_matches as $match) {
                    $type = substr($match,0,1);
                    $match = substr($match,1);
                     if ($type == 'e') {
                         $valid_matches_entries[] = $match;
                     } elseif ($type == 'p') {
                         $valid_matches_pages[] = $match;
                     }
                }
                return TRUE;
            } else {
                return FALSE;
            }

        }
        // OK, we are run from a weblog - check if the matched entries and/or 
        // pages belong to the current weblog, and are still published,
        // i.e., not set on hold.

        foreach($tmp_matches as $match) {
            $type = substr($match,0,1);
            $match = substr($match,1);
            // Handling entries
            if ($type == 'e') {
                $PIVOTX['db']->read_entry($match);
                // $weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($PIVOTX['db']->entry['category']);

                if ($PIVOTX['db']->entry['status'] == "publish") {  // in_array($Current_weblog,$weblogs) &&
                    $valid_matches_entries[] = $match;
                }
            } else if ($type == 'p') {
                $page = $PIVOTX['pages']->getPage($match);
                if ($page['status'] == "publish") {  
                    $valid_matches_pages[] = $match;
                }
            } else {
                debug('Unknown type in search index - this can\'t happen ...');
            }

        }
        if (count($valid_matches_pages)>0) {
            $matches_pages[] = $valid_matches_pages;
            $found = true;
        }
        if (count($valid_matches_entries)>0) {
            $matches_entries[] = $valid_matches_entries;
            $found = true;
        }
    }
    return $found;
}


/**
 * Checks if a word is part of the search index and if so sets the global variable
 * $matches_entries to the matching entry codes and the global variable $matches_pages 
 * to the matching page codes.
 *
 * If this function returns no results when they are expected, keep in mind that:
 * # Words called stopwords are ignored, you can specify your own stopwords, but
 *   default words include the, have, some - see default stopwords list.
 * # If a word is present in more than 50% of the rows it will have a weight of
 *   zero. This has advantages on large datasets, but can make testing difficult on
 *   small ones.
 * http://www.petefreitag.com/item/477.cfm
 *
 * @param string $word Word to search for.
 * @return boolean false if not found, else true.
 */
function getWordSql($word) {
    global $PIVOTX, $matches_entries, $matches_pages;

    $entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
    $pagestable = safe_string($PIVOTX['config']->get('db_prefix')."pages", true);

    // Set up DB connection
    $database = new sql('mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );

    // Make sure we don't inject unwanted stuff..
    $word = $database->quote($word);

    $query = "SELECT uid FROM $entriestable WHERE MATCH(title, subtitle, introduction, body, keywords) AGAINST ($word) AND status='publish';";

    $database->query($query);

    //echo nl2br(htmlentities($PIVOTX['db']->get_last_query()));

    $result_entries = $database->fetch_all_rows();
    if (!empty($result_entries)) {
        $matches_entries[] = make_valuepairs($result_entries, '', 'uid');
    }

    $query = "SELECT uid FROM $pagestable WHERE MATCH(title, subtitle, introduction, body, keywords) AGAINST ($word) AND status='publish';";

    $database->query($query);

    $result_pages = $database->fetch_all_rows();
    if(!empty($result_pages)) {
        $matches_pages[] = make_valuepairs($result_pages, '', 'uid');
    }

    if (empty($result_entries) && empty($result_pages)) {
        // If we get here, we have no results. See if we can put a reasonable 
        // explanation in the debug logs.
        $database->query("SELECT COUNT('uid') AS count FROM $entriestable");
        $countentries = $database->fetch_row();
        $database->query("SELECT COUNT('uid') AS count FROM $pagestable");
        $countpages = $database->fetch_row();
        
        if ( $countpages<5 || $countentries<5 ) {
            debug("Your search provided no results. Since you have few entries/pages, it ".
                "could be that the search returned nothing, because there are too few records".
                "to search, or that your search term is present in 50% or more of the pages/entries");
        } else if (strlen($word)<6) {
            // we use '6' for length, because 'quotes' were added, so it triggers if the
            // original $word was shorter than 4 characters.
            debug("Your search provided no results, probably because the search term is too ".
                "short, causing MySQL to ignore it.");            
        } else {
            debug("Your search provided no results. Either your term is really not present, ".
                "or your search term is in the list of MySQL stop words, which are ignored by default");            
        }
        
    }

    return true;
}


/**
 * Returns the search form and (possibly) the search results.
 *
 * @uses searchIndex
 * @return string
 */
function searchResult() {
    global $PIVOTX;
    
    static $counter;
    $counter++;
    
    // search is an array
    // 0 -> for display
    // 1 -> for search [no accents]
    $search_str = trim( get_default($_GET['q'], $_POST['q']));

    // Avoiding XSS attacks in display string
    $search_a[0] = htmlspecialchars($search_str);
    // Removing any accented chars and lower casing search string
    $search_a[1] = strtolower(transliterateAccents($search_str));

    $search_formname    = __('Search for words used in entries on this website') ;
    $search_fldname     = __('Enter the word[s] to search for here:') ;
    $search_idname      = 'search-'.$counter ;
    $search_placeholder = __('Enter searchterms') ;

    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $search_url = $PIVOTX['paths']['site_url']."index.php?q=";
    } else {
        $prefix = get_default($PIVOTX['config']->get('localised_search_prefix'), "search");
        $search_url = $PIVOTX['paths']['site_url'].makeURI($prefix);
    }

    // build up accessible form, keeping track of current weblog (if multiple)
    $form = '<form method="post" action="'.$search_url.'" class="pivotx-search-result">'."\n" ;
    $form .= '<fieldset><legend>'.$search_formname.'</legend>'."\n" ;
    $form .= '<label for="'.$search_idname.'">'.$search_fldname.'</label>'."\n" ;
    $form .= '<input id="'.$search_idname.'" type="text" name="q" class="result-searchbox" value="';
    $form .= $search_a[0].'" onfocus="this.select();" />'."\n" ;
    $form .= '<input type="submit" class="result-searchbutton" value="'.__('Search!').'" />'."\n" ;

    //if ($Cfg['weblog_count'] > 1) {
    //    $form .= '<input type="hidden" name="w" value="'.$Current_weblog.'" />'."\n";
    //}

    $form .= '</fieldset></form>'."\n" ;
    // add search results - if any
    $output = searchIndex( $search_a ) ;
    $output = str_replace("%search_form%", $form, $output);

    return $output;
}



?>
