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
 * Pulse instance test cases defined. Check the credit system works fine.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

/**
 * Pulse resource credits phpunit test cases defined.
 */
class local_pulsepro_credits_testcase extends advanced_testcase {

    /**
     * Setup the course and admin user to test the presets.
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;

        require_once($CFG->dirroot.'/local/pulsepro/lib.php');
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->coursecontext = \context_course::instance($this->course->id);
        $this->generator = $this->getDataGenerator()->get_plugin_generator('local_pulsepro');
        $this->defaultdata = $this->generator->default_data($this->course->id);
    }

    /**
     * Create and set the profile field for the credits.
     *
     * @return void
     */
    public function create_and_setfield(): void {
        $this->customprofileid = $this->generator->create_custom_profile_field(array(
            'shortname' => 'credits', 'name' => 'Credits', 'datatype' => 'text'))->id;
        set_config('creditsfield', $this->customprofileid, 'local_pulsepro');
    }

    /**
     * Create pulse module with pro features.
     *
     * @param  mixed $options Module configs specified for test.
     * @return void
     */
    public function create_pulse_module($options=[]) {
        $data = array_merge($this->defaultdata, $options);
        $this->module = $this->getDataGenerator()->create_module('pulse', $data);
        $this->cm = get_coursemodule_from_instance('pulse', $this->module->id);
        $this->modulecontext = context_module::instance($this->cm->id);
    }

    /**
     * Test user profile field matched with selected field.
     *
     * @return void
     */
    public function test_userprofile_creditfield(): void {
        $creditfields = \local_pulsepro\credits::creditsfield();
        $this->assertEmpty($creditfields);
        $this->create_and_setfield();
        $creditfields = \local_pulsepro\credits::creditsfield();
        $this->assertEquals('profile_field_credits', $creditfields['shortname']);
        $this->assertEquals($this->customprofileid, $creditfields['id']);
    }

    /**
     * Test credits are updated for user enrollments.
     *
     * @return void
     */
    public function test_credits_userenrolment(): void {
        global $DB;
        $this->create_pulse_module(['credits' => 100, 'credits_status' => 1]);
        $this->create_and_setfield();
        $user = $this->getDataGenerator()->create_user([
            'email' => 'student1@test.com', 'username' => 'student1'
        ]);
        profile_load_data($user);
        $this->assertEmpty($user->profile_field_credits);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
        profile_load_data($user);
        $this->assertEquals($user->profile_field_credits, 100);
        // Module created event test.
        $this->create_pulse_module(['credits' => 50, 'credits_status' => 1, 'name' => 'Pulse credit 50']);
        $credits = new \local_pulsepro\credits();
        $credits->prepare_adhoctask();
        phpunit_util::run_all_adhoc_tasks();
        profile_load_data($user);
        $this->assertEquals(150, $user->profile_field_credits);
        // Enrol in another course.
        $this->course = $this->getDataGenerator()->create_course();
        $this->create_pulse_module(['course' => $this->course->id, 'credits' => -200, 'credits_status' => 1]);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
        profile_load_data($user);
        $this->assertEquals(-50, $user->profile_field_credits);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function test_credits_moduleupdated(): void {
        global $DB;
        $this->create_pulse_module(['credits' => 50, 'credits_status' => 1]);
        $this->create_and_setfield();
        $user = $this->getDataGenerator()->create_user([
            'email' => 'student2@test.com', 'username' => 'student2'
        ]);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
        profile_load_data($user);
        $this->assertEquals(50, $user->profile_field_credits);
        $mod = (array) $DB->get_record('pulse', ['id' => $this->module->id]);
        $data = $DB->set_field('local_pulsepro', 'credits', 75, ['pulseid' => $this->module->id]);
        $credits = new \local_pulsepro\credits();
        $credits->prepare_adhoctask();
        phpunit_util::run_all_adhoc_tasks();
        profile_load_data($user);
        $this->assertEquals(75, $user->profile_field_credits);
    }
}
