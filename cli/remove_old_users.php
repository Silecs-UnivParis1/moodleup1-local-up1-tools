#!/usr/bin/env php
<?php
/**
 * This script remove all old users.
 *
 * @package      local_up1_tools
 * @copyright    (C) 2020 Université Paris 1
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
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
        "Fix user with more than 3 year without connection

        Options:
        -h, --help            Print out this help

        Example:
        \$sudo -u www-data /usr/bin/php admin/cli/remove_old_users.php
        ";

    echo $help;
    die;
}

cli_heading('Looking for user to delete');

$sql = "SELECT * 
        FROM {user} 
        WHERE lastlogin < unix_timestamp(CURDATE() - INTERVAL 3 year +  INTERVAL 0 SECOND) 
        AND  suspended=1 and deleted=0 limit 100";

$rs = $DB->get_recordset_sql($sql);
/*$sqlTables= "SELECT COLUMN_NAME, TABLE_NAME
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE COLUMN_NAME LIKE '%userid%'";
$rsTables = $DB->get_recordset_sql($sqlTables);*/
echo time() ;

foreach ($rs as $user) {
    if ($user->id === '94375') continue;
    echo "Redeleting user $user->id: $user->username ($user->email)\n";
    try {
        delete_user($user);
    } catch (Exception $e) {
        echo "ERROR $e\n";
    }
    // $DB->delete_records_select("user", "id = ".$user->id);
   /* cli_heading('Deleting all leftovers');

    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, "https://moodle-test3.univ-paris1.fr/webservice/rest/server.php?wstoken=e8033c4712516b466b28e38298d7d31f&wsfunction=local_gdpr_deleteuserdata_single&moodlewsrestformat=json&parameters[userid]=".$user->id);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // grab URL and pass it to the browser
    curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);
*/


    // https://moodle-test3.univ-paris1.fr/webservice/rest/server.php?wstoken=opensesame&wsfunction=local_gdpr_deleteuserdata_single&moodlewsrestformat=json&parameters[userid]=46334
/*
    foreach ($rsTables as $value) {
        $table_name = str_replace("mdl_", "" ,$value->table_name );
        $champs =$value->column_name;
        // cli_heading($table_name );
        // $sqlColumn = "SELECT *
        //      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        //      WHERE TABLE_NAME like '%"  .$table_name. "%'"  ;
        // $rsColumn = $DB->get_recordset_sql($sqlColumn);
        // foreach( $rsColumn  as $columns )
        // {
        //     cli_heading('From table ' .$table_name );
        //     print_r($columns);
        // }
        // $DB->delete_records_select($table_name, $champs ."=" .$user->id);
    } 
*/
    cli_heading( "End for " .$user->id);
}
//$rsTables->close();
$rs->close();

cli_heading('End '.time());

exit(0);
