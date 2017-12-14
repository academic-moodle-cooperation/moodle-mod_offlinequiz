<?PHP
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

/**
 * The English language strings for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

$string['modulename'] = 'Offline Quiz';
$string['modulenameplural'] = 'Offline Quizzes';
$string['pluginname'] = 'Offline Quiz';

$string['addnewquestion'] = 'a new question';
$string['add'] = 'Add';
$string['addlist'] = 'Add list';
$string['addarandomselectedquestion'] = 'Add a random selected question ...';
$string['addnewpagesafterselected'] = 'Add page breaks after selected questions';
$string['addnewquestionsqbank'] = 'Add questions to the category {$a->catname}: {$a->link}';
$string['addnewuseroverride'] = 'Add user override';
$string['addpagebreak'] = 'Add page break';
$string['addpagehere'] = 'Add page here';
$string['addparts'] = 'Add participants';
$string['addquestionfrombankatend'] = 'Add from the question bank at the end';
$string['addquestionfrombanktopage'] = 'Add from the question bank to page {$a}';
$string['addrandom'] = 'Randomly add {$a} multiple choice question(s) ';
$string['addrandomfromcategory'] = 'questions randomly';
$string['addrandomquestion'] = 'questions randomly';
$string['addrandomquestiontoofflinequiz'] = 'Adding questions to offlinequiz {$a->name} (group {$a->group})';
$string['addrandomquestiontopage'] = 'Add a random question to page {$a}';
$string['addarandomquestion'] = 'questions randomly';
$string['addarandomquestion_help'] = 'Moodle adds a random selection of multiple choice questions (or all-or-nothing multiple-choice questions) to the current offline quiz group. The number of questions added can be set. The questions are chosen from the current question category (and, if selected, its sub-categories).';
$string['addtoofflinequiz'] = 'Add to offline quiz';
$string['addtoqueue'] = 'Add to queue';
$string['allinone'] = 'Unlimited';
$string['alllists'] = 'All lists';
$string['allornothing'] = 'All-or-nothing';
$string['allresults'] = 'Show all results';
$string['allstudents'] = 'Show all students';
$string['alwaysavailable'] = 'Always available';
$string['analysis'] = 'Item analysis';
$string['answerformforgroup'] = 'Answer form for group {$a}';
$string['answerform'] = 'Form for answers';
$string['answerforms'] = 'Answers forms';
$string['answerpdfxy'] = 'Form for answers ({$a}->maxquestions questions / {$a}->maxanswers options)';
$string['areyousureremoveselected'] = 'Are you sure you want to remove all selected questions?';
$string['attemptexists'] = 'Attempt exists';
$string['attemptsexist'] = 'You can no longer add or remove questions.';
$string['attemptsnum'] = 'Results: {$a}';
$string['attemptsonly'] = 'Show students with results only';
$string['attendances'] = 'Attendances';
$string['basicideasofofflinequiz'] = 'The basic ideas of offline quiz-making';
$string['bulksavegrades'] = 'Save Grades';
$string['calibratescanner'] = 'Calibrate scanner';
$string['cannoteditafterattempts'] = 'You cannot add or remove questions because there are already complete results. ({$a})';
$string['category'] = 'Category';
$string['changed'] = 'Result has been changed.';
$string['checkparts'] = 'Mark selected participants as present';
$string['checkuserid'] = 'Check group/user ID';
$string['chooseagroup'] = 'Choose a group...';
$string['closebeforeopen'] = 'Could not update the offline quiz. You have specified a close date before the open date.';
$string['closestudentview'] = 'Close Student View';
$string['closewindow'] = 'Close Window';
$string['cmmissing'] = 'The course module for the offline quiz with ID {$a} is missing';
$string['configintro'] = 'The values you set here are used as default values for the settings of new offline quizzes.';
$string['configkeepfilesfordays'] = 'Determine how many days the uploaded image files are kept in temporary storage. During this time the image files are available in the offline quiz admin report.';
$string['configonlylocalcategories'] = 'No shared question categories allowed.';
$string['configshuffleanswers'] = 'Shuffle answers';
$string['configshufflequestions'] = 'If you enable this option, then the order of questions in the offline quiz groups will be randomly shuffled each time you re-create the preview in the "Create forms" tab.';;
$string['configshufflewithin'] = 'If you enable this option, the answers of multiple choice questions are shuffled separately for each offline quiz group.';
$string['configuseridentification'] = 'A formula describing the user identification. This formula is used to assign answer forms to users in the system. The right hand side of the equation must denote a field in the user table of Moodle.';
$string['configpapergray'] = 'white-value of paper, which is used for the evaluation of answer-sheets';
$string['configshufflewithin'] = 'If you enable this option, then the parts making up the individual questions will be randomly shuffled when the question- and answer-forms are created.';
$string['confirmremovequestion'] = 'Are you sure you want to remove this {$a} question?';
$string['copyright'] = '<strong>Warning: The texts on this page are just for your personal information. Like any other texts these questions are under copyright restrictions. You are not allowed to copy them or to show them to other people!</strong>';
$string['copy'] = 'Copy';
$string['correct'] = 'correct';
$string['correcterror'] = 'solve';
$string['correctforgroup'] = 'Correct answers for Group {$a}';
$string['correctionform'] = 'Correction';
$string['correctionforms'] = 'Correction forms';
$string['correctionoptionsheading'] = 'Correction options';
$string['correctupdated'] = 'Updated correction form for group {$a}.';
$string['couldnotgrab'] = 'Could not grab image {$a}';
$string['couldnotregister'] = 'Could not register user {$a}';
$string['createcategoryandaddrandomquestion'] = 'Create category and add random question';
$string['createofflinequiz'] = 'Create forms';
$string['createlistfirst'] = 'Add participants';
$string['createpartpdferror'] = 'The PDF form for the list of participants {$a} could not be created. The list may be empty.';
$string['createpdferror'] = 'The form for group {$a} could not be created. Maybe there are no questions in the group.';
$string['createpdffirst'] = 'Create PDF list first';
$string['createpdfforms'] = 'Create forms';
$string['createpdf'] = 'PDF form';
$string['createpdfs'] = 'PDF forms';
$string['createpdfsparticipants'] = 'PDF forms for list of participants';
$string['createquestionandadd'] = 'Create a new question and add it to the quiz.';
$string['createquiz'] = 'Create forms';
$string['csvfile'] = 'CSV file';
$string['csvformat'] = 'Comma separated values text file (CSV)';
$string['csvplus1format'] = 'Text file with raw data (CSV)';
$string['csvpluspointsformat'] = 'Text file with points (CSV)';
$string['darkgray'] = 'Dark grey';
$string['datanotsaved'] = 'Could not save settings';
$string['configdecimalplaces'] = 'Number of digits that should be shown after the decimal point when displaying grades for the offline quiz.';
$string['decimalplaces'] = 'Decimal places';
$string['decimalplaces_help'] = 'Number of digits that should be shown after the decimal point when displaying grades for the offline quiz.';
$string['deletelistcheck'] = 'Do you really want to delete the selected list and all it\'s participants?';
$string['deleteresultcheck'] = 'Do you really want to delete the selected results?';
$string['deletepagesafterselected'] = 'Remove page breaks after selected questions';
$string['deletepartcheck'] = 'Do you really want to delete the selected participants?';
$string['deletepagecheck'] = 'Do you really want to delete the selected pages?';
$string['deleteparticipantslist'] = 'Delete participants list';
$string['deletepdfs'] = 'Delete documents';
$string['deleteselectedresults'] = 'Delete selected results';
$string['deleteselectedpart'] = 'Delete selected participants';
$string['deletethislist'] = 'Delete this list';
$string['deleteupdatepdf'] = 'Delete and update PDF-forms';
$string['difficultytitlea'] = 'Difficulty A';
$string['difficultytitleb'] = 'Difficulty B';
$string['difficultytitlediff'] = 'Difference';
$string['difficultytitle'] = 'Difficulty';
$string['displayoptions'] = 'Display options';
$string['done'] = 'done';
$string['downloadallzip'] = 'Download all files as ZIP';
$string['downloadpartpdf'] = 'Download PDF file for list \'{$a}\'';
$string['downloadpdfs'] = 'Download documents';
$string['downloadresultsas'] = 'Download results as: ';
$string['dragtoafter'] = 'After {$a}';
$string['dragtostart'] = 'To the start';
$string['editlist'] = 'Edit list';
$string['editthislist'] = 'Edit this list';
$string['editlists'] = 'Edit lists';
$string['editgroups'] = 'Edit Offline Groups';
$string['editgroupquestions'] = 'Edit group questions';
$string['editingofflinequiz'] = 'Editing group questions';
$string['editingofflinequiz_help'] = 'When creating an offline quiz, the main concepts are:
<ul><li> The offline quiz, containing questions over one or more pages</li>
<li> The question bank, which stores copies of all questions organised into categories</li></ul>';
$string['editofflinequiz'] = 'Edit offline quiz';
$string['editingofflinequizx'] = 'Edit offline quiz: {$a}';
$string['editmaxmark'] = 'Edit maximum mark';
$string['editorder'] = 'Edit order';
$string['editparticipants'] = 'Edit participants';
$string['editquestion'] = 'Edit question';
$string['editquestions'] = 'Edit questions';
$string['editscannedform'] = 'Edit scanned form';
$string['emptygroups'] = 'Some offline quiz groups are empty. Please add some questions.';
$string['enroluser'] = 'Enrol user';
$string['erroraccessingreport'] = 'You are not allowed to view this report.';
$string['errorreport'] = 'Report of import errors';
$string['eventattemptdeleted'] = 'Offline quiz attempt deleted';
$string['eventattemptpreviewstarted'] = 'Offline quiz attempt preview started';
$string['eventattemptreviewed'] = 'Offline quiz attempt reviewed';
$string['eventattemptsummaryviewed'] = 'Offline quiz attempt summary viewed';
$string['eventattemptviewed'] = 'Offline quiz attempt viewed';
$string['eventdocscreated'] = 'Offline quiz question and answer forms created';
$string['eventdocsdeleted'] = 'Offline quiz question and answer forms deleted';
$string['eventeditpageviewed'] = 'Offline quiz edit page viewed';
$string['eventofflinequizattemptsubmitted'] = 'Offline quiz attempt submitted';
$string['eventoverridecreated'] = 'Offline quiz override created';
$string['eventoverridedeleted'] = 'Offline quiz override deleted';
$string['eventoverrideupdated'] = 'Offline quiz override updated';
$string['eventparticipantmarked'] = 'Offline quiz participant marked manually';
$string['eventquestionmanuallygraded'] = 'Question manually graded';
$string['eventreportviewed'] = 'Offline quiz report viewed';
$string['eventresultsregraded'] = 'Offline quiz results regraded';
$string['everythingon'] = 'enabled';
$string['excelformat'] = 'Excel spreadsheet (XLSX)';
$string['fileprefixanswer'] = 'answer_form';
$string['fileprefixcorrection'] = 'correction_form';
$string['fileprefixform'] = 'question_form';
$string['fileprefixparticipants'] = 'participants_list';
$string['fileformat'] = 'Format for question sheets';
$string['fileformat_help'] = 'Choose whether you want your question sheets in PDF, DOCX or TEX format. Answer forms and correction sheets will always be generated in PDF format.';
$string['filesizetolarge'] = 'Some of your image files are very large. The dimensions will be resized during the interpretation. Please try to scan with a resolution between 200 and 300 dpi and in black and white mode. This will speed up the interpretation next time.';
$string['fontsize'] = 'Fontsize';
$string['forautoanalysis'] = 'For automatic analysis';
$string['formforcorrection'] = 'Correction form for group {$a}';
$string['formforgroup'] = 'Question form for group {$a}';
$string['formforgroupdocx'] = 'Question form for group {$a} (DOCX)';
$string['formforgrouplatex'] = 'Question form for group {$a} (LATEX)';
$string['formsexist'] = 'Forms already created.';
$string['formsexistx'] = 'Forms already created (<a href="{$a}">Download forms</a>)';
$string['formsheetsettings'] = 'Form Settings';
$string['formspreview'] = 'Preview for forms';
$string['formwarning'] = 'There is no answer form defined. Please contact your administrator.';
$string['fromquestionbank'] = 'from question bank';
$string['functiondisabledbysecuremode'] = 'That functionality is currently disabled';
$string['generalfeedback'] = 'General feedback';
$string['generalfeedback_help'] = 'General feedback is text which is shown after a question has been attempted. Unlike feedback for a specific question which depends on the response given, the same general feedback is always shown.';
$string['generatepdfform'] = 'Generate PDF form';
$string['grade'] = 'Grade';
$string['gradedon'] = 'Graded on';
$string['gradedscannedform'] = 'Scanned form with grades';
$string['gradeiszero'] = 'Note: The maximum grade for this offline quiz is 0 points!';
$string['gradeswarning'] = 'The question grades have to be numbers!';
$string['gradewarning'] = 'The question grade has to be a number!';
$string['gradingofflinequiz'] = 'Grades';
$string['gradingofflinequizx'] = 'Grades: {$a}';
$string['gradingoptionsheading'] = 'Grading options';
$string['greeniscross'] = 'counted as a cross';
$string['rediswrong'] = 'wrong cross or missing cross';
$string['group'] = 'Group';
$string['groupoutofrange'] = 'Group was out of range and replaced with group A.';
$string['groupquestions'] = 'Group Questions';
$string['hasresult'] = 'Result exists';
$string['idnumber'] = 'ID number';
$string['imagefile'] = 'Image file';
$string['imagenotfound'] = 'Image file: {$a} not found!';
$string['imagenotjpg'] = 'Image not jpg or png: {$a}';
$string['imagickwarning'] = 'Missing imagemagick: Ask your system administrator to install the imagemagick library and check the path to the convert binary in your TeX notation filter settings. You cannot import TIF files without imagemagick!';
$string['importerror11'] = 'Other result exists';
$string['importerror12'] = 'User not registered';
$string['importerror13'] = 'No group data';
$string['importerror14'] = 'Could not grab';
$string['importerror15'] = 'Insecure markings';
$string['importerror16'] = 'Page error';
$string['importerror17'] = 'Pages incomplete';
$string['importerror21'] = 'Could not grab';
$string['importerror22'] = 'Insecure markings';
$string['importerror23'] = 'User not in list';
$string['importerror24'] = 'List not detected';
$string['importfromto'] = 'Importing {$a->from} to {$a->to} of {$a->total}.';
$string['import'] = 'Import';
$string['importnew'] = 'Import';
$string['importnew_help'] = '<p>
You can import single scanned image files or several scanned image files in a ZIP-archive. The offline quiz module will process the image files in the background.
File names are not relevant but should not contain special characters such as umlauts. Images should be GIFs, PNGs
or TIFs. A resolution between 200 and 300dpi is recommended.</p>';
$string['importedon'] = 'Imported on';
$string['importforms'] = 'Import answer forms';
$string['importisfinished'] = 'Import for offline quiz {$a} is finished.';
$string['importlinkresults'] = 'Link to results: {$a}';
$string['importlinkverify'] = 'Link to verifying: {$a}';
$string['importmailsubject'] = 'offline quiz import notification';
$string['importnumberexisting'] = 'Number of double forms: {$a}';
$string['importnumberpages'] = 'Number of successfully imported pages: {$a}';
$string['importnumberresults'] = 'Number of imported : {$a}';
$string['importnumberverify'] = 'Number of forms that need verifying: {$a}';
$string['importtimefinish'] = 'Process finished: {$a}';
$string['importtimestart'] = 'Process started: {$a}';
$string['inconsistentdata'] = 'Inconsistent data: {$a}';
$string['info'] = 'Info';
$string['infoshort'] = 'i';
$string['insecuremarkingsforquestion'] = 'Insecure markings need manual corrections for question';
$string['insecuremarkings'] = 'Insecure markings need manual corrections';
$string['insertnumber'] = 'Please insert the correct identification number marked by the blue frame.';
$string['instruction1'] = 'This answer form will be scanned automatically. Please do not fold or spot. Use a black or blue pen to mark the fields:';
$string['instruction2'] = 'Only clear markings can be interpreted correctly! If you want to correct a marking, completely fill the box with color. This field will be interpreted like an empty box:';
$string['instruction3'] = 'Corrected boxes cannot be marked again. Please do not write anything outside of the boxes.';
$string['introduction'] = 'Introduction';
$string['invalidformula'] = 'Invalid formula for user identification. The formula must have the form <prefix>[<#digits>]<suffix>=<db-field&>.';
$string['invalidnumberofdigits'] = 'Invalid number of digits used. Only 1 up to 9 digit(s) are allowed.';
$string['invaliduserfield'] = 'Invalid field of the user table used.';
$string['invigilator'] = 'Invigilator';
$string['ischecked'] = 'Participation is checked';
$string['isnotchecked'] = 'Participation is not checked';
$string['itemdata'] = 'Itemdata';
$string['keepfilesfordays'] = 'Keep files for days';
$string['lightgray'] = 'Light grey';
$string['linktoscannedform'] = 'View scanned form';
$string['listnotdetected'] = 'Could not detect barcode for list!';
$string['logdeleted'] = 'Log entry {$a} deleted.';
$string['logourl'] = 'Logo URL';
$string['logourldesc'] = 'URL of an image file that is displayed on the top right corner of answer forms, i.e. <b>http://www.yoursite.tld/mylogo.png</b> or <b>../path/to/your/logo.png</b>. The maximum allowed size is 520x140 pixels. Answer forms cannot be evaluated if the image exceeds the maximum size!';
$string['lowertrigger'] = 'Lower second boundary';
$string['lowertriggerzero'] = 'Lower second boundary is zero';
$string['lowerwarning'] = 'Lower first boundary';
$string['lowerwarningzero'] = 'Lower first boundary is zero';
$string['marginwarning'] = 'Please print the following PDF files without additional margins!<br /> Avoid handing out photocopies to students. If you have any doubts order copies from the support team.';
$string['marks'] = 'Marks';
$string['matrikel'] = 'student number';
$string['maxgradewarning'] = 'The maximum grade has to be a number!';
$string['maxmark'] = 'Maximum mark';
$string['membersinplist'] = '{$a->count} participants in <a href="{$a->url}">{$a->name}</a>';
$string['missingimagefile'] = 'Missing image file';
$string['missingitemdata'] = 'Missing answer(s) for user {$a}';
$string['missinglogdata'] = 'Missing logdata for existing result.';
$string['missingquestion'] = 'This question no longer seems to exist';
$string['missinguserid'] = 'Missing user identification number! Could not read barcode!';
$string['modulename_help'] = 'This module allows the teacher to design offline quizzes consisting of multiple choice questions.
These questions are kept in the Moodle question bank and can be re-used within courses and even between courses.
The offline quizzes can be downloaded as PDF-, DOCX- or LaTeX-files. The students mark their answers on form sheets. The form sheets are scanned and the answers imported into the system.';
$string['moveselectedonpage'] = 'Move selected questions to page: {$a}';
$string['copyselectedtogroup'] = 'Add selected questions to group: {$a}';
$string['copytogroup'] = 'Add all questions to group: {$a}';
$string['multichoice'] = 'Multiple choice';
$string['multipleanswers'] = 'Choose at least one answer.';
$string['moodleprocessing'] = 'Let Moodle process data';
$string['movecorners'] = 'Change the positions of the corner markings first. Use drag and drop.';
$string['multianswersforsingle'] = 'Multiple answers for single choice question';
$string['name'] = 'Offline quiz name';
$string['neededcorrection'] = '<strong>Attention: Some of your markings needed manual correction. Have a look at the red squares in the following picture.
<br />This implied manual intervention by a teacher and delayed the publishing of the offline quiz results!</strong>';
$string['newgrade'] = 'Graded';
$string['newpage'] = 'New Page';
$string['noattemptexists'] = 'No result exists';
$string['noattempts'] = 'No results imported!';
$string['noattemptsonly'] = 'Show students with no results only';
$string['nocourse'] = 'The course with id {$a->course} that the offline quiz with ID {$a->offlinequiz} belongs to is missing.';
$string['nogradesseelater'] = 'This quiz has not been graded yet for {$a}. Results will be published here.';
$string['nogroupdata'] = 'No group data for user {$a}';
$string['noscannedpage'] = 'There is no scanned page with ID {$a}!';
$string['nomcquestions'] = 'There are no multiple choice questions in group {$a}!';
$string['noofflinequiz'] = 'There is no offline quiz with id {$a}!';
$string['nopages'] = 'No pages imported';
$string['noparticipantsfound'] = 'No participants found';
$string['nopdfscreated'] = 'No documents created!';
$string['noquestions'] = 'Some offline quiz groups are empty. Please add some questions.';
$string['noquestionsfound'] = 'There are no questions in group {$a}!';
$string['noquestionsonpage'] = 'Empty page';
$string['noquestionselected'] = 'No questions selected!';
$string['noresults'] = 'There are no results.';
$string['noreview'] = 'You are not allowed to review this offline quiz';
$string['nothingtodo'] = 'Nothing to do!';
$string['notxtfile'] = 'No TXT file';
$string['notyetgraded'] = 'Not yet graded';
$string['nozipfile'] = 'No ZIP file';
$string['numattempts'] = 'Number of results imported: {$a}';
$string['numattemptsqueue'] = '{$a} answer forms added to queue. An email will be sent to your address after data processing.';
$string['numattemptsverify'] = 'Scanned forms waiting for correction: {$a}';
$string['numberformat'] = 'The value has to be a number with {$a} digits!';
$string['numbergroups'] = 'Number of groups';
$string['numpages'] = '{$a} pages imported';
$string['numquestionsx'] = 'Questions: {$a}';
$string['numusersadded'] = '{$a} participants added';
$string['odsformat'] = 'OpenDocument spreadsheet (ODS)';
$string['offlineimplementationfor'] = 'Offline implementation for';
$string['editofflinesettings'] = 'Edit offline settings';
$string['offlinequizcloseson'] = 'The review for this offline quiz will close at {$a}';
$string['offlinequizisclosed'] = 'Offline quiz closed)';
$string['offlinequizisclosedwillopen'] = 'Offline quiz closed (opens {$a})';
$string['offlinequizisopen'] = 'This offline quiz is open';
$string['offlinequizisopenwillclose'] = 'Offline quiz open (closes {$a})';
$string['offlinequizopenedon'] = 'This offline quiz opened at {$a}';
$string['offlinequizsettings'] = 'Offline settings';
$string['offlinequiz:addinstance'] = 'Add an offline quiz';
$string['offlinequiz:attempt'] = 'Attempt quizzes';
$string['offlinequizcloses'] = 'Offline Quiz closes';
$string['offlinequiz:createofflinequiz'] = 'Create offline quiz forms';
$string['offlinequiz:deleteattempts'] = 'Delete offline quiz results';
$string['offlinequiz:grade'] = 'Grade offline quizzes manually';
$string['offlinequiz:manage'] = 'Manage offline quizzes';
$string['offlinequizopens'] = 'Offline-Quiz opens';
$string['offlinequiz:preview'] = 'Preview offline quizzes';
$string['offlinequiz:viewreports'] = 'View offline quiz reports';
$string['offlinequiz:view'] = 'View offline quiz information';
$string['offlinequizwillopen'] = 'Offline-Test opens on {$a}';
$string['oneclickenrol'] = '1-Click Enrolment';
$string['oneclickenroldesc'] = 'If this option is activated teachers have the possiblity to enrol users with one click while correcting answer forms (error "User not in course").';
$string['oneclickrole'] = 'Role for 1-Click Enrolment.';
$string['oneclickroledesc'] = 'Choose the role used for 1-click enrolment. Only roles with archetype "student" can be selected.';
$string['onlylocalcategories'] = 'Only local question categories';
$string['orderingofflinequiz'] = 'Order and paging';
$string['orderandpaging'] = 'Order and paging';
$string['orderandpaging_help'] = 'The numbers 10, 20, 30, ... opposite each question indicate the order of the questions. The numbers increase in steps of 10 to leave space for additional questions to be inserted. To reorder the questions, change the numbers then click the "Reorder questions" button.

To add page breaks after particular questions, tick the checkboxes next to the questions then click the "Add page breaks after selected questions" button.

To arrange the questions over a number of pages, click the Repaginate button and select the desired number of questions per page.';
$string['otherresultexists'] = 'Different result for {$a} already exists, import ignored! Delete result first.';
$string['outof'] = '{$a->grade} out of a maximum of {$a->maxgrade}';
$string['outofshort'] = '{$a->grade}/{$a->maxgrade}';
$string['overallfeedback'] = 'Overall feedback';
$string['overview'] = 'Overview';
$string['overviewdownload_help'] = '';
$string['pagecorrected'] = 'Corrected sheet of participants list imported';
$string['pageevaluationtask'] = 'Answer sheet evaluation for the offlinequiz-plugin';
$string['pageimported'] = 'Sheet of participants list imported';
$string['page-mod-offlinequiz-x'] = 'Any offline quiz page';
$string['page-mod-offlinequiz-edit'] = 'Edit offline quiz page';
$string['pagenumberimported'] = 'Sheet {$a} of participants list imported';
$string['pagenumberupdate'] = 'Page number update';
$string['pagenotdetected'] = 'Could not detect barcode for page!';
$string['pagesizeparts'] = 'Participants shown per page:';
$string['papergray'] = 'White value of paper';
$string['papergray_help'] = 'If the white parts of your scanned answer forms are very dark you can correct this by setting this value to dark grey.';
$string['partcheckedwithresult'] = '{$a} checked participants with result';
$string['partcheckedwithoutresult'] = '<a href="{$a->url}">{$a->count} checked participants without result</a>';
$string['partuncheckedwithresult'] = '<a href="{$a->url}">{$a->count} unchecked participants with result</a>';
$string['partuncheckedwithoutresult'] = '{$a} unchecked participants without result';
$string['participantslist'] = 'List of participants';
$string['participantslists'] = 'Participants';
$string['participants'] = 'Participants';
$string['participantsinlists'] = 'Participants in lists';
$string['participants_help'] = '<p>Lists of participants are designed for large offline quizzes with many participants. They help the teacher to check which students participated in the quiz and whether all the results were imported correctly.
You can add users to different lists. Each list could, for instance, contain the participants in a particular room. The participants can be members of a special group. A group registration tool can be used for creating those groups.
Lists of participants can be downloaded as PDF documents, printed and marked with crosses just like the answer forms of offline quizzes. Afterwards they can be uploaded and the marked students will be marked as present in the database.
Please avoid spots on the barcodes as they are used to identify the students.</p>';
$string['partimportnew'] = 'Uploading lists of participants';
$string['partimportnew_help'] = '<p>
In this tab you can upload the filled-in lists of participants. You can upload single scanned image files or several scanned image files in a ZIP-archive. The offline quiz module will process the image files in the background.
File names are not relevant but should not contain special characters such as umlauts. Images should be GIFs, PNGs
or TIFs. A resolution between 200 and 300dpi is recommended.</p>';
$string['pdfdeletedforgroup'] = 'Form for group {$a} deleted';
$string['pdfscreated'] = 'PDF forms have been created';
$string['pdfsdeletedforgroup'] = 'Forms for group {$a} deleted';
$string['pdfintro'] = 'Additional information';
$string['pdfintro_help'] = 'This information will be printed on the first page of the question sheet and should contain general information about how to fill in the answer form.';
$string['pdfintrotext'] = '<b>How do I mark correctly?</b><br />This answer form will be scanned automatically. Please do not fold or spot. Use a black or blue pen to mark the fields. If you want to correct a marking, completely fill the box with color. This field will be interpreted like an empty box.<br />';
$string['pdfintrotoolarge'] = 'The introduction is too long (max. 2000 characters).';
$string['pearlywhite'] = 'Pearly white';
$string['pluginadministration'] = 'Offline quiz administration';
$string['point'] = 'point';
$string['present'] = 'present';
$string['preventsamequestion'] = 'Prevent multiple usage of the same question in different groups';
$string['previewforgroup'] = 'Preview for group {$a}';
$string['preview'] = 'Preview';
$string['previewquestion'] = 'Preview question';
$string['printstudycodefield'] = 'Print study code field on question sheet';
$string['printstudycodefield_help'] = 'If checked, the study code field will be printed on the first page of the question sheet.';
$string['questionanalysis'] = 'Difficulty analysis';
$string['questionanalysistitle'] = 'Difficulty Analysis Table';
$string['questionbankcontents'] = 'Question bank contents';
$string['questionforms'] = 'Question forms';
$string['questioninfoanswers'] = 'Number of correct answers';
$string['questioninfocorrectanswer'] = 'correct answer';
$string['questioninfocorrectanswers'] = 'correct answers';
$string['questioninfonone'] = 'Nothing';
$string['questioninfoqtype'] = 'Question type';
$string['questionname'] = 'Question name';
$string['questionsheet'] = 'Question sheet';
$string['questionsheetlatextemplate'] = '\documentclass[12pt,a4paper]{article}
\textwidth 16truecm
\textheight 23truecm
\setlength{\oddsidemargin}{0cm}
\setlength{\evensidemargin}{0cm}
\setlength{\topmargin}{-1cm}
\usepackage{amsmath} % for \implies etc
\usepackage{amsfonts} % for \mathbb etc
\usepackage{graphicx} % for including pictures
\renewcommand{\familydefault}{\sfdefault} % Font
\newcommand{\lsim}{\mbox{\raisebox{-.3em}{$\stackrel{<}{\sim}$}}} % less or approximately equal
\newcommand{\subs}{\mbox{\raisebox{-.5em}{$\stackrel{\subset}{\neq}$}}}
\newcommand{\sei}{\mbox{\raisebox{.0em}{$\stackrel{!}{=}$}}}
\parindent 0pt % no indent on the beginning of a section
\usepackage{esvect} % long vector arrows, e.g. \vv{AB}
\usepackage[colorlinks=true,urlcolor=dunkelrot,linkcolor=black]{hyperref} % For using of Hyperlinks
\renewcommand\UrlFont{\sf}
\usepackage{ulem} %  \sout{horizontal cross out} \xout{diagonal strike out}
\newcommand{\abs}[1]{\left\lvert#1\right\rvert}
\usepackage{scrpage2} % For Header and Footer
\pagestyle{scrheadings}
\clearscrheadfoot
\ifoot{[Gruppe \Group]}
\makeatletter %%% disable pagebreaks between answers
\@beginparpenalty=10000
\@itempenalty=10000
\makeatother
%
\newcommand{\answerIs}[1]{} %%%Disable showing the right answer
% \newcommand{\answerIs}[1]{[#1]} %%%Enable showing the right answer
%%%

\begin{document}



% ===========================================================================================================
%%% Data of the Course
\begin{center}{\LARGE {$a->coursename}}\end{center}
\begin{center}{Written Exam {$a->date}}\end{center}
%%%
\def\Group{{$a->groupname}}
\begin{center}{\Large Group \Group}\end{center}

{\bf Name:}\\\\
{\bf Matriculation number:}\\\\
{\bf Signature:}\\

% ===========================================================================================================
\bigskip

{$a->latexforquestions}


\end{document}';
$string['questionsin'] = 'Questions in';
$string['questionsingroup'] = 'Questions in group';
$string['questionsinthisofflinequiz'] = 'Questions in this offline quiz';
$string['questiontextisempty'] = '[Empty question text]';
$string['quizdate'] = 'Date of offline quiz';
$string['quizopenclose'] = 'Open and close dates';
$string['quizopenclose_help'] = 'Students can only see their attempt(s) after the open time and before the close time.';
$string['quizquestions'] = 'Quiz Questions';
$string['randomfromexistingcategory'] = 'Random question from an existing category';
$string['randomnumber'] = 'Number of random questions';
$string['randomquestionusinganewcategory'] = 'Random question using a new category';
$string['readjust'] = 'Readjust';
$string['reallydeletepdfs'] = 'Do you really want to delete the form files?';
$string['reallydeleteupdatepdf'] = 'Do you really want to delete and update the participants list?';
$string['recreatepdfs'] = 'Recreate PDFs';
$string['recurse'] = 'Include questions from subcategories too';
$string['refreshpreview'] = 'Refresh preview';
$string['regrade'] = 'Regrade';
$string['regradedisplayexplanation'] = '<b>Attention:</b> Regrading will not change marks that have been overwritten manually!';
$string['regradinginfo'] = 'If you change the score for a question, you must regrade the offline quiz to update the participants’ results.';
$string['regradingquiz'] = 'Regrading';
$string['regradingresult'] = 'Regrading result for user {$a}...';
$string['reloadpreview'] = 'Reload preview';
$string['reloadquestionlist'] = 'Reload question list';
$string['remove'] = 'Remove';
$string['removeemptypage'] = 'Remove empty page';
$string['removepagebreak'] = 'Remove page break';
$string['removeselected'] = 'Remove selected';
$string['reordergroupquestions'] = 'Reorder Group Questions';
$string['reorderquestions'] = 'Reorder questions';
$string['reordertool'] = 'Show the reordering tool';
$string['repaginate'] = 'Repaginate with {$a} questions per page';
$string['repaginatecommand'] = 'Repaginate';
$string['repaginatenow'] = 'Repaginate now';
$string['reportoverview'] = 'Overview';
$string['reportstarts'] = 'review of results';
$string['resetofflinequizzes'] = 'Reset Offline Quiz data';
$string['results'] = 'Results';
$string['resultexists'] = 'Same result for {$a} already exists, import ignored';
$string['resultimport'] = 'Import results';
$string['reviewcloses'] = 'Review closes';
$string['reviewbefore'] = 'Allow review while offline quiz is open';
$string['reviewclosed'] = 'After the offline quiz is closed';
$string['reviewimmediately'] = 'Immediately after the attempt';
$string['reviewincludes'] = 'Review includes';
$string['reviewofresult'] = 'Review of result';
$string['reviewopens'] = 'Review opens';
$string['reviewoptions'] = 'Students may view';
$string['reviewoptionsheading'] = 'Review options';
$string['reviewoptions_help'] = 'With these options you can control what the students may see after the results were imported.
You can also define start and end time for the results report. The checkboxes mean:
<table>
<tr><td style="vertical-align: top;"><b>The attempt</b></td><td>
The text of the questions and answers will be shown to the students. They will see which answers they chose, but the correct answers will not be indicated.</td>
</td></tr>
<tr><td style="vertical-align: top;"><b>Whether correct</b></td><td>
This option can only be activated if the option "The attempt" is activated. If activated, the students can see which of the chosen answers are correct (green background) or incorrect (red background).
</td></tr>
<tr><td style="vertical-align: top;"><b>Marks</b></td><td>
The group (e.g. B), scores (achieved grade, total grade for questions, achieved in percent, e.g. 40/80 (50)) and the grade (e.g. 50 out of a maximum of 100) are displayed.
Additionally, if "The attempt" is selected, the achieved score and the maximum score are shown for every question.
</td></tr>
<tr><td style="vertical-align: top;"><b>Right Answers</b></td><td>
It is shown which answers are correct or wrong. This option is only available if "The attempt" is set.
</td></tr>
<tr><td style="vertical-align: top;"><b>Scanned form</b></td><td>
The scanned answer forms are shown. Checked boxes are marked with green squares.
</td></tr>
<tr><td style="vertical-align: top;"><b>Scanned form with grades</b></td><td>
The scanned answer forms are shown. Checked boxes are marked with green squares. Wrong marks and missing marks are highlighted.
Additionally, a table shows the maximum grade and the achieved grade for every question.
</td></tr>
</table>';

$string['review'] = 'Review';
$string['rimport'] = 'Upload/Correct';
$string['rotate'] = 'Rotate';
$string['rotatingsheet'] = 'Sheet is rotated...';
$string['saveandshow'] = 'Save and show changes to student';
$string['save'] = 'Save';
$string['savescannersettings'] = 'Save scanner settings';
$string['scannedform'] = 'Scanned form';
$string['scannerformfortype'] = 'Form for type {$a}';
$string['scanningoptionsheading'] = 'Scanning options';
$string['scanneroptions'] = 'Scanner settings';
$string['scannerpdfs'] = 'Empty forms';
$string['scannerpdfstext'] = 'Download the following empty forms if you want to use your own scanner software.';
$string['score'] = 'Score';
$string['search:activity'] = 'Offline quiz - activity information';
$string['select'] = 'Select';
$string['selectagroup'] = 'Select a group';
$string['selectall'] = 'Select all';
$string['selectcategory'] = 'Select category';
$string['selectedattempts'] = 'Selected attempts...';
$string['selectnone'] = 'Deselect all';
$string['selectquestiontype'] = '-- Select question type --';
$string['selectdifferentgroup'] = 'Please select a different group!';
$string['selectformat'] = 'Select format...';
$string['selectgroup'] = 'Select group';
$string['selectlist'] = 'Please select a list or try to readjust sheet:';
$string['selectmultipletoolbar'] = 'Select multiple toolbar';
$string['selectpage'] = 'Please select page number or try to readjust sheet:';
$string['showallparts'] = 'Show all {$a} participants';
$string['showcopyright'] = 'Show copyright statement';
$string['showcopyrightdesc'] = 'If you enable this option, a copyright statement will be shown on the student result review page.';
$string['showgrades'] = 'Print question grades';
$string['showgrades_help'] = 'This option controls whether the maximum grades of the offline quiz questions should be printed on the question sheet.';
$string['showmissingattemptonly'] = 'Show all checked participants without result';
$string['showmissingcheckonly'] = 'Show all unchecked participants with result';
$string['shownumpartsperpage'] = 'Show {$a} participants per page';
$string['showquestioninfo'] = 'Print info about answers';
$string['showquestioninfo_help'] = 'With this option you can control, which additional information about the question is printed on the question sheet.
You can choose one of these:
<ul>
<li> Nothing
<li> Question type - Depending on question type Single-Choice, Multiple-Choice, All-or-Nothing Multiple-Choice will be printed
<li> Number of correct answers - The number of correct answers will be printed
</ul>';
$string['showstudentview'] = 'Show student view';
$string['showtutorial'] = 'Show an offline quiz tutorial to students.';
$string['showtutorial_help'] = 'This option determines whether students can see a tutorial about the basics of offline quizzes.
The tutorial provides information about how to handle the different types of documents in offline quizzes. In an interactive part they learn how to tick their student ID correctly.<br />
<b>Please note:</b><br />
If you set this option to "Yes" but hide the offline quiz the link to the tutorial will not be visible. In this case you can add a link to the tutorial on the course page.';
$string['showtutorialdescription'] = 'You can add a link to the tutorial to the course page using the following URL:';
$string['shuffleanswers'] = 'Shuffle answers';
$string['shufflequestions'] = 'Shuffle questions';
$string['shufflequestionsanswers'] = 'Shuffle questions and answers';
$string['shufflequestionsselected'] = 'Shuffle questions has been set, so some actions relating to pages are not available. To change the shuffle option, {$a}.';
$string['shufflewithin'] = 'Shuffle within questions';
$string['shufflewithin_help'] = 'If enabled, the parts making up each question will be randomly shuffled each time you press the reload button in the form preview. NOTE: This setting only applies to questions that have the shuffeling option activated.';
$string['signature'] = 'Signature';
$string['singlechoice'] = 'Single choice';
$string['standard'] = 'Standard';
$string['starttutorial'] = 'Start tutorial about the examination';
$string['statistics'] = 'Statistics';
$string['statisticsplural'] = 'Statistics';
$string['statsoverview'] = 'Statistics Overview';
$string['studycode'] = 'Study code';
$string['theattempt'] = 'The attempt';
$string['timesup'] = 'Time is up!';
$string['totalmarksx'] = 'Total of marks: {$a}';
$string['totalpointsx'] = 'Total of marks: {$a}';
$string['totalquestionsinrandomqcategory'] = 'Total of {$a} questions in category.';
$string['trigger'] = 'lower/higher boundary';
$string['tutorial'] = 'Tutorial for offline quizzes';
$string['type'] = 'Type';
$string['uncheckparts'] = 'Mark selected participants as absent';
$string['updatedsumgrades'] = 'The sum of all grades of group {$a->letter} was re-calculated to {$a->grade}.';
$string['upgradingfilenames'] = 'Upgrading filenames of documents: offline quiz {$a->done}/{$a->outof} (Offline Quiz ID {$a->info})';
$string['upgradingofflinequizattempts'] = 'Upgrading offline quiz attempts: offline quiz {$a->done}/{$a->outof} <br/>(Offline Quiz ID {$a->info})';
$string['upgradingilogs'] = 'Upgrading scanned pages: scanned page {$a->done}/{$a->outof} <br/>(Offline Quiz ID {$a->info})';
$string['uploadpart'] = 'Upload/Correct lists of participants';
$string['upload'] = 'Upload/Correct';
$string['uppertrigger'] = 'Higher second boundary';
$string['uppertriggerzero'] = 'Higher second boundary is zero';
$string['upperwarning'] = 'Higher first boundary';
$string['upperwarningzero'] = 'Higher first boundary is zero';
$string['useradded'] = 'User {$a} added';
$string['userdoesnotexist'] = 'User {$a} does not exist in system';
$string['useridentification'] = 'User identification';
$string['userimported'] = 'User {$a} imported and graded';
$string['usernotincourse'] = 'User {$a} not in course.';
$string['userpageimported'] = 'Single page imported for user {$a}';
$string['usernotinlist'] = 'User not registered in list!';
$string['usernotregistered'] = 'User {$a} not registered in course';
$string['valuezero'] = 'Value should not be zero';
$string['viewresults'] = 'View results';
$string['white'] = 'White';
$string['withselected'] = 'With selected...';
$string['zipfile'] = 'ZIP file';
$string['zipok'] = 'ZIP file imported';
$string['zerogradewarning'] = 'Warning: Your offline quiz grade is 0.0!';
$string['disableimgnewlines'] = 'Disable new lines before and after images';
$string['disableimgnewlines_help'] = 'This option removes extra new lines surrounding images. It will also move the a/b/c... from left upper corner of the image into the left down corner of image answear. Helpful in quizzes containing LaTeX formulas.';
$string['configdisableimgnewlines'] = 'This will also move the a/b/c... from over the image into the left down corner of image answear';
