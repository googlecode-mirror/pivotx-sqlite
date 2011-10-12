<?php
/*
    TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
    http://code.google.com/p/timthumb/

    MIT License: http://www.opensource.org/licenses/mit-license.php

    Parameters
    ---------
    w: width
    h: height
    zc: zoom crop (0 or 1)
    q: quality (default is 75 and max is 100)
    fit: fit inside w * h (0 or 1) 
    nu: no upscaling (value is rgb colour in hex)
    
    HTML example: <img src="[[pivotx_dir]]includes/timthumb.php?src=whatever.jpg&w=150&h=200&zc=1" alt="" />
    
    Remote files: (it's advisable to use base64_encoded URLs, since a lot of
    browsers/firewalls/webservers won't allow 'normal' URL's in the parameter string.
    <img src="[[pivotx_dir]]includes/timthumb.php?src=aHR0cDovL3Bpdm90eC5uZXQvcGl2b3QvdGVtcGxhdGVzL3Bpdm90eC9pbWFnZXMvaGVhZGVyLmpwZw==&w=300&h=200&zc=1" alt="" />
    <img src="[[pivotx_dir]]includes/timthumb.php?src=http://example.org/images/header.jpg&w=300&h=200&zc=1" alt="" />
      
*/

/*
$sizeLimits = array(
    "100x100",
    "150x150",
);
    
error_reporting(E_ALL);
ini_set("display_errors", 1); 
*/

include_once "../lib.php";

// Change this, if you've changed the default upload folder for images in the PivotX configuration.    
$upload_folder = 'images';

// Set base folder taking multisite into account
$multisite = new MultiSite();
if ($multisite->isActive()) {
    $sites_path = $multisite->getPath();
    $base_folder = 'pivotx/' . $sites_path . $upload_folder;
} else {
    $sites_path = '';
    $base_folder = $upload_folder;
}
 

// Do NOT allow remote thumbnailing by default. Only enable this, if you know what you're doing. 
$allow_remote_all = false;

