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
 * The first page to view the 360-degree feedback.
 *
 * @copyright 2015 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixty
 */
require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$makeavailable = optional_param('makeavailable', false, PARAM_BOOL);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'threesixty');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$threesixty = \mod_threesixty\api::get_instance($cm->instance);

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixty/view.php', array('id' => $cm->id, 'do_show' => 'view'));
$title = format_string($threesixty->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

echo $OUTPUT->heading($title);
echo $OUTPUT->heading(get_string('participants', 'mod_threesixty'), 3);

if ($makeavailable) {
    if (\mod_threesixty\api::make_ready($threesixty->id)) {
        \core\notification::success(get_string('instancenowready', 'mod_threesixty'));
    }
}
// Edit items.
$instanceready = \mod_threesixty\api::is_ready($threesixty->id);
if (\mod_threesixty\api::can_edit_items($threesixty->id, $context)) {
    $edititemsurl = new moodle_url('edit_items.php');
    $edititemsurl->param('id', $cm->id);
    echo html_writer::link($edititemsurl, get_string('edititems', 'threesixty'), ['class' => 'btn btn-default']);
    if (!$instanceready) {
        $url = $PAGE->url;
        $url->param('makeavailable', true);
        echo html_writer::link($url, get_string('makeavailable', 'threesixty'), ['class' => 'btn btn-default pull-right']);
    }
}

if ($instanceready) {
    $canparticipate = mod_threesixty\api::can_respond($threesixty, $USER->id, $context);
    if ($canparticipate !== true) {
        \core\notification::warning($canparticipate);
    }

    \mod_threesixty\api::generate_360_feedback_statuses($threesixty->id, $USER->id);
    $participants = \mod_threesixty\api::get_participants($threesixty->id, $USER->id);
    $canviewreports = \mod_threesixty\api::can_view_reports($context);

    // 360-degree feedback To-do list.
    $memberslist = new mod_threesixty\output\list_participants($threesixty, $USER->id, $participants, $canviewreports);
    $memberslistoutput = $PAGE->get_renderer('mod_threesixty');
    echo $memberslistoutput->render($memberslist);

} else {
    \core\notification::error(get_string('instancenotready', 'mod_threesixty'));
}

echo $OUTPUT->footer();
