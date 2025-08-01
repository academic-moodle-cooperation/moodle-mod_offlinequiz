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

$string['modulename'] = 'Offline quiz';
$string['modulenameplural'] = 'Offline quizzes';
$string['pluginname'] = 'Offline quiz';

$string['addnewquestion'] = 'a new question';
$string['add'] = 'Add';
$string['addlist'] = 'Add attendance list';
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
$string['addarandomquestion'] = 'a random question';
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
$string['attendancesheadline'] = 'Attendances';
$string['attendancespreparationheadline'] = '1. Preparation';
$string['attendancesevaluationheadline'] = '2. Evaluation';
$string['attendanceoverview'] = 'Attendances';
$string['attendenceoverviewattwithoutresults'] = 'Present without results:';
$string['attendenceoverviewattwithresults'] = 'Present with results:';
$string['attendenceoverviewcorrectionnecessary'] = 'Correction necessary (pages):';
$string['attendenceoverviewnoattwithoutresults'] = 'Absent without results:';
$string['attendenceoverviewnoattwithresults'] = 'Absent with results:';
$string['blackwhitethreshold'] = 'Black/white threshold.';
$string['backtopreview'] = 'Show Preview';
$string['basicideasofofflinequiz'] = 'The basic ideas of offline quiz-making';
$string['bulksavegrades'] = 'Save Grades';
$string['calibratescanner'] = 'Calibrate scanner';
$string['cannoteditafterattempts'] = 'You cannot add or remove questions because there are already complete results. ({$a})';
$string['category'] = 'Category';
$string['changed'] = 'Result has been changed.';
$string['changeevaluationmode'] = 'Change the way how the evaluation is done. Only possible if allowed by the administrator.';
$string['checkparts'] = 'Mark selected participants as present';
$string['checkuserid'] = 'Check group/user ID';
$string['chooseagroup'] = 'Choose a group...';
$string['closebeforeopen'] = 'Could not update the offline quiz. You have specified a close date before the open date.';
$string['closestudentview'] = 'Close Student View';
$string['closewindow'] = 'Close Window';
$string['cmmissing'] = 'The course module for the offline quiz with ID {$a} is missing';
$string['completiondetail:passgrade'] = 'Receive a pass grade';
$string['completionpass'] = 'Pass to complete';
$string['completionpass_help'] = 'Students only complete this offlinequiz, if they reach a grade higher or equal of the passing grade set under the grade-options';
$string['configblackwhitethreshold'] = 'Sets the threshold for the black/white converting between 1-99 - The higher the more white has to be the imported pixel to be recognized as white. Any other value leads to no black/white conversion at all.';
$string['configdisableimgnewlines'] = 'This option disables new lines before and after images in the pdf question sheets. Warning: This might lead to formatting problems.';
$string['configexperimentalevaluation'] = 'Experimental evaluation of answer sheets';
$string['configexperimentalevaluationdesc'] = 'DO NOT USE IN PRODUCTION! This option is used for alpha tests of the new evaluation algorithm. If this option is turned to yes, you can turn it on in the options of the offlinequiz instance. We highly discourage to use this option. There is no support at all for lost data due to turning on this option.';
$string['configintro'] = 'The values you set here are used as default values for the settings of new offline quizzes.';
$string['configkeepfilesfordays'] = 'Determine how many days the uploaded image files are kept in temporary storage. During this time the image files are available in the offline quiz admin report.';
$string['configonlylocalcategories'] = 'No shared question categories allowed.';
$string['configshuffleanswers'] = 'Shuffle answers';
$string['configshufflequestions'] = 'If you enable this option, then the order of questions in the offline quiz groups will be randomly shuffled each time you re-create the preview in the "Create forms" tab.';
$string['configshufflewithin'] = 'If you enable this option, the answers of multiple choice questions are shuffled separately for each offline quiz group.';
$string['configuseridentification'] = 'A formula describing the user identification. This formula is used to assign answer forms to users in the system. The left hand side of the equation defines the number of digits (integer only, up to 10 digits). The right hand side of the equation must denote a field in the user table of Moodle (e.g. id, idnumber, phone1).
In addition, the equation can also deal with prefix and suffix which is added to the marked digits (e.g. "a[7]=username" or "b[5]cd=username"). These concatenations of strings and numbers denote the value of the used field in the user table, that is used for user identification.';
$string['configpapergray'] = 'white-value of paper, which is used for the evaluation of answer-sheets';
$string['configshufflewithin'] = 'If you enable this option, then the parts making up the individual questions will be randomly shuffled when the question- and answer-forms are created.';
$string['confirmremovequestion'] = 'Are you sure you want to remove this {$a} question?';
$string['copyright'] = '<strong>Warning: The texts on this page are just for your personal information. Like any other texts these questions are under copyright restrictions. You are not allowed to copy them or to show them to other people!</strong>';
$string['copy'] = 'Copy';
$string['correct'] = 'correct';
$string['correctheader'] = 'Correction necessary: ';
$string['correctionheader'] = 'Correction';
$string['correctionheader_help'] = '<p>The answer forms displayed here need your your attention and must be corrected manually. An automatic evaluation is not possible.</p>
<u>The following errors may occur...</u>
<ul>
  <li><strong>Inaccurate markings:</strong> Some marks could not be evaluated.</li>
  <li><strong>Invalid group number:</strong> The group number could not be evaluated.</li>
  <li><strong>Invalid page number:</strong> The page number could not be evaluated.</li>
  <li><strong>Not adjusted:</strong> The answer form was scanned crooked and cannot be evaluated.</li>
  <li><strong>Different result exists:</strong> An evaluated result with a deviating group number already exists.</li>
  <li><strong>Pages incomplete:</strong> The current answer sheet has been evaluated, but at least 1 additional sheet is missing to complete the evaluation for the identified participant. (Only occurs with multi-page answer forms.)</li>
  <li><strong>Result exists:</strong> An evaluated result already exists.</li>
  <li><strong>User not in course:</strong> The identified participant is not currently registered in the course.</li>
  <li><strong>User does not exist:</strong> The identified participant is unknown.</li>
