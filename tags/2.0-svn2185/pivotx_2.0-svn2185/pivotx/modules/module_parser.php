<?php
/**
 * Contains the general functions we use to generate pages.
 *
 * @package pivotx
 * @subpackage modules
 */


// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: module_parser.php 2185 2009-10-22 15:09:06Z pivotlog $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";


/**
 * The class that renders pages and handles caching, for all the pages that are
 * seen on the 'front side'.
 *
 */
class Parser {

    var $html;
    var $action;
    var $modifier;
    var $maintemplate;


    /**
     * Initialise the Parser object.
     *
     * @param string $action
     * @param array $modifier
     * @return Parser
     */
    function Parser($action="weblog", $modifier=array()) {

        // In case $action is an empty string, set to default value.
        if ($action == "") {
            $action = "weblog";
        }

        $this->action = $action;
        $this->modifier = $modifier;

        $this->maintemplate = "";
        

    }

    /**
     * Returns some properties of the Parser.
     *
     * @param string $property
     * @return string
     */
    function get($property) {
        if ($property == 'action') {
            return $this->action;
        } elseif ($property == 'modifier') {
            return $this->modifier;
        } else {
            return '';
        }
    }

    /**
     * Wrapper for the different functions that render the different types of
     * pages.
     *
     * @see $Parser::renderPage
     * @see $Parser::renderWeblog
     * @see $Parser::renderEntry
     * @see $Parser::renderTag
     * @see $Parser::renderSearch
     * @see $Parser::renderSpecial
     *
     */
    function render() {

        // add 'action' to the modifier array
        $this->modifier['action'] = $this->action;

        switch ($this->action) {

            case "page":
                $this->renderPage();
                break;

            case "weblog":
                $this->renderWeblog();
                break;

            case "entry":
                $this->renderEntry();
                break;

            case "tag":
                $this->renderTag();
                break;

            case "search":
                $this->renderSearch();
                break;

            case "special":
                $this->renderSpecial();
                break;

            case "feed":
                $this->renderFeed();
                break;

            default:
                $this->renderWeblog();
                break;

        }

    }


