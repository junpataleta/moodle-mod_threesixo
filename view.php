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
 * @package mod_threesixo
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$makeavailable = optional_param('makeavailable', false, PARAM_BOOL);
$release = optional_param('release', -1, PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'threesixo');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$threesixty = \mod_threesixo\api::get_instance($cm->instance);

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/threesixo/view.php', ['id' => $cm->id]);
$title = format_string($threesixty->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('participants', 'mod_threesixo'), 2);

if ($release != -1) {
    // Toggle the released flag.
    \mod_threesixo\api::toggle_released_flag($threesixty, $release);
}

// Edit items.
$instanceready = \mod_threesixo\api::is_ready($threesixty->id);
$canedit = \mod_threesixo\api::can_edit_items($threesixty->id, $context);
$hideallparticipants = !has_capability('moodle/site:accessallgroups', $context);
echo $OUTPUT->box(groups_print_activity_menu($cm, $PAGE->url, true, $hideallparticipants));

if ($canedit) {
    $edititemsurl = new moodle_url('edit_items.php');
    $edititemsurl->param('id', $cm->id);
    echo html_writer::link($edititemsurl, get_string('edititems', 'threesixo'), ['class' => 'btn btn-secondary me-2 mb-2']);
    if (!$instanceready) {
        // Check if we can make the instance available to the respondents.
        if (\mod_threesixo\api::has_items($threesixty->id)) {
            if ($makeavailable) {
                if (\mod_threesixo\api::make_ready($threesixty->id)) {
                    \core\notification::success(get_string('instancenowready', 'mod_threesixo'));
                    // Instance is now ready once made available.
                    $instanceready = true;
                }
            } else {
                $url = $PAGE->url;
                $url->param('makeavailable', true);
                echo html_writer::link($url, get_string('makeavailable', 'threesixo'), ['class' => 'btn btn-secondary pull-right']);
            }
        } else {
            \core\notification::warning(get_string('noitemsyet', 'mod_threesixo'));
        }
    }
}

$canparticipate = mod_threesixo\api::can_respond($threesixty, $USER->id, $context);
if ($instanceready) {
    // Show a release report button if applicable.
    if ($canedit && $threesixty->releasing == \mod_threesixo\api::RELEASING_MANUAL) {
        $url = clone $PAGE->url;
        $releaseparam = $threesixty->released ? 0 : 1;
        $releaselabel = $releaseparam ? get_string('release', 'mod_threesixo') : get_string('release_close', 'mod_threesixo');
        $url->param('release', $releaseparam);
        echo $OUTPUT->single_button($url, $releaselabel);
    }
    // Whether to include self in the participants list.
    $includeself = false;
    if ($canparticipate !== true) {
        \core\notification::warning($canparticipate);
    } else {

        // Include self on the list if you can give feedback to others and the instance allows self review.
        $includeself = $threesixty->with_self_review;

        // Generate statuses if you can respond to the feedback.
        \mod_threesixo\api::generate_360_feedback_statuses($threesixty->id, $USER->id, $includeself);

        if (\mod_threesixo\api::can_view_own_report($threesixty)) {
            $reportsurl = new moodle_url('/mod/threesixo/report.php');
            $reportsurl->params([
                'threesixo' => $threesixty->id,
                'touser' => $USER->id,
            ]);

            $feedbackreport = html_writer::link($reportsurl, get_string('viewfeedbackreport', 'threesixo'),
                ['class' => 'btn btn-secondary mx-2']);
            echo html_writer::div($feedbackreport, 'text-end');
        }
    }

    try {
        // Check if instance is already open.
        $isopen = \mod_threesixo\api::is_open($threesixty, true);
        if ($isopen !== true) {
            // Show warning.
            \core\notification::warning($isopen);
            // Set to false, for usage on the participants list renderable.
            $isopen = false;
        }
        $participants = \mod_threesixo\api::get_participants($threesixty->id, $USER->id, $includeself);
        $canviewreports = \mod_threesixo\api::can_view_reports($context);

        // 360-degree feedback To-do list.
        $memberslist = new mod_threesixo\output\list_participants($threesixty, $USER->id, $participants, $canviewreports, $isopen);
        $memberslistoutput = $PAGE->get_renderer('mod_threesixo');
        echo $memberslistoutput->render($memberslist);
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }

} else {
    // Show error to respondents that indicate that the activity is not yet ready.
    if ($canparticipate === true) {
        \core\notification::error(get_string('instancenotready', 'mod_threesixo'));
    }
}

echo $OUTPUT->footer();
