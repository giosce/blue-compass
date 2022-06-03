<?php

include("../db-a.php");

$county = $_GET["county"];

$sql="select distinct muni from candidates_view where county = '".$county."' order by 1";

//echo $sql;
//echo "<br>";

$query = mysqli_query($conn, $sql);

$myArray = Array();
while($row = mysqli_fetch_array($query)) {
	$myArray[] = $row["muni"];
}
echo json_encode($myArray);
ob_end_flush();
?>