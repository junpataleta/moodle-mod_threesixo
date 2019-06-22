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
 * @copyright 2017 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixo
 */
require_once('../../config.php');

// The threesixo record id.
$id = required_param('threesixo', PARAM_INT);
$submissionid = required_param('submission', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_instance($id, 'threesixo');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$submission = \mod_threesixo\api::get_submission($submissionid);
$threesixty = \mod_threesixo\api::get_instance($submission->threesixo);

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixo/view.php', ['id' => $cm->id]);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($title));
echo $OUTPUT->heading(get_string('providefeedback', 'mod_threesixo'), 3);

// Check if instance is already open.
$openmessage = \mod_threesixo\api::is_open($threesixty, true);
$isready = \mod_threesixo\api::is_ready($threesixty);
if ($isready && $openmessage === true) {
    // Render user heading.
    if ($submission->touser > 0) {
        $touser = core_user::get_user($submission->touser);
        $userheading = [
            'heading' => fullname($touser),
            'user' => $touser,
            'usercontext' => context_user::instance($submission->touser)
        ];

        $contextheader = $OUTPUT->context_header($userheading, 3);
        $container = html_writer::div($contextheader, 'card-body');
        echo html_writer::div($container, 'card');
    }

    // Set status to in progress if pending.
    if ($submission->status == \mod_threesixo\api::STATUS_PENDING) {
        \mod_threesixo\api::set_completion($submission->id, \mod_threesixo\api::STATUS_IN_PROGRESS);
    }

    // 360-degree feedback question list.
    $questionslist = new mod_threesixo\output\questionnaire($submission);
    $questionslistoutput = $PAGE->get_renderer('mod_threesixo');
    echo $questionslistoutput->render($questionslist);

} else {
    if ($isready) {
        $message = get_string('instancenotready', 'mod_threesixo');
    } else {
        $message = $openmessage;
    }
    \core\notification::error($message);
    $viewurl = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
    echo html_writer::link($viewurl,  get_string('backto360dashboard', 'mod_threesixo'));
}

echo $OUTPUT->footer();
