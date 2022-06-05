<?php
session_start();

if(!isset($_SESSION['myusername'])) {
	$_SESSION['origin'] = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
	header("Location:../main_login.php");
}
//$_SESSION['origin'] = "";

include("../db-a.php");

$cong_dist = $_GET["cong_dist"];
$leg_dist = $_GET["leg_dist"];
$county = $_GET["county"];
//$id = $_GET["id"];

$sql="select muni_id, town from municipal_list_new where";

if (!empty($cong_dist)) {
	$cong_dist = substr($cong_dist, 2);
	if(substr($cong_dist, 0, 1) == "0") {
		$cong_dist = substr($cong_dist, 1);
	}
	$sql = $sql." cd = ".$cong_dist;
}

if (!empty($leg_dist)) {
	$leg_dist = substr($leg_dist, 2);
	if(substr($leg_dist, 0, 1) == "0") {
		$leg_dist = substr($leg_dist, 1);
	}
	$sql = $sql." ld = ".$leg_dist;
}

if (!empty($county)) {
	$sql = $sql." county = '".$county."'";
}

$sql = $sql." order by town";

//echo $sql;
//echo "<br>";

$query = mysqli_query($conn, $sql);

$myArray = Array();
while($row = mysqli_fetch_array($query)) {
	//$myArray[] = $row["town"];
	$myArray[] = $row;
}
echo json_encode($myArray);
ob_end_flush();
?>