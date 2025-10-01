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
 * Data generator class
 *
 * @package    mod_offlinequiz
 * @category   test
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_offlinequiz_generator extends testing_module_generator {

    /**
     * Creates an instance of the module for testing purposes.
     *
     * Module type will be taken from the class name.
     *
     * @param array|stdClass $record data for module being generated. Requires 'course' key
     *     (an id or the full object). Also can have any fields from add module form.
     * @param null|array $options general options for course module, can be merged into $record
     * @return stdClass record from module-defined table with additional field
     *     cmid (corresponding id in course_modules table)
     */
    public function create_offlinequiz($record = null, ?array $options = null): stdClass {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/offlinequiz/locallib.php');
        $record = (object)(array)$record;

        $iddigits = get_config('offlinequiz', 'ID_digits');
        $record->course = $DB->get_field('course', 'id', ['shortname' => $record->course] );

        $defaultofflinequizsettings = [
            'pdfintro'               => '',
            'timeopen'               => 0,
            'timeclose'              => 0,
            'time'                   => 0,
            'grade'                  => 0,
            'numgroups'              => 1,
            'decimalpoints'          => 2,
            'questionsperpage'       => 0,
            'docscreated'            => 0,
            'shufflequestions'       => 0,
            'shuffleanswers'         => 0,
            'printstudycodefield'    => 1,
            'papergray'              => 670,
            'fontsize'               => 10,
            'fileformat'             => 0,
            'showquestioninfo'       => 0,
            'showgrades'             => 0,
            'showtutorial'           => 0,
            'iddigits'               => $iddigits,
            'disableimgnewlines'     => 1,
            'algorithmversion'       => 0,
            'experimentalevaluation' => 0,
            'participantsusage'      => 1,
            'pdffont'                => 'freesans',
        ];

        foreach ($defaultofflinequizsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }
        if (is_string($record->fileformat) ) {
            $record->fileformat = $this->convert_file_format($record->fileformat);
        }

        if (isset($record->gradepass)) {
            $record->gradepass = unformat_float($record->gradepass);
        }
        return parent::create_instance($record, $options);
    }

    /**
     * convert "DOCX" into its int value
     * @param string $fileformat
     */
    private function convert_file_format(string $fileformat) {
        $varname = 'OFFLINEQUIZ_' . $fileformat . '_FORMAT';
        if (defined($varname)) {
            return constant($varname);
        } else {
            return 0;
        }
    }
}
