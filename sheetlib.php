<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
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
defined('MOODLE_INTERNAL') || die();

function offlinequiz_sheetlib_initialize_headers($workbook) {
    // Creating the first worksheet.
    $sheettitle = get_string('reportoverview', 'offlinequiz');
    $myxls = $workbook->add_worksheet($sheettitle);
    $formats = [];
    // Format types.
    $formats['format'] = $workbook->add_format();
    $formats['format']->set_bold(0);
    $formats['formatbc'] = $workbook->add_format();
    $formats['formatbc']->set_bold(1);
    $formats['formatbc']->set_align('center');
    $formats['formatb'] = $workbook->add_format();
    $formats['formatb']->set_bold(1);
    $formats['formaty'] = $workbook->add_format();
    $formats['formaty']->set_bg_color('yellow');
    $formats['formatc'] = $workbook->add_format();
    $formats['formatc']->set_align('center');
    $formats['formatr'] = $workbook->add_format();
    $formats['formatr']->set_bold(1);
    $formats['formatr']->set_color('red');
    $formats['formatr']->set_align('center');
    $formats['formatg'] = $workbook->add_format();
    $formats['formatg']->set_bold(1);
    $formats['formatg']->set_color('green');
    $formats['formatg']->set_align('center');
    return [$myxls, $formats];
}