<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>
<script src="../sorttable.js"></script>
<script src="../jquery-3.3.1.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>

<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
<link rel="stylesheet" type="text/css" href="../bluecompass.css" />

<style type="text/css">	

.tab-button {
  cursor: pointer;
  width: 199px;
  display: inline-block;
  background-color: #5E6B7F;
  color: white;
  text-align: center;
  transition: .25s ease;
  border: none;
  padding: 10px;
  border-radius: 12px 12px 0 0;
}


div:focus button, button:focus {
  background-color: #8DA1BF;
  outline: none;
}

table.padded_table td {
	padding-left: 10px;
}

table.padded_table tr:hover {
	background-color:#ccffff;
	cursor: default;
}

</style>

</head>

<body>

<?php
include("../db.php");

include("../menu.html");


$properties = parse_ini_file("../properties-a.ini");

$host = $properties["host"];
$username = $properties["username"];
$password = $properties["password"];
$db_name = $properties["db_name"];

$conn2 = mysqli_connect($host, $username, $password, $db_name);
if (!$conn2) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

$year = "2021";

echo "<H2><center>Who is running in ".$year."</center></H2>";

$app = "bluecompass: ";

$street_name = $_GET["street_name"];
$street_number = $_GET["street_number"];
$city = trim($_GET["town"]);
$county = $_GET["county"];
$el_type = $_GET["election_type"];
$party = $_GET["party"];
$office = $_GET["office"];
$district = $_GET["district"];
$debug = $_GET["gio"];
$muni = $_GET["muni"];
$incumbent = $_GET["incumbent"];
$el_year = $_GET["election_year"];
$slogan = $_GET["slogan"];
$endorsement = $_GET["endorsement"];
$query_for = $_GET["query_for"];

if(empty($el_year)) {
	$el_year=$year;
}

if(empty($el_type)) {
	$el_type="PRI";
}

$is_address = !empty($street_name) and !empty($street_number);

// perhaps first verify if the search returns more than one address

$street_name = trim(str_ireplace("Avenue", "Ave", $street_name));
$street_name = trim(str_ireplace("Place", "Pl", $street_name));
$street_name = trim(str_ireplace("Road", "Rd", $street_name));
$street_name = trim(str_ireplace("Street", "St", $street_name));
$street_name = trim(str_ireplace("Terrace", "Ter", $street_name));

if($is_address) {
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

	$query1 = mysqli_query($conn2, $sql1);
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

	$sql2 = "select * from candidates_new where (county='".$row1["county"]."' or ld='LD".$row1["ld"]."' or cd='CD".$row1["cd"]."'";
	if($is_address) {
		$sql2 .= " or (muni='".$muni."' and (ward = '".$ward."' or ward=''))"; 
	} else {
		 $sql2 .= " or muni='".$muni."'";
	}
	$sql2 .= ") ";
} else {   // not by address
	$sql2 = "select * from candidates_new where election_year='".$year."'";
	if(!empty($el_type)) { 
		$sql2 .= " and election_type='".$el_type."'";
	}
	if(!empty($party)) { 
		$sql2 .= " and party='".$party."'";
	}
	if(!empty($county)) { 
		$sql2 .= " and county_geo like '%".$county."%'";
	}
	if(!empty($office)) { 
		$sql2 .= " and office='".$office."'";
	}
	if(!empty($district)) {
		if(strpos($district, "CD") === false) {
			$ld = substr($district, 2);
			$sql2 .= " and ld='LD".$ld."'";
		} else {
			$cd = substr($district, 2);
			$sql2 .= " and cd='".$cd."'";
		}			
	}
	if(!empty($muni)) { 
		$sql2 .= " and (ifnull(muni,'')='' or muni='".$muni."')";
	} else {
		$sql2 .= " and ifnull(muni,'')=''";
	}
	if(!empty($incumbent)) {
		if($incumbent == "N") {
			$sql2 .= " and (incumbent='N' or ifnull(incumbent,'') = '')";
		} else {
			$sql2 .= " and incumbent='".$incumbent."'";
		}
	}
	if(!empty($endorsement)) {
		$sql2 .= " and endorsements like '%".$endorsement."%'";
	}
	if(!empty($slogan)) {
		$sql2 .= " and slogan='".$slogan."'";
	}
	if($query_for == "county") {
		$sql2 .= " and county is not null";
	} else {
		$sql2 .= " and county is null";		
	}
}