    /**
     * Render a Page, using the template that it was set to.
     *
     * @see $Parser::render
     */
    function renderPage() {
        global $PIVOTX;

        // If we're previewing, we need to set the posted values as the page,
        // otherwise get an entry from the DB.
        if (!empty($_GET['previewpage'])) {

            // Get the page from posted content.
            $page = sanitizePostedPage($page);

        } else {

            // Get the page from the DB..
            $page = $PIVOTX['pages']->getPageByUri($this->modifier['uri']);

            // If the page isn't found, and we are using mod_rewrite, this
            // might actually be a call for a weblog.
            if ((count($page) == 0) && ($PIVOTX['config']->get('mod_rewrite') > 0) && isset($_GET['rewrite'])) {
                if (in_array($this->modifier['uri'], $PIVOTX['weblogs']->getWeblognames())) {
                    $this->renderWeblog();
                    return;
                }
            }

        }

        // The type of page we're rendering
        $this->modifier['pagetype'] = 'page';
        $PIVOTX['template']->assign('pagetype', 'page');

        $PIVOTX['template']->assign('pageuri', $this->modifier['uri']);

        // Execute a hook, if present.
        $PIVOTX['extensions']->executeHook('before_parse', $this->modifier );

        // For pages that aren't previewed we check if it's published and
        // whether it's displayed with the correct URL. (This is
        // foolproof, since $_GET['previewpage'] is only set if logged in.)
        if (!isset($_GET['previewpage'])) {

            // If the site uses mod_rewrite (and we aren't at the root), crufty URLs 
            // should redirect (to avoid duplicate content).
            if (($PIVOTX['config']->get('mod_rewrite') > 0) && !$this->modifier['root'] && !isset($_GET['rewrite'])) {
                header("HTTP/1.1 301 Moved Permanently" ); 
                header("location: " . $page['link']);
                die();
            }

            // If the page isn't published yet, we shouldn't show it.
            // TODO: redirect people to the 404 page instead of showing an ugly 404 page.
            if ($page['status']!='publish') {
                header('HTTP/1.1 404 Not Found');
                $page=array(
                    'title' => __('Page not found (404 error)'),
                    'introduction' => '<p>' . __('The page isn\'t published or doesn\'t exist.') . ' ' . 
                    __('Please visit the frontpage by clicking the main title/logo.') . '</p>'
                );
            } 

        }

        // Here we convert the &quot; to ", if necessary, but only inside [[ tags ]]
        // Shouldn't we move this to pages_sql.php or pages_flat.php?
        $page['introduction'] = preg_replace_callback( '/\\[\[(.*)\]\]/ui', "fixquotescallback", $page['introduction']);
        $page['body'] = preg_replace_callback( '/\\[\[(.*)\]\]/ui', "fixquotescallback", $page['body']);

        // Set the 'code' we can use to invalidate this entry from cache.
        $this->code = "p".$page['uid']."_";


        // Set the page in $smarty as an array, as well as separate variables.
        $PIVOTX['template']->assign('page', $page);
        foreach($page as $key=>$value) {
            $PIVOTX['template']->assign($key, $value);
        }

        // Either use the specified page template, or the default page template 
        // as specified in the (current) weblog.
        $template = $page['template'];
        if (($page['template'] == '-') || ($page['template'] =='')) {
            $template = $PIVOTX['weblogs']->get('','page_template');
        }

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }

        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('page');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));

        // Render and show the template.
        $this->parseTemplate($template);


    }




    /**
     * Render a Weblog, using the template as was set in the config.
     *
     * @see $Parser::render
     */
    function renderWeblog() {
        global $PIVOTX;

        $not_found = false;
        
        // The type of page we're rendering
        $this->modifier['pagetype'] = 'weblog';
        $PIVOTX['template']->assign('pagetype', 'weblog');

        // Execute a hook, if present.
        $PIVOTX['extensions']->executeHook('before_parse', $this->modifier );

        // Determine which weblog to display (based on modifiers set by render.php).
        if (!empty($this->modifier['uri'])) {
            
            // We want a specific weblog..
            if (!$PIVOTX['weblogs']->setCurrent($this->modifier['uri'])) {
                $not_found = 'weblog';
            }
            $weblogname = $PIVOTX['weblogs']->getCurrent();
            
        } else if (!empty($this->modifier['category'])) {

            // Since we allow the category modifier to be a comma separated list 
            // we need some code to handle it here too. We silently remove categories 
            // don't exist from the list.
            $categories = explode(',',$this->modifier['category']);
            foreach ($categories as $key => $category) {
                if (!$PIVOTX['categories']->isCategory($category)) {
                    unset($categories[$key]);
                }
            }
            
            // We want to display one or more categories, with one or more items. 
            // We use the following logic:
            // 1) Check if any of the categories exist. If not -> 404
            // 2) Check if any of the categories belongs to a certain weblog. 
            //    If not -> continue to 3.
            //    If so -> Use that weblog to display the entries in the categories.
            // 3) Check if any of the categories have any entries. If not -> 404
            //    If so -> Display the archive with the template from the first weblog.

            if (count($categories) == 0) {
                $not_found = 'category';
            } else {

                $this->modifier['category'] = implode(',',$categories);
                
                if ($PIVOTX['weblogs']->setCurrentFromCategory($this->modifier['category'])) {
                    $weblogname = $PIVOTX['weblogs']->getCurrent();
                } else {
                    
                    // If the category isn't explicitly published in a weblog, set it to
                    // the correct weblog.
                    $PIVOTX['weblogs']->setCurrent();
                    
                }
                
            }
            
        } else {
            // Get the first weblog..
            $weblogname = $PIVOTX['weblogs']->getCurrent();
        }        

        // The wanted weblog has been determined - get one with the work.

        // Set the correct language (in case it has changed from render.php).
        $language = $PIVOTX['weblogs']->get('','language');
        $PIVOTX['languages']->switchLanguage($language);
        $PIVOTX['locale']->init();
        
        // Set the weblogname in the template
        $this->modifier['weblog'] = $weblogname;
        $PIVOTX['template']->assign('weblogname', $weblogname);

        if (!empty($this->modifier['archive']) || isset($this->modifier['offset'])) {
            $template = $PIVOTX['weblogs']->get('', 'archivepage_template');
        } else {
            $template = $PIVOTX['weblogs']->get('', 'front_template');
        }

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }

        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('front');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));


        // If category or weblog doesn't exist, switch to the entry template 
        // and display an error message. (This is a hack until we get a proper 
        // 404 page per weblog.)
        if ($not_found) {
            header('HTTP/1.1 404 Not Found');
            $template = $PIVOTX['weblogs']->get('', 'entry_template');
            if ($not_found == 'weblog') {
                $title = __('Weblog not found (404 error)');
            } elseif ($not_found == 'category') {
                $title = __('Category not found (404 error)');
            } else { 
                $title = __('Not found (404 error)');
            }
            $entry=array(
                'title' => $title,
                'introduction' => '<p>' . __('We didn\'t find what you were looking for.') . ' ' . 
                    __('Please visit the frontpage by clicking the main title/logo.') . '</p>',
            );
            // Set the entry in $smarty as an array, as well as separate variables.
            $PIVOTX['template']->assign('entry', $entry);
            foreach($entry as $key=>$value) {
                $PIVOTX['template']->assign($entry, $value);
            }
        }

        // Render and show the template.
        $this->parseTemplate($template);

    }


    /**
     * Render an Entry, using the template as was set in the config.
     *
     * @see $Parser::render
     */
    function renderEntry() {
        global $PIVOTX;

        // The type of page we're rendering
        $this->modifier['pagetype'] = 'entry';
        $PIVOTX['template']->assign('pagetype', 'entry');

        // Execute a hook, if present.
        $PIVOTX['extensions']->executeHook('before_parse', $this->modifier );

        // If we're previewing, we need to set the posted values as the entry,
        // otherwise get an entry from the DB.
        if (!empty($_GET['previewentry'])) {

            // Get the entry from posted content.
            $entry = sanitizePostedEntry($entry);

            // Also set it in the DB object (this is required for if we're
            // going to use the current entry to get others. In 'previousentry'
            // or 'nextentry' for instance.
            $PIVOTX['db']->set_entry($entry);

        } else {

            // Get the entry from the DB..
            $entry = $PIVOTX['db']->read_entry($this->modifier['uri'], $this->modifier['date']);

        }

        if ( empty($entry['code']) && empty($entry['uid']) && empty($_GET['previewentry']) ) {
            // We try to 'guess' an entry..
            $entry = $PIVOTX['db']->guess_entry($this->modifier['uri'], $this->modifier['date']);
            
            // If we did find an (old) entry, do a 301 redirect.
            if ( !empty($entry['uid']) && !empty($entry['link']) ) {
                header("HTTP/1.1 301 Moved Permanently" ); 
                header("location: " . $entry['link']);
                die();
            }
            
            // TODO: If we get here, we need to show the 404 page.
            
        }

        // For entries that aren't previewed we check if it's published and
        // whether it's displayed with the correct URL.(This is foolproof
        // since $_GET['previewentry'] is only set if logged in.)
        if (!isset($_GET['previewentry'])) {

            // If the site uses mod_rewrite, crufty URLs should redirect (to avoid duplicate content).
            if (($PIVOTX['config']->get('mod_rewrite') > 0) && !isset($_GET['rewrite'])) {
                header("HTTP/1.1 301 Moved Permanently" ); 
                header("location: " . $entry['link']);
                die();
            }

            // Redirect people that click a trackback link back to the entry.
            $trackback = get_default($PIVOTX['config']->get('localised_trackback_name'), "trackback");
            if (isset($_GET[$trackback])) {
                header("HTTP/1.1 301 Moved Permanently" ); 
                header("location: " . $entry['link']);
            }
 
            // If the entry isn't published yet, we shouldn't show it.
            // TODO: redirect people to the 404 page instead of showing an ugly 404 page.
            if ($entry['status']!='publish' && !isset($_GET['previewentry'])) {
                header('HTTP/1.1 404 Not Found');
                $entry=array(
                    'title' => __('Entry not found (404 error)'),
                    'introduction' => '<p>' . __('The entry isn\'t published or doesn\'t exist.') . ' ' . 
                    __('Please visit the frontpage by clicking the main title/logo.') . '</p>',
                    'comments' => array(),
                    'allow_comments' => false
                );
                $PIVOTX['db']->entry = $entry;
            }

        }

        // Here we convert the &quot; to ", if necessary, but only inside [[ tags ]]
        // Shouldn't we move this to pages_sql.php or pages_flat.php?
        $entry['introduction'] = preg_replace_callback( '/\\[\[(.*)\]\]/ui', "fixquotescallback", $entry['introduction']);
        $entry['body'] = preg_replace_callback( '/\\[\[(.*)\]\]/ui', "fixquotescallback", $entry['body']);

        // Set the 'code' we can use to invalidate this entry from cache.
        $this->code = "e".$entry['uid']."_";

        // Set the entry in $smarty as an array, as well as separate variables.
        $PIVOTX['template']->assign('entry', $entry);
        foreach($entry as $key=>$value) {
            $PIVOTX['template']->assign($entry, $value);
        }

        // Set the correct weblog..
        $PIVOTX['weblogs']->setCurrentFromCategory($entry['category']);

        // .. and get the entrypage template for it..
        $template = $PIVOTX['weblogs']->get('', 'entry_template');

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }

        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('entry');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));

        // Render and show the template.
        $this->parseTemplate($template);

    }



    /**
     * Render a Tag page, using the template as was set in the config.
     *
     * @see $Parser::render
     */
    function renderTag() {
        global $PIVOTX;

        // Execute a hook, if present.
        $PIVOTX['extensions']->executeHook('before_parse', $tag);

        // The type of page we're rendering
        $this->modifier['pagetype'] = 'tag';
        $PIVOTX['template']->assign('pagetype', 'tag');

        // Get the things we were searching for..
    
        $content = printTag($this->modifier['uri']);

        $template = $PIVOTX['weblogs']->get('', 'extra_template');

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }

        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('search');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));

        // Set the 'content' in smarty..
        $PIVOTX['template']->assign('content', $content);


        // Render and show the template.
        $this->parseTemplate($template);
               

    }



    /**
     * Render a Search page, using the template as was set in the config.
     *
     * @see $Parser::render
     */
    function renderSearch() {
        global $PIVOTX;

        // Execute a hook, if present.
        $dummy = array(); // because we pass by reference, PHP complains if the parameter is not a variable..
        $PIVOTX['extensions']->executeHook('before_parse', $dummy );

        // The type of page we're rendering
        $this->modifier['pagetype'] = 'search';
        $PIVOTX['template']->assign('pagetype', 'search');

        // Get the things we were searching for..

        $content = "\n<div class='pivotx-search-result'>\n";
        $content .= searchResult();
        $content .= "<!-- Search took ".timetaken() . " seconds -->\n";
        $content .= "</div>\n";

        $template = $PIVOTX['weblogs']->get('', 'extra_template');

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }
        
        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('search');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));

        // Set the 'content' in smarty..
        $PIVOTX['template']->assign('content', $content);


        // Render and show the template.
        $this->parseTemplate($template);
    }



    /**
     * Render a 'Special' page, using the template as was set in the config.
     * for now, the only 'special' page is the tagcloud overview..
     *
     * @see $Parser::render
     */
    function renderSpecial() {
        global $PIVOTX;

        // Execute a hook, if present.
        $dummy = array(); // because we pass by reference, PHP complains if the parameter is not a variable..
        $PIVOTX['extensions']->executeHook('before_parse', $dummy );

        if ($this->modifier['uri'] == "tagpage") {

            // The type of page we're rendering
            $this->modifier['pagetype'] = 'tagpage';
            $PIVOTX['template']->assign('pagetype', 'tagpage');

            $content = "<h2>" . __('Tag cloud for') . " " . $PIVOTX['config']->get('sitename') . "</h2>";
            $content .= "<p>" . __("This page shows the global Tag Cloud for this website. Tags that are used more often " .
                "are shown in a larger font, so this Cloud gives you a quick overview of what's happening on the site. Click ".
                "on one of the Tags to go to an overview page that shows all entries and pages that are related to the Tag, ".
                "as well as other relevant information.") ."</p>";
            $content .= "\n<div class='pivot-tagpage'>\n";
            $content .= smarty_tagcloud( array('amount'=>1000, 'minsize'=>10, 'maxsize'=>32));
            $content .= "</div>\n";
    
               
        } elseif ($this->modifier['uri'] == "visitorpage") {

            // The type of page we're rendering
            $this->modifier['pagetype'] = 'visitorpage';
            $PIVOTX['template']->assign('pagetype', 'visitorpage');

            require_once(dirname(__FILE__)."/module_userreg.php");
            $visitors = new Visitors();
            $content = $visitors->getPage();

        } elseif ($this->modifier['uri'] == "rsd") {
            
            $homepagelink = $PIVOTX['paths']['host'] . $PIVOTX['weblogs']->get('', 'link');

            $rsd = '<?xml version="1.0" ?' . '>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
    <service>
        <engineName>PivotX</engineName>
        <engineLink>http://pivotx.net/</engineLink>
        <homePageLink>'.$homepagelink.'</homePageLink>
        <apis>
            <api name="MetaWeblog" preferred="true"
                apiLink="'.$PIVOTX['paths']['host'].$PIVOTX['paths']['pivotx_url'].'metaweblog.php"
                blogID="'.$PIVOTX['weblogs']->getCurrent().'" />
        </apis>
    </service>
</rsd>';
            header('Content-Type: application/rsd+xml; charset=utf-8');
            echo $rsd;

            return;

        } else {

            // The type of page we're rendering
            $this->modifier['pagetype'] = 'special';
            $PIVOTX['template']->assign('pagetype', 'special');

    
            $this->html = "This functionality (special pages) is not yet implemented.";

            return;
            
        }

        // Only known special pages get here.

        $template = $PIVOTX['weblogs']->get('', 'extra_template');

        // Perhaps override the template, if we're allowed to do so.
        if (!empty($this->modifier['template']) && ($PIVOTX['config']->get('allow_template_override')==1) ) {
            $template = $this->modifier['template'];
        }
        
        // If the template isn't set, or doesn't exist..
        if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
            // .. we guesstimate a template, and show that..
            $template = templateGuess('search');
        }

        // We know what theme we're in, because of the used template.
        $PIVOTX['template']->assign('themename', dirname($template));

        // Set the 'content' in smarty..
        $PIVOTX['template']->assign('content', $content);

        // Render and show the template.
        $this->parseTemplate($template);
 
    }



    /**
     * Render a 'Feed' page, using our own templates.
     *
     * @see $Parser::render
     */
    function renderFeed() {
        global $build, $PIVOTX;

        // Execute a hook, if present.
        // $dummy = array(); // because we pass by reference, PHP complains if the parameter is not a variable..
        // $PIVOTX['extensions']->executeHook('before_feedparse', $dummy );

        if ($this->modifier['feedcontent'] == 'entries') {
            $template = 'feed_%type%_template.xml';
        } else if ($this->modifier['feedcontent'] == 'comments') {
            $template = 'feed_comments_%type%_template.xml';
        }

        if ($this->modifier['feedtype']=="rss") {
            $template = str_replace('%type%','rss',$template);
            $mime_type = "application/rss+xml";
            $link_self = makeFeedLink("rss");
        } else {
            $template = str_replace('%type%','atom',$template);
            $mime_type = "application/atom+xml";
            $link_self = makeFeedLink("atom");
        }

        $preamble = $this->_feedTemplate($template,'head');
        $feed_item = $this->_feedTemplate($template,'item');
        $footer = $this->_feedTemplate($template,'footer');

        $error = __('Feed error - selected %s doesn\'t exists!');
        $error_info = __('The %s "%s" given by the "%s" parameter in the URL, doesn\'t exist');
        
        // Try to set current weblog intelligently.
        if (isset($this->modifier['weblog'])) {
            if (!$PIVOTX['weblogs']->setCurrent($this->modifier['weblog'])) {
                renderErrorpage(sprintf($error,__('weblog')),
                    sprintf($error_info,__('weblog'),htmlspecialchars($this->modifier['weblog']),'w'));
            }
        } else if (isset($this->modifier['category'])) {
            if (!$PIVOTX['categories']->isCategory($this->modifier['category'])) {
                renderErrorpage(sprintf($error,__('category')),
                    sprintf($error_info,__('category'),htmlspecialchars($this->modifier['category']),'c'));
            }
            list($thisweblog, $dummy) = $PIVOTX['weblogs']->getWeblogsWithCat($this->modifier['category']);
            $PIVOTX['weblogs']->setCurrent($thisweblog);
        } else if (isset($this->modifier['entry'])) {
            $entry = $PIVOTX['db']->read_entry($this->modifier['entry']);
            if (empty($entry) || ($entry['uid'] == '')) {
                renderErrorpage(sprintf($error,__('entry')),
                    sprintf($error_info,__('entry'),htmlspecialchars($this->modifier['entry']),'e'));
            }
            list($thisweblog, $dummy) = $PIVOTX['weblogs']->getWeblogsWithCat($entry['category']);
            $PIVOTX['weblogs']->setCurrent($thisweblog);
        } else {
            $PIVOTX['weblogs']->setCurrent();
        }

        $thisweblog = $PIVOTX['weblogs']->getWeblog();
        // tally up the categories and number of entries displayed in the
        // current weblog ..
        $thiscategories = array();
        $thisamount = 0;

        foreach($thisweblog['sub_weblog'] as $sub_weblog) {
            // Make sure the categories are in an array..
            if (!is_array($sub_weblog['categories'])) { $sub_weblog['categories'] = array($sub_weblog['categories']); }
            
            $thiscategories = array_merge($thiscategories, $sub_weblog['categories']);
            $thisamount += $sub_weblog['num_entries'];
        }

        // Check what content to display
        if ($this->modifier['feedcontent'] == 'entries') {
            if (isset($this->modifier['category'])) {
                $thiscategories = array($this->modifier['category']);
            }
            if (isset($this->modifier['number'])) {
                $thisamount = $this->modifier['number'];
            }
        } else if ($this->modifier['feedcontent'] == 'comments') {
            $thisamount = 10;
            if (isset($this->modifier['number'])) {
                $thisamount = $this->modifier['number'];
            }
        } else {
            die('This can not happen');
        }


        /**
         * First we'll make the head section of the feed..
         *
         */

        if (strlen($PIVOTX['weblogs']->get('', 'rss_link'))>2) {
            $link= trim($PIVOTX['weblogs']->get('', 'rss_link'));
        } else {
            // determine the value ourselves..
            $link= gethost() . $PIVOTX['paths']['site_url'];
        }


        if (strlen($PIVOTX['weblogs']->get('', 'rss_img'))>2) {
            $image = trim($PIVOTX['weblogs']->get('', 'rss_img'));
        } else {
            // if no image is set, we will also have to remove the <image> .. </image>
            // part from the feed.. Bit hackish, but it works.
            $image= "";
            $preamble = preg_replace("/<image>(.*)<\/image>/msi", "", $preamble);
        }

        // Get the first user, for in the heading of the atom feed.
        $userdata = $PIVOTX['users']->getUsers();
        $userdata = current($userdata);

        $replace = array(
            "%sitename%"      => $PIVOTX['config']->get('sitename'),
            "%title%"         => str_replace("&", "&amp;", $PIVOTX['weblogs']->get('', 'name')),
            "%sitename_safe%" => strtolower(str_replace("_", "", safe_string($PIVOTX['config']->get('sitename'), TRUE))),
            "%title_safe%"    => str_replace("_", "", safe_string($PIVOTX['weblogs']->get('', 'name'), TRUE)),
            "%link%"          => $link,
            "%link_self%"     => $link_self,
            "%description%"   => str_replace("&", "&amp;", $PIVOTX['weblogs']->get('', 'payoff')),
            "%author%"        => $userdata['username'],
            "%admin-email%"   => $userdata['email'],
            "%admin-nick%"    => $userdata['nickname'],
            "%year%"          => date("Y"),
            "%date%"          => date("Y-m-d\TH:i:s") . $this->_rssOffset(),
            "%date_rfc%"      => get_rfcdate(mktime()),
            "%genagent%"      => "http://www.pivotx.net/?ver=".urlencode(strip_tags($build)),
            "%version%"       => strip_tags($build),
            "%lang%"          => smarty_lang(),
            "%image%"         => $image
        );

        // Execute the 'feed_head' hook, if present.
        $PIVOTX['extensions']->executeHook('feed_head', $replace );                

        $feed = str_replace(array_keys($replace), array_values($replace), $preamble);

        /**
         * Then we'll add the feed items.
         */
        if ($this->modifier['feedcontent'] == 'entries') {
            
            $entries = $PIVOTX['db']->read_entries( array('show'=>$thisamount,
                'cats'=>$thiscategories, 'status'=>'publish', 'order'=>'desc'));
            $feed .= $this->_renderFeedEntries($feed_item,$entries);
            
        } else {
            
            if (isset($this->modifier['entry'])) {
                // Comment feed for a single entry..
                $feed .= $this->_renderFeedComments($feed_item, $thisamount, array_reverse($entry['comments']));
            } else {
                // Comment feed for a weblog.
                $cats = $PIVOTX['weblogs']->getCategories();
            
                $comments = $PIVOTX['db']->read_latestcomments(array(
                        'cats'=>$cats,
                        'count'=>$thisamount,
                        'moderated'=>1
                    ));
                
                $feed .= $this->_renderFeedComments($feed_item, $thisamount, $comments);
            }
            
        }


        /**
         * And last, but certainly not least, we add a footer to the feed, and output it.
         */

        $feed .= $footer;

        header('Content-Type: ' . $mime_type .'; charset=utf-8');

        // Execute the 'feed_finish' hook, if present.
        $PIVOTX['extensions']->executeHook('feed_finish', $feed );                

        echo $feed;

    }

    /**
     * The function that does the actual rendering of the smarty template
     *
     * @param string $template
     */
    function parseTemplate($template) {
        global $PIVOTX, $build, $version, $codename;

        $this->maintemplate = $template;

        // Extra security measures for when we're rendering 'frontside' pages:
        $PIVOTX['template']->security = true;    
        if ($PIVOTX['config']->get('allow_php_in_templates')==1) {
            $PIVOTX['template']->security_settings['PHP_TAGS'] = true;
        }

        $allowedfunctions = explode(',', "addslashes,trim,ltrim,rtrim,strlen,date," .
            "substr,strpos,md5,nl2br,strstr,strtoupper,strtolower,ucfirst,ucwords," .
            "count,empty,is_array,in_array,is_int,is_float,is_integer,is_numeric," .
            "is_string,serialize,unserialize,isset,sizeof,true,false,stripslashes," .
            "encode_text,safe_string,htmlentities,htmlspecialchars,html_entity_decode,trimtext,round");
        $PIVOTX['template']->security_settings['IF_FUNCS'] = $allowedfunctions;
        $PIVOTX['template']->security_settings['MODIFIER_FUNCS'] = $allowedfunctions;
        
        // Check if we use caching..
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->caching = true;
            $PIVOTX['template']->compile_check = true;
            $PIVOTX['template']->force_compile = false;
            
            $code = get_default($this->code, "");
            
            $cachekey = "tpl_".$code.substr(md5($template.','.implode(',',$this->modifier)),0,10);
    
            // Now, let's see if the page we want is already in the cache..
            if($PIVOTX['template']->is_cached($template, $cachekey)) {
                // It is! We can get that, and return to the calling function..
                
                // But first, we check for cache_before_read hooks..
                $PIVOTX['extensions']->executeHook('cache_before_read', $template);
                
                $this->html = $PIVOTX['template']->fetch($template, $cachekey);
                
                // Before we return, we check for cache_after_read hooks..
                $PIVOTX['extensions']->executeHook('cache_after_read', $this->html);
                
                return;
            
            } else {
                
                // Before we continue, we check for cache_missed_read hooks..
                $PIVOTX['extensions']->executeHook('cache_missed_read', $template);
                
            }
            
        } else {
            $cachekey = "";
        }

        // If we've set the hidden config option for 'always jquery', add the hook here:
        if ($PIVOTX['config']->get('always_jquery') == 1) {
            $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');
        }

        // Add a favicon to the page, PiovtX or user configured, unless it's set 
        // to display nothing (by using '0' as user configured favicon)
        $favicon_html = "\t<link rel=\"shortcut icon\" href=\"%s\" />\n";
        $favicon = $PIVOTX['config']->get('favicon');
        if ($favicon == '0') {
            $favicon_html = '';
        } else {
            if ($favicon == '') {
                $favicon = $PIVOTX['paths']['pivotx_url'] ."pics/favicon.ico";
            }
            $favicon_html = sprintf($favicon_html,$favicon);
        }

        // Add a hook to insert the generator meta tag and possibly a favicon link
        $PIVOTX['extensions']->addHook(
            'after_parse',
            'insert_before_close_head',
            "\t<meta name=\"generator\" content=\"PivotX\" /><!-- version: " . strip_tags($build) . " -->\n" .
            $favicon_html
            );
        
        // Output the canonical link. See:
        // http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html
        if ($PIVOTX['config']->get('dont_add_canonical')==0) {
        
            // If we're at the site's root, regardless of _what_ page or blog it is, 
            // we always return the site url..
            if( (($this->modifier['action']=="page") && ($PIVOTX['config']->get('root') == "p:".$this->modifier['uri'])) ||
                (($this->modifier['action']=="weblog") && ($PIVOTX['config']->get('root') == "w:".$this->modifier['uri'])) || 
		(($this->modifier['uri']=="") && ($PIVOTX['config']->get('root') == "")) ) {
                $link = "";
                // Also set $modifier.home, so we can check if we're at the homepage from the templates.
                $this->modifier['home'] = true;
            } else {
                $link = smarty_link( array('hrefonly'=>true), $PIVOTX['template']);
            }
        
            // Set the canonical link..
            $canonical = sprintf("\t<link rel=\"canonical\" href=\"%s%s\" />\n",
                    $PIVOTX['paths']['canonical_host'],              
                    (empty($link) ? $PIVOTX['paths']['site_url'] : $link)
                );
                
            $PIVOTX['extensions']->addHook(
                'after_parse',
                'insert_before_close_head',
                $canonical
                );
        }

        // Add a hook to insert the scheduler. Unless the hidden configuration option
        // 'dont_run_scheduler' is set.
        if ($PIVOTX['config']->get('dont_run_scheduler')==0) {
            $PIVOTX['extensions']->addHook(
                'after_parse',
                'insert_before_close_body',
                "\t<div class='scheduler-wrapper'><img src='". $PIVOTX['paths']['pivotx_url']."scheduler.php' alt='' width='0' height='0' /></div>\n"
                );
        }

        // If we've enabled the XML Feeds for this weblog, insert the auto-discovery tags..
        if ($PIVOTX['weblogs']->get('', 'rss')==1) {

            $autodiscovery = sprintf("\t<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%s (RSS Feed)\" href=\"%s\" />\n",
                encode_text($PIVOTX['weblogs']->get('', 'name')), makeFeedLink("rss") );

            if ($PIVOTX['config']->get('feed_posts_only')!=1) {
                $autodiscovery .= sprintf("\t<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%s (RSS Feed %s)\" href=\"%s\" />\n",
                   encode_text($PIVOTX['weblogs']->get('', 'name')), __("for comments"), makeFeedLink("rss", "comm=1") );
            }
            
            $autodiscovery .= sprintf("\t<link rel=\"alternate\" type=\"application/atom+xml\" title=\"%s (Atom Feed)\" href=\"%s\" />\n",
                encode_text($PIVOTX['weblogs']->get('', 'name')), makeFeedLink("atom") );

            if ($PIVOTX['config']->get('feed_posts_only')!=1) {
                $autodiscovery .= sprintf("\t<link rel=\"alternate\" type=\"application/atom+xml\" title=\"%s (Atom Feed %s)\" href=\"%s\" />\n",
                    encode_text($PIVOTX['weblogs']->get('', 'name')), __("for comments"), makeFeedLink("atom", "comm=1") );
            }

            // Add a hook to insert RSS and ATOM autodiscovery-tag
            $PIVOTX['extensions']->addHook(
                'after_parse',
                'insert_before_close_head',
                $autodiscovery
                );

        }

        // If we've enabled XML-RPC / the MetaWeblog API, insert the auto-discovery tags...
        if ($PIVOTX['config']->get('xmlrpc')==1) {

            $autodiscovery = sprintf("\t<link rel=\"EditURI\" type=\"application/rsd+xml\" title=\"RSD\" href=\"%s\" />\n",
                makeRSDLink());

            // Add a hook to insert XML-RPC / the MetaWeblog API autodiscovery-tag
            $PIVOTX['extensions']->addHook(
                'after_parse',
                'insert_before_close_head',
                $autodiscovery
                );

        }

        // If we've enabled (non-hardened) trackback, insert the auto-discovery tags...
        if ($PIVOTX['config']->get('trackbacks')==1 && ($PIVOTX['config']->get('hardened_trackback') != 1)) {

            $autodiscovery = <<<EOM
<!-- <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
rdf:about="%url%"
dc:identifier="%url%"
dc:title="%title%"
trackback:ping="%tb-url%" />
</rdf:RDF> -->
EOM;

            $url = $PIVOTX['paths']['host'] .  makeFilelink($PIVOTX['db']->entry['code'], '','');
            if ($PIVOTX['config']->get('mod_rewrite')==0) {
                $tb_url = $url . '&amp;trackback';
            } else {
                $tb_url = $url . '/trackback/';
            }
            $autodiscovery = str_replace("%url%", $url, $autodiscovery);
            $autodiscovery = str_replace("%title%", $PIVOTX['db']->entry['title'], $autodiscovery);
            $autodiscovery = str_replace("%tb-url%", $tb_url, $autodiscovery);

            // Add a hook to insert XML-RPC / the MetaWeblog API autodiscovery-tag
            $PIVOTX['extensions']->addHook(
                'after_parse',
                'insert_before_close_head',
                $autodiscovery
                );

        }


        // Assign some stuff to $smarty, so it's accessible from the templates.
        $PIVOTX['template']->assign('build', $build);
        $PIVOTX['template']->assign('version', $version);        
        $PIVOTX['template']->assign('codename', $codename);        
        $PIVOTX['template']->assign('config', $PIVOTX['config']->getConfigArray());
        $PIVOTX['template']->assign('weblogs', $PIVOTX['weblogs']->getWeblogs() );
        $PIVOTX['template']->assign('paths', $PIVOTX['paths']);
        $PIVOTX['template']->assign('modifier', $this->modifier);
        $PIVOTX['template']->assign('timetaken', timetaken() );
        $PIVOTX['template']->assign('memtaken', getMem() );
        $PIVOTX['template']->assign('query_count', $GLOBALS['query_count']);
        
        // If we've set the hidden config option for 'set_request_variables', set them..
        if ($PIVOTX['config']->get('set_request_variables') == 1) {
            $PIVOTX['template']->assign('get', $_GET );
            $PIVOTX['template']->assign('post', $_POST );
            $PIVOTX['template']->assign('request', $_REQUEST );
            $PIVOTX['template']->assign('server', $_SERVER );
            $PIVOTX['template']->assign('session', $_SESSION);
        }

        // Add the 'base part' of the path to the smarty variables as well
        $PIVOTX['template']->assign('templatedir', dirname($template));

        if (file_exists($PIVOTX['paths']['templates_path'].$template)) {

            // Execute a hook, if present.
            $PIVOTX['extensions']->executeHook('during_parse', $template);

        } else {

            // hmm, template doesn't exist, so we set it to our '404' template..
            $template = "";

            // Execute a hook, if present.
            $PIVOTX['extensions']->executeHook('during_parse', $template);

            if ($template=="") {
                $template = "404.html"; // TODO: what will be our 404 template?
            }

        }

        if($PIVOTX['config']->get('smarty_cache')){
            // Before we continue, we check for cache_before_write hooks..
            $PIVOTX['extensions']->executeHook('cache_before_write', $template);
        }

        
        $this->html = $PIVOTX['template']->fetch($template, $cachekey);

        // We're going to something really, really stupid here. If we've enabled
        // caching, and we've just written to the cache, we immediately get
        // the page from the cache again, because the copy that's in memory does
        // not have the HTML that's updated with our hooks. Thankfully this step
        // is really, really fast, because we use caching. :-)
        if($PIVOTX['config']->get('smarty_cache')){
            $this->html = $PIVOTX['template']->fetch($template, $cachekey);
            
            // Before we continue, we check for cache_after_write hooks..
            $PIVOTX['extensions']->executeHook('cache_after_write', $this->html);
            
        }

    }


    /**
     * Output the compiled HTML to the browser.
     *
     * We also takes this as an opportunity to add the 'generated by' meta-tag
     * to the header, as well as the autodiscovery links for the XML Feeds.
     *
     */
    function output() {
        global $build, $PIVOTX;

        $html = $this->html;

        // If we don't use caching, we'll need to execute the after_parse hooks
        // here, and perhaps minify the JS and CSS files.
        if(!$PIVOTX['config']->get('smarty_cache')) {
            $PIVOTX['extensions']->executeHook('after_parse', $html);
            
            // If minify_frontend is enabled, we compress our output here.
            if ($PIVOTX['config']->get('minify_frontend')) {
                $minify = new Minify($html);
                $html = $minify->minifyURLS();
            }
            
        }

        // If debug is enabled, we add a line that states how long it took to render
        // the page, how many queries were done (if we're using mysql) and what template
        // was used.
        if ($PIVOTX['config']->get('debug')==1) {

            if ($PIVOTX['db']->db_type == "sql") {
                $queries = sprintf("SQL, %s queries", $GLOBALS['query_count']);
            } else {
                $queries = "Flat Files";
            }
            
            if (!$PIVOTX['config']->get('debug_visible')) {
                $format = "\n<!-- %s sec. - %s - tpl: %s - modifier: %s -->\n";
            } else {
                $format = "\n<p><small>[-- %s sec. - %s - tpl: %s - modifier: %s  --]</small></p>\n";
            }
            
            $modifier = array();
            foreach($this->modifier as $key => $value){
                $modifier[] = sprintf('%s->%s', $key, get_default($value, "'..'"));
            }
            
            $debugcode = sprintf($format, timetaken('int'), $queries, $this->maintemplate, implode(", ", $modifier) );

            // If $query_log is filled, output the executed queries..
            if (count($GLOBALS['query_log'])>0) {
                sort($GLOBALS['query_log']);
                debug_printr($GLOBALS['query_log']);
            }
            
            if ($PIVOTX['config']->get('debug_cachestats')) {
                debug_printr($PIVOTX['cache']->stats());
            }
            
            $html = str_replace('</body>', $debugcode.'</body>', $html);
            
        }

        // If the site doesn't link back to pivotx.net, we add a 'PivotX feels unloved'
        // comment to the HTML. 
        if (!preg_match('#href=[\'"](http://(www.)?pivotx.net)#i', $html)) {
            $unloved = "\n<!--  PivotX feels unloved.. :-(  -->\n";
            $html = str_replace('</body>', $unloved.'</body>', $html);
        }

        // Process the last hook, right before we output the HTML to the browser.
        $PIVOTX['extensions']->executeHook('before_output', $html);

        // Output the results to the browser..
        header('Content-Type: text/html; charset=utf-8'); 
        echo $html;

    }




    // =============================================
    // the functions below are used for outputting
    // the weblog as RSS.
    // =============================================

    /**
     * Creates a feed of entries.
     *
     * @param string $feed_template
     * @param array $entries
     * @return string
     */
    function _renderFeedEntries($feed_template,$entries) {
        global $PIVOTX;

        // Getting category display names
        $categories = $PIVOTX['categories']->getCategories();
        $categories = make_valuepairs($categories, 'name', 'display');

        // Loop through the entries..
        foreach ($entries as $entry) {

            // Get the full entry..
            $entry = $PIVOTX['db']->read_entry($entry['code']);

            $link = makeFileURL($entry['uid'], "", "");

            $title = trim(unentify($entry['title']));
            $subtitle = trim(unentify($entry['subtitle']));

            // parse fields and remove scripting from the feed. Script in feed is bad..
            $introduction = parse_intro_or_body( $entry['introduction'], false, $entry['convert_lb'] );
            $introduction = $this->_cleanFeedText($introduction);

            $body = parse_intro_or_body( $entry['body'], false, $entry['convert_lb'] );
            $body = $this->_cleanFeedText($body);

            $tag =  safe_string($PIVOTX['config']->get('sitename'), TRUE) .
                ",". date("Y") . ":" .  safe_string($PIVOTX['weblogs']->get('', 'name'), TRUE)."." . $entry['uid'];
            $tag = str_replace("_", "",strtolower($tag));

            $date = format_date( $entry['date'], "%year%-%month%-%day%T%hour24%:%minute%:00") . $this->_rssOffset();
            $date_rfc = format_date( $entry['date'], "%english_dname%, %day% %english_monname% %year% %hour24%:%minute%:00 ") . $this->_rssOffset("rfc822");
            if ($PIVOTX['db']->entry['edit_date']!="") {
                $edit_date = format_date( $entry['edit_date'], "%year%-%month%-%day%T%hour24%:%minute%:00"). $this->_rssOffset();
            } else {
                // if the entry was never edited, use the entrydate
                $edit_date = $date;
            }

            $summary = unentify(strip_tags($introduction));
            $summary = trim(str_replace("&", "&amp;", str_replace("&nbsp;"," ", $summary)));

            // Set content (Atom 1.0) and description (RSS 2.0) according to completeness settings
            if ( $PIVOTX['weblogs']->get('', 'rss_full')==0) {
                // don't put anything in the content.
                $content="";
                $description = trim($introduction);
                if (strlen($body)>5) {
                    $description .= smarty_more();
                    $summary .= ' ...';
                }
            } else {
                // put the introduction and body in the content..
                $content = trim(str_replace("&nbsp;"," ", ($introduction.$body)));
                $description = trim($introduction.$body);
            }

            // Handling viatitle special to avoid validation errors
            if (!empty($entry['viatitle'])) {
                $viatitle = 'title="'.addslashes($entry['viatitle']).'"';
            } else {
                $viatitle = "";
            }

            // Getting user information..
            $user = $PIVOTX['users']->getUser($entry['user']);
            if (!$user) {
                $user = array('username'=>$entry['user'], 'email'=>'', 'nickname'=>$entry['user']);
            }

            // Setting the category display names
            $cat_display=array();
            foreach ($entry['category'] as $cat) {
                if (!empty($categories[$cat])) {
                    $cat_display[] = $categories[$cat];
                }
            }
            
            $replace = array(
                "%title%"         => htmlspecialchars(strip_tags($title)),
                "%subtitle%"     => htmlspecialchars(strip_tags($subtitle)),
                "%link%"         => $link,
                "%description%"  => RelativeToAbsoluteURLS($description),
                "%summary%"      => RelativeToAbsoluteURLS($summary),
                "%author%"       => $user['username'],
                "%author-email%" => $user['email'],
                "%author-nick%"  => $user['nickname'],
                "%guid%"         => $entry['uid']."@".str_replace('http://','',$PIVOTX['paths']['canonical_host']).$PIVOTX['paths']['site_url'],
                "%date%"         => $date,
                "%edit_date%"     => $edit_date,
                "%date_rfc%"      => $date_rfc,
                "%category%"      => htmlspecialchars(implode(", ", $cat_display)),
                "%categorynames%" => htmlspecialchars(implode(", ", $entry['category'])),
                "%content%"       => RelativeToAbsoluteURLS($content),
                "%tag%"           => $tag,
                "%lang%"         => smarty_lang(),
                "%vialink%"      => $PIVOTX['db']->entry['vialink'],
                "%viatitle%"     => $viatitle
            );
            
            // Execute the 'feed_entry' hook, if present.
            $PIVOTX['extensions']->executeHook('feed_entry', $replace );                
                
            $feed .= str_replace(array_keys($replace), array_values($replace), $feed_template);

        }
        return $feed;
    }

    /**
     * Creates a feed of comments.
     *
     * @todo Do not display comments that haven't been moderated/approved.
     * @param string $feed_template
     * @param array $comment
     * @return string
     */
    function _renderFeedComments($feed_template, $amount=10, $comments) {
        global $PIVOTX;

        $i = 0;
        $feed_items = "";
        
        // Loop through the comments..
        foreach ($comments as $comment) {
            
            $tag =  safe_string($PIVOTX['config']->get('sitename'), TRUE) .
                ",". date("Y") . ":" .  safe_string($PIVOTX['weblogs']->get('', 'name'), TRUE);
            $tag .= '.entry%uid%.comment'.$i;
            $tag = str_replace("_", "",strtolower($tag));

            $date = format_date( $comment['date'], "%year%-%month%-%day%T%hour24%:%minute%:00") .
                $this->_rssOffset();
            $date_rfc = format_date( $comment['date'],
                "%english_dname%, %day% %english_monname% %year% %hour24%:%minute%:00 ") .
                $this->_rssOffset("rfc822");

            $summary = unentify(strip_tags($comment['comment']));
            $summary = trim(str_replace("&", "&amp;", str_replace("&nbsp;"," ", $summary)));
            $summary = RelativeToAbsoluteURLS($summary);
            if (strlen($summary) > 32) {
                $title = substr($summary,0,35).'...';
            } else {
                $title = $summary;
            }
            
            // Make the link..
            $id = safe_string($comment['name'], true) ."-". format_date($comment['date'],"%ye%%month%%day%%hour24%%minute%");
            $url = makeFileURL($comment['entry_uid'], '', $id);    

            $replace = array(
                "%title%"       => htmlspecialchars(strip_tags($title)),
                "%link%"        => $url,
                "%summary%"     => $summary,
                "%content%"     => $summary,
                "%description%" => $summary,
                "%author%"      => $comment['name'],
                "%guid%"        => $url,
                "%date%"        => $date,
                "%date_rfc%"    => $date_rfc,
                "%tag%"         => $tag,
                "%lang%"        => smarty_lang()
            );

            // Execute the 'feed_comment' hook, if present.
            $PIVOTX['extensions']->executeHook('feed_comment', $replace );                
                
            $item = str_replace(array_keys($replace), array_values($replace), $feed_template);

            // Handling e-mail and url separately.
            if (isemail($comment['email'])) {
                $item = str_replace('%author-email%', $comment['email'], $item);
            } else {
                $item = str_replace('<email>%author-email%</email>', '', $item);
            }
            if (isurl($comment['url'])) {
                if (strpos($comment["url"], "ttp://") < 1 ) {
                    $comment["url"]="http://".$comment["url"];
                }
                $item = str_replace('%author-link%', $comment['url'], $item);
            } else {
                $item = str_replace('<uri>%author-link%</uri>', '', $item);
            }
            
            $feed_items .= $item;

        }

        return $feed_items;
    }


    /**
     * Generates a time offset for the feeds (using the correct format).
     *
     * @param string $type
     */
    function _rssOffset($type="") {

        if ($type== "rfc822") {
            // RSS 2.0
            $format = "%02d%02d";
        } else {
            // Atom 1.0
            $format = "%02d:%02d";
        }

        $z=date("Z");

        if (!is_numeric($z)) { $z = 0; }

        $offset = ( ($z>0) ? "+" : "-" ) . sprintf($format, floor(abs($z) / 3600), floor($z % 3600)/60);

        return $offset;

    }


    /**
     * Load the feed templates.
     *
     * @param string $format What type of XML feed, currently Atom or RSS
     * @param string $whatpart Selects ead, item or footer part of the templates
     * @return string
     */
    function _feedTemplate($format, $whatpart) {
        global $feedtemplates, $PIVOTX;

        if (!isset($feedtemplates[$format])) {
            $file = implode('', file( $PIVOTX['paths']['templates_path'].$format));

            // Execute the 'feed_rss_template' or 'feed_atom_template' hook, if present.
            if (strpos($format, "rss") !== false) {
                $PIVOTX['extensions']->executeHook('feed_rss_template', $file );
            } else { // must be Atom..
                $PIVOTX['extensions']->executeHook('feed_atom_template', $file );                
            }
            
            list ($feedtemplates[$format]['head'], $feedtemplates[$format]['item'], $feedtemplates[$format]['footer']) =
                    explode("------", $file);
        }

        return $feedtemplates[$format][$whatpart];


    }


    /**
     * Cleans the text (to be inserted into feeds) for unwanted elements and
     * attributes.
     *
     * Currently only (java)script is removed.
     *
     * @param string $text
     * @return string
     */
    function _cleanFeedText($text) {
        $text = preg_replace('/onclick=([\'"])[^\1]*\1/Ui', "", $text);
        $text = preg_replace('#<script [^>]*>.*</script>#Uis',
            "<!-- script element removed -->\n", $text);
        return $text;
    }




}





