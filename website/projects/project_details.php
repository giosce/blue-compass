<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

$proj_id = $_GET["proj_id"];

$sql="select * from project a, project_contact b, voter c
where b.proj_id = a.proj_id and c.voter_id = b.voter_id
and a.proj_id=".$proj_id." 
order by street_name, street_num";

//echo "$sql";

$query = mysqli_query($conn, $sql);

$surveyQuestionSql = "select *, 
(select count(*) from project_contact pc where pc.proj_id = p.proj_id) as voter_qty,
(select count(distinct concat(street_num, street_name)) 
from voter v, project_contact pc where pc.proj_id = p.proj_id and v.voter_id = pc.voter_id) as door_qty
from project p 
left join project_survey_question a on a.proj_id = p.proj_id
left join survey_question b on b.surv_ques_id = a.surv_ques_id 
where p.proj_id=".$proj_id." 
order by b.surv_ques_id";

//echo "<BR>";
//echo "$surveyQuestionSql";

$surveyQuestionQuery = mysqli_query($conn, $surveyQuestionSql);



$surveyAnswerSql = "select a.surv_ques_id, surv_ques_name, surv_resp_code
from survey_question a, survey_question_response b, project_survey_question c
where b.surv_ques_id = a.surv_ques_id
and a.surv_ques_id = c.surv_ques_id
and c.proj_id=".$proj_id." order by a.surv_ques_id";

$surveyAnswerQuery = mysqli_query($conn, $surveyAnswerSql);

$voterAnswersSql = "select b.voter_id, b.surv_ques_id, b.surv_resp_id
from project_contact a
join survey_responses b on b.proj_id = a.proj_id and b.voter_id = a.voter_id
where a.proj_id=".$proj_id."
and contact_date <> '0000-00-00'
order by b.voter_id, surv_ques_id, surv_resp_id";

$voterAnswersQuery = mysqli_query($conn, $voterAnswersSql);


$s_ques_id;
$answers = array();
$x = -1;
$y = 0;
while($row3 = mysqli_fetch_array($surveyAnswerQuery)) {
	if($s_ques_id !=  $row3["surv_ques_id"]) {
		$x++;
		$y = 0;
		$answers[$x] = array();
		$answers[$x][$y] = $row3["surv_ques_name"];
	}
	$s_ques_id =  $row3["surv_ques_id"];
	$y++;
	$answers[$x][$y] = $row3["surv_resp_code"];
}
//print_r($answers);

$voter_answers = array();
$voter_id;
$ques_id;
$q=0;
while($row4 = mysqli_fetch_array($voterAnswersQuery)) {
	//print_r($row4);
	//echo "<br>";
	if($voter_id !=  $row4["voter_id"]) {
		//if(!empty($voter_id)) {
		//	echo $voter_id." : ";
		//	print_r($voter_answers[$voter_id]);
		//	echo "<br>";
		//}
		$ques_id="";
		$voter_id =  $row4["voter_id"];
		$voter_answers[$voter_id] = array();
	}
	if($ques_id != $row4["surv_ques_id"]) {
		$ques_id = $row4["surv_ques_id"];
		//if(!empty($ques_id)) {
		//	echo "qid: ".$ques_id." : ";
		//	echo $row4["surv_ques_id"]." : ";
		//	print_r($voter_answers[$voter_id][ques_id]);
		//	echo "<br>";
		//}			
		$voter_answers[$voter_id][$ques_id] = array();
		//$q=0;
	}
	$voter_answers[$voter_id][$ques_id][] = $row4["surv_resp_id"];
	//$q++;
}
echo "<br>";
print_r($voter_answers);
echo "<hr>";
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
<script src="jquery-3.3.1.min.js"></script>
<script lang="javascript" src="xlsx.full.min.js"></script>
<script lang="javascript" src="FileSaver.min.js"></script>	
  </head>
  <body>

<a href="../index.html">Home</a>
<a href="index.php">Projects</a>

<CENTER>
<H2>Voter Outreach Project Details</H2>
</CENTER>
<BR>

