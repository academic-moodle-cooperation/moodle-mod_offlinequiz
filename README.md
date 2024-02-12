Offline Quiz 
===================

This file is part of the mod_offlinequiz plugin for Moodle - <http://moodle.org/>

*Author:*    Thomas Wedekind, Juergen Zimmer, Richard Rode, Alexander Heher, Adrian Czermak

*Copyright:* [Academic Moodle Cooperation](http://www.academic-moodle-cooperation.org)

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

The Offline Quiz activity allows the creation of multiple choice tests with questions from the question bank of a Moodle course, which are handed out to students in printed form. After completion, the answer forms are scanned and can be automatically evaluated online directly in Moodle. 


Usage
-------

Teachers want to conduct the final examination of a course with several hundred students in a lecture hall.
For this purpose, an offline quiz is created in a Moodle course and the quiz is filled with questions from the question bank. To make copying more difficult, the lecturers decide to create several groups in the offline quiz that contain different questions. The forms are then generated, downloaded and printed out.

Two forms are handed out to students during the exam: the questionnaire (contains the questions and answer options) and the corresponding answer sheet (form with boxes to tick the selected answers). After the exam, all answer sheets are scanned and uploaded in the offline quiz of the Moodle course for evaluation.
Forms that could not be automatically evaluated by the system, e.g. because the ID number was incorrectly ticked or ticks or crossed out ticks are unclear, must be corrected by the teachers in the offline quiz. 
Teachers can now allow students to view their exams online, where they can, for example, see the points they have achieved and check that their ticks have been recognized.

*Note:* The question types multiple choice, description and all-or-nothing multiple choice are currently supported. Questionnaires can be downloaded in PDF, DOCX or LaTeX format.


Requirements
------------

* You need to have imagemagick and the relating php module (http://pecl.php.net/package/imagick) installed. It is used for converting the uploaded answer sheets. If you have problems that not all pdf pages are recognized try to increase the memory limit of imagemagick which can be found in the policy.xml (in linux based systems in /etc/ImageMagick-${version}/policy.xml).

* If you want to use LaTeX formulas in the questions it is necessary to have LaTeX installed.
See https://www.latex-project.org/get/ for more information how to install it.
You can set your latex path in the admin settings at:
*Website administration -> Plugins -> Filters -> Manage filters -> TeX Notation -> Settings*.
Everything should work if the "pathconvert" has a tick symbol.

* The plugin uses a cron job for evaluating the answer forms. If you didn't configure the offline-quiz
cron job, the automated analysis of answer forms is not going work! Information about how to
configure cron jobs can be found at https://docs.moodle.org/en/Cron

* The admin setting *useridentification* has to be set to a formula describing how the user IDs
can be retrieved from the digits marked by the students on the answer forms. Details can be found at the admin settings description.


Installation
------------

* Copy the code directly to the mod/offlinequiz directory.

* Log into Moodle as administrator.

* Open the administration area (http://your-moodle-site/admin) to start the installation
  automatically.

Furthermore you can use the report [Offline Quiz Cronjob Admin](https://moodle.org/plugins/report_offlinequizcron), which adds an interface to the Offline Quiz activity to inspect and change pending cronjobs. 


Privacy API
------------------------

The plugin fully implements the Moodle Privacy API.


Documentation
-------------

You can find a documentation for the plugin on the [AMC website](https://academic-moodle-cooperation.org/mod_offlinequiz/).


Bug Reports / Support
---------------------

We try our best to deliver bug-free plugins, but we cannot test the plugin for every platform,
database, PHP and Moodle version. If you find any bug please report it on
[GitHub](https://github.com/academic-moodle-cooperation/moodle-mod_offlinequiz/issues). Please
provide a detailed bug description, including the plugin and Moodle version and, if applicable, a
screenshot.

You may also file a request for enhancement on GitHub. If we consider the request generally useful
and if it can be implemented with reasonable effort we might implement it in a future version.

You may also post general questions on the plugin on GitHub, but note that we do not have the
resources to provide detailed support.


License
-------

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

The plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License with Moodle. If not, see
<http://www.gnu.org/licenses/>.


Good luck and have fun!
