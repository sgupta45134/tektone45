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
 * Upgrade steps defined
 *
 * @package   local_coursepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Pulsepro DB upgrade.
 *
 * @param  mixed $oldversion
 * @return void
 */
function xmldb_local_custom_tasks_install() {
    global $DB;

    $dbman = $DB->get_manager();

        $coursetable = new xmldb_table('course');
        $field = new xmldb_field('timeregclosed', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'showcompletionconditions');
        $creditstatus = new xmldb_field('isregclosed', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'showcompletionconditions');

        // Conditionally launch add field privatereplyto.
        if (!$dbman->field_exists($coursetable, $field)) {
            $dbman->add_field($coursetable, $field);
        }
        if (!$dbman->field_exists($coursetable, $creditstatus)) {
            $dbman->add_field($coursetable, $creditstatus);
        }

    return true;
}
