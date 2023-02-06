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
 * 360-degree feedback items management page.
 *
 * @copyright 2017 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixo
 */

require_once("../../config.php");

$cmid = required_param('id', PARAM_INT);
$itemid = optional_param('itemid', 0, PARAM_INT);
$makeavailable = optional_param('makeavailable', false, PARAM_BOOL);

$viewurl = new moodle_url('view.php');
$viewurl->param('id', $cmid);

if ($cmid == 0) {
    throw new moodle_exception('error360notfound', 'mod_threesixo', $viewurl);
}

$PAGE->set_url('/mod/threesixo/edit_items.php', ['id' => $cmid]);

if (!$cm = get_coursemodule_from_id('threesixo', $cmid)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}

require_login($course, true, $cm);

if (!$threesixty = $DB->get_record("threesixo", ["id" => $cm->instance])) {
    throw new moodle_exception('error360notfound', 'mod_threesixo', $viewurl);
}

// Check capability to edit items.
$context = context_module::instance($cm->id);
if (!\mod_threesixo\api::can_edit_items($threesixty->id, $context)) {
    throw new moodle_exception('nocaptoedititems', 'mod_threesixo', $viewurl);
}

$question = '';
$questiontype = 0;

$PAGE->navbar->add(get_string('titlemanageitems', 'threesixo'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($threesixty->name);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->heading(get_string('edititems', 'mod_threesixo'), 2);

$viewurl = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
// Check if we can make the activity avaialble from here.
$instanceready = \mod_threesixo\api::is_ready($threesixty->id);
$makeavailableurl = null;
if (!$instanceready) {
    // Check if we can make the instance available to the respondents.
    if (\mod_threesixo\api::has_items($threesixty->id)) {
        $makeavailableurl = clone $viewurl;
        $makeavailableurl->param('makeavailable', true);
    }
}

// 360-degree feedback item list.
$itemslist = new mod_threesixo\output\list_360_items($cmid, $course->id, $threesixty->id, $viewurl, $makeavailableurl);
$itemslistoutput = $PAGE->get_renderer('mod_threesixo');
echo $itemslistoutput->render($itemslist);

echo $OUTPUT->footer();
