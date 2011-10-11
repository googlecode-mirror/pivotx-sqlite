<?php

// TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
// http://code.google.com/p/timthumb/
//
// MIT License: http://www.opensource.org/licenses/mit-license.php
//
// Adapted for PivotX
//
// Parameters allowed: 

// w: width
// h: height
// zc: zoom crop (0 or 1)
// q: quality (default is 80 and max is 100)

// HTML examples:
//
// Local files:
// <img src="[[pivotx_dir]]includes/timthumb.php?src=whatever.jpg&w=150&h=200&zc=1" alt="" />
// <img src="[[pivotx_dir]]includes/timthumb.php?src=MjAwOC0wOS9uc2Z3X2xhdXJlbl9kcmlua2luZ19mcm=&w=150&h=200&zc=1" alt="" />
//
// Remote files: (it's advisable to use base64_encoded URLs, since a lot of
// browsers won't allow 'normal' URL's in the parameter string.
// <img src="[[pivotx_dir]]includes/timthumb.php?src=aHR0cDovL3Bpdm90eC5uZXQvcGl2b3QvdGVtcGxhdGVzL3Bpdm90eC9pbWFnZXMvaGVhZGVyLmpwZw==&w=300&h=200&zc=1" alt="" />
// <img src="[[pivotx_dir]]includes/timthumb.php?src=http://pivotx.net/pivot/templates/pivotx/images/header.jpg&w=300&h=200&zc=1" alt="" />


// Set this to 'true' to allow thumbnailing of off-site images. 
$allow_remote_thumbnailing = false;
	
// Change this, if you've changed the default upload folder for images in the PivotX configuration.    
$base_folder = "images";    
    
    
error_reporting(0);

if( !isset( $_REQUEST[ "src" ] ) ) { die( "no image specified" ); }

// clean params before use
$src = clean_source( $_REQUEST[ "src" ] );

// set document root
$doc_root = get_document_root($src);
                   
// get path to image on file system
$src = $doc_root . $src;

$new_width = intval($_REQUEST['w']);
$new_height = intval($_REQUEST['h']);

if( !isset( $_REQUEST['q'] ) ) { $quality = 80; } else { $quality = intval($_REQUEST['q']); }
if( !isset( $_REQUEST['zc'] ) ) { $zoom_crop = 1; } else { $zoom_crop = intval($_REQUEST[ 'zc' ]); }


// set path to cache directory (default is ./cache)
// this can be changed to a different location
$cache_dir = '../db/cache/thumbnails/';

// get mime type of src
$mime_type = mime_type( $src );

// check to see if this image is in the cache already
check_cache( $cache_dir, $mime_type );

// make sure that the src is gif/jpg/png
if( !valid_src_mime_type( $mime_type ) ) {
	$error = "Invalid src mime type: $mime_type";
	die( $error );
}

// check to see if GD function exist
if(!function_exists('imagecreatetruecolor')) {
	$error = "GD Library Error: imagecreatetruecolor does not exist";
	die( $error );
}


// open the existing image
$image = open_image( $mime_type, $src );
if( $image === false ) { die( 'Unable to open image : ' . $src ); }		

// Get original width and height
$width = imagesx( $image );
$height = imagesy( $image );

// don't allow new width or height to be greater than the original
if( $new_width > $width ) { $new_width = $width; }
if( $new_height > $height ) { $new_height = $height; }

// generate new w/h if not provided
if( $new_width && !$new_height ) {
    $new_height = $height * ( $new_width / $width );
}
elseif($new_height && !$new_width) {
    $new_width = $width * ( $new_height / $height );
}
elseif(!$new_width && !$new_height) {
    $new_width = $width;
    $new_height = $height;
}

// create a new true color image
$canvas = imagecreatetruecolor( $new_width, $new_height );

