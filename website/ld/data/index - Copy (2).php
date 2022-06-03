<?php

// leg_dist data home page

include("../../db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$town = $_GET["town"];
$ld_id = $_GET["ld"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];


$properties_a = parse_ini_file("../../properties-a.ini");

$host_a = $properties_a["host"];
$username_a = $properties_a["username"];
$password_a = $properties_a["password"];
$db_name_a = $properties_a["db_name"];

$conn_a = mysqli_connect($host_a, $username_a, $password_a, $db_name_a);
if (!$conn_a) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}


// divide by 2 for Ass% on vote_pct
$sql = "SELECT election_year, election_type_code, sum(registered_voters) as registered_voters,
if(election_type_code like 'Ass%', sum(ballots_cast)/2, sum(ballots_cast)) as ballots_cast, 
if(election_type_code like 'Ass%', (sum(ballots_cast)/sum(registered_voters)/2), (ballots_cast/registered_voters)) 
as turnout, 
sum(dem_votes) as dem_votes, sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes) as rep_votes, sum(rep_votes)/sum(ballots_cast) as rep_pct
from turnout_precincts_tbl a, municipal_list b
where b.ssn = substr(precinct_id, 1, 4)
and election_type_code not like 'Town%'  and election_type_code not like 'Mayor%'
and ld = $ld_id
and election_year > '2013'
group by election_year, election_type_code
order by election_year desc, election_type_code";

$query = mysqli_query($conn, $sql);


// by town

// divide by 2 for Ass% on vote_pct
$sql2 = "SELECT town, a.election_year, a.election_type_code, sum(registered_voters) as registered_voters, 
if(a.election_type_code like 'Ass%', sum(ballots_cast)/2, sum(ballots_cast)) as ballots_cast, 
if(a.election_type_code like 'Ass%', (sum(ballots_cast)/sum(registered_voters)/2), (ballots_cast/registered_voters)) 
as turnout,
sum(dem_votes) as dem_votes, sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes) as rep_votes, sum(rep_votes)/sum(ballots_cast) as rep_pct,
dem_candidate, rep_candidate
from turnout_precincts_tbl a
join municipal_list b on b.ssn = substr(precinct_id, 1, 4)
left join candidates c on c.election_year = a.election_year
and c.ld = b.ld and c.cd = b.cd
and c.election_type_code = a.election_type_code
where a.election_type_code not like 'Town%' and a.election_type_code not like 'Mayor%'
and b.ld = ".$ld_id."
and a.election_year > '2013'";

if(!empty($town)) {
	$sql2 = $sql2." and town = '".$town."'";
}
if(!empty($year)) {
	$sql2 = $sql2." and a.election_year = '".$year."'";
}

if(!empty($el_type)) {
	$sql2 = $sql2." and a.election_type_code = '".$el_type."'";
}

$sql2 = $sql2." 
group by town, a.election_year, a.election_type_code
order by a.election_year desc, a.election_type_code, town";

//echo "$sql2";
//echo "<br>";

$query2 = mysqli_query($conn, $sql2);

$sql3 = "SELECT town from municipal_list where ld=".$ld_id." order by 1";

$query3 = mysqli_query($conn, $sql3);

//$sql4 = "select date_format(month_year, '%M %Y') as date, una, dem, rep, oth from monthly_registrations order by 1";

//$query4 = mysqli_query($conn, $sql4);

$sql5 = "select county, town, ssn from municipal_list where ld=".$ld_id." order by 1, 2";

//echo "$sql5";


$query5 = mysqli_query($conn_a, $sql5);

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from monthly_registrations
where ld='".$ld_id."' and publish='Y' 
order by `year_month`";

//echo "$registrations_sql";
//echo "<br>";

$registrations_query = mysqli_query($conn, $registrations_sql);


