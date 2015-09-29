<?php


require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

// Include Composer autoloader if not already done.
include '../local/myplugin/pdfparser/vendor/autoload.php';
require("../local/myplugin/pdfparser/dbOption/Db.class.php");

echo "<meta http-equiv='Content-Type' content='text/html;charset=utf-8' />";

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
question_edit_setup('upload', '/question/upload.php');

$url = new moodle_url($thispageurl);
if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
    $url->param('lastchanged', $lastchanged);
}

$PAGE->set_url($url);

$context = $contexts->lowest();
$streditingquestions = get_string('upload', 'local_myplugin');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);

$question       =   new Db();
$answer         =   new Db();
$multichoice    =   new Db();
$truefalse      =   new Db();
$shortanswer    =   new Db();
$numerical      =   new Db();
$matchingtype   =   new Db();
$obe            =   new Db();

$target_dir = "../local/myplugin/pdfparser/uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);

$allowedExts = array(
  "pdf",
); 

$extension = end(explode(".", $_FILES["fileToUpload"]["name"]));

$category = $_SESSION['category'];      // get the sessioned data category
$user_id = $_SESSION['user_id'];        // get the sessioned data userid
$course_id = $_SESSION['course_id'];    // get the sessioned data courseid

// get the category and return part of the string
if(strlen($category) <= 5)
{
    $category = substr($category, 0, strlen($category)-3);
    $category = $_SESSION['category'];
}

if(strlen($category) <= 9)
{
    $category = substr($category, 0, strlen($category)-4);
    $category = $_SESSION['category'];
}

if(strlen($category) >= 10)
{
    $category = substr($category, 0, strlen($category)-6);
    $category = $_SESSION['category'];
}

echo $OUTPUT->header();

echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';

if(isset($_POST["submit"])) 
{
    //$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

    // Check if file already exists
    /*if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }*/

    // Check if file is PDF
    if ( ! ( in_array($extension, $allowedExts ) ) ) {
      echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
      echo 'The file is not a PDF.<br><a href="jas.php?courseid='.$course_id.'">Back to upload page.</a>';
      return FALSE;
      echo "</div>\n";
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) 
    {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) 
    {
        echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
    } 

    else 
    {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) 
        {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
            echo "<br/><br/>";
        } 

        else 
        {
            echo "Sorry, there was an error uploading your file.";
            echo "<br/><br/>";
        }
    }

}


// pdf parser

// gets the file
$file = $target_file;

// Parse pdf file and build necessary objects.
$parser = new \Smalot\PdfParser\Parser();

$pdf    = $parser->parseFile($file);

// Extract all contents from the pdf file.
$pages = $pdf->getText();

// Split the content by \n
$entries = preg_split('/(\n)/', $pages, -1, PREG_SPLIT_NO_EMPTY);

// search for 'Multiple Choice' in the array
$sMC = array_search('Multiple Choice', $entries);

// search for 'True/False' in the array
$sTF = array_search('True/False', $entries);

// search for 'Short answer' in the array
$sSA = array_search('Short answer', $entries);

// search for 'Numerical' in the array
$sN = array_search('Numerical', $entries);

// search for 'Matching type' in the array
$sMT = array_search('Matching Type', $entries);

// search for 'End' in the array
$end = array_search('End', $entries);

echo '<pre>';
        // print_r($entries);
echo '<pre/>';

$q = 0;

// count questions
foreach ($entries as $entry) {
    if(is_numeric(substr($entry, 0, 1)) === true) {
        $q++;
    }
}  

