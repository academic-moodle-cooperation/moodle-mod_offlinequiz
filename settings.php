<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
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
 * Administration settings definitions for the offlinequiz module.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    global $DB;
    require_once($CFG->dirroot.'/mod/offlinequiz/lib.php');
    require_once($CFG->dirroot.'/mod/offlinequiz/settingslib.php');


    // Introductory explanation that all the settings are defaults for the add offlinequiz form.
    $settings->add(new admin_setting_heading('offlinequizintro', '', get_string('configintro', 'offlinequiz')));

    // User identification.
    $settings->add(new admin_setting_configtext_user_formula('offlinequiz/useridentification',
            get_string('useridentification', 'offlinequiz'), get_string('configuseridentification', 'offlinequiz'),
            '[7]=idnumber' , PARAM_RAW, 30));

    // Print study code field.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/printstudycodefield',
            get_string('printstudycodefield', 'offlinequiz'), get_string('printstudycodefield_help', 'offlinequiz'),
            1));
    // Fontfamily.
    require_once($CFG->dirroot . '/lib/pdflib.php');
    $pdf = new pdf();
    if($pdf) {
        $fontfamilies = $pdf->get_font_families();
    } else {
        $fontfamilies = [];
        $fontfamilies['fontfamilyfreserif'] = get_string('fontfamilyfreeserif', 'offlinequiz');
    }
    $options = [];
    foreach ($fontfamilies as $name => $values) {
        if(get_string_manager()->string_exists('fontfamily' . $name, 'offlinequiz') ) {
            $options[$name] = get_string('fontfamily' . $name, 'offlinequiz');
        } else {
            $options[$name] = $name;
        }
    }
    $settings->add(new admin_setting_configselect('offlinequiz/defaultpdffont',
        get_string('defaultpdffont', 'offlinequiz'), get_string('defaultpdffont_help', 'offlinequiz'),
        'freeserif', $options));
    // PDF default fontsize.
    $options = [
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
            14 => 14
        ];
        
    $settings->add(new admin_setting_configselect('offlinequiz/defaultpdffontsize',
        get_string('defaultpdffontsize', 'offlinequiz'), get_string('defaultpdffontsize_help', 'offlinequiz'),
        10, $options));

    // Shuffle questions.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/shufflequestions',
            get_string('shufflequestions', 'offlinequiz'), get_string('configshufflequestions', 'offlinequiz'),
            0));

    // Shuffle within questions.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/shuffleanswers',
            get_string('shufflewithin', 'offlinequiz'), get_string('configshufflewithin', 'offlinequiz'),
            1));

    // Logo image URL setting.
    $settings->add(new admin_setting_configtext('offlinequiz/logourl', get_string('logourl', 'offlinequiz'),
            get_string('logourldesc', 'offlinequiz'), '', PARAM_URL));

    // Admin setting to disable display of copyright statement.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/showcopyright', get_string('showcopyright', 'offlinequiz'),
            get_string('showcopyrightdesc', 'offlinequiz'), 1));

    // Admin setting to set if participant usage is possible.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/defaultparticipantsusage', get_string('defaultparticipantsusage', 'offlinequiz'),
            get_string('defaultparticipantsusagedesc', 'offlinequiz'), 1));

    // Disable newlines around images.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/disableimgnewlines',
            get_string('disableimgnewlines', 'offlinequiz'), get_string('configdisableimgnewlines', 'offlinequiz'),
            0));

    // Review options.
    $settings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'offlinequiz'), ''));

    foreach (mod_offlinequiz_admin_review_setting::fields() as $field => $name) {
        $default = mod_offlinequiz_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_offlinequiz_admin_review_setting::DURING;
            $forceduring = false;
        }
        $settings->add(new mod_offlinequiz_admin_review_setting('offlinequiz/review' . $field,
                $name, '', $default, $forceduring));
    }


    // Decimal places for overall grades.
    $settings->add(new admin_setting_heading('gradingheading',
            get_string('gradingoptionsheading', 'offlinequiz'), ''));

    $options = array();
    for ($i = 0; $i <= 3; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('offlinequiz/decimalpoints',
            get_string('decimalplaces', 'offlinequiz'), get_string('configdecimalplaces', 'offlinequiz'),
            2, $options));

    $settings->add(new admin_setting_configtext('offlinequiz/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'offlinequiz'),
            100, PARAM_INT));

    $settings->add(new admin_setting_heading('scanningheading',
            get_string('scanningoptionsheading', 'offlinequiz'), ''));

    $options = array();
    $options[610] = get_string("darkgray", "offlinequiz");
    $options[640] = get_string("lightgray", "offlinequiz");
    $options[670] = get_string("standard", "offlinequiz");
    $options[680] = get_string("white", "offlinequiz");
    $options[700] = get_string("pearlywhite", "offlinequiz");

    $settings->add(new admin_setting_configselect('offlinequiz/papergray', get_string('papergray', 'offlinequiz'),
            get_string('configpapergray', 'offlinequiz'), 670, $options));

    $settings->add(new admin_setting_configtext('offlinequiz/blackwhitethreshold', get_string('blackwhitethreshold', 'offlinequiz'),
            get_string('configblackwhitethreshold', 'offlinequiz'), '75', PARAM_INT));

    $settings->add(new admin_setting_heading('correctionheading',
            get_string('correctionoptionsheading', 'offlinequiz'), ''));

    // Admin setting to allow teachers to enrol users with one click while correcting answer forms.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/oneclickenrol', get_string('oneclickenrol', 'offlinequiz'),
            get_string('oneclickenroldesc', 'offlinequiz'), 0));

    $studentroles = $DB->get_records('role', array('archetype' => 'student'), 'sortorder');
    $options = array();
    $default = null;
    foreach ($studentroles as $role) {
        if ($role->name) {
            $name = $role->name;
        } else {
            $name = $role->shortname;
        }
        $options[$role->id] = $name;
        if (!$default) {
            $default = $role->id;
        }
    }

    $settings->add(new admin_setting_configselect('offlinequiz/oneclickrole', get_string('oneclickrole', 'offlinequiz'),
            get_string('oneclickroledesc', 'offlinequiz'), $default, $options));

    $settings->add(new admin_setting_configtext('offlinequiz/keepfilesfordays', get_string('keepfilesfordays', 'offlinequiz'),
             get_string('configkeepfilesfordays', 'offlinequiz'), 8, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('offlinequiz/experimentalevaluation',
            get_string('configexperimentalevaluation', 'offlinequiz'),
            get_string('configexperimentalevaluationdesc', 'offlinequiz'), 0));

    // If wiris is installed, add the setting to enable it.

    $settings->add(new admin_setting_heading('furtheroptionsheading',
        get_string('furtheroptionsheading', 'offlinequiz'), ''));

    // Enable tabs interface.
    $settings->add(new admin_setting_configcheckbox('offlinequiz/usetabs', get_string('usetabs', 'offlinequiz'),
        get_string('usetabsdesc', 'offlinequiz'), 0));

    if (get_config('wiris_enabled')) {
        $settings->add(new admin_setting_configcheckbox('offlinequiz/wirismathfilter_enabled',
            get_string('wirismathenabled', 'offlinequiz'), get_string('wirismathenabled_help', 'offlinequiz'), 0));
    }
    
    $subplugins = core_component::get_plugin_list('offlinequiz');
    foreach ($subplugins as $subpluginname => $subpluginpath) {
        $settingspath = $subpluginpath . '/settings.php';
        if (file_exists($settingspath)) {
            include($settingspath);
        }
    }
}
