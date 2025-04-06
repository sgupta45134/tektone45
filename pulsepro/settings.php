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
 * Pulse pro module settings definition.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');

$ADMIN->add('localplugins', new admin_category('local_pulsepro',
            get_string('pluginname', 'local_pulsepro', null, true)));

$page = new admin_settingpage('local_pulsepro_settings', get_string('generalsettings', 'local_pulsepro'));

$name = 'local_pulsepro/expiretime';
$title = get_string('expiretime', 'local_pulsepro');
$description = get_string('expiretimedesc', 'local_pulsepro');
$setting = new admin_setting_configduration($name, $title, $description, 0);
$page->add($setting);

$userfields = [0 => get_string('choose')];
$userfields += \local_pulsepro\credits::userprofile_fields();
$name = 'local_pulsepro/creditsfield';
$title = get_string('creditsfield', 'pulse');
$description = get_string('creditsfielddesc', 'pulse');
$setting = new admin_setting_configselect($name, $title, $description, null, $userfields);
$page->add($setting);

// Email placeholders.
$vars = \EmailVars::vars();
$placeholders = "<div class='form-group row  fitem'>
    <div class='col-md-9'><div class='emailvars '>";
$optioncount = 0;
foreach ($vars as $option) {
    $placeholders .= "<a href='#' data-text='$option' class='clickforword'><span>$option</span></a>";
    $optioncount++;
}
$placeholders .= "</div></div></div>";

// Notification header.
$name = 'mod_pulse/notificationheader';
$title = get_string('notificationheader', 'pulse');
$description = get_string('notificationheaderdesc', 'pulse', ['placeholders' => $placeholders]);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$page->add($setting);
// Notification footer.
$name = 'mod_pulse/notificationfooter';
$title = get_string('notificationfooter', 'pulse');
$description = get_string('notificationfooterdesc', 'pulse', ['placeholders' => $placeholders]);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$page->add($setting);

// Email tempalte placholders.
$PAGE->requires->js_call_amd('mod_pulse/module', 'init');


$ADMIN->add('local_pulsepro', $page);

// Manage presets admin setting menu.
$managepresetsurl = new moodle_url('/local/pulsepro/presets.php');
$ADMIN->add(
    'local_pulsepro',
    new admin_externalpage('local_pulsepro_presets', get_string('managepresets', 'pulse'), $managepresetsurl)
);
