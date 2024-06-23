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
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/grouplib.php');
require_once($CFG->dirroot.'/report/courseprogress/locallib.php');

$courseid = required_param('id', PARAM_INT);
$groupid  = optional_param('group', 0, PARAM_INT);
$sort     = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir      = optional_param('dir', 'ASC', PARAM_ALPHA);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 50, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/courseprogress:view', $context);

$PAGE->set_url('/report/courseprogress/index.php', array('id' => $courseid));
$PAGE->set_pagelayout('report');
$PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_courseprogress'));
$PAGE->set_heading($course->fullname);

$groupmode = groups_get_course_groupmode($course);
$currentgroup = groups_get_course_group($course, true);

$output = $PAGE->get_renderer('report_courseprogress');

echo $output->header();

if ($groupmode) {
    groups_print_course_menu($course, $PAGE->url);
}

// Get users enrolled in the course, considering the current group
$enrolled_users = get_enrolled_users($context, 'moodle/course:isincompletionreports', $currentgroup, 'u.*', "u.lastname, u.firstname");

// Calculate progress and get enrolment dates for each user
$user_data = array();
foreach ($enrolled_users as $user) {
    $progress = \report_courseprogress\progress_calculator::get_course_progress($courseid, $user->id);
    $enrolment_dates = \report_courseprogress\progress_calculator::get_enrolment_dates($courseid, $user->id);
    $user_data[] = array(
        'user' => $user,
        'progress' => $progress,
        'startdate' => $enrolment_dates->startdate,
        'enddate' => $enrolment_dates->enddate
    );
}

// Custom sorting
usort($user_data, function($a, $b) use ($sort, $dir) {
    if ($sort === 'lastname') {
        $aval = $a['user']->lastname;
        $bval = $b['user']->lastname;
    } elseif ($sort === 'progress') {
        $aval = $a['progress'];
        $bval = $b['progress'];
    } elseif ($sort === 'startdate') {
        $aval = $a['startdate'];
        $bval = $b['startdate'];
    } elseif ($sort === 'enddate') {
        $aval = $a['enddate'];
        $bval = $b['enddate'];
    } else {
        // Default to sorting by lastname if an unknown sort key is provided
        $aval = $a['user']->lastname;
        $bval = $b['user']->lastname;
    }

    if ($dir === 'ASC') {
        return $aval <=> $bval;
    } else {
        return $bval <=> $aval;
    }
});

// Pagination
$total = count($user_data);
$user_data = array_slice($user_data, $page * $perpage, $perpage);

// Render the progress report
echo $output->render_progress_report($user_data, $courseid, $sort, $dir);

// Feature 4: Add export buttons
$exporturl = new moodle_url('/report/courseprogress/export.php', array('id' => $courseid, 'group' => $groupid));
echo html_writer::start_tag('div', array('class' => 'export-actions'));
echo $output->single_button(new moodle_url($exporturl, array('format' => 'csv')), get_string('exportcsv', 'report_courseprogress'));
echo $output->single_button(new moodle_url($exporturl, array('format' => 'excel')), get_string('exportexcel', 'report_courseprogress'));
echo $output->single_button(new moodle_url($exporturl, array('format' => 'pdf')), get_string('exportpdf', 'report_courseprogress'));
echo html_writer::end_tag('div');

echo $output->footer();