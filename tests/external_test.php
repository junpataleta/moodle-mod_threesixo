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

use advanced_testcase;
use core_external\external_api;
use mod_threesixo_generator;
use moodle_exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * External API tests.
 *
 * @package    mod_threesixo
 * @copyright  2025 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(external::class)]
final class external_test extends advanced_testcase {
    /**
     * Test getting questions.
     */
    public function test_get_questions(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $generator->create_course();
        $threesixogenerator = $generator->get_plugin_generator('mod_threesixo');
        $threesixogenerator->create_question(['question' => 'Question 1', 'type' => api::QTYPE_RATED]);
        $threesixogenerator->create_question(['question' => 'Question 2', 'type' => api::QTYPE_COMMENT]);

        $result = external::get_questions(false);
        $result = external_api::clean_returnvalue(external::get_questions_returns(), $result);

        $this->assertArrayHasKey('questions', $result);
        $this->assertCount(2, $result['questions']);
    }

    /**
     * Test adding, updating, and deleting a question.
     */
    public function test_question_crud(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $course = $generator->create_course();
        $threesixo = $generator->create_module('threesixo', ['course' => $course->id], [
            'ratedquestions' => ['R1'],
            'commentquestions' => [],
        ]);

        /** @var mod_threesixo_generator $threesixogenerator */
        $threesixogenerator = $generator->get_plugin_generator('mod_threesixo');
        $questionid = $threesixogenerator->create_question([
            'question' => 'Original question',
            'type' => api::QTYPE_RATED,
            'createdby' => get_admin()->id,
        ]);

        $addresult = external::add_question('Added question', api::QTYPE_RATED, $threesixo->id);
        $addresult = external_api::clean_returnvalue(external::add_question_returns(), $addresult);
        $this->assertNotEmpty($addresult['questionid']);

        $updateresult = external::update_question($questionid, 'Updated question', api::QTYPE_COMMENT, $threesixo->id);
        $updateresult = external_api::clean_returnvalue(external::update_question_returns(), $updateresult);
        $this->assertTrue($updateresult['result']);

        $deleteresult = external::delete_question($questionid, $threesixo->id);
        $deleteresult = external_api::clean_returnvalue(external::delete_question_returns(), $deleteresult);
        $this->assertTrue($deleteresult['result']);
    }

    /**
     * Test participant list data.
     */
    public function test_data_for_participant_list(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $course = $generator->create_course();
        /** @var mod_threesixo_generator $threesixogenerator */
        $threesixogenerator = $generator->get_plugin_generator('mod_threesixo');
        $threesixo = $threesixogenerator->create_instance(['course' => $course->id]);

        $result = external::data_for_participant_list($threesixo->id);
        $result = external_api::clean_returnvalue(external::data_for_participant_list_returns(), $result);

        $this->assertArrayHasKey('participants', $result);
        $this->assertArrayHasKey('threesixtyid', $result);
        // The web service layer casts this to an integer, which is what a client actually receives.
        $this->assertSame((int) $threesixo->id, $result['threesixtyid']);
    }

    /**
     * Data provider for test_item_editing_locked_after_responses.
     *
     * @return array
     */
    public static function item_action_provider(): array {
        return [
            'Set items (add/remove questions)' => ['set_items'],
            'Delete item' => ['delete_item'],
            'Move item up' => ['move_item_up'],
            'Move item down' => ['move_item_down'],
        ];
    }

    /**
     * The questionnaire items cannot be modified once respondents have started providing feedback.
     *
     * @param string $action The item-modifying external method to exercise.
     */
    #[DataProvider('item_action_provider')]
    #[RunInSeparateProcess]
    public function test_item_editing_locked_after_responses(string $action): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $s1 = $generator->create_user();
        $s2 = $generator->create_user();
        $generator->enrol_user($s1->id, $course->id, 'student');
        $generator->enrol_user($s2->id, $course->id, 'student');

        $options = [
            'ratedquestions' => ['R1', 'R2', 'R3'],
            'commentquestions' => [],
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', ['course' => $course->id], $options);
        $items = api::get_items($threesixo->id);
        $firstitem = reset($items);

        // Sanity check: while there are no responses yet, the items can be edited.
        $this->assertFalse(api::has_responses($threesixo->id));

        // A respondent submits a response.
        $DB->insert_record('threesixo_response', (object) [
            'threesixo' => $threesixo->id,
            'item' => $firstitem->id,
            'fromuser' => $s1->id,
            'touser' => $s2->id,
            'value' => 5,
        ]);
        $this->assertTrue(api::has_responses($threesixo->id));

        $this->setUser($teacher);

        // Every item-modifying operation should now be rejected.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('cannotmodifyitemswithresponses', 'mod_threesixo'));

        self::call_item_action($action, $threesixo->id, $firstitem);
    }

