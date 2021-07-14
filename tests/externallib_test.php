<?php

use mod_offlinequiz\local\tests\base;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/offlinequiz/externallib.php');

/**
 * External mod offlinequiz functions unit tests
 */
class mod_offlinequiz_external_testcase extends externallib_advanced_testcase {

    /**
     * Test if the user only gets offlinequiz for enrolled courses
     */
    public function test_get_offlinequizs_by_courses() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse1',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse2',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        $course3 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse3',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $offlinequiz1 = self::getDataGenerator()->create_module('offlinequiz', [
            'course' => $course1->id,
            'name' => 'Offlinequiz Module 1',
            'intro' => 'Offlinequiz module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $offlinequiz2 = self::getDataGenerator()->create_module('offlinequiz', [
            'course' => $course2->id,
            'name' => 'Offlinequiz Module 2',
            'intro' => 'Offlinequiz module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $offlinequiz3 = self::getDataGenerator()->create_module('offlinequiz', [
            'course' => $course3->id,
            'name' => 'Offlinequiz Module 3',
            'intro' => 'Offlinequiz module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $this->setUser($user);

        $result = mod_offlinequiz_external::get_offlinequizzes_by_courses([]);

        // user is enrolled only in course1 and course2, so the third offlinequiz module in course3 should not be included
        $this->assertEquals(2, count($result->offlinequizzes));
    }


    /**
     * Test if the user gets a valid offlinequiz from the endpoint
     */
    public function test_get_offlinequiz() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $offlinequiz = self::getDataGenerator()->create_module('offlinequiz', [
            'course' => $course->id,
            'name' => 'Offlinequiz Module',
            'intro' => 'Offlinequiz module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $this->setUser($user);

        $result = mod_offlinequiz_external::get_offlinequiz($offlinequiz->id);

        // offlinequiz name should be equal to 'Offlinequiz Module'
        $this->assertEquals('Offlinequiz Module', $result->offlinequiz->name);

        // Course id in offlinequiz should be equal to the id of the course
        $this->assertEquals($course->id, $result->offlinequiz->course);
    }


    /**
     * Test if the user gets an exception when the offlinequiz is hidden in the course
     */
    public function test_get_offlinequiz_hidden() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $offlinequiz = self::getDataGenerator()->create_module('offlinequiz', [
            'course' => $course->id,
            'name' => 'Hidden Offlinequiz Module',
            'intro' => 'Offlinequiz module for automated php unit tests',
            'introformat' => FORMAT_HTML,
            'visible' => 0,
        ]);

        $this->setUser($user);

        // Test should throw require_login_exception
        $this->expectException(require_login_exception::class);

        $result = mod_offlinequiz_external::get_offlinequiz($offlinequiz->id);

    }

}