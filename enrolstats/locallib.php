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
 * Plugin administration pages are defined here.
 *
 * @package     local_enrolstats
 * @category    admin
 * @copyright   2020 Chandra Kishor <developerck@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
/**
 * This function to prepare the data for download
 *
 * @param dataformat $dataformat csv|html|xlsx
 * @param categoryid $categoryid to fetch the data
 */
function local_enrolstats_download_stats($dataformat, $categoryid) {
global $DB, $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/dataformatlib.php');


if ($dataformat) {

    // Define the headers and columns.
    $headers = [];
    $headers[] = get_string('table_head_category', 'local_enrolstats');
    $headers[] = get_string('table_head_course', 'local_enrolstats');
//    $headers[] = get_string('table_head_enrol_method', 'local_enrolstats');
//    $headers[] = get_string('table_head_enrol_instance', 'local_enrolstats');
    $headers[] = get_string('index_active', 'local_enrolstats');
    $headers[] = get_string('index_suspended', 'local_enrolstats');
    $headers[] = get_string('index_certified', 'local_enrolstats');
   
    $tmpdata = new stdClass();
    $alllearners = [];
    $c = 0;
    $cats = array();
    $options = [];
    $options['recursive'] = true;
    $courses = core_course_category::get($categoryid)->get_courses($options);
    $cats = array();
    foreach ($courses as $c) {
        if (!array_key_exists($c->category, $cats)) {
            $cats[$c->category] = $DB->get_field("course_categories", "name", array("id" => $c->category));
        }
        $enrolinstances = enrol_get_instances($c->id, false);
        $certified = $DB->count_records('tool_certificate_issues',array('courseid' =>$c->id));
        $acount = 0;
        $scount = 0;
        $coursecontext = context_course::instance($c->id);
        foreach ($enrolinstances as $enrolinstance) {
            $tmpdata = new stdClass();
            if ($enrolplugin = enrol_get_plugin($enrolinstance->enrol)) {
                $name = $enrolplugin->get_instance_name($enrolinstance);
                $sql = "SELECT count(distinct(ue.userid)) as count FROM `mdl_user_enrolments` ue left join mdl_enrol e on ue.enrolid = e.id "
                        . "left join mdl_context c on c.instanceid = e.courseid left join mdl_role_assignments ra on ue.userid = ra.userid where ue.status = 0 "
                        . "and c.contextlevel = 50 and ra.contextid = $coursecontext->id and ra.roleid = 5 and ue.enrolid = $enrolinstance->id";
                $record = $DB->get_record_sql($sql);
                $acount += $record->count;
                $scount += $DB->count_records("user_enrolments", array("enrolid" => $enrolinstance->id, "status" => 1));
            }
        }
                $line = array();
                $tmpdata->categoryname = $cats[$c->category];
                $tmpdata->coursename = $c->fullname;
//                $tmpdata->enroltype = $enrolinstance->enrol;
//                $tmpdata->instance= $name;
                $tmpdata->active = $acount;
                $tmpdata->suspended = $scount;
                $tmpdata->certified = $certified;
             $alllearners[] = $tmpdata;
       
    }
    $name = "Enrolment_Report";
    $alllearners = (object) $alllearners;
    $filename = clean_filename($name);
    $user = new ArrayObject($alllearners);
    $iterator = $user->getIterator();
   

    $countrecord = 0;
    \core\dataformat::download_data($filename, $dataformat, $headers, $iterator, function ($user) {
        global $DB;
        $data = array();
        $data[] = $user->categoryname;
        $data[] = $user->coursename;
//        $data[] = $user->enroltype;
//        $data[] = $user->instance;
        $data[] = $user->active;
        $data[] = $user->suspended;
        $data[] = $user->certified;
        return $data;
    });
    exit;
}
}