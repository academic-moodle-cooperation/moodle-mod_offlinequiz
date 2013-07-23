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
	attempt = $('#id_attemptclosed').is(":checked");
	correctness = $('#id_correctnessclosed').is(":checked");
	marks = $('#id_marksclosed').is(":checked");
	specificfeedback = $('#id_specificfeedbackclosed').is(":checked");
	generalfeedback = $('#id_generalfeedbackclosed').is(":checked");
	rightanswer = $('#id_rightanswerclosed').is(":checked");
	sheet = $('#id_sheetclosed').is(":checked");
	gradedsheet = $('#id_gradedsheetclosed').is(":checked");

	str = 'attempt='+ attempt + ' correctness=' + correctness + ' marks='+marks+ ' specfeed='+ specificfeedback + ' genfeed='+ generalfeedback +
			' rightanswer='+ rightanswer+ ' sheet=' + sheet + ' gradedsheet=' + gradedsheet + '<br/>';
	
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
		imagefile1 = imagefile1 + "_marks"; 
	}
	imagefile1 = imagefile1 + '.png';

	if (sheet) {
		imagefile2 = imagefile2 + "_sheet.png"; 
	} else if (gradedsheet) {
		imagefile2 = imagefile2 + "_gradedsheet.png"; 
	}

	console.log(str);
	button = $('.Popup').html();
	if (imagefile2 != '') {
		$('<br/>').prependTo($('.Popup'));
		$('<img />').attr({ 'id': 'image2', 'src': imagefile2}).prependTo($('.Popup'));
	}
	
	$('<br/>').prependTo($('.Popup'));
	$('<img />').attr({ 'id': 'image1', 'src': imagefile1}).prependTo($('.Popup'));
	
	$('.Popup').fadeIn("slow");
	$('#overlay').fadeIn("slow");
}

function resetPopup () {
	$('.Popup').fadeOut('slow');
	$('#overlay').fadeOut('slow');
	
	$('.Popup').children('img').remove();
	$('.Popup').children('br').remove();
}