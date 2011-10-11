<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" [[lang type='xml']] [[lang type='html']]>
<head>

    [[ hook name="head-begin" ]]

    <link rel="shortcut icon" href="[[ $paths.pivotx_url ]]pics/favicon.ico" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <title>PivotX &raquo; 
    [[ if $currentpage=="dashboard" || $currentpage=="login" ]] [[ $config.sitename|strip_tags ]] &raquo; [[/if]]
    [[ $title|strip_tags ]]</title>

    <!-- jquery and the likes -->
    <script src="includes/js/jquery.js" type="text/javascript"></script>
    <script src="includes/js/jquery-ui.js" type="text/javascript"></script>
    <script src="includes/js/jquery-plugins.js" type="text/javascript"></script>
    <link rel="stylesheet" href="templates_internal/ui-theme/jquery-ui.css" type="text/css" />

        
    <!-- Markitup -->
    <link rel="stylesheet" type="text/css" href="includes/markitup/markitup.css" />
    <script src="includes/markitup/jquery.markitup.js" type="text/javascript"></script>
    <script src="includes/markitup/set.js" type="text/javascript"></script>

    <!-- Thickbox -->
    <script src="includes/js/thickbox.js" type="text/javascript"></script>
    <link rel="stylesheet" href="templates_internal/assets/thickbox.css" type="text/css" />

    <!-- Formclass library -->
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/formclass.css" />

    <!-- Pivot -->
    <script src="includes/js/pivotx.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/pivotx.css"/>
    <!--[if lte IE 7]>
        <link rel="stylesheet" type="text/css" href="templates_internal/assets/pivotx_ie.css"/>
    <![endif]-->

    [[ hook name="head-end" ]]

</head>

<body>
    [[ hook name="body-begin" ]]


