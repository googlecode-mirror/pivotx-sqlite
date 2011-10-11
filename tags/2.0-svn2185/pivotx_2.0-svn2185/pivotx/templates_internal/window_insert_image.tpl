[[ include file="inc_window_header.tpl" ]]

<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/swfupload.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/swfupload.queue.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/fileprogress.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/handlers.js"></script>
[[ literal ]]
<script type="text/javascript">
//<![CDATA[

//We need to submit this to the opener, that is to the editor
function do_submit_f_image(){

    var f_image = trim( $('#f_image').val() );
    var f_image_alt = $('#f_image_alt').val();
    var f_image_align = $('#f_image_align').val();
    var f_target = $('#f_target').val();

    var selected_popup = getValue('f_popup');

    if(selected_popup != 'text') {
        f_text = '(thumbnail)';
    } else {
        f_text = $('#f_text').val();
    }

    
    if ($.browser.safari && $.browser.version<500) {
        // safari 2 can't use the wysiwyg to dynamically insert stuff in the entry...
        var code = '[[image file="'+f_image+'" alt="'+f_image_alt+'" align="'+f_image_align+'" ]]';
        var msg = "Dynamically inserting code does not work properly on Safari 2. Copy the code below, and insert it in your entry manually.";

        prompt(msg, code);
        window.close();
        
        
    } else {

        // If window.opener is unknown, we've opened the image inserter as a Dialog. then 
        /// we can use top.frames[0]
        if (window.opener==null) {
            top.doImage(f_image, f_image_alt, f_image_align, f_target);
        } else {
            window.opener.doImage(f_image, f_image_alt, f_image_align, f_target);
            window.close();
        }
    }

}



/**
 * Javascript for dynamic uploader..
 */
var swfu;

window.onload = function() {
    var settings = {
        flash_url : "[[/literal]][[$paths.pivotx_url]][[literal]]includes/uploader/swfupload.swf",
        upload_url: "[[/literal]][[$paths.pivotx_url]][[literal]]includes/editor/handle_upload.php",	
        file_post_name: "userfile",
        file_size_limit : "2 MB",
        file_types : "*.jpg;*.gif;*.jpeg;*.png",
        file_types_description : "Images",
        file_upload_limit : 100,
        file_queue_limit : 0,
        custom_settings : {
            progressTarget : "divFileProgressContainer",
            cancelButtonId : "btnCancel"
        },
        debug: false,

        post_params : {
            pivotxsession: [[/literal]]"[[$pivotxsession]]"[[literal]]
        },

        // Button settings
        button_image_url: "[[/literal]][[$paths.pivotx_url]][[literal]]includes/uploader/button.png",	// Relative to the Flash file
        button_width: "199",
        button_height: "31",
        button_placeholder_id: "spanButtonPlaceHolder",
        button_text: '<span class="theFont"> </span>',
        button_text_style: ".theFont { font-size: 14; }",
        button_text_left_padding: 12,
        button_text_top_padding: 3,
        
        // The event handler functions are defined in handlers.js
        file_queued_handler : fileQueued,
        file_queue_error_handler : fileQueueError,
        file_dialog_complete_handler : fileDialogComplete,
        upload_start_handler : uploadStart,
        upload_progress_handler : uploadProgress,
        upload_error_handler : uploadError,
        upload_success_handler : pivotUploadSuccess,
        upload_complete_handler : uploadComplete,
        queue_complete_handler : queueComplete	// Queue plugin event
    };

    swfu = new SWFUpload(settings);
 };


function pivotUploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("Complete.");
		progress.toggleCancel(false);

	} catch (ex) {
		this.debug(ex);
	}

    $('#f_image').val(trim(serverData));
    updatePreview();    
    
}


/**
 * End of Javascript for dynamic uploader..
 */


/**
 * Javascript for autocomplete on filename..
 */
jQuery(function($) {

    function formatItem(row) {
        var result = "<img src='../timthumb.php?src=" + row[0] + "&w=171&h=128' width='43' height='32' style='margin: 0 6px -6px 0' />";
        result += row[2] + "  <em>" + row[1] + "</em> ";
        return result;
    }

    function formatResult(row) {
        return row[0];
    }

    $("#f_image").autocomplete("../../ajaxhelper.php?function=autoComplete", {
        delay: 150,
        width: 460,
        formatItem: formatItem,
        formatResult: formatResult,
        selectFirst: false,
        matchSubset: false,
        minChars: 2,
        max: 50
    });

    $("#f_image").result(function(event, data, formatted) {
        updatePreview(data[0]);
    });

    $("#f_image").blur(function(event, data, formatted) {
        updatePreview();
    });

});

/**
 * End of Javascript for autocomplete on filename..
 */


