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
 * AMD code for the Question Bank.
 *
 * The question bank dialogue contains all the questions that can be added to the 360 feedback activity.
 * It also serves as the interface where questions can be added, edited, or even removed permanently from the question bank.
 *
 * @module     mod_threesixo/question_bank
 * @class      question_bank
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as templates from 'core/templates';
import * as notification from 'core/notification';
import * as ajax from 'core/ajax';
import {get_string as getString} from 'core/str';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Pending from 'core/pending';
import {notifyItemsUpdated} from "mod_threesixo/events";
import * as CheckboxToggleAll from 'core/checkbox-toggleall';

const SELECTORS = {
    PICK_ALL: '#pick-all',
    ADD_QUESTION: '#btn-question-bank-add',
    QUESTION_CHECKBOX: '.question-checkbox',
    DELETE_QUESTION: '.delete-question-button',
    EDIT_QUESTION: '.edit-question-button',
};

// Private variables and functions.
let selectedQuestionsOld,
    selectedQuestions,
    questions = [],
    threeSixtyId,
    questionTypes;

/**
 * Fetches option data for the question type selector.
 *
 * @param {number} selectedId The currently selected question type.
 * @returns {Array}
 */
function getQuestionTypeOptions(selectedId) {
    const questionTypeOptions = [];
    // Get question type options.
    for (const key in questionTypes) {
        if (!questionTypes.hasOwnProperty(key)) {
            continue;
        }
        const questionType = {
            typeVal: key,
            typeName: questionTypes[key],
        };

        if (typeof selectedId !== 'undefined') {
            questionType.selected = parseInt(key) === parseInt(selectedId);
        }

        questionTypeOptions.push(questionType);
    }

    return questionTypeOptions;
}

/**
 * Loops over the list of questions and marks a question as checked if it belongs to the list of selected questions.
 *
 * @param {Object[]} questions The questions to be checked.
 * @returns {Object[]} The list of checked questions.
 */
function checkQuestions(questions) {
    for (const i in questions) {
        const question = questions[i];
        if (selectedQuestions.indexOf(questions[i].id) !== -1) {
            question.checked = true;
        }
    }
    return questions;
}

/**
 * Renders the question input dialogue.
 *
 * @param {String} dialogueTitle
 * @param {Object} bodyTemplate
 * @param {HTMLElement} trigger
 */
const renderInputDialogue = async(dialogueTitle, bodyTemplate, trigger) => {
    const pendingPromise = new Pending('mod_threesixo/question_input');
    const modal = await ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: dialogueTitle,
        body: bodyTemplate,
        large: true
    });
    // Display the dialogue.
    modal.show();

    modal.getRoot().on(ModalEvents.bodyRendered, function() {
        // Focus on the question text area.
        const questionInput = document.getElementById("question-input");
        if (questionInput) {
            questionInput.focus();
        }
    });

    // On hide handler.
    modal.getRoot().on(ModalEvents.hidden, function() {
        // Just destroy the modal.
        modal.destroy();
        trigger.removeAttribute('disabled');
    });

    // On save handler.
    modal.getRoot().on(ModalEvents.save, () => {
        const questionInput = document.getElementById("question-input");
        const question = questionInput.value.trim();
        // Validate the entered question. Prevent saving if passing an empty question string.
        if (!question) {
            question.value = '';
            const form = questionInput.form;
            form.classList.add('was-validated');
            questionInput.classList.add('is-invalid');
            questionInput.focus();
            return false;
        }
        const qtype = document.getElementById("question-type-select").value;
        const threesixtyid = document.getElementById("threesixtyid").value;

        const data = {
            question: question,
            type: qtype,
            threesixtyid: threesixtyid,
        };

        let method = 'mod_threesixo_add_question';
        const questionId = document.getElementById("question-id").value;
        if (questionId) {
            method = 'mod_threesixo_update_question';
            data.id = questionId;
        }

        // Refresh the list of questions through AJAX.
        const promises = ajax.call([
            {methodname: method, args: data}
        ]);
        return promises[0].then(function() {
            return refreshQuestionsList();
        }).catch(notification.exception);
    });

    pendingPromise.resolve();
};

/**
 * Function that displays the input dialogue.
 *
 * @param {Number} threesixtyId The 360 instance ID.
 * @param {Number} questionId The question ID.
 * @param {HTMLElement} trigger The element that triggered the dialogue.
 */
