<?php
session_start();

if(!isset($_SESSION['myusername'])) {
	$_SESSION['origin'] = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
	header("Location:../main_login.php");
}
//$_SESSION['origin'] = "";

include("../db-a.php");

$leg_dist = $_GET["leg_dist"];
$cong_dist = $_GET["cong_dist"];
$county = $_GET["county"];

$town = $_GET["town"];

$E2020 = $_GET["2020"];
$E2019 = $_GET["2019"];
$E2018 = $_GET["2018"];
$E2017 = $_GET["2017"];
$E2016 = $_GET["2016"];
$E2015 = $_GET["2015"];
$E2014 = $_GET["2014"];

$frequency = $_GET["frequency"];
$age = $_GET["age"];
$party = $_GET["party"];
$county_muni = $_GET["county_muni"];
$prediction = $_GET["prediction"];
$prediction_b = $_GET["prediction_b"];
$gender = $_GET["gender"];
$has_voted = $_GET["has_voted"];
$address = $_GET["address"];
$name = $_GET["name"];
$ward = $_GET["ward"];
$precinct = $_GET["precinct"];

//if(!empty($_GET['precinct_id'])){
//	foreach($_GET['precinct_id'] as $precinct){
//		echo $precinct."</br>";
//	}
//}

//echo "<h1>Hello ".$precinct."</h1>";

//$sql="SELECT * FROM all_counties_alpha_and_history2 where precint_id='".$precinct."'";
//$sql="SELECT * FROM all_counties_alpha_and_history2 where precint_id='21010004'";

$prec_num = 0;
if(is_array($_GET['precinct_id'])) {
	$sql="SELECT * FROM state_voters_history where precinct_id in ('";
	foreach($_GET['precinct_id'] as $precinct){
		//echo $precinct."</br>";
		if($prec_num > 0) {
			$sql=$sql.", '";
		}
		$sql=$sql.$precinct."'";
		$prec_num++;
	}
	$sql=$sql.")";
} else if(!empty($_GET['precinct_id'])){
	$sql="SELECT * FROM state_voters_history where precinct_id = '".$_GET['precinct_id']."'";
} else if(!empty($county_muni)) {
	$sql="SELECT * FROM state_voters_history where concat(county_code, muni_code) = '".$county_muni."'";
}

//$sql="SELECT * FROM voter_and_voter_history where 1=1";
//$sql="SELECT * FROM dem_anag_and_voting_hist_union where 1=1";
$sql="SELECT *, (year(now()) - substr(date_of_birth, 1, 4)) as age FROM state_voters_history where 1=1";

if(is_array($frequency)) {
	$x = 0;
	$sql = $sql." and (";
	foreach($frequency as $f){
		if($x == 0) {
			$sql = $sql." voting_frequency ".$f;
			$x = 1;
		} else {
			$sql = $sql." or voting_frequency ".$f;
		}
	}
	$sql = $sql.")";	
} else if (!empty($frequency)) {
	$sql = $sql." and voting_frequency ".$frequency;
}

if(is_array($age)) {
	$x = 0;
	$sql = $sql." and (";
	foreach($age as $a){
		if($x == 0) {
			$sql = $sql." (year(now()) - substr(date_of_birth, 1, 4)) ".$a;
			$x = 1;
		} else {
			$sql = $sql." or (year(now()) - substr(date_of_birth, 1, 4)) ".$a;
		}
	}
	$sql = $sql.")";
} else if (!empty($age)) {
	//$sql = $sql." and age ".$age;
	$sql = $sql." (year(now()) - substr(date_of_birth, 1, 4)) ".$age;
}
if (!empty($party)) {
	$sql = $sql." and party_code = '".$party."'";
}
if (!empty($gender)) {
	$sql = $sql." and gender = '".$gender."'";
}
if (!empty($has_voted)) {
	$sql = $sql." and (2017G + 2018G + 2019G + 2020G) >= ".$has_voted;
}
if (!empty($town)) {
	$sql = $sql." and muni_code = '".$town."'";
}
if (!empty($ward)) {
	$sql = $sql." and ward = '".$ward."'";
}
if (!empty($precinct)) {
	$sql = $sql." and precinct = '".$precinct."'";
}
if (!empty($leg_dist)) {
	$ld = str_replace("LD", "", $leg_dist);
	$sql = $sql." and ld = '".$ld."'";
}
if (!empty($cong_dist)) {
	$cd = str_replace("NJ", "", $cong_dist);
	$sql = $sql." and cd = '".$cd."'";
}
if (!empty($county)) {
	$sql = $sql." and county = '".$county."'";
}


$elections = false;

