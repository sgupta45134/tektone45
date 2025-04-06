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

use stdclass;

/**
 * Send reminder notification to users filter by the users availability.
 */
class notification {

    /**
     * Fetched complete record for all instances.
     *
     * @var array
     */
    private $records;

    /**
     * Module info sorted by course.
     *
     * @var mod_info
     */
    public $modinfo = [];

    /**
     * List of created pulse instances in LMS.
     *
     * @var array
     */
    protected $instances;

    /**
     * Pulse instance data record.
     *
     * @var object
     */
    public $instance;

    /**
     * Fetch all pulse instance data with course and context data.
     * Each instance are set to adhoc task to send reminders.
     *
     * @param  string $additionalwhere Additional where condition to filter the pulse record
     * @param  array $additionalparams Parameters for additional where clause.
     * @return void
     */
    public function __construct($additionalwhere='', $additionalparams=[]) {
        global $DB;

        $sql = "SELECT nt.id as nid, nt.*, '' as pulseend,
        pp.id as pulseproid, pp.*, '' as pulsepro,
        cm.id as coursemoduleid, cm.*, '' as coursemodules,
        md.id as moduleid, '' as modules,
        ctx.id as contextid, ctx.*, '' as context,
        cu.id as courseid, cu.*, cu.groupmode as coursegroupmode, '' as courseend
        FROM {pulse} nt
        JOIN {local_pulsepro} pp ON pp.pulseid = nt.id
        JOIN {course_modules} cm ON cm.instance = nt.id
        JOIN {modules} md ON md.id = cm.module
        JOIN {course} cu on cu.id = nt.course
        RIGHT JOIN {context} ctx on ctx.instanceid = cm.id and contextlevel = 70
        WHERE md.name = 'pulse' AND cm.visible = 1
        AND cu.visible = 1 AND cu.startdate <= :startdate AND (cu.enddate = 0 OR cu.enddate >= :enddate)";

        $sql .= $additionalwhere ? ' AND '.$additionalwhere : '';

        $params = array_merge(['startdate' => time(), 'enddate' => time()], $additionalparams);
        $this->records = $DB->get_records_sql($sql, $params);
        if (empty($this->records)) {
            mtrace('No pulse instance are added yet'."\n");
            return true;
        }
        mtrace('Fetched available pulse modules');
        foreach ($this->records as $key => $record) {
            $params = [];
            $record = (array) $record;
            $keys = array_keys($record);
            // Pulse.
            $pulseendpos = array_search('pulseend', $keys);
            $pulse = array_slice($record, 0, $pulseendpos);
            $pulse['id'] = $pulse['nid'];

            // Pulse pro record.
            $propos = array_search('pulseproid', $keys);
            $pulseproendpos = array_search('local_pulsepro', $keys);
            $pulsepro = array_slice($record, $propos, ($pulseproendpos - $propos) );
            $pulsepro['id'] = $pulsepro['pulseproid'];
            mtrace('Initiate pulse module - '.$pulse['name']);
            // Context.
            $ctxpos = array_search('contextid', $keys);
            $ctxendpos = array_search('context', $keys);
            $context = array_slice($record, $ctxpos, ($ctxendpos - $ctxpos));
            $context['id'] = $context['contextid']; unset($context['contextid']);
            // Course module.
            $cmpos = array_search('coursemoduleid', $keys);
            $cmendpos = array_search('coursemodules', $keys);
            $cm = array_slice($record, $cmpos, ($cmendpos - $cmpos));
            $cm['id'] = $cm['coursemoduleid']; unset($cm['coursemoduleid']);
            // Course records.
            $coursepos = array_search('courseid', $keys);
            $course = array_slice($record, $coursepos);
            $course['id'] = $course['courseid'];

            if (!in_array($course['id'], $this->modinfo)) {
                $this->modinfo[$course['id']] = get_fast_modinfo($course['id'], 0);
            }
            if (isset($cm['id']) && !empty($cm['id']) && !empty($this->modinfo[$course['id']]->cms[$cm['id']])) {
                $coursemodule = $this->modinfo[$course['id']]->get_cm($cm['id']);
                $data = new stdclass();
                $data->pulse = $pulse;
                $data->course = $course;
                $data->pulsepro = (object) $pulsepro;
                $data->cm = $coursemodule;
                $data->cm_array = $cm;
                $data->context = $context;
                $data->modinfo = $this->modinfo[$course['id']];
                // Fetch list of sender users for the instance.
                $data->sender = \mod_pulse\task\sendinvitation::get_sender($data->course['id']);
                $this->instances[$pulse['id']] = $data;
            }
        }
    }

