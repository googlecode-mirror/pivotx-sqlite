[[include file="inc_header.tpl" ]]


[[ if is_array($submenu.$listing) ]]
    <ul id="placeholder-menu">
    [[foreach from=$submenu.$listing key=key item=item name=submenu]]
        [[ if $item.1!="" ]]
            <li>
            <a href='index.php?page=[[$key]]'>[[$item.0]]</a>
            <p>[[$item.1]]</p>
            </li>
        [[ /if ]]
    [[/foreach ]]
    </ul>
[[ /if ]]

[[include file="inc_footer.tpl" ]]