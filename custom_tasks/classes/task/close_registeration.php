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
// Created by Priya K for US#6050

namespace local_custom_tasks\task;

/**
 * An example of a scheduled task.
 */
class close_registeration extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('close_registeration', 'local_custom_tasks');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        global $DB;
        $timetocapture = time() + 4 * 60 * 60;
        $courses = $DB->get_records_sql("SELECT id from {course} where isregclosed = 0 and timeregclosed < $timetocapture and timeregclosed > 0");
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $current_stripe_enrol = $DB->get_record_sql("SELECT count(ue.id) as count,e.id FROM {user_enrolments} ue left join {enrol} e on e.id = ue.enrolid "
                        . "WHERE enrol = 'stripepayment' and e.courseid = $course->id");
                if (!empty($current_stripe_enrol->id)) {
                    $data = new \stdClass();
                    $data->id = $current_stripe_enrol->id;
                    $data->customint3 = $current_stripe_enrol->count;
                    if ($DB->update_record('enrol', $data)) {
                        $coursedata = new \stdClass();
                        $coursedata->id = $course->id;
                        $coursedata->isregclosed = 1;
                        $DB->update_record('course', $coursedata);
                    }
                }
            }
        }
    }
}
