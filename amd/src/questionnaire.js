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
 * @module     mod_threesixo/questionnaire
 * @class      view
 * @package    core
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
    'core/templates',
    'core/notification',
    'core/ajax',
    'core/str',
    'core/modal_factory',
    'core/modal_events'
], function($, Templates, Notification, Ajax, Str, ModalFactory, ModalEvents) {

    var responses = [];
    var questionnaire = function() {
        this.registerEvents();

        $('[data-region="question-row"]').each(function() {
            responses[$(this).data('itemid')] = null;
        });

        var questionnaireTable = $('[data-region="questionnaire"]');
        var fromUser = questionnaireTable.data('fromuserid');
        var toUser = questionnaireTable.data('touserid');
        var threesixtyId = questionnaireTable.data('threesixtyid');

        var promises = Ajax.call([
            {
                methodname: 'mod_threesixo_get_responses',
                args: {
                    threesixtyid: threesixtyId,
                    fromuserid: fromUser,
                    touserid: toUser
                }
            }
        ]);

        promises[0].done(function(result) {
            $.each(result.responses, function() {
                var response = this;
                responses[response.item] = response.value;

                $('[data-region="question-row"]').each(function() {
                    if ($(this).data('itemid') === response.item) {
                        var options = $(this).children('.scaleoption');
                        if (options) {
                            options.each(function() {
                                // Mark selected option as selected.
                                var selected = $(this).find('label');
                                if (selected.data('value') == response.value) {
                                    selected.removeClass('label-default');
                                    selected.removeClass('label-info');
                                    selected.addClass('label-success');
                                }
                            });
                        }
                        var comment = $(this).find('.comment');
                        if (comment) {
                            var commentTextArea = $(this).find('textarea');
                            commentTextArea.val(response.value);
                        }
                    }
                });
            });
        });
    };

    questionnaire.prototype.registerEvents = function() {
        $('.scaleoption').click(function(e) {
            e.preventDefault();

            var row = $(this).parent('[data-region="question-row"]');
            var options = row.find('label');

            // Deselect the option that has been selected.
            $.each(options, function() {
                if ($(this).hasClass('label-success')) {
                    $(this).removeClass('label-success');
                    $(this).addClass('label-default');

                    var forId = $(this).attr('for');
                    var optionRadio = $("#" + forId);
                    optionRadio.removeAttr('checked');
                }
            });

            // Mark selected option as selected.
            var selected = $(this).find('label');
            selected.removeClass('label-default');
            selected.removeClass('label-info');
            selected.addClass('label-success');

            // Mark hidden radio button as checked.
            var radio = $("#" + selected.attr('for'));
            radio.attr('checked', 'checked');
            var itemid = row.data('itemid');

            // Add this selected value to the array of responses.
            responses[itemid] = selected.data('value');
        });

        $('.scaleoptionlabel').hover(function(e) {
            e.preventDefault();

            if (!$(this).hasClass('label-success')) {
                if ($(this).hasClass('label-default')) {
                    $(this).removeClass('label-default');
                    $(this).addClass('label-info');
                } else {
                    $(this).addClass('label-default');
                    $(this).removeClass('label-info');
                }
            }
        });

        $("#save-feedback").click(function() {
            saveResponses(false);
        });

        $("#submit-feedback").click(function() {
            saveResponses(true);
        });
    };

    /**
     * Save the responses.
     *
     * @param {boolean} finalise
     */
    function saveResponses(finalise) {
        $('.comment').each(function() {
            responses[$(this).data('itemid')] = $(this).val().trim();
        });

        var questionnaireTable = $('[data-region="questionnaire"]');
        var toUser = questionnaireTable.data('touserid');
        var toUserFullname = questionnaireTable.data('tousername');
        var threesixtyId = questionnaireTable.data('threesixtyid');
        var anonymous = questionnaireTable.data('anonymous');

        if (anonymous && finalise) {
            // Show confirmation dialogue to anonymise the feedback responses.
            var messageStrings = [
                {
                    key: 'finaliseanonymousfeedback',
                    component: 'mod_threesixo'
                },
                {
                    key: 'confirmfinaliseanonymousfeedback',
                    component: 'mod_threesixo',
                    param: {
                        'name': toUserFullname
                    }
                }
            ];

            Str.get_strings(messageStrings, 'mod_threesixo').done(function(messages) {
                showConfirmationDialogue(messages[0], messages[1], threesixtyId, toUser, responses, finalise);
            }).fail(Notification.exception);
        } else {
            // Just save the responses.
            submitResponses(threesixtyId, toUser, responses, finalise);
        }
    }

    /**
     * Send the responses to the server.
     *
     * @param {number} threesixtyId
     * @param {number} toUser
     * @param {array} responses
     * @param {boolean} finalise
     */
    function submitResponses(threesixtyId, toUser, responses, finalise) {
        var promises = Ajax.call([
            {
                methodname: 'mod_threesixo_save_responses',
                args: {
                    threesixtyid: threesixtyId,
                    touserid: toUser,
                    responses: responses,
                    complete: finalise
                }
            }
        ]);

        promises[0].done(function(response) {
            var messageStrings = [
                {
                    key: 'responsessaved',
                    component: 'mod_threesixo'
                },
                {
                    key: 'errorresponsesavefailed',
                    component: 'mod_threesixo'
                }
            ];

            Str.get_strings(messageStrings).done(function(messages) {
                var notificationData = {};
                if (response.result) {
                    notificationData.message = messages[0];
                    notificationData.type = "success";
                } else {
                    notificationData.message = messages[1];
                    notificationData.type = "error";
                }
                Notification.addNotification(notificationData);
            }).fail(Notification.exception);

            if (finalise) {
                window.location = response.redirurl;
            }
        }).fail(Notification.exception);
    }

    /**
     * Renders the confirmation dialogue to submit and finalise the responses.
     *
     * @param {string} title
     * @param {string} confirmationMessage
     * @param {number} threesixtyId
     * @param {number} toUser
     * @param {Array} responses
     * @param {boolean} finalise
     */
    function showConfirmationDialogue(title, confirmationMessage, threesixtyId, toUser, responses, finalise) {
        var confirmButtonTextPromise = Str.get_string('finalise', 'mod_threesixo');
        var confirmModalPromise = ModalFactory.create({
            title: title,
            body: confirmationMessage,
            large: true,
            type: ModalFactory.types.SAVE_CANCEL
        });
        $.when(confirmButtonTextPromise, confirmModalPromise).done(function(confirmButtonText, modal) {
            modal.setSaveButtonText(confirmButtonText);

            // Display the dialogue.
            modal.show();

            // On hide handler.
            modal.getRoot().on(ModalEvents.hidden, function() {
                // Empty modal contents when it's hidden.
                modal.setBody('');
            });

            modal.getRoot().on(ModalEvents.save, function() {
                submitResponses(threesixtyId, toUser, responses, finalise);
            });
        });

    }

    return questionnaire;
});
