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
 * Adhoc task definition to Send reminders to users.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_pulsepro\task;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * The Adhoc task sends the reminders notification. Task setup for each reminder separately.
 */
class sendreminders extends \core\task\adhoc_task {

    /**
     * Type of the reminder (first, second, recurring, invitation)
     *
     * @var string
     */
    public $type;

    /**
     * List of notified users, Used to update the notified users status after reminders are send to all users.
     *
     * @var array
     */
    public $notifiedusers = [];

    /**
     * Adhoc task execution send the reminder to multiple roles.
     * Reminder are send to student roles, course context roles, and user context roles.
     *
     * Course context roles and user context roles are notified for each students separately.
     * @return true
     */
    public function execute() {
        global $DB;
        $instance = $this->get_custom_data();
        if (empty($instance)) {
            return true;
        }
        $this->instance = $instance;
        $this->type = $instance->type;
        if (!$DB->record_exists('pulse', ['id' => $this->instance->pulse->id])) {
            return true;
        }
        mtrace('Sending reminders - '. $this->type);
        if ($instance->role == 'student') {
            if (!empty($instance->users)) {
                foreach ($instance->users as $user) {
                    if (isset($user->id)) {
                        if ($this->type == 'invitation') {
                            $condition = ['userid' => $user->id, 'pulseid' => $instance->pulse->id, 'status' => 1];
                            if (!$DB->record_exists('pulse_users', $condition)) {
                                mtrace(
                                    'Prepare '.$this->type.' reminder eventdata for the user - '. $user->id. ' for the pulse '.
                                    $instance->pulse->name
                                );
                                $this->send_notification($user, $instance);
                            }
                        } else {
                            if ($this->type == 'recurring' || (!isset($user->{$this->type.'_reminder_status'})
                                || $user->{$this->type.'_reminder_status'} == 0) ) {
                                mtrace('Prepare '.$this->type.' reminder eventdata for the user - '. $user->id.
                                    ' for the pulse '. $instance->pulse->id
                                );
                                $this->send_notification($user, $instance);
                            }
                        }
                    }
                }
            }
        }
        // Send the notification to parents.
        if ($instance->role == 'usercontext' && !empty($instance->users)) {
            mtrace(" $this->type notification to user roles started");
            foreach ($instance->users as $parent) {
                mtrace("Prepare students data for the parent ". $parent->username);
                if (!empty($parent->students)) {
                    foreach ($parent->students as $studentid => $student) {
                        // Check the parent user notified about this student.
                        if (!$this->is_parent_notified($parent, $student)) {
                            $student->approveuser = $parent->id;
                            $this->send_notification($student, $instance, $parent, 'parent');
                        }
                    }
                } else {
                    mtrace("Parent doesn't have available students");
                }
            }
        }

        // Send the notification to teachers.
        if ($instance->role == 'coursecontext' && !empty($instance->users)) {
            mtrace(" $this->type notification to course roles started");
            foreach ($instance->users as $teacher) {
                mtrace("Prepare students data for the teacher ". $teacher->username);
                if (!empty($teacher->students)) {
                    foreach ($teacher->students as $studentid => $student) {
                        if (!$this->is_parent_notified($teacher, $student)) {
                            $student->approveuser = $teacher->id;
                            $this->send_notification($student, $instance, $teacher, 'teacher');
                        }
                    }
                } else {
                    mtrace("Parent doesn't have available students");
                }
            }
        }

        mtrace('Completed the Send reminder for instance - '. $instance->pulse->id);

        if ($this->type == 'invitation' && !empty($this->notifiedusers) ) {
            // Update invite users in pulse users.
            mod_pulse_update_notified_users($this->notifiedusers, $instance->pulse);
        }
        return true;
    }


    /**
     * Check usercontext or other role users in coursecontext has notified for the student.
     *
     * @param  object $parent
     * @param  object $student
     * @return bool result of user notification
     */
    public function is_parent_notified($parent, $student) {
        if (!empty($parent->{$this->type.'_users'})) {
            $notified = json_decode($parent->{$this->type.'_users'});
            if ($this->type == 'recurring') {
                $comparetime = ($parent->recurring_reminder_time != '') ? $parent->recurring_reminder_time : time();
                $difference = time() - $comparetime;
                $duration = $this->instance->pulsepro->recurring_relativedate;
                if ($duration && $difference > $duration) {
                    return false;
                }
            }
            return (in_array($student->id, $notified)) ? true : false;
        }
        return false;
    }

