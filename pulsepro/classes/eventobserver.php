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
 * Event observer class definition.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pulsepro;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Observer class for the course module deleted and user enrolment deleted events. It will remove the user data from pulse.
 */
class eventobserver {

    /**
     * course module deleted event observer.
     * Remove the user and instance records for the deleted modules from pulsepro tables.
     *
     * @param  stdclass $event
     * @return void
     */
    public static function course_module_deleted($event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/pulsepro/lib.php');
        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];
            if ($DB->record_exists('pulse_users', ['pulseid' => $pulseid])) {
                $DB->delete_records('pulse_users', ['pulseid' => $pulseid]);
            }
            // Remove all the users invitations and users availability records related to that instnace.
            local_pulsepro_delete_instance($event->objectid, $pulseid);

            // Remove pulse user credits records.
            if ($DB->record_exists('local_pulsepro_credits', ['pulseid' => $pulseid])) {
                $DB->delete_records('local_pulsepro_credits', ['pulseid' => $pulseid]);
                \local_pulsepro\credits::recalculate_pulsecredits($courseid);
            }
        }
    }

    /**
     * User unenrolled event observer.
     * Remove the unenrolled user records related to list of pulse instances created in the course.
     * It deletes the users availability data, reaction tokens and activity completion data.
     *
     * @param  stdclass $event
     * @return bool true
     */
    public static function user_enrolment_deleted($event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/pulsepro/lib.php');
        $userid = $event->relateduserid; // Unenrolled user id.
        $courseid = $event->courseid;
        // Retrive list of pulse instance added in course.
        $list = local_pulsepro_course_instancelist($courseid);
        if (!empty($list)) {
            $pulselist = array_column($list, 'instance');
            list($insql, $inparams) = $DB->get_in_or_equal($pulselist);
            $inparams[] = $userid;
            $select = " pulseid $insql AND userid = ? ";
            // Remove the user availability records.
            $DB->delete_records_select('local_pulsepro_availability', $select, $inparams);
            $DB->delete_records_select('pulse_completion', $select, $inparams);

            $DB->delete_records_select('local_pulsepro_credits', $select, $inparams);
            \local_pulsepro\credits::recalculate_usercredits($userid);

            $select = " pulseid $insql AND (userid = ? OR relateduserid = ? ) ";
            $inparams[] = $userid;
            $DB->delete_records_select('local_pulsepro_tokens', $select, $inparams);

        }
        return true;
    }

    /**
     * Recalculate the user credits when student enrolled in course.
     *
     * @param \core\event\user_enrolment_created $event Event data.
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/pulsepro/lib.php');
        $userid = $event->relateduserid; // Unenrolled user id.
        $user = \core_user::get_user($userid);
        $users = [$userid => $user];
        $courseid = $event->courseid;

        $sql = 'SELECT p.id AS pulseid, p.*, pp.*, cm.id as cmid, cm.* FROM {pulse} p
        JOIN {local_pulsepro} pp ON pp.pulseid = p.id
        JOIN {course_modules} cm ON cm.instance = p.id AND cm.module = (SELECT m.id FROM {modules} m WHERE m.name=:pulse)
        WHERE pp.credits_status = 1 AND p.course = :courseid';
        $instances = $DB->get_records_sql($sql, ['pulse' => 'pulse', 'courseid' => $courseid]);
        if (!empty($instances)) {
            foreach ($instances as $instance) {
                (new \local_pulsepro\credits())->update_usercredits($instance, $users);
            }
        }
    }

    /**
     * Update the users credit score when the module updated.
     *
     * @param stdclass $event Event data.
     * @return bool Updated user credits result.
     */
    public static function course_module_updated($event) {
        global $DB;
        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];
            $sql = 'SELECT p.id AS pulseid, p.*, pp.*, cm.id as cmid, cm.* FROM {pulse} p
            JOIN {local_pulsepro} pp ON pp.pulseid = p.id
            JOIN {course_modules} cm ON cm.instance = p.id AND cm.module = (SELECT m.id FROM {modules} m WHERE m.name=:pulse)
            WHERE p.id = :pulseid';
            $instances = $DB->get_records_sql($sql, ['pulseid' => $pulseid, 'pulse' => 'pulse']);
            if (!empty($instances)) {
                $instance = reset($instances);
                return (new \local_pulsepro\credits())->update_usercredits($instance);
            }
        }
    }

}
