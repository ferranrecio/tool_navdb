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
 * Quick search form renderable.
 *
 * @package    mod_forum
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_navdb\output;
defined('MOODLE_INTERNAL') || die();

use help_icon;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use moodleform;

use stdClass;

/**
 * Quick search form renderable class.
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main_form extends moodleform {

    /** @var string current table */
    protected $formfields;
    /** @var moodle_url The form action URL. */
    protected $actionurl;
    /** @var help_icon The help icon. */
    protected $helpicon;
    /** @var help_icon The help icon. */
    protected $defaults;


    /**
     * Constructor.
     *
     * @param int $courseid The course ID.
     * @param string $query The current query.
     */
    /*public function __construct($navdb) {
        $fields = $navdb->getNavigation();
        $this->formfields = array();
        foreach ($fields as $key => $value) {
            $field = new stdClass();
            $field->label = get_string("navdb_$key",'tool_navdb');
            $field->name = $key;
            $field->value = $value;
            $this->formfields[] = $field;
        }
        $this->formfields['actionurl'] = new moodle_url('/admin/tool/navdb/index.php');
        $this->formfields['helpicon'] = new help_icon('search', 'core');
    }*/

    function definition() {
        global $CFG, $COURSE, $DB;
        $customdata = $this->_customdata;
        $defaults = $this->get_defaults();

        $mform    =& $this->_form;

        $mform->addElement('header', 'mainform', get_string('mainform', 'tool_navdb'));
        $mform->setExpanded('mainform', false);

        $mform->addElement('text', 'table', get_string('navdb_table', 'tool_navdb'), array('size'=>'72'));
        $mform->setType('table', PARAM_ALPHANUMEXT);
        $mform->addRule('table', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('table', $defaults->table);

        $mform->addElement('text', 'filter', get_string('navdb_filter', 'tool_navdb'), array('size'=>'128'));
        $mform->setType('filter', PARAM_RAW);
        $mform->setDefault('filter', $defaults->filter);

        $mform->addElement('text', 'limit', get_string('navdb_limit', 'tool_navdb'));
        $mform->setDefault('limit', $defaults->limit);
        $mform->setType('limit', PARAM_INT);

        $mform->addElement('text', 'limitfrom', get_string('navdb_limitfrom', 'tool_navdb'));
        $mform->setDefault('limitfrom', $defaults->limitfrom);
        $mform->setType('limitfrom', PARAM_INT);

        $mform->addElement('checkbox', 'muggle', get_string('navdb_muggle', 'tool_navdb'));
        $mform->setDefault('muggle', $defaults->muggle);
        $mform->setType('muggle', PARAM_INT);

        $submit = $mform->createElement('submit', 'submit', get_string('show_results','tool_navdb'));
        $cancel = $mform->createElement('cancel', 'cancel', get_string('show_db','tool_navdb'));
        $mform->addElement('group', 'categorygroup', '',
            [$submit, $cancel], null, false);

    }

    function get_defaults () {
        $defaults = new stdClass();
        $defaults->table = optional_param('table', '', PARAM_ALPHANUMEXT);
        $defaults->filter = optional_param('filter', '', PARAM_RAW);
        $defaults->limit = optional_param('limit', 500, PARAM_INT);
        $defaults->limitfrom = optional_param('limitfrom', 0, PARAM_INT);
        $defaults->muggle = optional_param('muggle', 0, PARAM_INT);
        return $defaults;
    }

    function get_extras () {
        $extras = new stdClass();
        $extras->orderfield = optional_param('orderfield', 'id', PARAM_ALPHANUMEXT);
        $extras->ordering = strtoupper(optional_param('ordering', 'ASC', PARAM_ALPHANUMEXT));
        if ($extras->ordering != 'ASC' && $extras->ordering != 'DESC') $extras->ordering = 'ASC';
        return $extras;
    }

    function get_navdb () {
        $extras = $this->get_extras();
        if ($this->is_cancelled()){
            $navdb = new \tool_navdb\data\navdb( new stdClass(), new stdClass() );
        } else if (($fromform = $this->get_data()) && (confirm_sesskey())) {
            $navdb = new \tool_navdb\data\navdb($fromform, $extras);
        } else {
            $navdb = new \tool_navdb\data\navdb( $this->get_defaults(), $extras );
        }
        if (!empty($navdb->filter)) {
            $this->_form->setExpanded('mainform', true);
        }
        return $navdb;
    }

    /**
     * get extra filters from page params
     * @return string current filter
     */
    function builtExtraFilters() {

    }

}
