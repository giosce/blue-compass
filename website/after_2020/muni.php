<?php

include("../db.php");

#$district = $_GET["district"];
#$town = $_GET["town"];
$muni_id = $_GET["muni_id"];
#$year = $_GET["year"];
#$el_type = $_GET["el_type"];
#$precinct_id = $_GET["precinct_id"];

$muni_sql = "select * from municipal_list where muni_code = ".$muni_id; 

$muni_query = mysqli_query($conn, $muni_sql);

$muni = mysqli_fetch_array($muni_query);

$county = $muni["county"];

$election_results_sql = "
select a.year, a.office, dem_votes, rep_votes, a.dem_candidate, a.rep_candidate,
c.dem_candidate as dem_candidate_2, c.rep_candidate as rep_candidate_2,
registered_voters, ballots_cast, pct_ballots_cast,
ballots_cast / registered_voters as turnout, 
dem_votes / ballots_cast as dem_pct,
rep_votes / ballots_cast as rep_pct
from state_election_results a left join state_ballots_cast b
on b.muni_code = a.muni_code and b.year = a.year
left join candidates_view c on c.election_year = a.year and c.office=a.office
and (c.cd is null or c.cd=a.district)
where a.muni_code='".$muni_id."'
order by a.year desc, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";

$election_results_sql =
"select sum(registered_voters) as registered_voters, sum(ballot_cast) as ballot_cast,
sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, year, ward, precinct,
sum(ballot_cast)/sum(registered_voters) as turnout_pct,
sum(dem_votes)/sum(ballot_cast) as dem_pct,
sum(rep_votes)/sum(ballot_cast) as rep_pct
from election_results
where muni_code='".$muni_id."'
group by year
order by year";

if($county != "Morris") {
	$election_results_sql = "
	select a.county, a.muni, a.muni_code, a.ward, a.precinct, 
	sum(a.registered_voters) registered_voters,
	sum(a.rep_votes) rep_votes, sum(a.dem_votes) dem_votes, sum(a.ballot_cast) ballot_cast, 
	sum(dem_votes)/sum(ballot_cast) as dem_pct,
	sum(rep_votes)/sum(ballot_cast) as rep_pct,
	sum(ballot_cast)/sum(registered_voters) as turnout_pct,
	a.type_of_vote, a.year, a.office, dem_candidate, rep_candidate
	from election_results a
	where a.muni_code='".$muni_id."'
	group by muni_code, a.year, office";
} else {
	$election_results_sql = "
	select a.county, a.muni, a.muni_code, a.ward, a.precinct, 
	sum(a.registered_voters) registered_voters,
	sum(a.rep_votes) rep_votes, sum(a.dem_votes) dem_votes, sum(a.ballot_cast)/2 ballot_cast, 
	sum(dem_votes)/(sum(ballot_cast)/2) as dem_pct,
	sum(rep_votes)/(sum(ballot_cast)/2) as rep_pct,
	(sum(ballot_cast)/2)/sum(registered_voters) as turnout_pct,
	a.type_of_vote, a.year, a.office, dem_candidate, rep_candidate
	from election_results a
	where a.muni_code='".$muni_id."'
	group by muni_code, a.year, office";
}

$election_results_sql .= 
" union
select b.county, b.muni, b.muni_code, 'w', 'p', sum(c.registered_voters), 
sum(b.rep_votes), sum(b.dem_votes), sum(c.ballots_cast), 
sum(dem_votes)/sum(c.ballots_cast) as dem_pct,
sum(rep_votes)/sum(c.ballots_cast) as rep_pct,
sum(c.ballots_cast)/sum(c.registered_voters) as turnout_pct,
'v' as type_of_vote, b.year, b.office, dem_candidate, rep_candidate
from state_election_results b left join state_ballots_cast c
on c.muni_code = b.muni_code and c.year=b.year
where b.muni_code='".$muni_id."'
group by b.year, b.muni_code, b.office, type_of_vote
order by year desc, if(office = 'NJ Senate', 'AB', if(office = 'Governor', 'AA', if(office='US Senate', 'US A', office)))";

$election_results_query = mysqli_query($conn, $election_results_sql);
#echo "<br>".$election_results_sql."<br>";


