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

/**
 * Privacy provider tests.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\writer;
use core_privacy\tests\request\content_writer;
use mod_threesixo\api;
use mod_threesixo\helper;
use mod_threesixo\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_threesixo_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The student object. */
    protected $student;

    /** @var stdClass The threesixo object. */
    protected $threesixo;

    /** @var stdClass The course object. */
    protected $course;

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_threesixo');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(2, $itemcollection);

        $tables = ['threesixo_submission', 'threesixo_response'];
        $submissionfields = [
            'threesixo',
            'fromuser',
            'touser',
            'status',
            'remarks',
        ];
        $responsefields = [
            'threesixo',
            'item',
            'fromuser',
            'touser',
            'value',
        ];
        foreach ($itemcollection as $table) {
            $this->assertContains($table->get_name(), $tables);

            if ($table->get_name() == 'threesixo_submission') {
                $fields = $submissionfields;
                $this->assertEquals('privacy:metadata:threesixo_submission', $table->get_summary());
            } else {
                $fields = $responsefields;
                $this->assertEquals('privacy:metadata:threesixo_response', $table->get_summary());
            }
            $privacyfields = $table->get_privacy_fields();
            foreach ($privacyfields as $key => $field) {
                $this->assertContains($key, $fields);
            }
        }
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $this->setup_data();

        $cm = get_coursemodule_from_instance('threesixo', $this->threesixo->id);
        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_user_data() {
        $this->setup_data();

        $id = $this->threesixo->id;
        $cm = get_coursemodule_from_instance('threesixo', $id);
        $cmcontext = context_module::instance($cm->id);

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($recipient->userid);

        // Export all of the data for the context.
        $this->export_context_data_for_user($this->student->id, $cmcontext, 'mod_threesixo');
        /** @var content_writer $writer */
        $writer = writer::with_context($cmcontext);
        $this->assertTrue($writer->has_any_data());

        // Check exported submissions data for the feedback the user has given.
        $subcontext = [
            get_string('feedbackgiven', 'mod_threesixo'),
            get_string('submissions', 'mod_threesixo')
        ];
        $data = $writer->get_data($subcontext);
        // We should have 2.
        $this->assertEquals($this->threesixo->name, $data->name);
        $this->assertCount(2, $data->submissions);
        foreach ($data->submissions as $submission) {
            if ($submission['recipient'] == $recipient->userid) {
                $this->assertEquals(helper::get_status_string(api::STATUS_COMPLETE), $submission['status']);
            } else {
                $this->assertEquals(helper::get_status_string(api::STATUS_PENDING), $submission['status']);
            }
        }

        // Check responses you have given.
        $subcontext = [
            get_string('feedbackgiven', 'mod_threesixo'),
            get_string('responses', 'mod_threesixo')
        ];
        $data = $writer->get_data($subcontext);
        // There should be 1 set of response for each question.
        foreach ($data->questions as $question) {
            $this->assertCount(1, $question['responses']);
        }

        // Check exported submissions data for the feedback the user has received.
        $subcontext = [
            get_string('feedbackreceived', 'mod_threesixo'),
            get_string('submissions', 'mod_threesixo')
        ];
        $data = $writer->get_data($subcontext);
        // We should have 2 from the other 2 students and should be still pending.
        $this->assertEquals($this->threesixo->name, $data->name);
        $this->assertCount(2, $data->submissions);
        foreach ($data->submissions as $submission) {
            $this->assertEquals(helper::get_status_string(api::STATUS_PENDING), $submission['status']);
        }

        // The user has not yet received any feedback from the other participants.
        $subcontext = [
            get_string('feedbackreceived', 'mod_threesixo'),
            get_string('responses', 'mod_threesixo')
        ];
        $data = $writer->get_data($subcontext);
        $this->assertEmpty($data);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->setup_data();

        $id = $this->threesixo->id;

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($recipient->userid);

        // Before deletion, we should have 6 responses.
        $count = $DB->count_records('threesixo_submission', ['threesixo' => $id]);
        $this->assertEquals(6, $count);

        // Before deletion, we should have 6 responses.
        $count = $DB->count_records('threesixo_response', ['threesixo' => $id]);
        $this->assertNotEmpty($count);

        // Delete data based on context.
        $cm = get_coursemodule_from_instance('threesixo', $id);
        $cmcontext = context_module::instance($cm->id);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // After deletion, the threesixo submissions should have been deleted.
        $count = $DB->count_records('threesixo_submission', ['threesixo' => $id]);
        $this->assertEquals(0, $count);

        // Responses as well.
        $count = $DB->count_records('threesixo_response', ['threesixo' => $id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user_() {
        global $DB;

        $this->setup_data(false);

        $id = $this->threesixo->id;

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($recipient->userid);

        // Before deletion, we should have 6 responses.
        $count = $DB->count_records('threesixo_submission', ['threesixo' => $id]);
        $this->assertEquals(6, $count);

        // Before deletion, we should have 6 responses.
        $count = $DB->count_records('threesixo_response', ['threesixo' => $id]);
        $this->assertNotEmpty($count);

        // Delete users's data.
        $cm = get_coursemodule_from_instance('threesixo', $id);
        $cmcontext = context_module::instance($cm->id);
        $contextlist = new approved_contextlist($this->student, 'threesixo',
            [context_system::instance()->id, $cmcontext->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the threesixo submissions should have been deleted.
        $select = 'threesixo = :threesixo AND (fromuser = :fromuser OR touser = :touser)';
        $userid = $this->student->id;
        $params = ['threesixo' => $id, 'fromuser' => $userid, 'touser' => $userid];

        $count = $DB->count_records_select('threesixo_submission', $select, $params);
        $this->assertEquals(0, $count);

        $count = $DB->count_records_select('threesixo_response', $select, $params);
        $this->assertEquals(0, $count);
    }

    /**
     * Generate a course, enrol users and a 360-degree feedback instance.
     *
     * @param bool $anonymous Whether to set up an anonymous feedback.
     */
    protected function setup_data($anonymous = true) {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        // Create a 360-degree feedback instance in the course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $record = [
            'course' => $course->id,
            'participantrole' => $studentrole->id, // Only for students.
            'anonymous' => $anonymous,
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $record);

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $studentids = [];
        for ($i = 0; $i < 3; $i++) {
            // Create a student.
            $student = $generator->create_user();

            // Enrol the student manually to the course.
            $generator->enrol_user($student->id, $course->id, 'student');

            $studentids[] = $student->id;
        }

        // Generate feedback statuses for each student.
        foreach ($studentids as $id) {
            api::generate_360_feedback_statuses($threesixo->id, $id);
        }

        // Set the last student as our subject user.
        $this->student = $student;

        // Login as this student.
        $this->setUser($student);

        $this->threesixo = $threesixo;
        $this->course = $course;
    }

    /**
     * Simulate the user giving feedback to another user.
     *
     * @param int $recipientid The recipient ID.
     */
    protected function give_feedback_to_user($recipientid) {
        $id = $this->threesixo->id;

        $items = api::get_items($this->threesixo->id);
        $responses = [];
        foreach ($items as $item) {
            $response = 'Response to item ' . $item->id;
            if ($item->type == api::QTYPE_RATED) {
                $response = rand(0, 6);
            }
            $responses[$item->id] = $response;
        }
        api::save_responses($id, $recipientid, $responses);
        $submission = api::get_submission_by_params($id, $this->student->id, $recipientid);
        api::set_completion($submission->id, api::STATUS_COMPLETE);
    }
}
