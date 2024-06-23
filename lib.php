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
 * Version information. When a new version is released the version is incremented
 *
 * @package    report_courseprogress
 * @copyright  2024 Lewis Carr adaptiVLE Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Defines the capabilities used by the Course Progress report
 *
 * @return array An array of capabilities
 */
function report_courseprogress_get_extra_capabilities() {
    return array('moodle/course:viewparticipants');
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_courseprogress_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/courseprogress:view', $context)) {
        $url = new moodle_url('/report/courseprogress/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_courseprogress'), $url, 
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Is the report enabled for this course?
 *
 * @param int $courseid the course id
 * @return bool true if the report is enabled, false otherwise
 */
function report_courseprogress_can_access_course_report($courseid) {
    global $DB;

    $context = context_course::instance($courseid);
    return has_capability('report/courseprogress:view', $context);
}

/**
 * Callback to verify if the given instance of store is supported by this report or not.
 *
 * @param string $instance store instance.
 * @return bool returns true if the store is supported by the report, false otherwise.
 */
function report_courseprogress_supports_logstore($instance) {
    if ($instance instanceof \core\log\sql_internal_table_reader || $instance instanceof \logstore_legacy\log\store) {
        return true;
    }
    return false;
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function report_courseprogress_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (empty($course)) {
        // We are not in a course - no need to add a link to the course report.
        return true;
    }
    $context = context_course::instance($course->id);
    if (has_capability('report/courseprogress:view', $context)) {
        $url = new moodle_url('/report/courseprogress/index.php', array('id' => $course->id));
        $node = new core_user\output\myprofile\node('reports', 'courseprogress',
            get_string('pluginname', 'report_courseprogress'), null, $url);
        $tree->add_node($node);
    }
    return true;
}