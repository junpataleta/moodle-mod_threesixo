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

use context_module;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use core_privacy\tests\request\content_writer;
use mod_threesixo\privacy\provider;
use stdClass;

/**
 * Privacy provider tests class.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_threesixo\privacy\provider
 */
final class provider_test extends provider_testcase {

    /** @var stdClass The teacher in the course. */
    protected $teacher;

    /** @var stdClass The student that is going to be providing a feedback. */
    protected $student;

    /** @var stdClass The threesixo object. */
    protected $threesixo;

    /** @var stdClass The course object. */
    protected $course;

    /** @var array Students enrolled in the course. */
    protected $students = [];

    /**
     * Test for provider::get_metadata().
     *
     * @covers ::get_metadata
     */
    public function test_get_metadata(): void {
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
     *
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
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
     *
     * @covers ::export_user_data
     */
    public function test_export_user_data(): void {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        $this->setup_data(true, $studentrole->id);

        $id = $this->threesixo->id;
        $cm = get_coursemodule_from_instance('threesixo', $id);
        $cmcontext = context_module::instance($cm->id);

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($this->student->id, $recipient->userid);

        // Export all the data for the context.
        $this->export_context_data_for_user($this->student->id, $cmcontext, 'mod_threesixo');
        /** @var content_writer $writer */
        $writer = writer::with_context($cmcontext);
        $this->assertTrue($writer->has_any_data());

        // Check exported submissions data for the feedback the user has given.
        $subcontext = [
            get_string('feedbackgiven', 'mod_threesixo'),
            get_string('submissions', 'mod_threesixo'),
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
            get_string('responses', 'mod_threesixo'),
        ];
        $data = $writer->get_data($subcontext);
        // There should be 1 set of response for each question.
        foreach ($data->questions as $question) {
            $this->assertCount(1, $question['responses']);
        }

        // Check exported submissions data for the feedback the user has received.
        $subcontext = [
            get_string('feedbackreceived', 'mod_threesixo'),
            get_string('submissions', 'mod_threesixo'),
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
            get_string('responses', 'mod_threesixo'),
        ];
        $data = $writer->get_data($subcontext);
        $this->assertEmpty($data);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     *
     * @covers ::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        $this->setup_data(true, $studentrole->id);

        $id = $this->threesixo->id;

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($this->student->id, $recipient->userid);

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
     *
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        $this->setup_data(false, $studentrole->id);

        $id = $this->threesixo->id;

        $participants = api::get_participants($id, $this->student->id);
        // Get a feedback recipient.
        $recipient = reset($participants);
        $this->give_feedback_to_user($this->student->id, $recipient->userid);

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
     * Test for \mod_threesixo\privacy\provider::get_users_in_context()
     *
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context(): void {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        // Create a 360 activity in a course with students.
        $this->setup_data(true, $studentrole->id);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create another course.
        $course = $generator->create_course();

        // Create a 360-degree feedback instance in the course.
        $record = [
            'course' => $course->id,
            'participantrole' => $studentrole->id, // Only for students.
            'anonymous' => false,
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $record);

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Create a new student.
        $newstudent = $generator->create_user();
        // Enrol the new student manually to the course.
        $generator->enrol_user($newstudent->id, $course->id, 'student');

        // Generate feedback statuses for each student.
        foreach ($this->students as $id) {
            // Enrol the student manually to the course.
            $generator->enrol_user($id, $course->id, 'student');

            api::generate_360_feedback_statuses($threesixo->id, $id);
        }

        // Get the user IDs for the 360 instance that we're testing.
        $cm = get_coursemodule_from_instance('threesixo', $this->threesixo->id);
        $cmcontext = context_module::instance($cm->id);
        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_threesixo');
        \mod_threesixo\privacy\provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        // This should match the participants list of the first 360 instance.
        $this->assertEqualsCanonicalizing($this->students, $userids, '');
        // And definitely not include user from the other 360 instance.
        $this->assertNotContains($newstudent->id, $userids);
    }

    /**
     * Test for \mod_threesixo\privacy\provider::delete_data_for_users()
     *
     * @covers ::delete_data_for_users
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        // Create an anonymous 360 instance with all course participants.
        $this->setup_data();

        $participants = api::get_participants($this->threesixo->id, $this->student->id);
        foreach ($participants as $participant) {
            $this->give_feedback_to_user($this->student->id, $participant->userid);
        }

        // Log in as the teacher and give feedbacks to the participants.
        $this->setUser($this->teacher);
        $participants = api::get_participants($this->threesixo->id, $this->teacher->id);
        foreach ($participants as $participant) {
            $this->give_feedback_to_user($this->teacher->id, $participant->userid);
        }

        $cm = get_coursemodule_from_instance('threesixo', $this->threesixo->id);
        $context = context_module::instance($cm->id);

        $userids = [$this->student->id];

        $approveduserlist = new approved_userlist($context, 'mod_threesixo', $userids);
        provider::delete_data_for_users($approveduserlist);

        // Confirm that the submission/responses the student provided have been deleted.
        list($sqlfrom, $paramsfrom) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        list($sqlto, $paramsto) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "threesixo = :threesixo AND (fromuser $sqlfrom OR touser $sqlto)";
        $params = ['threesixo' => $cm->instance] + $paramsfrom + $paramsto;

        $submissionscount = $DB->count_records_select('threesixo_submission', $select, $params);
        $responsescount = $DB->count_records_select('threesixo_response', $select, $params);
        $this->assertEquals(0, $submissionscount);
        $this->assertEquals(0, $responsescount);

        // Confirm that the submission/responses the teacher provided have not been deleted.
        list($sqlfrom, $paramsfrom) = $DB->get_in_or_equal([$this->teacher->id], SQL_PARAMS_NAMED);
        list($sqlto, $paramsto) = $DB->get_in_or_equal([$this->teacher->id], SQL_PARAMS_NAMED);

        $select = "threesixo = :threesixo AND (fromuser $sqlfrom OR touser $sqlto)";
        $params = ['threesixo' => $cm->instance] + $paramsfrom + $paramsto;

        $submissionscount = $DB->count_records_select('threesixo_submission', $select, $params);
        $responsescount = $DB->count_records_select('threesixo_response', $select, $params);
        $this->assertGreaterThan(0, $submissionscount);
        $this->assertGreaterThan(0, $responsescount);

        // Confirm though that the responses provided to the student got deleted as well.
        $params = [
            'threesixo' => $cm->instance,
            'fromuser' => $this->teacher->id,
            'touser' => $this->student->id,
        ];
        $this->assertFalse($DB->record_exists('threesixo_submission', $params));
        $this->assertFalse($DB->record_exists('threesixo_response', $params));
    }

    /**
     * Generate a course, enrol users and a 360-degree feedback instance.
     *
     * @param bool $anonymous Whether to set up an anonymous feedback.
     * @param int $roleid The role ID for the participants.
     */
    protected function setup_data($anonymous = true, $roleid = 0) {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        // Create a 360-degree feedback instance in the course.
        $record = [
            'course' => $course->id,
            'participantrole' => $roleid,
            'anonymous' => $anonymous,
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $record);

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->teacher = $teacher;

        // Create students.
        $studentids = [];
        for ($i = 0; $i < 3; $i++) {
            // Create a student.
            $student = $generator->create_user();

            // Enrol the student manually to the course.
            $generator->enrol_user($student->id, $course->id, 'student');

            $studentids[] = $student->id;
        }
        $this->students = $studentids;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);

        // Generate feedback statuses for participants.
        if (empty($roleid) || $roleid == $teacherrole->id) {
            api::generate_360_feedback_statuses($threesixo->id, $teacher->id);
        }
        if (empty($roleid) || $roleid == $studentrole->id) {
            foreach ($studentids as $id) {
                api::generate_360_feedback_statuses($threesixo->id, $id);
            }
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
     * @param int $participantid The participant ID.
     * @param int $recipientid The recipient ID.
     */
    protected function give_feedback_to_user($participantid, $recipientid) {
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
        $submission = api::get_submission_by_params($id, $participantid, $recipientid);
        api::set_completion($submission->id, api::STATUS_COMPLETE);
    }
}
