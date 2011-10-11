[[include file="inc_header.tpl" ]]
[[include file="inc_init_texteditor.tpl" ]]

[[ hook name="media-before" ]]

[[ if count($labels)>1 ]]
    <div class="buttons_small">
    [[ foreach from=$labels key=key item=item ]]
        <a href='index.php?page=[[$currentpage]]&additionalpath=[[$additionalpath]]&offset=[[$key]]' style="margin: 0 5px 5px 0;">
                <img src='pics/page.png' alt='' /><strong>[[t]]Pg.[[/t]] [[ $key+1 ]]</strong> ([[$item]])
        </a>
    [[ /foreach ]]
    </div>
    <div class="cleaner">&nbsp;</div>
[[ /if ]]

<table border='0' cellspacing='0' class='tabular' >

<tr>
    <th>&nbsp;</th>
    <th>[[t]]Filename[[/t]]</th>
    <th>[[t]]Description[[/t]]</th>
    <th align="right">[[t]]Filesize[[/t]]</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>  
</tr>

[[ foreach from=$dirs key=name item=path ]]

<tr class='[[ cycle values="even, odd"]]' style='height: 26px;'>
    <td>
        <img src='pics/folder.png' width='16' height='16' alt='folder' />
    </td>
    <td colspan='7'>
        <strong><a href='index.php?page=[[$currentpage]]&amp;additionalpath=[[ $path|escape:"url" ]]'>[[ $name ]]</a></strong>

    </td>

    <td class="buttons_small">
        [[ hook name="media-line-folder" value=$path ]]
    </td>

</tr>


[[ /foreach ]]


[[ foreach from=$files key=name item=item ]]

<tr class='[[ cycle values="even, odd"]]' style='height: 26px;'>
    [[ if $item.ext=="gif" || $item.ext=="jpg" || $item.ext=="jpeg" || $item.ext=="png" ]]
    
        <td>
            <img src='pics/image.png' width='16' height='16' alt='image' />
        </td>
        <td>
            <a href="[[$imageurl]][[$name]]" title="[[ $name ]]" class="thickbox">[[ $name ]]</a>
        </td>
        
        <td class='nowrap small'>
            [[t]]Image[[/t]], [[ $item.dimension ]] px.
        </td>
        <td align='right' class='nowrap'>
            [[ $item.size ]]
        </td>
        <td>
            &nbsp;
        </td>
    
        <td class="buttons_small">
            [[ if $user.userlevel>=3]]
                [[ hook name="media-line-image" value=$item ]]
            [[ /if ]]            
        </td>
    
        [[ if $hide.medialineimage ]]<!--[[/if x="-->"]]
        <td class="buttons_small">
            <a href="#" onclick="imageEdit('[[$imagepath]][[$name]]');">
                <img src='pics/image_edit.png' alt='' /> [[t]]Thumbnail[[/t]]
            </a>
        </td>
        [[ if $hide.medialineimage ]]-->[[/if]]
        
        
    

    [[ else ]]

        <td>
            <img src='pics/page.png' width='16' height='16' alt='file' />
        </td>
        <td>
            [[ $name ]]
        </td>
    
        
        <td class='nowrap small'>
            [[ filedescription filename=$name ]]&nbsp;
        </td>
        <td align='right' class='nowrap'>
            [[ $item.size ]]
        </td>
        <td>
            &nbsp;
        </td>
    
        <td class="buttons_small">
            [[ hook name="media-line-file" value=$item ]]           
        </td>
        <td class="buttons_small">        
            [[ if $item.writable && $item.bytesize<65536 && $user.userlevel>=3 ]]
            
                <a href="ajaxhelper.php?function=view&amp;basedir=[[ $basedir|escape:"base64" ]]&amp;file=[[ $item.path|escape:"url" ]]"
                    title="[[t]]Edit[[/t]] &raquo; <strong>[[ $item.path ]]</strong>" class="dialog editor">
                    <img src='pics/pencil.png' alt='' /> [[t]]Edit[[/t]]
                </a>

            [[ /if ]]
        </td>

    [[/if]]
    

        
    <td class="buttons_small">
        [[ if $item.writable && $user.userlevel>=3  ]]
        <a href="#"  onclick="return confirmme('index.php?page=[[$currentpage]]&amp;del=[[ $item.path|escape:"url" ]]', '[[t escape=js ]]Are you sure you wish to delete this file?[[/t]]');" class="negative">
            <img src='pics/delete.png' alt='' />
            [[t]]Delete[[/t]]
        </a>
        [[ /if ]]
    </td>
        
    <td class="buttons_small">
        [[ if $writable && $user.userlevel>=3  ]]
        
        
        
        <a href="#"  onclick="return askme('index.php?page=[[$currentpage]]&amp;file=[[ $item.path|escape:"url" ]]', 'Copy to file name?');">
        <img src='pics/add.png' alt='' />
        [[t]]Duplicate[[/t]]
        </a>
        [[ /if ]]
    </td>