$sql2 .= " order by ld, ifnull(sort_by, 100), county, office, cd, ward, precinct, party, name";

syslog(LOG_INFO, $app.$sql1);
syslog(LOG_INFO, $app.$sql2);

echo "Here you can see the 2021 NJ Primary Election candidates.";
echo "<br>";
echo "<br>";
echo "Several Party County Committees have postponed their elections.";
echo "<br>";
echo "<br>";
echo "If you are not sure of your district you can find your electoral information (Representatives, Candidates) "; 
echo "<a href='http://bluecompass.org/myinfo/'>here</a>.";
echo "<hr>";

if($is_address) {
	echo "<b>Address:</b> ".$row1["street_number"]." ".$row1["street_name"];
	echo "<br>";
}

//echo "<b>Town:</b> ".$row1["city"].", ".$row1["zip"];
//echo "<br>";
/*
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
*/
		
$query2 = mysqli_query($conn, $sql2);

$num_rows = mysqli_num_rows($query2);


$sql_counties = "select distinct county
from candidates where election_year='2021'and election_type='PRI' and county is not null
order by 1";

$sql_cds = "select distinct concat('CD',cd)
from candidates where election_year='2021'and election_type='PRI' and cd is not null
order by 1";

$sql_lds = "select distinct ld
from candidates_new where election_year='2021'and election_type='PRI' and ld is not null
order by 1";

$sql_offices_state = "select distinct office 
from candidates_new where election_year='2021'and election_type='PRI' and office in ('Assembly', 'Senate', 'Governor')
order by sort_by";
//case when office like 'Freeholder %' then 'Freeholder' else office end

$sql_slogan_state = "select distinct slogan
from candidates_new where election_year='2021'and election_type='PRI' and (ld is not null or office='Governor')
order by 1";

$sql_endorsement_state = "select distinct endorsements
from candidates_new where election_year='2021'and election_type='PRI' and (ld is not null  or office='Governor')
order by 1";

$sql_offices_counties = "select distinct office 
from candidates_new where election_year='2021'and election_type='PRI' and county is not null 
order by sort_by";
//case when office like 'Freeholder %' then 'Freeholder' else office end

$sql_slogan_counties = "select distinct slogan
from candidates_new where election_year='2021'and election_type='PRI' and county is not null
order by 1";

$sql_endorsement_counties = "select distinct endorsements
from candidates_new where election_year='2021'and election_type='PRI' and county is not null
order by 1";

if($debug == "y") {
	echo "$sql2";
	echo "<br>";
}

$query_counties = mysqli_query($conn, $sql_counties);
//$query_cds = mysqli_query($conn, $sql_cds);
$query_lds =mysqli_query($conn, $sql_lds);
$query_offices_state = mysqli_query($conn, $sql_offices_state);
$query_slogan_state = mysqli_query($conn, $sql_slogan_state);
$query_endorsement_state = mysqli_query($conn, $sql_endorsement_state);

$query_offices_counties = mysqli_query($conn, $sql_offices_counties);
$query_slogan_counties = mysqli_query($conn, $sql_slogan_counties);
$query_endorsement_counties = mysqli_query($conn, $sql_endorsement_counties);


//$districts = array();
//while($d = mysqli_fetch_array($query_cds)) {
//	$districts[] = $d[0];
//}
//while($d = mysqli_fetch_array($query_lds)) {
	//$districts[] = $d[0];
//}
//print_r($districts);

