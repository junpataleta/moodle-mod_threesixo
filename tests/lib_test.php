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
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Library tests.
 *
 * @package    mod_threesixo
 * @copyright  2025 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('threesixo_add_instance')]
#[CoversFunction('threesixo_update_instance')]
#[CoversFunction('threesixo_delete_instance')]
final class lib_test extends advanced_testcase {
    /**
     * Test adding a new 360-degree feedback instance.
     */
    public function test_threesixo_add_instance(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        // Create a course.
        $course = $generator->create_course();

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

        $this->assertNotEmpty($threesixo->id);
    }

    /**
     * Test updating a 360-degree feedback instance.
     */
    public function test_threesixo_update_instance(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        global $DB;
        $this->setAdminUser();

        // Create a course.
        $course = $generator->create_course();

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

        // Update the instance.
        $threesixo->instance = $threesixo->id;
        $threesixo->name = 'Updated Name';
        $result = threesixo_update_instance($threesixo);

        $this->assertTrue($result);

        $updated = $DB->get_record('threesixo', ['id' => $threesixo->id], '*', MUST_EXIST);
        $this->assertSame('Updated Name', $updated->name);
    }

    /**
     * Test deleting a 360-degree feedback instance.
     */
    public function test_threesixo_delete_instance(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        // Create a course.
        $course = $generator->create_course();

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

        // Delete the instance.
        $result = threesixo_delete_instance($threesixo->id);

        $this->assertTrue($result);
    }
}
