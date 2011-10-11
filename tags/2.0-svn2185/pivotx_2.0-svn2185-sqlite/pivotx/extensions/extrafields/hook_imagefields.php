<?php
// - Extension: Extra Image field
// - Version: 0.2
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=extraimagefield
// - Description: An example for adding extra fields to the Entries and Pages.
// - Date: 2009-09-13

$this->addHook(
    'in_pivotx_template',
    'entry-introduction-before',
    array('callback' => 'extraImageFieldEntryIntroduction' )
    );


/**
 * Callback function for our hook..
 */ 
function extraImageFieldEntryIntroduction($entry) {
    
    // print("<pre>\n"); print_r($entry); print("\n</pre>\n");
    
    $output = <<< EOM
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>
            <tr>
            <td colspan="3"><hr noshade="noshade" size="1" /></td></tr>
            
            <tr>
                <td width="150">
                    <label><strong>Image:</strong></label>
                </td>
                <td width="400">
                    <input id="extrafield-image" name="extrafields[image]" value="%image%" type="text" style="width: 400px;"/>
                </td>
                <td width="100" class="buttons_small">
                    <a href="javascript:;" onclick="openUploadWindow('%label1%', $('#extrafield-image'), 'gif,jpg,png');">
                        <img src='pics/page_lightning.png' alt='' /> %label2%
                    </a>                                  
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Description:</strong></label>
                </td>
                <td colspan="2">
                    <input id="extrafield-image-description" name="extrafields[image_description]" value="%image_description%" type="text" />
                </td>
            </tr>
        
        </tbody>
    </table>
EOM;

    // Substitute some labels..
    $output = str_replace("%label1%", __("Upload an image"), $output);
    $output = str_replace("%label2%", __("Upload"), $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($entry as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($entry['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;
    
}



$this->addHook(
    'in_pivotx_template',
    'page-introduction-before',
    array('callback' => 'extraImageFieldPageIntroduction' )
    );


/**
 * Callback function for our hook..
 */ 
function extraImageFieldPageIntroduction($page) {
    
    // print("<pre>\n"); print_r($entry); print("\n</pre>\n");
    
    $output = <<< EOM
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>
            <tr>
            <td colspan="3"><hr noshade="noshade" size="1" /></td></tr>
            
            <tr>
                <td width="150">
                    <label><strong>Image:</strong></label>
                </td>
                <td width="400">
                    <input id="extrafield-image" name="extrafields[image]" value="%image%" type="text" style="width: 400px;"/>
                </td>
                <td width="100" class="buttons_small">
                    <a href="javascript:;" onclick="openUploadWindow('%label1%', $('#extrafield-image'), 'gif,jpg,png');">
                        <img src='pics/page_lightning.png' alt='' /> %label2%
                    </a>                                  
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Description:</strong></label>
                </td>
                <td colspan="2">
                    <input id="extrafield-image-description" name="extrafields[image_description]" value="%image_description%" type="text" />
                </td>
            </tr>
        
        </tbody>
    </table>
EOM;

    // Substitute some labels..
    $output = str_replace("%label1%", __("Upload an image"), $output);
    $output = str_replace("%label2%", __("Upload"), $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($page as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($page['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;
    
}


?>