// multiple choice
if (in_array("Multiple Choice", $entries)) {

    // slice array starting from $sMC and stop at the result of $sTF
    $entries2 = array_slice($entries, $sMC, $sTF);

    // remove the key that contains "Multiple Choice"
    $indexMultiple = array_search('Multiple Choice', $entries2);
    unset($entries2[$indexMultiple]);


    $answerIDs      = "";
    $questionID     = "";

    $allAnswerIDs = array();

    $s_entries = implode("\n", $entries2);

    $entries2 = preg_split('/\v+(?=^[a-e\d]+\.)/m', $s_entries);
    
    foreach ($entries2 as $entry) {

        if(is_numeric(substr($entry, 0, 1)) === true) {
            // remove # and .
            $c_entry = substr($entry, 7, strlen($entry)-1);

            $correct = 1.0000000;

            // Insert question as you do now.
            $question->query("INSERT INTO mdl_question(category, name, questiontext, questiontextformat, generalfeedbackformat, qtype, timecreated, timemodified, createdby, modifiedby) VALUES(:category, :name, :questiontext, :questiontextformat, :generalfeedbackformat, :qtype, :timecreated, :timemodified, :createdby, :modifiedby)",array("category"=> $category, "name"=> $c_entry, "questiontext"=> $c_entry, "questiontextformat"=> 1, "generalfeedbackformat"=> 1, "qtype"=> "multichoice", "timecreated"=> time(), "timemodified" => time(), "createdby"=> $user_id, "modifiedby"=> $user_id));

            $questionID = $question->lastInsertId();

            // Store the question ID in the array.
            $allAnswerIDs[$questionID] = array();

            $entry2 = substr($entry, 3, strlen($entry)-1).'<br>';

            if(substr($entry2, 2, 1) == ")")
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 1);
            }

            else
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 2);
            }   

            // Insert question id and $cat
            $obe->query("INSERT INTO mdl_question_obe(question, obe_category) VALUES(:question, :obe_category)", array("question" => $questionID, "obe_category" => $cat));


        } else {
            // remove letter and .
            $entry = substr($entry, 3, strlen($entry)-1);

            // Insert answer.
            $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>1, "fraction"=>$correct, "feedbackformat"=>1));

            $answerID = $answer->lastInsertId();

            // Store ID in $answerIDs as an array (or as a string, if you like).
            $allAnswerIDs[$questionID][] = $answerID;

            $correct = 0.0000000;
        }
    }

    foreach ($allAnswerIDs as $questionID => $questionAnswerIDs) {
        // Build a string of the answers per question.
        $answerIDs = implode(',', $questionAnswerIDs);

        // Insert the multiple choice row using $questionID and $answerIDs.
        $multichoice->query("INSERT INTO mdl_question_multichoice(question, answers, single, correctfeedbackformat, partiallycorrectfeedbackformat, incorrectfeedbackformat) VALUES(:question, :answers, :single, :correctfeedbackformat, :partiallycorrectfeedbackformat, :incorrectfeedbackformat)", array("question"=>$questionID, "answers"=>$answerIDs, "single"=>1, "correctfeedbackformat"=>1, "partiallycorrectfeedbackformat"=>1, "incorrectfeedbackformat"=>1));
    }   

    echo "Got Multiple Choice.<br>";
}