/**
 * Parse a 'rewritten' url, if the site uses mod_rewrite.
 *
 * /archive/2007-01-08/name -> $rewrite = archive , $uri = 2007-01-08/name
 * /archive/2007/01/08/name -> $rewrite = archive , $uri = 2007/01/08/name
 * /page/name-of-page -> $rewrite = page , $uri = name-of-page
 * [...]
 *
 * It also supports multiple weblogs, i.e., 
 * /archive/2007-01-08/name/weblog -> $rewrite = archive , $uri = 2007-01-08/name/weblog
 * /page/name-of-page/weblog -> $rewrite = page , $uri = name-of-page/weblog
 * [...]
 *
 * This function modifies the superglobal $_GET array, so that it can
 * select which page to render, and what to render on it.
 *
 * @param string $rewrite
 * @param string $uri
 */
function parseRewrittenURL($rewrite, $uri) {
    global $PIVOTX;

    switch ($rewrite) {

        case "page":
            $parts = explode("/", $uri);
            $_GET['p'] = $parts[0];
            
            if (count($parts) > 1)  {
                // this is (should be) the weblog parameter
                $_GET['w'] = $parts[1];
                $_REQUEST['w'] = $parts[1];
            }

            break;

        case "archive":
            // Find the date part - matching 2007/01/08, 2007-01-08 or 2007-w02.
            if (preg_match('#^(\d{4}([/-]\d{2}[/-]\d{2}|-[wmy]\d{2}))#',$uri,$matches)) {
                $date = $matches[1];
                // Remove the date part (and following slash) and find 
                // additional info in the URI.
                $uri = preg_replace('#^'.$date.'/?#','',$uri);
                $parts = explode("/", $uri);
                $count = count($parts);

                if (trim($uri) == '') {
                    // We want an archive (for a given period).
                    $_GET['a'] = str_replace('/','-',$date);
                } elseif ( $count >= 2 )  {
                    // We want an entry for a given weblog.
                    $_GET['date'] = str_replace('/','-',$date);
                    $_GET['e'] = $parts[0];
                    $_GET['w'] = $parts[1];
                    $_REQUEST['w'] = $parts[1];
                } else if ( $count==1 )  {
                    // We want an archive for a given weblog or an entry.
                    if (in_array($uri,$PIVOTX['weblogs']->getWeblogNames())) {
                        $_GET['a'] = $date;
                        $_GET['w'] = $uri;
                        $_REQUEST['w'] = $uri;
                    } else {
                        $_GET['date'] = str_replace('/','-',$date);
                        $_GET['e'] = $uri;
                    }
                }
            } else {
                debug("Can't parse Rewritten url: $rewrite / $uri");
            }

            break;

        case "tag":
            $parts = explode("/", $_GET['t']);
            $count = count($parts);
            
            $_GET['t'] = $parts[0];
            
            if ($count>1) {
                // this is (should be) the weblog parameter
                $_GET['w'] = $parts[1];
                $_REQUEST['w'] = $parts[1];
            }

            break;
        case "offset":
            $parts = explode("/", $_GET['o']);
            $count = count($parts);
            
            $_GET['o'] = $parts[0];
            
            if ($count>1) {
                // this is (should be) the weblog parameter
                $_GET['w'] = $parts[1];
                $_REQUEST['w'] = $parts[1];
            }

            break;
        case "entry":
            $parts = explode("/", $_GET['e']);
            $count = count($parts);
            
            $_GET['e'] = $parts[0];
            
            if ($count>1) {
                $_GET['uri'] = $parts[1];
            }

            if ($count>2) {
                // this is (should be) the weblog parameter
                $_GET['w'] = $parts[2];
                $_REQUEST['w'] = $parts[2];                
            }

            if ($count>3) {
                $_GET['remainder'] = $parts[3];
            }

            break;

        case "search":

            // We can use both $_GET and $_POST for the searchterms.
            $_GET['q'] = get_default($_GET['q'], $_POST['q']);

            break;

        case "category":
            
            $parts = explode("/", $_GET['c']);
            $count = count($parts);
            
            $_GET['c'] = $parts[0];

            if ($count>1) {
                if (preg_match('/^\d+$/',$parts[1])) {
                    $_GET['o'] = $parts[1];
                    if ($count>2) {
                        // this is (should be) the weblog parameter
                        $_GET['w'] = $parts[2];
                        $_REQUEST['w'] = $parts[2];
                    }
                } else {
                    // this is (should be) the weblog parameter
                    $_GET['w'] = $parts[1];
                    $_REQUEST['w'] = $parts[1];
                }
            }
            
            break;

        default:
            debug("Can't parse Rewritten url: $rewrite / $uri");
            break;

    }

    // echo "<pre>\n"; print_r($_GET); echo "</pre>";

}




