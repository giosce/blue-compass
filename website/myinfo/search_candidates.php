<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>
<script src="sorttable.js"></script>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
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
</style>
<!-- <meta http-equiv="refresh" content="0; url=data?ld=21" /> -->
</head>

<body>
<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../njlocal.html" class="aa">2019 Other NJ Local Elections</a></td>
<td><a href="all_candidates.php" class="aa">2019 NJ Candidates</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">Party Connection</a></td>
<td><a href="" class="aa">Forum</a></td>
<td><a href="" class="aa">Blog</a></td>
</tr>
</table>

<H2><center>Who is running</center></H2>

<?php
include("../db-a.php");


// perhaps first do this query to verify if the search returns more than one address

"select * from addresses a
where street_num='54' and street_name like 'evergreen%'
-- street_num='28' and street_name like 'Glenside av%'
and (city='Summit' or municipality='Summit')";


$sql = "select distinct b.county, a.street_num, a.street_name, a.zip_5,
a.ward, a.precinct, b.ssn, a.city, a.municipality, b.cd, b.ld,
c.office, c.name, c.address, c.email, c.party, c.slogan, c.website, c.incumbent 
from addresses a
join municipal_list b on b.muni_name_from_hist=municipality
join candidates_view c on 
c.county_geo = b.county
AND (c.county = b.county
OR (muni_name_from_hist = c.muni and c.ward = '' and c.precinct = '') 
OR (muni_name_from_hist = c.muni and c.ward = a.ward and c.precinct = '') 
OR (muni_name_from_hist = c.muni and c.ward = a.ward and c.precinct = a.precinct)
OR b.ld = c.ld or b.cd = c.cd) 
where c.election_year='".$_GET["election_year"]."' 
and c.election_type='".$_GET["election_type"]."' 
and street_num='".$_GET["street_number"]."' 
and street_name like '".$_GET["street_name"]."%'
and city like '".$_GET["town"]."%'
order by sort_by, office, party, slogan, name";

//echo "$sql";
//echo "<br>";

$query = mysqli_query($conn, $sql);
// redirect to result page or initial page

$i = 0;
while($row = mysqli_fetch_array($query)) {
	//print_r($row);
	if($i == 0) {
		echo "<b>Address:</b> ".$row["street_num"]." ".$row["street_name"];
		echo "<br>";
		echo "<b>Town:</b> ".$row["city"].", ".$row["zip_5"];
		echo "<br>";
		echo "<b>Municipality:</b> ".$row["municipality"];
		echo "<br>";
		echo "<b>County:</b> ".$row["county"];
		echo "<br>";
		echo "<b>CD:</b> ".$row["cd"];
		echo "<br>";
		echo "<b>LD:</b> ".$row["ld"];
		echo "<br>";
		echo "<b>Ward:</b> ".$row["ward"];
		echo "<br>";
		echo "<b>Precinct:</b> ".$row["precinct"];
		echo "<br><br>";
		echo "<b>Candidates</b>";
		echo "<table border='1'>";
		echo "<tr>";
		echo "<th>Office</th>";
		echo "<th>Name</th>";
		echo "<th>Party</th>";
		echo "<th>Incumbent</th>";
		echo "<th>Address</th>";
		echo "<th>Email</th>";
		echo "<th>Slogan</th>";
		echo "<th>Website</th>";
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td>".$row["office"]."</td>";
	echo "<td>".$row["name"]."</td>";
	echo "<td>".$row["party"]."</td>";
	echo "<td>".$row["incumbent"]."</td>";
	echo "<td>".$row["address"]."</td>";
	$email = $row["email"];
	if(!empty($email)) {
		echo "<td><a href='mailto:".$email."'>$email</a></td>";
	} else {
		echo "<td></td>";
	}
	echo "<td>".$row["slogan"]."</td>";
	$website = $row["website"];
	if(!empty($email)) {
		echo "<td><a href='".$website."'>$website</a></td>";
	} else {
		echo "<td></td>";
	}
	echo "</tr>";
	$i++;
}
echo "</table>";
//header("Location:party.php");
?>
<i>
Addresses are extracted from the voter list, addresses of not registered voters are not in the database.
<br>
"Slogan" indicates the Primary election endorsement.
<br>
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<br>
<br>
</body>
</html>