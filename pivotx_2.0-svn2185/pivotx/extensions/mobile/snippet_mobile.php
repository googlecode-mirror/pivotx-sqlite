<?php
// - Extension: Mobile Browser Extension
// - Version: 0.2 - development
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=mobile
// - Description: A snippet extension to detect mobile browsers, and redirect them to a specific page.
// - Date: 2009-02-22
// - Identifier: mobile

global $mobiledetect_config;

$mobiledetect_config = array(
    'mobile_detection' => 1,
    'mobile_redirect' => 1,
    'mobile_redirectlink' => "Click here to visit the mobile version of this site.",
    'mobile_domain' => "m.example.org",
    'mobile_frontpage' => "mobile/frontpage_template.html",
    'mobile_entrypage' => "mobile/entrypage_template.html",
    'mobile_page' => "mobile/page_template.html"
);


$this->addHook(
    'before_parse',
    'callback',
    "mobileHook"
    );


/**
 * Adds the hook for mobileAdmin()
 *
 * @see mobileAdmin()
 */
$this->addHook(
    'configuration_add',
    'mobile',
    array("mobileAdmin", "Mobile version")
);





// Register 'mobiledetect' as a smarty tag.
$PIVOTX['template']->register_function('mobiledetect', 'smarty_mobiledetect');



/**
 * main: If enabled, detect mobile browsers, and redirect..
 */
if ( defined('PIVOTX_INWEBLOG') && ($PIVOTX['config']->get('mobile_domain') != $_SERVER['HTTP_HOST']) ) {
    
    if ($PIVOTX['config']->get('mobile_detection')==1) {
        
        if (!isMobile()) {
            return "";
        } else {
            
            $link = "http://".$PIVOTX['config']->get('mobile_domain');
            $linktext = sprintf("<a href='%s'>%s</a>", $link, $PIVOTX['config']->get('mobile_redirectlink') );
            
            if ($PIVOTX['config']->get('mobile_redirect')==1) {
                header("location: ".$link);
                echo "<script type=\"text/javascript\">window.location=\"".$link."\";</script>\n";
                echo $linktext;
                die();    
            } else {
                
                echo "<div style='border: 1px solid #000; background-color: #FFF; padding: 4px;'>$linktext</div>";
            }
            
        }
            
    }
    
}



function mobileHook(&$params) {
    global $PIVOTX;
    
    
    // Only change the templates when we're at the correct (sub)domain
    if ($PIVOTX['config']->get('mobile_domain') != $_SERVER['HTTP_HOST'] ) {
        return;
    }

    // Make sure we're allowed to override the templates.
    $PIVOTX['config']->set('allow_template_override', 1);
    
    $hostname = $_SERVER['http_host'];
    
    switch ($params['action']) {
        
        case "weblog":
            $params['template'] = $PIVOTX['config']->get('mobile_frontpage');
            break;
        
        case "entry":
            $params['template'] = $PIVOTX['config']->get('mobile_entrypage');
            break;
                
        case "page":
            $params['template'] = $PIVOTX['config']->get('mobile_page');
            break;
        

    }

}



/**
 * Detect a mobile browser, and perhaps redirect them to a different page..
 *
 * Not very useful, since pivotx can do this automatically by checking the option in de 'mobile version'
 * configuration screen. If, however, you'd like to redirect people to a specific page, you can use
 * [[ mobiledetect redirect="http://example.org/page/mobile" ]]
 *
 * Note: insert this tag at the very top of your template, for the best results..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_mobiledetect($params, &$smarty) {
    global $PIVOTX, $mobiledetect_config; 

    if (!isMobile()) {
        return "";
    } else {
        
        // Redirect to a different page..
        if (!empty($params['redirect'])) {
            header("location: ".$params['redirect']);
            echo "<script type=\"text/javascript\">window.location=\"".$params['redirect']."\";</script>\n";
            echo "<a href=\"".$params['redirect']."\">Go to the mobile version of this page at ".$params['redirect'].".</a>";
            die();
        }


    }

}




/**
 * The configuration screen for Mobile version
 *
 * @param object $form_html
 */
