<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\local_autoenroloncomplete\observer::activity_completed'
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_autoenroloncomplete\observer::quiz_submitted'
    ],
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback' => '\local_autoenroloncomplete\observer::assign_graded'
    ],
];
