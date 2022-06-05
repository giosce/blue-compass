<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

$proj_id = $_GET["proj_id"];
$voter_id = $_GET["voter_id"];
$next_voter_id = $_GET["next_voter_id"];

$sql="select * from project a, project_contact b, voter c
where b.proj_id = a.proj_id and c.voter_id = b.voter_id
and a.proj_id=".$proj_id." and c.voter_id = '".$voter_id."'";

//echo "$sql";

$query = mysqli_query($conn, $sql);

//select * from project a
//join project_contact b on b.proj_id = a.proj_id 
//join voter c on c.voter_id = b.voter_id 
//left join survey_responses d on d.proj_id = a.proj_id and d.voter_id = c.voter_id
//where a.proj_id=".$proj_id." and c.voter_id = '".$voter_id."'

$responsesSQl = "select * from survey_responses
where proj_id=".$proj_id." and voter_id = '".$voter_id."'
order by surv_ques_id";
$responsesQuery = mysqli_query($conn, $responsesSQl);

$surveyQuestionSql = "select b.surv_ques_id, surv_ques_text, allow_multiple_response 
from project_survey_question a, survey_question b
where b.surv_ques_id = a.surv_ques_id and a.proj_id=".$proj_id."  
order by b.surv_ques_id";

//echo "<BR>";
//echo "$surveyQuestionSql";

$surveyQuestionQuery = mysqli_query($conn, $surveyQuestionSql);

$surveyQuestionResponseSql = 
"select a.surv_ques_id, a.surv_resp_code, surv_resp_text  
from survey_question_response a, project_survey_question b
where b.surv_ques_id = a.surv_ques_id and b.proj_id=".$proj_id."  
order by a.surv_ques_id, a.surv_resp_code";

//echo "<BR>";
//echo "$surveyQuestionResponseSql";
// these would be to construct dropdown (for single answers) or checkbox (for multiple answers)
//echo "<BR>";

$surveyQuestionResponseQuery = mysqli_query($conn, $surveyQuestionResponseSql);

?>

<html>
  <head>
    <title>BlueCompass.org - Voter Outreach Projects</title>
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

<a href="../index.html">Home</a>
<a href="index.php">Projects</a>

<CENTER>
<H2>Voter Outreach Project Voter Detail</H2>
</CENTER>
<BR>

<table border=0>

<?php

$questions = array();
$multiRespQuest = array();
while($row2 = mysqli_fetch_array($surveyQuestionQuery)) {
	//echo $row2["surv_ques_id"].", ".$row2["surv_ques_text"];
	//echo "<BR>";
	$questions[$row2["surv_ques_id"]] = $row2["surv_ques_text"];
	$multiRespQuest[$row2["surv_ques_id"]] = $row2["allow_multiple_response"];
}
//echo "<BR>";
//print_r($questions);
//print_r($multiRespQuest);

$responses = array();
while($row3 = mysqli_fetch_array($surveyQuestionResponseQuery)) {
	//echo "<BR>";
	$k = $row3["surv_ques_id"];
	$v = $row3["surv_resp_code"];
	$t = $row3["surv_resp_text"];
	//echo $k.", ".$v.", ".$t;
	$responses[$k][$v] = $t;
}
//echo "<BR>";
//print_r($responses);

//echo "<BR><HR><BR>";

$voter_responses = array();
$k = "";
$v = "";
while($row4 = mysqli_fetch_array($responsesQuery)) {
	//echo "<BR>";
	if($k != $row4["surv_ques_id"]) {
		$k = $row4["surv_ques_id"];
		$voter_responses[$k] = array();
	}
	$v = $row4["surv_resp_id"];
	//echo $k.", ".$v.", ".$t;
	$voter_responses[$k][] = $v;
}
//echo "<BR>";
//print_r($voter_responses);

//echo "<BR><HR><BR>";

$row = mysqli_fetch_array($query);
$contact_result = $row["contact_result_code"];
echo "<form action='save_project_contact.php' method='post'>";
echo "<tr>";
	echo "<td><B>Voter ID:</B> ".$row["voter_id"]."</td>";
	echo "<td><B>First & Last Name:</B> ".$row["first_name"]." ".$row["last_name"]."</td>";
	//echo "<td>".$row["last_name"]."</td>";
echo "</tr>";
echo "<tr>";
	echo "<td colspan=3><B>Address:</B> ".$row["street_num"]." ".$row["street_name"]." ".$row["muni_name"]."</td>";
echo "</tr>";
echo "<tr>";
	echo "<td><B>Party:</B> ".$row["party_code"]."</td>";
	echo "<td><B>Gender:</B> ".$row["gender_code"]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <B>Age:</B> ".$row["age"]."</td>";
	//echo "<td>Age: ".$row["age"]."</td>";
echo "</tr>";
echo "<tr><td>&nbsp;</td></tr>";
echo "<tr>";
	echo "<td>Date Contact: <input type='date' name='contact_date' value=".date("Y-m-d")."></td>";
	echo "<td>Contact Result: ";
	//echo "<td>";
		echo "<select name='contact_result'>";
		echo "<option value=''></option>";
		if($contact_result == "NH") {
			echo "<option value='NH' selected>Not Home</option>";
		} else {
			echo "<option value='NH'>Not Home</option>";
		}
		if($contact_result == "IN") {
			echo "<option value='IN' selected>Inaccessible</option>";
		} else {
			echo "<option value='IN'>Inaccessible</option>";
		}
		if($contact_result == "SENT") {
			echo "<option value='SENT' selected>Sent Mail</option>";
		} else {
			echo "<option value='SENT'>Sent Mail</option>";
		}
		echo "</select>";
	echo "</td>";
echo "</tr>";
echo "<tr><td>&nbsp;</td></tr>";
foreach ($questions as $key => $value) {
	echo "<tr>";
		echo "<td>$value</td>";
		echo "<td>";
			$resp = $voter_responses[$key];
			if($multiRespQuest[$key] != 'Y') {
				//$response
				echo "<select name='$key'>";
				echo "<option value=''></option>";
				$answers = $responses[$key];
				//print_r($answers);
				foreach($answers as $k => $v) {
					if($resp[0] == $k) {
						echo "<option value='$k' selected>$v</option>";
					} else {
						echo "<option value='$k'>$v</option>";
					}
				}
				echo "</select>";
			} else {
				//echo "<input type='checkbox' name='issue[]' value='E'>E";
				//echo "<input type='checkbox' name='issue[]' value='G'>G";
				$answers = $responses[$key];
				//print_r($answers);
				foreach($answers as $k => $v) {
					if(in_array($k, $resp)) {
						echo "<input type='checkbox' name='".$key."[]' value='$k' title='$v' checked>$k &nbsp;&nbsp;";
					} else {
						echo "<input type='checkbox' name='".$key."[]' value='$k' title='$v'>$k &nbsp;&nbsp;";
					}
				}
			}
		echo "</td>";
	echo "</tr>";
	$i++;
}
echo "<input type='hidden' name='proj_id' value='".$proj_id."'>";
echo "<input type='hidden' name='voter_id' value='".$voter_id."'>";
echo "<input type='hidden' name='next_voter_id' value='".$voter_id."'>";

?>
</table>
<BR>
<input type="Submit" value="Save">
</form>
<BR>
</body>
</html>
<?php
ob_end_flush();
?>