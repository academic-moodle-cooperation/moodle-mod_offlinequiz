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
 * @package        mod
 * @subpackage    offlinequiz
 * @author        Staff AulaWeb <staff@aulaweb.unige.it>
 * @copyright     2018 Università degli Studi di Genova {@link https://unige.it}
 * @since         Moodle 3.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

$string['modulename'] = 'Quiz offline';
$string['modulenameplural'] = 'Quiz offline';
$string['pluginname'] = 'Offline Quiz';

$string['addnewquestion'] = 'aggiungi domanda';
$string['add'] = 'Aggiungi';
$string['addlist'] = 'Aggiungi lista';
$string['addarandomselectedquestion'] = 'Aggiungi una domanda scelta casualmente...';
$string['addnewpagesafterselected'] = 'Aggiungi un salto pagina dopo le domande selezionate';
$string['addnewquestionsqbank'] = 'Aggiungi domande alla categoria {$a->catname}: {$a->link}';
$string['addnewuseroverride'] = 'Permetti all\'utente di sovrascrivere';
$string['addpagebreak'] = 'Inserisci salto pagina';
$string['addpagehere'] = 'Aggiungi pagina qui';
$string['addparts'] = 'Aggiungi partecipanti';
$string['addquestionfrombankatend'] = 'Aggiungi in fondo dal deposito delle domande';
$string['addquestionfrombanktopage'] = 'Aggiungi alla pagin dal {$a} deposito delle domande';
$string['addrandom'] = 'Aggiungi casualmente {$a} domande a risposta multipla';
$string['addrandomfromcategory'] = 'domande casuali';
$string['addrandomquestion'] = 'domande casuali';
$string['addrandomquestiontoofflinequiz'] = 'Aggiungi domande al quiz offline {$a->name} (group {$a->group})';
$string['addrandomquestiontopage'] = 'Aggiungi una domanda casuale alla pagina {$a}';
$string['addarandomquestion'] = 'domande casuali';
$string['addarandomquestion_help'] = 'Moodle aggiungerà una selezione casuale di domande a risposta multipla (o domande tutte-o-nessuna a scelta multipla) al gruppo corrente del quiz offline. Puoi impostare il numero delle domande da aggiungere. Le domande sono scelte dall categoria corrente (e dalle relative sottocategorie, se selezinate).';
$string['addtoofflinequiz'] = 'Aggiungi al quiz offline';
$string['addtoqueue'] = 'Accoda';
$string['allinone'] = 'Illimitato';
$string['alllists'] = 'Tutte le liste';
$string['allornothing'] = 'Tutto-o-nulla';
$string['allresults'] = 'Mostra tutti i risultati';
$string['allstudents'] = 'Mostra tutti gli studenti';
$string['alwaysavailable'] = 'Sempre disponibile';
$string['analysis'] = 'Analisi elemento';
$string['answerformforgroup'] = 'Modulo risposte per il gruppo {$a}';
$string['answerform'] = 'Modulo risposte';
$string['answerforms'] = 'Moduli domande';
$string['answerpdfxy'] = 'Modulo risposte ({$a}->maxquestions questions / {$a}->maxanswers options)';
$string['areyousureremoveselected'] = 'Sicuro di voler eliminare tutte le domande selezionate?';
$string['attemptexists'] = 'Tentativo esistente';
$string['attemptsexist'] = 'Non puoi più aggiungere o eliminare domande.';
$string['attemptsnum'] = 'Risultati: {$a}';
$string['attemptsonly'] = 'Mostra solo gli studenti valutati';
$string['attendances'] = 'Presenti';
$string['basicideasofofflinequiz'] = 'Le idee alla base della creazione di un quiz offline';
$string['bulksavegrades'] = 'Salva valutazioni';
$string['calibratescanner'] = 'Calibra scanner';
$string['cannoteditafterattempts'] = 'Non puoi più aggiungere o eliminare domande preché ci sono già delle valutazioni. ({$a})';
$string['category'] = 'Categoria';
$string['changed'] = 'I risultati sono stati cambiati.';
$string['checkparts'] = 'Segna come presenti gli iscritti selezionti';
$string['checkuserid'] = 'Verifica ID gruppo/utente';
$string['chooseagroup'] = 'Scegli un gruppo...';
$string['closebeforeopen'] = 'Il quiz offline non può essere aggiornato. La data di chiusura impostata è antecedente quella di apertura';
$string['closestudentview'] = 'Chiudi vista studente';
$string['closewindow'] = 'Chiudi finestra';
$string['cmmissing'] = 'Il modulo corso per il quiz offline con ID {$a} non è disponibile';
$string['configdisableimgnewlines'] = 'Quest\'opzione disabilita gli a-capo prima  dopo le immagini nel foglio domande. Attenzione: può causare dei problemi con la formattazione';
$string['configintro'] = 'I valori ipostati in questo pannello verranno usati come default nei nuovi quiz offline';
$string['configkeepfilesfordays'] = 'Definisce quanti giorni le file delle immagini caricati sono mantenuti nello storage temporaneo. In questo periodo le immagini saranno disponibili nel report dell\'amministratore del quiz offline.';
$string['configonlylocalcategories'] = 'Non sono permesse categorie di domande condivise.';
$string['configshuffleanswers'] = 'Mischia le risposte';
$string['configshufflequestions'] = 'Abilitando quest\'opzione, l\'ordine delle domande nel gruppo del quiz offline verrà cambiato casualmente ogni volta che apri l\'anteprima dal tab "Crea modulo".';
$string['configshufflewithin'] = 'Abilitando quest\'opzione, le risposte nelle domande a risposta multipla saranno mischiate differentemente per ogni gruppo del quiz offline.';
$string['configuseridentification'] = 'Una formula che descrive come identificare l\'utente. La formula è usata per associare nel sistema il modulo risposte all\'utente. Il lato destro dell\'equazione deve identificare un campo nel profilo utenti di Moodle'
        . 'A formula describing the user identification. This formula is used to assign answer forms to users in the system. The right hand side of the equation must denote a field in the user table of Moodle.';
$string['configpapergray'] = 'Livello di bianco della carta su cui vengono stampati i quiz, usato durante l\'elaborazione automatica';
$string['configshufflewithin'] = 'Abilitando questa opzione le parti che costituiscono le domande individuali verranno mischiate alla generazione dei foglio di domande e del modulo risposte.';
$string['confirmremovequestion'] = 'Sei sicuro di voler eliminare la domanda {$a}?';
$string['copyright'] = '<strong>Attenzione: i testi in questa pagina sono per uso strettamente personale! Come tutti i testi, queste domande sono protette dal diritto d\'autore. Non possono essere copiate né mostrate ad altri.</strong>';
$string['copy'] = 'Copia';
$string['correct'] = 'corretta';
$string['correcterror'] = 'risolvi';
$string['correctforgroup'] = 'Risposta corrette per il gruppo {$a}';
$string['correctionform'] = 'Correzione';
$string['correctionforms'] = 'Correzione moduli';
$string['correctionoptionsheading'] = 'Opzioni di correzione';
$string['correctupdated'] = 'Modulo di correzione per il gruppo {$a} aggiornato.';
$string['couldnotgrab'] = 'Cattura dell\'immagine {$a} fallita';
$string['couldnotregister'] = 'Registrazione dell\'utente {$a} fallita';
$string['createcategoryandaddrandomquestion'] = 'Crea categoria e aggiungi una domanda a caso';
$string['createofflinequiz'] = 'Genera moduli';
$string['createlistfirst'] = 'Aggiungi partecipanti';
$string['createpartpdferror'] = 'Il registro presenze per la lista di partecipanti {$a} non può essere creato. La lista potrebbe essere vuota.';
$string['createpdferror'] = 'Il modulo per il gruppo {$a} non può essere creato. Potrebbero non esserci domande nel gruppo.';
$string['createpdffirst'] = 'Crea prima i PDF';
$string['createpdfforms'] = 'Crea i moduli PDF';
$string['createpdf'] = 'Genera PDF';
$string['createpdfs'] = 'Genera PDF';
$string['createpdfsparticipants'] = 'Genera i Registri presenze per liste di partecipanti';
$string['createquestionandadd'] = 'Create a new question and add it to the quiz.';
$string['createquiz'] = 'Genera moduli risposte';
$string['csvfile'] = 'File CSV';
$string['csvformat'] = 'Comma separated values text file (CSV)';
$string['csvplus1format'] = 'Text file with raw data (CSV)';
$string['csvpluspointsformat'] = 'Text file with points (CSV)';
$string['darkgray'] = 'Grigio scuro';
$string['datanotsaved'] = 'Could not save settings';
$string['configdecimalplaces'] = 'Number of digits that should be shown after the decimal point when displaying grades for the offline quiz.';
$string['decimalplaces'] = 'Cifre decimali';
$string['decimalplaces_help'] = 'Numero di cifre decimali che verranno mostrate nelle valutazioni dei quiz offline';
$string['deletelistcheck'] = 'Vuoi davvero eliminare la lista selezionata e tutti i suoi partecipanti?';
$string['deleteresultcheck'] = 'Vuoi davvero eliminare i risultati selezionati?';
$string['deletepagesafterselected'] = 'Remove page breaks after selected questions';
$string['deletepartcheck'] = 'Vuoi davvero eliminare i partecipanti selezionati?';
$string['deletepagecheck'] = 'Vuoi davvero eliminare le pagine selzionate?';
$string['deleteparticipantslist'] = 'Elimina la lista partecipanti';
$string['deletepdfs'] = 'Elimina i documenti';
$string['deleteselectedresults'] = 'Elimina i risultati selezionati';
$string['deleteselectedpart'] = 'Elimina i partecipanti selezionati';
$string['deletethislist'] = 'Delete this list';
$string['deleteupdatepdf'] = 'Elimina e aggiorna i moduli PDF';
$string['difficultytitlea'] = 'Difficulty A';
$string['difficultytitleb'] = 'Difficulty B';
$string['difficultytitlediff'] = 'Difference';
$string['difficultytitle'] = 'Difficulty';
$string['disableimgnewlines'] = 'Disable new lines before and after images';
$string['disableimgnewlines_help'] = 'This option disables new lines before and after images in the pdf question sheets. Warning: This might lead to formatting problems.';
$string['displayoptions'] = 'Opzioni di visualizzazione';
$string['done'] = 'done';
$string['downloadallzip'] = 'Scarica tutti i file come ZIP';
$string['downloadpartpdf'] = 'Scarica PDF per la lista \'{$a}\'';
$string['downloadpdfs'] = 'Scarica i documenti';
$string['downloadresultsas'] = 'Scarica i risultati come: ';
$string['dragtoafter'] = 'After {$a}';
$string['dragtostart'] = 'To the start';
$string['editlist'] = 'Modifica la lista';
$string['editthislist'] = 'Modifica questa lista';
$string['editlists'] = 'Modifica le liste';
$string['editgroups'] = 'Edit Offline Groups';
$string['editgroupquestions'] = 'Modifica il gruppo di domande';
$string['editingofflinequiz'] = 'Modificare il gruppo di domande';
$string['editingofflinequiz_help'] = 'Nella creazione di un quiz, i principali concetti sono:
<ul><li>Il quiz offline, che contiene le domande, su una o più pagine</li>
<li>Il deposito delle domande, che contiene la copia di tutte le domande, organizzate in categorie</li></ul>';
$string['editofflinequiz'] = 'Modifica il quiz offline';
$string['editingofflinequizx'] = 'Modifica il quiz offline: {$a}';
$string['editmaxmark'] = 'Edit maximum mark';
$string['editorder'] = 'Edit order';
$string['editparticipants'] = 'Modifica i partecipanti';
$string['editquestion'] = 'Edit question';
$string['editquestions'] = 'Edit questions';
$string['editscannedform'] = 'Modifica la scansione del modulo';
$string['emptygroups'] = 'Ci sono dei gruppi di domande vuoti. Aggiungere qualche domanda.';
$string['enroluser'] = 'Enrol user';
$string['erroraccessingreport'] = 'You are not allowed to view this report.';
$string['errorreport'] = 'Report of import errors';
$string['eventattemptdeleted'] = 'Consegna Quiz offline eliminata';
$string['eventattemptpreviewstarted'] = 'Avvio anteprima consegna Quiz offline quiz';
$string['eventattemptreviewed'] = 'Consegna Quiz offline verificata';
$string['eventattemptsummaryviewed'] = 'Sommario consegne Quiz offline visualizzato';
$string['eventattemptviewed'] = 'Consegna Quiz offline visualizzata';
$string['eventdocscreated'] = 'Foglio domande e moduli risposta Quiz offline generati';
$string['eventdocsdeleted'] = 'Foglio domande e moduli risposta Quiz offline eliminati';
$string['eventeditpageviewed'] = 'Offline quiz edit page viewed';
$string['eventofflinequizattemptsubmitted'] = 'Consegna Quiz offline caricata';
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
$string['fileformat'] = 'Formato del file con i fogli domande';
$string['fileformat_help'] = 'Choose whether you want your question sheets in PDF, DOCX or TEX format. Answer forms and correction sheets will always be generated in PDF format.';
$string['filesizetolarge'] = 'Some of your image files are very large. The dimensions will be resized during the interpretation. Please try to scan with a resolution between 200 and 300 dpi and in black and white mode. This will speed up the interpretation next time.';
$string['forautoanalysis'] = 'per elaborazione automatica';
$string['formforcorrection'] = 'Modulo correzioni per il gruppo {$a}';
$string['formforgroup'] = 'Modulo domande per il gruppo {$a}';
$string['formforgroupdocx'] = 'Modulo domande per il gruppo {$a} (DOCX)';
$string['formforgrouplatex'] = 'Modulo domande per il gruppo {$a} (LATEX)';
$string['formsexist'] = 'Moduli già creati.';
$string['formsexistx'] = 'Moduli già creati (<a href="{$a}">Scarica i moduli</a>)';
$string['formsheetsettings'] = 'Impostazioni modulo';
$string['formspreview'] = 'Anteprima moduli';
$string['formwarning'] = 'There is no answer form defined. Please contact your administrator.';
$string['fromquestionbank'] = 'dal deposito domande';
$string['functiondisabledbysecuremode'] = 'That functionality is currently disabled';
$string['generalfeedback'] = 'General feedback';
$string['generalfeedback_help'] = 'General feedback is text which is shown after a question has been attempted. Unlike feedback for a specific question which depends on the response given, the same general feedback is always shown.';
$string['generatepdfform'] = 'Genera registro presenze';
$string['grade'] = 'Voto';
$string['gradedon'] = 'Graded on';
$string['gradedscannedform'] = 'Scanned form with grades';
$string['gradeiszero'] = 'Note: The maximum grade for this offline quiz is 0 points!';
$string['gradeswarning'] = 'The question grades have to be numbers!';
$string['gradewarning'] = 'The question grade has to be a number!';
$string['gradingofflinequiz'] = 'Valutazioni';
$string['gradingofflinequizx'] = 'Valutazioni: {$a}';
$string['gradingoptionsheading'] = 'Grading options';
$string['greeniscross'] = 'considerata una crocetta';
$string['rediswrong'] = 'crocetta sbagliata o mancante';
$string['group'] = 'Gruppo';
$string['groupoutofrange'] = 'Group was out of range and replaced with group A.';
$string['groupquestions'] = 'Gruppi domande';
$string['hasresult'] = 'Result exists';
$string['idnumber'] = 'Matricola';
$string['imagefile'] = 'File immagine';
$string['imagenotfound'] = 'File immagine: {$a} non trovato!';
$string['imagenotjpg'] = 'L\'immagine non è jpg o png: {$a}';
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
$string['importedon'] = 'Importato il';
$string['importforms'] = 'Importa moduli risposte';
$string['importisfinished'] = 'Importazione per il quiz offline {$a} completata.';
$string['importlinkresults'] = 'Link to results: {$a}';
$string['importlinkverify'] = 'Link to verifying: {$a}';
$string['importmailsubject'] = 'offline quiz import notification';
$string['importnumberexisting'] = 'Number of double forms: {$a}';
$string['importnumberpages'] = 'Numero id pagine importate correttamente: {$a}';
$string['importnumberresults'] = 'Number of imported : {$a}';
$string['importnumberverify'] = 'Numero di moduli che richiedono una verifica: {$a}';
$string['importtimefinish'] = 'Processo concluso: {$a}';
$string['importtimestart'] = 'Processo iniziato: {$a}';
$string['inconsistentdata'] = 'Inconsistent data: {$a}';
$string['info'] = 'Info';
$string['infoshort'] = 'i';
$string['insecuremarkingsforquestion'] = 'Insecure markings need manual corrections for question';
$string['insecuremarkings'] = 'Insecure markings need manual corrections';
$string['insertnumber'] = 'Please insert the correct identification number marked by the blue frame.';
$string['instruction1'] = 'Questo modulo sarà analizzato automaticamente. Non piegarlo né macchiarlo. Usare una penna nera o blu per marcare i campi così:';
$string['instruction2'] = 'Solo le caselle marcate chiaramente saranno interpretate correttamente! Non uscire dalla casella. Per correggere una casella, riempire completamente il quadrato di colore: la casella verrà interpretata come un quadrato vuoto:';
$string['instruction3'] = 'Le caselle corrette non possono essere marcate di nuovo. Attenzione a non scrivere nulla fuori dalle caselle.';
$string['introduction'] = 'Introduzione';
$string['invalidformula'] = 'Invalid formula for user identification. The formula must have the form <prefix>[<#digits>]<suffix>=<db-field&>.';
$string['invalidnumberofdigits'] = 'Invalid number of digits used. Only 1 up to 9 digit(s) are allowed.';
$string['invaliduserfield'] = 'Invalid field of the user table used.';
$string['invigilator'] = 'Vigilante';
$string['ischecked'] = 'Participation is checked';
$string['isnotchecked'] = 'Participation is not checked';
$string['itemdata'] = 'Itemdata';
$string['keepfilesfordays'] = 'Keep files for days';
$string['lightgray'] = 'Grigio chiaro';
$string['linktoscannedform'] = 'Immagine del modulo acquisita';
$string['listnotdetected'] = 'Could not detect barcode for list!';
$string['logdeleted'] = 'Log entry {$a} deleted.';
$string['logourl'] = 'Logo URL';
$string['logourldesc'] = 'URL of an image file that is displayed on the top right corner of answer forms, i.e. <b>http://www.yoursite.tld/mylogo.png</b> or <b>../path/to/your/logo.png</b>. The maximum allowed size is 520x140 pixels. Answer forms cannot be evaluated if the image exceeds the maximum size!';
$string['lowertrigger'] = 'Lower second boundary';
$string['lowertriggerzero'] = 'Lower second boundary is zero';
$string['lowerwarning'] = 'Lower first boundary';
$string['lowerwarningzero'] = 'Lower first boundary is zero';
$string['marginwarning'] = 'Ricordati di stampare i PDF seguenti senza margini aggiuntivi!<br /> Non distribuire fotocopie agli studenti, se necessario ristampa i file PDF originali.';
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
$string['copyselectedtogroup'] = 'Aggiungi le domande selezionate al gruppo: {$a}';
$string['copytogroup'] = 'Aggiungi tutte le domande al gruppo: {$a}';
$string['multichoice'] = 'Multiple choice';
$string['multipleanswers'] = 'Choose at least one answer.';
$string['moodleprocessing'] = 'Let Moodle process data';
$string['movecorners'] = 'Modifica la posizione dei punti di ancoraggio tramite trascinamento e rilascio';
$string['multianswersforsingle'] = 'Multiple answers for single choice question';
$string['name'] = 'Nome quiz offline';
$string['neededcorrection'] = '<strong>Attention: Some of your markings needed manual correction. Have a look at the red squares in the following picture.
<br />This implied manual intervention by a teacher and delayed the publishing of the offline quiz results!</strong>';
$string['newgrade'] = 'Graded';
$string['newpage'] = 'Nuova pagina';
$string['noattemptexists'] = 'Nessuna consegna presente';
$string['noattempts'] = 'Consegne non importate!';
$string['noattemptsonly'] = 'Mostra solo gli studenti senza consegne';
$string['nocourse'] = 'The course with id {$a->course} that the offline quiz with ID {$a->offlinequiz} belongs to is missing.';
$string['nogradesseelater'] = 'This quiz has not been graded yet for {$a}. Results will be published here.';
$string['nogroupdata'] = 'No group data for user {$a}';
$string['noscannedpage'] = 'There is no scanned page with ID {$a}!';
$string['nomcquestions'] = 'There are no multiple choice questions in group {$a}!';
$string['noofflinequiz'] = 'There is no offline quiz with id {$a}!';
$string['nopages'] = 'No pages imported';
$string['noparticipantsfound'] = 'No participants found';
$string['nopdfscreated'] = 'Nessun documento creato!';
$string['noquestions'] = 'Alcuni gruppi di domande sono vuoti. Aggiungi qualche domanda.';
$string['noquestionsfound'] = 'Non ci sono domande nel gruppo {$a}!';
$string['noquestionsonpage'] = 'Pagina vuota';
$string['noquestionselected'] = 'Nessuna domanda selezionata!';
$string['noresults'] = 'Non ci sono risultati.';
$string['noreview'] = 'You are not allowed to review this offline quiz';
$string['nothingtodo'] = 'Nothing to do!';
$string['notxtfile'] = 'No TXT file';
$string['notyetgraded'] = 'Not yet graded';
$string['nozipfile'] = 'No ZIP file';
$string['numattempts'] = 'Numero delle consegne importate: {$a}';
$string['numattemptsqueue'] = '{$a} moduli risposta accodati. Alla fine dell\'elaborazione dei dati ti verrà inviato un messaggio e-mail.';
$string['numattemptsverify'] = 'Moduli digitalizzati in attesa di correzione: {$a}';
$string['numberformat'] = 'The value has to be a number with {$a} digits!';
$string['numpages'] = '{$a} pagine importate';
$string['numquestionsx'] = 'Domande: {$a}';
$string['numusersadded'] = '{$a} partecipanti aggiunti';
$string['odsformat'] = 'OpenDocument spreadsheet (ODS)';
$string['offlineimplementationfor'] = 'Offline implementation for';
$string['editofflinesettings'] = 'Edit offline settings';
$string['offlinequizcloseson'] = 'The review for this offline quiz will close at {$a}';
$string['offlinequizisclosed'] = 'Offline quiz closed)';
$string['offlinequizisclosedwillopen'] = 'Offline quiz closed (opens {$a})';
$string['offlinequizisopen'] = 'Questo quiz offline è aperto';
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
$string['outof'] = '{$a->grade} su un massimo di {$a->maxgrade}';
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
$string['pagesizeparts'] = 'Numero di partecipanti per pagina:';
$string['papergray'] = 'Livello di bianco della carta';
$string['papergray_help'] = 'Se lo scanner produce delle scansioni molto scure o si usa carta riciclata, si possono compensare indicando il livello di bianco nella scansione.';
$string['partcheckedwithresult'] = '{$a} checked participants with result';
$string['partcheckedwithoutresult'] = '<a href="{$a->url}">{$a->count} checked participants without result</a>';
$string['partuncheckedwithresult'] = '<a href="{$a->url}">{$a->count} unchecked participants with result</a>';
$string['partuncheckedwithoutresult'] = '{$a} unchecked participants without result';
$string['participantslist'] = 'Lista dei partecipanti';
$string['participantslists'] = 'Partecipanti';
$string['participants'] = 'Partecipanti';
$string['participantsinlists'] = 'Partecipanti nella lista';
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
$string['pdfscreated'] = 'I PDF sono stati generati';
$string['pdfsdeletedforgroup'] = 'Moduli per il gruppo {$a} eliminati';
$string['pdfintro'] = 'Informazioni aggiuntive';
$string['pdfintro_help'] = 'Il testo verrà stampato sulla prima pagina del foglio domande e possono contenere informazioni o istruzioni generali sulla prova o sulla compilazione del modulo.';
$string['pdfintrotext'] = '<b>Come compilare correttamente il modulo</b><br />Evitare di piegare o macchiare. Usare una penna nera o blu per marcare i campi. Non uscire dalla casella. Per correggere un campo, riempire completamente il quadrato di colore: la casella verrà interpretata come un quadrato vuoto.<br />';
$string['pdfintrotoolarge'] = 'L\'introduzione è troppo lunga (massimo 2000 caratteri).';
$string['pearlywhite'] = 'Bianco lucido';
$string['pluginadministration'] = 'Offline quiz administration';
$string['point'] = 'point';
$string['present'] = 'presente';
$string['preventsamequestion'] = 'Impedisci l\'utilizzo multiplo della stessa domanda in gruppi differenti';
$string['previewforgroup'] = 'Anteprima per il gruppo {$a}';
$string['preview'] = 'Anteprima';
$string['previewquestion'] = 'Anteprima domanda';
$string['printstudycodefield'] = 'Stampa il campo per il codice esame';
$string['printstudycodefield_help'] = 'Verrà stampato un campo da compilare con il codice esame sul foglio domande';
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
$string['questionsheet'] = 'Foglio domande';
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
$string['questionsingroup'] = 'Domande nel gruppo';
$string['questionsinthisofflinequiz'] = 'Questions in this offline quiz';
$string['questiontextisempty'] = '[Empty question text]';
$string['quizdate'] = 'Data di svolgimento';
$string['quizopenclose'] = 'Open and close dates';
$string['quizopenclose_help'] = 'Gli studenti possono vedere le proprie consegne solo tra la data di apertura e chiusura.';
$string['quizquestions'] = 'Quiz Questions';
$string['randomfromexistingcategory'] = 'Random question from an existing category';
$string['randomnumber'] = 'Numero di domande casuali';
$string['randomquestionusinganewcategory'] = 'Random question using a new category';
$string['readjust'] = 'Riposiziona';
$string['reallydeletepdfs'] = 'Vuoi davvero eliminare tutti i moduli?';
$string['reallydeleteupdatepdf'] = 'Vuoi davvero eliminare e aggiornare la lista partecipanti?';
$string['recreatepdfs'] = 'Recreate PDFs';
$string['recurse'] = 'Icludi anche domande dalle sottocategorie';
$string['refreshpreview'] = 'Aggiorna anteprima';
$string['regrade'] = 'Rivaluta';
$string['regradedisplayexplanation'] = '<b>Attention:</b> Regrading will not change marks that have been overwritten manually!';
$string['regradinginfo'] = 'If you change the score for a question, you must regrade the offline quiz to update the participants’ results.';
$string['regradingquiz'] = 'Rivalutazione';
$string['regradingresult'] = 'Regrading result for user {$a}...';
$string['reloadpreview'] = 'Ricarica anteprima';
$string['reloadquestionlist'] = 'Ricarica la lista domande';
$string['remove'] = 'Rimuovi';
$string['removeemptypage'] = 'Remove empty page';
$string['removepagebreak'] = 'Rimuovi salto pagina';
$string['removeselected'] = 'Rimuovi i selezionati';
$string['reordergroupquestions'] = 'Reorder Group Questions';
$string['reorderquestions'] = 'Reorder questions';
$string['reordertool'] = 'Show the reordering tool';
$string['repaginate'] = 'Rimpagina con {$a} domande per pagina';
$string['repaginatecommand'] = 'Rimpagina';
$string['repaginatenow'] = 'Rimpagina ora';
$string['reportoverview'] = 'Overview';
$string['reportstarts'] = 'review of results';
$string['resetofflinequizzes'] = 'Reset Offline Quiz data';
$string['results'] = 'Risultati';
$string['resultexists'] = 'Esiste un risultato identico per {$a}, importazione ignorata';
$string['resultimport'] = 'Importa i risultati';
$string['reviewcloses'] = 'Chiusura valutazioni';
$string['reviewbefore'] = 'Permetti le valutazioni mentre il quiz online è aperto';
$string['reviewclosed'] = 'Dopo che il qui offline è chiuso';
$string['reviewimmediately'] = 'Immediatamente dopo il tentativo';
$string['reviewincludes'] = 'Review includes';
$string['reviewofresult'] = 'Verifica dei risultati';
$string['reviewopens'] = 'Apertura valutazioni';
$string['reviewoptions'] = 'Students may view';
$string['reviewoptionsheading'] = 'Opzioni di valutazione';
$string['reviewoptions_help'] = 'Queste opzioni controllano ciò che gli studenti possono vedere dopo che le consegne sono state importate.
Puoi anche definire un periodo di apertura e chiusura dei report dei risultati. I check box significano:
<table>
<tr><td style="vertical-align: top;"><b>La consegna</b></td><td>
Il testo delle domande e delle risposte sarà visibile agli studenti. Potranno vedere quale risposte hanno scelto, ma non sarà indicato quale sia la risposta corretta.</td>
</td></tr>
<tr><td style="vertical-align: top;"><b>La correzione</b></td><td>
L\'opzione è attivabile solo se è attiva l\'opzione "La consegna". Se attiva, gli studenti postrà controllare quali delle risposte che ha scelto siano corrette (sfondo verde) e quali errate (sfondo rosso).
</td></tr>
<tr><td style="vertical-align: top;"><b>Punteggi</b></td><td>
Sono mostrati il gruppo (es. B), i punteggi (risultato, totale per le domande, risultato in percentuale, es. 40/80 (50)) e il voto (es. 50 su 100).
Se "La consegna" è abilitata, sono mostrati anche i punteggi e i massimi per ogni singola domanda.
</td></tr>
<tr><td style="vertical-align: top;"><b>Risposte corrette</b></td><td>
Mostra se le risposte sono corrette o meno. L\'opzione richiede che "La consegna" sia attiva.
</td></tr>
<tr><td style="vertical-align: top;"><b>Modulo digitalizzato</b></td><td>
Mostra l\'immagine acquisita del modulo risposte. Le caselle considerate come risposte sono evidenziate in verde.
</td></tr>
<tr><td style="vertical-align: top;"><b>Modulo digitalizzato con i risultati</b></td><td>
Mostra l\'immagine acquisita del modulo risposte. Le caselle considerate come risposte sono evidenziate in verde.
Inoltre, tutte le caselle che sono state considerate errate e quelle contrassegnate in modo errato sono evidenziate in rosso.
Sul margine destro, ci sarà un elenco dei punti segnati per domanda.
</td></tr>
</table>';

$string['review'] = 'Review';
$string['rimport'] = 'Carica/Correggi';
$string['rotate'] = 'Ruota';
$string['rotatingsheet'] = 'Sheet is rotated...';
$string['saveandshow'] = 'Salva e mosta le modifiche allo studente';
$string['save'] = 'Salva';
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
$string['selectagroup'] = 'Scegli un gruppo';
$string['selectall'] = 'Seleziona tutto';
$string['selectcategory'] = 'Select category';
$string['selectedattempts'] = 'Selected attempts...';
$string['selectnone'] = 'Deseleziona tutto';
$string['selectquestiontype'] = '-- Select question type --';
$string['selectdifferentgroup'] = 'Please select a different group!';
$string['selectformat'] = 'Select format...';
$string['selectgroup'] = 'Select group';
$string['selectlist'] = 'Please select a list or try to readjust sheet:';
$string['selectmultipletoolbar'] = 'Select multiple toolbar';
$string['selectpage'] = 'Please select page number or try to readjust sheet:';
$string['showallparts'] = 'Mostra tutti i {$a} partecipanti';
$string['showcopyright'] = 'Show copyright statement';
$string['showcopyrightdesc'] = 'If you enable this option, a copyright statement will be shown on the student result review page.';
$string['showgrades'] = 'Stampa la valutazione delle domande';
$string['showgrades_help'] = 'This option controls whether the maximum grades of the offline quiz questions should be printed on the question sheet.';
$string['showmissingattemptonly'] = 'Mostra tutti i partecipanti verificati senza risultato';
$string['showmissingcheckonly'] = 'Mostra tutti i partecipanti non verificati con risultato';
$string['shownumpartsperpage'] = 'Show {$a} participants per page';
$string['showquestioninfo'] = 'Stampa informazioni sulle risposte';
$string['showquestioninfo_help'] = 'With this option you can control, which additional information about the question is printed on the question sheet.
You can choose one of these:
<ul>
<li> Nothing
<li> Question type - Depending on question type Single-Choice, Multiple-Choice, All-or-Nothing Multiple-Choice will be printed
<li> Number of correct answers - The number of correct answers will be printed
</ul>';
$string['showstudentview'] = 'Show student view';
$string['showtutorial'] = 'Mostra agli studenti un tutorial sui quiz offline.';
$string['showtutorial_help'] = 'Opzionalmente gli studenti possono leggere un tutorial sullo svolgimento dei quiz offline.
Il tutorial mostra come usare i diversi tipi di documento dei quiz offline. In una parte interattiva, gli studenti possono pravare a compilare il loro ID correttamente.<br />
<b>Attenzione:</b><br />
Abilitando quest\'opzione lasciando però il quiz offline nascosto, il link al tutorial non sarà visibile. In questo caso, trascivere il link al tutorial nella pagina del corso.';
$string['showtutorialdescription'] = 'Il link del tutorial ha questo URL:';
$string['shuffleanswers'] = 'Rimescola le risposte';
$string['shufflequestionsanswers'] = 'Rimescola le domande e le risposte';
$string['shufflequestionsselected'] = 'L\'ordinamento casuale è attivo, quindi non è possibile eseguire alcune azioni sulle pagine. Per cambiare l\'opzione di ordinamento casuale, {$a}.';
$string['shufflewithin_help'] = 'Se abilitata, la sezione delle domande verrà ordinata riordinata casualmente ad ogni aggiornamento della pagina di anteprima. NOTA: Questa impostazione si applica solo alle domande che hanno già l\'opzione di ordinamento casuale attiva.';
$string['signature'] = 'Firma';
$string['singlechoice'] = 'Single choice';
$string['standard'] = 'Standard';
$string['starttutorial'] = 'Avvia il tutorial sull\'esame';
$string['statistics'] = 'Statistiche';
$string['statisticsplural'] = 'Statistiche';
$string['statsoverview'] = 'Panoramica statistiche';
$string['studycode'] = 'Codice esame';
$string['theattempt'] = 'The attempt';
$string['timesup'] = 'Time is up!';
$string['totalmarksx'] = 'Punteggio totale: {$a}';
$string['totalpointsx'] = 'Punteggio totale: {$a}';
$string['totalquestionsinrandomqcategory'] = 'Total of {$a} questions in category.';
$string['trigger'] = 'lower/higher boundary';
$string['tutorial'] = 'Tutorial sui quiz offline';
$string['type'] = 'Tipo';
$string['uncheckparts'] = 'Marca assenti i partecipanti selezionati';
$string['updatedsumgrades'] = 'La somma di tutte le valutazioni del gruppo {$a->letter} è stata ricalcolata in {$a->grade}.';
$string['upgradingfilenames'] = 'Aggiornamento nomi dei file dei documenti: quiz offline {$a->done}/{$a->outof} (ID Quiz offline {$a->info})';
$string['upgradingofflinequizattempts'] = 'Aggiornamento tentativi dei quiz offline: quiz offline {$a->done}/{$a->outof} <br/>(Offline Quiz ID {$a->info})';
$string['upgradingilogs'] = 'Aggiornamento pagine scansionate: pagine scansionate {$a->done}/{$a->outof} <br/>(Offline Quiz ID {$a->info})';
$string['uploadpart'] = 'Carica/Correggi lista dei partecipanti';
$string['upload'] = 'Carica/Correggi';
$string['uppertrigger'] = 'Higher second boundary';
$string['uppertriggerzero'] = 'Higher second boundary is zero';
$string['upperwarning'] = 'Higher first boundary';
$string['upperwarningzero'] = 'Higher first boundary is zero';
$string['useradded'] = 'User {$a} added';
$string['userdoesnotexist'] = 'L\'utente {$a} non è presente nel sistema';
$string['useridentification'] = 'User identification';
$string['userimported'] = 'User {$a} imported and graded';
$string['usernotincourse'] = 'User {$a} not in course.';
$string['userpageimported'] = 'Single page imported for user {$a}';
$string['usernotinlist'] = 'User not registered in list!';
$string['usernotregistered'] = 'User {$a} not registered in course';
$string['valuezero'] = 'Value should not be zero';
$string['viewresults'] = 'View results';
$string['white'] = 'Bianco';
$string['withselected'] = 'With selected...';
$string['zipfile'] = 'File ZIP';
$string['zipok'] = 'File ZIP importato';
$string['zerogradewarning'] = 'Attenzione: la valutazione del quiz offline è 0.0!';
