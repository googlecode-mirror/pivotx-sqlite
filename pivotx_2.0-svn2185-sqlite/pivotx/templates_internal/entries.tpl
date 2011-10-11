[[include file="inc_header.tpl" ]]


<div id="entriesgrid">
    <img src='./pics/loadingAnimation.gif' style='display: block; margin: 50px auto;' alt='Loading..' />
</div>


<script type="text/javascript">
//<![CDATA[

jQuery(function($) {
    
    [[ if $search!=""]]
    filterword = "[[$search]]";
    [[ /if]]
    
    loadEntries(0,20);
});

//]]>
</script>

[[include file="inc_footer.tpl" ]]