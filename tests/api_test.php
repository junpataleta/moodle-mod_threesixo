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
use DateTime;

/**
 * API tests.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_threesixo\api
 */
final class api_test extends advanced_testcase {

    /**
     * Tests for mod_threesixo\api::get_participants().
     *
     * @covers ::get_participants
     */
    public function test_get_participants_with_multiple_enrol_methods(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        $enrolmethods = ['manual', 'self'];

        // Only enable the manual enrol plugin.
        $CFG->enrol_plugins_enabled = implode(',', $enrolmethods);

        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        foreach ($enrolmethods as $method) {
            // Get the enrol plugin.
            $plugin = enrol_get_plugin($method);
            // Enable this enrol plugin for the course.
            $plugin->add_instance($course);
        }

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher', $enrolmethods[0]);
        $studentids = [];
        for ($i = 0; $i < 10; $i++) {
            // Create a student.
            $student = $generator->create_user();

            foreach ($enrolmethods as $method) {
                // Enrol the student manually to the course.
                $generator->enrol_user($student->id, $course->id, 'student', $method);
            }
            $studentids[] = $student->id;
        }
        sort($studentids);

        $threesixo = $this->getDataGenerator()->create_module('threesixo', ['course' => $course->id]);

        // Get participants for teacher's view.
        $participants = api::get_participants($threesixo->id, $teacher->id);
        $this->assertDebuggingNotCalled();
        $participantids = array_keys($participants);
        sort($participantids);
        $this->assertEquals($studentids, $participantids);

        // Get participants for a student's view.
        $studentid = reset($studentids);
        $participants = api::get_participants($threesixo->id, $studentid);
        $this->assertDebuggingNotCalled();
        $participantids = array_keys($participants);
        foreach ($studentids as $id) {
            if ($id == $studentid) {
                $this->assertNotContainsEquals($id, $participantids);
            } else {
                $this->assertContainsEquals($id, $participantids);
            }
        }
    }

    /**
     * Data provider for test_is_open.
     *
     * @return array
     */
    public static function is_open_provider(): array {
        return [
            'Empty open and close' => [null, null, false, true],
            'After open, empty close' => ['yesterday', null, false, true],
            'Empty open, before close' => [null, 'tomorrow', false, true],
            'After open, before close' => ['yesterday', 'tomorrow', false, true],
            'Before open, empty close' => ['tomorrow', null, false, false],
            'Empty open, after close' => [null, 'yesterday', false, false],
            'Before open, before close' => ['tomorrow', 'next week', false, false],
            'After open, after close' => ['last week', 'yesterday', false, false],
            'Before open, empty close, return message' => ['tomorrow', null, true, 'instancenotyetopen'],
            'Empty open, after close, return message' => [null, 'yesterday', true, 'instancealreadyclosed'],
            'Before open, before close, return message' => ['tomorrow', 'next week', true, 'instancenotyetopen'],
            'After open, after close, return message' => ['last week', 'yesterday', true, 'instancealreadyclosed'],
        ];
    }

    /**
     * Test for \mod_threesixo\api::is_open().
     *
     * @dataProvider is_open_provider
     * @param string|null $open Relative open date.
     * @param string|null $close Relative close date.
     * @param bool $messagewhenclosed Whether to return a message when the instance is not yet open.
     * @param bool|string $expected Expected function result.
     * @covers ::is_open
     */
    public function test_is_open(?string $open, ?string $close, bool $messagewhenclosed, $expected): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();
        $params = [
            'course' => $course->id,
            'timeopen' => $open ? (new DateTime($open))->getTimestamp() : 0,
            'timeclose' => $close ? (new DateTime($close))->getTimestamp() : 0,
        ];
        // Create the instance.
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $params);

        // Check if instance is open.
        $result = api::is_open($threesixo, $messagewhenclosed);

        // Check the result.
        if ($messagewhenclosed && $expected === 'instancenotyetopen') {
            $openstring = userdate($params['timeopen']);
            $message = get_string($expected, 'threesixo', $openstring);
            $this->assertEquals($message, $result);

        } else if ($messagewhenclosed && $expected === 'instancealreadyclosed') {
            $message = get_string($expected, 'threesixo');
            $this->assertEquals($message, $result);

        } else {
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Data provider for response validation.
     *
     * @return array[]
     */
    public static function validate_responses_provider(): array {
        return [
            'Valid responses' => [
                true, [1, 6, 0], '',
            ],
            'Responses with null values' => [
                true, [6, null, 0], '',
            ],
            'Invalid item' => [
                false, [], get_string('errorinvaliditem', 'mod_threesixo'),
            ],
            'Negative value response' => [
                true, [1, -6, 0], get_string('errorinvalidratingvalue', 'mod_threesixo', -6),
            ],
            'Over expected int value response' => [
                true, [1, 6, (api::RATING_MAX + 1)], get_string('errorinvalidratingvalue', 'mod_threesixo', 7),
            ],
            'Over expected float value response' => [
                true, [1, 6, 6.1], get_string('errorinvalidratingvalue', 'mod_threesixo', 6.1),
            ],
        ];
    }

    /**
     * Test for {@see api::validate_responses()}
     *
     * @dataProvider validate_responses_provider
     * @covers ::validate_responses
     * @param bool $validitem If false, we'll pass an invalid item ID that does not belong in the feedback activity.
     * @param array $responsedata The response data.
     * @param string $message The expected error message.
     * @return void
     */
    public function test_validate_responses(bool $validitem, array $responsedata, string $message): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        // Create the instance.
        $params = [
            'course' => $course->id,
        ];
        $options = [
            'ratedquestions' => [
                'R1',
                'R2',
                'R3',
            ],
            'commentquestions' => [],
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $params, $options);

        $items = api::get_items($threesixo->id);

        $responses = [];
        $index = 0;
        $maxitemid = 0;
        foreach ($items as $item) {
            $responses[$item->id] = $responsedata[$index] ?? api::RATING_NA;
            $maxitemid = max($item->id, $maxitemid);
            $index++;
        }
        if (!$validitem) {
            $maxitemid++;
            $responses[$maxitemid] = api::RATING_MAX;
        }
        $result = api::validate_responses($threesixo->id, $responses);
        $this->assertEquals($message, $result);
    }
}
