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
 * Renderer outputting the offlinequiz editing UI.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at
 * @copyright     2025 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 5.0+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_offlinequiz\output;

class action_api {
    public static function insert_all_actions($sourceplugin, $sourcepage , $cm, $offlinequiz) {
        $subplugins = \core_component::get_plugin_list('offlinequiz');
        $html = '';
        foreach ($subplugins as $subplugin => $subpluginpath) {
            // Instantiate the subplugin.
            $reportclass = offlinequiz_instantiate_report_class($subplugin);
            if ($reportclass && method_exists($reportclass, 'get_actions_html')) {
                $html .= $reportclass->get_actions_html($sourceplugin, $sourcepage, $cm, $offlinequiz);
            }
        }
        return $html;
    } 
}