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
 * Row export for template class
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
class rowexport {

    private $navdb;
    private $rowoptions;

    /**
     * Constructor.
     *
     * @param \tool_navdb\data\navdb $params
     */
    public function __construct($navdb) {
        global $DB;
        $this->navdb = $navdb;
        $this->rowoptions = new \tool_navdb\data\rowoptions($navdb);
    }

    /**
     * generate data export the field list a DB records table. Each column export has:
     *   - name (column name)
     *   - ops  array of "order by" links
     *       - icon
     *       - url
     *   - [current] if it's the current "order by" field
     * @param  array  $fields array of current field names
     * @param  boolean $alone if the record is shown alone or in a table
     * @return array          array of columns
     */
    public function export_fields($fields, $alone=false) {
        global $OUTPUT;
        $currentorder = $this->navdb->getOrderfield();
        $fieldcols = array();
        foreach ($fields as $field) {
            $col = new stdClass();
            $col->name = $field;
            $col->ops = array();

            $op = new stdClass();
            $op->icon = $OUTPUT->pix_icon('i/down', 'Sort ASC');
            $op->url = $this->navdb->getUrl( array('orderfield'=>$field, 'ordering'=> 'ASC') );
            $col->ops[] = $op;

            $op = new stdClass();
            $op->icon = $OUTPUT->pix_icon('i/up', 'Sort DESC');
            $op->url = $this->navdb->getUrl( array('orderfield'=>$field, 'ordering'=> 'DESC') );
            $col->ops[] = $op;

            if ($currentorder == $field) $col->current = true;

            $fieldcols[] = $col;
        }
        return $fieldcols;
    }

    /**
     * Generate a export data from a database record. The result data has:
     *     - id record id
     *     - [hideoptions] if the record doesn't have special options
     *     - [options] array of options exported from rowoptions object
     *     - cols array of columns
     *         - name field name
     *         - text text to show on the table
     *         - title extra ifnormation to show when mouse over
     *         - url link to go when clic to the value
     * @param  record  $record database record
     * @param  boolean $alone  if the record is shown alone or in a table
     * @return mixed          record export data
     */
    public function export_row($record, $alone=false) {
        $data = new stdClass();
        $data->id = $record->id;
        // options
        $options = $this->rowoptions->get_options($this->navdb->table, $record, $alone);
        if (empty($options)) {
            $data->hideoptions = true;
        } else {
            $data->options = $options;
        }
        // fields
        $data->cols = array();
        $recordarray = (array) $record;
        foreach ($recordarray as $key => $val) {
            $data->cols[] = $this->format_field($key, $val, $record, $alone);
        }
        return $data;
    }

    // --- field conversions ---

    /**
     * Timestamps database field names
     * @return array of fieldnames
     */
    /*static function get_date_fields () {
        $fields = array('time','timecreated','timemodified','datainici','datafi','wakeuptime',
                    'timestart','timefinish','timeend','lastcron','timestamp','lastupdated','darreracomprovacio',
                    'darreraactivacio','dataencuat','locktime','overridden','exported','added','timeadded',
                    'timeopen','timeclose','firstaccess','lastaccess','lastlogin','currentlogin','nextruntime','expiry',
                    'cacherev'
        );
        return $fields;
    }*/