//if(!empty($county)) {
//	$sql_munis = "select distinct muni from candidate_view2 where election_year='".$year."' 
//	and county='".$county."' and muni<>'' order by 1";
//}
//if(!empty($sql_munis)) {
//	$query_munis = mysqli_query($conn, $sql_munis);
	//while($m = mysqli_fetch_array($query_munis)) {
	//	echo $m[0].",";
	//}	
//}
?>

<div class="w3-bar w3-black">
<?php
	//if($query_for == "state") {
		echo "<button class='tab-button' onclick=\"openTab('state')\" id='state-tab' style='background-color:#8DA1BF;'>State</button>";
		echo "<button class='tab-button' onclick=\"openTab('county')\" id='county-tab'>County</button>";
	//} else {
		//echo "<button class='tab-button' onclick='openTab(\'state\')' id='state-tab'>State</button>";
		//echo "<button class='tab-button' onclick='openTab(\'county\')' id='county-tab' style='background-color:#8DA1BF;'>County</button>";
	//}
?>
</div>

<?php
	if(empty($query_for) or $query_for == "state") {
		echo "<div id='state' class='tab2'>";
	} else {
		echo "<div id='state' class='tab2' style='display:none;'>";
	}
?>
<form action="candidates.php" method="GET">
<input type="hidden" name="query_for" value="state">
<table horizontal-pad="10">
<tr>
<td>District</td>
<td>
	<select name="district" onchange="changeDistrict(this.form);">
	<?php
	if(empty($district)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($d = mysqli_fetch_row($query_lds)) {
		if($district==$d[0]) {
			echo "<option selected value'".$d[0]."'>".$d[0]."</option>";
		} else {
			echo "<option value'".$d[0]."'>".$d[0]."</option>";
		}
	}
	?>	
	</select>
</td>
<td>&nbsp;</td>
<td>Office</td>
<td>
	<select name="office" onchange="this.form.submit();">
	<?php
	if(empty($office)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($o = mysqli_fetch_row($query_offices_state)) {
		if($office==$o[0]) {
			echo "<option selected value'".$o[0]."'>".$o[0]."</option>";
		} else {
			echo "<option value'".$o[0]."'>".$o[0]."</option>";
		}
	}
	?>	
	
	</select>
</td>
<td>&nbsp;</td>
<td>Incumbent</td>
<td>
	<select name="incumbent" onchange="this.form.submit();">
	<?php
		if(empty($incumbent)) {
			echo "<option selected value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($incumbent == "Y") {
			echo "<option selected value='Y'>Yes</option>";
		} else {
			echo "<option value='Y'>Yes</option>";
		}
		if($incumbent == "N") {
			echo "<option selected value='N'>No</option>";
		} else {
			echo "<option value='N'>No</option>";
		}
	?>
	</select>
</td>
<td>&nbsp;</td>
<td>Party</td>
<td>
	<select name="party" onchange="this.form.submit();">
	<?php
		if(empty($party)) {
			echo "<option selected value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($party=="Dem") {
			echo "<option selected value='Dem'>Dem</option>";
		} else {	
			echo "<option value='Dem'>Dem</option>";
		}
		if($party=="Rep") {
			echo "<option selected value='Rep'>Rep</option>";
		} else {
			echo "<option value='Rep'>Rep</option>";
		}
	?>
	</select>
</td>
<td>&nbsp;</td>
<td>Slogan</td>
<td>
	<select name="slogan" onchange="this.form.submit();">
	<?php
	while($s = mysqli_fetch_row($query_slogan_state)) {
		if($slogan==$s[0]) {
			echo "<option selected value'".$s[0]."'>".$s[0]."</option>";
		} else {
			echo "<option value'".$s[0]."'>".$s[0]."</option>";
		}
	}
	?>	
	
	</select>
</td>
<td>&nbsp;</td>
<td>Endorsement</td>
<td>
	<select name="endorsement" onchange="this.form.submit();">
	<?php
	$eee = [];
	while($o = mysqli_fetch_row($query_endorsement_state)) {
		// split if there are multiple endorsements separated by commas
		$ee = split(",", $o[0]);
		foreach($ee as $e) {
			if(in_array($e, $eee)) {
				continue;
			}
			array_push($eee, $e);
			print_r($eee);
			if($endorsement==$e) {
				echo "<option selected value'".$e."'>".$e."</option>";
			} else {
				echo "<option value'".$e."'>".$e."</option>";
			}
		}
	}
	?>	
	
	</select>
</td>

</tr>
</table>
</form>
<?php
echo "<div style='float:left;'><b>".$num_rows." candidates</b></div>";
//echo "<div style='float:right; padding-bottom:4px; padding-right:100px;'><input id='btnHide' type='button' value='Show More Columns'/></div>";
echo "<br>";
//echo "</form>";

echo "<table border='1' class='tableFixHead' id='candidate_table'>";
echo "<thead>";
echo "<tr>";
echo "<th style='width:100px'>District</th>";
echo "<th style='width:100px'>Office</th>";
echo "<th style='width:100px'>Name</th>";
echo "<th style='width:60px'>Party</th>";
echo "<th style='width:300px'>Slogan</th>";
echo "<th style='width:100px;'>Endorsements</th>";
echo "<th style='width:60px;'>Email</th>";
echo "<th style='width:60px;'>Website</th>";
echo "<th style='width:60px;'>Socials</th>";
echo "<th style='width:300px;'>Address</th>";
//echo "<th style='width:160px; display:none;'>VoteSmart</th>";
//echo "<th style='width:160px; display:none;'>OpenSecret</th>";
//echo "<th style='width:160px; display:none;'>ProPublica</th>";
echo "</tr>";
echo "</thead>";

echo "<tbody"; // style='display: block; height: 400px;'>";
while($row2 = mysqli_fetch_array($query2)) {

	$place = "";
	if(!empty($row2["ld"])) {
		$place = $row2["ld"];
	} else 	if(!empty($row2["cd"])) {
		$place =  "CD".$row2["cd"];
	} else if(!empty($row2["ward"])) {
		$place =  "Ward ".$row2["ward"];
		if(!empty($row2["precinct"])) {
			$place =  " Dist ".$row2["precinct"];
		}	
	} else if(!empty($row2["precinct"])) {
		$place =  "Dist ".$row2["precinct"]." ".$row2["muni"];		
	} else if(!empty($row2["county"])) {
		$place =  $row2["county"]." County";
	}
	
	echo "<tr onclick='highlightRow(this)' title='".$row2["name"]." - ".$row2["office"]." - ".$place."'>";
	
	echo "<td>";
	echo $place;
	echo "</td>";
	
	echo "<td>".$row2["office"]."</td>";
	
	echo "<td>".$row2["name"];
	if($row2["incumbent"] == "Y") {
		echo " *";
	}
	echo "</td>";
	echo "<td>".$row2["party"]."</td>";
	echo "<td>".$row2["slogan"]."</td>";	
	echo "<td>".$row2["endorsements"]."</td>";
	
	//echo "</td>";
	$email = $row2["email"];
	if(!empty($email)) {
		echo "<td><a href='mailto:".$email."'><img src='../img/email-logo.png' width=20' height='20' style='vertical-align: middle;'></a></td>";
	} else {
		echo "<td></td>";
	}
	$website = $row2["website"];
	if(!empty($website)) {
		echo "<td><a target='new' href='".$website."'><img src='../img/www-logo.jpeg' width=20' height='20' style='vertical-align: middle;'></a></td>";
	} else {
		echo "<td></td>";
	}
	$twitter = $row2["twitter"];
	$fb = $row2["facebook"];
	if(empty($twitter) and empty($fb)) {
		echo "<td></td>";
	} else {
		echo "<td valign='top'>";
		if(!empty($fb)) {
			echo "<a target='new' href='".$fb."'><img alt='FB' src='../img/facebook-logo.png' width=20' height='20' style='vertical-align: middle;'></a>";
		}
		if(!empty($twitter)) {
			echo "<a target='new' href='".$twitter."'><img alt='FB' src='../img/twitter-logo.png' width=40' height='40' style='vertical-align: middle;'></a>";
		}
		echo "</td>";
	}
	//echo "<td>"; //style='display:none;'>";
	//echo "<div style='max-height:30px;'>";
	
	echo "<td>";
	if(!empty($row2["address"])) {
		//if($row2["office"] != "County Committee") {
			echo $row2["address"].", ".$row2["town"].", ".$row2["state"].", ".$row2["zip"];
		//} else {
			//echo $row2["address"];
		//}
	}
	echo "</td>";
	//echo "</div>";
	$votesmart = $row2["votesmart"];
	if(!empty($votesmart)) {
		echo "<td style='display:none;'><a href='".$votesmart."'>votesmart</a></td>";
	} else {
		echo "<td style='display:none;'></td>";
	}
	$opensecret = $row2["opensecret"];
	if(!empty($opensecret)) {
		echo "<td style='display:none;'><a href='".$opensecret."'>opensecret</a></td>";
	} else {
		echo "<td style='display:none;'></td>";
	}
	$propublica = $row2["propublica"];
	if(!empty($propublica)) {
		echo "<td style='display:none;'><a href='".$propublica."'>propublica</a></td>";
	} else {
		echo "<td style='display:none;'></td>";
	}
	echo "</tr>";
}
echo "</tbody>";
echo "</table>";

?>

</div>

<?php
	if($query_for == "county") {
		echo "<div id='county' class='tab2'>";
	} else {
		echo "<div id='county' class='tab2' style='display:none;'>";
	}
?>
	
<form action="candidates.php" method="GET">
<input type="hidden" name="query_for" value="county">
<table horizontal-pad="10">
<tr>
<?php
echo "<td>County</td>";
echo "<td>";
	echo "<select name='county' onchange='changeCounty(this.form);'>";
	
	if(empty($county)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($c = mysqli_fetch_row($query_counties)) {
		if($county==$c[0]) {
			echo "<option selected value'".$c[0]."'>".$c[0]."</option>";
		} else {
			echo "<option value'".$c[0]."'>".$c[0]."</option>";
		}
	}
	echo "</select>";
echo "</td>";

?>
<td>Office</td>
<td>
	<select name="office" onchange="this.form.submit();">
	<?php
	if(empty($office)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($o = mysqli_fetch_row($query_offices_counties)) {
		if($office==$o[0]) {
			echo "<option selected value'".$o[0]."'>".$o[0]."</option>";
		} else {
			echo "<option value'".$o[0]."'>".$o[0]."</option>";
		}
	}
	?>	
	
	</select>
</td>
<td>Incumbent</td>
<td>
	<select name="incumbent" onchange="this.form.submit();">
	<?php
		if(empty($incumbent)) {
			echo "<option selected value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($incumbent == "Y") {
			echo "<option selected value='Y'>Yes</option>";
		} else {
			echo "<option value='Y'>Yes</option>";
		}
		if($incumbent == "N") {
			echo "<option selected value='N'>No</option>";
		} else {
			echo "<option value='N'>No</option>";
		}
	?>
	</select>
</td>
<td>Party</td>
<td>
	<select name="party" onchange="this.form.submit();">
	<?php
		if(empty($party)) {
			echo "<option selected value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($party=="Dem") {
			echo "<option selected value='Dem'>Dem</option>";
		} else {	
			echo "<option value='Dem'>Dem</option>";
		}
		if($party=="Rep") {
			echo "<option selected value='Rep'>Rep</option>";
		} else {
			echo "<option value='Rep'>Rep</option>";
		}
	?>
	</select>
</td>
<?php
if(empty($county)) {
} else {
	echo "<td>Muni</td>";
	echo "<td name='muni'>";
	echo "<select id='muni_dropdown' name='muni' style='width:160px;' onchange='this.form.submit();'>";
	if(empty($muni)) {
		echo "<option selected value=''></option>";
	} else {
		echo "<option value=''></option>";
	}	
	while($m = mysqli_fetch_row($query_munis)) {
		if($muni==$m[0]) {
			echo "<option selected value'".$m[0]."'>".$m[0]."</option>";
		} else {
			echo "<option value'".$m[0]."'>".$m[0]."</option>";
		}
	}
	echo "</td>";
}
?>

<td>Slogan</td>
<td>
	<select name="slogan" onchange="this.form.submit();">
	<?php
	while($s = mysqli_fetch_row($query_slogan_counties)) {
		if($slogan==$s[0]) {
			echo "<option selected value'".$s[0]."'>".$s[0]."</option>";
		} else {
			echo "<option value'".$s[0]."'>".$s[0]."</option>";
		}
	}
	?>	
	
	</select>
</td>
<td>&nbsp;</td>
<td>Endorsement</td>
<td>
	<select name="endorsement" onchange="this.form.submit();">
	<?php
	$eee = [];
	while($o = mysqli_fetch_row($query_endorsement_counties)) {
		// split if there are multiple endorsements separated by commas
		$ee = split(",", $o[0]);
		foreach($ee as $e) {
			if(in_array($e, $eee)) {
				continue;
			}
			array_push($eee, $e);
			print_r($eee);
			if($endorsement==$e) {
				echo "<option selected value'".$e."'>".$e."</option>";
			} else {
				echo "<option value'".$e."'>".$e."</option>";
			}
		}
	}
	?>	
	
	</select>
</td>

</tr>
</table>
</form>
<?php
echo "<div style='float:left;'><b>".$num_rows." candidates</b></div>";
//echo "<div style='float:right; padding-bottom:4px; padding-right:100px;'><input id='btnHide' type='button' value='Show More Columns'/></div>";
echo "<br>";
//echo "</form>";

echo "<table border='1' class='tableFixHead' id='candidate_table'>";
echo "<thead>";
echo "<tr>";
echo "<th style='width:160px'>County</th>";
echo "<th style='width:160px'>Office</th>";
echo "<th style='width:160px'>Name</th>";
echo "<th style='width:60px'>Party</th>";
echo "<th style='width:300px'>Slogan</th>";
echo "<th style='width:160px;'>Endorsements</th>";
echo "<th style='width:160px;'>Email</th>";
echo "<th style='width:160px;'>Website</th>";
echo "<th style='width:160px;'>Socials</th>";
echo "<th style='width:300px;'>Address</th>";
echo "</tr>";
echo "</thead>";

echo "<tbody"; // style='display: block; height: 400px;'>";
while($row2 = mysqli_fetch_array($query2)) {

	$place = "";
	if(!empty($row2["ld"])) {
		$place = $row2["ld"];
	} else 	if(!empty($row2["cd"])) {
		$place =  "CD".$row2["cd"];
	} else if(!empty($row2["ward"])) {
		$place =  "Ward ".$row2["ward"];
		if(!empty($row2["precinct"])) {
			$place =  " Dist ".$row2["precinct"];
		}	
	} else if(!empty($row2["precinct"])) {
		$place =  "Dist ".$row2["precinct"]." ".$row2["muni"];		
	} else if(!empty($row2["county"])) {
		$place =  $row2["county"]." County";
	}
	
	echo "<tr onclick='highlightRow(this)' title='".$row2["name"]." - ".$row2["office"]." - ".$place."'>";
	
	echo "<td>";
	echo $place;
	echo "</td>";
	
	echo "<td>".$row2["office"]."</td>";
	
	echo "<td>".$row2["name"];
	if($row2["incumbent"] == "Y") {
		echo " *";
	}
	echo "</td>";
	echo "<td>".$row2["party"]."</td>";
	echo "<td>".$row2["slogan"]."</td>";	
	echo "<td>".$row2["endorsement"]."</td>";
	
	//echo "</td>";
	$email = $row2["email"];
	if(!empty($email)) {
		echo "<td><a href='mailto:".$email."'><img src='../img/email-logo.png' width=20' height='20' style='vertical-align: middle;'></a></td>";
	} else {
		echo "<td></td>";
	}
	$website = $row2["website"];
	if(!empty($website)) {
		echo "<td><a target='new' href='".$website."'><img src='../img/www-logo.jpeg' width=20' height='20' style='vertical-align: middle;'></a></td>";
	} else {
		echo "<td></td>";
	}
	$twitter = $row2["twitter"];
	$fb = $row2["facebook"];
	if(empty($twitter) and empty($fb)) {
		echo "<td></td>";
	} else {
		echo "<td valign='top'>";
		if(!empty($fb)) {
			echo "<a target='new' href='".$fb."'><img alt='FB' src='../img/facebook-logo.png' width=20' height='20' style='vertical-align: middle;'></a>";
		}
		if(!empty($twitter)) {
			echo "<a target='new' href='".$twitter."'><img alt='FB' src='../img/twitter-logo.png' width=40' height='40' style='vertical-align: middle;'></a>";
		}
		echo "</td>";
	}
	
	echo "<td>";
	if(!empty($row2["address"])) {
		echo $row2["address"].", ".$row2["town"].", ".$row2["state"].", ".$row2["zip"];
	}
	echo "</td>";
	echo "</tr>";
}
echo "</tbody>";
echo "</table>";

?>

</div>

<i>
"Slogan" indicates the Primary election endorsement. For office spanning multiple counties, "slogans" can be multiple, here only the 2 major are shown.
<br>
* indicates incumbent candidate.
<br>
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<br>
<br>
</body>

<script type="text/javascript">
tabdropdown.init("bluecompasstab", 1);

function changeCounty(form)
{
	var el = document.getElementById("muni_dropdown");
	if(el != null) {
		el.options.length = 0;
	}
	form.submit();
}

function changeDistrict(form)
{
	//var el = document.getElementById("muni_dropdown");
	//if(el != null) {
		//el.options.length = 0;
	//}
	form.submit();
}

function visibility()
{
	var elements = document.getElementsByName("muni_filter");
	for(let i = 0 ; i < elements.length; i++) {
		elements[i].style.visibility="visible";
	}
}

$(document).ready(function() {
	$('#btnHide').click(function() {
		if($('#candidate_table td:nth-child(7)').is(":visible")){
			$('#candidate_table td:nth-child(7),th:nth-child(7)').hide();
			$('#candidate_table td:nth-child(8),th:nth-child(8)').hide();
			$('#candidate_table td:nth-child(9),th:nth-child(9)').hide();
			$('#candidate_table td:nth-child(10),th:nth-child(10)').hide();			
			$('#candidate_table td:nth-child(11),th:nth-child(11)').hide();			
		} else {
			$('#candidate_table td:nth-child(7),th:nth-child(7)').show();
			$('#candidate_table td:nth-child(8),th:nth-child(8)').show();
			$('#candidate_table td:nth-child(9),th:nth-child(9)').show();
			$('#candidate_table td:nth-child(10),th:nth-child(10)').show();
			$('#candidate_table td:nth-child(11),th:nth-child(11)').show();
		}
	});
});

function highlightRow(row)
{
	var table = document.getElementById("candidate_table");
    
	// deselect rows
    for(var i = 1; i < table.rows.length; i++)
    {
		table.rows[i].style.backgroundColor = "";
	}	
	row.style.backgroundColor = "#87CEFA";
}


function openTab(tabName) {
  var i;
  var x = document.getElementsByClassName("tab2");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  var x = document.getElementsByClassName("tab-button");
  for (i = 0; i < x.length; i++) {
    x[i].style.backgroundColor = "#5E6B7F";  
  }
  var tabButtonId=tabName+"-tab";
  //alert(tabButtonId + ": " + document.getElementById(tabButtonId));
  document.getElementById(tabName).style.display = "block";
  document.getElementById(tabButtonId).style.backgroundColor = "#8DA1BF";  
}

</script>

</html>