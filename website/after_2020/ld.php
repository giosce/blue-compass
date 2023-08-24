<?php

// leg_dist data home page

include("../db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$town = $_GET["town"];
$ld_id = $_GET["ld"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];


$sql_election_results = 
"select c.ld, a.year, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, 
sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast,
sum(ballots_cast)/sum(registered_voters) as turnout_pct,
sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes)/sum(ballots_cast) as rep_pct,
dem_candidate, rep_candidate, a.district 
from state_election_results a
join municipal_list c on a.muni_code = c.muni_code
left join state_ballots_cast b on b.muni_code = a.muni_code and b.year = a.year
where c.ld='".$ld_id."'";
if($ld_id == "28" || $ld_id == "29" || $ld_id == "31" || $ld_id == "33") {
	// 28 & 29 have Newark in common, 31 & 33 have Jersey City in common
	// eventually we should get results by precincts and know which precincts are in which district
	$sql_election_results = $sql_election_results." and (a.district is null OR a.district = 'LD".$ld_id."')";
}
$sql_election_results = $sql_election_results. 
" and office in ('NJ Senate','Assembly 1','Assembly 2','Governor', 'US House', 'President')
and (b.district is null or b.district = 'LD".$ld_id."')
group by a.year, ld, office 
order by a.year desc, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";

#Removing the join to ballots_cast from query above since it retrieve too many tuples
$sql_election_results = 
"select c.ld, a.year, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, 
dem_candidate, rep_candidate, a.district 
from state_election_results a
join municipal_list c on a.muni_code = c.muni_code
where c.ld='".$ld_id."'";
if($ld_id == "28" || $ld_id == "29" || $ld_id == "31" || $ld_id == "33") {
	// 28 & 29 have Newark in common, 31 & 33 have Jersey City in common
	// eventually we should get results by precincts and know which precincts are in which district
	$sql_election_results = $sql_election_results." and (a.district is null OR a.district = 'LD".$ld_id."')";
}
$sql_election_results = $sql_election_results. 
" group by a.year, ld, office 
order by a.year desc, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";


$query_election_results = mysqli_query($conn, $sql_election_results);

$sql5 = "select county, town, muni_code, cd from municipal_list where ld=".$ld_id." order by 1, 2";

$query5 = mysqli_query($conn, $sql5);

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from state_monthly_voter_registrations
where district='LD".$ld_id."' and publish='Y' 
order by year, month";

$registrations_query = mysqli_query($conn, $registrations_sql);

$party_turnout_sql = "select * from party_turnout_by_muni_2018_view where ld=".$ld_id;

//$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

$results_by_county_sql = "
select a.year, district, a.county, office, 
sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast, sum(ballots_cast)/sum(registered_voters) as turnout_pct,
sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes,
 sum(dem_votes)/sum(ballots_cast) as dem_pct, sum(rep_votes)/sum(ballots_cast) as rep_pct
from state_election_results a
join municipal_list_new b on b.county = a.county and b.muni_code = a.muni_code
join state_ballots_cast c on c.county = a.county and c.muni_code = a.muni_code
and c.year = a.year
where ld = '".$ld_id."'
group by a.year, district, a.county, office
order by a.year desc, district, a.county, office";

//$results_by_county_query = mysqli_query($conn, $results_by_county_sql);


#$sql_repr = "select * from representatives where ld='".$ld_id."' order by office desc, name";
#$representatives_query = mysqli_query($conn, $sql_repr);

$ballots_cast_sql = "
select year, sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast
from state_ballots_cast a, municipal_list b
where b.ld='".$ld_id."' and a.muni_code = b.muni_code
group by year
order by year desc";

$ballots_cast_query = mysqli_query($conn, $ballots_cast_sql);

$bc = array();
while($bc_row = mysqli_fetch_array($ballots_cast_query)) {
	$bc[$bc_row["year"]] = array($bc_row["registered_voters"], $bc_row["ballots_cast"]);
}

#print_r($bc);

$candidates_sql = "
select * from candidates where ld='".$ld_id."' and election_type='GEN' and election_year='2023'
order by office desc, party, name";

