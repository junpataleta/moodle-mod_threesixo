<?php
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

namespace mod_threesixo;

defined('MOODLE_INTERNAL') || die();

// TODO: When the plugin supports 4.2 as a minimum version, remove this and import the proper core_external classes.
require_once($CFG->libdir . '/externallib.php');

use cm_info;
use coding_exception;
use context_module;
use context_system;
use dml_exception;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use mod_threesixo\output\list_participants;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use restricted_context_exception;
use stdClass;

/**
 * Class external.
 *
 * The external API for the 360-degree feedback module.
 *
 * @package mod_threesixo
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameter description for get_questions().
     *
     * @return external_function_parameters
     */
    public static function get_questions_parameters() {
        return new external_function_parameters([
            'ownquestions' => new external_value(
                PARAM_BOOL,
                'Whether to fetch only the questions created by the user.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Fetches the questions from the 360-degree feedback question bank.
     *
     * @param bool $ownquestions Whether to fetch only the questions created by the user.
     * @return array
     */
    public static function get_questions(bool $ownquestions): array {
        $warnings = [];

        [
            'ownquestions' => $ownquestions,
        ] = external_api::validate_parameters(self::get_questions_parameters(), [
            'ownquestions' => $ownquestions,
        ]);

        $questions = api::get_questions($ownquestions);

        return [
            'questions' => $questions,
            'warnings' => $warnings,
        ];
    }

    /**
     * Method results description for get_questions().
     *
     * @return external_description
     */
    public static function get_questions_returns() {
        return new external_single_structure(
            [
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'The question ID.'),
                            'question' => new external_value(PARAM_TEXT, 'The question text.'),
                            'type' => new external_value(PARAM_INT, 'The question type.'),
                            'typeName' => new external_value(PARAM_TEXT, 'The question type text value.'),
                            'canEdit' => new external_value(
                                PARAM_BOOL,
                                'Whether the question can be edited.',
                                VALUE_DEFAULT,
                                false
                            ),
                            'canDelete' => new external_value(
                                PARAM_BOOL,
                                'Whether the question can be deleted.',
                                VALUE_DEFAULT,
                                false
                            ),
                        ]
                    )
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Adds a question into the 360-degree feedback question bank.
     *
     * @param string $question The question text.
     * @param int $type The question type.
     * @param int $threesixtyid The 360 instance ID, for capability checking.
     * @return array
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws dml_exception
     */
    public static function add_question($question, $type, $threesixtyid) {
        $warnings = [];

        $params = external_api::validate_parameters(self::add_question_parameters(), [
            'question' => $question,
            'type' => $type,
            'threesixtyid' => $threesixtyid,
        ]);

        // Validate context and capability.
        $threesixtyid = $params['threesixtyid'];
        $coursecm = get_course_and_cm_from_instance($threesixtyid, 'threesixo');
        $context = context_module::instance($coursecm[1]->id);
        self::validate_context($context);

        require_capability('mod/threesixo:addquestions', $context);

        $dataobj = new stdClass();
        $dataobj->question = $params['question'];
        $dataobj->type = $params['type'];
        $questionid = api::add_question($dataobj);

        return [
            'questionid' => $questionid,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for add_question().
     *
     * @return external_function_parameters
     */
    public static function add_question_parameters() {
        return new external_function_parameters([
            'question' => new external_value(PARAM_TEXT, 'The question text.'),
            'type' => new external_value(PARAM_INT, 'The question type.'),
            'threesixtyid' => new external_value(PARAM_INT, 'The threesixty instance ID. For capability checking.'),
        ]);
    }

    /**
     * Method results description for add_question().
     *
     * @return external_description
     */
    public static function add_question_returns() {
        return new external_single_structure(
            [
                'questionid' => new external_value(PARAM_INT, 'The question ID of the added question.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Updates a question in the 360-degree feedback question bank.
     *
     * @param int $id The question ID.
     * @param string $question The question text.
     * @param int $type The question type.
     * @param int $threesixtyid The 360 instance ID, for capability checking.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function update_question($id, $question, $type, $threesixtyid) {
        global $USER;
        $warnings = [];

        $params = external_api::validate_parameters(self::update_question_parameters(), [
                'id' => $id,
                'question' => $question,
                'type' => $type,
                'threesixtyid' => $threesixtyid,
            ]
        );

        // Validate context and capability.
        $threesixtyid = $params['threesixtyid'];
        $coursecm = get_course_and_cm_from_instance($threesixtyid, 'threesixo');
        $context = context_module::instance($coursecm[1]->id);
        self::validate_context($context);

        require_capability('mod/threesixo:editquestions', $context);

        // Check if the user has permission to edit questions created by others.
        $question = api::get_question($params['id']);
        if ($question->createdby != $USER->id && !has_capability('mod/threesixo:editothersquestions', context_system::instance())) {
            throw new moodle_exception('errorcannoteditothersquestion', 'mod_threesixo');
        }

        $dataobj = new stdClass();
        $dataobj->id = $params['id'];
        $dataobj->question = $params['question'];
        $dataobj->type = $params['type'];
        $dataobj->editedby = $USER->id;

        $result = api::update_question($dataobj);

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for update_question().
     *
     * @return external_function_parameters
     */
    public static function update_question_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The question ID.'),
            'question' => new external_value(PARAM_TEXT, 'The question text.'),
            'type' => new external_value(PARAM_INT, 'The question type.'),
            'threesixtyid' => new external_value(PARAM_INT, 'The threesixty instance ID. For capability checking.'),
        ]);
    }

    /**
     * Method results description for update_question().
     *
     * @return external_description
     */
    public static function update_question_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The question update processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Delete a question from the question bank.
     *
     * @param int $id The question ID.
     * @param int $threesixtyid The 360 instance ID, for capability checking.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function delete_question($id, $threesixtyid) {
        global $DB;

        $warnings = [];

        $params = external_api::validate_parameters(self::delete_question_parameters(), [
            'id' => $id,
            'threesixtyid' => $threesixtyid,
        ]);

        $id = $params['id'];
        $threesixtyid = $params['threesixtyid'];

        // Validate context and capability.
        $coursecm = get_course_and_cm_from_instance($threesixtyid, 'threesixo');
        $context = context_module::instance($coursecm[1]->id);
        self::validate_context($context);

        require_capability('mod/threesixo:deletequestions', $context);

        $question = $DB->get_record('threesixo_question', ['id' => $id]);
        if ($question && !api::can_delete_others_question($question)) {
            // User can only delete questions they created.
            throw new moodle_exception('errorcannotdeleteothersquestion', 'mod_threesixo');
        }

        if (api::can_delete_question($id)) {
            $result = api::delete_question($id);
        } else {
            throw new moodle_exception('errorquestionstillinuse', 'mod_threesixo');
        }

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for delete_question().
     *
     * @return external_function_parameters
     */
    public static function delete_question_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The question ID.'),
            'threesixtyid' => new external_value(PARAM_INT, 'The threesixty instance ID. For capability checking.'),
        ]);
    }

    /**
     * Method results description for delete_question().
     *
     * @return external_description
     */
    public static function delete_question_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The question update processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Fetches the questions assigned to a 360-degree feedback instance.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_items($threesixtyid) {
        $warnings = [];
        $params = external_api::validate_parameters(self::get_items_parameters(), ['threesixtyid' => $threesixtyid]);

        $items = api::get_items($params['threesixtyid']);

        return [
            'items' => $items,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for get_items().
     *
     * @return external_function_parameters
     */
    public static function get_items_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
            ]
        );
    }

    /**
     * Method results description for get_items().
     *
     * @return external_description
     */
    public static function get_items_returns() {
        return new external_single_structure(
            [
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'The item ID.'),
                            'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
                            'questionid' => new external_value(PARAM_INT, 'The question ID.'),
                            'position' => new external_value(PARAM_INT, 'The item position'),
                            'question' => new external_value(PARAM_TEXT, 'The question text.'),
                            'type' => new external_value(PARAM_INT, 'The question type.'),
                            'typetext' => new external_value(PARAM_TEXT, 'The question type text value.'),
                        ]
                    )
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Sets the questions for the 360 activity.
     *
     * @param int $threesixtyid The 360-degree feedback instance.
     * @param int[] $questionids The list of question IDs from the question bank being assigned to the feedback instance.
     * @return array
     * @throws moodle_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function set_items($threesixtyid, $questionids) {
        $warnings = [];
        $params = external_api::validate_parameters(self::set_items_parameters(), [
            'threesixtyid' => $threesixtyid,
            'questionids' => $questionids,
        ]);

        // Validate context and capability.
        $cm = get_coursemodule_from_instance('threesixo', $threesixtyid);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixo:edititems', $context);

        $result = api::set_items($params['threesixtyid'], $params['questionids']);

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for set_items().
     *
     * @return external_function_parameters
     */
    public static function set_items_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
                'questionids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'The question ID.')
                ),
            ]
        );
    }

    /**
     * Method results description for set_items().
     *
     * @return external_description
     */
    public static function set_items_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Parameter description for get_question_types().
     *
     * @return external_function_parameters
     */
    public static function get_question_types_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Fetches the list of question types supported by the 360-degree feedback activity.
     *
     * @return array
     * @throws coding_exception
     */
    public static function get_question_types() {
        $warnings = [];
        $result = api::get_question_types();
        return [
            'questiontypes' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Method results description for get_question_types().
     *
     * @return external_description
     */
    public static function get_question_types_returns() {
        return new external_single_structure(
            [
                'questiontypes' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Question type.'),
                    'List of question types.'
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Deletes a question item from the 360 feedback activity.
     *
     * @param int $id The item ID.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function delete_item($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::delete_item_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        $cm = get_coursemodule_from_instance('threesixo', $item->threesixo);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixo:edititems', $context);

        $result = api::delete_item($id);

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for delete_item().
     *
     * @return external_function_parameters
     */
    public static function delete_item_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.'),
            ]
        );
    }

    /**
     * Method results description for delete_item().
     *
     * @return external_description
     */
    public static function delete_item_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Move an item up.
     *
     * @param int $id The item ID.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function move_item_up($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::move_item_up_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        $cm = get_coursemodule_from_instance('threesixo', $item->threesixo);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixo:edititems', $context);

        $result = api::move_item_up($id);
        if (!$result) {
            $warnings[] = 'An error was encountered while trying to move the item up.';
        }

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for move_item_up().
     *
     * @return external_function_parameters
     */
    public static function move_item_up_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.'),
            ]
        );
    }

    /**
     * Method results description for move_item_up().
     *
     * @return external_description
     */
    public static function move_item_up_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Move an item down.
     *
     * @param int $id The item ID.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function move_item_down($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::move_item_down_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        $cm = get_coursemodule_from_instance('threesixo', $item->threesixo);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixo:edititems', $context);

        $result = api::move_item_down($id);
        if (!$result) {
            $warnings[] = 'An error was encountered while trying to move the item down.';
        }

        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for move_item_down().
     *
     * @return external_function_parameters
     */
    public static function move_item_down_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.'),
            ]
        );
    }

    /**
     * Method results description for move_item_down().
     *
     * @return external_description
     */
    public static function move_item_down_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Decline a feedback.
     *
     * @param int $statusid The submission ID.
     * @param string $reason The reason why the feedback is being declined.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function decline_feedback($statusid, $reason) {
        $warnings = [];

        $params = external_api::validate_parameters(self::decline_feedback_parameters(), [
            'statusid' => $statusid,
            'declinereason' => $reason,
        ]);

        $statusid = $params['statusid'];
        $reason = $params['declinereason'];

        // Validate context.
        $submission = api::get_submission($statusid);
        $cmrecord = get_coursemodule_from_instance('threesixo', $submission->threesixo);
        $cm = cm_info::create($cmrecord);

        // Make sure that the user can provide feedback to the feedback recipient in the submission before undoing anything.
        if (!api::can_provide_feedback_to_user($cm, $submission->touser)) {
            throw new moodle_exception('errorcannotprovidefeedbacktouser', 'threesixo');
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $result = api::decline_feedback($statusid, $reason);
        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for decline_feedback().
     *
     * @return external_function_parameters
     */
    public static function decline_feedback_parameters() {
        return new external_function_parameters(
            [
                'statusid' => new external_value(PARAM_INT, 'The submission ID.'),
                'declinereason' => new external_value(PARAM_TEXT, 'The reason for declining the feedback request.', VALUE_DEFAULT),
            ]
        );
    }

    /**
     * Method results description for decline_feedback().
     *
     * @return external_description
     */
    public static function decline_feedback_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Undo declined feedback.
     *
     * @param int $statusid The submission ID.
     * @return array
     */
    public static function undo_decline($statusid) {
        global $USER;

        $warnings = [];

        $params = external_api::validate_parameters(self::undo_decline_parameters(), [
            'statusid' => $statusid,
        ]);

        $statusid = $params['statusid'];

        // Get the submission record.
        $submission = api::get_submission($statusid);

        // Get this 360 instance.
        $threesixo = api::get_instance($submission->threesixo);

        // Validate context.
        $cmrecord = get_coursemodule_from_instance('threesixo', $submission->threesixo);
        $cm = cm_info::create($cmrecord);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Make sure that the user can provide feedback to the feedback recipient in the submission before declining anything.
        if (!api::can_provide_feedback_to_user($cm, $submission->touser)) {
            throw new moodle_exception('errorcannotprovidefeedbacktouser', 'threesixo');
        }

        // Make sure unauthorised users can't undo someone else's declined feedback.
        if ($submission->fromuser != $USER->id) {
            require_capability('moodle/course:manageactivities', $context);
        }

        // Make sure this instance allows declined feedback to be undone.
        if ($threesixo->undodecline != api::UNDO_DECLINE_ALLOW) {
            throw new moodle_exception('This 360-degree feedback activity does not allow declined feedback to be undone.');
        }

        $result = api::set_completion($statusid, api::STATUS_PENDING);
        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for undo_decline().
     *
     * @return external_function_parameters
     */
    public static function undo_decline_parameters() {
        return new external_function_parameters(
            [
                'statusid' => new external_value(PARAM_INT, 'The submission ID.'),
            ]
        );
    }

    /**
     * Method results description for undo_decline().
     *
     * @return external_description
     */
    public static function undo_decline_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The processing result.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Fetches template data for the list participants the user will provide feedback to.
     *
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function data_for_participant_list($threesixtyid) {
        global $PAGE, $USER;
        $warnings = [];
        $params = external_api::validate_parameters(self::data_for_participant_list_parameters(), [
            'threesixtyid' => $threesixtyid,
        ]);

        $threesixtyid = $params['threesixtyid'];
        $coursecm = get_course_and_cm_from_instance($threesixtyid, 'threesixo');
        $context = context_module::instance($coursecm[1]->id);
        self::validate_context($context);
        $renderer = $PAGE->get_renderer('mod_threesixo');
        $threesixty = api::get_instance($threesixtyid);
        $participants = api::get_participants($threesixty->id, $USER->id, $threesixty->with_self_review);
        $isopen = api::is_open($threesixty);
        $canviewreports = api::can_view_reports($context);
        $listparticipants = new list_participants($threesixty, $USER->id, $participants, $canviewreports, $isopen);
        $data = $listparticipants->export_for_template($renderer);
        return [
            'threesixtyid' => $data->threesixtyid,
            'participants' => $data->participants,
            'canperformactions' => $data->canperformactions,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parameter description for data_for_participant_list().
     *
     * @return external_function_parameters
     */
    public static function data_for_participant_list_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
            ]
        );
    }

    /**
     * Method results description for data_for_participant_list().
     *
     * @return external_description
     */
    public static function data_for_participant_list_returns() {
        return new external_single_structure(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
                'participants' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'name' => new external_value(PARAM_TEXT, 'The target participant name.'),
                            'statusid' => new external_value(PARAM_INT, 'The submission ID', VALUE_OPTIONAL),
                            'statuspending' => new external_value(PARAM_BOOL, 'Pending status', VALUE_DEFAULT, false),
                            'statusinprogress' => new external_value(PARAM_BOOL, 'In progress status', VALUE_DEFAULT, false),
                            'statusdeclined' => new external_value(PARAM_BOOL, 'Declined status', VALUE_DEFAULT, false),
                            'statuscompleted' => new external_value(PARAM_BOOL, 'Completed status', VALUE_DEFAULT, false),
                            'statusviewonly' => new external_value(PARAM_BOOL, 'View only status', VALUE_DEFAULT, false),
                            'viewlink' => new external_value(PARAM_RAW, 'Flag for view button.', VALUE_OPTIONAL, false),
                            'respondlink' => new external_value(PARAM_URL, 'Questionnaire URL.', VALUE_OPTIONAL),
                            'declinelink' => new external_value(PARAM_BOOL, 'Flag for decline button.', VALUE_OPTIONAL, false),
                            'undodeclinelink' => new external_value(PARAM_BOOL, 'Flag for the undo decline button.', VALUE_OPTIONAL,
                                false),
                        ]
                    )
                ),
                'canperformactions' => new external_value(PARAM_BOOL, 'Whether actions should be displayed or not'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Parameter description for save_responses().
     *
     * @return external_function_parameters
     */
    public static function save_responses_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback identifier.'),
                'touserid' => new external_value(PARAM_INT, 'The user identifier for the feedback subject.'),
                'responses' => new external_multiple_structure(
                    new external_single_structure([
                        'item' => new external_value(PARAM_INT, 'The item ID.'),
                        'value' => new external_value(PARAM_TEXT, 'The response value with the key as the item ID.'),
                    ]), 'Array of response objects containing item and value'
                ),
                'complete' => new external_value(PARAM_BOOL, 'Whether to mark the submission as complete.'),
            ]
        );
    }

    /**
     * Save a user's responses to the feedback questions for another user.
     *
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @param int $touserid The recipient of the feedback responses.
     * @param array $responses The responses data.
     * @param bool $complete Whether to mark the submission as complete.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function save_responses($threesixtyid, $touserid, $responses, $complete) {
        global $USER;
        $warnings = [];

        $cmrecord = get_coursemodule_from_instance('threesixo', $threesixtyid);
        $cm = cm_info::create($cmrecord);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        $redirecturl = new moodle_url('/mod/threesixo/view.php');
        $redirecturl->param('id', $cm->id);

        $params = external_api::validate_parameters(self::save_responses_parameters(), [
            'threesixtyid' => $threesixtyid,
            'touserid' => $touserid,
            'responses' => $responses,
            'complete' => $complete,
        ]);

        $threesixtyid = $params['threesixtyid'];
        $touserid = $params['touserid'];
        $responses = $params['responses'];
        $complete = $params['complete'];

        // Make sure that the user can provide feedback to the feedback recipient in the submission before saving the responses.
        if (!api::can_provide_feedback_to_user($cm, $touserid)) {
            throw new moodle_exception('errorcannotprovidefeedbacktouser', 'threesixo');
        }

        $responsesarray = [];
        foreach ($responses as $response) {
            $responsesarray[$response['item']] = $response['value'];
        }

        $result = api::save_responses($threesixtyid, $touserid, $responsesarray);

        if ($complete) {
            $items = api::get_items($threesixtyid);
            foreach ($items as $item) {
                if ($responsesarray[$item->id] === null) {
                    $complete = false;
                    break;
                }
            }

            if ($complete && $submission = api::get_submission_by_params($threesixtyid, $USER->id, $touserid)) {
                // Anonymise responses, if necessary.
                $result &= api::anonymise_responses($threesixtyid, $USER->id, $touserid);
                // Mark the submission of this feedback to the target user as completed.
                $result &= api::set_completion($submission->id, api::STATUS_COMPLETE);
            }
        }

        return [
            'result' => $result,
            'redirurl' => $redirecturl->out(),
            'warnings' => $warnings,
        ];
    }

    /**
     * Method results description for save_responses().
     *
     * @return external_description
     */
    public static function save_responses_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'redirurl' => new external_value(PARAM_URL, 'The redirect URL.'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Parameter description for get_responses().
     *
     * @return external_function_parameters
     */
    public static function get_responses_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback identifier.'),
                'fromuserid' => new external_value(PARAM_INT, 'The user identifier of the respondent.'),
                'touserid' => new external_value(PARAM_INT, 'The user identifier for the feedback subject.'),
            ]
        );
    }

    /**
     * Fetches the user's responses to a feedback for a specific user.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @param int $fromuserid The ID of the user who is responding to the feedback.
     * @param int $touserid The user ID of the recipient of the feedback.
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function get_responses($threesixtyid, $fromuserid, $touserid) {
        $warnings = [];

        $cm = get_coursemodule_from_instance('threesixo', $threesixtyid);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);
        $redirecturl = new moodle_url('/mod/threesixo/view.php');
        $redirecturl->param('id', $cmid);

        $params = external_api::validate_parameters(self::get_responses_parameters(), [
            'threesixtyid' => $threesixtyid,
            'fromuserid' => $fromuserid,
            'touserid' => $touserid,
        ]);

        $threesixtyid = $params['threesixtyid'];
        $fromuserid = $params['fromuserid'];
        $touserid = $params['touserid'];

        $responses = api::get_responses($threesixtyid, $fromuserid, $touserid);

        return [
            'responses' => $responses,
            'redirurl' => $redirecturl->out(),
            'warnings' => $warnings,
        ];
    }

    /**
     * Method results description for get_responses().
     *
     * @return external_description
     */
    public static function get_responses_returns() {
        return new external_single_structure(
            [
                'responses' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'The response ID.'),
                            'item' => new external_value(PARAM_INT, 'The item ID for the response.'),
                            'value' => new external_value(PARAM_TEXT, 'The the value for the response.'),
                        ]
                    )
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