const displayInputDialogue = async(threesixtyId, questionId, trigger) => {
    trigger.setAttribute('disabled', 'disabled');
    const dialogueTitle = await getString('addanewquestion', 'mod_threesixo');
    const data = {
        threesixtyid: threesixtyId
    };

    if (questionId) {
        data.questionid = questionId;
        for (const i in questions) {
            const question = questions[i];
            if (question.id === questionId) {
                data.question = question.question;
                data.type = question.type;
                break;
            }
        }
    }

    data.questionTypes = getQuestionTypeOptions(data.type);
    const body = await templates.render('mod_threesixo/item_edit', data);
    await renderInputDialogue(dialogueTitle, body, trigger);
};

/**
 * Displays the question bank dialogue.
 *
 * @param {string} title
 * @param {Promise} questionBankTemplate
 */
const displayQuestionBankDialogue = async(title, questionBankTemplate) => {
    const pendingPromise = new Pending('mod_threesixo/question_bank');

    const modal = await ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: title,
        body: questionBankTemplate,
        large: true
    });
    const modalRoot = modal.getRoot();

    // On hide handler.
    modalRoot.on(ModalEvents.hidden, function() {
        // Empty modal contents when it's hidden.
        modal.destroy();
    });

    modalRoot.on(ModalEvents.save, function() {
        let changed = false;
        // Check if the new selected questions exist in the old selected questions.
        selectedQuestionsOld.forEach(questionId => {
            if (selectedQuestions.indexOf(questionId) === -1) {
                changed = true;
            }
        });
        // Conversely, if the newly selected items seem to have not changed,
        // check if the old selected questions exist in the new selected questions.
        if (!changed) {
            selectedQuestions.forEach(questionId => {
                if (selectedQuestionsOld.indexOf(questionId) === -1) {
                    changed = true;
                }
            });
        }

        if (changed) {
            const data = {
                threesixtyid: threeSixtyId,
                questionids: selectedQuestions
            };

            // Save the selected questions.
            const promises = ajax.call([
                {methodname: 'mod_threesixo_set_items', args: data}
            ]);
            // Refresh the list of questions through AJAX.
            promises[0].then(function() {
                return notifyItemsUpdated(threeSixtyId);
            }).catch(notification.exception);
        } else {
            // Nothing changed in the selection, but it's possible that the question texts have been updated.
            // So better to refresh the list as well.
            notifyItemsUpdated(threeSixtyId);
        }
    });

    // Display the dialogue.
    modal.show();

    pendingPromise.resolve();
};

/**
 * Adds/removes a question from the array of selected questions depending on its selection state.
 *
 * @param {number} questionId
 * @param {boolean} isSelected
 */
const updateItemSelection = (questionId, isSelected) => {
    if (isSelected) {
        const index = selectedQuestions.indexOf(questionId);
        if (index === -1) {
            // Add the question ID if it's not yet present.
            selectedQuestions.push(questionId);
        }
    } else {
        const index = selectedQuestions.indexOf(questionId);
        if (index > -1) {
            // Remove the question ID only if it's present.
            selectedQuestions.splice(index, 1);
        }
    }
};

/**
 * Binds the event listeners to question items such as edit, delete, checking.
 */
const registerEvents = function() {
    document.addEventListener('click', async(e) => {
        if (e.target.closest(SELECTORS.PICK_ALL)) {
            const questionCheckboxes = document.querySelectorAll(SELECTORS.QUESTION_CHECKBOX);
            questionCheckboxes.forEach(checkbox => {
                const questionId = parseInt(checkbox.dataset.questionid);
                updateItemSelection(questionId, checkbox.checked);
            });
        } else if (e.target.closest(SELECTORS.QUESTION_CHECKBOX)) {
            const questionCheckbox = e.target.closest(SELECTORS.QUESTION_CHECKBOX);
            const questionId = parseInt(questionCheckbox.dataset.questionid);

            updateItemSelection(questionId, questionCheckbox.checked);
        } else if (e.target.closest(SELECTORS.EDIT_QUESTION)) {
            e.preventDefault();

            const editQuestionButton = e.target.closest(SELECTORS.EDIT_QUESTION);
            const threesixtyId = parseInt(editQuestionButton.dataset.threesixtyid);
            const questionId = parseInt(editQuestionButton.dataset.questionid);
            await displayInputDialogue(threesixtyId, questionId, editQuestionButton);
        } else if (e.target.closest(SELECTORS.DELETE_QUESTION)) {
            e.preventDefault();

            const deleteButton = e.target.closest(SELECTORS.DELETE_QUESTION);
            const threesixtyId = parseInt(deleteButton.dataset.threesixtyid);
            const questionId = parseInt(deleteButton.dataset.questionid);
            await handleDeletion(questionId, threesixtyId);
        } else if (e.target.closest(SELECTORS.ADD_QUESTION)) {
            e.preventDefault();

            const addButton = e.target.closest(SELECTORS.ADD_QUESTION);
            const id = parseInt(addButton.dataset.threesixtyid);
            await displayInputDialogue(id, null, addButton);
        }
    });
};