    /**
     * DB field that has a callback function
     * @return array of fieldnames => callback
     */
    static function get_callback_fields () {
        $fields = array(
                // generic fields
                'contextpath' => 'parse_context_path',
                'contextlevel' => 'parse_context_contextlevel',
                'contextinstance' => 'parse_context_instanceid',
                'id' => 'parse_id_field',
                'parent' => 'parse_parent_field',
                'role' => 'parse_roleid_field',
                'roleid' => 'parse_roleid_field',
                // table.field callback
                'block_instances.configdata' => 'parse_base64_field',
                'config_log.value' => 'parse_json_field',
                'config_log.oldvalue' => 'parse_json_field',
                'context.instanceid' => 'parse_context_instanceid',
                'context.contextlevel' => 'parse_context_contextlevel',
                'context.path' => 'parse_context_path',
                'course_modules.instance' => 'parse_course_modules_instance',
                'course_modules.module' => 'parse_course_modules_module',
                'course_categories.path' => 'parse_course_categories_path',
                'enrol.customint1' => 'parse_enrol_customint',
                'grade_items.iteminstance' => 'parse_grade_items_iteminstance',
                'grade_items.calculation' => 'parse_grade_items_calculation',
                'logstore_standard_log.other' => 'parse_serialize_field',
                'logstore_standard_log.objectid' => 'parse_logstore_standard_log_objectid',
                'user_info_data.fieldid' => 'parse_user_info_data_fieldid',
                'at_connections.params' => 'parse_json_field',
                'at_connections.mylog' => 'parse_json_field',
                'at_mylog.content' => 'parse_json_field',
                'at_mylog.lasterror' => 'parse_json_field',
                'at_course_reg.value' => 'parse_at_course_reg_value',
        );
        return $fields;
    }

    /**
     * DB direct foreign keys (replacing "VAL" for the current field value)
     * @return array of fieldname => array ( from_table, where_value)
     */
    static function get_translate_fields () {
        $fields = array(
                'context' => array('context','VAL'),
                'contextid' => array('context','VAL'),
                'parentcontextid' => array('context','VAL'),
                'user' => array('user','VAL'),
                'userid' => array('user','VAL'),
                'useridfrom' => array('user',"VAL"),
                'useridto' => array('user',"VAL"),
                'relateduserid' => array('user','VAL'),
                'metauserid' => array('user','VAL'),
                'course' => array('course','VAL'),
                'courseid' => array('course','VAL'),
                // 'role' => array('role','VAL'),
                // 'roleid' => array('role','VAL'),
                'cuaid' => array('at_cua','VAL'),
                'noproc' => array('at_cua_proces','VAL'),
                'procesid' => array('at_cua_proces','VAL'),
                'groupid' => array('groups','VAL'),
                'groupingid' => array('groupings','VAL'),
                'question' => array('question',"VAL"),
                'createdby' => array('user',"VAL"),
                'modifiedby' => array('user',"VAL"),
                'config_plugins.plugin' => array('config_plugins',"plugin='VAL'"),
                'course.category' => array('course_categories','VAL'),//cerca senzilla
                'at_cua_proces.origen' => array('at_cua_proces',"origen='VAL'"),
                'at_cua_proces.creador' => array('at_cua_proces',"creador='VAL'"),
                'at_cua_proces.estat' => array('at_cua_proces',"estat='VAL'"),
                'at_cua_registre.idreg' => array('at_cua_registre',"idreg='VAL'"),
                'at_cua_registre.document' => array('at_cua_registre',"document='VAL'"),
                'at_cua_registre.mylog' => array('at_mylog',"VAL"),
                'course_modules.section' => array('course_sections',"VAL"),
                'grade_items.categoryid' => array('grade_categories',"VAL"),
                'question.category' => array('question_categories',"VAL"),
                'role_capabilities.capability' => array('role_capabilities',"capability like 'VAL'"),
            );
        return $fields;
    }

    /**
     * fields that can be shortened when are displayed in a table
     * @return array of fieldnames
     */
    static function get_shorten_fields () {
        $fields = array('summary','modinfo','mylog','content','description','sectioncache','configdata');
        return $fields;
    }

    // --- field  general formatting ---

