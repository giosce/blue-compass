<?php


include("../db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$town = $_GET["town"];
$cd_id = $_GET["cd"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];

$cd_id = str_replace("NJ","",$cd_id);

#$cd_id = "07";

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


$sql_election_results = 
"SELECT office, SUM(registered_voters) AS registered_voters, SUM(ballot_cast) AS ballot_cast, 
SUM(dem_votes) AS dem_votes, SUM(rep_votes) AS rep_votes, year, 
SUM(ballot_cast)/ SUM(registered_voters) AS turnout_pct, SUM(dem_votes)/ SUM(ballot_cast) AS dem_pct, 
SUM(rep_votes)/ SUM(ballot_cast) AS rep_pct,
dem_candidate, rep_candidate
FROM election_results
where muni_code in (select muni_code from municipal_list where cd='".$cd_id."')
-- query above is at precinct level so it takes in account split towns, for now only for CD7 for 2022
GROUP BY year 
UNION -- this below is by muni, so it doesn't account for split towns 
select office, sum(c.registered_voters), sum(c.ballots_cast), sum(b.dem_votes), sum(b.rep_votes), b.year, 
sum(c.ballots_cast)/sum(c.registered_voters) as turnout_pct, sum(dem_votes)/sum(c.ballots_cast) as dem_pct, 
sum(rep_votes)/sum(c.ballots_cast) as rep_pct,
dem_candidate, rep_candidate 
from municipal_list a join state_election_results b on b.muni_code = a.muni_code 
left join state_ballots_cast c on b.muni_code = c.muni_code and c.year = b.year 
where a.cd='".$cd_id."' and (a.cd <> '07' OR b.year <> '2022') -- since for now 2022 NJ07 is in election_results at precinct level
group by year, office order by year desc, 
if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";

// where b.office='US House' and a.cd='".$cd_id."' and b.year < '2022' -- since for now 2022 is in election_results at precinct level

$query_election_results = mysqli_query($conn, $sql_election_results);


$sql_election_prev_results = 
"select office, sum(c.registered_voters) as registered_voters, sum(c.ballots_cast) as ballot_cast, 
sum(b.dem_votes) as dem_votes, sum(b.rep_votes) as rep_votes, b.year, 
sum(c.ballots_cast)/sum(c.registered_voters) as turnout_pct, sum(dem_votes)/sum(c.ballots_cast) as dem_pct, 
sum(rep_votes)/sum(c.ballots_cast) as rep_pct,
dem_candidate, rep_candidate 
from municipal_list a join state_election_results b on b.muni_code = a.muni_code 
left join state_ballots_cast c on b.muni_code = c.muni_code and c.year = b.year 
where a.old_cd='".$cd_id."'
group by year, office order by year desc, 
if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";

$query_election_prev_results = mysqli_query($conn, $sql_election_prev_results);


$sql5 = "select county, town, muni_id, ld, partial from municipal_list where cd=".$cd_id." or other_cd=".$cd_id." order by 1, 2";

$query5 = mysqli_query($conn, $sql5);

$registrations_sql =
"select dt_label, dem, rep, una, gre+lib+rfp+con+nat+cnv+ssp as oth 
from state_monthly_voter_registrations
where district='CD".$cd_id."' and publish='Y' 
order by `year`, `month`";

$registrations_query = mysqli_query($conn, $registrations_sql);


// query for munis total registered and votes since can't get it right with the large join
$votes_by_town_sql = "select muni, ssn, sum(registered_voters) as registered_total, 
sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes
from turnout_precincts_tbl a
join municipal_list b on ssn=substr(a.precinct_id, 1, 4)
where ld=".$ld_id." 
and election_type_code='Con' and a.election_year='2018'
group by ssn
order by muni";

#$votes_by_town_query = mysqli_query($conn, $votes_by_town_sql);
// better put votes by town in a map by muni_id

$party_turnout_sql = "select * from party_turnout_by_muni_2018_view where cd=".$cd_id;

#$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

$sql_county_stats =
# very specific to NJ07 using precinct data so it accounts for split towns
"select a.county, sum(registered_voters) registered_voters, sum(ballot_cast) turnout, 
sum(dem_votes) dem_votes, sum(rep_votes) rep_votes
FROM municipal_list a left join election_results b
on b.muni_code = a.muni_code 
where cd='".$cd_id."' and b.county <> 'Morris'
group by a.county
union -- Morris has TOTAL and same data split by vote type
select a.county, sum(registered_voters) registered_voters, sum(ballot_cast) turnout, 
sum(dem_votes) dem_votes, sum(rep_votes) rep_votes
FROM municipal_list a left join election_results b
on b.muni_code = a.muni_code 
where cd='".$cd_id."' and b.county='Morris' and type_of_vote = 'TOTAL'
order by 1";

if($cd_id != "07") {
	$sql_county_stats =
	"select b.county, sum(registered_voters) registered_voters, sum(ballots_cast) turnout, 
	sum(mail_ballots_cast) mail_ballots_cast, sum(dem_votes) dem_votes, sum(rep_votes) rep_votes
	FROM municipal_list a, state_ballots_cast b, state_election_results c
	where cd='".$cd_id."' and b.year = '2022'
	and b.muni_code = a.muni_code and c.muni_code = a.muni_code
	and c.year = b.year
	group by 1
	order by 1";
}

$query_county_stats = mysqli_query($conn, $sql_county_stats);
$num_counties = mysqli_num_rows($query_county_stats);


#$sql_voter_reg_from_voter_list_and_2022_elec_results_muni = 
#"select a.county, a.muni, a.muni_code,
#sum(dem) reg_dem, sum(rep) reg_rep, sum(una) reg_una, 
#sum(dem_votes) dem_votes, sum(rep_votes) rep_votes
#from election_results a 
#join voter_registrations_from_voter_list b on a.muni_code = b.muni_code
#where cd='".$cd_id."'
#group by a.muni_code, b.muni_code
#order by a.county, a.muni";

$sql_voter_reg_from_voter_list = 
"select muni, muni_code, sum(dem) dem, sum(rep) rep, sum(una) una
from voter_registrations_from_voter_list c
where c.cd='".$cd_id."'
group by muni_code
order by muni_code";

$query_voter_reg_from_voter_list = mysqli_query($conn, $sql_voter_reg_from_voter_list);


#2022 Nov election_results by muni
$sql_2022_elec_results_muni = 
"select a.county, a.muni, a.muni_code, 
sum(registered_voters) registered_voters, sum(ballot_cast) ballot_cast, 
sum(rep_votes) rep_votes, sum(dem_votes) dem_votes, partial
from election_results a join municipal_list b on a.muni_code = b.muni_code
where a.county <> 'morris' and a.muni_code <> ''
and cd='".$cd_id."'
group by a.muni_code
union
select a.county, a.muni, a.muni_code, registered_voters, ballot_cast, rep_votes, dem_votes, partial
from election_results a join municipal_list b on a.muni_code = b.muni_code
where a.county='morris' and a.muni_code <> '' 
and type_of_vote='TOTAL' and cd='".$cd_id."'
order by muni_code;";

$query_2022_elec_results_muni = mysqli_query($conn, $sql_2022_elec_results_muni);


# who voted in Nov 2022 by muni
$sql_voter_history_2022_muni = 
"select res_county, res_muni, res_muni_code, party, count(*) voters
from voter_history
where res_congressional='".$cd_id."' and election_date='2022-11-08'
and party in ('Democratic', 'Republican', 'Unaffiliated')
group by res_county, res_muni, party
order by res_county, res_muni";

$query_voter_history_2022_muni = mysqli_query($conn, $sql_voter_history_2022_muni);


if($debug=="y") { // debug
	echo $sql5."<br>";
	echo $sql_election_results."<br>";
	echo $sql_county_stats."<br>";
	echo $registrations_sql."<br>";
	echo $sql_voter_history_2022_muni."<br>";
	echo $sql_voter_reg_from_voter_list_and_2022_elec_results_muni."<br>";
}

?>

<html>
  <head>
    <title>NJ CD <?php echo $cd_id; ?> Electoral Data</title>
	<script src="../sorttable.js"></script>
	<script src="../Chart.bundle.min.js"></script> <!-- chartjs.org -->
	<script src="../chartjs-plugin-datalabels.min.js"></script>	
	<script src="../jquery-3.3.1.min.js"></script>
	<script src="../Blob.min.js"></script>
	<script src="../xls.core.min.js"></script>	
	<script src="../FileSaver.min.js"></script>
	<script src="../tableexport.min.js"></script>

	<link href="../tableexport.css" type="text/css" rel="stylesheet">
	<link href="../bluecompass.css" type="text/css" rel="stylesheet">
	
	<style type="text/css">	
		
		H2 {
			display: inline;
		}
		
		.tab3 {
			overflow: hidden;
			border: 1px solid #ccc;
		}

		
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
	#background-color:#ccffff;
	cursor: default;
}
		
	</style>
	
	<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
	<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>
	
  </head>
