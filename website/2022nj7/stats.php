<?php


include("db.php");

$district = $_GET["district"];
$year = $_GET["year"];
$town = $_GET["town"];
$cd_id = $_GET["cd"];
$el_type = $_GET["el_type"];
$debug = $_GET["gio"];
$county = $_GET["county"];

$cd_id = str_replace("NJ","",$cd_id);

/*
$sql_pop_and_reg_voters =
"select b.county, a.muni_code, b.municipality, party, a.citizens_18_plus, count(*) registered_voters,
count(*) / a.citizens_18_plus * 100 '%'
from acs_population a, voter_list_nj7 b
where b.muni_id = a.muni_code
and status='Active'
and party in ('Democratic','Republican','Unaffiliated')
group by b.municipality, b.party
order by b.county, b.municipality, b.party";
*/

$sql_pop_and_reg_voters =
"select b.county, a.muni_code, b.municipality, party, a.citizens_18_plus, 
count(*) registered_voters,
count(*) / a.citizens_18_plus * 100 'reg_voters_pct',
(select count(*) from voter_history_nj7 c
where election_date='2022-11-08' and c.res_muni_code = b.muni_id and c.party = b.party
group by res_muni, c.party) as voted
from acs_population a, voter_list_nj7 b, municipal_list_nj7 d
where b.muni_id = a.muni_code and b.muni_id = d.muni_code 
and status='Active'
and party in ('Democratic','Republican','Unaffiliated')
group by b.municipality, b.party
order by 1, 3, 4";

$query_pop_and_reg_voters = mysqli_query($conn, $sql_pop_and_reg_voters);

/*
$sql_party_turnout = "
select res_county, res_muni, party, count(*) from voter_history
where election_date='2022-11-08'
and party in ('Democratic','Republican','Unaffiliated')
and res_congressional='07'
group by res_county, res_muni, party
order by res_county, res_muni, party";
*/

//$query_party_turnout = mysqli_query($conn, $sql_party_turnout);

if($debug=="y") {
	//echo $query_pop_and_reg_voters."<br>";
}

//phpinfo();

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
		
	</style>
	
	<link rel="stylesheet" type="text/css" href="../dropdowntabfiles/ddcolortabs.css" />
	<script type="text/javascript" src="../dropdowntabfiles/dropdowntabs.js"></script>
	
  </head>
<body style="font-family:Arial">

<a href="../index.html">Home</a>
<a href="index.php" title="Congressional District">NJ07</a>

<br>
<center>

<H2>NJ CD <?php echo $cd_id; ?> Data</H2>

</center>

<br>

<?php
echo "<table border='1' class='sortable' id='pop_and_voter_reg_table'>";
echo "<caption><b>Eligible Voters and Voter Registrations General Elections 2022 / end of year 2022</b></caption>";
echo "<tr title='Click to sort'>";
echo "<th style='min-width:120px'>County</th><th>Municipality</th><th>Citizens 18+</th>";
echo "<th style='min-width:50px'>Dem</th><th style='min-width:50px'>Dem %</th>";
echo "<th style='min-width:50px'>Dem Voted</th><th style='min-width:50px'>Dem Voted %</th>";
echo "<th style='min-width:50px'>Rep</th><th style='min-width:50px'>Rep %</th>";
echo "<th style='min-width:50px'>Rep Voted</th><th style='min-width:50px'>Rep Voted %</th>";
echo "<th style='min-width:50px'>Una</th><th style='min-width:50px'>Una %</th>";
echo "<th style='min-width:50px'>Una Voted</th><th style='min-width:50px'>Una Voted %</th>";
echo "<th style='min-width:50px'>Tot Reg Voters</th><th style='min-width:50px'>Tot Reg Voters %</th>";
echo "</tr>";


$muni = "";
$i = 0;
$tot_reg_voters = 0;

while($row = mysqli_fetch_array($query_pop_and_reg_voters)) {
	if($debug=="y" and $i < 1) {
		print_r($row);
	}
	if($muni != $row["municipality"]) {
		$muni = $row["municipality"];
		$tot_reg_voters = 0;
		echo "<tr>";
		echo "<td>".$row["county"]."</td>";
		echo "<td><a href='muni.php?muni_id=".$row["muni_code"]."'>".$muni."</a></td>";
		echo "<td align=right>".number_format($row["citizens_18_plus"], 0, ".", ",")."</td>";
	}
	$reg_voters += $row["registered_voters"];
	$i += 1;
	echo "<td align=right>".number_format($row["registered_voters"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["reg_voters_pct"], 2, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["voted"], 0, ".", ",")."</td>";
	echo "<td align=right>".number_format($row["voted"]/$row["registered_voters"]*100, 2, ".", ",")."</td>";
	if($i == 3) {
		echo "<td align=right>".number_format($tot_reg_voters, 0, ".", ",")."</td>";
		echo "<td align=right>".number_format($tot_reg_voters_pct / $row["citizens_18_plus"] * 100, 2, ".", ",")."</td>";
		echo "</tr>";
	}
}
echo "</table>";
echo "<i>Source: NJ State voter file and ACS estimated population</i>";
?>

  </body>
</html>
<?php
ob_end_flush();
?>