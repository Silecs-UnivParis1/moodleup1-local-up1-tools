<?php
/**
 * @package    local_up1_tools
 * @copyright  2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_up1_tools;

/**
 * Méthodes one-shot pour transformer la métadonnée de cours up1_datefermeture en champ standard course.enddate
 * Ticket M#5015
 */

class migrate_datefermeture
{
    private $verbose;
    private $limit;

    /**
     *
     * @param int $verbose
     */
    function __construct(int $verbose, int $limit)
    {
        $this->verbose = $verbose;
        $this->limit = $limit;
    }

    private function get_jointure($set = '')
    {
        $sql = "FROM {customfield_data} CD "
            . "JOIN {customfield_field} CF ON (CF.id = CD.fieldid) "
            . "JOIN {course} C ON (C.id=CD.instanceid) "
            . $set . " "
            . "WHERE CF.shortname='up1datefermeture' AND enddate=0 AND intvalue>0 "
            . ($this->limit > 0 ? "LIMIT " . $this->limit : "");
        return $sql;
    }

    /**
     *
     * @global \moodle_database $DB
     * @return int
     */
    public function get_number_to_migrate()
    {
        global $DB;
        $sql = "SELECT COUNT(*) " . $this->get_jointure();
        return $DB->count_records_sql($sql);
    }

    /**
     *
     * @global \moodle_database $DB
     * @return array
     */
    public function get_preview_to_migrate()
    {
        global $DB;
        $sql = "SELECT instanceid, startdate,  intvalue AS datefermeture, GREATEST(intvalue, startdate) AS newenddate "
            . $this->get_jointure();
        return $DB->get_records_sql($sql);
    }

    /**
     *
     * @global \moodle_database $DB
     * @return int
     */
    public function migrate()
    {
        global $DB;
        $sql = "UPDATE {customfield_data} CD "
            . "JOIN {customfield_field} CF ON (CF.id = CD.fieldid) "
            . "JOIN {course} C ON (C.id=CD.instanceid) "
            . "SET C.enddate = GREATEST(intvalue, startdate) "
            . "WHERE CF.shortname='up1datefermeture' AND enddate=0 AND intvalue>0 "
            . ($this->limit > 0 ? "LIMIT " . $this->limit : "");
        $diag = $DB->execute($sql);
        return $diag;
    }

    /**
     *
     * @global \moodle_database $DB
     * @return bool
     */
    public function delete_datefermeture()
    {
        global $DB;
        $fid = $DB->get_field('customfield_field', 'id', ['shortname' => 'up1datefermeture'], MUST_EXIST);

        $DB->delete_records('customfield_data', ['fieldid' => $fid]);
        $DB->delete_records('customfield_field', ['shortname' => 'up1datefermeture']);
        return true;
    }

/*
SELECT instanceid, startdate, FROM_UNIXTIME(startdate) AS startdateH,  intvalue AS datefermeture, FROM_UNIXTIME(intvalue) AS datefermetureH, enddate, FROM_UNIXTIME(enddate) AS enddateH
FROM mdl_customfield_data CD
JOIN mdl_customfield_field CF ON (CF.id = CD.fieldid)
JOIN mdl_course C ON (C.id=CD.instanceid)
WHERE CF.shortname='up1datefermeture' AND enddate=0 AND intvalue>0;
 */

}