/**
 * Parsing string (with template tags).
 *
 * if $strip is set, we strip out all tags, except for the most common ones. 
 *
 * @param string $text
 * @param boolean $strip
 * @return string
 */
function parse_string ($text, $strip=false) {
    global $PIVOTX;

    $output = trim($text);

    if (strpos($text,'[[') !== false) {
        // Parse [[tags]] in string if present.
        // Use $key so a unique name is made, to prevent strange results
        // popping up when we're using caching.
        $cachekey = "tpl_".substr(md5($output),0,10);
        $PIVOTX['template']->caching = false;
        $PIVOTX['template']->custom_template[$cachekey] = $output;
        $output = $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);

        // Re-enable caching, if desired..
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->caching = true;
        }
    }

    if ($strip!=false) {
        $output = strip_tags($output,"<a><b><i><u><strong><em>");
    }

    return tidy_html($output);

}

/**
 * Parsing intro or body.
 *
 * if $strip is set, we strip out all tags, except for the most common ones. If
 * $text_processing_only is set, we only apply the text processing (textile,
 * markdown), but not the Smarty parsing. This is useful for converting between
 * one editing mode to the other
 *
 * @param string $text
 * @param boolean $strip
 * @param integer $textmode
 * @param boolean $text_processing_only
 * 
 */
