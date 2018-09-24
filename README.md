Offline Quiz Module
===================

This file is part of the mod_offlinequiz plugin for Moodle - <http://moodle.org/>

*Author:*    Thomas Wedekind, Juergen Zimmer, Richard Rode, Alexander Heher

*Copyright:* 2014 [Academic Moodle Cooperation](http://www.academic-moodle-cooperation.org)

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

The MC Offline Quiz module adds paper-and-pencil multiple-choice quizzes to Moodle. In offline
quizzes students mark answers to questions on a sheet of paper (the answer form). The students'
answer forms are evaluated and graded automatically by the offline quiz module.

More precisely, a complete offline quiz consists (at least) of the following steps:

* A teacher creates an offline quiz in Moodle and adds multiple-choice questions, all-or-nothing
  multiple-choice questions or description questions (text) to the quiz. This is very similar to
  creating online quizzes (standard Moodle quizzes).

* From the question lists the teacher creates question sheets and answer forms as PDF (DOCX, LaTeX)
  documents using the module.

* The question sheets and answer forms are handed out to students for the actual quiz. The students
  mark the answers they think are correct in the answer form.

* The teacher scans the filled-in answer forms and uploads the resulting images into the offline
  quiz. The scanned answer forms are evaluated and graded automatically by the module.

* If necessary, the teacher corrects errors that might have occurred due to mistakes made by the
  students or due to bad scan quality.

After results have been created in an offline quiz, students can review their result as usual. If
the teacher allows it, students can also see the scanned answer forms and which markings have been
recognised as crosses.

The module supports up to six groups which are not related to Moodle course groups. Each group can
contain a different set of questions in a different order. Separate question sheets and answer
forms are created for the different offline quiz groups.

The module also supports lists of participants which are useful for checking which students
actually took part in the exam. Lists of participants are pre-filled with students in Moodle. PDF
versions of those lists can be created in the module for easy marking during the exam. The marked
lists can be uploaded and evaluated automatically.


Example
-------

The Offline Quiz module is used intensively at different Austrian universities for mass exams.
Hundreds of students can be easily examined at the same time (given enough seating space in lecture
halls) without the need for expensive e-testing equipment.


Requirements
------------

The plugin is available for Moodle 2.5+. This version is for Moodle 3.5.


Installation
------------

* Copy the module code directly to the mod/offlinequiz directory.

* Log into Moodle as administrator.

* Open the administration area (http://your-moodle-site/admin) to start the installation
  automatically.


Cron Job
--------

The plugin uses a cron job for evaluating the answer forms. If you didn't configure the offline-quiz
cron job, the automated analysis of answer forms is not going work! Information about how to
configure cron jobs can be found at https://docs.moodle.org/en/Cron

Before Version 3.2 there was an additional cron job required. This cron job is no longer necessary,
unless you intend to run the cron job on a separate server.

Since the evaluation of answer forms usually takes a lot of system resources, it is recommended to
run this cron job on a separate application server to take load from the frontend servers.

If you want to run the cron job on a dedicated server you have to disable it in the moodle settings
and create an additional job on the dedicated server looking like this:

    */10 * * * * DATE=`date +\%Y\%m\%d`; php <your moodle root dir>/mod/offlinequiz/cron.php --cli=1 >> /var/log/moodle/cron-olq.log.$DATE 2>&1


Admin Settings
--------------

In the website admin settings for the module

_Site Administration -> Plugins -> Activity modules -> Offline Quiz_

One can choose the default settings for the module and also determine the University Logo that will
appear on the top of the answer forms:

* formula for participant identification (text field)
* mix questions (checkbox)
* mix answers (checkbox)
* logo URL (text field)
* copyright indication (checkbox)
* settings for exam inspection (checkbox)
* decimal places (drop down)
* paper's white level (drop down)
* 1-click inscription (checkbox)
* role for inscription (drop down)
* saving days (text field)

The user identification has to be set to a formula describing how the user IDs
can be retrieved from the digits marked by the students on the answer forms. For example:

A user identification formula

    a[7]=username

means that the students mark a 7 digit number on the answer form. A concatenation of the letter 'a'
and that number denotes the 'username' of the user in Moodle's 'user' table.

A formula

    b[5]cd=idnumber

means that the students mark a 5 digit number on the answer form. A concatenation of the letter
'b', the marked number, and the string 'cd' denotes the 'idnumber' of the user in Moodle's 'user'
table.


Scanning of Answer Forms
------------------------

Answer forms should be scanned as black-and-white images with 200 - 300 dpi. Do not scan in
greyscale! Supported file types are TIF, PNG and GIF.


Documentation
-------------

You can find a cheat sheet for the plugin on the [AMC
website](http://www.academic-moodle-cooperation.org/en/modules/offline-quiz/) and a video tutorial
in German only in the [AMC YouTube Channel](https://www.youtube.com/c/AMCAcademicMoodleCooperation).


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
