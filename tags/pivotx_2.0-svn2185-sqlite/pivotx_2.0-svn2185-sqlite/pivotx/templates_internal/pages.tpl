[[include file="inc_header.tpl" ]]

[[ foreach from=$pages key=key item=item ]]


[[ if $item.chaptername!="" ]]
<h1 style='margin-bottom: 0;'>
    [[ $item.chaptername ]] &nbsp; <em style='font-size: 12px;'>[[ $item.description ]]</em>
</h1>
[[ else ]]
<h1 style='margin-bottom: 0;'>[[t]]Orphaned Pages[[/t]]</h1>
[[ /if ]]

<table class='formclass' cellspacing='0' style='margin: 8px 0px; border:0; width: 98%;'>
[[ foreach from=$item.pages key=pagekey item=page name=loop ]]
    <tr class="[[cycle values='odd, even' name=$key ]]">
        <td width='640'  class='entriesclip'> 
            &#8470; [[ $page.uid ]].
            <strong>
                [[ assign var=uid value=$page.uid ]]
                [[ if $page.editable ]]<a href="index.php?page=page&amp;uid=[[$uid]]">[[ /if ]]
                [[ $page.title|truncate:35 ]][[ if $page.editable ]]</a>[[ /if ]]
                
            </strong>
            <span style="font-size: 85%;">
                ([[$page.uri]] - [[t]]order[[/t]] [[ $page.sortorder ]]
                [[ if $page.status=="timed" ]]- [[t]]Timed Publish[[/t]][[/if]]
                [[ if $page.status=="hold" ]]- [[t]]Hold[[/t]][[/if]])</span><br />
            <div class="clip" style="width: 500px;">[[ $page.excerpt|hyphenize ]]</div>
        </td>
        <td width='170' class="nowrap">
            <span>
                [[ assign var=username value=$page.user ]]
                [[ if $users.$username != "" ]][[ $users.$username]][[ else ]][[ $page.user]][[/if]], 
                [[ date date=$page.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </span><br />
            <span style="font-size: 85%;"> [[ $page.template|truncate:35 ]]</span>
        </td>
        <td width='70' align='right' class="buttons_small" style="padding: 2px 4px;">
            [[ if $page.editable ]]
                [[ button link="index.php?page=page&uid=$uid" icon="page_white_edit.png" ]] [[t]]Edit Page[[/t]] [[/button]]
            [[ else ]]
                &nbsp;
            [[ /if]]
        </td>

        <td width='70' align='right' class="buttons_small" style="padding: 2px 4px;">
            [[ if $page.editable ]]
            <a href="#" onclick="return confirmme('index.php?page=pagesoverview&amp;delpage=[[ $uid ]]', '[[t escape=js ]]Are you sure you wish to delete this Page?[[/t]]');"  class="negative">
               <img src="pics/page_white_delete.png" alt="" /> [[t]]Delete Page[[/t]] </a>
            </td>
            [[ /if ]]

    </tr>

[[/foreach]]
</table>

[[ if $item.chaptername!="" ]]

<p style="margin: 8px 0px;" class="buttons">


    [[ button link="index.php?page=page&chapter=$key" icon="page_white_add.png" ]] [[t]]Write a new Page[[/t]] [[/button]]
    
    [[ if $item.editable ]]
    <a href="index.php?page=chapter&amp;id=[[$key]]" title="[[t]]Edit Chapter[[/t]]" class="dialog chapter">
        <img src="pics/book_edit.png" alt="" /> [[t]]Edit Chapter[[/t]] </a>
    
     <a href="#" onclick="return confirmme('index.php?page=pagesoverview&amp;del=[[ $key ]]', '[[t escape=js ]]Are you sure you wish to delete this Chapter?[[/t]]');" class="negative">
        <img src="pics/book_delete.png" alt="" /> [[t]]Delete Chapter[[/t]] </a>
    [[ /if ]]
    
</p>

[[ /if ]]

<hr size='1' noshade='noshade' />

[[ /foreach ]]

[[ if $item.editable ]]
<p style="margin: 16px 0px">

    <span class="buttons">  
    <a href="index.php?page=chapter&amp" title="[[t]]Add a Chapter[[/t]]" class="dialog chapter">
        <img src="pics/book_add.png" alt="" /> [[t]]Add a Chapter[[/t]] </a>        
    </span>

</p>
[[ /if ]]

<br />

[[include file="inc_footer.tpl" ]]