$candidates_query = mysqli_query($conn, $candidates_sql);

if($debug=="y") { // debug
	echo $sql5."<br>";
	echo $sql_election_results."<br><br>";
	echo $results_by_county_sql."<br><br>";
	echo $sql_repr."<br><br>";
	echo $registrations_sql."<br><br>";
}

?>

<html>
  <head>
    <title>NJ LD <?php echo $ld_id; ?> Electoral Data</title>
	<script src="../../sorttable.js"></script>
	<script src="../../Chart.bundle.min.js"></script> <!-- chartjs.org -->
	<script src="../../chartjs-plugin-datalabels.min.js"></script>	
	<script src="../../jquery-3.3.1.min.js"></script>
	<script src="../../Blob.min.js"></script>
	<script src="../../xls.core.min.js"></script>	
	<script src="../../FileSaver.min.js"></script>
	<script src="../../tableexport.min.js"></script>

	<link href="../../tableexport.css" type="text/css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="../../bluecompass.css"> </head>	
	
	<link rel="stylesheet" type="text/css" href="../../dropdowntabfiles/ddcolortabs.css" />
	<script type="text/javascript" src="../../dropdowntabfiles/dropdowntabs.js"></script>
	
  </head>

<body>
<!--
<a href="../../index.html">Home</a>
<a href="../../njcongress.php">NJ Congress</a>
-->

<?php

include("../../menu.html");

$prev_ld = $ld_id - 1;
if(strlen($prev_ld) < 2) {
	$prev_ld = "0".$prev_ld;
}
$next_ld = $ld_id + 1;
if(strlen($next_ld) < 2) {
	$next_ld = "0".$next_ld;
}

echo "<CENTER>";
echo "<H2>";
if($prev_ld != "00") {
	//echo "<a href='?elections_district_flag=".$ld_id."&ld=".$prev_ld."'><</a>";
	echo "<a href='?ld=".$prev_ld."'><</a>";
}
echo " NJ LD ".$ld_id." Data ";
if($next_ld != "41") {
	echo "<a href='?ld=".$next_ld."'>></a>";
}
echo "</H2>";
echo "</CENTER>";


$towncount=mysqli_num_rows($query5);
?>
<BR>
The New Jersey Legislative District <?php echo $ld_id; ?> is made of <?php echo $towncount; ?> municipalities:
<BR>
<?php
$cty = "";
$cds = [];

while($row5 = mysqli_fetch_array($query5)) {
	if($debug=="y") {
		print_r($row5);
	}
	$county = $row5["county"];
	if($county != $cty) {
		echo "<br><b>".$county.":</b> ";
		$cty = $county;
	}
	echo "<a href='muni.php?muni_id=".$row5["muni_code"]."'>".$row5["town"]."</a> ";
	array_push($cds, $row5["cd"]);
}
$cds2 = array_values(array_unique($cds));
//print_r($cds2);
echo "<br><br>";
echo "The Legislative District <b>LD".$ld_id."</b> is part of ";
for($x=0; $x < count($cds2); $x++) {
	echo "<a href='cd.php?cd=".$cds2[$x]."'>".$cds2[$x]."</a> ";
}
echo "</b>Congressional District";

echo "<BR>";

