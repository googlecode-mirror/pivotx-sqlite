<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: objects.php 2175 2009-10-17 06:49:10Z hansfn $
//
// ---------------------------------------------------------------------------


/**
 * Takes care of all configuration settings. The configuration is stored in
 * pivotx/db/ser_config.php, but is completely accessible through this object.
 * Saving is automagical and only when something has changed.
 *
 */
class Config {

    var $configfile;

    function Config($sites_path = '') {

        $this->configfile = dirname(__FILE__) . '/' . $sites_path . 'db/ser_config.php';

        $this->data = load_serialize($this->configfile, true);

        if (count($this->data)<5) {
            // hmm, couldn't find the data.. Perhaps try to import it from old Pivot 1.x
            $this->readOld();
            $this->save();
        }

        $this->checkConfig();

    }


    /**
     * Check if all required fields in the config are set. If not, we add them.
     *
     */
    function checkConfig() {

        $mustsave = false;

        $default = getDefaultConfig();

        foreach($default as $key=>$value) {

            if (!isset($this->data[$key])) {
                $this->data[$key] = $value;
                $mustsave = true;
            }
        }

        if ($mustsave) {
            $this->save();
        }

    }

    /**
     * If the config file is missing, we check if there's a pivot 1.x config
     * file that we can use. This function does some comversions to get it up
     * to date, and sets it in $this->data
     *
     */
    function readOld() {
        global $pivotx_path;

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = split("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }
        //GetUserInfo();
        //ExpandSessions();

        @$Cfg['ping_urls']=str_replace("|", "\n", $Cfg['ping_urls']);
        @$Cfg['default_introduction']=str_replace("|", "\n", $Cfg['default_introduction']);

        if (!isset($Cfg['selfreg'])) { $Cfg['selfreg']= 0; }
        if (!isset($Cfg['xmlrpc'])) { $Cfg['xmlrpc']= 0; }
        if (!isset($Cfg['hashcash'])) { $Cfg['hashcash']= 0; }
        if (!isset($Cfg['spamquiz'])) { $Cfg['spamquiz']= 0; }
        if (!isset($Cfg['hardened_trackback'])) { $Cfg['hardened_trackback']= 0; }
        if (!isset($Cfg['moderate_comments'])) { $Cfg['moderate_comments']= 0; }
        if (!isset($Cfg['lastcomm_amount_max'])) { $Cfg['lastcomm_amount_max'] = 60; }

        if (!isset($Cfg['tag_cache_timeout'])) { $Cfg['tag_cache_timeout'] = 60; }
        if (!isset($Cfg['tag_flickr_enabled'])) { $Cfg['tag_flickr_enabled'] = 1; }
        if (!isset($Cfg['tag_flickr_amount'])) { $Cfg['tag_flickr_amount'] = 6; }
        if (!isset($Cfg['tag_fetcher_enabled'])) { $Cfg['tag_fetcher_enabled'] = 1; }
        if (!isset($Cfg['tag_fetcher_amount'])) { $Cfg['tag_fetcher_amount'] = 10; }
        if (!isset($Cfg['tag_min_font'])) { $Cfg['tag_min_font'] = 9; }
        if (!isset($Cfg['tag_max_font'])) { $Cfg['tag_max_font'] = 42; }

        if(!isset($Cfg['server_spam_key']))  {
            $key = $_SERVER['SERVER_SIGNATURE'].$_SERVER['SERVER_ADDR'].$_SERVER['SCRIPT_URI'].$_SERVER['DOCUMENT_ROOT'].time();
            $Cfg['server_spam_key'] = md5($key);
        }

        // Remove stuff we don't need:
        unset($Cfg['session_length']);
        unset($Cfg['sessions']);
        unset($Cfg['users']);
        unset($Cfg['userfields']);
        unset($Cfg['<?php']);
        unset($Cfg['?>']);


        foreach ($Cfg as $key => $val) {
            if ( (strpos($key,'uf-')===0) || (strpos($key,'user-')===0) ) {
                unset($Cfg[$key]);
            }
        }





        $this->data = $Cfg;

    }

    /**
     * Save the config to disk.
     *
     */
    function save() {

        if (is_array($this->data)) {
            ksort($this->data);
        }

        save_serialize($this->configfile, $this->data);

    }

    /**
     * Return the entire config as a big array.. It's probable better to use
     * $PIVOTX['config']->get() if you only need one or few items.
     *
     * @see $this->get
     * @return array
     */
    function getConfigArray() {

        return $this->data;

    }

    /**
     * Sets a configuration value, and then saves it.
     *
     * @param string $key
     * @param unknown_type $value
     */
    function set($key, $value) {

        // Empty checkboxes are passed by jQuery as string 'undefined', but we want to store them as integer '0'
        if ($value==="undefined") { $value=0; }

        // Only set (and save) if the value has actually changed.
        if ($value !== $this->data[safe_string($key)] ) {

            $this->data[safe_string($key)] = $value;
            $this->save();
            
        }

    }

    /**
     * Delete a configuration value. Use with extreme caution. Saves the
     * configuration afterwards
     *
     * @param string $key
     */
    function del($key) {
        // Old pre Pivot 2.0 configuration didn't use safe_string
        // on the key - we are handling it here.
        if (isset($this->data[safe_string($key)])) {
            unset($this->data[safe_string($key)]);
        } else {
            unset($this->data[$key]);
        }

        $this->save();

    }

    /**
     * Gets a single value from the configuration.
     *
     * @param string $key
     * @return string
     */
    function get($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return "";
        }
    }

}

/**
 * Since PHP4 doesn't allow class constants, we define the userlevels as 
 * global constants.
 */
define("PIVOTX_UL_MOBLOGGER", 0);
define("PIVOTX_UL_NORMAL", 1);
define("PIVOTX_UL_ADVANCED", 2);
define("PIVOTX_UL_ADMIN", 3);
define("PIVOTX_UL_SUPERADMIN", 4);

/**
 * This Class handles all operations with regard to users: adding, deleting,
 * getting info, etc.
 *
 */
class Users {

    /**
     * Initialisation
     *
     */
    function Users() {
        global $PIVOTX;

        $this->data = load_serialize($PIVOTX['paths']['db_path'] . "ser_users.php", true);

        if ($this->count()<1) {
            // hmm, couldn't find the data.. Perhaps try to import it from old Pivot 1.x
            $this->readOld();
            $this->save();
        }

        // Make sure the users are sorted as intended.
        uasort($this->data, array($this, 'sort'));
    }

