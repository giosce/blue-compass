<?php

session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

//print_r($_POST);
//echo "<BR>";

$proj_id = $_POST["proj_id"];
$voter_ids = $_POST["voter_ids"];
$voter_ids2 = explode(",", $voter_ids);

$sql = "insert into project_contact (proj_id,voter_id) values";
for ($x = 0; $x < sizeof($voter_ids2); $x++) {
	$sql = $sql."(".$proj_id.", '".$voter_ids2[$x]."'),";
}
$sql = rtrim($sql, ",");

//echo "<BR>";
//echo "$sql";
//echo "<BR>";

$sqlResult = mysqli_query($conn, $sql);
	
if($sqlResult) {
	mysqli_commit($conn);
	//echo "<BR>";
	//echo "Project voters saved";
	header('Location: project_details.php?proj_id='.$proj_id);
} else {
	die ('Failed to execute SQL: ' . mysqli_error($conn));
}

mysqli_close($conn);

?>