// check to see if GD function exist
if(!function_exists('imagecreatetruecolor')) {
    displayError('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
}

define ('CACHE_SIZE', 250);        // number of files to store before clearing cache
define ('CACHE_CLEAR', 5);        // maximum number of files to delete on each cache clear
define ('VERSION', '1.12');        // version number (to force a cache refresh

if (function_exists('imagefilter') && defined('IMG_FILTER_NEGATE')) {
	$imageFilters = array(
		"1" => array(IMG_FILTER_NEGATE, 0),
		"2" => array(IMG_FILTER_GRAYSCALE, 0),
		"3" => array(IMG_FILTER_BRIGHTNESS, 1),
		"4" => array(IMG_FILTER_CONTRAST, 1),
		"5" => array(IMG_FILTER_COLORIZE, 4),
		"6" => array(IMG_FILTER_EDGEDETECT, 0),
		"7" => array(IMG_FILTER_EMBOSS, 0),
		"8" => array(IMG_FILTER_GAUSSIAN_BLUR, 0),
		"9" => array(IMG_FILTER_SELECTIVE_BLUR, 0),
		"10" => array(IMG_FILTER_MEAN_REMOVAL, 0),
		"11" => array(IMG_FILTER_SMOOTH, 0),
	);
}

$src = '';

if (isset($_GET['rsrc']) && ($_GET['rsrc'] != '')) {
    $src = safeString($_GET['rsrc'],true,'./');
} else {
    // sort out image source
    $src = get_request("src", "");
}
if($src == '' || strlen($src) <= 3) {
    displayError ('no image specified');
}

// clean params before use
$src = cleanSource($src);
// last modified time (for caching)
$lastModified = filemtime($src);

// get properties
$new_width         = preg_replace("/[^0-9]+/", "", get_request("w", 0));
$new_height     = preg_replace("/[^0-9]+/", "", get_request("h", 0));
$zoom_crop         = preg_replace("/[^0-9]+/", "", get_request("zc", 1));
$quality         = preg_replace("/[^0-9]+/", "", get_request("q", 80));
$fit         = preg_replace("/[^0-9]+/", "", get_request("fit", ""));
$filters        = get_request("f", "");
$no_upscale  = preg_replace("/[^0-9a-fA-F]+/", "", get_request("nu", ""));

if ($new_width == 0 && $new_height == 0) {
    $new_width = 100;
    $new_height = 100;
}

// set path to cache directory (default is ./cache)
// this can be changed to a different location
$cache_dir = '../' . $sites_path . 'db/cache/thumbnails/';

// get mime type of src
$mime_type = mime_type($src);

// check to see if this image is in the cache already
check_cache ($cache_dir, $mime_type);

// if not in cache then clear some space and generate a new file
// Note: no cleanup here, we let PivotX scheduled cleanup handle this..
// cleanCache();

ini_set('memory_limit', "50M");

// make sure that the src is gif/jpg/png
if(!valid_src_mime_type($mime_type)) {
    displayError("Invalid src mime type: " .$mime_type);
}

// Added for PivotX: If the source-file is the same size as the requested 
// image, just return the original. 
if(strlen($src) && file_exists($src)) {
    list($ow,$oh) = getimagesize($src);

    if (($new_width == $ow) && ($new_height == $oh)) {
        echo file_get_contents($src);
        exit();
    }
}

if(strlen($src) && file_exists($src)) {

    // open the existing image
    $image = open_image($mime_type, $src);
    if($image === false) {
        displayError('Unable to open image : ' . $src);
    }

    // Get original width and height
    $width = imagesx($image);
    $height = imagesy($image);
    
    // generate new w/h if not provided
    if( $new_width && !$new_height ) {
        
        $new_height = $height * ( $new_width / $width );
        
    } elseif($new_height && !$new_width) {
        
        $new_width = $width * ( $new_height / $height );
        
    } elseif(!$new_width && !$new_height) {
        
        $new_width = $width;
        $new_height = $height;
        
    }
    
    
    if ($fit) {
    	if ($new_width < 1) {
    		$new_width = $width;
    	}
    	if ($new_height < 1) {
    		$new_height = $height;
    	}
    
    	$s = 1;
    	if (($width > $new_width) &&
    	    ($height > $new_height)) {
    		$s_w = $new_width / $width;
    		$s_h = $new_height / $height;
    		if ($s_w < $s_h) {
    			$s = $s_w;
    		}
    		else {
    			$s = $s_h;
    		}
    	}
    	else if ($width > $new_width) {
    		$s = $new_width / $width;
    	}
    	else if ($height > $new_height) {
    		$s = $new_height / $height;
    	}
    
    	$new_width  = (int) floor($width * $s);
    	$new_height = (int) floor($height * $s);
    	
    	$zoom_crop  = 0;
    }    
    
    // create a new true color image
    $canvas = imagecreatetruecolor( $new_width, $new_height );
    imagealphablending($canvas, false);
    // Create a new transparent color for image
    $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    // Completely fill the background of the new image with allocated color.
    imagefill($canvas, 0, 0, $color);
    // Restore transparency blending
    imagesavealpha($canvas, true);


    if (($no_upscale != '') && (($width < $new_width) || ($height < $new_height))) {
        list($r,$g,$b,$a) = decode_hex_rgb($no_upscale);

        $color = imagecolorallocatealpha($canvas, $r,$g,$b,$a);
        imagefill($canvas, 0,0, $color);

        $dst_x = floor(($new_width - $width) / 2);
        $dst_y = floor(($new_height - $height) / 2);
        if ($dst_x < 0) {
            $dst_x = 0;
        }
        if ($dst_y < 0) {
            $dst_y = 0;
        }

        $src_w = $width;
        $src_h = $height;

        if ($src_w > $new_width) {
            $src_w = $new_width;
        }
        if ($src_h > $new_height) {
            $src_h = $new_height;
        }

        imagecopy($canvas, $image, $dst_x,$dst_y, 0,0,$src_w,$src_h);
    }
    else if( $zoom_crop ) {

        $src_x = $src_y = 0;
        $src_w = $width;
        $src_h = $height;

        $cmp_x = $width  / $new_width;
        $cmp_y = $height / $new_height;

        // calculate x or y coordinate and width or height of source

        if ( $cmp_x > $cmp_y ) {

            $src_w = round( ( $width / $cmp_x * $cmp_y ) );
            $src_x = round( ( $width - ( $width / $cmp_x * $cmp_y ) ) / 2 );

        } elseif ( $cmp_y > $cmp_x ) {

            $src_h = round( ( $height / $cmp_y * $cmp_x ) );
            $src_y = round( ( $height - ( $height / $cmp_y * $cmp_x ) ) / 2 );

        }
        
        imagecopyresampled( $canvas, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h );

    } else {

        // copy and resize part of an image with resampling
        imagecopyresampled( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

    }
    
    if ($filters != '' && function_exists('imagefilter') && defined('IMG_FILTER_NEGATE')) {
        // apply filters to image
        $filterList = explode("|", $filters);
        foreach($filterList as $fl) {
            $filterSettings = explode(",", $fl);
            if(isset($imageFilters[$filterSettings[0]])) {
            
                for($i = 0; $i < 4; $i ++) {
                    if(!isset($filterSettings[$i])) {
                        $filterSettings[$i] = null;
                    }
                }
                
                switch($imageFilters[$filterSettings[0]][1]) {
                
                    case 1:
                    
                        imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1]);
                        break;
                    
                    case 2:
                    
                        imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2]);
                        break;
                    
                    case 3:
                    
                        imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3]);
                        break;
                    
                    default:
                    
                        imagefilter($canvas, $imageFilters[$filterSettings[0]][0]);
                        break;
                        
                }
            }
        }
    }
    
    // output image to browser based on mime type
    show_image($mime_type, $canvas, $cache_dir);
    
    // remove image from memory
    imagedestroy($canvas);
    
} else {

    if(strlen($src)) {
        displayError("image " . $src . " not found");
    } else {
        displayError("no source specified");
    }
    
}

