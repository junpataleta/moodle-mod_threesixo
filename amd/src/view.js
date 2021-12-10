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
 * @module     mod_threesixo/view
 * @class      view
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/templates',
    'core/notification',
    'core/ajax',
    'core/str',
    'core/modal_factory',
    'core/modal_events'
], function($, Templates, notification, ajax, Str, ModalFactory, ModalEvents) {

    /**
     * List of action selectors.
     *
     * @type {{VIEW_FEEDBACK: string, DECLINE_FEEDBACK: string}}
     */
    var ACTIONS = {
        VIEW_FEEDBACK: '[data-action="view-feedback"]',
        DECLINE_FEEDBACK: '[data-action="decline-feedback"]',
        UNDO_DECLINE: '[data-action="undo-decline"]'
    };

    var threesixtyid;

    /**
     * Refresh the list of participants.
     */
    function refreshParticipantsList() {
        // Refresh the list of participants thru AJAX.
        var promises = ajax.call([
            {methodname: 'mod_threesixo_data_for_participant_list', args: {threesixtyid: threesixtyid}}
        ]);
        $.when(promises[0]).then(function(response) {
            return Templates.render('mod_threesixo/list_participants', response);

        }).done(function(compiledSource, js) {
            $('[data-region="participantlist"]').replaceWith(compiledSource);
            Templates.runTemplateJS(js);

        }).fail(notification.exception);
    }

    var view = function(id) {
        threesixtyid = id;
        this.registerEvents();
    };

    view.prototype.registerEvents = function() {
        $(ACTIONS.DECLINE_FEEDBACK).click(function(e) {
            e.preventDefault();

            var statusid = $(this).data('statusid');
            var name = $(this).data('name');
            var context = {
                statusid: statusid,
                name: name
            };
            var declineTemplatePromise = Templates.render('mod_threesixo/decline_feedback', context);
            var titlePromise = Str.get_string('declinefeedback', 'mod_threesixo');

            $.when(titlePromise).then(function(title) {
                return ModalFactory.create({
                    title: title,
                    body: declineTemplatePromise,
                    large: true,
                    type: ModalFactory.types.SAVE_CANCEL
                });
            }).done(function(modal) {
                // Display the dialogue.
                modal.show();

                // On hide handler.
                modal.getRoot().on(ModalEvents.hidden, function() {
                    // Destroy modal when hidden.
                    modal.destroy();
                });

                modal.getRoot().on(ModalEvents.save, function() {
                    var statusid = $("#decline-statusid").val();
                    var reason = $("#decline-reason").val().trim();
                    var data = {
                        statusid: statusid,
                        declinereason: reason
                    };

                    var method = 'mod_threesixo_decline_feedback';

                    // Refresh the list of questions thru AJAX.
                    var promises = ajax.call([
                        {methodname: method, args: data}
                    ]);
                    promises[0].done(function() {
                        refreshParticipantsList();
                    }).fail(notification.exception);
                });
            }).fail(notification.exception);
        });

        $(ACTIONS.UNDO_DECLINE).click(function(e) {
            e.preventDefault();

            var statusid = $(this).data('statusid');
            var data = {
                statusid: statusid
            };

            var method = 'mod_threesixo_undo_decline';

            // Refresh the list of questions thru AJAX.
            var promises = ajax.call([
                {methodname: method, args: data}
            ]);
            promises[0].done(function() {
                refreshParticipantsList();
            }).fail(notification.exception);
        });
    };

    return view;
});
