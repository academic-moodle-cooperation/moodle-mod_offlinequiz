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
 * The mod_offlinequiz identifiedformsselector
 *
 * @package   offlinequiz_identified
 * @author    Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @copyright 2023
 * @since     Moodle 4.1
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace offlinequiz_identified;

use html_writer;

defined('MOODLE_INTERNAL') || die();

class identifiedformselector extends \moodleform {
    // Constructor.
    public function __construct($action, $customdata, $method = 'post', $target = '', $attributes = null, $editable = true) {
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }  
    public function definition() {
        global $CFG, $DB;
        $offlinequiz = $this->_customdata['offlinequiz'];
        $cmid = $this->_customdata['id'];
        $sql = "SELECT id, name, listnumber, filename
        FROM {offlinequiz_p_lists}
        WHERE offlinequizid = :offlinequizid
        ORDER BY name ASC";
        $lists = $DB->get_records_sql($sql, array('offlinequizid' => $offlinequiz->id));
        $groups = $DB->get_records(
            'offlinequiz_groups',
            array('offlinequizid' => $offlinequiz->id),
            'groupnumber',
            'groupnumber',
            0,
            $offlinequiz->numgroups
        );
        // Map groups to letters.
        $groupsoptions = [];
        foreach ($groups as $group) {
            $letterstr = "ABCDEFGH"; 
            $letter = $letterstr[$group->groupnumber-1];
            $groupsoptions[] = $letter;
            };
        
        // Map lists to list->name.
        $lists = array_map(function($list) use ($offlinequiz) {
                $alluserids = offlinequizidentified_get_participants($offlinequiz, $list, false);
                $accessuserids = offlinequizidentified_get_participants($offlinequiz, $list, true);
                $numusers = count($alluserids);
                $numaccessusers = count($accessuserids);
                if ($numaccessusers != $numusers) {
                    $listname = $list->name . ' (' . $numaccessusers . '/'. $numusers . ')';
                } else {
                    $listname = $list->name . '(' . $numusers . ')';
                }
                return $listname;
            }, $lists);
        $mform = $this->_form;
        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'identified');
        $mform->setType('mode', PARAM_TEXT);
        $mform->addElement('select', 'groupnumber', get_string('group', 'offlinequiz'), $groupsoptions);
        $mform->setType('groupnumber', PARAM_INT);
        $mform->setDefault('groupnumber', 0);
        $mform->addRule('groupnumber', null, 'required', null, 'client');
        // Check box for nor marking group.
        $mform->addElement('checkbox', 'nogroupmark', get_string('nogroupmark', 'offlinequiz_identified'));
        $mform->setDefault('nogroupmark', 0);
        
        $mform->addElement('select', 'list', get_string('participants', 'offlinequiz'), $lists);
        $mform->setType('list', PARAM_INT);
        $mform->setDefault('list', 0);
        // Check box for only if access.
        $mform->addElement('checkbox', 'onlyifaccess', get_string('onlyifaccess', 'offlinequiz_identified'));
        // Set list required.
        $mform->addRule('list', null, 'required', null, 'client');
        $mform->addElement('submit', 'submitbutton', get_string('submit'));
    }
}