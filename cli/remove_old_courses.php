#!/usr/bin/env php
<?php
/**
 * This script remove all old courses.
 *
 * @package      local_up1_tools
 * @copyright    (C) 2020 Université Paris 1
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require(__DIR__.'/../../course/lib.php');
require_once($CFG->libdir.'/clilib.php');

cli_heading('Start '.time());

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help'=>false),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "Remove courses from a year

        Options:
        -h, --help            Print out this help

        Example:
        \$sudo -u www-data /usr/bin/php admin/cli/remove_old_users.php
        ";

    echo $help;
    die;
}

cli_heading('Looking for courses to delete');

$sql = "SELECT * 
        FROM {course} 
        WHERE  
            fullname like 'Archive année 2015-2016%' 
            or fullname like 'Archive année 2014-2015%'
            or fullname like 'Archive année 2013-2014%'
            or fullname like 'Archive année 2012-2013%'";

$rs = $DB->get_recordset_sql($sql);

foreach ($rs as $courses) {
    echo "Redeleting courses $courses->id: $user->shortname \n";
    $cms = $DB->get_records_select('course_modules', 'course = ?', array( $courses->id),'', 'id') ;    
    foreach ($cms as $cm) {
         try {
            course_delete_module($cm->id);
        } catch (Exception $e) {
            echo "ERROR $e\n";
        }
    }

    cli_heading( "End for " .$courses->id);
}
$rs->close();

cli_heading('End '.time());

exit(0);
