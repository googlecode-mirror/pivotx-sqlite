<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
	       <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Converting </title>
	</head>
	<body>
<?php


// Set to 'false' if you'd like to renumber all entries, starting with '1'
$keepuids = true;


// Set to 'true' if you wish to convert the entries to UTF-8, whilst importing.
$convert_iso88591 = false;


$categorylist = array();

// A quick and dirty tool to convert flat file entries to mysql!!

// Before we get close to ditibuting 2.0, we'll need to make this look better.

require_once('lib.php');

initializePivotX();


if ($PIVOTX['db']->db_type!="sql") {
    echo "<strong>Please set up MySQL in Pivot.</strong><br /><br />
        This utility will convert your old flat-file entries to mySQL, but you'll need to set up Pivot for MySQL first!";
    die();
}

if (empty($_GET['action'])) {
	echo "<p>This is a preview of the conversion. If all seems ok, you must click 'import' at the bottom of this page.</p>";
	$confirmed = false;
} else {
	echo "<p>I'm going to convert all your entries now!.</p>";
	$confirmed = true;
}


$d = dir("./db");

while ($filename=$d->read()) {
    $pos=strpos($filename, "standard-");
    if ( (!($pos===FALSE)) && ($pos==0) ) {
        echo "dir: $filename<br />\n";
        index_entries($filename);
        flush();
    }
}
$d->close();


// After the entries, we'll see if we need to add categories:

$existingcats = $PIVOTX['categories']->getCategorynames();

foreach($categorylist as $name=>$display) {
    if(!in_array($name, $existingcats)) {
        // We have to add this one..
        $PIVOTX['categories']->setCategory($name, array('name'=>$name, 'display'=>$display));
    }
}


debug("Finished converting Entries!");





// -----------




// given a dirname, this will index the entries in that directory
function index_entries($dirname) {
    global $PIVOTX, $categorylist, $confirmed, $keepuids, $convert_iso88591;

	$entriestable = safe_string($PIVOTX['config']->get('db_prefix')."entries", true);

  // Set up DB factory
  $sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
   														 $PIVOTX['config']->get('db_databasename'),
   														 $PIVOTX['config']->get('db_hostname'),
   														 $PIVOTX['config']->get('db_username'),
   														 $PIVOTX['config']->get('db_password')
  								  );


  // Set up DB connection
  $sql = $sqlFactory->getSqlInstance();

    if (is_dir("./db/".$dirname)) {
        $d= dir("./db/".$dirname);

        while ($filename=$d->read()) {
            if ((strlen($filename)==9) && getextension($filename)=="php") {
                $filelist[] = $filename;
            }
        }

		asort($filelist);

        foreach($filelist as $file) {

            $entry = load_serialize("./db/".$dirname."/".$file, false);

            if(!$entry) {
                $entry = liberal_unserialize("./db/".$dirname."/".$file);
            }

            if ($convert_iso88591) {
                $entry = convertFromIso88591($entry);
                
            }


            
            if(is_array($entry['category'])) {
                
                foreach ($entry['category'] as $key => $cat) {
                    // Collect the categories, see if we need to add them later.
                    $categorylist[safe_string($cat, true)] = $cat;

                    // Convert the categories to 'safe strings.
                    $entry['category'][$key] = strtolower(safe_string($cat));
                }
                    
            }

           
            echo "Entry: ($file / " .$entry['code'] . ") " . $entry['title']. " - cats: " .
                implode(", ", $entry['category']) . " - tags: " . $entry['keywords'] . "<br />\n";





            if ($confirmed) {

                if (!empty($entry['code']) && ($keepuids==false)) {
	            unset($entry['code']);
			} else {
				$entry['uid'] = $entry['code'];
				
				// We're doing a bit of an ugly hack here.. By making sure there's an entry with the current ID in de DB,
				// we trick PivotX into updating that one, instead of making a new one. 
				$sql->query("DELETE FROM $entriestable WHERE uid=" . intval($entry['uid']));
				$sql->query("INSERT INTO $entriestable (`uid`) VALUES ('".intval($entry['uid'])."');");
				
			}
			
	            $PIVOTX['db']->set_entry($entry);
	            $PIVOTX['db']->save_entry();
			}

            // TODO: Delete the entry, to prevent importing the same thing over and over..

            // for testing purposes, we can stop after a given number of entries..
            // if ($counter++ > 5) { die(); }

        }



    }
}


if (empty($_GET['action'])) {
	echo "<p>If this looks ok, click the following link to import your entries: <a href='convertentries2sql.php?action=1'>Import Entries</a></p>";
} else {
	echo "<p><strong>All done!<strong></p>";
	
}


function convertFromIso88591($entry) {
    
    $entry['title'] = utf8_encode($entry['title']);
    $entry['subtitle'] = utf8_encode($entry['subtitle']);
    $entry['introduction'] = utf8_encode($entry['introduction']);
    $entry['body'] = utf8_encode($entry['body']);
    $entry['keywords'] = utf8_encode($entry['keywords']);
    
    $entry['category'] = array_map('utf8_encode', $entry['category']);
    
    return $entry;

}



?>

	</body>
</html>
