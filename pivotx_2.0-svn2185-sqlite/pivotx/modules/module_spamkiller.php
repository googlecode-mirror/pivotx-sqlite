<?php
/**
 * Contains all the functions needed for SpamQuiz and HashCash spam
 * protection.
 *
 * Based on the excellent Pivot Blacklist (version 0.9.3)
 * written by Marco van Hylckama Vlieg.
 *   marco@i-marco.nl - http://www.i-marco.nl/
 *
 * Includes a modified/stripped down version of Elliott Back's WP-Hashcash
 * version 2.3 http://www.elliottback.com/
 *
 * @package pivotx
 * @subpackage modules
 */
// ini_set('display_errors',1);
// error_reporting(E_ALL);
/**
 * Log file for spamkiller module.
 */
$GLOBALS['spamkiller_log'] = $spamkiller_log = dirname(dirname(__FILE__))."/db/spamkiller.".date("Y-m").".log";



/**
 * Returns a random string of length $l.
 *
 * @param integer $l String length
 * @param array $exclude String to exclude
 * @return string
 */
function hashcash_random_string($l, $exclude = array()) {
    // Sanity check
    if($l < 1){
        return '';
    }

    srand((double) microtime() * 1000000);

    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars = preg_split('//', $alphabet, -1, PREG_SPLIT_NO_EMPTY);
    $len = count($chars) - 1;

    $str = '';
    while(in_array($str, $exclude) || strlen($str) < 1){
        $str = '';
        while(strlen($str) < $l){
            $str .= $chars[rand(0, $len)];
        }
    }
    return $str;
}

/**
 * Returns md5 javascript bits to be randomly spliced into the header.
 *
 * @param string $md5_function_name A string md5_function_name to call the md5 function
 * @return string
 */
