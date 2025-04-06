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
 * Adds this plugin to the admin menu.
 *
 * @package    local
 * @subpackage replacestring
 */
defined('MOODLE_INTERNAL') || die;

global $DB, $PAGE;
if ($hassiteconfig) { // Needs this condition or there is error on login page.
    require_once(__DIR__ . '/lib.php');
    $replacestrings = new admin_settingpage('languagestring', new lang_string('stringsetting', 'local_replacestring'));
    $replacestrings->add(new admin_setting_heading('strinreplace123', '', get_string('stringreplace_desc', 'local_replacestring')));
    $replacestrings->add(new local_replacestring_setting());
    $name = new lang_string('bsr_string', 'local_replacestring');
    $lang = current_language();
    $default = $DB->get_field('config', 'value', array('name' => 'bsr' . "_$lang"));
    $replace = new local_replacestring_settings('replacestring' . "_$lang", $name, new lang_string('intro', 'local_replacestring'), '', PARAM_RAW);
    $name = new lang_string('active', 'local_replacestring');
    $replacestrings->add($replace);
    $replacestrings->add(new admin_setting_configcheckbox("bsr_active_$lang", $name, new lang_string('checkbox_info', 'local_replacestring'), ''));
    $ADMIN->add('language', $replacestrings);
    get_string_manager()->reset_caches();
}
