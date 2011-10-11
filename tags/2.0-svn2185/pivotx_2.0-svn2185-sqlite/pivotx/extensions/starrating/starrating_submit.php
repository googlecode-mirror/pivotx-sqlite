<?php

require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');
initializePivotX();


// Make this user's 'unique' key:

$uniquekey = substr(md5($_SERVER['REMOTE_ADDR']. $_SERVER['HTTP_USER_AGENT']), 0, 8);



// Let's get the key from the URL..
foreach($_GET as $key=>$value) {
    
    list ($dummy, $uid) = explode("-", $key);
        
    if ($dummy=="entry" && (intval($uid)!=0) && (intval($value)!=0) ) {
     
        // If we get here, we have a numerical entry UID, and a value.

        $entry = $PIVOTX['db']->read_entry(intval($uid));

        if (is_array($entry['extrafields']['ratings'])) {
            $ratings = $entry['extrafields']['ratings'];
        } else {
            $ratings = array();
        }
        
        $entry['extrafields']['ratings'][$uniquekey] = $value;
        $entry['extrafields']['ratingcount'] = count($entry['extrafields']['ratings']);
        $entry['extrafields']['ratingaverage'] = array_sum($entry['extrafields']['ratings']) / $entry['extrafields']['ratingcount'];
        
        $PIVOTX['db']->set_entry($entry);
        $PIVOTX['db']->save_entry(true);
        
    }    
}

echo "<p>Thank you for voting!</p>";
echo "<p>Use your browser's back button to go back to the previous page.</p>";

?>
