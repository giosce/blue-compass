<?php

include("db.php");

//$sql="SELECT `year_month`, ld, dem, rep, una,  gre+lib+rfp+con+nat+cnv+ssp as oth
//FROM monthly_registrations where publish='Y' order by ld, `year_month`";

$sql = "select a.*, b.last_color from leg_dist_pop_regist_and_pct a 
join districts_info b on a.ld = substr(b.ld, 5, 2) 
where `year_month` not like '2018%'
order by a.ld, `year_month`";

$query = mysqli_query($conn, $sql);

echo "<BR>";
echo "$sql";

//$sql2 = "select `year_month`, sum(vot_pop_2017), sum(dem), sum(rep), sum(una), sum(tot),
//(sum(dem)+sum(rep)+sum(una)+sum(oth))/sum(vot_pop_2017) as reg_pct,
//sum(dem)/sum(tot) as dem_pct, sum(rep)/sum(tot) as rep_pct, sum(una)/sum(tot) as una_pct
//from leg_dist_pop_regist_and_pct 
//group by `year_month` 
//order by `year_month`";

//$sql3 = "select * from voter_registrations_pct a 
//join districts_info b on a.ld = b.ld
//where cd = '' order by year_month_reg desc, a.ld";

//$query3 = mysqli_query($conn, $sql3);

?>

<html>
  <head>
    <title>Blue Compass - Electoral Data</title>
	<script src="../../sorttable.js"></script>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
