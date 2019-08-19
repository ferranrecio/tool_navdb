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
 * Tool NavDB external API
 *
 * @package    tool_navdb
 * @category   external
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.2
 */
namespace tool_navdb;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');


use context;
use context_system;
use context_user;
use context_coursecat;
use context_course;
use context_module;
use context_block;
use context_helper;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use stdClass;

/**
 * Tool NavDB external functions
 *
 * @package    auth_email
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.2
 */
class external extends external_api {


    private static function new_warning($code, $message) {
        return array(
                    'item' => 0,
                    'itemid' => 0,
                    'warningcode' => $code,
                    'message' => s($message)
                );
    }

    /**
     * Generic params
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    private static function operation_params () {
        $params = array(
                'value' => new external_value(PARAM_TEXT, 'String to evaluate',VALUE_REQUIRED),
            );
        return new external_function_parameters($params);
    }

    /**
     * Generic return
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function operation_returns() {

        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'True if the value can be converted, false otherwise'),
                'warnings'  => new external_warnings(),
                'newvalue' => new external_value(PARAM_INT, 'New value'),
                'human' => new external_value(PARAM_TEXT, 'New value in human format'),
            )
        );
    }
    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function get_date_parameters() {
        return self::operation_params();
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function get_date($value) {
        global $CFG, $PAGE;

        $params = self::validate_parameters(
            self::get_date_parameters(),
            array(
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = false;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();

        if (!empty($value)) {
            if (is_numeric($value)) {
                $result->newvalue = $value;
            } else {
                try {
                    $result->newvalue = strtotime($value);
                    if (!$result->newvalue) $result->newvalue = 0;
                    // $test = new \DateTime($value);
                    // $result->newvalue = $test->getTimestamp().'';
                } catch (Exception $e) {
                    $result->newvalue = 0;
                    $result->warnings[] = self::new_warning('fielderror',"Could not convert to timestamp the value $value");
                }
            }
        } else {
            $result->human = get_string('typetoseach','tool_navdb', get_string('date'));
        }

        if ($result->newvalue) {
            $result->human = userdate($result->newvalue);
            $result->success = true;
        } else {
            if (empty($result->human)) $result->human = get_string('unknown','tool_navdb', $value);
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function get_date_returns() {
        return self::operation_returns();
    }

    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function get_userid_parameters() {
        return self::operation_params();
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function get_userid($value) {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_userid_parameters(),
            array(
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = false;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();

        if (!empty($value)) {
            if (is_numeric($value)) {
                $user = $DB->get_record('user',array('id'=>$value));
                if ($user) {
                    $result->newvalue = $user->id;
                    //$result->human = $user->username;
                }
            }
            if (empty($result->newvalue)) {
                $users = get_users(true, $value);
                if (!$users) {
                    $result->warnings[] = self::new_warning('notfound',"No results found");
                } else if (count($users) == 1) {
                    $user = array_pop($users);
                    $result->newvalue = $user->id;
                } else {
                    $result->human = get_string('too_results','tool_navdb', $value);
                }
            }
        } else {
            $result->human = get_string('typetoseach','tool_navdb', get_string('user'));
        }

        if ($result->newvalue) {
            $result->success = true;
            if (isset($user)) {
                $result->human = "ID:$user->id (";
                $result->human.= $user->username.')';
            }
        } else {
            if (empty($result->human)) $result->human = get_string('unknown','tool_navdb', $value);
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function get_userid_returns() {
        return self::operation_returns();
    }

    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function get_roleid_parameters() {
        return self::operation_params();
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function get_roleid($value) {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_roleid_parameters(),
            array(
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = false;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();

        if (!empty($value)) {
            if (is_numeric($value)) {
                $role = $DB->get_record('role',array('id'=>$value));
            }
            if (!$role) {
                $role = $DB->get_record('role',array('shortname'=>$value));
            }
            if (!$role) {
                $role = $DB->get_record('role',array('name'=>$value));
            }
            if ($role) {
                $result->newvalue = $role->id;
                $result->human = 'ID: '.$role->id.' (';
                if (!empty($role->name)) $result->human.= $role->name.'/';
                $result->human.= $role->shortname.')';
            }
        } else {
            $result->human = get_string('typetoseach','tool_navdb', get_string('role'));
        }

        if ($result->newvalue) {
            $result->success = true;
        } else {
            if (empty($result->human)) $result->human = get_string('unknown','tool_navdb', $value);
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function get_roleid_returns() {
        return self::operation_returns();
    }

    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function get_courseid_parameters() {
        return self::operation_params();
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function get_courseid($value) {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_courseid_parameters(),
            array(
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = false;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();

        if (!empty($value)) {
            if (is_numeric($value)) {
                $course = $DB->get_record('course',array('id'=>$value));
            }
            if (!$course) {
                $course = $DB->get_record('course',array('shortname'=>$value));
            }
            if (!$course) {
                $course = $DB->get_record('course',array('idnumber'=>$value));
            }
            if (!$course) {
                $params = array();
                $params['value'] = '%'.$DB->sql_like_escape($value).'%';
                $select = $DB->sql_like('fullname', ':value', false, false);
                $courses = $DB->get_records_select('course', $select, $params, '', '*', 0, 3);
                if (count($courses)) {
                    $result->human = get_string('too_results','tool_navdb', count($courses));
                }
                if (count($courses) == 1) {
                    $course = reset($courses);
                }
            }
            if ($course) {
                $result->newvalue = $course->id;
                $result->human = 'ID: '.$course->id.' (';
                $result->human.= $course->shortname.')';
            }
        } else {
            $result->human = get_string('typetoseach','tool_navdb', get_string('course'));
        }

        if ($result->newvalue) {
            $result->success = true;
        } else {
            if (empty($result->human)) $result->human = get_string('unknown','tool_navdb', $value);
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function get_courseid_returns() {
        return self::operation_returns();
    }

    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function get_contextid_parameters() {
        return self::operation_params();
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function get_contextid($value) {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_contextid_parameters(),
            array(
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = false;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();

        if (!empty($value)) {
            if (is_numeric($value)) {
                $context = context::instance_by_id($value, IGNORE_MISSING);
            }
            if (!$context) {
                $parts = array_map('trim',explode(':', $value));
                $classname = 'context_'.$parts[0];
                if (count($parts)==2) {
                    // try to translate query
                    if (!is_numeric($parts[1])) {
                        //TODO
                    }
                    if (is_numeric($parts[1]) && class_exists($classname) && method_exists($classname, 'instance')) {
                        $context = $classname::instance($parts[1]);
                    }
                }
            }
            if ($context) {
                $result->newvalue = $context->id;
                $result->human = "ID:$result->newvalue (";
                $result->human.= context_helper::get_level_name($context->contextlevel).' '.$context->instanceid.')';
            }
        } else {
            $result->human = get_string('typetoseach','tool_navdb', get_string('context','role'));
        }

        if ($result->newvalue) {
            $result->success = true;
        } else {
            if (empty($result->human)) $result->human = get_string('unknown','tool_navdb', $value);
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function get_contextid_returns() {
        return self::operation_returns();
    }

    /**
     * Describes the parameters for get_timestamp.
     *
     * @return external_function_parameters
     * @since Moodle 3.5
     */
    public static function set_starred_table_parameters() {
        $params = array(
                'table' => new external_value(PARAM_ALPHANUMEXT, 'Table name',VALUE_REQUIRED),
                'value' => new external_value(PARAM_BOOL, 'New starred value (toggle if none)',VALUE_OPTIONAL),
            );
        return new external_function_parameters($params);
    }

