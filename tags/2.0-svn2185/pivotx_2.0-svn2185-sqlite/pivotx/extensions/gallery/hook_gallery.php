<?php
// - Extension: Gallery
// - Version: 0.7
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=gallery
// - Description: Add simple galleries to your Entries or Pages
// - Date: 2009-10-21


/**
 * Example for use in templates:
 *
 *  <div class="gallery">
 *  [[gallery popup="thickbox"]]
 *    <a href='%imageurl%%filename%' class="thickbox" title="%title%" rel="gallery-%uid%" >
 *    <img src="%pivotxurl%includes/timthumb.php?src=%filename%&amp;w=106&amp;h=80&amp;zc=1" alt="%alttext%" />
 *    </a>
 *  [[/gallery]]
 *  </div>
 *
 * You can style the output in your CSS. A very basic example would be:
 * 
 * div.gallery { margin: 0; padding: 0; }
 * div.gallery img { margin: 2px; padding: 0; } 
 *
 * If you have enabled the fancybox extension and want to use it instead, 
 * replace the two "thickbox" strings with "fancybox" in the example above.
 *
 * ----
 * 
 * There is also a gallery_image template tag so you can use just one of the 
 * images in the gallery as a preview in the entry introduction for example.
 * Typical usage is (if you want a thumbnail):
 *
 * <img src="[[pivotx_dir]]includes/timthumb.php?src=[[gallery_image]]&amp;w=106&amp;h=80&amp;zc=1" />
 *
 * Parameters for gallery_image is "number" - the position in the gallery 
 * starting from 0 (default) - and "attr" - the wanted attribute from the selecyed
 * image; "src" (default), "title" or "alttext". In other words, 
 * "[[gallery_image]]" is equivalent to "[[gallery_image number=0 attr=src]]".
 * 
 */

$this->addHook(
    'in_pivotx_template',
    'entry-keywords-before',
    array('callback' => 'galleryFieldExtension' )
    );


$this->addHook(
    'in_pivotx_template',
    'page-keywords-before',
    array('callback' => 'galleryFieldExtension' )
    );

/**
 * Callback function for our hook..
 */ 
function galleryFieldExtension($content) {
    
    // print("<pre>\n"); print_r($entry); print("\n</pre>\n");
    
    $output = <<< EOM
    <script src="extensions/gallery/gallery.js" type="text/javascript"></script>
    <style>
        
        #galleryrow1 {
            width: 390px;
        }
        
        #galleryrow3 {
            width: 106px;
        }
        
        #galleryrow2, #galleryrow4 {
            display: none;
        }
    
        #gallerythumbnails {
            border: 1px solid #DDD;
            background-color: F2F2F2;
            padding: 4px;
            width: 380px;
            min-height: 100px;
        }
        
        #gallerywastebin {
            border: 1px solid #DDD;
            background-color: F2F2F2;
            margin-top: 4px;
            padding: 2px;
            height: 74px;
            background-image: url(pics/delete.png);
            background-position: bottom right;
            background-repeat: no-repeat;
            width: 100px;
        }        
        
        #gallerythumbnails img, #gallerywastebin img {
            border: 1px solid #888;
            margin: 0px;
            list-style-image: none;
            width: 70px;
            height: 70px;
            margin: 2px;
            float: left;            
        }
        
        
        .ghost {
            border: 1px dashed #BBB !important;
            background-color: #EEE !important;
            width: 70px;
            height: 70px;
            background-image: url(none);
        }
        
        .ghost img {
            display: none;
            visibility: hidden;
        }
        
    </style>
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>
            
            <tr>
                <td colspan="3"><hr noshade="noshade" size="1" /></td>
            </tr>
            
            
            <tr>
                <td width="150">
                    <label><strong>%title%:</strong></label>
                </td>
                <td width="400">

                    <div id='galleryrow1'>
                        <a href='javascript:showGallery()'>%edit%</a>
                    </div>
                    
                    <div id='galleryrow2'>
                        <div id="gallerythumbnails" class='gallerysortable'></div>
                        
                        <textarea id="extrafield-galleryimagelist" name="extrafields[galleryimagelist]" style="width: 400px; display: none; visibility: hidden"/>%galleryimagelist%</textarea>
                    </div>
                    
                </td>
                <td width="100" class="buttons_small">
        
                    <div id='galleryrow3'>
                       &nbsp;
                    </div>
        
                    <div id='galleryrow4'>
                        <a href="javascript:;" onclick="openGalleryUploadWindow('%label1%', $('#extrafield-image'), 'gif,jpg,png');">
                            <img src='pics/page_lightning.png' alt='' /> %label2%
                        </a>

                        <div class='cleaner'>&nbsp;</div>
                        <div id="gallerywastebin" class='gallerysortable'></div>
                    </div>    
                    
                </td>
            </tr>
        </tbody>
    </table>
EOM;

    // Substitute some labels..
    $output = str_replace("%title%", __("Gallery"), $output);
    $output = str_replace("%edit%", __("Edit Gallery"), $output);
    $output = str_replace("%label1%", __("Add an image"), $output);
    $output = str_replace("%label2%", __("Add"), $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($content as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($content['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;
    
}



// Register 'gallery' as a smarty block tag.
$PIVOTX['template']->register_block('gallery', 'smarty_gallery');

function smarty_gallery($params, $text, &$smarty) {
    global $PIVOTX;

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }

    $params = clean_params($params);

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = get_default($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);

    $output = "";
    
    if (!empty($gallery)) {
        $gallery = explode("\n", $gallery);
        foreach($gallery as $image) {
            $image = trim($image);
            list($image, $title, $alttext) = explode('###',$image);

            $nicefilename = formatFilename($image);
            if (empty($alttext)) {
                $alttext = $nicefilename;
            }
            if (empty($title)) {
                $title = $nicefilename;
            }
            
            if (!empty($image)) {
                $this_output = $text;
                $this_output = str_replace('%title%', $title, $this_output);
                $this_output = str_replace('%alttext%', $alttext, $this_output);
                $this_output = str_replace('%filename%', $image, $this_output);
                $this_output = str_replace('%nicefilename%', $nicefilename, $this_output);
                $this_output = str_replace('%uid%', $entry['uid'], $this_output);
                $this_output = str_replace('%imageurl%', $PIVOTX['paths']['upload_base_url'], $this_output);
                $this_output = str_replace('%pivotxurl%', $PIVOTX['paths']['pivotx_url'], $this_output);
                
                $output .= $this_output;
            }
        }
    }
    
    // If a specific popup type is selected execute the callback.
    if (isset($params['popup'])) {
        $callback = $params['popup']."IncludeCallback";
        if (function_exists($callback)) {
            $PIVOTX['extensions']->addHook('after_parse', 'callback', $callback);
        } else {
            debug("There is no function '$callback' - the popups won't work.");
        }
    }

    return entifyAmpersand($output);

}

// Register 'gallery_image' as a smarty tag.
$PIVOTX['template']->register_function('gallery_image', 'smarty_gallery_image');

function smarty_gallery_image($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    $number = get_default($params['number'], 0);
    $attr = get_default($params['attr'], 'src');

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = get_default($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);

    $output = "";
    
    if (!empty($gallery)) {
        $gallery = explode("\n", $gallery);

        $image = trim($gallery[$number]);

        list($image, $title, $alttext) = explode('###',$image);

        if ($attr == 'src') {
            $output = $image;
        } elseif ($attr == 'title') {
            $output = $title;
        } elseif ($attr == 'alttext') {
            $output = $alttext;
        } 

    }
    
    return entifyAmpersand($output);

}


?>
