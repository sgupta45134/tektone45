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
 * Recurring reminder scheduled task.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_pulsepro\task;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Task to prepare and send recurring reminder notification to users.
 */
class recurring_reminder extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('reminders:recurring', 'mod_pulse');
    }

    /**
     * Cron execution to generate the recurring reminder notification data and available users data
     * then set the adhoc task to send the recurring reminders.
     *
     * @return void
     */
    public function execute() {
        global $CFG;
        $notification = new \local_pulsepro\notification();
        $notification->recurring_reminder();
    }
}
