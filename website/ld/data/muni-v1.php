<?php

// leg_dist data home page

include("../../db.php");

$district = $_GET["district"];
$town = $_GET["town"];
$muni_id = $_GET["muni_id"];
$year = $_GET["year"];
$el_type = $_GET["el_type"];
$precinct_id = $_GET["precinct_id"];

// I think to sum all wards number per town, especially for the chart
$sql = "SELECT election_year, election_type_code, sum(registered_voters) as registered_voters, 
sum(ballots_cast) as ballots_cast, (sum(ballots_cast)/sum(registered_voters)) as turnout,
sum(dem_votes) as dem_votes, sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes) as rep_votes, sum(rep_votes)/sum(ballots_cast) as rep_pct
from turnout_precincts_tbl a 
where substr(precinct_id, 1, 4) = '".$muni_id."' 
and election_year > '2013'
group by election_year, election_type_code
order by election_year desc, election_type_code";

//if(election_type_code like 'Ass%', sum(ballots_cast)/2, sum(ballots_cast)) as ballots_cast, 
//if(election_type_code like 'Ass%', (sum(ballots_cast)/sum(registered_voters)/2), (ballots_cast/registered_voters)) 
//as turnout,


$query = mysqli_query($conn, $sql);


$sql2 = "SELECT precinct, a.election_year, a.election_type_code, registered_voters, 
ballots_cast, (ballots_cast/registered_voters) as turnout,
dem_votes, dem_votes/ballots_cast as dem_pct, 
rep_votes, rep_votes/ballots_cast as rep_pct,
dem_candidate, rep_candidate
from turnout_precincts_tbl a join municipal_list b
on b.ssn = substr(a.precinct_id, 1, 4)
left join candidates c
on c.election_year = a.election_year
and c.election_type_code = a.election_type_code
and c.ld = b.ld 
and c.cd = b.cd
where substr(precinct_id, 1, 4) = '".$muni_id."' 
and a.election_year > '2013'";

//if(a.election_type_code like 'Ass%', ballots_cast/2, ballots_cast) as ballots_cast, 
//if(a.election_type_code like 'Ass%', ballots_cast/registered_voters/2, ballots_cast/registered_voters) as turnout,

if(!empty($precinct_id)) {
	$end_str = substr($precinct_id, -2);
	if($end_str == '00') {
		$sql2 = $sql2." and precinct_id like '".substr($precinct_id, 0, 4)."%".$end_str."'";
	} else {
		$sql2 = $sql2." and precinct_id = '".$precinct_id."'";
	}
}
if(!empty($year)) {
	$sql2 = $sql2." and a.election_year = '".$year."'";
}

if(!empty($el_type)) {
	if($el_type == 'Town') {
		$sql2 = $sql2." and a.election_type_code like '".$el_type."%'";
	} else {
		$sql2 = $sql2." and a.election_type_code = '".$el_type."'";
	}
}

$sql2 = $sql2." order by a.election_year desc, a.election_type_code, precinct_id";

//echo "$sql2";

$query2 = mysqli_query($conn, $sql2);

//$sql4 = "select date_format(month_year, '%M %Y') as date, una, dem, rep, oth from monthly_registrations order by 1";

//$query4 = mysqli_query($conn, $sql4);

$query5 = mysqli_query($conn, "select county, town, ld from municipal_list where ssn=".$muni_id.";");

$row5 = mysqli_fetch_array($query5);
$town=$row5["town"];
$county = $row5["county"];
$ld_id = $row5["ld"];

$sql6 = "select distinct precinct, precinct_id from turnout_precincts_tbl where election_year = '2018' 
and precinct_id like '".$muni_id."%' order by 2";
//echo "$sql6";
$query6 = mysqli_query($conn, $sql6);



