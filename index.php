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

// Course ID.
$id = required_param('id', PARAM_INT);

// Ensure that the course specified is valid.
if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception('Course ID is incorrect');
}

require_course_login($course);

$context = context_course::instance($course->id);
$PAGE->set_context($context);

$strthreesixo = get_string('modulename', 'threesixo');
$title = get_string('courseinstances', 'threesixo', format_string($course->fullname));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url('/mod/threesixo/index.php', ['id' => $id]);
$PAGE->add_body_class('limitedwidth');
$PAGE->navbar->add($strthreesixo);
echo $OUTPUT->header();

$threesixos = get_all_instances_in_course('threesixo', $course);
if (empty($threesixos)) {
    $returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    throw new moodle_exception('thereareno', 'moodle', $returnurl->out(), $strthreesixos);
}

$instancedata = [];
foreach ($threesixos as $instance) {
    $instanceurl = new moodle_url('/mod/threesixo/view.php', ['id' => $instance->coursemodule]);
    $instancedata[] = (object)[
        'name' => format_string($instance->name),
        'url' => $instanceurl->out(),
    ];
}
echo $OUTPUT->render_from_template('mod_threesixo/index', ['instances' => $instancedata]);

echo $OUTPUT->footer();
