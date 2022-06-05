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

<H2><center>Who is running in 2019</center></H2>

<?php
include("../db-a.php");

$app = "bluecompass: ";

$street_name = $_GET["street_name"];
$street_number = $_GET["street_number"];
$city = trim($_GET["town"]);
$county = $_GET["county"];

$is_address = !empty($street_name) and !empty($street_number);

// perhaps first verify if the search returns more than one address

$street_name = trim(str_ireplace("Avenue", "Ave", $street_name));
$street_name = trim(str_ireplace("Place", "Pl", $street_name));
$street_name = trim(str_ireplace("Road", "Rd", $street_name));
$street_name = trim(str_ireplace("Street", "St", $street_name));
$street_name = trim(str_ireplace("Terrace", "Ter", $street_name));

$sql1 = "select * ";
//if($is_address) {
//	$sql1 .= "distinct county, cd, ld, city, municipality, ward, precinct ";
//} else {
//	$sql1 .= "distinct county, cd, ld, city, municipality ";
//}
$sql1 .= "from alpha_voter_list_state where 1=1";
if(!empty($county)) {
	$sql1 .= " and county = '".$county."'";
}
if(!empty($city)) {
	// not good, too many hits $sql1 .= " and (city like '%".$city."%' or municipality like '%".$city."%')";
	$sql1 .= " and municipality like '%".$city."%'";
}
if(!empty($street_name)) {
	$sql1 .= " and street_name like '".$street_name."%'";
}
if(!empty($street_number)) {
	$sql1 .= " and street_number='".$street_number."'";
}
$sql1 .= " limit 1";

echo "$sql1";
echo "<br>";

$query1 = mysqli_query($conn, $sql1);
// if multiple addresses, should display a page with links to different addresses

$row1 = mysqli_fetch_array($query1);

if(empty($row1)) {
	$err_msg = "No address found for";
	if(!empty($street_name)) {
		$err_msg .= " ".$street_name;
	}
	if(!empty($street_number)) {
		$err_msg .= " ".$street_number;
	}
	if(!empty($city)) {
		$err_msg .= " ".$city;
	}
	if(!empty($county)) {
		$err_msg .= ", ".ucfirst(strtolower($county));
	}
	
	//header("Location: error.php?err_msg=$err_msg"); 
	//exit();
}

$ward = $row1["ward"];
$muni = $row1["municipality"];

$sql2 = "select * from candidates where (county='".$row1["county"]."' or ld='".$row1["ld"]."' or cd='".$row1["cd"]."'";
if($is_address) {
	$sql2 .= " or (muni='".$muni."' and (ward = '".$ward."' or ward=''))"; 
} else {
	 $sql2 .= " or muni='".$muni."'";
}
$sql2 .= ") ";
$sql2 .= "and election_type='".$_GET["election_type"]."'";
$sql2 .= " order by ifnull(sort_by, 100), office, ward, precinct, party, name";

echo "$sql2";
echo "<br>";

syslog(LOG_INFO, $app.$sql1);
syslog(LOG_INFO, $app.$sql2);

echo "<hr>";

if($is_address) {
	echo "<b>Address:</b> ".$row1["street_number"]." ".$row1["street_name"];
	echo "<br>";
}
//echo "<b>Town:</b> ".$row1["city"].", ".$row1["zip"];
//echo "<br>";
echo "<b>Municipality:</b> ".$row1["municipality"];
if($is_address) {
	echo ", ".$row1["zip"];
}
echo "<br>";
echo "<b>County:</b> ".$row1["county"];
echo "<br>";
echo "<b>Congressional District:</b> ".$row1["cd"];
echo "<br>";
echo "<b>Legislative District:</b> ".$row1["ld"];
echo "<br>";
if($is_address) {
	echo "<b>Ward:</b> ".$row1["ward"];
	echo "<br>";
	echo "<b>Precinct:</b> ".$row1["precinct"];
	echo "<br>";
}
echo"<br>";

		
$query2 = mysqli_query($conn, $sql2);

$num_rows = mysqli_num_rows($query2);

echo "<b>".$num_rows." candidates</b>";
echo "<table border='1'>";
echo "<tr>";
echo "<th style='width:160px'>Office</th>";
echo "<th style='width:160px'>District</th>";
echo "<th style='width:160px'>Name</th>";
echo "<th style='width:60px'>Party</th>";
echo "<th style='width:200px'>Address</th>";
echo "<th style='width:160px'>Email</th>";
//echo "<th>Slogan</th>";
echo "<th style='width:160px'>Website</th>";
echo "</tr>";

while($row2 = mysqli_fetch_array($query2)) {	
	echo "<tr>";
	echo "<td>".$row2["office"]."</td>";
	
	echo "<td>";
	if(!empty($row2["ld"])) {
		echo "Leg Dist ".$row2["ld"];
	} else 	if(!empty($row2["cd"])) {
		echo "Cong Dist ".$row2["cd"];
	} else if(!empty($row2["ward"])) {
		echo "Ward ".$row2["ward"];
		if(!empty($row2["precinct"])) {
			echo " Dist ".$row2["precinct"];
		}	
	} else if(!empty($row2["precinct"])) {
		echo "Dist ".$row2["precinct"];
	}	
	echo "</td>";
	
	echo "<td>".$row2["name"];
	if($row2["incumbent"] == "Y") {
		echo " *";
	}
	echo "</td>";
	echo "<td>".$row2["party"]."</td>";
	echo "<td>";
	if(!empty($row2["address"])) {
		echo $row2["address"].", ".$row2["town"].", ".$row2["state"].", ".$row2["zip"];
	}
	echo "</td>";
	$email = $row2["email"];
	if(!empty($email)) {
		echo "<td><a href='mailto:".$email."'>$email</a></td>";
	} else {
		echo "<td></td>";
	}
	//echo "<td>".$row2["slogan"]."</td>";
	$website = $row2["website"];
	if(!empty($website)) {
		echo "<td><a href='".$website."'>$website</a></td>";
	} else {
		echo "<td></td>";
	}
	echo "</tr>";
}
echo "</table>";

?>
<i>
Addresses are extracted from the voter list, addresses of not registered voters are not in the database.
<br>
<!--
"Slogan" indicates the Primary election endorsement.
<br>
-->
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<br>
<br>
</body>
</html>