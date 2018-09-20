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
 * @package   mod_threesixo
 * @copyright 2017 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

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

    return true;
}
