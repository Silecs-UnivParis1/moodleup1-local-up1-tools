#!/usr/bin/env php
<?php
/**
 * This script remove all old cohorts.
 *
 * @package      local_up1_tools
 * @copyright    (C) 2020 Université Paris 1
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require(__DIR__.'/../../cohort/lib.php');
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
        "Remove cohorts for a year

        Options:
        -h, --help            Print out this help

        Example:
        \$sudo -u www-data /usr/bin/php admin/cli/remove_old_users.php
        ";

    echo $help;
    die;
}

cli_heading('Looking for cohorts to delete');

$sql = "SELECT * 
        FROM {cohort} 
        WHERE  
            name like '[2015]%' 
           or  name like '[2014]%' 
           or  name like '[2013]%' 
           or  name like '[2012]%'";

$rs = $DB->get_recordset_sql($sql);

foreach ($rs as $cohort) {
    echo "Redeleting cohort $cohort->id : $cohort->name  \n";
    try {
        cohort_delete_cohort( $cohort);
    } catch (Exception $e) {
        echo "ERROR $e\n";
    }
    cli_heading( "End for " .$cohort->id);
}
$rs->close();

cli_heading('End '.time());

exit(0);