function hashcash_get_md5_javascript($md5_function_name){
    $p = '';
    $s = '';

    $names = array();
    $excl = array('a', 's', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
            'v', 'w', 'x', 'y', 'z', 'num', 'cnt', 'str', 'bin',
            'length', 'len', 'var', 'Array', 'mask', 'return', 'msw',
            'lsw', 'olda', 'oldb', 'oldc', 'oldd', 'function', 'new');
    for($i = 0; $i < 20; $i++){
        $t = hashcash_random_string(rand(1,6), $excl);
        $names [] = $t;
        $excl [] = $t;
    }

    $bits = array();
    $bits [] = $p . 'function ' . $md5_function_name . '(s){return ' . $names[5] . '(' . $names[6] . '(' . $names[7] . '(s),s.length*8));}' . $s;
    $bits [] = $p . 'function ' . $names[6] . '(x,len){x[len>>5]|=0x80<<((len)%32);x[(((len+64)>>>9)<<4)+14]=len;var a=1732584193;var b=-271733879;var c=-1732584194;var d=271733878;for(var i=0;i<x.length;i+=16){var olda=a;var oldb=b;var oldc=c;var oldd=d;a=' . $names[8] . '(a,b,c,d,x[i+0],7,-680876936);d=' . $names[8] . '(d,a,b,c,x[i+1],12,-389564586);c=' . $names[8] . '(c,d,a,b,x[i+2],17,606105819);b=' . $names[8] . '(b,c,d,a,x[i+3],22,-1044525330);a=' . $names[8] . '(a,b,c,d,x[i+4],7,-176418897);d=' . $names[8] . '(d,a,b,c,x[i+5],12,1200080426);c=' . $names[8] . '(c,d,a,b,x[i+6],17,-1473231341);b=' . $names[8] . '(b,c,d,a,x[i+7],22,-45705983);a=' . $names[8] . '(a,b,c,d,x[i+8],7,1770035416);d=' . $names[8] . '(d,a,b,c,x[i+9],12,-1958414417);c=' . $names[8] . '(c,d,a,b,x[i+10],17,-42063);b=' . $names[8] . '(b,c,d,a,x[i+11],22,-1990404162);a=' . $names[8] . '(a,b,c,d,x[i+12],7,1804603682);d=' . $names[8] . '(d,a,b,c,x[i+13],12,-40341101);c=' . $names[8] . '(c,d,a,b,x[i+14],17,-1502002290);b=' . $names[8] . '(b,c,d,a,x[i+15],22,1236535329);a=' . $names[9] . '(a,b,c,d,x[i+1],5,-165796510);d=' . $names[9] . '(d,a,b,c,x[i+6],9,-1069501632);c=' . $names[9] . '(c,d,a,b,x[i+11],14,643717713);b=' . $names[9] . '(b,c,d,a,x[i+0],20,-373897302);a=' . $names[9] . '(a,b,c,d,x[i+5],5,-701558691);d=' . $names[9] . '(d,a,b,c,x[i+10],9,38016083);c=' . $names[9] . '(c,d,a,b,x[i+15],14,-660478335);b=' . $names[9] . '(b,c,d,a,x[i+4],20,-405537848);a=' . $names[9] . '(a,b,c,d,x[i+9],5,568446438);d=' . $names[9] . '(d,a,b,c,x[i+14],9,-1019803690);c=' . $names[9] . '(c,d,a,b,x[i+3],14,-187363961);b=' . $names[9] . '(b,c,d,a,x[i+8],20,1163531501);a=' . $names[9] . '(a,b,c,d,x[i+13],5,-1444681467);d=' . $names[9] . '(d,a,b,c,x[i+2],9,-51403784);c=' . $names[9] . '(c,d,a,b,x[i+7],14,1735328473);b=' . $names[9] . '(b,c,d,a,x[i+12],20,-1926607734);a=' . $names[10] . '(a,b,c,d,x[i+5],4,-378558);d=' . $names[10] . '(d,a,b,c,x[i+8],11,-2022574463);c=' . $names[10] . '(c,d,a,b,x[i+11],16,1839030562);b=' . $names[10] . '(b,c,d,a,x[i+14],23,-35309556);a=' . $names[10] . '(a,b,c,d,x[i+1],4,-1530992060);d=' . $names[10] . '(d,a,b,c,x[i+4],11,1272893353);c=' . $names[10] . '(c,d,a,b,x[i+7],16,-155497632);b=' . $names[10] . '(b,c,d,a,x[i+10],23,-1094730640);a=' . $names[10] . '(a,b,c,d,x[i+13],4,681279174);d=' . $names[10] . '(d,a,b,c,x[i+0],11,-358537222);c=' . $names[10] . '(c,d,a,b,x[i+3],16,-722521979);b=' . $names[10] . '(b,c,d,a,x[i+6],23,76029189);a=' . $names[10] . '(a,b,c,d,x[i+9],4,-640364487);d=' . $names[10] . '(d,a,b,c,x[i+12],11,-421815835);c=' . $names[10] . '(c,d,a,b,x[i+15],16,530742520);b=' . $names[10] . '(b,c,d,a,x[i+2],23,-995338651);a=' . $names[11] . '(a,b,c,d,x[i+0],6,-198630844);d=' . $names[11] . '(d,a,b,c,x[i+7],10,1126891415);c=' . $names[11] . '(c,d,a,b,x[i+14],15,-1416354905);b=' . $names[11] . '(b,c,d,a,x[i+5],21,-57434055);a=' . $names[11] . '(a,b,c,d,x[i+12],6,1700485571);d=' . $names[11] . '(d,a,b,c,x[i+3],10,-1894986606);c=' . $names[11] . '(c,d,a,b,x[i+10],15,-1051523);b=' . $names[11] . '(b,c,d,a,x[i+1],21,-2054922799);a=' . $names[11] . '(a,b,c,d,x[i+8],6,1873313359);d=' . $names[11] . '(d,a,b,c,x[i+15],10,-30611744);c=' . $names[11] . '(c,d,a,b,x[i+6],15,-1560198380);b=' . $names[11] . '(b,c,d,a,x[i+13],21,1309151649);a=' . $names[11] . '(a,b,c,d,x[i+4],6,-145523070);d=' . $names[11] . '(d,a,b,c,x[i+11],10,-1120210379);c=' . $names[11] . '(c,d,a,b,x[i+2],15,718787259);b=' . $names[11] . '(b,c,d,a,x[i+9],21,-343485551);a=' . $names[13] . '(a,olda);b=' . $names[13] . '(b,oldb);c=' . $names[13] . '(c,oldc);d=' . $names[13] . '(d,oldd);}return Array(a,b,c,d);}' . $s;
    $bits [] = $p . 'function ' . $names[12] . '(q,a,b,x,s,t){return ' . $names[13] . '(' . $names[16] . '(' . $names[13] . '(' . $names[13] . '(a,q),' . $names[13] . '(x,t)),s),b);}function ' . $names[8] . '(a,b,c,d,x,s,t){return ' . $names[12] . '((b&c)|((~b)&d),a,b,x,s,t);}' . $s;
    $bits [] = $p . 'function ' . $names[9] . '(a,b,c,d,x,s,t){return ' . $names[12] . '((b&d)|(c&(~d)),a,b,x,s,t);}' . $s;
    $bits [] = $p . 'function ' . $names[10] . '(a,b,c,d,x,s,t){return ' . $names[12] . '(b ^ c ^ d,a,b,x,s,t);}' . $s;
    $bits [] = $p . 'function ' . $names[11] . '(a,b,c,d,x,s,t){return ' . $names[12] . '(c ^(b|(~d)),a,b,x,s,t);}' . $s;
    $bits [] = $p . 'function ' . $names[13] . '(x,y){var lsw=(x&0xFFFF)+(y&0xFFFF);var msw=(x>>16)+(y>>16)+(lsw>>16);return(msw<<16)|(lsw&0xFFFF);}' . $s;
    $bits [] = $p . 'function ' . $names[16] . '(num,cnt){return(num<<cnt)|(num>>>(32-cnt));}' . $s;
    $bits [] = $p . 'function ' . $names[7] . '(' . $names[18] . '){var ' . $names[17] . '=Array();var ' . $names[19] . '=(1<<8)-1;for(var i=0;i<' . $names[18] . '.length*8;i+=8)' . $names[17] . '[i>>5]|=(' . $names[18] . '.charCodeAt(i/8)&' . $names[19] . ')<<(i%32);return ' . $names[17] . ';}' . $s;
    $bits [] = $p . 'function ' . $names[5] . '(' . $names[15] . '){var ' . $names[14] . '="0123456789abcdef";var str="";for(var i=0;i<' . $names[15] . '.length*4;i++){str+=' . $names[14] . '.charAt((' . $names[15] . '[i>>2]>>((i%4)*8+4))&0xF)+' . $names[14] . '.charAt((' . $names[15] . '[i>>2]>>((i%4)*8))&0xF);}return str;}' . $s;

    return $bits;
}

