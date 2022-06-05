<html>
<head>
<title>BlueCompass.org - My Info</title>
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

.tab-button2 {
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

<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
<link rel="stylesheet" type="text/css" href="../bluecompass.css" />
<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>

</head>
<?php
ini_set('display_errors','off');

include("../db-a.php");

$properties2 = parse_ini_file("../properties.ini");

$host2 = $properties2["host"];
$username2 = $properties2["username"];
$password2 = $properties2["password"];
$db_name2 = $properties2["db_name"];

$conn2 = mysqli_connect($host2, $username2, $password2, $db_name2);
if (!$conn2) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}


$election_year = $_GET["election_year"];
$street_num = $_GET["street_number"]; 
$street_name = $_GET["street_name"];
$muni = $_GET["town"];
$county = $_GET["county"];
$muni = $_GET["town"];

$first_name = $_GET["first_name"];
$last_name = $_GET["last_name"];
$dob = $_GET["dob"];
$display = $_GET["display"];
$debug = $_GET["debug"];

$year = date("Y");

$sql = "select distinct county from alpha_voter_list_state order by 1";
$query = mysqli_query($conn, $sql);

$counties = array();
while($row = mysqli_fetch_array($query)) {
	$c1 = $row["county"];
	$c2 = ucwords(strtolower($c1));
	$counties[]=$c2;
}
//print_r($counties);
$num_counties = count($counties);

$sql_muni = "select muni_from_alpha_state from municipal_list where county = '".$county."' order by 1";
$query_muni = mysqli_query($conn, $sql_muni); 