// party turnout
$party_turnout_sql = "select a.county, c.year, month as election_month, 
muni, ssn, ld, 
sum(dem) as registered_dem, voters_dem, voters_dem/sum(dem) as turnout_dem,
sum(rep) as registered_rep, voters_rep, voters_rep/sum(rep) as turnout_rep,
sum(una) as registered_una, voters_una, voters_una/sum(una) as turnout_una,
sum(cnv)+sum(con)+sum(gre)+sum(lib)+sum(nat)+sum(rfp)+sum(ssp) as registered_oth
from municipal_list a  
left join voters_by_muni_party b on b.muni_id = ssn
left join registered_voters_precinct_copy c on c.muni_id=ssn
where (year='2018' or year is null) and (month='10' or month is null)
and ld=".$ld_id." 
group by ssn
order by muni";

$party_turnout_sql = "
select a.county, c.year, month as election_month, 
muni, ssn, ld, cd,
sum(dem) as registered_dem, voters_dem, voters_dem/sum(dem) as turnout_dem,
sum(rep) as registered_rep, voters_rep, voters_rep/sum(rep) as turnout_rep,
sum(una) as registered_una, voters_una, voters_una/sum(una) as turnout_una,
sum(cnv)+sum(con)+sum(gre)+sum(lib)+sum(nat)+sum(rfp)+sum(ssp) as registered_oth
from municipal_list a  
left join voters_by_muni_party b on b.muni_id = ssn
left join registered_voters_precinct_copy c on c.muni_id=ssn
where 1=1
and year='2018'
and (month='10' or (c.county='Warren' and month='09')) 
and ld=".$ld_id."
group by ssn
order by muni";

//echo "$party_turnout_sql";

//$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

// query for munis total registered and votes since can't get it right with the large join
$votes_by_town_sql = "select muni, ssn, sum(registered_voters) as registered_total, 
sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes
from turnout_precincts_tbl a
join municipal_list b on ssn=substr(a.precinct_id, 1, 4)
where ld=".$ld_id." 
and election_type_code='Con' and a.election_year='2018'
group by ssn
order by muni";

$votes_by_town_query = mysqli_query($conn, $votes_by_town_sql);
// better put votes by town in a map by muni_id

$party_turnout_sql = "select * from party_turnout_by_muni_2018_view where ld=".$ld_id;

$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

$sql_to_check_town_data =
"select distinct town
from turnout_precincts_tbl a, municipal_list b
where b.ssn = substr(precinct_id, 1, 4)
and ld='".$ld_id."'";

$query_to_check_town_data = mysqli_query($conn, $sql_to_check_town_data);
$num_towns2 = mysqli_num_rows($query_to_check_town_data);
$num_towns = mysqli_num_rows($query5);

