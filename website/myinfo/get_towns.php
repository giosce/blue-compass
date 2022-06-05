<?php

include("../db-a.php");

$county = $_GET["county"];

$sql="select muni_from_alpha_state from municipal_list where county = '".$county."' order by 1";

//echo $sql;
//echo "<br>";

$query = mysqli_query($conn, $sql);

$myArray = Array();
while($row = mysqli_fetch_array($query)) {
	$myArray[] = $row["muni_from_alpha_state"];
}
echo json_encode($myArray);
ob_end_flush();
?>