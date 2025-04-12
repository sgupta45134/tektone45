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
 * Privacy implementation for Pulse Pro.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_pulsepro\privacy;

use stdClass;
use context;

use core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * The pulse pro stores user reminder notified time and tokens used on the notification.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * List of used data fields summary meta key.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // Completion table fields meta summary.
        $completionmetadata = [
            'pulseid' => 'privacy:metadata:pulseid',
            'userid' => 'privacy:metadata:userid',
            'status' => 'privacy:metadata:availability:status',
            'availabletime' => 'privacy:metadata:availability:availabletime',
            'first_reminder_status' => 'privacy:metadata:availability:first_reminder_status',
            'second_reminder_status' => 'privacy:metadata:availability:second_reminder_status',
            'first_reminder_time' => 'privacy:metadata:availability:first_reminder_time',
            'second_reminder_time' => 'privacy:metadata:availability:second_reminder_time',
            'recurring_reminder_time' => 'privacy:metadata:availability:recurring_reminder_time',
            'recurring_reminder_prevtime' => 'privacy:metadata:availability:recurring_reminder_prevtime',
            'invitation_users' => 'privacy:metadata:availability:invitation_users',
            'first_users' => 'privacy:metadata:availability:first_users',
            'second_users' => 'privacy:metadata:availability:second_users',
            'recurring_users' => 'privacy:metadata:availability:recurring_users',
        ];
        $collection->add_database_table('local_pulse_availability', $completionmetadata, 'privacy:metadata:availability');

        // Users invitation notified data.
        $usersmetadata = [
            'pulseid' => 'privacy:metadata:pulseid',
            'userid' => 'privacy:metadata:userid',
            'relateduserid' => 'privacy:metadata:token:relateduserid',
            'token' => 'privacy:metadata:token:token',
            'reactiontype' => 'privacy:metadata:token:reactiontype',
            'status' => 'privacy:metadata:token:status',
            'timemodified' => 'privacy:metadata:token:timemodified',
            'timecreated' => 'privacy:metadata:token:timecreated'

        ];
        $collection->add_database_table('local_pulsepro_tokens', $usersmetadata, 'privacy:metadata:tokens');

         // Users invitation notified data.
        $creditsmetadata = [
            'userid' => 'privacy:metadata:userid',
            'pulseid' => 'privacy:metadata:credits:status',
            'credit' => 'privacy:metadata:credits:credit',
            'timecreated' => 'privacy:metadata:credits:timecreated'
        ];
        $collection->add_database_table('local_pulsepro_credits', $creditsmetadata, 'privacy:metadata:pulsecredits');

        // Added moodle subsystems used in pulse.
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:pulsemessageexplanation');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int         $userid    The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        // User availability.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pulse} p ON p.id = cm.instance
                LEFT JOIN {local_pulsepro_availability} pc ON pc.pulseid = p.id
                WHERE (pc.userid = :userid)";

        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        // Invitation notified users.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pulse} p ON p.id = cm.instance
                LEFT JOIN {local_pulsepro_tokens} pt ON pt.pulseid = p.id
                WHERE (pt.userid = :userid or pt.relateduserid = :relateduserid)";
        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
            'relateduserid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        // Pulse user credits.
        $sql = "SELECT c.id
        FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {pulse} p ON p.id = cm.instance
        LEFT JOIN {local_pulsepro_credits} pu ON pu.pulseid = p.id
        WHERE pu.userid = :userid";
        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'instanceid'    => $context->instanceid,
            'modulename'    => 'pulse',
        ];

         // Discussion authors.
        $sql = "SELECT d.userid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {local_pulsepro_tokens} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Approved users.
        $sql = "SELECT d.relateduserid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {local_pulsepro_tokens} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('relateduserid', $sql, $params);

        $sql = "SELECT d.userid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {local_pulsepro_availability} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Credit users.
        $sql = "SELECT d.userid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {local_pulsepro_credits} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['pulseid' => $pulse->id], $userinparams);
        $sql = "pulseid = :pulseid AND userid {$userinsql}";
        $DB->delete_records_select('local_pulsepro_tokens', $sql, $params);
        $DB->delete_records_select('local_pulsepro_availability', $sql, $params);
        $DB->delete_records_select('local_pulsepro_credits', $sql, $params);

        $sql = "pulseid = :pulseid AND relateduserid {$userinsql}";
        $DB->set_field_select('local_pulsepro_tokens', 'status', 0, $sql, $params);
        $DB->set_field_select('local_pulsepro_tokens', 'relateduserid', '', $sql, $params);
    }

    /**
     * Delete user completion data for multiple context.
     *
     * @param approved_contextlist $contextlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('local_pulsepro_availability', ['pulseid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('local_pulsepro_tokens', ['pulseid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('local_pulsepro_credits', ['pulseid' => $instanceid, 'userid' => $userid]);

            $DB->set_field('local_pulsepro_tokens', 'status', 0, ['pulseid' => $instanceid, 'relateduserid' => $userid]);
            $DB->set_field('local_pulsepro_tokens', 'relateduserid', '', ['pulseid' => $instanceid, 'relateduserid' => $userid]);
        }
    }

    /**
     * Delete all completion data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('pulse', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('local_pulsepro_availability', ['pulseid' => $cm->instance]);
        $DB->delete_records('local_pulsepro_tokens', ['pulseid' => $cm->instance]);
        $DB->delete_records('local_pulsepro_credits', ['pulseid' => $cm->instance]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        // Context user.
        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT pc.id AS availabilityid,
                cm.id AS cmid, c.id AS contextid,
                p.id AS pid, p.course AS pcourse,
                pc.status as availabilitystatus,
                pc.availabletime as availabletime,
                pc.first_reminder_status as first_reminder_status,
                pc.second_reminder_status as second_reminder_status,
                pc.first_reminder_time as first_reminder_time,
                pc.second_reminder_time as second_reminder_time,
                pc.recurring_reminder_time as recurring_reminder_time,
                pc.recurring_reminder_prevtime as recurring_reminder_prevtime,
                pc.invitation_users as invitation_users,
                pc.first_users as first_users,
                pc.second_users as second_users,
                pc.recurring_users as recurring_users
              FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {pulse} p ON p.id = cm.instance
        INNER JOIN {local_pulsepro_availability} pc ON pc.pulseid = p.id AND pc.userid = :userid
            WHERE c.id {$contextsql}
            ORDER BY cm.id, pc.id ASC";

        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $contextlist->get_user()->id,
        ];
        $availabilities = $DB->get_records_sql($sql, $params + $contextparams);

        self::export_pulse_availabilities(get_string('availability', 'local_pulsepro'), $availabilities, $user);

        $sql = "SELECT pc.id AS tokenid,
                cm.id AS cmid, c.id AS contextid,
                p.id AS pid, p.course AS pcourse,
                pc.userid as userid,
                pc.relateduserid as relateduserid,
                pc.token as token,
                pc.reactiontype as reactiontype,
                pc.status as tokenstatus,
                pc.timecreated as timecreated,
                pc.timemodified as timemodified
            FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {pulse} p ON p.id = cm.instance
        INNER JOIN {local_pulsepro_tokens} pc ON pc.pulseid = p.id AND (pc.userid = :userid or pc.relateduserid = :relateduserid)
            WHERE c.id {$contextsql}
            ORDER BY cm.id, pc.id ASC";

        $tokens = $DB->get_records_sql($sql, $params + $contextparams + ['relateduserid' => $params['userid']]);

        self::export_pulse_tokens(
            get_string('tokenrecevied', 'local_pulsepro'),
            array_filter(
                $tokens,
                function(stdClass $completion) use ($contextlist) : bool {
                    return $completion->userid == $contextlist->get_user()->id;
                }
            ),
            $user
        );

        self::export_pulse_tokens(
            get_string('approvedviatoken', 'local_pulsepro'),
            array_filter(
                $tokens,
                function(stdClass $completion) use ($contextlist) : bool {
                    return $completion->relateduserid == $contextlist->get_user()->id;
                }
            ),
            $user
        );

        // User credits.
        $sql = "SELECT pc.id AS creditid, cm.id AS cmid, c.id AS contextid,
                p.id AS pid, p.course AS pcourse,
                pc.userid AS userid, pc.credit as credit
              FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {pulse} p ON p.id = cm.instance
        INNER JOIN {local_pulsepro_credits} pc ON pc.pulseid = p.id AND pc.userid = :userid
            WHERE c.id {$contextsql}
            ORDER BY cm.id, pc.id ASC";

        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $contextlist->get_user()->id,
        ];
        $credits = $DB->get_records_sql($sql, $params + $contextparams);
        self::export_pulse_credits(get_string('credit', 'mod_pulse'), $credits, $user);
    }

    /**
     * Export the pulse credits data for user.
     *
     * @param string $path Data path to display.
     * @param array $credits all available user credits.
     * @param stdclass $user User record object.
     * @return void
     */
    private static function export_pulse_credits(string $path, array $credits, $user) {
        $creditsbycontextid = self::group_by_property($credits, 'contextid');
        foreach ($creditsbycontextid as $contextid => $credits) {
            $context = context::instance_by_id($contextid);

            foreach ($credits as $creditid => $credit) {
                $creditdata = ['credits' => $credit->credit];

                if (!empty($creditdata)) {
                    $context = context::instance_by_id($contextid);
                    // Fetch the generic module data for the questionnaire.
                    $contextdata = (object) $creditdata;
                    writer::with_context($context)->export_data([],
                        $contextdata
                    );
                }
            };
        }
    }

    /**
     * Helper function to export completions.
     *
     * The array of "completions" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of sessions. Which is why it needs to be grouped by context id.
     *
     * @param string $path The path in the export (relative to the current context).
     * @param array $tokens Array of completions to export the logs for.
     * @param stdclass $user User record object.
     */
    private static function export_pulse_tokens(string $path, array $tokens, $user) {
        $tokensbycontextid = self::group_by_property($tokens, 'contextid');
        foreach ($tokensbycontextid as $contextid => $token) {
            $context = context::instance_by_id($contextid);
            $tokensbyid = self::group_by_property($token, 'tokenid');
            foreach ($tokensbyid as $tokenid => $tokens) {
                $tokendata = array_map(function($token) use ($user) {
                    if ($user->id == $token->relateduserid) {
                        return [
                            'approvalstatus' => $token->tokenstatus == 1 ? get_string('yes') : get_string('no'),
                            'approvalby' => fullname(\core_user::get_user($token->userid)),
                            'approvaltime' => $token->timemodified ? transform::datetime($token->timemodified) : '-',
                        ];

                    } else {

                        $reactionstatus = function(stdclass $token): string {
                            if ($token->reactiontype == 2) {
                                return ($token->tokenstatus == 2) ? get_string('like', 'pulse') : get_string('dislike', 'pulse');
                            } else if ($token->reactiontype == 3) {
                                return ($token->tokenstatus == 1) ? get_string('approved', 'pulse') : '-';
                            } else if ($token->reactiontype == 1) {
                                return ($token->tokenstatus == 1) ? get_string('selfcomplete', 'pulse') : '-';
                            }
                            return '-';
                        };

                        $base = [
                            'reactiontype' => self::find_the_reactiontype($token->reactiontype),
                            'reactionstatus' => $reactionstatus,
                            'timemodified' => $token->timemodified
                                ? transform::datetime($token->timemodified) : '-',
                            'timecreated' => $token->timecreated ? transform::datetime($token->timecreated) : '-',
                        ];

                        if (!empty($token->relateduserid)) {
                            $base['approved_the_user'] = fullname(\core_user::get_user($token->relateduserid));
                        }

                        return $base;
                    }
                }, $tokens);

                if (!empty($tokendata)) {
                    $context = context::instance_by_id($contextid);
                    // Fetch the generic module data for the questionnaire.
                    $contextdata = helper::get_context_data($context, $user);
                    $contextdata = (object)array_merge((array)$contextdata, $tokendata);
                    writer::with_context($context)->export_data(
                        [get_string('privacy:token', 'local_pulsepro').' '.$tokenid, $path],
                        $contextdata
                    );
                }
            };
        }
    }

    /**
     * Reaction type in text
     *
     * @param int $reactiontype selected reaction type
     * @return string Reaction type in text.
     */
    public static function find_the_reactiontype(int $reactiontype): string {
        switch ($reactiontype) {
            case 1: // Mark Complete.
                $type = 'Self complete';
                break;
            case 2:
                // Rate.
                $type = 'Rate';
                break;
            case 3:
                // Approve.
                $type = 'Approve users';
                break;
        }
        return isset($type) ? $type : '-';
    }

    /**
     * Helper function to export availabilities.
     *
     * The array of "availabilities" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of sessions. Which is why it needs to be grouped by context id.
     *
     * @param string $path The path in the export (relative to the current context).
     * @param array $availabilities Array of availabilities to export the logs for.
     * @param stdclass $user User record object.
     */
    private static function export_pulse_availabilities(string $path, array $availabilities, $user) {

        $availablesbycontextid = self::group_by_property($availabilities, 'contextid');
        $teacherfields = ['first_users', 'second_users', 'recurring_users'];
        foreach ($availablesbycontextid as $contextid => $available) {
            $context = context::instance_by_id($contextid);
            $availablesbyid = self::group_by_property($available, 'availabilityid');
            foreach ($availablesbyid as $availabilityid => $availables) {
                $availabledata = array_map(function($available) use ($user, $teacherfields) {
                    $availablefields = [
                        'availabilitystatus' => (($available->availabilitystatus == 1) ? get_string('yes') : get_string('no')),
                        'availabletime' => $available->availabletime
                            ? transform::datetime($available->availabletime) : '-',
                        'first_reminder_notified' =>
                            (($available->first_reminder_status == 1) ? get_string('yes') : get_string('no')),
                        'first_reminder_time' =>
                            ($available->first_reminder_time ? transform::datetime($available->first_reminder_time) : '-'),
                        'second_reminder_notified' =>
                            (($available->second_reminder_status == 1) ? get_string('yes') : get_string('no')),
                        'second_reminder_time' =>
                            ($available->second_reminder_time ? transform::datetime($available->second_reminder_time) : '-'),
                        'recurring_reminder_time' =>
                            ($available->recurring_reminder_time ? transform::datetime($available->recurring_reminder_time) : '-'),
                        'previous_recurring_reminders' =>
                            self::generate_previous_recurring($available->recurring_reminder_prevtime),
                    ];

                    foreach ($teacherfields as $field) {
                        if (isset($available->{$field}) && !empty($available->{$field}) ) {
                            $availablefields[$field] = array_map(function($field) {
                                return fullname(\core_user::get_user($field));
                            }, json_decode($available->$field));
                        }
                    }
                    return $availablefields;

                }, $availables);

                if (!empty($availabledata)) {
                    $context = context::instance_by_id($contextid);
                    // Fetch the generic module data for the questionnaire.
                    $contextdata = helper::get_context_data($context, $user);
                    $contextdata = (object)array_merge((array)$contextdata, $availabledata);
                    writer::with_context($context)->export_data(
                        [get_string('privacy:availability', 'local_pulsepro').' '.$availabilityid, $path],
                        $contextdata
                    );
                }
            };
        }
    }

    /**
     * Previous recurring reminder.
     *
     * @param string|null $prevtime
     * @return array
     */
    public static function generate_previous_recurring(?string $prevtime): array {
        $previoustimes = json_decode($prevtime);
        if ($previoustimes) {
            foreach ($previoustimes as $time) {
                $times[] = transform::datetime($time);
            }
        }
        return isset($times) ? $times : [];
    }

    /**
     * Helper function to group an array of stdClasses by a common property.
     *
     * @param array $classes An array of classes to group.
     * @param string $property A common property to group the classes by.
     * @return array list of element seperated by given property.
     */
    private static function group_by_property(array $classes, string $property): array {
        return array_reduce(
            $classes,
            function (array $classes, stdClass $class) use ($property) : array {
                $classes[$class->{$property}][] = $class;
                return $classes;
            },
            []
        );
    }

}
