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
 * navdb main object.
 *
 * @package    mod_forum
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_navdb\data;
defined('MOODLE_INTERNAL') || die();

use stdClass;
use moodle_url;

/**
 * Quick search form renderable class.
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navdb {

    /** @var string current table */
    public $table;
    public $filter;
    public $limit;
    public $limitfrom;
    /** @var expanded results and filtered fields */
    public $muggle;

    /** @var database DB connection */
    public $db;
    /** @var starred tables */
    public $starred;

    private $query;
    private $fields;



    /**
     * Constructor.
     *
     * @param stdClass $params
     */
    public function __construct($params, $extras) {
        global $DB;
        // main params (trhough all navigation)
        $this->table = (isset($params->table)) ? $params->table : '';
        $this->filter = (isset($params->filter)) ? $params->filter : '';
        $this->limit = (isset($params->limit)) ? $params->limit : '';
        $this->limitfrom = (isset($params->limitfrom)) ? $params->limitfrom : '';
        $this->muggle = (isset($params->muggle)) ? $params->muggle : 0;
        // extra params (only apply to current query)
        $this->extras = new stdClass();
        $this->extras->orderfield = (isset($extras->orderfield)) ? $extras->orderfield : 'id';
        $this->extras->ordering = (isset($extras->ordering)) ? $extras->ordering : 'ASC';
        // configurations
        $this->db = $DB;
        $this->query = $this->getQueryParts();
        $this->starred = explode(',', get_config('tool_navdb', 'starred_tables'));
    }

    // getters

    /**
     * get a navdb url for a specific params
     * @param  array   $params        extra params for the current filter
     * @return [type]                 [description]
     */
    public function getUrl($params=array()) {
        $currentparams = $this->getNavigation();
        // override with extra params
        foreach ($params as $key => $value) {
            $currentparams[$key] = $value;
        }
        return new moodle_url('/admin/tool/navdb/index.php', $currentparams);
    }

    /**
     * return an array with current flters and navigation
     * @return array
     */
    public function getNavigation() {
        $params = array();
        foreach ($this as $key => $value) {
            if (!is_scalar($value)) continue;
            if (empty($value)) continue;
            $params[$key] = $value;
        }
        return $params;
    }

    /**
     * validate if a table name is valid
     * @param  String $tablename if false checks the current table
     * @return boolean            true if valid, false otherwise
     */
    public function isTableValid ($tablename = false) {
        if (!$tablename) $tablename = $this->table;
        if (empty($tablename)) return false;
        $manager = $this->db->get_manager();
        return $manager->table_exists($tablename);
    }

    /**
     * return DB list of tables
     * @return array of groups tables (->name ->subtables[])
     */
    public function getTables() {
        global $OUTPUT;
        $records = $this->db->get_tables(false);
        $tables = array();
        foreach ($records as $tablename) {
            $parts = explode('_',$tablename);
            if (!isset($tables[$parts[0]])) {
                $tables[$parts[0]] = array();
            }
            $tables[$parts[0]][] = $this->getTableData($tablename);
        }
        array_map('asort', $tables);
        ksort($tables);
        return $tables;
    }

    public function getTableData ($tablename) {
        $data = new stdClass();
        $data = new stdClass();
        $data->name = $tablename;
        $data->url = $this->getUrl(array('table'=>$tablename));
        $data->browseurl = $this->getUrl(array('table'=>$tablename,'filter'=>'*'));
        $data->settingsurl = $this->getUrl(array('table'=>$tablename,'show'=>'settings'));
        if (in_array($tablename, $this->starred)) $data->starred = true;
        return $data;
    }

    public function getOrderfield() {
        if (isset($this->extras->orderfield)) return $this->extras->orderfield;
        return 'id';
    }

    /**
     * return anr array of fields from selected table
     * @return array of database_column_info (or false)
     */
    public function getFields() {
        if (empty($this->table)) return false;
        $records = $this->db->get_columns($this->table, false);//$this->exdbb->get_records_sql($sql);
        if (!$records) return false;
        $fields = array();
        foreach ($records as $rec) {
            $fields[$rec->name] = $rec;
        }
        return $fields;
    }

    public function countRecords(){
        if (empty($this->filter)) return array();
        $query = $this->query;
        try {
            $result = $this->db->count_records_select($query->table, $query->where, $query->params);
            return $result;
        } catch (Exception $e) {
            return 0;
        } catch (\dml_exception $e) {
            $returnurl = $this->getUrl();
            notice( get_string('err_sql_error', 'tool_navdb', $e), $returnurl);
            return 0;
        }
    }

    public function getRecordSet () {
        if (empty($this->filter)) return array();
        try {
            $query = $this->query;
            if (empty($this->muggle)) {
                $records = $this->db->get_recordset_select(
                                $query->table,
                                $query->where,
                                $query->params,
                                $query->orderby,
                                implode(',',$query->fields),
                                $query->limitfrom,
                                $query->limit);
            } else {
                $records = $this->db->get_recordset_sql(
                        $query->expandedsql,
                        $query->params,
                        $query->limitfrom,
                        $query->limit);
            }
        } catch (Exception $e) {
            return false;
        } catch (\dml_exception $e) {
            $returnurl = $this->getUrl();
            notice( get_string('err_sql_error', 'tool_navdb', $e), $returnurl);
            return false;
        }
        // get fields
        if ($records) {
            $current = (array) $records->current();
            $this->query->rowfields = array_keys($current);
        }
        return $records;
    }

    public function getQueryFields() {
        if (!isset($this->query->fields)) return array();
        if (isset($this->query->rowfields)) return $this->query->rowfields;
        return $this->query->fields;
    }

    protected function getQueryParts () {
        if (empty($this->table)) return false;
        if (empty($this->filter)) return false;

        $query = new stdClass();
        $query->fields = array_keys($this->getFields());
        $query->table = $this->table;
        $query->params = array(); // TODO

        $where = '';
        // filter wildcards
        if ($this->filter=='*') $where = '1=1';
        // a numeric is ID filter by default
        if (is_numeric($this->filter)) $where = 'id='.$this->filter;
        //>,<,=,! plus a numeric is for ID also
        if (strlen($this->filter)>1) {
            $op = substr($this->filter,0,1);
            $ops = array('>','<','=','!');
            if (in_array($op,$ops)) $where = 'id'.$this->filter;
        }
        if (empty($where)) $where = $this->filter;
        $query->where = $where;

        // order
        $orderfield = (!empty($this->extras->orderfield)) ? $this->extras->orderfield : 'id';
        $ordering = (!empty($this->extras->ordering)) ? $this->extras->ordering : 'ASC';
        if (!in_array($orderfield, $query->fields)) {
            $orderfield = 'id';
            $ordering = 'ASC';
        }
        $query->orderby = $orderfield.' '.$ordering;

        // limit
        $query->limit = $this->limit;
        $query->limitfrom = $this->limitfrom;

        // check if we need to expand query with add joins
        $this->loadExpandedSQL($query);

        return $query;
    }

    private function loadExpandedSQL($query) {
        global $CFG;
        //fa les joins que necessiti
        $expandable = array(
            'course' => array('c.shortname, c.fullname, cat1.name as cat1, cat2.name as cat2', 'JOIN {course} c ON c.id=course LEFT JOIN {course_categories} cat1 ON cat1.id=c.category LEFT JOIN {course_categories} cat2 ON cat2.id=cat1.parent'),
            'courseid' => array('c.shortname, c.fullname, cat1.name as cat1, cat2.name as cat2', 'JOIN {course} c ON c.id=courseid LEFT JOIN {course_categories} cat1 ON cat1.id=c.category LEFT JOIN {course_categories} cat2 ON cat2.id=cat1.parent'),
            'userid' => array('u.username, u.idnumber, u.firstname, u.lastname', 'JOIN {user} u ON u.id=userid'),
            'user' => array('u.username, u.idnumber as document, u.firstname, u.lastname', 'JOIN {user} u ON u.id=user'),
            'roleid' => array('r.shortname as rolename', 'JOIN {role} r ON r.id=roleid'),
            'module' => array('m.name as modname', 'JOIN {modules} m ON m.id=module'),
            'contextid' => array('ctx.contextlevel, ctx.instanceid as contextinstance, ctx.path as contextpath', 'JOIN {context} ctx ON ctx.id=contextid'),
            'externalserviceid' => array('ext.name as externalname','JOIN {external_services} ext ON ext.id=externalserviceid'),
            );
        $select = array();
        $from = array('{'.$query->table.'} t');
        $filter = $query->where;
        $fields = $this->getMuggleFields($query);
        foreach ($fields as $field) {
            $select[] = 't.'.$field;
            //si el camp és ID es fa per expressió regural
            if ($field == 'id') {
                $count = null;
                $filter = preg_replace('/(^|\\s*[^a-zA-Z0-9\\.])(id)/', '\\1t.id', $filter, -1, $count);
            } else {
                $filter = str_replace($field, 't.'.$field, $filter);
            }
            if (isset($expandable[$field])) {
                $select[] = $expandable[$field][0];
                $from[] = $expandable[$field][1];
            }
        }
        $sql = 'SELECT '.implode(', ', $select).' FROM '.implode(' ', $from).' WHERE '.$filter;
        if (!empty($query->orderby)) $sql.= ' ORDER BY '.$query->orderby;
        $query->expandedsql = $sql;
        // return $this->exdbb->get_records_sql($sql, $params, $limitfrom, $limit);
    }

    private function getMuggleFields($query) {
        //camps que no es mostren en mode muggle
        $nomuggle_fields = array(
                        //camps de curs pero de nom raro que mai voldrem per muggles
                        'summary','summaryformat','newsitems','showreports','visibleold',
                        'calendartype','showgrades','marker','maxbytes','legacyfiles','groupmodeforce',
                        'defaultgroupingid','cacherev',
                        //camps de user que mai voldrem
                        'mnethostid','password','icq','skype','yahoo','aim','msn','phone1','phone2',
                        'secret','url','description','descriptionformat','mailformat','maildigest',
                        'maildisplay','autosubscribe','trackforums','trustbitmask','imagealt',
                        'lastnamephonetic','firstnamephonetic','middlename','alternatename','completionnotify',
                        //camps de logstore
                        'logstore_standard_log.eventname','logstore_standard_log.other',
                        'logstore_standard_log.objectid','logstore_standard_log.anonymous',
                        'logstore_standard_log.objecttable','logstore_standard_log.realuserid',
                        //camps dels moduls
                        'intro','instroformat',//general
                        'showexpanded','showdownloadfolder',//folder
                        'maxattachments','forcesubscribe','trackingtype',//forum
                        );
        $res = array();
        foreach ($query->fields as $field) {
            if (in_array($field, $nomuggle_fields)) continue;
            if (in_array($query->table.'.'.$field, $nomuggle_fields)) continue;
            $res[] = $field;
        }
        return $res;
    }

}