<body style="font-family:Arial">

<?php include("../menu.html"); ?>

<!--
<a href="../../index.html">Home</a>
<a href="../../congress.html">Congress</a>
<a href="../cd<?php echo $cd_id; ?>.php">District</a>
-->

<br>
<center>
<?php
$num_cd = (int)$cd_id;
$next_cd = $num_cd + 1;
$prev_cd = $num_cd - 1;
if(strlen($prev_cd) == 1) {
	$prev_cd = "0".$prev_cd;
}
if(strlen($next_cd) == 1) {
	$next_cd = "0".$next_cd;
}
?>
<H2><?php echo "<a href='cd.php?cd=$prev_cd'><</a> NJ CD ".$cd_id." Data <a href='cd.php?cd=$next_cd'>></a>";?></H2>

</center>
<?php
#if($cd_id == "07") {
#	echo "<a href='stats.php'>Municipal electoral statistics across the district</a>";
#	echo "<br>";
#}

$towncount=mysqli_num_rows($query5);
?>
<BR>
The New Jersey Congressional District <?php echo $cd_id; ?> is made of <?php echo $towncount; ?> municipalities 
across <?php echo $num_counties; ?> <a href="#counties">counties</a>.
<BR>
<?php
$cty = "";
#$lds = array();
while($row5 = mysqli_fetch_array($query5)) {
	$county = $row5["county"];
	if($county != $cty) {
		echo "<br><b><a href='county.php?county=".$county."&cd=".$cd_id."'>".$county."</a>:</b> ";
		$cty = $county;
	}
	echo "<a href='muni.php?muni_id=".$row5["muni_id"]."'>".$row5["town"];
	if($row5["partial"] == "Y") {
		echo "(partial)";
	}
	echo "</a>, ";
	if(!in_array($row5["ld"], $lds)) {
		$lds[] = $row5["ld"];
	}
}
echo "<BR>";
echo "<BR>";
#sort($lds);
echo "Congressional District <b>CD".$cd_id."</b> overlaps part of Legislative Districts ";
foreach($lds as $d) {
	echo "<a href='ld.php?ld=".$d."'>".$d."</a> ";
}


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
<canvas id="regChart" width="600" height="300"></canvas>
</td>
<td width="50"></td>
<td valign="top">
<canvas id="lastRegChart" width="260" height="260"></canvas>
</td>
</tr>
</table>
<i>Note: Between March and April 2022 the State switched to count registered voters with the 2021 Redistricting district boundaries</i>
<br>
<br>
<br>
<br>