<!-- display project details like name, person, questions, possible answers -->
<table border=0>
<?php
$a=0;
$proj_name_date = "";
while($row2 = mysqli_fetch_array($surveyQuestionQuery)) {
	if($proj_name_date == "") {
		$proj_name_date = $row2["proj_name"]."_".$row2["proj_create_date"];
	}
	echo "<tr>";
	if($a == 0) {
		// display projec name, etc
		echo "<td>".$row2["proj_type_code"]."</td><td>".$row2["proj_name"]."</td>";
		echo "</tr><tr>";
		echo "<td>".$row2["proj_create_date"]."</td><td>".$row2["proj_org_name"]."</td>";
		echo "</tr><tr>";
		echo "<td>".$row2["proj_creator"]."</td><td>".$row2["proj_status"]."</td>";
		echo "</tr><tr>";
		echo "<td>Doors: ".$row2["door_qty"]."</td><td>Voters: ".$row2["voter_qty"]."</td>";
		echo "</tr>";
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr>";
		echo "<th>Quest Code</th><th>Multiple Answer</th><th>Quest Name</th><th>Quest Text</th>";
		echo "</tr><tr>";
		echo "<td border=1>".$row2["surv_ques_type_code"]."</td><td>".$row2["allow_multiple_response"]."</td>";
		echo "<td>".$row2["surv_ques_name"]."</td><td>".$row2["surv_ques_text"]."</td>";
	} else {
		echo "<td>".$row2["surv_ques_type_code"]."</td><td>".$row2["allow_multiple_response"]."</td>";
		echo "<td>".$row2["surv_ques_name"]."</td><td>".$row2["surv_ques_text"]."</td>";
	}
	echo "</tr>";
	$a++;
}	
?>
</table>
<BR>
<BR>
<table border=1 class="sortable" id="data-table">
<tr>
<th>Voter ID</th><th>First Name</th><th>Last Name</th>
<th>Address</th><th>Party</th><th>Gender</th><th>Age</th>
<!--<th>Contact Meth</th>-->
<th>Contact Date</th><th>Contact Result</th>
<th>Survey</th>
<?php

$questions = [];
while($row2 = mysqli_fetch_array($surveyQuestionQuery)) {
	$questions[] = $row2[4];
}

$rowcount=mysqli_num_rows($query);

$i = 0;
while($row = mysqli_fetch_array($query)) {
	if($i == 0) {
		$prev_row = $row;
		$i++;
		continue;
	}
	$answ = $voter_answers[$prev_row["voter_id"]];
	echo "<tr>";
		echo "<td><a href='project_voter_details.php?proj_id=".$proj_id."&voter_id=".$prev_row["voter_id"]."&next_voter_id=".$row["voter_id"]."'>".$prev_row["voter_id"]."</td>";
		echo "<td>".$prev_row["first_name"]."</td>";
		echo "<td>".$prev_row["last_name"]."</td>";
		echo "<td>".$prev_row["street_num"]." ".$prev_row["street_name"]." ".$prev_row["muni_name"]."</td>";
		echo "<td>".$prev_row["party_code"]."</td>";
		echo "<td>".$prev_row["gender_code"]."</td>";
		echo "<td>".$prev_row["age"]."</td>";
		//echo "<td>".$prev_row["contact_comm_method_code"]."</td>";
		echo "<td>".$prev_row["contact_date"]."</td>";
		echo "<td>".$prev_row["contact_result_code"]."</td>";
		echo "<td>";
		for($k=0; $k < count($answers); $k++) {
			$val = "";
			for($j=0; $j < count($answers[$k]); $j++) {
				$val = $val." ".$answers[$k][$j];
				if($j == 0) {
					$val = $val.": ";
				} else {
					if(!empty($answ)) {
						print_r($answ[$k]);
					}
				}
			}
			echo $val;
			echo "<BR>";
		}
		echo "</td>";
	echo "</tr>";
	$prev_row = $row;
	$i++;
	// need to print the last record...	
	if($i == $rowcount) {
		echo "<tr>";
			echo "<td><a href='project_voter_details.php?proj_id=".$proj_id."&voter_id=".$prev_row["voter_id"]."'>".$prev_row["voter_id"]."</td>";
			echo "<td>".$prev_row["first_name"]."</td>";
			echo "<td>".$prev_row["last_name"]."</td>";
			echo "<td>".$prev_row["street_num"]." ".$prev_row["street_name"]." ".$prev_row["muni_name"]."</td>";
			echo "<td>".$prev_row["party_code"]."</td>";
			echo "<td>".$prev_row["gender_code"]."</td>";
			echo "<td>".$prev_row["age"]."</td>";
			//echo "<td>".$prev_row["contact_comm_method_code"]."</td>";
			echo "<td>".$prev_row["contact_date"]."</td>";
			echo "<td>".$prev_row["contact_result_code"]."</td>";
		echo "</tr>";
	}
}
$file_name = $proj_name_date."_walk-list";
?>
</table>
<BR>
<?php echo $rowcount." Records Found";?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="javascript:saveToFile('xlsx', '<?php echo $file_name.".xlsx"?>');">Export to Excel</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<BR>

<script>
function saveToFile(type, fn, dl) {
	var elt = document.getElementById('data-table');
	//window.alert(elt);
	var wb = XLSX.utils.table_to_book(elt, {sheet:"County Data"});
	//window.alert(wb);
	return dl ?
		XLSX.write(wb, {bookType:type, bookSST:true, type: 'base64'}) :
		XLSX.writeFile(wb, fn || ('county_data.' + (type || 'xlsx')));
}
</script>

</body>
</html>
<?php
ob_end_flush();
?>