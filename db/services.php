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
 * Ext Web Services template external functions and service definitions
 *
 * @package     local_definum
 * @copyright   2020 onwards valery.fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = [

   'definum_get_stats' => [
        'classname'     => 'local_definum_external',
        'methodname'    => 'get_stats',
        'classpath'     => 'local/definum/externallib.php',
        'description'   => 'Get user stats.',
        'type'          => 'read'
    ],
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'extcpre' => [
        'functions' => [
            'definum_get_stats',
        ],
        'restrictedusers'   => 0,
        'enabled'           => 1,
    ]
];