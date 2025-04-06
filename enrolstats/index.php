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
require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/enrolstats/locallib.php');
$categoryid = optional_param("categoryid", 0, PARAM_INT);
$page = optional_param("page", 0, PARAM_INT);
$perpage = optional_param("perpage", 10, PARAM_INT);
$export = optional_param("export", '', PARAM_ALPHA);

$startlimit = $page * $perpage;

require_login();

if ($categoryid) {
    $category = core_course_category::get($categoryid); // This will validate access.
} else {
    if (empty($categoryid)) {
        if (is_siteadmin()) {
            $category = core_course_category::user_top();
        } else {
            $cats = core_course_category::make_categories_list('local/enrolstats:access_enrolstats');
            $categoryid = array_pop(array_keys($cats));
            if ($categoryid) {
                $category = core_course_category::get($categoryid);
            }
        }
    }
    if (!$category) {
        throw new moodle_exception('cannotviewcategory');
    }
}

if ($category->id) {
    $catcontext = context_coursecat::instance($category->id);
} else {
    $catcontext = context_system::instance();
}
if (!is_siteadmin()) {
    require_capability('local/enrolstats:access_enrolstats', $catcontext);
}


if ($export) {
    local_enrolstats_download_stats($export, $categoryid);
}


$PAGE->set_context($catcontext);
$PAGE->set_url(new moodle_url('/local/enrolstats/index.php'));
$PAGE->set_pagetype('standard');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('index_title', 'local_enrolstats'));
$PAGE->set_heading(get_string('index_title', 'local_enrolstats'));

$params = array(
    'objectid' => $categoryid,
    'courseid' => '',
    'context' => $catcontext,
    'other' => array(
        'category_id' => $categoryid
    )
);
$event = \local_enrolstats\event\enrolstats_view::create($params);
$event->trigger();


$options['recursive'] = true;
$options['offset'] = $page * $perpage;
$options['limit'] = $perpage;

$courses = core_course_category::get($categoryid)->get_courses($options);
$coursecount = core_course_category::get($categoryid)->get_courses_count(array('recursive' => true));

echo $OUTPUT->header();


$output = '';
$output .= html_writer::start_tag('div', array('class' => 'categorypicker'));
$select = new single_select(new moodle_url('/local/enrolstats/index.php'), 'categoryid',
        core_course_category::make_categories_list('local/enrolstats:access_enrolstats'),
        $category->id, get_string('select', 'local_enrolstats'), 'switchcategory');
$select->set_label(get_string('categories') . ':');
$output .= $OUTPUT->render($select);
$output .= html_writer::end_tag('div');
echo $output;
echo html_writer::start_div('export', array("style" => "float:right"));
echo $OUTPUT->download_dataformat_selector(get_string('download', 'local_enrolstats'),
        '/local/enrolstats/index.php', 'export', array("categoryid" => $categoryid));
echo html_writer::end_div();
echo '<br/>';
$table = new html_table();
$table->attributes['class'] = 'table table-bordered table-striped';
$table->head = array(get_string('table_head_course', 'local_enrolstats'), get_string('table_head_student', 'local_enrolstats'),
    get_string('table_head_stats', 'local_enrolstats'));
$enrols = enrol_get_plugins(true);

foreach ($courses as $c) {

    $url = new moodle_url("/course/view.php", array("id" => $c->id));
    $link = '<a href="' . $url->out() . '">' . $c->fullname . '</a>';
    $html = [];
    $enrolinstances = enrol_get_instances($c->id, false);
    $coursecontext = context_course::instance($c->id);
    $total_enrol = 0;
    foreach ($enrolinstances as $enrolinstance) {
        if ($enrolplugin = enrol_get_plugin($enrolinstance->enrol)) {
            $sql = "SELECT count(distinct(ue.userid)) as count FROM `mdl_user_enrolments` ue left join mdl_enrol e on ue.enrolid = e.id "
                        . "left join mdl_context c on c.instanceid = e.courseid left join mdl_role_assignments ra on ue.userid = ra.userid where ue.status = 0 "
                        . "and c.contextlevel = 50 and ra.contextid = $coursecontext->id and ra.roleid = 5 and ue.enrolid = $enrolinstance->id";
            $record = $DB->get_record_sql($sql);
            $acount = $record->count;
            $total_enrol += $acount;
            $scount = $DB->count_records("user_enrolments", array("enrolid" => $enrolinstance->id, "status" => 1));
            $tatal_suspend += $scount;

            $name = $enrolplugin->get_instance_name($enrolinstance);

            $type = $enrolinstance->enrol;

            $l = $OUTPUT->single_button(new moodle_url('/user/index.php', array('id' => $c->id, 'contextid' => $coursecontext->id,
                'unified-filters[]' => '1:'
                . $enrolinstance->id, 'unified-filter-submitted' => 1)),
                    get_string('index_active', 'local_enrolstats'). ' (' . $acount . ')'. " | ".
                    get_string('index_suspended', 'local_enrolstats').' (' . $scount . ') ');

            $str = <<<HTML
  <tr>
   <td>$name</td>
        <td>$type</td>
                    <td>$l</td>
       </tr>
HTML;
            $html[] = $str;
        }
    }
    $htmlstr = '';
    if (!empty($html)) {
        $htmlstr = '<table class="table table-hover">';
        $htmlstr .= implode(" ", $html);
        $htmlstr .= '</table>';
    }

    $url = new moodle_url("/user/index.php", array("id" => $c->id));
//    $ae = $total_enrol;
//    $al = count(enrol_get_course_users($c->id, false));

    $l1 = '<a href="' . $url->out() . '"> ' . get_string('index_enrolled',
            'local_enrolstats') . ' : ' . get_string('index_active', 'local_enrolstats')
            . " : " . $total_enrol . ' / ' . get_string('index_suspended', 'local_enrolstats') . ' : ' . $tatal_suspend . '</a>';
    $table->data[] = array($link, $l1, $htmlstr);
}

$paginationurl = new moodle_url("/local/enrolstats/index.php", array("categoryid" => $category->id,
    'page' => $page, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($coursecount, $page, $perpage, $paginationurl);

echo html_writer::table($table);

echo $OUTPUT->paging_bar($coursecount, $page, $perpage, $paginationurl);

echo $OUTPUT->footer();
