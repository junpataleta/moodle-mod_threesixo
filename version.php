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
 * 360-degree feedback version information
 *
 * @package mod_threesixo
 * @copyright 2017  Jun Pataleta
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025050700;        // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2022112800.00;     // Requires Moodle 4.1+.
$plugin->component = 'mod_threesixo';   // Full name of the plugin (used for diagnostics).
$plugin->cron      = 0;
$plugin->release = 'v4.1.0';
$plugin->maturity = MATURITY_STABLE;
