<?php

include("../db-a.php");

$muni = $_GET["muni"];
$st_name = $_GET["street_name"];
$st_num = $_GET["street_number"];

$sql = "select county, sum(count_seats) as seats, sum(count_no_petition) as vacancies
from committee_vacancies
group by county
order by county";

$sql2 = "select * from committee_vacancies
where count_no_petition > 1
order by county, muni";

$query = mysqli_query($conn, $sql);

$query2 = mysqli_query($conn, $sql2);

//echo "<br>".$sql."<br>";

if(!empty($muni)) {
	$sql3 = "select * from committee_candidates where (name='No Petition Filed' or name = '') 
	and muni = '".$muni."'
	order by ward, precinct, gender";
	//echo "<br>".$sql3."<br>";
	$query3 = mysqli_query($conn, $sql3);
}

if(!empty($st_name)) {
	$sql4 = "select * from addresses where street_name like '".$st_name."%'
	and street_num='".$st_num."' and city='".$muni."'";
	// add county
	//echo "<br>".$sql4."<br>";
	$query4 = mysqli_query($conn, $sql4);
}

?>


<html>
<head>
<title>BlueCompass.org - Party Vacancies</title>
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

ul.tree li {
    list-style-type: none;
    position: relative;
}

ul.tree li ul {
    display: none;
}

ul.tree li.open > ul {
    display: block;
}

ul.tree li a {
    color: black;
    text-decoration: none;
}

ul.tree li a:before {
    height: 1em;
    padding:0 .1em;
    font-size: .8em;
    display: block;
    position: absolute;
    left: -1.3em;
    top: .2em;
}

ul.tree li > a:not(:last-child):before {
    content: '+';
}

ul.tree li.open > a:not(:last-child):before {
    content: '-';
}

tr {
	border-bottom: 1pt solid black;
}

</style>
</head>

<body>
<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../njlocal.html" class="aa">2019 Other NJ Local Elections</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">Party Connection</a></td>
<td><a href="" class="aa">Forum</a></td>
<td><a href="" class="aa">Blog</a></td>
</tr>
</table>

<H2><center>Democratic Party Committee available seats</center></H2>

<a href="index.html">Party Home</a>&nbsp;
<a href="party_survey.html">Party Survey</a>&nbsp;
<a href="party.php">Party Committees</a>&nbsp;
<a href="party_vacancies.php">Party Vacancies</a>&nbsp;
<a href="">How it works</a>&nbsp;
<a href="">How to run</a>
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">Committee Member Role</a>
<br>
<br>
Below are the Democratic Party Committee seats for which no petitions to run have been filed. We encourage to fill in as many committee seats as possible and for seats that are still open on June 4th (Primary Election Day), people can win these seats by simple write-in their name (better if a few friends do the same).
<br>
<i>We strongly advised that you contact your municipal committee chair if you'd like to run because we can't guarantee the accurancy of this information (either because the information wasn't provided correctly by the county or party or because things are changing quickly up to the election day).
</i>
<br>
<br>
If you are a registered voter, you can find out your town Ward and Precinct as well as your Democratic Party Municipal Committee information and the members representing your Precinct <a href="index.html"><b>here</b></a>.
<br>
If you know the address of a registered voter, you can find out all the candidates for that specific address (including Democratic Party Municipal Committee) <a href="../candidates/"><b>here</b></a>.
<br>
<br>
<table border="1">
<tr>
<th>County</th><th>Seats</th><th>Vacancies</th>
</tr>
<?php
$tot_seats=0;
$tot_vacancies=0;
while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
	echo "<td>".$row["county"]."</td>";
	echo "<td align='right'>".number_format($row["seats"], 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($row["vacancies"], 0, ".", ",")."</td>";
	echo "</tr>";
	$tot_seats += $row["seats"];
	$tot_vacancies += $row["vacancies"];
}
echo "<tr>";
echo "<td style='font-weight:bold;'>Total</td>";
echo "<td align='right' style='font-weight:bold;'>".number_format($tot_seats, 0, ".", ",")."</td>";
echo "<td align='right' style='font-weight:bold;'>".number_format($tot_vacancies, 0, ".", ",")."</td>";
echo "</tr>";
?>
</table>
<br>
<table>
<tr>
<td>
<table border="1">
<tr>
<th>County</th><th>Municipality</th><th>Seats</th><th>Vacancies</th>
</tr>
<?php
while($row2 = mysqli_fetch_array($query2)) {
	echo "<tr>";
	echo "<td>".$row2["county"]."</td>";
	echo "<td><a href='party_vacancies.php?muni=".$row2["muni"]."'>".$row2["muni"]."</a></td>";
	echo "<td align='right'>".number_format($row2["count_seats"], 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($row2["count_no_petition"], 0, ".", ",")."</td>";
	echo "</tr>";
}
echo "</table>";
echo "</td>";
if(!empty($muni)) {
	echo "<td width='50'></td>";
	echo "<td valign='top'>";
	echo "<i>Note: Not all county has provided all the race details.";
	echo "<br>If precinct is not provided the seat should be open across the municipality.";
	echo "</i>";
	echo "<table border='1'>";
	echo "<caption><b>".$muni." Vacancies</b></caption>";
	echo "<tr>";
	echo "<th>ward</th><th>precinct</th><th>gender</th><th>term</th>";
	echo "</tr>";
	while($row3 = mysqli_fetch_array($query3)) {
		//print_r($row3);
		echo "<tr>";
		echo "<td>".$row3["ward"]."</td>";
		echo "<td>".$row3["precinct"]."</td>";
		echo "<td>".$row3["gender"]."</td>";
		echo "<td>".$row3["term"]."</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</td>";
	echo "<td width='50'></td>";
	echo "<td valign='top'>";
	echo "<table>";
	echo "<caption><b>Find my ward & precinct</b></caption>";
	echo "<form action='party_vacancies.php'>";
	echo "<tr>";
	echo "<td>Street Number:</td>";
	echo "<td><input type='text' name='street_number'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Street name:</td>";
	echo "<td><input type='text' name='street_name'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Municipality:</td>";
	echo "<td><input type='text' name='muni' value='".$muni."'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2' align='center'><input type='submit' value='Submit'></td>";
	echo "</tr>";
	echo "<input type='hidden' name='election_year' value='2019'>";
	echo "<input type='hidden' name='election_type' value='PRI'>";
	echo "</form>";
	$row4 = mysqli_fetch_array($query4);
	//print_r($row4);
	if(!empty($row4)) {
		if(!empty($row4["ward"])) {
			echo "<tr>";
			echo "<td>Ward:</td>";
			echo "<td>".$row4["ward"]."</td>";
			echo "</tr>";
		}
		if(!empty($row4["precinct"])) {
			echo "<tr>";
			echo "<td>Precinct:</td>";
			echo "<td>".$row4["precinct"]."</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
}
?>
</tr>
</table>
<br>
<br>
</body>
</html>