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
 * @copyright 2017 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/threesixo/lib.php');

/**
 * Class mod_threesixo_mod_form.
 *
 * @copyright 2017 Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_threesixo
 */
class mod_threesixo_mod_form extends moodleform_mod {

    /**
     * Form definition.
     *
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     */
    public function definition() {

        $mform =& $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        $this->standard_intro_elements();

        // Anonymous.
        $mform->addElement('advcheckbox', 'anonymous', get_string('anonymous', 'mod_threesixo'));

        // Self-review.
        $mform->addElement('advcheckbox', 'with_self_review', get_string('enableselfreview', 'mod_threesixo'));
        $mform->disabledIf('with_self_review', 'anonymous', 'checked');

        // 360-degree feedback participants.
        $context = $this->get_context();
        $roles = get_profile_roles($context);
        $roleoptions = role_fix_names($roles, $context, ROLENAME_ALIAS, true);
        $roleoptions[0] = get_string('allparticipants', 'mod_threesixo');
        ksort($roleoptions);
        $mform->addElement('select', 'participantrole', get_string('participants', 'mod_threesixo'), $roleoptions);

        // Releasing options.
        $releasingoptions = [
            \mod_threesixo\api::RELEASING_NONE => get_string('rel_closed', 'mod_threesixo'),
            \mod_threesixo\api::RELEASING_OPEN => get_string('rel_open', 'mod_threesixo'),
            \mod_threesixo\api::RELEASING_MANUAL => get_string('rel_manual', 'mod_threesixo'),
            \mod_threesixo\api::RELEASING_AFTER => get_string('rel_after', 'mod_threesixo'),
        ];
        $mform->addElement('select', 'releasing', get_string('releasing', 'mod_threesixo'), $releasingoptions);
        $mform->addHelpButton('releasing', 'releasing', 'mod_threesixo');

        // Allow participants to undo declined feedback submissions.
        $mform->addElement('advcheckbox', 'undodecline', get_string('allowundodecline', 'mod_threesixo'));

        // Availability.
        $mform->addElement('header', 'timinghdr', get_string('availability'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('feedbackopen', 'feedback'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeclose', get_string('feedbackclose', 'feedback'), ['optional' => true]);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
