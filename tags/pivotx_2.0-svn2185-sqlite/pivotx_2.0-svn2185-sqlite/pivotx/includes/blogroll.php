<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under 
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: blogroll.php 1672 2009-01-08 23:50:54Z hansfn $
//
// ---------------------------------------------------------------------------


if (isset($_GET["id"])) {
	$uid = $_GET["id"];
	$color = $_GET["color"];
	list ($fg, $bg, $line1, $line2, $c1, $c2, $c3, $c4) = explode("|", $color);
} else {
	$uid = "";
	$color = $_GET["color"];
	list ($fg, $bg, $line1, $line2, $c1, $c2, $c3, $c4) = explode("|", "#000000|#FFFFFF|#666666|#E8E8E8|#90A8BF|#ACBECF|#C8D4DF|#E3E9EF");
}



?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Blogroll</title>
<style>

body {
	margin: 0px; 
	padding: 0px;
	font-size: 10px;
	font-family: Arial, "sans serif";
}

a {
	text-decoration: none;
	color: <?php echo $fg; ?>;
	font-weight: bold;
}

a:hover {
	text-decoration: underline;
}

.header {
	font-size: 11px;
	font-family: Arial, "sans serif";
	font-weight: bold;
	padding: 2px 4px 2px 4px;
	border-top: 1px solid <?php echo $line1; ?>;
	border-bottom: 1px solid <?php echo $line1; ?>;
	background: #CCC;
}

.header2 {
	font-size: 10px;
	font-family: Arial, "sans serif";
	padding: 2px 4px 2px 4px;
	background: #FFF;
}

.header2 a {
	text-decoration: underline;
}

.post0, .post1, .post2, .post3, .post4 {
	padding: 1px 4px 1px 4px;
	border-bottom: 1px solid <?php echo $line2; ?>;
}

.post0 {
	background: <?php echo $bg; ?>;
}


.post1 {
	background: <?php echo $c1; ?>;
}

.post2 {
	background: <?php echo $c2; ?>;
}

.post3 {
	background: <?php echo $c3; ?>;
}

.post4 {
	background: <?php echo $c4; ?>;
}

.dim {
	float: right;
	color: #666;
	line-height: 11px;
}

</style>
<script language="JavaScript" type="text/JavaScript">
<!--
var img_plus = new Image; img_plus.src='../pics/node_plus.gif';
var img_min = new Image; img_plus.src='../pics/node_min.gif';

function toggle(name) {

	mytr=document.getElementsByTagName('div');

	for (i=0;i<mytr.length;i++) {
		if (mytr[i].id==name) {

			if ( (mytr[i].style.visibility=="") || (mytr[i].style.visibility=="visible") ) {
				mytr[i].style.visibility="hidden"; mytr[i].style.display="none";
			} else {
				mytr[i].style.visibility="visible"; mytr[i].style.display="block";
			}

		}
	}

	if (document.images[name].src.match(/node_min/)) {
		document.images[name].src='../pics/node_plus.gif';
	} else {
		document.images[name].src='../pics/node_min.gif';
	}

	if (document.images[name+"-all"].src.match(/node_min/)) {
		document.images[name+"-all"].src='../pics/node_plusplus.gif';
	} else {
		document.images[name+"-all"].src='../pics/node_minmin.gif';
	}
}

function toggle_all(clickname) {
	
	mytr=document.getElementsByTagName('div');

	if (document.images[clickname+"-all"].src.match(/node_min/)) {
		for (i=0;i<mytr.length;i++) {
			name= mytr[i].id;
			if (name.length>0) {
				mytr[i].style.visibility="hidden"; mytr[i].style.display="none";
				document.images[name].src='../pics/node_plus.gif';
				document.images[name+"-all"].src='../pics/node_plusplus.gif';
			}
		}
		toggle(clickname);

	} else {
		for (i=0;i<mytr.length;i++) {
			name= mytr[i].id;
			if (name.length>0) {
				mytr[i].style.visibility="visible"; mytr[i].style.display="block";
				document.images[name].src='../pics/node_min.gif';
				document.images[name+"-all"].src='../pics/node_minmin.gif';
			}
		}

	}


}

//-->
</script>
</head>

<body><?php

// an easy function to recursively create chmodded directory's
function makedir($name) {

	// if it exists, just return.
	if (file_exists($name)) {
		return;
	}

	// if more than one level, try parent first..
	if (dirname($name) != ".") {
		makedir(dirname($name));
	}

	$oldumask = umask(0);
	@mkdir ($name, 0777);
	@chmod ($name, 0777);
	umask($oldumask);

}

function self() {
	global $uid, $colors;

	return $_SERVER['PHP_SELF']."?id=$uid&amp;colors=".urlencode($colors);
}

$order = (isset($_GET['order'])) ? TRUE : FALSE;
$force = (isset($_GET['force'])) ? TRUE : FALSE;

define('MAGPIE_DIR', dirname(__FILE__).'/magpierss/');
define('MAGPIE_CACHE_DIR', $pivotx_path.'./db/rsscache/');
define('MAGPIE_FETCH_TIME_OUT', 5);	// 5 second timeout
define('MAGPIE_CACHE_AGE', 60*60*8); // 8 hours

require_once('magpierss/rss_fetch.inc');

$url = "http://rpc.blogrolling.com/rss.php?r=$uid";


$months['January']=1;
$months['February']=2;
$months['March']=3;
$months['April']=4;
$months['May']=5;
$months['June']=6;
$months['July']=7;
$months['August']=8;
$months['September']=9;
$months['October']=10;
$months['November']=11;
$months['December']=12;


