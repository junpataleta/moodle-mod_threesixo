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
    'mod_threesixo/question_bank',
    'core/yui'
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
    };

    return editItems;
});