    /**
     * Filter students based on their availability for the instance.
     * Users are filtered by the time difference selected for the reminder and the user module available time.
     * Filter by time only true when the reminder option set as relative time.
     *
     * @param  array $users List of enrolled users in course.
     * @param  stdclass $instance Pulse instance data.
     * @param  bool $filter Filter status (
     * if enabled filter the users by the time difference between their module available time and selected reminder relative time.)
     * @param  int|null $duration Selected reminder duration.
     * @return array List of available users.
     */
    public function filter_students($users, $instance, $filter=false, $duration=null) {

        $users = array_filter($users, function($value) use ($instance, $filter, $duration) {

            if ($value->roleshortname != 'student' && !$value->isavailable) {
                return false;
            }
            // Filter available users.
            if (!empty($instance->cm)) {
                mtrace('Filter users based on their availablity..');
                $modinfo = \course_modinfo::instance((object) $instance->course, $value->id);
                $cm = $modinfo->get_cm($instance->cm_array['id']);
                if (!$cm->uservisible) {
                    return false;
                }
            }

            if ($filter) {
                if ($instance->type == 'recurring') {
                    // Send the recurring reminders in selected duration time intervals.
                    $availabletime = !empty($value->availabletime) ? $value->availabletime : time();
                    $comparetime = ($value->recurring_reminder_time != '') ? $value->recurring_reminder_time : $availabletime;
                    $difference = time() - $comparetime;
                    if ($duration && $difference > $duration) {
                        return true;
                    }
                } else {
                    // Filter first and second reminders difference between the selected duration and users module available time.
                    $availabletime = !empty($value->availabletime) ? $value->availabletime : time();
                    $difference = time() - $availabletime;
                    if ($duration && $difference > $duration) {
                        return true;
                    }
                }
                return false;
            }
            return true;
        });
        return $users;
    }

    /**
     * Set adhoc task for reminders send message for each instance.
     *
     * @param  array $students List of users.
     * @param  stdclass $instance Pulse instnace data.
     * @param  string $type Notification reminder type (first, second, recurring, invitation).
     * @param  string $role Type notify users (parent, teacher, studnet).
     * @return void
     */
    public function set_reminder_adhoctask($students, $instance, $type, $role='student') {
        global $DB;

        $task = new \local_pulsepro\task\sendreminders();
        $instance->users = $students;
        $instance->type = $type;
        $instance->role = $role;
        $task->set_custom_data($instance);
        $task->set_component('local_pulsepro');
        if (!empty($instance)) {
            \core\task\manager::queue_adhoc_task($task);
        }
    }

    /**
     * Get the relative role assigned user data for list of users.
     * It fetch the list of users who are assigned in relative role for the shared student ids.
     *
     * @param  array $roles List of user context roles.
     * @param  array $students List of students.
     * @param  int $courseid Course id.
     * @param  int $pulseid Pulse instance id.
     * @return array List of relative roles.
     */
    public function get_parent_users($roles, $students, $courseid, $pulseid) {
        global $DB;
        if (empty($roles)) {
            return [];
        }

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);
        $enrolmethods = $DB->get_records('enrol', ['courseid' => $courseid], '', 'id');
        if (empty($enrolmethods) || empty($students)) {
            return [];
        }
        list($enrolinsql, $enrolinparams) = $DB->get_in_or_equal(array_keys($enrolmethods));
        list($userinsql, $userinparams) = $DB->get_in_or_equal(array_keys($students));