function parse_intro_or_body ($text, $strip=false, $textmode=0, $text_processing_only=false) {
    global $PIVOTX;

    // Abort immediately if there is no text to parse.
    if (empty($text)) {
        return '';
    }

    $output = $text;

    // Parse [[tags]] in introduction and body..
    // Use $key so a unique name is made, to prevent strange results
    // popping up when we're using caching.
    if (!$text_processing_only) {
        $cachekey = "tpl_".substr(md5($output),0,10);
        $PIVOTX['template']->caching = false;
        $PIVOTX['template']->custom_template[$cachekey] = $output;
        $output = $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);
        
        // Re-enable caching, if desired..
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->caching = true;
        }        
    }
    
    if ($strip!=false) {
        $output = strip_tags($output,"<a><b><i><u><strong><em>");
    }

    /**
     * text processing: nl2br, Textile or Markdown/SmartyPants
     * We ensure that newlines aren't converted to br elements in script
     * blocks - currently handling PHP and JavaScript.
     * More exclusions will/can be added.
     */

    // Use the ACK (006) ASCII symbol to replace all script elements temporarily
    $output = str_replace("\x06", "", $output);
    $regexp = "#(<script[ >].*?</script>)|(<\?php\s.*?\?>)#is";
    preg_match_all($regexp, $output, $scripts);
    $output = preg_replace($regexp, "\x06", $output);

    if ($textmode==1) {
        $output = strip_trailing_space(nl2br( $output ));
    } else if ($textmode==2) {
        $output = pivotx_textile( $output );
    } else if ( ($textmode==3) || ($textmode==4) ) {
        $output = pivotx_markdown( $output, $textmode );
    }

    // Put captured scripts back into the output
    foreach ($scripts[0] as $script) {
        $output = preg_replace("/\x06/", $script, $output, 1);
    }

    // emoticons..
    if ($PIVOTX['weblogs']->get('', 'emoticons')==1) {
        $output = emoticonize( $output );
    }

    // There's a silly quirk in TinyMCE, that prevents transparent Flash. We
    // need to fix this, to make Youtube videos work properly.
    $output = str_replace("<param name=\"wmode\" value=\"\" />", "<param name=\"wmode\" value=\"transparent\" />", $output);
    $output = str_replace(" wmode=\"\" ", " wmode=\"transparent\" ", $output);

    return tidy_html($output);

}


