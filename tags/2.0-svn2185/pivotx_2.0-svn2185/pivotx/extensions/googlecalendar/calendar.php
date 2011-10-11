<?php


DEFINE('PIVOTX_INWEBLOG', TRUE);

require_once(dirname(dirname(dirname(__FILE__)))."/lib.php");

initializePivotX();


$url = $PIVOTX['config']->get('googlecalendar_id');

if (empty($url)) {
    echo "No Calendar ID set in the Calendar widget admin screen!";
    die();
}

$url = "http://www.google.com/calendar/feeds/" . urlencode($url) . "/public/basic";

$max = get_default($PIVOTX['config']->get('googlecalendar_max_items'), $googlecalendar_config['googlecalendar_max_items']);
$header = get_default($PIVOTX['config']->get('googlecalendar_header'), $googlecalendar_config['googlecalendar_header']);
$format = get_default($PIVOTX['config']->get('googlecalendar_format'), $googlecalendar_config['googlecalendar_format']);
$footer = get_default($PIVOTX['config']->get('googlecalendar_footer'), $googlecalendar_config['googlecalendar_footer']);


include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

$rss = fetch_rss($url);

$output = "";

if (count($rss->items)>0) {
    // Slice it, so no more than 4 items will be shown.
    $rss->items = array_slice($rss->items, 0, $max);

    foreach($rss->items as $item) {

        $description = $item['summary'];
        $description = str_replace(array("\n","\r"), "", $description);
        // Get the date from the first line of the description.
        $date = substr($description, 0, strpos($description,'<br>'));
        $date = substr($date, strpos($date,':')+1);
        // Stripping all tags from $description since it's used (by default) 
        // in the title attribute of a link.
        $description = strip_tags(str_replace("<br>", " ", $description)); 

        $temp_output = $format;
        $temp_output = str_replace('%title%', $item['title'] , $temp_output );
        $temp_output = str_replace('%link%', $item['link'] , $temp_output );
        $temp_output = str_replace('%description%', $description, $temp_output );
        $temp_output = str_replace('%date%', $date, $temp_output );

        $output .= $temp_output."\n";
    }
} else {
    debug("<p>Oops! I'm afraid I couldn't read the Google Calendar feed.</p>");
    $output = "<p>" . __("Oops! I'm afraid I couldn't read the Google Calendar feed.") . "</p>";
    debug(magpie_error());
}


echo $header.$output.$footer;

?>
