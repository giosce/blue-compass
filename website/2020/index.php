<html>
<head>
<title>BlueCompass.org - NJ 2020 Federal Elections</title>

<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>

<script src="../sorttable.js"></script>
<script src="../Chart.bundle.min.js"></script> <!-- chartjs.org -->
<script src="../chartjs-plugin-datalabels.min.js"></script>	

<style>
th, td {
  padding: 5px;
}
</style>

</head>

<body style="font-family:Arial; font-size:small;">

<div id="bluecompasstab" class="ddcolortabs">
<ul>
<li><a href="http://bluecompass.org" title="About Us, Memories" rel="dropmenu_home"><span>Home</span></a></li>
<li><a href="http://bluecompass.org/elections" title="Registrations, predictions, past results and more" rel="dropmenu_elections"><span>Elections</span></a></li>
<li><a href="http://bluecompass.org/cd" title="Congressional and Legislative Districts" rel="dropmenu_districts"><span>Districts</span></a></li>
<li><a href="http://bluecompass.org/party" title="County Committees, Member roles and more" rel="dropmenu_party"><span>Democratic Party Information</span></a></li>
<li><a href="http://bluecompass.org/myinfo" title="My District, my represantatives, my candidates"><span>My Information</span></a></li>
<li><a href="http://bluecompass.org/projects" title="Build voter lists for voter outreach projects"><span>Voter Outreach</span></a></li>	
</ul>
</div>

<br>

<div id="dropmenu_home" class="dropmenudiv_a">
<a href="http://bluecompass.org/aboutus.html">About Us</a>
<a href="http://bluecompass.org/memories">Memories</a>
<a href="https://www.facebook.com/bluecompass.org/">Facebook</a>
<a href="http://bluecompass.org/swagger">API</a>
<a href="http://bluecompass.org/forum">Forum</a>
<a href="http://bluecompass.org/blog">Blog</a>
</div>

<div id="dropmenu_elections" class="dropmenudiv_a">
<a href="http://bluecompass.org/2021">2021</a>
<a href="http://bluecompass.org/2021/candidates.php">  2021 Candidates</a>
<a href="http://bluecompass.org/2020">2020</a>
<a href="http://bluecompass.org/2020/candidates.php">  2020 Candidates</a>
<a href="http://bluecompass.org/njcongress.html">2019</a>
<a href="http://bluecompass.org/congress.html">2018</a>
</div>

<div id="dropmenu_party" class="dropmenudiv_a">
<a href="http://bluecompass.org/party/committees.php">County Committees</a>
<a href="http://bluecompass.org/party/comm_seats.php">County Committees Seats</a>
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">County Committee Member Role</a>
<a href="http://bluecompass.org/party/party_survey.html">Survey</a>
</div>

<div id="dropmenu_districts" class="dropmenudiv_a">
<a href="http://bluecompass.org/cd">Congressional Districts</a>
<a href="http://bluecompass.org/ld">Legislative Districts</a>
</div>

<center><H2>2020 Federal Congress Elections</H2></center>

<?php
ini_set('display_errors','off');

include("../db-a.php");

/*
$sql = "select * from cd order by cd";

$query = mysqli_query($conn, $sql);



$properties = parse_ini_file("../properties.ini");

$host = $properties["host"];
$username = $properties["username"];
$password = $properties["password"];
$db_name = $properties["db_name"];

$conn2 = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

$sql2 = "select * from monthly_registrations
where `year_month` = (select max(`year_month`) from monthly_registrations)
and cd <> ''
order by cd";

$latest_registrations = array();
$query2 = mysqli_query($conn2, $sql2);
while($row2 = mysqli_fetch_array($query2)) {
		$latest_registrations[$row2["cd"]] = array();
		$latest_registrations[$row2["cd"]]["dem"] = $row2["dem"];
		$latest_registrations[$row2["cd"]]["rep"] = $row2["rep"];
		$latest_registrations[$row2["cd"]]["una"] = $row2["una"];
		//echo "<br>".$row2["cd"].", ".$row2["dem"];
}
//echo "<br>Latest Registrations: ";
//print_r($latest_registrations);
*/

