<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>

<script src="../sorttable.js"></script>
<script src="../jquery-3.3.1.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>

<script lang="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
<script lang="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js"></script>

<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
<link rel="stylesheet" type="text/css" href="../bluecompass.css" />

</head>

<body style="font-family:Arial; font-size:small;">

<?php
include("../db.php");

include("../menu.html");


$year = "2023";

echo "<center><H2>Who is running in ".$year."</H2></center>";

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

$sql2 = "select * from candidates where election_year='".$year."'";
if(!empty($el_type)) { 
	$sql2 .= " and election_type='".$el_type."'";
}
if(!empty($party)) { 
	$sql2 .= " and party='".$party."'";
}
if(!empty($county)) { 
	//$sql2 .= " and county_geo like '%".$county."%'";
	$sql2 .= " and county = '".$county."'";
}
if(!empty($office)) {
	if($office == "Legislature") {
		$sql2 .= " and office in ('Senate','Assembly')";
	} else {
		$sql2 .= " and office like '".$office."%'";
	}
}
if(!empty($district)) {
	if(strpos($district, "CD") === false) {
		$ld = $district; #substr($district, 2);
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

$sql2 .= " order by ld, ifnull(sort_by, 100), county, office, cd, ward, precinct, party, name";

syslog(LOG_INFO, $app.$sql1);
syslog(LOG_INFO, $app.$sql2);

echo "Here you can see the ".$year." NJ Primary Election candidates for State and County offices.";
echo "<br>";
echo "<br>";
echo "If you are not sure of your district you can find your electoral information (Representatives, Candidates) "; 
echo "<a href='http://bluecompass.org/myinfo/'>here</a>.";
echo "<hr>";

if($is_address) {
	echo "<b>Address:</b> ".$row1["street_number"]." ".$row1["street_name"];
	echo "<br>";
}

		
$query2 = mysqli_query($conn, $sql2);

$num_rows = mysqli_num_rows($query2);


$sql_counties = "select distinct county
from candidates where election_year='".$year."'and election_type='".$el_type."' and county is not null
order by 1";

$sql_cds = "select distinct concat('CD',cd)
from candidates where election_year='".$year."'and election_type='".$el_type."' and cd is not null
order by 1";

$sql_lds = "select distinct ld
from candidates where election_year='".$year."'and election_type='".$el_type."' and ld is not null
order by 1";

$sql_offices = "select distinct if(office like 'Commissioner%', 'Commissioner', office), sort_by  
from candidates where election_year='".$year."'and election_type='".$el_type."'
union select 'Legislature', 11 as sort_by from dual 
order by sort_by";

$sql_slogan = "select distinct slogan
from candidates where election_year='".$year."'and election_type='".$el_type."' 
order by 1";

$sql_endorsement = "select distinct endorsements
from candidates where election_year='".$year."'and election_type='".$el_type."' 
order by 1";

if($debug == "y") {
	echo "$sql2";
	echo "<br>";
}

$query_counties = mysqli_query($conn, $sql_counties);
//$query_cds = mysqli_query($conn, $sql_cds);
$query_lds =mysqli_query($conn, $sql_lds);
$query_offices = mysqli_query($conn, $sql_offices);
$query_slogan = mysqli_query($conn, $sql_slogan);
$query_endorsement = mysqli_query($conn, $sql_endorsement);


?>


<form action="candidates.php" method="GET">
<table horizontal-pad="10" border="0">
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
<td>County</td>
<td>
	<select name="county" onchange="this.form.submit();">
	<?php
	if(empty($county)) {
		echo "<option selected value''></option>";
	} else {
		echo "<option value''></option>";
	}
	while($d = mysqli_fetch_row($query_counties)) {
		if($county==$d[0]) {
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
</tr>
<tr>
<td>Slogan</td>
<td colspan="4">
	<select name="slogan" onchange="this.form.submit();">
	<?php
	while($s = mysqli_fetch_row($query_slogan)) {
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
<td colspan="3">
	<select name="endorsement" onchange="this.form.submit();">
	<?php
	$eee = [];
	while($o = mysqli_fetch_row($query_endorsement)) {
		// split if there are multiple endorsements separated by commas
		$ee = split(",", $o[0]);
		foreach($ee as $e) {
			//if(in_array($e, $eee)) {
				//continue;
			//}
			array_push($eee, trim($e));
		}
	}
	$eeee = array_unique($eee);
	foreach($eeee as $e) {
		if($endorsement==$e) {
			echo "<option selected value'".$e."'>".$e."</option>";
		} else {
			echo "<option value'".$e."'>".$e."</option>";
		}
	}
	//print_r($eee);
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
echo "<th style='width:100px'>Place</th>";
echo "<th style='width:100px'>Office</th>";
echo "<th style='width:130px'>Name</th>";
echo "<th style='width:60px'>Party</th>";
echo "<th style='width:200px'>Slogan</th>";
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
	} else {
		$place = "State";
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
	$party = $row2["party"];
	echo "<td>".$party."</td>";
	$slogan = $row2["slogan"];
	if($slogan == '') {
		$slogan = "Regular ";
		if($party == "Dem") {
			$slogan .= "Democratic";
		} elseif($party == "Rep") {
			$slogan .= "Republican";
		}
	}
	echo "<td>".$slogan."</td>";
	$e = $row2["endorsements"];
	$l = $row2["endorse_link"]; // not working with multiple links
	$ee = split(",", $e);
	$tt = $row2["endorse_tooltip"];
	echo "<td>";
	//$k = 0;
	foreach($ee as $e2) {
		if(stristr($l, $e2) != false) { // link contains the word of the endorsing org. this doesn't work where there are multiple endorsements
			echo " <a title='".$tt."' target='new' href='".$l."'>".$e2."</a>";
		} else if($e2 == "NJWFA" and stristr($l, "workingfamilies") != false) {
			echo " <a title='".$tt."' target='new' href='".$l."'>".$e2."</a>";
		} else {
			//if($k > 0) {
				echo "<div title='".$tt."'>".$e2."</div>";
			//} else {
				//echo " ".$e2;
			//}
		}
		//$k += 1;
	}
	echo "</td>";
	
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
			echo "<a target='new' href='".$twitter."'><img alt='FB' src='../img/twitter-logo.png' width=20' height='20' style='vertical-align: middle;'></a>";
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
<button id="excel-button">Export To Excel</button>
<br>
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
	//var el = document.getElementById("muni_dropdown");
	var el = document.getElementById("district");
	if(el != null) {
		el.options.length = 0;
	}
	form.submit();
}

function changeDistrict(form)
{
	var el = document.getElementById("county");
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

var wb = XLSX.utils.table_to_book(document.getElementById('candidate_table'));
var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary'});

function s2ab(s) {
	var buf = new ArrayBuffer(s.length);
	var view = new Uint8Array(buf);
	for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
	return buf;
}

$("#excel-button").click(function(){
	saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), 'candidates_2021.xlsx');
});

</script>

</html>