    /**
     * Send reminder notification to available users. Users are filter by selected fixed date or relative date.
     * Once the reminders and invitations are send then it will updates the notified users list in availability table.
     *
     * @param  stdclass $user User record data
     * @param  stdclass $instance Pulse instance record.
     * @param  object $sendto Notification sendto user or course context roles. otherwise it send to the $user.
     * @param  string $method Notification method.
     * @return void
     */
    public function send_notification($user, $instance, $sendto=null, $method='student') {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/pulse/lib.php');

        $course = (object) $instance->course;
        $context = (object) $instance->context;
        $pulse = (object) $instance->pulse;
        $pulsepro = $instance->pulsepro;
        $type = $instance->type;
        $filearea = $type.'_content';

        // Use intro content as message text, if different pulse disabled.
        $subject = ($type == 'invitation') ? $instance->pulse->pulse_subject : $instance->pulsepro->{$type.'_subject'};
        if ($type == 'invitation') {
            $template = ($instance->pulse->diff_pulse) ? $instance->pulse->pulse_content : $pulse->intro;
            $filearea = ($instance->pulse->diff_pulse) ? 'pulse_content' : 'intro';
            $subject = ($instance->pulse->diff_pulse) ? $instance->pulse->pulse_subject : $pulse->name;
        } else {
            $template = $instance->pulsepro->{$type.'_content'};
        }

        // Find the sender for that user.
        $sender = \mod_pulse\task\sendinvitation::find_user_sender($instance->sender, $user->id);

        // Update the notification header and footer templates.
        self::join_notification_template($template);

        // Replace the email text placeholders with data.
        list($subject, $messagehtml) = mod_pulse_update_emailvars($template, $subject, $course, $user, $pulse, $sender);
        // Rewrite the plugin file placeholders in the email text.
        $messagehtml = file_rewrite_pluginfile_urls($messagehtml, 'pluginfile.php',
        $context->id, 'mod_pulse', $filearea, 0);
        $messageplain = html_to_text($messagehtml); // Plain text.

        // Send message to user.
        mtrace( " Sending pulse to the user ". fullname($user) ."\n" );
        $sendto = ($sendto != null) ? $sendto : $user;
        $messagesend = mod_pulse_messagetouser($sendto, $subject, $messageplain, $messagehtml, $pulse, $sender);
        if ($messagesend) {
            if ($type == 'invitation') {
                if ($method == 'student') {
                    $this->notifiedusers[] = $sendto->id;
                }
            }
            // Update reminders notification send status to prevent send notification on next adhoc task.
            $record = new \stdclass();
            if ($type != 'invitation') {
                $record->{$type.'_reminder_time'} = time();
                // Update the reminder status only for the student roles.
                if ($method == 'student') {
                    if ($type == 'recurring') {
                        $prevtime = $sendto->recurring_reminder_prevtime;
                        if (!empty($prevtime)) {
                            $prevtime = json_decode($prevtime);
                            $prevtime = (is_array($prevtime)) ? $prevtime : [];
                            array_push($prevtime, $sendto->recurring_reminder_time);
                        } else {
                            $prevtime = ($sendto->recurring_reminder_time) ?
                                array($sendto->recurring_reminder_time) : [$record->recurring_reminder_time];
                        }
                        $record->recurring_reminder_prevtime = json_encode($prevtime);

                    } else {
                        $record->{$type.'_reminder_status'} = 1;
                    }
                }
            }

            if ($availdata = $DB->get_record('local_pulsepro_availability', ['userid' => $sendto->id, 'pulseid' => $pulse->id])) {
                if ($method != 'student') {
                    // Update the notified user id as json in availability table for that role user to prevent notify again.
                    $notifiedusers = ($availdata->{$type.'_users'}) ? json_decode($availdata->{$type.'_users'}) : [];
                    array_push($notifiedusers, $user->id);
                    $notifiedusers = array_unique($notifiedusers);
                    $record->{$type.'_users'} = json_encode($notifiedusers);
                }
                if (!empty((array) $record)) {
                    $record->id = $availdata->id;
                    $DB->update_record('local_pulsepro_availability', $record);
                }
            } else {
                // Update notified users for not student role users.
                if ($method != 'student') {
                    $record->{$type.'_users'} = json_encode([$user->id]);
                }
                $record->pulseid = $instance->pulse->id;
                $record->userid = $sendto->id;
                $record->availabletime = 0;
                $DB->insert_record('local_pulsepro_availability', (array) $record);
            }
        }
        return true;
    }

    /**
     * Join the global notification template with the invitation notification content.
     *
     * @param string $template
     * @return void
     */
    public static function join_notification_template(string &$template): void {
        global $CFG;

        $context = \context_system::instance();
        $header = get_config('mod_pulse', 'notificationheader');
        $headerhtml = file_rewrite_pluginfile_urls($header, 'pulginfile.php', $context->id, 'mod_pulse', 'notificationheader', 0);
        $headerhtml = format_text($headerhtml, FORMAT_HTML, array('trusted' => true, 'noclean' => true));
        $footer = get_config('mod_pulse', 'notificationfooter');
        $footerhtml = file_rewrite_pluginfile_urls($footer, 'pulginfile.php', $context->id, 'mod_pulse', 'notificationfooter', 0);
        $footerhtml = format_text($footerhtml, FORMAT_HTML, array('trusted' => true, 'noclean' => true));

        $template = $headerhtml . $template . $footerhtml;
    }
}
