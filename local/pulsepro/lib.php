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
 * PulsePro - Libarary file contains all the extended function definitions.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die('No direct access !');


/**
 * Mark complete reaction type constant value
 */
define('REACTION_MARKCOMPLETE', 1);

/**
 * Rate reaction type value.
 */
define('REACTION_RATE', 2);

/**
 * Value of approval by selected role reaction method.
 */
define('REACTION_APPROVAL', 3);

/**
 * List of strings for different reaction methods.
 */
define('REACTIONS', [
    0 => get_string('noreaction', 'mod_pulse'),
    1 => get_string('markcomplete', 'mod_pulse'),
    2 => get_string('rate', 'mod_pulse'),
    3 => get_string('approve', 'mod_pulse')
]);

/**
 * Add the pulse reactions view reports page link to course administration section under reports category.
 *
 * @param  navigation_node $navigation Navigation nodes.
 * @param  stdclass $course Current course object.
 * @param  coursecontext $context Course context object.
 * @return void
 */
function local_pulsepro_extend_navigation_course($navigation, $course, $context) {
    $node = $navigation->get('coursereports');
    if (has_capability('local/pulsepro:viewreports', $context)) {
        $url = new moodle_url('/local/pulsepro/index.php', ['id' => $course->id]);
        $node->add(get_string('reports', 'pulse'), $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/report', ''));
    }
}

/**
 * Fetch the list of pulse instance created in the course.
 *
 * @param  int $courseid Course id.
 * @return array list of pulse instance added in the course.
 */
function local_pulsepro_course_instancelist($courseid) {
    global $DB;
    $sql = "SELECT cm.*, pl.name FROM {course_modules} cm
            JOIN {pulse} pl ON pl.id = cm.instance
            WHERE cm.course=:courseid AND cm.module IN (SELECT id FROM {modules} WHERE name=:pulse)";
    return $DB->get_records_sql($sql, ['courseid' => $courseid, 'pulse' => 'pulse']);
}

/**
 * Extended pulse module add / edit form to include the pro features.
 *
 * @param  moodle_form $mform Pulse Module form object.
 * @param  stdclass $instance Pulse mform class object.
 * @param  string $method (reaction => Fetch only reaction fields otherwise return other than the reaction fields)
 * @return void null
 */
function local_pulsepro_extend_pulse_form($mform, $instance, $method='') {
    global $PAGE;

    if ($method == 'reaction') {

        // Actions section.
        $mform->addElement('header', 'actions', get_string('actions', 'pulse'));

        if (empty(\local_pulsepro\credits::creditsfield())) {
            $setuppending = get_string("setupcredit", 'pulse');
            $setup = get_string('setup', 'pulse');
            $pulsesettings = new moodle_url('/admin/settings.php?section=local_pulsepro_settings');
            $mform->addElement('html',
            '<div class="credits-field-pending">'.$setuppending.' <a href="'.$pulsesettings.'">'.$setup.'</a></div>');
        } else {
            $credit = $mform->createElement('text', 'credits', get_string('credits', 'pulse'));
            $credits[] =& $credit;
            $credits[] =& $mform->createElement('checkbox', 'credits_status', '', get_string('enable'));
            $mform->addGroup($credits, 'creditgroup', get_string('creditesgroup', 'pulse'), '', false);
            $mform->disabledIf('credits', 'credits_status');
            $mform->setType('credits', PARAM_INT);
        }

        // Reaction section header.
        $mform->addElement('header', 'reactions', get_string('reactions', 'mod_pulse'));

        $mform->addElement('select', 'reactiontype', get_string('reactiontype', 'pulse'), REACTIONS);
        $mform->setType('reactiontype', PARAM_INT);
        $mform->addHelpButton('reactiontype', 'reactiontype', 'mod_pulse');

        $displaytype = array(
            0 => get_string('displaytype:notificationonly', 'mod_pulse'),
            1 => get_string('displaytype:notificationcontent', 'mod_pulse'),
            2 => get_string('displaytype:contentonly', 'mod_pulse'),
        );
        $mform->addElement('select', 'reactiondisplay', get_string('reactiondisplaytype', 'mod_pulse'), $displaytype);
        $mform->setType('reactiondisplay', PARAM_INT);
        $mform->addHelpButton('reactiondisplay', 'reactiondisplaytype', 'mod_pulse');
        return '';
    }
    // Get list of roles in course and user context to get notifications.
    $roles = $instance->course_roles();
    // Roles of recipients that need ot receive Invitation.
    $select = $mform->addElement('autocomplete', 'invitation_recipients',
                get_string('recipients', 'pulse'), $roles);
    $select->setMultiple(true);
    $mform->addHelpButton('invitation_recipients', 'recipients', 'mod_pulse');

    // First Reminder.
    $mform->addElement('header', 'first_reminders', get_string('head:firstreminder', 'mod_pulse'));

    // Enable / disable first reminder.
    $mform->addElement('checkbox', 'first_reminder', get_string('enablereminder:first', 'pulse'),
        get_string('enable:disable', 'pulse'));
    $mform->addHelpButton('first_reminder', 'enablereminder:first', 'mod_pulse');

    // First reminder subject.
    $mform->addElement('text', 'first_subject', get_string('remindersubject', 'pulse'), array('size' => '64' ));
    $mform->setType('first_subject', PARAM_RAW);
    $mform->addHelpButton('first_subject', 'remindersubject', 'mod_pulse');

    $editoroptions  = null;// pulse_get_editor_options();
    $mform->addElement('editor', 'first_content_editor', get_string('remindercontent', 'pulse'),
    ['class' => 'fitem_id_templatevars_editor'], $editoroptions);
    $mform->setType('first_content_editor', PARAM_RAW);
    $instance->pulse_email_placeholders($mform);
    $mform->addHelpButton('first_content_editor', 'remindercontent', 'mod_pulse');

    $select = $mform->addElement('autocomplete', 'first_recipients',
                get_string('recipients', 'pulse'), $roles);
    $select->setMultiple(true);
    $mform->addHelpButton('first_recipients', 'recipients', 'mod_pulse');

    $group = array();
    $radioarray = array();
    $radioarray[] = $mform->createElement('radio', 'first_schedule', '', get_string('schedule:fixeddate', 'pulse'), 0);
    $radioarray[] = $mform->createElement('radio', 'first_schedule', '', get_string('schedule:relativedate', 'pulse'), 1);
    $mform->addGroup($radioarray, 'first_schedule_arr', get_string('reminderschedule', 'pulse'), array(' '), false);
    $mform->addHelpButton('first_schedule_arr', 'reminderschedule', 'mod_pulse');

    $mform->addElement('date_time_selector', 'first_fixeddate', '');
    $mform->hideIf('first_fixeddate', 'first_schedule', 'neq', '0');

    $mform->addElement('duration', 'first_relativedate', '');
    $mform->hideIf('first_relativedate', 'first_schedule', 'neq', '1');

    // Second reminder.
    $mform->addElement('header', 'second_reminders', get_string('head:secondreminder', 'mod_pulse') );

    // Enable / disable second reminder.
    $mform->addElement('checkbox', 'second_reminder', get_string('enablereminder:second', 'pulse'),
        get_string('enable:disable', 'pulse'));
    $mform->addHelpButton('second_reminder', 'enablereminder:second', 'mod_pulse');

    // Second reminder subject.
    $mform->addElement('text', 'second_subject', get_string('remindersubject', 'pulse'), array('size' => '64'));
    $mform->setType('second_subject', PARAM_RAW);
    $mform->addHelpButton('second_subject', 'remindersubject', 'mod_pulse');

    $mform->addElement('editor', 'second_content_editor', get_string('remindercontent', 'pulse'),
    ['class' => 'fitem_id_templatevars_editor'], $editoroptions);
    $mform->setType('second_content_editor', PARAM_RAW);
    $instance->pulse_email_placeholders($mform);
    $mform->addHelpButton('second_content_editor', 'remindercontent', 'mod_pulse');

    $select = $mform->addElement('autocomplete', 'second_recipients',
                get_string('recipients', 'pulse'), $roles);
    $select->setMultiple(true);
    $mform->addHelpButton('second_recipients', 'recipients', 'mod_pulse');

    $group = array();
    $radioarray = array();
    $radioarray[] = $mform->createElement('radio', 'second_schedule', '', get_string('schedule:fixeddate', 'pulse'), 0);
    $radioarray[] = $mform->createElement('radio', 'second_schedule', '', get_string('schedule:relativedate', 'pulse'), 1);
    $mform->addGroup($radioarray, 'second_schedule_arr', get_string('reminderschedule', 'pulse'), array(' '), false);
    $mform->addHelpButton('second_schedule_arr', 'reminderschedule', 'mod_pulse');

    $mform->addElement('date_time_selector', 'second_fixeddate', '');
    $mform->hideIf('second_fixeddate', 'second_schedule', 'neq', '0');

    $mform->addElement('duration', 'second_relativedate', '');
    $mform->hideIf('second_relativedate', 'second_schedule', 'neq', '1');

    // Recurring reminder.
    $mform->addElement('header', 'recurring_reminders', get_string('head:recurringreminder', 'mod_pulse'));

    // Enable / disable recurring reminder.
    $mform->addElement('checkbox', 'recurring_reminder', get_string('enablereminder:recurring', 'pulse'),
        get_string('enable:disable', 'pulse'));
    $mform->addHelpButton('recurring_reminder', 'enablereminder:recurring', 'mod_pulse');
    // Recurring reminder subject.
    $mform->addElement('text', 'recurring_subject', get_string('remindersubject', 'pulse'), array('size' => '64'));
    $mform->setType('recurring_subject', PARAM_RAW);
    $mform->addHelpButton('recurring_subject', 'remindersubject', 'mod_pulse');
    // Recurring reminder content.
    $mform->addElement('editor', 'recurring_content_editor', get_string('remindercontent', 'pulse'),
    ['class' => 'fitem_id_templatevars_editor'], $editoroptions);
    $mform->setType('recurring_content_editor', PARAM_RAW);
    $instance->pulse_email_placeholders($mform);
    $mform->addHelpButton('recurring_content_editor', 'remindercontent', 'mod_pulse');

    // Recurring recipients.
    $select = $mform->addElement('autocomplete', 'recurring_recipients',
                get_string('recipients', 'pulse'), $roles);
    $select->setMultiple(true);
    $mform->addHelpButton('recurring_recipients', 'recipients', 'mod_pulse');
    // Recurring Relative Date.
    $mform->addElement('duration', 'recurring_relativedate', get_string('reminderschedule', 'pulse'));
    $mform->addHelpButton('recurring_relativedate', 'reminderschedule', 'mod_pulse');

    $mform->addElement('hidden', 'pulsepro_extended');
    $mform->setType('pulsepro_extended', PARAM_INT);
    $mform->setDefault('pulsepro_extended', 1);

    $PAGE->requires->js_call_amd('local_pulsepro/pulsepro', 'init');
}

/**
 * Pulse module form post processing observer.
 * When the pulse module form processed to display, this will called and updates the reminders content names.
 *
 * @param  stdclass $data Form submitted data.
 * @return void
 */
function local_pulsepro_extend_pulse_postprocessing(&$data) {
    global $DB;
    $notifications = ['first', 'second', 'recurring'];
    foreach ($notifications as $reminder) {
        $var = $reminder.'_content_editor';
        if (isset($data->$var)) {
            $editorcontent = $data->{$var};
            $data->{$reminder.'_contentformat'} = $editorcontent['format'];
            $data->{$reminder.'_content'} = $editorcontent['text'];
        }
        $data->{$reminder.'_recipients'} = implode(',', $data->{$reminder.'_recipients'});
    }
    $data->invitation_recipients = implode(',', $data->invitation_recipients);
}


/**
 * Processing the form data after the form submits before update/add data to DB.
 * Updates the reminder contents file itemids and editor contents.
 *
 * @param  array $defaultvalues Default form field valuse.
 * @param  bool $currentinstance Is current add instance or existing instance.
 * @param  stdclass $context
 * @return void
 */
function local_pulsepro_extend_pulse_preprocessing(&$defaultvalues, $currentinstance, $context) {
    global $DB;
    $notifications = ['first', 'second', 'recurring'];
    $editoroptions = null;//pulse_get_editor_options();
    if (!isset($defaultvalues['id']) || $defaultvalues['id'] == null) {
        return '';
    }
    $prodata = $DB->get_record('local_pulsepro', array('pulseid' => $defaultvalues['id']));
    if (!empty($prodata) && !empty($prodata->id)) {
        foreach ($notifications as $reminder) {
            if ($currentinstance) {
                // Prepare draft item id to store the files.
                $draftitemid = file_get_submitted_draft_itemid($reminder.'_content');
                $defaultvalues[$reminder.'_content_editor']['text'] =
                                        file_prepare_draft_area($draftitemid, $context->id,
                                        'mod_pulse', $reminder.'_content', false,
                                        $editoroptions,
                                        $prodata->{$reminder.'_content'});

                $defaultvalues[$reminder.'_content_editor']['format'] = $prodata->{$reminder.'_contentformat'};
                $defaultvalues[$reminder.'_content_editor']['itemid'] = $draftitemid;
            } else {
                $draftitemid = file_get_submitted_draft_itemid($reminder.'_content_editor');
                file_prepare_draft_area($draftitemid, null, 'mod_pulse', $reminder.'_content', false);
                $defaultvalues[$reminder.'_content_editor']['format'] = editors_get_preferred_format();
                $defaultvalues[$reminder.'_content_editor']['itemid'] = $draftitemid;
            }
            $defaultvalues[$reminder.'_recipients'] = explode(',', $prodata->{$reminder.'_recipients'});
        }
        $defaultvalues['invitation_recipients'] = explode(',', $prodata->invitation_recipients);
        $defaultvalues = array_merge($defaultvalues, (array) $prodata);
    }
}

/**
 * Pulse instance added extended pro method. Here the pro features configurations are processes and
 * stored in pulsepro table.
 *
 * @param  int $pulseid Pulse instance id
 * @param  stdclass $pulse Module form submitted data
 * @return void
 */
function local_pulsepro_extend_pulse_add_instance($pulseid, $pulse) {
    global $DB;

    if (!empty($pulse) && !empty($pulseid) && isset($pulse->pulsepro_extended)) {
        $record = new stdclass();
        $record->pulseid = $pulseid;
        $record->reactiontype = isset($pulse->reactiontype) ? $pulse->reactiontype : '';
        $record->reactiondisplay = isset($pulse->reactiondisplay) ? $pulse->reactiondisplay : '';
        $notifications = ['first', 'second', 'recurring'];
        foreach ($notifications as $reminder) {
            $record->{$reminder.'_reminder'} = isset($pulse->{$reminder.'_reminder'}) ? $pulse->{$reminder.'_reminder'} : 0;
            $record->{$reminder.'_content'} = $pulse->{$reminder.'_content'};
            $record->{$reminder.'_contentformat'} = $pulse->{$reminder.'_contentformat'};
            $record->{$reminder.'_subject'} = isset($pulse->{$reminder.'_subject'}) ? $pulse->{$reminder.'_subject'} : '';
            $record->{$reminder.'_recipients'} = $pulse->{$reminder.'_recipients'};
            if ($reminder != 'recurring') {
                $record->{$reminder.'_schedule'} = $pulse->{$reminder.'_schedule'};
                $record->{$reminder.'_fixeddate'} = $pulse->{$reminder.'_fixeddate'};
            }
            $record->{$reminder.'_relativedate'} = $pulse->{$reminder.'_relativedate'};
        }
        $record->invitation_recipients = isset($pulse->invitation_recipients) ? $pulse->invitation_recipients : '';
        $record->credits = isset($pulse->credits) ? $pulse->credits : '';
        $record->credits_status = isset($pulse->credits_status) ? $pulse->credits_status : 0;
        $id = $DB->insert_record('local_pulsepro', $record);

        $completiontimeexpected = !empty($pulse->completionexpected) ? $pulse->completionexpected : null;
        \core_completion\api::update_completion_date_event($pulse->coursemodule, 'pulse', $pulseid, $completiontimeexpected);
    }
}

/**
 * Update pro notification and reaction config when the instance updated.
 *
 * @param  stdclass $pulse Pulse instance data.
 * @param  context_module $context Module context.
 * @return void
 */
function local_pulsepro_extend_pulse_update_instance($pulse, $context) {
    global $DB;

    $record = new stdclass();
    $record->reactiontype = isset($pulse->reactiontype) ? $pulse->reactiontype : '';
    $record->reactiondisplay = isset($pulse->reactiondisplay) ? $pulse->reactiondisplay : '';
    $record->credits = isset($pulse->credits) ? $pulse->credits : '';
    $record->credits_status = isset($pulse->credits_status) ? $pulse->credits_status : 0;
    $notifications = ['first', 'second', 'recurring'];
    foreach ($notifications as $reminder) {
        $record->{$reminder.'_reminder'} = isset($pulse->{$reminder.'_reminder'}) ? $pulse->{$reminder.'_reminder'} : 0;
        $record->{$reminder.'_contentformat'} = $pulse->{$reminder.'_contentformat'};
        $record->{$reminder.'_subject'} = isset($pulse->{$reminder.'_subject'}) ? $pulse->{$reminder.'_subject'} : '';
        $record->{$reminder.'_recipients'} = $pulse->{$reminder.'_recipients'};
        if ($reminder != 'recurring') {
            $record->{$reminder.'_schedule'} = $pulse->{$reminder.'_schedule'};
            $record->{$reminder.'_fixeddate'} = $pulse->{$reminder.'_fixeddate'};
        }
        $record->{$reminder.'_relativedate'} = $pulse->{$reminder.'_relativedate'};

        if (isset($pulse->{$reminder.'_content'})) {
            // Save pulse content areafiles.
            $record->{$reminder.'_content'} = file_save_draft_area_files($pulse->{$reminder.'_content_editor'}['itemid'],
                                                        $context->id, 'mod_pulse', $reminder.'_content', 0,
                                                        array('subdirs' => true), $pulse->{$reminder.'_content_editor'}['text']);
        }
    }
    if (isset($pulse->resend_pulse) && $pulse->resend_pulse) {
        local_pulsepro_reset_invitation($pulse->id);
    }
    $record->invitation_recipients = isset($pulse->invitation_recipients) ? $pulse->invitation_recipients : '';

    $prodata = $DB->get_record('local_pulsepro', ['pulseid' => $pulse->id]);
    if (!empty($prodata) && !empty($prodata->id)) {
        $record->id = $prodata->id;
        // Reset previous user reactions if reaction type was changed.
        if ($prodata->reactiontype != $record->reactiontype) {
            $DB->set_field('local_pulsepro_tokens', 'status', '0', ['pulseid' => $prodata->id]);
        }
        $DB->update_record('local_pulsepro', $record);
    } else {
        $record->pulseid = $pulse->id;
        $DB->insert_record('local_pulsepro', $record);
    }

    $completiontimeexpected = !empty($pulse->completionexpected) ? $pulse->completionexpected : null;
    \core_completion\api::update_completion_date_event($pulse->coursemodule, 'pulse', $pulse->id, $completiontimeexpected);
}

/**
 * Observe the pulse module deleted event.
 * Remove the pulsepro data related to the pulse instance when the pulse module deleted.
 *
 * @param  int $cmid course moudle id.
 * @param  int $pulseid Pulse instance id
 * @return void
 */
function local_pulsepro_delete_instance(int $cmid, int $pulseid) {
    global $DB;

    \core_completion\api::update_completion_date_event($cmid, 'pulse', $pulseid, null);

    if ($DB->record_exists('local_pulsepro', ['pulseid' => $pulseid])) {
        $DB->delete_records('local_pulsepro', ['pulseid' => $pulseid]);
        local_pulsepro_remove_userdata($pulseid);
    }
}


/**
 * Reset the invitation users list for the pulse instance when the resent invitation triggered.
 * Then the already notified users are deleted form record.
 * So the pulse will send the invitations to users again
 *
 * @param  int $pulseid pulse instance id
 * @return void
 */
function local_pulsepro_reset_invitation($pulseid) {
    global $DB;
    $DB->set_field('local_pulsepro_availability', 'invitation_users', null, ['pulseid' => $pulseid]);
}

/**
 * Remove all user's data related to the pulse instance. Function triggered when the pulse instance is deleted.
 * Removes the user's availability data related to the instance and created tokens for reactions.
 *
 * @param  int $pulseid pulse instance id
 * @return void
 */
function local_pulsepro_remove_userdata($pulseid) {
    global $DB;
    // Remove pulse availability records.
    if ($DB->record_exists('local_pulsepro_availability', ['pulseid' => $pulseid])) {
        $DB->delete_records('local_pulsepro_availability', ['pulseid' => $pulseid]);
    }

    // Remove pulse tokens completion records.
    if ($DB->record_exists('local_pulsepro_tokens', ['pulseid' => $pulseid])) {
        $DB->delete_records('local_pulsepro_tokens', ['pulseid' => $pulseid]);
    }
}
/**
 * Extend the pulse reaction - to send the reaction via email. Also it will generate the content to display the
 * reactions on course content page.
 *
 * @param  object $instance Email vars Class object.
 * @param  string $type reaction location type (notification, content).
 * @return string|null $content Token content.
 */
function local_pulsepro_extend_pulse_reaction($instance, $type=true) {
    global $DB, $OUTPUT;

    $pulsecontent = $DB->get_record('local_pulsepro', ['pulseid' => $instance->pulse->id]);
    if (empty($pulsecontent) || $pulsecontent->reactiontype == 0) {
        return '';
    }
    $pulseid = $instance->pulse->id;
    $reactiontype = $pulsecontent->reactiontype;
    $userid = $instance->user->id;
    $approveuser = isset($instance->user->approveuser) ? $instance->user->approveuser : $userid;
    $relateduserid = null;
    $cm = get_coursemodule_from_instance('pulse', $pulseid);

    if (($type == 'content' && $pulsecontent->reactiondisplay == 0)
        || ($type == 'notification' && $pulsecontent->reactiondisplay == 2) || ($type == 'content' && $reactiontype == 3) ) {
        return '';
    }
    // Check the reaction display type is content only or both.
    // If generate token called from content side.
    // if already token generated just reuse the tokens.
    $params = ['pulseid' => $pulseid, 'userid' => $userid, 'reactiontype' => $reactiontype];
    if ($pulsecontent->reactiontype == REACTION_APPROVAL) {
        // Each user has received their own notification with approval token.
        $params['userid'] = $approveuser;
        $params['relateduserid'] = $userid;
        $relateduserid = $userid;
        $userid = $approveuser;
    }

    $token = local_pulsepro_gettoken($params);

    if (!isset($token) || $token == '') {
        $token = local_pulsepro_generate_token($pulseid, $userid, $reactiontype, $relateduserid);
    }
    $reactionurl = new moodle_url('/local/pulsepro/reaction.php', ['token' => $token]);
    $reactionurl = $reactionurl->out();
    switch ($pulsecontent->reactiontype) {
        case 0: // No reaction.
            $content = '';
            break;
        case 1: // Mark Complete.
            $content = get_string('reaction:markcomplete', 'mod_pulse', ['reactionurl' => $reactionurl]);
            break;
        case 2: // Rate.
            $data['reactionurl_like'] = new moodle_url('/local/pulsepro/reaction.php', ['token' => $token, 'rate' => 2]);
            $data['reactionurl_dislike'] = new moodle_url('/local/pulsepro/reaction.php', ['token' => $token, 'rate' => 1]);
            $content = $OUTPUT->render_from_template('local_pulsepro/ratereaction', $data);
            break;
        case 3: // Approve.
            if (pulse_has_approvalrole($instance->pulse->completionapprovalroles, $cm->id, true, $approveuser)) {
                $content = get_string('reaction:approve', 'mod_pulse', ['reactionurl' => $reactionurl]);
            } else {
                $content = '';
            }
            break;
    }
    return $content;
}

/**
 * Get previously generated token if token doesn't expired.
 *
 * @param array $params
 * @return void
 */
function local_pulsepro_gettoken(array $params) {
    global $DB;
    $token = '';
    $record  = $DB->get_record('local_pulsepro_tokens', $params);
    if ($record) {
        $token = $record->token;
        $timeexpire = get_config('local_pulsepro', 'expiretime'); // Time expiration in seconds.
        if ($timeexpire != 0 && (time() - $record->timecreated) >= $timeexpire) {
            $token = '';
        }
    }
    return $token;
}

/**
 * Generate token for the reaction.
 * Tokend generated by moodle default token generation method. hashed using md5.
 *
 * @param  int $pulseid
 * @param  int $userid
 * @param  int $reactiontype Selected reaction type.
 * @param  int $relateduserid If reation for approval than the student id is related userid.
 * @return string Return generated token.
 */
function local_pulsepro_generate_token($pulseid, $userid, $reactiontype, $relateduserid=null) {
    global $DB, $USER;
    // Make sure the token doesn't exist (even if it should be almost impossible with the random generation).
    $numtries = 0;
    do {
        $numtries ++;
        $generatedtoken = md5( uniqid(rand(), 1) );
        if ($numtries > 5) {
            throw new moodle_exception('tokengenerationfailed');
        }
    } while ($DB->record_exists('local_pulsepro_tokens', array('token' => $generatedtoken)));
    $newtoken = new stdClass();
    $newtoken->token = $generatedtoken;
    $newtoken->pulseid = $pulseid;
    $newtoken->userid = $userid;
    $newtoken->relateduserid = $relateduserid;
    $newtoken->reactiontype = $reactiontype;
    $newtoken->status = 0;
    $newtoken->timemodified = time();
    $newtoken->timecreated = time();
    $params = ['pulseid' => $pulseid, 'userid' => $userid, 'reactiontype' => $reactiontype];
    if ($reactiontype == REACTION_APPROVAL) {
        $params['relateduserid'] = $relateduserid;
    }
    if ($record = $DB->get_record('local_pulsepro_tokens', $params) ) {
        $newtoken->id = $record->id;
        $DB->update_record('local_pulsepro_tokens', $newtoken);
        return $generatedtoken;
    } else {
        if ($DB->insert_record('local_pulsepro_tokens', $newtoken)) {
            return $generatedtoken;
        }
    }
    return '';
}

/**
 * Extend the pulse send invitation cron task and send the invitations based on the pro options.
 *
 * @return bool true => prevents the pulse cron task to send invitations.
 */
function local_pulsepro_extend_pulse_invitation() {
    $notification = new \local_pulsepro\notification("nt.pulse=:enabled AND pp.invitation_recipients != ''", ['enabled' => 1]);
    $notification->send_invitations();
    return true;
}

/**
 * Get the list of Course and User context roles with data for the role ids.
 *
 * @param  array $roles list of roles.
 * @return array $roledata List of roles with data.
 */
function local_pulsepro_get_roles($roles) {
    global $DB;
    list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);

    $sql = "SELECT rl.*, rcl.contextlevel FROM {role} rl
    JOIN {role_context_levels} rcl ON rcl.roleid = rl.id
    WHERE contextlevel IN (".CONTEXT_USER.",". CONTEXT_COURSE.") AND rcl.roleid $roleinsql ";
    $roledata = $DB->get_records_sql($sql, $roleinparams);
    return $roledata;
}

