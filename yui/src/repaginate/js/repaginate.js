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
 * Repaginate functionality for a popup in offlinequiz editing page.
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
    REPAGINATECONTAINERCLASS: '.rpcontainerclass',
    REPAGINATECOMMAND: '#repaginatecommand'
};

var PARAMS = {
    CMID: 'cmid',
    HEADER: 'header',
    FORM: 'form'
};

var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base, {
    header: null,
    body: null,

    initializer: function() {
        var rpcontainerclass = Y.one(CSS.REPAGINATECONTAINERCLASS);

        // Set popup header and body.
        this.header = rpcontainerclass.getAttribute(PARAMS.HEADER);
        this.body = rpcontainerclass.getAttribute(PARAMS.FORM);
        Y.one(CSS.REPAGINATECOMMAND).on('click', this.display_dialog, this);
    },

    // eslint-disable-next-line camelcase
    display_dialog: function(e) {
        e.preventDefault();

        // Configure the popup.
        var config = {
            headerContent: this.header,
            bodyContent: this.body,
            draggable: true,
            modal: true,
            zIndex: 1000,
            context: [CSS.REPAGINATECOMMAND, 'tr', 'br', ['beforeShow']],
            centered: false,
            width: '30em',
            visible: false,
            postmethod: 'form',
            footerContent: null
        };

        var popup = {dialog: null};
        popup.dialog = new M.core.dialogue(config);
        popup.dialog.show();
    }
});

// eslint-disable-next-line camelcase
M.mod_offlinequiz = M.mod_offlinequiz || {};
M.mod_offlinequiz.repaginate = M.mod_offlinequiz.repaginate || {};
M.mod_offlinequiz.repaginate.init = function() {
    return new POPUP();
};
