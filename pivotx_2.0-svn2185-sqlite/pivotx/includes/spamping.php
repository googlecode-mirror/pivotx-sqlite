<?php


// error_reporting(E_ALL);
require_once('xmlrpc/xmlrpc.inc');
require_once('../pv_core.php');
CheckLogin();
Setpaths();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CurrentEncoding; ?>\"" />
	<title>Pivot &#187; <?php _e('Send Pings'); ?></title>
	<link href="../<?php echo $theme['css']; ?>" rel="stylesheet" type="text/css" />
	<script language='JavaScript'  type='text/javascript'>
	self.focus();
	</script>
<body style="margin: 6px 6px 6px 6px; background-image: none;" onload="self.focus()";>
<h1>Pivot &raquo; Report Spam Ping</h1>
<?php

flush();

@set_time_limit(180);

// pings Weblogs.com
function pingWeblogs($name, $url, $server) {
	global $PIVOTX, $Cfg, $Pivot_Vars;


	if(!isset($Cfg['spampingurl'])) {
		$server = parse_url("http://www.pivotlog.net/global_phrases/ping.php");
	} else {
		$server = parse_url($Cfg['spampingurl']."ping.php");
	}

	if ($server['path'] == "") { $server['path'] = "/"; }
	if ($server['port'] == "") { $server['port'] = "80"; }

	//printf("<p><b>%s:%s%s</b>:<br />", $server['host'], $server['port'], $server['path']);

	flush();

	// Get the entry
	$db = new db();
	$PIVOTX['db']->read_entry(intval($_GET['entry']));

	// Initialize the XMLRPC client.
	$client = new xmlrpc_client($server['path'], $server['host'], $server['port']);
	//$client->setDebug(2);

	$type = safe_string($_GET['type'],true);

	// Compose the xmlrpc message, depending on the type
	if ($type=="comment") {


		// Check if it's a link in the form of name@date..
		// If so, we'll have to walk the comments, selecting the correct one.
		if (strpos($_GET['n'],"@")>0) {

			list($delname, $deldate) = explode("@", $_GET['n']);
			$_GET['n'] = -1;

			foreach($PIVOTX['db']->entry['comments'] as $key => $value) {
				if (($value['name']==$delname) && ($value['date']==$deldate) ) {
					$_GET['n'] = $key;
				}
			}
		}



		$comment = $PIVOTX['db']->entry['comments'][ intval($_GET['n'])];

		if ($comment['name']!="") {
			$ok = true;
			$message = new xmlrpcmsg("spamReport.commentping", array(
								new xmlrpcval($type),
								new xmlrpcval($comment['name']),
								new xmlrpcval($comment['url']),
								new xmlrpcval($comment['comment']),
								new xmlrpcval($comment['ip']),
								new xmlrpcval($Pivot_Vars['user']),
								new xmlrpcval($Cfg['sitename']),
								new xmlrpcval($Cfg['server_spam_key']),
							)
						);
		} else {
			$ok = false;
		}

	} else {

		$track = $PIVOTX['db']->entry['trackbacks'][ intval($_GET['n'])];

		if ($comment['name']!="") {
			$ok = true;
			$message = new xmlrpcmsg("spamReport.commentping", array(
								new xmlrpcval($type),
								new xmlrpcval($track['title']),
								new xmlrpcval($track['url']),
								new xmlrpcval($track['excerpt']),
								new xmlrpcval($track['ip']),
								new xmlrpcval($Pivot_Vars['user']),
								new xmlrpcval($Cfg['sitename']),
								new xmlrpcval($Cfg['server_spam_key']),
							)
						);
		} else {
			$ok = false;
		}



	}

	if ($ok) {

		$result = $client->send($message);

		if (!$result || $result->faultCode()) {

			echo "<br />Pivot says: could not send ping. Check if you set the server address correctly, or else the server may be temporarily down. This happens sometimes, and if this error occurs out of the blue, it's likely that it will go away in a few hours or days. <br /></p>";
			echo "<!-- \n";
				print_r($result);
			echo "\n -->\n\n\n";
			return(false);
		}
		$msg = $result->serialize();

		$msg = preg_replace("/.*<\/boolean>/si","",$msg);
		$msg = preg_replace("/.*<value>/si","",$msg);
		$msg = preg_replace("/<\/value>.* /si","",$msg);

		$msg = escape($msg);

		echo $msg;
		return(true);
	} else {
		echo "Could not ping: Comment or trackback could not be found";
	}
}



$title = $Weblogs[urldecode($Pivot_Vars['title'])]['name'];
$Current_weblog = urldecode($Pivot_Vars['title']);
$file = urldecode($Pivot_Vars['file']);

$logpath = $Weblogs[$Current_weblog]['front_path'];


if (siteurl_isset()) {

	$url = $Weblogs[$Current_weblog]['siteurl'];

} else {

	$url= $PIVOTX['paths']['pivotx_url'] . $logpath . $file;
	$url = gethost() . fixpath($url);

}

$url = str_replace("/index.php", "/", $url);
$url = str_replace("/index.html", "/", $url);

$servers = explode("\n", $Cfg['ping_urls']);

echo "<p>Now sending Report Spam pings. This might take a while, so please be patient.</p><br />";

flush();

foreach ($servers as $server) {

	$server = trim($server);
	if (strlen($server)>3) {
		pingWeblogs($title, $url, $server);
		flush();
	}

}

echo "<br /><br /><hr /><p>This window will auto-close in 10 seconds, <a href='javascript:self.close();'>or close it manually</a><p>\n\n";

echo "<script language='JavaScript'  type='text/javascript'>\n";
echo "setTimeout('self.close()',10000);";
echo "</script>\n";

?></body></html>