/**
 * Unparsing intro or body: Make a futile attempt at undoing HTML markup, so the
 * entry or page can be edited with 'convert LB', 'Textile' or 'Markup', without
 * getting too much extra line- and paragraph breaks
 */
function unparse_intro_or_body ($text) {
    global $PIVOTX;

    $text = str_replace("\n", " ", $text);
    $text = str_replace("</p>", "\n\n", $text);
    $text = preg_replace("/<br( [^>]*)?>/Ui", "\n", $text);
    $text = preg_replace("/<p( [^>]*)?>/Ui", "", $text);
    
    return $text;

}

/**
 * Parsing snippets in templates
 */
function snippet_parse( $snippet_code ) {
    global $PIVOTX;

    require_once($PIVOTX['paths']['pivotx_path'].'modules/module_snippets.php');

    $snippet_code = trim($snippet_code);
    @list( $command, $para1, $para2, $para3, $para4, $para5, $para6, $para7, $para8 ) = preg_split("/:\s?(?!\/\/)/",$snippet_code,-1 );
    // Get only the parameters that are really set (to avoid killing default
    // parameters values in the snippets). Also trim parameters to avoid
    // killing default parameters values in the snippets.
    $params = array();
    for ($i=1; $i<9; $i++) {
        if (isset(${"para$i"})) {
            $params[] = trim(${"para$i"});
        } else {
            break;
        }
    }
    $insert   = '';
    $command  = str_replace( '-','_', trim($command) );
    $command  = str_replace( '/','slash',$command );
    $function = 'snippet_'.$command;

    // has the snippet already been declared?
    if( function_exists( $function )) {
        // Once again avoiding nuking default parameters values in the snippets.
        if (!empty($params)) {
            $insert = call_user_func_array($function,$params);
        } else {
            $insert = $function();
        }
    } else {
        // determine the path to the extensions..

        $extensions_path = $PIVOTX['paths']['extensions_path'];

        // do the 'extensions/snippets/ directories' exist?
        if( file_exists( realpath(  $extensions_path . 'snippets/' . $function . '.php' ))) {
            // pull in the snippet
            include_once( realpath( $extensions_path . 'snippets/' . $function . '.php' ));
            // final test
            if( function_exists( $function )) {
                // Once again avoiding nuking default parameters values in the snippets.
                if (!empty($params)) {
                    $insert = call_user_func_array($function,$params);
                } else {
                    $insert = $function();
                }
            } else {
                $insert = '<!-- snippet ='.$command.'= is not defined -->';
                debug( 'snippeterror: '.$command );
            }
        } else {
            $insert = '<!-- snippet ='.$command.'= is not defined because file doesn\'t exist -->';
            debug( 'snippeterror: '.$command );
        }
    }


    return $insert;
}


// ----------------------------------------------------
// Finally some auxillary functions.
// ----------------------------------------------------


/**
 * Removes all subweblog tags, and replaces one of the subweblogs,
 * "standard" if present else the first, with the input replacement HTML.
 *
 * @param string $template_html
 * @param string $replace_html
 * @return string
 */
function replace_subweblogs_templates($template_html,$replace_html) {
    if (preg_match_all('/\[\[(sub)?weblog:(.*)?(:[^]]*)?\]\]/siU', $template_html, $match)) {
        if (count($match[1])==1) {
            $template_html = str_replace($match[0][0], $replace_html, $template_html);
            } else {
                if (preg_match("/\[\[(sub)?weblog:standard(:[^]]*)?\]\]/siU", $template_html) > 0) {
                $template_html = preg_replace("/\[\[(sub)?weblog:standard(:[^]]*)?\]\]/siU",
                    $replace_html, $template_html);
                } else {
                    $template_html = str_replace($match[0][0], $replace_html, $template_html);
                }
                foreach ($match[0] as $name) {
                    $template_html = str_replace($name, "", $template_html);
            }
        }
    }
    return $template_html;
}

// =============================================
// the functions below are used for processing the <cms> tags
// into <html>.
// =============================================

/**
 * Process CMS tags into HTML for weblogs.
 */
