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
 * The page containing the feedback to a certain user.
 *
 * @copyright 2017 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixty
 */
require_once('../../config.php');

$threesixtyid = required_param('threesixty', PARAM_INT);
$touserid = required_param('touser', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/threesixty:viewreports', $context);

$threesixty = \mod_threesixty\api::get_instance($threesixtyid);

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixty/view.php', ['id' => $cm->id]);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($title));
echo $OUTPUT->heading(get_string('viewfeedbackforuser', 'mod_threesixty'), 3);

// Render user heading.
if ($touserid > 0) {
    $touser = core_user::get_user($touserid);
    $userheading = [
        'heading' => fullname($touser),
        'user' => $touser,
        'usercontext' => context_user::instance($touserid)
    ];
    $contextheader = $OUTPUT->context_header($userheading, 3);
    echo html_writer::div($contextheader, 'card card-block');
}

$includeself = \mod_threesixty\api::can_respond($threesixtyid, $USER->id, $context) === true;
$participants = \mod_threesixty\api::get_participants($threesixtyid, $USER->id, $includeself);

$responses = mod_threesixty\api::get_feedback_for_user($threesixtyid, $touserid);
$responselist = new mod_threesixty\output\report($cm->id, $threesixtyid, $responses, $participants);
$renderer = $PAGE->get_renderer('mod_threesixty');
echo $renderer->render($responselist);

echo $OUTPUT->footer();