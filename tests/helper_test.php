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
use moodle_exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the helper class.
 *
 * @package    mod_threesixo
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(helper::class)]
final class helper_test extends advanced_testcase {
    /**
     * Data provider for test_get_status_string.
     *
     * @return array
     */
    public static function status_provider(): array {
        return [
            'Pending' => [api::STATUS_PENDING, 'statuspending'],
            'In progress' => [api::STATUS_IN_PROGRESS, 'statusinprogress'],
            'Complete' => [api::STATUS_COMPLETE, 'statuscompleted'],
            'Declined' => [api::STATUS_DECLINED, 'statusdeclined'],
        ];
    }

    /**
     * Test that each submission status maps to its own string.
     *
     * @param int $status The submission status.
     * @param string $identifier The expected string identifier.
     */
    #[DataProvider('status_provider')]
    public function test_get_status_string(int $status, string $identifier): void {
        $this->assertSame(get_string($identifier, 'mod_threesixo'), helper::get_status_string($status));
    }

    /**
     * Test that an unknown status is rejected rather than being reported as one of the known ones.
     */
    public function test_get_status_string_invalid(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('errorinvalidstatus', 'mod_threesixo'));

        helper::get_status_string(-1);
    }

    /**
     * Test that every rating on the scale resolves to its own description.
     */
    public function test_get_scale_values(): void {
        $descriptions = [];
        foreach (range(api::RATING_NA, api::RATING_MAX) as $value) {
            $description = helper::get_scale_values($value);
            $this->assertIsString($description, "No description for the rating {$value}.");
            $this->assertNotEmpty($description);
            $descriptions[] = $description;
        }

        // Each rating, including "N/A", describes something different.
        $this->assertCount(count($descriptions), array_unique($descriptions));
    }

    /**
     * Test that a value outside the rating scale has no description.
     */
    public function test_get_scale_values_outside_the_scale(): void {
        $this->assertFalse(helper::get_scale_values(api::RATING_MAX + 1));
        $this->assertFalse(helper::get_scale_values(-1));
    }

    /**
     * Data provider for test_get_question_type_text.
     *
     * @return array
     */
    public static function question_type_provider(): array {
        return [
            'Rated' => [api::QTYPE_RATED, 'qtyperated'],
            'Comment' => [api::QTYPE_COMMENT, 'qtypecomment'],
        ];
    }

    /**
     * Test that each question type maps to its own string.
     *
     * @param int $type The question type.
     * @param string $identifier The expected string identifier.
     */
    #[DataProvider('question_type_provider')]
    public function test_get_question_type_text(int $type, string $identifier): void {
        $this->assertSame(get_string($identifier, 'mod_threesixo'), helper::get_question_type_text($type));
    }

    /**
     * Test that an unknown question type has no text, rather than falling back to one of the known types.
     */
    public function test_get_question_type_text_unknown(): void {
        $this->assertSame('', helper::get_question_type_text(-1));
    }

    /**
     * Returns the calendar events of a 360-degree feedback instance, keyed by event type.
     *
     * Keying by type would hide a duplicate event, which is the failure a function that rewrites events is most
     * likely to have, so this also checks that no two events share a type.
     *
     * @param int $instanceid The instance ID.
     * @return array
     */
    protected function get_events(int $instanceid): array {
        global $DB;

        $events = [];
        $records = $DB->get_records('event', ['modulename' => 'threesixo', 'instance' => $instanceid]);
        foreach ($records as $record) {
            $events[$record->eventtype] = $record;
        }
        $this->assertSameSize($records, $events, 'The instance has more than one calendar event of the same type.');

        return $events;
    }

    /**
     * Test that the calendar events follow the instance's open and close dates.
     */
    public function test_set_events(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $timeopen = time() + DAYSECS;
        $timeclose = $timeopen + WEEKSECS;
        $threesixo = $this->getDataGenerator()->create_module('threesixo', [
            'course' => $course->id,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
        ]);

        // Creating the instance gives it an event for each date.
        $events = $this->get_events($threesixo->id);
        $this->assertCount(2, $events);
        $this->assertArrayHasKey(api::THREESIXO_EVENT_TYPE_OPEN, $events);
        $this->assertArrayHasKey(api::THREESIXO_EVENT_TYPE_CLOSE, $events);
        $this->assertEquals($timeopen, $events[api::THREESIXO_EVENT_TYPE_OPEN]->timestart);
        $this->assertEquals($timeclose, $events[api::THREESIXO_EVENT_TYPE_CLOSE]->timestart);

        // Moving a date moves its event.
        $threesixo->timeopen = $timeopen + DAYSECS;
        helper::set_events($threesixo);
        $events = $this->get_events($threesixo->id);
        $this->assertCount(2, $events);
        $this->assertEquals($threesixo->timeopen, $events[api::THREESIXO_EVENT_TYPE_OPEN]->timestart);

        // Clearing a date removes its event, and leaves the other one alone.
        $threesixo->timeclose = 0;
        helper::set_events($threesixo);
        $events = $this->get_events($threesixo->id);
        $this->assertCount(1, $events);
        $this->assertArrayHasKey(api::THREESIXO_EVENT_TYPE_OPEN, $events);
        $this->assertArrayNotHasKey(api::THREESIXO_EVENT_TYPE_CLOSE, $events);

        // Setting a date again recreates its event.
        $threesixo->timeclose = $timeclose;
        helper::set_events($threesixo);
        $events = $this->get_events($threesixo->id);
        $this->assertCount(2, $events);
        $this->assertEquals($timeclose, $events[api::THREESIXO_EVENT_TYPE_CLOSE]->timestart);
    }

    /**
     * Test that the events can be set for an instance that does not carry its course module ID.
     */
    public function test_set_events_without_the_course_module_id(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $timeopen = time() + DAYSECS;
        $threesixo = $this->getDataGenerator()->create_module('threesixo', [
            'course' => $course->id,
            'timeopen' => $timeopen,
            'timeclose' => 0,
        ]);

        // The record straight from the database has no coursemodule property, which set_events has to look up.
        $record = $DB->get_record('threesixo', ['id' => $threesixo->id], '*', MUST_EXIST);
        $this->assertObjectNotHasProperty('coursemodule', $record);

        $record->timeopen = $timeopen + DAYSECS;
        helper::set_events($record);

        $events = $this->get_events($threesixo->id);
        $this->assertCount(1, $events);
        $this->assertEquals($record->timeopen, $events[api::THREESIXO_EVENT_TYPE_OPEN]->timestart);
    }
}
