<?php

// leg_dist data home page

include("../../db.php");

$district = $_GET["district"];
$town = $_GET["town"];
$muni_id = $_GET["muni_id"];
$year = $_GET["year"];
$el_type = $_GET["el_type"];
$precinct_id = $_GET["precinct_id"];
$ld_id = $_GET["ld"];


$muni_sql = "select * from municipal_list_new where muni_code = ".$muni_id; 

$muni_query = mysqli_query($conn, $muni_sql);

$muni = mysqli_fetch_array($muni_query);

$election_results_sql = "
select a.year, office, dem_votes, rep_votes, dem_candidate, rep_candidate,
registered_voters, ballots_cast, pct_ballots_cast,
ballots_cast / registered_voters as turnout, 
dem_votes / ballots_cast as dem_pct,
rep_votes / ballots_cast as rep_pct
from state_election_results a left join state_ballots_cast b
on b.muni_code = a.muni_code and b.year = a.year
where a.muni_code='".$muni_id."'
and office in ('NJ Senate','Assembly 1','Assembly 2','Governor')
order by a.year desc, if(office = 'NJ Senate', 'AB', if(office = 'GOvernor', 'AA', office))";

$election_results_query = mysqli_query($conn, $election_results_sql);

//echo "<br>".$election_results_sql."<br>";
?>

<html>
  <head>
    <title>NJ <?php echo $muni["muni"]." (".$muni["county"].")"; ?> Electoral Data</title>
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
<a href="../data/index4.php?ld=<?php echo $ld_id; ?>">District</a>
<CENTER>
<H2>
<?php echo $muni["muni"]." - ".$muni["county"]; ?> County
</H2>
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
	if($row["year"] == "2017") {
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
	echo "<td>".$row["dem_candidate"]."</td>";
	echo "<td>".$row["rep_candidate"]."</td>";
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