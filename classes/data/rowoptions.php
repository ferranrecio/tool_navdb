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
 * Row options for specific DB records
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
 * Specifics DB record options
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rowoptions {

    private $navdb;
    private $modules;

    /**
     * Constructor.
     *
     * @param \tool_navdb\data\navdb $params
     */
    public function __construct($navdb) {
        global $DB;
        $this->navdb = $navdb;
    }

    public function get_options($table, $row, $alone=false) {
        $navdb = $this->navdb;
        $options = array();
        $method = 'row_options_'.$table;
        if (method_exists($this, $method)) {
            $options = $this->$method($row, $alone);
        }
        // check if it's an activity module
        if (!isset($this->moduleid)) {
            if ($navdb->db->get_manager()->table_exists('modules')) {
                $this->moduleid = $this->navdb->db->get_field('modules','id',array('name'=>$table));
            } else {
                $this->moduleid = 0;
            }
        }
        if ($this->moduleid) {
            $options = array_merge($options,$this->row_options_special_modules($table,$row,$this->moduleid));
        }
        return $options;
    }

    private function new_option($text, $plugin='tool_navdb', $icon='i/db') {
        global $OUTPUT;
        $op = new stdClass();
        $op->text = get_string($text, $plugin);
        $op->icon = $OUTPUT->pix_icon($icon, '');
        return $op;
    }

    function row_options_special_modules($table,$row,$moduleid) {
        $options = array();
        $op = $this->new_option('goto_course_modules','tool_navdb');
        $params = array( 'table' => 'course_modules', 'filter' => 'module='.$moduleid.' AND instance='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;
        return $options;
    }

    function row_options_course ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_course','tool_navdb','i/course');
        $op->url = new moodle_url('/course/view.php', array('id' => $row->id));
        $options[] = $op;

        $op = $this->new_option('view_course_enrolments','tool_navdb','i/enrolusers');
        $op->url = new moodle_url('/enrol/instances.php', array('id' => $row->id));
        $options[] = $op;

        $op = $this->new_option('view_course_delete','tool_navdb','i/delete');
        $op->url = new moodle_url('/course/delete.php', array('id' => $row->id));
        $options[] = $op;

        $context = \context_course::instance($row->id);
        $op = $this->new_option('goto_context','tool_navdb');
        $params = array( 'table' => 'context', 'filter' => $context->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_course_modules','tool_navdb');
        $params = array( 'table' => 'course_modules', 'filter' => 'course='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_course_modules ($row, $alone=false) {
        $options = array();
        if (!isset($this->modulenames)) $this->modulenames = array();
        if (!isset($this->modulenames[$row->module])) {
            $mod = $this->navdb->db->get_record('modules', array('id'=>$row->module));
            if ($mod) $this->modulenames[$row->module] = $mod->name;
        }
        if (!isset($this->modulenames[$row->module])) return $options;

        $op = $this->new_option('view_activity','tool_navdb','i/course');
        $op->url = new moodle_url(
                            '/mod/'.$this->modulenames[$row->module].'/view.php',
                            array('id' => $row->id)
                        );
        $options[] = $op;

        $context = \context_module::instance($row->id);
        $op = $this->new_option('goto_context','tool_navdb');
        $params = array( 'table' => 'context', 'filter' => $context->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_course_categories ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_course_category','tool_navdb','i/folder');
        $op->url = new moodle_url('/course/index.php', array('categoryid' => $row->id));
        $options[] = $op;

        $op = $this->new_option('view_course_category_management','tool_navdb','i/settings');
        $op->url = new moodle_url('/course/management.php', array('categoryid' => $row->id));
        $options[] = $op;

        $context = \context_coursecat::instance($row->id);
        $op = $this->new_option('goto_context','tool_navdb');
        $params = array( 'table' => 'context', 'filter' => $context->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_course_categories_sons','tool_navdb');
        $params = array( 'table' => 'course_categories', 'filter' => 'parent='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        if ($row->coursecount) {
            $op = $this->new_option('goto_course_categories_courses','tool_navdb');
            $params = array( 'table' => 'course', 'filter' => 'category='.$row->id);
            $op->url = $this->navdb->getUrl($params);
            $options[] = $op;
        }


        return $options;
    }

    function row_options_enrol ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_enrol_methods','tool_navdb','i/enrolusers');
        $op->url = new moodle_url('/enrol/instances.php', array('id'=>$row->courseid));
        $options[] = $op;

        $op = $this->new_option('view_enrol_settings','tool_navdb','i/settings');
        $op->url = new moodle_url('/enrol/editinstance.php',
                    array('courseid'=>$row->courseid, 'id'=>$row->id, 'type'=>$row->enrol));
        $options[] = $op;

        return $options;
    }

    function row_options_user ($row, $alone=false) {
        $options = array();

        if ($row->deleted) {
            return $options;
        }

        $op = $this->new_option('view_user','tool_navdb','i/user');
        $op->url = new moodle_url('/user/profile.php', array('id'=>$row->id));
        $options[] = $op;

        $op = $this->new_option('view_user_edit','tool_navdb','i/settings');
        $op->url = new moodle_url('/user/editadvanced.php', array('course'=>1,'id'=>$row->id));
        $options[] = $op;

        $context = \context_user::instance($row->id);
        $op = $this->new_option('goto_context','tool_navdb');
        $params = array( 'table' => 'context', 'filter' => $context->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_role_assignments','tool_navdb');
        $params = array( 'table' => 'role_assignments', 'filter' => 'userid='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_user_info_data','tool_navdb');
        $params = array( 'table' => 'user_info_data', 'filter' => 'userid='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_context ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('goto_role_assignments','tool_navdb');
        $params = array( 'table' => 'role_assignments', 'filter' => 'contextid='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_context_subcontexts','tool_navdb');
        $params = array( 'table' => 'context', 'filter' => 'path like \''.$row->path.'/%\'');
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_user_info_field ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_user_info_field_edit','tool_navdb','i/settings');
        $op->url = new moodle_url('/user/profile/index.php', array('id'=>$row->id, 'action'=>'editfield'));
        $options[] = $op;

        $op = $this->new_option('goto_user_info_data','tool_navdb');
        $params = array( 'table' => 'user_info_data', 'filter' => 'fieldid='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_grade_items ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_grades','tool_navdb','i/course');
        $op->url = new moodle_url('/grade/index.php', array('id'=>$row->courseid));
        $options[] = $op;

        $op = $this->new_option('view_grade_items_edit','tool_navdb','i/settings');
        $params = array(
                'id'=>$row->id, 'courseid'=>$row->courseid,
                'gpr_type'=>'edit', 'gpr_plugin'=>'tree', 'gpr_courseid'=>$row->courseid
            );
        if ($row->itemtype=='category' || $row->itemtype=='course') {
            $params['id'] = $row->iteminstance;
            $op->url = new moodle_url('/grade/edit/tree/category.php', $params);
        } else {
            $op->url = new moodle_url('/grade/edit/tree/item.php', $params);
        }
        $options[] = $op;

        if (!empty($row->calculation)) {
            $op = $this->new_option('view_grade_items_edit_calculation','tool_navdb','i/calc');
            $params = array(
                    'id'=>$row->id, 'courseid'=>$row->courseid,
                    'gpr_type'=>'edit', 'gpr_plugin'=>'tree', 'gpr_courseid'=>$row->courseid
                );
            $op->url = new moodle_url('/grade/edit/tree/calculation.php', $params);
            $options[] = $op;
        }

        return $options;
    }

    function row_options_logstore_standard_log ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('goto_logstore_standard_log_user','tool_navdb');
        $params = array( 'table' => 'logstore_standard_log', 'filter' => 'userid='.$row->userid);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_logstore_standard_log_course','tool_navdb');
        $params = array( 'table' => 'logstore_standard_log', 'filter' => 'courseid='.$row->courseid);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_logstore_standard_log_context','tool_navdb');
        $params = array( 'table' => 'logstore_standard_log', 'filter' => 'contextid='.$row->contextid);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_logstore_standard_log_component','tool_navdb');
        $params = array( 'table' => 'logstore_standard_log', 'filter' => "component='$row->component'");
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_logstore_standard_log_action','tool_navdb');
        $params = array( 'table' => 'logstore_standard_log', 'filter' => "action='$row->action'");
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        $op = $this->new_option('goto_logstore_standard_log_component_action','tool_navdb');
        $params = array(
                'table' => 'logstore_standard_log',
                'filter' => "component='$row->component' AND action='$row->action'"
            );
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_at_cua_proces ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('view_at_cua_proces','tool_navdb','i/settings');
        $op->url = new moodle_url('/local/atenea/cues/showreg.php', array('proces'=>$row->id));
        $options[] = $op;

        $op = $this->new_option('view_at_cua_proces_cuastudio','tool_navdb','i/edit');
        $op->url = new moodle_url('/local/atenea/scripts/index.php', array('sc'=>'cuastudio', 'p'=>$row->id));
        $options[] = $op;

        $op = $this->new_option('goto_at_cua_registre','tool_navdb');
        $params = array( 'table' => 'at_cua_registre', 'filter' => 'procesid='.$row->id);
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }

    function row_options_at_cua_registre ($row, $alone=false) {
        $options = array();

        $op = $this->new_option('goto_at_cua_proces_registres','tool_navdb');
        $params = array( 'table' => 'at_cua_registre', 'filter' => 'procesid='.$row->procesid );
        $op->url = $this->navdb->getUrl($params);
        $options[] = $op;

        return $options;
    }
}