if( $zoom_crop ) {

    $src_x = $src_y = 0;
    $src_w = $width;
    $src_h = $height;

    $cmp_x = $width  / $new_width;
    $cmp_y = $height / $new_height;

    // calculate x or y coordinate and width or height of source

    if ( $cmp_x > $cmp_y ) {

        $src_w = round( ( $width / $cmp_x * $cmp_y ) );
        $src_x = round( ( $width - ( $width / $cmp_x * $cmp_y ) ) / 2 );

    }
    elseif ( $cmp_y > $cmp_x ) {

        $src_h = round( ( $height / $cmp_y * $cmp_x ) );
        $src_y = round( ( $height - ( $height / $cmp_y * $cmp_x ) ) / 2 );

    }
    
    imagecopyresampled( $canvas, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h );

}
else {

    // copy and resize part of an image with resampling
    imagecopyresampled( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

}

// output image to browser based on mime type
show_image( $mime_type, $canvas, $quality, $cache_dir );

// remove image from memory
imagedestroy( $canvas );
	

// -----------------

function show_image ( $mime_type, $image_resized, $quality, $cache_dir ) {

	// check to see if we can write to the cache directory
	$is_writable = 0;
	$cache_file_name = $cache_dir . '/' . get_cache_file();        	

	if( touch( $cache_file_name ) ) {
		// give 666 permissions so that the developer 
		// can overwrite web server user
		chmod( $cache_file_name, 0666 );
		$is_writable = 1;
	}
	else {
		$cache_file_name = NULL;
		header( 'Content-type: ' . $mime_type );
	}
	
	if( stristr( $mime_type, 'gif' ) ) {
		imagegif( $image_resized, $cache_file_name );
	}
	elseif( stristr( $mime_type, 'jpeg' ) ) {
		imagejpeg( $image_resized, $cache_file_name, $quality );
	}
	elseif( stristr( $mime_type, 'png' ) ) {
		imagepng( $image_resized, $cache_file_name, ceil( $quality / 10 ) );
	}
	if( $is_writable ) { show_cache_file( $cache_dir, $mime_type ); }
	exit;

}

function open_image ( $mime_type, $src ) {

	if( stristr( $mime_type, 'gif' ) ) {
		$image = imagecreatefromgif( $src );
	}
	elseif( stristr( $mime_type, 'jpeg' ) ) {
		@ini_set('gd.jpeg_ignore_warning', 1);
		$image = imagecreatefromjpeg( $src );
	}
	elseif( stristr( $mime_type, 'png' ) ) {
		$image = imagecreatefrompng( $src );
	}
	return $image;

}

function mime_type($file) {

	if (stristr(PHP_OS, 'WIN')) { 
		$os = 'WIN';
	} else { 
		$os = PHP_OS;
	}

	$mime_type = '';

	if (function_exists('mime_content_type')) {
		$mime_type = mime_content_type($file);
	}
	
	// use PECL fileinfo to determine mime type
	if (!valid_src_mime_type($mime_type)) {
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mime_type = finfo_file($finfo, $file);
			finfo_close($finfo);
		}
	}

	// try to determine mime type by using unix file command
	// this should not be executed on windows
        if (!valid_src_mime_type($mime_type) && $os != "WIN") {
		if (preg_match("/FREEBSD|LINUX/", $os)) {
			$mime_type = trim(@shell_exec('file -bi "' . $file . '"'));
		}
	}

	// use file's extension to determine mime type
	if (!valid_src_mime_type($mime_type)) {

		// set defaults
		$mime_type = 'image/png';
		// file details
		$fileDetails = pathinfo($file);
		$ext = strtolower($fileDetails["extension"]);
		// mime types
		$types = array(
 			'jpg'  => 'image/jpeg',
 			'jpeg' => 'image/jpeg',
 			'png'  => 'image/png',
 			'gif'  => 'image/gif'
 		);
		
		if (strlen($ext) && strlen($types[$ext])) {
			$mime_type = $types[$ext];
		}
		
	}
	
	return $mime_type;

}

function valid_src_mime_type ( $mime_type ) {

	if( preg_match( "/jpg|jpeg|gif|png/i", $mime_type ) ) { return 1; }
	return 0;

}

function check_cache ( $cache_dir, $mime_type ) {

	// make sure cache dir exists
	if( !file_exists( $cache_dir ) ) {
		// give 777 permissions so that developer can overwrite
		// files created by web server user
		mkdir( $cache_dir );
		chmod( $cache_dir, 0777 );
	}
	show_cache_file( $cache_dir, $mime_type );

}

