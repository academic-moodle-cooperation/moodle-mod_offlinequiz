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
    attempt = document.getElementById('id_attemptclosed').checked && !document.getElementById('id_attemptclosed').disabled;
    correctness = document.getElementById('id_correctnessclosed').checked && !document.getElementById('id_correctnessclosed').disabled;
    marks = document.getElementById('id_marksclosed').checked && !document.getElementById('id_marksclosed').disabled;
    specificfeedback = document.getElementById('id_specificfeedbackclosed').checked && !document.getElementById('id_specificfeedbackclosed').disabled;
    generalfeedback = document.getElementById('id_generalfeedbackclosed').checked && !document.getElementById('id_generalfeedbackclosed').disabled;
    rightanswer = document.getElementById('id_rightanswerclosed').checked && !document.getElementById('id_rightanswerclosed').disabled;
    sheet = document.getElementById('id_sheetclosed').checked && !document.getElementById('id_sheetclosed').disabled;
    gradedsheet = document.getElementById('id_gradedsheetclosed').checked && !document.getElementById('id_gradedsheetclosed').disabled;

    baseurl = document.getElementById('basefilename').value;
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

    const popup = document.querySelector('.Popup');
    const overlay = document.querySelector('#overlay');

    if (imagefile1 != '') {
        var img = document.createElement('img');
        img.id = 'image1';
        img.style.width = '100%';
        img.src = imagefile1;
        var br = document.createElement('br');
        popup.append(img, br);
    }

    if (imagefile2 != '') {
        var img = document.createElement('img');
        img.id = 'image2';
        img.style.width = '100%';
        img.src = imagefile2;
        var br = document.createElement('br');
        popup.append(img, br);
    }

    if (imagefile3 != '') {
        var img = document.createElement('img');
        img.id = 'image3';
        img.style.width = '100%';
        img.src = imagefile3;
        var br = document.createElement('br');
        popup.append(img, br);
    }

    if (pagefile != '') {


        var hr = document.createElement('hr');
        popup.appendChild(hr);

        var img = document.createElement('img');
        img.id = 'image4';
        img.style.width = '100%';  // Use style for percentage-based width
        img.src = pagefile;
        popup.appendChild(img);

        var br = document.createElement('br');
        popup.appendChild(br);
    }

    popup.classList.add('fade', 'show');
    overlay.classList.add('fade', 'show');
}

function closePopup() {
    const popup = document.querySelector('.Popup');
    const overlay = document.querySelector('#overlay');
    
    popup.classList.add('fade-out');
    overlay.classList.add('fade-out','hide');
    popup.classList.remove('fade', 'show');
    overlay.classList.remove('fade', 'show');
    
    popup.addEventListener('transitionend', () => {
          popup.classList.add('hidden');
    });
    overlay.addEventListener('transitionend', () => {
          overlay.classList.add('hidden');
    });
    
    while (child = popup.querySelector('img, br, hr')) {
        popup.removeChild(child);
    }
}

function closePopupOnEsc(e) {
    if (e.keyCode == 27) {
        closePopup();
    }
}



// Catch ESC key to close popup.
addEventListener("keyup", closePopupOnEsc);

document.getElementById('overlay').addEventListener('click', function closePopupOnClick() {
    if (document.getElementsByClassName('Popup')[0].visible) {
        closePopup();
    }
})

document.getElementsByClassName('Popup')[0].addEventListener('click', function(e) {
    e.stopPropagation();
});