function cms_tag_weblog($params, $format){
    global $current_date, $diffdate_lastformat, $PIVOTX;

    // Get the original state of the template vars, so we can reset it afterwards:
    // $template_vars = $PIVOTX['template']->get_template_vars();
    
    // Get the original state of the entry, so we can reset it afterwards:
    $old_entry = $PIVOTX['db']->entry;

    if ($params['order']=="random") {
        $order="random";
    } elseif ($params['order']=="firsttolast" || $params['order']=="asc") {
        $order="asc";
    } else {
        $order="desc";
    }

    $output="";

    // to force the 'diffdate' to start anew on each (sub)weblog..
    $diffdate_lastformat="";

    $subweblog = $PIVOTX['weblogs']->getSubweblog('', $params['name']);

    if (isset($PIVOTX['parser'])) {
        $modifier = $PIVOTX['parser']->get('modifier');
    } else {
        $modifier = array();
    }

    // safety check to prevent recursive weblogs..
    if (preg_match("/\[\[(sub)?weblog(.*)?\]\]/mUi", $format)) {
         $tag_default_orig = "<p>(You can't recursively use [weblogs]!)</p>";
    }

    // See if we should override the offset..
    if (!empty($params['offset'])) {
        $offset = intval($params['offset']);
    } else {
        $offset = intval($subweblog['offset']);
    }

    // Set the 'number of entries' that we want to show..
    $num_entries = get_default($params['showme'], $subweblog['num_entries']);

    
    // If we have an 'offset' parameter, we need to increase the offset by 'o' pages.
    // So, if 'o' is 2, and we publish 12 entries, the offset will be increased
    // by 24. If we set the 'ignorepadding' parameter, we will skip this.
    if (!empty($modifier['offset']) && empty($params['ignorepaging'])) {
        $offset += intval($num_entries * $modifier['offset']);
    }

    // See if we should override the user..
    if (!empty($params['user'])) {
        $username = safe_string($params['user'], false);
    } else if (!empty($modifier['user'])) {
        $username = safe_string($modifier['user'], false);
    } else {
        $username = "";
    }

    // If we have a 'c=' parameter, use the cats in that to display..
    // To prevent weird things from happening, we only allow changing weblogs
    // with a name like 'default', 'standard', 'main' or 'weblog'.
    // Alternatively, we check if the template specifies the categories to
    // display, like [[ weblog name='weblog' category="default, movies, music" ]]
    if ((!empty($modifier['category']) && (in_array($params['name'] , array('default', 'standard', 'main', 'weblog'))))) {
        $cats = explode(",",safe_string($modifier['category']));
        $cats = array_map("trim", $cats);
    } else if (!empty($params['category'])) {
        // if category is '*', we show _all_ categories, else only those that were passed.
        if ($params['category']=="*") {
            $cats = $PIVOTX['categories']->getCategorynames();
        } else {
            $cats = explode(",",safe_string($params['category']));
            $cats = array_map("trim", $cats);
        }
    } else {
        // else we just display the categories as they're defined in the weblog.
        $cats = $subweblog['categories'];
    }

    $entries = array();

    // If we're displaying an archive, we need to get the start and
    // end date, and fetch those entries..
    // That is, unless we sort by 'random'. In this case we shouldn't do this,
    // because it will ignore the amount, which leads to weird results.
    if ( (!empty($modifier['archive']) || !empty($params['archive'])) && ($order!="random") ) {    

        if ($modifier['archive']) {
            list($start, $end) = archivenameToDates($modifier['archive']);
        } else {
            list($start, $end) = archivenameToDates($params['archive']);
        }

        $entries = $PIVOTX['db']->read_entries( array(
            'cats' => $cats,
            'status' => 'publish',
            'order' => $order,
            'start' => $start,
            'end' => $end,
            'user' => $username
        ));

    } else {

        $entries = $PIVOTX['db']->read_entries( array(
            'show' => $num_entries,
            'offset' => $offset,
            'cats' => $cats,
            'tags' => $params['tags'],
            'status' => 'publish',
            'order' => $order,
            'orderby' => $params['orderby'],
            'user' => $username,
            'start' => $params['start'],
            'end' => $params['end'],
            'date' => $params['date'],
            'uid' => $params['uid']
        ));

    }

    $counter=1;

    // We need to parse [[tags]] in entries..
    // Use $templatekey so a unique name is made, to prevent strange results
    // popping up when we're using caching.
    $templatekey = substr(md5($format),0,10);
    $PIVOTX['template']->caching = 0;
    $PIVOTX['template']->custom_template[$templatekey] = $format;

    if (!empty($entries)) {
        
        foreach($entries as $entry) {
    
            // We set a $cachekey for each entry..
            $cachekey = "tpl_".substr(md5($entry['title'].$entry['date']),0,10);
            
            // include an anchor, if it's not set manually with the [[id_anchor]] tag
            if (strpos($format, "[[id_anchor]]")==0) {
                $entry_html = '<span id="e'.$entry['code'].'"></span>';
            } else {
                $entry_html ="";
            }
            // Assign the vars in $entry to the template engine
            foreach($entry as $key=>$value) {
                $PIVOTX['template']->assign($key, $value);
            }
            
            $PIVOTX['template']->assign('entry', $entry);
            $PIVOTX['template']->assign('first', ($counter==1) );
            $PIVOTX['template']->assign('max', count($entries) );
            $PIVOTX['template']->assign('last', ($counter==(count($entries))) );
            $PIVOTX['template']->assign('counter', $counter++);
    
            $output .= $PIVOTX['template']->fetch("db:".$templatekey, $cachekey);
    
        }        
        
    } else {
        
        // No entries match, output the 'noresult' text..
        $output = get_default($params['noresult'], "<!-- no results for this subweblog -->");
        
    }



    // Re-enable caching, if desired..
    if($PIVOTX['config']->get('smarty_cache')){
        $PIVOTX['template']->caching = true;
    }

    // Restore the original Template variables.
    //foreach($template_vars as $key=>$value) {
    //    $PIVOTX['template']->assign($key, $value);
    //}
    
    // Restore the old entry..
    $PIVOTX['db']->set_entry($old_entry);

    return $output;

}


/**
 * Makes archive link for a certain date.
 */
function makeArchiveLink($date="", $unit="", $override_weblog="") {
    global $PIVOTX;

    if ($date=="") {
        $vars = $PIVOTX['template']->get_template_vars();
        $date = $vars['date'];
    }

    if ($unit!='') {
        $archive_uri = makeArchiveName($date,'',$unit);
    } else {
        $archive_uri = makeArchiveName($date);
    }

    // Set the weblog, according to passed parameter or current weblog
    $weblog = get_default($override_weblog, $PIVOTX['weblogs']->getCurrent());
    
    $site_url = get_default($PIVOTX['weblogs']->get($weblog, 'site_url'), $PIVOTX['paths']['site_url']);
    if ($PIVOTX['config']->get('mod_rewrite') < 1) {
        $link = $site_url . '?a=' . $archive_uri;

    } else {
        $prefix = get_default($PIVOTX['config']->get('localised_archive_prefix'), "archive");
        $link = $site_url . makeURI($prefix) . "/" . $archive_uri;
    }

    // If we have more than one weblog, add the w=weblogname parameter
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            // we treat it as an extra 'folder'
            $link .= "/" . $weblog;
        } else {
            $link .= "&amp;w=" . $weblog;
        }
    }

    // Check if there's a hook set, and if so call it. This hook has no 'return' value.
    if ($PIVOTX['extensions']->hasHook('make_link#archives')) {
        $PIVOTX['extensions']->executeHook('make_link#archives', $link, 
            array('date'=>$date, 'w'=>$weblog));
    }

    return $link;

}


/**
 * Make a link to an entry, using the settings for how they should be formed.
 *
 * @param mixed $data
 * @param string $weblog
 * @param string $anchor
 * @param string $parameter
 * @param boolean $para_weblog
 */
function makeFilelink($data="", $weblog="", $anchor="comm", $parameter="", $para_weblog=false) {
    global $PIVOTX;

    // Set the weblog, if it isn't set already.
    if ($weblog=="") { $weblog=$PIVOTX['weblogs']->getCurrent(); }

    // Set $entry (and $code)
    if (empty($data)) {
        // Using current entry - the db object must exist and be set
        $template_vars = $PIVOTX['template']->get_template_vars();
        $uid = $template_vars['uid'];
    } elseif (is_array($data)) {
        // Using passed/inputed entry
        $entry = $data;
        $uid = $entry['uid'];
    } elseif (is_numeric($data)) {
        $uid = $data;

        // Using the entry with the given $code
        // If it's not the current one, we need to load it
        if (!isset($PIVOTX['db']) || ($uid != $PIVOTX['db']->entry['uid'])) {
            $fl_db = new db(FALSE);
            $fl_db->read_entry($uid);
            $entry = $fl_db->entry;
        } else {
            $entry = $PIVOTX['db']->entry;
        }
    } else {
        debug('Entry code must be an integer/numeric - no output.');
        return;
    }

    $site_url = get_default($PIVOTX['weblogs']->get($weblog, 'site_url'), $PIVOTX['paths']['site_url']);
    switch ($PIVOTX['config']->get('mod_rewrite')) {

        // Mod rewrite disabled..
        case "0":
        case "":
            $filelink = sprintf("%s?e=%s%s", $site_url, $uid, $parameter);

            break;


        // archive/2005/04/20/title_of_entry
        case "1":

            $name = $entry['uri'];

            $archiveprefix = makeURI( get_default($PIVOTX['config']->get('localised_archive_prefix'), "archive") );

            list($yr,$mo,$da,$ho,$mi) = preg_split("/[ :-]/",$entry['date']);
            $filelink = $site_url . "$archiveprefix/$yr/$mo/$da/$name";

            break;

        // archive/2005-04-20/title_of_entry
        case "2":

            $name = $entry['uri'];

            $archiveprefix = makeURI( get_default($PIVOTX['config']->get('localised_archive_prefix'), "archive") );

            list($yr,$mo,$da,$ho,$mi) = preg_split("/[ :-]/",$entry['date']);
            $filelink = $site_url . "$archiveprefix/$yr-$mo-$da/$name";

            break;

        // entry/1234
        case "3":

            $entryprefix = makeURI( get_default($PIVOTX['config']->get('localised_entry_prefix'), "entry") );

            $filelink = $site_url . "$entryprefix/$uid";

            break;

        // entry/1234/title_of_entry
        case "4":

            $name = $entry['uri'];

            $entryprefix = makeURI( get_default($PIVOTX['config']->get('localised_entry_prefix'), "entry") );

            $filelink = $site_url . "$entryprefix/$uid/$name";

            break;

        // 2005/04/20/title_of_entry
        case "5":

            $name = $entry['uri'];

            list($yr,$mo,$da,$ho,$mi) = preg_split("/[ :-]/",$entry['date']);
            $filelink = $site_url . "$yr/$mo/$da/$name";

            break;

        // 2005-04-20/title_of_entry
        case "6":

            $name = $entry['uri'];

            list($yr,$mo,$da,$ho,$mi) = preg_split("/[ :-]/",$entry['date']);
            $filelink = $site_url . "$yr-$mo-$da/$name";

            break;

    }




    // Add a weblog parameter if asked for, or if multiple weblogs
    if ( $para_weblog || para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')) {
            // we treat it as an extra 'folder'
            $filelink .= "/".para_weblog($weblog);
        } else {
            $filelink .= "&amp;w=".para_weblog($weblog);
        }
    }

    $filelink = fixPath($filelink);
    $filelink = str_replace("%1", $code, $filelink);
    $filelink = format_date("", $filelink, $entry['title']);

    if ($anchor != "") {
        $filelink .= "#".$anchor;
    }


    // Check if there's a hook set, and if so call it. This hook has no 'return' value.
    if ($PIVOTX['extensions']->hasHook('make_link#entries')) {
        $PIVOTX['extensions']->executeHook('make_link#entries', $filelink,
                array('uri'=>$entry['uri'], 'title'=>$entry['title'], 
                    'uid'=>$entry['uid'], 'date'=>$entry['date'], 'w'=>$weblog));
    }


    return $filelink;

}

