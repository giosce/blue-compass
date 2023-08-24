<?php


include("../db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$county = $_GET["county"];
$cd_id = $_GET["cd"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];
 
$cd_id = str_replace("NJ","",$cd_id);


if($county != "Morris") {
	$sql_election_results = 
	"select sum(registered_voters) as registered_voters, sum(ballot_cast) as ballot_cast,
	sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, year,
	sum(ballot_cast)/sum(registered_voters) as turnout_pct,
	sum(dem_votes)/sum(ballot_cast) as dem_pct,
	sum(rep_votes)/sum(ballot_cast) as rep_pct
	from election_results
	where county='".$county."'
	group by year
	union ";
} else {
	$sql_election_results = 
	"select sum(registered_voters) as registered_voters, sum(ballot_cast)/2 as ballot_cast,
	sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, year,
	(sum(ballot_cast)/2)/sum(registered_voters) as turnout_pct,
	sum(dem_votes)/(sum(ballot_cast)/2) as dem_pct,
	sum(rep_votes)/(sum(ballot_cast)/2) as rep_pct
	from election_results
	where county='".$county."'
	group by year
	union ";
}	
$sql_election_results .= 
"select sum(c.registered_voters), sum(c.ballots_cast),
sum(b.dem_votes), sum(b.rep_votes), b.year,
sum(c.ballots_cast)/sum(c.registered_voters) as turnout_pct,
sum(dem_votes)/sum(c.ballots_cast) as dem_pct,
sum(rep_votes)/sum(c.ballots_cast) as rep_pct
from state_election_results b, state_ballots_cast c
where b.office='US House' and b.county='".$county."'
and c.muni_code = b.muni_code and c.year=b.year
group by b.year
order by year desc";


$query_election_results = mysqli_query($conn, $sql_election_results);


$sql5 = "select county, town, muni_id, ld, partial from municipal_list where cd=".$cd_id."  and county='".$county."' order by 1, 2";

$query5 = mysqli_query($conn, $sql5);

# need registrations by muni
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

$votes_by_town_query = mysqli_query($conn, $votes_by_town_sql);
// better put votes by town in a map by muni_id

#$party_turnout_sql = "select * from party_turnout_by_muni_2018_view where cd=x".$cd_id;

#$party_turnout_query = mysqli_query($conn, $party_turnout_sql);


$sql_pop_and_reg_voters =
"select * from acs_population a, party_affiliation_stats_muni_view b, municipal_list c
where b.muni_id = a.muni_code and year(as_of)='2022'
and b.muni_id = c.muni_id 
and c.cd='".$cd_id."' and c.county='".$county."'
order by a.muni_code, party";

$query_pop_and_reg_voters = mysqli_query($conn, $sql_pop_and_reg_voters);


$sql_muni_stats =
"select muni, muni_code, sum(registered_voters) as registered_voters, 
case when county='morris' then sum(ballot_cast)/2 else sum(ballot_cast) end as ballot_cast,
sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes
from election_results
where county='".$county."'  
group by muni order by muni";

$query_muni_stats = mysqli_query($conn, $sql_muni_stats);

$party_turnout_sql =
"select county, cd, election_date, muni, muni_id, party,
sum(voters) voters, sum(registered_voters) registered_voters, sum(voters)/sum(registered_voters) turnout 
from
(
select a.county, a.cd, election_date, a.muni, a.muni_id,
party_2 party, voters, registered_voters
from voter_history_by_date_muni_party_view a
join party_affiliation_stats_muni_view b 
on b.muni_id = a.muni_id and year(b.as_of) = year(a.election_date)
and b.party = a.party_2 and year(election_date)='2022'
) as t
where county='".$county."' and cd='".$cd_id."'
group by election_date, muni_id, party
order by election_date, muni_id, party";

$party_turnout_query = mysqli_query($conn, $party_turnout_sql);


if(debug == "y") {
	echo $sql5;
	echo "<br>";
	echo $sql_election_results;
	echo "<br>";
	echo $registrations_sql;
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
	<script src="../tableexport.min.js"></script>

	<link href="../tableexport.css" type="text/css" rel="stylesheet">
	<link href="../bluecompass.css" type="text/css" rel="stylesheet">
	
	<style type="text/css">	
		
		H2 {
			display: inline;
		}
		
	</style>
	
	<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
	<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>
	
  </head>
<body style="font-family:Arial">


<a href="../index.html">Home</a>
<a href="index.html" title="After 2020">After 2020</a>
<?php
echo "<a href='cd.php?cd=".$cd_id."' title='Congressional District'>".$cd_id."</a>";
?>
<!--<a href='stats.php'>District Statistics</a>-->
<br>
<center>

<H2>NJ CD <?php echo $cd_id; ?> <?php echo $county; ?> County Data</H2>

</center>


<?php
$towncount=mysqli_num_rows($query5);
?>
<BR>
The are <?php echo $towncount; ?> municipalities in <?php echo $county; ?> County that are part of New Jersey Congressional District <?php echo $cd_id; ?>.
<BR>
<br>
<?php
while($row5 = mysqli_fetch_array($query5)) {
	echo "<a href='muni.php?muni_id=".$row5["muni_id"]."'>".$row5["town"]."</a>, ";
	if(!in_array($row5["ld"], $lds)) {
		$lds[] = $row5["ld"];
	}
}
echo "<BR>";
echo "<BR>";

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
echo "<i style=\"color:red;\">Note: Temporarily, this is data for the Congressional District, not the County</i>";

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
	echo "<tr>";
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
		echo number_format(abs($margin_pct*100), 2, ".", ",")."%";
		echo "</td>";			
	} else {
		echo "<td></td>";
		echo "<td></td>";
	}
	
	echo "</tr>";
	$label = $row["year"];
	array_push($labels, $label);
	$values[] = array("Election"=>$label, "Registered_Voters"=>$row["registered_voters"], "Voted"=>$row["ballot_cast"],
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

if(mysqli_num_rows($query_muni_stats) > 0) {
	echo "<h3><a id='counties'>Municipalities</a></h3>";
	
	echo "The \"Municipalities Relevance\" chart shows how the different municipalities within NJ".$cd_id." and ".$county." County contribute to the 2022 General Election results.";
	echo "<br>";
	echo "<br>";
	
	$a_munis = array();
	$a_reg_voters = array();
	$a_turnout = array();
	$a_dem_votes = array();
	$a_rep_votes = array();
	
	$first_row = true;
	while($row = mysqli_fetch_array($query_muni_stats)) {
		#print_r($row);
		$muni = $row["muni"];
		$registered = $row["registered_voters"];
		$turnout = $row["ballot_cast"];
		$dem_votes = $row["dem_votes"];
		$rep_votes = $row["rep_votes"];
		if($first_row) {
			echo "<table border='1' class='sortable' id='muni_stats_table'>";
			echo "<caption><b>Municipalities Relevance ".$el_type." ".$el_year."</b></caption>";
			echo "<tr title='Click to sort'>";
			echo "<th>Muni</th>";
			echo "<th>Registered Voters</th><th>Ballot Cast</th>";
			echo "<th>Dem Votes</th><th>Rep Votes</th>";
			echo "</tr>";
		}
		$first_row = false;
		echo "<tr>";
		echo "<td style='white-space:nowrap;'><a href=muni.php?muni_id=".$row["muni_code"].">".$muni."</a></td>";
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
		array_push($a_munis, $muni);
		array_push($a_reg_voters, $registered);
		array_push($a_turnout, $turnout);
		array_push($a_dem_votes, $dem_votes);
		array_push($a_rep_votes, $rep_votes);
	}
	$js_reg_voters = json_encode($a_reg_voters);
	$js_turnout = json_encode($a_turnout);
	$js_dem_votes = json_encode($a_dem_votes);
	$js_rep_votes = json_encode($a_rep_votes);
	$js_munis = json_encode($a_munis);
	
	echo "<table>";
	echo "<tr>";
	echo "<td valign='top'><canvas id='MuniChart' width='1000' height='400'></canvas></td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR>";
}


echo "<table border='1' class='sortable' id='pop_and_voter_reg_table'>";
echo "<caption><b>Eligible Voters and Voter Registrations General Elections 2022 / end of year 2022</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th>Municipality</th><th>Citizens 18+</th>";
echo "<th style='min-width:50px'>Reg Dem</th>"; #<th style='min-width:50px'>Dem %</th>";
echo "<th style='min-width:50px'>Reg Rep</th>"; #<th style='min-width:50px'>Rep %</th>";
echo "<th style='min-width:50px'>Reg Una</th>"; #<th style='min-width:50px'>Una %</th>";
echo "<th style='min-width:50px'>Tot Reg Voters</th><th style='min-width:50px'>Tot Reg Voters %</th>";
echo "</tr>";


$muni = "";
$i = 0;
$reg_voters = 0;

while($row = mysqli_fetch_array($query_pop_and_reg_voters)) {
	if($debug=="y" and $i < 1) {
		print_r($row);
	}
	if($muni != $row["muni"]) {
		$muni = $row["muni"];
		echo "<tr>";
		echo "<td><a href='muni.php?muni_id=".$row["muni_code"]."'>".$muni."</a></td>";
		echo "<td align=right>".number_format($row["citizens_18_plus"], 0, ".", ",")."</td>";
		$reg_voters = 0;
	}
	$reg_voters += $row["registered_voters"];
	$i += 1;
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	#echo "<td align=right>".number_format($row["reg_voters_pct"], 2, ".", ",")."</td>";
	if($i == 3) {
		$i = 0;
		#$muni = $row["municipality"];
		echo "<td align=right>".number_format($reg_voters, 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($reg_voters/$row["citizens_18_plus"]*100, 2, ".", ",")."</td>";
		echo "</tr>";
	}
}
echo "</table>";
echo "<i>Source: County party affiliation statistics and ACS estimated population</i>";

echo "<br><br><br>";

if(mysqli_num_rows($party_turnout_query) > 0) {
	echo "<table border='1' class='sortable' id='pop_and_voter_reg_table'>";
	echo "<caption><b>Party Turnout as of 2022 General Elections</b></caption>";
	echo "<tr title='Click to sort'>";
	echo "<th>Election Date</th><th>Municipality</th>";
	echo "<th>Party</th><th>Registered Voters</th>";
	echo "<th>Voters</th><th>Turnout</th>";
	while($row = mysqli_fetch_array($party_turnout_query)) {
		echo "<tr>";
		echo "<td>".$row["election_date"]."</td>";
		#<a href=muni.php?muni_id=".$row["muni_id"].">".$muni."</a>
		echo "<td><a href=muni.php?muni_id=".$row["muni_id"].">".$row["muni"]."</a></td>";
		echo "<td>".$row["party"]."</td>";
		echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["voters"], 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($row["turnout"]*100, 2, ".", ",")." %</td>";
		echo "</tr>";
	}
	echo "</table>";
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

var ctx_cty = document.getElementById("MuniChart");

//alert("el" + ctx_cty);


if(ctx_cty != null) {
	
	var c = <?php echo $js_munis; ?>;
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
	  label: 'Ballot Cast',
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
				maxBarThickness: 18,
				ticks: {
					autoSkip: false
				}
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
			text: 'Muni Relevance House 2022',
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