$sql = "select office, a.district, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes, 
		sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast,
		sum(mail_ballots_cast) as mail_ballots_cast, sum(provisional_ballots_cast) as provisional_ballots_cast, 
		dem_candidate, rep_candidate
		from state_election_results a, state_ballots_cast b
		where b.muni_code = a.muni_code and b.year = a.year 
		and a.year='2020'
		group by office, a.district 
		order by a.district, office";

$query = mysqli_query($conn, $sql);

?>

<br>
This year (November 3rd 2020) we'll elect the President, 435 House of Representatives seats and 1/3 of the Senate seats.
<br>
Currently the Senate is 53R and 47D and the House is 196R and 233D.
<br>
<br>
New Jersey is electing one Senator (of two, representing the whole state) 
and twelve House of Representatives members (each one representing one <a href="http://bluecompass.org/cd">NJ Congressional District</a>).
<br>
<br>
There are a multitude of NJ local elections: Most counties elect some of the Freeholders while some elect their County Clerk or Sheriff or Surrogate. 
<br>
12 counties also elect their Democratic County Committee members, a role that we consider the main bridge between citizens and the political parties.
<br>
<br>
<b>You can see the list of NJ federal and county offices candidates <a href="http://bluecompass.org/2020/candidates.php">here</a>.</b>
<br>
<br>
On this page you can see for each NJ Congressional District the 2018 elections results and voter registrations
as well as the 2020 elections candidates, their primary elections votes and the latest 2020 voter registrations.
<BR>
<HR>

<br>
<!--
<i>
* Jeff Van Drew was elected in 2018 as Democratic and switched to Republican in 2019.
<br>
Hover over the latest voter registrations number to see the difference compared to 2018.
</i>
-->
<table border=0>
<tr>
<td>
<table border="1" class="sortable" style="font-family:arial; font-size:small;">
<tr>
<th>Office</th><th>District</th><th>Registered Voters</th><th>Ballots Cast</th><th>Turnout</th>
<th>Dem Candidate</th><th>Rep Candidate</th><th>Dem Votes</th><th>%</th><th>Rep Votes</th><th>%</th>
<!-- put 2020 margin and 2018 margin? -->
</tr>
<?php

// TODO: I should get and display the MonthYear of latest registrations from DB, it's already in any record returned
// with the voter registrations.

