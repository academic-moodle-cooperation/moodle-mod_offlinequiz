CHANGELOG
=========
4.5.1
-----------------
* [Bug] Fixed that adding questions was not possible, introduced through moodle core 4.5.2

4.5.0
-----------------
* [Feature] 4.5 compatible version

4.4.0
-----------------
* [Feature] 4.4 compatible version with new logo using the new logo style
* [Feature] Rework on the tutorial so it is translateable in AMOS now
* [Feature] Manual regrading removed, since it's done automatically
* [Feature] New setting for PDF font style. Warning for admins: The default font has changed - from (Free-)Sans - but can be (re-)set in the admin settings.
* [Feature] New admin setting for default font size [github #254]
* [Feature] Students will be sent immediately to either the tutorial or their result depending on the status of the offline quiz
* [Feature] Rework on the activity overview page: attendances now available in new style
* [Feature] Report subplugins can now add themselves to the navigation by implementing a offlinequiz_${pluginname}_report->add_to_tabs() function. [github PR #219 - juacas]
* [Info] You might want to checkout a new subplugin for prefilled forms support: https://github.com/juacas/moodle-offlinequiz_identified. Since this plugin is developed by the community, we cannot provide support for it.
* [Bug] Fixed PHP 8.2 warnings
* [Bug] Fixed bug where a question could be added twice if an older version was already added
* [Bug] Fixed encoding issues concerned with uploaded files
* [Bug] Fixed bug in the document creation after changing the question version

4.3.3
-----------------
[Bug] Fixed a version upgrade path in Offlinequiz 4.1.5 which lead to offlinequiz not working anymore.

4.3.2
-----------------
* [Bug] Fixed some more statistics errors [github #245]
* [Bug] Fixed some bugs regarding the filter for adding questions from question bank
* [Bug] Fixed that the question version selection in restored offlinequizzes was not correct
* [Bug] Fixed that some data was not deleted correctly when deleting a course/offlinequiz activity [github #230]
* [Bug] Fixed that user_get_participants was used in the privacy provider which doesn't exist anymore [github #242]
* [Bug] Fixed deleting offlinequizzes resulted in questions still in use [github #165]

4.3.1
-----------------
* [Bug] Fixed some statistics that weren't displayed correctly
* [Bug] Fixed bug with adding and deleting questions in Group B to F
* [Bug] Fixed bug with adding questions randomly
* [Bug] Fixed some bugs with the filter when adding questions from the question bank

4.3.0
-----------------
* [Feature] Compatible Version for Moodle 4.03
* [Feature] Support questions from global context and use the question add popup like in quiz
* [Feature] Support for up to 10 digits for the user identification [github #213]
* [Feature] User table field "id" and fields with numbers (phone1,phone2) are now officially supported for user identification [github #181 and #204/ github pull request #207/ #208 @mhughes2k]
* [Bug] Fixed wrong amount of tests with errors/results displayed on the overview page
* [Bug] Fixed bug for "<" and ">" in plain text fields [github #221]
* [Bug] Fixed bug to manually mark participants as present/not present [github #173]
* [Bug] Fixed statistics to work with big offlinequizzes with many users even on big moodle instances
* [Bug] Fixed bug with divide by zero error in statistics if nobody chose the answer of a question
* [Bug] Fixed bug with error message when no results where chosen to delete
* [Bug] Fixed image out of bounds error for skew answer sheets
* [Bug] Fixed some filename errors under windows
* [Bug] Fixed that page not displayed in course block Activities if there are offlinequizzes in multiple sections [github #222]
* [Bug] Fixed incorrect overview displayed under oracle databases [github #211]
* [Bug] Fixed statistics under oracle databases [github #146]
* [Bug] Fixed bug in line 155 index.php [github #222]
* [Bug] Fixed bug with url enconding [github pull request #188 @jboulen]
* [Bug] Fixed that deleting groups has no effect in overview
* [Bug] Grades and Gradebook update now correctly after question version is changed 

4.2.0
-----------------
**WARNING:** THIS UPDATE MAY TAKE SOME TIME ON LARGE INSTANCES! For more information see https://github.com/academic-moodle-cooperation/moodle-mod_offlinequiz/issues/220

* [Feature] Compatible Version for Moodle 4.02
* [Feature] Regrading now works automatically. It is still possible to regrade on request in case of an error.
* [Feature] Using newer PDFWord version compatible with PHP 8.2+
* [Feature] Better feedback on changing question versions.
* [Bug] Fixed missing question references so you can't delete questions that are still in use.
* [Bug] Fixed some bugs regarding participants lists with multiple pages and multiple groups
* [Bug] Fixed that questions were not randomized even if shuffle questions was turned on.
* [Bug] Fixed a bug that new versions were not added to all groups.
* [Bug] Fixed a bug, that question versions were displayed wrong.
* [Bug] Fixed an error in the preview caused changing question versions

4.1.0
-----------------
* [Feature] Compatible Version for Moodle 4.01
* [Feature] On the overview page you can see the progress of what's done.

4.0.1
-----------------
* [Bug] Fixed that offlinequiz was not compatible with Moodle 4.0.0 to 4.0.3

4.0.0
-----------------
* [Feature] Compatible Version for Moodle 4.0
* [Feature] It is now possible to change the question version, even after the documents are created
* [Feature] New activity overview page when coming in to offlinequiz
* [Feature] changing a question directly in offlinequiz results in a recalculation of all the grades in that group
* [Feature] sub-navigation changed so it fits to the new moodle standard

3.11.4
-----------------
* [Bug] Fix PHP 8.0 incompabilities
* [Bug] Date of test used instead of review start used for calendar
* [Bug] Wrong language string for "group" was used in all questionsheets (pdf, LaTeX and docx)

3.11.3
-----------------
* [Bug] Fixed a bug that special html characters would break docx documents

3.11.2
-----------------
* [Feature] Autocompletion rules from quiz: grade and passing grade as well as view have been added.
* [Bug] Fix privacy provider if there are multiple users in an offlinequiz context. [github pull #157 @micaherne]
* [Bug] Heading consistency: Every page has a heading before the tabs.
* [Feature] Question sheet: Latex question sheet now shows activityname instead of coursename, both variables are still possible.
* [Bug] CLI execution on another server than the cron server is now possible again.
* [Bug] Non-breaking spaces are now possible to use in docx documents.
* [Bug] Removed deprecation message on the statistics page.
* [Bug] The edit page for the questions looks much nicer and without any strange artifacts.

3.11.1
-----------------
* [Feature] Solve version confusion with 3.11.0 and 3.10.1

3.11.0
-----------------
* [Feature] Compatible Version for Moodle 3.11

3.10.1
-----------------
* [Bug] Fix privacy bug for people using integer-idnumber

3.10.0
-----------------
* [Feature] Compatible Version for Moodle 3.10

3.9.0
-----------------
* [Feature] Support for all kinds of special characters in pdf/docx/LaTeX documents.
* [Feature] Deleted table offlinequiz_attempts, which wasn't used since moodle/offlinequiz 2.0.
* [Feature] Support of superscript and subscript in docx documents.
* [Feature] Support of "<" and ">" in LaTeX documents.
* [Bug] Changed css so it doesn't interfere with quiz anymore.
* [Feature] Offlinequiz now recognizes, if there are multiple users with a given idnumber.
          It will choose the one in the course. Otherwise it will throw an error.
* [Feature] Rewrote some of the privacy lang strings to make them more clear.


3.8.2
-----------------
* [Bug] Fixed a long existing bug with offlinequiz not using the right temp folder which leads to errors when setting localcache-directory in config.php.

3.8.1
-----------------
* [Bug] Fixed a bug that not all questions were shown in question statistics

3.8.0
-----------------
* [Feature] Moodle 3.8 compatible Version
* [Feature] Updated PDFWord version to 0.17.0


3.7.0
-----------------
* [Feature] Moodle 3.7 compatible Version
* [Feature] #6046 changed the place of reviewoptions to be more accurate
* [Bug] #6166 deletion of hotspots and old temp-files has not been done, now done by an own task
* [Feature] #6038 export results as printable html for face to face review
* [Feature] #6125 support of the plugin editdates
* [Bug] #6047 question tagging now works properly as in quiz
* [Feature] #4375 extended information about review options in offlinequiz
* [Bug] #6054, github issue #79: fixed that only students could be enroled into courses
* [Bug] #6115, github issue #81: fixed a couple of problems with oracle databases.


3.6.2 (2019-02-28)
-----------------
* added information about imagemagick in the README.md
* fixed broken image links in 'show student view' popup
* fixed a performance bug for upgrading big moodle-instances to 3.6.0. If you already upgraded to 3.6.0 this fix will not affect you at all.
* fixed a spacing issue in the statistics tab for choosing groups
* fixed a missing langstring ('questionpage')

3.6.1 (2019-02-11)
-----------------
* fixed a wrong hardcoded prefix in the offlinequiz plugin
* fixed an upgrade-query not working on certain databases
* added the userlist_provider feature

3.6.0 (2019-01-29)
-----------------
* Moodle 3.6 compatible Version
* added an experimental way to evaluate answer sheets. This is the new way, but don't use it in production systems
* Icon for adding questions was not visible.
* Privacy-API changes for moodle 3.6
* return to default group if chosen in the statistics menu
* fixed an evaluation error for big zip file uploads

3.5.3 (2018-11-29)
------------------
* Bugfix: Questions can not be sorted

3.5.2 (2018-11-29)
------------------
* Fixed a bug, where the update of scanned pages lead to wrong results with "missing pages", even though there is only one page in the test

3.5.1 (2018-11-27)
------------------
* fixed a bug where under certain circumstances at the result download the grade was not correct.

3.5.0 (2018-08-13)
------------------
* Moodle 3.5 compatible version
* introduced privacy API
* partial results are displayed by default in the result table
* added possibility to upload pdf files

3.4.2 (2018-04-10)
------------------
* changed the way how LaTeX-Question sheets work. It should now support almost every question that is written in the moodle editor
* export of results now has the column "letter"
* fixed an error for the calender-plugin where the report-appointment was displayed for all the days

3.4.1 (2018-01-17)
------------------
* fixed an error, where under certain condition the field id_digits was missing.

3.4.0 (2017-11-16)
-------------------
* latex-document supports double-$ as begin-tag for formulas in new line
* latex-document supports underline, bold and italic texts
* new option for alignment of latex-questions in pdf-files

3.3.2 (2017-11-06)
-------------------
* additional index for tables page_corners and scanned_page

3.3.0 (2017-08-03)
------------------
* Moodle 3.3 compatible version
* Official support of boost Theme
* Offlinequizzes are now searchable through the moodle search
* Multiple little bugfixes 

3.2.0 (2017-02-01)
------------------

* Moodle 3.2 compatible version

3.1 (2016-07-23)
----------------

* First release for Moodle 3.1


3.0 (2016-05-18)
----------------

* First release for Moodle 3.0


2.9 (2015-11-20)
----------------

* First release for Moodle 2.9


2.8 (2015-06-09)
----------------

* First release for Moodle 2.8


2.7 (2014-09-23)
----------------

* First release for Moodle 2.7


2.6 (2014-03-31)
----------------

* First release for Moodle 2.6
