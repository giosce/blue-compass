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

$E2021 = $_GET["2021"];
$E2020 = $_GET["2020"];
$E2019 = $_GET["2019"];
$E2018 = $_GET["2018"];
$E2017 = $_GET["2017"];
$E2016 = $_GET["2016"];
$E2015 = $_GET["2015"];
$E2014 = $_GET["2014"];

$age = $_GET["age"];
$party = $_GET["party"];
$county_muni = $_GET["county_muni"];
$gender = $_GET["gender"];
$has_voted = $_GET["has_voted"];
$address = $_GET["address"];
$name = $_GET["name"];
$ward = $_GET["ward"];
$precinct = $_GET["precinct"];

// user will select in the last 1 months, or 3 or 6 or 12, easier if doing in days (30, 90, 180, 365) and use datediff
$registered_since = $_GET["registered_since"];
$vbm = $_GET["vbm"];

$sql="SELECT * FROM voter_hist_alpha_view where 1=1";

if(is_array($age)) {
	$x = 0;
	$sql = $sql." and (";
	foreach($age as $a){
		if($x == 0) {
			$sql = $sql." age ".$a;
			$x = 1;
		} else {
			$sql = $sql." or age ".$a;
		}
	}
	$sql = $sql.")";
} else if (!empty($age)) {
	//$sql = $sql." and age ".$age;
	$sql = $sql." age ".$age;
}
if (!empty($party)) {
	$sql = $sql." and substr(party, 1, 3) = '".$party."'";
}
//if (!empty($gender)) {
//	$sql = $sql." and gender = '".$gender."'";
//}
if (!empty($has_voted)) {
	$sql = $sql." and (2018G + 2019G + 2020G + 2021G) >= ".$has_voted;
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
	$sql = $sql." and legislative = '".$ld."'";
}
if (!empty($cong_dist)) {
	$cd = str_replace("NJ0", "", $cong_dist);
	$sql = $sql." and congressional = '".$cd."'";
}
if (!empty($county)) {
	$sql = $sql." and county = '".$county."'";
}
if (!empty($registered_since)) {
	$sql = $sql." and datediff(now(), registration_date) <= '".$registered_since."'";
}
if (!empty($vbm)) {
	$sql = $sql." and vbm = 'Y'";
}

$elections = false;

if(!empty($E2021) OR !empty($E2020) OR !empty($E2019) OR !empty($E2018) OR !empty($E2017) OR !empty($E2016)) {
	$sql = $sql." and (";
	$elections = true;
}
if(!empty($E2021)) {
	if($E2021 == 2) {
		$E2021 = 0;
	}
	$sql = $sql." 2021G = ".$E2021." ".$_GET["2021-AND-OR"];
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
	$sql = $sql." 2019G = ".$E2019." ".$_GET["2019-AND-OR"];
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

$sql = $sql." order by municipality, ward, cast(precinct as unsigned), street_name, cast(street_num as unsigned), last_name, first_name";

//echo $sql;
//echo "<BR>";

$query = mysqli_query($conn, $sql);


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
&nbsp;&nbsp;&nbsp;&nbsp;<button id="excel-button">Export To Excel</button>
<br><br>
<table border=1 class="scroll" id="data-table">
<thead>
<tr>
<th>Voter ID</th><th>Party</th><th>Last, First, Middle Name & Suffix</th>
<th>Gender</th><th>Age</th>
<th>Address</th><th>Town</th><th>Zip</th>
<th>Ward</th><th>Precinct</th><th>Reg Date</th><th>VBM</th>
<th>2016</th><th>2017</th><th>2018</th><th>2019</th><th>2020</th><th>2021</th>
</tr>
</thead>
<tbody>
<?php
$rowcount = 0;
$voterIds = array();
while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
	echo "<td>".$row["voter_id"]."</td><td width=60>".$row["party"]."</td>";
	echo "<td>".$row["last_name"].", ".$row["first_name"]." ".$row["middle_name"]." ".$row["name_suffix"]."</td>";
	echo "<td width=40>".$row["gender"]."</td><td>".$row["age"]."</td>";
	echo "<td>".$row["street_num"]." ".$row["street_name"]." ".$row["suffix_a"]." ".$row["apt_unit_no"]."</td>";
	echo "<td>".$row["municipality"].", ".$row["state"]."</td>";
	if(strlen($row["zip"]) < 5) {
		echo "<td>0".$row["zip"];	
	} else {
		echo "<td>".$row["zip"];
	}
	if($row["zip_4"] != "") {
		echo "-".$row["zip_4"];
	}
	echo "</td>";
	//echo "<td>".$row["cd"]."</td>";
	//echo "<td>".$row["ld"]."</td>";
	echo "<td>".$row["ward"]."</td>";
	echo "<td>".$row["precinct"]."</td>";
	//echo "<td>".$row["2013G"]."</td>";
	//echo "<td>".$row["2014G"]."</td>";
	//echo "<td>".$row["2015G"]."</td>";
	echo "<td>".$row["registration_date"]."</td>";
	echo "<td>".$row["vbm"]."</td>";
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
	if($row["2021G"] == "0") {
		$G2021 = "&#9746";
	} else {
		$G2021 = "&#9745";
	}
	echo "<td align='center'>".$G2021."</td>";
	
	
	
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
/*
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
*/

var wb = XLSX.utils.table_to_book(document.getElementById('data-table'));
var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary'});

function s2ab(s) {
	var buf = new ArrayBuffer(s.length);
	var view = new Uint8Array(buf);
	for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
	return buf;
}

$("#excel-button").click(function(){
	let d1 = new Date()
	let d2 = d1.toISOString().split('T')[0]
	//window.alert("Excel...");
	//var e = document.getElementById('data-table')
	//var place = e.options[e.selectedIndex].text;
	//if(place) {
	//	fname = "voter_list_"+place+".xlsx"; // and today date?
	//} else {
		fname = "voter_list_"+d2+".xlsx";
	//}
	saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), fname);
});
/*
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
*/
</script>

</html>
<?php
ob_end_flush();
?>