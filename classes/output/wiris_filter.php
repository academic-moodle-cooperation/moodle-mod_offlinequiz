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
//

/**
 * It is a filter that allows to visualize formulas generated with
 * MathType image service.
 * Modified by Juan Pablo de Castro to force the server-side rendering.
 *
 * Replaces all substrings '«math ... «/math»' '<math ... </math>'
 * generated with MathType by the corresponding image.
 *
 * @package    mod_offlinequiz
 * @subpackage output
 * @author Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @copyright  WIRIS Europe (Maths for more S.L)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace  mod_offlinequiz\output;


defined('MOODLE_INTERNAL') || die();

// Disable this class if wiris does not exist in the system.
if (!class_exists('filter_wiris\text_filter')) {
    return;
}
// Import PHP 'subfilter'.
use filter_wiris\subfilters\filter_wiris_php;

require_once($CFG->dirroot . "/filter/wiris/subfilters/php.php");

/**
 * Class wiris_filter
 * This class extends the moodle_text_filter and provides a method to filter text using the Wiris filter.
 */
class wiris_filter extends \core_filters\text_filter {
    /**
     * wiris subfilter
     * @var filter_wiris_php
     */
    private $subfilter;
    /**
     * Constructor for the wiris_filter class.
     *
     * @param \context $context The context in which the filter is applied.
     * @param array $localconfig The local configuration for the filter.
     */
    public function __construct($context, $localconfig) {
        parent::__construct($context, $localconfig);
        // Server-sider render: Uses the PHP third-party lib (default).
        $this->subfilter = new filter_wiris_php($this->context, $this->localconfig);
    }
    /**
     * Filters the given text using the Wiris filter.
     *
     * @param  string $text    The text to be filtered.
     * @param  array  $options An array of options for the filter (optional).
     * @return string The filtered text.
     */
    public function filter($text, array $options = []) {
        // Our custom Haxe-transpiled EReg was obsolete, so we have to do a replacement that used to happen in
        // filterMath here instead.
        // This fixes the xmlns=¨http://www.w3.org/1998/Math/MathML¨ being converted into a link by the
        // "Convert URLs into links and images" filter by Moodle when it is applied before the Wiris filter.
        // Looks for every SafeXML instance, and within there, removes the surrounding <a>...</a>.
        $text = preg_replace_callback(
            '/«math.*?«\\/math»/',
            function ($matches) {
                return preg_replace('/<a href="[^\"]*"[^>]*>([^<]*)<\\/a>|<a href="[^\"]*">/', '$1', $matches[0]);
            },
            $text
        );

        $text = $this->subfilter->filter($text, $options);

        return $text;
    }
}
