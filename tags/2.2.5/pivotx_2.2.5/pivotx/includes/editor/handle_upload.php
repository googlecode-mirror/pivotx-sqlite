<?php


// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: handle_upload.php 2347 2010-01-19 09:01:43Z pivotlog $
//
// ---------------------------------------------------------------------------


require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');

// Include some other files
require_once($pivotx_path.'lib.php');
require_once($pivotx_path.'modules/module_i18n.php');
require_once($pivotx_path.'modules/module_lang.php');

initializePivotX(false);

//debug('handle upload');
//debug_printr($_FILES);
//debug_printr($_POST);

// Make sure the person requesting this page is logged in:
$PIVOTX['session']->minLevel(1);

if (isset($_FILES) && count($_FILES)>0 ) {

    require_once($pivotx_path.'includes/fileupload-class.php');
    $my_uploader = new uploader($PIVOTX['languages']->getCode());

    // OPTIONAL: set the max filesize of uploadable files in bytes
    $my_uploader->max_filesize($PIVOTX['config']->get('upload_max_filesize'));

    // Make sure the requested folder exists..
    $path = makeUploadFolder($_FILES['userfile']['name']);

    // Figure out what the final filename is.. is it 'filename.jpg' or something
    // like 'f/filename.jpg' or '2007-10/filename.jpg.
    if (strpos($PIVOTX['paths']['upload_path'], "%")>0) {
        $addedpath = basename($path)."/";
    } else {
        $addedpath = "";
    }

    // UPLOAD the file
    if ($my_uploader->upload('userfile', $PIVOTX['config']->get('upload_accept'), $PIVOTX['config']->get('upload_extension'))) {
        $success = $my_uploader->save_file($path, $PIVOTX['config']->get('upload_save_mode'));
    }
}

if ($success) {
    $dbg_txt = "Uploaded " . $my_uploader->file['name'];
    require_once($PIVOTX['paths']['pivotx_path'].'modules/module_imagefunctions.php');
    if (auto_thumbnail($my_uploader->file['name'], $path)) {
        $dbg_txt .= " and created thumbnail";
    }
    debug($dbg_txt);
    $msg = $addedpath . $my_uploader->file['name'];

} else if($my_uploader->errors) {

    debug("upload failed");
    debug_printr($my_uploader->errors);

    $msg = "0";

}


echo $msg."\n";

?>
