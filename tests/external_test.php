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
use mod_threesixo_generator;
use moodle_exception;
use required_capability_exception;

/**
 * API tests.
 *
 * @package    mod_threesixo
 * @copyright  2025 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_threesixo\external
 */
final class external_test extends advanced_testcase {

    /**
     * Data provider for test_delete_question.
     *
     * @return array
     */
    public static function delete_question_provider(): array {
        return [
            'Admin can delete any question' => [
                'user' => 'admin',
                'deleteinuse' => false,
                'exception' => null,
                'exceptionmessage' => null,
            ],
            'Admin cannot delete questions in use' => [
                'user' => 'admin',
                'deleteinuse' => true,
                'exception' => moodle_exception::class,
                'exceptionmessage' => 'errorquestionstillinuse',
            ],
            'Users can delete their own questions' => [
                'user' => 'u1',
                'deleteinuse' => false,
                'exception' => null,
                'exceptionmessage' => null,
            ],
            'Users cannot delete questions in use, even their own questions' => [
                'user' => 'u1',
                'deleteinuse' => true,
                'exception' => moodle_exception::class,
                'exceptionmessage' => 'errorquestionstillinuse',
            ],
            'Users cannot delete questions created by others' => [
                'user' => 'u2',
                'deleteinuse' => false,
                'exception' => moodle_exception::class,
                'exceptionmessage' => 'errorcannotdeleteothersquestion',
            ],
            'Students cannot delete questions' => [
                'user' => 's1',
                'deleteinuse' => false,
                'exception' => required_capability_exception::class,
                'exceptionmessage' => null,
            ],
        ];
    }

    /**
     * Test question deletion.
     *
     * @dataProvider delete_question_provider
     * @covers ::delete_question
     * @runInSeparateProcess
     * @param string $user The user to set for the test.
     * @param bool $deleteinuse Whether to delete a question that is in use.
     * @param string|null $exception The expected exception class, if any.
     * @param string|null $exceptionmessage The expected exception message, if any.
     */
    public function test_delete_question(string $user, bool $deleteinuse, ?string $exception, ?string $exceptionmessage): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $u1 = $generator->create_user(['username' => 'u1']);
        $u2 = $generator->create_user(['username' => 'u2']);
        $s1 = $generator->create_user(['username' => 's1']);

        // Create a course.
        $course = $generator->create_course();
        // Enrol users.
        $generator->enrol_user($u1->id, $course->id, 'editingteacher');
        $generator->enrol_user($u2->id, $course->id, 'editingteacher');
        $generator->enrol_user($s1->id, $course->id, 'student');

        // Create the instance.
        $params = [
            'course' => $course->id,
        ];
        $options = [
            'ratedquestions' => [
                'R1',
            ],
            'commentquestions' => [],
        ];
        $threesixo = $this->getDataGenerator()->create_module('threesixo', $params, $options);

        /** @var mod_threesixo_generator $threesixogenerator */
        $threesixogenerator = $generator->get_plugin_generator('mod_threesixo');
        // Unused question.
        $q1 = $threesixogenerator->create_question([
            'question' => 'Question by u1',
            'type' => api::QTYPE_RATED,
            'createdby' => $u1->id,
        ]);
        // Question in use.
        $q2 = $threesixogenerator->create_question([
           'question' => 'Question by u1 but in use',
           'type' => api::QTYPE_RATED,
           'createdby' => $u1->id,
        ]);
        api::set_items($threesixo->id, [$q2]);

        switch ($user) {
            case 'admin':
                break;
            default:
                $this->setUser($$user);
                break;
        }

        // Admin can delete others' question.
        $qtodelete = $q1;
        if ($deleteinuse) {
            $qtodelete = $q2;
        }
        if ($exception) {
            $this->expectException($exception);
            if ($exceptionmessage) {
                $this->expectExceptionMessage(get_string($exceptionmessage, 'threesixo'));
            }
        }
        external::delete_question($qtodelete, $threesixo->id);
    }
}
