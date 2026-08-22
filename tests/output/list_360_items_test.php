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

namespace mod_threesixo\output;

use advanced_testcase;
use mod_threesixo\api;
use moodle_url;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the 360-degree feedback item list renderable.
 *
 * @package    mod_threesixo
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(list_360_items::class)]
final class list_360_items_test extends advanced_testcase {
    /**
     * The item action controls are only offered while the items can still be modified.
     */
    public function test_export_for_template_locked(): void {
        global $DB, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $s1 = $generator->create_user();
        $s2 = $generator->create_user();
        $generator->enrol_user($s1->id, $course->id, 'student');
        $generator->enrol_user($s2->id, $course->id, 'student');

        $threesixo = $generator->create_module('threesixo', ['course' => $course->id], [
            'ratedquestions' => ['R1', 'R2'],
            'commentquestions' => [],
        ]);
        $viewurl = new moodle_url('/mod/threesixo/view.php', ['id' => $threesixo->cmid]);
        $renderable = new list_360_items($threesixo->cmid, $course->id, $threesixo->id, $viewurl);
        $output = $PAGE->get_renderer('mod_threesixo');

        // While there are no responses, the items can be deleted and reordered.
        $data = $renderable->export_for_template($output);
        $this->assertFalse($data->locked);
        $this->assertCount(2, $data->allitems);
        foreach ($data->allitems as $item) {
            $this->assertTrue($item->deletebutton);
        }
        // With two items, the first one can be moved down and the last one up.
        $this->assertTrue($data->allitems[0]->movedownbutton);
        $this->assertTrue($data->allitems[1]->moveupbutton);

        // Once a respondent has provided feedback, the items are locked.
        $items = api::get_items($threesixo->id);
        $first = reset($items);
        $DB->insert_record('threesixo_response', (object) [
            'threesixo' => $threesixo->id,
            'item' => $first->id,
            'fromuser' => $s1->id,
            'touser' => $s2->id,
            'value' => '5',
        ]);

        $data = $renderable->export_for_template($output);
        $this->assertTrue($data->locked);
        $this->assertCount(2, $data->allitems);
        foreach ($data->allitems as $item) {
            $this->assertFalse($item->deletebutton);
            $this->assertFalse($item->moveupbutton);
            $this->assertFalse($item->movedownbutton);
        }
    }
}
