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
 * Class containing data to render the questionnaire page.
 *
 * @package    mod_threesixo
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixo\output;

use coding_exception;
use core_user;
use dml_exception;
use mod_threesixo\api;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Class containing data to render the questionnaire page.
 *
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionnaire implements renderable, templatable {

    /** @var stdClass The feedback submission data. */
    protected $submission;

    /**
     * questionnaire constructor.
     *
     * @param stdClass $submission The feedback submission data.
     */
    public function __construct($submission) {
        $this->submission = $submission;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $data = new stdClass();

        $submission = $this->submission;
        $threesixty = api::get_instance($submission->threesixo);
        switch ($submission->status) {
            case api::STATUS_IN_PROGRESS: // In Progress.
                $data->statusclass = 'badge-info';
                $data->status = get_string('statusinprogress', 'threesixo');
                break;
            case api::STATUS_COMPLETE: // Completed.
                $data->statusclass = 'badge-success';
                $data->status = get_string('statuscompleted', 'threesixo');
                break;
            case api::STATUS_DECLINED: // Declined.
                $data->statusclass = 'badge-warning';
                $data->status = get_string('statusdeclined', 'threesixo');
                break;
            default: // Pending.
                $data->statusclass = 'badge-secondary';
                $data->status = get_string('statuspending', 'threesixo');
                break;
        }
        $data->scales = api::get_scales();

        $items = api::get_items($submission->threesixo);
        $ratedquestions = [];
        $commentquestions = [];

        foreach ($items as $item) {
            switch ($item->type) {
                case api::QTYPE_RATED:
                    $ratedquestions[] = $item;
                    break;
                case api::QTYPE_COMMENT:
                    $commentquestions[] = $item;
                    break;
                default:
                    break;
            }
        }

        $cm = null;
        $modinfo = get_fast_modinfo($threesixty->course);
        foreach ($modinfo->get_instances_of('threesixo') as $instance) {
            if ($instance->instance === $threesixty->id) {
                $cm = $instance;
                break;
            }
        }
        $data->hasratedquestions = !empty($ratedquestions);
        $data->ratedquestions = $ratedquestions;
        $data->hascommentquestions = !empty($commentquestions);
        $data->commentquestions = $commentquestions;
        $data->touserid = $submission->touser;
        $fields = implode(",", \core_user\fields::get_name_fields());
        $touser = core_user::get_user($submission->touser, $fields);
        $data->tousername = fullname($touser);
        $data->threesixtyid = $submission->threesixo;
        $data->submissionid = $submission->id;
        $data->anonymous = $threesixty->anonymous;
        $data->returnurl = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
        $data->fromuserid = $submission->fromuser;
        $data->actionurl = $PAGE->url;

        // Hack to display the radio button to enable selection during Behat runs.
        // It's disgusting, but there's no way around it at the moment.
        $data->optionclass = defined('BEHAT_SITE_RUNNING') ? '' : 'threesixo_rating_option';

        return $data;
    }
}