/**
 * 
 */
function show_image($mime_type, $image_resized, $cache_dir) {

    global $quality;

    // check to see if we can write to the cache directory
    $is_writable = 0;
    $cache_file_name = $cache_dir . '/' . get_cache_file();

    if (touch($cache_file_name)) {
        
        // give 666 permissions so that the developer 
        // can overwrite web server user
        chmod ($cache_file_name, 0666);
        $is_writable = 1;
        
    } else {
        
        $cache_file_name = NULL;
        header ('Content-type: ' . $mime_type);
        
    }

    switch ($mime_type) {
    
        case 'image/jpeg':
            imagejpeg($image_resized, $cache_file_name, $quality);
            break;
        
        default :
            $quality = floor ($quality * 0.09);
            imagepng($image_resized, $cache_file_name, $quality);
            
    }
    
    if ($is_writable) {
        show_cache_file ($cache_dir, $mime_type);
    }

    imagedestroy ($image_resized);
    
    displayError ("error showing image");

}

/**
 * 
 */
function get_request( $property, $default = 0 ) {
    
    if( isset($_REQUEST[$property]) ) {
    
        return $_REQUEST[$property];
        
    } else {
    
        return $default;
        
    }
    
}

/**
 * 
 */
function open_image($mime_type, $src) {

	$mime_type = strtolower($mime_type);
	
    if (stristr ($mime_type, 'gif')) {
    
        $image = imagecreatefromgif($src);
        
    } elseif (stristr($mime_type, 'jpeg')) {
    
        @ini_set ('gd.jpeg_ignore_warning', 1);
        $image = imagecreatefromjpeg($src);
        
    } elseif (stristr ($mime_type, 'png')) {
    
        $image = imagecreatefrompng($src);
        
    }
    
    return $image;

}

/**
 * clean out old files from the cache
 * you can change the number of files to store and to delete per loop in the defines at the top of the code
 */
function cleanCache() {

    $files = glob("cache/*", GLOB_BRACE);
    
    if (count($files) > 0) {
    
        $yesterday = time() - (24 * 60 * 60);
        
        usort($files, 'filemtime_compare');
        $i = 0;
        
        if (count($files) > CACHE_SIZE) {
            
            foreach ($files as $file) {
                
                $i ++;
                
                if ($i >= CACHE_CLEAR) {
                    return;
                }
                
                if (@filemtime($file) > $yesterday) {
                    return;
                }
                
				if (file_exists($file)) {
					unlink($file);
				}
                
            }
            
        }
        
    }

}


