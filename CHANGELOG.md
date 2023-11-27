CHANGELOG
=========
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