</tr>


[[ /foreach ]]

</table>



[[ if $writable ]]

    <p class="buttons">
        [[ if $user.userlevel>=3 ]]
        <a href="#" onclick="return askme('index.php?page=[[$currentpage]]&amp;path=[[ $additionalpath|escape:"url" ]]', 'New file name?');">
            <img src="pics/page_add.png" alt="" />
           [[t]]Create a new file[[/t]]
        </a>
    
        <a href="#" onclick="return askme('index.php?page=[[$currentpage]]&amp;addfolder=[[ $additionalpath|escape:"url" ]]', 'New folder name?');">
            <img src="pics/folder_add.png" alt="" />
            [[t]]Create a new folder[[/t]]
        </a>
        [[ /if ]]
        <a href="#" id="uploadbutton" >
            <img src="pics/page_lightning.png" alt="" />
           [[t]]Upload a file[[/t]]
        </a>
    
    
    </p>


[[ else ]]

    [[t]]You're not allowed to upload/duplicate files in this folder. Use your FTP program to do this.[[/t]]

[[ /if ]]

<script type="text/javascript">
//<![CDATA[

jQuery(function($) {
    $('#uploadbutton').bind('click', function() {
        $('#uploader').dialog({
            bgiframe:true, 
            resizable: true,
            modal: true,
            draggable: true, 
            width: 540,
            height: 220,
            title: "[[t]]Upload a file[[/t]]",
            overlay: { opacity: 0.75, background: "#789" }
        }).show();
        return false;       
    });
    
    $('#uploadmore').bind('click', function() {
        $('#uploader').dialog('close');
        $('#uploader2').dialog({
            bgiframe:true, 
            resizable: true,
            modal: true,
            draggable: true, 
            width: 540,
            height: 340,
            title: "[[t]]Upload more files[[/t]]",
            overlay: { opacity: 0.75, background: "#789" }
        }).show();
        return false;       
    });
    
});

//]]>
</script>
<div id="uploader">
    <div>
        <form action="index.php?page=[[$currentpage]]" method="post" class="uploader" enctype="multipart/form-data">
            <p>[[t]]Select the file(s) to upload to this folder:[[/t]]</p>
            <input type="hidden" name="page" value="[[$currentpage]]" />
            <input type="hidden" name="additionalpath" value="[[$additionalpath]]" />
            <p>1: <input type="file" name="file1" size="55"  /> <br />
            2: <input type="file" name="file2" size="55"  /> <br />
            3: <input type="file" name="file3" size="55"  /> <br />
            <input type="submit" value="[[t]]Upload[[/t]]" /></p>
    
            <a href="#" onclick="openUploadMore();">[[t]]Upload more[[/t]]</a>
    
            <a href="#" id="uploadmore"></a>
    
        </form>
    </div>
</div>


<div id="uploader2">
    <div>
        <form action="index.php?page=[[$currentpage]]" method="post" class="uploader" enctype="multipart/form-data">
            <p>[[t]]Select the file(s) to upload to this folder:[[/t]]</p>
            <input type="hidden" name="page" value="[[$currentpage]]" />
            <input type="hidden" name="additionalpath" value="[[$additionalpath]]" />
            <p>1: <input type="file" name="file1" size="55"  /> <br />
            2: <input type="file" name="file2" size="55"  /> <br />
            3: <input type="file" name="file3" size="55"  /> <br />
            4: <input type="file" name="file4" size="55"  /> <br />
            5: <input type="file" name="file5" size="55"  /> <br />
            6: <input type="file" name="file6" size="55"  /> <br />
            7: <input type="file" name="file7" size="55"  /> <br />
            8: <input type="file" name="file8" size="55"  /> <br />
            <input type="submit" value="[[t]]Upload[[/t]]" /></p>
        </form>
    </div>
</div>

[[ hook name="media-after" ]]


[[include file="inc_footer.tpl" ]]
