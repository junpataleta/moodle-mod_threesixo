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
 * ES module for the frequently used comments chooser for the marking guide grading form.
 *
 * @module     mod_threesixo/view
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Ajax from 'core/ajax';
import {getString} from 'core/str';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';

/**
 * List of action selectors.
 *
 * @type {{VIEW_FEEDBACK: string, DECLINE_FEEDBACK: string}}
 */
const ACTIONS = {
    VIEW_FEEDBACK: '[data-action="view-feedback"]',
    DECLINE_FEEDBACK: '[data-action="decline-feedback"]',
    UNDO_DECLINE: '[data-action="undo-decline"]'
};

let threesixtyid;

/**
 * Refresh the list of participants.
 */
async function refreshParticipantsList() {
    // Refresh the list of participants thru AJAX.
    const promises = Ajax.call([
        {methodname: 'mod_threesixo_data_for_participant_list', args: {threesixtyid: threesixtyid}}
    ]);
    const response = await promises[0];
    const compiledSource = await Templates.render('mod_threesixo/list_participants', response);
    const participantList = document.querySelector('[data-region="participantlist"]');
    if (participantList) {
        participantList.outerHTML = compiledSource;
    }
}

const view = function(id) {
    threesixtyid = id;
    this.registerEvents();
};

view.prototype.registerEvents = function() {
    document.addEventListener('click', async(e) => {
        const declineButton = e.target.closest(ACTIONS.DECLINE_FEEDBACK);
        if (declineButton) {
            e.preventDefault();

            const statusid = declineButton.dataset.statusid;
            const name = declineButton.dataset.name;
            const context = {
                statusid: statusid,
                name: name
            };
            const declineTemplatePromise = Templates.render('mod_threesixo/decline_feedback', context);
            const titlePromise = getString('declinefeedback', 'mod_threesixo');

            const [title, body] = await Promise.all([titlePromise, declineTemplatePromise]);
            const modal = await ModalSaveCancel.create({
                title: title,
                body: body,
                large: true
            });

            // Display the dialogue.
            modal.show();

            // On hide handler.
            modal.getRoot().on(ModalEvents.hidden, function() {
                // Destroy modal when hidden.
                modal.destroy();
            });

            modal.getRoot().on(ModalEvents.save, async() => {
                const statusid = document.getElementById("decline-statusid").value;
                const reason = document.getElementById("decline-reason").value.trim();
                const data = {
                    statusid: statusid,
                    declinereason: reason
                };

                const method = 'mod_threesixo_decline_feedback';

                // Refresh the list of questions thru AJAX.
                await Ajax.call([
                    {methodname: method, args: data}
                ])[0];
                await refreshParticipantsList();
            });
        }

        const undoButton = e.target.closest(ACTIONS.UNDO_DECLINE);
        if (undoButton) {
            e.preventDefault();

            const statusid = undoButton.dataset.statusid;
            const data = {
                statusid: statusid
            };

            const method = 'mod_threesixo_undo_decline';

            // Refresh the list of questions thru AJAX.
            await Ajax.call([
                {methodname: method, args: data}
            ])[0];
            await refreshParticipantsList();
        }
    });
};

export default view;
