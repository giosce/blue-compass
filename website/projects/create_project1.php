<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	header("Location:main_login.php");
}

$properties = parse_ini_file('properties.ini');

include("db.php");

$sql="SELECT * FROM survey_question_type order by 2";

//echo "$sql";

$query = mysqli_query($conn, $sql);

$sql2 = "SELECT * FROM project_type order by 2";
$query2 = mysqli_query($conn, $sql2);

?>

<html>
  <head>
    <title>NJ07 Voter Outreach Projects</title>
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
<BR>
<H2>NJ07 - Voter Outreach Projects</H2>
</CENTER>
<BR>

<form action="save_project.php" method="post">
<table border=1 id="myTable">
<tr>
<td>Project Name:</td><td><input type="text" name="proj_name"></td>
<td>Project Type</td>
<td>
<select name="proj_type_code">
<?php
while($row2 = mysqli_fetch_array($query2)) {
	echo "<option value=".$row2["proj_type_code"].">".$row2["proj_type_text"]."</option>";
}
?>
</select>
</td>
</tr>
<tr>
<!--<th>Create Date</th><td><input type="text" name="proj_name"></td>-->
<td>Organization</td><td><input type="text" name="proj_org_name"></td>
<td>Person</td><td><input type="text" name="proj_creator_name"></td>
</tr>
<tr>
<td>Doors</td><td><input type="text" name="proj_door_qty" value="should be calculated by voter list"></td>
<td>Voters</td><td><input type="text" name="proj_voter_qty" value="should be calculated by voter list"></td>
<!--<th>Status</th><td><input type="text" name="proj_name"></td>-->
</tr>
</table>

<table id="questions-table" style="visibility:hidden">
<tr>
<td><B>Question Type</B></td><td><B>Question Text</B></td><td><B>Question Name</B></td><td><B>Multiple Responses?</B></td>
</tr>


<?php
//echo "<select id='surv_ques_type_code[]' name='surv_ques_type_code'>";
$quests = array();
while($row = mysqli_fetch_array($query)) {
	//echo "<option value=".$row["surv_ques_type_code"].">".$row["surv_ques_type_text"]."</option>"; 
	$quests[$row["surv_ques_type_code"]] = $row["surv_ques_type_text"]; 
	echo $row["surv_ques_type_code"]." => ".$row["surv_ques_type_text"];
}
print_r($quests);

?>
</table>
<BR>
<a href="javascript:addQuestion()">Add Question</a>
<BR>
<BR>
<input type="Submit" value="Save Project">
</form>
<BR>

<script type="text/javascript">

var q = JSON.parse('<?php echo json_encode($quests); ?>');
console.log(q);
console.log(q.toString());

function addQuestion() {
    var table = document.getElementById("questions-table");
    var row = table.insertRow(-1);
    var cell1 = row.insertCell(0);
    var cell2 = row.insertCell(1);
	var cell3 = row.insertCell(2);
	var cell4 = row.insertCell(3);
    var cell5 = row.insertCell(4);
	cell1.innerHTML = "<select id='surv_ques_type_code[]' name='surv_ques_type_code[]'></select>";
    cell2.innerHTML = "<input type='text' name='surv_ques_text[]'>";
	cell3.innerHTML = "<input type='text' name='surv_ques_name[]'>";
	cell4.innerHTML = "<select name='surv_ques_multi_resps[]'><option value='N'>No</option><option value='Y'>Yes</option></select>";
	cell5.innerHTML = "<a href='javascript:addResponse("+(row.rowIndex-1)+")'>Add Response</a> " + row.rowIndex;
	//window.alert(table);
	
	var sel = document.getElementById("surv_ques_type_code[]");
	// big issue, second question dropdown is empty. Probably because I don't save_project
	// the responses once pulled from DB (and used)?
	// Try to save in an html hidden element.
	
	var keys = Object.keys(q),
		len = keys.length,
		i = 0,
		prop,
		value;
	while (i < len) {
		prop = keys[i];
		value = q[prop];
		var opt = new Option(value, prop);
		sel.options.add(opt);
		i += 1;
	}	
	
	//window.alert(sel);
	table.style.visibility="visible";
	return 0;
}

function addResponse(qId) {
	window.alert("add response for question " + qId);
    var table = document.getElementById("questions-table");
    var row = table.insertRow(-1);
    var cell1 = row.insertCell(0);
    var cell2 = row.insertCell(1);
	var cell3 = row.insertCell(2);
	var cell4 = row.insertCell(3);
    //cell1.innerHTML = "";
    cell2.innerHTML = "Response";
	cell3.innerHTML = "<input type='text' name='surv_resp_text[]' value='Resp Text'>";
	cell4.innerHTML = "<input type='text' name='surv_resp_code[]' value='Resp Code'>";
}

</script>

</body>
</html>
<?php
ob_end_flush();
?>