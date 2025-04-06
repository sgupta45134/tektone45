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
 * Definition backup-activity-task
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_pulsepro\backup;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Backup table structures are defined.
 */
class backup_steps {

    /**
     * Define the structure of pulse pro tabled need to get backup.
     *
     * @param  stdclass $pulse Pulse instnace data.
     * @param  stdclass $userinfo user data.
     * @return sdclass $pulse Backup Db structures.
     */
    public static function define_structure($pulse, $userinfo) {

        // Add pulse pro table fields.
        $pulseprodata = new \backup_nested_element('pulse_pro');
        $pulsepro = new \backup_nested_element('local_pulsepro', array('id'), self::pulsepro_fields());

        // Add pulse pro availability table fields.
        $pulseavailabilitydata = new \backup_nested_element('pulseavailabilitydata');
        $pulseproavailability = new \backup_nested_element('local_pulsepro_availability', array('id'),
            self::pulseavailability_fields()
        );

        // Add pulse pro availability table fields.
        $pulsecreditsdata = new \backup_nested_element('pulsecreditsdata');
        $pulseprocredits = new \backup_nested_element('local_pulsepro_credits', array('id'),
            self::pulsecredits_fields()
        );

        $pulse->add_child($pulseprodata);
        $pulseprodata->add_child($pulsepro);

        $pulse->add_child($pulseavailabilitydata);
        $pulseavailabilitydata->add_child($pulseproavailability);

        $pulse->add_child($pulsecreditsdata);
        $pulsecreditsdata->add_child($pulseprocredits);

        $pulsepro->set_source_table('local_pulsepro', array('pulseid' => \backup::VAR_PARENTID));

        if ($userinfo) {
            $pulseproavailability->set_source_table('local_pulsepro_availability', array('pulseid' => \backup::VAR_PARENTID));
            $pulseproavailability->annotate_ids('user', 'userid');
        }

        if ($userinfo) {
            $pulseprocredits->set_source_table('local_pulsepro_credits', array('pulseid' => \backup::VAR_PARENTID));
            $pulseprocredits->annotate_ids('user', 'userid');
        }

        // Define module file annotations.
        $pulse->annotate_files('mod_pulse', 'first_content', null);
        $pulse->annotate_files('mod_pulse', 'second_content', null);
        $pulse->annotate_files('mod_pulse', 'recurring_content', null);

        return $pulse;
    }

    /**
     * List of pulsepro fields needs to fetch for backup.
     *
     * @return array Returns availalble pulsepro table fields.
     */
    public static function pulsepro_fields() {
        return [
            'pulseid', 'credits', 'credits_status', 'reactiontype', 'reactiondisplay', 'invitation_recipients', 'first_reminder',
            'first_content', 'first_contentformat', 'first_subject', 'first_recipients', 'first_schedule',
            'first_fixeddate', 'first_relativedate', 'second_reminder', 'second_content', 'second_contentformat',
            'second_subject', 'second_recipients', 'second_schedule', 'second_fixeddate', 'second_relativedate',
            'recurring_reminder', 'recurring_content', 'recurring_contentformat',
            'recurring_subject', 'recurring_recipients', 'recurring_relativedate'
        ];
    }

    /**
     * List of pulse_availability fields need to fetch for backup.
     *
     * @return array Lis of pulse availability table fields.
     */
    public static function pulseavailability_fields() {
        return [
            'pulseid', 'userid', 'status', 'availabletime', 'first_reminder_status',
            'second_reminder_status', 'first_reminder_time', 'second_reminder_time',
            'recurring_reminder_time', 'recurring_reminder_prevtime', 'invitation_users',
            'first_users', 'second_users', 'recurring_users'
        ];
    }

    /**
     * List of pulse_availability fields need to fetch for backup.
     *
     * @return array Lis of pulse availability table fields.
     */
    public static function pulsecredits_fields() {
        return [
            'pulseid', 'userid', 'credit', 'timecreated'
        ];
    }
}