// true/false
if (in_array("True/False", $entries)) {

    // slice array starting from $sTF and stop at the result of $sSA-$sTF
    $entries2 = array_slice($entries, $sTF, ($sSA-$sTF));

    // remove the key that contains "True/False"
    $indexTruefalse = array_search('True/False', $entries2);
    unset($entries2[$indexTruefalse]);

    $answerIDs          = "";
    $questionID         = "";

    $allAnswerIDs = array();
    $bool = array();

    foreach ($entries2 as $entry) {

        if(is_numeric(substr($entry, 0, 1)) === true) {
            // remove # and .
            $c_entry = substr($entry, 7, strlen($entry)-1);

            $correct = 1.0000000;

            // Insert question as you do now.
            $question->query("INSERT INTO mdl_question(category, name, questiontext, questiontextformat, generalfeedbackformat, qtype, timecreated, timemodified, createdby, modifiedby) VALUES(:category, :name, :questiontext, :questiontextformat, :generalfeedbackformat, :qtype, :timecreated, :timemodified, :createdby, :modifiedby)",array("category"=> $category, "name"=> $c_entry, "questiontext"=> $c_entry, "questiontextformat"=> 1, "generalfeedbackformat"=> 1, "qtype"=> "truefalse", "timecreated"=> time(), "timemodified" => time(), "createdby"=> $user_id, "modifiedby"=> $user_id));

            $questionID = $question->lastInsertId();
            
            // Store the question ID in the array.
            $allAnswerIDs[$questionID] = array();
            $bool[$questionID] = array();

            $entry2 = substr($entry, 3, strlen($entry)-1).'<br>';

            if(substr($entry2, 2, 1) == ")")
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 1);
            }

            else
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 2);
            }   

            // Insert question id and $cat
            $obe->query("INSERT INTO mdl_question_obe(question, obe_category) VALUES(:question, :obe_category)", array("question" => $questionID, "obe_category" => $cat));

        } else {

            if (substr($entry, 0, 1) == 'T') {

                // Insert answer.
                $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>0, "fraction"=>$correct, "feedbackformat"=>1));

                $answerID = $answer->lastInsertId();
                
                // Store ID in $answerIDs as an array (or as a string, if you like).
                $allAnswerIDs[$questionID][] = $answerID;

                $correct = 0.0000000;

                $entry = 'False';

                // Insert answer.
                $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>0, "fraction"=>$correct, "feedbackformat"=>1));

                $answerID = $answer->lastInsertId();
                
                // Store ID in $answerIDs as an array (or as a string, if you like).
                $allAnswerIDs[$questionID][] = $answerID;

                $x = 1;

                $bool[$questionID][] = $x;
            }

            else{
                // Insert answer.
                $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>0, "fraction"=>$correct, "feedbackformat"=>1));

                $answerID = $answer->lastInsertId();
                
                // Store ID in $answerIDs as an array (or as a string, if you like).
                $allAnswerIDs[$questionID][] = $answerID;

                $correct = 0.0000000;

                $entry = 'True';

                // Insert answer.
                $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>0, "fraction"=>$correct, "feedbackformat"=>1));

                $answerID = $answer->lastInsertId();
                
                // Store ID in $answerIDs as an array (or as a string, if you like).
                $allAnswerIDs[$questionID][] = $answerID;

                $x = 0;

                $bool[$questionID][] = $x;
            }
        }
    }

    echo "Got True/False.<br>";
}

foreach ($allAnswerIDs as $questionID => $questionAnswerIDs) {

    if($bool[$questionID][0] == 1){

        // Separate correct answer and incorrect answer.
        $cor  = current($questionAnswerIDs);
        $icor = next($questionAnswerIDs);

        // Insert the truefalse row using $questionID and $cor, $icor.
        $truefalse->query("INSERT INTO mdl_question_truefalse(question, trueanswer, falseanswer) VALUES(:question, :trueanswer, :falseanswer)",array("question"=> $questionID,"trueanswer"=>$cor,"falseanswer"=>$icor));
    }

    else
    {
        $cor  = next($questionAnswerIDs);
        $icor = prev($questionAnswerIDs);

            // Insert the truefalse row using $questionID and $cor, $icor.
        $truefalse->query("INSERT INTO mdl_question_truefalse(question, trueanswer, falseanswer) VALUES(:question, :trueanswer, :falseanswer)",array("question"=> $questionID,"trueanswer"=>$cor,"falseanswer"=>$icor));
    }
}

// short answer
if (in_array("Short answer", $entries)) {

    // slice array starting from $sSA and stop at the result of $sN-$sSA
    $entries2 = array_slice($entries, $sSA, ($sN-$sSA));

    // remove the key that contains "Short answer"
    $indexShortAnswer = array_search('Short answer', $entries2);
    unset($entries2[$indexShortAnswer]);


    $answerIDs          = "";
    $questionID         = "";

    $allAnswerIDs = array();

    foreach ($entries2 as $entry) {
        if(is_numeric(substr($entry, 0, 1)) === true) {
            // remove # and .
            $c_entry = substr($entry, 7, strlen($entry)-1);

            $correct = 1.0000000;

            // Insert question as you do now.
            $question->query("INSERT INTO mdl_question(category, name, questiontext, questiontextformat, generalfeedbackformat, qtype, timecreated, timemodified, createdby, modifiedby) VALUES(:category, :name, :questiontext, :questiontextformat, :generalfeedbackformat, :qtype, :timecreated, :timemodified, :createdby, :modifiedby)",array("category"=> $category, "name"=> $c_entry, "questiontext"=> $c_entry, "questiontextformat"=> 1, "generalfeedbackformat"=> 1, "qtype"=> "shortanswer", "timecreated"=> time(), "timemodified" => time(), "createdby"=> $user_id, "modifiedby"=> $user_id));

            $questionID = $question->lastInsertId();

            // Store the question ID in the array.
            $allAnswerIDs[$questionID] = array();

            $entry2 = substr($entry, 3, strlen($entry)-1).'<br>';

            if(substr($entry2, 2, 1) == ")")
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 1);
            }

            else
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 2);
            }   

            // Insert question id and $cat
            $obe->query("INSERT INTO mdl_question_obe(question, obe_category) VALUES(:question, :obe_category)", array("question" => $questionID, "obe_category" => $cat));

        } else {
            // remove letter and .
            $entry = substr($entry, 3, strlen($entry)-1);

            // Insert answer.
            $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>1, "fraction"=>$correct, "feedbackformat"=>1));

            $answerID = $answer->lastInsertId();

            // Store ID in $answerIDs as an array (or as a string, if you like).
            $allAnswerIDs[$questionID][] = $answerID;

            $correct = 0.0000000;
        }
    }

    foreach ($allAnswerIDs as $questionID => $questionAnswerIDs) {
        // Build a string of the answers per question.
        $answerIDs = implode(',', $questionAnswerIDs);

        // Insert the shortanswer row using $questionID and $answerIDs
        $shortanswer->query("INSERT INTO mdl_question_shortanswer(question, answers) VALUES(:question, :answers)",array("question"=> $questionID,"answers"=>$answerIDs));
    }

    echo "Got Short answer.<br>";
}