$registered_and_eligible_voters_sql = "
select a.county, b.muni, 
sum(una+dem+rep+cnv+con+gre+lib+nat+rfp+ssp) as tot_registered_voters,
b.muni_id, b.census_eligible_voters_2019 as eligible_voters, as_of
from registered_voters a, municipal_list_2022 b
where a.county = b.county and a.muni = b.muni_from_county
and census_eligible_voters_2019 is not null
and b.muni_id='".$muni_id."'
group by a.county, b.muni";

$eligible_voters_sql = "select * from acs_population where muni_code=".$muni_id;

//echo "<br>".$registered_and_eligible_voters_sql."<br>";
$eligible_voters_query; # = mysqli_query($conn, $eligible_voters_sql);
$eligible_voters; # = mysqli_fetch_array($eligible_voters_query);

$registered_voters_sql = "
select a.county, b.muni, b.muni_id, ward, precinct, as_of,
una, dem, rep, cnv, con, gre, lib, nat, rfp, ssp
from registered_voters a, municipal_list_2022 b
where a.county = b.county and a.muni = b.muni_from_county
and b.muni_id='".$muni_id."'
order by ward, cast(precinct as unsigned)";

//echo "<br>".$registered_and_eligible_voters_sql."<br>";
#$registered_voters_query = mysqli_query($conn2, $registered_voters_sql);

$registered_voters_sql = "
select municipality, muni_id, party, count(*)
from voter_list_nj7
where muni_id='".$muni_id."'
and party in ('Democratic', 'Republican','Unaffiliated')
group by municipality, party
order by party";

$registered_voters_sql = 
"select municipality,sum(Dem) Dem,sum(Rep) Rep,sum(Una) Una,sum(Cnv) Cnv,
sum(Con) Con,sum(Gre) Gre,sum(Lib) Lib,sum(Nat) Nat,sum(Rfp) Rfp,sum(Ssp) Ssp 
from
(select municipality, muni_id, 
case 
when party='Democratic' then count(*)
end as 'Dem',
case 
when party='Republican' then count(*)
end as 'Rep',
case 
when party='Unaffiliated' then count(*)
end as 'Una',
case 
when party='Conservative Part,y' then count(*)
end as 'Cnv',
case 
when party='Green Party' then count(*)
end as 'gre',
case 
when party='Libertarian' then count(*)
end as 'Lib',
case 
when party='Natural Law Party' then count(*)
end as 'Nat',
case 
when party='Reform Party' then count(*)
end as 'rfp',
case 
when party='Socialist Party' then count(*)
end as 'ssp',
case 
when party='U.S. Constitution Party' then count(*)
end as 'Con'
from voter_list_nj7
where muni_id='".$muni_id."' and status='Active'
group by county, municipality, party) as t";


$registered_voters_query; # = mysqli_query($conn, $registered_voters_sql);

$last_date = "Jun 2023";

"select muni, 
case when type_of_vote='Election Day' then ' Election Day' else type_of_vote end vote_type, 
sum(registered_voters), sum(ballot_cast), sum(dem_votes), sum(rep_votes)
from election_results
where county='union'
group by muni, vote_type
order by muni, vote_type";

$party_turnout_sql = 
"select county, cd, election_date, muni, muni_id, ward, precinct, party,
sum(voters) voters, sum(registered_voters) registered_voters, sum(voters)/sum(registered_voters) turnout 
from
(
select a.county, a.cd, election_date, a.muni, a.muni_id, b.ward, a.precinct,
party_2 party, voters, registered_voters
from voter_history_by_date_precinct_party_view a
join party_affiliation_stats_view b 
on b.muni_id = a.muni_id and year(b.as_of) = year(a.election_date)
and b.party = a.party_2 and convert(b.precinct, int) = convert(a.precinct,int)
and (
if (a.ward is null, '00', convert(a.ward,int)) = convert(b.ward,int)
or if (b.ward is null, '00', convert(b.ward,int)) = convert(a.ward,int)
)
) as t
where cd='07' and muni_id='".$muni_id."' and year(election_date)='2022'
group by election_date, muni_id, party, ward, precinct
order by election_date, muni_id, ward, precinct, party";

$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

#echo $party_turnout_sql;

$party_affiliation_stats_sql = 
"select sum(dem)as dem, sum(rep) as rep, sum(una) as una, date_format(as_of, '%b %Y') as dt_label 
from party_affiliation_stats where muni_id = '".$muni_id."' group by as_of order by as_of";

$party_affiliation_stats_query = mysqli_query($conn, $party_affiliation_stats_sql);

$party_affiliation_stats_check_sql = 
"select 1 from party_affiliation_stats where muni_id = '".$muni_id."'";