if($representatives_query) {
	echo "<BR>";
	//echo "<hr width='70%'>";
	echo "<br>";

	echo "<table border='1'>";
	echo "<caption><b>State Legislators</b></caption>";
	echo "<tr>";
	echo "<th>Office</th>";
	echo "<th>Term</th>";
	echo "<th>Name</th>";
	echo "<th>Party</th>";
	echo "<th>First Elected</th>";
	echo "<th>Expire On</th>";
	echo "<th>Address</th>";
	echo "<th>Email</th>";
	echo "<th>Official Website</th>";
	echo "<th>Vote Smart</th>";
	echo "</tr>";

	while($rep = mysqli_fetch_array($representatives_query)) {
		echo "<tr>";
		echo "<td>".$rep["office"]."</td> ";
		echo "<td>".$rep["term"]."</td> ";
		echo "<td>".$rep["name"]."</td> ";
		echo "<td>".$rep["party"]."</td> ";
		echo "<td>".$rep["first_elected"]."</td> ";	
		echo "<td>".$rep["expire_on"]."</td> ";
		echo "<td>";
		if(!empty($rep["address"])) {
			echo $rep["address"].", ".$rep["town"]." - ".$rep["zip"]." ".$rep["state"];
		}
		echo "</td>";
		echo "<td>";
		$email = $rep["email"];
		if(!empty($email)) {
			echo "<a href='mailto:".$email."'>".$email."</a>";
		}
		echo "</td>";
		echo "<td>";
		$website = $rep["website"];
		if(!empty($website)) {
			echo "<a target='_blank' href='".$website."'>website</a>";
		}
		echo "</td>";
		echo "<td>";
		$vote_smart = $rep["votesmart"];
		if(!empty($vote_smart)) {
			echo "<a target='_blank' href='".$vote_smart."'>Vote Smart</a>";
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
echo "<br>";
echo "<hr width='70%'>";

echo "<center><H2 title='See your candidates'><a href='candidates.php?district=".$ld_id."'>Election Coming Up!!</a></H2></center>";

if(mysqli_num_rows($candidates_query) > 0) {
	echo "<table border='1'>";
	echo "<caption><b>2023 Election Candidates</b></caption>";
	echo "<tr>";
	echo "<th>Office</th><th>Name</th><th>Incumbent</th><th>First Elected</th>";
	echo "<th>Party</th><th>Slogan</th>";
	echo "<th>Address</th><th>Email</th><th>Website</th><th>Social Media</th>";
	echo "</tr>";
	
	while($row = mysqli_fetch_array($candidates_query)) {
		echo "<tr>";
		$address = $row["address"]." ".$row["town"]. " NJ ".$row["zip"];
		echo "<td>".$row["office"]."</td>";
		echo "<td>".$row["name"]."</td>";
		echo "<td>".$row["incumbent"]."</td>";
		echo "<td>".$row["first_elected"]."</td>";
		echo "<td>".$row["party"]."</td>";
		echo "<td>".$row["slogan"]."</td>";
		echo "<td>".$address."</td>";
		echo "<td>".$row["email"]."</td>";
		echo "<td>".$row["website"]."</td>";
		echo "<td>".$row["facebook"]."</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}

echo "<br>";

$reg_labels = array();
$reg_values = array();

$col = mysqli_num_fields($registrations_query);

echo "<table border='1'>";
echo "<caption><b>Voter Registrations</b></caption>";

$aff = Array("Dem", "Rep", "Una", "Oth");

$y = 0;
$tot = Array();
$vals = Array();

$dem_margin = Array();

$num_rows = mysqli_num_rows($registrations_query);
//echo $num_rows;

while($row = mysqli_fetch_array($registrations_query)) {
	//print_r($row);
	//echo "<br>";
	if($y == 0) {
		echo "<tr>";
		echo "<th>Affiliation</th>";
	}
	$dt = $row["dt_label"];
	echo "<th>".$dt."</th>";
	$reg_labels[] = $dt;
	if($y < $num_rows-1) {
		echo "<th>Change</th>";
	}
	$vals[$y++] = Array($row["dem"], $row["rep"], $row["una"], $row["oth"]);
}
echo "</tr>";

$v = 0;

for($p=0;  $p<count($aff);$p++) {
	echo "<tr>";
	$t = 0;
	echo "<td>".$aff[$p]."</td>";
	for($x=0; $x<count($vals); $x++) {
		if($x > 0) {
			// dem, rep, una, oth monthly change
			$v = ($vals[$x][$p]-$vals[$x-1][$p]);
			if($v > 0) {
				echo "<td align=right>+".number_format($v, 0, ".", ",")."</td>";
			} else {
				echo "<td align=right>".number_format($v, 0, ".", ",")."</td>";
			}
			if($p == 1) {
				$dem_margin[] = ($vals[$x][$p-1]-$vals[$x-1][$p-1]) - $v;
			}
		}
		// dem, rep, una, oth monthly values
		$v = $vals[$x][$p];
		echo "<td align=right>".number_format($v, 0, ".", ",")."</td>";
		if($p == 1) {
			$dem_margin[] = ($vals[$x][$p-1]-$vals[$x][$p]);
		}

		$last_values[$aff[$p]] = $v;

		$tot[$t] = $tot[$t] + $v;
		$t++;
		
		if($aff[$p] != "Oth") {
			$reg_values[] = array("Month"=>$reg_labels[$x], $aff[$p]=>$vals[$x][$p]);
		}
		
	}
	echo "</tr>";
}

//print_r($reg_values); // these are correct

echo "<tr style='font-weight:bold;'>";
echo "<td>Total</td>";
$i = 0;
echo "<td align=right>".number_format($tot[$i], 0, ".", ",")."</td>";
for($i = 1; $i < $num_rows; $i++) {
	$ch = $tot[$i] - $tot[$i-1];
	if($ch > 0) {
		echo "<td align=right>+".number_format($ch, 0, ".", ",")."</td>";
	} else {
		echo "<td align=right>".number_format($ch, 0, ".", ",")."</td>";
	}
	echo "<td align=right>".number_format($tot[$i], 0, ".", ",")."</td>";
}
echo "</tr>";

echo "<tr>";
echo "<td>Margin</td>";
for($i = 0; $i < count($dem_margin); $i++) {
	if($dem_margin[$i] > 0) {
		echo "<td align=right style='color:blue;'>";
	} else {
		echo "<td align=right style='color:red;'>";
	}
	echo number_format(abs($dem_margin[$i]), 0, ".", ",")."</td>";
}
echo "</tr>";

echo "</table>";
echo "<i>Source: NJ State Voter Registrations By Legislative District</i>";

//echo "<br>";
//print_r($reg_labels);
//echo "<br>";
//print_r($reg_values);
//echo "<br><hr><br>";

$reg_values3 = array();

$t = count($reg_labels); // number of months for which there is registrations data 
//echo "<br>t: ".$t;

for ($r=0; $r < $t; $r++) {
	$rr = $t+$r;
	$rrr = ($t*2)+$r;
	$reg_values3[] = array("Month"=>$reg_values[$r]["Month"], "Dem"=>$reg_values[$r]["Dem"], "Rep"=>$reg_values[$rr]["Rep"], "Una"=>$reg_values[$rrr]["Una"]);
}

$last_values2 = array();
$last_values2[] = $last_values;

//echo "<br>";
//print_r($reg_values3);
//echo "<br>";

$RegsStats = json_encode($reg_values3);

//echo "<br>$RegsStats<br>";

$LastRegsStats = json_encode($last_values2);
//echo "<br>$LastRegsStats<br>";

?>

<BR>
<BR>
<table border="0">
<tr>
<td valign="top">
<canvas id="regChart" width="400" height="300"></canvas>
</td>
<td width="50"></td>
<td valign="top">
<canvas id="lastRegChart" width="260" height="260"></canvas>
</td>
</tr>
</table>
<br>
<br>
<hr width='70%'>
<br>
<a id="elections">
<?php
$elections_data = mysqli_num_rows($query_election_results) > 0;

if($elections_data) { // display only if there is data
	if($num_towns != $num_towns2) {
		echo "<p style='color:red; font-weight:bold;'>";
		echo "Attention, historical elections results below are not complete (only ".$num_towns2." towns data loaded)!";
		echo "</p>";
		//echo $num_towns." <> ".$num_towns2;
		//echo "<br>";
	}
	
	//echo "<div>";
	//	echo "<input type='checkbox' id='elections' name='elections_district_flag' onchange=\"hide_rows('election_result_table', this);\">";
	//	echo "<label for='elections'>Display only District Elections</label>";
	//echo "</div>";
	
	
	echo "<table border='1' class='sortable' name='election_result_table' id='election_result_table'>";
	echo "<caption><b>Previous Elections Results</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>Year</th><th>Election</th>";
	echo "<th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
	echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th>";
	echo "<th>Dem Candidate</th><th>Rep Candidate</th><th>Margin</th><th>Margin %</th>";
	echo "</tr>";

	$labels = array();
	$values = array();
	$i = 0;
	
	while($row = mysqli_fetch_array($query_election_results)) {
		if(($row["year"] % 2) == 1) {
			echo "<tr bgcolor='#ffffcc'>";
		} else {
			echo "<tr>";
		}
		echo "<td>".$row["year"]."</td>";
		echo "<td>".$row["office"]."</td>";
		$registered_voters = $bc[$row["year"]][0];
		$ballots_cast = $bc[$row["year"]][1];
		$turnout_pct = $ballots_cast / $registered_voters * 100;
		echo "<td align=right>".number_format($registered_voters, 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($ballots_cast, 0, ".", ",")."</td>";
		//echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
		//echo "<td align=right>".number_format($row["ballots_cast"], 0, ".", ",")."</td>";
		//echo "<td align=right>".number_format($row["turnout_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right>".number_format($turnout_pct, 2, ".", ",")." %</td>";
		if($row["dem_votes"] != 0) {
			echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
			$dem_pct = $row["dem_votes"] / $ballots_cast * 100;
			echo "<td align=right>".number_format($dem_pct, 2, ".", ",")." %</td>";
		} else {
			echo "<td></td><td></td>";
		}
		if($row["rep_votes"] != 0) {
			echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
			$rep_pct = $row["rep_votes"] / $ballots_cast * 100;
			echo "<td align=right>".number_format($rep_pct, 2, ".", ",")." %</td>";
		} else {
			echo "<td></td><td></td>";
		}
		if(strpos($row["district"], "CD") === false) {
			echo "<td>".$row["dem_candidate"]."</td>";
			echo "<td>".$row["rep_candidate"]."</td>";
		} else {
			echo "<td></td>";
			echo "<td></td>";
		}
		if($row["dem_votes"] > 0 && $row["rep_votes"] > 0) {
			$margin = $row["dem_votes"] - $row["rep_votes"]; // it's Dem margin
			echo "<td align=right>";	
			if($margin > 0) {
				echo  "<p><font color='blue'>";
			} else {
				echo  "<p><font color='red'>";
			}
			echo number_format(abs($margin), 0, ".", ",");
			echo "</td>";

			$margin_pct = $dem_pct - $rep_pct; // it's Dem margin
			echo "<td align=right>";	
			if($margin_pct > 0) {
				echo  "<p><font color='blue'>";
			} else {
				echo  "<p><font color='red'>";
			}
			echo number_format(abs($margin_pct), 2, ".", ",")."%";
			echo "</td>";			
		} else {
			echo "<td></td>";
			echo "<td></td>";
		}
		
		echo "</tr>";
		$label = $row["year"]." ".$row["office"];
		array_push($labels, $label);
		$values[] = array("Election"=>$label, "Registered_Voters"=>$registered_voters, "Voted"=>$ballots_cast,
		"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
	}
	//print_r($values);
	$SubsStats = json_encode(array_reverse($values));
	//echo "$SubsStats";

	echo "</table>";
	echo "<i>Source: NJ State Elections Results Reports</i>";
	echo "<BR>";
	echo "<BR>";
	echo "<canvas id='myChart' width='900' height='500'></canvas>";
	echo "<BR>";
}
?>
<BR>
<a id="towns">
<br>


<?php
/*
echo "<table border='1' class='sortable' id='election_countie_result_table'>";
echo "<caption><b>Previous Elections Results By County</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>County</th><th>Year</th><th>Election</th>";
echo "<th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th><th>Dem Margin</th>";
echo "</tr>";

$labels = array();
$values = array();
$i = 0;
while($row = mysqli_fetch_array($results_by_county_query)) {
	echo "<tr>";
	echo "<td>".$row["county"]."</td>";
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballots_cast"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["turnout_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["dem_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["rep_pct"]*100, 2, ".", ",")." %</td>";
	$dem_margin = $row["dem_votes"] - $row["rep_votes"];
	echo "<td align=right>".number_format($dem_margin, 0, ".", ",")."</td>";
	echo "</tr>";
	#$label = $row["year"]." ".$row["office"];
	#array_push($labels, $label);
	#$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballots_cast"],
	#"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
}
//print_r($values);
#$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";

echo "</table>";
echo "<i>Source: NJ State Elections Results Reports</i>";
echo "<BR>";
#echo "<BR>";
#echo "<canvas id='myChart' width='750' height='400'></canvas>";
echo "<BR>";
*/
?>

<script>

var data = <?php if($SubsStats) echo $SubsStats; else echo "null"; ?>;
//alert(data);

if(data != null) {
	
	var ctx = document.getElementById("myChart");
	//alert(ctx);

	var labels = data.map(function(e) {
	   return e.Election;
	});
	var data1 = data.map(function(e) {
	   return e.Registered_Voters;
	});
	var data2 = data.map(function(e) {
	   return e.Voted;
	});
	var data3 = data.map(function(e) {
	   return e.Dem_Votes;
	});
	var data4 = data.map(function(e) {
	   return e.Rep_Votes;
	});


	var config = {
	   type: 'line',
	  options: {
		title: {
		  display: true,
		  text: 'Past Elections Turnout and Results'
		},
		scales: {
		  xAxes: [{
			ticks: {
			  autoSkip: false,
			  maxRotation: 50,
              minRotation: 30
			}
		  }]
		},		
		responsive: false,
		animation: {
		  duration: 0
		},
		tooltips: {
		  callbacks: {
			label: function(tooltipItem, data) {
				var dataset = data.datasets[tooltipItem.datasetIndex];
				var value = dataset.data[tooltipItem.index];
				return data.datasets[tooltipItem.datasetIndex].label+": "+Number(value).toLocaleString();
			}
		  }
		},	
		plugins: {
			datalabels: {
				formatter: (value, ctx2) => {
					return "";
				}
			}
		}	
	  },
	  data: {
		  labels: labels,
		  datasets: [
			{
			 label: 'Registered',
			 data: data1,
			 borderColor: "#839192",
			 lineTension: 0,
			 fill: false
			},
			{
			 label: 'Turnout',
			 data: data2,
			 borderColor: "#27AE60",
			 lineTension: 0,
			 fill: false
			},
			{
			 label: 'Dem Votes',
			 data: data3,
			 borderColor: "#335DFF",
			 lineTension: 0,
			 fill: false
			},
			{
			 label: 'Rep Votes',
			 data: data4,
			 borderColor: "#FF0000",
			 lineTension: 0,
			 fill: false
			}
		  ]
	   }
	};

	var chart = new Chart(ctx, config);
}


var regData = <?php echo $RegsStats; ?>;

var ctx2 = document.getElementById("regChart");

var labels2 = regData.map(function(e) {
   return e.Month;
});

var reg_data1 = regData.map(function(e) {
   return e.Dem;
});
var reg_data2 = regData.map(function(e) {
   return e.Rep;
});
var reg_data3 = regData.map(function(e) {
   return e.Una;
});

var config2 = {
   type: 'line',
  options: {
    title: {
      display: true,
      text: 'Voter Registrations'
    },
	tooltips: {
      callbacks: {
        label: function(tooltipItem, data) {
			var dataset = data.datasets[tooltipItem.datasetIndex];
			var value = dataset.data[tooltipItem.index];
			return data.datasets[tooltipItem.datasetIndex].label+": "+Number(value).toLocaleString();
        }
      }
    },	
    responsive: false,
	animation: {
      duration: 0
    },
   plugins: {
        datalabels: {
            formatter: (value, ctx2) => {
                return "";
            }
        }
    }		
  },
  data: {
      labels: labels2,
      datasets: [
	    {
         label: 'Dem',
         data: reg_data1,
		 borderColor: "#335DFF",
		 lineTension: 0,
		 fill: false
        },
	    {
         label: 'Rep',
         data: reg_data2,
		 borderColor: "#FF0000",
		 lineTension: 0,
		 fill: false
        },
	    {
         label: 'Una',
         data: reg_data3,
		 borderColor: "#839192",
		 lineTension: 0,
		 fill: false
        }
	  ]
   }
};

var reg_chart = new Chart(ctx2, config2);

//alert(labels2[labels2.length-1]);

var lastRegData = <?php echo $LastRegsStats; ?>;

var ctx3 = document.getElementById("lastRegChart");

var reg_dataD = lastRegData.map(function(e) {
   return e.Dem;
});
var reg_dataR = lastRegData.map(function(e) {
   return e.Rep;
});
var reg_dataU = lastRegData.map(function(e) {
   return e.Una;
});

// search for the datalabels plugin
var datalabels = Chart.plugins.getAll().filter(function(p) {
  return p.id === 'datalabels';
})[0];

// globally unregister the plugin
Chart.plugins.unregister(datalabels);

var config3 = {
  type: 'pie',
  options: {
    title: {
      display: true,
      text: 'Voter Registrations ' + labels2[labels2.length-1]
    },
	responsive: false,
	animation: {
      duration: 0
    },
	tooltips: {
      callbacks: {
        label: function(tooltipItem, data) {
			var dataset = data.datasets[tooltipItem.datasetIndex];
			var value = dataset.data[tooltipItem.index];
			//return data.datasets[tooltipItem.datasetIndex].label+": "+Number(value).toLocaleString();
			return Number(value).toLocaleString();
        }
      }
    },		
    plugins: {
        datalabels: {
            formatter: (value, ctx3) => {
                let sum = 0;
                let dataArr = ctx3.chart.data.datasets[0].data;
                dataArr.map(data => {
                    sum += parseInt(data);
                });
                let percentage = (value*100 / sum).toFixed(2)+"%";
                return percentage;
            },
            color: '#fff',
        }
    }	
  },
  data: {
      labels: ['Dem', 'Rep', 'Una'],
      datasets: [{
         data: [reg_dataD, reg_dataR, reg_dataU],
		 backgroundColor: ["#335DFF","#FF0000", "#839192"]
        }]
   },
  plugins: [
    datalabels  //< this will add the plugin locally
  ]   
};

var last_reg_chart = new Chart(ctx3, config3);

// checkbox true, hide rows
// would be great to remove data from the chart too
// and to include the flag into the query for next page
function hide_rows(table_id, checkbox) {
	table = document.getElementById(table_id);
	//console.log(checkbox.checked);
	for (var i = 1; i < table.rows.length; i++) {
		let row = table.rows[i];
		//console.log(row);
		if(typeof row === "undefined") {
			continue;
		}
		let cell = row.cells[1].innerHTML;
		if(cell == "Assembly 1" || cell == "Assembly 2" || cell == "NJ Senate") {
			continue;
		}
		if(checkbox.checked == true) {
			row.style.display="none";
		} else {
			row.style.display="";
		}
	}
	
	console.log(chart.data);
	
	//if(checkBox.checked == false) {
	//	virusData.datasets[dsId].hidden = true;
		//lazioData.datasets[dsId].hidden = true;
	//var a = 0;
	//chart.data.datasets.forEach(function(e) {
	//	if(a == 1 || a == 3 || a == 5) {
	//		console.log("hiding " + e.data.labels);
	//		e.hidden = true; // this remove like all registered voters or all Dems
			// instead I need to remove the non Assembly Elections
	//	}
	//	a++;
	//});
	//chart.update();

	chart.data.labels.forEach(function(el, index) {
		if(el.indexOf("President") >= 0 || el.indexOf("US House") >= 0 || el.indexOf("US Senate") >= 0) {
			chart.data.labels.splice(index, 1);
			chart.data.datasets[0].data.splice(index, 1);
			chart.data.datasets[1].data.splice(index, 1);
			chart.data.datasets[2].data.splice(index, 1);
			chart.data.datasets[3].data.splice(index, 1);
		}
	});
	chart.update();
}

tabdropdown.init("bluecompasstab", 2);

</script>

  </body>
</html>
<?php
ob_end_flush();
?>