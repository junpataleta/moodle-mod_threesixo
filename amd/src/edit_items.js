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
 * AMD code for the frequently used comments chooser for the marking guide grading form.
 *
 * @module     mod_threesixo/edit_items
 * @class      edit_items
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import * as Templates from 'core/templates';
import * as Notification from 'core/notification';
import Ajax from 'core/ajax';
import * as Bank from 'mod_threesixo/question_bank';
import {eventTypes, notifyItemsUpdated} from 'mod_threesixo/events';
import {add as addToast} from 'core/toast';
import {get_string as getString} from "core/str";
import Pending from 'core/pending';

/**
 * List of action selectors.
 *
 * @type {{DELETE: string, MOVE_UP: string, MOVE_DOWN: string}}
 */
const ACTIONS = {
    DELETE: '[data-action="delete-item"]',
    MOVE_UP: '[data-action="move-item-up"]',
    MOVE_DOWN: '[data-action="move-item-down"]',
    PICK_QUESTION: '#btn-question-bank',
};

export default class EditItems {
    threesixtyId;

    constructor(threesixtyid) {
        this.threesixtyId = threesixtyid;
        this.registerEvents();
    }

    refreshItemList() {
        const pending = new Pending('mod_threesixo/refreshItems');
        const editItems = this;
        const promises = Ajax.call([
            {
                methodname: 'mod_threesixo_get_items',
                args: {
                    threesixtyid: editItems.threesixtyId
                }
            }
        ]);
        promises[0].then(function(response) {
            const context = {
                threesixtyid: editItems.threesixtyId
            };

            const items = [];
            const itemCount = response.items.length;
            response.items.forEach((value) => {
                const item = value;
                item.deletebutton = true;
                item.moveupbutton = false;
                item.movedownbutton = false;
                item.type = value.typetext;
                if (itemCount > 1) {
                    if (value.position === 1) {
                        item.movedownbutton = true;
                    } else if (value.position === itemCount) {
                        item.moveupbutton = true;
                    } else if (value.position > 1 && value.position < itemCount) {
                        item.moveupbutton = true;
                        item.movedownbutton = true;
                    }
                }
                items.push(item);
            });
            context.allitems = items;

            return Templates.render('mod_threesixo/list_360_items', context);
        }).then((compiledSource) => {
            document.querySelector('[data-region="itemlist"]').outerHTML = compiledSource;
            return pending.resolve();
        }).catch(Notification.exception);
    }

    callItemAction(action, itemId, successMessage) {
        const threesixtyId = this.threesixtyId;
        const promises = Ajax.call([
            {
                methodname: action,
                args: {
                    itemid: itemId
                }
            }
        ]);
        promises[0].then((response) => {
            if (response.result) {
                return successMessage;
            }
            const warnings = response.warnings.join($('<br/>'));
            throw new Error(warnings);
        }).then((message) => {
            notifyItemsUpdated(threesixtyId);
            return addToast(message, {});
        }).catch(Notification.exception);
    }

    registerEvents() {
        const editItems = this;

        document.addEventListener('click', e => {
            let actionButton = '';
            let action = '';
            let successMessage = '';
            if (e.target.closest(ACTIONS.DELETE)) {
                actionButton = e.target.closest(ACTIONS.DELETE);
                action = 'mod_threesixo_delete_item';
                successMessage = getString('itemdeleted', 'mod_threesixo');
            } else if (e.target.closest(ACTIONS.MOVE_UP)) {
                actionButton = e.target.closest(ACTIONS.MOVE_UP);
                action = 'mod_threesixo_move_item_up';
                successMessage = getString('itemmovedup', 'mod_threesixo');
            } else if (e.target.closest(ACTIONS.MOVE_DOWN)) {
                actionButton = e.target.closest(ACTIONS.MOVE_DOWN);
                action = 'mod_threesixo_move_item_down';
                successMessage = getString('itemmoveddown', 'mod_threesixo');
            } else if (e.target.closest(ACTIONS.PICK_QUESTION)) {
                e.preventDefault();

                Bank.init(editItems.threesixtyId);
            }

            if (action) {
                e.preventDefault();

                // Remove the tooltip markup from the DOM. For some reason calling .tooltip('dispose') mucks Behat tests.
                const tooltipId = actionButton.getAttribute('aria-describedby');
                if (tooltipId) {
                    const tooltip = document.querySelector(`#${tooltipId}`);
                    if (tooltip) {
                        tooltip.remove();
                    }
                }

                const itemId = actionButton.dataset.itemid;
                editItems.callItemAction(action, itemId, successMessage);
            }
        });

        document.addEventListener(eventTypes.itemsUpdated, function() {
            editItems.refreshItemList();
        });
    }
}
