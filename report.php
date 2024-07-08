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
 * @package mod_threesixo
 */

require_once('../../config.php');

$threesixtyid = required_param('threesixo', PARAM_INT);
$touserid = required_param('touser', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixo');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$threesixty = \mod_threesixo\api::get_instance($threesixtyid);

$viewingforself = $touserid == $USER->id;
$participants = [];
if (!$viewingforself) {
    require_capability('mod/threesixo:viewreports', $context);

    $participants = \mod_threesixo\api::get_participants($threesixtyid, $USER->id, $threesixty->with_self_review);
} else if (!\mod_threesixo\api::can_view_own_report($threesixty)) {
    throw new moodle_exception('errorreportnotavailable', 'mod_threesixo');
}

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixo/report.php', ['threesixo' => $threesixtyid, 'touser' => $touserid]);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewfeedbackforuser', 'mod_threesixo'), 3);

// Make sure that the report being viewed is for someone who can participate in the activity.
if (\mod_threesixo\api::can_respond($threesixty, $touserid) !== true) {
    throw new moodle_exception('invaliduserid', 'error', new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]));
}

$touser = core_user::get_user($touserid);
// Render user heading.
$userheading = [
    'heading' => fullname($touser),
    'user' => $touser,
    'usercontext' => context_user::instance($touserid),
];
$contextheader = $OUTPUT->context_header($userheading, 3);
echo html_writer::div($contextheader, 'card card-block p-1');

// Download format options.
$downloadformats = [];
$formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
foreach ($formats as $format) {
    if (!$format->is_enabled()) {
        continue;
    }
    $downloadformats[$format->name] = $format->displayname;
}

$responses = mod_threesixo\api::get_feedback_for_user($threesixtyid, $touserid);
$responselist = new mod_threesixo\output\report($cm->id, $threesixtyid, $responses, $participants, $touserid, $downloadformats);
$renderer = $PAGE->get_renderer('mod_threesixo');
echo $renderer->render($responselist);

echo $OUTPUT->footer();
