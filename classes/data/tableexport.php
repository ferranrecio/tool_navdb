<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Table export for template class
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_navdb\data;
defined('MOODLE_INTERNAL') || die();

use stdClass;
use moodle_url;

/**
 * DB Row formatter
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tableexport {

    private $navdb;
    private $operations = array(
        '=' => 'FLD=VAL',
        'like' => 'FLD like VAL',
        '<' => 'FLD<VAL',
        '>' => 'FLD>VAL',
        '<>' => 'FLD<>VAL',
        'not_like' => 'FLD not like VAL',
        'in' => 'FLD in (VAL)'
    );

    /**
     * Constructor.
     *
     * @param \tool_navdb\data\navdb $params
     */
    public function __construct($navdb) {
        global $DB;
        $this->navdb = $navdb;
        // this code leads to overcomplicated and non-standard SQL filters
        // for now is better to use standard SQL
        // $this->operations['='] = $this->navdb->db->sql_equal('FLD','VAL');
        // $this->operations['like'] = $this->navdb->db->sql_like('FLD','VAL',false, false);
        // $this->operations['<'] = str_replace('=', '<', $this->operations['=']);
        // $this->operations['>'] = str_replace('=', '>', $this->operations['=']);
        // $this->operations['<>'] = $this->navdb->db->sql_equal('FLD','VAL',true,true,true);
        // $this->operations['not_like'] = $this->navdb->db->sql_like('FLD','VAL',false, false, true);
    }

    public function export_navigation () {
        global $OUTPUT;
        $navigation = array();
        return $navigation;
    }

    /**
     * returns a $data representation of the current table fields
     * @return stdClass table data object or false if a invalid table is selected
     */
    public function export_table_fields () {
        $data = new stdClass();
        if (!$this->navdb->isTableValid()) return false;
        $data->table = $this->navdb->table;
        $data->url = $this->navdb->getUrl(array('table'=>$data->table));
        $data->browseurl = $this->navdb->getUrl(array('table'=>$data->table, 'filter'=>'*'));
        $fields = $this->navdb->getFields();
        if (empty($fields)) return $data;
        $data->fields = array();
        foreach ($fields as $fieldobj) {
            $fld = new stdClass();
            $fld->name = $fieldobj->name;
            $fld->dbtype = $fieldobj->type;
            if ($fieldobj->max_length > 0) {
                $fld->dbtype.= " ($fieldobj->max_length)";
            }
            $fld->ops = $this->getOperationsData($fieldobj);
            list($fld->helper, $fld->type) = $this->getHelperData($fieldobj);
            $data->fields[] = $fld;
        }
        $data->operations = $this->operations;
        $data->rawfields = $fields;
        return $data;
    }

    public function getOperationsData($fieldobj) {
        $numeric_ops = array('=','<','>','<>', 'in');
        $char_ops = array('=','like','<>','not_like', 'in');
        $data = array();
        foreach ($this->operations as $key => $sql) {
            $op = new stdClass();
            // add general data conversion and filtering
            switch ($fieldobj->meta_type) {
                case 'C': // XMLDB_TYPE_CHAR
                case 'X': // XMLDB_TYPE_TEXT:
                    $op->transform = 'addslashes';
                    break;
                case 'I': // XMLDB_TYPE_INTEGER
                case 'R': // XMLDB_TYPE_INTEGER
                case XMLDB_TYPE_FLOAT:
                    $op->transform = 'integer';
                    break;
                case 'N': // XMLDB_TYPE_NUMBER
                case 'F': // XMLDB_TYPE_NUMBER
                case XMLDB_TYPE_FLOAT:
                    $op->transform = 'numeric';
                    break;
            }
            if ($key == 'in') {
                $op->transform.= '_array';
            }
            if ($op->transform == 'addslashes' && !in_array($key, $char_ops)) continue;
            if ($op->transform == 'integer' && !in_array($key, $numeric_ops)) continue;
            if ($op->transform == 'numeric' && !in_array($key, $numeric_ops)) continue;
            // SQL and other
            $op->name = $key;
            $op->value = $key;
            $op->sql = str_replace('FLD', $fieldobj->name, $sql);
            $data[] = $op;
        }

        return $data;
    }

    public function getHelperData($fieldobj) {
        if (in_array($fieldobj->name, $this->get_userid_fields() )) return array('userid', PARAM_INT);
        if (in_array($fieldobj->name, $this->get_courseid_fields() )) return array('courseid', PARAM_INT);
        if (in_array($fieldobj->name, $this->get_contextid_fields() )) return array('contextid', PARAM_INT);
        if (in_array($fieldobj->name, $this->get_roleid_fields() )) return array('roleid', PARAM_INT);
        if (in_array($fieldobj->name, $this->get_date_fields() )) return array('date', PARAM_INT);
        return array('undefined', PARAM_RAW);
    }

    /**
     * Userid database field names
     * @return array of fieldnames
     */
    public static function get_userid_fields () {
        $fields = array('user', 'userid', 'useridfrom', 'useridto');
        return $fields;
    }

    /**
     * Courseid database field names
     * @return array of fieldnames
     */
    public static function get_courseid_fields () {
        $fields = array('course', 'courseid');
        return $fields;
    }

    /**
     * Roleids database field names
     * @return array of fieldnames
     */
    public static function get_roleid_fields () {
        $fields = array('role', 'roleid');
        return $fields;
    }

    /**
     * Contextid database field names
     * @return array of fieldnames
     */
    public static function get_contextid_fields () {
        $fields = array('context', 'contextid');
        return $fields;
    }

    /**
     * Timestamps database field names
     * @return array of fieldnames
     */
    public static function get_date_fields () {
        $fields = array('time','timecreated','timemodified','datainici','datafi','wakeuptime',
                    'timestart','timefinish','timeend','lastcron','timestamp','lastupdated',
                    'darreracomprovacio', 'darreraactivacio','dataencuat','locktime',
                    'overridden','exported','added','timeadded','timeopen','timeclose',
                    'firstaccess','lastaccess','lastlogin','currentlogin','nextruntime','expiry',
                    'cacherev'
                );
        return $fields;
    }
}
