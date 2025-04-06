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
 * Pulse instance test instance generate defined.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse module instance generator.
 */
class local_pulsepro_generator extends testing_module_generator {

    /**
     * Module intro content.
     *
     * @var string
     */
    public $intro = 'Pulse test notification';

    /**
     * Default pulse pro module data.
     *
     * @param  mixed $courseid
     * @return void
     */
    public function default_data($courseid) {
        $options = [
            'course' => $courseid,
            'intro' => $this->intro,
            "invitation_recipients" => '',
            "credits" => "",
            "credits_status" => 0,
            "reactiontype" => 0,
            "reactiondisplay" => 1,
            "first_reminder" => 0,
            "first_subject" => "First pulse pro reminder",
            "first_content" => "First reminder content",
            "first_contentformat" => '1',
            "first_recipients" => '',
            "first_schedule" => 0,
            "first_fixeddate" => strtotime(date('Y-m-d')),
            "first_relativedate" => 0,
            "second_reminder" => 0,
            "second_subject" => "Second pulse pro reminder",
            "second_content" => "Second reminder content",
            "second_contentformat" => '1',
            "second_recipients" => '',
            "second_schedule" => 0,
            "second_fixeddate" => strtotime(date('Y-m-d')),
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

    /**
     * Insert defined demo presets for test.
     *
     * @return void
     */
    public function create_presets(): void {
        pulsepro_create_presets();
    }

    /**
     * Create a new category for custom profile fields.
     *
     * @param array $data Array with 'name' and optionally 'sortorder'
     * @return \stdClass New category object
     */
    public function create_custom_profile_field_category(array $data): \stdClass {
        global $DB;

        // Pick next sortorder if not defined.
        if (!array_key_exists('sortorder', $data)) {
            $data['sortorder'] = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_category}') + 1;
        }

        $category = (object)[
            'name' => $data['name'],
            'sortorder' => $data['sortorder']
        ];
        $category->id = $DB->insert_record('user_info_category', $category);

        return $category;
    }

    /**
     * Creates a new custom profile field.
     *
     * Optional fields are:
     *
     * categoryid (or use 'category' to specify by name). If you don't specify
     * either, it will add the field to a 'Testing' category, which will be created for you if
     * necessary.
     *
     * sortorder (if you don't specify this, it will pick the next one in the category).
     *
     * all the other database fields (if you don't specify this, it will pick sensible defaults
     * based on the data type).
     *
     * @param array $data Array with 'datatype', 'shortname', and 'name'
     * @return \stdClass Database object from the user_info_field table
     */
    public function create_custom_profile_field(array $data): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Set up category if necessary.
        if (!array_key_exists('categoryid', $data)) {
            if (array_key_exists('category', $data)) {
                $data['categoryid'] = $DB->get_field('user_info_category', 'id',
                        ['name' => $data['category']], MUST_EXIST);
            } else {
                // Make up a 'Testing' category or use existing.
                $data['categoryid'] = $DB->get_field('user_info_category', 'id', ['name' => 'Testing']);
                if (!$data['categoryid']) {
                    $created = $this->create_custom_profile_field_category(['name' => 'Testing']);
                    $data['categoryid'] = $created->id;
                }
            }
        }

        // Pick sort order if necessary.
        if (!array_key_exists('sortorder', $data)) {
            $data['sortorder'] = (int)$DB->get_field_sql(
                    'SELECT MAX(sortorder) FROM {user_info_field} WHERE categoryid = ?',
                    [$data['categoryid']]) + 1;
        }

        // Defaults for other values.
        $defaults = [
            'description' => '',
            'descriptionformat' => 0,
            'required' => 0,
            'locked' => 0,
            'visible' => PROFILE_VISIBLE_ALL,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'defaultdataformat' => 0,
            'param1' => '',
            'param2' => '',
            'param3' => '',
            'param4' => '',
            'param5' => ''
        ];

        // Type-specific defaults for other values.
        $typedefaults = [
            'text' => [
                'param1' => 30,
                'param2' => 2048
            ],
            'menu' => [
                'param1' => "Yes\nNo",
                'defaultdata' => 'No'
            ],
            'datetime' => [
                'param1' => '2010',
                'param2' => '2015',
                'param3' => 1
            ],
            'checkbox' => [
                'defaultdata' => 0
            ]
        ];
        foreach ($typedefaults[$data['datatype']] ?? [] as $field => $value) {
            $defaults[$field] = $value;
        }

        foreach ($defaults as $field => $value) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = $value;
            }
        }

        $data['id'] = $DB->insert_record('user_info_field', $data);
        return (object)$data;
    }

}
