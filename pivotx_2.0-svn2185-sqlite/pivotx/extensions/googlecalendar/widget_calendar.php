<?php
// - Extension: Google Calendar widget
// - Version: 0.1 - development
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Updatecheck: http://www.pivotx.net/update.php?ext=googlecalendar
// - Description: A widget to display your Google Calendar.
// - Date: 2007-12-23
// - Identifier: googlecalendar



global $googlecalendar_config;

$googlecalendar_config = array(
    'googlecalendar_id' => "",
    'googlecalendar_max_items' => 8,
    'googlecalendar_style' => 'widget-lg',
    'googlecalendar_header' => "<p><strong>My Calendar</strong></p>\n<ul>",
    'googlecalendar_footer' => "<ul>",
    'googlecalendar_format' => "<li><a href=\"%link%\">%title%</a> - <span class='date'>%date%</span></li>",
);



/**
 * Adds the hook for googlecalendarAdmin()
 *
 * @see googlecalendarAdmin()
 */
$this->addHook(
    'configuration_add',
    'googlecalendar',
    array("googlecalendarAdmin", "Google Calendar")
);



/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_googlecalendar()
 */
$this->addHook(
    'widget',
    'googlecalendar',
    "smarty_googlecalendar"
);




// Register 'googlecalendar' as a smarty tag.
$PIVOTX['template']->register_function('googlecalendar', 'smarty_googlecalendar');

/**
 * Output a googlecalendar feed
 *
 * @param array $params
 * @return string
 */
function smarty_googlecalendar($params) {
    global $googlecalendar_config, $PIVOTX;

    $style = get_default($PIVOTX['config']->get('googlecalendar_style'), $googlecalendar_config['googlecalendar_style']);

    $output = $PIVOTX['extensions']->getLoadCode('defer_file', 'googlecalendar/calendar.php', $style);

    return $output;

}



/**
 * The configuration screen for Del.iciou.us
 *
 * @param unknown_type $form_html
 */
function googlecalendarAdmin(&$form_html) {
    global $form_titles, $googlecalendar_config, $PIVOTX;

    $form = $PIVOTX['extensions']->getAdminForm('googlecalendar');



    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_id',
        'label' => "Feed",
        'value' => '',
        'error' => 'That\'s not a proper ID!',
        'text' => "The ID of your Google Calendar. This should look something like 'd8a49s3jgekpep0p1eiv7pueo4@group.calendar.google.com'.",
        'size' => 60,
        'isrequired' => 1,
        'validation' => 'string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_max_items',
        'label' => "Max. items",
        'value' => '',
        'error' => 'That\'s not a proper nickname!',
        'text' => "The maximum amount of items to show from your Calendar.",
        'size' => 5,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=60'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'googlecalendar_style',
        'label' => "Widget Style",
        'value' => '',
        'options' => getDefaultWidgetStyles(),
        'error' => 'That\'s not a proper style!',
        'text' => "Select the style to use for this widget.",

    ));


    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_header',
        'label' => "Header format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_format',
        'label' => "Output format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 5,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));


    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_footer',
        'label' => "Footer format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['googlecalendar'] = $PIVOTX['extensions']->getAdminFormHtml($form, $googlecalendar_config);


}


?>
