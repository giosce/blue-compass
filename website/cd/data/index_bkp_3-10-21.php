<?php

// leg_dist data home page

include("../../db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$town = $_GET["town"];
$cd_id = $_GET["cd"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];

$cd_id = str_replace("NJ","",$cd_id);

$properties_a = parse_ini_file("../../properties-a.ini");

$host_a = $properties_a["host"];
$username_a = $properties_a["username"];
$password_a = $properties_a["password"];
$db_name_a = $properties_a["db_name"];

$conn_a = mysqli_connect($host_a, $username_a, $password_a, $db_name_a);
if (!$conn_a) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}


$sql_prev_election_results =
"select a.election_year, a.election_type_code,  
sum(registered_voters) AS registered_voters,
sum(ballots_cast) AS ballots_cast,
sum(ballots_cast) / sum(registered_voters) AS turnout,
dem_candidate, sum(dem_votes) AS dem_votes, sum(dem_votes) / sum(ballots_cast) AS dem_pct,
rep_candidate, sum(rep_votes) AS rep_votes, sum(rep_votes) / sum(ballots_cast) AS rep_pct, 
sum(dem_votes) - sum(rep_votes) as dem_margin 
from turnout_precincts_tbl a join municipal_list b join candidates c 
where b.ssn = substr(a.precinct_id,1,4) 
and c.election_type_code = a.election_type_code 
and c.election_year = a.election_year
and c.cd = b.cd 
-- and c.ld = b.ld
and a.election_type_code in ('Pre', 'Con', 'Sen', 'Gov')
and c.cd=".$cd_id."
group by a.election_year, a.election_type_code
order by a.election_year desc, a.election_type_code";

//$query_prev_election_results = mysqli_query($conn, $sql_prev_election_results);
//$prev_election_result_count = mysqli_num_rows($query_prev_election_results);


$sql_election_results = 
"select cd, a.year, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, 
sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast,
sum(ballots_cast)/sum(registered_voters) as turnout_pct,
sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes)/sum(ballots_cast) as rep_pct,
dem_candidate, rep_candidate, a.district 
from state_election_results a
join municipal_list_new c on a.muni_code = c.muni_code
left join state_ballots_cast b on b.muni_code = a.muni_code and b.year = a.year
where cd='".$cd_id."'";
//if($ld_id == "28" || $ld_id == "29" || $ld_id == "31" || $ld_id == "33") {
	// 28 & 29 have Newark in common, 31 & 33 have Jersey City in common
	// eventually we should get results by precincts and know which precincts are in which district
//	$sql_election_results = $sql_election_results." and (a.district is null OR a.district = 'LD".$ld_id."')";
//}
$sql_election_results = $sql_election_results. 
" and office in ('NJ Senate','Assembly 1','Assembly 2','Governor', 'US House')
and (b.district is null or b.district = 'CD".$cd_id."')
group by a.year, cd, office 
order by a.year desc, ld, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', office))";

$query_election_results = mysqli_query($conn, $sql_election_results);


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
and ld = $cd_id
and election_year > '2013'
group by election_year, election_type_code
order by election_year desc, election_type_code";

//$query = mysqli_query($conn, $sql);


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
and b.ld = ".$cd_id."
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

//$query2 = mysqli_query($conn, $sql2);

//$sql3 = "SELECT town from municipal_list where cd=".$cd_id." order by 1";

//$query3 = mysqli_query($conn, $sql3);

$sql5 = "select county, town, ssn from municipal_list where cd=".$cd_id." order by 1, 2";


$query5 = mysqli_query($conn_a, $sql5);

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from monthly_registrations
where cd='".$cd_id."' and publish='Y' 
order by `year_month`";


$registrations_query = mysqli_query($conn, $registrations_sql);


/*
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
and cd=".$cd_id." 
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
and cd=".$cd_id."
group by ssn
order by muni";
*/

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

$party_turnout_sql = "select * from party_turnout_by_muni_2018_view where cd=x".$cd_id;

$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

$sql_to_check_town_data =
"select distinct town
from turnout_precincts_tbl a, municipal_list b
where b.ssn = substr(precinct_id, 1, 4)
and cd='".$cd_id."'";

