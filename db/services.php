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
 * Chat external functions and service definitions.
 *
 * @package    mod_threesixo
 * @category   external
 * @copyright  2016 Juan Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_threesixo_get_questions' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'get_questions',
        'classpath'     => '',
        'description'   => 'Get the questions from the question bank.',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_add_question' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'add_question',
        'classpath'     => '',
        'description'   => 'Add a question to the question bank.',
        'type'          => 'write',
        'capabilities'  => 'mod/threesixo:editquestions',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_update_question' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'update_question',
        'classpath'     => '',
        'description'   => 'Update a question in the question bank.',
        'type'          => 'write',
        'capabilities'  => 'mod/threesixo:editquestions',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_delete_question' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'delete_question',
        'classpath'     => '',
        'description'   => 'Delete a question in the question bank.',
        'type'          => 'write',
        'capabilities'  => 'mod/threesixo:editquestions',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_get_items' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'get_items',
        'description'   => 'Get items for a specific 360-degree feedback instance.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_set_items' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'set_items',
        'description'   => 'Set the items for a specific 360-degree feedback instance.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_delete_item' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'delete_item',
        'description'   => 'Delete item.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_get_question_types' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'get_question_types',
        'description'   => 'Get 360-degree feedback question types.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'mod_threesixo_move_item_up' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'move_item_up',
        'description'   => 'Move item up.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_move_item_down' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'move_item_down',
        'description'   => 'Move item down.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_decline_feedback' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'decline_feedback',
        'description'   => 'Decline feedback request.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_undo_decline' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'undo_decline',
        'description'   => 'Undo declined feedback submission',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_data_for_participant_list' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'data_for_participant_list',
        'description'   => 'Get data for the list of participants.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_save_responses' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'save_responses',
        'description'   => 'Save responses for the 360 degree feedback.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_threesixo_get_responses' => [
        'classname'     => 'mod_threesixo\external',
        'methodname'    => 'get_responses',
        'description'   => 'Loads the responses of a user for the 360 degree feedback questionnaire.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
