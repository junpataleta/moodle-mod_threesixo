// This file is part of Moodle - http://moodle.org/ //
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
 * Javascript events for the `mod_threesixo`.
 *
 * @module     mod_threesixo/events
 * @copyright  2023 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @example <caption>Example of listening to a mod_threesixo event.</caption>
 * import {eventTypes as threesixoEventTypes} from 'core_threesixo/events';
 *
 * document.addEventListener(threesixoEventTypes.itemsUpdated, e => {
 *     window.console.log(e.target); // The HTMLElement relating to the block whose content was updated.
 *     window.console.log(e.detail.instanceId); // The instanceId of the block that was updated.
 * });
 */

import {dispatchEvent} from 'core/event_dispatcher';

/**
 * Events for `mod_threesixo`.
 *
 * @constant
 * @property {String} itemsUpdated See {@link event:itemsUpdated}
 */
export const eventTypes = {
    /**
     * An event triggered when the items for a 360-degree feedback instance have been updated.
     *
     * @event itemsUpdated
     * @type {CustomEvent}
     * @property {HTMLElement} target The block element that was updated
     * @property {object} detail
     * @property {number} detail.instanceId The block instance id
     */
    itemsUpdated: 'mod_threesixo/itemsUpdated',
};

/**
 * Trigger an event to indicate that the content of a block was updated.
 *
 * @method notifyItemsUpdated
 * @param {Number} threesixtyId The 360-degree feedback instance ID.
 * @returns {itemsUpdated}
 * @fires itemsUpdated
 */
export const notifyItemsUpdated = threesixtyId => dispatchEvent(
    eventTypes.itemsUpdated,
    {
        threesixtyId: threesixtyId,
    }
);
