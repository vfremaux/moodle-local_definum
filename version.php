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
 * Definum Services version file
 *
 * This is a plugin for providing missing core web services that should be there.
 * 
 * @package     local_definum
 */

$plugin->version    = 2025043003;
$plugin->requires   = 2012120300;
$plugin->component  = 'local_definum';
$plugin->maturity   = MATURITY_BETA;
$plugin->release    = '4.5.0 (Build 2025043003)';
$plugin->supported = [401, 405];

// Non moodle attributes.
$plugin->codeincrement = '4.5.0001';
$plugin->privacy = 'public';
