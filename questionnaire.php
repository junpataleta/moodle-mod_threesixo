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
 * @author Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixty
 */
require_once('../../config.php');

// The threesixty record id.
$id = required_param('threesixty', PARAM_INT);
$submissionid = required_param('submission', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_instance($id, 'threesixty');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$submission = \mod_threesixty\api::get_submission($submissionid);
$threesixty = \mod_threesixty\api::get_instance($submission->threesixty);

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixty/view.php', ['id' => $cm->id]);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($title));
echo $OUTPUT->heading(get_string('providefeedback', 'mod_threesixty'), 3);

if (\mod_threesixty\api::is_ready($threesixty)) {
    // Render user heading.
    if ($submission->touser > 0) {
        $touser = core_user::get_user($submission->touser);
        $userheading = [
            'heading' => fullname($touser),
            'user' => $touser,
            'usercontext' => context_user::instance($submission->touser)
        ];

        $contextheader = $OUTPUT->context_header($userheading, 3);
        echo html_writer::div($contextheader, 'card card-block');
    }

    // Set status to in progress if pending.
    if ($submission->status == \mod_threesixty\api::STATUS_PENDING) {
        \mod_threesixty\api::set_completion($submission->id, \mod_threesixty\api::STATUS_IN_PROGRESS);
    }

    // 360-degree feedback question list.
    $questionslist = new mod_threesixty\output\questionnaire($submission);
    $questionslistoutput = $PAGE->get_renderer('mod_threesixty');
    echo $questionslistoutput->render($questionslist);

} else {
    \core\notification::error(get_string('instancenotready', 'mod_threesixty'));
    $viewurl = new moodle_url('/mod/threesixty/view.php', ['id' => $cm->id]);
    echo html_writer::link($viewurl,  get_string('backto360dashboard', 'mod_threesixty'));
}

echo $OUTPUT->footer();