<div class="w3-bar w3-black">
  <button class="tab-button" onclick="openTab('current')" id="current-tab" style="background-color:#8DA1BF;">Current</button>
  <button class="tab-button" onclick="openTab('before2022')" id="before2022-tab">Before 2022</button>
</div>

<div id="current" class="tab3">
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
	if(($row["year"] % 2) == 1) {
		echo "<tr bgcolor='#ffffcc'>";
	} else {
		echo "<tr>";
	}	
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballot_cast"], 0, ".", ",")."</td>";
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
		if($margin_pct != 0) {
			echo number_format(abs($margin_pct*100), 2, ".", ",")."%";
		}
		echo "</td>";			
	} else {
		echo "<td></td>";
		echo "<td></td>";
	}
	
	echo "</tr>";
	$label = $row["year"]." ".$row["office"];
	array_push($labels, $label);
	$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballot_cast"],
	"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
}
//print_r($values);
$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";
echo "</table>";
echo "<i>Source: State and Counties elections results reports</i>";
echo "<br>";
echo "<i>The results before 2022 are based on the current district composition, not at the time of the elections</i>";
echo "<BR>";
echo "<BR>";
echo "<canvas id='myChart' width='750' height='400'></canvas>";
echo "<BR>";

?>

</div>


<div id="before2022" class="tab3" style="display:none;">
<?php
echo "<table border='1' class='sortable' id='election_result_table'>";
echo "<caption><b>Previous Elections Results</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>";
echo "<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th>";
echo "<th>Dem Candidate</th><th>Rep Candidate</th><th>Margin</th><th>Margin %</th>";
echo "</tr>";

