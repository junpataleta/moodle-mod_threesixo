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

use mod_threesixo\api;

require_once('../../config.php');

// The threesixo record id.
$id = required_param('threesixo', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_instance($id, 'threesixo');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Return URL in case of error.
$returnurl = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
$submitted = optional_param('feedback-submitted', 0, PARAM_INT);
if ($submitted) {
    redirect($returnurl->out(false));
}
$submissionid = required_param('submission', PARAM_INT);

try {
    $submission = api::get_submission($submissionid);
} catch (moodle_exception $e) {
    // Show a friendlier message if submission record is not found.
    throw new moodle_exception('errorcannotprovidefeedbacktouser', 'threesixo', $returnurl);
}

$threesixty = api::get_instance($submission->threesixo);

// Make sure that the 360 instance ID matches the 360 instance ID from the submission entry and that the feedback recipient
// is still enrolled in the course.
if ($id != $submission->threesixo || !api::can_provide_feedback_to_user($cm, $submission->touser, $threesixty)) {
    throw new moodle_exception('errorcannotprovidefeedbacktouser', 'threesixo', $returnurl);
}

// Make sure the user can participate in the activity.
if (api::can_respond($threesixty, $USER->id, $context) !== true) {
    throw new moodle_exception('errorcannotparticipate', 'mod_threesixo', $returnurl);
}

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixo/questionnaire.php', ['threesixo' => $cm->instance, 'submission' => $submission->id]);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('providefeedback', 'mod_threesixo'), 2);

// Check if instance is already open.
$openmessage = api::is_open($threesixty, true);
$isready = api::is_ready($threesixty);
if ($isready && $openmessage === true) {
    // Render user heading.
    if ($submission->touser > 0) {
        $touser = core_user::get_user($submission->touser);
        $userheading = [
            'heading' => fullname($touser),
            'user' => $touser,
            'usercontext' => context_user::instance($submission->touser),
        ];

        $contextheader = $OUTPUT->context_header($userheading, 3);
        $container = html_writer::div($contextheader, 'card-body');
        echo html_writer::div($container, 'card');
    }

    // Set status to in progress if pending.
    if ($submission->status == api::STATUS_PENDING) {
        api::set_completion($submission->id, api::STATUS_IN_PROGRESS);
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