    function readOld() {
        global $pivotx_path;

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = split("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }

        if(isset($Cfg['users']))  {
            foreach(explode('|', trim($Cfg['users'])) as $inc => $user){
                $userdata = array();
                foreach(explode('|-|' , $Cfg['user-' . $user]) as $var => $val){
                    list($Nvar, $Nval) = explode('|', $val);
                    if ($Nvar == 'nick') {
                        $userdata['nickname'] = $Nval;
                    } elseif ($Nvar == 'pass') {
                        $userdata['md5_pass'] = $Nval;
                    } else {
                        $userdata[$Nvar] = $Nval;
                    }
                }
                list($userdata['language']) = explode("_",$userdata['language']);
                $this->addUser($userdata);
            }
        }
    }

    /**
     * Get the count of users
     *
     * @return int
     */
    function count() {

        return ( is_array($this->data) && count($this->data) );

    }

    /**
     * Print a comprehensible representation of the users
     *
     */
    function print_r() {

        echo "<pre>\n";
        print_r($this->data);
        echo "</pre>\n";

    }


    /**
     * Add a user to Pivot
     *
     * @param array $user
     */
    function addUser($user) {

        // Make sure the username is OK..
        $user['username'] = strtolower(safe_string($user['username']));

        if ($this->getUser($user['username'])!==false) {
            // this username is already taken..
            return false;
        }

        // Password can come from old (1.x) config or from a new user.
        if (!isset($user['pass1']) && isset($user['md5_pass'])) {
            $md5_pass = $user['md5_pass'];
        } else {
            // Create a new Salt, and set the salted password. 
            $salt = md5(rand(1,999999) . mktime());  
            $md5_pass = md5($user['pass1'] . $salt);
        }

        $newuser = array(
            'username' => $user['username'],
            'password' => $md5_pass,
            'salt' => $salt,
            'email' => $user['email'],
            'userlevel' => $user['userlevel'],
            'nickname' => $user['nickname'],
            'language' => $user['language'],
            'image' => $user['image'],
            'text_processing' => $user['text_processing']
        );

        $this->data[] = $newuser;

        $this->save();

    }

    function deleteUser($username) {

        foreach($this->data as $key=>$user) {
            if ($username == $user['username']) {
                unset($this->data[$key]);
            }
        }

        $this->save();

    }

    /**
     * Update a given property of a user
     *
     * @param string $username
     * @param array $properties
     * @see $this->save
     */
    function updateUser($username, $properties) {

        // Select the correct user
        foreach ($this->data as $key=>$user) {
            if ($username == $user['username']) {

                // Set the properties
                foreach($properties as $property => $value) {

                    switch ($property) {
                        case "email":
                        case "nickname":
                        case "language":
                        case "text_processing":
                        case "lastseen":
                        case "userlevel":
                        case "image":
                        case "reset_id":

                            $this->data[$key][$property] = $value;

                            break;

                        case "pass1":
                            if ( ($value!="") && ($value!="******")) {
                                // Create a new Salt, and set the salted password. 
                                $this->data[$key]['salt'] = md5(rand(1,999999) . mktime());  
                                $this->data[$key]['password'] = md5( $value . $this->data[$key]['salt']);
                            }

                        default:
                            break;
                    }

                }

            }

        }


        $this->save();

    }

    /**
     * Saves the Users to the filesystem.
     *
     */
    function save() {
        global $PIVOTX;

        // Make sure the users are sorted as intended.
        uasort($this->data, array($this, 'sort'));

        save_serialize($PIVOTX['paths']['db_path'] . "ser_users.php", $this->data);

    }


    /**
     * Check if a given password matches the one stored.
     *
     * @param string $username
     * @param string $password
     * @return boolean
     */
    function checkPassword($username, $password) {

        foreach($this->data as $user) {

            if ( ($username==$user['username']) && (md5($password . $user['salt'])) == $user['password'] ) {
                return true;
            }

        }

        return false;

    }


    /**
     * Get the specifics for a given user by its username.
     *
     * @param string $username
     * @return array
     */
    function getUser($username) {

        foreach($this->data as $user) {

            if ( ($username==$user['username']) ) {
                return $user;
            }

        }

        return false;

    }

    /**
     * Get the specifics for a given user by its nickname.
     *
     * @param string $username
     * @return array
     */
    function getUserByNickname($username) {

        foreach($this->data as $user) {

            if ( strtolower($username) == strtolower($user['nickname']) ) {
                return $user;
            }

        }

        return false;

    }


    /**
     * Get a list of the Usernames
     *
     * @return array
     */
    function getUsernames() {

        $res = array();

        foreach($this->data as $user) {
            $res[]=$user['username'];
        }

        return $res;

    }

    /**
     * Get a list of the Users Nicknames
     *
     * @return array
     */
    function getUserNicknames() {

        $res = array();

        foreach($this->data as $user) {
            $res[ $user['username'] ] = $user['nickname'];
        }

        return $res;

    }

    /**
     * Get a list of the Users Email adresses
     *
     * @return array
     */
    function getUserEmail() {

        $res = array();

        foreach($this->data as $user) {
            $res[ $user['username'] ] = $user['email'];
        }

        return $res;

    }


    /**
     * Get all users as an array
     *
     * @return array
     */
    function getUsers() {

        return $this->data;

    }
    
    
    /**
     * Determines if $currentuser (or 'the current user', if left empty) is allowed
     * to edit a page or entry that's owned by $contentowner.
     *
     * @param string $contentowner
     * @param string $currentuser
     * @return boolean
     */
    function allowEdit($contenttype, $contentowner="", $currentuser="") {
        global $PIVOTX;

        // Default to the current logged in user.
        if (empty($currentuser)) {
            $currentuser = $PIVOTX['session']->currentUsername();
        }

        // Fetch the current user..
        $currentuser = $PIVOTX['users']->getUser( $currentuser );
        $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);
        
        // Always allow editing for superadmins - no matter content type.
        if ($currentuserlevel==4) {
            return true;
        } 

        // Fetch the owner..
        $contentowner = $PIVOTX['users']->getUser( $contentowner );
        $contentownerlevel = (!$contentowner?1:$contentowner['userlevel']);

        // Now run the checks for different content types
        if ($contenttype == 'chapter') {

            // Only sdministrator and superadmins can add, edit and delete chapters.
            if ($currentuserlevel>=3) {
                return true;
            } 

        } else if (($contenttype == 'entry') || ($contenttype == 'page')) {

            // Get the value (if any) of allow_edit_for_own_userlevel setting
            $allowsamelevel = get_default( $PIVOTX['config']->get('allow_edit_for_own_userlevel'), 4);

            if ($contentowner['username']==$currentuser['username']) {
                // Always allow editing of your own content..
                return true;
            } else  if ($currentuserlevel > $contentownerlevel) {
                // Allow editing content for items owned by lower levels.
                return true;
            } else if ( ($currentuserlevel == $contentownerlevel) && ( $currentuserlevel >= $allowsamelevel) ) {
                // Allow if userlevel is the same, and greater than or equal to $allowsamelevel
                return true;
            }
        } else {
            debug('Unknown content type');
        }

        // Disallow editing
        return false;
        
    }
    
    /**
     * Sort the users based on string comparison of username.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        return strcmp($a['username'],$b['username']);

    }

}

/**
 * This class deals with the Weblogs.
 *
 */
class Weblogs {

    var $default;
    var $current;

    /**
     * Initialisation
     *
     * @return Weblogs
     */
    function Weblogs() {
        global $PIVOTX;

        $this->data = load_serialize($PIVOTX['paths']['db_path'] . "ser_weblogs.php", true);

        if ($this->count()<1) {
            // hmm, couldn't find the data.. Perhaps try to import it from old Pivot 1.x
            $this->readOld();
            $this->save();
        }

        if ($this->count()<1) {
            // No weblogs, create one from scratch
            $this->add('weblog', 'My weblog', 'pivotdefault');
        }

        foreach ($this->data as $key => $weblog) {
                   
            // Unset '$subkey' weblog -> compensates for an old bug
            if (!empty($this->data[$key]['sub_weblog']['$subkey'])) {
                unset($this->data[$key]['sub_weblog']['$subkey']);
            }
            
            // Make sure all categories are arrays.
            foreach ($weblog['sub_weblog'] as $subkey => $subweblog) {
                if (!is_array($subweblog['categories'])) {
                    $this->data[$key]['sub_weblog'][$subkey]['categories'] = array($subweblog['categories']);
                }
            }
 
            // Set the correct link to the weblog.
            $this->data[$key]['link'] = $this->_getLink($key, $this->data[$key]['site_url']);
 
            // Set the 'categories' for the combined subweblogs..
            $this->data[$key]['categories'] = $this->getCategories($key);

        }

        // Make sure the weblogs are sorted as intended.
        uasort($this->data, array($this, 'sort'));

        // Set default weblog either as specified by the root in the config
        // or just by selecting the first in the weblo
        list($type, $root) = explode(":", $PIVOTX['config']->get('root'));
        if ($type=="w" && !empty($root) && isset($this->data[$root]) ) {
            $this->default = $root;
        } else {
            // Nothing to do but fall back to the first available weblog..
            reset($this->data);
            $this->default = key($this->data);
        }


    }

    /**
     * Return all weblogs as an array
     *
     * @return array
     */
    function getWeblogs() {

        return $this->data;

    }

    /**
     * Returns an array with the weblog names.
     *
     * @return array
     */
    function getWeblogNames() {

        $names = array();

        foreach($this->data as $name=>$data) {
            $names[] = $name;
        }

        return $names;

    }

    /**
     * Return the weblogs that have the given category or categories assigned
     * to them.
     *
     * @param array $categories
     */
    function getWeblogsWithCat($categories) {

        // $cats might be a string with one cat, if so, convert to array
        if (is_string($categories)) {
            $categories= array($categories);
        }

        $res=array();

        // search every weblog for all cats
        foreach ($this->data as $key => $weblog) {

            $weblogcategories = $this->getCategories($key);

            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $res[]=$key;
                }
            }

        }

