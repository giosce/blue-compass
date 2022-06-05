<?php

ini_set('display_errors','off');

include("../db.php");

$prop = parse_ini_file("../properties-a.ini");

$host2 = $prop["host"];
$username2 = $prop["username"];
$password2 = $prop["password"];
$db_name2 = $prop["db_name"];

$conn2 = mysqli_connect($host2, $username2, $password2, $db_name2);
if (!$conn2) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

$district = $_GET["district"];
$town = $_GET["town"];
$muni_id = $_GET["muni_id"];
$year = $_GET["year"];
$el_type = $_GET["el_type"];
$precinct_id = $_GET["precinct_id"];

$muni_sql = "select * from municipal_list_new where muni_code = ".$muni_id; 

$muni_query = mysqli_query($conn, $muni_sql);

$muni = mysqli_fetch_array($muni_query);

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
order by a.year desc, if(a.office = 'NJ Senate', 'AB', if(a.office = 'GOvernor', 'AA', a.office))";

$election_results_query = mysqli_query($conn, $election_results_sql);
//echo "<br>".$election_results_sql."<br>";


$registered_and_eligible_voters_sql = "
select a.county, b.muni, 
sum(una+dem+rep+cnv+con+gre+lib+nat+rfp+ssp) as tot_registered_voters,
b.muni_id, b.census_eligible_voters_2019 as eligible_voters, as_of
from registered_voters a, municipal_list_2022 b
where a.county = b.county and a.muni = b.muni_from_county
and census_eligible_voters_2019 is not null
and b.muni_id='".$muni_id."'
group by a.county, b.muni";

//echo "<br>".$registered_and_eligible_voters_sql."<br>";
$registered_and_eligible_voters_query = mysqli_query($conn2, $registered_and_eligible_voters_sql);

$registered_voters_sql = "
select a.county, b.muni, b.muni_id, ward, precinct, as_of,
una, dem, rep, cnv, con, gre, lib, nat, rfp, ssp
from registered_voters a, municipal_list_2022 b
where a.county = b.county and a.muni = b.muni_from_county
and b.muni_id='".$muni_id."'
order by ward, cast(precinct as unsigned)";

//echo "<br>".$registered_and_eligible_voters_sql."<br>";
$registered_voters_query = mysqli_query($conn2, $registered_voters_sql);


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
<a href="../njcongress.php">NJ Legislature</a> 
<a href="../ld/data/?ld=<?php echo $muni["ld"]; ?>" title="Legislative District">LD</a>
<a href="../cd/data/?cd=<?php echo $muni["cd"]; ?>" title="Congressional District">CD</a>

<CENTER>
<H2>
<?php echo $muni["muni"]." - ".$muni["county"]." County - LD".$muni["ld"]." - CD".$muni["cd"]; ?> 
</H2>
</CENTER>

<BR>
<?php

if(!empty($muni["map_url"])) {
	echo "<a target='new' href='".$muni["map_url"]."'>Town Precincts Map</a>";
	//echo "&nbsp;<a href='../../munidata.php?muni_id=".$muni_id."&year=2018&type=Con'>Voters List</a>";
	echo "<br>";
	echo "<br>";
}
?>


<?php
/*
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
	echo "<i>Source: ".$county." County Clerk Office</i>";
*/
$phpRegData = [];
?>

<!--
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
-->


<?php
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

if(mysqli_num_rows($registered_voters_query) > 0) {
	echo "<table border='1' id='voters'>";
	echo "<caption><b>Registered Voters By Party and Precinct</b></caption>";
	echo "<tr>";
	echo "<th>Ward</th><th>Precinct</th>";
	echo "<th>Una</th><th>Dem</th><th>Rep</th><th>Cnv</th><th>Con</th><th>Gre</th><th>Lib</th><th>Nat</th><th>Rfp</th><th>Ssp</th><th>Total</th>";
	echo "</tr>";

	$total = array();

	while($row = mysqli_fetch_array($registered_voters_query)) {
		echo "<tr>";
		if($row["ward"] != null) {
			echo "<td>".$row["ward"]."</td>";	
		} else {
			echo "<td></td>";
		}
		echo "<td>".$row["precinct"]."</td>";
		echo "<td align=right>".number_format($row["una"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["dem"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["rep"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["cnv"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["con"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["gre"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["lib"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["nat"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["rfp"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["ssp"], 0, ".", ",")."</td>";
		$row_tot = $row["una"] + $row["dem"] + $row["rep"] + $row["cnv"] + $row["con"] + 
		$row["gre"] + $row["lib"] + $row["nat"] + $row["rfp"] + $row["ssp"];
		echo "<td align=right>".number_format($row_tot)."</td>";
		$as_of = $row["as_of"];
		$total["una"] += $row["una"];
		$total["dem"] += $row["dem"];
		$total["rep"] += $row["rep"];
		$total["cnv"] += $row["cnv"];
		$total["con"] += $row["con"];
		$total["gre"] += $row["gre"];
		$total["lib"] += $row["lib"];
		$total["nat"] += $row["nat"];
		$total["rfp"] += $row["rfp"];
		$total["ssp"] += $row["ssp"];
	}
	echo "<tr style='font-weight:bold;'>";
	echo "<td colspan='2'>Total</td>";
	echo "<td align=right>".number_format($total["una"])."</td>";
	echo "<td align=right>".number_format($total["dem"])."</td>";
	echo "<td align=right>".number_format($total["rep"])."</td>";
	echo "<td align=right>".number_format($total["cnv"])."</td>";
	echo "<td align=right>".number_format($total["con"])."</td>";
	echo "<td align=right>".number_format($total["gre"])."</td>";
	echo "<td align=right>".number_format($total["lib"])."</td>";
	echo "<td align=right>".number_format($total["nat"])."</td>";
	echo "<td align=right>".number_format($total["rfp"])."</td>";
	echo "<td align=right>".number_format($total["ssp"])."</td>";
	$row_tot = $total["una"] + $total["dem"] + $total["rep"] + $total["cnv"] + $total["con"] + 
	$total["gre"] + $total["lib"] + $total["nat"] + $total["rfp"] + $total["ssp"];
	echo "<td align=right>".number_format($row_tot)."</td>";
	echo "</tr>";
	echo "</table>";
	echo "<i>Registered voters provided by the county clerk as of ".$as_of;
	echo "<br><br><br>";
}

?>

<table border="1" class="sortable" id="election_result_table">
<caption><b>Previous Elections Results</b></caption>
<tr title="Click to sort">
<th>Year</th><th>Election</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>
<th>Dem Votes</th><th>Dem Votes %</th><th>Rep Votes</th><th>Rep Votes %</th>
<th>Dem Candidate</th><th>Rep Candidate</th><th>Margin</th><th>Margin %</th>
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
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["ballots_cast"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["turnout"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["dem_pct"]*100, 2, ".", ",")." %</td>";
	echo "<td align=right>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["rep_pct"]*100, 2, ".", ",")." %</td>";
	
	$dem_candidate = $row["dem_candidate"];
	if(empty($dem_candidate)) {
		$dem_candidate = $row["dem_candidate_2"];
	}
	echo "<td>".$dem_candidate."</td>";
	
	$rep_candidate = $row["rep_candidate"];
	if(empty($rep_candidate)) {
		$rep_candidate = $row["rep_candidate_2"];
	}
	echo "<td>".$rep_candidate."</td>";
	
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

// registrations chart

/*
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
*/

</script>

  </body>
</html>
<?php
ob_end_flush();
?>