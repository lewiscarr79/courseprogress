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
namespace report_courseprogress;

defined('MOODLE_INTERNAL') || die();

class progress_calculator {
    public static function get_course_progress($courseid, $userid) {
        global $DB;

        $total_activities = $DB->count_records_sql(
            "SELECT COUNT(cm.id)
             FROM {course_modules} cm
             JOIN {modules} m ON m.id = cm.module
             WHERE cm.course = :courseid AND cm.completion > 0",
            ['courseid' => $courseid]
        );

        $completed_activities = $DB->count_records_sql(
            "SELECT COUNT(cmc.id)
             FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
             WHERE cm.course = :courseid AND cmc.userid = :userid AND cmc.completionstate > 0",
            ['courseid' => $courseid, 'userid' => $userid]
        );

        if ($total_activities > 0) {
            return round(($completed_activities / $total_activities) * 100, 2);
        }

        return 0;
    }

    public static function get_enrolment_dates($courseid, $userid) {
        global $DB;

        $sql = "SELECT ue.timestart, ue.timeend, e.enrol, ue.timecreated
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid AND ue.userid = :userid
                ORDER BY ue.timecreated DESC";
        
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

        if ($records) {
            $record = reset($records);
            $startdate = $record->timestart;
            $enddate = $record->timeend;

            // If timestart is 0, use timecreated as the start date
            if ($startdate == 0) {
                $startdate = $record->timecreated;
            }

            // If timeend is 0, it means no end date is set
            if ($enddate == 0) {
                $enddate = 0;  // Keep it as 0 to indicate "no end date"
            }

            return (object)[
                'startdate' => $startdate,
                'enddate' => $enddate
            ];
        }

        return (object)['startdate' => 0, 'enddate' => 0];
    }
}