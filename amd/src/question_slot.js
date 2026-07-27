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
 * Render the question slot template for each question in the offlinequiz edit view.
 *
 * @module     mod_offlinequiz/question_slot
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Guillermo Gomez Arias <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import {Alert} from 'core/modal';
import * as str from 'core/str';

/**
 * Set the question version for the slot.
 *
 * @param {Number} slotId
 * @param {Number} newVersion
 * @param {Boolean} canBeEdited Whether the forms were already created
 * @return {Promise}
 */
const setQuestionVersion = (slotId, newVersion, canBeEdited) => fetchMany([{
    methodname: 'mod_offlinequiz_set_question_version',
    args: {
        slotid: slotId,
        newversion: newVersion,
        canbeedited: canBeEdited
    },
}])[0];

const registerEventListeners = (canBeEdited) => {
    document.addEventListener('change', async(e) => {
        const target = e.target;

        if (!target.matches('[data-action="mod_offlinequiz-select_slot"][data-slot-id]')) {
            return;
        }

        try {
            const slotId = target.dataset.slotId;
            const newVersion = Number(target.value);

            const response = await setQuestionVersion(slotId, newVersion, canBeEdited);

            const strings = await str.get_strings([
                {key: 'qversioncannotupdate', component: 'mod_offlinequiz'},
                {key: 'qversionupdated', component: 'mod_offlinequiz'},
                {key: 'qversionnumbersdiffer', component: 'mod_offlinequiz'},
                {key: 'qversionupdatedwarning', component: 'mod_offlinequiz'},
                {key: 'qversionupdateerror', component: 'mod_offlinequiz'}
            ]);

            let title = null;
            let body = null;

            if (response.result) {
                // Updated successfully.
                if (!response.answersdiffer && !canBeEdited) {
                    title = strings[1];
                    body = strings[3];
                }
            } else if (response.answersdiffer && !canBeEdited) {
                // Update prevented because answer count differs.
                title = strings[0];
                body = strings[2];
            } else {
                // Generic update error.
                title = strings[0];
                body = strings[4];
            }

            if (title) {
                const modal = await Alert.create({
                    title,
                    body,
                });

                modal.getRoot().on('hidden.bs.modal', () => {
                    window.location.reload();
                });

                modal.show();
            } else {
                window.location.reload();
            }
        } catch (error) {
            Notification.exception(error);
        }
    });
};

let eventsRegistered = false;

/**
 * Entrypoint.
 *
 * @param {number} slotid
 * @param {boolean} canBeEdited
 */
export const init = (slotid, canBeEdited) => {
    if (eventsRegistered) {
        return;
    }

    eventsRegistered = true;
    registerEventListeners(canBeEdited);
};