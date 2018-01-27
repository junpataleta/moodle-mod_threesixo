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
 * API tests.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use mod_threesixo\api;

/**
 * API tests.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_threesixo_api_testcase extends advanced_testcase {

    /**
     * Tests for mod_threesixo\api::get_participants().
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_get_participants_with_multiple_enrol_methods() {
        global $CFG;
        $this->resetAfterTest();

        $enrolmethods = ['manual', 'self'];

        // Only enable the manual enrol plugin.
        $CFG->enrol_plugins_enabled = implode($enrolmethods, ',');

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
        $students = [];
        $studentids = [];
        for ($i = 0; $i < 10; $i++) {
            // Create a student.
            $student = $generator->create_user();

            foreach ($enrolmethods as $method) {
                // Enrol the student manually to the course.
                $generator->enrol_user($student->id, $course->id, 'student', $method);
            }
            $students[] = $student;
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
                $this->assertNotContains($id, $participantids);
            } else {
                $this->assertContains($id, $participantids);
            }
        }
    }
}
