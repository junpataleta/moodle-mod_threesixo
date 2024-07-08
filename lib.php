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
 * Library of functions and constants for module threesixo.
 *
 * Includes the main-part of threesixo-functions
 *
 * @package mod_threesixo
 * @copyright 2017 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\event\course_module_updated;
use core_calendar\action_factory;
use core_calendar\local\event\entities\action_interface;
use mod_threesixo\api;
use mod_threesixo\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Include forms lib.
require_once($CFG->libdir.'/formslib.php');

/**
 * Adds a new 360-degree feedback instance.
 *
 * @param stdClass $threesixty
 * @return bool|int The ID of the created 360-degree feedback or false if the insert failed.
 * @throws coding_exception
 * @throws dml_exception
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

        helper::set_events($threesixty);

        $DB->update_record('threesixo', $threesixty);
    }

    return $threesixtyid;
}

/**
 * Updates the given 360-degree feedback.
 *
 * @param stdClass $threesixty
 * @return bool
 * @throws dml_exception
 */
function threesixo_update_instance($threesixty) {
    global $DB;

    $threesixty->timemodified = time();
    $threesixty->id = $threesixty->instance;

    helper::set_events($threesixty);

    // Save the feedback into the db.
    return $DB->update_record("threesixo", $threesixty);
}

/**
 * Deletes the 360-degree feedback.
 *
 * @param int $id The ID of the 360-degree feedback to be deleted.
 * @return bool
 * @throws dml_exception
 */
function threesixo_delete_instance($id) {
    global $DB;

    // Delete responses.
    $DB->delete_records("threesixo_response", ["threesixo" => $id]);

    // Delete statuses.
    $DB->delete_records("threesixo_submission", ["threesixo" => $id]);

    // Delete items.
    $DB->delete_records('threesixo_item', ['threesixo' => $id]);

    // Delete events.
    $DB->delete_records('event', ['modulename' => 'threesixo', 'instance' => $id]);

    // Finally, delete the 360-degree feedback.
    return $DB->delete_records("threesixo", ["id" => $id]);
}

/**
 * Features supported by this plugin.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if unknown
 */
function threesixo_supports($feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_timeline in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return action_interface|null
 */
function threesixo_core_calendar_provide_event_action(calendar_event $event, action_factory $factory, int $userid = 0) {
    global $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['threesixo'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $now = time();

    if (!empty($cm->customdata['timeclose']) && $cm->customdata['timeclose'] < $now) {
        // The threesixo has closed so the user can no longer submit anything.
        return null;
    }

    // The threesixo is actionable if we don't have a start time or the start time is in the past, if the instance is ready,
    // and the user can provide feedback to other users.
    $actionable = (empty($cm->customdata['timeopen']) || $cm->customdata['timeopen'] <= $now) && api::is_ready($event->instance);
    $pendingcount = 0;
    if ($actionable) {
        $pendingcount = api::count_users_awaiting_feedback($event->instance, $userid);
        if (empty($pendingcount)) {
            // There is no action if the instance is not yet ready, or the user can't provide feedback to the participants, or the
            // user has already finished providing feedback to all of the participants..
            return null;
        }
    }

    return $factory->create_instance(
        get_string('providefeedback', 'threesixo'),
        new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]),
        $pendingcount,
        $actionable
    );
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The date must be after this date'],
 *     [1506741172, 'The date must be before this date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $threesixo The module instance to get the range from
 * @return array
 */
function threesixo_core_calendar_get_valid_event_timestart_range(calendar_event $event, stdClass $threesixo) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == api::THREESIXO_EVENT_TYPE_OPEN) {
        if (!empty($threesixo->timeclose)) {
            $maxdate = [
                $threesixo->timeclose,
                get_string('openafterclose', 'threesixo'),
            ];
        }
    } else if ($event->eventtype == api::THREESIXO_EVENT_TYPE_CLOSE) {
        if (!empty($threesixo->timeopen)) {
            $mindate = [
                $threesixo->timeopen,
                get_string('closebeforeopen', 'threesixo'),
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the threesixo module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the threesixo instance
 * according to the type of event provided.
 *
 * @param calendar_event $event
 * @param stdClass $threesixo The module instance to get the range from
 */
function threesixo_core_calendar_event_timestart_updated(calendar_event $event, stdClass $threesixo) {
    global $DB;

    if (!in_array($event->eventtype, [api::THREESIXO_EVENT_TYPE_OPEN, api::THREESIXO_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    // Something weird going on. The event is for a different module so we should ignore it.
    if ($modulename != 'threesixo') {
        return;
    }

    if ($threesixo->id != $instanceid) {
        return;
    }

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == api::THREESIXO_EVENT_TYPE_OPEN) {
        // If the event is for the threesixo activity opening then we should set the start time of the threesixo activity
        // to be the new start time of the event.
        if ($threesixo->timeopen != $event->timestart) {
            $threesixo->timeopen = $event->timestart;
            $modified = true;
        }
    } else if ($event->eventtype == api::THREESIXO_EVENT_TYPE_CLOSE) {
        // If the event is for the threesixo activity closing then we should set the end time of the threesixo activity
        // to be the new start time of the event.
        if ($threesixo->timeclose != $event->timestart) {
            $threesixo->timeclose = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $threesixo->timemodified = time();
        // Persist the instance changes.
        $DB->update_record('threesixo', $threesixo);
        $event = course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Callback function that determines whether an action event should be showing its item count
 * based on the event type and the item count.
 *
 * @param calendar_event $event The calendar event.
 * @param int $itemcount The item count associated with the action event.
 * @return bool
 */
function threesixo_core_calendar_event_action_shows_item_count(calendar_event $event, $itemcount = 0) {
    // Make sure that this event is for the 360 feedback module (shouldn't happen though).
    if ($event->modulename !== 'threesixo') {
        return false;
    }

    // Item count should be shown if there is one or more item count.
    return $itemcount > 0;
}
