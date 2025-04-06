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
 * Pulse pro - Update reaction page. Returns the success message if successfully reaction updated. otherwise redirect to dashboard.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

$token = required_param('token', PARAM_RAW);
$rate = optional_param('rate', 0, PARAM_INT);
$react = new local_pulsepro\react($token);

$heading = get_string('completereaction', 'pulse');
$context = context_module::instance($react->cm->id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('maintenance');
$url = new moodle_url('/local/pulsepro/reaction.php', ['token' => $token]);
$PAGE->set_url($url);
$PAGE->set_course($react->course);
$PAGE->set_cm($react->cm);
$PAGE->add_body_class('pulse-reaction-page');

$tokenrecord = $react->tokenrecord;

if (!$tokenrecord) {
    throw new \moodle_exception('invalidtoken', 'error');
    require_login();
}

$timeexpire = get_config('local_pulsepro', 'expiretime'); // Time expiration in seconds.
$userid = $tokenrecord->userid;
$pulseid = $tokenrecord->pulseid;

if ($timeexpire != 0 && (time() - $tokenrecord->timecreated) >= $timeexpire) {
    if (!isloggedin()) {
        $dashboard = new moodle_url('/');
        redirect($dashboard, get_string('tokenexpired', 'pulse'));
    }
    // Check is token valid.
} else if ($timeexpire == 0 || (time() - $tokenrecord->timecreated) < $timeexpire) {
    // Check the user already logged in as token user. Otherwise redirect to dashboard.
    if (isloggedin() && $USER->id != $userid) {
        $dashboard = new moodle_url('/');
        redirect($dashboard, get_string('notsameuser', 'pulse'));
    }
}

switch ($react->pulsedata->reactiontype) {
    case 1: // Mark Complete.
        $react->mark_complete();
        break;
    case 2:
        // Rate.
        $react->update_status($rate);
        break;
    case 3:
        // Approve.
        $react->approve_user();
        break;
}

$courseurl = new moodle_url('/course/view.php', ['id' => $react->course->id]);
$template = $OUTPUT->render_from_template('local_pulsepro/reaction',
    ['courseurl' => $courseurl, 'sitefullname' => $SITE->fullname]
);

echo $OUTPUT->header();

echo $template;

echo $OUTPUT->footer();
