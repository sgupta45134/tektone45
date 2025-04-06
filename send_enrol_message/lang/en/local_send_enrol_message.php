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
 *
 * @package   local_user_signup_score
 * @copyright  Sudhanshu Gupta<sudhanshug5@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Send Enrol Message';
$string['subject'] = 'Course Enrolment';
$string['message'] = 'Hi {$a->username},
You have successfully registered for a TekTone Training Class. For all details including date, time and location, please visit the class home page. ({$a->courselink})
You will also find a link to a set of files to download in preparation and a few quick videos to watch before attending class (not required for the re-certification classes). If you have registered for an online class, the meeting link will be posted approximately 48 hrs. before class is to be held. Please email us with any questions at training@tektone.com
Sincerely,
TekTone Training team';
$string['message_admin'] = 'Hi {$a->username},
You have successfully registered for a TekTone Training Class. For all details including date, time and location, please visit the class home page. ({$a->courselink})
You will also find a link to a set of files to download in preparation and a few quick videos to watch before attending class (not required for the re-certification classes). If you have registered for an online class, the meeting link will be posted approximately 48 hrs. before class is to be held. Please email us with any questions at training@tektone.com
Sincerely,
TekTone Training team';
