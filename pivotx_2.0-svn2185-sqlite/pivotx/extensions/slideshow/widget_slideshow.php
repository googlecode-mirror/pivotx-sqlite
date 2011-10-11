<?php
// - Extension: Slideshow
// - Version: 0.5 - development
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=slideshow
// - Description: a slideshow widget
// - Date: 2008-02-24
// - Identifier: slideshow



global $slideshow_config;

$slideshow_config = array(
    'slideshow_width' => "250",
    'slideshow_height' => "180",
    'slideshow_folder' => "slideshow",
    'slideshow_limit' => "15",
    'slideshow_orderby' => "date_desc",
    'slideshow_thickbox' => 1
);



/**
 * Adds the hook for slideshowAdmin()
 *
 * @see slideshowAdmin()
 */
$this->addHook(
    'configuration_add',
    'slideshow',
    array("slideshowAdmin", "Slideshow")
);



/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_slideshow()
 */
$this->addHook(
    'widget',
    'slideshow',
    "smarty_slideshow"
);



/**
 * Add some javascript to the header..
 */
$this->addHook(
    'after_parse',
    'insert_before_close_head',
    "
    <!-- Includes for slideshow extension -->
    <script type='text/javascript' src='[[pivotx_dir]]extensions/slideshow/jquery.slideviewer.1.1.js'></script>
    <script type='text/javascript' src='[[pivotx_dir]]extensions/slideshow/jquery.easing.1.2.js'></script>
    <script type='text/javascript'>
        var slideshow_pathToImage = '[[pivotx_dir]]extensions/slideshow/spinner.gif';
    </script>
    <link href='[[pivotx_dir]]extensions/slideshow/slideshow.css' rel='stylesheet' type='text/css' />\n"
);

// If the hook for the jQuery include in the header was not yet installed, do so now..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');


// Register 'slideshow' as a smarty tag.
$PIVOTX['template']->register_function('slideshow', 'smarty_slideshow');

/**
 * Output a slideshow feed
 *
 * @param array $params
 * @return string
 */
function smarty_slideshow($params) {
    global $PIVOTX, $slideshow_config;


    $width = get_default($PIVOTX['config']->get('slideshow_width'), $slideshow_config['slideshow_width']);
    $height = get_default($PIVOTX['config']->get('slideshow_height'), $slideshow_config['slideshow_height']);
    $foldername = get_default($PIVOTX['config']->get('slideshow_folder'), $slideshow_config['slideshow_folder']);
    $limit = get_default($PIVOTX['config']->get('slideshow_limit'), $slideshow_config['slideshow_limit']);
    $order = get_default($PIVOTX['config']->get('slideshow_orderby'), $slideshow_config['slideshow_orderby']);
    $thickbox = $PIVOTX['config']->get('slideshow_thickbox');

    $imagefolder = fixPathSlash($PIVOTX['paths']['upload_base_path']."$foldername");
    $imagelink = fixPathSlash($PIVOTX['paths']['upload_base_url']."$foldername");
    $ok_extensions = explode(",", "jpg,jpeg,png,gif");

    if (!file_exists($imagefolder) || !is_dir($imagefolder)) {
        debug("Image folder $imagefolder does not exist.");
        echo("Image folder $imagefolder does not exist.");
        return "";
    } else if (!is_readable($imagefolder)) {
        debug("Image folder $imagefolder is not readable.");
        echo("Image folder $imagefolder is not readable.");
        return "";
    }

    $images = array();

    $key = "";

    $dir = dir($imagefolder);
    while (false !== ($entry = $dir->read())) {
        if ( in_array(getextension($entry), $ok_extensions) ) {

            if ($order=='date_asc' || $order=='date_desc') {
                $key = filemtime($imagefolder.$entry).rand(10000,99999);
                //echo "[$key - $imagefolder$entry] <br />";
                $images[$key] = $entry;
            } else {
                $images[] = $entry;
            }


        }
    }
    $dir->close();


    if ($order=='date_asc') {
        ksort($images);
    } else if ($order=='date_desc') {
        ksort($images);
        $images = array_reverse($images);
    } else if ($order=='alphabet') {
        natcasesort($images);
    } else {
        shuffle($images);
    }

    // Cut it to the desired length..
    $images = array_slice($images, 0, $limit);



    $output = "\n<div class='widget-lg'>\n<div id=\"pivotx-slideshow\" class=\"svw\">\n<ul>\n";

    foreach ($images as $image) {

        $line = "<li>\n";
        if ($thickbox==1) {
            $line .= sprintf("<a href=\"%s%s\" class=\"thickbox\" rel=\"slideshow\" title=\"%s\">\n",
                $imagelink, rawurlencode($image), $image);
        }
        $line .= sprintf("<img src=\"%sincludes/timthumb.php?src=%s/%s&amp;w=%s&amp;h=%s&amp;zc=1\" " .
                "alt=\"%s\" width=\"%s\" height=\"%s\" />\n",
            $PIVOTX['paths']['pivotx_url'], $foldername, rawurlencode($image), $width, $height, $entry, $width, $height);
        if ($thickbox==1) {
            $line .= "</a>";
        }
        $line .= "</li>\n";
        $output .= $line;

    }

    $output .= "</ul>\n</div>\n</div>\n";



    return $output;


}



/**
 * The configuration screen for slideshow
 *
 * @param unknown_type $form_html
 */
function slideshowAdmin(&$form_html) {
    global $PIVOTX, $slideshow_config;

    $form = $PIVOTX['extensions']->getAdminForm('slideshow');

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_folder',
        'label' => "Folder name",
        'value' => '',
        'error' => 'That\'s not a proper folder name!',
        'text' => "The name of the folder in where the images are that Slideshow should use. This should be a folder inside your <tt>images</tt> folder. So if you input <tt>slideshow</tt>, the slideshow will look in the <tt>/images/slideshow/</tt> folder. Don't start or finish with a slash.",
        'size' => 32,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=32'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_width',
        'label' => "Width",
        'value' => '',
        'error' => 'Error!',
        'text' => "",
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=500'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_height',
        'label' => "Height",
        'value' => '',
        'error' => 'Error!',
        'text' => "The width and height of the thumbnails in the widget. The borders are added to this, so the total dimensions of the widget will be 6 pixels wider and taller.",
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'string|min=1|max=500'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_limit',
        'label' => "Limit",
        'value' => '',
        'error' => 'Error!',
        'text' => "This limits the number of items that are shown. If you set it too high, it will take longer to load your site.",
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'string|min=1|max=500'
    ));



    $form->add( array(
        'type' => 'select',
        'name' => 'slideshow_orderby',
        'label' => "Order by",
        'value' => '',
        'firstoption' => __('Select'),
        'options' => array(
           'date_asc' => "Date ascending",
           'date_desc' => "Date descending",
           'alphabet' => "Alphabet",
           'random' => "Random"
           ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => "Select the order in which the images are shown."
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_thickbox',
        'label' => "Use Thickbox",
        'text' => "Yes, open the images in a thickbox, when clicked."
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['slideshow'] = $PIVOTX['extensions']->getAdminFormHtml($form, $slideshow_config);



}


?>
