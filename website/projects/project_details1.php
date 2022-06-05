<?php
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

$county = $_GET["county"];
$muni = $_GET["muni"];
$year = $_GET["year"];
$type = $_GET["type"];

$sql="select * from project a, project_contact b, voter c
where b.proj_id = a.proj_id and c.voter_id = b.voter_id
and a.proj_id='".$_GET["proj_id"]."' 
order by c.voter_id";

echo "$sql";

$query = mysqli_query($conn, $sql);



$surveyQuestionSql = "select * from project_survey_question a, survery_question b
where b.surv_ques_id = a.surv_ques_id and a.proj_id='".$_GET["proj_id"]."'  
order by b.surv_ques_id";

echo "<BR>";
echo "$surveyQuestionSql";

$surveyQuestionQuery = mysqli_query($conn, $surveyQuestionSql);

$surveyQuestionResponseSql = 
"select * from survey_question_response a, project_survey_question b
where b.surv_ques_id = a.surv_ques_id and b.proj_id='".$_GET["proj_id"]."'  
order by a.surv_ques_id, a.surv_resp_code";

echo "<BR>";
echo "$surveyQuestionResponseSql";
// these would be to construct dropdown (for single answers) or checkbox (for multiple answers)
$surveyQuestionResponseQuery = mysqli_query($conn, $surveyQuestionResponseSql);

if ($resultQuestions = mysqli_query($conn, $surveyQuestionSql)) {
	$row_cnt = mysqli_num_rows($resultQuestions);
	if($row_cnt < 4) {
		// put them inline
	} else {
		// make voter_id clickable to open a full form for one voter
		// would need a mechanism to save+next
		
		// need a save anyway for record inline responses
	}
}
?>

<html>
  <head>
    <title>NJ07 Voter Outreach Projects</title>
	<script src="sorttable.js"></script>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}	
</style>	
  </head>
  <body>

<a href="http://technology4democracy.org">Home</a>
<a href="http://technology4democracy.org/nj7datahome2.php">NJ07</a>

<CENTER>
<H2>NJ07 - Voter Outreach Projects</H2>
</CENTER>
<BR>

<table border=1 class="sortable">
<tr>
<th>Voter ID</th><th>First Name</th><th>Last Name</th><th>Contact Date</th>
<th>Address</th><th>Party</th><th>Gender</th><th>Age</th><th>Contact Result</th>
<?php

$questions = [];
while($row2 = mysqli_fetch_array($surveyQuestionQuery)) {
	$questions[] = $row2[4];
}
	
while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
		echo "<td>".$row["voter_id"]."</td>";
		echo "<td>".$row["first_name"]."</td>";
		echo "<td>".$row["last_name"]."</td>";
		echo "<td><input type='text'></td>";
		echo "<td>".$row["street_num"]." ".$row["street_name"]." ".$row["muni_name"]."</td>";
		echo "<td>".$row["party_code"]."</td>";
		echo "<td>".$row["gender_code"]."</td>";
		echo "<td>".$row["age"]."</td>";
		echo "<td>";
			echo "<select name='contact_result'>";
			echo "<option value=''></option>";
			echo "<option value='NH'>NH</option>";
			echo "<option value='IN'>IN</option>";
			echo "</select>";
		echo "</td>";
		echo "<td>";
			echo "<table>";
			$i = 0;
			while($i < $row_cnt) {
				echo "<tr>";
					echo "<td>$questions[$i]</td>";
					echo "<td>";
					if($i == 0) {
							echo "<select name='contact_result'>";
							echo "<option value=''></option>";
							echo "<option value='Good'>Good</option>";
							echo "<option value='Bad'>Bad</option>";
							echo "</select>";
					} else if($i == 1) {
						echo "<input type='checkbox' name='issue[]' value='E'>E";
						echo "<input type='checkbox' name='issue[]' value='G'>G";
					}
					echo "</td>";
				echo "</tr>";
				$i++;
			}
			echo "</table>";
		echo "</td>";
		echo "<td><input type='Submit'></td>";
	echo "</tr>";
}
?>
</table>

</body>
</html>
<?php
ob_end_flush();
?>