/**
 * compare the file time of two files
 */
function filemtime_compare($a, $b) {

    return filemtime($a) - filemtime($b);
    
}


/**
 * determine the file mime type
 */
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
			$finfo = @finfo_open(FILEINFO_MIME);
			if ($finfo != '') {
				$mime_type = finfo_file($finfo, $file);
				finfo_close($finfo);
			}
		}
	}

    // try to determine mime type by using unix file command
    // this should not be executed on windows
    // Commented out for PivotX: Adds nothing valuable, does an extra system call, and generates
    // errors on some servers. 
    //if (!valid_src_mime_type($mime_type) && $os != "WIN") {
    //	if (preg_match("/FREEBSD|LINUX/", $os)) {
    //		$mime_type = trim(@shell_exec('file -bi "' . $file . '"'));
    //	}
    //}

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


/**
 * 
 */
function valid_src_mime_type($mime_type) {

    if (preg_match("/jpg|jpeg|gif|png/i", $mime_type)) {
        return true;
    }
    
    return false;

}


/**
 * 
 */
function check_cache ($cache_dir, $mime_type) {

    // make sure cache dir exists
    if (!file_exists($cache_dir)) {
        // give 777 permissions so that developer can overwrite
        // files created by web server user
        mkdir($cache_dir);
        chmod($cache_dir, 0777);
    }

    show_cache_file ($cache_dir, $mime_type);

}


/**
 * 
 */
function show_cache_file ($cache_dir, $mime_type) {

    $cache_file = $cache_dir . '/' . get_cache_file();

    if (file_exists($cache_file)) {
        
        $gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($cache_file));
        
        if(! strstr($gmdate_mod, "GMT")) {
            $gmdate_mod .= " GMT";
        }
        
        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
        
            // check for updates
            $if_modified_since = preg_replace ("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
            
            if ($if_modified_since == $gmdate_mod) {
                header("HTTP/1.1 304 Not Modified");
                die();
            }

        }
        
        $fileSize = filesize ($cache_file);
        
        // send headers then display image
        header ('Content-Type: ' . $mime_type);
        header ('Accept-Ranges: bytes');
        header ('Last-Modified: ' . $gmdate_mod);
        header ('Content-Length: ' . $fileSize);
        header ('Cache-Control: max-age=9999, must-revalidate');
        header ('Expires: ' . $gmdate_mod);
        
        readfile ($cache_file);
        
        die();

    }
    
}


/**
 * 
 */
function get_cache_file() {

    global $lastModified;
    static $cache_file;
    
    if (!$cache_file) {
        $cachename = $_SERVER['QUERY_STRING'] . VERSION . $lastModified;
        $cache_file = md5($cachename) . '.png';
    }
    
    return $cache_file;

}


/**
 * check to if the url is valid or not
 */
function valid_extension ($ext) {

    if (preg_match("/jpg|jpeg|png|gif/i", $ext)) {
        return TRUE;
    } else {
        return FALSE;
    }
    
}


/**
 *
 */
