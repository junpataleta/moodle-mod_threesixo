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
 * @package    mod_threesixo
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixo\output;

use dml_exception;
use mod_threesixo\api;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Class containing data for users that need to be given with 360 feedback.
 *
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_360_items implements \renderable, \templatable {

    /** @var int The context module ID. */
    private $cmid;

    /** @var int The course ID. */
    private $courseid;

    /** @var int The 360-degree feedback instance ID. */
    private $threesixtyid;

    /** @var int The user ID of the user giving the feedback. */
    private $userid;

    /** @var moodle_url The URL to the view.php page. */
    protected $viewurl;

    /** @var moodle_url The URL to the view.php page with the make available parameter set to true. */
    protected $makeavailableurl;

    /**
     * list_360_items constructor.
     *
     * @param int $cmid The context module ID.
     * @param int $courseid The course ID.
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @param moodle_url $viewurl The URL to the view.php page.
     * @param moodle_url $makeavailableurl The URL to the view.php page with the make available parameter set to true.
     */
    public function __construct($cmid, $courseid, $threesixtyid, $viewurl, $makeavailableurl = null) {
        global $USER;

        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->threesixtyid = $threesixtyid;
        $this->userid = $USER->id;
        $this->viewurl = $viewurl;
        $this->makeavailableurl = $makeavailableurl;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @throws \coding_exception
     * @throws dml_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->allitems = [];
        $data->threesixtyid = $this->threesixtyid;
        $data->viewurl = $this->viewurl;
        $data->makeavailableurl = $this->makeavailableurl;

        if ($items = api::get_items($this->threesixtyid)) {
            $itemcount = count($items);

            foreach ($items as $item) {
                $listitem = new stdClass();
                // Item ID.
                $listitem->id = $item->id;

                // Question column.
                $listitem->question = $item->question;

                // Question type.
                $listitem->type = $item->typetext;

                // Action buttons column.
                // Move up and move down button display flags.
                $listitem->moveupbutton = false;
                $listitem->movedownbutton = false;
                if ($itemcount > 1) {
                    if ($item->position == 1) {
                        $listitem->movedownbutton = true;
                    } else if ($item->position == $itemcount) {
                        $listitem->moveupbutton = true;
                    } else if ($item->position > 1 && $item->position < $itemcount) {
                        $listitem->moveupbutton = true;
                        $listitem->movedownbutton = true;
                    }
                }

                // Delete action.
                $listitem->deletebutton = true;

                $data->allitems[] = $listitem;
            }
        }

        return $data;
    }
}