if(!empty($E2020) OR !empty($E2019) OR !empty($E2018) OR !empty($E2017) OR !empty($E2016)) {
	$sql = $sql." and (";
	$elections = true;
}
if(!empty($E2020)) {
	if($E2020 == 2) {
		$E2020 = 0;
	}
	$sql = $sql." 2020G = ".$E2020." ".$_GET["2020-AND-OR"];
}
if(!empty($E2019)) {
	if($E2019 == 2) {
		$E2019 = 0;
	}
	$sql = $sql." 2029G = ".$E2019." ".$_GET["2019-AND-OR"];
}
if(!empty($E2018)) {
	if($E2018 == 2) {
		$E2018 = 0;
	}
	$sql = $sql." 2018G = ".$E2018." ".$_GET["2018-AND-OR"];
}
if(!empty($E2017)) {
	if($E2017 == 2) {
		$E2017 = 0;
	}
	$sql = $sql." 2017G = ".$E2017." ".$_GET["2017-AND-OR"];
}
if(!empty($E2016)) {
	if($E2016 == 2) {
		$E2016 = 0;
	}
	$sql = $sql." 2016G = ".$E2016; //." ".$_GET["2016-AND-OR"];
}

if($elections) {
	$sql = $sql.")";
}

if (!empty($address)) {
	$a = explode(" ", $address);
	$st_num = 0;
	$b = "";
	for($x=0; $x < count($a); $x++) {
		if(is_numeric($a[$x])) {
			$st_num = $a[$x];
		} else {
			if($b == "") {
				$b = $a[$x];
			} else {
				$b = $b." ".$a[$x];
			}
		}
	}
	if($st_num == 0) {
		$sql = $sql." and street_name like '%".$address."%'";
	} else {
		$sql = $sql." and street_num = '".$st_num."' and street_name like '%".$b."%'";
	}
}

if (!empty($name)) {
	$sql = $sql." and (first_name like '%".$name."%' or last_name like '%".$name."%')";
}

$query_display = $sql; //substr($sql, strpos($sql, "1=1")+8);

$where_clause = substr($sql, strpos($sql, "1=1")+8);

//$sql = $sql." order by precinct_id, street_name, street_num, last_name, first_name";
$sql = $sql." order by municipality, ward, precinct, street_name, street_num, last_name, first_name";

//echo "<BR>";
//echo $sql;

$query = mysqli_query($conn, $sql);

if(is_array($_GET['precinct_id'])) {
	$sql2="SELECT * FROM state_voters_history where precinct_id in ('";
	foreach($_GET['precinct_id'] as $precinct){
		//echo $precinct."</br>";
		if($prec_num > 0) {
			$sql2=$sql2.", '";
		}
		$sql2=$sql2.$precinct."'";
		$prec_num++;
	}
	$sql2=$sql2.")";
} else if(!empty($_GET['precinct_id'])){
	$sql2="SELECT * FROM state_voters_history where precinct_id = '".$_GET['precinct_id']."'";
} else if(!empty($county_muni)) {
	$sql2="SELECT * FROM state_voters_history where concat(county_code, muni_code) = '".$county_muni."'";
}

if (!empty($frequency)) {
	$sql2 = $sql2." and voting_frequency ".$frequency;
}
if (!empty($age)) {
	$sql2 = $sql2." and age ".$age;
}
if (!empty($party)) {
	$sql2 = $sql2." and party_code = '".$party."'";
}


//echo "<BR>";
//echo "$sql2";

				
//$query2 = ""; //mysqli_query($conn, $sql2);

//$precincts = $_GET["precinct_id"];
//$precinct = "";

if(is_array($precincts)) {
	$arrlength = count($precincts);
	for($x = 0; $x < $arrlength; $x++) {
		$precinct = $precinct." ".$precincts[$x];
	}
} else if(!empty($_GET['precinct_id'])){
	$precinct = $_GET['precinct_id'];	
}	


//echo "<br>$sql";

//$query = mysqli_query($conn, $sql);
//echo "<br>";


$sql_projects = "select proj_id, proj_name from project where proj_id not in(select distinct proj_id from project_contact)";


?>

<html>
  <head>
    <title>Blue Compass Voter Outreach</title>
	<script src="../sorttable.js"></script>
	<script src="../jquery-3.3.1.min.js"></script>
	<script lang="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
	<script lang="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js"></script>

	<style type="text/css">	.
		table.sortable thead {
			background-color:#eee;
			color:#666666;
			font-weight: bold;
			cursor: default;
		}	
	</style>
</head>
<body>

<a href='../index.html'>Home</a>
<a href='index.php'>Projects</a>

<CENTER><H2>Create a voters list</H2></CENTER>

