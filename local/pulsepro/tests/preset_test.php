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
class local_pulsepro_preset_testcase extends advanced_testcase {

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
    }

    /**
     * Test pulsepro create preset insert the pro presets.
     *
     * @return void
     */
    public function test_create_demo_preset(): void {
        global $DB;
        pulsepro_create_presets();
        $records = $DB->get_records('pulse_presets');
        $this->assertCount(2, $records);
        $this->assertEquals('Demo pro preset 1', reset($records)->title);
    }

    /**
     * Test is all the pulsepro fields are fetched for custom configurable fields.
     *
     * @return void
     */
    public function test_get_config_list(): void {
        $presetform = new local_pulsepro\presets\preset_form();
        $fields = local_pulsepro\presets\preset_form::get_pulse_config_list();
        $profields = $presetform->pulsepro_fields();
        $exists = !array_diff($profields, array_keys($fields));
        $this->assertTrue($exists);
    }

    /**
     * Test apply and save method updates the pulse pro fields custom config options.
     *
     * @return void
     */
    public function test_apply_save_preset(): void {
        global $DB;
        $this->generator->create_presets();
        $records = $DB->get_records('pulse_presets');
        $record = reset($records);

        $preset = new mod_pulse\preset($record->id, $this->course->id, $this->coursecontext);
        $configdata = ['importmethod' => 'save', 'presetid' => $record->id];
        $prodata = [
            'reactiontype' => '2', 'reactiondisplay' => '1',
            'first_content' => 'First reminder - test case content',
            'second_content' => 'Second reminder - test case content',
        ];
        $result = $preset->apply_presets($configdata + $prodata);
        $result = json_decode($result);
        $courseurl = new \moodle_url('/course/view.php', ['id' => $this->course->id]);
        $this->assertEquals($courseurl, $result->url);

        $cm = $DB->get_record('local_pulsepro', ['pulseid' => $result->pulseid]);
        foreach ($prodata as $key => $data) {
            $this->assertEquals($data, $cm->$key);
        }
    }
}
