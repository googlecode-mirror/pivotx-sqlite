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
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CurrentEncoding; ?>" />
    <title>Pivot &#187; <?php _e('Send Pings'); ?></title>
    <link href="../<?php echo $theme['css']; ?>" rel="stylesheet" type="text/css" />
    <script language="JavaScript" type="text/javascript">
        document.onkeypress = function esc(e) {
            if(typeof(e) == "undefined") { e=event; }
            if (e.keyCode == 27) { self.close(); }
                }
        </script>
</head>
<body style="margin: 6px 6px 6px 6px; background-image: none;" onload="self.focus();">
<h1>Pivot &raquo; Ping Update Trackers</h1>
<?php 

flush();

@set_time_limit(180);

// pings an update tracker..
function pingWeblogs($name, $url, $server) {
    global $PIVOTX;

    if (strpos($server,"http://") === false) {
        $server = "http://".$server;
    }
    $server = parse_url($server);

    if ($server['path'] == "") { $server['path'] = "/"; }
    if ($server['port'] == "") { $server['port'] = "80"; }

    printf("<p><b>%s:%s%s</b>:<br />", $server['host'], $server['port'], $server['path']);

    flush();


    $client = new xmlrpc_client($server['path'], $server['host'], $server['port']);
    $message = new xmlrpcmsg("weblogUpdates.ping", array(new xmlrpcval($name), new xmlrpcval($url)));
    $result = $client->send($message);


    if (!$result || $result->faultCode()) {

        echo "<br />Pivot says: could not send ping. Check if you set the server address correctly, or else the server may be temporarily down. This happens sometimes, and if this error occurs out of the blue, it's likely that it will go away in a few hours or days. <br /></p>";
        echo "<!-- \n";
            print_r($result);
        echo "\n -->\n\n\n";
        return(false);
    }
    $msg = $result->serialize();

    $msg = preg_replace('#.*<name>message</name>[^<]*<value>(.*?)</value>.*#si','$1',$msg);
    // Stripping off any tags in the message value - typically the string element 
    $msg = strip_tags($msg);
    $msg = escape($msg);

    echo "Server said: <em>'$msg'</em><br /></p>";
    return(true);

}



// Determine what weblogs to ping. If $Pivot_vars['weblogs'] is not set, we
// send pings for all weblogs.
if (!isset($Pivot_Vars['weblogs'])) {
    $ping_weblogs = array();
    foreach ($Weblogs as $name => $weblog) {
        $ping_weblogs[] = $name;
    }
} else {
    $ping_weblogs = explode('|', urldecode($Pivot_Vars['weblogs']));
}


echo "<p>Now sending update-pings. This might take a while, so please be patient.<br />";

foreach ($ping_weblogs as $ping_weblog) {

    echo "<hr size='1' noshade='noshade' />";

    $ping_weblog = weblog_from_para($ping_weblog);

    $title = $Weblogs[$ping_weblog]['name'];
    $Current_weblog = $ping_weblog;
    $file = $Weblogs[$ping_weblog]['front_filename'];


    $logpath = $Weblogs[$ping_weblog]['front_path'];

    if (siteurl_isset()) {

        $url = $Weblogs[$ping_weblog]['siteurl'];

    } else {

        $url= $PIVOTX['paths']['pivotx_url'] . $logpath . $file;
        $url = gethost() . fixpath($url);

    }

    $url = str_replace("/index.php", "/", $url);
    $url = str_replace("/index.html", "/", $url);

    $servers = explode("\n", $Cfg['ping_urls']);


    echo "<p>url: $url<br /></p>";
    flush();

    foreach ($servers as $server) {

        $server = trim($server);
        if (strlen($server)>3) {
            pingWeblogs($title, $url, $server);
            flush();
        }

    }

}

echo "<p><br /><b><a href='#' onclick='self.close();'>done</a></b></p>";

?></body></html>
