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
use backup;
use backup_controller;
use backup_setting;
use core_courseformat\local\cmactions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use restore_controller;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Required at file scope: the backup and restore classes live in legacy .class.php files that the autoloader
// does not map, and PHPUnit resolves this file's coverage targets before any test method runs.
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Tests for how the shared question bank is handled by backup and restore.
 *
 * @package    mod_threesixo
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\backup_threesixo_activity_task::class)]
#[CoversClass(\backup_threesixo_activity_structure_step::class)]
#[CoversClass(\restore_threesixo_activity_task::class)]
#[CoversClass(\restore_threesixo_activity_structure_step::class)]
final class backup_restore_questions_test extends advanced_testcase {
    /**
     * Forces the user data settings of a backup or restore plan on or off.
     *
     * The activity level userinfo setting is locked by the root users setting, so both have to be set for user
     * data to actually be included.
     *
     * @param array $settings The plan's settings.
     * @param bool $enabled Whether user data should be included.
     */
    protected function set_user_data(array $settings, bool $enabled): void {
        foreach ($settings as $setting) {
            $name = $setting->get_name();
            if ($name === 'users' || substr($name, -9) === '_userinfo') {
                $setting->set_status(backup_setting::NOT_LOCKED);
                $setting->set_value($enabled);
            }
        }
    }

