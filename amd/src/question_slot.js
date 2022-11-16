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

/**
 * Render the question slot template for each question in the quiz edit view.
 *
 * @module     mod_offlinequiz/question_slot
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Guillermo Gomez Arias <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as str from 'core/str';

/**
 * Set the question version for the slot.
 *
 * @param {Number} slotId
 * @param {Number} newVersion
 * @return {Array} The modified question version
 */
const setQuestionVersion = (slotId, newVersion) => fetchMany([{
    methodname: 'mod_offlinequiz_set_question_version',
    args: {
        slotid: slotId,
        newversion: newVersion
    }
}])[0];

/**
 * Replace the container with a new version.
 *
 * @param {number} elementslotid the id of the slot
 */
const registerEventListeners = (elementslotid) => {
    document.addEventListener('change', e => {
        if (!e.target.matches('[data-action="mod_offlinequiz-select_slot"][data-slot-id]')) {
            return;
        }

        const slotId = e.target.dataset.slotId;
        const newVersion = parseInt(e.target.value);

        if (elementslotid == slotId) {
            setQuestionVersion(slotId, newVersion)
                .then((response) => {
                    let message = new Object();
                    var langstrings = [
                        {key: 'qversioncannotupdate', component: 'mod_offlinequiz'},
                        {key: 'qversionupdated', component: 'mod_offlinequiz'},
                        {key: 'qversionnumbersdiffer', component: 'mod_offlinequiz'},
                        {key: 'qversionupdatedwarning', component: 'mod_offlinequiz'},
                        {key: 'qversionupdateerror', component: 'mod_offlinequiz'},
                    ];
                    str.get_strings(langstrings).done(function(strings) {
                        if (response.result) { // If the question was updated.
                            // If the number of answers are the same but the forms are already created, we need a warning.
                            if (!response.answersdiffer && !response.canbeedited) {
                                message.title = strings[1];
                                message.body = strings[3];
                            } else {
                                message.title = null;
                            }
                        } else {
                            if (response.answersdiffer && !response.canbeedited) {
                                // If the version was not updated because the numbers of answers differ and the forms are created.
                                message.title = strings[0];
                                message.body = strings[2];
                            } else {
                                if (response.samequestion) {
                                    message.title = null;
                                } else {
                                    // If the version was not updated because of some other error.
                                    message.title = strings[0];
                                    message.body = strings[4];
                                }
                            }
                        }

                        let url = new URL(location.href);
                        url.searchParams.delete('lastchanged');
                        let redirect = url.toString();

                        if (message.title) {
                            ModalFactory.create({
                                type: ModalFactory.types.ALERT,
                                title: message.title,
                                body: message.body
                            }).done(function(modal) {
                                var root = modal.getRoot();
                                root.on(ModalEvents.cancel, function() {
                                    location.href = redirect;
                                });
                                modal.show();
                            });
                        } else {
                            location.href = redirect;
                        }
                    });
                return;
            })
            .catch(Notification.exception);
        }
    });
};

/** @property {Boolean} eventsRegistered If the event has been registered or not */
let eventsRegistered = false;

/**
 * Entrypoint of the js.
 *
 * @param {number} slotid the id of the slot
 */
export const init = (slotid) => {
    if (eventsRegistered) {
        return;
    }

    registerEventListeners(slotid);
};