//$labels = array();
//$values = array();
$i = 0;
while($row = mysqli_fetch_array($query_election_prev_results)) {
	if(($row["year"] % 2) == 1) {
		echo "<tr bgcolor='#ffffcc'>";
	} else {
		echo "<tr>";
	}
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballot_cast"], 0, ".", ",")."</td>";
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
		if($margin_pct != 0) {
			echo number_format(abs($margin_pct*100), 2, ".", ",")."%";
		}
		echo "</td>";			
	} else {
		echo "<td></td>";
		echo "<td></td>";
	}
	
	echo "</tr>";
	#$label = $row["year"]." ".$row["office"];
	#array_push($labels, $label);
	#$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballot_cast"],
	#"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
}
//print_r($values);
#$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";
echo "</table>";
echo "<i>Source: State and Counties elections results reports</i>";
echo "<br>";
#echo "<i>The results before 2022 are based on the current district composition, not at the time of the elections</i>";
#echo "<BR>";
#echo "<BR>";
#echo "<canvas id='myChart2' width='750' height='400'></canvas>";
#echo "<BR>";

?>
</div>
<!-- if I put chart in if ... data... javascript breaks
<canvas id='myChart' width='750' height='400'></canvas>
<BR>
<BR>
-->

<a id="towns">
<br>

<?php

