<?php

ini_set('display_errors','off');

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
"select c.cd, a.year, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, 
sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast,
sum(ballots_cast)/sum(registered_voters) as turnout_pct,
sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes)/sum(ballots_cast) as rep_pct,
dem_candidate, rep_candidate, a.district 
from state_election_results a
join municipal_list_new c on a.muni_code = c.muni_code
left join state_ballots_cast b on b.muni_code = a.muni_code and b.year = a.year
where c.cd='".$cd_id."'";
//if($ld_id == "28" || $ld_id == "29" || $ld_id == "31" || $ld_id == "33") {
	// 28 & 29 have Newark in common, 31 & 33 have Jersey City in common
	// eventually we should get results by precincts and know which precincts are in which district
//	$sql_election_results = $sql_election_results." and (a.district is null OR a.district = 'LD".$ld_id."')";
//}
$sql_election_results = $sql_election_results. 
" and office in ('NJ Senate','Assembly 1','Assembly 2','Governor', 'US House', 'President')
and (b.district is null or b.district = 'CD".$cd_id."')
group by a.year, cd, office 
order by a.year desc, c.cd, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', office))";
// the b.district=cd_id is wrong for split towns like East Greenwich in CD01 and CD02
// it is wrong because in election_results and ballots_cast the district is never null
// the is null is valid for statewide elections


$query_election_results = mysqli_query($conn, $sql_election_results);


$sql_election_results_after_redistrict_2020 =
"select c.cd, a.year, c.county, town, office, dem_votes, rep_votes, 
sum(registered_voters) as registered_voters, 
sum(ballots_cast) as ballots_cast, 
sum(ballots_cast)/sum(registered_voters) as turnout_pct, 
sum(dem_votes)/sum(ballots_cast) as dem_pct, 
sum(rep_votes)/sum(ballots_cast) as rep_pct, 
dem_candidate, rep_candidate, a.district 
from state_election_results a 
join municipal_list_2022 c on a.muni_code = c.muni_code 
left join state_ballots_cast b on b.muni_code = a.muni_code and b.year = a.year 
where c.cd='".$cd_id."'
and office in ('President','NJ Senate','Assembly 1','Assembly 2','Governor', 'US House') 
group by a.year, cd, office 
order by a.year desc, c.cd, c.county, town, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', office));
";

$sql5 = "select county, town, muni_id, ld from municipal_list_new where cd=".$cd_id." 
or other_cd=".$cd_id." order by 1, 2";


$query5 = mysqli_query($conn, $sql5);

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from monthly_registrations
where cd='".$cd_id."' and publish='Y' 
order by `year_month`";

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from state_voter_registrations
where district='CD".$cd_id."' and publish='Y' 
order by `year`, `month`";

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


$sql_repr = "select * from representatives where cd='".$cd_id."'";
$representatives_query = mysqli_query($conn_a, $sql_repr);

if($debug=="y") { // debug
	echo $sql5."<br>";
	echo $sql_election_results."<br>";
	echo $sql_county_stats."<br>";
	echo $registrations_sql."<br>";
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
$lds = array();
while($row5 = mysqli_fetch_array($query5)) {
	$county = $row5["county"];
	if($county != $cty) {
		echo "<br><b>".$county.":</b> ";
		$cty = $county;
	}
	echo "<a href='../../muni?muni_id=".$row5["muni_id"]."'>".$row5["town"]."</a>, ";
	if(!in_array($row5["ld"], $lds)) {
		$lds[] = $row5["ld"];
	}
}
echo "<BR>";
echo "<BR>";
sort($lds);
echo "Congressional District <b>CD".$cd_id."</b> overlaps part of Legislative Districts ";
foreach($lds as $d) {
	echo "<a href='../../ld/data/?ld=".$d."'>".$d."</a> ";
}
?>

<BR>
<BR>

<?php

echo "<table border='1'>";
echo "<caption><b>District Representatives</b></caption>";
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
echo "<th>Propublica</th>";
echo "<th>Opensecrets</th>";
echo "<th>GovTrack</th>";
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
	//echo table_cell($rep["propublica"], "propublica");
	echo "<td>";
	$propublica = $rep["propublica"];
	if(!empty($propublica)) {
		echo "<a target='_blank' href='".$propublica."'>Propublica</a>";
	}
	echo "</td>";	
	echo "<td>";
	$opensecret = $rep["opensecret"];
	if(!empty($opensecret)) {
		echo "<a target='_blank' href='".$opensecret."'>Opensecrets</a>";
	}
	echo "</td>";	
	echo "<td>";
	$govtrack = $rep["govtrack"];
	if(!empty($govtrack)) {
		echo "<a target='_blank' href='".$govtrack."'>GovTrack</a>";
	}
	echo "</td>";	
	echo "</tr>";
}
echo "</table>";
echo "<br>";
echo "<hr width='70%'>";
echo "<br>";
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
echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th>";
echo "<th>Dem Candidate</th><th>Rep Candidate</th><th>Margin</th><th>Margin %</th>";
echo "</tr>";

$labels = array();
$values = array();
$i = 0;
while($row = mysqli_fetch_array($query_election_results)) {
	if(($row["year"] % 2) == 0) {
		echo "<tr bgcolor='#ffffcc'>";
	} else {
		echo "<tr>";
	}
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballots_cast"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["turnout_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["dem_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["rep_pct"]*100, 2, ".", ",")." %</td>";
	
	//$dem_margin = $row["dem_votes"] - $row["rep_votes"];
	//echo "<td align=right>".number_format($dem_margin, 0, ".", ",")."</td>";
	
	if(strpos($row["district"], "LD") === false) {
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

		$margin_pct = $row["dem_pct"] - $row["rep_pct"]; // it's Dem margin
		echo "<td align=right>";	
		if($margin_pct > 0) {
			echo  "<p><font color='blue'>";
		} else {
			echo  "<p><font color='red'>";
		}
		echo number_format(abs($margin_pct*100), 2, ".", ",")."%";
		echo "</td>";			
	} else {
		echo "<td></td>";
		echo "<td></td>";
	}
	
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

<?php
function table_cell($data, $label)
{
	$st = "<td>";
	if(!empty($data)) {
		$st += "<a target='_blank' href='".$data."'>".$label."</a>";
	}
	$st += "</td>";
	return $st;
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