/**
 * Get list of enrolled users with the given roles from the course.
 *
 * @param  array $roles Get users only contain this roles.
 * @param  int $courseid Course id.
 * @param  int $contextid Course context id.
 * @return array $records Returns list of users id.
 */
function local_pulsepro_get_users($roles, $courseid, $contextid) {
    global $DB;
    list($roleinsql, $roleinparams) = $DB->get_in_or_equal(array_keys($roles));

    $usersql = "SELECT DISTINCT eu1_u.id, ra.*
        FROM {user} eu1_u
        JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
        JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
        JOIN (SELECT DISTINCT userid, rle.shortname as roleshortname, roleid
                FROM {role_assignments}
                JOIN {role} rle ON rle.id = roleid
                WHERE contextid = ? AND roleid $roleinsql GROUP BY userid, rle.shortname, roleid
            ) ra ON ra.userid = eu1_u.id
        WHERE 1 = 1 AND ej1_ue.status = 0
        AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= ?)
        AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > ?)
        AND eu1_u.deleted = 0 AND eu1_u.suspended = 0";

    array_unshift($roleinparams, $contextid);
    array_unshift($roleinparams, $courseid);
    $roleinparams[] = time();
    $roleinparams[] = time();
    $records = $DB->get_records_sql($usersql, $roleinparams);

    return array_keys($records);
}