$party_affiliation_stats_check_query = mysqli_query($conn, $party_affiliation_stats_check_sql);

?>


<html>
  <head>
    <title>NJ <?php echo $muni["muni"]." (".$muni["county"].")"; ?> Electoral Data</title>
	<script src="../sorttable.js"></script>
	<script src="../Chart.bundle.min.js"></script> <!-- chartjs.org -->
	<script src="../chartjs-plugin-datalabels.min.js"></script>	
	
	<script src="../jquery-3.3.1.min.js"></script>
	<script src="../Blob.min.js"></script>
	<script src="../xls.core.min.js"></script>	
	<!-- <script src="../FileSaver.min.js"></script> -->
	<script src="../tableexport.min.js"></script>

	<link href="../tableexport.css" rel="stylesheet">
	<link href="../bluecompass.css" rel="stylesheet">	
	
  </head>
  
<body>
<a href="../index.html">Home</a>
<a href="index.html" title="After 2020">After 2020</a>
<a href="cd.php?cd=<?php echo $muni["cd"]; ?>" title="Congressional District">CD</a>
<a href="ld.php?ld=<?php echo $muni["ld"]; ?>" title="Legislative District">LD</a>
<a href="county.php?county=<?php echo $county; ?>" title="County"><?php echo $county; ?> County</a>

<CENTER>
<H2>
<?php echo $muni["muni"]. " - ".$muni["county"]." County - CD".$muni["cd"]." - LD".$muni["ld"]; ?> 
</H2>
<H3>
<?php 
if($muni["partial"] == "Y") {
	echo "This municipality spans over Congressional District ".$muni["cd"]." and ".$muni["other_cd"]; 
}
?>
</H3>
</CENTER>

<BR>
<?php

/* if(!empty($muni["map_url"])) {
	echo "<a target='new' href='".$muni["map_url"]."'>Town Precincts Map</a>";
	//echo "&nbsp;<a href='../../munidata.php?muni_id=".$muni_id."&year=2018&type=Con'>Voters List</a>";
	echo "<br>";
	echo "<br>";
}

$phpRegData = [];

if(mysqli_num_rows($registered_and_eligible_voters_query) > 0) {
	echo "<table border='1' id='eli_voters'>";
	echo "<caption><b>Eligible and Registered Voters</b></caption>";
	echo "<tr><th>Eligible Voters</th><th>Registered Voters</th><th>% of Registered Voters</th></tr>";

	while($row = mysqli_fetch_array($registered_and_eligible_voters_query)) {
		$pct = $row["tot_registered_voters"] / $row["eligible_voters"];
		echo "<tr>";
		echo "<td align=right>".number_format($row["eligible_voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["tot_registered_voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($pct * 100, 2, ".", ",")."%</td>";
		echo "</tr>";
		$as_of = $row["as_of"];
	}
	echo "</table>";
	echo "<i>Notes: Eligible voters extracted from Census 2019 estimates";
	echo "<br>Registered voters provided by the county clerk as of ".$as_of;
	echo "<br>Numbers may be incorrect mainly because Census is an estimate and Census and County may use different town boundaries";
	echo "<br>Nevertheless the percentage of registered voters (registered/eligible) can help gauging areas of low voter registrations";
	echo "<br><br><br>";
}
 */?>

