// This file is for Moodle - http://moodle.org/
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
 * README.txt
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/
 
I. Summary 

The Moodle module offlinequiz adds paper-and-pencil quizzes to Moodle
Versions 2.x+. In offline quizzes students mark answers to Moodle
questions on a sheet of paper (the answer form). The students' answer
forms are evaluated and graded automatically by the offlinequiz module.

More precisely, a complete offline quiz consists (at least) of the following steps :
 1. A teacher creates an offline quiz in Moodle. This is very similar to creating online quizzes (standard Moodle quizzes).
 2. The teacher creates question sheets and answer forms as PDF (DOCX) documents using the module.
 3. The question sheets and answer forms are handed out to students for the actual quiz. The students mark the answers they think are correct in the answer form.
 4. The teacher scans the filled-in answer forms and uploads the resulting images into the offline quiz.
    The scanned answer forms are evaluated and graded automatically by the module. 
 5. If necessary, the teacher corrects errors that might have occurred due to mistakes made by the students or due to bad scan quality. 
 
After results have been created in an offlinequiz, students can review
their result as usual. If the teacher allows it, students can also see
the scanned answer forms and which markings have been recognised as
crosses.
 
The module supports up to six groups which are not related to Moodle
course groups. Each group can contain a different set of questions in a
different order. Separate question sheets and answer forms are created
for the different offlinequiz groups.

The module also supports lists of participants which are useful for
checking which students actually took part in the exam.  Lists of
participants are pre-filled with students in Moodle. PDF versions of
those lists can be created in the module for easy marking during the
exam. The marked lists can be uploaded and evaluated automatically.

The offline quiz module is used intensively at different Austrian
Universities for mass exams. Hundreds of students can be easily examined
at the same time (given enough seating space in lecture halls) without the need
for expensive e-testing equipment.

II. Installation

  The module is an activity module and has to be installed in the directory 
  
  <your moodle root dir>/mod/offlinequiz 
  

III. Cronjob 

   For the evaluation of answer forms an additional cronjob has to be installed. This should look similar to the following
   
     */10 * * * * DATE=`date +\%Y\%m\%d`; php <your moodle root dir>/mod/offlinequiz/cron.php --cli=1 >> /var/log/moodle/cron-olq.log.$DATE 2>&1
     
   but has to be adjusted to your environment. Since the evaluation of answer forms usually takes a lot of system resources, it
   is recommended to run this cronjob on a separate application server to take load from the frontend servers.
   
IV. Website settings 

   In the website admin settings for the module   

         Site Administration -> Plugins -> Activity modules -> Offline Quiz

   One can choose the default settings for the module and also determine the University Logo that will appear on the top 
   of the answer forms (Logo URL).
 
   The user identification has to be set to a formula describing how the user IDs 
   can be retrieved from the digits marked by the students on the answer forms.

   For example:
 
     A user identification formula   
        a[7]=username
     means that the students mark a 7 digit number on the answer form. A concatenation of the letter 'a' and that number 
     denotes the 'username' of the user in Moodle's 'user' table.
 
     A formula 
        b[5]cd=idnumber 
     means that the students mark a 5 digit number on the answer form. A concatenation of the letter 'b', the marked number, 
     and the string 'cd' denotes the 'idnumber' of the user in Moodle's 'user' table.
     
V. Scanning of answer forms

     Answer forms should be scanned as black-and-white images with 200 - 300 dpi. Do not scan in greyscale! 
     Supported file types are TIF, PNG and GIF.
     
V. Contact
   In case you have any questions regarding this module you can either contact the author of the module (zimmerj7@univie.ac.at)
