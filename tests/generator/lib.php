<?php

defined('MOODLE_INTERNAL') || die();

class mod_offlinequiz_generator extends testing_module_generator {

    /**
     * Generator method creating a mod_offlinequiz instance.
     *
     *
     * @param array|stdClass $record (optional) Named array containing instance settings
     * @param array $options (optional) general options for course module. Can be merged into $record
     * @return stdClass record from module-defined table with additional field cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        $timecreated = time();

        $defaultsettings = [
            'name' => 'Offlinequiz',
            'intro' => 'Introtext',
            'introformat' => 1,
            'pdfintro' => 'pdf intro text',
            'timeopen' => 0,
            'timeclose' => 0,
            'time' => 0,
            'grade' => 100.0,
            'numgroups' => 1,
            'decimalpoints' => 2,
            'review' => 0,
            'questionsperpage' => 0,
            'docscreated' => 0,
            'shufflequestions' => 0,
            'shuffleanswers' => 0,
            'printstudycodefield' => 1,
            'papergray' => 670,
            'fontsize' => 10,
            'timecreated' => $timecreated,
            'showquestioninfo' => 0,
            'timemodified' => $timecreated,
            'fileformat' => 0,
            'showgrades' => 0,
            'showtutorial' => 0,
            'id_digits' => 8,
            'disableimgnewlines' => 0,
            'algorithmversion' => 0,
            'experimentalevaluation' => 0,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