<?php
if($registered_voters_query and $eligible_voters and mysqli_num_rows($registered_voters_query) > 0) {
	echo "<table border='1' id='voters'>";
	echo "<caption><b>Eligible Voters and Registered Voters By Party</b></caption>";
	echo "<tr>";
	echo "<th>Eligible Voters</th><th>Tot Registered Voters</th></th><th>Tot Registered Voters %</th>";
	echo "<th>Dem</th><th>Rep</th><th>Una</th>";
	echo "</tr>";

	$total = array();

	while($row = mysqli_fetch_array($registered_voters_query)) {
		echo "<tr>";
		
		$tot_reg_voters = $row["Dem"]+$row["Rep"]+$row["Una"];
		echo "<td align=right>".number_format($eligible_voters["citizens_18_plus"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($tot_reg_voters, 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($tot_reg_voters / $eligible_voters["citizens_18_plus"] * 100, 2, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["Dem"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["Rep"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["Una"], 0, ".", ",")."</td>";

		#$last_values[0] = array("Dem"=>$row["Dem"], "Rep"=>$row["Rep"], "Una"=>$row["Una"]);
	}
	echo "</tr>";
	echo "</table>";
	echo "<i>Eligible voters extracted from American Community Survey (ACS - US Census) 2021 estimates".$as_of;
	echo "<br>";
	echo "<i>Registered voters extracted from the State Voter File as of ".$as_of;
	echo "</i>";
	echo "<br>";
	
}


# Standard party affiliation stats table and charts

$reg_labels = array();
$reg_values = array();

$col = mysqli_num_fields($party_affiliation_stats_query);

$voter_registrations_num_rows = mysqli_num_rows($party_affiliation_stats_check_query);
$num_rows = mysqli_num_rows($party_affiliation_stats_query);

$lastRegsStats = "[{'Dem':'1','Rep':'2','Una':'3'}]";
$RegsStats  = "[{'Dem':'1','Rep':'2','Una':'3'}]";

if($voter_registrations_num_rows > 0) {
	echo "<center><H3>Voter Registrations</H3></center>";
	echo "<br>";
	echo "<table border='1'>";
	#echo "<caption><b>Voter Registrations</b></caption>";

	$aff = Array("Dem", "Rep", "Una", "Oth");

	$y = 0;
	$tot = Array();
	$vals = Array();

	$dem_margin = Array();

	while($row = mysqli_fetch_array($party_affiliation_stats_query)) {
		#print_r($row);
		#echo "<br><br>";
		$last_date = $row["dt_label"];
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

	// trying to redo like it is above
	#$last_values[0] = array("Dem"=>$row["dem"], "Rep"=>$row["rep"], "Una"=>$row["una"]);
	
	echo "</table>";
	echo "<i>Source: County party affiliation statistics</i>";

	$reg_values3 = array();

	$t = count($reg_labels); // number of months for which there is registrations data 
	//echo "<br>t: ".$t;

	for ($r=0; $r < $t; $r++) {
		$rr = $t+$r;
		$rrr = ($t*2)+$r;
		$reg_values3[] = array("Month"=>$reg_values[$r]["Month"], "Dem"=>$reg_values[$r]["Dem"], "Rep"=>$reg_values[$rr]["Rep"], "Una"=>$reg_values[$rrr]["Una"]);
	}

	#$last_values2 = array();
	$last_values2[] = $last_values;

	$RegsStats = json_encode($reg_values3);

	//echo "<br>$RegsStats<br>";

	$lastRegsStats = "[{}]";
	if(count(last_values2) > 0) {
		#echo "assign to js ".$last_values2;
		$lastRegsStats = json_encode($last_values2);
	}
	#echo "<br>lastRegsStats: ".$lastRegsStats."<br>";

	echo "<BR>";
	echo "<BR>";
	echo "<table border='0'>";
	echo "<tr>";
	echo "<td valign='top'>";
	echo "<canvas id='regChart' width='400' height='300'></canvas>";
	echo "</td>";
	echo "<td width='50'></td>";
	echo "<td valign='top'>";
	echo "<canvas id='lastRegChart' width='260' height='260'></canvas>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

} 	#everything above only if there are party_affiliation_stats records

?>

<br>

<hr>

<table border="1" class="sortable" id="election_result_table">
<caption><b>Previous Elections Results</b></caption>
<tr title="Click to sort">
<th>Year</th><th>Office</th>
<!--<th>Ward</th><th>Precinct</th>-->
<th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>
<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th>
<th>Dem Candidate</th><th>Rep Candidate</th>
<th>Margin</th><th>Margin %</th>
</tr>
<?php
$labels = array();
$values = array();
$i = 0;

$reg_voters = 0;
$ballots_cast = 0;
$reg_voters = 0;
$reg_voters = 0;

while($row = mysqli_fetch_array($election_results_query)) {
	//$election_type = $row["office"];
	if($row["year"] % 2 == 1) {
		echo "<tr bgcolor='#ffffcc'>";
	} else {
		echo "<tr>";
	}
	echo "<td>".$row["year"]."</td>";
	echo "<td>".$row["office"]."</td>";
	#echo "<td align=right>".$row["ward"]."</td>";
	#echo "<td align=right>".$row["precinct"]."</td>";
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballot_cast"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["turnout_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["dem_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["rep_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td>".$row["dem_candidate"]."</td>";
	echo "<td>".$row["rep_candidate"]."</td>";
	
	
/* 	$dem_candidate = $row["dem_candidate"];
	if(empty($dem_candidate)) {
		$dem_candidate = $row["dem_candidate_2"];
	}
	echo "<td>".$dem_candidate."</td>";
	
	$rep_candidate = $row["rep_candidate"];
	if(empty($rep_candidate)) {
		$rep_candidate = $row["rep_candidate_2"];
	}
	echo "<td>".$rep_candidate."</td>";
 */	
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
	$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballot_cast"],
	"Dem_Votes"=>$row["dem_votes"], "Rep_Votes"=>$row["rep_votes"]);
	
}
echo "</table>";
echo "<i>Notes: Margin (or difference) in blue means Democratic advantage, in red Republican advantage</i>";
echo "<br>";
echo "<i>Source: State Election Results</i>";

$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";

?>

<BR>
<BR>
<canvas id="myChart" width="750" height="400"></canvas>
<BR>


</td>
</tr>
</table>

<?php

if(mysqli_num_rows($party_turnout_query) > 0) {
	echo "<br><br>";
	echo "<input type='text', id='precinct'>";
	echo "<input type='button' value='Search' id='search' onclick='search();'>";
	echo "<input type='button' value='Reset' id='reset' onclick='reset();'>";
	echo "<table border='1' class='sortable' id='party_turnout_table'>";
	echo "<caption><b>Party Turnout</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>County</th><th>CD</th><th>Election Date</th>";
	echo "<th>Muni</th><th>Ward</th><th>Precinct</th>";
	echo "<th>Party</th><th>voters</th><th>Registered Voters</th><th>Turnout</th>";
	echo "</tr>";
	while($row = mysqli_fetch_array($party_turnout_query)) {
		echo "<tr>";
		echo "<td>".$row["county"]."</td>";
		echo "<td>".$row["cd"]."</td>";
		echo "<td>".$row["election_date"]."</td>";
		echo "<td>".$row["muni"]."</td>";
		echo "<td>".$row["ward"]."</td>";
		echo "<td>".$row["precinct"]."</td>";
		echo "<td>".$row["party"]."</td>";
		echo "<td align='right'>".number_format($row["voters"], 0, ".", ",")."</td>";
		echo "<td align='right'>".number_format($row["registered_voters"], 0, ".", ",")."</td>";		
		echo "<td align='right'>".number_format($row["turnout"]*100, 2, ".", ",")."%</td>";
		echo "</tr>";
	}
}
echo "</table>";
?>


<?php
if(!empty($SubsStats)) {
	echo "<BR>";
	echo "<canvas id='precinctChart' width='750' height='400'></canvas>";
	echo "<BR>";
}
?>
<BR>

<script>

function search() {
	precinct = document.getElementById("precinct").value;
	//alert("Search " + precinct);
	table = document.getElementById("party_turnout_table");
	tr = table.getElementsByTagName("tr");

	// Loop through all table rows, and hide those who don't match the search query
	for (i = 1; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[5];
		//if(i < 3) {
		//	alert(td);
		//}
		if (td) {
			txtValue = td.textContent || td.innerText;
			if (txtValue.indexOf(precinct) > -1) {
				tr[i].style.display = "";
			} else {
				tr[i].style.display = "none";
			}
		}
	}		
}

function reset() {
	precinct = document.getElementById("precinct").value;
	//alert("Search " + precinct);
	table = document.getElementById("party_turnout_table");
	tr = table.getElementsByTagName("tr");

	// Loop through all table rows, and hide those who don't match the search query
	for (i = 1; i < tr.length; i++) {
		tr[i].style.display = "";
	}		
}

var data = <?php echo $SubsStats; ?>;

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
	scaleShowValues: true,
	scales: {
		xAxes: [{
			ticks: {
				autoSkip: false,
				maxRotation: 50,
				minRotation: 30
			}
		}]
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
            formatter: (value, ctx) => {
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

var lastRegData = <?php echo $lastRegsStats; ?>;

var lastDate = "<?php echo $last_date; ?>";

//alert(lastRegData);
//alert(lastDate);

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

var config3 = {
  type: 'pie',
  options: {
    title: {
      display: true,
      text: 'Latest Voter Registrations ' + lastDate
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

if(ctx3 != undefined) {
	var last_reg_chart = new Chart(ctx3, config3);
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

if(ctx2 != undefined) {
	var reg_chart = new Chart(ctx2, config2);
}

</script>

  </body>
</html>
<?php
ob_end_flush();
?>