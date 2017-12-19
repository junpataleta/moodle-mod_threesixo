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
 * Library of functions and constants for module threesixo
 * includes the main-part of threesixo-functions
 *
 * @package mod_threesixo
 * @copyright 2017 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

// Include forms lib.
require_once($CFG->libdir.'/formslib.php');

/**
 * Adds a new 360-degree feedback instance.
 *
 * @param stdClass $threesixty
 * @return bool|int The ID of the created 360-degree feedback or false if the insert failed.
 */
function threesixo_add_instance($threesixty) {
    global $DB;

    $threesixty->timemodified = time();

    // Insert the 360-degree feedback into the DB.
    if ($threesixtyid = $DB->insert_record("threesixo", $threesixty)) {
        $threesixty->id = $threesixtyid;

        if (!isset($threesixty->coursemodule)) {
            $cm = get_coursemodule_from_id('threesixo', $threesixty->id);
            $threesixty->coursemodule = $cm->id;
        }

        $DB->update_record('threesixo', $threesixty);
    }

    return $threesixtyid;
}

/**
 * Updates the given 360-degree feedback.
 *
 * @param stdClass $threesixty
 * @return bool
 */
function threesixo_update_instance($threesixty) {
    global $DB;

    $threesixty->timemodified = time();
    $threesixty->id = $threesixty->instance;

    if (empty($threesixty->site_after_submit)) {
        $threesixty->site_after_submit = '';
    }

    // save the feedback into the db
    return $DB->update_record("threesixo", $threesixty);
}

/**
 * Deletes the 360-degree feedback.
 *
 * @param int $id The ID of the 360-degree feedback to be deleted.
 * @return bool
 */
function threesixo_delete_instance($id) {
    global $DB;

    // Delete responses.
    $DB->delete_records("threesixo_response", array("threesixo"=>$id));

    // Delete statuses.
    $DB->delete_records("threesixo_submission", array("threesixo"=>$id));

    // Delete items.
    $DB->delete_records('threesixo_item', array('threesixo'=>$id));

    // Delete events.
    $DB->delete_records('event', array('modulename'=>'threesixo', 'instance'=>$id));

    // Finally, delete the 360-degree feedback.
    return $DB->delete_records("threesixo", array("id"=>$id));
}
