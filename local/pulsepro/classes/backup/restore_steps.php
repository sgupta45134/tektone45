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
 * Definition restore activity task.
 *
 * @package   local_pulsepro
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pulsepro\backup;

/**
 * Pulse restore task that provides all the settings and steps to perform one complete restore of the activity
 */
class restore_steps {

    /**
     * Restore content to decode.
     *
     * @param  mixed $contents
     * @return array $contents Restore decoded content.
     */
    public static function restore_contents($contents) {
        $contents[] = new \restore_decode_content('local_pulsepro', array(
            'first_content', 'second_content', 'recurring_content'), 'pulse'
        );
        return $contents;
    }

    /**
     * Restore steps structure definition.
     *
     * @param  mixed $paths
     * @return array Restore paths to store data.
     */
    public static function restore_structure($paths) {
        global $DB;

        $paths[] = new \restore_path_element('local_pulsepro', '/activity/pulse/pulse_pro/local_pulsepro');
        $paths[] = new \restore_path_element('local_pulsepro_availability',
            '/activity/pulse/pulseavailabilitydata/local_pulsepro_availability'
        );

        return $paths;
    }
}
