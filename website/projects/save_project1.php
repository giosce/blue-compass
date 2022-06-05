<?php
// debugging setting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");

session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}
$host="giovannisce.net"; // Host name 
$username="giova_giova"; // Mysql username 
$password="Cristina!70"; // Mysql password 
$db_name="giova_nj_cd_7"; // Database name 

$conn = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

print_r($_POST);
echo "<BR>";

$proj_name = $_POST["proj_name"];
$proj_type_code = $_POST["proj_type_code"];
$proj_org_name = $_POST["proj_org_name"];
$proj_creator_name = $_POST["proj_creator_name"];
$proj_door_qty = $_POST["proj_door_qty"];
$proj_voter_qty = $_POST["proj_voter_qty"];
$proj_create_date = date("Y-m-d");
$proj_status = "New";

$sql1 = "insert into project values (null, '".$proj_type_code."', '".$proj_name."', '". $proj_create_date."', '".$proj_org_name."', '".
$proj_creator_name."', ". $proj_door_qty.", ". $proj_voter_qty.", '". $proj_status."')";

echo "<BR>";
echo "$sql1";
echo "<BR>";

// Transaction!!!
mysqli_query($conn, $sql1);
$proj_id = mysqli_insert_id($conn);


$questions_type_codes = $_POST["surv_ques_type_code"];
$surv_ques_text = $_POST["surv_ques_text"];
$surv_ques_name = $_POST["surv_ques_name"];
$surv_ques_multi_resps = $_POST["surv_ques_multi_resps"];

$quest_count = count($questions_type_codes);

echo "To insert ".$quest_count." for project ID ".$proj_id;
echo "<BR>";

// need to have a question - answers association from web page / javascript
//$surv_resp_text = $_POST["surv_resp_text"];
//$surv_resp_code = $_POST["surv_ques_name"];

$i = 0;
while($i < $quest_count) {
	$sql2 = "insert into survey_question values(null, '".$questions_type_codes[$i]."','".$surv_ques_text[$i]."','".$surv_ques_name[$i]."','".$surv_ques_multi_resps[$i]."')";
	echo "$sql2";
	mysqli_query($conn, $sql2);
	$q_id = mysqli_insert_id($conn);
	$i++;
	$sql3 = "insert into project_survey_question values($proj_id, $q_id)";
	mysqli_query($conn, $sql3);
}
?>