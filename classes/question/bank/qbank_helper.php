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

namespace mod_offlinequiz\question\bank;

use core_question\local\bank\question_version_status;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for question bank and its associated data.
 *
 * @package    mod_offlinequiz
 * @category   question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbank_helper {

    /**
     * Get the available versions of a question where one of the version has the given question id.
     *
     * @param int $questionid id of a question.
     * @return \stdClass[] other versions of this question. Each object has fields versionid,
     *       version and questionid. Array is returned most recent version first.
     */
    public static function get_version_options(int $questionid): array {
        global $DB;

        return $DB->get_records_sql("
                SELECT allversions.id AS versionid,
                       allversions.version,
                       allversions.questionid

                  FROM {question_versions} allversions

                 WHERE allversions.questionbankentryid = (
                            SELECT givenversion.questionbankentryid
                              FROM {question_versions} givenversion
                             WHERE givenversion.questionid = ?
                       )
                   AND allversions.status <> ?

              ORDER BY allversions.version DESC
              ", [$questionid, question_version_status::QUESTION_STATUS_DRAFT]);
    }
}
