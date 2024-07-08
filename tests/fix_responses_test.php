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
use mod_threesixo\task\fix_responses;

/**
 * Test for the ad-hoc task that fixes the responses.
 *
 * @package    mod_threesixo
 * @copyright  2024 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_threesixo\task\fix_responses
 */
final class fix_responses_test extends advanced_testcase {

    /**
     * Task execution tests.
     *
     * @covers ::execute
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Create a student.
        $s1 = $generator->create_user();
        // Enrol the student manually to the course.
        $generator->enrol_user($s1->id, $course->id, 'student');

        // Create another student.
        $s2 = $generator->create_user();
        // Enrol the student manually to the course.
        $generator->enrol_user($s2->id, $course->id, 'student');

        $params = [
            'course' => $course->id,
            'anonymous' => true,
        ];
        $options = [
            'ratedquestions' => [
                'R1',
                'R2',
                'R3',
            ],
            'commentquestions' => [
                'C1',
                'C2',
            ],
        ];

        // Create an anonymous feedback.
        $anonthreesixo = $this->getDataGenerator()->create_module('threesixo', $params, $options);
        $anonitems = api::get_items($anonthreesixo->id);

        unset($params['anonymous']);
        // Create a non-anonymous feedback.
        $nonanonthreesixo = $this->getDataGenerator()->create_module('threesixo', $params, $options);
        $nonanonitems = api::get_items($nonanonthreesixo->id);

        // Generate submission records for s1.
        api::generate_360_feedback_statuses($anonthreesixo->id, $s1->id);
        api::generate_360_feedback_statuses($nonanonthreesixo->id, $s1->id);

        // Generate submission records for s2.
        api::generate_360_feedback_statuses($anonthreesixo->id, $s2->id);
        api::generate_360_feedback_statuses($nonanonthreesixo->id, $s2->id);

        $s1responses = [
            'R1' => 1,
            'R2' => 6,
            'R3' => 0,
            'C1' => 'Good job!',
            'C2' => 'All good',
        ];
        $s2responses = [
            'R1' => -1,
            'R2' => 6.5,
            'R3' => 10000,
            'C1' => '100',
            'C2' => '10',
        ];
        foreach ($anonitems as $item) {
            $s1submission = (object)[
                'threesixo' => $anonthreesixo->id,
                'item' => $item->id,
                'fromuser' => $s1->id,
                'touser' => $s2->id,
                'value' => $s1responses[$item->question],
            ];
            $DB->insert_record('threesixo_response', $s1submission);
            $s2submission = (object)[
                'threesixo' => $anonthreesixo->id,
                'item' => $item->id,
                'fromuser' => $s2->id,
                'touser' => $s1->id,
                'value' => $s2responses[$item->question],
            ];
            $DB->insert_record('threesixo_response', $s2submission);
        }
        // Mark complete and anonymise s1's feedback to s2.
        $s1submissionanon = api::get_submission_by_params($anonthreesixo->id, $s1->id, $s2->id);
        api::set_completion($s1submissionanon->id, api::STATUS_COMPLETE);
        api::anonymise_responses($anonthreesixo->id, $s2->id, $s1->id);

        // Mark complete and anonymise s2's feedback to s1.
        $s2submissionanon = api::get_submission_by_params($anonthreesixo->id, $s2->id, $s1->id);
        api::set_completion($s2submissionanon->id, api::STATUS_COMPLETE);
        api::anonymise_responses($anonthreesixo->id, $s1->id, $s2->id);

        foreach ($nonanonitems as $item) {
            $s1response = (object)[
                'threesixo' => $nonanonthreesixo->id,
                'item' => $item->id,
                'fromuser' => $s1->id,
                'touser' => $s2->id,
                'value' => $s1responses[$item->question],
            ];
            $DB->insert_record('threesixo_response', $s1response);
            $s2response = (object)[
                'threesixo' => $nonanonthreesixo->id,
                'item' => $item->id,
                'fromuser' => $s2->id,
                'touser' => $s1->id,
                'value' => $s2responses[$item->question],
            ];
            $DB->insert_record('threesixo_response', $s2response);
        }

        // Mark complete s1's feedback to s2.
        $s1submission = api::get_submission_by_params($nonanonthreesixo->id, $s1->id, $s2->id);
        api::set_completion($s1submission->id, api::STATUS_COMPLETE);

        // Mark complete s2's feedback to s1.
        $s2submission = api::get_submission_by_params($nonanonthreesixo->id, $s2->id, $s1->id);
        api::set_completion($s2submission->id, api::STATUS_COMPLETE);

        // Run the task to fix the responses.
        $clioutput = "/Invalid ratings found for user {$s1->id} in the anonymous 360-degree feedback activity"
            . " with instance ID {$anonthreesixo->id}. The submission records for this user will be reset to 'In progress'/";
        $this->expectOutputRegex($clioutput);
        $task = new fix_responses();
        $task->execute();

        // S1's responses are all valid. So it should remain complete.
        $s1subupdated = api::get_submission($s1submission->id, $s1->id);
        $this->assertEquals(api::STATUS_COMPLETE, $s1subupdated->status);

        // S2's responses are not valid. So it should have been reset to in-progress.
        $s2subupdated = api::get_submission($s2submission->id, $s2->id);
        $this->assertEquals(api::STATUS_IN_PROGRESS, $s2subupdated->status);

        // Given this is an anonymous feedback with valid responses, the status should have not changed and remains complete.
        $s1subanonupdated = api::get_submission($s1submissionanon->id, $s1->id);
        $this->assertEquals(api::STATUS_COMPLETE, $s1subanonupdated->status);

        // Given this is an anonymous feedback with invalid ratings, the status should have not changed and remains complete.
        $s2subanonupdated = api::get_submission($s2submissionanon->id, $s2->id);
        $this->assertEquals(api::STATUS_IN_PROGRESS, $s2subanonupdated->status);

        $feedbackfors1 = $DB->get_records('threesixo_response', ['threesixo' => $nonanonthreesixo->id, 'touser' => $s1->id]);

        // Invalid feedback ratings for s1 should have been reset to null. Comments remain unchanged.
        $fixedresponses = [
            'R1' => null,
            'R2' => null,
            'R3' => null,
            'C1' => '100',
            'C2' => '10',
        ];
        foreach ($feedbackfors1 as $response) {
            $item = $nonanonitems[$response->item];
            $this->assertEquals($fixedresponses[$item->question], $response->value);
        }

        // Invalid anonymous feedback ratings for s1 should have been reset to N/A. Comments remain unchanged.
        $anonfeedbackfors1 = $DB->get_records('threesixo_response', ['threesixo' => $anonthreesixo->id, 'touser' => $s1->id]);
        // The response records to the user for these questions should have been deleted.
        $removedresponses = [
            'R1',
            'R2',
            'R3',
        ];
        // These should be the only response records fetched for the user with invalid ratings.
        $fixedanonresponses = [
            'C1' => '100',
            'C2' => '10',
        ];
        $this->assertCount(count($fixedanonresponses), $anonfeedbackfors1);
        foreach ($anonfeedbackfors1 as $response) {
            $item = $anonitems[$response->item];
            $this->assertNotContains($item->question, $removedresponses);
            $this->assertEquals($fixedanonresponses[$item->question], $response->value);
        }

        // Feedback responses for s2 should remain the same since they are valid.
        $feedbackfors2 = $DB->get_records('threesixo_response', ['threesixo' => $nonanonthreesixo->id, 'touser' => $s2->id]);
        foreach ($feedbackfors2 as $response) {
            $item = $nonanonitems[$response->item];
            $this->assertEquals($s1responses[$item->question], $response->value);
        }

        $anonfeedbackfors2 = $DB->get_records('threesixo_response', ['threesixo' => $anonthreesixo->id, 'touser' => $s2->id]);
        foreach ($anonfeedbackfors2 as $response) {
            $item = $anonitems[$response->item];
            $this->assertEquals($s1responses[$item->question], $response->value);
        }
    }
}