        return array_unique($res);

    }

    /**
     * Get the categories from a certain weblog.
     *
     * @param string $weblogname
     * @return array
     */
    function getCategories($weblogname='') {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        $results = array();

        // Group the categories from the subweblogs together..
        foreach ($this->data[$weblogname]['sub_weblog'] as $key=>$sub) {

            $cats = $sub['categories'];
            // $cats might be a string with one cat, if so, convert to array
            if (is_string($cats)) {
              $cats= array($cats);
            }

            // Add them to results
            foreach($cats as $cat) {
                $results[] = $cat;
            }
        }

        return array_unique($results);

    }

    /**
     * Returns the given weblog as an array. If no weblogname was given, use
     * the current weblog.
     *
     * @param string $weblogname
     * @return array
     */
    function getWeblog($weblogname='') {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        return $this->data[$weblogname];

    }



    /**
     * Return a subweblog as an array
     *
     * @param string $weblogname
     * @return array
     */
    function getSubweblog($weblogname='', $subweblogname) {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        return $this->data[$weblogname]['sub_weblog'][$subweblogname];

    }






    /**
     * Return the subweblogs of a given weblog as an array. It does this
     * by grabbing all [[weblog]] and [[ subweblog ]] tags from the template
     * that was selected as the frontpage template. Updates the subweblog 
     * info in the weblogs object.
     *
     * @param string $weblogname
     * @return array
     */
    function getSubweblogs($weblogname='') {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $this->getCurrent(); }

        $weblog = $this->getWeblog($weblogname);

        $template_html = load_template($weblog['front_template']);

        preg_match_all("/\[\[\s?(sub)?weblog([: ])(.*)?\]\]/mUi", $template_html, $matches);

        $results = array();

        foreach($matches[3] as $key=>$match) {

            // if $matches[2][$key] was a ':', we know it's an old pivot 1.x style [[ subweblog:name ]]
            // We also must handle optional arguments to the subweblog.
            if ($matches[2][$key]==":") {
                $name = explode(':',$match);
                $results[] = trim($name[0]);
            } else {
                preg_match("/name=['\"]([^'\"]*)/mi", $match, $name);

                if ($name[1]!="") {
                    $results[] = $name[1];
                }

            }

        }    
        
        // Remove any subweblogs that no longer exists from the weblog data.
        $updated = false;
        foreach ($this->data[$weblogname]['sub_weblog'] as $name => $value) {
            if (!in_array($name,$results)) {
                unset($this->data[$weblogname]['sub_weblog'][$name]);
                $updated = true;
            }
        }
        if ($updated) {
            $this->save();
        }

        return $results;

    }




     /**
     * Sets a given weblog as 'current' and returns false if the weblog
     * doesn't exist.
     *
     * @param string $weblogname
     * @return boolean
     */
    function setCurrent($weblogname='') {
        global $PIVOTX;
        
        $exists = true;

        if ( !isset($this->data[$weblogname]) ) {
            $exists = false;
            $weblogname = '';
        }

        if (empty($weblogname)) {
            $this->current = $this->default;
        } else  {
            $this->current = $weblogname;
        }

        return $exists;

    }



     /**
     * Sets a given weblog as 'current' based on a given category and returns false
     * if no matching weblog could be set.
     *
     * @param string $weblogname
     * @return boolean
     */
    function setCurrentFromCategory($categories) {

        // $cats might be a string with concatenated categories.. 
        if (strpos($categories, ",") > 0 ) {
            $categories = explode(",", $categories);
            $categories = array_map('trim', $categories);
        }
                
        // $cats might be a string with one cat, if so, convert to array
        if (is_string($categories)) {
            $categories= array($categories);
        }

        // Check categories in current weblog first (if set) and then the 
        // default weblog
        if (!empty($this->current)) {
            $weblogcategories = $this->data[$this->current]['categories'];
            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    return true;
                }
            }
        } else {
            $weblogcategories = $this->data[$this->default]['categories'];
            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $this->setCurrent($this->default);
                    return true;
                }
            }
        }

        $skip_weblogs = array($this->current, $this->default);

        // search every weblog for all cats
        foreach ($this->data as $key => $weblog) {

            // Skip current and default since we checked them above
            if (in_array($key, $skip_weblogs)) {
                continue;
            }

            $weblogcategories = $this->getCategories($key);

            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $this->setCurrent($key);
                    return true;
                }
            }
        }

        return false;
        
    }


     /**
     * Gets the currently active weblog.
     *
     * @return
     */
    function getCurrent() {

        // Set the current weblog, just to be sure.
        if (empty($this->current)) { $this->setCurrent(""); }

        return $this->current;

    }

     /**
     * Gets the default weblog.
     *
     * @return
     */
    function getDefault() {

        return $this->default;

    }



    /**
     * Add a new weblog, based on $theme. returns the internal name used for
     * the weblog.
     *
     * @param string $internal
     * @param string $name
     * @param string $theme
     * @return string
     */
    function add($internal, $name, $theme) {

        if ( ($internal=="") || isset($this->data[$internal])) {
            // Make a new 'name'..
            for($i=1;$i<1000;$i++) {
                if (!isset($this->data[$internal . "_" . $i])) {
                    $internal = $internal . "_" . $i;
                    break;
                }
            }
        }

        if ($theme=="blank") {

            $this->data[$internal]['name']=$name;

            $this->save();

        } else if ($theme=="pivotdefault") {

            $weblog = getDefaultWeblog();

            $weblog['name'] = $name;

            $this->data[$internal] = $weblog;

            $this->save();


        } else {

            $weblog = load_serialize($theme, true);

            $weblog['name'] = $name;

            $this->data[$internal] = $weblog;

            $this->save();

        }

        return $internal;

    }

    /**
     * Delete a weblog
     *
     * @param string $weblogname
     */
    function delete($weblogname) {

        unset($this->data[$weblogname]);

        $this->save();

    }

    /**
     * Export a weblog as a theme file. The file is saved in the same folder as
     * the weblog's frontpage template.
     *
     * @param string $weblogname
     */
    function export($weblogname) {

        $weblog = $this->data[$weblogname];
        $filename = dirname("./templates/".$weblog['front_template'])."/".$weblogname.".theme";

        save_serialize($filename, $weblog);

    }


    /**
     * Read old weblogs data..
     */
    function readOld() {

        $oldweblogs = load_serialize(dirname(__FILE__)."/pv_cfg_weblogs.php", true);

        // Looping over old weblogs. For each old weblog, add a new one with
        // defaults values and then override the ones already set in the 
        // old config. This way we remove settings no longer present in 
        // PivotX. We also make sure the categories are all 'safe strings'..
        if(is_array($oldweblogs)) {
            foreach($oldweblogs as $weblogkey => $weblog) {
                $newweblogkey = safe_string($weblogkey,true);
                $this->add($newweblogkey, $oldweblogs[$weblogkey]['name'], 'pivotdefault');
                foreach ($this->data[$newweblogkey] as $key => $value) {
                    if (isset($weblog[$key])) {
                        $this->data[$newweblogkey][$key] = $weblog[$key];
                    }
                }
                foreach($this->data[$newweblogkey]['sub_weblog'] as $subweblogkey => $subweblog) {
                    foreach($subweblog['categories'] as $categorykey => $category) {
                        $this->data[$newweblogkey]['sub_weblog'][$subweblogkey]['categories'][$categorykey] = 
                            safe_string($category, true);
                    }
                }
                foreach($this->data[$newweblogkey]['categories'] as $categorykey => $category) {
                    $this->data[$newweblogkey]['categories'][$categorykey] = safe_string($category, true);
                }
            }
        }

    }

    /**
     * Get the count of weblogs
     *
     * @return int
     */
    function count() {

        return ( is_array($this->data) && count($this->data) );

    }

    /**
     * Sets a property of a given weblog
     *
     * @param string $weblogname
     * @param string $key
     * @param string $value
     */
    function set($weblogname, $key, $value) {

        if (isset($this->data[$weblogname])) {

            if (strpos($key, "#")>0) {
                // we're setting something in a subweblog
                // we get these as linkdump#categories = linkdump,books,movies
                list($sub, $key) = explode("#", str_replace("[]", "", $key));


                if (strpos($value, ",")>0) {
                    $value = explode(",", $value);
                }

                $this->data[$weblogname]['sub_weblog'][$sub][$key] = $value;
                
                // we must update the list of categories for the weblog
                $categories = array();
                foreach ($this->data[$weblogname]['sub_weblog'] as $subweblog) {
                    $categories = array_merge($categories,$subweblog['categories']);
                } 
                $this->data[$weblogname]['categories'] = array_unique($categories);

            } else {

                if ($key == 'site_url') {
                    $this->data[$weblogname]['link'] = $this->_getLink($weblogname, $value);
                }

                $this->data[$weblogname][$key] = $value;

            }

            $this->save();

        } else {

            debug('tried to set a setting without passing a weblogname, or non-existing weblog');

        }

    }


    /**
     * Gets a property of a given weblog
     *
     * @param string $weblogname
     * @param string $key
     */
    function get($weblogname, $key) {

        if ($weblogname=="") {
            $weblogname = $this->getCurrent();
        }

        if (empty($this->data[$weblogname])) {
            debug("Weblog '$weblogname' doesn't exist!");
            $weblogname = key($this->data);
        }

        return $this->data[$weblogname][$key];

    }


    /**
     * Calculates the link for a given weblog
     *
     * @param string $value
     * @param string $weblogname
     */
    function _getLink($weblogname, $value) {
        global $PIVOTX;
        
        $link = trim($value);
        if ($link == '') {
            if ($PIVOTX['config']->get('mod_rewrite')==0) {
                $link = $PIVOTX['paths']['site_url'] . '?w=' . $weblogname;
            } else {
                $link = $PIVOTX['paths']['site_url'] . $weblogname;
            }
        } else {
            $ext = getextension(basename($link));
            if ($ext == '') {
                $link = fixPathSlash($link);
            }
        }

        return $link;
    }

    /**
     * Save the weblogs to disk
     *
     */
    function save() {
        global $PIVOTX;

        save_serialize($PIVOTX['paths']['db_path'] . "ser_weblogs.php", $this->data);

    }

    /**
     * Sort the weblogs based on string comparison of name.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        return strcmp($a['name'],$b['name']);

    }

}

/**
 * This class deals with the categories
 *
 */
class Categories {

    /**
     * Initialisation
     *
     * @return Categories
     */
    function Categories() {
        global $PIVOTX;

        $this->data = load_serialize($PIVOTX['paths']['db_path']."ser_categories.php", true);

        if ($this->count()<1) {
            // hmm, couldn't find the data.. Perhaps try to import it from old Pivot 1.x
            $this->readOld();
            $this->saveCategories();
        }

        if ($this->count()<1) {
            // Having no categories at all is just silly. Add 'default' and 'linkdump'
            $this->setCategory('default', array('name'=>'default', 'display'=>'Default'));
            $this->setCategory('linkdump', array('name'=>'linkdump', 'display'=>'Linkdump'));
        }

        // Make sure the categories are sorted as intended.
        usort($this->data, array($this, 'sort'));

    }


    /**
     * Get the count of categories
     *
     * @return int
     */
    function count() {

        return ( is_array($this->data) && count($this->data) );

    }




