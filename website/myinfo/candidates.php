<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
.menu td:hover {
	background-color: blue; 
	color: white;
	text-shadow: -.25px -.25px 0 white, .25px .25px white;
	cursor: pointer;
}

.menu td {
	text-decoration: none;
	padding: 2px;
	color: blue;
}

.aa {
	text-decoration: none;
	color: blue;
	text-shadow: -.25px -.25px 0 transparent, .25px .25px transparent;	
}
.aa:hover {
	color: white;
	text-shadow: -.25px -.25px 0 white, .25px .25px white;
}
</style>
<!-- <meta http-equiv="refresh" content="0; url=data?ld=21" /> -->


<script src="../jquery-3.3.1.min.js"></script>
<script lang="javascript" src="../xlsx.full.min.js"></script>
<script lang="javascript" src="../FileSaver.min.js"></script>


</head>

<body>
<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../njlocal.html" class="aa">2019 Other NJ Local Elections</a></td>
<td><a href="candidates.php" class="aa">2019 NJ Candidates</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">Party Connection</a></td>
<td><a href="" class="aa">Forum</a></td>
<td><a href="" class="aa">Blog</a></td>
</tr>
</table>

<H2><center>Who is running in 2019</center></H2>
<a href="index.php">Search by address</a>
<br>
<?php
ini_set('display_errors','off');

include("../db-a.php");

$county = $_GET["county"];
$ld = $_GET["ld"];
$muni = $_GET["muni"];
$office = $_GET["office"];
$party = $_GET["party"];
$el_type = $_GET["el_type"];

if(empty($el_type)) {
	$el_type="GEN";
}

$sql = "select * from candidates where election_year='2019'";


if(!empty($county)) {
	$sql = $sql. " and county_geo like '%".$county."%'";
}
if(!empty($muni)) {
	$sql = $sql. " and (muni='".$muni."' or ld=(select ld from municipal_list where muni='".$muni."'))";
} 
if(!empty($office)) {
	if($office == "County Offices") {
		//$sql = $sql. " and county ='".$county."'";
		$sql = $sql. " and county <>''";
	} else if($office == "Municipal Offices") {
		$sql = $sql. " and (office like '%Council%' or office like '%Township%' or office like '%Mayor%')";
	} else if($office == "Assembly") {
		$sql = $sql. " and office like '%".$office."%'";
	} else if($office == "State Senate") {
		$sql = $sql. " and office like '%".$office."%'";
	} else if($office == "Legislature") {
		$sql = $sql. " and office in ('General Assembly', 'State Senate')";
	} else {
		$sql = $sql. " and office ='".$office."'";
	}
}
if(!empty($party)) {
	if($party == "None") {
		$sql = $sql. " and party not in ('Dem', 'Rep')";
	} else {
		$sql = $sql. " and party='".$party."'";
	}
}
if(!empty($el_type)) {
	$sql = $sql. " and election_type='".$el_type."'";
}
if(!empty($ld)) {
	$sql = $sql. " and ld='".$ld."'";
}

//if($office == "Assembly" or $office == "State Senate" or $office == "Legislature") {
//	$sql = $sql." order by ld, county_geo, sort_by, muni, incumbent desc, party, ward, precinct, slogan";
//} else 
if($office == "County Offices") {
	$sql = $sql." order by county_geo, ifnull(sort_by, 100), office, ld, muni, incumbent desc, party, ward, precinct, slogan";
} else if(!empty($county) and empty($office)) {
	$sql = $sql." order by ifnull(ld, 'ZZ'), muni, ifnull(sort_by, 100), office, incumbent desc, party, ward, precinct, slogan";	
} else {
	$sql = $sql." order by ifnull(sort_by, 100), office, ld, county_geo, muni, incumbent desc, party, ward, precinct, slogan";
}

echo "<script>";
echo "console.log(".$sql.")";
echo "</script>";

$query = mysqli_query($conn, $sql);

$num_rows = mysqli_num_rows($query);

if(!empty($county)) {
	$sql2 = "select distinct muni from candidates where county_geo ='".$county."' order by 1";
	$query2 = mysqli_query($conn, $sql2);
}
//echo "sql: ".$sql2."<br>";

$sql3 = "select distinct county from municipal_list order by 1";
$query3 = mysqli_query($conn, $sql3);

//echo "sql: ".$sql3;

echo "<form id='get_candidates' action='candidates.php'>";
echo "<select name='el_type' onchange='changeSelect();'>";
if($el_type == "GEN") {
	echo "<option value='GEN' selected>GEN</option>";
	echo "<option value='PRI'>PRI</option>";
} else {
	echo "<option value='GEN'>GEN</option>";
	echo "<option value='PRI' selected>PRI</option>";
}
echo "</select>"; 
echo "&nbsp;";

echo "County: ";
echo "<select name='county' onchange='changeSelect();'>";
echo "<option value=''></option>";
while($row3 = mysqli_fetch_array($query3)) {
	//print_r($row3);
	$cty = $row3["county"];
	if($county == $cty) {
		echo "<option value='".$cty."' selected>".$cty."</option>";
	} else {
		echo "<option value='".$cty."'>".$cty."</option>";
	}
}
echo "</select>"; 

echo "&nbsp;";
echo "Leg Dist: ";
echo "<select name='ld' id='ld' onchange='this.form.submit();'>";
echo "<option value=''></option>";
for($i = 1; $i < 41; $i++) {
	if($i < 10) {
		$i = "0".$i;
	}
	if($ld == $i) {
		echo "<option value='".$i."' selected>".$i."</option>";
	} else {
		echo "<option value='".$i."'>".$i."</option>";
	}
}
echo "</select>";