/**
 * Make a URL (global link) to an entry.
 *
 * @uses makeFilelink makes the link (without protocol and host).
 *
 * @param string $code
 * @param string $weblog
 * @param string $anchor
 * @param string $parameter
 */
function makeFileURL($code="", $weblog="", $anchor="comm", $parameter="") {
    global $PIVOTX;

    $link = makeFilelink($code, $weblog, $anchor, $parameter);

    return gethost().$link;

}


/**
 * Make a link to the visitor page.
 * 
 * @param string $func
 * @return string
 */
function makeVisitorPageLink($func = '') {
    global $PIVOTX;

    if ($PIVOTX['config']->get('mod_rewrite') < 1) {
        $link = $PIVOTX['paths']['site_url']."?x=visitorpage";
    } else {
        $addon = get_default($PIVOTX['config']->get('localised_visitorpage_prefix'), "visitor");
        $link = $PIVOTX['paths']['site_url'] . makeURI($addon) . '/';
    }

    // If we have more than one weblog, add the w=weblogname parameter
    $weblog = $PIVOTX['weblogs']->getCurrent();
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            $link .= $weblog . '/';
        } else {
            $link .= "&amp;w=" . $weblog;
        }
    }

    if ($func != '') {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            $link .= '?func=' . $func;
        } else {
            $link .= "&amp;func=" . $func;
        }
    }
 
    return $link;

}

/**
 * Make a link to a page.
 *
 * @param string $uri
 * @param string $title
 * @param string $uid
 * @param string $date
 */
function makePageLink($uri, $title="", $uid="", $date="", $override_weblog="") {
    global $PIVOTX;

    if ($PIVOTX['config']->get('mod_rewrite') < 1) {
        $link = $PIVOTX['paths']['site_url']."?p=".$uri;
    } else if ($PIVOTX['config']->get('mod_rewrite') < 5) {
        $prefix = get_default($PIVOTX['config']->get('localised_page_prefix'), "page");
        $link = $PIVOTX['paths']['site_url'] . makeURI($prefix) . "/" . $uri;
    } else {
        $link = $PIVOTX['paths']['site_url'] . $uri;        
    }

    // Set the weblog, according to passed parameter or current weblog
    $weblog = get_default($override_weblog, $PIVOTX['weblogs']->getCurrent());
    
    // If we have more than one weblog, add the w=weblogname parameter
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            // we treat it as an extra 'folder'
            $link .= "/" . $weblog;
        } else {
            $link .= "&amp;w=" . $weblog;
        }
    }

    // Check if there's a hook set, and if so call it. This hook has no 'return' value.
    if ($PIVOTX['extensions']->hasHook('make_link#pages')) {
        $PIVOTX['extensions']->executeHook('make_link#pages', $link, 
            array('uri'=>$uri, 'title'=>$title, 'uid'=>$uid, 'date'=>$date, 'w'=>$weblog));
    }

    return $link;

}


/**
 * Make a link to a category.
 *
 * @param string $name
 */
function makeCategoryLink($name, $override_weblog="") {
    global $PIVOTX;

    // Set the weblog, according to passed parameter or current weblog
    $weblog = get_default($override_weblog, $PIVOTX['weblogs']->getCurrent());
    
    $site_url = get_default($PIVOTX['weblogs']->get($weblog, 'site_url'), $PIVOTX['paths']['site_url']);
    if ($PIVOTX['config']->get('mod_rewrite') < 1) {
        $link = $site_url . "?c=" . $name;
    } else {
        $prefix = get_default($PIVOTX['config']->get('localised_category_prefix'), "category");
        $link = $site_url . makeURI($prefix) . "/" . $name;
    }

    // If we have more than one weblog, add the w=weblogname parameter
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            // we treat it as an extra 'folder'
            $link .= "/" . $weblog;
        } else {
            $link .= "&amp;w=" . $weblog;
        }
    }

    // Check if there's a hook set, and if so call it. This hook has no 'return' value.
    if ($PIVOTX['extensions']->hasHook('make_link#category')) {
        $PIVOTX['extensions']->executeHook('make_link#category', $link, 
            array('name'=>$name, 'w'=>$weblog));
    }

    return $link;

}



/**
 * Makes a link to a RSD (API autodiscovery) file.
 *
 */
function makeRSDLink() {
    global $PIVOTX;

    // Else we process as usual..
    $link = $PIVOTX['paths']['host'] . fixpath( $PIVOTX['paths']['site_url'] );

    if ($PIVOTX['config']->get('mod_rewrite')>0) {
        $link .= "index.php?x=rsd";
    } else {
        $link .= "index.php?x=rsd";
    }

    // If we have more than one weblog, add the w=weblogname parameter
    $weblog = $PIVOTX['weblogs']->getCurrent();
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            $link .= "&amp;w=" . $weblog;
        } else {
            $link .= "&amp;w=" . $weblog;
        }
    }

    return $link;

}

/**
 * Makes a link to an Atom or RSS feed.
 *
 */
function makeFeedLink($type="rss", $append="") {
    global $PIVOTX;

    // Check if we should override our own Feed with a value from the
    // weblog config..
    $override_link = ($type=="rss") ? $PIVOTX['weblogs']->get('', 'rss_url') : $PIVOTX['weblogs']->get('', 'atom_url');

    if (!empty($override_link)) {
        return $override_link;
    } else {

        // Else we process as usual..
        $link = $PIVOTX['paths']['host'] . fixpath( $PIVOTX['paths']['site_url'] );
        
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            $link .= $type;
        } else {
            $link .= "index.php?feed=" . $type;
        }

        // If we have more than one weblog, add the w=weblogname parameter
        $weblog = $PIVOTX['weblogs']->getCurrent();
        if (para_weblog_needed($weblog)) {
            if ($PIVOTX['config']->get('mod_rewrite')>0) {
                // we treat it as an extra 'folder'
                $link .= "/" . $weblog;
            } else {
                $link .= "&amp;w=" . $weblog;
            }
        }
 
        // If we have an $append, we have to append it..
        if (!empty($append)) {
            if ($PIVOTX['config']->get('mod_rewrite')>0) {
                $link .= "?" . $append;
            } else {
                $link .= "&amp;" . $append; 
            }
        }
 
        return $link;

    }

}

/**
 * prepares a weblogname so it can be used as a parameter in an URL
 * it it's us-ascii, it can be used straight away, otherwise it uses
 * the index in $Weblogs
 *
 * @param string $weblog
 * @param array $categories
 * @return string
 */
function para_weblog($weblogkey, $categories = "") {
    global $PIVOTX;

    if ($categories != "") {
        $in_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($categories);
        if ((count($in_weblogs) != 0) && !in_array($weblogkey,$in_weblogs)) {
            $weblogkey = $in_weblogs[0];
        }
    }

    // We do 'allow' spaces in the names, but we need to represent them as
    // underscores. (even though i'm not sure this case is even possible)
    $weblog = str_replace(" ", "_", $weblogkey);

    // see if we need to represent the weblog name as a number to prevent problems:
    if ($weblog != urlencode($weblog) ) {
        $keys = array_flip(array_keys($Weblogs));
        $parameter = $keys[$weblogkey];
    } else {
        $parameter = $weblog;
    }

    return safe_string($parameter);
}



/**
 * prepares a category name so it can be used as a parameter in an URL
 * it it's us-ascii, it can be used straight away, otherwise it uses
 * the index in the categories from configuration.
 *
 * @param string $weblog
 * @return string
 */
function para_category($catkey) {
    global $PIVOTX;

    // We do 'allow' spaces in the names, but we need to represent them as underscores
    $cat = str_replace(" ", "_", $catkey);

    // see if we need to represent the category name as a number to prevent problems:
    if ($cat != urlencode($cat) ) {
        $keys = array_flip(array_keys($PIVOTX['categories']->getCategories()));
        $parameter = $keys[$catkey];
    } else {
        $parameter = $cat;
    }

    return safe_string($parameter);

}


/**
 * Translate a category that came from an url back to it's
 * proper name, if necessary. Returns false if the category
 * isn't present.
 *
 * @param string $cat
 * @return mixed
 */
function category_from_para($cat) {

    // Get all categories..
    $all_cats = cfg_cats();

    if (isset($all_cats[$cat])) {
        // If $cat is present 'as is'
        return $cat;
    } else if(is_numeric($cat)) {
        // If it's numeric, we need to translate it back..
        $keys = array_keys($all_cats);
        return $keys[$cat];
    } else {
        // Make an educated guess.


        // If it's there, but it had an underscore instead of a space
        foreach ($all_cats as $loop_cat) {
            if (str_replace("_"," ",$cat) == str_replace("_"," ",$loop_cat['name']) ) {
                return $loop_cat['name'];
            }
        }

        // Hmm, we don't know this category
        return false;

    }
}


/**
 * Translate a weblog name that came from an url back to it's
 * proper name, if necessary. Returns false is the weblog isn't
 * present.
 *
 * @param string $weblog
 * @return mixed
 */
function weblog_from_para($weblog) {
    global $PIVOTX;

    // If it's numeric, translate it back..
    if(is_numeric($weblog)){
        $keys = array_keys($Weblogs);
        return $keys[$weblog];
    } else {

        if (isset($Weblogs[$weblog])) {
            // $weblog is present 'as is'
            return $weblog;
        } else if (isset($Weblogs[str_replace("_"," ",$weblog)])) {
            // It's there, but it had an underscore, instead of a space
            return str_replace("_"," ",$weblog);
        } else {
            // Hmm, we don't know this category
            return false;
        }

    }
}



/**
 * fixes quotes inside tags.
 * input: [[ foo value=&quot;bar&quot; ]]
 * output: [[ foo value="bar" ]]
 *
 * @param string $tag
 */
function fixquotescallback($tag) {
    return str_replace('&quot;', '"', $tag[0]);
}

?>