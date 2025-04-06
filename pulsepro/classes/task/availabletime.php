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
 * Available time task observer.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pulsepro\task;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Scheduled task to update the users pulse availability time.
 */
class availabletime extends \core\task\scheduled_task {

    /**
     * Task name defined.
     *
     * @return string name of the task.
     */
    public function get_name() {
        return get_string('reminders:availabletime', 'mod_pulse');
    }

    /**
     * Cron execution to setup the users availability time update adhoc task.
     *
     * @return void
     */
    public function execute() {
        global $CFG;
        $availabletime = new \local_pulsepro\notification();
        $availabletime->update_mod_availability();
    }
}