$query_to_check_town_data = mysqli_query($conn, $sql_to_check_town_data);
$num_towns2 = mysqli_num_rows($query_to_check_town_data);
$num_towns = mysqli_num_rows($query5);

$sql_county_stats =
"select * 
from cd_county_stats 
where cd='NJ".$cd_id."' 
order by county";

$query_county_stats = mysqli_query($conn_a, $sql_county_stats);
$num_counties = mysqli_num_rows($query_county_stats);

if($debug=="y") { // debug
	echo $sql5."<br>";
	echo $sql_election_results."<br>";
	echo $sql_county_stats."<br>";
}

?>

<html>
  <head>
    <title>NJ CD <?php echo $cd_id; ?> Electoral Data</title>
	<script src="../../sorttable.js"></script>
	<script src="../../Chart.bundle.min.js"></script> <!-- chartjs.org -->
	<script src="../../chartjs-plugin-datalabels.min.js"></script>	
	<script src="../../jquery-3.3.1.min.js"></script>
	<script src="../../Blob.min.js"></script>
	<script src="../../xls.core.min.js"></script>	
	<script src="../../FileSaver.min.js"></script>
	<script src="../../tableexport.min.js"></script>

	<link href="../../tableexport.css" type="text/css" rel="stylesheet">
	<link href="../../bluecompass.css" type="text/css" rel="stylesheet">
	
	<style type="text/css">	
		
		H2 {
			display: inline;
		}
		
	</style>
	
	<link rel="stylesheet" type="text/css" href="../../dropdowntabfiles/ddcolortabs.css" />
	<script type="text/javascript" src="../../dropdowntabfiles/dropdowntabs.js"></script>
	
  </head>
<body style="font-family:Arial">

<?php include("../../menu.html"); ?>

<!--
<a href="../../index.html">Home</a>
<a href="../../congress.html">Congress</a>
<a href="../cd<?php echo $cd_id; ?>.php">District</a>
-->

<br>

<center>

<table border='0'>
<tr>
<td><a href='?cd=01'>NJ01</a></td>
<td><a href='?cd=02'>NJ02</a></td>
<td><a href='?cd=03'>NJ03</a></td>
<td><a href='?cd=04'>NJ04</a></td>
<td><a href='?cd=05'>NJ05</a></td>
<td><a href='?cd=06'>NJ06</a></td>
<td><a href='?cd=07'>NJ07</a></td>
<td><a href='?cd=08'>NJ08</a></td>
<td><a href='?cd=09'>NJ09</a></td>
<td><a href='?cd=10'>NJ10</a></td>
<td><a href='?cd=11'>NJ11</a></td>
<td><a href='?cd=12'>NJ12</a></td>
</tr>
</table>


<H2>NJ CD <?php echo $cd_id; ?> Data</H2>

</center>

