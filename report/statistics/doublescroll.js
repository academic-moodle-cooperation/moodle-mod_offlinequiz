// This file is for Moodle - http://moodle.org/
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
 * JavaScript library for the offlinequiz module editing interface.
 * 
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.4
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Initialise double scrollbar on the offlinequiz statistics table.
var offlinequiz_statistics = {};

function offlinequiz_statistics_init_doublescroll(Y) {
    jQuery(document).ready(function($) {
		$('#tablecontainer > div.no-overflow').doubleScroll();
	});

	$(window).resize(function() {
		width = $('#tablecontainer > div.no-overflow').width();
		$('div.suwala-doubleScroll-scroll-wrapper').width(width);
	});

//	fxheaderInit('questionstatistics', 380, 1, 0);
//	fxheader();
}

function offlinequiz_statistics_init_fxheader(Y) {
	fxheaderInit('questionstatistics', 320, 1, 0);
	fxheader();
}