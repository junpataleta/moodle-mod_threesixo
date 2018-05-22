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
 * Helper functions for the mod_threesixo activity module.
 *
 * @package    mod_threesixo
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_threesixo;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class containing helper functions for the mod_threesixo activity module.
 *
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Gets the localised string value of a status code.
     *
     * @param int $status
     * @return string
     */
    public static function get_status_string($status) {
        switch ($status) {
            case api::STATUS_PENDING: // Pending.
                return get_string('statuspending', 'mod_threesixo');
            case api::STATUS_IN_PROGRESS: // In Progress.
                return get_string('statusinprogress', 'mod_threesixo');
            case api::STATUS_COMPLETE: // Completed.
                return get_string('statuscompleted', 'mod_threesixo');
            case api::STATUS_DECLINED: // Declined.
                return get_string('statusdeclined', 'mod_threesixo');
            default:
                throw new moodle_exception('errorinvalidstatus', 'mod_threesixo');
        }
    }

    /**
     * Gets the localised string value of a status code.
     *
     * @param int $value The scale value.
     * @return string|false The scale description. False if there's no scale mathing the given value.
     */
    public static function get_scale_values($value) {
        $scales = api::get_scales();
        foreach ($scales as $scale) {
            if ($scale->scale == $value) {
                return $scale->description;
            }
        }
        return false;
    }

    /**
     * Gets the localised string value of a question type code.
     *
     * @param int $type The question type numeric equivalent
     * @return string The string equivalent of the question type.
     * @throws \coding_exception
     */
    public static function get_question_type_text($type) {
        switch ($type) {
            case api::QTYPE_RATED:
                return get_string('qtyperated', 'threesixo');
            case api::QTYPE_COMMENT:
                return get_string('qtypecomment', 'threesixo');
            default:
                return '';
        }
    }
}
