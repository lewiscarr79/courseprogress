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
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/excellib.class.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot.'/report/courseprogress/locallib.php');

$courseid = required_param('id', PARAM_INT);
$groupid  = optional_param('group', 0, PARAM_INT);
$format   = required_param('format', PARAM_ALPHA);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/courseprogress:view', $context);

$data = report_courseprogress_export_data($courseid, $groupid, $format);

$filename = clean_filename($course->shortname . '_progress_report');

switch ($format) {
    case 'csv':
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename);
        foreach ($data as $row) {
            $csvexport->add_data($row);
        }
        $csvexport->download_file();
        break;

    case 'excel':
        $workbook = new MoodleExcelWorkbook($filename);
        $worksheet = $workbook->add_worksheet(get_string('pluginname', 'report_courseprogress'));
        $row = 0;
        foreach ($data as $datarow) {
            $col = 0;
            foreach ($datarow as $datacell) {
                $worksheet->write($row, $col, $datacell);
                $col++;
            }
            $row++;
        }
        $workbook->close();
        exit;

    case 'pdf':
        // Extend TCPDF with custom footer
        class MYPDF extends TCPDF {
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Exported on: ' . userdate(time()), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Moodle ' . $CFG->release);
        $pdf->SetTitle($filename);
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->setHeaderFont(Array('helvetica', '', 8));
        $pdf->setFooterFont(Array('helvetica', '', 8));
        $pdf->AddPage();

        // Add logo to the top left
        $logo = $CFG->dirroot . '/theme/' . $CFG->theme . '/pix/logo.png';
        if (file_exists($logo)) {
            $pdf->Image($logo, 15, 10, 0, 20); // max height 20mm
        }

        // Add report name as heading
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 30, get_string('pluginname', 'report_courseprogress'), 0, 1, 'C');
        
        // Add course name
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $course->fullname, 0, 1, 'C');
        
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', '', 10);
        
        // Add padding to cells and make headers bold
        $html = '<table border="1" cellpadding="5"><thead><tr style="font-weight: bold;">';
        foreach ($data[0] as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        for ($i = 1; $i < count($data); $i++) {
            $html .= '<tr>';
            foreach ($data[$i] as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename . '.pdf', 'D');
        exit;
}