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

class report_courseprogress_renderer extends plugin_renderer_base {
    public function render_progress_report($user_data, $courseid, $sort = '', $dir = 'ASC') {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $table->id = 'courseprogress';

        $baseurl = new moodle_url('/report/courseprogress/index.php', array('id' => $courseid));
        $table->head = array(
            $this->helper_sortable_heading(get_string('fullname'), 'lastname', $sort, $dir, $baseurl),
            $this->helper_sortable_heading(get_string('progress', 'report_courseprogress'), 'progress', $sort, $dir, $baseurl),
            $this->helper_sortable_heading(get_string('enrolmentstartdate', 'report_courseprogress'), 'startdate', $sort, $dir, $baseurl),
            $this->helper_sortable_heading(get_string('enrolmentenddate', 'report_courseprogress'), 'enddate', $sort, $dir, $baseurl)
        );

        foreach ($user_data as $data) {
            $user = $data['user'];
            $progress = $data['progress'];
            $startdate = $data['startdate'] ? userdate($data['startdate']) : '-';
            $enddate = $data['enddate'] ? userdate($data['enddate']) : get_string('noenddate', 'report_courseprogress');

            $progress_bar = $this->render_progress_bar($progress);

            $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $courseid));
            $username = html_writer::link($userurl, fullname($user));

            $table->data[] = array($username, $progress_bar, $startdate, $enddate);
        }

        return html_writer::table($table);
    }

    private function render_progress_bar($progress) {
        $class = 'progress-bar ';
        if ($progress < 20) {
            $class .= 'bg-danger';
        } elseif ($progress < 60) {
            $class .= 'bg-warning';
        } elseif ($progress < 100) {
            $class .= 'bg-success';
        } else {
            $class .= 'bg-info';
        }

        $bar = html_writer::start_tag('div', array('class' => 'progress'));
        $bar .= html_writer::tag('div', $progress . '%', array(
            'class' => $class,
            'role' => 'progressbar',
            'aria-valuenow' => $progress,
            'aria-valuemin' => 0,
            'aria-valuemax' => 100,
            'style' => 'width: ' . $progress . '%;'
        ));
        $bar .= html_writer::end_tag('div');
        return $bar;
    }

    private function helper_sortable_heading($text, $column, $sort, $dir, $baseurl) {
        return $this->output->action_link(
            new moodle_url($baseurl, array('sort' => $column, 'dir' => $dir === 'ASC' ? 'DESC' : 'ASC')),
            $text,
            null,
            array('class' => $sort === $column ? 'sorted ' . strtolower($dir) : '')
        );
    }
}