$search_by=$_GET["search_by"];
if(!empty($search_by)) {
	if($search_by == "address") {
		$sql2 = "select * from alpha_voter_list_state where 1=1 and county = '".$county."' 
		and municipality = '".$muni."' 
		and street_name like '".$street_name."%' 
		and street_number = '".$street_num."' limit 1";
	} else {
		$sql2 = "select * from alpha_voter_list_state where 1=1 and county = '".$county."' 
		and first_name = '".$first_name."' 
		and last_name = '".$last_name."' 
		and dob = '".$dob."' limit 1";
	}
		
	$address_found = true;
	
	$query2 = mysqli_query($conn, $sql2);
	
	//$address_found = mysql_num_rows($query2) > 0;
	$num_rows = mysqli_num_rows($query2);
	if($num_rows < 1) {
		$address_found = false;
		//echo "Num rows: ".$num_rows.", address found 2: ".$address_found;
		//return;
		if(!empty($debug)) {
			echo $sql2."<br>";
		}
	} else {
		//echo "Num rows: ".$num_rows.", address found 2: ".$address_found;

	while($row2 = mysqli_fetch_array($query2)) {
		$ld = $row2["ld"];
		$cd = $row2["cd"];
		$ward = $row2["ward"];
		$precinct = $row2["precinct"];
		if(empty($muni)) {
			$muni = $row2["municipality"];
		}
		//if(empty($street_name)) {
			$street_name = $row2["street_name"];
		//}
		if(empty($street_num)) {
			$street_num = $row2["street_number"];
		}
	}
	$sql_repr = "select county, cd, ld, muni, muni_id, ward, precinct,
		name, null, party, email, office, last_elected, term, expire_on, 
		website, votesmart, propublica, opensecret, twitter, 
		notes, incumbent, sort_by
		from representatives
		where (county_geo='' or county='".$county."' or cd = '".$cd."' or  ld='".$ld."'
		or (muni like '".$muni."%' and ((ward='".$ward."' or ward='')
		and (precinct='".$precinct."' or precinct=''))))";
		if(!empty($display)) {
			$sql_repr .= " and display='Y' "; 
		}
		$sql_repr .= " order by sort_by, office, party, expire_on, name";
		/*
	$sql_repr .= "union all
		select a.county, null, null, a.town, a.muni_id, ward, precinct,
		member_name, gender, 'Dem', member_email,
		ifnull(member_role,'Municipal Committee'), election_year, term_years, next_election, 
		null, null, null, null, null, 
		null, null, 200
		from dem_committee_members a join dem_committee d on d.county = a.county
		where a.town like '".$muni."%' and (ward='".$ward."' or ward='') and precinct='".$precinct."'
		and d.muni='' 
		order by sort_by, office, party, expire_on, name";
		*/
		
	$sql_cand = "select * from candidates_new 
		where (county='".$county."' or ld='LD".$ld."' or cd='CD".$cd."' 
		or (muni='".$muni."' and ((ward = '".$ward."' or ward='') 
		and (precinct='".$precinct."' or precinct=''))) or office='US Senate' or office='President')
		and election_year >= year(now())
		-- and election_type='GEN' 
		order by election_type, ifnull(sort_by, 100), office, ward, precinct, party, slogan, name";

	$sql_races = "select county, cd, ld, muni, muni_id, ward, precinct,
	name, null, party, email, office, first_elected, last_elected, term, expire_on, 
	website, votesmart, propublica, opensecret, twitter, 
	notes, incumbent, sort_by
	from representatives
	where expire_on = year(now())";
	if(!empty($display)) {
		$sql_races .= " and display='Y' "; 
	}
	if(!empty($county)) {
		$sql_races .= " and (county='".$county."'";
		$sql_races .= " or cd='".$cd."'"; //in(select distinct cd_2 from municipal_list where county='".$county."'";
		if(!empty($muni)) {
			$sql_races .= " or muni like '".$muni."%'";
		}
		//$sql_races .= ")";
		
		$sql_races .= " or ld='".$ld."'";
		//$sql_races .= ")";
		
		//if(!empty($muni)) {
			//$sql_races .= " or muni='".$muni."'";
		//}
		$sql_races .= " or office = 'US Senate'"; 
		$sql_races .= ")";
	}
	$sql_races .= " union all
		select county, null, null, town, null, ward, precinct,
		member_name, null, 'Dem', member_email, 'County Committee', null, null, '2', election_year,
		null, null, null, null, null,
		notes, null, 200
		from dem_committee_members
		where election_year = year(now()) and county='".$county."' and town='".$muni."'";
		if($ward != "00") {
			$sql_races .= " and ward='".$ward."'";
		}
		if($precinct != "00") {
			$sql_races .= " and precinct='".$precinct."'";
		}
		
	$sql_races .= " order by sort_by, office, cd, ld, muni, ward, precinct, name";
		
	if(!empty($debug)) {			
		print($sql2);
		echo "<br>";
		print($sql_repr);
		echo "<br>";
		print($sql_cand);
		echo "<br>";
		print($sql_races);
	}
	}
}

?>

<body style="font-family:Arial">

<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v6.0"></script>

<?php include("../menu.html"); ?>

<center><H2>Your Voting Districts, Representatives and Candidates</H2></center>
<br>
<br>
Providing an address or a name, you will be able to see voting districts, the representatives and the candidates running in the next elections.
<br>
<br>
<i>This section is still in progress. More elected officials information is being loaded and 2020 candidates, 
for primaries and then general, will be loaded as the information become available.
<br>
The type and amount of information displayed can be expanded (for candidates usually there are address and contact information) 
as well as some evaluation/score and notes can be added.
<br>
Please, consider to like BlueCompass on Facebook 
<div class="fb-like" data-href="https://www.facebook.com/bluecompass.org" data-width="" data-layout="button" 
data-action="like" data-size="small" data-share="false"></div> 
to receive updates on major upgrade as well as to ask/suggest any improvements
</i>
<br>
<hr>
<div class="w3-bar w3-black">
  <button class="tab-button" onclick="openTab('address')" id="address-tab" style="background-color:#8DA1BF;">Address</button>
  <button class="tab-button" onclick="openTab('name')" id="name-tab">Name</button>
</div>

