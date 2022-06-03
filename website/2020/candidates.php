<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>
<script src="../sorttable.js"></script>
<script src="../jquery-3.3.1.min.js" type="text/javascript"></script>

<style type="text/css">	
	table.sortable thead {
		background-color:#eee;
		color:#666666;
		font-weight: bold;
		cursor: default;
	}

	/* Fix table head */
	.tableFixHead    { overflow-y: auto; height: 100px; }
	.tableFixHead th { position: sticky; top: 0; background-color:#eee;}
/*	
	td 
	{
	  display: block;
	  overflow-y: hidden;
	  max-height: 20px;
	}	
*/

	.tableFixHead {
		//display: block;
		//height: 500px;
		overflow-x: auto;
		white-space: nowrap;
	}
	
</style>
<!-- <meta http-equiv="refresh" content="0; url=data?ld=21" /> -->

<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>

</head>

<body style="font-family:Arial">

<?php
include("../db-a.php");

include("../menu.html");

$year = "2020";

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
$debug = $_GET["debug"];
$muni = $_GET["muni"];

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
} else {   // not by address
	$sql2 = "select * from candidates_view where election_year='".$year."'";
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
			$sql2 .= " and ld='".$ld."'";
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
}

$sql2 .= " order by ifnull(sort_by, 100), county, office, cd, ld, ward, precinct, party, name";

if($debug == "gio") {
	echo "$sql2";
	echo "<br>";
}

syslog(LOG_INFO, $app.$sql1);
syslog(LOG_INFO, $app.$sql2);

echo "Here you can see the 2020 NJ candidates. At this time only 13 (out of 21) counties have provided the list of candidates.";
echo "<br>";
echo "In the next weeks information should be available from the remaining counties and some of the current counties may publish updates.";
echo "<br>";
echo "There is the aim to load in this database also the Democratic County Committees candidates.";
echo "Eventually it is possible that municipal races candidates will be loaded in this database.";
echo "<br>";
echo "<br>";
echo "If you are not sure of your district you can find your electoral information (Representatives, Candidates)"; 
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
from candidates where election_year='2020'and election_type='PRI' and county is not null
order by 1";

$sql_cds = "select distinct concat('CD',cd)
from candidates where election_year='2020'and election_type='PRI' and cd is not null
order by 1";

$sql_lds = "select distinct concat('LD',ld)
from candidates where election_year='2020'and election_type='PRI' and ld is not null
order by 1";

$sql_offices = "select distinct 
case when office like 'Freeholder %' then 'Freeholder' else office end
from candidates where election_year='2020'and election_type='PRI'
order by sort_by";

$query_counties = mysqli_query($conn, $sql_counties);
$query_cds = mysqli_query($conn, $sql_cds);
$query_lds =mysqli_query($conn, $sql_lds);
$query_offices = mysqli_query($conn, $sql_offices);

$districts = array();
while($d = mysqli_fetch_array($query_cds)) {
	$districts[] = $d[0];
}
while($d = mysqli_fetch_array($query_lds)) {
	$districts[] = $d[0];
}
//print_r($districts);

if(!empty($county)) {
	$sql_munis = "select distinct muni from candidate_view2 where election_year='".$year."' 
	and county='".$county."' and muni<>'' order by 1";
}
if(!empty($sql_munis)) {
	$query_munis = mysqli_query($conn, $sql_munis);
	//while($m = mysqli_fetch_array($query_munis)) {
	//	echo $m[0].",";
	//}	
}
?>

<form action="candidates.php" method="GET">
<table>
<tr>
<td>Office</td>
<td>
	<select name="office" onchange="this.form.submit();">
	<?php
	if(empty($office)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($o = mysqli_fetch_row($query_offices)) {
		if($office==$o[0]) {
			echo "<option selected value'".$o[0]."'>".$o[0]."</option>";
		} else {
			echo "<option value'".$o[0]."'>".$o[0]."</option>";
		}
	}
	?>	
	
	</select>
</td>
<td>County</td>
<td>
	<select name="county" onchange="changeCounty(this.form);">
	<?php
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
	?>	
	</select>
</td>
<td>District</td>
<td>
	<select name="district" onchange="this.form.submit();">
	<?php
	echo "<option value=''></option>";
	foreach($districts as $d) {
		if($d == $district) {
			echo "<option selected value='".$d."'>".$d."</option>";
		} else {
			echo "<option value='".$d."'>".$d."</option>";
		}
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
	//echo "<td name='muni_filter' style='visibility:hidden;'>Muni</td>";
	//echo "<td id='muni-dropdown' name='muni_filter' style='visibility:hidden;'>";
	//echo "<select name='muni' onchange='this.form.submit();'>";
	//echo "</td>";
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
<!--
<td name="muni_filter" style="visibility:hidden;">Ward</td>
<td name="muni_filter" style="visibility:hidden;">
	<select name="ward" onchange="this.form.submit();">
</td>
<td name="muni_filter" style="visibility:hidden;">Precinct</td>
<td name="muni_filter" style="visibility:hidden;">
	<select name="precinct" onchange="this.form.submit();">
</td>
-->
</tr>
</table>
</form>


<?php
echo "<div style='float:left;'><b>".$num_rows." candidates</b></div>";
echo "<div style='float:right; padding-bottom:4px; padding-right:100px;'><input id='btnHide' type='button' value='Show More Columns'/></div>";
echo "<br>";
//echo "</form>";

echo "<table border='1' class='tableFixHead' id='candidate_table'>";
echo "<thead>";
echo "<tr>";
echo "<th style='width:160px'>Office</th>";
echo "<th style='width:160px'>Place</th>";
echo "<th style='width:160px'>Name</th>";
echo "<th style='width:60px'>Party</th>";
echo "<th style='width:300px'>Slogan</th>";
echo "<th style='width:160px;'>Website</th>";
echo "<th style='width:160px; display:none;'>Email</th>";
echo "<th style='width:300px; display:none;'>Address</th>";
echo "<th style='width:160px; display:none;'>VoteSmart</th>";
echo "<th style='width:160px; display:none;'>OpenSecret</th>";
echo "<th style='width:160px; display:none;'>ProPublica</th>";
echo "</tr>";
echo "</thead>";

echo "<tbody"; // style='display: block; height: 400px;'>";
while($row2 = mysqli_fetch_array($query2)) {

	$place = "";
	if(!empty($row2["ld"])) {
		$place = "LD".$row2["ld"];
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
	echo "<td>".$row2["office"]."</td>";
	
	echo "<td>";
	echo $place;
	echo "</td>";
	
	echo "<td>".$row2["name"];
	if($row2["incumbent"] == "Y") {
		echo " *";
	}
	echo "</td>";
	echo "<td>".$row2["party"]."</td>";
	echo "<td>".$row2["slogan"]."</td>";
	echo "</td>";
	$website = $row2["website"];
	if(!empty($website)) {
		echo "<td><a href='".$website."'>$website</a></td>";
	} else {
		echo "<td></td>";
	}
	$email = $row2["email"];
	if(!empty($email)) {
		echo "<td style='display:none;'><a href='mailto:".$email."'>$email</a></td>";
	} else {
		echo "<td style='display:none;'></td>";
	}
	echo "<td style='display:none;'>";
	//echo "<div style='max-height:30px;'>";
	if(!empty($row2["address"])) {
		if($row2["office"] != "County Committee") {
			echo $row2["address"].", ".$row2["town"].", ".$row2["state"].", ".$row2["zip"];
		} else {
			echo $row2["address"];
		}
	}
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

</script>

</html>