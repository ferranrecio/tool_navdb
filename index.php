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
 * @package     tool_vuejsdemo
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/lib/formslib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

// navigation main params
/*$params = new stdClass();
$params->table = optional_param('table', '', PARAM_ALPHANUM);
$params->filter = optional_param('filter', '', PARAM_RAW);
$params->limit = optional_param('limit', '', PARAM_ALPHANUM);
$params->limitfrom = optional_param('limitfrom', '', PARAM_ALPHANUM);*/

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/navdb/index.php'));

$mainform = new \tool_navdb\output\main_form('');
$navdb = $mainform->get_navdb();

// add navbar breadcrumb
if ($navdb->table) {
    $url = $navdb->getUrl(array('filter'=>''));
    $PAGE->navbar->add(format_string($navdb->table), $url);
}
if (!empty($navdb->filter)) {
    $url = $navdb->getUrl();
    $PAGE->navbar->add(get_string('navdb_filter','tool_navdb'), $url);
}

// the $navdb determines the current navigation
//  * No table: want to select a table first
//  * table but no filters: show field filters to select rows
//  * table and filter: show results
//     * if the query returns just one result, show a single page
//     * if has more than one result, show a as a table with pagination

echo $OUTPUT->header();

$mainform->display();

if (empty($navdb->table)) {
    // show table list
    $tables = $navdb->getTables();
    // build data for template export
    $data = new stdClass();
    $data->groups = array();
    foreach ($tables as $group => $list) {
        $obj = new stdClass();
        if (count($list)>1) {
            $obj->name = $group;
            $obj->tables = $list;
        } else {
            $obj->table = reset($list);
        }
        $data->groups[] = $obj;
    }
    echo $OUTPUT->render_from_template('tool_navdb/tablelist', $data);
} else {
    if (empty($navdb->filter)) {
        // show filter form
        $te = new \tool_navdb\data\tableexport($navdb);
        $data = $te->export_table_fields();
        if ($data) {
            echo $OUTPUT->render_from_template('tool_navdb/tableform', $data);
        } else {
            $returnurl = $navdb->getUrl(array('table' => '', 'filter' => ''));
            notice (get_string('err_table_not_found', 'tool_navdb', $navdb->table), $returnurl);
        }
    } else {
        // show filtered results
        $count = $navdb->countRecords();
        if (!$count) {
            // no results
            $message = get_string("noresults",'tool_navdb',$navdb);
            $url = $navdb->getUrl(array('filter'=>''));
            notice($message, $url);
        } else {
            $re = new \tool_navdb\data\rowexport($navdb);
            $alone = ($count == 1);
            $data = new stdClass();
            $data->table = $navdb->table;
            $data->count = 0;
            $rows = array();
            $rs = $navdb->getRecordSet();
            foreach ($rs as $record) {
                $rows[] = $re->export_row($record, $alone);
                $data->count++;
            }
            $rs->close();

            $data->total = $count;
            $fields = $navdb->getQueryFields();
            $data->fields = $re->export_fields($fields, $alone);

            $data->rows = $rows;
            if ($alone) {
                $data->menutext = get_string('options');
                $template = 'tool_navdb/rowsingle';
            } else {
                $data->menutext = '';
                $template = 'tool_navdb/rowstable';
            }
            echo $OUTPUT->render_from_template($template, $data);
        }
    }
}

echo $OUTPUT->footer();