</ul>';
$string['correcterror'] = 'solve';
$string['correctforgroup'] = 'Correct answers for Group {$a}';
$string['correctionerrors'] = 'Forms with correction errors: ';
$string['correctionform'] = 'Correction';
$string['correctionforms'] = 'Correction forms';
$string['correctionoptionsheading'] = 'Correction options';
$string['correctupdated'] = 'Updated correction form for group {$a}.';
$string['couldnotgrab'] = 'Could not grab image {$a}';
$string['couldnotextractpages'] = 'Error while extracting pages from the uploaded files.';
$string['couldnotinsertjobs'] = 'Error with the database while extracting pages';
$string['couldnotregister'] = 'Could not register user {$a}';
$string['couldnotextracttiff'] = 'Error while converting tiff to single images. Please try a smaller document or contact your local administrator.';
$string['couldnotextractpdf'] = 'Error while converting pdf to single images. Please try a smaller document or contact your administrator.';
$string['createcategoryandaddrandomquestion'] = 'Create category and add random question';
$string['createofflinequiz'] = 'Create forms';
$string['createlistfirst'] = 'Add participant lists first';
$string['createpartpdferror'] = 'The PDF form for the list of participants {$a} could not be created. The list may be empty.';
$string['createpdferror'] = 'The form for group {$a} could not be created. Maybe there are no questions in the group.';
$string['createpdffirst'] = 'Create PDF list first';
$string['createpdfforms'] = 'Create forms';
$string['createpdf'] = 'Form';
$string['createpdfs'] = 'Forms';
$string['createpdfsparticipants'] = 'Forms';
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
$string['defaultparticipantsusage'] = 'Record attendance';
$string['defaultparticipantsusagedesc'] = 'Determines if attendances should be recorded with attendance lists by default.';
$string['defaultpdffontsize'] = 'Default fontsize';
$string['defaultpdffontsize_help'] = 'The default font size all forms will use.';
$string['defaultpdffont'] = 'Default form font';
$string['defaultpdffont_help'] = 'The default font offlinequiz will use for forms. You may want to manually download tcpdf package and extract its fonts/ directory into your $CFG->dataroot/fonts/ for more fonts. It is recommended to use Freeserif as the default as it supports the most characters. See https://www.gnu.org/software/freefont/coverage.html for the full compatibility list.';
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
$string['disableimgnewlines'] = 'Disable new lines before and after images';
$string['disableimgnewlines_help'] = 'This option disables new lines before and after images in the pdf question sheets. Warning: This might lead to formatting problems.';
$string['discriminationindex_info'] = 'Since the discrimination index calculates prediction of the question between good and inferior grades it can\'t be calculated if either
- there are no different responses of the students in this question
- there are no different grades in the exam';
$string['displayoptions'] = 'Display options';
$string['documentschanged'] = 'One ore more question(s) in the offlinequiz was/ were changed since creating the documents.<br>Future results will be regraded automatically during the evaluation to the current version of the question(s) chosen here. In the documents the origin version of the question(s) is still shown.';
$string['documentschangedwithresults'] = 'One ore more question(s) in the offlinequiz was/ were changed since creating the documents.<br>Existing results were regraded automatically to the current version of the question(s) chosen here. In the documents the origin version of the question(s) is still shown.';
$string['done'] = 'done';
$string['downloadallzip'] = 'Download all files as ZIP';
$string['downloadpartpdf'] = 'Download PDF file for list \'{$a}\'';
$string['downloadpdfs'] = 'Forms';
$string['downloadresultsas'] = 'Download results as: ';
$string['dragtoafter'] = 'After {$a}';
$string['dragtostart'] = 'To the start';
$string['editingofflinequizx'] = 'Questions';
$string['editlist'] = 'Edit list';
$string['editlists'] = 'Attendance lists';
$string['editgroups'] = 'Edit Offline Groups';
$string['editgroupquestions'] = 'Edit group questions';
$string['editingofflinequiz'] = 'Editing group questions';
$string['editingofflinequiz_help'] = 'When creating an offline quiz, the main concepts are:
<ul><li> The offline quiz, containing questions over one or more pages</li>
<li> The question bank, which stores copies of all questions organised into categories</li></ul>';
$string['editofflinequiz'] = 'Edit offline quiz';
$string['editmaxmark'] = 'Edit maximum mark';
$string['editorder'] = 'Edit order';
$string['editparticipants'] = 'Edit participants';
$string['editquestion'] = 'Edit question';
$string['editquestionlistitem'] = '{$a}';
$string['editquestions'] = 'Questions';
$string['editscannedform'] = 'Edit scanned form';
$string['editthislist'] = 'Edit this list';
$string['emptygroups'] = 'Some offline quiz groups are empty. Please add some questions.';
$string['enroluser'] = 'Enrol user';
$string['erroraccessingreport'] = 'You are not allowed to view this report.';
$string['errorreport'] = 'Report of import errors';
$string['errormultiuserinlist'] = 'User is more than once listed in attendance lists!';
$string['evaluation'] = '2. Evaluation';
$string['evaluated'] = 'Evaluated: ';
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
$string['experimentalevaluation'] = 'Experimental evaluation of answer sheets';
$string['experimentalevaluation_help'] = 'Experimental evaluation of answer sheets';
$string['fileprefixanswer'] = 'answer_form';
$string['fileprefixcorrection'] = 'correction_form';
$string['fileprefixform'] = 'question_form';
$string['fileprefixparticipants'] = 'participants_list';
$string['fileformat'] = 'Format for question sheets';
$string['fileformat_help'] = 'Choose whether you want your question sheets in PDF, DOCX or TEX format. Answer forms and correction sheets will always be generated in PDF format.';
$string['filesizetolarge'] = 'Some of your image files are very large. The dimensions will be resized during the interpretation. Please try to scan with a resolution between 200 and 300 dpi and in black and white mode. This will speed up the interpretation next time.';
$string['filterbytags'] = 'Filter by tags...';
$string['fontsize'] = 'Fontsize';
$string['fontfamilycourier'] = 'Courier';
$string['fontfamilyfreemono'] = '(Free-)Mono';
$string['fontfamilyfreesans'] = '(Free-)Sans';
$string['fontfamilyfreeserif'] = '(Free-)Serif (recommended)';
$string['fontfamilyhelvetica'] = 'Helvetica';
$string['fontfamilyhysmyeongjostdmedium'] = 'MyungJo Medium (Korean)';
$string['fontfamilykozgopromedium'] = 'Kozuka Gothic Pro (Japanese Sans-Serif)';
$string['fontfamilykozminproregular'] = 'Kozuka Mincho Pro (Japanese Serif)';
$string['fontfamilymsungstdlight'] = 'MSung Light (Trad. Chinese)';
$string['fontfamilystsongstdlight'] = 'STSong Light (Simp. Chinese)';
$string['fontfamilysymbol'] = 'Symbol';
$string['fontfamilytimes'] = 'Times';
$string['fontfamilyzapfdingbats'] = 'ZapfDingbats';
$string['forautoanalysis'] = 'For automatic analysis';
$string['formforcorrection'] = 'Correction form for group {$a}';
$string['formforgroup'] = 'Question form for group {$a}';
$string['formforgroupdocx'] = 'Question form for group {$a} (DOCX)';
$string['formforgrouplatex'] = 'Question form for group {$a} (LATEX)';
$string['forms'] = 'Forms';
$string['formsexist'] = 'Forms already created.';
$string['formsexistx'] = 'Forms already created (<a href="{$a}">Download forms</a>)';
$string['formsheetsettings'] = 'Form Settings';
$string['formspreview'] = 'Preview';
$string['formwarning'] = 'There is no answer form defined. Please contact your administrator.';
$string['fromquestionbank'] = 'from question bank';
$string['functiondisabledbysecuremode'] = 'That functionality is currently disabled';
$string['furtheroptionsheading'] = 'Further Options';
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
$string['gradingofflinequizx'] = 'Grades';
$string['gradingoptionsheading'] = 'Grading options';
$string['greeniscross'] = 'counted as a cross';
$string['rediswrong'] = 'wrong cross or missing cross';
$string['group'] = 'Group';
$string['groupoutofrange'] = 'Group was out of range and replaced with group A.';
$string['hasresult'] = 'Result exists';
$string['hotspotdeletiontask'] = 'Deletion of hotspots';
$string['html'] = 'HTML';
$string['id'] = 'User-ID';
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
$string['importfromto'] = 'Evaluating {$a->from} to {$a->to} of {$a->total}.';
$string['import'] = 'Evaluate';
$string['importnew'] = 'Evaluate';
$string['importnew_help'] = '<p>
You can import single scanned image files or several scanned image files in a ZIP-archive. The offline quiz module will process the image files in the background.
File names are not relevant but should not contain special characters such as umlauts. Images should be GIFs, PNGs
or TIFs. A resolution between 200 and 300dpi is recommended.</p>';
$string['importedon'] = 'Evaluated on';
$string['importforms'] = 'Upload answer forms';
$string['importisfinished'] = 'Evaluation for offline quiz {$a} is finished.';
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
$string['legend'] = 'Legend';
$string['legenddone'] = 'Step successfully done (no further action required).';
$string['legendnextitem'] = 'Action required in the further workflow (possibly dependence on previous steps).';
$string['legendnoaction'] = 'No action required.';
$string['legendwarning'] = 'Immediate action required.';
$string['letter'] = 'Letter';
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
$string['marginwarning'] = 'Please print the following PDF files without additional margins!<br /> Avoid handing out photocopies to students.';
$string['marks'] = 'Marks';
$string['matrikel'] = 'student number';
$string['maxgradewarning'] = 'The maximum grade has to be a number!';
$string['maxmark'] = 'Maximum mark';
$string['membersinplist'] = '{$a->count} participants in <a href="{$a->url}">{$a->name}</a>';
$string['messageprovider:jobs'] = 'Offline quiz notifications';
$string['missingimagefile'] = 'Missing image file';
$string['missingitemdata'] = 'Missing answer(s) for user {$a}';
$string['missinglogdata'] = 'Missing logdata for existing result.';
$string['missingquestion'] = 'This question no longer seems to exist';
$string['missinguserid'] = 'Missing user identification number! Could not read barcode!';
$string['modulename_help'] = 'This module allows the teacher to design offline quizzes consisting of multiple choice questions. These questions are kept in the Moodle question bank and can be re-used within courses and even between courses. The offline quizzes can be downloaded as PDF-, DOCX- or LaTeX-files. The students mark their answers on form sheets. The form sheets are scanned and the answers imported into the system.

