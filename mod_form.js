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
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
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

	baseurl = $('#basefilename').val();
	imagefile1 = '';
	imagefile2 = '';
	imagefile3 = '';
	pagefile = '';
	if (sheet || gradedsheet) {
		imagefile1 = baseurl + '1';
		pagefile = baseurl;
	}

	if (attempt) {
		imagefile1 = baseurl + '1';
		imagefile2 = baseurl + '2';
	}
	if (correctness) {
		imagefile2 = imagefile2 + "_correctness";
	}
	if (marks) {
		imagefile1 = baseurl + '1';
		imagefile1 = imagefile1 + "_marks";
		if (imagefile2 != '') {
			imagefile2 = imagefile2 + "_marks";
		}
	}
	if (specificfeedback) {
		imagefile2 = imagefile2 + "_specific";
        imagefile3 = baseurl + '3';
		imagefile3 = imagefile3 + "_specific";
	}
	if (generalfeedback) {
        if (imagefile3 == '') {
        	imagefile3 = baseurl + '3';
        }
		imagefile3 = imagefile3 + "_general";
    }
	if (rightanswer) {
		if (imagefile2 == '') {
			imagefile2 = baseurl + '2';
		}
        if (imagefile3 == '') {
        	imagefile3 = baseurl + '3';
        }
		imagefile3 = imagefile3 + "_rightanswer";
	}

	if (gradedsheet) {
		imagefile1 = imagefile1 + "_pagelink";
		pagefile = pagefile + "_gradedsheet.png";
	} else if (sheet) {
		imagefile1 = imagefile1 + "_pagelink";
		pagefile = pagefile + "_sheet.png";
	}

	if (imagefile1 != '') {
		imagefile1 = imagefile1 + '.png';
	}
	if (imagefile2 != '') {
		imagefile2 = imagefile2 + '.png';
	}
	if (imagefile3 != '') {
		imagefile3 = imagefile3 + '.png';
	}

	if (imagefile1 != '') {
		$('<img />').attr({ 'id': 'image1', 'width': '100%', 'src': imagefile1}).appendTo($('.Popup'));
		$('<br/>').appendTo($('.Popup'));
	}

	if (imagefile2 != '') {
		$('<img />').attr({ 'id': 'image2', 'width': '100%', 'src': imagefile2}).appendTo($('.Popup'));
		$('<br/>').appendTo($('.Popup'));
	}

	if (imagefile3 != '') {
		$('<img />').attr({ 'id': 'image3', 'width': '100%', 'src': imagefile3}).appendTo($('.Popup'));
		$('<br/>').appendTo($('.Popup'));
	}

	if (pagefile != '') {
        $('<hr/>').appendTo($('.Popup'));
		$('<img />').attr({ 'id': 'image4', 'width': '100%', 'src': pagefile}).appendTo($('.Popup'));
		$('<br/>').appendTo($('.Popup'));
	}

	$('.Popup').fadeIn("slow");
	$('#overlay').fadeIn("slow");
}

function closePopup () {
	$('.Popup').fadeOut('slow');
	$('#overlay').fadeOut('slow');

	$('.Popup').children('img').remove();
	$('.Popup').children('br').remove();
	$('.Popup').children('hr').remove();
}

// Catch ESC key to close popup.
$(document).keyup(function(e) {
	  if (e.keyCode == 27) {
		  closePopup();
      }   // esc
	});

// Close popup when user clicks on overlay
$("#overlay").click(function(e){
	  if ($('.Popup').is(':visible')) {
		  closePopup();
	  }
});

// Prevent events from getting pass .popup
$(".Popup").click(function(e){
  e.stopPropagation();
});
