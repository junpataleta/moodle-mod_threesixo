<?php
/**
 * Created by PhpStorm.
 * User: jun
 * Date: 15/02/16
 * Time: 3:19 PM
 */
namespace mod_threesixty;

use context_module;
use context_user;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use mod_threesixty\output\list_participants;
use moodle_url;
use stdClass;

/**
 * Class external.
 *
 * The external API for the 360-degree feedback module.
 *
 * @package mod_threesixty
 */
class external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_questions_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function get_questions() {
        $warnings = [];

        $questions = api::get_questions();

        return [
            'questions' => $questions,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method result value
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
                            'typeName' => new external_value(PARAM_TEXT, 'The question type text value.')
                        ]
                    )
                ),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function add_question($question, $type) {
        global $USER;

        $warnings = [];

        $params = external_api::validate_parameters(self::add_question_parameters(), ['question' => $question, 'type' => $type]);

        // Validate context and capability.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        require_capability('mod/threesixty:editquestions', $context);

        $dataobj = new stdClass();
        $dataobj->question = $params['question'];
        $dataobj->type = $params['type'];
        $questionid = api::add_question($dataobj);

        return [
            'questionid' => $questionid,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_question_parameters() {
        return new external_function_parameters([
            'question' => new external_value(PARAM_TEXT, 'The question text.'),
            'type' => new external_value(PARAM_INT, 'The question type.')
        ]);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_question_returns() {
        return new external_single_structure(
            [
                'questionid' => new external_value(PARAM_INT, 'The question ID of the added question.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function update_question($id, $question, $type) {
        global $USER;

        $warnings = [];

        $params = external_api::validate_parameters(self::update_question_parameters(), [
                'id' => $id,
                'question' => $question,
                'type' => $type
            ]
        );

        // Validate context and capability.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        require_capability('mod/threesixty:editquestions', $context);

        $dataobj = new stdClass();
        $dataobj->id = $params['id'];
        $dataobj->question = $params['question'];
        $dataobj->type = $params['type'];

        $result = api::update_question($dataobj);

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function update_question_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The question ID.'),
            'question' => new external_value(PARAM_TEXT, 'The question text.'),
            'type' => new external_value(PARAM_INT, 'The question type.')
        ]);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function update_question_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The question update processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Delete a question from the question bank.
     *
     * @param int $id The question ID.
     * @return array
     */
    public static function delete_question($id) {
        global $USER;

        $warnings = [];

        $params = external_api::validate_parameters(self::delete_question_parameters(), ['id' => $id]);

        $id = $params['id'];

        // Validate context and capability.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        require_capability('mod/threesixty:editquestions', $context);

        $result = api::delete_question($id);

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_question_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The question ID.')
        ]);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_question_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The question update processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * @param $threesixtyid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function get_items($threesixtyid) {
        $warnings = [];
        $params = external_api::validate_parameters(self::get_items_parameters(), ['threesixtyid' => $threesixtyid]);

        $items = api::get_items($params['threesixtyid']);

        return [
            'items' => $items,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_items_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.')
            ]
        );
    }

    /**
     * Returns description of method result value
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
                            'typetext' => new external_value(PARAM_TEXT, 'The question type text value.')
                        ]
                    )
                ),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * @param $threesixtyid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function set_items($threesixtyid, $questionids) {
        $warnings = [];
        $params = external_api::validate_parameters(self::set_items_parameters(), [
            'threesixtyid' => $threesixtyid,
            'questionids' => $questionids
        ]);

        // Validate context and capability.
        list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixty:edititems', $context);

        $result = api::set_items($params['threesixtyid'], $params['questionids']);

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function set_items_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' => new external_value(PARAM_INT, 'The 360-degree feedback ID.'),
                'questionids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'The question ID.')
                )
            ]
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function set_items_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_question_types_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @param $threesixtyid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function get_question_types() {
        $warnings = [];
        $result = api::get_question_types();
        return [
            'questiontypes' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_question_types_returns() {
        return new external_single_structure(
            [
                'questiontypes' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Question type.'),
                    'List of question types.'
                ),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function delete_item($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::delete_item_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        list($course, $cm) = get_course_and_cm_from_instance($item->threesixty, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixty:edititems', $context);

        $result = api::delete_item($id);

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_item_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.')
            ]
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_item_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Move an item up.
     *
     * @return array
     */
    public static function move_item_up($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::move_item_up_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        list($course, $cm) = get_course_and_cm_from_instance($item->threesixty, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixty:edititems', $context);

        $result = api::move_item_up($id);
        if (!$result) {
            $warnings[] = 'An error was encountered while trying to move the item up.';
        }

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function move_item_up_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.')
            ]
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function move_item_up_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Move an item down.
     *
     * @return array
     */
    public static function move_item_down($id) {
        $warnings = [];

        $params = external_api::validate_parameters(self::move_item_down_parameters(), ['itemid' => $id]);

        $id = $params['itemid'];

        // Validate context and capability.
        $item = api::get_item_by_id($id);
        list($course, $cm) = get_course_and_cm_from_instance($item->threesixty, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        require_capability('mod/threesixty:edititems', $context);

        $result = api::move_item_down($id);
        if (!$result) {
            $warnings[] = 'An error was encountered while trying to move the item down.';
        }

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function move_item_down_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'The item ID.')
            ]
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function move_item_down_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Decline a feedback.
     *
     * @return array
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
        list($course, $cm) = get_course_and_cm_from_instance($submission->threesixty, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);

        $result = api::decline_feedback($statusid, $reason);
        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function decline_feedback_parameters() {
        return new external_function_parameters(
            [
                'statusid' => new external_value(PARAM_INT, 'The item ID.'),
                'declinereason' => new external_value(PARAM_TEXT, 'The reason for declining the feedback request.', VALUE_DEFAULT)
            ]
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function decline_feedback_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * @param $threesixtyid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function data_for_participant_list($threesixtyid) {
        global $PAGE, $USER;
        $warnings = [];
        $params = external_api::validate_parameters(self::data_for_participant_list_parameters(), [
            'threesixtyid' => $threesixtyid
        ]);

        $threesixtyid = $params['threesixtyid'];
        $coursecm = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
        $context = context_module::instance($coursecm[1]->id);
        self::validate_context($context);
        $renderer = $PAGE->get_renderer('mod_threesixty');
        $threesixty = api::get_instance($threesixtyid);
        $participants = api::get_participants($threesixty->id, $USER->id);
        $listparticipants = new list_participants($threesixty, $USER->id, $participants);
        $data = $listparticipants->export_for_template($renderer);
        return [
            'threesixtyid' => $data->threesixtyid,
            'participants' => $data->participants,
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method parameters
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
     * Returns description of method result value
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
                            'statuspending' => new external_value(PARAM_BOOL, 'Pending status', VALUE_DEFAULT, false),
                            'statusinprogress' => new external_value(PARAM_BOOL, 'In progress status', VALUE_DEFAULT, false),
                            'statusdeclined' => new external_value(PARAM_BOOL, 'Declined status', VALUE_DEFAULT, false),
                            'statuscompleted' => new external_value(PARAM_BOOL, 'Completed status', VALUE_DEFAULT, false),
                            'statusviewonly' => new external_value(PARAM_BOOL, 'View only status', VALUE_DEFAULT, false),
                            'viewlink' => new external_value(PARAM_RAW, 'Flag for view button.', VALUE_OPTIONAL, false),
                            'respondlink' => new external_value(PARAM_URL, 'Questionnaire URL.', VALUE_OPTIONAL),
                            'declinelink' => new external_value(PARAM_BOOL, 'Flag for decline button.', VALUE_OPTIONAL, false)
                        ]
                    )
                ),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function save_responses_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' =>  new external_value(PARAM_INT, 'The 360-degree feedback identifier.'),
                'touserid' => new external_value(PARAM_INT, 'The user identifier for the feedback subject.'),
                'responses' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'The response value with the key as the item ID.')
                ),
                'complete' => new external_value(PARAM_BOOL, 'Whether to mark the submission as complete.'),
            ]
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function save_responses($threesixtyid, $touserid, $responses, $complete) {
        global $USER;
        $warnings = [];

        list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);
        $redirecturl = new moodle_url('/mod/threesixty/view.php');
        $redirecturl->param('id', $cmid);

        $params = external_api::validate_parameters(self::save_responses_parameters(), [
            'threesixtyid' => $threesixtyid,
            'touserid' => $touserid,
            'responses' => $responses,
            'complete' => $complete
        ]);

        $threesixtyid = $params['threesixtyid'];
        $touserid = $params['touserid'];
        $responses = $params['responses'];
        $complete = $params['complete'];

        $result = api::save_responses($threesixtyid, $touserid, $responses);

        if ($complete) {
            $items = api::get_items($threesixtyid);
            foreach ($items as $item) {
                if ($responses[$item->id] === null) {
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
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function save_responses_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'The item deletion processing result.'),
                'redirurl' => new external_value(PARAM_URL, 'The redirect URL.'),
                'warnings' => new external_warnings()
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_responses_parameters() {
        return new external_function_parameters(
            [
                'threesixtyid' =>  new external_value(PARAM_INT, 'The 360-degree feedback identifier.'),
                'fromuserid' => new external_value(PARAM_INT, 'The user identifier of the respondent.'),
                'touserid' => new external_value(PARAM_INT, 'The user identifier for the feedback subject.'),
            ]
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function get_responses($threesixtyid, $fromuserid, $touserid) {
        $warnings = [];

        list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
        $cmid = $cm->id;
        $context = context_module::instance($cmid);
        self::validate_context($context);
        $redirecturl = new moodle_url('/mod/threesixty/view.php');
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
            'warnings' => $warnings
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_responses_returns() {
        return new external_single_structure(
            [
                'responses' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'The response ID.'),
                            'item' => new external_value(PARAM_TEXT, 'The item ID for the response.'),
                            'value' => new external_value(PARAM_TEXT, 'The the value for the response.'),
                        ]
                    )
                ),
                'warnings' => new external_warnings()
            ]
        );
    }
}
