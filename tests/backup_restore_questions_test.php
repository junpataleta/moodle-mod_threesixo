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
use restore_controller;
use stdClass;

/**
 * Tests for how the shared question bank is handled by backup and restore.
 *
 * @package    mod_threesixo
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

        // The course_delete_module() function is deprecated from Moodle 5.2, which is where cmactions::delete()
        // was added. The cmactions class itself has been around since Moodle 4.5, so the method has to be the
        // thing that is checked here, otherwise older versions take this branch and fatal.
        if (method_exists(\core_courseformat\local\cmactions::class, 'delete')) {
            (new \core_courseformat\local\cmactions($course))->delete($cmid);
        } else {
            course_delete_module($cmid);
        }
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
     * @dataProvider orphaned_question_provider
     * @covers \backup_threesixo_activity_structure_step::define_structure
     * @covers \restore_threesixo_activity_structure_step::process_threesixo_question
     * @param bool $withuserdata Whether the backup and restore include user data.
     */
    public function test_restore_recreates_orphaned_question(bool $withuserdata): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

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
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $threesixo->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $restorer->id,
            backup::RELEASESESSION_NO
        );
        $this->set_user_data($bc->get_plan()->get_settings(), $withuserdata);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

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
        $rc = new restore_controller(
            $backupid,
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $restorer->id,
            backup::TARGET_CURRENT_ADDING
        );
        $this->set_user_data($rc->get_plan()->get_settings(), $withuserdata);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newinstanceid = (int) $DB->get_field_sql(
            'SELECT MAX(id) FROM {threesixo} WHERE course = :course AND id <> :orig',
            ['course' => $course->id, 'orig' => $threesixo->id],
            MUST_EXIST
        );
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
}
