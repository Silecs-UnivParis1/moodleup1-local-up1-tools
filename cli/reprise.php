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
        ['help'=>false,  'run-field'=>false,  'run-data' =>false],
        ['h'=>'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


$help =
"Reprise des champs supplémentaires des cours

Options:
-h, --help               Affiche cette aide
--run-field              lance la reprise des tables custom_info_category et custom_info_field
                         (vers customfield_category et customfield_field)
						 Ne fonctionne que si les tables cibles sont vides.
--run-data               Lance la reprise de la table custom_info_data (LONG). 
						 Ne fonctionne que si la table customfield_data est vide.

A utiliser une seule fois, de la manière suivante :

	php7.3 cli/reprise.php --run-field
puis : 
	php7.3 cli/reprise.php --run-data
						  
";


if (!empty($options['help']) ) {
    echo $help;
    return 0;
}

if ($options['run-field']) {
    echo "Reprise des catégories et champs\n";       
    if ($DB->record_exists('customfield_field', [])) {
        echo "Attention : la tables customfield_field n'est pas vide. La reprise a déjà été effectuée\n";
        exit;
    }
    $time = time();
    $systemcontext = context_system::instance();
    $custom_info_category_course = $DB->get_records('custom_info_category', ['objectname' => 'course']);
    if ($custom_info_category_course) {
        foreach ($custom_info_category_course as $cat) {
            echo $cat->name . "\n";
            $newcat = (object)['name'=> $cat->name,
                'sortorder' => $cat->sortorder, 'descriptionformat' => 0,
                'component' => 'core_course', 'area' => 'course', 'contextid' => $systemcontext->id,
                'timecreated' => $time, 'timemodified' => $time
            ];
            $categoryid = $DB->insert_record('customfield_category', $newcat);

            $custom_info_field_course = $DB->get_records('custom_info_field', ['objectname' => 'course', 'categoryid' => $cat->id]);
            if ($custom_info_field_course) {
                foreach ($custom_info_field_course as $field) {
                    createinfofield($field, $categoryid);
                }
            }
        }
    }
}

if ($options['run-data']) {
    echo "Reprise des données custom_info_data\n";
    if ($DB->record_exists('customfield_data', [])) {
        echo "Attention : la tables customfield_data n'est pas vide. La reprise a déjà été effectuée\n";
        exit;
    }

    $nbc = 0;
    $textarea = ['up1rofpathid', 'up1specialite', 'up1nomnorme', 'up1abregenorme',  'up1rofname', 'up1diplome'];

    $oldfields = $DB->get_records('custom_info_field', ['objectname' => 'course'], '', 'id, shortname, datatype, categoryid');
    $courses = $DB->get_records('course');

    if ($courses) {
        $heuredeb = date('H:i:s');
        echo "On commence la reprise à $heuredeb \n";
        foreach ($courses as $course) {
            ++$nbc;
            $infos = $DB->get_records('custom_info_data', ['objectname' => 'course', 'objectid' => $course->id]);
            $coursedata = ['id' => $course->id];

            foreach ($infos as $info) {
                $name = $oldfields[$info->fieldid]->shortname;
                if (in_array($name, $textarea)) {
                        $donnees = ['text' => trim($info->data), 'format' => 2];
                        $coursedata['customfield_' . $name . '_editor'] = $donnees;
                } else {
                    $coursedata['customfield_' . $name] = trim($info->data);
                }
            }
            $instance = (object) $coursedata;
            $contextcourse = \context_course::instance($instance->id);

            $handler = core_course\customfield\course_handler::create();
            $editablefields = $handler->get_fields();
            $fields = core_customfield\api::get_instance_fields_data($editablefields, $instance->id);

            foreach ($fields as $data) {
                if (!$data->get('id')) {
                    $data->set('contextid', $contextcourse->id);
                }
                $data->instance_form_save($instance);
            }

            echo '.';
        }
    }
    echo "\nFin de la reprise : $nbc traités";
    $heurefin = date('H:i:s');
    echo "\nFin de la reprise à $heurefin \n";
}


function createinfofield($oldfield, $categoryid) {
	
	$time = time();
	$datemax = strtotime('12/31/2030 23:59:59');
	$typetab = ['text' => 'text', 'checkbox' => 'checkbox', 'datetime' => 'date' ];
	
	$textarea = ['up1rofpathid', 'up1specialite', 'up1nomnorme', 'up1abregenorme',  'up1rofname', 'up1diplome'];

	$type = $typetab[$oldfield->datatype];
	
	if (in_array($oldfield->shortname, $textarea)) {
		$type = 'textarea';
	}
	
	$category = \core_customfield\category_controller::create($categoryid);
	$field = \core_customfield\field_controller::create(0, (object)['type' => $type], $category);
	
	$handler = $field->get_handler();
 
	$configdata = ['required' => $oldfield->required, 'locked' => $oldfield->locked, 
		'visibility' => $oldfield->visible, 'uniquevalues' => $oldfield->forceunique, 
		'defaultvalue' => $oldfield->defaultdata
	];
	
	if ($type == 'checkbox') {
		$configdata['checkbydefault'] = $oldfield->defaultdata;
	}
	
	if ($type == 'date') {
		$configdata['includetime'] = $oldfield->param3;
		$datemin = strtotime('01/01/'.$oldfield->param1);
		
		$configdata['mindate'] = $datemin;
		$configdata['maxdate'] = $datemax;
	}
	
	if ($type == 'text') {
		$configdata['displaysize'] = $oldfield->param1;
		$configdata['maxlength'] = 1333;
		$configdata['ispassword'] = $oldfield->param3;
		$configdata['link'] = $oldfield->param4;
		$configdata['linktarget'] = $oldfield->param5;
	}
	
	if ($type == 'textarea') {
		$configdata['defaultvalueformat'] = 2;
	}
	
	$data = (object)['name' => $oldfield->name, 'shortname' => $oldfield->shortname, 
	 'sortorder' => $oldfield->sortorder, 'descriptionformat' => $oldfield->descriptionformat , 'description' => $oldfield->description,
	 'configdata' => $configdata]; 
	$handler->save_field_configuration($field, $data);
}