<div id="address" class="tab">
<form action="index.php">
  <table border="0" style="border-spacing: 5px;">
	<tr>
	  <td width="10%">County:</td>
	  <td width="10%">
	  <select name="county" id="acounty" onchange="getTowns('acounty', <?php echo $town; ?>);">
		<option value=""></option>
		<?php
		for($i = 0; $i < $num_counties; $i++) {
			if($county == $counties[$i]) {
				echo "<option value=".$counties[$i]." selected>".$counties[$i]."</option>";
			} else {
				echo "<option value=".$counties[$i].">".$counties[$i]."</option>";
			}
		}
		?>
	  </select>
	  </td>
	  <td></td>
	</tr>
	<tr>
	  <td>Municipality:</td>
	  <td><select name="town" id="town-dropdown" style="width:170px;">
	  <option value=""></option>
	  <?php
		while($row = mysqli_fetch_array($query_muni)) {
			$m = $row["muni_from_alpha_state"];
			if($muni == $m) {
				echo "<option value='".$m."' selected>".$m."</option>";
			} else {
				echo "<option value='".$m."'>".$m."</option>";
			}
		}			
	  ?>
	  </select>
	  </td>
	  <td></td>
	</tr>
	<tr>
	  <td>Street Number:</td>
	  <td><input id="street_num" style="height:20px; width:50px" type="text" name="street_number" value="<?php echo $street_num; ?>"></td>
	  <td></td>
	</tr>
	<tr>
	  <td>Street Name:</td>
	  <td><input id="street_name" style="height:20px;" type="text" name="street_name" value="<?php echo ucwords(strtolower($street_name)); ?>"></td>
	  <td style='font-size:small;' valign='bottom'><i>Don't enter 'place type' like Street or Avenue or Place, you can use St, Pl, Av</i></td>
	</tr>
  </table>
  <br>
  <input type="submit" value="Search By Address" id="submit-address">
  <input type="hidden" name="election_year" value="2019">
  <input type="hidden" name="election_type" value="GEN">
  <input type="hidden" name="search_by" value="address">
  <input type="hidden" name="display" value="Y">
  <?php echo "<input type='hidden' name='debug' value='".$debug."'>";?>
</form>
</div>

<div id="name" class="tab" style="display:none;">
<form action="index.php">  
  <table style="border-spacing: 5px;">
	<tr>
	  <td width="40%">County:</td>
	  <td>
	  <select name="county">
		<option value=""></option>
		<?php
		for($i = 0; $i < $num_counties; $i++) {
			if($county == $counties[$i]) {
				echo "<option value=".$counties[$i]." selected>".$counties[$i]."</option>";
			} else {
				echo "<option value=".$counties[$i].">".$counties[$i]."</option>";
			}
		}
		?>
	  </select>
	  </td>
	</tr>
	<tr>
	  <td>First Name:</td>
	  <td><input style="height:20px;" type="text" name="first_name"></td>
	</tr>
	<tr>
	  <td>Last Name:</td>
	  <td><input style="height:20px;" type="text" name="last_name"></td>
	</tr>
	<tr>
	  <title>mm/dd/yyyy</title>
	  <td>Date of Birth:</td>
	  <td><input style="height:20px;" type="text" name="dob"></td>
	</tr>
  </table>

  <br>
  <input type="submit" value="Search By Name" id="submit-name">
  <input type="hidden" name="election_year" value="2019">
  <input type="hidden" name="election_type" value="GEN">
  <input type="hidden" name="search_by" value="name">
  <input type="hidden" name="display" value="Y">
  <?php echo "<input type='hidden' name='debug' value='".$debug."'>";?>
</form> 
</div>

<i>
Addresses are extracted from the voter list as of Dec 5th 2019, addresses of not registered voters are not in the database.
<br>
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<hr>
<br>

<?php
if($address_found != true) {
	echo "Address not found";
}

