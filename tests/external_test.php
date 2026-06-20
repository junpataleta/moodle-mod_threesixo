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
use PHPUnit\Framework\Attributes\CoversClass;

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
        $this->assertNotEmpty($addresult['questionid']);

        $updateresult = external::update_question($questionid, 'Updated question', api::QTYPE_COMMENT, $threesixo->id);
        $this->assertTrue($updateresult['result']);

        $deleteresult = external::delete_question($questionid, $threesixo->id);
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

        $this->assertArrayHasKey('participants', $result);
        $this->assertArrayHasKey('threesixtyid', $result);
        $this->assertSame($threesixo->id, $result['threesixtyid']);
    }
}