<?php
$towncount=mysqli_num_rows($query5);
?>
<BR>
The New Jersey Congressional District <?php echo $cd_id; ?> is made of <?php echo $towncount; ?> municipalities 
across <?php echo $num_counties; ?> <a href="#counties">counties</a>.
<BR>
<?php
$cty = "";
while($row5 = mysqli_fetch_array($query5)) {
	$county = $row5["county"];
	if($county != $cty) {
		echo "<br><b>".$county.":</b> ";
		$cty = $county;
	}
	if($cd_id == "07") {
		echo "<a href='muni.php?muni_id=".$row5["ssn"]."'>".$row5["town"]."</a>, ";
	} else {
		echo $row5["town"].", ";
	}
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
			echo "<td align=right>".number_format($v, 0, ".", ",")."</td>";
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
	echo "<td align=right>".number_format($tot[$i] - $tot[$i-1], 0, ".", ",")."</td>";
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
echo "<i>Source: NJ State Voter Registrations By Congressional District</i>";

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

echo "<table border='1' class='sortable' id='election_result_table'>";
echo "<caption><b>Previous Elections Results</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th><th>Dem Margin</th>";
echo "</tr>";

$labels = array();
$values = array();
$i = 0;
while($row = mysqli_fetch_array($query_election_results)) {
	echo "<tr>";
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
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
	$label = $row["year"]." ".$row["office"];
	array_push($labels, $label);
	$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballots_cast"],
	"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
}
//print_r($values);
$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";
echo "</table>";
echo "<i>Source: State Elections Results Report</i>";
echo "<BR>";
echo "<BR>";
echo "<canvas id='myChart' width='750' height='400'></canvas>";
echo "<BR>";

?>

<!-- if I put chart in if ... data... javascript breaks
<canvas id='myChart' width='750' height='400'></canvas>
<BR>
<BR>
-->

<a id="towns">
<br>

<?php

if(mysqli_num_rows($party_turnout_query) > 0) {

echo "<table border='1' class='sortable' id='election_result_table'>";
echo "<caption><b>Party Turnout General Elections 2018 (Congress)</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>Municipality</th>";
echo "<th>Registered Dem</th><th>Dem Voters</th><th>Dem Turnout</th>";
echo "<th>Registered Rep</th><th>Rep Voters</th><th>Rep Turnout</th>";
echo "<th>Registered Una</th><th>Una Voters</th><th>Una Turnout</th>";
//echo "<th>Other Registered</th><th>Total Registered</th>";
//echo "<th>Dem Votes</th><th>Rep Votes</th><th>Dem Margin</th>";
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
	/*
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
	*/
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
/*
echo "<td align=right>".number_format($turnout_total["registered_oth"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["registered_total"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["dem_votes"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["rep_votes"], 0, ".", ",")."</td>";
echo "<td align=right>".number_format($turnout_total["dem_margin"], 0, ".", ",")."</td>";
*/
echo "</tr>";
echo "</tfoot>";

//print_r($values);
//$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";

echo "</table>";
echo "<i>Source: NJ State Voters' File, Counties Voter Registrations Report and Elections Results Report</i>";
echo "<br>";

}

if(mysqli_num_rows($query2) > 0) {

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
echo "<table border=0>";
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
echo "<select name='year' onchange='this.form.submit()'>";
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
} // end if election results by muni and year

if(mysqli_num_rows($query_county_stats) > 0) {
	echo "<h3><a id='counties'>Counties</a></h3>";
	
	if($cd_id == "07") {
		// should be dynamic but I don't want to run another query and the loop is below
		echo "<a href='../../nj7/data/countydata3.php?county=Essex'>Essex</a> ";
		echo "<a href='../../nj7/data/countydata3.php?county=Hunterdon'>Hunterdon</a> ";
		echo "<a href='../../nj7/data/countydata3.php?county=Morris'>Morris</a> ";
		echo "<a href='../../nj7/data/countydata3.php?county=Somerset'>Somerset</a> ";
		echo "<a href='../../nj7/data/countydata3.php?county=Union'>Union</a> ";
		echo "<a href='../../nj7/data/countydata3.php?county=Warren'>Warren</a>";	
		echo "<br>";
		echo "<br>";
	}
	echo "The \"County Relevance\" chart shows how the different counties within NJ".$cd_id." contribute to the 2018 district electoral results (for Congress).";
	echo "<br>";
	echo "<br>";
	
	$a_counties = array();
	$a_reg_voters = array();
	$a_turnout = array();
	$a_dem_votes = array();
	$a_rep_votes = array();
	
	$first_row = true;
	while($row = mysqli_fetch_array($query_county_stats)) {
		$cty = $row["county"];
		$el_year = $row["election_year"];
		$el_type = $row["election_type"];
		$registered = $row["registered_voters"];
		$turnout = $row["turnout"];
		$dem_votes = $row["dem_votes"];
		$rep_votes = $row["rep_votes"];
		if($first_row) {
			echo "<table border='1' class='sortable' id='county_stats_table'>";
			echo "<caption><b>County Relevance ".$el_type." ".$el_year."</b></caption>";
			echo "<tr title='Click to sort'>";
			echo "<th>County</th>";
			echo "<th>Registered Voters</th><th>Turnout</th>";
			echo "<th>Dem Votes</th><th>Rep Votes</th>";
			echo "</tr>";
		}
		$first_row = false;
		echo "<tr>";
		echo "<td style='white-space:nowrap;'>".$cty."</td>";
		if($registered > 0) {
			echo "<td align=right>".number_format($registered, 0, ".", ",")."</td>";
		} else {
			echo "<td align=right></td>";
		}
		if($turnout > 0) {
			echo "<td align=right>".number_format($turnout, 0, ".", ",")."</td>";
		} else {
			echo "<td align=right></td>";
		}
		if($dem_votes > 0) {
			echo "<td align=right>".number_format($dem_votes, 0, ".", ",")."</td>";
		} else {
			echo "<td align=right></td>";
		}
		if($rep_votes > 0) {
			echo "<td align=right>".number_format($rep_votes, 0, ".", ",")."</td>";
		} else {
			echo "<td align=right></td>";
		}
		echo "</tr>";
		array_push($a_counties, $cty);
		array_push($a_reg_voters, $registered);
		array_push($a_turnout, $turnout);
		array_push($a_dem_votes, $dem_votes);
		array_push($a_rep_votes, $rep_votes);
	}
	$js_reg_voters = json_encode($a_reg_voters);
	$js_turnout = json_encode($a_turnout);
	$js_dem_votes = json_encode($a_dem_votes);
	$js_rep_votes = json_encode($a_rep_votes);
	$js_counties = json_encode($a_counties);
	
	echo "<table>";
	echo "<tr>";
	echo "<td valign='top'><canvas id='CountyChart' width='650' height='400'></canvas></td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR>";
} else {
	// no data, to make the js work
	$a_counties = array("");
	$js_counties = json_encode($a_counties);
	$js_reg_voters = json_encode(array(""));	
	$js_turnout = json_encode(array(""));
	$js_dem_votes = json_encode(array(""));
	$js_rep_votes = json_encode(array(""));
}
?>

<BR>

<script>

var data = <?php echo $SubsStats; ?>;

//alert(data);
if(data != '') {

var ctx = document.getElementById("myChart");

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

//alert(document.getElementById("CountyChart"));

var ctx_cty = document.getElementById("CountyChart");

//alert("el" + ctx_cty);


if(ctx_cty != null) {
	
	var c = <?php echo $js_counties; ?>;
	var reg = <?php echo $js_reg_voters; ?>;
	var t = <?php echo $js_turnout; ?>;
	var d = <?php echo $js_dem_votes; ?>;
	var r = <?php echo $js_rep_votes; ?>;


	var registeredVoters = {
	  label: 'Reg Voters',
	  data: reg,
	  backgroundColor: '#839192',
	  borderWidth: 0,
	};

	var turnout = {
	  label: 'Turnout',
	  data: t,
	  backgroundColor: '#27AE60',
	  borderWidth: 0,
	};

	var demVotes = {
	  label: 'Dem Votes',
	  data: d,
	  backgroundColor: '#335DFF',
	  borderWidth: 0,
	};

	var repVotes = {
	  label: 'Rep Votes',
	  data: r,  
	  backgroundColor: '#FF0000',
	  borderWidth: 0,
	};


	var relevanceData = {
	  labels: c,
	  datasets: [registeredVoters, turnout, demVotes, repVotes]
	};


	var chartOptions = {
		responsive: false,
		scales: { 
			xAxes: [{
				barPercentage: 0.8,
				//barThickness: 18,
				maxBarThickness: 18
			}]
		},			
		animation: {
			duration: 0
		},
		legend: {
			position: 'bottom',
			labels: {
				boxWidth: 30
			}
		},
		title: {
			display: true,
			text: 'County Relevance House 2018',
			position: 'top'
		},
		tooltips: {
		  callbacks: {
			label: function(tooltipItem, data) {
				var dataset = data.datasets[tooltipItem.datasetIndex];
				var value = dataset.data[tooltipItem.index];
				return data.datasets[tooltipItem.datasetIndex].label+": "+value.toLocaleString('en');
			}
		  }
		}	
	};


	var barChart = new Chart(ctx_cty, {
	  type: 'bar',
	  data: relevanceData,
	  options: chartOptions
	});
} // if chart county exists

tabdropdown.init("bluecompasstab", 2);
</script>

  </body>
</html>
<?php
ob_end_flush();
?>