// for results chart (sum townw1 and townw2, w3 ...)
$sql7 = "SELECT election_year, substr(election_type_code, 1, 5) as election_type_code, 
sum(registered_voters) as registered_voters, 
sum(ballots_cast) as ballots_cast, (sum(ballots_cast)/sum(registered_voters)) as turnout,
sum(dem_votes) as dem_votes, sum(dem_votes)/sum(ballots_cast) as dem_pct,
sum(rep_votes) as rep_votes, sum(rep_votes)/sum(ballots_cast) as rep_pct
from turnout_precincts_tbl a 
where substr(precinct_id, 1, 4) = '".$muni_id."' 
and election_year > '2013' and election_type_code <> 'TownAtLarge' 
group by election_year, substr(election_type_code, 1, 5)
order by election_year desc, election_type_code";
// 2/6, changed group above from substr(... 4) to substr(...5) so to sum TownWx but not Townx (when there are 2 seats)

$query7 = mysqli_query($conn, $sql7);


// registrations
$reg_sql = "select str_to_date(as_of_date, '%m/%d/%Y'), ward_code, precinct_code,
dem, dem/total as dem_pct, rep, rep/total as rep_pct, 
una, una/total as una_pct, other, other/total as other_pct, total
from
(select as_of_date, muni_name, ward_code, precinct_code,
dem, rep, una,
cnv + gre + lib + nat + rfp + ssp + con as other,
cnv + dem + gre + lib + nat + rfp + rep + ssp + con + una as total
from registered_voters_precinct_copy 
where muni_id ='".$muni_id."' and publish_flag='Y'  
) as T
 order by 2, 3, 1";
 
$reg_query = mysqli_query($conn, $reg_sql);

// query to get dates of registrations
$reg_dates_sql = "select distinct get_month_short_name(month) as short_name, month, year 
from registered_voters_precinct_copy
where muni_id='".$muni_id."' and publish_flag='Y'
order by 3, 2";
$reg_dates_query = mysqli_query($conn, $reg_dates_sql);


$tot_reg_sql = "select str_to_date(as_of_date, '%m/%d/%Y') as as_of_date, 
muni_name, dem, dem/total as dem_pct, rep, rep/total as rep_pct, 
una, una/total as una_pct, other, other/total as other_pct, total
from
(select as_of_date, muni_name,  
sum(dem) as dem,  sum(rep) as rep, sum(una) as una,
sum(cnv) + sum(gre) + sum(lib) + sum(nat) + sum(rfp) + sum(ssp) + sum(con) as other,
sum(cnv) + sum(dem) + sum(gre) + sum(lib) + sum(nat) + sum(rfp) + sum(rep) +
sum(ssp) + sum(con) + sum(una) as total
from registered_voters_precinct_copy 
where muni_id ='".$muni_id."' 
and publish_flag='Y'
group by as_of_date, muni_id) as T
order by 1";

$tot_reg_query = mysqli_query($conn, $tot_reg_sql);

// query for precinct chart
if(!empty($precinct_id) and empty($year) and empty($el_type)) { 
	// for results chart (sum townw1 and townw2, w3 ...)
	$sql8 = "select election_year, election_type_code, 
	registered_voters, ballots_cast, (ballots_cast/registered_voters) as turnout,
	dem_votes, (dem_votes/ballots_cast) as dem_pct,
	rep_votes, (rep_votes/ballots_cast) as rep_pct
	from turnout_precincts_tbl a 
	where precinct_id = '".$precinct_id."' 
	and election_year > '2013'
	order by election_year, election_type_code";
	//echo "$sql8";
	$query8 = mysqli_query($conn, $sql8);
} else {
	$sql8 = '';
}

//$party_turnout_sql = "select * from party_turnout_by_precinct_2018
//where muni_id = '".$precinct_id."'";

$party_turnout_sql = "select * from party_turnout_by_precinct_2018_view
where ssn='".$muni_id."'
order by ward, precinct";

$party_turnout_query = mysqli_query($conn, $party_turnout_sql);

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
<a href="../ld<?php echo $ld_id; ?>.html">District</a>
<a href="../data?ld=<?php echo $ld_id; ?>">District Data</a>
<CENTER>
<H2><?php echo "$town"; echo " - "; echo "$county"; ?></H2>
</CENTER>