function checkExternal ($src) {
    global $allow_remote_all;

    $allowedSites = array(
        'flickr.com',
        'picasa.com',
        'blogger.com',
        'wordpress.com',
        'img.youtube.com',
        'dummyimage.com',
    );

    if (preg_match ('/http:\/\//', $src) == true) {

        $url_info = parse_url ($src);
	
        if ($allow_remote_all) {
            $isAllowedSite = true;
        } else {
            $isAllowedSite = false;
            foreach ($allowedSites as $site) {
                $site = '/' . addslashes ($site) . '/';
                if (preg_match($site, $url_info['host']) == true) {
                    $isAllowedSite = true;
                }
            }
        }
        
        if ($isAllowedSite) {

            $fileDetails = pathinfo($src);
            $ext = strtolower($fileDetails['extension']);

            $filename = md5($src);
            // Changed for PivotX: Use local db/cache folder
            $local_filepath = '../db/cache/' . $filename . '.' . $ext;

            if (!file_exists($local_filepath)) {

                if (function_exists('curl_init')) {

                    $fh = fopen($local_filepath, 'w');
                    $ch = curl_init($src);

                    curl_setopt($ch, CURLOPT_URL, $src);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
                    curl_setopt($ch, CURLOPT_FILE, $fh);

                    if (curl_exec($ch) === FALSE) {
                        if (file_exists($local_filepath)) {
                            unlink($local_filepath);
                        }
                        displayError('error reading file ' . $src . ' from remote host: ' . curl_error($ch));
                    }

                    curl_close($ch);
                    fclose($fh);

                } else {
            
                    if (!$img = file_get_contents($src)) {
                        displayError('remote file for ' . $src . ' can not be accessed. It is likely that the file permissions are restricted');
                    }
                    
                    if (file_put_contents($local_filepath, $img) == FALSE) {
                        displayError('error writing temporary file');
                    }
                    
                }
                
                if (!file_exists($local_filepath)) {
                    displayError('local file for ' . $src . ' can not be created');
                }
                
            }
            
            $src = $local_filepath;
            
        } else {
        
            displayError('remote host "' . $url_info['host'] . '" not allowed');
            
        }
        
    }
    
    return $src;
    
}


/**
 * tidy up the image source url
 */
function cleanSource($src) {

    if (is_base64_encoded($src)) {
        $src = base64_decode($src);
    }    

	$host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
	$regex = "/^((ht|f)tp(s|):\/\/)(www\.|)" . $host . "/i";
	
	$src = preg_replace ($regex, '', $src);
	$src = htmlentities ($src);
    $src = checkExternal ($src);
    
    // remove slash from start of string
    if (strpos($src, '/') === 0) {
        $src = substr ($src, -(strlen($src) - 1));
    }

    // don't allow users the ability to use '../' 
    // in order to gain access to files below document root
    $src = preg_replace("/\.\.+\//", "", $src);
    
    // get path to image on file system
    $src = get_document_root($src) . '/' . $src;

    return $src;

}


/**
 * 
 */
function get_document_root ($src) {
    global $base_folder;

    // check for unix servers
    if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $src)) {
        return $_SERVER['DOCUMENT_ROOT'];
    }

    // check from script filename (to get all directories to timthumb location)
    $parts = array_diff(explode('/', $_SERVER['SCRIPT_FILENAME']), explode('/', $_SERVER['DOCUMENT_ROOT']));
    $path = $_SERVER['DOCUMENT_ROOT'];
    foreach ($parts as $part) {
        $path .= '/' . $part;
        if (file_exists($path . '/' . $src)) {
            return $path;
        }
    }    
    
    // the relative paths below are useful if timthumb is moved outside of document root
    // specifically if installed in wordpress themes like mimbo pro:
    // /wp-content/themes/mimbopro/scripts/timthumb.php
    $paths = array(
    	"../../".$base_folder,
        ".",
        "..",
        "../..",
        "../../..",
        "../../../..",
        "../../../../.."
    );
    
    foreach ($paths as $path) {
        if(file_exists($path . '/' . $src)) {
            return $path;
        }
    }
    
    // special check for microsoft servers
    if (!isset($_SERVER['DOCUMENT_ROOT'])) {
        $path = str_replace("/", "\\", $_SERVER['ORIG_PATH_INFO']);
        $path = str_replace($path, "", $_SERVER['SCRIPT_FILENAME']);
        
        if (file_exists($path . '/' . $src)) {
            return $path;
        }
    }    
    
    displayError('file not found ' . $src);

}


/**
 * generic error message
 */
function displayError($errorString = '') {

    header('HTTP/1.1 400 Bad Request');
    die($errorString);
    
}

/**
 * Check if a given string is base64 encoded.
 *
 * @param string $str
 */
function is_base64_encoded($str) {   
    return (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $str));
}

/**
 */
function decode_hex_rgb($hex) {
    $r = 0;
    $r = 0;
    $r = 0;
    $r = 0;

    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    }
    else if (strlen($hex) == 6) {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }

    return array($r,$g,$b,$a);
}


?>
