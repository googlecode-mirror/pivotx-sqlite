[[include file="inc_header.tpl" ]]

<!-- Jquery UI's Sortables class might be called version 1.0, but it really doesn't
work at all like one might expect. For now I've added the widgets to the regular 
extensions page, and will revisit this once a newer version of UI pops up. -->

<div id="widgets">
    <div id="active" class="panel">
        <p>[[t]]Active widgets[[/t]]</p>
        <ul class="widgetlist">
        [[ $active ]]
        </ul>
    </div>


    <div id="available" class="panel" style="margin-left: 20px;">
        <p>[[t]]Available Widgets[[/t]]</p>
        <ul class="widgetlist">
        [[ $inactive ]]
        </ul>
    </div>
 </div>
 
<div style="clear: both;">&nbsp;</div>
 
<p class="buttons">
    <a href="#" class="positive" id="savewidgets">
        <img src="./pics/tick.png" alt="" />
        [[t]]Save[[/t]]
    </a>
</p>


<script type="text/javascript">
//<![CDATA[

jQuery(function($) {

    $("ul.widgetlist").sortable({ connectWith: ['ul.widgetlist'] });
        
    $("#savewidgets").click(function(){saveWidgets(); return false;});
            
});

function saveWidgets(s) {

    serial = $("#active ul.widgetlist").sortable('serialize', { expression: "([a-z0-9-]+)_([a-z0-9-]+)"}  );
    
    //console.log(serial);
    
    if (serial=="") { serial = "widget=0"; }
    
    self.location = "index.php?page=widgets&" + serial; 

}

//]]>
</script>

[[include file="inc_footer.tpl" ]]
