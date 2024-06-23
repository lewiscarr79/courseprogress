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

function report_courseprogress_export_data($courseid, $groupid, $format) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/enrollib.php');

    $context = context_course::instance($courseid);
    $enrolled_users = get_enrolled_users($context, 'moodle/course:isincompletionreports', $groupid);

    $data = array();
    $data[] = array(
        get_string('fullname'),
        get_string('progress', 'report_courseprogress'),
        get_string('enrolmentstartdate', 'report_courseprogress'),
        get_string('enrolmentenddate', 'report_courseprogress')
    );

    foreach ($enrolled_users as $user) {
        $progress = \report_courseprogress\progress_calculator::get_course_progress($courseid, $user->id);
        $enrolment_dates = \report_courseprogress\progress_calculator::get_enrolment_dates($courseid, $user->id);
        
        $startdate = $enrolment_dates->startdate ? userdate($enrolment_dates->startdate, get_string('strftimedatefullshort', 'langconfig')) : '-';
        $enddate = $enrolment_dates->enddate ? userdate($enrolment_dates->enddate, get_string('strftimedatefullshort', 'langconfig')) : '-';

        $data[] = array(
            fullname($user),
            $progress . '%',
            $startdate,
            $enddate
        );
    }

    return $data;
}