/**
 * Add the pulsepro backup structures and steps during the backup of mod_pulse.
 *
 * @param  stdclass $pulse
 * @param  stdclass $userinfo
 * @return array List of steps for backup.
 */
function local_pulsepro_extend_pulse_backup_steps($pulse, $userinfo) {
    return \local_pulsepro\backup\backup_steps::define_structure($pulse, $userinfo);
}

/**
 * Observe the restore event and add the pro contents to restore.
 *
 * @param  array $contents
 * @return array Updated restore contents.
 */
function local_pulsepro_extend_pulse_restore_content($contents) {
    return \local_pulsepro\backup\restore_steps::restore_contents($contents);
}

/**
 * Observer the pulse restore structure definition and define the pulse pro table and data structures
 *
 * @param  array $paths Restoreable XMLDB paths.
 * @return array structure definition paths.
 */
function local_pulsepro_extend_pulse_restore_structure($paths) {
    return \local_pulsepro\backup\restore_steps::restore_structure($paths);
}


/**
 * Returns list of fileareas used in the pulsepro reminder contents.
 *
 * @return array list of filearea to support pluginfile.
 */
function local_pulsepro_extend_pulse_filearea() : array {
    return ['first_content', 'second_content', 'recurring_content', 'preset_template', 'description', 'instruction'];
}

