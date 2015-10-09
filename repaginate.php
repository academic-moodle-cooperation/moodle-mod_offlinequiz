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
 * Rest endpoint for ajax editing for paging operations on the offlinequiz structure.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/offlinequiz.class.php');

$cmid = required_param('cmid', PARAM_INT);
$offlinequizid = required_param('offlinequizid', PARAM_INT);
$offlinegroupid = required_param('offlinegroupid', PARAM_INT);
$slotnumber = required_param('slot', PARAM_INT);
$repagtype = required_param('repag', PARAM_INT);

require_sesskey();
$offlinequizobj = offlinequiz::create($offlinequizid, $offlinegroupid);
$group = $DB->get_record('offlinequiz_groups', array('id' => $offlinegroupid));

require_login($offlinequizobj->get_course(), false, $offlinequizobj->get_cm());
require_capability('mod/offlinequiz:manage', $offlinequizobj->get_context());

if (offlinequiz_has_scanned_pages($offlinequizid)) {
    $reportlink = offlinequiz_attempt_summary_link_to_reports($offlinequizobj->get_offlinequiz(),
                    $offlinequizobj->get_cm(), $offlinequizobj->get_context());
    throw new \moodle_exception('cannoteditafterattempts', 'offlinequiz',
            new moodle_url('/mod/offlinequiz/edit.php', array('cmid' => $cmid)), $reportlink);
}

$slotnumber++;
$repage = new \mod_offlinequiz\repaginate($offlinequizid, $offlinegroupid);
$repage->repaginate_slots($slotnumber, $repagtype);

offlinequiz_delete_template_usages($offlinequizobj->get_offlinequiz());

$structure = $offlinequizobj->get_structure();
$slots = $structure->refresh_page_numbers_and_update_db($structure->get_offlinequiz());

redirect(new moodle_url('edit.php',
    array('cmid' => $offlinequizobj->get_cmid(),
          'groupnumber' => $group->number)));
