<?php

namespace local_autoenroloncomplete;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function activity_completed($event) {

        self::execute($event->userid, $event->courseid);
        return true;
    }

    public static function quiz_submitted($event) {

        self::execute($event->userid, $event->courseid);
        return true;
    }

    public static function assign_graded($event) {

        self::execute($event->relateduserid, $event->courseid);
        return true;
    }

    public static function execute($userid, $courseid) {
        global $DB, $CFG;

        $targetcourse = 293; // Enrol into this course.

        require_once($CFG->libdir . '/completionlib.php');
        // Load completion system.
        $completion = new \completion_info(\get_course($courseid));

        // Get all activities with completion tracking.
        $activities = $completion->get_activities();

        $allcompleted = true;

        foreach ($activities as $activity) {
            // Only check quiz and assign activities
            if (!in_array($activity->modname, ['quiz', 'assign'])) {
                continue;   // Skip other modules
            }


            $data = $completion->get_data($activity, false, $userid);

            // If any required activity is NOT completed → stop here.
            if ($data->completionstate == COMPLETION_INCOMPLETE || $data->completionstate == COMPLETION_COMPLETE_FAIL) {
                $allcompleted = false;
                break;
            }
        }

        // If NOT all activities are completed → do NOT enrol.
        if (!$allcompleted) {
            return true;
        }

        // Check if already enrolled.
        $context = \context_course::instance($targetcourse);
        if (is_enrolled($context, $userid)) {
            return true; // Already enrolled.
        }

        // Enrol user using manual enrolment.
        $enrol = enrol_get_plugin('manual');
        if ($enrol) {
            $instances = enrol_get_instances($targetcourse, true);
            foreach ($instances as $instance) {
                if ($instance->enrol == 'manual') {
                    $enrol->enrol_user($instance, $userid, 5);
                }
            }
        }

        return true;
    }
}