if(!empty($search_by) && $address_found == true) {
	echo "<div class=\"w3-bar w3-black\">";
	echo "<button class=\"tab-button2\" onclick=\"openTab2('info')\" id=\"info-tab\" style=\"background-color:#8DA1BF; font-weight:bold;\">Your Info</button>";
	echo "<button class=\"tab-button2\" onclick=\"openTab2('representatives')\" id=\"representatives-tab\">Office Holders</button>";
	//echo "<button class=\"tab-button2\" onclick=\"openTab2('races')\" id=\"races-tab\">Races ".$year."</button>";
	echo "<button class=\"tab-button2\" onclick=\"openTab2('candidates')\" id=\"candidates-tab\">Candidates ".$year."</button>";	
	echo "</div>";

	echo "<div id='info' class='tab2'>";
	echo "<table class='padded_table'>";
	echo "<tr>";
	echo "<td><b>County:</b></td><td>".$county."</td>";
	echo "<td><b>Dem County Committee:</b></td><td><a href='../party/comm_seats.php?county=".$county."#table'>details</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><b>Municipality:</b></td><td>".ucwords(strtolower($muni))."</td>";
	echo "<td><b>Dem Municipal Committee:</b></td><td><a href='../party/comm_seats.php?county=".$county."&town=".$muni."#table'>details</a></td>";
	echo "</tr>";
	echo "<tr><td><b>Address:</b></td><td>".$street_num." ".ucwords(strtolower($street_name))."</td></tr>";	
	echo "<tr><td><b>Congressional District:</b></td><td>NJ".$cd."</td></tr>";
	echo "<tr><td><b>Legislative District:</b></td><td>LD".$ld."</td></tr>";
	echo "<tr><td><b>Ward:</b></td><td>".$ward."</td></tr>";
	echo "<tr><td><b>Precinct:</b></td><td>".$precinct."</td></tr>";	
	echo "</table>";
	echo "</div>";
	
	echo "<div id='representatives' class='tab2' style='display:none;'>";
	//echo "<i>Note: Municipal Elected officials data is not updated yet</i>";
	echo "<table class='padded_table'>";
	echo "<tr>";
	echo "<th>Office</th>";
	echo "<th>Jurisdiction</th>";
	echo "<th>Name</th>";
	echo "<th>Party</th>";
	echo "<th>Elected On</th>";
	echo "<th>Term</th>";
	echo "<th>Expire On</th>";
	echo "<th>Email</th>";
	echo "<th colspan='5'>Other Info</th>";
	echo "</tr>";
	$query_repr = mysqli_query($conn, $sql_repr);
	while($row = mysqli_fetch_array($query_repr)) {
		$notes = $row["notes"];
		if(!empty($notes)) {
			echo "<tr title='".$notes."'>";
		} else {
			echo "<tr>";
		}
		echo "<td>".$row["office"]."</td>";
		if(!empty($row["muni"])) {
			echo "<td>".$row["muni"]."</td>";
		} else if(!empty($row["cd"])) {
			echo "<td>NJ".$row["cd"]."</td>";
		} else if(!empty($row["ld"])) {
			echo "<td>LD".$row["ld"]."</td>";
		} else if(!empty($row["county"])) {
			echo "<td>".$row["county"]." County</td>";
		} else {
			echo "<td>NJ</td>";
		}
		echo "<td>".$row["name"]."</td>";
		echo "<td>".$row["party"]."</td>";
		echo "<td>".$row["last_elected"]."</td>";
		echo "<td>".substr($row["term"],0,1)." years</td>";
		$expire_on = $row["expire_on"];
		if($expire_on == date("Y")) {
			echo "<td style='font-weight:bold;'>".$expire_on."</td>";
		} else {
			echo "<td>".$expire_on."</td>";
		}
		if(!empty($row["email"])) {
			echo "<td><a href='mailto:".$row["email"]."'>email</a></td>";
		} else {
			echo "<td></td>";
		}			
		if(!empty($row["website"])) {
			echo "<td><a target='new' href='".$row["website"]."'>website</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["votesmart"])) {
			echo "<td><a target='new' href='".$row["votesmart"]."'>votesmart</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["propublica"])) {
			echo "<td><a target='new' href='".$row["propublica"]."'>propublica</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["opensecret"])) {
			echo "<td><a target='new' href='".$row["opensecret"]."'>opensecret</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["twitter"])) {
			echo "<td><a target='new' href='".$row["twitter"]."'>twitter</a></td>";
		} else {
			echo "<td></td>";
		}
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";
	/*
	echo "<div id='races' class='tab2' style='display:none;'>";
	//echo "<i>Note: Municipal races data is not completed yet</i>";
	//echo $sql_races;
	echo "<table class='padded_table'>";
	echo "<tr>";
	echo "<th>Office</th>";
	echo "<th>Place</th>";
	echo "<th>Term</th>";
	echo "<th>Incumbent</th>";
	echo "<th>Party</th>";
	echo "<th>First Elected On</th>";
	echo "<th>Last Elected On</th>";
	echo "<th>Expire On</th>";
	echo "<th>Email</th>";
	echo "<th colspan='5'>Online</th>";
	echo "</tr>";
	$query_races = mysqli_query($conn, $sql_races);
	while($row = mysqli_fetch_array($query_races)) {
		$notes = $row["notes"];
		if(!empty($notes)) {
			echo "<tr title='".$notes."'>";
		} else {
			echo "<tr>";
		}
		echo "<td>".$row["office"]."</td>";
		if(!empty($row["muni"])) {
			echo "<td>".$row["muni"]."</td>";
		} else if(!empty($row["cd"])) {
			echo "<td>NJ".$row["cd"]."</td>";
		} else if(!empty($row["ld"])) {
			echo "<td>LD".$row["ld"]."</td>";
		} else if(!empty($row["county"])) {
			echo "<td>".$row["county"]." county</td>";
		} else {
			echo "<td>NJ</td>";
		}
		echo "<td>".substr($row["term"],0,1)." years</td>";
		echo "<td>".$row["name"]."</td>";
		echo "<td>".$row["party"]."</td>";
		echo "<td>".$row["first_elected"]."</td>";
		echo "<td>".$row["last_elected"]."</td>";
		$expire_on = $row["expire_on"];
		echo "<td>".$expire_on."</td>";
		if(!empty($row["email"])) {
			echo "<td><a href='mailto:".$row["email"]."'>email</a></td>";
		} else {
			echo "<td></td>";
		}			
		if(!empty($row["website"])) {
			echo "<td><a target='new' href='".$row["website"]."'>website</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["votesmart"])) {
			echo "<td><a target='new' href='".$row["votesmart"]."'>votesmart</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["propublica"])) {
			echo "<td><a target='new' href='".$row["propublica"]."'>propublica</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["opensecret"])) {
			echo "<td><a target='new' href='".$row["opensecret"]."'>opensecret</a></td>";
		} else {
			echo "<td></td>";
		}
		if(!empty($row["twitter"])) {
			echo "<td><a target='new' href='".$row["twitter"]."'>twitter</a></td>";
		} else {
			echo "<td></td>";
		}
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";
	*/
	
	echo "<div id='candidates' class='tab2' style='display:none;'>";
	//echo "<i>Note: 2020 Candidates will be uploaded as their information become available</i>";	
	//echo $sql_cand;
	echo "<table class='padded_table'>";
	echo "<tr>";
	echo "<th>Office</th>";
	echo "<th>Jurisdiction</th>";
	echo "<th>Name</th>";
	echo "<th>Party</th>";
	echo "<th>Term</th>";
	echo "<th>Email</th>";
	echo "<th colspan='5'>Other Info</th>";
	echo "<th>Slogan</th>";
	echo "</tr>";
	
	$query_cand = mysqli_query($conn, $sql_cand);	
	while($row2 = mysqli_fetch_array($query_cand)) {
		echo "<tr>";
		echo "<td>".$row2["office"]."</td>";
		if(!empty($row2["muni"])) {
			echo "<td>".$row2["muni"]."</td>";
		} else if(!empty($row2["cd"])) {
			echo "<td>NJ".$row2["cd"]."</td>";
		} else if(!empty($row2["ld"])) {
			echo "<td>".$row2["ld"]."</td>";
		} else if(!empty($row2["county"])) {
			echo "<td>".$row2["county"]." County</td>";
		} else {
			echo "<td>NJ</td>";
		}
		echo "<td>".$row2["name"]."</td>";
		echo "<td>".$row2["party"]."</td>";
		echo "<td>".$row2["term"]."</td>";
		if(!empty($row2["email"])) {
			echo "<td><a href='mailto:".$row2["email"]."'>email</a></td>";
		} else {
			echo "<td></td>";
		}			
		if(!empty($row2["website"]) or !empty($row2["facebook"])) {
			echo "<td>";
			if(!empty($row2["website"])) {
				echo "<a target='new' href='".$row2["website"]."'>website</a>  ";
			}
			if(!empty($row2["facebook"])) {
				echo "<a target='new' href='".$row2["facebook"]."'>facebook</a>";
			}
			echo "</td>";
		} else {
			echo "<td></td>";
		}		
		if(!empty($row2["votesmart"])) {
			echo "<td><a target='new' href='".$row2["votesmart"]."'>votesmart</a></td>";
		} else {
			echo "<td></td>";
		}		
		if(!empty($row2["propublica"])) {
			echo "<td><a target='new' href='".$row2["propublica"]."'>propublica</a></td>";
		} else {
			echo "<td></td>";
		}		
		if(!empty($row2["opensecret"])) {
			echo "<td><a target='new' href='".$row2["opensecret"]."'>opensecret</a></td>";
		} else {
			echo "<td></td>";
		}	
		if(!empty($row["twitter"])) {
			echo "<td><a target='new' href='".$row["twitter"]."'>twitter</a></td>";
		} else {
			echo "<td></td>";
		}		
		echo "<td>".$row2["slogan"]."</td>";		
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";
}
?>
<br>
<script>

function openTab(tabName) {
  var i;
  var x = document.getElementsByClassName("tab");
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


function openTab2(tabName) {
  //alert(tabName);
  var i;
  var x = document.getElementsByClassName("tab2");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  var x = document.getElementsByClassName("tab-button2");
  for (i = 0; i < x.length; i++) {
    x[i].style.backgroundColor = "#5E6B7F"; 
	x[i].style.fontWeight = "normal"; 	
  }
  var tabButtonId=tabName+"-tab";
  //alert(tabButtonId + ": " + document.getElementById(tabButtonId));
  document.getElementById(tabName).style.display = "block";
  document.getElementById(tabButtonId).style.backgroundColor = "#8DA1BF"; 
  document.getElementById(tabButtonId).style.fontWeight = "bold";
}

function getTowns(elem_id, selected_town) {
  //if (id=="") {
   // document.getElementById(elem_id).innerHTML="";
  //  return;
  //}
  //alert(selected_town);
  e = document.getElementById(elem_id);
  val = e.options[e.selectedIndex].value;
  //alert(val);
  
  // also clear the other fields
  document.getElementById("street_num").value="";
  document.getElementById("street_name").value="";
  
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      //document.getElementById("txtHint").innerHTML=this.responseText;
	  //alert(this.responseText);

    const data = JSON.parse(this.responseText);
	//alert(data);
	
	let dropdown = document.getElementById('town-dropdown');
	dropdown.length = 0;
	  
    let option;
	option = document.createElement('option');
	option.text = "";
	option.value = "";
	dropdown.add(option);
    
	for (let i = 0; i < data.length; i++) {
      option = document.createElement('option');
	  //alert(data[i]);
      option.text = data[i];
      option.value = data[i];
      dropdown.add(option);
	}
	}
  }
  xmlhttp.open("GET","get_towns.php?county="+val,true);
  xmlhttp.send();
}

//SYNTAX: tabdropdown.init("menu_id", [integer OR "auto"])
tabdropdown.init("bluecompasstab", 4);

</script>

</body>
</html>