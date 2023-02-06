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

use calendar_event;
use moodle_exception;
use stdClass;

/**
 * Class containing helper functions for the mod_threesixo activity module.
 *
 * @package mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Gets the localised string value of a status code.
     *
     * @param int $status
     * @return string
     */
    public static function get_status_string($status) {
        switch ($status) {
            case api::STATUS_PENDING: // Pending.
                return get_string('statuspending', 'mod_threesixo');
            case api::STATUS_IN_PROGRESS: // In Progress.
                return get_string('statusinprogress', 'mod_threesixo');
            case api::STATUS_COMPLETE: // Completed.
                return get_string('statuscompleted', 'mod_threesixo');
            case api::STATUS_DECLINED: // Declined.
                return get_string('statusdeclined', 'mod_threesixo');
            default:
                throw new moodle_exception('errorinvalidstatus', 'mod_threesixo');
        }
    }

    /**
     * Gets the localised string value of a status code.
     *
     * @param int $value The scale value.
     * @return string|false The scale description. False if there's no scale mathing the given value.
     */
    public static function get_scale_values($value) {
        $scales = api::get_scales();
        foreach ($scales as $scale) {
            if ($scale->scale == $value) {
                return $scale->description;
            }
        }
        return false;
    }

    /**
     * Gets the localised string value of a question type code.
     *
     * @param int $type The question type numeric equivalent
     * @return string The string equivalent of the question type.
     * @throws \coding_exception
     */
    public static function get_question_type_text($type) {
        switch ($type) {
            case api::QTYPE_RATED:
                return get_string('qtyperated', 'threesixo');
            case api::QTYPE_COMMENT:
                return get_string('qtypecomment', 'threesixo');
            default:
                return '';
        }
    }

    /**
     * This creates new calendar events given as timeopen and timeclose by $threesixo.
     *
     * @param stdClass $threesixo The 360-degree feedback instance.
     * @return void
     */
    public static function set_events($threesixo) {
        global $CFG;

        require_once($CFG->dirroot.'/calendar/lib.php');

        // Get CMID if not sent as part of $threesixo.
        if (!isset($threesixo->coursemodule)) {
            $cm = get_coursemodule_from_instance('threesixo', $threesixo->id, $threesixo->course);
            $threesixo->coursemodule = $cm->id;
        }

        // Common event parameters.
        $instanceid = $threesixo->id;
        $courseid = $threesixo->course;
        $eventdescription = format_module_intro('threesixo', $threesixo, $threesixo->coursemodule, false);
        $visible = instance_is_visible('threesixo', $threesixo);

        // Calendar event for when the 360-degree feedback opens.
        $eventname = get_string('calendarstart', 'threesixo', $threesixo->name);
        $eventtype = api::THREESIXO_EVENT_TYPE_OPEN;
        // Calendar event type is set to action event when there's no timeclose.
        $calendareventtype = empty($threesixo->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        self::set_event($instanceid, $eventname, $eventdescription, $eventtype, $calendareventtype, $threesixo->timeopen,
            $visible, $courseid);

        // Calendar event for when the 360-degree feedback closes.
        $eventname = get_string('calendarend', 'threesixo', $threesixo->name);
        $eventtype = api::THREESIXO_EVENT_TYPE_CLOSE;
        $calendareventtype = CALENDAR_EVENT_TYPE_ACTION;
        self::set_event($instanceid, $eventname, $eventdescription, $eventtype, $calendareventtype, $threesixo->timeclose,
            $visible, $courseid);
    }

    /**
     * Sets the calendar event for the 360-degree feedback instance.
     *
     * For existing events, if timestamp is not empty, the event will be updated. Otherwise, it will be deleted.
     * If the event is not yet existing and the timestamp is empty, the event will be created.
     *
     * @param int $id The threesixo instance ID.
     * @param string $eventname The event name.
     * @param string $description The event description.
     * @param string $eventtype The type of the module event.
     * @param int $calendareventtype The calendar event type, whether a standard or an action event.
     * @param int $timestamp The event's timestamp.
     * @param bool $visible Whether this event is visible.
     * @param int $courseid The course ID of this event.
     */
    protected static function set_event($id, $eventname, $description, $eventtype, $calendareventtype, $timestamp, $visible,
                                        $courseid) {
        global $DB;

        // Build the calendar event object.
        $event = new stdClass();
        $event->name         = $eventname;
        $event->description  = $description;
        $event->format       = FORMAT_HTML;
        $event->eventtype    = $eventtype;
        $event->timestart    = $timestamp;
        $event->timesort     = $timestamp;
        $event->visible      = $visible;
        $event->timeduration = 0;
        $event->type         = $calendareventtype;

        // Check if event exists.
        $event->id = $DB->get_field('event', 'id', ['modulename' => 'threesixo', 'instance' => $id, 'eventtype' => $eventtype]);
        if ($event->id) {
            $calendarevent = calendar_event::load($event->id);
            if ($timestamp) {
                // Calendar event exists so update it.
                $calendarevent->update($event, false);
            } else {
                // Calendar event is no longer needed.
                $calendarevent->delete();
            }
        } else if ($timestamp) {
            // Event doesn't exist so create one.
            $event->courseid     = $courseid;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 'threesixo';
            $event->instance     = $id;

            calendar_event::create($event, false);
        }
    }
}