    function readOld() {
        global $pivotx_path;

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = split("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }
        //GetUserInfo();
        //ExpandSessions();

        $catnames = explode("|",$Cfg['cats']);

        // Check which categories are "hidden"..
        if (isset($Cfg['cats-searchexclusion'])) {
            $hiddenarray = explode('|', strtolower($Cfg['cats-searchexclusion']));
        } else {
            $hiddenarray = array();
        }

        // Check the category order..
        if (isset($Cfg['cats-order'])) {
            $temp = explode('|-|', strtolower($Cfg['cats-order']));

            foreach($temp as $item) {
                list($catname, $order) = explode("|", $item);
                $orderarray[strtolower($catname)] = $order;
            }
        } else {
            $orderarray = array();
        }


        $cats = array();

        foreach ($catnames as $cat) {

            // Skip empty category names.
            $catname = trim($cat);
            if ($catname == '') {
                continue;
            }
            
            $catname = strtolower($catname);

            if (isset($Cfg['cat-'.$cat])) {
                $users = explode('|', strtolower($Cfg['cat-'.$cat]));
            } else {
                $users = array();
            }

            // Make sure the users are 'safe strings'
            foreach($users as $key=>$user) {
                $users[$key] = safe_string($user, true);
            }

            $cats[] = array (
                'name' => safe_string($catname, true),
                'display' => $cat,
                'users' => $users,
                'hidden' => (in_array($catname, $hiddenarray)) ? 1 : 0,
                'order' => (isset($orderarray[$catname])) ? $orderarray[$catname] : 110,
                );

        }


        $this->data = $cats;

    }

    /**
     * change the settings for an existing category, or modify an existing one.
     *
     * @param string $name
     * @param array $cat
     */
    function setCategory($name, $cat) {

        $name = strtolower(safe_string($name));
        $cat['name'] = strtolower(safe_string($cat['name']));

        foreach($this->data as $key=>$val) {

            if ($name==$val['name']) {

                $this->data[$key] = $cat;
                $this->saveCategories();
                return;
            }

        }

        // Otherwise it must be a new one, let's add it:
        if(!empty($cat['name'])){
            $this->data[] = $cat;
            $this->saveCategories();
        }


    }



    /**
     * Save the categories to disk
     *
     */
    function saveCategories() {
        global $PIVOTX;

        // If $this->data is empty, make it an empty array.
        if (empty($this->data)) {
            $this->data = array();
        }

        usort($this->data, array($this, 'sort'));

        save_serialize($PIVOTX['paths']['db_path'] . "ser_categories.php", $this->data);

    }

    /**
     * Get an array with all the categories. We filter the users to make sure we only
     * return users that still exist
     *
     * @return array
     */
    function getCategories() {
        global $PIVOTX;

        $results = $this->data;
        
        $users = $PIVOTX['users']->getUsernames();
        
        // Filter only existing users..
        foreach ($results as $key=>$value) {
            $results[$key]['users'] = array_intersect($results[$key]['users'], $users);
        }

        return $results;

    }
    


    

    /**
     * Get a list of categories the user is allowed to post into
     */
    function allowedCategories($username) {

        $allowed = array();

        foreach($this->data as $cat) {

            if (in_array($username, $cat['users'])) {
                $allowed[$cat['name']] = $cat['name'];
            }

        }

        return $allowed;

    }

    /**
     * Allow a user to post in this category
     *
     * @param string $catname
     * @param string $username
     */
    function allowUser($catname, $username) {

        // Loop through all available categories
        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$catname) {

                // Add the username
                $this->data[$key]['users'][] = $username;

                // But remove duplicates
                $this->data[$key]['users'] = array_unique($this->data[$key]['users']);

            }

        }

    }


    /**
     * Disallow a user to post in this category
     *
     * @param string $catname
     * @param string $username
     */
    function disallowUser($catname, $username) {

        // Loop through all available categories
        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$catname) {

                // Loop through the users, and remove $username if present.
                foreach($cat['users'] as $userkey=>$catuser){
                    if ($catuser==$username) {
                        unset($this->data[$key]['users'][$userkey]);
                    }
                }

            }

        }

    }


    /**
     * Get a single category
     *
     * @param string $name
     * @return array
     */
    function getCategory($name) {

        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$name) {
                return $cat;
            }

        }

        return array();

    }

    /**
     * Get a list of all category names
     *
     * @return array
     */
    function getCategorynames() {

        $names = array();

        foreach($this->data as $cat) {
            $names[]=$cat['name'];
        }
        return $names;

    }


    /**
     * Check if a given $name is a category.
     *
     * @param string $name
     * @return boolean
     */
    function isCategory($name) {

        foreach($this->data as $cat) {
            if($name==$cat['name']) { return true; }
        }

        return false;

    }



    /**
     * Get a list of all category names in which we should NOT search
     *
     * @return array
     */
    function getSearchCategorynames() {

        $names = array();

        foreach($this->data as $cat) {
            if ($cat['hidden']!=1) {
                $names[]=$cat['name'];
            }
        }

        return $names;



    }


    /**
     * Delete a single category
     *
     * @param string $name
     */
    function deleteCategory($name) {
        global $PIVOTX;

        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$name) {
                unset($this->data[$key]);
                $this->saveCategories();
                break;
            }

        }
        
        // Remove it from all weblogs as well.
        $weblogs = $PIVOTX['weblogs']->getWeblogs();

        foreach($weblogs as $weblogkey=>$weblog) {
            foreach($weblog['sub_weblog'] as $subweblogkey=>$subweblog) {
                foreach($subweblog['categories'] as $catkey => $cat) {
                    if ($cat==$name) {
                        unset($weblogs[$weblogkey]['sub_weblog'][$subweblogkey]['categories'][$catkey]);
                    }
                }
                
            }
            foreach($weblogs[$weblogkey]['categories'] as $catkey => $cat) {
                if ($cat==$name) {
                    unset($weblogs[$weblogkey]['categories'][$catkey]);
                }
            }
        }
        
        $PIVOTX['weblogs']->data = $weblogs;
        $PIVOTX['weblogs']->save();

    }

    /**
     * Sort the categories based on the order and string comparison
     * of display name if order is identical.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        if ($PIVOTX['config']->get('sort_categories_by_alphabet')==true) {
            // If we set 'sort_categories_by_alphabet' to true, always sort by alphabet..
            return strcmp($a['display'],$b['display']);
        } else if ($a['order'] == $b['order']) {
            // Else sort by alphabet, if order is the same..
            return strcmp($a['display'],$b['display']);
        } else {
            // else sort by order..
            return ($a['order'] < $b['order']) ? -1 : 1;
        }

    }

}

/**
 * Sort the pages based on the order and string comparison
 * of (page) title if order is identical.
 * 
 * @todo This function seems to be only used by the flat file db. 
 *      Move it to modules/pages_flat.php? Or should it be used by MySQL db too?
 * @param array $a
 * @param array $b
 * @return int
 */
function pageSort($a, $b) {
    global $PIVOTX;

    if ($PIVOTX['config']->get('sort_pages_by_alphabet')==true) {
        // If we set 'sort_pages_by_alphabet' to true, always sort by alphabet..
        return strcmp($a['title'],$b['title']);
    } else if ($a['sortorder'] == $b['sortorder']) {
        // Else sort by alphabet, if order is the same..
        return strcmp($a['title'],$b['title']);
    } else {
        // else sort by order..
        return ($a['sortorder'] < $b['sortorder']) ? -1 : 1;
    }

}

/**
 * This class deals with Sessions: logging in, logging out, saving sessions
 * and performing checks for required userlevels.
 * 
 * This class protects the cookie/session against standard XSS attacks and 
 * sidejacking.
 *
 */
class Session {

    var $permsessions, $logins, $message;
    /**
     * Initialisation
     *
     * @return Session
     */
    function Session() {
        global $PIVOTX;

        $this->cookie_lifespan = 60*60*24*30;  // 30 days

        // Select the secure bit for the session cookie. Setting it to true if
        // using HTTPS which stops sidejacking / session hijacking.
        // If we're on regular HTTP, $_SERVER['HTTPS'] will be 'empty' on Apache 
        // servers, and have a value of 'off' on IIS servers.  
        if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS'])=="off" ) {
            $this->cookie_secure = false;
        } else {
            $this->cookie_secure = true;
        }
        
        // Force cookie to be "HTTP only" to make cookie stealing harder - stops
        // standard XSS attacks. (Introduced in PHP 5.2.0.)
        if (check_version(phpversion(), '5.2.0')) {
            $this->cookie_httponly = true;
        } else {
            $this->cookie_httponly = false;
        }

        // On second thought, our CSRF check (that uses the double cookie submit 
        // test) needs to access the cookie ... We just can't use "HTTP only".
        $this->cookie_httponly = false;

        // Set to 'site url' instead of 'pivotx_url', because then we
        // can use 'edit this entry' and the like.
        $this->cookie_path = $PIVOTX['paths']['site_url'];

        // Don't set the domain for a cookie on a "TLD" - like localhost ...
        if (strpos($_SERVER["SERVER_NAME"], ".") > 0) {
            if (preg_match("/^www./",$_SERVER["SERVER_NAME"])) {
                $this->cookie_domain = "." . preg_replace("/^www./", "", $_SERVER["SERVER_NAME"]);
            } else {
                $this->cookie_domain = $_SERVER["SERVER_NAME"];
            }
        } else {
            $this->cookie_domain = "";
        }

        // Only set "HTTP only" if supported
        if ($this->cookie_httponly) {
            session_set_cookie_params($this->cookie_lifespan, 
                $this->cookie_path, $this->cookie_domain, $this->cookie_secure, $this->cookie_httponly); 
        } else {
            session_set_cookie_params($this->cookie_lifespan, 
                $this->cookie_path, $this->cookie_domain, $this->cookie_secure); 
        }

