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
 * Class containing data for users that need to be given with 360 feedback.
 *
 * @package    mod_threesixty
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixty\output;

defined('MOODLE_INTERNAL') || die();

use core_user;
use mod_threesixty\api;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Class containing data for users that need to be given with 360 feedback.
 *
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionnaire implements renderable, templatable {
    protected $submission;

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
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $data = new stdClass();

        $submission = $this->submission;
        $threesixty = api::get_instance($submission->threesixty);
        switch ($submission->status) {
            case api::STATUS_IN_PROGRESS: // In Progress.
                $data->statusclass = 'label-info';
                $data->status = get_string('statusinprogress', 'threesixty');
                break;
            case api::STATUS_COMPLETE: // Completed.
                $data->statusclass = 'label-success';
                $data->status = get_string('statuscompleted', 'threesixty');
                break;
            case api::STATUS_DECLINED: // Declined.
                $data->statusclass = 'label-warning';
                $data->status = get_string('statusdeclined', 'threesixty');
                break;
            default: // Pending.
                $data->statusclass = 'label';
                $data->status = get_string('statuspending', 'threesixty');
                break;
        }
        $data->scales = api::get_scales();

        $items = api::get_items($submission->threesixty);
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
        $data->ratedquestions = $ratedquestions;
        $data->commentquestions = $commentquestions;
        $data->touserid = $submission->touser;
        $touser = core_user::get_user($submission->touser, get_all_user_name_fields(true));
        $data->tousername = fullname($touser);
        $data->threesixtyid = $submission->threesixty;
        $data->anonymous = $threesixty->anonymous;
        $data->returnurl = $PAGE->url;
        $data->fromuserid = $submission->fromuser;

        return $data;
    }
}