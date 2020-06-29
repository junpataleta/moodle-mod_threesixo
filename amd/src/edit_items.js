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
 * @package    core
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/templates',
    'core/notification',
    'core/ajax',
    'core/str',
    'mod_threesixo/question_bank'
], function($, Templates, Notification, Ajax, Str, Bank) {

    /**
     * List of action selectors.
     *
     * @type {{DELETE: string, MOVE_UP: string, MOVE_DOWN: string}}
     */
    var ACTIONS = {
        DELETE: '[data-action="delete-item"]',
        MOVE_UP: '[data-action="move-item-up"]',
        MOVE_DOWN: '[data-action="move-item-down"]'
    };

    /**
     * List of selectors.
     *
     * @type {{ITEM_LIST: string, ITEM_ROW: string}}
     */
    var SELECTORS = {
        ITEM_LIST: '[data-region="itemlist"]',
        ITEM_ROW: '[data-region="itemrow"]'
    };

    var threesixtyId;
    var editItems = function(threesixtyid) {
        threesixtyId = threesixtyid;
        this.registerEvents();
    };

    editItems.refreshItemList = function() {
        var promises = Ajax.call([
            {
                methodname: 'mod_threesixo_get_items',
                args: {
                    threesixtyid: threesixtyId
                }
            }
        ]);
        $.when(promises[0]).then(function(response) {
            var context = {
                threesixtyid: threesixtyId
            };

            var items = [];
            var itemCount = response.items.length;
            $.each(response.items, function(key, value) {
                var item = value;
                item.deletebutton = true;
                item.moveupbutton = false;
                item.movedownbutton = false;
                item.type = value.typetext;
                if (itemCount > 1) {
                    item.position = value.position;
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

        }).done(function(compiledSource, js) {
            $('[data-region="itemlist"]').replaceWith(compiledSource);
            Templates.runTemplateJS(js);

        }).fail(Notification.exception);
    };

    editItems.callItemAction = function(action, itemId) {
        var promises = Ajax.call([
            {
                methodname: action,
                args: {
                    itemid: itemId
                }
            }
        ]);
        promises[0].done(function(response) {
            if (response.result) {
                editItems.refreshItemList();
                return true;
            }
            var warnings = response.warnings.join($('<br/>'));
            throw new Error(warnings);
        }).fail(Notification.exception);
    };

    editItems.prototype.registerEvents = function() {
        // Bind click event for the comments chooser button.
        $("#btn-question-bank").click(function(e) {
            e.preventDefault();
            Bank.init(threesixtyId);
        });

        $(ACTIONS.DELETE).click(function(e) {
            e.preventDefault();

            var itemId = $(this).data('itemid');
            editItems.callItemAction('mod_threesixo_delete_item', itemId);
        });

        $(ACTIONS.MOVE_UP).click(function(e) {
            e.preventDefault();

            var itemId = $(this).data('itemid');
            editItems.callItemAction('mod_threesixo_move_item_up', itemId);
        });

        $(ACTIONS.MOVE_DOWN).click(function(e) {
            e.preventDefault();

            var itemId = $(this).data('itemid');
            editItems.callItemAction('mod_threesixo_move_item_down', itemId);
        });

        const root = document.querySelector(SELECTORS.ITEM_LIST);
        const rows = root.querySelectorAll(SELECTORS.ITEM_ROW);
        rows.forEach((row) => {
            row.addEventListener('dragstart', handleDrag, false);
            row.addEventListener('dragover', handleDragOver, false);
            row.addEventListener('drop', handleDrop, false);
        });

    };

    let dragSrcEl = null;

    const handleDrag = (e) => {
        dragSrcEl = e.target;

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', dragSrcEl.outerHTML);
    };

    const handleDrop = (e) => {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        const dropTarget = e.target.closest(SELECTORS.ITEM_ROW);

        // Don't do anything if dropping the same column we're dragging.
        if (dragSrcEl !== undefined && dragSrcEl !== dropTarget) {
            // Set the source column's HTML to the HTML of the column we dropped on.
            const sourceItemID = dragSrcEl.getAttribute('data-itemid');
            const sourcePosition = parseInt(dragSrcEl.getAttribute('data-position'));
            let targetPosition = parseInt(dropTarget.getAttribute('data-position'));
            let moveTo = 'beforebegin';
            if (sourcePosition < targetPosition) {
                moveTo = 'afterend';
            }
            dragSrcEl.parentNode.removeChild(dragSrcEl);

            const templateElement = document.createElement('template');
            templateElement.innerHTML = e.dataTransfer.getData('text/html').trim();
            const droppedElement = templateElement.content.firstChild;
            droppedElement.setAttribute('data-position', targetPosition);
            dropTarget.insertAdjacentElement(moveTo, droppedElement);

            const rows = dropTarget.parentNode.querySelectorAll(SELECTORS.ITEM_ROW);
            rows.forEach((row) => {
                let itemId = row.getAttribute('data-itemid');
                let itemPosition = parseInt(row.getAttribute('data-position'));
                if (itemId !== sourceItemID) {
                    if (moveTo === 'beforebegin' && itemPosition >= targetPosition) {
                        row.setAttribute('data-position', itemPosition + 1);
                    } else if (moveTo === 'afterend' && itemPosition <= targetPosition) {
                        row.setAttribute('data-position', itemPosition - 1);
                    }
                }
            });
        }

        return false;
    };

    const handleDragOver = (e) => {
        if (e.preventDefault) {
            e.preventDefault();
        }

        e.dataTransfer.dropEffect = 'move';

        return false;
    };

    return editItems;
});
