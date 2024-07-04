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

// This file keeps track of upgrades to
// the feedback module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * Upgrade code for the 360-degree feedback activity plugin.
 *
 * @package    mod_threesixo
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_threesixo upgrade function.
 *
 * @param int $oldversion The old version number.
 * @return bool
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_threesixo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017011100) {
        // Nothing to do here. Moodle Plugins site's just complaining about missing upgrade.php.

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2017011100, 'threesixo');
    }

    if ($oldversion < 2018052601) {

        // Define field with_self_review to be added to threesixo.
        $table = new xmldb_table('threesixo');
        $field = new xmldb_field('with_self_review', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'publish_responses');

        // Conditionally launch add field with_self_review.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2018052601, 'threesixo');
    }

    if ($oldversion < 2018052602) {

        // Changing type of field type on table threesixo_question to int.
        $table = new xmldb_table('threesixo_question');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'question');

        // Launch change of type for field type.
        $dbman->change_field_type($table, $field);

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2018052602, 'threesixo');
    }

    if ($oldversion < 2018120301) {

        // Define field releasing to be added to threesixo.
        $table = new xmldb_table('threesixo');
        $field = new xmldb_field('releasing', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field releasing.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('released', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'releasing');

        // Conditionally launch add field released.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2018120301, 'threesixo');
    }

    if ($oldversion < 2018120302) {

        // Define field undodecline to be added to threesixo.
        $table = new xmldb_table('threesixo');
        $field = new xmldb_field('undodecline', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'released');

        // Conditionally launch add field undodecline.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2018120302, 'threesixo');
    }

    if ($oldversion < 2018120303) {

        // Define field completionsubmit to be dropped from threesixo.
        $table = new xmldb_table('threesixo');
        $field = new xmldb_field('completionsubmit');

        // Conditionally launch drop field completionsubmit.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field publish_responses to be dropped from threesixo.
        $field = new xmldb_field('publish_responses');

        // Conditionally launch drop field publish_responses.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2018120303, 'threesixo');
    }

    if ($oldversion < 2019052000) {

        // Define field touser to be dropped from threesixo_response.
        $table = new xmldb_table('threesixo_response');
        $field = new xmldb_field('salt');

        // Conditionally launch drop field touser.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2019052000, 'threesixo');
    }

    if ($oldversion < 2024063000) {
        // Queue the ad-hoc task to fix.
        $task = new \mod_threesixo\task\fix_responses();
        \core\task\manager::queue_adhoc_task($task, true);
        // Threesixo savepoint reached.
        upgrade_mod_savepoint(true, 2024063000, 'threesixo');
    }

    return true;
}