<div id="header">

    [[ hook name="logo-before" ]]
    <div id="logo">
        <a href="[[ $paths.pivotx_url ]]index.php"><img src="templates_internal/assets/pivotx.png" alt="PivotX" /></a>
    </div>
    [[ hook name="logo-after" ]]


    [[ hook name="sitename-before" ]]
    <div id="sitenamediv">
        <a href="[[ $paths.site_url ]]">[[ $config.sitename ]]</a>
    </div>
    [[ hook name="sitename-after" ]]


    [[ hook name="usermenu-before" ]]
    <div id="usermenu">
        [[if $user.username=="" ]]
            [[t]]Welcome, unknown user.[[/t]]
        [[ else ]]
            [[t]]Welcome back[[/t]], [[ $user.nickname ]]
            - <a href="index.php?page=myinfo">[[t]]My Info[[/t]]</a>
            - <a href="index.php?page=logout">[[t]]Logout[[/t]]</a>
        [[/if]]
    </div>
    [[ hook name="usermenu-after" ]]


    [[ hook name="mainmenu-before" ]]
    <!-- main menu -->
    
    
    <ul id="mainmenu" class="sf-menu sf-navbar">
        
        [[ if $user.userlevel>0 ]]
        <li [[if $currentpage=="dashboard" ]] class="current parent"[[/if]]>
           <a href="[[ $paths.pivotx_url ]]index.php">[[t]]Dashboard[[/t]]</a>
            <ul>
                <li><a href="[[ $paths.pivotx_url ]]index.php">[[t]]Back to dashboard[[/t]]</a></li>
                [[ if is_array($weblogs) ]]
                
                    [[* We add an exta level, if we have more than 2 weblogs. *]]
                    [[ if count($weblogs)>2 ]]
                        <li><a class="sf-with-ul" href="#">[[t]]View weblog[[/t]]</a><ul>
                    
                        [[foreach from=$weblogs key=key item=item name=weblogs ]]
                            <li [[ if $smarty.foreach.weblogs.last ]] class="last"[[/if]]>
                                <a href='[[$item.link]]' title="[[$item.name]] - [[$item.payoff]]">[[$item.name]]</a>
                            </li>
                        [[/foreach]]
                        
                        </ul></li>
                    
                    [[ else ]]
                        
                        [[foreach from=$weblogs key=key item=item ]]
                            <li class="divider">&nbsp;</li>
                            <li>
                                <a href='[[$item.link]]' title="[[$item.name]] - [[$item.payoff]]">[[t]]view[[/t]] [[$item.name]]</a>
                            </li>
                        [[/foreach]]
                        
                    [[ /if ]]
                    
                [[/if]]
            </ul>       
        </li>
        [[ /if ]]
        
        [[foreach from=$menu key=key item=item ]]
        
        [[* Only show the menu options for items that actually have items under them,
            with the 'login' option being the only exception. *]]
        [[if (is_array($submenu.$key) || $key=="login") ]]
            <li [[if $key==$currentpage || in_array($currentpage, $menuchildren[$key]) ]] class="current parent"[[/if]]>
                <a href="?page=[[$key]]"><span>[[ $item.0 ]]</span></a>
                <ul>
                [[foreach from=$submenu.$key key=subkey item=subitem name="submenu" ]]
                
                    [[* We add an exta level for the extensions. *]]
                    [[ if $key=="extensions" && $smarty.foreach.submenu.index==2 ]]
                        <li><a class="sf-with-ul" href="#">[[t]]Configure Extensions[[/t]]</a><ul>
                    [[/if]]
    
                    [[* And also add an exta level for the 'maintenance'. *]]
                    [[ if $subkey=="spamprotection" ]]
                        <li><a class="sf-with-ul" href="#">[[t]]Maintenance[[/t]]</a><ul>
                    [[/if]]                
                
                    [[ if $subitem.0!="" ]]
                        <li class="[[if $subkey==$currentpage]]current [[/if]][[ if $smarty.foreach.submenu.last ]]last[[/if]]">
                            <a href='index.php?page=[[$subkey]]' title="[[$subitem.1]]">[[$subitem.0]]</a>
                        </li>
                    [[ /if ]]
                    
                    [[ if $subitem.3 ]]
                        <li class="divider">&nbsp;</li>
                    [[/if]]
                    
                    [[* Close the extra level, if necessary. *]]
                    [[ if ($key=="extensions" || $key=="administration") && $smarty.foreach.submenu.index>0 && $smarty.foreach.submenu.last ]]
                       </ul></li>
                    [[/if]]
                
                    
                [[ /foreach ]]
                </ul>
            </li>
        [[/if]]
        [[ /foreach]]    
        
    
    </ul>

    <!-- end of main menu -->
    [[ hook name="mainmenu-after" ]]


</div>

[[ hook name="content-before" ]]
<div id="content">
    [[ hook name="content-begin" ]]


    [[ hook name="title-before" ]]

    [[ if $skiptitle!=true ]]
        <h1>
            [[ $title ]]
            [[ if $entry.title != ""]]<span> &raquo; [[$entry.title]]</span>[[/if]]
            [[ if $page.title != ""]]<span> &raquo; [[$page.title]]</span>[[/if]]
        </h1>
        
    [[ /if ]]

    [[ if $heading!=$title]]
        <h2>[[ $heading ]]</h2>
    [[/if]]

    [[ hook name="title-after" ]]

    [[ hook name="error-before" ]]

    [[if $error!="" ]]
        <div class="errorbanner" id='errorbanner'>
            [[ $error ]]
        </div>
    [[/if]]

    [[ hook name="error-after" ]]

    [[ if is_array($messages) && count($messages)>0 ]]
    <script type="text/javascript">
    //<![CDATA[
    
    jQuery(function($) {
        [[ foreach from=$messages key=key item=item ]]
        humanMsg.displayMsg("[[ $item|escape ]]");
        [[ /foreach ]]
    });
    //]]>
    </script>
    [[ /if ]]
    
