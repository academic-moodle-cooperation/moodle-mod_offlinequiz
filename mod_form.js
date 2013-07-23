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
 * JavaScript functions for student view in mod_form.php
 * 
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.4
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function showStudentView() {
	attempt = $('#id_attemptclosed').is(":checked") && !$('#id_attemptclosed').is(":disabled");
	correctness = $('#id_correctnessclosed').is(":checked") && !$('#id_correctnessclosed').is(":disabled");
	marks = $('#id_marksclosed').is(":checked") && !$('#id_marksclosed').is(":disabled");
	specificfeedback = $('#id_specificfeedbackclosed').is(":checked") && !$('#id_specificfeedbackclosed').is(":disabled");
	generalfeedback = $('#id_generalfeedbackclosed').is(":checked") && !$('#id_generalfeedbackclosed').is(":disabled");
	rightanswer = $('#id_rightanswerclosed').is(":checked") && !$('#id_rightanswerclosed').is(":disabled");
	sheet = $('#id_sheetclosed').is(":checked") && !$('#id_sheetclosed').is(":disabled");
	gradedsheet = $('#id_gradedsheetclosed').is(":checked") && !$('#id_gradedsheetclosed').is(":disabled");

//	str = 'attempt='+ attempt + ' correctness=' + correctness + ' marks='+marks+ ' specfeed='+ specificfeedback + ' genfeed='+ generalfeedback +
//			' rightanswer='+ rightanswer+ ' sheet=' + sheet + ' gradedsheet=' + gradedsheet + '<br/>';
//	
//	console.log(str);
	baseurl = $('#basefilename').val();
	imagefile1 = baseurl;
	imagefile2 = '';
	if (sheet || gradedsheet) {
		imagefile2 = baseurl;
	}
	
	if (attempt) {
		imagefile1 = imagefile1 + "_attempt"; 
	}
	if (correctness) {
		imagefile1 = imagefile1 + "_correctness"; 
	}
	if (marks) {
		imagefile1 = imagefile1 + "_marks"; 
	}
	if (specificfeedback) {
		imagefile1 = imagefile1 + "_specific"; 
	}
	if (generalfeedback) {
		imagefile1 = imagefile1 + "_general"; 
	}
	if (rightanswer) {
		imagefile1 = imagefile1 + "_rightanswer"; 
	}
	imagefile1 = imagefile1 + '.png';

	if (gradedsheet) {
		imagefile2 = imagefile2 + "_gradedsheet.png"; 
	} else if (sheet) {
		imagefile2 = imagefile2 + "_sheet.png"; 
	}

	$('<img />').attr({ 'id': 'image1', 'width': '100%', 'src': imagefile1}).appendTo($('.Popup'));
	$('<br/>').appendTo($('.Popup'));

	if (imagefile2 != '') {
        $('<hr/>').appendTo($('.Popup'));
		$('<img />').attr({ 'id': 'image2', 'width': '100%', 'src': imagefile2}).appendTo($('.Popup'));
		$('<br/>').appendTo($('.Popup'));
	}

	$('.Popup').fadeIn("slow");
	$('#overlay').fadeIn("slow");
}

function resetPopup () {
	$('.Popup').fadeOut('slow');
	$('#overlay').fadeOut('slow');
	
	$('.Popup').children('img').remove();
	$('.Popup').children('br').remove();
	$('.Popup').children('hr').remove();	
}