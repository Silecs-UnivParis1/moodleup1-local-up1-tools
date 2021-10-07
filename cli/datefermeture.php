#!/usr/bin/env php
<?php
/**
 * @package    local_up1_tools
 * @copyright  2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \local_up1_tools\migrate_datefermeture;

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/cohortsyncup1/locallib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params([
        'help' => false, 'verbose' => 1, 'limit' => 0,
        'diag'=>false, 'convert-datefermeture' => false, 'delete-datefermeture' => false]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Convertit la métadonnée up1_datefermeture en attribut standard course.enddate. One-shot.

Options:
--diag                   affiche un diagnostic / comptage des conversions à faire.
--convert-datefermeture  applique la conversion
--delete-datefermeture   supprime la métadonnée up1_datefermeture définitivement

--verbose=N           Verbosité (0 to 3), 1 par défaut.
--limit=N             Pour les tests, agit seulement sur les N premiers enregistrements en table.
";


if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}

if ( $options['diag'] ) {
    $process = new migrate_datefermeture($options['verbose'], $options['limit']);
    printf("%d cours à mettre à jour\n", $process->get_number_to_migrate());
    echo "  (course.enddate à 0 et datefermeture > 0)\n\n";

    if ($options['verbose'] > 1) {
        printf("%5s  %12s  %12s  %12s \n", 'crsid', 'startdate', 'datefermeture', 'newenddate');
        foreach ($process->get_preview_to_migrate() as $row) {
            printf("%5d  %12d  %12d  %12d \n", $row->instanceid, $row->startdate, $row->datefermeture, $row->newenddate);
        }
    }
    return 0;
}

if ( $options['convert-datefermeture'] ) {
    $process = new migrate_datefermeture($options['verbose'], $options['limit']);
    echo $process->migrate() . "\n";
    printf("%d cours à mettre à jour\n", $process->get_number_to_migrate());
}

if ( $options['delete-datefermeture'] ) {
    $process = new migrate_datefermeture($options['verbose'], $options['limit']);
    $nb = $process->get_number_to_migrate();
    if ($nb > 0) {
        die("Il reste $nb cours à convertir, je refuse de supprimer up1datefermeture.\n");
        return 1;
    }
    echo "Suppression des champs up1datefermeture... ";
    $process->delete_datefermeture();
    echo "OK.\n\n";
}