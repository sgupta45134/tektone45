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
 * @package   local_pulsepro
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
function xmldb_local_pulsepro_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion <= 1206202107) {

        $table = new xmldb_table('pulsepro');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_pulsepro', true, true);
        }
        $table = new xmldb_table('pulsepro_availability');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_pulsepro_availability', true, true);
        }
        $table = new xmldb_table('pulsepro_tokens');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_pulsepro_tokens', true, true);
        }
    }

    if ($oldversion <= 2021082601) {
        pulsepro_create_presets();
    }

    if ($oldversion <= 2021100800) {

        $pulsetable = new xmldb_table('local_pulsepro');
        $field = new xmldb_field('credits', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'pulseid');
        $creditstatus = new xmldb_field('credits_status', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'credits');

        // Conditionally launch add field privatereplyto.
        if (!$dbman->field_exists($pulsetable, $field)) {
            $dbman->add_field($pulsetable, $field);
        }
        if (!$dbman->field_exists($pulsetable, $creditstatus)) {
            $dbman->add_field($pulsetable, $creditstatus);
        }

        $table = new xmldb_table('local_pulsepro_credits');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('pulseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'pulseid');
        $table->add_field('credit', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'userid');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'credit');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('pulseuserid', XMLDB_KEY_UNIQUE, ['pulseid', 'userid']);
        // Conditionally launch create table.
        if (!$dbman->table_exists('local_pulsepro_credits')) {
            $dbman->create_table($table);
        }
    }

    return true;
}