// numerical
if (in_array("Numerical", $entries)) {

    // slice array starting from $sN and stop at the result of $sMT-$sN
    $entries2 = array_slice($entries, $sN, ($sMT-$sN));

    // remove the key that contains "Numerical"
    $indexNumerical = array_search('Numerical', $entries2);
    unset($entries2[$indexNumerical]);

    $answerIDs          = "";
    $questionID         = "";

    $allAnswerIDs = array();

    foreach ($entries2 as $entry) {
        if(is_numeric(substr($entry, 0, 1)) === true) {
            // remove # and .
            $c_entry = substr($entry, 7, strlen($entry)-1);

            $correct = 1.0000000;

            // Insert question as you do now.
            $question->query("INSERT INTO mdl_question(category, name, questiontext, questiontextformat, generalfeedbackformat, qtype, timecreated, timemodified, createdby, modifiedby) VALUES(:category, :name, :questiontext, :questiontextformat, :generalfeedbackformat, :qtype, :timecreated, :timemodified, :createdby, :modifiedby)",array("category"=> $category, "name"=> $c_entry, "questiontext"=> $c_entry, "questiontextformat"=> 1, "generalfeedbackformat"=> 1, "qtype"=> "numerical", "timecreated"=> time(), "timemodified" => time(), "createdby"=> $user_id, "modifiedby"=> $user_id));

            $questionID = $question->lastInsertId();
            
            // Store the question ID in the array.
            $allAnswerIDs[$questionID] = array();

            $entry2 = substr($entry, 3, strlen($entry)-1).'<br>';

            if(substr($entry2, 2, 1) == ")")
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 1);
            }

            else
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 2);
            }   

            // Insert question id and $cat
            $obe->query("INSERT INTO mdl_question_obe(question, obe_category) VALUES(:question, :obe_category)", array("question" => $questionID, "obe_category" => $cat));

        } else {
            // remove letter and .
            $entry = substr($entry, 3, strlen($entry)-1);
            // Insert answer.
            $answer->query("INSERT INTO mdl_question_answers(question, answer, answerformat, fraction, feedbackformat) VALUES(:question, :answer, :answerformat, :fraction, :feedbackformat)", array("question"=>$questionID, "answer"=>$entry, "answerformat"=>1, "fraction"=>$correct, "feedbackformat"=>1));

            $answerID = $answer->lastInsertId();

            // Store ID in $answerIDs as an array (or as a string, if you like).
            $allAnswerIDs[$questionID][] = $answerID;

            $correct = 0.0000000;
        }
    }

    foreach ($allAnswerIDs as $questionID => $questionAnswerIDs) {
        // Build a string of the answers per question.
        $array_size = count($questionAnswerIDs);
        $x = 0;

        $numerical->query("INSERT INTO mdl_question_numerical_options(question, showunits) VALUES(:question, :showunits)",array("question"=> $questionID, "showunits"=> 3));

        for ($i=1; $i <= $array_size; $i++) {
            // Insert the numerical row using $questionID and $answerIDs
            $numerical->query("INSERT INTO mdl_question_numerical(question, answer) VALUES(:question, :answer)",array("question"=> $questionID,"answer"=>$questionAnswerIDs[$x]));
            $x++;
        }
    }

    echo "Got Numerical.<br>";    
}

