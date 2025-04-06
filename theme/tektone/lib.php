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
 * Theme functions.
 *
 * @package    theme_tektone
 * @copyright  2025 Amit Singh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_tektone_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'logo' || $filearea === 'loginpageimg')) {
        $theme = theme_config::load('tektone');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}


/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_tektone_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    $pre = file_get_contents($CFG->dirroot . '/theme/tektone/scss/pre.scss');
    $post = file_get_contents($CFG->dirroot . '/theme/tektone/scss/post.scss');
    $responsivescss = file_get_contents($CFG->dirroot . '/theme/tektone/scss/responsive.scss');

    return $pre . "\n" . $scss . "\n" . $post . "\n" . $responsivescss;
}


/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_tektone_get_pre_scss($theme) {
    global $CFG;

    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'headerbg' => ['header-bg'],
        'loginbg' => ['login-bg'],
        'blockbg' => ['block-bg'],
        'primarybtnbg' => ['primarybtn-bg'],
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

// Logo Image URL Fetch from theme settings.
// @ return string.
if (!function_exists('get_logo_url')) {
    /**
     * Description
     * @return type|string
     */
    function get_logo_url() {
        global $OUTPUT;
        static $theme;
        if (empty($theme)) {
            $theme = theme_config::load('tektone');
        }
        $logo = $theme->setting_file_url('logo', 'logo');
        $logo = empty($logo) ? $OUTPUT->image_url('logo', 'theme') : $logo;
        return $logo;
    }
}

// Loginimage Image URL Fetch from theme settings.
// @ return string.
if (!function_exists('get_loginimage_url')) {
    /**
     * Description
     * @return type|string
     */
    function get_loginimage_url() {
        global $OUTPUT;
        static $theme;
        if (empty($theme)) {
            $theme = theme_config::load('tektone');
        }
        $loginpageimg = $theme->setting_file_url('loginpageimg', 'loginpageimg');
        $loginpageimg = empty($loginpageimg) ? $OUTPUT->image_url('login-bg', 'theme') : $loginpageimg;
        return $loginpageimg;
    }
}

/**
 *
 * Description
 * @param type|string $setting
 * @param type|bool $format
 * @return type|string
 */
function theme_tektone_get_setting($setting, $format = false) {
    global $CFG;
    require_once($CFG->dirroot . '/lib/weblib.php');
    static $theme;
    if (empty($theme)) {
        $theme = theme_config::load('tektone');
    }
    if (empty($theme->settings->$setting)) {
        return false;
    } else if (!$format) {
        return $theme->settings->$setting;
    } else if ($format === 'format_text') {
        return format_text($theme->settings->$setting, FORMAT_PLAIN);
    } else if ($format === 'format_html') {
        return format_text($theme->settings->$setting, FORMAT_HTML, array('trusted' => true, 'noclean' => true));
    } else {
        return format_string($theme->settings->$setting);
    }
}

function theme_tektone_update_settings_images($settingname) {                                                                         
    global $CFG;
                                                                                                 
    $parts = explode('_', $settingname);
    $settingname = end($parts);                                                                                                                                     
    $syscontext = context_system::instance();
    $component = 'theme_tektone';
                                                                                                                                    
    $filename = get_config($component, $settingname);                                                            
    $extension = substr($filename, strrpos($filename, '.') + 1);                                                                    
                                                                                                                                    
    $fullpath = "/{$syscontext->id}/{$component}/{$settingname}/0{$filename}";                                                                               
    $fs = get_file_storage();                                                          
    if ($file = $fs->get_file_by_hash(sha1($fullpath))) {                               
        $pathname = $CFG->dataroot . '/pix_plugins/theme/tektone/' . $settingname . '.' . $extension;                                 

        $pathpattern = $CFG->dataroot . '/pix_plugins/theme/tektone/' . $settingname . '.*';                                          

        @mkdir($CFG->dataroot . '/pix_plugins/theme/tektone/', $CFG->directorypermissions, true);                                      

        foreach (glob($pathpattern) as $filename) {                                                                                 
            @unlink($filename);                                                                                                     
        }                                                                                                                           
                                                                                                                                    
        $file->copy_content_to($pathname);                                                                                          
    }                                                                                                                               
                                                                                                                                   
    theme_reset_all_caches();                                                                                                       
}