    /**
     * Get the unix timestamp translation with human date format.
     *
     * @param  string $value               value
     * @return conversion and possible warnings
     * @since Moodle 3.5
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function set_starred_table($table, $value = null) {
        global $CFG, $DB;

        $table = strtolower($table);
        $params = self::validate_parameters(
            self::set_starred_table_parameters(),
            array(
                'table' => $table,
                'value' => $value,
            )
        );

        $result = new stdClass();
        $result->success = true;
        $result->newvalue = 0;
        $result->human = '';
        $result->warnings = array();
        $starred_tables = explode(',', get_config('tool_navdb', 'starred_tables'));
        $starred_tables = array_filter($starred_tables);
        $starred_count = count($starred_tables);
        $result->warnings[] = self::new_warning('starreds',implode(',', $starred_tables));
        if ($value === null) {
            $value = (in_array($table, $starred_tables))? false : true ;
            $result->warnings[] = self::new_warning('toggler',"Toggling $table ($value)");
        }
        $result->newvalue = ($value)? 1 : 0;
        $key = array_search($table, $starred_tables);
        if ($value) {
            $result->warnings[] = self::new_warning('toggler',"Add star $key");
            if ($key === false) {
                $result->warnings[] = self::new_warning('toggler',"Adding star $key");
                $starred_tables[] = $table;
            }
        } else {
            $result->warnings[] = self::new_warning('toggler',"Remove star $key");
            if ($key !== false) {
                $result->warnings[] = self::new_warning('toggler',"Removing star $key");
                unset($starred_tables[$key]);
            }
        }
        $result->warnings[] = self::new_warning('toggler',implode(',', $starred_tables));
        if ($starred_count != count($starred_tables)) {
            $result->warnings[] = self::new_warning('updtoggler',"Updating starreds");
            set_config('starred_tables', implode(',', $starred_tables), 'tool_navdb');
        }

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.5
     */
    public static function set_starred_table_returns() {
        return self::operation_returns();
    }
}

