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
 * Questionnaire JS module.
 *
 * @module     mod_threesixo/questionnaire
 * @class      questionnaire
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import {get_string as getString, get_strings as getStrings} from 'core/str';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {add as addToast} from 'core/toast';
import Pending from 'core/pending';

/**
 * Selectors for the questionnaire page.
 *
 * @type {{questionItem: string, questionnaireTable: string, ratingOption: string, commentItem: string}}
 */
const selectors = {
    commentItem: 'textarea[data-region="comment-item"]',
    questionnaireTable: '[data-region="questionnaire"]',
    ratingOption: 'input[type=radio]',
    questionItem: '[data-region="question-item"]',
};

/**
 * Array of responses to the items in the questionnaire with item ID for the key and the response for the value.
 */
const responses = [];

const itemIds = [];
/**
 * Initialiser function.
 */
export const init = () => {
    const pending = new Pending('mod_threesixo/questionnaire-init');
    registerEvents();

    const questionItems = document.querySelectorAll(selectors.questionItem);
    questionItems.forEach(option => {
        itemIds.push(option.dataset.itemid);
        responses[option.dataset.itemid] = null;
    });

    const questionnaireTable = document.querySelector(selectors.questionnaireTable);
    const fromUser = questionnaireTable.dataset.fromuserid;
    const toUser = questionnaireTable.dataset.touserid;
    const threesixtyId = questionnaireTable.dataset.threesixtyid;

    const promises = Ajax.call([
        {
            methodname: 'mod_threesixo_get_responses',
            args: {
                threesixtyid: threesixtyId,
                fromuserid: fromUser,
                touserid: toUser
            }
        }
    ]);

    promises[0].then(result => {
        result.responses.forEach(response => {
            responses[response.item] = response.value;
            const responseItemId = parseInt(response.item);

            questionItems.forEach(questionItem => {
                const questionItemId = parseInt(questionItem.getAttribute('data-itemid'));
                if (questionItemId === responseItemId) {
                    const options = questionItem.querySelectorAll(selectors.ratingOption);
                    if (options.length) {
                        // Ratings.
                        options.forEach(option => {
                            // Mark selected option as selected.
                            const selectedValue = option.value;
                            if (selectedValue === response.value) {
                                handleOptionActivation(option);
                            }
                        });
                    } else {
                        // Comments.
                        const commentTextArea = questionItem.querySelector(selectors.commentItem);
                        if (commentTextArea) {
                            commentTextArea.value = response.value;
                        }
                    }
                }
            });
        });
        return true;
    }).then(pending.resolve).catch(Notification.exception);
};

/**
 * Registers the event listeners for the questionnaire.
 */
const registerEvents = () => {
    document.addEventListener('change', e => {
        const ratingOption = e.target.closest(selectors.ratingOption);
        if (ratingOption) {
            if (ratingOption.checked) {
                handleOptionActivation(ratingOption);
            }
        }
    });

    document.addEventListener('click', e => {
        const ratingOption = e.target.closest(selectors.ratingOption);
        if (ratingOption) {
            const ratingOptionLabel = ratingOption.closest('label');
            if (ratingOptionLabel) {
                ratingOptionLabel.classList.add('focus');
            }
        }
    });

    document.addEventListener('blur', e => {
        const ratingOption = e.target.closest(selectors.ratingOption);
        if (ratingOption) {
            const ratingOptionLabel = ratingOption.closest('label');
            if (ratingOptionLabel) {
                ratingOptionLabel.classList.remove('focus');
            }
        }
    });

    const btnSaveFeedback = document.getElementById('save-feedback');
    btnSaveFeedback.addEventListener('click', (e) => {
        e.preventDefault();
        saveResponses(false);
    });

    const btnSubmitFeedback = document.getElementById('submit-feedback');
    btnSubmitFeedback.addEventListener('click', (e) => {
        e.preventDefault();
        saveResponses(true);
    });
};

/**
 * Handles the selection of a rated question's option.
 *
 * @param {HTMLElement} ratingOption The selected option for the given rated question.
 */