    /**
     * The item-modifying web services still work while no feedback has been provided yet.
     *
     * This is the counterpart of test_item_editing_locked_after_responses, so that a regression that always reports the
     * items as locked cannot pass unnoticed.
     *
     * @param string $action The item-modifying external method to exercise.
     */
    #[DataProvider('item_action_provider')]
    #[RunInSeparateProcess]
    public function test_item_editing_allowed_without_responses(string $action): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        $options = [
            'ratedquestions' => ['R1', 'R2', 'R3'],
            'commentquestions' => [],
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', ['course' => $course->id], $options);
        $items = api::get_items($threesixo->id);
        $firstitem = reset($items);

        $this->assertFalse(api::has_responses($threesixo->id));

        $this->setUser($teacher);

        $result = self::call_item_action($action, $threesixo->id, $firstitem);
        $this->assertTrue($result['result']);
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Sets up a 360-degree feedback instance with two participants, ready for providing feedback.
     *
     * @param bool $anonymous Whether the instance is anonymous.
     * @return array An array containing the instance, the respondent and the feedback recipient.
     */
    protected function setup_feedback_instance(bool $anonymous): array {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $course = $generator->create_course();
        $s1 = $generator->create_user();
        $s2 = $generator->create_user();
        $generator->enrol_user($s1->id, $course->id, 'student');
        $generator->enrol_user($s2->id, $course->id, 'student');

        $threesixo = $generator->create_module('threesixo', [
            'course' => $course->id,
            'anonymous' => $anonymous,
        ], [
            'ratedquestions' => ['R1', 'R2'],
            'commentquestions' => ['C1'],
        ]);

        api::generate_360_feedback_statuses($threesixo->id, $s1->id);

        return [$threesixo, $s1, $s2];
    }

    /**
     * Data provider for the save_responses tests.
     *
     * @return array
     */
    public static function anonymous_provider(): array {
        return [
            'Non-anonymous feedback' => [false],
            'Anonymous feedback' => [true],
        ];
    }

    /**
     * A submission is marked as completed when every item has been answered.
     *
     * @param bool $anonymous Whether the instance is anonymous.
     */
    #[DataProvider('anonymous_provider')]
    #[RunInSeparateProcess]
    public function test_save_responses_complete(bool $anonymous): void {
        global $DB;

        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance($anonymous);

        $responses = [];
        foreach (api::get_items($threesixo->id) as $item) {
            $responses[] = [
                'item' => (int) $item->id,
                'value' => $item->type == api::QTYPE_RATED ? '5' : 'Some feedback',
            ];
        }

        $this->setUser($s1);
        $result = external::save_responses($threesixo->id, $s2->id, $responses, true);
        $result = external_api::clean_returnvalue(external::save_responses_returns(), $result);
        // The result is built with a bitwise AND, so it is an int until it is cast by the web service layer.
        $this->assertTrue((bool) $result['result']);

        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        $this->assertEquals(api::STATUS_COMPLETE, $submission->status);

        // The responses of a completed anonymous submission are anonymised, otherwise the respondent is retained.
        $expectedfromuser = $anonymous ? 0 : $s1->id;
        $fromusers = $DB->get_fieldset_select(
            'threesixo_response',
            'DISTINCT fromuser',
            'threesixo = :threesixo AND touser = :touser',
            ['threesixo' => $threesixo->id, 'touser' => $s2->id]
        );
        $this->assertEquals([$expectedfromuser], $fromusers);
    }

    /**
     * A submission is not marked as completed while an item has not been answered.
     *
     */
    #[RunInSeparateProcess]
    public function test_save_responses_incomplete(): void {
        global $DB;

        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance(true);

        // Leave one rated question unanswered, which is what the questionnaire sends for an unanswered rating.
        $responses = [];
        $skipped = true;
        foreach (api::get_items($threesixo->id) as $item) {
            $value = $item->type == api::QTYPE_RATED ? '5' : 'Some feedback';
            if ($skipped && $item->type == api::QTYPE_RATED) {
                $value = null;
                $skipped = false;
            }
            $responses[] = ['item' => (int) $item->id, 'value' => $value];
        }

        $this->setUser($s1);
        $result = external::save_responses($threesixo->id, $s2->id, $responses, true);
        $result = external_api::clean_returnvalue(external::save_responses_returns(), $result);
        // The result is built with a bitwise AND, so it is an int until it is cast by the web service layer.
        $this->assertTrue((bool) $result['result']);

        // The submission is not completed, and the responses are not anonymised.
        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        $this->assertNotEquals(api::STATUS_COMPLETE, $submission->status);
        $this->assertTrue($DB->record_exists('threesixo_response', [
            'threesixo' => $threesixo->id,
            'touser' => $s2->id,
            'fromuser' => $s1->id,
        ]));
    }

    /**
     * A submission is not marked as completed when an item is missing from the responses altogether.
     *
     */
    #[RunInSeparateProcess]
    public function test_save_responses_missing_item(): void {
        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance(false);

        // Omit the last item entirely from the submitted responses.
        $items = api::get_items($threesixo->id);
        array_pop($items);
        $responses = [];
        foreach ($items as $item) {
            $responses[] = [
                'item' => (int) $item->id,
                'value' => $item->type == api::QTYPE_RATED ? '5' : 'Some feedback',
            ];
        }

        $this->setUser($s1);
        $result = external::save_responses($threesixo->id, $s2->id, $responses, true);
        $result = external_api::clean_returnvalue(external::save_responses_returns(), $result);
        // The result is built with a bitwise AND, so it is an int until it is cast by the web service layer.
        $this->assertTrue((bool) $result['result']);

        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        $this->assertNotEquals(api::STATUS_COMPLETE, $submission->status);
    }

    /**
     * Calls the given item-modifying external method.
     *
     * @param string $action The item-modifying external method to call.
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @param \stdClass $item The item to act on.
     * @return array The external method's result.
     */
    protected static function call_item_action(string $action, int $threesixtyid, \stdClass $item): array {
        switch ($action) {
            case 'set_items':
                return external::set_items($threesixtyid, [$item->questionid]);
            case 'delete_item':
                return external::delete_item($item->id);
            case 'move_item_up':
                return external::move_item_up($item->id);
            case 'move_item_down':
                return external::move_item_down($item->id);
        }

        throw new \coding_exception('Unknown item action: ' . $action);
    }

    /**
     * Test fetching the question types offered when adding a question.
     */
    public function test_get_question_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $result = external::get_question_types();
        $result = external_api::clean_returnvalue(external::get_question_types_returns(), $result);

        // The service returns the type labels only, so a client has to rely on each label's position matching
        // the question type constant it belongs to.
        $this->assertSame(
            [
                api::QTYPE_RATED => get_string('qtyperated', 'mod_threesixo'),
                api::QTYPE_COMMENT => get_string('qtypecomment', 'mod_threesixo'),
            ],
            $result['questiontypes']
        );
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Test fetching the items of a questionnaire.
     */
    public function test_get_items(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $threesixo = $generator->create_module('threesixo', ['course' => $course->id], [
            'ratedquestions' => ['R1', 'R2'],
            'commentquestions' => ['C1'],
        ]);

        $result = external::get_items($threesixo->id);
        $result = external_api::clean_returnvalue(external::get_items_returns(), $result);

        $this->assertCount(3, $result['items']);
        $this->assertEmpty($result['warnings']);

        // The items come back in position order, carrying their question text and type.
        $positions = array_column($result['items'], 'position');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
        $this->assertSame(['R1', 'R2', 'C1'], array_column($result['items'], 'question'));
    }

    /**
     * Test fetching a respondent's saved responses.
     */
    public function test_get_responses(): void {
        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance(false);

        $items = api::get_items($threesixo->id);
        $responses = [];
        foreach ($items as $item) {
            $responses[] = [
                'item' => (int) $item->id,
                'value' => $item->type == api::QTYPE_RATED ? '5' : 'Some feedback',
            ];
        }

        $this->setUser($s1);
        external::save_responses($threesixo->id, $s2->id, $responses, false);

        $result = external::get_responses($threesixo->id, $s1->id, $s2->id);
        $result = external_api::clean_returnvalue(external::get_responses_returns(), $result);

        $this->assertCount(count($items), $result['responses']);
        $this->assertEmpty($result['warnings']);

        // Each saved value comes back against its own item.
        $values = array_column($result['responses'], 'value', 'item');
        foreach ($items as $item) {
            $expected = $item->type == api::QTYPE_RATED ? '5' : 'Some feedback';
            $this->assertSame($expected, $values[$item->id]);
        }
    }

    /**
     * Test declining feedback, and then undoing that.
     */
    public function test_decline_feedback_and_undo(): void {
        global $DB;

        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance(false);

        // Undoing a declined submission is only allowed when the activity says so.
        $DB->set_field('threesixo', 'undodecline', api::UNDO_DECLINE_ALLOW, ['id' => $threesixo->id]);

        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        $this->setUser($s1);

        $result = external::decline_feedback($submission->id, 'Not working with them any more');
        $result = external_api::clean_returnvalue(external::decline_feedback_returns(), $result);
        $this->assertTrue($result['result']);
        $this->assertEmpty($result['warnings']);
        $this->assertEquals(api::STATUS_DECLINED, api::get_submission($submission->id)->status);

        $result = external::undo_decline($submission->id);
        $result = external_api::clean_returnvalue(external::undo_decline_returns(), $result);
        $this->assertTrue($result['result']);
        $this->assertEmpty($result['warnings']);
        $this->assertEquals(api::STATUS_PENDING, api::get_submission($submission->id)->status);
    }

    /**
     * Test that a declined submission cannot be undone when the activity does not allow it.
     */
    public function test_undo_decline_not_allowed(): void {
        global $DB;

        $this->resetAfterTest();
        [$threesixo, $s1, $s2] = $this->setup_feedback_instance(false);
        $DB->set_field('threesixo', 'undodecline', api::UNDO_DECLINE_DISALLOW, ['id' => $threesixo->id]);

        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        $this->setUser($s1);
        external::decline_feedback($submission->id, 'No longer relevant');

        $this->expectException(moodle_exception::class);
        external::undo_decline($submission->id);
    }
}