echo "&nbsp;";
echo "Town: ";
echo "<select name='muni' id='muni' onchange='this.form.submit();'>";
echo "<option style='width:120px;' value=''></option>";
while($row2 = mysqli_fetch_array($query2)) {
	//print_r($row2);
	$mni = $row2["muni"];
	if($muni == $mni) {
		echo "<option value='".$mni."' selected>".$mni."</option>";
	} else {
		echo "<option value='".$mni."'>".$mni."</option>";
	}
}
echo "</select>"; 
echo "&nbsp;";
echo "Office: ";
echo "<select name='office' onchange='this.form.submit();'>";
if(empty($office)) {
	echo "<option value='' selected></option>";
} else {
	echo "<option value=''></option>";
}
if($office == "Assembly") {
	echo "<option value='Assembly' selected>Assembly</option>";
} else {
	echo "<option value='Assembly'>Assembly</option>";
}
if($office == "Legislature") {
	echo "<option value='Legislature' selected>Legislature</option>";
} else {
	echo "<option value='Legislature'>Legislature</option>";
}
if($office == "State Senate") {
	echo "<option value='State Senate' selected>State Senate</option>";
} else {
	echo "<option value='State Senate'>State Senate</option>";
}
if($office == "County Offices") {
	echo "<option value='County Offices' selected>County Offices</option>";
} else {
	echo "<option value='County Offices'>County Offices</option>";
}
if($office == "Municipal Offices") {
	echo "<option value='Municipal Offices' selected>Municipal Offices</option>";
} else {
	echo "<option value='Municipal Offices'>Municipal Offices</option>";
}
if($office == "County Committee") {
	echo "<option value='County Committee' selected>Party Committee</option>";
} else {
	echo "<option value='County Committee'>Party Committee</option>";
}
echo "</select>"; 

echo "&nbsp;";
echo "Party: ";
echo "<select name='party' id='party' onchange='this.form.submit();'>";
if(empty($party)) {
	echo "<option value='' selected></option>";
} else {
	echo "<option value=''></option>";
}

if($party == "Dem") {
	echo "<option value='Dem' selected>Dem</option>";
} else {
	echo "<option value='Dem'>Dem</option>";
}
if($party == "Rep") {
	echo "<option value='Rep' selected>Rep</option>";
} else {
	echo "<option value='Rep'>Rep</option>";
}
if($party == "None") {
	echo "<option value='None' selected>None / Other</option>";
} else {
	echo "<option value='None'>None / Other</option>";
}

echo "</select>"; 
echo "</form>"; 

echo "Retrieved ".$num_rows." records";

?>
<br>
<i>
Data has been obtained via County Clerk, County Boards of Elections and County Democratic Committees.
Not all municipal elections data loaded yet.
<br>
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<table border="1" id='data-table'>
<tr>
<th width='240px'>County</th>
<th>LD</th>
<th>Muni</th>
<th>Ward</th>
<th>Precinct</th>
<th width='200px'>Office</th>
<th width='200px'>Candidate</th>
<th>Party</th>
<th>Address</th>
<th width='200px'>Email</th>
<th width='200px'>Website</th>
<!--<th>Slogan</th>-->
</tr>
<?php
while($row = mysqli_fetch_array($query)) {
	//print_r($row);
	echo "<tr>";
	echo "<td>".$row["county_geo"]."</td>";
	echo "<td>".$row["ld"]."</td>";
	echo "<td>".$row["muni"]."</td>";
	echo "<td>".$row["ward"]."</td>";
	echo "<td>".$row["precinct"]."</td>";
	echo "<td>".$row["office"]."</td>";
	if($row["incumbent"] == "Y") {
		echo "<td>".$row["name"]." *</td>";
	} else {
		echo "<td>".$row["name"]."</td>";
	}
	echo "<td>".$row["party"]."</td>";
	//echo "<td>".$row["incumbent"]."</td>";
	if(!empty($row["address"])) {
		echo "<td>".$row["address"].", ".$row["town"]." ".$row["zip"].", ".$row["state"]."</td>";
	} else {
		echo "<td></td>";
	}		
	$email = $row["email"];
	if(!empty($email)) {
		echo "<td><a href='mailto:".$email."'>$email</a></td>";
	} else {
		echo "<td></td>";
	}	
	//echo "<td>".$row["slogan"]."</td>";
	$website = $row["website"];
	if(!empty($website)) {
		echo "<td><a target='new' href='".$website."'>$website</a></td>";
	} else {
		echo "<td></td>";
	}	
	echo "</tr>";
}
echo "</table>";

echo "<br>";
$print_sql = substr($sql, 56);
echo "sql: ".$print_sql;

?>
<br>

<a href="javascript:doit('xlsx');">Export to Excel</a>
<BR>

<br>
<script>
function changeSelect() {
	document.getElementById('muni').value = ''; 
	this.get_candidates.submit();
}

function doit(type, fn, dl) {
	var elt = document.getElementById('data-table');
	var d = new Date();
	//alert(d);
	var dd = ""+d.getFullYear()+""+(d.getMonth()+1)+""+d.getDate();
	//window.alert(elt);
	var wb = XLSX.utils.table_to_book(elt, {sheet:"Candidates"});
	//window.alert(wb);
	return dl ?
		XLSX.write(wb, {bookType:type, bookSST:true, type: 'base64'}) :
		XLSX.writeFile(wb, fn || ('candidates_' + dd + '.' + (type || 'xlsx')));
}
</script>
</body>
</html>