// Submitting and cancelling..
jQuery(function($) {

    // Attach event for 'insert image' button.
    $("#button_submit").click(function(event, data, formatted) {
        do_submit_f_image();
    });

    // Attach event for 'cancel' button.
    $("#button_cancel").click(function(event, data, formatted) {
        if (window.opener==null) {
            top.$('#dialogframe').dialog('close');
        } else {
            self.close();
        }
    });

    // Select the 'alt' form field by default..
    $('#f_image_alt').select();

    // Make sure the window is on top..
    self.focus();

});

// Small function to update the preview of the image..
function updatePreview(imagename) {

    var imagename = trim( $('#f_image').val() );

    if ( (imagename.length < 4) || ( (!imagename.match('.gif')) && (!imagename.match('.jpg')) && (!imagename.match('.jpeg')) && (!imagename.match('.png'))) ) {
        $('#imagepreview').html("");
    } else {
        $('#imagepreview').html("<img src='../timthumb.php?src=" + imagename + "&w=171&h=128' width='171' height='128' alt='preview' />");
    }
}


//]]>
</script>
[[/literal]]
</head>
<body style="margin: 0 12px 0 0; background-image: none; background-color: #FFF;">

[[ if $msg != "" ]]
    <p style="background-color:#FFF6BF; border-bottom:1px solid #FFD324; border-top:1px solid #FFD324; margin-bottom:6px;   min-height:16px; padding:6px;">[[ $msg ]]</p>
[[ /if ]]

<table border="0" cellspacing="0" cellpadding="2" class="formclass" style="border:0px;">

    <tr>
        <td colspan="3">
            <b>[[t]]Insert an Image[[/t]]:</b> <br />
            <br />
            [[t]]To insert an image, you should upload an image, or select a previously uploaded image.[[/t]]
        </td>

        <td rowspan="3">
            <div id="imagepreview">&nbsp;</div>
        </td>
    </tr>

    <tr>
        <td class="nowrap">
            <b>[[t]]Upload[[/t]]:</b>
        </td>
        <td colspan="2">


        <form style="clear:both;">
        
            <p style="margin: 2px 0px;" class="buttons">
    

                <span id="spanButtonPlaceHolder">
                    <a href="#">
                        <img src="../../pics/page_lightning.png" alt="" />[[t]]Upload an image[[/t]] <span style="font-size: 7pt;">(2 MB Max)</span>
                    </a>                    
                    
                    
                    
                </span>
                
        
            </p>

        </form>
		<div id="divFileProgressContainer" style="width:330px; clear:both;"></div>

        <a href="#" id="btnCancel"  onclick="swfu.cancelQueue();"></a> 

        </td>
    </tr>



    <tr>

        <td class="nowrap">
           <b>[[t]]Image name[[/t]]:</b>
        </td>
        <td>
                <input type='text' name='f_image' id='f_image' size='25' value='[[ $imagename ]]' class='input' style='width: 230px;' />
		</td>
		<td class="buttons_small">
			 <a href="#" onclick="top.openFileSelector('[[t]]Select an image[[/t]]', $('#f_image'), 'gif,jpg,png');">
						<img src='../../pics/page.png' /> [[t]]Select[[/t]]
					</a>
			
        </td>
    </tr>


    <tr>
        <td colspan="3">
            <hr size="1" noshade><form name="pick_f_image" action="" method="post">
        </td>
    </tr>


    <tr>
        <td class="nowrap">
            <b>[[t]]Alternate text[[/t]]:</b>
        </td>
        <td colspan="3">
            <input type='text' name='f_image_alt' id='f_image_alt' size='25' value='' class='input' />
       </td>
    </tr>



    <tr>
        <td>
           <b>[[t]]Align[[/t]]:</b>
        </td>
        <td colspan="3">
            <select id="f_image_align" name="f_image_align" class='input' />
                <option value="center" selected='selected'>[[t]]Center (default)[[/t]]</option>
                <option value="left">[[t]]Left[[/t]]</option>
                <option value="right">[[t]]Right[[/t]]</option>
                <option value="inline">[[t]]Inline[[/t]]</option>
            </select>
        </td>

    </tr>

[[*
    <tr>
        <td>
           <b>[[t]]Border[[/t]]:</b>
        </td>
        <td colspan="2">
            <input type='text' id='f_border' name='f_border' size='10' value='1' class='input' />
            [[t]]pixels[[/t]]
        </td>
    </tr>
*]]

    <tr>
        <td colspan="3">

            <input type='hidden' name='f_target' id='f_target' value='[[ $target ]]' />

            <p style="margin: 8px 0px;" class="buttons">

            <a href="#" class="positive" id='button_submit'>
            <img src="../../pics/tick.png" alt="" />[[t]]Insert image![[/t]]</a>

            <a href="#" class="negative" id='button_cancel'>
            <img src="../../pics/delete.png" alt="" />[[t]]Cancel[[/t]]</a>
            </p>

        </td>
    </tr>

</table>
</form>




</body>
</html>
