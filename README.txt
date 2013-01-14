I. Summary 

The offlinequiz module adds the possibility of paper-based offline quizzes to Moodle 2.x.

A complete offline quiz consists (at least) of the following steps :
 1. A teacher edits lists of questions similar to the lists of questions in online quizzes (standard Moodle quizzes).
 2. The teacher creates question sheets and answer forms as PDF documents using the module. 
 3. The question sheets and answer forms are handed out to students for the actual quiz.
 4. The teacher scans the filled-in answer forms and uploads the resulting images into the appropriate offline quiz.
 5. If necessary, the teacher corrects errors that might have occurred due to mistakes made by the students or due to bad scan quality. 
 
After results have been created in an offlinequiz, students can review their result as usual. If the teacher allows it, students 
can also see the scanned answer forms and which markings have been recognised as crosses. 
 
The module supports up to six groups which are not related to Moodle course groups. Each group can contain a different
set of questions in a different order. Different question sheets and answer forms are created for each group.

The module also supports lists of participants which are useful for checking which students actually took part in the exam.
Lists of participants are filled with students in Moodle. PDF versions of those lists can be created in the module for
easy marking during the exam. The marked lists can be uploaded and evaluated automatically. 


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
     means that the students mark a 5 digit number on the answer form. A concatenation of the letter 'a', the marked number, 
     and the string 'cd' denotes the 'idnumber' of the user in Moodle's 'user' table.
     
V. Contact
   In case you have any questions regarding this module you can either contact the author of the module (zimmerj7@univie.ac.at) or the 
   e-learning support of the University of Vienna (e-support.zid@univie.ac.at).
