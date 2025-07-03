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

namespace local_definum;

use stdClass;
use externallib_advanced_testcase;
use local_definum_external;
use core_badges_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/definum/externallib.php');
require_once($CFG->dirroot . '/badges/tests/generator/lib.php');

/**
 * External choice functions unit tests
 *
 * @package    local_definum
 */
final class externallib_test extends externallib_advanced_testcase {

    /**
     * Test get_stats_results
     */
    public function test_identify_user() {
        global $DB;

        $this->resetAfterTest(true);

        $userrec = [
            'username' => 'testuser',
            'idnumber' => 'TESTID',
            'email' => 'testuser@foo.com',
        ];
        $student1 = $this->getDataGenerator()->create_user($userrec);

        // Get results
        $results = local_definum_external::get_stats('id', $student1->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

        // Get results
        $results = local_definum_external::get_stats('idnumber', $userrec['idnumber']);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

        // Get results
        $results = local_definum_external::get_stats('email', $userrec['email']);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

        // Get results
        $results = local_definum_external::get_stats('username', $userrec['username']);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

        $oidctoken = new StdClass;
        $oidctoken->oidcuniqid = '00000000';
        $oidctoken->username = $student1->username;
        $oidctoken->userid = $student1->id;
        $oidctoken->oidcusername = 'usertest_00000000';
        $oidctoken->token = md5($oidctoken->oidcusername);
        $oidctoken->scope = 'openid email';
        $oidctoken->tokenresource = 'moodle';
        $oidctoken->expiry = 0;
        $DB->insert_record('auth_oidc_token', $oidctoken);

        // Get results
        $results = local_definum_external::get_stats('oidcusername', 'usertest_00000000');
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

        // Get results
        $results = local_definum_external::get_stats('oidcuniqid', '00000000');
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['lastname'], $student1->lastname);

    }

    /**
     * Test get_stats_results
     */
    public function test_get_stats() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = self::getDataGenerator()->create_course(); // Non finishable
        $course = new StdClass;
        $course->enablecompletion = 1;
        $course2 = self::getDataGenerator()->create_course($course); // Finishable
        $course3 = self::getDataGenerator()->create_course($course); // Finished

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Get results
        $results = local_definum_external::get_stats('id', $student1->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 0);
        $this->assertEquals($results['finishablecourses'], 0);
        $this->assertEquals($results['finishedcourses'], 0);
        $this->assertEquals($results['numbadges'], 0);

        // Enroll Students in Courses.
        self::getDataGenerator()->enrol_user($student1->id,  $course1->id, $studentrole->id);
        self::getDataGenerator()->enrol_user($student2->id,  $course1->id, $studentrole->id);
        self::getDataGenerator()->enrol_user($student2->id,  $course2->id, $studentrole->id);
        self::getDataGenerator()->enrol_user($student2->id,  $course3->id, $studentrole->id);

        // Get results
        $results = local_definum_external::get_stats('id', $student2->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['courses'], 3);
        $this->assertEquals($results['finishablecourses'], 2);
        $this->assertEquals($results['finishedcourses'], 0);

        if ($comprec = $DB->get_record('course_completions', ['userid' => $student2->id, 'course' => $course3->id])) {
            $comprec->timeenrolled = time() - (int) DAYSECS - 100;
            $comprec->timestarted = time() - (int) DAYSECS;
            $comprec->timecompleted = time();
            $DB->update_record('course_completions', $comprec);
        } else {
            $comprec = new StdClass();
            $comprec->userid = $student2->id;
            $comprec->course = $course3->id;
            $comprec->timeenrolled = time() - (int) DAYSECS - 100;
            $comprec->timestarted = time() - (int) DAYSECS;
            $comprec->timecompleted = time();
            $DB->insert_record('course_completions', $comprec);
        }

        // Get results
        $results = local_definum_external::get_stats('id', $student2->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['finishedcourses'], 1);


        // Create and test badges
        $generator = new core_badges_generator();
        $badge = $generator->create_badge();
        $record = new StdClass;
        $record->badgeid = $badge->id;
        $record->userid = $student1->id;
        $issued = $generator->create_issued_badge();

        // Get results
        $results = local_definum_external::get_stats('id', $student1->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $results = \external_api::clean_returnvalue(local_definum_external::get_stats_returns(), $results);

        $this->assertEquals($results['badgenum'], 1);

    }

}