        $sql = "SELECT ra.id, ra.userid, c.instanceid
            FROM {context} c
            JOIN {role_assignments} ra ON ra.contextid = c.id
            JOIN {user} u ON u.id = c.instanceid
            JOIN (SELECT DISTINCT ue.userid FROM {user_enrolments} ue WHERE ue.enrolid $enrolinsql) ue ON ue.userid = c.instanceid
            WHERE ra.roleid $roleinsql AND c.instanceid $userinsql AND c.contextlevel = ".CONTEXT_USER;

        $params = array_merge($enrolinparams, $roleinparams, $userinparams);

        if ($usercontexts = $DB->get_records_sql($sql, $params)) {
            $users = [];

            foreach ($usercontexts as $usercontext) {
                // Add the student data to parent roles.
                if (isset($students[$usercontext->instanceid])) {
                    $users[$usercontext->userid][] = $students[$usercontext->instanceid];
                }
            }
            list($userinsql, $userinparams) = $DB->get_in_or_equal(array_keys($users));

            $availablityfields = $this->pulsepro_availability_fields();
            $records = $DB->get_records_sql("SELECT u.* from {user} u where u.id $userinsql", $userinparams);
            $this->merge_user_availability($records, $pulseid);
            foreach ($records as $userid => $data) {
                $records[$userid]->students = $users[$userid];
            }
            return $records;
        }
        return [];
    }

    /**
     * Fetch and merge the user availability fields data into user instance data.
     *
     * @param  array $users
     * @param  int $pulseid
     * @return void
     */
    public function merge_user_availability(&$users, $pulseid) {
        global $DB;

        $availablityfields = $this->pulsepro_availability_fields();
        $userids = array_keys($users);
        if (!empty($userids)) {
            list($inusersql, $inuserparams) = $DB->get_in_or_equal($userids);
            $availablesql = "SELECT DISTINCT(pp.userid), $availablityfields, pp.status as isavailable
                            FROM {local_pulsepro_availability} pp
                            WHERE pp.userid $inusersql AND pp.pulseid = ?";
            $inuserparams[] = $pulseid;
            $availabilityusers = $DB->get_records_sql($availablesql, $inuserparams);
            foreach ($users as $key => $value) {
                if (isset($availabilityusers[$value->id])) {
                    $userdata = (array) $availabilityusers[$value->id];
                    $users[$value->id] = (object) array_merge((array) $value, $userdata);
                } else {
                    // Add not updated teacher elements to the availability fields - Quick FIX - PST.
                    foreach (explode(',', $availablityfields) as $field) {
                        $fieldname = trim(str_replace('pp.', '', $field));
                        $users[$value->id]->{$fieldname} = '';
                    }
                    $users[$value->id]->isavailable = 0;
                }
            }
        }
    }

    /**
     * Generate users list with data who are available to receive the reminder notifications.
     * Selected recipients role users are filtered by fixed/relative date, by role context level.
     *
     * User context role users and course context users other than students are stored separately and notified separately.
     *
     * @param  int $pulseid Pulse instance id.
     * @param  stdClass $instance Pulse instance data.
     * @param  string $type Type of reminder (first, second, recurring, invitation).
     * @param array $excludeusers List of users id need to exclude from result.
     * @return array separated list of users by role.
     */
    public function generate_users_data($pulseid, $instance, $type, $excludeusers=[]) {
        global $DB;

        $recipients = explode(',', $instance->pulsepro->{$type.'_recipients'});

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($recipients);
        // Get available users in course.
        // Get enrolled users with capability.
        $contextlevel = explode('/', $instance->context['path']);
        list($insql, $inparams) = $DB->get_in_or_equal(array_filter($contextlevel));
        $usersql = "SELECT
            u.*, je.roleshortname, je.roleid, pu.timecreated as invitation_reminder_time
            FROM {user} u
            LEFT JOIN {pulse_users} pu ON (pu.status = 1 AND pu.userid = u.id AND pu.pulseid = ?)
            JOIN (SELECT DISTINCT eu1_u.id, ra.roleshortname, ra.roleid
                FROM {user} eu1_u
                JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
                JOIN (SELECT userid, Max(rle.shortname) as roleshortname, MAX(roleid) as roleid
                        FROM {role_assignments}
                        JOIN {role} rle ON rle.id = roleid
                        WHERE contextid $insql
                        AND ( roleid $roleinsql OR rle.shortname = 'student') GROUP BY userid
                    ) ra ON ra.userid = eu1_u.id
                WHERE ej1_ue.status = 0
                AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= ?)
                AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > ?)
                AND eu1_u.deleted = 0 AND eu1_u.id <> ? AND eu1_u.suspended = 0
                ) je ON je.id = u.id
        WHERE u.deleted = 0 AND u.suspended = 0 ";

        $params[] = $pulseid;
        $params[] = $instance->course['id'];
        $params = array_merge($params, array_filter($inparams));
        $params = array_merge($params, array_filter($roleinparams));
        $params[] = time();
        $params[] = time();
        $params[] = 1;

        if (!empty($excludeusers)) {
            list($insql, $param) = $DB->get_in_or_equal($excludeusers, SQL_PARAMS_QM, '', false);
            $usersql .= ' AND u.id '.$insql;
            $params = array_merge($params, array_values($param));
        }

        $usersql .= " ORDER BY u.lastname, u.firstname, u.id ";

        $users = $DB->get_records_sql($usersql, $params);
        $this->merge_user_availability($users, $pulseid);
        // Add the filter for relative date for reminder.
        if ($type == 'invitation') {
            $filteravailability = false;
        } else {
            $filteravailability = ($type == 'recurring'
            || (isset($instance->pulsepro->{$type.'_schedule'}) && $instance->pulsepro->{$type.'_schedule'} == 1)) ? true : false;
        }
        // Filter student users. modules availabilty test returns false for other roles like teachers.
        // So need to filter the students from userslist before check the modules availability.
        $duration = isset($instance->pulsepro->{$type.'_relativedate'}) ? $instance->pulsepro->{$type.'_relativedate'} : 0;

        $instance->type = $type;
        $students = $this->filter_students($users, $instance, $filteravailability, $duration);
        // Filter the users who has access to this instance.

        // Fetch parent users in context role.
        $parents = $this->get_parent_users($recipients, $students, $instance->course['id'], $pulseid);
        // Filter other selected roles.
        $teachers = array_filter($users, function($value) {
            return ($value->roleshortname != 'student') ? true : false;
        });
        if (!empty($teachers)) {
            $this->filter_group_users($teachers, $students);
        }
        // Remove the students list if the reminder doesn't selected the student role to get receipents.
        if (!empty($students)) {
            $studentroleid = reset($students)->roleid;
            if (!in_array($studentroleid, $recipients)) {
                $students = [];
            }
        }
        return [$students, $parents, $teachers];
    }

    /**
     * Filter the users assigned in the groups. Find and separate the teachers and students based on the group.
     * So the teachers are prevent to receive reminders for other group students.
     * Returns the teachers list with the group students
     *
     * @param  array $teachers List of teachers
     * @param  array $students List of students.
     * @return array $teachers List of teachers with list of students.
     */
    public function filter_group_users($teachers, $students) {

        foreach ($teachers as $teacherid => $teacher) {
            $canaccessallgroups = (has_capability('moodle/site:accessallgroups',
                \context_course::instance($this->instance->course['id']), $teacher->id
            ));
            $forcegroups = ($this->instance->course['coursegroupmode'] == SEPARATEGROUPS && !$canaccessallgroups);
            if ($forcegroups) {
                // Teacher user has only able to view the group users.
                $allowedgroupids = array_keys(groups_get_all_groups($this->instance->course['id'], $teacher->id));

                foreach ($students as $studentid => $student) {
                    $usergroups = array_keys(groups_get_all_groups($this->instance->course['id'], $studentid));
                    $diff = array_diff($allowedgroupids, $usergroups);
                    // Difference of user group allocation and the teacher allocation is lesser than the teacher allocation.
                    // Then the user must assigned in any of teacher groups.
                    if (count($allowedgroupids) > count($diff)) {
                        $teachers[$teacherid]->students[$studentid] = $student;
                    }
                }
            } else {
                $teachers[$teacherid]->students = $students;
            }
        }

        return $teachers;
    }

    /**
     * Setup the first reminder adhoc task for selected roles.
     * Users are filtered based on their time duration and module visibilty.
     *
     * @return void
     */
    public function first_reminder() {
        global $DB;
        // Get list of first remainders added.
        if (!empty($this->instances)) {
            foreach ($this->instances as $pulseid => $instance) {
                $this->instance = $instance;
                $reminder = false;
                // Selected roles for the reminder recipents.
                if (!$instance->pulsepro->first_reminder  || !$instance->pulsepro->first_recipients) {
                    continue;
                }
                mtrace('Start the First reminder for instance - '. $instance->pulse['name']);
                list($students, $parents, $teachers) = $this->generate_users_data($pulseid, $instance, 'first');
                // Check is fixed date expires.
                if ($instance->pulsepro->first_schedule == 0) {
                    mtrace('Fixed date scheduled');
                    $fixeddate = $instance->pulsepro->first_fixeddate;
                    if ($fixeddate < time()) {
                        $reminder = true;
                    }
                } else {
                    mtrace('Relative date scheduled');
                    $reminder = true;
                }

                if ($reminder && !empty($students)) {
                    mtrace('Sending first reminder to students');
                    $this->set_reminder_adhoctask($students, $instance, 'first');
                }
                if ($reminder &&!empty($parents)) {
                    mtrace('Sending first reminder to parents');
                    $this->set_reminder_adhoctask($parents, $instance, 'first', 'usercontext');
                }
                if ($reminder &&!empty($teachers)) {
                    mtrace('Sending first reminder to teachers');
                    $this->set_reminder_adhoctask($teachers, $instance, 'first', 'coursecontext');
                }

            }
        }
    }

    /**
     * Setup the second reminder adhoc task for selected roles.
     * Users are filtered based on their time duration and module visibility.
     *
     * @return void
     */
    public function second_reminder() {

        if (!empty($this->instances)) {
            foreach ($this->instances as $pulseid => $instance) {
                $this->instance = $instance;
                $reminder = false;
                mtrace('Start the second reminder for instance - '. $instance->pulse['name']);
                // Selected roles for the reminder recipents.
                if (!$instance->pulsepro->second_reminder || !$instance->pulsepro->second_recipients) {
                    continue;
                }
                list($students, $parents, $teachers) = $this->generate_users_data($pulseid, $instance, 'second');

                // Check is fixed date expires.
                if ($instance->pulsepro->second_schedule == 0) {
                    mtrace('Fixed date scheduled');
                    $fixeddate = $instance->pulsepro->second_fixeddate;
                    if ($fixeddate < time()) {
                        $reminder = true;
                    }
                } else {
                    mtrace('Relative date scheduled');
                    $reminder = true;
                }

                if ($reminder && !empty($students)) {
                    mtrace('Sending second reminder to students');
                    $this->set_reminder_adhoctask($students, $instance, 'second');
                }
                // Send notification to user context roles.
                if ($reminder && !empty($parents)) {
                    mtrace('Sending second reminder to parents');
                    $this->set_reminder_adhoctask($parents, $instance, 'second', 'usercontext');
                }
                // Send al course context roles.
                if ($reminder && !empty($teachers)) {
                    mtrace('Sending second reminder to teachers');

                    $this->set_reminder_adhoctask($teachers, $instance, 'second', 'coursecontext');
                }
            }
        }
    }

    /**
     * Setup the recurring reminder adhoc task for selected roles.
     * Users are filtered based on their time duration and module visibilty.
     *
     * @return void
     */
    public function recurring_reminder() {

        if (!empty($this->instances)) {
            foreach ($this->instances as $pulseid => $instance) {
                $this->instance = $instance;
                $reminder = true;
                mtrace('Start the recurring reminder for instance - '. $instance->pulse['name']);
                // Selected roles for the reminder recipents.
                if (!$instance->pulsepro->recurring_reminder || $instance->pulsepro->recurring_recipients == '') {
                    continue;
                }
                list($students, $parents, $teachers) = $this->generate_users_data($pulseid, $instance, 'recurring');
                mtrace('Relative date scheduled');
                if ($reminder && !empty($students)) {
                    mtrace('Sending recurring reminder to students');
                    $this->set_reminder_adhoctask($students, $instance, 'recurring');
                }
                if ($reminder && !empty($parents)) {
                    mtrace('Sending recurring reminder to parents');
                    $this->set_reminder_adhoctask($parents, $instance, 'recurring', 'usercontext');
                }
                if ($reminder && !empty($teachers)) {
                    mtrace('Sending recurring reminder to teachers');
                    $this->set_reminder_adhoctask($teachers, $instance, 'recurring', 'coursecontext');
                }

            }
        }
    }

    /**
     * Setup the invitation reminder adhoc task for selected roles.
     * Users are filtered based on their module visibilty.
     *
     * @return void
     */
    public function send_invitations() {
        global $DB;
        if (!empty($this->instances)) {

            foreach ($this->instances as $pulseid => $instance) {

                $this->instance = $instance;
                mtrace('Start sending invitation for instance - '. $instance->pulse['name']);
                // Selected roles for the reminder recipents.
                if (!$instance->pulse['pulse'] || !$instance->pulsepro->invitation_recipients) {
                    continue;
                }
                $invitedusers = $DB->get_fieldset_select('pulse_users', 'userid', 'pulseid = :pulseid AND status = 1',
                    ['pulseid' => $pulseid]
                );

                list($students, $parents, $teachers) = $this->generate_users_data($pulseid, $instance, 'invitation', $invitedusers);

                if (!empty($students)) {
                    mtrace('Sending invitation to students');
                    $this->set_reminder_adhoctask($students, $instance, 'invitation');
                }
                if (!empty($parents)) {
                    mtrace('Sending invitation to parents');
                    $this->set_reminder_adhoctask($parents, $instance, 'invitation', 'usercontext');
                }
                if (!empty($teachers)) {
                    mtrace('Sending invitation to teachers');
                    $this->set_reminder_adhoctask($teachers, $instance, 'invitation', 'coursecontext');
                }
            }
        }
    }

    /**
     * Set the adhoc task to update the user activity available time.
     *
     * @return void
     */
    public function update_mod_availability() {

        if (empty($this->instances)) {
            return true;
        }
        foreach ($this->instances as $pulseid => $instance) {
            $task = new \local_pulsepro\task\availability();
            $modulecontext = \context_module::instance($instance->cm->id);
            $instance->students = get_enrolled_users($modulecontext, 'local/pulsepro:storeavailability');
            if (!empty($instance->students)) {
                $task->set_custom_data($instance);
                $task->set_component('local_pulsepro');
                \core\task\manager::queue_adhoc_task($task, true);
            }
        }
    }


    /**
     * Pulse pro availability table fields are defined to use in sql select query.
     *
     * @return string
     */
    public function pulsepro_availability_fields() {
        $fields = [
            'pp.userid', 'pp.pulseid', 'pp.availabletime',
            'pp.first_reminder_status', 'pp.second_reminder_status', 'pp.recurring_reminder_prevtime',
            'pp.first_reminder_time', 'pp.second_reminder_time', 'pp.recurring_reminder_time',
            'pp.invitation_users', 'pp.first_users', 'pp.second_users', 'pp.recurring_users'
        ];
        return implode(', ', $fields);
    }

}
