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
 * Update pulse avaiblibity time for User.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pulsepro\task;

use stdclass;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Update the user instance availability with time for all pulse instance.
 */
class availability extends \core\task\adhoc_task {

    /**
     * Execution part of the adhoc task.
     *
     * Filter the users available time and updated the users status.
     *
     * @return void
     */
    public function execute() {
        global $DB;
        // Raise the time limit for each instance.
        \core_php_time_limit::raise(120);

        $instance = $data = $this->get_custom_data();
        if (!$DB->get_record('pulse', ['id' => $instance->pulse->id])
            || !$DB->get_record('course', ['id' => $instance->course->id]) ) {
            return true;
        }
        if (!empty($instance->students)) {
            if (isset($instance->cm_array)) {
                mtrace('Update availability init for the instance - '. $instance->cm_array->id);
                foreach ($instance->students as $userid => $user) {
                    mtrace('Updating user status in pulse availability - '.$userid);
                    $modinfo = \course_modinfo::instance((object) $instance->course, $userid);
                    $cm = $modinfo->get_cm($instance->cm_array->id);
                    $pulseid = $instance->pulse->id;
                    $status = ($cm->uservisible) ? 1 : 0;
                    $this->update_user_visible($pulseid, $userid, $status);
                }
            }
        }
    }

    /**
     * Update the pulse instance available time and status for the user in availability table. This time used to
     * calculate the relative date for sending reminders to users.
     *
     * @param  int $pulseid Pulse instance id
     * @param  int $userid User id.
     * @param  bool $status true|false User visibility status for this instance.
     * @return void
     */
    public function update_user_visible(int $pulseid, int $userid, bool $status): void {
        global $DB;
        // Check module availability already added for user.
        if ($record = $DB->get_record('local_pulsepro_availability', array('userid' => $userid, 'pulseid' => $pulseid))) {
            // Only update the availabilty status if both are different.
            // Otherwise don't need to update anything.
            if ($record->status != $status) {
                $record->status = $status;
                $record->availabletime = time();
                $DB->update_record('local_pulsepro_availability', $record);
            }
        } else {
            $record = new stdclass();
            $record->status = $status;
            $record->pulseid = $pulseid;
            $record->userid = $userid;
            $record->availabletime = time();
            $DB->insert_record('local_pulsepro_availability', (array) $record);
        }
    }
}