function mobileAdmin(&$form_html) {
    global $mobiledetect_config, $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(3);

    // When running for the first time, set the default options, if they are not available in config..
    foreach ($mobiledetect_config as $key => $value) {
        if ($PIVOTX['config']->get($key)==="") {
            $PIVOTX['config']->set($key, $value);
        }
    }


    $form = $PIVOTX['extensions']->getAdminForm('mobile', 'mobileAdmin');


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_detection',
        'label' => "Detect mobile visitors",
        'text' => "Yes, detect visitors that use a mobile browser."
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_redirectlink',
        'label' => "Redirect link",
        'size' => 60,
    ));
    
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_redirect',
        'label' => "Immediate redirect",
        'text' => "Yes, redirect the visitor to the mobile version immediately. If this is disabled, the user will be presented with a link at the top of the page."
    ));

    $form->add( array(
       'type' => 'custom',
       'text' => "<tr><td colspan='3'><hr size='1' noshade='1' /></td></tr>"
        
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_domain',
        'label' => "Mobile Domain name",
        'value' => '',
        'error' => 'That\'s not a proper domain name!',
        'text' => "The domain name of the mobile version, for example m.example.org. Do not include the 'http://' part.",
        'size' => 26,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_frontpage',
        'label' => "Frontpage template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_entrypage',
        'label' => "Entry template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_page',
        'label' => "Page template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));





    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['mobile'] = $PIVOTX['extensions']->getAdminFormHtml($form, $mobiledetect_config);


}





/**
 * adapted from: http://www.russellbeattie.com/blog/mobile-browser-detection-in-php
 */
function isMobile() {
    
    $isMobile = false;

    $op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $ac = strtolower($_SERVER['HTTP_ACCEPT']);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
        || $op != ''
        || strpos($ua, 'sony') !== false 
        || strpos($ua, 'symbian') !== false 
        || strpos($ua, 'nokia') !== false 
        || strpos($ua, 'samsung') !== false 
        || strpos($ua, 'mobile') !== false
        || strpos($ua, 'windows ce') !== false
        || strpos($ua, 'epoc') !== false
        || strpos($ua, 'opera mini') !== false
        || strpos($ua, 'nitro') !== false
        || strpos($ua, 'j2me') !== false
        || strpos($ua, 'midp-') !== false
        || strpos($ua, 'cldc-') !== false
        || strpos($ua, 'netfront') !== false
        || strpos($ua, 'mot') !== false
        || strpos($ua, 'up.browser') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'audiovox') !== false
        || strpos($ua, 'blackberry') !== false
        || strpos($ua, 'ericsson,') !== false
        || strpos($ua, 'panasonic') !== false
        || strpos($ua, 'philips') !== false
        || strpos($ua, 'sanyo') !== false
        || strpos($ua, 'sharp') !== false
        || strpos($ua, 'sie-') !== false
        || strpos($ua, 'portalmmm') !== false
        || strpos($ua, 'blazer') !== false
        || strpos($ua, 'avantgo') !== false
        || strpos($ua, 'danger') !== false
        || strpos($ua, 'palm') !== false
        || strpos($ua, 'series60') !== false
        || strpos($ua, 'palmsource') !== false
        || strpos($ua, 'pocketpc') !== false
        || strpos($ua, 'smartphone') !== false
        || strpos($ua, 'rover') !== false
        || strpos($ua, 'ipaq') !== false
        || strpos($ua, 'au-mic,') !== false
        || strpos($ua, 'alcatel') !== false
        || strpos($ua, 'ericy') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'vodafone/') !== false
        || strpos($ua, 'wap1.') !== false
        || strpos($ua, 'wap2.') !== false;
    
    return $isMobile;
    
}


?>
