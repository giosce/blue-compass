<?php
session_start();

include("db-a.php");

$tbl_name="members"; 


$myusername=$_POST['myusername']; 
$mypassword=$_POST['mypassword']; 

// To protect MySQL injection (more detail about MySQL injection)
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
#$myusername = mysqli_real_escape_string($conn, $myusername);
#$mypassword = mysqli_real_escape_string($conn, $mypassword);

$sql="SELECT * FROM $tbl_name WHERE username='$myusername' and password='$mypassword'";
$query = mysqli_query($conn, $sql);

#echo mysqli_error($conn);

#echo "db: ".$db_name;
#echo "<br>";
#echo $sql;

$count = mysqli_num_rows($query);
#echo "<br>count: ".$count;

if($count==1){
	// Register $myusername, $mypassword and redirect to file "login_success.php"
	//session_register("myusername");
	//("mypassword"); 
	$_SESSION['myusername'] = $myusername;
	//echo ($_SESSION['myusername']);
	header("Location:".$_SESSION['origin']); // can I say original page?
}
else {
	echo "Wrong Username or Password";
}

ob_end_flush();
?>
