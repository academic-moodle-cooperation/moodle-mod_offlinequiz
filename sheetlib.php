<?php
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
    $formats['formatbc']>set_align('center');
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
    return ['formats' => $formats, 'xls' => $myxls];
}