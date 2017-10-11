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
 * Module/Courses creation local plugin.
 *
 * @package    local_modulecreate
 * @copyright  2017 RMOelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * NOTE: This plugin does not actually create the modules, it creates a table
 * which is then used by the core external_database enrol plugin to create new
 * courses.
 **/

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2018100611;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2017050500;        // Requires this Moodle version.
$plugin->component = 'local_modulecreate';  // Full name of the plugin (used for diagnostics).