tr:nth-child(even) {background: #DDD}
tr:nth-child(odd) {background: #FFF}

td {
  text-align: right;
  padding-left: 3px;
  padding-right: 3px;
}

table.myTable tr{
  bottom-border:none;
}

table { 
  font-size: 14px; 
}

@header_background_color: red;
@header_text_color: #FDFDFD;
@alternate_row_background_color: #DDD;

@table_width: 750px;
@table_body_height: 300px;

.fixed_headers {
  width: @table_width;
  table-layout: fixed;
  
  th { text-decoration: underline; }
  th, td {
    padding: 5px;
    text-align: left;
	border-left: 1px solid black;
  }
  
  thead {
    background-color: @header_background_color;
    color: @header_text_color;
    tr {
      display: block;
      position: relative;
    }
  }

  tbody {
    display: block;
    overflow: auto;
    width: 100%;
    height: @table_body_height;
    tr:nth-child(even) {
      background-color: @alternate_row_background_color;
    }
  }
}

tbody {
    display:block;
    height:500px;
    overflow:auto;
}
thead, tbody tr {
    display:table;
    width:100%;
    table-layout:fixed;/* even columns width , fix width of table too*/
}
thead {
    width: calc( 100% - 1em )/* scrollbar is average 1em/16px width, remove it from thead width */
}

th:last-child { padding-right:16px; }

tfoot {
  position: absolute;
  font-weight: bold;
  td:last-child { padding-right:16px; }
}


</style>	

<script src="jquery-3.3.1.min.js"></script>
<script lang="javascript" src="xlsx.full.min.js"></script>
<script lang="javascript" src="FileSaver.min.js"></script>

<script src="sorttable.js"></script>
<script src="Chart.bundle.min.js"></script> <!-- chartjs.org -->	
<script src="chartjs-plugin-datalabels.min.js"></script>	

<script src="Blob.min.js"></script>
<script src="xls.core.min.js"></script>	
<script src="tableexport.min.js"></script>
	
</head>
<body>

<a href="../index.html">Home</a>
<a href="../njcongress.html">NJ Congress</a>

<CENTER>
<BR>
<H2>Voter Registrations By NJ Legislative District</H2>
</CENTER>
<?php
$ld = "";
$dt = "";
$new_rows = array();
while($row = mysqli_fetch_array($query)) {
	//echo "<br>";
	//print_r($row);
	
	if($ld == "") {
		$dates = array();
	}
		
	if($ld != $row["ld"]) {
		$ld = $row["ld"];
		$new_rows[$ld] = array("ld"=>$row["ld"]);
	}
	
	if($dt != $row["year_month"]) {
		$dt = $row["year_month"];
		$new_rows[$ld][$dt] = array("dem"=>$row["dem"], "rep"=>$row["rep"], "una"=>$row["una"], "oth"=>$row["oth"], "tot"=>$row["tot"], "pct"=>$row["pct"], "vot_pop_2017"=>$row["vot_pop_2017"], "color"=>$row["last_color"]);
		if(!in_array($dt, $dates)) {
			$dates[] = $dt;
			//array_push($dates, $row["year_month"]);
		}
	}
	
	//echo "<br>new row in loop for ld: ".$ld." ";
	//print_r($new_row);
	//echo "<br>";
}

//echo "<br><br>";
//print_r($new_rows);

//echo "<br><br>";
//foreach($new_rows as $r) {
//	print_r($r);
//	echo "<br>";
//}

//echo "<br><br>";
//print_r($dates);

echo "<div style='overflow:auto; overflow-x:auto; overflow-y:auto;'>";
//echo "<table class=myTable>";
echo "<table border=1 id='data-table'>";
//echo "<table class=fixed_headers>";

echo "<thead>";
echo "<tr>";
echo "<th colspan=2></th>";
$i=0;
foreach($dates as $d) {
	if($i == 0) {
		echo "<th colspan=5>".$d."</th>";
	} elseif ($i == count($dates)-1) { // last date
		echo "<th colspan=12>".$d."</th>";
	} else {
		echo "<th colspan=10>".$d."</th>";
	}
	$i++;
}
echo "</tr>";

echo "<tr>";
echo "<th>District</th>";
echo "<th>Pop 18yo and older</th>";

$i=0;
foreach($dates as $d) {
	echo "<th>Dem</th>";
	if($i > 0) {
		echo "<th>Change</th>";
	}
	echo "<th>Rep</th>";
	if($i > 0) {
		echo "<th>Change</th>";
	}
	echo "<th>Una</th>";
	if($i > 0) {
		echo "<th>Change</th>";
	}
	echo "<th>Other</th>";
	if($i > 0) {
		echo "<th>Change</th>";
	}
	echo "<th>Total</th>";
	if($i > 0) {
		echo "<th>Change</th>";
	}
	if ($i == count($dates)-1) { // last date
		echo "<th>% Of Pop</th>";
	}
	$i++;
}
echo "<th>Dem Reg Marg</th>";
echo "</tr>";

echo "</thead>";

echo "<tbody>";

$tot_tot = array();
$vot_pop = 0;
foreach($new_rows as $r) {
	//print_r($r);
	//echo "color: ".$r["color"];
	echo "<tr onclick='select(this);' style='cursor:default;'>";
	$i=0;
	foreach($dates as $d2) {
		if($i == 0) {
			echo "<td align=left style='color:".$r[$d2]["color"].";'>".$r["ld"]."</td>";
			echo "<td style='left-padding:20px;'>".number_format($r[$d2]["vot_pop_2017"], 0, ".", ",")."</td>";
			$vot_pop += $r[$d2]["vot_pop_2017"];
		}
		$dem = $r[$d2]["dem"];
		echo "<td>".number_format($dem, 0, ".", ",")."</td>";
		if($i > 0 and $dem > 0) {
			$ch = $dem - $prev["dem"];
			if($ch != "") {
				echo "<td>".number_format($ch, 0, ".", ",")."</td>";
			} else {
				echo "<td>a</td>";
			}
		}
		
		$rep = $r[$d2]["rep"];
		echo "<td>".number_format($rep, 0, ".", ",")."</td>";
		if($i > 0 and $rep > 0) {
			$ch = $rep - $prev["rep"];
			//if($ch != 0) {
				echo "<td>".number_format($ch, 0, ".", ",")."</td>";
			//} else {
				//echo "<td>b</td>";
			//}
		}
		
		$una = $r[$d2]["una"];
		echo "<td>".number_format($una, 0, ".", ",")."</td>";
		if($i > 0 and $una > 0) {
			$ch = $una - $prev["una"];
			echo "<td>".number_format($ch, 0, ".", ",")."</td>";
		}
		
		$oth = $r[$d2]["oth"];
		echo "<td>".number_format($oth, 0, ".", ",")."</td>";
		if($i > 0 and $oth > 0) {
			$ch = $oth - $prev["oth"];
			echo "<td>".number_format($ch, 0, ".", ",")."</td>";
		}
		
		$tot = $r[$d2]["tot"];
		echo "<td>".number_format($tot, 0, ".", ",")."</td>";
		if($i > 0 and $tot > 0) {
			$ch = $tot - $prev["tot"];
			echo "<td>".number_format($ch, 0, ".", ",")."</td>";
		}

		if ($i == count($dates)-1) { // last date
			$pct = $r[$d2]["pct"];
			echo "<td>".number_format($pct*100, 2, ".", ",")."%</td>";
		}
				
		$prev = array("dem"=>$dem, "rep"=>$rep, "una"=>$una, "oth"=>$oth, "tot"=>$tot);

		$tot_dem = $tot_tot[$d2]["tot_dem"] + $dem;
		$tot_rep = $tot_tot[$d2]["tot_rep"] + $rep;
		$tot_una = $tot_tot[$d2]["tot_una"] + $una;
		$tot_oth = $tot_tot[$d2]["tot_oth"] + $oth;
		$tot_tot[$d2] = array("tot_dem"=>$tot_dem, "tot_rep"=>$tot_rep, "tot_una"=>$tot_una, "tot_oth"=>$tot_oth);
		
		$dem_margin = $dem - $rep;
		$i++;
	} // end dates for one district
	if($dem_margin > 0) {
		echo "<td style='color:blue;'>";
	} else {
		echo "<td style='color:red;'>";
	}
	echo number_format($dem_margin, 0, ".", ",")."</td>";
	echo "</tr>";
}

echo "</tbody>";

echo "<tfoot border=1>";

$chart_data = array();
/*
$dt = "2018-01";

echo "<tr onclick='select(this);' style='cursor: default;'>";
echo "<td style='padding-right: 20px;'>Total</td>";
echo "<td>".number_format($vot_pop, 0, ".", ",")."</td>";
$t_dem = $tot_tot[$dt]["tot_dem"];
$t_rep = $tot_tot[$dt]["tot_rep"];
$t_una = $tot_tot[$dt]["tot_una"];
$t_oth = $tot_tot[$dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;
echo "<td>".number_format($t_dem, 0, ".", ",")."</td>";
echo "<td>".number_format($t_rep, 0, ".", ",")."</td>";
echo "<td>".number_format($t_una, 0, ".", ",")."</td>";
echo "<td>".number_format($t_oth, 0, ".", ",")."</td>";
echo "<td>".number_format($t_tot, 0, ".", ",")."</td>";

$chart_data[0] = array($dt, "Dem"=>$t_dem, "Rep"=>$t_rep, "Una"=>$t_una);

$prev_tot = $t_tot;
$prev_dt = $dt;
$dt = "2018-11";

$t_dem = $tot_tot[$dt]["tot_dem"];
$t_rep = $tot_tot[$dt]["tot_rep"];
$t_una = $tot_tot[$dt]["tot_una"];
$t_oth = $tot_tot[$dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;

$chart_data[1] = array($dt, "Dem"=>$t_dem, "Rep"=>$t_rep, "Una"=>$t_una);

$dem_change = $t_dem - $tot_tot[$prev_dt]["tot_dem"];
$rep_change = $t_rep - $tot_tot[$prev_dt]["tot_rep"];
$una_change = $t_una - $tot_tot[$prev_dt]["tot_una"];
$oth_change = $t_oth - $tot_tot[$prev_dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;
$tot_change = $t_tot - $prev_tot;

echo "<td>".number_format($t_dem, 0, ".", ",")."</td>";
echo "<td>".number_format($dem_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_rep, 0, ".", ",")."</td>";
echo "<td>".number_format($rep_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_una, 0, ".", ",")."</td>";
echo "<td>".number_format($una_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_oth, 0, ".", ",")."</td>";
echo "<td>".number_format($oth_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_tot, 0, ".", ",")."</td>";
echo "<td>".number_format($tot_change, 0, ".", ",")."</td>";

$prev_tot = $t_tot;
$prev_dt = $dt;
*/
$dt = "2019-01";

$t_dem = $tot_tot[$dt]["tot_dem"];
$t_rep = $tot_tot[$dt]["tot_rep"];
$t_una = $tot_tot[$dt]["tot_una"];
$t_oth = $tot_tot[$dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;

$chart_data[2] = array($dt, "Dem"=>$t_dem, "Rep"=>$t_rep, "Una"=>$t_una);

$dem_change = $t_dem - $tot_tot[$prev_dt]["tot_dem"];
$rep_change = $t_rep - $tot_tot[$prev_dt]["tot_rep"];
$una_change = $t_una - $tot_tot[$prev_dt]["tot_una"];
$oth_change = $t_oth - $tot_tot[$prev_dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;
$tot_change = $t_tot - $prev_tot;

echo "<td>".number_format($t_dem, 0, ".", ",")."</td>";
echo "<td>".number_format($dem_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_rep, 0, ".", ",")."</td>";
echo "<td>".number_format($rep_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_una, 0, ".", ",")."</td>";
echo "<td>".number_format($una_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_oth, 0, ".", ",")."</td>";
echo "<td>".number_format($oth_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_tot, 0, ".", ",")."</td>";
echo "<td>".number_format($tot_change, 0, ".", ",")."</td>";

$prev_tot = $t_tot;
$prev_dt = $dt;
$dt = "2019-04";

$last_date = "April 2019";

$t_dem = $tot_tot[$dt]["tot_dem"];
$t_rep = $tot_tot[$dt]["tot_rep"];
$t_una = $tot_tot[$dt]["tot_una"];
$t_oth = $tot_tot[$dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;

$chart_data[3] = array($dt, "Dem"=>$t_dem, "Rep"=>$t_rep, "Una"=>$t_una);

$dem_change = $t_dem - $tot_tot[$prev_dt]["tot_dem"];
$rep_change = $t_rep - $tot_tot[$prev_dt]["tot_rep"];
$una_change = $t_una - $tot_tot[$prev_dt]["tot_una"];
$oth_change = $t_oth - $tot_tot[$prev_dt]["tot_oth"];
$t_tot = $t_dem + $t_rep + $t_una + $t_oth;
$tot_change = $t_tot - $prev_tot;

echo "<td>".number_format($t_dem, 0, ".", ",")."</td>";
echo "<td>".number_format($dem_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_rep, 0, ".", ",")."</td>";
echo "<td>".number_format($rep_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_una, 0, ".", ",")."</td>";
echo "<td>".number_format($una_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_oth, 0, ".", ",")."</td>";
echo "<td>".number_format($oth_change, 0, ".", ",")."</td>";
echo "<td>".number_format($t_tot, 0, ".", ",")."</td>";
echo "<td>".number_format($tot_change, 0, ".", ",")."</td>";

$tot_pct = $t_tot / $vot_pop;
echo "<td>".number_format($tot_pct*100, 2, ".", ",")."%</td>";

echo "<td width='40px'></td>";

echo "</tr>";

echo "</tfoot>";
//echo "</tbody>";

echo "</table>";
echo "</div>";
echo "<br>";
echo "<i>Source: NJ State Voter Registrations By Legislative District</i>";

//echo "<br>";
//print_r($tot_tot);
echo "<br>";

$RegsStats = json_encode($chart_data);

//echo "<br>$RegsStats<br>";

$last_values[0] = array("Dem"=>$t_dem, "Rep"=>$t_rep, "Una"=>$t_una);

//$phpRegData = json_encode($chart_values);	
$LastRegsStats = json_encode($last_values);	
	
	
?>

<br>
<a href="javascript:doit('xlsx');">Export to Excel</a>
<BR>

<br>
<table border="0">
<tr>
<td valign="top">
<canvas id='regChart' width='650' height='450'></canvas>
</td>
<td width="100"></td>
<td valign="top">
<canvas id="lastRegChart" width="370" height="370"></canvas>
</td>
</tr>
</table>
<br>

</body>
</html>

<script>
function doit(type, fn, dl) {
	var elt = document.getElementById('data-table');
	var d = Date.now().toDateString();
	//window.alert(elt);
	var wb = XLSX.utils.table_to_book(elt, {sheet:"Registrations"});
	//window.alert(wb);
	return dl ?
		XLSX.write(wb, {bookType:type, bookSST:true, type: 'base64'}) :
		XLSX.writeFile(wb, fn || ('voter_registrations_' + d + '.' + (type || 'xlsx')));
}

var dist = "";

function select(r) {
	var table = document.getElementById('data-table');
	var i;
	r.style.backgroundColor = "00FFFF";
	if(r.cells[0].innerHTML == "Total") {
		dist = ' - ' + r.cells[0].innerHTML;
	} else {
		dist = ' - LD ' + r.cells[0].innerHTML;
	}
	for (var i = 0, row; row = table.rows[i]; i++) {
		if(r != row) {
			if(i % 2 == 0) {// even row
				row.style.backgroundColor  = "#FFFFFF";
			} else {
				row.style.backgroundColor  = "#DDD";
			}
		}
	}
		
	var cdata = [
		[r.cells[2].firstChild.nodeValue.split(',').join(''), 
		 r.cells[7].firstChild.nodeValue.split(',').join(''), 
		 r.cells[17].firstChild.nodeValue.split(',').join(''),
		 r.cells[27].firstChild.nodeValue.split(',').join('')
		],
		[r.cells[3].firstChild.nodeValue.split(',').join(''),
		 r.cells[9].firstChild.nodeValue.split(',').join(''), 
		 r.cells[19].firstChild.nodeValue.split(',').join(''),
		 r.cells[29].firstChild.nodeValue.split(',').join('')
		],
		[r.cells[4].firstChild.nodeValue.split(',').join(''), 
		 r.cells[11].firstChild.nodeValue.split(',').join(''), 
		 r.cells[21].firstChild.nodeValue.split(',').join(''),
		 r.cells[31].firstChild.nodeValue.split(',').join('')
		]
	];
	changeChartData(cdata);

	var pieData = [
		[
		 r.cells[27].firstChild.nodeValue.split(',').join('')
		],
		[
		 r.cells[29].firstChild.nodeValue.split(',').join('')
		],
		[
		 r.cells[31].firstChild.nodeValue.split(',').join('')
		]
	];
	changePieChartData(pieData);
}

function changeChartData(newData) {
	//alert("changeChartData " + newData);
	//alert("changeChartData " + dist);
	for(i = 0; i < 3; i++) {
		for(j = 0; j < 4; j++) {
			reg_chart.data.datasets[i].data[j] = newData[i][j];
		}
	}
	
	config2.options.title.text = 'Voter Registrations' + dist;
	reg_chart.update();
}

function changePieChartData(newData) {
	//alert("changePieChartData " + newData);
	//alert("changeChartData " + dist);
	last_reg_chart.data.datasets[0].data = newData;
	
	config3.options.title.text = 'Voter Registrations ' + lastDate + dist;
	last_reg_chart.update();
}

var regData = <?php echo $RegsStats; ?>;

var ctx2 = document.getElementById("regChart");

var reg_data1 = regData.map(function(e) {
   return e.Dem;
});
var reg_data2 = regData.map(function(e) {
   return e.Rep;
});
var reg_data3 = regData.map(function(e) {
   return e.Una;
});

//var tit = "<?php echo $RegsStats; ?>";

var config2 = {
   type: 'line',
  options: {
    title: {
      display: true,
      text: 'Voter Registrations' + dist
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
            formatter: (value, ctx) => {
                return "";
            }
        }
    }	
  },
  data: {
      labels: ['Jan 2018', 'Nov 2018', 'Jan 2019', 'Apr 2019'],
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
   }
 };

var last_reg_chart = new Chart(ctx3, config3);

</script>

<?php
ob_end_flush();
?>