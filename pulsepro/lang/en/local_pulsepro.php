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
 * Pulse language contents defined.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Pulse Pro';
$string['generalsettings'] = 'Pulse Pro settings';
$string['expiretime'] = 'Expire time';
$string['expiretimedesc'] = 'Enter the time limit to allow the users to make reactions using tokenized URL.';
$string['pulsepro:storeavailability'] = 'Store user available time';
$string['pulsepro:viewreports'] = 'View pulse pro reports';

$string['availability'] = 'Availability';
$string['privacy:token'] = 'Token';
$string['privacy:availability'] = 'Availability';
$string['tokenrecevied'] = 'Token Recevied';
$string['approvedviatoken'] = 'Approved via token';
$string['order_no'] = "Order No";
$string['invalidpresetid'] = "Invalid preset ID";

// Privacy.
$string['privacy:metadata:availability'] = 'Pulse availability for user';
$string['privacy:metadata:pulseid'] = 'Pulse instance ID';
$string['privacy:metadata:userid'] = 'ID of user';
$string['privacy:metadata:availability:status'] = 'Pulse available status for user.';
$string['privacy:metadata:availability:availabletime'] = 'Time of the pulse module available to user';
$string['privacy:metadata:availability:first_reminder_status'] = 'Status of first reminder send';
$string['privacy:metadata:availability:second_reminder_status'] = 'Status of second reminder send';
$string['privacy:metadata:availability:first_reminder_time'] = 'Time of the first reminder send to user';
$string['privacy:metadata:availability:second_reminder_time'] = 'Time of the second reminder send to user';
$string['privacy:metadata:availability:recurring_reminder_time'] = 'Time of the lateset recurring reminder send to user';
$string['privacy:metadata:availability:recurring_reminder_prevtime'] = 'List of previous recurring reminder send to user';
$string['privacy:metadata:availability:invitation_users'] = 'List of users who received the invitation mail from the teacher roles';
$string['privacy:metadata:availability:first_users'] = 'List of users id send to the teacher for first reminder';
$string['privacy:metadata:availability:second_users'] = 'List of users id send to the teacher for second reminder';
$string['privacy:metadata:availability:recurring_users'] = 'List of users id send to the teacher for recurring reminder';
$string['privacy:metadata:tokens'] = 'User tokens for apply reactions';
$string['privacy:metadata:token:relateduserid'] = 'ID of the student related to the reaction, In approval, teacher user reacts for student';
$string['privacy:metadata:token:token'] = 'Token for the user reaction.';
$string['privacy:metadata:token:reactiontype'] = 'Reaction type for the token';
$string['privacy:metadata:token:status'] = 'Status of reaction applied or not';
$string['privacy:metadata:token:timemodified'] = 'Time of reaction modified';
$string['privacy:metadata:token:timecreated'] = 'Time of token generated';
$string['privacy:metadata:pulsecredits'] = 'Credits for pulse modules';
$string['privacy:metadata:credits:status'] = 'Enable/disable credit status for the pulse';
$string['privacy:metadata:credits:credit'] = 'Credit score for the pulse';
$string['privacy:metadata:credits:timecreated'] = 'Time of credit created for the pulse';
$string['privacy:metadata:pulsemessageexplanation'] = 'Reminder notifications send to users.';
