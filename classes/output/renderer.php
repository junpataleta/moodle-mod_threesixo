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
 * Renderer class for template library.
 *
 * @package    mod_threesixty
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixty\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Renderer class for 360 users.
 *
 * @copyright  2015 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param list_participants $page
     * @return string html for the page
     */
    public function render_list_participants($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_threesixty/list_participants', $data);
    }

    /**
     * @param questionnaire $page
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_questionnaire($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_threesixty/questionnaire', $data);
    }

    /**
     * @param list_360_items $page
     * @return bool|string html for the page.
     * @throws \moodle_exception
     */
    public function render_list_360_items($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_threesixty/list_360_items', $data);
    }

    /**
     * @param report $page
     * @return bool|string html for the page.
     * @throws \moodle_exception
     */
    public function render_report($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_threesixty/report', $data);
    }
}