/**
 * Extended the Pulse module add/update form validation method.
 *
 * @param  array $data Module object data.
 * @param  array $files Files from module form.
 * @return array $errors List of errors.
 */
function local_pulsepro_extend_pulse_validation($data, $files) {
    $reminders = array('first', 'second', 'recurring');
    $errors = [];
    if ($data['pulse'] && empty($data['invitation_recipients'])) {
        $errors['invitation_recipients'] = get_string('required');
    }
    foreach ($reminders as $step) {
        $reminder = $step.'_reminder';
        if (isset($data[$reminder]) && $data[$reminder]) {
            if (empty($data[$step.'_subject'])) {
                $errors[$step.'_subject'] = get_string('required');
            }
            if (empty($data[$step.'_content_editor']['text'])) {
                $errors[$step.'_content_editor'] = get_string('required');
            }
            if (empty($data[$step.'_recipients'])) {
                $errors[$step.'_recipients'] = get_string('required');
            }

            if ($step != 'recurring') {
                if ($data[$step.'_schedule'] == 1 && !$data[$step.'_relativedate']) {
                    $errors[$step.'_relativedate'] = get_string('required');
                }
                if ($data[$step.'_schedule'] == 0 && !$data[$step.'_fixeddate']) {
                    $errors[$step.'_fixeddate'] = get_string('required');
                }
            } else {
                if (!$data[$step.'_relativedate']) {
                    $errors[$step.'_relativedate'] = get_string('required');
                }
            }
        }
    }
    if (isset($data['credits_status']) && $data['credits_status']) {
        if (empty($data['credits'])) {
            $errors['creditgroup'] = get_string('required');
        } else if (!is_numeric($data['credits'])) {
            $errors['creditgroup'] = get_string('numberic');
        }
    }
    return $errors;
}

