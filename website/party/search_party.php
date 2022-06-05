<html>
<head>
<title>BlueCompass.org</title>
<script src="sorttable.js"></script>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}	
</style>
<!-- <meta http-equiv="refresh" content="0; url=data?ld=21" /> -->
</head>

<body>
<a href="index.html">Home</a>
<a href="party.html">Party</a>
<BR>
<H2><center>Your Party Information</center></H2>

<a href="index.html">Party Home</a>&nbsp;
<a href="party_survey.html">Party Survey</a>&nbsp;
<a href="party.php">Party Committees</a>&nbsp;
<a href="party_vacancies.php">Party Vacancies</a>&nbsp;
<a href="index.html">How it works</a>&nbsp;
<a href="index.html">How to run</a>
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">Committee Member Role</a>
<BR>
<?php
include("../db-a.php");

$dob = $_GET["dob"];
$dob2 = substr($dob, 5, 2);
$dob2 = $dob2."/";
$dob2 = $dob2.substr($dob, 8, 2);
$dob2 = $dob2."/";
$dob2 = $dob2.substr($dob, 0, 4);

//echo $dob2;

$sql = "select * from voters_list_from_hist_view a
left join dem_committee_members b
on a.ssn = b.muni_id and a.ward = b.ward and a.precinct = b.precinct
left join dem_committee c
on c.muni_id = ssn
where a.party_code = 'DEM' and 
first_name='".$_GET["firstname"]."' and last_name='".$_GET["lastname"]."'";
if($dob2 != "0001-01-01") {
	"and date_of_birth='".$dob2."'";
}

//echo "$sql";
echo "<br>";

$query = mysqli_query($conn, $sql);
// redirect to result page or initial page



$sql2 = "select a.county, chair_name, committee_email, website, bylaws, address 
from voters_list_from_hist_view a
left join dem_committee b
on b.county = a.county
where a.party_code = 'DEM' and 
first_name='".$_GET["firstname"]."' and last_name='".$_GET["lastname"]."' 
and date_of_birth='".$dob2."' and muni=''";

//echo "$sql2";

$query2 = mysqli_query($conn, $sql2);
$row2 = $row=mysqli_fetch_assoc($query2);

$i=0;
while($row = mysqli_fetch_array($query)) {
	// assuming one person only, the multiple records should be for committee members
	//print_r($row);
	if($i == 0) {
		//print_r($row);
		echo "<b>Name:</b> ";
		echo $row["first_name"];
		echo " ";
		echo $row["middle_name"];
		echo " ";
		echo $row["last_name"];
		echo "<br>";
		echo "<b>County:</b> ".$row[13]; // why the hell "county" doesn't work!?
		echo "<br>";
		echo "<b>Town:</b> ".$row["town"];
		echo "<br>";
		echo "<b>Zip Code:</b> ".$row["zip_5"];
		echo "<br>";
		if($row["ward"] != "00") {
			echo "<b>Ward:</b> ".$row["ward"];
			echo "<br>";
		}
		echo "<b>Precinct:</b> ".$row["precinct"];
		echo "<br>";
		
		echo "<br>";
		$ld = $row["ld"];
		if($ld == "21" or $ld=="23") {
			$link = true;
			$ld2 = $ld;
		}
		if(strlen($ld) == 1) {
			$ld = "NJ LD0".$ld;
		} else {
			$ld = "NJ LD".$ld;
		}
		echo "<b>Legislative District:</b> ";
		if(link) {
			echo "<a href='../leg_dist/ld".$ld2.".php'>".$ld."</a>";
		} else {
			echo $ld;
		}
		echo "<br>";
		$cd = $row["cd"];
		if(strlen($cd) == 1) {
			$cd = "NJ0".$cd;
		} else {
			$cd = "NJ".$cd;
		}
		echo "<b>Congressional District:</b> ";
		if($cd == "NJ07") {
			echo "<a href='../nj7'>".$cd."</a>";
		} else {
			echo $cd;
		}
		echo "<br>";
	}
	if($i == 00 and !empty($row2)) {
		//print_r($row2);
		echo "<H3>".$row2["county"]." County Democratic Committee</H3>";
		echo "<b>Chairperson:</b> ".$row2["chair_name"];
		echo "<br>";
		echo "<b>Website:</b> <a target='new' href='".$row2["website"]."'>".$row2["website"]."</a>";
		echo "<br>";
		echo "<b>Email:</b> <a target='new' href=mailto:".$row2["committee_email"].">".$row2["committee_email"]."</a>";
		echo "<br>";
		echo "<b>Address:</b> ".$row2["address"];
		echo "<br>";
		echo "<b>Bylaws:</b> <a target='new' href='".$row2["bylaws"]."'>".$row2["bylaws"]."</a>";
	}
	if($i == 0) {
		echo "<H3>Democratic Party Municipal Committee</H3>";
		echo "<b>Committee Chairperson:</b> ".$row["chair_name"];
		echo "<br>";
		echo "<b>Committee Email:</b> <a href=mailto:".$row["committee_email"].">".$row["committee_email"]."</a>";
		echo "<br>";
		echo "<b>Committee Website:</b> <a target='new' href='".$row["website"]."'>".$row["website"]."</a>";
		echo "<br>";
		echo "<H3>Municipal Committee Members</H3>";
	}
	//echo $row["gender"];
	//echo " - ";
	echo $row["member_name"];
	echo "<br>";
	$i = 1;
}
//header("Location:party.php");
?>
<br>
<br>
</body>
</html>