const handleOptionActivation = ratingOption => {
    const pending = new Pending('mod_threesixo:handleOptionActivation');
    const optionGroup = ratingOption.closest(selectors.questionItem);
    const itemId = optionGroup.getAttribute('data-itemid');
    const options = optionGroup.querySelectorAll(selectors.ratingOption);

    // Deselect the option that has been selected.
    options.forEach(option => {
        const optionLabel = option.nextElementSibling;
        if (optionLabel.classList.contains('btn-success')) {
            optionLabel.classList.toggle('btn-success', false);
            optionLabel.classList.toggle('btn-secondary', true);
            option.checked = false;
        }
    });

    // Mark selected option as selected.
    const selectedLabel = ratingOption.nextElementSibling;
    selectedLabel.classList.remove('btn-secondary');
    selectedLabel.classList.add('btn-success');
    ratingOption.checked = true;

    // Add this selected value to the array of responses.
    responses[itemId] = ratingOption.value;
    pending.resolve();
};

/**
 * Save the responses.
 *
 * @param {boolean} finalise
 */
const saveResponses = finalise => {
    const comments = document.querySelectorAll(selectors.commentItem);
    comments.forEach(comment => {
        responses[comment.dataset.itemid] = comment.value.trim();
    });

    const questionnaireTable = document.querySelector(selectors.questionnaireTable);
    const toUser = parseInt(questionnaireTable.dataset.touserid);
    const toUserFullname = questionnaireTable.dataset.tousername;
    const threesixtyId = parseInt(questionnaireTable.dataset.threesixtyid);
    const anonymous = parseInt(questionnaireTable.dataset.anonymous);

    if (anonymous && finalise) {
        // Show confirmation dialogue to anonymise the feedback responses.
        const messageStrings = [
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

        getStrings(messageStrings, 'mod_threesixo').then(messages => {
            return showConfirmationDialogue(messages[0], messages[1], threesixtyId, toUser, responses, finalise);
        }).catch(Notification.exception);
    } else {
        // Just save the responses.
        submitResponses(threesixtyId, toUser, responses, finalise);
    }
};

/**
 * Send the responses to the server.
 *
 * @param {number} threesixtyId
 * @param {number} toUser
 * @param {array} responses
 * @param {boolean} finalise
 */
const submitResponses = (threesixtyId, toUser, responses, finalise) => {
    const pending = new Pending('mod_threesixo/submit-responses');
    let redirectUrl = null;
    const responsesToSubmit = [];
    itemIds.forEach(itemId => {
        responsesToSubmit.push({
            item: itemId,
            value: responses[itemId]
        });
    });
    Ajax.call([
        {
            methodname: 'mod_threesixo_save_responses',
            args: {
                threesixtyid: threesixtyId,
                touserid: toUser,
                responses: responsesToSubmit,
                complete: finalise
            }
        }
    ])[0].then(response => {
        if (response.result) {
            redirectUrl = response.redirurl;
            return getString('responsessaved', 'mod_threesixo');
        }
        return getString('errorresponsesavefailed', 'mod_threesixo');
    }).then(message => {
        if (!finalise) {
            // Show toast message when saving the responses but not redirecting.
            return addToast(message, {});
        }
        return true;
    }).then(() => {
        pending.resolve();
        if (finalise && redirectUrl) {
            const form = document.getElementById('questionnaire');
            const submitted = document.getElementById('feedback-submitted');
            submitted.value = 1;
            form.submit();
        }
        return true;
    }).catch(Notification.exception);
};

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
const showConfirmationDialogue = async(title, confirmationMessage, threesixtyId, toUser, responses, finalise) => {
    const confirmButtonText = await getString('finalise', 'mod_threesixo');
    const confirmModal = await ModalFactory.create({
        title: title,
        body: confirmationMessage,
        large: true,
        type: ModalFactory.types.SAVE_CANCEL
    });

    confirmModal.setSaveButtonText(confirmButtonText);

    // Display the dialogue.
    confirmModal.show();

    // On hide handler.
    confirmModal.getRoot().on(ModalEvents.hidden, () => {
        // Empty modal contents when it's hidden.
        confirmModal.setBody('');
    });

    confirmModal.getRoot().on(ModalEvents.save, () => {
        submitResponses(threesixtyId, toUser, responses, finalise);
    });
};
