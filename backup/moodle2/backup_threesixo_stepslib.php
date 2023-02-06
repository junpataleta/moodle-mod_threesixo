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
 * Define the complete threesixo structure for backup, with file and id annotations
 *
 * @package    mod_threesixo
 * @copyright 2019 onwards Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_threesixo_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the 360-degree feedback instance structure.
     *
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $threesixo = new backup_nested_element('threesixo', ['id'], [
            'name', 'intro', 'introformat', 'anonymous',
            'participantrole', 'email_notification', 'status',
            'with_self_review', 'timeopen', 'timeclose', 'timemodified',
            'releasing', 'release', 'undodecline']);

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], ['question', 'type']);

        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', ['id'], ['threesixo', 'question', 'position']);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], ['threesixo', 'fromuser', 'touser', 'status', 'remarks']);

        $responses = new backup_nested_element('responses');
        $response = new backup_nested_element('response', ['id'], ['threesixo', 'item', 'fromuser', 'touser', 'value']);

        // Build the tree.
        $threesixo->add_child($questions);
        $questions->add_child($question);

        $threesixo->add_child($items);
        $items->add_child($item);

        $threesixo->add_child($submissions);
        $submissions->add_child($submission);

        $threesixo->add_child($responses);
        $responses->add_child($response);

        // Define sources.
        $threesixo->set_source_table('threesixo', ['id' => backup::VAR_ACTIVITYID]);

        $item->set_source_table('threesixo_item', ['threesixo' => backup::VAR_PARENTID], 'id ASC');
        $question->set_source_table('threesixo_question', [], 'id ASC');

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $submission->set_source_table('threesixo_submission', ['threesixo' => '../../id']);
            $response->set_source_table('threesixo_response', ['threesixo' => '../../id']);
        }

        // Define id annotations.
        $submission->annotate_ids('user', 'fromuser');
        $submission->annotate_ids('user', 'touser');
        $response->annotate_ids('user', 'fromuser');
        $response->annotate_ids('user', 'touser');

        // Define file annotations.
        $threesixo->annotate_files('mod_threesixo', 'intro', null); // This file area has no itemid.

        // Return the root element (threesixo), wrapped into standard activity structure.
        return $this->prepare_activity_structure($threesixo);
    }
}
