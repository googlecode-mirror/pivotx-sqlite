[[include file="inc_header.tpl" ]]

[[ if $moderating ]]
    [[ if (is_array($modcomments) && count($modcomments)>0 ) ]]
    <h3>[[t]]Comments waiting for moderation[[/t]].</h3>
        <form action="index.php?page=comments" method="post">
        <table class='formclass' cellspacing='0' border='0' width='800'>
            <tr>
                <th> &nbsp; </th>
                <th> [[t]]Name[[/t]] / [[t]]Email[[/t]]  /  [[t]]URL[[/t]]    </th>
                <th> [[t]]IP-address[[/t]]     </th>
                <th> [[t]]Date[[/t]]     </th>
            </tr>
    
            [[ foreach from=$modcomments key=key item=comment ]]
    
            <tr>
    
                <td rowspan="2" style='border-bottom: 1px solid #BBB; color: #777;'>
                    <input type="checkbox" name="checked[]" value="[[ $comment.uid ]]" />
                </td>
                <td class="nowrap">&#8470; [[ $key ]]. 
                    <strong>
                        [[ $comment.name|truncate:28 ]]
                    </strong>
                    <span style="color:#888; font-size: 11px;">
                       [[ if $comment.email!="" ]]
                   / <a href='mailto:[[ $comment.email ]]'>[[ $comment.email|truncate:28 ]]</a>
                    [[ /if ]]
                    [[ if $comment.url!="" ]]
                    / <a href='[[ $comment.url|addhttp ]]'>[[ $comment.url|trimhttp|truncate:28 ]]</a>
                    [[ /if ]]
    
                    [[ if $comment.registered==1 ]](registered)[[ /if ]]
                    [[ if $comment.discreet==1 ]](discreet)[[ /if ]]
                    [[ if $comment.notify==1 ]](notify)[[ /if ]]
                    [[ if $comment.moderate==1 ]](in moderation)[[ /if ]]
                                    
                                    
                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ $comment.ip ]]
                </span>
            </td>
                <td class="nowrap">
                <span style="font-size: 11px;">
                    [[ date date=$comment.date format="%ordday% %monthname% '%ye% - %hour24%:%minute%" ]]
                    </span>
                </td>
    
                [[ if 0 ]] [[* todo: Fix editing comments in the moderation queue *]]
                <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                
                    <a href="index.php?page=editcomment&amp;uid=[[ $uid ]]&amp;key=[[ $key ]]" class="dialog comment" title="[[t]]Edit this comment[[/t]]"><img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]</a>
            
            </td>
            
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
    
                <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $uid ]]&amp;del=[[ $key ]]', '[[t escape=js ]]Delete this comment?[[/t]]');" class="negative"><img src="pics/world_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Delete[[/t]]</a>
                
                </td>
                [[ /if ]]
    
            </tr>
    
            <tr>
                <td colspan='3' style='border-bottom: 1px solid #BBB; color: #777; padding-top: 0px;'>
                    <p style='margin: 0px;'>
                        [[ $comment.comment|truncate:110 ]] 
                        [[ if $comment.entrytitle!="" ]]<em>([[t]]on[[/t]]: [[ $comment.entrytitle|truncate:40 ]])</em>[[ /if]]</p>
                </td>
            </tr>
    
            [[ /foreach ]]
    
    
        </table>
    
    
        <p style="margin: 8px 0px;" class="buttons">
    
             <a onclick="commentsCheckAll();">
                <img src="pics/tick.png" alt="" />
                [[t]]Check all[[/t]]
            </a>
        
         <a onclick="commentsCheckNone();">
            <img src="pics/cross.png" alt="" />
            [[t]]Check none[[/t]]
        </a>
        
         <button type="submit" class="positive" name="action_approve">
            <img src="pics/accept.png" alt="" />
            [[t]]Approve comments[[/t]]
        </button>
        
         <button type="submit" class="negative" name="action_delete">
                <img src="pics/delete.png" alt="" />
                [[t]]Delete comments[[/t]]
            </button>
    
        </p>
    
        </form>
    
    [[ else ]]
    
    <p>[[t]]There are no comments waiting for moderation[[/t]].</p>
    
    [[ /if ]]<!-- /if is_array($modcomments) -->
[[ /if  ]]<!-- /if moderating -->



[[ if (is_array($comments) && count($comments)>0 )]]
    [[ if $uid==0 ]]<h3>[[t]]The latest comments[[/t]].</h3>[[/if]]
    <table class='formclass' cellspacing='0' border='0' width='800'>
        <tr>

            <th> [[t]]Name[[/t]] / [[t]]Email[[/t]]  /  [[t]]URL[[/t]]    </th>
            <th> [[t]]IP-address[[/t]]     </th>
            <th> [[t]]Date[[/t]]     </th>
            <th> &nbsp;       </th>
            <th> &nbsp;       </th>            
        </tr>

        [[ foreach from=$comments key=key item=comment ]]

        <tr>

            <td class="nowrap">&#8470; [[ $key ]]. 
                <strong>
                    [[ $comment.name|truncate:28 ]]
                </strong>
                <span style="color:#888; font-size: 11px;">
                   [[ if $comment.email!="" ]]
                   / <a href='mailto:[[ $comment.email ]]'>[[ $comment.email|truncate:28 ]]</a>
                    [[ /if ]]
                    [[ if $comment.url!="" ]]
                    / <a href='[[ $comment.url|addhttp ]]'>[[ $comment.url|trimhttp|truncate:28 ]]</a>
                    [[ /if ]]

                    [[ if $comment.registered==1 ]](registered)[[ /if ]]
                    [[ if $comment.discreet==1 ]](discreet)[[ /if ]]
                    [[ if $comment.notify==1 ]](notify)[[ /if ]]
                    [[ if $comment.moderate==1 ]](in moderation)[[ /if ]]


                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ $comment.ip ]]
                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ date date=$comment.date format="%ordday% %monthname% '%ye% - %hour24%:%minute%" ]]
                </span>
            </td>

            [[ if 1 ]][[* todo: allow for setting a userlevel to edit the comments *]]
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
            
                <a href="index.php?page=editcomment&amp;uid=[[ $comment.entry_uid ]]&amp;key=[[ $comment.uid ]]" class="dialog comment" title="[[t]]Edit this comment[[/t]]"><img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]</a>
        
            </td>
            
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
    
                <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;del=[[ $comment.uid ]]', '[[t escape=js ]]Delete this comment?[[/t]]');" class="negative"><img src="pics/world_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Delete[[/t]]</a>

            </td>
            [[ /if ]]

        </tr>

        <tr>
            <td colspan='3' style='border-bottom: 1px solid #BBB; color: #777; padding-top: 0px;'>
                <p style='margin: 0px;'>
                    [[ $comment.comment|truncate:110 ]] 
                    [[ if $comment.entrytitle!="" ]]<em>([[t]]on[[/t]]: [[ $comment.entrytitle|truncate:40 ]])</em>[[ /if]]</p>
            </td>
        </tr>

        [[ /foreach ]]


    </table>



[[ else ]]

<p>[[ if $uid==0 ]][[t]]There are no latest comments[[/t]][[else]][[t]]No comments[[/t]][[ /if ]].</p>

[[ /if ]]    

[[include file="inc_footer.tpl" ]]
