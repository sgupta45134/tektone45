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
 *
 * @package   local_send_enrol_message
 * @copyright  Sudhanshu Gupta<sudhanshug5@gmail.com>
 */
defined('MOODLE_INTERNAL') || die();

function send_enrol_message(\core\event\user_enrolment_created $event) {
  global $DB, $USER, $CFG;
  $courseid = $event->courseid;
  $userid = $event->relateduserid; // Unenrolled user id.
  $user = \core_user::get_user($userid);
  $a = new stdClass();
  $a->courselink = $CFG->wwwroot."/course/view.php?id=$courseid";
  $a->username = $user->firstname." ".$user->lastname;
  $message = get_string('message', 'local_send_enrol_message',$a);

  //Send mail
  require_once($CFG->libdir . '/moodlelib.php');
  $user = $DB->get_record('user', array('id' => $event->relateduserid));
  $from = \core_user::get_noreply_user();
  $subject = get_string('subject', 'local_send_enrol_message');
  $attachment = $CFG->dirroot.'/local/send_enrol_message/TrainingWelcomePacket.pdf';
  $attachname = 'TrainingWelcomePacket.pdf';
  if(is_franklin($courseid)) email_to_user($user, $from, $subject, $message,'', $attachment, $attachname);
  else email_to_user($user, $from, $subject, $message);

  //Send mail to brian
  $message = get_string('message_admin', 'local_send_enrol_message',$a);
  $user = $DB->get_record('user', array('email' => 'training@tektone.com'));
  $attachname = 'TrainingWelcomePacket.pdf';
  if(is_franklin($courseid)) email_to_user($user, $from, $subject, $message,'', $attachment, $attachname);
  else email_to_user($user, $from, $subject, $message);
}

function is_franklin($courseid) {
    global $DB;
    $data = $DB->get_record_sql("SELECT id from {course} where id = $courseid and fullname like '%FRANKLIN%'");
    if(!empty($data)) return true;
    else return false;
}