/**
 * Returns the hashcash special code, based on the session or ip.
 *
 * @return string
 */
function hashcash_special_code(){
    global $pivotx_path;
    $key = strip_tags(session_id());

    if(!$key){
        $key = $_SERVER['REMOTE_ADDR'];
    }

    return md5($key . $pivotx_path . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a"));
}

/**
 * Returns the hashcash special field value.
 *
 * @return string
 */
function hashcash_field_value() {
        global $PIVOTX, $pivotx_path;
        
        return $PIVOTX['db']->entry['code'] * strlen($pivotx_path);
}

/**
 * Returns Javascript to compute field value.
 *
 * @param string $val_name Name of function
 * @return string
 */
function hashcash_field_value_js($val_name){
    $js = 'function ' . $val_name . '(){';

    $type = rand(0, 5);
    switch($type){
        /* Addition of n times of field value / n, + modulus */
        case 0:
            $eax = hashcash_random_string(rand(8,10));
            $val = hashcash_field_value();
            $inc = rand(1, $val - 1);
            $n = floor($val / $inc);
            $r = $val % $inc;

            $js .= "var $eax = $inc; ";
            for($i = 0; $i < $n - 1; $i++){
                $js .= "$eax += $inc; ";
            }

            $js .= "$eax += $r; ";
            $js .= "return $eax; ";

            break;

        /* Conversion from binary */
        case 1:
            $eax = hashcash_random_string(rand(8,10));
            $ebx = hashcash_random_string(rand(8,10));
            $ecx = hashcash_random_string(rand(8,10));
            $val = hashcash_field_value();
            $binval = strrev(base_convert($val, 10, 2));

            $js .= "var $eax = \"$binval\"; ";
            $js .= "var $ebx = 0; ";
            $js .= "var $ecx = 0; ";
            $js .= "while($ecx < $eax.length){ ";
            $js .= "if($eax.charAt($ecx) == \"1\") { ";
            $js .= "$ebx += Math.pow(2, $ecx); ";
            $js .= "} ";
            $js .= "$ecx++; ";
            $js .= "} ";
            $js .= "return $ebx; ";

            break;

        /* Multiplication of square roots */
        case 2:
            $val = hashcash_field_value();
            $sqrt = floor(sqrt($val));
            $r = $val - ($sqrt * $sqrt);
            $js .= "return $sqrt * $sqrt + $r; ";
            break;

        /* Closest sum up to n */
        case 3:
            $val = hashcash_field_value();
            $n = floor((sqrt(8*$val+1)-1)/2);
            $sum = $n * ($n + 1) / 2;
            $r = $val - $sum;
            $eax = hashcash_random_string(rand(8,10));

            $js .= "var $eax = $r; ";
            for($i = 0; $i <= $n; $i++){
                $js .= "$eax += $i; ";
            }
            $js .= "return $eax; ";
            break;

        /* Closest sum up to n #2 */
        case 4:
            $val = hashcash_field_value();
            $n = floor((sqrt(8*$val+1)-1)/2);
            $sum = $n * ($n + 1) / 2;
            $r = $val - $sum;

            $js .= "return $r ";
            for($i = 0; $i <= $n; $i++){
                $js .= "+ $i ";
            }
            $js .= ";";
            break;

        /* Closest sum up to n #3 */
        case 5:
            $val = hashcash_field_value();
            $n = floor((sqrt(8*$val+1)-1)/2);
            $sum = $n * ($n + 1) / 2;
            $r = $val - $sum;
            $eax = hashcash_random_string(rand(8,10));

            $js .= "var $eax = $r; var i; ";
            $js .= "for(i = 0; i <= $n; i++){ ";
            $js .= "$eax += i; ";
            $js .= "} ";
            $js .= "return $eax; ";
            break;
    }

    $js .= "} ";
    return $js;
}



/**
 * Returns true if it matches the hidden md5'ed tag.
 *
 * @return boolean
 */
function add_hashcash($page) {
    global $PIVOTX;

    // If the hook for the thickbox includes in the header was not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

    // Setting up random values/strings
    $field_id = hashcash_random_string(rand(6,18));
    $field_name = hashcash_random_string(rand(6,18));
    $hashcash_form_action = hashcash_random_string(rand(6,18));
    $md5_name = hashcash_random_string(rand(6,18));
    $val_name = hashcash_random_string(rand(6,18));
    $eElement = hashcash_random_string(rand(6,18));
    $in_str = hashcash_random_string(rand(6,18));
    $fn_enable_name = hashcash_random_string(rand(6,18));
    // Globals
    $GLOBALS["hashcash_form_action"] = $hashcash_form_action;

    // 1) Hidden value form field
    $page = str_replace('<input type="hidden" name="piv_code"',
            '<input type="hidden" id="'.$field_id.'" name="'.$field_name.'" value="'.rand(100, 99999999).'" />'.
        "\n".'<input type="hidden" name="piv_code"', $page);

    // 2)  Disable submit button for non-users of javascript,
    // adding a noscript warning, and then dynamically enabling the submit button;
    $page = str_replace('<input type="submit" name="post"',
        '<input type="submit" name="post" id="submitbutton" disabled="disabled"', $page);
    $page = str_replace('<form', '<noscript><p class="pivotx-message">'.__('Please enable Javascript (and reload this page) to add any comments.').'</p></noscript>'.
        "\n" . '<form', $page);
    
    $hashcash_bits = hashcash_get_md5_javascript($md5_name);
    $hashcash_bits [] = "function $hashcash_form_action($in_str){ "
        . "$eElement = document.getElementById(\"$field_id\"); "
        . "if(!$eElement){ return false; } else { $eElement"
        . ".name = $md5_name($in_str); $eElement"
        . ".value = $val_name(); return true; }}";
    $hashcash_bits [] = hashcash_field_value_js($val_name);
    shuffle($hashcash_bits);
    $hashcash_js = implode(" ", $hashcash_bits);

    // 3)   Adding HashCash onsubmit javascript to form.
    $page = str_replace('<form', '<form onsubmit="' . $hashcash_form_action . "('".hashcash_special_code()."');\" ", $page);

    $callonload = 'jQuery(function($) { setTimeout("'.$fn_enable_name.'()", 100); });' . "\n";
    $hashcash_onload_js = 'function ' . $fn_enable_name . "(){ jQuery('input[name=post]').attr('disabled', ''); }";

    // 5)  Write all the javascript bits to various lines of <head>
    $js = '<script type="text/javascript">' . "\n"
        . '<!--' . "\n"
        . $hashcash_onload_js . "\n"
        . $hashcash_js . "\n"
        . $callonload
        . '// -->' . "\n"
        . '</script>' . "\n"
        . "\n";

    // 6) Insert it via a hook.
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js );

    return $page;
}



