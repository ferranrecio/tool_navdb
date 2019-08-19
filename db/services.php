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
 * Auth email webservice definitions.
 *
 * @package    auth_email
 * @copyright  2016 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'tool_navdb_get_date' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'get_date',
        'classpath'    => '',
        'description' => 'Get the unix timestamp of a string',
        'type'        => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
    'tool_navdb_get_userid' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'get_userid',
        'classpath'    => '',
        'description' => 'Search userid from a string (id, username, idnumber, email)',
        'type'        => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
    'tool_navdb_get_roleid' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'get_roleid',
        'classpath'    => '',
        'description' => 'Search a roleid from a string (id, shortname, name)',
        'type'        => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
    'tool_navdb_get_courseid' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'get_courseid',
        'classpath'    => '',
        'description' => 'Search a courseid from a string (id, shortname, idnumber, fullname)',
        'type'        => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
    'tool_navdb_get_contextid' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'get_contextid',
        'classpath'    => '',
        'description' => 'Search a roleid from a string (id, shortname, name)',
        'type'        => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
    'tool_navdb_starred_table' => array(
        'classname'   => 'tool_navdb\external',
        'methodname'  => 'set_starred_table',
        'classpath'    => '',
        'description' => 'Set a table starred value (no params=toggle, 1=starred, 0=no starred)',
        'type'        => 'write',
        'capabilities' => 'moodle/site:config',
        'ajax'          => true,
    ),
);