    /**
     * Generate a export data for a specific field. Data structure:
     *     - name: fieldname
     *     - text: HTML to display
     *     - [url]: text link destination
     *     - [title]: additional information about the value
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data as descrived above
     */
    function format_field ($field,$value,$rec,$alone=false) {
        global $CFG;
        $navdb = $this->navdb;
        $table = $navdb->table;
        $res = new stdClass();

        // callback fields
        $callback_fields = $this->get_callback_fields();
        if (isset($callback_fields[$field]) || isset($callback_fields[$table.'.'.$field])) {
            $pars = (isset($callback_fields[$table.'.'.$field]))? $callback_fields[$table.'.'.$field] : $callback_fields[$field];
            if (method_exists($this, $pars)) {
                $res = $this->$pars($table,$field,$value,$rec,$alone);
            }
        }
        // direct translation fields
        $translate_fields = $this->get_translate_fields();
        if (isset($translate_fields[$field]) || isset($translate_fields[$table.'.'.$field])) {
            $pars = (isset($translate_fields[$table.'.'.$field]))? $translate_fields[$table.'.'.$field] : $translate_fields[$field];
            $linkvalue = str_replace(':','%:%', $value); // chars : can broke SQL
            if (!empty($linkvalue)) { // empty and zero balues cannot be foreign keys
                $params = array(
                'table'  => $pars[0],
                'filter' => str_replace('VAL',$linkvalue, $pars[1]),
                );
                $res->url = $navdb->getUrl($params);
            }
        }

        // date translated fields
        $date_fields = \tool_navdb\data\tableexport::get_date_fields(); // $this->get_date_fields();
        if (in_array($field, $date_fields) || (strpos($field,'date')!==false && is_numeric($value))) {
            if ($navdb->muggle) {
                $res->text = userdate($value,get_string('strftimedatetime', 'langconfig'));
            } else {
                $res->title = userdate($value);
            }
        }

        if (!isset($res->text)) $res->text = $value;

        // shorten fields
        $shorten_fields = $this->get_shorten_fields();
        if (!$alone) {
            if (in_array($field, $shorten_fields) && !empty($res->text)) {
                $res->text = substr(strip_tags($value), 0, 20).'...';
            }
            if (in_array($table.'.'.$field, $shorten_fields) && !empty($res->text)) {
                $res->text = substr(strip_tags($value), 0, 20).'...';
            }
        }


        // si encara no té res, intentem fer el pareseig automagic
        if (empty($res->title) && empty($res->url) && $res->text == $value) {
            $res = $this->magic_format_field ($table,$field,$value,$rec,$alone);
        }

        if (!isset($res->name)) $res->name = $field;

        return $res;
    }