if(mysqli_num_rows($query_county_stats) > 0) {
	echo "<br>";
	echo "<br>";
	echo "<h3><a id='counties'>Counties</a></h3>";
	
	$row = mysqli_fetch_array($query_county_stats);
	$registered = $row["registered_voters"];

	if($registered == "") { # to check if we have stats
		echo "<a href='county.php?county=".$row["county"]."&cd=".$cd_id."'>".$row["county"]."</a> ";
		while($row = mysqli_fetch_array($query_county_stats)) {
			echo "<a href='county.php?county=".$row["county"]."&cd=".$cd_id."'>".$row["county"]."</a> ";
		}
		echo "<br>";
	} else {
		echo "<br>";
		echo "The \"County Relevance\" chart shows how the different counties within NJ".$cd_id." contribute to the 2022 district electoral results (for Congress).";
		echo "<br>";
		echo "<table border='1' class='sortable' id='county_stats_table'>";
		echo "<caption><b>County Relevance ".$el_type." ".$el_year."</b></caption>";
		echo "<tr title='Click to sort'>";
		echo "<th>County</th>";
		echo "<th>Registered Voters</th><th>Turnout</th>";
		echo "<th>Dem Votes</th><th>Rep Votes</th>";
		echo "</tr>";
	
		$a_counties = array();
		$a_reg_voters = array();
		$a_turnout = array();
		$a_dem_votes = array();
		$a_rep_votes = array();

		$cty = $row["county"];
		$el_year = $row["election_year"];
		$el_type = $row["election_type"];
		$registered = $row["registered_voters"];
		$turnout = $row["turnout"];
		$dem_votes = $row["dem_votes"];
		$rep_votes = $row["rep_votes"];
	
		echo "<tr>";
		echo "<td style='white-space:nowrap;'><a href='county.php?county=".$cty."&cd=".$cd_id."'>".$cty."</a></td>";
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

		while($row = mysqli_fetch_array($query_county_stats)) {
			$cty = $row["county"];
			$el_year = $row["election_year"];
			$el_type = $row["election_type"];
			$registered = $row["registered_voters"];
			$turnout = $row["turnout"];
			$dem_votes = $row["dem_votes"];
			$rep_votes = $row["rep_votes"];

			echo "<tr>";
			echo "<td style='white-space:nowrap;'><a href='county.php?county=".$cty."&cd=".$cd_id."'>".$cty."</a></td>";
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
	}
	$js_reg_voters = json_encode($a_reg_voters);
	$js_turnout = json_encode($a_turnout);
	$js_dem_votes = json_encode($a_dem_votes);
	$js_rep_votes = json_encode($a_rep_votes);
	$js_counties = json_encode($a_counties);
	
	if($registered != "") {
		echo "<br>";
		echo "<table>";
		echo "<tr>";
		echo "<td valign='top'><canvas id='CountyChart' width='650' height='400'></canvas></td>";
		echo "</tr>";
		echo "</table>";
		echo "<BR>";
	}
} else {
	// no data, to make the js work
	$a_counties = array("");
	$js_counties = json_encode($a_counties);
	$js_reg_voters = json_encode(array(""));	
	$js_turnout = json_encode(array(""));
	$js_dem_votes = json_encode(array(""));
	$js_rep_votes = json_encode(array(""));
}

echo "<br>";
echo "<br>";

$voter_who_voted = array();

$muni_code = "";
while($row = mysqli_fetch_array($query_voter_history_2022_muni)) {
	if($row["res_muni_code"] != $muni_code) {
		$muni_code = $row["res_muni_code"];
		$voter_who_voted[$muni_code] = array();
	}
	$voter_who_voted[$muni_code][] = $row["voters"];
}

#print_r($voter_who_voted);

$registered_voters = array();
$muni_code = "";
while($row = mysqli_fetch_array($query_voter_reg_from_voter_list)) {
	$muni_code = $row["muni_code"];
	#$registered_voters($muni_code => array($row["dem"], $row["rep"], $row["una"]));
	#$registered_voters[] = $muni_code;
	#$registered_voters[$muni_code][] = array(["dem" => $row["dem"], "rep" => $row["rep"], "una" => $row["una"]]);
	#$registered_voters[$muni_code][$row["dem"]]; #, "rep" => $row["rep"], "una" => $row["una"]];
	$registered_voters[$muni_code][] = array();
	array_push($registered_voters[$muni_code], $row["dem"], $row["rep"], $row["una"]);
}
#print_r($registered_voters);
#echo "<br><br>";
#print_r($registered_voters["1001"]);
#echo "<br><br>";
#print_r($registered_voters["1001"][2]);
#echo "<br><br>";
#echo "Reg for 1001: ".$registered_voters["1001"][1];

if(mysqli_num_rows($query_2022_elec_results_muni) > 0) {
	echo "<table border='1' class='sortable' id='vr_and_elec_result_table'>";
	echo "<caption><b>Registered Voters, Voting Voters and Last Elections Results</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>County</th><th>Town</th>";
	echo "<th>Reg Dem</th><th>Dem Who Voted</th><th>%</th>";
	echo "<th>Reg Rep</th><th>Rep Who Voted</th><th>%</th>";
	echo "<th>Reg Una</th><th>Una Who Voted</th><th>%</th>";
	echo "<th>Dem Votes</th><th>Rep Votes</th><th>Winner</th>";
	echo "</tr>";
}

while($row = mysqli_fetch_array($query_2022_elec_results_muni)) {
	$muni_code = $row["muni_code"];
	$muni_name = $row["muni"];
	if($row["partial"] == "Y") {
		$muni_name .= " (partial)";
	}
	$reg_dem = $registered_voters[$muni_code][1];
	$reg_rep = $registered_voters[$muni_code][2];
	$reg_una = $registered_voters[$muni_code][3];
	echo "<tr>";
	echo "<td>".$row["county"]."</td>";
	echo "<td><a href='muni.php?muni_id=".$muni_code."'>".$muni_name."</a></td>";
	#echo "<td align=right>".number_format($row["reg_dem"], 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='BEE2FC'>".number_format($reg_dem, 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='BEE2FC'>".number_format($voter_who_voted[$muni_code][0], 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='BEE2FC'>".number_format($voter_who_voted[$muni_code][0]/$reg_dem*100, 2, ".", ",")." %</td>";
	echo "<td align=right bgcolor='FFE3E4'>".number_format($reg_rep, 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='FFE3E4'>".number_format($voter_who_voted[$muni_code][1], 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='FFE3E4'>".number_format($voter_who_voted[$muni_code][1]/$reg_rep*100, 2, ".", ",")."%</td>";
	echo "<td align=right bgcolor='E4E4E4'>".number_format($reg_una, 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='E4E4E4'>".number_format($voter_who_voted[$muni_code][2], 0, ".", ",")."</td>";
	echo "<td align=right bgcolor='E4E4E4'>".number_format($voter_who_voted[$muni_code][2]/$reg_una*100, 2, ".", ",")."%</td>";
	#echo "<td align=right bgcolor='4A60FD'>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td align=right><font color='blue'>".number_format($row["dem_votes"], 0, ".", ",")."</font></td>";
	echo "<td align=right><font color='red'>".number_format($row["rep_votes"], 0, ".", ",")."</font></td>";
	if($row["dem_votes"] > $row["rep_votes"]) {
		echo "<td bgcolor='blue'>";
	} else {
		echo "<td bgcolor='red'>";
	}
	echo "</td>";
	echo "</tr>";
}
echo "</table>";
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
			text: 'County Relevance House 2022',
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


function openTab(tabName) {
  var i;
  var x = document.getElementsByClassName("tab3");
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

  </body>
</html>
<?php
ob_end_flush();
?>