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


use stdClass;

/**
 * Quick search form renderable class.
 *
 * @package    tool_navdb
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tableicon {

    /**
     * return an OUTPUT icon for a specific table
     * @param  string $tablename table name
     * @return pix_icon
     */
    public static function getIcon($tablename) {
        global $OUTPUT;
        $icon = 'i/db';
        if (strpos($tablename, 'user') !== false) $icon = 'i/user';
        if (strpos($tablename, 'course') !== false) $icon = 'i/course';
        if (strpos($tablename, 'role') !== false) $icon = 'i/role';
        if (strpos($tablename, 'cohort') !== false) $icon = 'i/cohort';
        return $OUTPUT->pix_icon($icon,'');
    }

}
