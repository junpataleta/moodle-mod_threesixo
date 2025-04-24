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
 * Generates the user's feedback report for download.
 *
 * @copyright 2019 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixo
 */

require_once('../../config.php');

$threesixtyid = required_param('threesixo', PARAM_INT);
$touserid = required_param('touser', PARAM_INT);
$format = required_param('format', PARAM_ALPHA);

list ($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixo');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$threesixty = \mod_threesixo\api::get_instance($threesixtyid);

$viewingforself = $touserid == $USER->id;
if (!$viewingforself) {
    require_capability('mod/threesixo:viewreports', $context);
} else if (!\mod_threesixo\api::can_view_own_report($threesixty)) {
    throw new moodle_exception('errorreportnotavailable', 'mod_threesixo');
}

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$urlparams = [
    'threesixo' => $threesixtyid,
    'touser' => $touserid,
    'downloadformat' => $format,
];
$PAGE->set_url('/mod/threesixo/report_download.php', $urlparams);
$PAGE->set_heading($course->fullname);
$title = format_string($threesixty->name);
$PAGE->set_title($title);

// Make sure that the report being viewed is for someone who can participate in the activity.
if (\mod_threesixo\api::can_respond($threesixty, $touserid) !== true) {
    throw new moodle_exception('invaliduserid', 'error', new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]));
}

// Check first if we can process the required data format for the report.
$plugins = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
if (!isset($plugins[$format]) || !$plugins[$format]->is_enabled()) {
    $urlparams = [
        'threesixo' => $threesixtyid,
        'touser' => $touserid,
    ];
    throw new moodle_exception('dataformatinvalid', 'threesixo', new moodle_url('/mod/threesixo/report.php', $urlparams));
}

// Otherwise, everything's good. Proceed with the processing.

// Fetch the user.
$touser = core_user::get_user($touserid);

// Get the responses to the user.
$responses = \mod_threesixo\api::get_feedback_for_user($threesixtyid, $touserid);

// Set the column names.
$columnnames = [
    'question' => get_string('question', 'threesixo'),
    'type' => get_string('questiontype', 'threesixo'),
    'responsecount' => get_string('numrespondents', 'threesixo'),
    'averagerating' => get_string('ratingaverage', 'threesixo'),
    'comments' => get_string('comments', 'threesixo'),
];

// Prepare the report data.
$reportdata = [];
foreach ($responses as $response) {
    switch ($response->type) {
        case \mod_threesixo\api::QTYPE_RATED:
            $qtype = get_string('qtyperated', 'threesixo');
            $rating = $response->averagerating ?? get_string('notapplicableabbr', 'threesixo');
            // Rated questions don't have comments.
            $comments = get_string('notapplicableabbr', 'threesixo');
            $count = $response->responsecount;
            break;
        case \mod_threesixo\api::QTYPE_COMMENT:
            $qtype = get_string('qtypecomment', 'threesixo');
            // Comment questions don't have ratings.
            $rating = get_string('notapplicableabbr', 'threesixo');
            $count = count($response->comments);
            // Process commments.
            $commentlist = [];
            foreach ($response->comments as $comment) {
                $commentlist[] = get_string('commentfromuser', 'threesixo', $comment);
            }
            if ($format === 'html') {
                $comments = html_writer::alist($commentlist);
            } else {
                $comments = '"' . implode(PHP_EOL, $commentlist) . '"';
            }

            break;
        default:
            // We've got an invalid question type. This shouldn't happen though.
            throw new moodle_exception('qtypeinvalid', 'threesixo');
    }

    $reportdata[] = (object)[
        'question' => $response->question,
        'type' => $qtype,
        'responsecount' => $count,
        'averagerating' => $rating,
        'comments' => $comments,
    ];
}

// Download the report file.
\core\dataformat::download_data('360FeedbackReport-' . fullname($touser), $format, $columnnames, $reportdata);
