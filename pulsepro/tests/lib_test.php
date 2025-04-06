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
 * Pulse instance test cases defined.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL') || die(' No direct access ');



/**
 * Pulse resource phpunit test cases defined.
 */
class local_pulsepro_lib_testcase extends advanced_testcase {

    /**
     * Module intro content.
     *
     * @var string
     */
    public $intro = 'Pulse test notification';

    /**
     * Setup testing cases.
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;

        $this->resetAfterTest();
        // Remove the output display of cron task.
        $CFG->mtrace_wrapper = 'mod_pulse_remove_mtrace_output';
        $this->course = $this->getDataGenerator()->create_course();
        $this->coursecontext = context_course::instance($this->course->id);
    }

    /**
     * Create pulse module with pro features.
     *
     * @param  mixed $options Module configs specified for test.
     * @return void
     */
    public function create_pulse_module($options=[]) {
        $default = $this->default_data();
        $data = array_merge($default, $options);
        $this->module = $this->getDataGenerator()->create_module('pulse', $data);
        $this->cm = get_coursemodule_from_instance('pulse', $this->module->id);
    }

    /**
     * Send messages.
     *
     * @return void
     */
    public function send_message() {
        $this->preventResetByRollback();
        $slink = $this->redirectMessages();
        // Setup adhoc task to send notifications.
        mod_pulse_cron_task(true);
        // Check adhock task count.
        $tasklist = core\task\manager::get_adhoc_tasks('local_pulsepro\task\sendreminders');
        // ...cron_run_adhoc_tasks(time());.
        // Run all adhoc task to send notification.
        phpunit_util::run_all_adhoc_tasks();
        $messages = $slink->get_messages();
        return ['tasklist' => $tasklist, 'messages' => $messages];
    }

    /**
     * Test create instance of pulse module creates the pulsepro features.
     *
     * @return void
     */
    public function test_create_instance() {
        global $DB;
        $this->create_pulse_module();
        $result = (object) $DB->get_record('local_pulsepro', ['pulseid' => $this->module->id]);
        $this->assertEquals('First reminder content', $result->first_content);
        $this->assertEquals('Second reminder content', $result->second_content);
        $this->assertEquals('Recurring reminder content', $result->recurring_content);
    }

    /**
     * Test reaction variables are updated.
     *
     * @return void
     */
    public function test_reaction_vars() {
        global $DB;
        $options = ['reactiontype' => 1];
        $this->create_pulse_module($options);
        $result = (object) $DB->get_record('local_pulsepro', ['pulseid' => $this->module->id]);
        // Enrol users.
        $user = $this->getDataGenerator()->create_user(['email' => 'testuser1@test.com', 'username' => 'testuser1']);
        $sender = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher', [
            'email' => 'sender1@test.com', 'username' => 'sender1'
        ]);
        $template = "{reaction}";
        $subject = '';
        list($subject, $template) = mod_pulse_update_emailvars($template, $subject, $this->course, $user, $this->module, $sender);
        $token = $DB->get_field('local_pulsepro_tokens', 'token', ['pulseid' => $this->module->id, 'userid' => $user->id]);
        $reactionurl = new moodle_url('/local/pulsepro/reaction.php', ['token' => $token]);
        $reactionurl = $reactionurl->out();
        $actualcontent = get_string('reaction:markcomplete', 'mod_pulse', ['reactionurl' => $reactionurl]);
        $this->assertEquals($actualcontent, $template);
    }

    /**
     * Test course users are fetched.
     *
     * @return void
     */
    public function test_get_course_users() {
        global $DB;
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->create_pulse_module();
        $user1 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student1@test.com', 'username' => 'student1'
        ]);
        $user2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student2@test.com', 'username' => 'student2'
        ]);
        $user3 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student3@test.com', 'username' => 'student3'
        ]);
        $teacher1 = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher', [
            'email' => 'sender1@test.com', 'username' => 'sender1'
        ]);

        $userslist = local_pulsepro_get_users([$studentroleid => 'student'], $this->course->id, $this->coursecontext->id);
        $this->assertCount(3, $userslist);
    }

    /**
     *
     * Test delete instance are removed the pro datas related to the users.
     *
     * @return void
     */
    public function test_delete_instance() {
        global $DB, $CFG;

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $options = ['reactiontype' => 1, 'invitation_recipients' => $studentroleid, 'intro' => '{reaction}'];
        $this->create_pulse_module($options);
        $user1 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student1@test.com', 'username' => 'student1'
        ]);
        $user2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student2@test.com', 'username' => 'student2'
        ]);
        $user2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student', [
            'email' => 'student3@test.com', 'username' => 'student3'
        ]);
        $messages = $this->send_message();

        $pro = $DB->count_records('local_pulsepro', ['pulseid' => $this->module->id]);
        $this->assertEquals(1, $pro);

        $availability = $DB->count_records('local_pulsepro_availability', ['pulseid' => $this->module->id]);
        $this->assertEquals(3, $availability);

        $tokenscount = $DB->count_records('local_pulsepro_tokens', ['pulseid' => $this->module->id]);
        $this->assertEquals(3, $tokenscount);
        // Delete instance.
        course_delete_module($this->module->cmid);

        phpunit_util::run_all_adhoc_tasks();

        $this->assertCount(0, $DB->get_records('local_pulsepro_tokens', ['pulseid' => $this->module->id]));
        $this->assertCount(0, $DB->get_records('local_pulsepro_availability', ['pulseid' => $this->module->id]));
        $this->assertCount(0, $DB->get_records('local_pulsepro', ['pulseid' => $this->module->id]));
    }

    /**
     * Test the get instance list are returned corrent instance data.
     *
     * @return void
     */
    public function test_instancelist() {
        $this->create_pulse_module(['name' => 'First pulse pro']);
        $this->create_pulse_module();

        $list = local_pulsepro_course_instancelist($this->course->id);
        $first = reset($list);
        $this->assertCount(2, $list);
        $this->assertEquals('First pulse pro', $first->name);
    }

    /**
     * Default pulse pro options.
     *
     * @return void
     */
    public function default_data() {
        $options = [
            'course' => $this->course->id,
            'intro' => $this->intro,
            "invitation_recipients" => '',
            "reactiontype" => 0,
            "reactiondisplay" => 1,
            "first_reminder" => 0,
            "first_subject" => "First pulse pro reminder",
            "first_content" => "First reminder content",
            "first_contentformat" => '1',
            "first_recipients" => 9,
            "first_schedule" => 1,
            "first_fixeddate" => 1628230740,
            "first_relativedate" => 600,
            "second_reminder" => 0,
            "second_subject" => "Second pulse pro reminder",
            "second_content" => "Second reminder content",
            "second_contentformat" => '1',
            "second_recipients" => '',
            "second_schedule" => 0,
            "second_fixeddate" => 1626253980,
            "second_relativedate" => 0,
            "recurring_reminder" => 0,
            "recurring_subject" => "Recurring pulse pro reminder",
            "recurring_content" => "Recurring reminder content",
            "recurring_contentformat" => '1',
            "recurring_recipients" => '5',
            "recurring_relativedate" => 45,
            "pulsepro_extended" => 1
        ];
        return $options;
    }
}
