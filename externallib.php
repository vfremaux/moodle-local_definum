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
 * Extended Core Web Services external web services
 *
 * @package     local_definum
 * @author      2025 Valery fremaux <valery.fremaux@gmail.com>
 * @copyright   2026 Valery fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/lib/badgeslib.php');

/**
 * External Webservices for definum project API class
 * @copyright   2020 onwards Valery fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_definum_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_stats_parameters() {
        return new external_function_parameters([
            'useridfield' => new external_value(PARAM_ALPHA, 'Field used for user identification'),
            'userid' => new external_value(PARAM_TEXT, 'id of user'),
        ]);
    }

    /**
     * Update groups
     *
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function get_stats($useridfield, $userid) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::get_stats_parameters(), ['useridfield' => $useridfield, 'userid' => $userid]);

        if (!in_array($useridfield, ['id', 'username', 'idnumber', 'email', 'oidcuniqueid', 'oidcusername'])) {
            throw new invalid_parameter_exception("Non supported user field");
        }

        if (! preg_match('/^oidc/', $useridfield)) {
            // standard moodle field
            $user = $DB->get_record('user', [$useridfield => $userid], '*', MUST_EXIST);
        } else {
            $oidcid = $DB->get_record('auth_oidc_token', [$useridfeld => $userid], '*', IGNORE_MULTIPLE);
            $user = $DB->get_record('user', ['username' => $oidcid->username], '*', MUST_EXIST);
        }

        // Get firstday of current month.
        $refdate = new DateTime('now');
        list($year, $month) = explode('-', $refdate->format('Y-m'));
        $refdate->setDate($year, $month, 1);
        $refdate->setTime(0, 0, 0, 1);

        $format = get_string('strftimedaydatetime', 'langconfig');

        $stats = new StdClass;
        $stats->id = $user->id;
        $stats->lastname = $user->lastname;
        $stats->firstname = $user->firstname;
        $stats->lastlogin = $user->lastlogin;
        $stats->lastloginstr = core_date::strftime($format, (int) $user->lastlogin);
        $select = ' userid = :userid AND action = "loggedin" AND timecreated > :fromdate ';
        $stats->loginsinmonth = $DB->count_records_select('logstore_standard_log', $select, ['userid' => $user->id, 'fromdate' => $refdate->getTimestamp()]);

        // Courses stats
        $courses = enrol_get_all_users_courses($user->id, true, 'id, shortname,enablecompletion');
        $stats->courses = count($courses);
        $stats->finishablecourses = 0;
        foreach ($courses as $c) {
            if (!$c->enablecompletion) {
                continue;
            }
            $stats->finishablecourses++;
        }
        $select = ' userid = :userid AND timecompleted > 0 ';
        $stats->finishedcourses = $DB->count_records_select('course_completions', $select, ['userid' => $user->id]);
        if ($stats->finishablecourses) {
            $stats->finishedcoursesratio = round($stats->finishedcourses / $stats->finishablecourses);
        } else {
            $stats->finishedcoursesratio = 0;
        }

        // Badges.
        $badges = badges_get_user_badges($user->id);
        if (!empty($badges)) {
            foreach ($badges as $b) {
                $badgeexport = new StdClass;
                $badgeexport->id = $b->id;
                $badgeexport->name = format_string($b->name);
                $badgeexport->issuername = $b->issuername;
                $badgeexport->issuerurl = $b->issuerurl;
                $badgeexport->dateissued = $b->dateissued;
                $badgeexport->dateissuedstr = core_date::strftime($format, (int) $b->dateissued);
            }
        } else {
            $stats->badges = [];
        }
        $stats->numbadges = count($badges);

        // If we have use_stats installed.
        if (is_dir($CFG->dirroot.'/blocks/use_stats')) {
            include_once($CFG->dirroot.'/blocks/use_stats/locallib.php');

            $now = time();
            $input = new Stdclass;

            $input->from = $refdate->getTimestamp();
            $input->to = time(); // now.
            $logs = use_stats_extract_logs($input->from, $input->to, $user->id, 0);
            if (!empty($logs)) {
                $aggregate = use_stats_aggregate_logs($logs, $input->from, $input->to, '', false, null);
                $elapsed = 0;
                if (!array_key_exists('coursetotal', $aggregate)) {
                    foreach ($aggregate['coursetotal'] as $c) {
                        $elapsed += $c->elapsed;
                    }
                }
                $stats->timespentinmonth = $elapsed;
                $stats->timespentinmonthstr = block_use_stats_format_time($elapsed);
            } else {
                $stats->timespentinmonth = 0;
                $stats->timespentinmonthstr = block_use_stats_format_time(0);
            }
        } else {
            $stats->timespentinmonth = -1;
            $stats->timespentinmonthstr = 'Not available';
        }

        return $stats;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_stats_returns() {
        return new external_single_structure([
                'id' => new external_value(PARAM_INT, 'userid'),
                'lastname' => new external_value(PARAM_TEXT, 'User last name'),
                'firstname' => new external_value(PARAM_TEXT, 'User first name'),
                'courses' => new external_value(PARAM_INT, 'Number of enrolled courses'),
                'finishablecourses' => new external_value(PARAM_INT, 'Courses enrolled having completion enabled'),
                'finishedcourses' => new external_value(PARAM_INT, 'Finished courses'),
                'finishedcoursesratio' => new external_value(PARAM_INT, 'Finished courses in percent'),
                'badges' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Badge id'),
                        'name' => new external_value(PARAM_INT, 'Badge name'),
                        'issuername' => new external_value(PARAM_INT, 'Badge issuer name'),
                        'issuerurl' => new external_value(PARAM_INT, 'Badge issuer url'),
                        'issuercontact' => new external_value(PARAM_INT, 'Badge issuer contact'),
                        'dateissued' => new external_value(PARAM_INT, 'Badge issue date'),
                        'dateissuedstr' => new external_value(PARAM_INT, 'Badge issue date (readable format)'),
                    ])
                ),
                'numbadges' => new external_value(PARAM_INT, 'Number of acquired badges'),
                'lastlogin' => new external_value(PARAM_INT, 'last login time'),
                'lastloginstr' => new external_value(PARAM_TEXT, 'last login time (readable text formatted)'),
                'timespentinmonth' => new external_value(PARAM_INT, 'time spent in seconds from the month start (1st of month)'),
                'timespentinmonthstr' => new external_value(PARAM_TEXT, 'Time spent (readable text formatted)'),
                'loginsinmonth' => new external_value(PARAM_INT, 'Number of connections from the start of month')
            ]
        );
    }
}