/**
 * Refreshes the list of questions in the question bank.
 */
function refreshQuestionsList() {
    // Get list of questions through AJAX.
    const promises = ajax.call([
        {
            methodname: 'mod_threesixo_get_questions',
            args: {}
        }
    ]);
    promises[0].then(response => {
        questions = response.questions;
        const data = {
            pickerMode: threeSixtyId,
            questions: checkQuestions(questions)
        };

        return templates.render('mod_threesixo/question_list', data);
    }).then(compiledSource => {
        const questionListWrapper = document.querySelector("#questionListWrapper");
        if (questionListWrapper) {
            questionListWrapper.innerHTML = compiledSource;
        }
        return null;
    }).catch(notification.exception);
}

/**
 * Handles item deletion.
 *
 * @param {Number} questionId The question ID.
 * @param {Number} threesixtyId The 360 instance ID.
 */
const handleDeletion = async(questionId, threesixtyId) => {
    const delTitle = await getString('deletequestion', 'mod_threesixo');
    const modal = await ModalFactory.create({
        title: delTitle,
        body: getString('confirmquestiondeletion', 'mod_threesixo'),
        type: ModalFactory.types.SAVE_CANCEL
    });

    modal.getRoot().on(ModalEvents.save, function() {
        // Get list of questions through AJAX.
        const promises = ajax.call([
            {
                methodname: 'mod_threesixo_delete_question',
                args: {
                    id: questionId,
                    threesixtyid: threesixtyId,
                }
            }
        ]);
        promises[0].then(function() {
            return refreshQuestionsList();
        }).catch(notification.exception);
    });

    modal.getRoot().on(ModalEvents.hidden, () => {
        modal.destroy();
    });

    return modal.show();
};

/**
 * Create the context and render the question  bank template.
 */
const renderQuestionBank = async() => {
    // Template context.
    const context = {pickerMode: threeSixtyId};

    // Render the question list.
    const response = await ajax.call([
        {
            methodname: 'mod_threesixo_get_questions',
            args: {}
        }
    ])[0];

    questions = response.questions;
    context.questions = checkQuestions(questions);

    // Render the template and display the comment chooser dialog.
    const questionBankTemplate = await templates.render('mod_threesixo/question_bank', context);
    const dialogueTitle = await getString('labelpickfromquestionbank', 'mod_threesixo');
    await displayQuestionBankDialogue(dialogueTitle, questionBankTemplate);

    if (threeSixtyId) {
        CheckboxToggleAll.init();
    }
};

const questionBankInit = function(id) {
    threeSixtyId = id;

    const methodCalls = [
        {
            methodname: 'mod_threesixo_get_question_types',
            args: {}
        }
    ];

    if (threeSixtyId) {
        // Get selected items for the 360-degree feedback.
        methodCalls.push({
            methodname: 'mod_threesixo_get_items',
            args: {
                threesixtyid: threeSixtyId
            }
        });
    }

    // Get list of questions through AJAX.
    const promises = ajax.call(methodCalls);
    promises[0].then(function(response) {
        questionTypes = response.questiontypes;
        if (threeSixtyId) {
            return promises[1];
        }
        return renderQuestionBank();
    }).then(response => {
        if (response === null) {
            return false;
        }
        selectedQuestions = [];
        selectedQuestionsOld = [];
        const items = response.items;
        for (const i in items) {
            if (!items.hasOwnProperty(i)) {
                continue;
            }
            selectedQuestions.push(items[i].questionid);
            // Store originally selected question IDs for comparison later.
            selectedQuestionsOld.push(items[i].questionid);
        }
        return renderQuestionBank();
    }).catch(notification.exception);

    registerEvents();
};

/** @alias module:mod_threesixo/question_bank */
export default {
    init: questionBankInit,
};
