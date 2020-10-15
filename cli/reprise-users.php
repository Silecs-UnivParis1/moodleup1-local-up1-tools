#!/usr/bin/env php
<?php
/**
 * @package      local
 * @subpackage   paris1tools
 * @copyright    (C)2013-2020 Silecs
 */

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))) . '/config.php');
require_once($CFG->libdir.'/clilib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(
        ['help'=>false,  'run'=>false, 'truncate' =>false],
        ['h'=>'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


$help =
"Reprise des champs des profils utilisateurs

Options:
-h, --help               Affiche cette aide
--run                    lance la reprise des tables custom_info_* (utilisateurs) dans user_info_*
--truncate               vide les tables user_info_* 

À utiliser une seule fois.
";


$sufftables = ['category', 'field', 'data'];

if (!empty($options['help']) ) {
    echo $help;
    return 0;
}

if ($options['truncate']) {
	echo "Vidage des tables :\n"; 
    
	foreach ($sufftables as $sufftable) {
        $table = 'mdl_user_info_' . $sufftable;
        echo "$table... ";
        $DB->execute("TRUNCATE TABLE $table");
        echo "OK.\n";
        }
    }

if ($options['run'] ) {
	echo "Reprise utilisateurs :\n"; 
    
	foreach ($sufftables as $sufftable) {
        if ($DB->record_exists('user_info_' . $sufftable, [])) {
			echo "Attention : la table user_info_$sufftable n'est pas vide. La reprise a déjà été effectuée ?\n";
			return 1;
        }
    }

    echo "user_info_category -> custom_info_category ... ";
	$fields = 'id, name, sortorder';
	$sql = sprintf("INSERT INTO mdl_user_info_category(%s) SELECT %s FROM mdl_custom_info_category WHERE objectname='user'",
        $fields, $fields); 
    $DB->execute($sql);
    echo "OK.\n";

    echo "user_info_field -> custom_info_field ... ";
	$fields = 'id, shortname, name, datatype, description, descriptionformat, categoryid, sortorder, required, locked, visible, forceunique, signup, defaultdata, defaultdataformat, param1, param2, param3, param4, param5';
	$sql = sprintf("INSERT INTO mdl_user_info_field(%s) SELECT %s FROM mdl_custom_info_field WHERE objectname='user'",
        $fields, $fields); 
    $DB->execute($sql);
    echo "OK.\n";

    echo "user_info_data -> custom_info_data ... ";
	$fields = 'id, userid, fieldid, data, dataformat';
	$sql = sprintf("INSERT INTO mdl_user_info_data(%s) SELECT %s FROM mdl_custom_info_data WHERE objectname='user'",
        $fields, str_replace('userid', 'objectid', $fields)); 
    $DB->execute($sql);
    echo "OK.\n";

}