/**
 * Returns true if it matches the hidden md5'ed tag.
 *
 * @return boolean
 */
function hashcash_check_hidden_tag() {
    global $pivotx_path;
    // Our special codes, fixed to check the previous hour
    $special = array();

        $special[] = md5($_SERVER['REMOTE_ADDR'] . $pivotx_path . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a"));
        $special[] = md5($_SERVER['REMOTE_ADDR'] . $pivotx_path . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a", time()-(60*60)));
        $special[] = md5(strip_tags(session_id()) . $pivotx_path . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a"));
        $special[] = md5(strip_tags(session_id()) . $pivotx_path . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a", time()-(60*60)));

    foreach($special as $val){
        if(($_POST[md5($val)] == ($_POST['piv_code'] * strlen($pivotx_path)))) {
            return true;
        }
    }
    logspammer($_SERVER["REMOTE_ADDR"], "hashcash");
    return false;
}


/**
 * Check the trackback for spam (currently using Hardened Trackback if enabled).
 *
 * @return void
 */
function killtrackbackspam() {
    global $PIVOTX;

    // Do nothing if hardened trackback isn't enabled.
    if ($PIVOTX['config']->get('hardened_trackback') != 1)  {
        return true;
    }
    $keydir = $PIVOTX['paths']["db_path"]."tbkeys/";
    $key = $_GET["key"];
    if(strlen($key) < 32) {
        logspammer('tampered key: invalid length', "htrackback", $_POST['url']);
        exit;
    } else {
        if(!preg_match('/^[a-f0-9]{32}$/',$_GET["key"])) {
            logspammer('tampered key: invalid characters found', "htrackback", $_POST['url']);
            exit;
        }
        if(file_exists($keydir.$key)) {
            $offset = timediffwebfile();
            if((time()-filectime($keydir.$key)) > (900 + $offset)) {
                @unlink($keydir.$key);
                // pbl_suspectIP($aConfig["blockstrikes"]);
                logspammer(stripslashes($_POST['excerpt']), "htrackback", $_POST['url']);
                exit;
            }
        } else {
            logspammer('key not found', "htrackback");
            exit;
        }
        unlink($keydir.$key);
    }
}


/**
 * Logs the blocked spam.
 *
 * @return void
 */
function logspammer($p_sSpam, $p_sType, $p_sAdditional="")  {
    global $Pivot_Vars, $spamkiller_log, $Cfg;

    // Check if we need to trim the spam logfile..
    trim_spamlog();

    $date = date("F j, Y, g:i a");
    $p_sSpam = trim(stripslashes(strip_tags($p_sSpam)));
    $p_sAdditional = trim(stripslashes(strip_tags($p_sAdditional)));
    $p_comment = trim(stripslashes(strip_tags($_POST['piv_name'] . ", " .
            $_POST['piv_email'] . ", " . $_POST['piv_url'] . ", " . $_POST["piv_comment"])));
    $fpHandle = fopen($spamkiller_log, "a");
    $p_sUrl = $_POST["piv_url"];

    switch($p_sType)  {
        case "trackback":
            $desc = "Trackback blocked";
            $text = $p_sSpam;
            break;
        case "htrackback":
            $desc = "Trackback blocked (Hardened)";
            $text = $p_sSpam;
            $p_sUrl = $p_sAdditional;
            break;
        case "hashcash":
            $desc = "Hashcash violation";
            $text = sprintf($p_comment);
            break;
        case "spamquiz":
            $desc = "Wrong Quiz Answer";
            $text = $p_comment;
            break;
        case "sskc":
            $desc = "Server Key check";
            $text = $p_comment;
            break;
        case "bpcomment":
            $desc = "Blocked Phrases comment";
            $text = $p_sSpam;
            break;
        case "bpreferer":
            $desc = "Blocked Phrases referer";
            $text = $p_sSpam;
            break;
        case "closedcomments":
            $desc = "Comment on closed entry";
            $text = $p_comment;
            break;
        default:
            if ($p_sAdditional) {
                $desc = $p_sAdditional;
            } else {
                $desc = "Comment blocked";
            }
            $text = $p_comment;
            if (!$text) {
                $text = $p_sSpam;
            }
            break;
    }
    // $text must not contain any newlines.
    $text = str_replace(array("\r\n", "\n", "\r"), " ",$text);
    $info = array($date, $desc, $_SERVER["REMOTE_ADDR"], $p_sUrl , $text);
    $sLogLine = implode(" #### ", $info)."\n";
    fwrite($fpHandle, $sLogLine);
    fclose($fpHandle);

    // Keeping a global spamcounter seemed like a nice idea at the time, but
    // apparently, it can cause the pv_cfg file to become corrupted. If there's
    // demand for this feature, we'll implement it properly..
    // $Cfg['spamcount']++;
    // SaveSettings();

}

/**
 * Fetches the spam log inserted into a table.
 *
 * @return string
 */
function get_spamlog()  {
    global $spamkiller_log;

    // Check if we need to trim the spam logfile..
    trim_spamlog();

    if (file_exists($spamkiller_log))  {

        $fpHandle = fopen($spamkiller_log, "r");
        $nTotalLines = count(file($spamkiller_log));
        $sLogRows = "";
        $nNumb=0;
        $bLimit = false;
        $nLineCount = 0;
        if(isset($_GET["limit"]))   {
            $bLimit = true;
        }
        while (!feof($fpHandle)) {
            $sBuffer = fgets($fpHandle, 4096);
            $nLineCount++;
            $sThisLine = explode("####", $sBuffer);

            // Output the line..
            if (($sThisLine[0] != "") && (count($sThisLine)>2)) {
                if(($bLimit) && ($nLineCount <= ($nTotalLines-$_GET["limit"]))) {
                    $sLogRows = "";
                } else  {

                    if (($nLineCount % 2)==0) {
                        $bg_color="even";
                    } else {
                        $bg_color="odd";
                    }

                    $sLogRows .= "<tr class=\"$bg_color\">";
                    $sLogRows .= "<td>".$sThisLine[0]."&nbsp; &nbsp;</td><td>";
                    $sLogRows .= "<a target=\"_blank\" href=\"http://centralops.net/co/DomainDossier.".
                            "aspx?dom_whois=1&amp;net_whois=1&amp;dom_dns=1&amp;addr=".
                            trim($sThisLine[2])."\">".$sThisLine[2]."</a>&nbsp; &nbsp;</td>";
                    $sLogRows .= "<td>".$sThisLine[1]."&nbsp; &nbsp;</td></tr>";                            
                    $sLogRows .= "<tr class=\"$bg_color\">";
                    $sLogRows .= "<td colspan=\"3\" valign='top' >".htmlspecialchars($sThisLine[3])." ";
                    if (strlen($sThisLine[3])>4) { $sLogRows .= "<br />"; }
                    $sLogRows .= htmlspecialchars(substr($sThisLine[4],0,100))."</td>";
                    $sLogRows .= "</tr>\n";
                    
                }
            }
            if($nNumb == 0)
                $nNumb=1;
            else
                $nNumb=0;
        }
        fclose($fpHandle);
        if (!empty($sLogRows)) {
            return "<table class=\"tabular\" border='0' cellspacing='0' cellpadding='0'>\n".
            "<tr class='tabular_nav'><th>Date/Time</th><th>IP</th><th>Rule</th></tr>\n".
            $sLogRows."</table>\n";
        } else {
            return "";
        }
    }
}

/**
 * Set the content of the spam log.
 *
 * @param string $text
 * @return void
 */
function set_spamlog($text="")  {
    global $spamkiller_log;
    if(file_exists($spamkiller_log))    {
        $fpHandle = fopen($spamkiller_log, "w");
        fwrite($fpHandle, $text);
        fclose($fpHandle);
    }
}


/**
 * This function trims the spamlog file down, if needed.
 *
 * The spam logfile can easily become very large.. So large that it can't be
 * read by common PHP installs without going over the amount of allocated
 * memory. This function checks the filesize, and trims the file if it
 * becomes to large.
 *
 * It trims off about 1/3 of the entire file. This is to prevent that it has to
 * be trimmed too often, since that would be very server-unfriendly.
 *
 * @return void
 */
function trim_spamlog() {
    global $spamkiller_log;

    // If it exists, and is larger than ~ 1mb, we will have to remove it,
    // because trying to read it, may cause a Fatal error..
    if( file_exists($spamkiller_log) && (filesize($spamkiller_log)>1000000) ) {
        unlink($spamkiller_log);
    }

    // If it exists, and is larger than ~ 250kb.
    if( file_exists($spamkiller_log) && (filesize($spamkiller_log)>250000) ) {

        // Read the file.
        $logfile = file($spamkiller_log);

        // Slice off 1/3 of the file.
        $lines = count($logfile);
        $logfile = array_slice($logfile, intval($lines/3));

        // Save it again.
        set_spamlog(implode("", $logfile));

    }
}

?>
