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
 * Add a random question functionality for a popup in offlinequiz editing page.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// eslint-disable-next-line no-redeclare
var CSS = {
    RANDOMQUESTIONFORM: 'div.randomquestionformforpopup',
    PAGEHIDDENINPUT: 'input#rform_qpage',
    RANDOMQUESTIONLINKS: '.menu [data-action="addarandomquestion"]'
};

var PARAMS = {
    PAGE: 'addonpage',
    HEADER: 'header',
    FORM: 'form'
};

var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base, {

    dialogue: function(header) {
        // Create a dialogue on the page and hide it.
        config = {
            headerContent: header,
            bodyContent: Y.one(CSS.RANDOMQUESTIONFORM),
            draggable: true,
            modal: true,
            zIndex: 1000,
            centered: false,
            width: 'auto',
            visible: false,
            postmethod: 'form',
            footerContent: null
        };
        var popup = {dialog: null};
        popup.dialog = new M.core.dialogue(config);
        popup.dialog.show();
    },

    initializer: function() {
        Y.one('body').delegate('click', this.display_dialogue, CSS.RANDOMQUESTIONLINKS, this);
    },

    // eslint-disable-next-line camelcase
    display_dialogue: function(e) {
        e.preventDefault();

        Y.one(CSS.RANDOMQUESTIONFORM + ' ' + CSS.PAGEHIDDENINPUT).set('value',
                e.currentTarget.getData(PARAMS.PAGE));

        this.dialogue(e.currentTarget.getData(PARAMS.HEADER));
    }
});

// eslint-disable-next-line camelcase
M.mod_offlinequiz = M.mod_offlinequiz || {};
M.mod_offlinequiz.randomquestion = M.mod_offlinequiz.randomquestion || {};
M.mod_offlinequiz.randomquestion.init = function() {
    return new POPUP();
};
