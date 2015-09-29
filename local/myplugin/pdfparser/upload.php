<?php

// this file was used for documentation that's why it's not that occupied with codes

echo "<meta http-equiv='Content-Type' content='text/html;charset=utf-8' />";
echo "<a href='index.php'>Back to index page!</a><br/><br/>";

// Include Composer autoloader if not already done.
include 'vendor/autoload.php';
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);


if(isset($_POST["submit"])) 
{
    //$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);


    // Check if file already exists
    /*if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }*/
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

    // count pages
    $no_of_pages = 1;

    // Parse pdf file and build necessary objects.
    $parser = new \Smalot\PdfParser\Parser();

    $pdf    = $parser->parseFile($file);

    // Retrieve all pages from the pdf file.
    $pages = $pdf->getPages();

    // Loop over each page to extract text.
    foreach ($pages as $page) 
    {
        // echo $no_of_pages;
        // echo '<br/>';
        //echo $page->getText();

        $no_of_pages++;

        $str = $page->getText();

        $entries = preg_split('/(\n)/', $str, -1, PREG_SPLIT_NO_EMPTY);

        echo "<pre>";
        print_r($entries);
        echo "<pre/>";
         
        // business logic goes here and use preg_match() for dot or parenthesis
        
        // code goes here

        /*$entries = preg_split('/(\n)/', $str, -1, PREG_SPLIT_NO_EMPTY);

        echo $perLine = implode("<br>", $entries);
        
        $pattern = '/Multiple/';

        if((preg_match($pattern, $perLine, $matches, PREG_OFFSET_CAPTURE)))
        {
          // $shit = implode("", $matches);
          echo '<br>Multiple shit is seen.<br>';  
        }

        $pattern = '/Matching/';

        if((preg_match($pattern, $perLine, $matches, PREG_OFFSET_CAPTURE)))
        {
          // $shit = implode("", $matches);
          echo '<br>Matching shit is seen.<br>';  
        }
        */

    }

        
?> 