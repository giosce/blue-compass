<?php

include("../db-a.php");

$county = $_GET["county"];
$town = $_GET["town"];
$status = $_GET["status"];
$election_year = $_GET["election_year"];

$sql_committees = "select *, a.county as county from dem_committee a left join dem_committee_seats_by_status b
on b.county = a.county where a.muni=''";
if(!empty($election_year)) {
	$sql_committees .= " and next_election='".$election_year."'";
}
$sql_committees .= " order by a.county";

//print $sql_committees;

$query_committees = mysqli_query($conn, $sql_committees);

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
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">NJ Democratic Party</a></td>
</tr>
</table>

<H2><center>Democratic Party Committees</center></H2>

<a href="index.html">Party Home</a>&nbsp;
<a href="party_survey.html">Party Survey</a>&nbsp;
<a href="committees.php">Party Committees</a>&nbsp;
<a href="comm_seats.php">Committees Seats</a>&nbsp;
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">Committee Member Role</a>
<br>
<br>
The <a href="https://nj.gov/state/dos-statutes-elections-19-01-09.shtml"><b>NJ Statute Title 19</b></a> states all the regulation pertaining to the political parties structure and processes in NJ.
<br>
<br>
The Party Committees play an important role on organizing political activities from local to national level.
<br>
The committees play also an important role with regard of how much the party is open, transparent and fair to everyone.
<br>
We believe that all this information should be public and publicized to encourage a larger number of residents to interact with 
the party in a mutually beneficial relationship.
<br>
<br>
The State Committee (<a href="https://www.njdems.org"><b>NJDSC</b></a>) is the top level NJ Committee, 
<a href="https://www.njdems.org/new-jersey-state-committee-members/"><b>its 113 members</b></a> 
are elected by voters in the same ballot of the Governor election every 4 years. 
The number of these members is proportional to the population of each county.
Here you can see the State Committee 
<a href="http://d3n8a8pro7vhmx.cloudfront.net/themes/52408ad68d57d97596000002/attachments/original/1380300629/BYLawNJDSC2013.pdf?1380300629">
<b>ByLaws</b></a>.
<br>
<br>
The County Committees (made up of the members of the county Municipal Committees) decide (in a more or less democratic way, 
depending on the bylaws and other rules) which candidates to support. 
In NJ this is a deal-breaker business because most NJ counties use the “party line” for the primary elections which gives pretty 
much certainty who will win the primary.
<br>
While a county committee doesn’t have a large influence on a Presidential primary campaign, it has a huge impact on Congress and all down ballot races.
<br>
<br>
So, it is very important that residents know their committee (from a light <a href="party_survey.html"><b>survey</b></a>, 50% of engaged citizens 
don’t know much about the committee), only participating on committee discussion registered voters can have more 
influence on which candidate to support as well as on openness and fairness.
<br>
<br>
On this page you can find the list of Democratic party county committees such as the term length, the last and next election, 
the number of seats, the chair, contact information and bylaws.
<br>
<br>
Clicking on the number of seats will display the list of committee members by their municipality, ward and precinct.
<br>
<br>

<form>

<a name="table"></a>
<table border="0">
<tr>
<th>Next Election</th>
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
	if($election_year == "2022") {
		echo "<option selected value='2022'>2022</option>";
	} else {
		echo "<option value='2022'>2022</option>";
	}
	if($election_year == "2023") {
		echo "<option selected value='2023'>2023</option>";
	} else {
		echo "<option value='2023'>2023</option>";
	}
?>
</select>
</td>
</tr>
</table>

</form>


<table border="1" class="blueTable">
<thead>
<tr>
<th>County</th><th>Last Election</th><th>Term</th><th>Next Election</th><th>Seats</th><th>Vacant</th><th>Enforce Gender</th>
<th>Chair</th><th>Committee Email</th><th>Phone</th><th>Website</th><th>By Laws</th><th>Address</th>
</tr>
</thead>
<?php
while($committee = mysqli_fetch_array($query_committees)) {
	//print_r($committee);
	echo "<tr>";
	$cnty = $committee["county"];
	$notes = $committee["notes"];
	$seats = $committee["seats"];
	$vacant = $committee["vacant"];
	if($cnty == "Hudson" and !empty($notes)) {
		echo "<td title='".$notes."'>";
	} else {
		echo "<td>";
	}
	//echo "<a href='committees.php?county=".$cnty."#table'>".$cnty."</a>";
	echo $cnty;
	if($cnty == "Hudson") {
		echo "<span style='color: red;'> *</span>";
	}
	echo "</td>";
	echo "<td>".$committee["last_election"]."</td>";
	echo "<td>".$committee["term_years"]."</td>";
	echo "<td>".$committee["next_election"]."</td>";
	if($seats > 0) {
		echo "<td><a href='committees.php?county=".$cnty."#table'>".$committee["seats"]."</a></td>";
		echo "<td><a href='committees.php?county=".$cnty."&status=Vacant#table'>".$vacant."</a></td>";
	} else {
		echo "<td>".$seats."</td>";
		echo "<td>".$vacant."</td>";
	}		
	echo "<td>".$committee["gender_enforced"]."</td>";
	echo "<td>".$committee["chair_name"]."</td>"; 
	//echo "<td><a href='mailto: ".$committee["chair_email"]."'>".$committee["chair_email"]."</a></td>"; 
	echo "<td><a href='mailto: ".$committee["committee_email"]."'>".$committee["committee_email"]."</a></td>"; 
	echo "<td style='white-space: nowrap;'>".$committee["committee_phone"]."</td>";
	echo "<td><a href='".$committee["website"]."'>".$committee["website"]."</a></td>"; 
	if(!empty($committee["bylaws"])) {
		echo "<td><a href='mailto: ".$committee["bylaws"]."'>ByLaws</a></td>"; 
	} else {
		echo "<td></td>";
	}
	echo "<td>".$committee["address"]."</td>";
	echo "</tr>";
}
?>
</table>
<br>
* Hudson County municipal committees hold elections in different years. Hover on the county name to see which municipalities vote in each year.
<br>
<br>
</body>
</html>