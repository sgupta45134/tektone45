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
 * Course renderer.
 *
 * @package    theme_tektone
 * @copyright  2025 Amit Singh (web.amitsingh@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_tektone\output\core;
defined('MOODLE_INTERNAL') || die();

use moodle_url;
use lang_string;
use coursecat_helper;
use coursecat;
use stdClass;
use course_in_list;
use context_course;
use pix_url;
use html_writer;
use heading;
use pix_icon;
use image_url;
use single_select;
use cm_info;
use url_select;
use completion_info;
use renderable;
use core_availability;
use core_availability\info;
use moodle_page;
use action_menu;
use context_module;
use core_course_list_element;

require_once($CFG->dirroot . '/course/renderer.php');

/**
 * Course renderer class.
 */

class course_renderer extends \core_course_renderer {

        protected $countcategories = 0;
        const _FORMAT_PARADISO = 'paradiso';

    protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof stdClass) {
            $course = new core_course_list_element($course);
        }
        $content = '';
        $classes = trim('coursebox clearfix '. $additionalclasses);
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $classes .= ' collapsed';
        }

      //  if($course->enddate > time())
       // $content .= html_writer::start_tag('div', array('class' => 'custom-grey-out'));
      if($course->enddate != 0 && $course->enddate < time()){
          if(is_siteadmin()) {
            $content .= html_writer::start_tag('div', array('class' => 'custom-grey-out'));
          }else{
              $content .= html_writer::start_tag('div', array('class' => 'custom-hide-course'));  
          }
	}

        // .coursebox
        $content .= html_writer::start_tag('div', array(
            'class' => $classes,
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ));
        
        $content .= html_writer::start_tag('div', array('class' => 'info'));
        $content .= $this->course_name($chelper, $course);
        $content .= $this->course_enrolment_icons($course);
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array('class' => 'content'));
        $content .= $this->coursecat_coursebox_content($chelper, $course);
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div'); // .coursebox
        
        if($course->enddate != 0 && $course->enddate < time())
        $content .= html_writer::end_tag('div'); // add grey out class
    
        return $content;
    }

    /**
     * Returns HTML to display course content (summary, course contacts and optionally category name)
     *
     * This method is called from coursecat_coursebox() and may be re-used in AJAX
     *
     * @param coursecat_helper $chelper various display options
     * @param stdClass|core_course_list_element $course
     * @return string
     */
    protected function coursecat_coursebox_content(coursecat_helper $chelper, $course) {
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return '';
        }
        if ($course instanceof stdClass) {
            $course = new core_course_list_element($course);
        }
        $content = \html_writer::start_tag('div', ['class' => 'd-flex']);
        $content .= $this->course_overview_files($course);
        $content .= \html_writer::start_tag('div', ['class' => 'flex-grow-1']);
        $content .= $this->course_summary($chelper, $course);

        $content .= $this->course_contacts($course);
        $content .= $this->course_category_name($chelper, $course);
        $content .= $this->course_custom_fields($course);
        $content .= \html_writer::end_tag('div');
        $content .= \html_writer::end_tag('div');
        return $content;
}

    protected function course_custom_fields(\core_course_list_element $course): string {
        global $DB;
        $content = '';
        if ($course->has_custom_fields()) {
            $handler = \core_course\customfield\course_handler::create();
            $customfields = $handler->display_custom_fields_data($course->get_custom_fields());
            $content .= \html_writer::tag('div', $customfields, ['class' => 'customfields-container']);
            if (($instance = theme_tektone_get_selfinstance($course->__get('id')) ) || $instance = theme_tektone_get_selfinstance($course->__get('id'), 'stripepayment')){
             $data = '<div class="customfield customfield_text customfield_location"> '.
             ' <span class="customfieldname">No of seats</span><span class="customfieldseparator">: </span>'.
              '<span class="customfieldvalue">'.$instance.'</span></div>';
              $content .= \html_writer::tag('div', $data, ['class' => 'customfields-container']);
             }

        }
        return $content;
    }
}