if($debug=="y") { // debug
	echo $sql5."<br>";
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

	<link href="../../tableexport.css" rel="stylesheet">
	
	<style type="text/css">	
		table.sortable thead {
			background-color:#eee;
			color:#666666;
			font-weight: bold;
			cursor: default;
		}

		/* Style the tab */
		.tab {
			overflow: hidden;
			border: 1px solid #ccc;
			background-color: #f1f1f1;
			width: 944px;
		}

		/* Style the buttons that are used to open the tab content */
		.tab button {
			background-color: inherit;
			float: left;
			border: none;
			outline: none;
			cursor: pointer;
			padding: 14px 16px;
			transition: 0.3s;
		}

		/* Change background color of buttons on hover */
		.tab button:hover {
			background-color: #ddd;
		}

		/* Create an active/current tablink class */
		.tab button.active {
			background-color: #ccc;
		}

		/* Style the tab content */
		.tabcontent {
			display: none;
			padding: 6px 12px;
			border: 1px solid #ccc;
			border-top: none;
			width: 920px;
		}
		
	</style>
  </head>
<body>
<a href="../../index.html">Home</a>
<a href="../../njcongress.html">NJ Congress</a>
<a href="../ld<?php echo $ld_id; ?>.php">District</a>

<?php
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
The New Jersey Legislative District <?php echo $ld_id; ?> is made of <?php echo $towncount; ?> <a href="#towns">municipalities</a>.
<BR>
<?php
$cty = "";
while($row5 = mysqli_fetch_array($query5)) {
	if($debug=="y") {
		print_r($row5);
	}
	$county = $row5["county"];
	if($county != $cty) {
		echo "<br><b>".$county.":</b> ";
		$cty = $county;
	}
	echo "<a href='muni.php?muni_id=".$row5["ssn"]."'>".$row5["town"]."</a> ";
}
?>

<BR>
<BR>

<?php

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

echo "<tr style='color:blue;'>";
echo "<td>Dem Margin</td>";
for($i = 0; $i < count($dem_margin); $i++) {
	echo "<td align=right>".number_format($dem_margin[$i], 0, ".", ",")."</td>";
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
<?php
$elections_data = mysqli_num_rows($query) > 0;

if($elections_data) { // display only if there is data
	if($num_towns != $num_towns2) {
		echo "<p style='color:red; font-weight:bold;'>";
		echo "Attention, historical elections results below are not complete (only ".$num_towns2." towns data loaded)!";
		echo "</p>";
		//echo $num_towns." <> ".$num_towns2;
		//echo "<br>";
	}

	echo "<table border='1' class='sortable' id='election_result_table'>";
	echo "<caption><b>Previous Elections Results</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
	echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th><th>Dem Margin</th>";
	echo "</tr>";

	$labels = array();
	$values = array();
	$i = 0;
	while($row = mysqli_fetch_array($query)) {
		echo "<tr>";
		echo "<td>".$row["election_year"]."</td>";
		echo "<td>".$row["election_type_code"]."</td>";
		echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["ballots_cast"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["turnout"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["dem_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["rep_pct"]*100, 2, ".", ",")." %</td>";
		$dem_margin = $row["dem_votes"] - $row["rep_votes"];
		echo "<td align=right>".number_format($dem_margin, 0, ".", ",")."</td>";
		echo "</tr>";
		$label = $row["election_year"]." ".$row["election_type_code"];
		array_push($labels, $label);
		$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballots_cast"],
		"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
	}
	//print_r($values);
	$SubsStats = json_encode(array_reverse($values));
	//echo "$SubsStats";

	echo "</table>";
	echo "<i>Source: Counties Elections Results Report</i>";
	echo "<BR>";
	echo "<BR>";
	echo "<canvas id='myChart' width='750' height='400'></canvas>";
	echo "<BR>";
}
?>
<BR>
<a id="towns">
<br>

<?php

$turnout_data = mysqli_num_rows($party_turnout_query);
if($turnout_data) {
	
echo "<table border='1' class='sortable' id='election_result_table'>";
echo "<caption><b>Party Turnout General Elections 2018 (Congress)</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>Municipality</th>";
echo "<th>Registered Dem</th><th>Dem Voters</th><th>Dem Turnout</th>";
echo "<th>Registered Rep</th><th>Rep Voters</th><th>Rep Turnout</th>";
echo "<th>Registered Una</th><th>Una Voters</th><th>Una Turnout</th>";
echo "<th>Other Registered</th><th>Total Registered</th>";
echo "<th>Dem Votes</th><th>Rep Votes</th><th>Dem Margin</th>";
echo "</tr>";



$elec_results = array();
while($row4 = mysqli_fetch_array($votes_by_town_query)) {
	$elec_results[$row4["ssn"]] = array("registered_total"=>$row4["registered_total"], "dem_votes"=>$row4["dem_votes"], "rep_votes"=>$row4["rep_votes"]);
}
//print_r($elec_results);

$turnout_total = array();
while($row = mysqli_fetch_array($party_turnout_query)) {
	
	//$row2 = mysqli_fetch_assoc($votes_by_town_query);
	$row2 = $elec_results[$row["muni_id"]];
	
	echo "<tr>";
	echo "<td style='white-space:nowrap;'><a href='muni.php?muni_id=".$row["muni_id"]."'>".$row["town"]."</a></td>";
	if($row["reg_dem"] > 0) {
		echo "<td align=right>".number_format($row["reg_dem"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["dem_voters"] >0) {
		echo "<td align=right>".number_format($row["dem_voters"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["turnout_dem"] > 0) {
		echo "<td align=right>".number_format($row["turnout_dem"]*100, 2, ".", ",")." %</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["reg_rep"] > 0) {
		echo "<td align=right>".number_format($row["reg_rep"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["rep_voters"] > 0) {
		echo "<td align=right>".number_format($row["rep_voters"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["turnout_rep"] > 0) {
		echo "<td align=right>".number_format($row["turnout_rep"]*100, 2, ".", ",")." %</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["reg_una"] > 0) {
		echo "<td align=right>".number_format($row["reg_una"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["una_voters"] > 0) {
		echo "<td align=right>".number_format($row["una_voters"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["turnout_una"] > 0) {
		echo "<td align=right>".number_format($row["turnout_una"]*100, 2, ".", ",")." %</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row["reg_oth"] > 0) {
		echo "<td align=right>".number_format($row["reg_oth"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}		
	if($row2["registered_total"] > 0) {
		echo "<td align=right>".number_format($row2["registered_total"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row2["dem_votes"] > 0) {
		echo "<td align=right>".number_format($row2["dem_votes"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	if($row2["rep_votes"] > 0) {
		echo "<td align=right>".number_format($row2["rep_votes"], 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}

	$dem_margin = $row2["dem_votes"] - $row2["rep_votes"];
	if($dem_margin != 0) {
		echo "<td align=right>".number_format($dem_margin, 0, ".", ",")."</td>";
	} else {
		echo "<td align=right></td>";
	}
	echo "</tr>";
	$label = ""; //$row["election_year"]." ".$row["election_type_code"];
	$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballots_cast"],
	"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);

	$turnout_total["registered_dem"]+=$row["reg_dem"];
	$turnout_total["voters_dem"]+=$row["dem_voters"];
	$turnout_total["registered_rep"]+=$row["reg_rep"];
	$turnout_total["voters_rep"]+=$row["rep_voters"];
	$turnout_total["registered_una"]+=$row["reg_una"];
	$turnout_total["voters_una"]+=$row["una_voters"];
	$turnout_total["registered_oth"]+=$row["reg_oth"];
	$turnout_total["registered_total"]+=$row2["registered_total"];
	$turnout_total["dem_votes"]+=$row2["dem_votes"];
	$turnout_total["rep_votes"]+=$row2["rep_votes"];	
	$turnout_total["dem_margin"]+=$dem_margin;
}
echo "<tfoot>";
echo "<tr style='font-weight:bold;'>";
echo "<td>Total</td>";
echo "<td align=right>".number_format($turnout_total["registered_dem"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_dem"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_dem"]/$turnout_total["registered_dem"]*100, 2, ".", ",")." %</td>";
echo "<td align=right>".number_format($turnout_total["registered_rep"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_rep"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_rep"]/$turnout_total["registered_rep"]*100, 2, ".", ",")." %</td>";
echo "<td align=right>".number_format($turnout_total["registered_una"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_una"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["voters_una"]/$turnout_total["registered_una"]*100, 2, ".", ",")." %</td>";
echo "<td align=right>".number_format($turnout_total["registered_oth"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["registered_total"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["dem_votes"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["rep_votes"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["dem_margin"], 0, ".", ",")."</td>";
echo "</tr>";
echo "</tfoot>";

//print_r($values);
//$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";

echo "</table>";
echo "<i>Source: NJ State Voters' File, Counties Voter Registrations Report and Elections Results Report</i>";
echo "<br>";

} // end of Party Turnout data


$muni_elec_data = mysqli_num_rows($query2);

if($muni_elec_data) {

	echo "<br>";
	echo "<br>";
	echo "<table>";
	echo "<tr valign='top'>";
	echo "<td>";
	echo "<table border='1' class='sortable' id='election_result_table'>";
	echo "<caption><b>Previous Elections Results Per Municipality</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>Town</th><th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
	echo "<th>Dem Candidate</th><th>Dem Votes</th><th>Dem Votes %</th><th>Rep Candidate</th><th>Rep Votes</th><th>Rep Votes %</th>";
	echo "<th>Dem Margin</th>";
	echo "</tr>";

	$labels = array();
	$values = array();
	$i = 0;
	while($row2 = mysqli_fetch_array($query2)) {
		echo "<tr>";
		echo "<td>".$row2["town"]."</td>";
		echo "<td>".$row2["election_year"]."</td>";
		echo "<td>".$row2["election_type_code"]."</td>";
		echo "<td align=right>".number_format($row2["registered_voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row2["ballots_cast"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row2["turnout"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right>".$row2["dem_candidate"]."</td>";
		echo "<td align=right>".number_format($row2["dem_votes"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row2["dem_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right>".$row2["rep_candidate"]."</td>";
		echo "<td align=right>".number_format($row2["rep_votes"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row2["rep_pct"]*100, 2, ".", ",")." %</td>";
		$dem_margin = $row2["dem_votes"] - $row2["rep_votes"];
		echo "<td align=right>".number_format($dem_margin, 0, ".", ",")."</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</td>";


	echo "<td valign='top'>";
	echo "<table border='0'>";
	echo "<form action='index.php#towns'>";
	echo "<input type='hidden' name='ld' value='".$ld_id."'>";
	echo "<tr><th>Select</th></tr>";
	echo "<tr>";
	echo "<td>Town</td>";
	echo "<td>";
	echo "<select name='town' onchange='this.form.submit();'>";
		if(empty($town)) {
			echo "<option selected='selected' value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		while($row3 = mysqli_fetch_array($query3)) {
			
			if($town==$row3["town"]) {
				echo "<option selected='selected' value='".$row3["town"]."'>".$row3["town"]."</option>";
			} else {
				echo "<option value='".$row3["town"]."'>".$row3["town"]."</option>";
			}
		}	
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>Year</td>";
	echo "<td>";
	echo "<select name='year' onchange='this.form.submit();'>";
		if(empty($year)) {
			echo "<option selected='selected' value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($year=="2018") {
			echo "<option selected='selected' value='2018'>2018</option>";
		} else {
			echo "<option value='2018'>2018</option>";
		}
		if($year=="2017") {
			echo "<option selected='selected' value='2017'>2017</option>";
		} else {
			echo "<option value='2017'>2017</option>";
		}
		if($year=="2016") {
			echo "<option selected='selected' value='2016'>2016</option>";
		} else {
			echo "<option value='2016'>2016</option>";
		}
		if($year=="2015") {
			echo "<option selected='selected' value='2015'>2015</option>";
		} else {
			echo "<option value='2015'>2015</option>";
		}
		if($year=="2014") {
			echo "<option selected='selected' value='2014'>2014</option>";
		} else {
			echo "<option value='2014'>2014</option>";
		}  

	echo "<tr>";
	echo "<td>Election Type</td>";
	echo "<td>";
	echo "<select name='el_type' onchange='this.form.submit();'>";
		if(empty($el_type)) {
			echo "<option selected='selected' value=''></option>";
		} else {
			echo "<option value=''></option>";
		}
		if($el_type=="Ass1") {
			echo "<option selected='selected' value='Ass1'>Ass1</option>";
		} else {
			echo "<option value='Ass1'>Ass1</option>";
		}
		if($el_type=="Ass2") {
			echo "<option selected='selected' value='Ass2'>Ass2</option>";
		} else {
			echo "<option value='Ass2'>Ass2</option>";
		}
		if($el_type=="Con") {
			echo "<option selected='selected' value='Con'>Con</option>";
		} else {
			echo "<option value='Con'>Con</option>";
		}
		if($el_type=="Gov") {
			echo "<option selected='selected' value='Gov'>Gov</option>";
		} else {
			echo "<option value='Gov'>Gov</option>";
		}
		if($el_type=="NJSen") {
			echo "<option selected='selected' value='NJSen'>NJSen</option>";
		} else {
			echo "<option value='NJSen'>NJSen</option>";
		}
		if($el_type=="Pre") {
			echo "<option selected='selected' value='Pre'>Pre</option>";
		} else {
			echo "<option value='Pre'>Pre</option>";
		}  

	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

} // end muni election data

?>

<BR>

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
		  text: 'Last Elections Turnout and Results'
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

</script>

  </body>
</html>
<?php
ob_end_flush();
?>