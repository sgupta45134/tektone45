<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once $CFG->libdir . '/adminlib.php';

class local_replacestring_settings extends admin_setting_configtextarea {

    public function validate($data) {
        global $DB, $PAGE;
        $errors = true;
        $result = array('Users', 'category', 'categories','course', 'Group', 'Site home', 'group', 'Grouping', 'grouping', 'Site pages', 'Site administration', 'participants', 'Role', 'role', 'Cohort sync', 'Flat file (CSV)', 'Manual enrolments', 'PayPal', 'MNet remote enrolments',
            'Guest access', 'External database', 'IMS Enterprise file', 'Publish as LTI tool', 'LDAP enrolments',
            'Category enrolments', 'Course meta link', 'Self enrolment', 'Foo bar!', 'Foo!', 'Foo bar!',
            'ClamAV antivirus', 'TinyMCE HTML editor', 'Wrap', 'Prevent automatic linking', 'Insert image',
            'Manage embedded files', 'Insert emoticon', 'Legacy spell checker', 'Toolbar Toggle', 'Insert media',
            'Atto HTML editor', 'Underline', 'Strike through', 'Table', 'Equation editor',
            'Show/hide advanced buttons', 'Indent', 'Insert character', 'Background colour', 'Subscript',
            'Accessibility checker', 'Link', 'Manage files', 'Media', 'Prevent auto-link', 'Text align',
            'RTL/LTR', 'HTML', 'Font colour', 'Undo/Redo', 'Clear formatting', 'Image', 'Unordered list', 'Italic',
            'Emoticon', 'Superscript', 'Bold', 'Paragraph styles', 'Plain text area', 'Restriction by profile',
            'Restriction by date', 'Restriction by group', 'Restriction by activity completion', 'Restriction by grades',
            'Restriction by grouping', 'Picasa', 'Google Drive', 'Box', 'Flickr.com', 'Mahara ePortfolio', 'File download',
            'File locking', 'Memcached', 'Memcache', 'File cache', 'MongoDB', 'Static request cache', 'Session cache',
            'Repository plugin name', 'Health center', 'Spam cleaner', 'Language packs', 'User upload', 'PHPUnit tests',
            'Development data generator', 'Learning plans', 'Unsupported role assignments', 'File types',
            'Acceptance testing', 'Git Update', 'Log store manager', 'Legacy log', 'Standard log', 'External database log',
            'XMLDB editor', 'Cohort roles management', 'Event monitor', 'Database transfer', 'Course upload',
            'Assignment upgrade helper', 'Profiling runs', 'DB search and replace', 'Competencies migration tool',
            'Language customisation', 'Availability condition management', 'Scheduled task configuration',
            'Moodle Mobile tools', 'Recycle bin', 'Plugin installer', 'Capability overview', 'Template library',
            'Multilang upgrade', 'Inbound message configuration', 'Convert to InnoDB', 'Remote enrolment service',
            'Checkbox', 'Date/Time', 'Text area', 'Dropdown menu', 'Text input', 'Latest announcements',
            'Remote RSS feeds', 'Recent activity', 'Tags', 'Online users', 'Course/site summary', 'Learning plans',
            'Upcoming events', 'Logged in user', 'Course completion status', 'Calendar', 'Main menu', 'Navigation',
            'YouTube', 'Course overview', 'Network servers', 'Comments', 'Flickr', 'Global search',
            'Self completion', 'Recent blog entries', 'Courses', 'Social activities', 'Blog menu', 'HTML', 'Mentees',
            'Activity results', 'Administration', 'Latest badges', 'Messages', 'Random glossary entry', 'People', 'Feedback',
            'Quiz results', 'Blog tags', 'Search forums', 'Community finder', 'Login', 'Section links',
            'Private files', 'Activities', 'Picasa web album', 'Dropbox', 'Recent files', 'Google Drive', 'Box',
            'Flickr public', 'Alfresco repository', 'WebDAV repository', 'Flickr', 'Merlot.org', 'Server files',
            'URL downloader', 'Legacy course files', 'Wikimedia', 'Private files', 'File system', 'Embedded files',
            'Microsoft OneDrive', 'EQUELLA repository', 'Amazon S3', 'Upload a file', 'YouTube videos', 'Paste from spreadsheet',
            'CSV file', 'XML file', 'Excel spreadsheet', 'XML file', 'Plain text file', 'Grade history', 'Grader report', 'Overview report',
            'User report', 'Single view', 'Outcomes report', 'Marking guide', 'Rubric', 'Topics format', 'Weekly format',
            'Social format', 'Single activity format', 'NNTP server', 'No authentication', 'Shibboleth', 'IMAP server',
            'Email-based self-registration', 'POP3 server', 'No login', 'Manual accounts', 'External database',
            'PAM (Pluggable Authentication Modules)', 'FirstClass server', 'MNet authentication', 'RADIUS server',
            'Web services authentication', 'LTI', 'LDAP server', 'CAS server (SSO)', 'SOAP protocol', 'REST protocol',
            'XML-RPC protocol', 'Performance overview', 'Config changes', 'Activity report', 'Competency breakdown',
            'Course participation', 'Question instances', 'Logs', 'Backups report', 'Activity completion',
            'Course completion', 'User sessions report', 'Statistics', 'Global search info', 'Events list', 'Security overview',
            'Course overview', 'Live logs', 'Solr', 'Glossary', 'Page', 'Chat', 'chat', 'Forum', 'Book', 'Book printing',
            'Book IMS CP export', 'Book chapter import', 'Comments', 'Accumulative grading', 'Number of errors',
            'Rubric', 'Comparison with the best assessment', 'Manual allocation', 'Scheduled allocation',
            'Random allocation', 'Workshop', 'Survey', 'File', 'Choice', 'choice', 'Image gallery', 'Multimenu', 'Radio button',
            'Picture', 'Checkbox', 'File', 'Date', 'Latlong', 'URL', 'Number', 'Text area', 'Menu', 'Text input', 'Database',
            'Label', 'Lesson', 'URL', 'SCORM package', 'Interactions report', 'Objectives report', 'Basic report',
            'Graph report', 'Assignment 2.2 (Disabled)', 'Online', 'Offline', 'Upload', 'Upload single', 'Assignment',
            'Online text submissions', 'File submissions', 'Submission comments', 'File feedback', 'Feedback comments',
            'Annotate PDF', 'Offline grading worksheet', 'Folder', 'Wiki', 'IMS content package', 'Feedback',
            'Tool Consumer Profile LTI Service', 'Memberships LTI Service', 'Tool Proxy Service', 'Tool Settings Service',
            'External tool', 'Quiz', 'Responses', 'Grades', 'Manual grading', 'Statistics', 'More', 'Bootstrap Base',
            'Base', 'Clean', 'Canvas', 'Gregorian calendar type', 'Deferred feedback', 'Immediate feedback',
            'Adaptive mode', 'Adaptive mode', 'Manually graded', 'Missing behaviour', 'XHTML format', 'Embedded answers',
            'Examview', 'WebCT format', 'Moodle XML format', 'Missing word format', 'Gift format', 'Aiken format',
            'Blackboard', 'True/False', 'Short answer', 'Multiple choice', 'Missing type', 'Numerical', 'Essay',
            'Drag and drop markers', 'Embedded answers (Cloze)', 'Drag and drop into text',
            'Calculated simple', 'Calculated', 'Matching', 'Description', 'Random', 'Calculated multichoice',
            'Email', 'Popup notification', 'Jabber message', 'Mobile notifications', 'mod', 'assignsubmission',
            'assignfeedback', 'assignment', 'booktool', ' datafield', 'datapreset', 'ltisource', 'ltiservice', 'quiz',
            'quizaccess', 'scormreport', 'workshopform', 'workshopallocation', 'workshopeval', 'block', 'qtype',
            'qbehaviour', 'qformat', 'filter', 'editor', 'atto', 'tinymce', 'enrol', 'auth', 'tool', 'logstore',
            'antivirus', 'availability', 'calendartype', 'message', 'format', 'dataformat', 'profilefield', 'report',
            'coursereport', 'gradeexport', 'gradeimport', 'gradereport', 'gradingform', 'mnetservice', 'webservice',
            'repository', 'portfolio', 'search', 'plagiarism', 'cachestore', 'cachelock', 'theme', 'local', 'Activity',
            'Assignment', 'Submission plugins', 'Book', 'Reports', 'Grading strategies', 'Presets', 'Blocks', 'Question types',
            'Question behaviours', 'Question import', 'export formats', 'Text filters', 'Editors', 'Atto plugins',
            'Plugins', 'Enrolment methods', 'Authentication methods', 'Admin tools', 'Log stores', 'Antivirus plugins',
            'Availability restrictions', 'Calendar types', 'Messaging outputs', 'Course formats', 'Data formats',
            'Course reports', 'Grade export methods', 'Grade import methods', 'Gradebook reports', 'MNet services',
            'Webservice protocols', 'Repositories', 'Portfolios', 'Search engines', 'Plagiarism plugins',
            'Cache stores', 'Cache lock handlers', 'Themes', 'Local plugins');
        if (!empty($data)) {
            //For line Validation
            $newarr = (explode("\n", $data)); //print_R($newarr);die;
            for ($z = 0; $z < count($newarr); $z++) {
                if (empty($newarr[$z])) {
                    $errors = "White space on the left or right of the source or replacement string - embedded space is allowed.";
                    return $errors;
                }
            }
            $data = trim($data);
            $newarr = (explode("\n", $data));
            for ($i = 0; $i < count($newarr); $i++) {
                $strings = explode(":", $newarr[$i]);
                if (count($strings) <= 2) {
                    $key = trim($strings[0]);
                    for ($m = 0; $m < count($newarr); $m++) {
                        $check = explode(":", $newarr[$m]);
                        $rvalue = isset($check[1]) ? trim($check[1]) : NULL;
                        if (!empty($rvalue)) {
                            if (!local_whitespaces_test($check)) {
                                // print_r($check);die;
                                $errors = "'$check[0]' , Remove  white space on the left or right of the source or replacement string.";
                                return $errors;
                            }
                            if (strcmp($key, $rvalue) == 0) {
                                $errors = 'Cyclical renaming is not allowed within the same language i.e. a member of a pair cannot lie on both sides of the separator anywhere in the whole set.';
                                return $errors;
                            }
                        }
                        if (empty($rvalue)) {
                            $errors = 'The target value can\'t be empty';
                            return $errors;
                        }
                    }
                } else {
                    $errors = "Please use colon delimeter(:) once for string replacement.";
                    return $errors;
                }
                if (isset($strings[1])) {
                    $value = trim($strings[1]);
                }
                for ($j = 0; $j < count($result); $j++) {
                    if ($key == $result[$j]) {
                        for ($k = 0; $k < count($result); $k++) {
                            if ($value == $result[$k]) {
                                $errors = "'$value', The target value matches an existing plugin name, entity or type, and is not allowed to prevent confusion.";
                                return $errors;
                            }
                        }
                    }
                }
            }
        }
        return $errors;
    }

}

function local_whitespaces_test($check) {
    $blen1 = strlen($check[0]);
    $alen1 = strlen(trim($check[0]));
    $blen2 = strlen(rtrim($check [1]));
    $alen2 = strlen(trim($check [1]));
    if ($alen1 == $blen1 && $blen2 == $alen2) {
        return true;
    }

    return false;
}

class local_replacestring_setting extends admin_setting {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('replacestring', get_string('replacestrgrp', 'local_replacestring'), '', '');
    }

    /**
     * Returns current value of this setting.
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Returns default setting if exists.
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Store new setting.
     * Always returns '', does not write anything.
     *
     * @param string $data string or array, must not be NULL.
     * @return string Always returns ''.
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

}
