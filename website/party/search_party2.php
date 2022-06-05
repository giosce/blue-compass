<?php
session_start();
$host="giovannisce.net"; // Host name 
$username="giova_giova"; // Mysql username 
$password="Cristina!70"; // Mysql password 
$db_name="giova_nj_cd_7"; // Database name 

$conn = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

$muni = $_GET["muni"];
$sql="SELECT ssn, ld FROM municipal_list WHERE town like '%$muni%'";
echo "$sql";

if ($result = mysqli_query($conn, $sql)) {

    /* determine number of rows result set */
    $row_cnt = mysqli_num_rows($result);

    printf("Result set has %d rows.\n", $row_cnt);

	if($row_cnt == 1){
		$row = mysqli_fetch_array($result);
		$m = $row["ssn"];
		$ld = $row["ld"];
		header("Location:leg_dist/ld$ld.php");
	}
	else {
		echo "List of towns";
	}
	
    /* close result set */
    mysqli_free_result($result);
} else {
	echo "Error";
}

ob_end_flush();
?>