function show_cache_file ( $cache_dir, $mime_type ) {

	$cache_file = get_cache_file();
	if( file_exists( $cache_dir . '/' . $cache_file ) ) {
    
		// check for updates
		$if_modified_since = preg_replace( '/;.*$/', '', $_SERVER[ "HTTP_IF_MODIFIED_SINCE" ] );
		$gmdate_mod = gmdate( 'D, d M Y H:i:s', filemtime( $cache_dir . '/' . $cache_file ) );
		if( strstr( $gmdate_mod, 'GMT' ) ) {
			$gmdate_mod .= " GMT";
		}
		if ( $if_modified_since == $gmdate_mod ) {
			header( "HTTP/1.1 304 Not Modified" );
			exit;
		}
		
		// send headers then display image
		header( "Content-Type: " . $mime_type );
		header( "Last-Modified: " . gmdate( 'D, d M Y H:i:s', filemtime( $cache_dir . '/' . $cache_file ) . " GMT" ) );
		header( "Content-Length: " . filesize( $cache_dir . '/' . $cache_file ) );
		header( "Cache-Control: max-age=9999, must-revalidate" );
		header( "Expires: " . gmdate( "D, d M Y H:i:s", time() + 9999 ) . "GMT" ); 
		readfile( $cache_dir . '/' . $cache_file );
		exit;

	}
    
}

function get_cache_file () {
    global $src;

	static $cache_file;
	if(!$cache_file) {
		$frags = split( "\.", $src );
		$ext = strtolower( $frags[ count( $frags ) - 1 ] );
		if(!valid_extension($ext)) { $ext = 'jpg'; }
		$cachename = $src . $_REQUEST['w'] . $_REQUEST['h'] . $_REQUEST['zc'] . $_REQUEST['q'];
		$cache_file = md5( $cachename ) . '.' . $ext;
	}
	return $cache_file;

}

function valid_extension ($ext) {

	if( preg_match( "/jpg|jpeg|png|gif/i", $ext ) ) return 1;
	return 0;

}

function clean_source ( $src ) {
    global $allow_remote_thumbnailing;

    if (is_base64_encoded($src)) {
        $src = base64_decode($src);
    }

	// don't allow off site src to be specified via http/https/ftp
	if( !$allow_remote_thumbnailing && (preg_match( "/^((ht|f)tp(s|):\/\/)/i", $src)) ) {
		die( "Improper src specified:" . $src );
	}

	//$src = preg_replace( "/(?:^\/+|\.{2,}\/+?)/", "", $src );
	//$src = preg_replace( '/^\w+:\/\/[^\/]+/', '', $src );

	// don't allow users the ability to use '../' 
	// in order to gain access to files below document root

	// src should be specified relative to document root like:
	// src=images/img.jpg or src=/images/img.jpg
	// not like:
	// src=../images/img.jpg
	$src = preg_replace( "/\.\.+\//", "", $src );

	return $src;

}

function get_document_root ($src) {
    global $allow_remote_thumbnailing, $base_folder;

	// If we allow offsite thumbnailing, check if the address starts with http/https/ftp
	if( $allow_remote_thumbnailing && (preg_match( "/^((ht|f)tp(s|):\/\/)/i", $src)) ) {
		return "";
	}    
    
	if( @file_exists( $_SERVER['DOCUMENT_ROOT'] . '/' . $src ) ) {
		return $_SERVER['DOCUMENT_ROOT']."/";
	}
	// the relative paths below are useful if timthumb is moved outside of document root
	// PIVOTX change: added '../../images/', since this is the usual location for images under PivotX
	$paths = array( '../../'.$base_folder, '..', '../..', '../../..', '../../../..' );
	foreach( $paths as $path ) {
		if( @file_exists( $path . '/' . $src ) ) {
			return $path."/";
		}
	}

}


/**
 * Check if a given string is base64 encoded.
 *
 * @param string $str
 */
function is_base64_encoded($str) {   
    return (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $str));
}


?>