<BR>
<?php
switch($muni_id) {
	case "2001":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/03/Berkeley_Heights_BOE.png";
		break;
	case "2003":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2018/07/Cranford_BOE-1.png";
		break;
	case "2006":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/07/Garwood_BOE.png";
		break;	
	case "2008":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/07/Kenilworth_BOE.png";
		break;	
	case "2010":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/07/Mountainside_BOE.png";
		break;	
	case "2011":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2018/07/New_Providence_BOE-1.png";
		break;	
	case "2015":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2017/08/Roselle_Park_BOE.png";
		break;	
	case "2017":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/07/Springfield_BOE.png";
		break;	
	case "2018":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2018/07/Summit_BOE-1.png";
		break;	
	case "2020":
		$map_link = "http://www.unioncountyvotes.com/wp-content/uploads/2016/07/Westfield_BOE.png";
		break;	
}

if(!empty($map_link)) {
	echo "<a target='new' href='".$map_link."'>Town Precincts Map</a>";
	echo "&nbsp;<a href='../../munidata.php?muni_id=".$muni_id."&year=2018&type=Con'>Voters List</a>";
	echo "<br>";
	echo "<br>";
}
?>


<?php
$dates = array();
$reg_values = array();
$last_date = "";

$cols = 0;
echo "<table border='1'>";
echo "<caption><B>Voter Registrations</B></caption>";
	echo "<tr>";
	echo "<th class='headcol'>&nbsp;</th>";
	while($r = mysqli_fetch_array($reg_dates_query)) {
		$reg_date = $r["short_name"]." ".$r["year"];
		echo "<th style='border-left:2px solid #000' colspan=7 class='fixed freeze_vertical'>".$reg_date."</th>";
		$cols++;
		$last_date = $reg_date;
	}
	echo "</tr>";
	echo "<tr>";
	echo "<th class='headcol'>Precinct</th>";
	while ($cols > 0) {
		$cols--;
		echo "<th style='border-left:2px solid #000'>Dem</th><th>Dem %</th><th>Rep</th><th>Rep %</th>";
		echo "<th>Una</th><th>Una %</th><th>Total</th>";
	}
	echo "</tr>";
	$prec = "";
	while($reg_row = mysqli_fetch_array($reg_query)) {
		//if(!empty($reg_row["ward_code"])) {
			$precinct = $reg_row["ward_code"]."-".$reg_row["precinct_code"];
		//} else {
			//$precinct = $reg_row["precinct_code"];
		//}
		if($prec != $precinct) {
			if($prec != "") {
				echo "</tr>";
			}
			$period = 0;
			echo "<tr title='".$precinct."'>";
			echo "<td style='white-space: nowrap' class='headcol'>".$precinct."</td>";
			echo "<td align=right style='color:blue; border-left:2px solid #000'>".number_format($reg_row["dem"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap; color:blue'>".number_format($reg_row["dem_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right style='color:red'>".number_format($reg_row["rep"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap; color:red'>".number_format($reg_row["rep_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right>".number_format($reg_row["una"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap'>".number_format($reg_row["una_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right style='font-weight:bold'>".number_format($reg_row["total"], 0, ".", ",")."</td>";			
			$prec = $reg_row["ward_code"]."-".$reg_row["precinct_code"];
		} else {
			$period++;
			
			$dem_diff = $reg_row["dem"] - $prev_dem;
			$dem_pct_diff = $reg_row["dem_pct"] - $prev_dem_pct;
			$rep_diff = $reg_row["rep"] - $prev_rep;
			$rep_pct_diff = $reg_row["rep_pct"] - $prev_rep_pct;
			$una_diff = $reg_row["una"] - $prev_una;
			$una_pct_diff = $reg_row["una_pct"] - $prev_una_pct;
			$total_diff = $reg_row["total"] - $prev_total;
			
			echo "<td align=right style='color:blue; border-left:2px solid #000' title=$dem_diff>".number_format($reg_row["dem"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap; color:blue' title=$dem_pct_diff>".number_format($reg_row["dem_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right style='color:red' title=$rep_diff>".number_format($reg_row["rep"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap; color:red' title=$rep_pct_diff>".number_format($reg_row["rep_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right title='$una_diff'>".number_format($reg_row["una"], 0, ".", ",")."</td>";
			echo "<td align=right style='white-space:nowrap' title=$una_pct_diff>".number_format($reg_row["una_pct"]*100, 2, ".", ",")." %</td>";
			echo "<td align=right style='font-weight:bold' title='$total_diff'>".number_format($reg_row["total"], 0, ".", ",")."</td>";

		}
		$prev_dem = $reg_row["dem"];
		$prev_dem_pct = $reg_row["dem_pct"];
		$prev_rep = $reg_row["rep"];
		$prev_rep_pct = $reg_row["rep_pct"];
		$prev_una = $reg_row["una"];
		$prev_una_pct = $reg_row["una_pct"];
		$prev_total = $reg_row["total"];			
	}
	echo "</tr>";
	
	echo "<tr title='Total'>";
	echo "<td class='headcol' style='font-weight:bold'>Total</td>";
	$period = 0;
	while($tot_reg_row = mysqli_fetch_array($tot_reg_query)) {
		$dem_diff = "";
		$rep_diff = "";
		$una_diff = "";
		$total_diff = "";
		
		if(!empty($chart_values[$period-1])) {
			$dem_diff = $tot_reg_row["dem"] - $chart_values[$period-1]["dem"];
			$rep_diff = $tot_reg_row["rep"] - $chart_values[$period-1]["rep"];
			$una_diff = $tot_reg_row["una"] - $chart_values[$period-1]["una"];
			$total_diff = $tot_reg_row["total"] - $chart_values[$period-1]["total"];
		}		
		echo "<td align=right style='color:blue; font-weight:bold; border-left:2px solid #000' title=$dem_diff>".number_format($tot_reg_row["dem"], 0, ".", ",")."</td>";
		echo "<td align=right style='white-space:nowrap; color:blue; font-weight:bold'>".number_format($tot_reg_row["dem_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right style='color:red; font-weight:bold' title=$rep_diff>".number_format($tot_reg_row["rep"], 0, ".", ",")."</td>";
		echo "<td align=right style='white-space:nowrap; color:red; font-weight:bold'>".number_format($tot_reg_row["rep_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right style='font-weight:bold' title=$una_diff>".number_format($tot_reg_row["una"], 0, ".", ",")."</td>";
		echo "<td align=right style='white-space:nowrap; font-weight:bold'>".number_format($tot_reg_row["una_pct"]*100, 2, ".", ",")." %</td>";
		echo "<td align=right style='font-weight:bold' title=$total_diff>".number_format($tot_reg_row["total"], 0, ".", ",")."</td>";
		$chart_values[] = array("label"=>$tot_reg_row["as_of_date"], "dem"=>$tot_reg_row["dem"], "rep"=>$tot_reg_row["rep"], "una"=>$tot_reg_row["una"], "total"=>$tot_reg_row["total"]);
		$period++;
		// I only want the latest set of data, so overwriting at 0 position
		$last_values[0] = array("Dem"=>$tot_reg_row["dem"], "Rep"=>$tot_reg_row["rep"], "Una"=>$tot_reg_row["una"]);
	}
	echo "</tr>";
	echo "</table>";
	
	$phpRegData = json_encode($chart_values);	

	$LastRegsStats = json_encode($last_values);	

	//echo "<br>$LastRegsStats<br>";
	
	//echo "<br>$last_date<br>";

?>
<i>Source: <?php echo $county; ?> County Clerk Office</i>

<BR>
<br>
<table border="0">
<tr>
<td valign="top">
<canvas id='registrationChart' width='650' height='350'></canvas>
</td>
<td width="50"></td>
<td valign="top">
<canvas id="lastRegChart" width="260" height="260"></canvas>
</td>
</tr>
</table>
<br>
		
<table border="1" class="sortable" id="election_result_table">
<caption><b>Previous Elections Results</b></caption>
<tr title="Click to sort">
<th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>
<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th><th>Dem Margin</th>
</tr>
<?php
$labels = array();
$values = array();
$i = 0;

$reg_voters = 0;
$ballots_cast = 0;
$reg_voters = 0;
$reg_voters = 0;

while($row = mysqli_fetch_array($query)) {
	$election_type = $row["election_type_code"];
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
}
?>
</table>
<i>Source: <?php echo $county; ?> County Clerk Office</i>

<?php

while($row7 = mysqli_fetch_array($query7)) {
		$label = $row7["election_year"]." ".$row7["election_type_code"];
		array_push($labels, $label);
		$values[] = array("Election"=>$label, "Registered_Voters"=>$row7["registered_voters"], "Voted"=>$row7["ballots_cast"],
		"Dem_Votes"=>$row7["dem_votes"], "Rep_Votes"=>$row7["rep_votes"]);
}
$SubsStats = json_encode(array_reverse($values));
//echo "$SubsStats";

// precinct data
$PrecStats='null';
if(!empty($query8)) {
	while($row8 = mysqli_fetch_array($query8)) {
			$label = $row8["election_year"]." ".$row8["election_type_code"];
			//array_push($labels, $label);
			$prec_values[] = array("Election"=>$label, "Registered_Voters"=>$row8["registered_voters"], "Voted"=>$row8["ballots_cast"],
			"Dem_Votes"=>$row8["dem_votes"], "Rep_Votes"=>$row8["rep_votes"]);
	}
	//print_r($prec_values);
	$PrecStats = json_encode($prec_values);
	//echo "$PrecStats";
}

?>

<BR>
<BR>
<canvas id="myChart" width="750" height="400"></canvas>
<BR>

<table border="1" class="sortable">
<caption><B>Party Turnout Congress Election 2018</B></caption>
<tr>
<th>Town</th><th>Ward - Precinct</th><th>Reg Dem</th><th>Dem Voters</th><th>Dem Turnout %</th>
<th>Reg Rep</th><th>Rep Voters</th><th>Rep Turnout %</th><th>Reg Una</th><th>Una Voters</th><th>Una Turnout %</th>
</tr>
<?php
while($row6 = mysqli_fetch_array($party_turnout_query)) {
	echo "<tr>";
	echo "<td>".$row6["town"]."</td>";
	echo "<td>".$row6["ward"]." - ".$row6["precinct"]."</td>";
	echo "<td align=right>".number_format($row6["reg_dem"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["dem_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["turnout_dem"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row6["reg_rep"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["rep_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["turnout_rep"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row6["reg_una"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["una_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row6["turnout_una"]*100, 2, ".", ",")." %</td>";
	echo "</tr>";
}
?>
</table>
<i>Source: NJ State Division of Elections Office</i>
<br>
<br>
<table>
<tr valign="top">
<td>
<table border="1" class="sortable" id="election_result_table">
<caption><b>Previous Elections Results Per Precinct</b></caption>
<tr title="Click to sort">
<th>Precint</th><th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>
<th>Dem Candidate</th><th>Dem Votes</th><th>Dem Votes %</th><th>Rep Candidate</th><th>Rep Votes</th><th>Rep Votes %</th>
<th>Dem Margin</th>
</tr>
<?php
$labels = array();
$values = array();
$i = 0;
while($row2 = mysqli_fetch_array($query2)) {
	echo "<tr>";
	echo "<td nowrap>".$row2["precinct"]."</td>";
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
?>
</table>
<i>Source: <?php echo $county; ?> County Clerk Office</i>
</td>

<a id="precincts">

<td valign="top">
<table border=0>
<?php
echo "<form action='muni.php#precincts'>";
echo "<input type='hidden' id='muni_id' name='muni_id' value=".$muni_id.">";
?>
<tr><th>Select</th></tr>
<tr>
<td>Precinct</td>
<td>
<select name="precinct_id" onchange="this.form.submit();">
  <?php 
  
	if(empty($precinct_id)) {
		echo "<option selected='selected' value=''></option>";
	} else {
		echo "<option value=''></option>";
	}
	while($row6 = mysqli_fetch_array($query6)) {
		
		if($precinct_id==$row6["precinct_id"]) {
			echo "<option selected='selected' value='".$row6["precinct_id"]."'>".$row6["precinct"]."</option>";
		} else {
			echo "<option value='".$row6["precinct_id"]."'>".$row6["precinct"]."</option>";
		}
	}
	
  ?>
</select>
</td>
</tr>
<tr>
<td>Year</td>
<td>
<select name="year" onchange="this.form.submit()">
  <?php 
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
  ?>
<tr>
<td>Election Type</td>
<td>
<select name="el_type" onchange="this.form.submit();">
  <?php 
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
	if($el_type=="Town") {
		echo "<option selected='selected' value='Town'>Town</option>";
	} else {
		echo "<option value='Town'>Town</option>";
	}
  ?>
</select>
</td>
</tr>

</select>
</td>
</tr>
</table>
</td>
</tr>
</table>

<?php
if(!empty($SubsStats)) {
	echo "<BR>";
	echo "<canvas id='precinctChart' width='750' height='400'></canvas>";
	echo "<BR>";
}
?>
<BR>

<script>

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
				autoSkip: false
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

// registrations chart

var dataReg = <?php echo $phpRegData; ?>;

if(typeof dataReg != "undefined" && dataReg != ' ') {
var ctxRegChart = document.getElementById("registrationChart");

var labels = dataReg.map(function(e) {
   return e.label;
});
var dataReg1 = dataReg.map(function(e) {
   return e.dem;
});
var dataReg2 = dataReg.map(function(e) {
   return e.rep;
});
var dataReg3 = dataReg.map(function(e) {
   return e.una;
});
var dataReg4 = dataReg.map(function(e) {
   return e.total;
});


var configRegChart = {
   type: 'line',
  options: {
    title: {
      display: true,
      text: 'Voter Registrations'
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
            formatter: (value, ctxRegChart) => {
                return "";
            }
        }
    }		
  },
  data: {
      labels: labels,
      datasets: [
	    {
         label: 'Democratic',
         data: dataReg1,
		 borderColor: "#335DFF",
		 lineTension: 0,
		 fill: false
        },
	    {
         label: 'Republican',
         data: dataReg2,
		 borderColor: "#FF0000",
		 lineTension: 0,
		 fill: false
        },
	    {
         label: 'Unaffiliated',
         data: dataReg3,
		 borderColor: "#839192",
		 lineTension: 0,
		 fill: false
        }
	  ]
   }
};

var chart = new Chart(ctxRegChart, configRegChart);
}

// precinct chart
var prec_data = <?php echo $PrecStats; ?>;


var prec = "<?php echo substr($precinct_id,4); ?>";
var chart_title2 = "";
if(typeof prec != "undefined" && prec != 'null' && prec != ' ' && prec != null) {
	chart_title2 =  '  -  ' + prec;
}

if(typeof prec_data != "undefined" && prec_data != 'null' && prec_data != ' ' && prec_data != null) {
var prec_ctx = document.getElementById("precinctChart");

var labels = prec_data.map(function(e) {
   return e.Election;
});
var data1 = prec_data.map(function(e) {
   return e.Registered_Voters;
});
var data2 = prec_data.map(function(e) {
   return e.Voted;
});
var data3 = prec_data.map(function(e) {
   return e.Dem_Votes;
});
var data4 = prec_data.map(function(e) {
   return e.Rep_Votes;
});


var prec_config = {
   type: 'line',
  options: {
    title: {
      display: true,
      text: 'Last Elections Turnout and Results' + chart_title2
    },
    responsive: false,
	animation: {
      duration: 0
    },
	scaleShowValues: true,
	scales: {
		xAxes: [{
			ticks: {
				autoSkip: false
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
            formatter: (value, prec_ctx) => {
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
         label: 'Voted',
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

var chart = new Chart(prec_ctx, prec_config);

}

var lastRegData = <?php echo $LastRegsStats; ?>;

var lastDate = "<?php echo $last_date; ?>";

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
      text: 'Voter Registrations ' + lastDate
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