        session_start();

    }

    /**
     * Sets a cookie named "pivotxsession" taking into account if "HTTP only" is 
     * supported. Just a wrapper around setcookie.
     *
     * @param string $key
     * @param string $time
     */
    function setCookie($key, $time) {
        if ($this->cookie_httponly) {
            $res = setcookie("pivotxsession", $key, $time, $this->cookie_path, 
                $this->cookie_domain, $this->cookie_secure, $this->cookie_httponly );
        } else {
            $res = setcookie("pivotxsession", $key, $time, $this->cookie_path, 
                $this->cookie_domain, $this->cookie_secure );
        }
        
        // Add some debug output, if we couldn't set the cookie.
        if ($res==false) {
            debug("Couldn't set cookies! (probably because output has already started)");
            if (headers_sent($filename, $linenum)) {
                debug("Headers already sent in $filename on line $linenum");
            } else {
                debug("Headers have not been sent yet. Something's wonky.");
            }
        }
        
    }

    /**
     * Verify if whomever requested the current page is logged in as a user,
     * or else attempt to (transparently) continue from a saved session.
     *
     * @return boolean
     */
    function isLoggedIn() {
        global $PIVOTX;
        
        $this->loadPermsessions();

        $sessioncookie = (!empty($_COOKIE['pivotxsession'])) ? $_COOKIE['pivotxsession'] : $_POST['pivotxsession'];

        if (isset($_SESSION['user']) && isset($_SESSION['user']['username']) && ($_SESSION['user']['username']!="") ) {

            // User is logged in!
            
            // Check if we're in the saved sessions.. 
            if (!empty($sessioncookie) && !isset($this->permsessions[$sessioncookie])) {
            
                $this->permsessions[ $sessioncookie ] = array(
                    'username' => $_SESSION['user']['username'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'lastseen' => time()
                );
                $this->savePermsessions();         
            }
            
            return true;

        } else {

            // See if we can continue a stored session..

            // Check if we have a pivotxsession cookie that matches a saved session..
            if ( (!empty($sessioncookie)) && (isset($this->permsessions[$sessioncookie])) ) {

                $savedsess = $this->permsessions[ $sessioncookie ];
                
                // Check if the IP in the saved session matches the IP of the user..
                if ($_SERVER['REMOTE_ADDR'] == $savedsess['ip']) {

                    // Check if the 'lastseen' wasn't too long ago..
                    if (time() < ($savedsess['lastseen'] + $this->cookie_lifespan) ) {

                        // If we get here, we can restore the session!

                        $_SESSION['user']= $PIVOTX['users']->getUser($savedsess['username']);

                        // Update the 'lastseen' in permanent sessions.
                        $this->permsessions[ $sessioncookie ]['lastseen'] = time();
                        $this->savePermsessions();

                        // Add the 'lastseen' to the user settings.
                        $PIVOTX['users']->updateUser($savedsess['username'], array('lastseen'=>time()) );
                        $_SESSION['user']['lastseen'] = time();

                        // Set the session cookie as session variable.
                        $_SESSION['pivotxsession'] = $sessioncookie;

                        return true;

                    }

                }

            }
            return false;

        }

    }

    /**
     * Attempt to log in a user, using the passed credentials. If succesfull,
     * the session info is updated and 'true' is returned. When unsuccesful
     * the session remains unaltered, and false is returned
     *
     *
     * @param string $username
     * @param string $password
     * @param int $stay
     * @return boolean
     */
    function login($username, $password, $stay) {
        global $PIVOTX;

        $this->loadLogins();

        if (!$this->checkFailedLogins()) {
            debug(sprintf(__("Blocked login attempt from '%s'."), $_SERVER['REMOTE_ADDR']));
            $this->message = __('Too many failed login attempts from this IP address. ' . 
                'Please contact your site administrator to unblock your account.');
            return false;
        }

        $username = strtolower($username);

        $match = $PIVOTX['users']->checkPassword($username, $password);

        if (!$match) {

            $this->message = __('Incorrect username/password');
            $this->logFailedLogin();
            return false;

        } else {

            $this->message = __('Successfully logged in');
            $key = makeKey(16);
            $_SESSION['pivotxsession'] = $key;

            // Add the 'lastseen' to the user settings.
            $PIVOTX['users']->updateUser($username, array('lastseen'=>time()) );

            // Keep track of people logging in (and remove any failed logins 
            // for IP if any).
            $this->logins['succeeded'][] = array(
                'username' => $username,
                'time' => time(),
                'ip' => $_SERVER['REMOTE_ADDR']
            );
            unset($this->logins['failed'][$_SERVER['REMOTE_ADDR']]);
            $this->saveLogins();

            $_SESSION['user']= $PIVOTX['users']->getUser($username);

            $path = $PIVOTX['paths']['site_url']; // Set to 'site url' instead of 'pivotx_url', because then we
                                        // can use 'edit this entry' and the like.

            if ($stay==1) {

                $this->setCookie($key, time() + $this->cookie_lifespan );

            } else {

                $this->setCookie($key, 0 );

            }

            $this->permsessions[ $key ] = array(
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'lastseen' => time()
            );

            $this->savePermsessions();

            return true;
        }

    }

    /**
     * Logs failed login attempts so PivotX can block brute force attacks.
     * 
     */
    function logFailedLogin() {
        global $PIVOTX;

        $ip = $_SERVER['REMOTE_ADDR'];
        

        $this->logins['failed'][ $ip ] = array(
          'attempts' => $this->logins['failed'][ $ip ]['attempts'] + 1,
          'time' => mktime()      
        );
            
        $this->saveLogins();
        debug(sprintf(__("Failed login attempt from '%s'."), $_SERVER['REMOTE_ADDR']));
    }

    /**
     * Checks failed login attempts so PivotX can block brute force attacks.
     * 
     */
    function checkFailedLogins() {
        global $PIVOTX;
        
        $limit = get_default($PIVOTX['config']->get('failed_logins_limit'), 8);
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if ($this->logins['failed'][ $ip ]['attempts'] > $limit) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Log out a user: clear the session, and delete the cookie
     *
     */
    function logout() {
        global $PIVOTX;

        $this->loadPermsessions();

        // remove current session (by username, so if the user logs out on
        // one location, he logs out everywhere)..
        foreach ($this->permsessions as $key => $session) {
            if ($session['username']==$_SESSION['user']['username']) {
                unset($this->permsessions[$key]);
            }
        }

        $PIVOTX['events']->add('logout');
        $this->savePermsessions();

        // End the session..
        unset($_SESSION['user']);
        $this->setCookie('', time()-10000 );

        session_destroy();

    }

    /**
     * Returns the latest/current message.
     *
     * @return array
     */
    function getMessage() {

        return $this->message;

    }

    /**
     * Returns the current user.
     *
     * @return array
     */
    function currentUser() {

        return $_SESSION['user'];

    }


    /**
     * Sets the specifics for the current user..
     *
     * @param array $user
     */
    function setUser($user) {

        $_SESSION['user'] = $user;

    }


    /**
     * Returns the username of the current user.
     *
     * @return array
     */
    function currentUsername() {

        return $_SESSION['user']['username'];

    }


    /**
     * Checks if the currently logged in user has at least the required level
     * to view the page he/she is trying to access.
     *
     * If not, the user is logged out of the system.
     *
     * @param int $level
     */
    function minLevel($level) {

        $this->isLoggedIn();

        if ($level>$_SESSION['user']['userlevel']) {
            debug("logged out because the user's level was too low, or not logged in at all");
            pageLogout();
            die();
        }

    }


    /**
     * Checks if the current request is accompanied by the correct
     * CSRF check.
     *
     * If not, the user is logged out of the system.
     *
     * @param int $value
     */
    function checkCSRF($value) {

        if ($value != $_SESSION['pivotxsession']) {
            debug( sprintf("CSRF check failed: '%s..' vs. '%s..'",
                substr($value,0,8), substr($_SESSION['pivotxsession'],0,8) ));
            pageLogout();
            die();
        }

    }

    /**
     * Get the key to use in the CSRF checks.
     *
     */
    function getCSRF() {

        return $_SESSION['pivotxsession'];

    }


    /**
     * Save permanent sessions to the filesystem, for users that check 'keep
     * me logged in'.
     *
     * The sessions are saved in db/ser_sessions.php, and they look somewhat like
     * Array
     * (
     *     [8nkvr62i3s37] => Array
     *         (
     *             [username] => admin
     *             [ip] => 127.0.0.1
     *             [lastseen] => 1168177821
     *         )
     * )
     *
     */
    function savePermsessions() {
        global $PIVOTX;

        save_serialize($PIVOTX['paths']['db_path'] . "ser_sessions.php", $this->permsessions);

    }


    /**
     * Load the permanent sessions from the filesystem.
     *
     */
    function loadPermsessions() {
        global $PIVOTX;

        $this->permsessions = load_serialize($PIVOTX['paths']['db_path'] . "ser_sessions.php", true);

        // Remove stale sessions after loading.
        foreach ($this->permsessions as $key=>$session) {
            if(($session['lastseen']+ $this->cookie_lifespan) < time() ) {
                unset($this->permsessions[$key]);
            }
        }

    }

    /**
     * Save login attempts from the filesystem.
     */
    function saveLogins() {
        global $PIVOTX;

        save_serialize($PIVOTX['paths']['db_path'] . "ser_logins.php", $this->logins);

    }


    /**
     * Load stored login attempts from the filesystem.
     */
    function loadLogins() {
        global $PIVOTX;

        $timeout = get_default($PIVOTX['config']->get('failed_logins_timeout'), 24);
    
        $this->logins = load_serialize($PIVOTX['paths']['db_path'] . "ser_logins.php", true);

        // Set timeout to the timestamp at which the block needs to be dropped.
        $timeout = mktime() - ($timeout*3600);

        // Iterate over the failed attempts, to see if they need to be dropped.
        foreach ($this->logins['failed'] as $ip => $item) {
            if ($item['time']<$timeout) {
                unset($this->logins['failed'][$ip]);
            }
        }

    }


}




/**
 * This class deals with Pages.
 *
 */
class Pages {

    var $index;
    var $currentpage;

    /**
     * Initialisation
     *
     * @return Pages
     */
    function Pages() {
        global $PIVOTX;

        if ($PIVOTX['config']->get('db_model')=="flat") {
            require_once("modules/pages_flat.php");
            $this->db = new PagesFlat();
        } else if ( ($PIVOTX['config']->get('db_model')=="mysql") ||
                ($PIVOTX['config']->get('db_model')=="sqlite") ||
                ($PIVOTX['config']->get('db_model')=="postgresql") ) {
            require_once("modules/pages_sql.php");
            $this->db = new PagesSql();
        } else {
            // TODO: In case of a fatal error, we should give the user the chance to reset the
            // Config to the default state, and try again.
            die("Unknown DB Model! Pivot can not continue!");
        }

        $this->currentpage = array();

        $this->getIndex();

    }

    /**
     * Get the index of the available chapters and pages.
     *
     * @return array
     */
    function getIndex($excerpts=false) {
        global $PIVOTX;

        $filteruser = "";

        // Check if we need to filter for a user, based on the 'show_only_own_userlevel'
        // settings.. We do this only when not rendering a weblog, otherwise the
        // pages that are filtered out won't show up on the site. 
        if (!defined('PIVOTX_INWEBLOG')) {
            $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
            $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);
    
            if ( $currentuserlevel <= $PIVOTX['config']->get('show_only_own_userlevel') ) {
                $filteruser = $currentuser['username'];
            }    
        }

        $this->index = $this->db->getIndex($filteruser, $excerpts);

        return $this->index;

    }

    /**
     * Get the information for a specific Chapter
     *
     * @param integer $id
     * @return array
     */
    function getChapter($id) {

        return $this->index[$id];

    }

    /**
     * Add a chapter, and save the index
     *
     * @param array $chapter
     */
    function addChapter($chapter) {

        $this->index = $this->db->addChapter($chapter);

        $this->saveIndex(false);

    }


    /**
     * Delete a chapter, and save the index
     *
     * @param integer $uid
     */
    function delChapter($uid) {

        $this->index = $this->db->delChapter($uid);

        $this->saveIndex(false);

    }


    /**
     * Update the information for a chapter, and save the index
     *
     * @param integer $uid
     * @param array $chapter
     */
    function updateChapter($uid,$chapter) {

        $this->index = $this->db->updateChapter($uid,$chapter);

        $this->saveIndex(false);
    }


    /**
     * Save the index to the DB, using the selected model.
     *
     * @param boolean $reindex
     */
    function saveIndex($reindex=true) {

        uasort($this->index, array($this, 'chapSort'));

        $this->db->setIndex($this->index);

        $this->db->saveIndex($reindex);

    }


    /**
     * Get a single page
     *
     * @param integer $uid
     * @return array
     */
    function getPage($uid) {

        $page = $this->db->getPage($uid);

        $this->currentpage = $page;

        return $page;

    }

    /**
     * Get a single page, as defined by its URI
     *
     * @param string $uri
     * @return array
     */
    function getPageByUri($uid) {

        $page = $this->db->getPageByUri($uid);

        $this->currentpage = $page;
        
        return $page;

    }

    /**
     * Gets the current page
     */
    function getCurrentPage() {
        
        return $this->currentpage;
        
    }

    /**
     * Gets the $amount latest pages as an array, suitable for displaying an
     * overview
     *
     * @param integer $amount
     */
    function getLatestPages($amount, $filter_user="") {

        $pages = $this->db->getLatestPages($amount, $filter_user);

        return $pages;

    }

    /**
     * Delete a single page
     *
     * @param integer $uid
     */
    function delPage($uid) {

        $this->db->delPage($uid);

    }


    /**
     * Save a single page. Returns the uid of the inserted page.
     *
     * @param array $page
     * @return integer.
     */
    function savePage($page) {

        $this->currentpage = $page;

        return $this->db->savePage($page);
    

    }

    /**
     * Sort the chapters based on the order and string comparison
     * of chapter name if order is identical.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function chapSort($a, $b) {
        global $PIVOTX;

        if ($PIVOTX['config']->get('sort_chapters_by_alphabet')==true) {
            // If we set 'sort_chapters_by_alphabet' to true, always sort by alphabet..
            return strcmp($a['chaptername'],$b['chaptername']);
        } else if ($a['sortorder'] == $b['sortorder']) {
            // Else sort by alphabet, if order is the same..
            return strcmp($a['chaptername'],$b['chaptername']);
        } else {
            // else sort by order..
            return ($a['sortorder'] < $b['sortorder']) ? -1 : 1;
        }

    }

}



/**
 * The class that does the work for the paging and paging_subweblog snippets.
 *
 * @author Hans Fredrik Nordhaug <hansfn@gmail.com>, The PivotX dev. Team.
 */
class Paging {
    var $offset;
    var $name;

    function Paging($name) {
        $this->name = $name;
    }

    function sanity_check($action) {
        global $PIVOTX;
        list($action,$dummy) = explode('|',$action);
        if (($action != "next") && ($action != "prev") &&
            ($action != "curr") && ($action != "digg")) {
            return "<!-- snippet {$this->name} error: unknow action '$action' -->\n";
        }

        // Only display the paging snippet on weblog pages
        $modifier = $PIVOTX['parser']->get('modifier');
        if (($PIVOTX['parser']->get('action') != 'weblog') || !empty($modifier['archive'])) {
            return "<!-- snippet {$this->name} ($action): only output on weblog pages -->\n";
        }
        return;
    }

    function setup() {
        // Determine the offset
        if (!isset($_GET['o'])) {
            $this->offset = 0;
        } elseif (is_numeric($_GET['o'])) {
            $this->offset = $_GET['o'];
        } else {
            return "<!-- snippet {$this->name} error: offset isn't numeric -->\n";
        }


        return;
    }


    function doit($action, $text, $cats, $amountperpage, $params) {
        global $PIVOTX;

        $Current_weblog = $PIVOTX['weblogs']->getCurrent();
        $modifier = $PIVOTX['parser']->get('modifier');

        // $amountperpage must be numberic, one or larger
        if (!is_numeric($amountperpage) || ($amountperpage<1)) {
            return "<!-- snippet {$this->name} error: invalid number of entries to skip ($amountperpage) -->\n";
        }

        $query = '';

        if (isset($_GET['w'])) $query .= '&amp;w='. $_GET['w'];
        if (isset($_GET['t'])) $query .= '&amp;t='. $_GET['t'];

        // Setting the text for the links
        if ($action == "next") {
            $text = get_default($params['format'], __("Next page")." &#187;" );
        } elseif ($action == "prev") {
            $text = get_default($params['format'], "&#171; ".__("Previous page"));
        } elseif ($action == "digg") {
            $text_prev = get_default($params['format_prev'], "&#171; ".__("Previous page"));
            $text_next = get_default($params['format_next'], __("Next page")." &#187;" );
        } else {
            $text = get_default($params['format'], __("Displaying entries %num_from%-%num_to% of %num_tot%") );
        }

        // Get the maximum amount of pages to show.
        $max_digg_pages = get_default($params['maxpages'], 9);

        // Get the id to attach to the <ul> for Digg style navigation.
        $digg_id = get_default($params['id'], "pages");

        // Start the real work.
        $eachcatshash = md5(implode_deep("", $cats));
        
        if ($PIVOTX['cache']->get('paging', $eachcatshash)) {
            // Check if this is in our simple cache?
            list($temp_tot, $num_tot) = $PIVOTX['cache']->get('paging', $eachcatshash); 
        } else {

            // Get the total amount of entries. How we do this depends on the used DB-model..
            // What we do is we get the amount of entries for each item in $cats.
            // For example, let's say we have 10 entries per page and 90 entries in one subweblog, and
            // 65 in the other. In this case we don't need (90+65)/10 pages, but (max(90,65))/10 pages.
            if ($PIVOTX['db']->db_type == "flat" ) {
                // Get the amount from the Flat files DB..
                $tot = $PIVOTX['db']->get_entries_count();
                foreach ($cats as $eachcats) {
                    if (trim($eachcats) == '') { continue; }
                    $temp_tot = count($PIVOTX['db']->read_entries(array(
                        'show'=>$tot, 'cats'=>$eachcats, 'status'=>'publish')));
                    $num_tot = max( $num_tot, $temp_tot);
                }
            } else {
                // Get the amount from our SQL db..
                $sql = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
                $entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);
                $categoriestable = safe_string($PIVOTX['config']->get('db_prefix')."categories", true);

                foreach ($cats as $eachcats) {
                    if (trim($eachcats) == '') { continue; }
                    if (is_array($eachcats)) {
                        $eachcats = implode("','", $eachcats);
                    }
                    $sql->query("SELECT COUNT(DISTINCT(e.uid)) FROM $entriestable AS e, $categoriestable as c
                    WHERE e.status='publish' AND e.uid=c.target_uid AND c.category IN ('$eachcats');");
                    $temp_tot = current($sql->fetch_row());
                    $num_tot = max( $num_tot, $temp_tot);
                }
            }
            $PIVOTX['cache']->set('paging', $eachcatshash, array($temp_tot, $num_tot));
        }

        $offset = intval($modifier['offset']);
        $num_pages = ceil($num_tot / $amountperpage);

        if ($num_tot == 0) {
            return "<!-- snippet {$this->name}: no entries -->\n";
        } elseif ($offset >= $num_pages) {
            return "<!-- snippet {$this->name}: no more entries -->\n";
        }

        if ($action == "next") {

            $offset++;

            if ($offset >= $num_pages) {
                return "<!-- snippet {$this->name} (next): no more entries -->\n";
            }

        } elseif ($action == "prev")  {

            if ($offset == 0) {
                return "<!-- snippet {$this->name} (previous): no previous entries -->\n";
            } else {
                $offset--;
            }

        } else {
            if ($num_tot == 0) {
                return "<!-- snippet {$this->name} (curr): no current entries -->\n";
            } else {
                $num = min($num,$num_tot);
            }

        }

        $num_from = $offset * $amountperpage + 1;
        $num_to = min($num_tot, ($offset+1) * $amountperpage);

        $text = str_replace("%num%", $amountperpage, $text);
        $text = str_replace("%num_tot%", $num_tot, $text);
        $text = str_replace("%num_from%", $num_from, $text);
        $text = str_replace("%num_to%", $num_to, $text);

        if ($action == "curr") {
            return $text;
        }


        if ($PIVOTX['config']->get('mod_rewrite')==0) {
            if (!empty($modifier['category']) || $params['catsinlink']==true ) {
                $link = $PIVOTX['paths']['site_url']."?c=".$modifier['category']."&amp;o=";
            } else {
                $link = $PIVOTX['paths']['site_url']."?o=";
            }
        } else {
            if (!empty($modifier['category']) || $params['catsinlink']==true ) {
                $categoryname = get_default( $PIVOTX['config']->get('localised_category_prefix'), "category");
                // Ensure that we get a sorted list of unique categories in 
                // the URL - better SEO, one unique URL.
                $catslink = implode_deep(",",$cats);
                $catslink = array_unique(explode(",",$catslink));
                sort($catslink, SORT_STRING);
                $catslink = implode(",",$catslink);
                $link = $PIVOTX['paths']['site_url'] . $categoryname . "/" . $catslink . "/";
            } else {
                $pagesname = get_default( $PIVOTX['config']->get('localised_browse_prefix'), "browse");
                $link = $PIVOTX['paths']['site_url']. $pagesname . "/";
            }
        }


        if ($action == 'digg') {
            $link .= '%offset%';
        } else {
            $link .= $offset;
        }

        $link .= $query;

        if ((!isset($_GET['w'])) && para_weblog_needed($Current_weblog)) {
            $link .= "&amp;w=".para_weblog($Current_weblog);
        }

        // Perhaps add the author name..
        if (!empty($_GET['u'])) {
            $link .= "&amp;u=" . $_GET['u'];
        }

        $link = str_replace(array('"',"'"), "", $link);

        if ($action != 'digg') {

            return '<a href="'.$link.'">'.$text.'</a>';

        } else {

            $output ="
<div id=\"{$digg_id}\">
    <ul>
    %links%
    </ul>
</div>";
            $links = '';

            // Adding the previous link
            if ($offset == 0) {
                $links .= '<li class="nolink">%text_prev%</li>';
            } else {
                $links .= '<li><a href="%url%">%text_prev%</a></li>';

                $url = str_replace('%offset%',max(0,$offset-1),$link);
                $links = str_replace('%url%',$url,$links);
            }

            if ($num_pages > $max_digg_pages ) {
                // Limit the number of links/listed pages.

                $max_digg_pages = intval($max_digg_pages);

                $start = (int) ($offset - 0.5 * ($max_digg_pages-1));
                $start = max(0,$start) + 1;
                $stop = (int) ($offset + 0.5 * ($max_digg_pages-1));
                $stop = max(min(1000,$stop),3);
                $page = $offset;

                if ($offset==0) {
                    $links .= '<li class="current">1</li>';
                } else if ($start>=1) {
                    $links .= '<li><a href="%url%">1</a></li>';
                    if ($start>=2) {
                        $links .= '<li class="skip">&#8230;</li>';
                    }
                    $url = str_replace('%offset%',0,$link);
                    $links = str_replace('%url%',$url,$links);
                }
            } else {
                // Display all links/listed pages.
                $start = 0;
                $stop = 100;
            }


            // Adding all links before the current page
            while ($start < $offset) {
                $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                $url = str_replace('%offset%', $start, $link);
                $links = str_replace('%url%', $url, $links);
                $start++;
            }

            // Current page..
            if ($start == $offset) {
                $links .= '<li class="current">' . ($start+1) . '</li>';
                $start++;
            }

            // Adding all links after the current page
            while ($start < $num_pages) {
                if ($start < $stop) {
                    $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                    $url = str_replace('%offset%', $start, $link);
                    $links = str_replace('%url%', $url, $links);
                } else if ($start == ($num_pages-2) ) {
                    $links .= '<li class="skip">&#8230;</li>';
                } else if ($start == ($num_pages-1) ) {
                    $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                    $url = str_replace('%offset%', $start, $link);
                    $links = str_replace('%url%', $url, $links);
                }
                $page++;
                $start++;
            }


            // Adding the next link
            if ( ($offset+1) >= $num_pages) {
                $links .= '<li class="nolink">%text_next%</li>';
            } else {
                $links .= '<li><a href="%url%">%text_next%</a></li>';
                $url = str_replace('%offset%', $offset + 1, $link);
                $links = str_replace('%url%', $url, $links);
            }
            $output = str_replace('%links%', $links, $output);
            $output = str_replace('%text_prev%', $text_prev, $output);
            $output = str_replace('%text_next%', $text_next, $output);
            return $output;
        }
    }

}


/**
 * A Class that provides for very simple, in-memory caching. 
 *
 * @author Bob, The PivotX dev. Team.
 */
class Simplecache {
    
    var $cache;
    var $stats;

    function SimpleCache() {
        
        $this->cache = array();
        $this->stats = array(
            'hits' => 0,
            'misses' => 0,
            'items' => 0,
            'size' => 0
        );
        
    }
    
    /**
     * Set a single item in the cache
     *
     * @param string $type
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    function set($type="general", $key, $value) {
        
        // Check if the $type and $key are OK
        if (empty($key) || (!is_string($key) && !is_integer($key) ) || !is_string($type)) {
            // debug("Not Set: $type - $key");
            return false;
        }
        
        if (!isset($this->cache[$type][$key])) {
            $this->stats['items']++;
        }
        
        // debug("Set: $type - $key");        
        $this->cache[$type][$key] = $value;
        
        return true;
        
    }

    /**
     * Set a single item in the cache
     *
     * @param string $type
     * @param array $values
     * @return bool
     */
    function setMultiple($type="general", $values) {
        
        // Check if the $type and $key are OK
        if (empty($values) || !is_array($values) || !is_string($type)) {
            return false;
        }
        
        foreach($values as $key=>$value) {
            $this->set($type, $key, $value);    
        }
        
        return true;
        
    }

    /**
     * Get a single item from the cache. Returns the value on success, or false
     * when it's a miss. So, storing booleans in the cache isn't very convenient.
     *
     * @param string $type
     * @param string $key
     * @return mixed
     */
    function get($type="general", $key) {
    
        if (!empty($this->cache[$type][$key])) {
            // debug("Get(hit): $type - $key");
            $this->stats['hits']++;
            return $this->cache[$type][$key];
        } else {
            // debug("Get(miss): $type - $key");
            $this->stats['misses']++;
            return false;
        }
    
    }
    
    
    /**
     * Return some basic statistics for the cache..
     *
     * @return array
     */
    function stats() {
        
        $this->stats['size'] = strlen(serialize($this->cache));
        
        return $this->stats;
        
    }
    
    function clear() {
        $this->cache = array();
    }

}

class Minify {
    
    var $html;
    var $head;
    var $jsfiles;
    var $cssfiles;
    var $base;
    
    function Minify($html) {
        global $PIVOTX;
        
        $this->html = $html;
        
        // Set the base path..
        if (defined('PIVOTX_INWEBLOG')) {
            $this->base = $PIVOTX['paths']['site_url'];
        } else {
            $this->base = $PIVOTX['paths']['pivotx_url'];
        }

    }
    
    function minifyURLS() {

        // if the PHP version is too low, we return the HTML, without doing anything.
        if (!check_version(phpversion(), '5.1.6')) {
            debug('PHPversion too low to us Minify: ' . phpversion() );
            return $this->html;
        }
        
        $head = $this->_getHead();
        
        if (empty($this->head)) {
            debug("Couldn't find a <head> section to minify");
        } else {
            $this->_getScripts();
            $this->_minifyScriptURLs();        
        }
        
        $this->_getStylesheets();
        $this->_minifyStylesheetURLs();

        return $this->html;
    }
    
    /**
     * Get the head section.
     **/			
    function _getHead() {

        preg_match("/<head([^>]+)?>.*?<\/head>/is", $this->html, $matches);

        if(!empty($matches[0])) {

            $head = $matches[0];

            // Pull out the comment blocks, so as to avoid touching conditional comments
            $head = preg_replace("/<!-- .*? -->/is", '', $head);		
        
        } else {         
            $head = "";
        }
        
        $this->head = $head;
        
    }    
    
    
    /**
     * Get the scripts from the head section.
     **/			
    function _getScripts() {
    
        $scripts = array();
        
        $regex = "/<script[^>]+type=['\"](text\/javascript)['\"]([^>]+)?>(.*)<\/script>/iUs";
        preg_match_all($regex, $this->head, $matches);

        if (!empty($matches[0])) {
            
            // echo "<pre>\n"; print_r($matches); echo "</pre>";
            
            $scripts = $matches[0];
         
            // remove 'inline' js, and links to external resources..
            // We also skip files with an '?', because they have extra paremeters, indicating
            // that they are generated, so we shouldn't minify them.
            foreach ($scripts as $key => $script) {
                preg_match('/src=[\'"](.*)[\'"]/iUs', $script, $res);
                
                $res = $res[1];
                $ext = getextension($res);
                
                if ( empty($res) || ($ext!="js") || (strpos($res, "ttp://")==1) || (strpos($res, "ttps://")==1) || (strpos($res, "?")>0) ) {
                    unset($scripts[$key]);
                    continue;
                }
                
                
            }
            
        }
        
        //debug("found scripts");
        //debug_printr($scripts);
        
        $this->jsfiles = $scripts;
        
    }
    
    /**
     * convert the found js files into one minify-link..
     */
    function _minifyScriptURLs() {
        global $PIVOTX;
        
        $sources = array();
        
        foreach ($this->jsfiles as $jsfile) {
            preg_match('/src=[\'"](.*)[\'"]/iUs', $jsfile, $res);
            
            // Add file paths to relative URLs..
            if (strpos($res[1], "/") !== 0) {
                $res[1] = $this->base . $res[1];
            }
            $sources[] = preg_replace('#'.$PIVOTX['paths']['site_url'].'#', '', $res[1], 1);
        }
        
        if (!empty($sources)) {

            $minifylink = sprintf("<scr"."ipt type=\"text/javascript\" src=\"%sincludes/minify/?f=%s\"></scr"."ipt>" ,
                    $PIVOTX['paths']['pivotx_url'],
                    implode(",", $sources)
                );
        
            // Replace the javascript links in the source with the minify-link:
            $this->html = str_replace($this->jsfiles[0], $minifylink, $this->html);
        
            foreach($this->jsfiles as $jsfile) {
                $this->html = str_replace($jsfile, "", $this->html);
            }
        
        }
        
    }
    
    
   /**
     * Get the stylesheets from the entire document.
     **/			
    function _getStylesheets() {
    
        $stylesheets = array();
        
        $regex = "/<link[^>]+text\/css[^>]+>/iUs";
        preg_match_all($regex, $this->html, $matches);

        if (!empty($matches[0])) {
            
            // remove links to external resources, and organize by 'media' type..
            foreach ($matches[0] as $key => $stylesheet) {
                preg_match('/href=[\'"](.*)[\'"]/iUs', $stylesheet, $res);
                
                $href = $res[1];
                $ext = getextension($href);
                
                // We also skip files with an '?', because they have extra paremeters, indicating
                // that they are generated, so we shouldn't minify them.                
                if ( empty($href) || ($ext!="css") || (strpos($href, "ttp://")==1) || (strpos($href, "ttps://")==1) || (strpos($res, "?")>0) ) {
                    continue;
                }
                
                preg_match('/media=[\'"](.*)[\'"]/iUs', $stylesheet, $res);
                
                $media = $res[1];
                
                if ( empty($media) || ($media=="screen") ) {
                    $stylesheets['screen'][] = $stylesheet;
                } else {
                    $stylesheets[$media][] = $stylesheet;
                }
                
            }
            
        }
          
        $this->cssfiles = $stylesheets;
        
    }    
    
    
    /**
     * convert the found css files into one minify-link..
     */
    function _minifyStylesheetURLs() {
        global $PIVOTX;

        // Loop for each separate mediatype..
        foreach($this->cssfiles as $mediatype => $cssfiles) {
            
            $sources = array();
             
            foreach ($cssfiles as $cssfile) {
                preg_match('/href=[\'"](.*)[\'"]/iUs', $cssfile, $res);
                 
                // Add file paths to relative URLs..
                if (strpos($res[1], "/") !== 0) {
                    $res[1] = $this->base . $res[1];
                }                  
                $sources[] = preg_replace('#'.$PIVOTX['paths']['site_url'].'#', '', $res[1], 1);

            }
             
            if (!empty($sources)) {
           
            
                $minifylink = sprintf('<link href="%sincludes/minify/?url=%s&amp;f=%s" ' .
                    ' rel="stylesheet" type="text/css" media="%s" />' ,
                        $PIVOTX['paths']['pivotx_url'],
                        substr($PIVOTX['paths']['site_url'],0,strlen($PIVOTX['paths']['site_url'])-1),
                        implode(",", $sources),
                        $mediatype
                    );
               
                // Replace the javascript links in the source with the minify-link:
                $this->html = str_replace($cssfiles[0], $minifylink, $this->html);
            
                foreach($cssfiles as $cssfile) {
                    $this->html = str_replace($cssfile, "", $this->html);
                }
            
            }            
            
        }
         
    }
        
    
}


/**
 * Takes care of the systemwide events, such as "Mike logged in." or "Pablo changed
 * the config setting 'xxx'."
 *
 */
class Events {

    var $data;
    var $filename;
    var $edit_timeout;
    var $maxevents;

    function Events() {
        global $PIVOTX;
        
        $this->filename = "ser_events.php";
        
        $this->edittimeout = 60;
        $this->maxevents = 60;
        
        $this->data = load_serialize($PIVOTX['paths']['db_path'] . $this->filename, true);
        
        if (empty($this->data) || !is_array($this->data)) {
            $this->data = array();
        }
        
    }

    function add($what, $uid, $extrainfo="") {
        global $PIVOTX;
        
        $timestamp = format_date("", "%year%-%month%-%day%-%hour24%-%minute%");
        $user = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
        
        $event = array($timestamp, $user['username'], $what, $uid, $extrainfo);
        
        //echo "<pre>Data:\n"; var_dump($event); echo "</pre>";
        
        array_push($this->data, $event);
        
        $this->save();
        
    }

    function save() {
        global $PIVOTX;
    
        //echo "<pre>Save:\n"; var_dump($this->data); echo "</pre>";
        
        save_serialize($PIVOTX['paths']['db_path'] . $this->filename, $this->data);
        
    }


    /**
     * Get the last $amount events..
     */
    function get($amount=8) {
        global $PIVOTX;
       
        for ($i = count($this->data)-1; ($i>0 && $amount>0) ; $i-- ) {
        
            $event = $this->data[$i];
            
            // If $event[3] holds more than one uid, implode it to a string for printing.
            if (is_array($event[3])) {
                $event[3] = implode(", ", $event[3]);
            }
            
            $name = "<strong>" . $event[1] ."</strong>";
        
            $format = "";
            
            switch ($event[2]) {
                
                case 'edit_entry':
                    if (!$saved['entry'][$event[1]][$event[3]]) {
                        $format = sprintf( __("%s started editing entry '%s'."), $name, $event[4] );
                        $saved['entry'][$event[1]][$event[3]] = true;
                    }
                    break;

                case 'edit_page':
                    if (!$saved['page'][$event[1]][$event[3]]) {
                        $format = sprintf( __("%s started editing page '%s'."), $name, $event[4] );
                        $saved['page'][$event[1]][$event[3]] = true;
                    }
                    break;
                
                case 'save_entry':
                    $saved['entry'][$event[1]][$event[3]] = true;
                    $format = sprintf( __("%s saved entry '%s'."), $name, $event[4] );
                    break;
                
                case 'save_page':
                    $saved['page'][$event[1]][$event[3]] = true;
                    $format = sprintf( __("%s saved page '%s'."), $name, $event[4] );
                    break;
                
                case 'login':
                    $format = sprintf( __("%s logged in."), $name );
                    break;
                
                case 'logout':
                    $format = sprintf( __("%s logged out."), $name );
                    break;

                case 'failed_login':
                    $format = sprintf( __("Failed login attempt for '%s'."), $event[4] );
                    break;
                
                case 'edit_config':
                    $format = sprintf( __("%s edited the setting for '%s'."), $name, $event[3] );
                    break;                
                
                case 'add_weblog':
                    $format = sprintf( __("%s added weblog '%s'."), $name, $event[4] );
                    break;                
                
                case 'edit_weblog':
                    $format = sprintf( __("%s edited a weblog setting for '%s'."), $name, $event[4] );
                    break;                
                
                case 'delete_weblog':
                    $format = sprintf( __("%s deleted weblog '%s'."), $name, $event[4] );
                    break;                
                
                case 'save_file':
                    $format = sprintf( __("%s saved the file '%s'."), $name, $event[3] );
                    break;                
                
                case 'add_user':
                    $format = sprintf( __("%s added user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_user':
                    $format = sprintf( __("%s edited user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'delete_user':
                    $format = sprintf( __("%s deleted user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_category':
                    $format = sprintf( __("%s edited category '%s'."), $name, $event[4] );
                    break;                
                              
                case 'delete_category':
                    $format = sprintf( __("%s deleted category '%s'."), $name, $event[4] );
                    break;
                
                case 'add_chapter':
                    $format = sprintf( __("%s added chapter '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_chapter':
                    $format = sprintf( __("%s edited chapter '%s'."), $name, $event[4] );
                    break;                     
                              
                
                default:
                    if (!empty($event[4])) {
                        $format = sprintf( __("%s did '%s' on '%s'(4)."), $name, $event[2], $event[4] );
                    } else if (!empty($event[3])) {
                        $format = sprintf( __("%s did '%s' on '%s'."), $name, $event[2], $event[3] );
                    } else {
                        $format = sprintf( __("%s did '%s'."), $name, $event[2] );  
                    }  
                    break;
                
            }
            
            
            if (!empty($format)) {
            
                $output[] = sprintf("<acronym title=\"%s\">%s</acronym>: %s",
                        format_date($event[0], $PIVOTX['config']->get('fulldate_format')),
                        formatDateFuzzy($event[0]),
                        $format
                    );

                $amount--;
            }
            
        }
        
        return $output;
        
    }


}

?>