//functions
function usort_time($a, $b) {
	if ($a['diff'] == $b['diff']) {
		return (strtolower($a['title']) < strtolower($b['title'])) ? -1 : 1;
	}
	return ($a['diff'] < $b['diff']) ? -1 : 1;
}

function usort_category($a, $b) {
	if ($a['category'] == $b['category']) {
		return (strtolower($a['title']) < strtolower($b['title'])) ? -1 : 1;
	}
	return (strtolower($a['category']) < strtolower($b['category'])) ? -1 : 1;
}



if ($uid) {

	if (file_exists(MAGPIE_CACHE_DIR."$uid")) {
		$time= time() - filemtime(MAGPIE_CACHE_DIR."$uid");
		$oldness = round($time / 60);
		if ($oldness>MAGPIE_CACHE_AGE) { $oldness = 0; }
	} else {
		$oldness= 0;
	}
	


	echo "<div class='header2'>";
	echo "cached $oldness min. ago |  "; // <a href='index.php?force=1' class='line'>refresh</a>
	if ($order) {
		echo "<a href='".self()."' class='line' target='_self'>group</a></div>";
	} else {
		echo "<a href='".self()."&amp;order=1' class='line' target='_self'>sort</a></div>";
	}

	$feed = fetch_rss($url);

	if (count($feed->items)<1) {
		echo "<h3>No links in Blogroll!</h3>";
	}

	$now= date("mdHi", mktime(date("H")-1,date("i"),0,date("m"),  date("d"), 0 ));

	$key=0;
	// some sorting and processing..
	foreach ( $feed->items as $item ) {
		
		$key++;

		$rssData[$key] = $item;
		$rssData[$key]['post']="0";
		$rssData[$key]['diff']="99999999";
		$rssData[$key]['ago'] = "";
		$rssData[$key]['category'] = 0;
		$rssData[$key]['stamp']="00000000";
	
		if(preg_match("/(\d+):(\d+):(\d+) GMT on (\w+), (\w+) (\d+)/i", $item['description'], $match)) {

			$rssData[$key]['stamp'] = sprintf("%02d%02d%02d%02d", $months[$match[5]], $match[6], $match[1]+1, $match[2]);
			
			if ( ($now-$rssData[$key]['stamp']) < 0 ) {
				$rssData[$key]['post'] = "0";
				$rssData[$key]['stamp']="-99999999999";							
			} else if ( ($now-$rssData[$key]['stamp']) < 200 ) {
				$rssData[$key]['post'] = "1";
			} else if ( ($now-$rssData[$key]['stamp']) < 500 ) {
				$rssData[$key]['post'] = "2";
			} else if ( ($now-$rssData[$key]['stamp']) < 1300 ) {
				$rssData[$key]['post'] = "3";
			} else if ( ($now-$rssData[$key]['stamp']) < 10000 ) {
				$rssData[$key]['post'] = "4";
			}
			$rssData[$key]['diff'] = $now - $rssData[$key]['stamp'];
			if ($rssData[$key]['diff']<2400) {
				$rssData[$key]['ago'] = sprintf("<span style='color:#000'>%02d:%02d</span>",  $rssData[$key]['diff'] / 100, ($rssData[$key]['diff'] % 100) *0.6);
			} else if ($rssData[$key]['diff']<20000) {
				$rssData[$key]['ago'] = "yesterday";
			} else if ($rssData[$key]['diff']<80000) {
				$rssData[$key]['ago'] = "this week";
			} else {
				$rssData[$key]['ago'] = "long ago";
			}

		}

		// filter out the 'category'
		if(preg_match("/\[([a-zA-Z0-9+-]+)\]/i", $item['title'], $match)) {
			$rssData[$key]['category'] = ucfirst($match[1]);
			$rssData[$key]['title'] = preg_replace("/\[([a-zA-Z0-9+-]+)\]/i", "", $item['title']);
		}

		if (isset($category[$rssData[$key]['category']])) {
			$rssData[$key]['category'] = $category[$rssData[$key]['category']];
		}

	}

	$last_cat="";
	$count=0;

	if ($order) {
		usort ($rssData,  "usort_time");
	} else {
		usort ($rssData,  "usort_category");
	}

	// some printing..
	foreach ( $rssData as $item ) {

		if ( ($item['category'] != $last_cat) && (!$order) ) {
			printf("</div>\n\n<div class='header'>");
			printf("<span class='dim'><a href=\"javascript:toggle_all('%s');\"><img src='../pics/node_minmin.gif' border=0 name='%s-all'></a></span>\n", $item['category'], $item['category']);
			printf("<a href=\"javascript:toggle('%s');\"><img src='../pics/node_min.gif' border=0 name='%s'>", $item['category'], $item['category']);
			printf("%s</a></div>\n", $item['category'], $item['category']);
			printf("<div class='links' id='%s' name='%s'>\n", $item['category'], $item['category'], $item['category']);
			$last_cat=$item['category'];
		}

		printf("<div class='post%s'>", $item['post'] );
		printf("<span class='dim'>%s</span>" ,$item['ago'] );
		printf("<a href='%s' title='%s' target='_blank'>%s</a></div>\n", $item['link'],$item['description'],  stripslashes($item['title']));

	}
	echo "</div>";

} else {
	echo "<div class='header2'><b>No blogrolling id found..</b><br />";
}

?>

<div class='header2'>Thanks to: <a href="http://www.blogrolling.com" target='_blank'>Blogrolling.com</a>
</div>
</body></html>
