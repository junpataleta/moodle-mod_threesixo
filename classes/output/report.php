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
 * Class containing data for rendering the 360 feedback report page for a participant.
 *
 * @package    mod_threesixo
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixo\output;

use action_link;
use mod_threesixo\api;
use moodle_url;
use renderable;
use renderer_base;
use single_select;
use stdClass;
use templatable;
use url_select;

/**
 * Class containing data for rendering the 360 feedback report page for a participant.
 *
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements renderable, templatable {

    /** @var int The 360 instance ID. */
    protected $threesixtyid;

    /** @var array List of items with the average rating/comments given to the user. */
    protected $items;

    /** @var url_select The user selector control. */
    protected $userselect;

    /** @var action_link The action link pointing to the 360 feedback view page. */
    protected $activitylink;

    /** @var single_select $downloadselect The single element containing the download format options. */
    protected $downloadselect;

    /**
     * report constructor.
     *
     * @param int $cmid The course module ID of the 360 instance.
     * @param int $threesixtyid The 360 instance ID.
     * @param array $items List of items with the average rating/comments given to the user.
     * @param array $participants List of participants for the 360 activity.
     * @param int $touserid The user this report is being generated for.
     * @param array $downloadformats List of download format options for the report.
     */
    public function __construct($cmid, $threesixtyid, $items, $participants, $touserid, $downloadformats = null) {
        $this->threesixtyid = $threesixtyid;
        $this->items = $items;

        // Generate data for the user selector widget.
        $participantslist = [];
        foreach ($participants as $participant) {
            // Module URL.
            $urlparams = ['threesixo' => $this->threesixtyid, 'touser' => $participant->userid];
            $linkurl = new moodle_url('/mod/threesixo/report.php', $urlparams);
            // Add module URL (as key) and name (as value) to the activity list array.
            $participantslist[$linkurl->out(false)] = fullname($participant);
        }

        if (!empty($participantslist)) {
            $select = new url_select($participantslist, '', ['' => get_string('switchtouser', 'mod_threesixo')]);
            $select->set_label(get_string('jumpto'), ['class' => 'sr-only']);
            $select->attributes = ['id' => 'jump-to-user-report'];
            $select->class = 'd-inline-block';
            $this->userselect = $select;
        }

        if (!empty($downloadformats)) {
            $downloadlabel = get_string('downloadreportas', 'mod_threesixo');
            $downloadurlparams = ['threesixo' => $this->threesixtyid, 'touser' => $touserid];
            $downloadurl = new moodle_url('/mod/threesixo/report_download.php', $downloadurlparams);
            $downloadselect = new single_select($downloadurl, 'format', $downloadformats, '', ['' => $downloadlabel]);
            $downloadselect->set_label($downloadlabel, ['class' => 'sr-only']);
            $downloadselect->attributes = ['id' => 'download-user-report'];
            $this->downloadselect = $downloadselect;
        }

        // Activity link.
        $linkname = get_string('backto360dashboard', 'mod_threesixo');
        $attributes = [
            'class' => 'btn btn-link',
            'id' => 'back-to-dashboard',
            'title' => $linkname,
        ];
        $activitylinkurl = new moodle_url('/mod/threesixo/view.php', ['id' => $cmid]);
        $this->activitylink = new action_link($activitylinkurl, $linkname, null, $attributes);
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
        $data = new stdClass();
        if ($this->userselect) {
            $data->userselect = $this->userselect->export_for_template($output);
        }
        if ($this->downloadselect) {
            $data->downloadselect = $this->downloadselect->export_for_template($output);
        }
        $data->activitylink = $this->activitylink->export_for_template($output);
        $data->ratings = [];
        $data->comments = [];
        foreach ($this->items as $item) {
            if ($item->type == api::QTYPE_RATED) {
                if (isset($item->averagerating)) {
                    $item->progresspercentage = ($item->averagerating / 6) * 100;
                }
                $data->ratings[] = $item;
            } else {
                $data->commentitems[] = $item;
            }
        }
        return $data;
    }
}
