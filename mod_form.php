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
 * Print the form to add or edit a 360-degree feedback instance.
 *
 * @author Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixty
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/threesixty/lib.php');

class mod_threesixty_mod_form extends moodleform_mod {

    /**
     * Form definition.
     *
     * @throws coding_exception
     */
    function definition() {

        $mform =& $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        $this->standard_intro_elements();

        // Anonymous.
        $mform->addElement('advcheckbox', 'anonymous', get_string('anonymous', 'mod_threesixty'));

        // 360-degree feedback participants.
        $context = $this->get_context();
        $roles = get_profile_roles($context);
        $roleoptions = role_fix_names($roles, $context, ROLENAME_ALIAS, true);
        $roleoptions[0] = get_string('allparticipants', 'mod_threesixty');
        ksort($roleoptions);
        $mform->addElement('select', 'participantrole', get_string('participants', 'mod_threesixty'), $roleoptions);

        // Availability.
        $mform->addElement('header', 'timinghdr', get_string('availability'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('feedbackopen', 'feedback'),
            array('optional' => true));
        $mform->addElement('date_time_selector', 'timeclose', get_string('feedbackclose', 'feedback'),
            array('optional' => true));

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}