    /**
     * Removes a course module, using the API available in this Moodle version.
     *
     * @param stdClass $course The course the module belongs to.
     * @param int $cmid The course module ID.
     */
    protected function delete_module(stdClass $course, int $cmid): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // The course_delete_module() function is deprecated from Moodle 5.2 in favour of cmactions::delete(),
        // but this plugin still supports 5.1, where the cmactions class already exists without that method.
        // Check for the method rather than the class, or 5.1 takes this branch and fatals.
        if (method_exists(cmactions::class, 'delete')) {
            (new cmactions($course))->delete($cmid);
        } else {
            course_delete_module($cmid);
        }
    }

    /**
     * Backs up a 360-degree feedback activity.
     *
     * @param stdClass $threesixo The instance to back up, as returned by the generator.
     * @param int $userid The user performing the backup.
     * @param bool|null $userdata Whether to force user data on or off. Null leaves the plan's defaults alone.
     * @return string The ID of the resulting backup.
     */
    protected function backup_activity(stdClass $threesixo, int $userid, ?bool $userdata = null): string {
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $threesixo->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $userid,
            backup::RELEASESESSION_NO
        );
        if ($userdata !== null) {
            $this->set_user_data($bc->get_plan()->get_settings(), $userdata);
        }
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        return $backupid;
    }

    /**
     * Restores a backup back into the course it came from.
     *
     * @param string $backupid The ID of the backup to restore.
     * @param stdClass $threesixo The instance the backup was taken from, used to tell the restored copy apart.
     * @param int $userid The user performing the restore.
     * @param bool|null $userdata Whether to force user data on or off. Null leaves the plan's defaults alone.
     * @return int The ID of the restored 360-degree feedback instance.
     */
    protected function restore_activity(string $backupid, stdClass $threesixo, int $userid, ?bool $userdata = null): int {
        global $DB;

        $rc = new restore_controller(
            $backupid,
            $threesixo->course,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid,
            backup::TARGET_CURRENT_ADDING
        );
        if ($userdata !== null) {
            $this->set_user_data($rc->get_plan()->get_settings(), $userdata);
        }
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return (int) $DB->get_field_sql(
            'SELECT MAX(id) FROM {threesixo} WHERE course = :course AND id <> :orig',
            ['course' => $threesixo->course, 'orig' => $threesixo->id],
            MUST_EXIST
        );
    }

    /**
     * Data provider for test_restore_recreates_orphaned_question.
     *
     * @return array
     */
    public static function orphaned_question_provider(): array {
        return [
            'With user data' => [true],
            'Without user data' => [false],
        ];
    }

    /**
     * Test restoring an instance whose questions have been deleted from the shared question bank.
     *
     * The question bank is site-wide, so a question used by an instance can be deleted by its author once the
     * instance itself is gone. Restoring the backup afterwards has to recreate the question, and the user doing
     * the restore takes ownership of it, since the original author is not recorded in the backup.
     *
     * @param bool $withuserdata Whether the backup and restore include user data.
     */
    #[DataProvider('orphaned_question_provider')]
    public function test_restore_recreates_orphaned_question(bool $withuserdata): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $author = $generator->create_user();
        $restorer = $generator->create_user();
        $s1 = $generator->create_user();
        $s2 = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'editingteacher');
        $generator->enrol_user($restorer->id, $course->id, 'editingteacher');
        $generator->enrol_user($s1->id, $course->id, 'student');
        $generator->enrol_user($s2->id, $course->id, 'student');

        // One teacher contributes the questions to the shared question bank.
        $this->setUser($author);
        $ratedqid = api::add_question((object) ['question' => 'Shared rated question', 'type' => api::QTYPE_RATED]);
        $commentqid = api::add_question((object) ['question' => 'Shared comment question', 'type' => api::QTYPE_COMMENT]);

        // Another teacher builds an instance out of them. The generator's own questions stay in the bank but are
        // not used by this instance, so they must not end up in the backup.
        $this->setUser($restorer);
        $threesixo = $generator->create_module('threesixo', ['course' => $course->id]);
        api::set_items($threesixo->id, [$ratedqid, $commentqid]);
        $this->assertGreaterThan(2, $DB->count_records('threesixo_question'));
        $this->assertCount(2, api::get_items($threesixo->id));

        // A respondent completes their feedback, so that the backup has user data to carry.
        api::generate_360_feedback_statuses($threesixo->id, $s1->id);
        $submission = api::get_submission_by_params($threesixo->id, $s1->id, $s2->id);
        api::set_completion($submission->id, api::STATUS_COMPLETE);
        $this->assertGreaterThan(0, $DB->count_records('threesixo_submission', ['threesixo' => $threesixo->id]));

        // Back up the activity.
        $backupid = $this->backup_activity($threesixo, $restorer->id, $withuserdata);

        // Only the two questions used by the instance belong in the backup.
        $files = glob(make_backup_temp_directory($backupid) . '/activities/threesixo_*/threesixo.xml');
        $this->assertCount(1, $files);
        $this->assertSame(2, substr_count(file_get_contents(reset($files)), '<question id='));

        // The instance is removed, which frees the questions to be deleted by their author.
        $this->delete_module($course, $threesixo->cmid);
        $this->setUser($author);
        $this->assertTrue(api::can_delete_question($ratedqid));
        api::delete_question($ratedqid);
        api::delete_question($commentqid);
        $this->assertFalse($DB->record_exists('threesixo_question', ['id' => $ratedqid]));

        // Restoring must rebuild the questionnaire regardless of whether user data is included.
        $this->setUser($restorer);
        $newinstanceid = $this->restore_activity($backupid, $threesixo, $restorer->id, $withuserdata);
        $this->assertCount(2, api::get_items($newinstanceid));

        // The deleted questions are recreated, and owned by the user who restored them.
        $recreated = $DB->get_record('threesixo_question', ['question' => 'Shared rated question'], '*', MUST_EXIST);
        $this->assertEquals($restorer->id, $recreated->createdby);
        $this->assertEquals($restorer->id, $recreated->editedby);
        $this->assertNotEquals(0, $recreated->timecreated);

        // The submissions only come back when user data was included.
        $submissions = $DB->count_records('threesixo_submission', ['threesixo' => $newinstanceid]);
        if ($withuserdata) {
            $this->assertGreaterThan(0, $submissions);
        } else {
            $this->assertEquals(0, $submissions);
        }
    }

    /**
     * Test restoring an instance whose question is in the shared question bank more than once.
     *
     * Nothing stops two authors from contributing the same question, so the restore has to settle on one of the
     * duplicates. It takes the oldest rather than leaving the choice to the database, which is what decides who
     * owns the question the restored instance ends up pointing at.
     */
    public function test_restore_matches_oldest_duplicate_question(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $author = $generator->create_user();
        $duplicator = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'editingteacher');
        $generator->enrol_user($duplicator->id, $course->id, 'editingteacher');

        $conditions = ['question' => 'Shared rated question', 'type' => api::QTYPE_RATED];

        $this->setUser($author);
        $questionid = api::add_question((object) $conditions);
        $threesixo = $generator->create_module('threesixo', ['course' => $course->id]);
        api::set_items($threesixo->id, [$questionid]);

        $backupid = $this->backup_activity($threesixo, $author->id);

        // A second teacher independently contributes the very same question to the shared bank.
        $this->setUser($duplicator);
        $duplicateid = api::add_question((object) $conditions);
        $this->assertGreaterThan($questionid, $duplicateid);
        $this->assertEquals(2, $DB->count_records('threesixo_question', $conditions));

        $newinstanceid = $this->restore_activity($backupid, $threesixo, $duplicator->id);

        // The question is matched rather than recreated, so the bank is left as it was.
        $this->assertEquals(2, $DB->count_records('threesixo_question', $conditions));

        // Of the two, the restored instance uses the one that was there first.
        $items = api::get_items($newinstanceid);
        $this->assertCount(1, $items);
        $this->assertEquals($questionid, reset($items)->questionid);

        // Matching a question that appears more than once must not report a database error.
        $this->assertDebuggingNotCalled();
    }
}
