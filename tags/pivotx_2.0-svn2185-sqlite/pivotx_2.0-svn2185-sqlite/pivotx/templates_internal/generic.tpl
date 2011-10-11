[[include file="inc_header.tpl" ]]

<div id="container">


    <div class="homeleftcolumn">
    
        [[ $html ]]
        [[ $form ]]

    </div>

    <div class="homerightcolumn">

        [[ if is_array($warnings) && count($warnings)>0 ]]
            [[ foreach from=$warnings key=key item=item ]]
            <div class="warning">
                <h2><img src="pics/error.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -3px;" /> Warning!</h2>   
                [[ $item ]]
            </div>
            [[ /foreach ]]
        [[ /if ]]

    </div>


    <div class="cleaner">&nbsp;</div>

</div><!-- end of 'container' -->

[[include file="inc_footer.tpl" ]]
