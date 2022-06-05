<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

//print_r($_POST);
//echo "<BR>";

$proj_id = $_POST["proj_id"];
$voter_id = $_POST["voter_id"];

mysqli_autocommit($conn, FALSE);

mysqli_begin_transaction($conn);

$sqlResult;

$sql1 = "update project_contact set contact_date = '".$_POST["contact_date"]."'";
if(!empty($_POST["contact_result"])) {
	$sql1 = $sql1.", contact_result_code = '".$_POST["contact_result"]."'";
}
$sql1 = $sql1." where proj_id = '".$proj_id."' and voter_id = '".$voter_id."'";

echo "<BR>";
echo "$sql1";
$sqlResult = mysqli_query($conn, $sql1);

if (!$sqlResult) {
	die ('Failed to execute SQL: ' . mysqli_error($conn));	
}

foreach($_POST as $k => $v) {
	if($k != "contact_result" and $k != "proj_id" and $k != "voter_id" and $k != "next_voter_id" and $k != "contact_date") {
		if(is_array($v)) {
			foreach($v as $k2 => $v2) {
				if(!empty($v2)) {
					$sql2 = "insert into survey_responses values ('".$proj_id."', '".$voter_id."', '".$k."', '".$v2."')";
					//echo "<BR>";
					//echo "$sql2";
					$sqlResult = mysqli_query($conn, $sql2);
				}
			}
		} else {
			if(!empty($v)) {
				$sql2 = "insert into survey_responses values ('".$proj_id."', '".$voter_id."', '".$k."', '".$v."')";
				//echo "<BR>";
				//echo "$sql2";
				$sqlResult = mysqli_query($conn, $sql2);
			}
		}
		if (!$sqlResult) {
			die ('Failed to execute SQL: ' . mysqli_error($conn));	
		}
	}
}

if($sqlResult) {
	mysqli_commit($conn);
	echo "<BR>";
	echo "Survery results saved";
	header('Location: project_details.php?proj_id='.$proj_id);
} else {
	mysqli_rollback($conn);
	echo "<BR>";
	echo "Transaction rolled back";
}

mysqli_close($conn);

mysqli_autocommit($conn, TRUE);


// I need the next voter_id to redirect to edit of the next voter
//header("Location:main_login.php");
?>