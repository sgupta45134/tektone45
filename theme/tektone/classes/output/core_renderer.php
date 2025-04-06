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

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_tektone
 * @copyright  2025 Amit Singh (web.amitsingh@gmail.com)
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
        $url = get_logo_url();
        $context->logourl = $url;
        $context->sitename = format_string(
            $SITE->fullname,
            true,
            ['context' => context_course::instance(SITEID), "escape" => false]
        );

        return $this->render_from_template('theme_tektone/core/loginform', $context);
    }
    
}
