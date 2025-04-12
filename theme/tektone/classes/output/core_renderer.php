<?php
// This file is part of the classic theme for Moodle
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

namespace theme_tektone\output;

use moodle_url;
use stdClass;
use pix_icon;
use core_text;
use action_menu;
use html_writer;
use custom_menu;
use context_course;
use core_course_list_element;
use core_customfield;
use core_customfield\api;
use core_customfield\field_controller;
use core_customfield\output\field_data;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_tektone
 * @copyright  2022 Amit Singh (web.amitsingh@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $CFG, $SITE;

        $context = $form->export_for_template($this);

        $context->errorformatted = $this->error_text($context->error);
        $context->companyinfo = theme_tektone_get_setting('companyinfo');
        $context->loginpageimg = get_loginimage_url();
        $url = get_logo_url();
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true,
                ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('theme_tektone/core/loginform', $context);
    }
     /**
     * Returns course-specific information to be output immediately above content on any course page
     * (for the current course)
     *
     * @param bool $onlyifnotcalledbefore output content only if it has not been output before
     * @return string
     */
    public function course_content_header($onlyifnotcalledbefore = false) {

        global $CFG;
        static $functioncalled = false;
        if ($functioncalled && $onlyifnotcalledbefore) {
            // we have already output the content header
            return '';
        }

        // Output any session notification.
        $notifications = \core\notification::fetch();

        $bodynotifications = '';
        foreach ($notifications as $notification) {
            $bodynotifications .= $this->render_from_template(
                    $notification->get_template_name(),
                    $notification->export_for_template($this)
                );
        }

        $output = html_writer::span($bodynotifications, 'notifications', array('id' => 'user-notifications'));

//        if ($this->page->course->id == SITEID) {
//            // return immediately and do not include /course/lib.php if not necessary
//            return $output;
//        }

        require_once($CFG->dirroot.'/course/lib.php');
        $functioncalled = true;
        $courseformat = course_get_format($this->page->course);

        if (($obj = $courseformat->course_content_header()) !== null) {
            $output .= html_writer::div($courseformat->get_renderer($this->page)->render($obj), 'course-content-header');
        }
        $course = $this->page->course ;
        if ($course instanceof stdClass) {
            $course = new core_course_list_element($course);
        }
       $output .= $this->course_custom_fields($course);
        return $output;
    }

    protected function course_custom_fields(core_course_list_element $course): string {
        global $DB;
        $out = '';
        $out = '';
           $out .= '<div class="d-flex flex-wrap mt-2 course-info-metadata incourse-area">';
        if ($course->has_custom_fields()) {
            $handler = \core_course\customfield\course_handler::create();
            $fieldsdata = $course->get_custom_fields();

             foreach ($fieldsdata as $data) {
            $fd = new field_data($data);
            if($fd->get_shortname() == 'daterange' && !empty($fd->get_value())){
                $out .= '<div><i class="fa fa-calendar" aria-hidden="true"></i><span>'.$fd->get_value().'</span></div>';
            }
            if(($fd->get_shortname() == 'location') && !empty($fd->get_value())){
                $out .= '<div><i class="fa fa-map-marker" aria-hidden="true"></i><span>'.$fd->get_value().'</span></div>';
            }
        }
        }
         if ($instance = theme_tektone_get_selfinstance_forenrolled($course->__get('id'))){

             $out .= '<div><i class="fa fa-users" aria-hidden="true"></i><span>'. $instance.'</span></div> ';
             }
            $out .= '</div>';

        return $out;
    }
}