// matching type
if (in_array("Matching Type", $entries)) {

    // slice array starting from $end and stop at the result of $end-$sMT
    $entries2 = array_slice($entries, $sMT, ($end-$sMT));

    // remove the key that contains "Matching Type"
    $indexMatchingtype = array_search('Matching Type', $entries2);
    unset($entries2[$indexMatchingtype]);

    $answerIDs          = "";
    $questionID         = "";

    $allAnswerIDs = array();

    foreach ($entries2 as $entry) {
        // for the code column
        $code = mt_rand(000000000,999999999); 

        if(is_numeric(substr($entry, 0, 1)) === true) {
            $c_entry = substr($entry, 7, strlen($entry)-1);

            // Insert question as you do now.
            $question->query("INSERT INTO mdl_question(category, name, questiontext, questiontextformat, generalfeedbackformat, qtype, timecreated, timemodified, createdby, modifiedby) VALUES(:category, :name, :questiontext, :questiontextformat, :generalfeedbackformat, :qtype, :timecreated, :timemodified, :createdby, :modifiedby)",array("category"=> $category, "name"=> $c_entry, "questiontext"=> $c_entry, "questiontextformat"=> 1, "generalfeedbackformat"=> 1, "qtype"=> "match", "timecreated"=> time(), "timemodified" => time(), "createdby"=> $user_id, "modifiedby"=> $user_id));
            $questionID = $question->lastInsertId();
            
            // Store the question ID in the array.
            $allAnswerIDs[$questionID] = array();

            $entry2 = substr($entry, 3, strlen($entry)-1).'<br>';

            if(substr($entry2, 2, 1) == ")")
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 1);
            }

            else
            {
                // get obe category (FI S M)
                $cat = substr($entry2, 1, 2);
            }   

            // Insert question id and $cat
            $obe->query("INSERT INTO mdl_question_obe(question, obe_category) VALUES(:question, :obe_category)", array("question" => $questionID, "obe_category" => $cat));

        } else {

            if(substr($entry, 0, 1) == "Q")
            {
                $entry = substr($entry, 2, strlen($entry)-1);

                // Insert question id and question text as you do now.
                $matchingtype->query("INSERT INTO mdl_question_match_sub(code, question, questiontext, questiontextformat) VALUES(:code, :question, :questiontext, :questiontextformat) ",array("code"=>$code, "question"=>$questionID, "questiontext"=>$entry, "questiontextformat"=>1));

                $answerID = $matchingtype->lastInsertId();
                
                // Store ID in $answerIDs as an array (or as a string, if you like).
                $allAnswerIDs[$questionID][] = $answerID;
            }

            else
            {
                $entry = substr($entry, 2, strlen($entry)-1);

                // Update the answertext column
                $matchingtype->query("UPDATE mdl_question_match_sub SET answertext = :a WHERE id = :i",array("a"=>$entry, "i"=>$answerID));
            }

        }

    }

    foreach ($allAnswerIDs as $questionID => $questionAnswerIDs) {
        // Build a string of the answers per question.
        $answerIDs = implode(',', $questionAnswerIDs);

        // Insert the match row using $questionID and $answerIDs
        $matchingtype->query("INSERT INTO mdl_question_match(question, subquestions, correctfeedbackformat, partiallycorrectfeedbackformat, incorrectfeedbackformat) VALUES(:question, :subquestions, :correctfeedbackformat, :partiallycorrectfeedbackformat, :incorrectfeedbackformat) ",array("question"=>$questionID, "subquestions"=>$answerIDs, "correctfeedbackformat"=>1, "partiallycorrectfeedbackformat"=>1, "incorrectfeedbackformat"=>1));
    }

    echo "Got Matching Type.<br>";    
}

echo '<br>('.$q.') questions are uploaded!<br>';
echo '<br><a href="edit.php?courseid='.$course_id.'">Preview Questions<a>';
echo "</div>\n";

echo $OUTPUT->footer();