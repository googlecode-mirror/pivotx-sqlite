<?php

require_once("../lib.php");

$title = $_GET['src'];

if (isBase64Encoded($title)) {
    $title = base64_decode($title);
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=480, initial-scale=1, user-scalable=yes" />
        <meta name="apple-mobile-web-app-capable" content="yes">
        <title><?php echo entifyQuotes(strip_tags($title)); ?> - PivotX</title>
        <style type="text/css">
            body, html, img { margin: 0; padding: 0; }
        </style>
    </head>
    <body>
    <img src="timthumb.php?<?php echo entifyQuotes($_SERVER['QUERY_STRING']); ?>" title="<?php echo entifyQuotes(strip_tags($title)); ?>" />
    </body>
</html>