while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
	echo "<td>".$row["office"]."</td>";
	if(!empty($row["district"])) {
		echo "<td><a href='http://bluecompass.org/cd/data/?cd=".str_replace("CD","",$row["district"])."'>".$row["district"]."</a></td>";
	} else {
		echo "<td></td>";
	}
	$registered_voters = $row["registered_voters"];
	$ballots_cast = $row["ballots_cast"];
	$turnout = ($ballots_cast / $registered_voters) * 100;
	$dem_votes_pct = ($row["dem_votes"] / $ballots_cast) * 100;
	$rep_votes_pct = ($row["rep_votes"] / $ballots_cast) * 100;
	echo "<td align='right'>".number_format($registered_voters, 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($ballots_cast, 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($turnout, 2, ".", ",")."%</td>";
	echo "<td>".$row["dem_candidate"]."</td>";
	echo "<td>".$row["rep_candidate"]."</td>";
	echo "<td align='right'>".number_format($row["dem_votes"], 0, ".", ",")."</td>";
	echo "<td nowrap align='right'>".number_format($dem_votes_pct, 2, ".", ",")."%</td>";
	echo "<td align='right'>".number_format($row["rep_votes"], 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($rep_votes_pct, 2, ".", ",")."%</td>";
/*
	echo "<td align='right'>".number_format($prev_reg_d, 0, ".", ",")."</td>";
	echo "<td align='right' title='".$diff_reg_d."'>".number_format($last_reg_d, 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($prev_reg_r, 0, ".", ",")."</td>";
	echo "<td align='right' title='".$diff_reg_r."'>".number_format($last_reg_r, 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($prev_reg_u, 0, ".", ",")."</td>";
	echo "<td align='right' title='".$diff_reg_u."'>".number_format($last_reg_u, 0, ".", ",")."</td>";

	$reg_voter_pct = ($last_reg_tot / $row["eligible_voters_2018"]) * 100;
	echo "<td align='right'>".number_format($last_reg_tot, 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($row["eligible_voters_2018"], 0, ".", ",")."</td>";
	echo "<td align='right'>".number_format($reg_voter_pct, 2, ".", ",")."%</td>";
*/	
	echo "</tr>";
/*	
	// totals
	$dem_votes += $row["votes_d"];
	$rep_votes += $row["votes_r"];
	
	$dem_prev_reg += $prev_reg_d;
	$dem_last_reg += $last_reg_d;
	$rep_prev_reg += $prev_reg_r;
	$rep_last_reg += $last_reg_r;
	$una_prev_reg += $prev_reg_u;
	$una_last_reg += $last_reg_u;
*/
}


?>
</table>

<tr>
<td>
<br>
<center>2016 - 2018
<BR>
<img src="../NJ-BlueWave-2016-2018.jpg" width="460" height="340">
</center>
</td>
</tr>
</table>
<BR>
</body>

<script type="text/javascript">

/*
var diff_d = <?php echo $diff_dem; ?>;
var diff_r = <?php echo $diff_rep; ?>;
var diff_u = <?php echo $diff_una; ?>;

var ctx = document.getElementById("RegChart");

var barChartData = {
	labels: ['Nov 2018', 'June 2020'],
	datasets: [{
		label: 'Dem',
		backgroundColor: "#335DFF",
		borderWidth: 1,
		data: [
			2214413, // first label
			2343315 // second label
			
		]
	}, {
		label: 'Rep',
		backgroundColor: "#FF0000",
		borderWidth: 1,
		data: [
			1283563, // first label
			1347646 // second label
			
		]
	}, {
		label: 'Una',
		//backgroundColor: 
		borderWidth: 1,
		data: [
			2394289, // first label
			2407570 // second label
			
		]
	}]

};
		
options = {
    animation: {
        duration: 0
    },	
    scales: {
        xAxes: [{
            gridLines: {
                offsetGridLines: true
            },
			barPercentage: 0.8
        }],
        yAxes: [{
            ticks: {
				beginAtZero: true,
				callback: function(value, index, values) {
					return Number(value).toLocaleString();
				}				
			}
        }]
    },
	tooltips: {
		callbacks: {
			label: function(tooltipItem, data) {
				return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].toLocaleString();
			}
		}
	},
    plugins: {
        datalabels: {
            formatter: (value, ctx2) => {
				st_label = Number(value).toLocaleString();
				if(ctx2.dataIndex == 1) {
					if(ctx2.datasetIndex == 0) {
						diff = diff_d;
					} else if(ctx2.datasetIndex == 1) {
						diff = diff_r;						
					} else if(ctx2.datasetIndex == 2) {
						diff = diff_u;
					}
					if(diff > 0) {
						st_label = st_label + "\n\n+" + Number(diff).toLocaleString();
					} else {
						st_label = st_label + "\n\n" + Number(diff).toLocaleString();
					}
				}
                return st_label;
            },
			color: '#000000',
			font: {
				weight: 'bold',
			}
        }
    }	
};

var myBarChart = new Chart(ctx, {
    type: 'bar',
    data: barChartData,
    options: options,
    responsive: false,
	animation: {
      duration: 0
    }
});
*/
tabdropdown.init("bluecompasstab", 1);

</script>

</html>