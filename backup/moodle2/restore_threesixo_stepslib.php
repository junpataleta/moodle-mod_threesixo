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
 * Structure step to restore one 360-degree feedback activity instance
 *
 * @package    mod_threesixo
 * @copyright 2019 onwards Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_threesixo_activity_structure_step extends restore_activity_structure_step {

    /**
     * Function that will return the structure to be processed by this restore_step.
     * Must return one array of @restore_path_element elements
     *
     * @return array
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('threesixo', '/activity/threesixo');
        $paths[] = new restore_path_element('threesixo_question', '/activity/threesixo/questions/question');
        $paths[] = new restore_path_element('threesixo_item', '/activity/threesixo/items/item');

        if ($userinfo) {
            $paths[] = new restore_path_element('threesixo_response', '/activity/threesixo/responses/response');
            $paths[] = new restore_path_element('threesixo_submission', '/activity/threesixo/submissions/submission');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the 360-degree feedback instance.
     *
     * @param array $data The threesixo data from the backup file.
     */
    protected function process_threesixo($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // Insert the threesixo record.
        $newitemid = $DB->insert_record('threesixo', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Processes question data from the 360-degree feedback instance.
     *
     * @param array $data The question data from the backup file.
     */
    protected function process_threesixo_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Check if the question already exists in the questions table.
        $newitemid = $DB->get_field('threesixo_question', 'id', ['question' => $data->question, 'type' => $data->type]);
        if (!$newitemid) {
            // If it doesn't exist yet, create a new one.
            $newitemid = $DB->insert_record('threesixo_question', $data);
        }
        $this->set_mapping('threesixo_question', $oldid, $newitemid);
    }

    /**
     * Processes item data from the 360-degree feedback instance.
     *
     * @param array $data The item data from the backup file.
     */
    protected function process_threesixo_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->threesixo = $this->get_new_parentid('threesixo');
        $data->question = $this->get_mappingid('threesixo_question', $data->question);

        $newitemid = $DB->insert_record('threesixo_item', $data);
        $this->set_mapping('threesixo_item', $oldid, $newitemid);
    }

    /**
     * Processes submission data from the 360-degree feedback instance.
     *
     * @param array $data The submission data from the backup file.
     */
    protected function process_threesixo_submission($data) {
        global $DB;

        $data = (object)$data;

        $data->threesixo = $this->get_new_parentid('threesixo');
        $data->fromuser = $this->get_mappingid('user', $data->fromuser);
        $data->touser = $this->get_mappingid('user', $data->touser);

        $DB->insert_record('threesixo_submission', $data);
    }

    /**
     * Processes response data from the 360-degree feedback instance.
     *
     * @param array $data The response data from the backup file.
     */
    protected function process_threesixo_response($data) {
        global $DB;

        $data = (object)$data;

        $data->threesixo = $this->get_new_parentid('threesixo');
        $data->item = $this->get_mappingid('threesixo_item', $data->item);
        $data->fromuser = $this->get_mappingid('user', $data->fromuser);
        $data->touser = $this->get_mappingid('user', $data->touser);

        $DB->insert_record('threesixo_response', $data);
    }

    /**
     * Post-execution processing.
     */
    protected function after_execute() {
        // Add threesixo related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_threesixo', 'intro', null);
    }
}
