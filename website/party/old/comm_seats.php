<?php

include("../db-a.php");

$county = $_GET["county"];
$town = $_GET["town"];
$status = $_GET["status"];
$election_year = $_GET["election_year"];

$sql_counties="select distinct county from dem_committee_members order by 1";

$sql_towns="select distinct town from dem_committee_members where county='".$county."'";
if(!empty($election_year)) {
	$sql_towns .= " and election_year = '".$election_year."'";
}
$sql_towns .= " order by 1";

$sql_seats="select county, town, case when ward='00' then '' else ward end as ward, precinct, member_name, member_role, gender  
from dem_committee_members where 1=1 ";
if(!empty($county)) {
	$sql_seats = $sql_seats." and county = '".$county."'";
}
if(!empty($town)) {
	$sql_seats = $sql_seats." and town = '".$town."'";
}
if($status == "Vacant") {
	$sql_seats = $sql_seats." and (member_name = 'vacant' or member_name = '' or member_name = 'no petition filed')";
} else if($status == "NotVacant") {
	$sql_seats = $sql_seats." and member_name <> 'vacant' and member_name <> '' and member_name <> 'no petition filed'";
}
if(!empty($election_year)) {
	$sql_seats = $sql_seats." and election_year = '".$election_year."'";
}

$sql_seats = $sql_seats." order by county, town, ward, precinct";

$sql_committee = "select * from dem_committee where county='".$county."'";
if(!empty($town)) {
	$sql_committee = $sql_committee." and (muni = '".$town."' or town='".$town."')";
} else {
	$sql_committee = $sql_committee." and muni = ''";
}

$sql = "select county, sum(count_seats) as seats, sum(count_no_petition) as vacancies
from committee_vacancies
group by county
order by county";

$sql2 = "select * from committee_vacancies
where count_no_petition > 1
order by county, muni";

$query_counties = mysqli_query($conn, $sql_counties);

$query_towns = mysqli_query($conn, $sql_towns);

$query_seats = mysqli_query($conn, $sql_seats);

$query_committee = mysqli_query($conn, $sql_committee);

//echo "<br>".$sql_seats."<br>";

?>


<html>
<head>
<title>BlueCompass.org - Democratic Party Committees</title>
<style type="text/css">	
.menu td:hover {
	background-color: blue; 
	color: white;
	text-shadow: -.25px -.25px 0 white, .25px .25px white;
	cursor: pointer;
}

.menu td {
	text-decoration: none;
	padding: 2px;
	color: blue;
}

.aa {
	text-decoration: none;
	color: blue;
	text-shadow: -.25px -.25px 0 transparent, .25px .25px transparent;	
}
.aa:hover {
	color: white;
	text-shadow: -.25px -.25px 0 white, .25px .25px white;
}

