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
 * Coverage information for mod_threesixo.
 *
 * Moodle's default list covers classes/, tests/generator/ and a handful of conventional root files. That
 * leaves out the backup and restore code, which has its own tests, and counts the data generator, which is
 * test scaffolding rather than plugin code.
 *
 * Two things are deliberately left out:
 *
 * - The page scripts in the plugin root. They are only exercised through Behat, and Behat coverage is not
 *   collected, so including them would only ever report them as uncovered.
 * - db/upgrade.php. Its steps only run for the version they were written for, so a test suite running against
 *   the current version cannot reach any of them, and including the file would only add dead weight.
 *
 * The class is referenced by its legacy name. Moodle 5.2 moved it to \core\test\phpunit\coverage_info and
 * aliased the old name to it, but the namespaced class does not exist on Moodle 5.1, which this branch still
 * supports. The legacy name resolves on every supported version.
 *
 * @package    mod_threesixo
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Coverage information for mod_threesixo.
 *
 * @copyright  2026 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
return new class extends phpunit_coverage_info {
    /** @var array Folders to include, on top of the classes/ folder that Moodle always includes. */
    protected $includelistfolders = [
        'backup',
    ];

    /** @var array Folders to exclude. The generator is test scaffolding, not plugin code. */
    protected $excludelistfolders = [
        'tests/generator',
    ];
};
