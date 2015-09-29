<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once(dirname(__FILE__) . '/../config.php');
require_once '../local/myplugin/excel_reader/excel_reader2.php';
require("../local/myplugin/pdfparser/dbOption/Db.class.php");


$target_dir = "../local/myplugin/pdfparser/uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$db = new Db();

$course_id = $_SESSION['course_id'];    // get the sessioned data courseid


if(isset($_POST["submit"])) 
{
    //$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);


    // Check if file already exists
    /*if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }*/

    // Check file type
    /*if ($_FILES["fileToUpload"]["tmp_name"] != $fileType) 
    {
        echo "Sorry, your file is not PDF.";
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

            $data = new Spreadsheet_Excel_Reader($target_file);

            $html="<table border='1'>";

			$truncate   = $db->query("TRUNCATE TABLE mdl_transmutation");

				for($i=0;$i<count($data->sheets);$i++) // Loop to get all sheets in a file.
				{	
					if(count($data->sheets[$i][cells])>0) // checking sheet not empty
					{	
						echo "Sheet $i:<br /><br />Total rows in sheet $i  ".count($data->sheets[$i][cells])."<br />";
						for($j=1;$j<=count($data->sheets[$i][cells]);$j++) // loop used to get each row of the sheet
						{ 
							$html.="<tr>";
							for($k=1;$k<=count($data->sheets[$i][cells][$j]);$k++) // This loop is created to get data in a table format.
							{
								$html.="<td>";
								$html.=$data->sheets[$i][cells][$j][$k];
								$html.="</td>";
							}

							$gradefrom = $data->sheets[$i][cells][$j][1];
							$gradeto = $data->sheets[$i][cells][$j][2];
							$equivalent = $data->sheets[$i][cells][$j][3];

							$insert	 	=  $db->query("INSERT INTO mdl_transmutation(gradefrom, gradeto,equivalent) 	VALUES(:gradefrom, :gradeto,:equivalent)",array("gradefrom"=>$gradefrom,"gradeto"=>$gradeto,"equivalent"=>$equivalent));
							$html.="</tr>";
						}
					}

				}

				$html.="</table>";

				header('Location: view.php?courseid='.$course_id.'');
				exit;
				
        } 

        else 
        {
            echo "Sorry, there was an error uploading your file.";
            echo "<br/><br/>";
        }
    }

}

	
?>