table.blueTable {
  font-family: Arial, Helvetica, sans-serif;
  border: 1px solid #1C6EA4;
  background-color: #F8F8F8;
  text-align: left;
  border-collapse: collapse;
}
table.blueTable td, table.blueTable th {
  border: 1px solid #AAAAAA;
  padding: 3px 2px;
}
table.blueTable tbody td {
  font-size: 13px;
  color: #000000;
}
table.blueTable tr:nth-child(even) {
  background: #C6F3F5;
}
table.blueTable thead {
  background: #1C6EA4;
  background: -moz-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  background: -webkit-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  background: linear-gradient(to bottom, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  border-bottom: 2px solid #444444;
}
table.blueTable thead th {
  font-size: 15px;
  font-weight: bold;
  color: #FFFFFF;
  border-left: 2px solid #D0E4F5;
}
table.blueTable thead th:first-child {
  border-left: none;
}

table.blueTable tfoot {
  font-size: 14px;
  font-weight: bold;
  color: #FFFFFF;
  background: #D0E4F5;
  background: -moz-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  background: -webkit-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  background: linear-gradient(to bottom, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  border-top: 2px solid #444444;
}
table.blueTable tfoot td {
  font-size: 14px;
}
table.blueTable tfoot .links {
  text-align: right;
}
table.blueTable tfoot .links a{
  display: inline-block;
  background: #1C6EA4;
  color: #FFFFFF;
  padding: 2px 8px;
  border-radius: 5px;
}

</style>
</head>

<body style="font-family:Arial">
<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../njlocal.html" class="aa">2019 Other NJ Local Elections</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">NJ Democratic Party</a></td>
</tr>
</table>

<H2><center>Democratic Party Committee seats</center></H2>

<a href="index.html">Party Home</a>&nbsp;
<a href="party_survey.html">Party Survey</a>&nbsp;
<a href="committees.php">Party Committees</a>&nbsp;
<a href="comm_seats.php">Committees Seats</a>&nbsp;
<!--
<a href="">How it works</a>&nbsp;
<a href="">How to run</a>
-->
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">Committee Member Role</a>
<br>
<br>
<a href="#table">Jump to the Committees members list</a>
<br>
<br>

The Party Committees play an important role on organizing political activities from local to national level.
<br>
The committees play also an important role with regard of how much the party is open, transparent and fair to everyone.
<br>
Unfortunately, the Democratic Party NJ Committees are not real champion on these characteristics and as such it is even very 
challenging to find information on their composition, bylaws, rules, meetings, discussions.
<br>
We believe that all this information should be public and publicized to encourage a larger number of residents to interact with 
the party in a mutually beneficial relationship.
<br>
<br>
The County Committees is made up of the members of the county Municipal Committees.
<br>
On this page you can find the list of committees members and when available, information like chairs, contacts and bylaws.
<br>
<br>
We encourage all residents to find out more about their municipal committee, these committees are usually small and if you are a slightly curios 
voter you may know someone in the committee. If you don’t know your “precinct” you can find it out <a href="../candidates"><b>here</b></a>.
<br>
Every few years (depending on the county) voters elect two Municipal Committee members (one man and one woman) also called Precinct Captains. 
These committee members represent the voters of the precinct and as such they are accountable and should engage with their constituents. 
If they don’t do so, it is your right to reach out to them.
<br>
In most Municipal Committees there usually empty seats. Vacancies can be filled in by the committee, so if your precinct has an empty seat 
we encourage you to reach out to someone in the committee to express your interest (among the committee chair duties there is to fill in vacancies).
Whether a seat is vacant or not, any precinct registered voter can run for a seat fairly easy. 
Legally, a candidate needs to deposit a petition with a variable number of signatures from precinct voters (typically around 5). 
From a practical point of view though it is pretty much necessary that you get agreement from the committee chair as, if the seat is 
contested your name will be placed outside of the party line (much more difficult to win).
<br>
<br>
If you are interested to run for a Committee seat, please check back this page often as we'll update it with up to date information
like the petition template, how many signatures are needed and whether a petition has been filled.
<br>
With the same spirit, a progressive group in NJ08 district has created a useful detailed document about running for a Committee seat 
<a href="https://www.nj08forprogress.org/countycommittee"><b>here</b></a>.
<br>
<br>
<a href="#table">Jump to the Committees members list</a>
<br>
<br>

<form>

<a name="table"></a>
<table border="0">
<tr>
<th>County</th><th>Town</th><th>Status</th><th>Next Election</th>
</tr>
<tr>

<td>
<select name="county" onchange="document.getElementById('select_town').selectedIndex='0'; this.form.submit();">
<option value=''></option>
<?php
while($row_c = mysqli_fetch_array($query_counties)) {
	$cty = $row_c["county"];
	if($cty == $county) {
		echo "<option selected value='".$cty."'>".$cty."</option>";
	} else {
		echo "<option value='".$cty."'>".$cty."</option>";
	}
}
?>
</select>
</td>

<td>
<select name="town" id="select_town" onchange="this.form.submit();">
<option value=''></option>
<?php
while($row_t = mysqli_fetch_array($query_towns)) {
	$twn = $row_t["town"];
	if($twn == $town) {
		echo "<option selected value='".$twn."'>".$twn."</option>";
	} else {
		echo "<option value='".$twn."'>".$twn."</option>";
	}
}
?>
</select>
</td>

<td>
<select name="status" id="select_status" onchange="this.form.submit();">
	<option value=''></option>
	<?php
	if($status == "Vacant") {
		echo "<option selected value='Vacant'>Vacant</option>";
	} else {
		echo "<option value='Vacant'>Vacant</option>";
	}
	if($status == "NotVacant") {
		echo "<option selected value='NotVacant'>Not Vacant</option>";
	} else {
		echo "<option value='NotVacant'>Not Vacant</option>";
	}
	?>
</select>

<td>
<select name="election_year" id="select_election_year" onchange="this.form.submit();">
<?php
	if($election_year == "") {
		echo "<option selected value=''></option>";
	} else {
		echo "<option value=''></option>";
	}
	if($election_year == "2020") {
		echo "<option selected value='2020'>2020</option>";
	} else {
		echo "<option value='2020'>2020</option>";
	}
	if($election_year == "2021") {
		echo "<option selected value='2021'>2021</option>";
	} else {
		echo "<option value='2021'>2021</option>";
	}
?>	
</select>

</tr>
</table>

</form>


<table border="0">
<?php
$committee = mysqli_fetch_array($query_committee);
if(!empty($committee)) {
	echo "<tr>";
	echo "<td>Chair: ".$committee["chair_name"]."</td>"; 
	echo "<td>Chair email: <a href='mailto: ".$committee["chair_email"]."'>".$committee["chair_email"]."</a></td>"; 
	echo "</tr>";
	echo "<tr>";
	echo "<td>Comm Email: <a href='mailto: ".$committee["committee_email"]."'>".$committee["committee_email"]."</a></td>"; 
	echo "<td>Website: <a href='".$committee["website"]."'>".$committee["website"]."</a></td>"; 
	echo "</tr>";
	if(!empty($committee["bylaws"])) {
		echo "<tr>";
		echo "<td>ByLaws: <a href='mailto: ".$committee["bylaws"]."'>ByLaws</a></td>"; 
		echo "</tr>";
	}
}
?>
</table>
<br>
<?php
$num_rows = mysqli_num_rows($query_seats);
echo $num_rows . " rows retrieved";

if($num_rows > 0) {
	echo "<table border='1'>";
	echo "<tr>";
	echo "<th>County</th><th>Town</th><th>Ward</th><th>Precinct</th><th>Name</th><th>Role</th><th>Gender</th>";
	echo "</tr>";

	while($row = mysqli_fetch_array($query_seats)) {
		echo "<tr>";
		echo "<td>".$row["county"]."</td>";
		echo "<td>".$row["town"]."</td>";
		echo "<td>".$row["ward"]."</td>";
		echo "<td>".$row["precinct"]."</td>";
		echo "<td>".$row["member_name"]."</td>";
		echo "<td>".$row["member_role"]."</td>";
		echo "<td>".$row["gender"]."</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>
<br>
<br>
<br>
</body>
</html>