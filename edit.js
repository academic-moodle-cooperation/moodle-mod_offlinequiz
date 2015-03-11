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
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.4
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Initialise everything on the offlinequiz edit/order and paging page.
var offlinequiz_edit = {};
function offlinequiz_edit_init(Y) {
    M.core_scroll_manager.scroll_to_saved_pos(Y);
    Y.on('submit', function(e) {
            M.core_scroll_manager.save_scroll_pos(Y, 'id_existingcategory');
        }, '#mform1');
    Y.on('submit', function(e) {
            M.core_scroll_manager.save_scroll_pos(Y, e.target.get('firstChild'));
        }, '.offlinequizsavegradesform');

    // Add random question dialogue --------------------------------------------
    var randomquestiondialog = Y.YUI2.util.Dom.get('randomquestiondialog');
    if (randomquestiondialog) {
        Y.YUI2.util.Dom.get(document.body).appendChild(randomquestiondialog);
    }

    offlinequiz_edit.randomquestiondialog = new Y.YUI2.widget.Dialog('randomquestiondialog', {
            modal: true,
            width: '100%',
            iframe: true,
            zIndex: 1000, // zIndex must be way above 99 to be above the active offlinequiz tab
            fixedcenter: true,
            visible: false,
            close: true,
            constraintoviewport: true,
            postmethod: 'form'
    });
    offlinequiz_edit.randomquestiondialog.render();
    var div = document.getElementById('randomquestiondialog');
    if (div) {
        div.style.display = 'block';
    }

    // Show the form on button click.
    Y.YUI2.util.Event.addListener(offlinequiz_edit_config.dialoglisteners, 'click', function(e) {
        // Transfer the page number from the button form to the pop-up form.
        var addrandombutton = Y.YUI2.util.Event.getTarget(e);
        var addpagehidden = Y.YUI2.util.Dom.getElementsByClassName('addonpage_formelement', 'input', addrandombutton.form);
        document.getElementById('rform_qpage').value = addpagehidden[0].value;

        // Show the dialogue and stop the default action.
        offlinequiz_edit.randomquestiondialog.show();
        Y.YUI2.util.Event.stopEvent(e);
    });

    // Make escape close the dialogue.
    offlinequiz_edit.randomquestiondialog.cfg.setProperty('keylisteners', [new Y.YUI2.util.KeyListener(
            document, {keys:[27]}, function(types, args, obj) { offlinequiz_edit.randomquestiondialog.hide();
    })]);

    // Make the form cancel button close the dialogue.
    Y.YUI2.util.Event.addListener('id_cancel', 'click', function(e) {
        offlinequiz_edit.randomquestiondialog.hide();
        Y.YUI2.util.Event.preventDefault(e);
    });

    Y.YUI2.util.Event.addListener('id_existingcategory', 'click', offlinequiz_yui_workaround);

    Y.YUI2.util.Event.addListener('id_newcategory', 'click', offlinequiz_yui_workaround);

    // Repaginate dialogue -----------------------------------------------------
    offlinequiz_edit.repaginatedialog = new Y.YUI2.widget.Dialog('repaginatedialog', {
            modal: true,
            width: '30em',
            iframe: true,
            zIndex: 1000,
            context: ['repaginatecommand', 'tr', 'br', ['beforeShow']],
            visible: false,
            close: true,
            constraintoviewport: true,
            postmethod: 'form'
    });
    offlinequiz_edit.repaginatedialog.render();
    offlinequiz_edit.randomquestiondialog.render();
    var div = document.getElementById('repaginatedialog');
    if (div) {
        div.style.display = 'block';
    }

    // Show the form on button click.
    Y.YUI2.util.Event.addListener('repaginatecommand', 'click', function() {
        offlinequiz_edit.repaginatedialog.show();
    });

    // Reposition the dialogue when the window resizes. For some reason this was not working automatically.
    Y.YUI2.widget.Overlay.windowResizeEvent.subscribe(function() {
      offlinequiz_edit.repaginatedialog.cfg.setProperty('context', ['repaginatecommand', 'tr', 'br', ['beforeShow']]);
    });

    // Make escape close the dialogue.
    offlinequiz_edit.repaginatedialog.cfg.setProperty('keylisteners', [new Y.YUI2.util.KeyListener(
            document, {keys:[27]}, function(types, args, obj) { offlinequiz_edit.repaginatedialog.hide();
    })]);

    // Nasty hack, remove once the YUI bug causing MDL-17594 is fixed.
    // https://sourceforge.net/tracker/index.php?func=detail&aid=2493426&group_id=165715&atid=836476
    var elementcauseinglayoutproblem = document.getElementById('_yuiResizeMonitor');
    if (elementcauseinglayoutproblem) {
        elementcauseinglayoutproblem.style.left = '0px';
    }
}

function offlinequiz_yui_workaround(e) {
YUI().use('yui2-event', function(Y) {
    // YUI does not send the button pressed with the form submission, so copy
    // the button name to a hidden input.
    var submitbutton = Y.YUI2.util.Event.getTarget(e);
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = submitbutton.name;
    input.value = 1;
    submitbutton.form.appendChild(input);
});
}

// Initialise everything on the offlinequiz settings form.
function offlinequiz_settings_init(Y) {
    var repaginatecheckbox = document.getElementById('id_repaginatenow');
    if (!repaginatecheckbox) {
        // This checkbox does not appear on the create new offlinequiz form.
        return;
    }
    var qppselect = document.getElementById('id_questionsperpage');
    var qppinitialvalue = qppselect.value;
    Y.YUI2.util.Event.addListener([qppselect, 'id_shufflequestions'] , 'change', function() {
        setTimeout(function() { // Annoyingly, this handler runs before the formlib disabledif code, hence the timeout.
            if (!repaginatecheckbox.disabled) {
                repaginatecheckbox.checked = qppselect.value != qppinitialvalue;
            }
        }, 50);
    });
}
