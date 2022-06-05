<?php

session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

//print_r($_POST);
//echo "<BR>";

$proj_name = $_POST["proj_name"];
$proj_type_code = $_POST["proj_type_code"];
$proj_org_name = $_POST["proj_org_name"];
$proj_creator_name = $_POST["proj_creator_name"];
$proj_door_qty = $_POST["proj_door_qty"];
$proj_voter_qty = $_POST["proj_voter_qty"];
$proj_create_date = date("Y-m-d");
$proj_status = "New";

if(empty($proj_door_qty)) {
	$proj_door_qty = "null";
}
if(empty($proj_voter_qty)) {
	$proj_voter_qty = "null";
}

$sql1 = "insert into project values (null, '".$proj_type_code."', '".$proj_name."', '". $proj_create_date."', '".$proj_org_name."', '".
$proj_creator_name."', ". $proj_door_qty.", ". $proj_voter_qty.", '". $proj_status."')";

echo "<BR>";
echo "$sql1";
echo "<BR>";

mysqli_autocommit($conn, FALSE);

mysqli_begin_transaction($conn);

$sqlResult = mysqli_query($conn, $sql1);

if (!$sqlResult) {
	die ('Failed to execute SQL: ' . mysqli_error($conn));	
}

$proj_id = mysqli_insert_id($conn);


//$questions_type_codes = $_POST["surv_ques_type_code"];
//$surv_ques_text = $_POST["surv_ques_text"];
//$surv_ques_name = $_POST["surv_ques_name"];
//$surv_ques_multi_resps = $_POST["surv_ques_multi_resps"];
$surv_ques_ids = $_POST["surv_ques_id"];

$quest_count = count($surv_ques_ids);

//echo "To insert ".$quest_count." for project ID ".$proj_id;
//echo "<BR>";

// need to have a question - answers association from web page / javascript
//$surv_resp_text = $_POST["surv_resp_text"];
//$surv_resp_code = $_POST["surv_ques_name"];

$i = 0;
while($i < $quest_count) {
	$q_id = $surv_ques_ids[$i];
	$i++;
	
	$sql2 = "insert into project_survey_question values($proj_id, $q_id)";
	
	$sqlResult = mysqli_query($conn, $sql2);
	
	if (!$sqlResult) {
		die ('Failed to execute SQL: ' . mysqli_error($conn));	
	}
}

if($sqlResult) {
	mysqli_commit($conn);
	echo "<BR>";
	echo "Survey questions saved";
	echo "<br>";
	echo $sql2;
	//header('Location: index.php');
	header('Location: http://bluecompass.org/projects/index.php');
} else {
	mysqli_rollback($conn);
	echo "<BR>";
	echo "Transaction rolled back";
}

mysqli_close($conn);

mysqli_autocommit($conn, TRUE);
?>