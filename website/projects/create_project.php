<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

include("../db.php");

$sql="select * from survey_question a, survey_question_response b
where b.surv_ques_id = a.surv_ques_id
order by a.surv_ques_type_code, a.surv_ques_name";

//echo "$sql";

$questQuery = mysqli_query($conn, $sql);

$sql2 = "SELECT * FROM project_type order by 2";
$query2 = mysqli_query($conn, $sql2);

?>

<html>
  <head>
    <title>BlueCompass.org Voter Outreach Projects</title>
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
<BR>
<H2>Voter Outreach New Project</H2>
</CENTER>
<BR>

<form action="save_project.php" method="post">
<table border=1 id="myTable">
<caption>Project Data</caption>
<tr>
<td>Project Name:</td><td><input type="text" name="proj_name"></td>
<td>Project Type</td>
<td>
<select name="proj_type_code">
<option value=""></option>
<?php
while($row2 = mysqli_fetch_array($query2)) {
	echo "<option value=".$row2["proj_type_code"].">".$row2["proj_type_text"]."</option>";
}
?>
</select>
</td>
</tr>
<tr>
<td>Organization</td><td><input type="text" name="proj_org_name"></td>
<td>Creator</td><td><input type="text" name="proj_creator_name"></td>
</tr>
<!--
<tr>
<td>Doors</td><td><input type="text" name="proj_door_qty" value=""></td>
<td>Voters</td><td><input type="text" name="proj_voter_qty" value=""></td>
</tr>
-->
</table>
<BR>
<BR>
Pick the Questions for your Project from the list below
</BR>
<table id="questions-table" border="1">
<tr>
<th>Select</th><th>Expand</th><th>Question Type &nbsp;</th><th>Question Text</th><th>Question Name</th><th>Multiple Responses?</th>
</tr>


<?php
$qId;
while($row = mysqli_fetch_array($questQuery)) {
	$quesId = $row["surv_ques_id"];
	if($quesId != $qId) {
		echo "<tr>";
			echo "<td><input type='checkbox' name='surv_ques_id[]' value='".$row["surv_ques_id"]."'></td>";
			echo "<td align='center'><a href='javascript:toggleRow(".$row["surv_ques_id"].")'>+</a></td>";
			echo "<td>".$row["surv_ques_type_code"]."</td>";
			echo "<td>".$row["surv_ques_text"]."</td>"; 
			echo "<td>".$row["surv_ques_name"]."</td>";
			echo "<td>".$row["allow_multiple_response"]."</td>";			
		echo "</tr>";
		echo "<tr style='display:none' name='".$quesId."'>";
			echo "<th></th>";
			echo "<th></th>";		
			echo "<th>Response Code</th>";
			echo "<th>Response Text</th>"; 
		echo "</tr>";
	}
	echo "<tr style='display:none' name='".$quesId."'>";
		echo "<td></td>";
		echo "<td></td>";		
		echo "<td>".$row["surv_resp_code"]."</td>";
		echo "<td>".$row["surv_resp_text"]."</td>"; 
	echo "</tr>";
	$qId = $quesId;
}
//print_r($quests);

?>
</table>

<BR>

<input type="Submit" value="Save Project">
<input type="Reset" value="Cancel" onclick="window.history.back()">
</form>
<BR>
After the project is created you can associate a list of voters to it from the voters query page

<script type="text/javascript">

function toggleRow(qId) {
	//window.alert("add response for question " + qId);
    var rows = document.getElementsByName(qId);
	var i;
    for (i = 0; i < rows.length; i++) {
		var el = rows[i];
		if(el.style.display == 'none') {
			el.style.display = '';
		} else {
			el.style.display = 'none';
		}
	}
}

</script>

</body>
</html>
<?php
ob_end_flush();
?>