    /**
     * Find is a specific field is a foreign key
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function magic_format_field ($table,$field,$value,$rec,$alone=false) {
        global $DB;
        $navdb = $this->navdb;
        $res = new stdClass();
        $res->text = $value;
        // check if field ends with "id".
        if (substr($field,strlen($field)-2)=='id') {
            $fieldref = substr($field,0,strlen($field)-2);
            $parts = explode('_',$table);
            // tables to check.
            $possible_tables =array(
                    $fieldref,
                    $parts[0].'_'.$fieldref, // subtables.
                    $parts[0].'_'.$fieldref.'s', // subtables in plural with "s".
                    $parts[0].'_'.$fieldref.'es', // subtables in plural with "es".
                );
            // check tables
            foreach ($possible_tables as $tablename) {
                if ($navdb->db->get_manager()->table_exists($fieldref) && $value!=0) {
                    $params = array(
                        'table'  => $tablename,
                        'filter' => $value, // id.
                        );
                    $res->url = $navdb->getUrl($params);
                }
            }
        }
        // just for muggles: some empty fileds must appear as a "-"
        // to ensure the future export as CSV
        if ($this->navdb->muggle && empty($res->text)) {
            $nonempty = array('cat1','cat2','idnumber');
            if (in_array($field, $nonempty)) $res->text = '-';
        }
        return $res;
    }

    //--- field specific formatting ---

    /**
     * contextlevel field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_context_contextlevel ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        $res->title = \context_helper::get_level_name($value).' ('.$value.')';
        $params = array(
            'table'  => 'context',
            'filter' => "contextlevel=$value",
            );
        $res->url = $this->navdb->getUrl($params);
        return $res;
    }

    /**
     * context.instanceid field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_context_instanceid ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        // context query translation
        $contexts = array(
                CONTEXT_COURSE => 'course',
                CONTEXT_MODULE => 'course_modules',
                CONTEXT_USER => 'user',
                CONTEXT_BLOCK => 'block_instances',
                CONTEXT_COURSECAT => 'course_categories',
        );
        if (isset($contexts[$rec->contextlevel])) {
            $table = $contexts[$rec->contextlevel];
            $params = array('table' => $table,'filter' => $value);
            $res->url = $this->navdb->getUrl($params);
        }
        return $res;
    }

    /**
     * context.path field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_context_path ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        $parts = explode('/',$value);
        foreach($parts as $key=>$val) {
            if (empty($val)) continue;
            $params = array('table' => 'context','filter' => $val);
            $url = $this->navdb->getUrl($params);
            $parts[$key] = '<a href="'.$url.'">'.$val.'</a>';
        }
        $res->text = implode('/', $parts);
        return $res;
    }

    /**
     * coursemodules.instance field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_course_modules_instance ($table,$field,$value,$rec,$alone=false) {
        global $DB,$CFG;
        $res = new stdClass();
        if (!isset($this->modulenames)) $this->modulenames = array();
        if (!isset($this->modulenames[$rec->module])) {
            $mod = $this->navdb->db->get_record('modules', array('id'=>$rec->module));
            if ($mod) $this->modulenames[$rec->module] = $mod->name;
        }
        if (!isset($this->modulenames[$rec->module])) return $res;
        $params = array( 'table' => $this->modulenames[$rec->module], 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        $res->title = $this->modulenames[$rec->module];
        return $res;
    }

    /**
     * course_modules.module field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_course_modules_module ($table,$field,$value,$rec,$alone=false) {
        global $DB,$CFG;
        $res = new stdClass();
        if (!isset($this->modulenames)) $this->modulenames = array();
        if (!isset($this->modulenames[$rec->module])) {
            $mod = $this->navdb->db->get_record('modules', array('id'=>$rec->module));
            if ($mod) $this->modulenames[$rec->module] = $mod->name;
        }
        if (isset($this->modulenames[$rec->module])) {
            $params = array( 'table' => 'modules', 'filter' => $value);
            $res->url = $this->navdb->getUrl($params);
            $res->title = $this->modulenames[$rec->module];
        }
        return $res;
    }

    /**
     * course_categories.path field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_course_categories_path ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        $parts = explode('/',$value);
        foreach($parts as $key=>$val) {
            if (empty($val)) continue;
            $params = array( 'table' => 'course_categories', 'filter' => $value);
            $url = $this->navdb->getUrl($params);
            $parts[$key] = '<a href="'.$url.'">'.$val.'</a>';
        }
        $res->text = implode('/', $parts);
        return $res;
    }

    /**
     * enrol_customint field callback (for metacourses)
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_enrol_customint ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if ($rec->enrol == 'meta') {
            $params = array( 'table' => 'course', 'filter' => $value);
            $res->url = $this->navdb->getUrl($params);
        }
        return $res;
    }

    /**
     * logstore_standard_log.objectid field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_logstore_standard_log_objectid ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if (empty($rec->objecttable) || empty($value)) return $res;
        $params = array( 'table' => $rec->objecttable, 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        $res->tile = $rec->objecttable;
        return $res;
    }

    /**
     * grade_items.iteminstance field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_grade_items_iteminstance ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if (empty($rec->itemmodule) || empty($value)) return $res;
        $params = array( 'table' => $rec->itemmodule, 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        return $res;
    }

    /**
     * grade_items.calculation field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_grade_items_calculation ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if (empty($value)) return $res;
        $search = array();
        $replace = array();
        if (preg_match_all('/##gi(\d+)##/', $value, $matches)) {
            foreach ($matches[1] as $id) {
                $params = array( 'table' => 'grade_items', 'filter' => $id);
                $url = $this->navdb->getUrl($params);
                $search[] = '##gi'.$id.'##';
                $replace[] = '<b><a href="'.$url.'">&#35;&#35;gi'.$id.'&#35;&#35;</a></b>';
            }
        }
        $res->text = str_replace($search, $replace, $value);
        return $res;
    }

    /**
     * parse_user_info_data.fieldid field callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_user_info_data_fieldid ($table,$field,$value,$rec,$alone=false) {
        global $DB,$CFG;
        $res = new stdClass();
        if (!isset($this->userfieldnames)) $this->userfieldnames = array();
        if (!isset($this->userfieldnames[$value])) {
            $mod = $this->navdb->db->get_record('user_info_field', array('id'=>$value));
            if ($mod) $this->userfieldnames[$value] = $mod->name;
        }
        if (!isset($this->userfieldnames[$value])) return $res;
        $params = array( 'table' => 'user_info_field', 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        if ($this->navdb->muggle) {
            $res->text = $this->userfieldnames[$value];
        } else {
            $res->title = $this->userfieldnames[$value];
        }
        return $res;
    }

    /**
     * at_course_reg.value field callback (3rd party plugin)
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_at_course_reg_value ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if (is_numeric($value) && strlen($value)>9) {
            $res->title = userdate($value);
        }
        return $res;
    }

    /**
     * json fields callback parser
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_json_field ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if ($alone) {
            try {
                $val = json_decode($value);
                if (json_last_error() != JSON_ERROR_NONE || empty($val)) $val = json_decode(stripslashes($value));
                if (json_last_error() != JSON_ERROR_NONE) {
                    if (strpos($value, "\n")!==false) $value = '<pre>'.$value.'</pre>';
                    $res->text = $value;
                } else {
                    $res->text = '<pre>'.print_r($val,true).'</pre>';
                }
            } catch (Exception $e) {
                // do nothing
            }
        }
        return $res;
    }

    /**
     * serialized fields callback parser
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_serialize_field ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if ($alone) {
            try {
                $val = unserialize($value);
                if (empty($val)) $val = unserialize(stripslashes($value));
                $res->text = '<pre>'.print_r($val,true).'</pre>';
            } catch (Exception $e) {
                // do nothing
            }
        }
        return $res;
    }

    /**
     * base64 fields callback parser
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_base64_field ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if ($alone && !empty($value)) {
            try {
                $val = (array)unserialize(base64_decode($value));
                $res->text = '<pre>'.htmlentities(print_r($val,true)).'</pre>';
            } catch (Exception $e) {
                // do nothing
            }
        }
        return $res;
    }


    /**
     * generic ID fields callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_id_field ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        $params = array( 'table' => $table, 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        return $res;
    }

    /**
     * generic parent fields callback
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_parent_field ($table,$field,$value,$rec,$alone=false) {
        $res = new stdClass();
        if (!$value) return $res;
        $params = array( 'table' => $table, 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        return $res;
    }

    /**
     * generic roleid field callback (adding rolename to title)
     * @param $table table name
     * @param $field field name
     * @param $value current value
     * @param $rec full DB record
     * @param $alone=false if the record is shown alone or in a table
     * @return stdClass field data @see format_field function
     */
    function parse_roleid_field ($table,$field,$value,$rec,$alone=false) {
        global $DB;
        $res = new stdClass();
        if (!isset($this->rolenames)) $this->rolenames = array();
        if (!isset($this->rolenames[$value])) {
            $role = $this->navdb->db->get_record('role', array('id'=>$value));
            if ($role) {
                if (!empty($role->name)) {
                    $this->rolenames[$value] = $role->name;
                } else {
                    $this->rolenames[$value] = $role->shortname;
                }
            }
        }
        if (!isset($this->rolenames[$value])) return $res;
        $params = array( 'table' => 'role', 'filter' => $value);
        $res->url = $this->navdb->getUrl($params);
        $res->title = $this->rolenames[$value];
        return $res;
    }
}