<p><a href="https://academic-moodle-cooperation.org/anleitungen/offline-test-einstellungen/?lng=en" target="_blank">Offline quiz: settings</a></p>
<p><a href="https://academic-moodle-cooperation.org/anleitungen/offline-test-fragen-hinzufugen-und-formulare-generieren/?lng=en" target="_blank">Offline quiz: add questions and generate forms</a></p>
<p><a href="https://academic-moodle-cooperation.org/anleitungen/offline-test-durchfuhren-und-auswerten/?lng=en" target="_blank">Offline quiz: Conduct and evaluate offline quiz</a></p>';
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
$string['notagselected'] = 'No tag selected';
$string['notavailable'] = 'N/A';
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
$string['offlinequizcloseson'] = 'The review for this offline quiz will close {$a}';
$string['offlinequizcontent'] = 'Offline quiz content';
$string['offlinequizisclosed'] = 'Offline quiz is closed';
$string['offlinequizisclosedwillopen'] = 'Offline quiz closed (opens {$a})';
$string['offlinequizisopen'] = 'This offline quiz is open';
$string['offlinequizisopenwillclose'] = 'Offline quiz open (closes {$a})';
$string['offlinequizopenedon'] = 'This offline quiz opened at {$a}';
$string['offlinequizsettings'] = 'Offline settings';
$string['offlinequiz:addinstance'] = 'Add an offline quiz';
$string['offlinequiz:attempt'] = 'Attempt quizzes';
$string['offlinequiz:changeevaluationmode'] = 'Make it possible to change the evaluationmode. WARNING: DO NOT USE IN PRODUCTION SYSTEMS!';
$string['offlinequizcloses'] = 'Offline quiz closes';
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
$string['overview'] = 'Quiz information';
$string['overviewdownload_help'] = 'Download Overview';
$string['pagecorrected'] = 'Corrected sheet of participants list imported';
$string['pageevaluationtask'] = 'Answer sheet evaluation for the offlinequiz-plugin';
$string['pageimported'] = 'Sheet of participants list imported';
$string['page-mod-offlinequiz-x'] = 'Any offline quiz page';
$string['page-mod-offlinequiz-edit'] = 'Edit offline quiz page';
$string['pagenumberimported'] = 'Sheet {$a} of participants list imported';
$string['pagenumberupdate'] = 'Page number update';
$string['pagenotdetected'] = 'Could not detect barcode for page!';
$string['pagesinprocessing'] = 'In processing: ';
$string['pagesizeparts'] = 'Participants shown per page:';
$string['papergray'] = 'White value of paper';
$string['papergray_help'] = 'If the white parts of your scanned answer forms are very dark you can correct this by setting this value to dark grey.';
$string['partcheckedwithresult'] = '{$a} checked participants with result';
$string['partcheckedwithoutresult'] = '<a href="{$a->url}">{$a->count} checked participants without result</a>';
$string['partuncheckedwithresult'] = '<a href="{$a->url}">{$a->count} unchecked participants with result</a>';
$string['partuncheckedwithoutresult'] = '{$a} unchecked participants without result';
$string['partial'] = 'partial';
$string['participantslist'] = 'List of participants';
$string['participantslists'] = 'Participants';
$string['participants'] = 'Participants';
$string['participantsinlists'] = 'Participants';
$string['participantsusage'] = 'Record attendance';
$string['participantsusage_help'] = 'This option determines whether you can record the attendance of participants with attendance lists. Attendance lists are designed for large offline quizzes with many participants. They can be used to check which students participated in the quiz and whether all the results were evaluated correctly.';
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
$string['pdffont'] = 'Form font';
$string['pdffont_help'] = 'Here you can choose a font for all your PDF forms: answer forms, correction forms, attendance forms. As well as question forms, if you choose a PDF format for it. The choice should also depend on which characters you use in your forms. With DOCX and LATEX format the question sheets are not affected by it.';
$string['pdfintro'] = 'Additional information';
$string['pdfintro_help'] = 'This information will be printed on the first page of the question sheet and should contain general information about how to fill in the answer form.';
$string['pdfintrotext'] = '<b>How do I mark correctly?</b><br />This answer form will be scanned automatically. Please do not fold or spot. Use a black or blue pen to mark the fields. If you want to correct a marking, completely fill the box with color. This field will be interpreted like an empty box.<br />';
$string['pdfintrotoolarge'] = 'The introduction is too long (max. 2000 characters).';
$string['pdfscreated'] = 'PDF forms have been created';
$string['pdfsdeletedforgroup'] = 'Forms for group {$a} deleted';
$string['pearlywhite'] = 'Pearly white';
$string['pluginadministration'] = 'Offline quiz administration';
$string['point'] = 'point';
$string['preparation'] = '1. Preparation';
$string['present'] = 'Present';
$string['preventsamequestion'] = 'Prevent multiple usage of the same question in different groups';
$string['previewforgroup'] = 'Group {$a}';
$string['preview'] = 'Preview';
$string['previewquestion'] = 'Preview question';
$string['printstudycodefield'] = 'Print study code field on question sheet';
$string['printstudycodefield_help'] = 'If checked, the study code field will be printed on the first page of the question sheet.';
$string['privacy:data_folder_name'] = 'Offlinequiz data';
$string['privacy:metadata:core_files'] = 'The offlinequiz uses the file API to store the generated question sheets, answer sheets and correction sheets as well as the filled out answer sheets.';
$string['privacy:metadata:core_question'] = 'The offlinequiz uses the question API for saving the questions for the quizes.';
$string['privacy:metadata:mod_quiz'] = 'The offlinequiz uses the quiz API for saving results of the quizes.';
$string['privacy:metadata:offlinequiz:course'] = 'The \'course\' column in the offlinequiz table saves in which course this offlinequiz is stored in.';
$string['privacy:metadata:offlinequiz:name'] = 'The \'name\' column saves the name of the offlinequiz.';
$string['privacy:metadata:offlinequiz:introformat'] = 'This field is not used.';
$string['privacy:metadata:offlinequiz:pdfintro'] = 'The additional information which is inserted into the question sheets in the beginning.';
$string['privacy:metadata:offlinequiz:timeopen'] = 'The timeopen column saves when an offlinequiz was/will be opened.';
$string['privacy:metadata:offlinequiz:timeclose'] = 'The timeclose column saves when the offlinequiz was/will be closed.';
$string['privacy:metadata:offlinequiz:time'] = 'The time column saves the date of the offlinequiz.';
$string['privacy:metadata:offlinequiz:grade'] = 'The grade shows the maximum amount of points to get in this offlinequiz.';
$string['privacy:metadata:offlinequiz:numgroups'] = 'The amount of groups this offlinequiz has.';
$string['privacy:metadata:offlinequiz:decimalpoints'] = 'The amount of decimal points to calculate for the grades.';
$string['privacy:metadata:offlinequiz:review'] = 'This column saves information about how the review is done.';
$string['privacy:metadata:offlinequiz:docscreated'] = 'If the documents were created this field is set to 1 otherwise its 0.';
$string['privacy:metadata:offlinequiz:shufflequestions'] = 'A preference if the questions should be shuffled when creating an offlinequiz; 1 for shuffling, 0 otherwise.';
$string['privacy:metadata:offlinequiz:printstudycodefield'] = 'A preference if the study code should be printed on the question formular. 1 for true, 0 otherwise.';
$string['privacy:metadata:offlinequiz:fontsize'] = 'The size of the font in the questionsheets.';
$string['privacy:metadata:offlinequiz:timecreated'] = 'The timecreated column saves when the offlinequiz was created.';
$string['privacy:metadata:offlinequiz:timemodified'] = 'The timemodified column saves the time when the offlinequiz was changed the last time.';
$string['privacy:metadata:offlinequiz:fileformat'] = 'The fileformat which is used to print the question sheets, 0 for pdf, 1 for docx, 2 for LaTeX.';
$string['privacy:metadata:offlinequiz:showquestioninfo'] = 'Saves if an information about the questions should be displayed, 0 for no, 1 for information about question type, 2 for information about the amount of right answers.';
$string['privacy:metadata:offlinequiz:showgrades'] = 'Saves if the amount of points to get for the question should be printed on the question sheet.';
$string['privacy:metadata:offlinequiz:showtutorial'] = 'Saves if participants should be asked to do an offlinequiz tutorial.';
$string['privacy:metadata:offlinequiz:id_digits'] = 'Saves the amount of digits the idnumber had when the answer sheets were created. This is needed for backward compability if the amount is raised between creation and import of the answer sheets.';
$string['privacy:metadata:offlinequiz:disableimgnewlines'] = 'Should disable new lines before and after images.';
$string['privacy:metadata:offlinequiz'] = 'The offlinequiz table saves every information specific to an offlinequiz instance.';
$string['privacy:metadata:offlinequiz_choices:scannedpageid'] = 'The scanned page the choice relates to.';
$string['privacy:metadata:offlinequiz_choices:slotnumber'] = 'The question slot of this choice.';
$string['privacy:metadata:offlinequiz_choices:choicenumber'] = 'The number of the choice for this question.';
$string['privacy:metadata:offlinequiz_choices:value'] = 'Is the choice considered to be crossed out. 0 for no, 1 for yes, -1 for uncertain.';
$string['privacy:metadata:offlinequiz_choices'] = 'This table holds the information of all the crosses for all the scanned pages. The information is needed to later create results based on the crosses.';
$string['privacy:metadata:offlinequiz_group_questions:offlinequizid'] = 'The offlinequizid this group question relates to.';
$string['privacy:metadata:offlinequiz_group_questions:offlinegroupid'] = 'The offlinequiz group this group question relates to.';
$string['privacy:metadata:offlinequiz_group_questions:questionid'] = 'The id of the selected question.';
$string['privacy:metadata:offlinequiz_group_questions:position'] = 'The position in this offlinequiz.';
$string['privacy:metadata:offlinequiz_group_questions:page'] = 'The page on which this question is printed in the answer sheets.';
$string['privacy:metadata:offlinequiz_group_questions:slot'] = 'The slot of the question in the quiz.';
$string['privacy:metadata:offlinequiz_group_questions:maxmark'] = 'The maximum amount of points being able to achieve for this question.';
$string['privacy:metadata:offlinequiz_group_questions'] = 'This table saves all the questions for every offlinequiz groups.';
$string['privacy:metadata:offlinequiz_groups:offlinequizid'] = 'The id of the offlinequiz this offlinequiz belongs to.';
$string['privacy:metadata:offlinequiz_groups:number'] = 'The number of the group for this offlinequiz, 1 for group A, 2 group B, and so on.';
$string['privacy:metadata:offlinequiz_groups:sumgrades'] = 'The sum of all grades for all questions in this group.';
$string['privacy:metadata:offlinequiz_groups:numberofpages'] = 'The amount of pages it needs to print the answer sheets on.';
$string['privacy:metadata:offlinequiz_groups:templateusageid'] = 'The id of the template usage, which is used to create a result in the quiz API.';
$string['privacy:metadata:offlinequiz_groups:questionfilename'] = 'The filename which was used to save the question file';
$string['privacy:metadata:offlinequiz_groups:answerfilename'] = 'The filename which was used to save the answer file.';
$string['privacy:metadata:offlinequiz_groups:correctionfilename'] = 'The file which was used to save the correction file.';
$string['privacy:metadata:offlinequiz_groups'] = 'Table for the groups in which the offlinequizzes take part.';
$string['privacy:metadata:offlinequiz_hotspots:scannedpageid'] = 'Scanned page on which the hotspot is.';
$string['privacy:metadata:offlinequiz_hotspots:name'] = 'Type of the hotspot, e.g. u%number for user hotspot, a-0-0 for question 1 answer 1, and so on.';
$string['privacy:metadata:offlinequiz_hotspots:x'] = 'The x value of the hotspot.';
$string['privacy:metadata:offlinequiz_hotspots:y'] = 'The y value of the hotspot.';
$string['privacy:metadata:offlinequiz_hotspots:blank'] = 'If the hotspot is analyzed successfully.';
$string['privacy:metadata:offlinequiz_hotspots:time'] = 'The last update time for this hotspot.';
$string['privacy:metadata:offlinequiz_hotspots'] = 'This table saves all the positions of the boxes and if they are evaluated successfully.';
$string['privacy:metadata:offlinequiz:papergray'] = 'The white value of this offlinequiz.';
$string['privacy:metadata:offlinequiz_page_corners:scannedpageid'] = 'The scanned page this corner is on.';
$string['privacy:metadata:offlinequiz_page_corners:x'] = 'The x value of the corner.';
$string['privacy:metadata:offlinequiz_page_corners:y'] = 'The y value of the corner.';
$string['privacy:metadata:offlinequiz_page_corners:position'] = 'The information whether this corner is at the top or bottom and right or left.';
$string['privacy:metadata:offlinequiz_page_corners'] = 'This table saves all the corners for every scanned page to evaluate it faster for the next evaluation or correction.';
$string['privacy:metadata:offlinequiz_participants:listid'] = 'The id of the list this participant is on.';
$string['privacy:metadata:offlinequiz_participants:userid'] = 'The userid of the user.';
$string['privacy:metadata:offlinequiz_participants:checked'] = 'The information if this user was checked on the participants list.';
$string['privacy:metadata:offlinequiz_participants'] = 'The participants table saves if the user was taking part in the offlinequiz or not.';
$string['privacy:metadata:offlinequiz_p_choices:scannedpageid'] = 'The scanned page this choice relates to.';
$string['privacy:metadata:offlinequiz_p_choices:userid'] = 'The userid this choice relates to.';
$string['privacy:metadata:offlinequiz_p_choices:value'] = 'If the cross is filled or not (0 for not filled, 1 for filled, -1 for insecure).';
$string['privacy:metadata:offlinequiz_p_choices'] = 'This table saves all the crosses for the participants lists.';
$string['privacy:metadata:offlinequiz_p_lists:offlinequizid'] = 'The offlinequiz this list belongs to.';
$string['privacy:metadata:offlinequiz_p_lists:name'] = 'The name of the participants list.';
$string['privacy:metadata:offlinequiz_p_lists:number'] = 'The number of the list in the offlinequiz.';
$string['privacy:metadata:offlinequiz_p_lists:filename'] = 'The name of the file for the list.';
$string['privacy:metadata:offlinequiz_p_lists'] = 'This table saves information about participants lists where the teachers can cross out, if a student was there or not.';
$string['privacy:metadata:offlinequiz_queue'] = 'This table saves a queue of an upload. For every upload there is an own object in this table.';
$string['privacy:metadata:offlinequiz_queue:offlinequizid'] = 'The offlinequiz id of the queue.';
$string['privacy:metadata:offlinequiz_queue:importuserid'] = 'The userid of the teacher who imported the files.';
$string['privacy:metadata:offlinequiz_queue:timecreated'] = 'The time this offlinequiz sheets were imported.';
$string['privacy:metadata:offlinequiz_queue:timestart'] = 'The time the evaluation of the queue was started.';
$string['privacy:metadata:offlinequiz_queue:timefinish'] = 'The time the evaluation of the queue was finished.';
$string['privacy:metadata:offlinequiz_queue:status'] = 'The status of the queue which is needed.';
$string['privacy:metadata:offlinequiz_queue_data:queueid'] = 'The queue this data belongs to.';
$string['privacy:metadata:offlinequiz_queue_data:filename'] = 'The filename of the file which this queue relates to.';
$string['privacy:metadata:offlinequiz_queue_data:status'] = 'The status of the queue data.';
$string['privacy:metadata:offlinequiz_queue_data:error'] = 'If the status is error, here will stand a more detailed error message.';
$string['privacy:metadata:offlinequiz_queue_data'] = 'This table saves data for the queue as every file in the queue will get a queue data object.';
$string['privacy:metadata:offlinequiz_results:offlinequizid'] = 'The offlinequiz, which this result belongs to.';
$string['privacy:metadata:offlinequiz_results:offlinegroupid'] = 'The offlinequiz group which this result belongs to.';
$string['privacy:metadata:offlinequiz_results:userid'] = 'The user which this result belongs to.';
$string['privacy:metadata:offlinequiz_results:sumgrades'] = 'The sum of all grades for this result.';
$string['privacy:metadata:offlinequiz_results:usageid'] = 'The template usage ID of the quiz API where this result is saved.';
$string['privacy:metadata:offlinequiz_results:teacherid'] = 'The teacher who uploaded the result.';
$string['privacy:metadata:offlinequiz_results:status'] = 'The status of the result (incomplete or complete).';
$string['privacy:metadata:offlinequiz_results:timestart'] = 'The beginning of the time the result was inserted the first time.';
$string['privacy:metadata:offlinequiz_results:timefinish'] = 'The end time the result was inserted for the first time.';
$string['privacy:metadata:offlinequiz_results:timemodified'] = 'The modify date for the result.';
$string['privacy:metadata:offlinequiz_results'] = 'This table saves all the result data, which is not storable in the quiz API.';
$string['privacy:metadata:offlinequiz_scanned_pages:offlinequizid'] = 'The offlinequiz of the scanned page.';
$string['privacy:metadata:offlinequiz_scanned_pages:resultid'] = 'The result relating to this page.';
$string['privacy:metadata:offlinequiz_scanned_pages:filename'] = 'The filename of the scanned page.';
$string['privacy:metadata:offlinequiz_scanned_pages:warningfilename'] = 'The filename of the file which is created when a wrongly filled in offlinequiz is corrected and the user gets a warning about that.';
$string['privacy:metadata:offlinequiz_scanned_pages:groupnumber'] = 'The groupnumber of the group this offlinequiz belongs to.';
$string['privacy:metadata:offlinequiz_scanned_pages:userkey'] = 'The userkey (not userid) of the crossed out user on the page.';
$string['privacy:metadata:offlinequiz_scanned_pages:pagenumber'] = 'The pagenumber of this page.';
$string['privacy:metadata:offlinequiz_scanned_pages:time'] = 'The time the page was processed.';
$string['privacy:metadata:offlinequiz_scanned_pages:status'] = 'The status of this page.';
$string['privacy:metadata:offlinequiz_scanned_pages:error'] = 'The detailed error this page has (if it exists).';
$string['privacy:metadata:offlinequiz_scanned_pages'] = 'The table saves information about a scanned page of an offlinequiz.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:offlinequizid'] = 'The offlinequiz this participants page belongs to.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:listnumber'] = 'The number of the list.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:filename'] = 'The name of the file for the scanned page.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:time'] = 'The time this page was processed.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:status'] = 'The status of this scanned page.';
$string['privacy:metadata:offlinequiz_scanned_p_pages:error'] = 'The error (if exists) that this page triggered while processing.';
$string['privacy:metadata:offlinequiz_scanned_p_pages'] = 'This table saves participant pages and their general information.';
$string['privacy:metadata:offlinequizpagesize'] = 'Sets the number of entries for the resultspage.';
$string['questionanalysis'] = 'Question analysis';
$string['questionanalysistitle'] = 'Question analysis table';
$string['questionandansweranalysis'] = 'Question and answer analysis';
$string['questionbankcontents'] = 'Question bank contents';
$string['questionforms'] = 'Question forms';
$string['questioninfoanswers'] = 'Number of correct answers';
$string['questioninfocorrectanswer'] = 'correct answer';
$string['questioninfocorrectanswers'] = 'correct answers';
$string['questioninfonone'] = 'Nothing';
$string['questioninfoqtype'] = 'Question type';
$string['questionname'] = 'Question name';
$string['questionpage'] = 'Page';
$string['questionsheet'] = 'Question sheet';
$string['questionsheetlatextemplate'] = '% !TEX encoding = UTF-8 Unicode
\documentclass[11pt,a4paper]{article}
\usepackage[utf8x]{inputenc}
\usepackage[T1]{fontenc}
\textwidth 16truecm
\textheight 23truecm
\setlength{\oddsidemargin}{0cm}
\setlength{\evensidemargin}{0cm}
\setlength{\topmargin}{-1cm}
\usepackage{amsmath} % for \implies etc
\usepackage{amsfonts} % for \mathbb etc
\usepackage[colorlinks=true,urlcolor=dunkelrot,linkcolor=black]{hyperref} % For using hyperlinks
\usepackage{ifthen}
\usepackage{enumitem}
\usepackage{xcolor}
\usepackage{ulem}
\parindent 0pt % no indent on the beginning of a section
\renewcommand\UrlFont{\sf}
\usepackage{lastpage}
\usepackage{fancyhdr}
\pagestyle{fancy}
\chead{\sc \TestTitle, Group \Group}
\cfoot{Seite \thepage/\pageref{LastPage}}
\makeatletter %%% disable pagebreaks between answers
\@beginparpenalty=10000
\@itempenalty=10000
\makeatother
%
\newcommand{\answerIs}[1]{} %%%Disable showing the right answer
% \newcommand{\answerIs}[1]{[#1]} %%%Enable showing the right answer
%%%


% ===========================================================================================================
%%% Course data:
\newcommand{\Group}{{$a->groupname}}
\newcommand{\Title}{{$a->activityname}}
%%% Or use {{$a->coursename} as course name instead
\newcommand{\Date}

\newcommand{\TestTitle}{%
\begin{center}
{\bf \Large Question sheet}\\\\[3mm]
\fbox{
\begin{tabular}{rl}
\rule{0pt}{25pt} Name: & $\underline{\hspace*{8cm}}$ \rule{20pt}{0pt}\\\\[5mm]
ID number: & $\underline{\hspace*{8cm}}$\\\\[5mm]
\ifthenelse{\equal{true}{{$a->printstudycodefield}}}{\rule{10pt}{0pt} Study code: & $\underline{\hspace*{8cm}}$\\\\[5mm]}{}
\rule[-20pt]{0pt}{20pt} Signature: & $\underline{\hspace*{8cm}}$
\end{tabular}}
\end{center}
}

\InputIfFileExists{offline_test_extras.tex}{}{} % Input extra user definitions

\begin{document}


% ===========================================================================================================
\TestTitle

% ===========================================================================================================


\bigskip
% ===========================================================================================================

{$a->pdfintrotext}

% ===========================================================================================================

\newpage

% ===========================================================================================================


{$a->latexforquestions}


\end{document}';
$string['questionsin'] = 'Questions in';
$string['questionsingroup'] = 'Group';
$string['questionsinthisofflinequiz'] = 'Questions in this offline quiz';
$string['questiontextisempty'] = '[Empty question text]';
$string['queueamountofpages'] = 'Amount of included files:';
$string['queuedownload'] = 'Download';
$string['queueheader'] = 'Uploaded files';
$string['queuefilecorrectionhovertext'] = 'Correction';
$string['queuefiledownloadhovertext'] = 'Download';
$string['queuefileerror'] = 'Error message:';
$string['queuefileimportedat'] = 'File imported: ';
$string['queuefileimportedby'] = 'Imported by:';
$string['queuefilecorrect'] = 'Correct';
$string['queuefilegroupnumber'] = 'Group number:';
$string['queuefilefilename'] = 'Origin file:';
$string['queuefileheader'] = 'Pages in this file';
$string['queuefilepagenumber'] = 'Page number:';
$string['queuefilestatus'] = 'File status:';
$string['queuefilestatusmessage_error'] = 'Correction necessary';
$string['queuefilestatusmessage_new'] = 'submitted';
$string['queuefilestatusmessage_processed'] = 'OK';
$string['queuefilestatusmessage_submitted'] = 'submitted';
$string['queuefilestatusmessage_processing'] = 'processing';
$string['queuefilenotstarted'] = 'Evaluation not started yet';
$string['queuefileprocessing'] = 'Evaluation in progress...';
$string['queuefilesactionsheader'] = 'Actions';
$string['queuefilesdetailsheader'] = 'Details';
$string['queuefilesfileheader'] = 'File';
$string['queuefilestatusheader'] = 'Status';
$string['queuefilestudent'] = 'Student name:';
$string['queuenotfinished'] = 'Evaluation not finished.';
$string['queuenotstarted'] = 'Evaluation not started.';
$string['queuestarttime'] = 'Evaluation started at:';
$string['queuefinishedtime'] = 'Evaluation finished at:';
$string['queuestatus'] = 'Queue status:';
$string['quizdate'] = 'Date of offline quiz';
$string['quizopenclose'] = 'Open and close dates';
$string['quizopenclose_help'] = 'The online review can be restricted to a specific time period. Click on "Show" to change the date and time settings.

**Caution:** If no time period is activated for the online review, the review is unrestricted as long as the offline quiz is visible to the participants.

In all cases, only the options ticked under "Review includes" are displayed.';
$string['qversioncannotupdate'] = 'Question version cannot be updated';
$string['qversionnumbersdiffer'] = 'The number of answers differ between the old and the new version. This would lead to inconsistencies.';
$string['qversionupdateerror'] = 'There was an error updating the question version.';
$string['qversionupdated'] = 'Question version updated';
$string['qversionupdatedwarning'] = 'Forms were already created.<br>If results exist they were regraded automatically to the current version of the changed question chosen here. In the documents the origin version of the question is still shown.';
$string['randomfromexistingcategory'] = 'Random question from an existing category';
$string['randomnumber'] = 'Number of random questions';
$string['randomquestionusinganewcategory'] = 'Random question using a new category';
$string['readjust'] = 'Readjust';
$string['reallydeletepdfs'] = 'Do you really want to delete the form files?';
$string['reallydeleteupdatepdf'] = 'Do you really want to delete and update the participants list?';
$string['recreatepdfs'] = 'Recreate PDFs';
$string['recordupdateanddocscreated'] = 'Questions were updated but as the forms are already created, the former version of the question is still active. To change to the newer version, please delete the forms and change the version manually.';
$string['recordupdateanddocscreatedversion'] = 'Questions were updated but the forms are already created. Please check, whether the changes did not ontroduce any changes that affect grading.';
$string['recurse'] = 'Include questions from subcategories too';
$string['refreshpreview'] = 'Refresh preview';
$string['regrade'] = 'Regrading';
$string['regradedisplayexplanation'] = '<b>Attention:</b> Regrading will not change marks that have been overwritten manually!';
$string['regradinginfo'] = 'If you change the score for a question, you must regrade the offlinequiz to update the participants results.';
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
$string['reportends'] = 'Review of results ends';
$string['reportoverview'] = 'Overview';
$string['reportstarts'] = 'Review of results starts';
$string['resetofflinequizzes'] = 'Reset Offline quiz data';
$string['results'] = 'Results';
$string['resultsavailable'] = 'Amount of available results: ';
$string['resultexists'] = 'Same result for {$a} already exists, import ignored';
$string['resultimport'] = 'Upload';
$string['reviewcloses'] = 'Review closes';
$string['reviewbefore'] = 'Allow review while offline quiz is open';
$string['reviewclosed'] = 'After the offline quiz is closed';
$string['reviewimmediately'] = 'Immediately after the attempt';
$string['reviewincludes'] = 'Review includes';
$string['reviewofresult'] = 'Review of result';
$string['reviewopens'] = 'Review opens';
$string['reviewoptions'] = 'Students may view';
$string['reviewoptionsheading'] = 'Review options';
$string['reviewoptions_help'] = 'With these options you can control what the students may see after the results were imported. You can also define start and end time for the results report.

The checkboxes mean:

**The attempt** - The text of the questions and answers will be shown to the students. They will see which answers they chose, but the correct answers will not be indicated.

**Whether correct** - This option can only be activated if the option "The attempt" is activated. If activated, the students can see which of the  chosen answers are correct (green background) or incorrect (red background).

**Marks** - The group (e.g. B), scores (achieved grade, total grade for questions, achieved in percent, e.g. 40/80 (50)) and the grade (e.g. 50 out of a maximum of 100) are displayed. Additionally, if "The attempt" is selected, the achieved score and the maximum score are shown for every question.

**Specific feedback** - Feedback that depens on what response the student gave.

**General feedback** - General feedback is shown to the student after the results were imported. Unlike specific feedback, which depends on the question type and what response the student gave, the same general feedback text is shown to all students. You can use the general feedback to give students a fully worked answer and perhaps a link to more information they can use if they did not understand the questions.

**Right Answers** - It is shown which answers are correct or wrong. This option is only available if "The attempt" is set.

**Scanned form** - The scanned answer forms are shown. Checked boxes are marked with green squares.

**Scanned form with grades** - The scanned answer forms are shown. Checked boxes are marked with green squares. Wrong marks and missing marks are highlighted. Additionally, a table shows the maximum grade and the achieved grade for every question.';
$string['review'] = 'Review';
$string['rimport'] = 'Upload';
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
$string['selectquestion'] = 'Select question {$a}';
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
$string['statisticsviewheader'] = '3. Statistics';
$string['statisticsplural'] = 'Statistics';
$string['statsoverview'] = 'Statistics overview';
$string['studycode'] = 'Study code';
$string['subplugintype_offlinequiz'] = 'Offlinequiz report';
$string['subplugintype_offlinequiz_plural'] = 'Offlinequiz reports';
$string['tabattendances'] = 'Attendances';
$string['tabattendancesoverview'] = 'Attendances';
$string['tabdownloadquizforms'] = 'Forms';
$string['tabdownloadparticipantsforms'] = 'Forms';
$string['tabeditgrades'] = 'Grades';
$string['tabeditgroupquestions'] = 'Questions';
$string['tabeditparticipants'] = 'Participants';
$string['tabofflinequizcontent'] = 'Preparation';
$string['tabofflinequizcorrect'] = 'Correction';
$string['tabofflinequizupload'] = 'Upload';
$string['tabparticipantscorrect'] = 'Correct';
$string['tabparticipantlists'] = 'Attendance lists';
$string['tabparticipantsupload'] = 'Upload';
$string['tabpreview'] = 'Forms';
$string['tabquestionandanswerstats'] = 'Question and answer analysis';
$string['tabquestionstats'] = 'Question analysis';
$string['tabresults'] = 'Results';
$string['tabresultsoverview'] = 'Results';
$string['tabstatistics'] = 'Statistics';
$string['tabstatsoverview'] = 'Quiz information';
$string['temporaryfiledeletiontask'] = 'Delete temporary files';
$string['theattempt'] = 'The attempt';
$string['timesup'] = 'Time is up!';
$string['totalmarksx'] = 'Total of marks: {$a}';
$string['totalpointsx'] = 'Total of marks: {$a}';
$string['totalquestionsinrandomqcategory'] = 'Total of {$a} questions in category.';
$string['trigger'] = 'lower/higher boundary';
$string['tutorial'] = 'Tutorial for offline quizzes';
$string['tutorial:feedback:1:1'] = 'There are no questions on the answer form. The answer form only contains the boxes for marking your answers in.';
$string['tutorial:feedback:1:2'] = 'The layout of the question sheet doesn\'t have to look exactly the same as the one in the screenshot. It is only to show you the questions and it is not scanned in after the examination.';
$string['tutorial:feedback:2:1'] = 'This cross is too pale. It is possible that it will not be scanned correctly.';
$string['tutorial:feedback:2:2'] = 'A cross of normal line width will always be interpreted correctly.';
$string['tutorial:feedback:2:3'] = 'A thick cross could be interpreted as having been corrected (filled in completely).';
$string['tutorial:feedback:2:4'] = 'An empty box will never be interpreted as a cross.';
$string['tutorial:feedback:2:5'] = 'A completely filled in box is interpreted exactly the same as an empty box.';
$string['tutorial:feedback:3:1'] = 'Only the crosses in the boxes are scanned. That is why it is so important to mark the right boxes under your identification number.';
$string['tutorial:feedback:3:2'] = 'Only the crosses in the boxes are scanned. That is why it is so important to mark the right boxes under your identification number.';
$string['tutorial:feedback:3:3'] = 'Only the crosses in the boxes are scanned. That is why it is so important to mark the right boxes under your identification number.';
$string['tutorial:feedback:3:4'] = 'Only the crosses in the boxes are scanned. That is why it is so important to mark the right boxes under your identification number.';
$string['tutorial:feedback:4:0'] = 'You have made a mistake. Your identification number is {$a->correctusernumber}, but you marked {$a->selectedusernumber}';
$string['tutorial:feedback:4:1'] = 'You have successfully completed this tutorial.';
$string['tutorial:feedback:correct'] = 'Correct!';
$string['tutorial:feedback:wrong'] = 'Wrong!';
$string['tutorial:generatedusernumberwarning'] = 'This is a generated identification number since your account doesn\'t have one!';
$string['tutorial:header:1'] = '1. Question sheets and answer forms';
$string['tutorial:header:2'] = '2. Crosses and corrections';
$string['tutorial:header:3'] = '3. The answer form';
$string['tutorial:header:4'] = '4. Marking your identification number';
$string['tutorial:header:5'] = '5. Finally...';
$string['tutorial:identification_info'] = 'Your identification number is: {$a}';
$string['tutorial:introduction:1'] = 'In the examination you will get two sheets: one with the questions and one with boxes to mark your answers on. The questions are numbered and so are the boxes on the answer form. Each answer is marked with a letter. After the examination, the answer form is scanned and interpreted by a computer.';
$string['tutorial:introduction:2:1'] = 'The boxes on the answer sheet are 5 millimetres wide. The boxes belonging to the same question are in one row. The number of the question is on the left hand side and the letter corresponding to the answer item is at the top of the column.';
$string['tutorial:introduction:2:2'] = 'Use a black or blue pen to mark the fields. If you want to correct a mark, fill in the box completely. This field will then be interpreted like an empty box.';
$string['tutorial:introduction:3:1'] = 'The answer form contains a block for your name (A), a block for your identification number (B) and a block for the answers (C). Fill in the boxes for your name, signature and identification number first. Note that the machine will not try to interpret your handwriting after the examination. These boxes are only for documentation purposes.';
$string['tutorial:introduction:3:2'] = 'Mark the boxes for your identification number carefully as the computer will need these marks to identify your results. Mistakes in the block with your identification number can lead to delays and misinterpretation.';
$string['tutorial:introduction:4'] = 'At the end of this tutorial we offer you a little test where you can learn how to fill in the marks for your identification number correctly. Under the box for your identification number is a matrix of boxes. Each column stands for one digit of your identification number. The rows mark the numbers written on the left and right hand side. Click on the boxes to mark them. Click again to delete your mark. If you want to start again, please <a href="javascript: document.location.reload()">reload the page</a>.';
$string['tutorial:introduction:5'] = 'Please keep in mind, that the answer forms are interpreted by a computer. This machine can\'t read anything you write beside the boxes. For the computer program anything outside the boxes is just a spot that prevents the machine from interpreting the form correctly. You punish yourself if you don\'t place the marks correctly.';
$string['tutorial:option:1:1'] = 'An answer form';
$string['tutorial:option:1:2'] = ' A question sheet';
$string['tutorial:option:3:1'] = 'The system reads your name using optical character recognition software.';
$string['tutorial:option:3:2'] = 'Your signature is scanned using software used for finger prints in criminal investigations.';
$string['tutorial:option:3:3'] = 'Only the crosses in the boxes under your identification number are interpreted.';
$string['tutorial:option:3:4'] = 'The system reads your identification number using optical character recognition software.';
$string['tutorial:question:1'] = 'This image shows:';
$string['tutorial:question:2'] = 'Which mark will be interpreted as a cross for sure?';
$string['tutorial:question:3'] = 'How does the computer assign the answer form to the right person?';
$string['tutorial:question:4'] = 'Identification Number';
$string['tutorial:question:5'] = 'Good luck!';
$string['tutorial:questionsheet:1'] = '1)&nbsp;&nbsp; How many offlinequiz documents will be given to you during an exam?';
$string['tutorial:questionsheet:1:1'] = 'a)&nbsp;&nbsp;0';
$string['tutorial:questionsheet:1:2'] = 'b)&nbsp;&nbsp;1';
$string['tutorial:questionsheet:1:3'] = 'c)&nbsp;&nbsp;2';
$string['tutorial:questionsheet:1:4'] = 'd)&nbsp;&nbsp;3';
$string['tutorial:questionsheet:1:5'] = 'e)&nbsp;&nbsp;4';
$string['tutorial:questionsheet:1:marks'] = '(Points: 1)';
$string['tutorial:questionsheet:2'] = '2)&nbsp;&nbsp; What content is available on a questionnaire of the offlinequiz?';
$string['tutorial:questionsheet:2:1'] = 'a)&nbsp;&nbsp;Questions';
$string['tutorial:questionsheet:2:2'] = 'b)&nbsp;&nbsp;Answer options';
$string['tutorial:questionsheet:2:3'] = 'c)&nbsp;&nbsp;Correct answers';
$string['tutorial:questionsheet:2:4'] = 'd)&nbsp;&nbsp;Further information';
$string['tutorial:questionsheet:2:5'] = 'e)&nbsp;&nbsp;None of them';
$string['tutorial:questionsheet:2:marks'] = '(Marks: 1)';
$string['tutorial:start_text'] = 'This tutorial is supposed to show you the correct way of filling out the forms of an offline quiz and marking your answers for the automatic evaluation. We highly recommend you to carry out the tutorial at least once, before having your first test or exam with an offline quiz.';
$string['tutorial:starttutorial'] = 'Start tutorial';
$string['tutorial:title'] = 'Tutorial';
$string['tutorial:title:navigation'] = 'Navigation';
$string['type'] = 'Type';
$string['uncheckparts'] = 'Mark selected participants as absent';
$string['unknownmimetype'] = 'Unknown or unsupported mimetype {$a->mimetpye}';
$string['updatedsumgrades'] = 'The sum of all grades of group {$a->letter} was re-calculated to {$a->grade}.';
$string['upgradingfilenames'] = 'Upgrading filenames of documents: offline quiz {$a->done}/{$a->outof} (Offline quiz ID {$a->info})';
$string['upgradingofflinequizattempts'] = 'Upgrading offline quiz attempts: offline quiz {$a->done}/{$a->outof} <br/>(Offline quiz ID {$a->info})';
$string['upgradingilogs'] = 'Reset Offline quiz data';
$string['upload'] = 'Upload';
$string['uploadfurtherfiles'] = 'Upload further files';
$string['uploadpart'] = 'Upload';
$string['uppertrigger'] = 'Higher second boundary';
$string['uppertriggerzero'] = 'Higher second boundary is zero';
$string['upperwarning'] = 'Higher first boundary';
$string['upperwarningzero'] = 'Higher first boundary is zero';
$string['useradded'] = 'User {$a} added';
$string['userdoesnotexist'] = 'User {$a} does not exist in system';
$string['useridentification'] = 'User identification';
$string['useridviolation'] = 'Multiple users found';
$string['userimported'] = 'User {$a} imported and graded';
$string['usernotincourse'] = 'User {$a} not in course.';
$string['userpageimported'] = 'Single page imported for user {$a}';
$string['usernotinlist'] = 'User not registered in list!';
$string['usernotregistered'] = 'User {$a} not registered in course';
$string['usetabs'] = 'Use tabs-based navigation';
$string['usetabsdesc'] = 'If enabled, the offline quiz third-level navigation will be displayed using tabs. If disabled, the offline quiz navigation will be displayed using a dropdown menu.';
$string['valuezero'] = 'Value should not be zero';
$string['viewresults'] = 'View results';
$string['white'] = 'White';
$string['wirismathenabled'] = 'Enable WIRIS Math equations';
$string['wirismathenabled_help'] = 'If enabled, WIRIS Math equations can be used in the question text and answers.';
$string['withselected'] = 'With selected...';
$string['zipfile'] = 'ZIP file';
$string['zipok'] = 'ZIP file imported';
$string['zerogradewarning'] = 'Warning: Your offline quiz grade is 0.0!';