/**
 * File manager options for preset activity backup file.
 *
 * @return array
 */
function local_pulsepro_preset_fileoptions(): array {
    return [
        'subdirs'        => 0,
        'maxfiles'       => 1,
        'accepted_types' => ['.mbz'],
        'return_types'   => FILE_INTERNAL | FILE_EXTERNAL
    ];
}

/**
 * Extend the pulse module apply preset method to format the data.
 *
 * @param string $method Name of method to trigger..
 * @param array $backupdata Preset restore data.
 * @return array $backupdata Updated preset template restore data.
 */
function local_pulsepro_extend_preset_formatdata(string $method, $backupdata) {
    if ($method == 'cleandata') {
        return \local_pulsepro\presets\preset_form::formatdata($backupdata);
    }
    return $backupdata;
}

/**
 * Triggered after the pulse created using presets save method. It helps to updated the user custom data to pulse pro table.
 *
 * @param int $pulseid ID of create pulse module.
 * @param array $configparams User gived custom module config data.
 * @return void
 */
function local_pulsepro_extend_preset_update($pulseid, $configparams) {
    \local_pulsepro\presets\preset_form::update_preset_config_params($pulseid, $configparams);
}

/**
 * Craete example presets for demo during the installtation.
 *
 * @return array List of created preset ID.
 */
function pulsepro_create_presets() {
    $presets = pulsepro_demo_presets();
    if (!empty($presets)) {
        return pulse_create_presets($presets, true);
    }
    return array();
}

/**
 * Demo presets data to create during the plugin install or upgrade.
 *
 * @return array
 */
function pulsepro_demo_presets() {
    global $CFG;

    if (file_exists($CFG->dirroot.'/local/pulsepro/assets/presets.xml')) {
        $presetsxml = simplexml_load_file($CFG->dirroot.'/local/pulsepro/assets/presets.xml');
        $result = json_decode(json_encode($presetsxml), true);
        return (!empty($result)) ? $result : array();
    }
    return array();
}