<?php
echo "<br>";
//echo "$query_display";
//echo "<br>";
//echo "<br>";
echo "$where_clause";
echo "<br>";
echo "<br>";
echo "Retrieved ".mysqli_num_rows($query)." voters";
?>
<br>
<table border=1 class="scroll" id="data-table">
<thead>
<tr>
<th>Voter ID</th><th>Party</th><th>Last, First, Middle Name & Suffix</th>
<th>Gender</th><th>Age</th>
<th>Address</th><th>Town</th><th>Zip</th>
<th>Ward</th><th>Precinct</th>
<th>2013</th><th>2014</th><th>2015</th><th>2016</th><th>2017</th><th>2018</th><th>2019</th><th>2020</th>
<!-- <th>Voting Frequency</th><th>Voting Probability</th> -->
</tr>
</thead>
<tbody>
<?php
$rowcount = 0;
$voterIds = array();
while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
	echo "<td>".$row["voter_id"]."</td><td width=60>".$row["party_code"]."</td>";
	echo "<td>".$row["last_name"].", ".$row["first_name"]." ".$row["middle_name"]." ".$row["name_suffix"]."</td>";
	echo "<td width=40>".$row["gender"]."</td><td>".$row["age"]."</td>";
	echo "<td>".$row["street_num"]." ".$row["street_name"]." ".$row["suffix_a"]." ".$row["apt_unit_no"]."</td>";
	echo "<td>".$row["municipality"].", ".$row["state"]."</td>";
	echo "<td>".$row["zip_5"];
	if($row["zip_4"] != "") {
		echo "-".$row["zip_4"];
	}
	echo "</td>";
	//echo "<td>".$row["cd"]."</td>";
	//echo "<td>".$row["ld"]."</td>";
	echo "<td>".$row["ward"]."</td>";
	echo "<td>".$row["precinct"]."</td>";
	echo "<td>".$row["2013G"]."</td>";
	echo "<td>".$row["2014G"]."</td>";
	echo "<td>".$row["2015G"]."</td>";
	echo "<td>".$row["2016G"]."</td>";
	if($row["2017G"] == "0") {
		$G2017 = "&#9746";
	} else {
		$G2017 = "&#9745";
	}
	echo "<td align='center'>".$G2017."</td>";
	if($row["2018G"] == "0") {
		$G2018 = "&#9746";
	} else {
		$G2018 = "&#9745";
	}
	echo "<td align='center'>".$G2018."</td>";
	if($row["2019G"] == "0") {
		$G2019 = "&#9746";
	} else {
		$G2019 = "&#9745";
	}
	echo "<td align='center'>".$G2019."</td>";
	if($row["2020G"] == "0") {
		$G2020 = "&#9746";
	} else {
		$G2020 = "&#9745";
	}
	echo "<td align='center'>".$G2020."</td>";
	
	
	//echo "<td width=60>".$row["voting_frequency"]."</td>";
	//echo "<td>".$row["prediction"]."</td>";
	echo "</tr>";
	$voterIds[$rowcount] = $row["voter_id"];
	$rowcount++;
}
?>
</tbody>
</table>
<br>
<button id="excel-button">Export To Excel</button>
<!-- <a href="javascript:excel('<?php echo $where_clause ?>');">Export to Excel</a>-->
<!--<a href="javascript:excel('query');">Export to Excel</a>-->
<br>
<br>
</body>

<script>
function changeSelect() {
	document.getElementById('muni').value = ''; 
	this.get_candidates.submit();
}

function excel(q) {
	var elt = document.getElementById('data-table');
	var d = new Date();
	alert(q);
	var dd = ""+d.getFullYear()+""+(d.getMonth()+1)+""+d.getDate();
	//window.alert(elt);
	var wb = XLSX.utils.table_to_book(elt, {sheet:"Voters"});
	window.alert(wb);
	var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary'});
	b = new Blob([s2ab(wbout)],{type:"application/octet-stream"});
	window.alert(b);
	saveAs(b, 'voter_list.xlsx');
	//saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), 'voter_list.xlsx');
}

var wb = XLSX.utils.table_to_book(document.getElementById('data-table'));
var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary'});

function s2ab(s) {
	var buf = new ArrayBuffer(s.length);
	var view = new Uint8Array(buf);
	for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
	return buf;
}

$("#excel-button").click(function(){
	//window.alert("Excel...");
	saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), 'voter_list.xlsx');
});

function doit(type, q, fn, dl) {
	var elt = document.getElementById('data-table');
	var d = new Date();
	//alert(q);
	var dd = ""+d.getFullYear()+""+(d.getMonth()+1)+""+d.getDate();
	//window.alert(elt);
	var wb = XLSX.utils.table_to_book(elt, {sheet:"Voters"});
	//window.alert(wb);
	return dl ?
		XLSX.write(wb, {bookType:type, bookSST:true, type: 'base64'}) :
		XLSX.writeFile(wb, fn || ('voters' + dd + '.' + (type || 'xlsx')));
}
</script>

</html>
<?php
ob_end_flush();
?>