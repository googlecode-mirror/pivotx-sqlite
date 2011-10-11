<?php


/**
 * Two Kings Form Class, to construct web based forms, do validation and
 * handle the output.
 *
 * For more information, read: http://twokings.eu/tools/
 *
 * Two Kings Form Class and all its parts are licensed under the GPL version 2.
 * see: http://www.twokings.eu/tools/license for more information.
 *
 * @version 1.1
 * @author Bob den Otter, bob@twokings.nl
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 * $Rev:: 98                                             $: SVN revision,
 * $Author:: pivotlog                                    $: author and
 * $Date:: 2006-09-07 22:31:37 +0200 (Thu, 07 Sep 2006)  $: date of last commit
 *
 */


//include_once("./config.inc.php");
include_once("./formclass.php");
include_once("./module_debug.php");

$value = $_GET['value'];
$criteria = explode('|', $_GET['validation']);

$form = new Form('ajax_validation');

$result = true;

foreach($criteria as $criterium) {

    //debug("check $criterium, $value = " . $form->validate_criterium($criterium, $value) );

    // Special case: 'ifany' - if value is empty, no further validation
    // will be done..
    if (($criterium == "ifany") && ($field['post_value']=="")) {
        echo 1;
        die();
    }

    $result = $result && $form->validate_criterium($criterium, $value);
}

//debug("ajax: " .  $result);

echo 0+$result;

?>