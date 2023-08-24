<?php
session_start();

//if(!isset($_SESSION['myusername'])) {
//	$_SESSION['origin'] = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
//	header("Location:main_login.php");
//}
//$_SESSION['origin'] = "";

include("db.php");

$county = $_GET["county"];
$muni = $_GET["muni"];

$sql="select county, muni, muni_id, ld, cd, old_cd, old_ld, partial, other_cd from municipal_list ";

if($muni != "") {
	$sql = $sql . "where muni='".$muni."'";
}

if($county != "") {
	$sql = $sql . "where county='".$county."'";
}

$sql = $sql .  " order by 1, 2";

$query = mysqli_query($conn, $sql);

?>

<html>
  <head>
    <title>Municipal List</title>
	<script src="sorttable.js"></script>
<style type="text/css">	
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}	
</style>	
  </head>
  <body>

<CENTER>
<BR>
<H2>NJ - Municipal List</H2>
</CENTER>
<BR>

<table>
<tr><td valign="top">
<?php
$num_row = mysqli_num_rows($query);
if($num_row > 1) {
	echo $num_row . " Municipalities";
}
if($num_row == 0) {
	echo "No data not found for ".$muni;
	exit;
}
?>
</tr></td>

<tr>
<td>
<input type='checkbox' id='partial' onclick='filterPartial()'>
<label for="partial">Split towns only</label>
</td>
<td>
<input type='checkbox' id='changedCD' onclick='filterChangedCD()'>
<label for="changedCD">Towns that changed CD only</label>
</td>
</tr>
<tr>

<td colspan='4' valign="top">

<table border=1 class="sortable" id='muni_table'>
<tr>
<th>County</th><th>Municipality</th>
<th>Congressional District</th><th>Old CD</th>
<th>Legislative District</th><th>Old LD</th>
<th>Partial</th>
</tr>
<?php
# add table sorting
# add search
while($row = mysqli_fetch_array($query)) {
	$cd = $row["cd"];
	echo "<tr>";
	echo "<td><a href=county.php?county=".$row["county"].">".$row["county"]."</a></td>";
	echo "<td><a href=muni.php?muni_id=".$row["muni_id"].">".$row["muni"]."</a></td>";
	echo "<td>";
	echo "<a href=cd.php?cd=".$row["cd"].">".$row["cd"]."</a>";
	if($row["other_cd"] != "") {
		echo ", <a href=cd.php?cd=".$row["other_cd"].">".$row["other_cd"]."</a>";
	}
	echo "</td>";
	echo "<td>".$row["old_cd"]."</td>";
	echo "<td>".$row["ld"]."</td>";	
	echo "<td>".$row["old_ld"]."</td>";	
	echo "<td>".$row["partial"]."</td>";	
	echo "</tr>";
}
?>
</table>
</td>

</table>

<script>
function filterPartial() {
  var input, filter, table, tr, td, i;
  input = document.getElementById("partial");
  table = document.getElementById("muni_table");
  tr = table.getElementsByTagName("tr");
  for (i = 1; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[6];
    if (td) {
	  if(input.checked == true) {
		if (td.innerHTML.toUpperCase() == "Y") {
			tr[i].style.display = "";
		} else {
			tr[i].style.display = "none";
		}
	  } else {
		tr[i].style.display = "";
	  }
    }       
  }
}

function filterChangedCD() {
  var input, filter, table, tr, td, i;
  input = document.getElementById("changedCD");
  table = document.getElementById("muni_table");
  tr = table.getElementsByTagName("tr");
  for (i = 1; i < tr.length; i++) {
    newCd = tr[i].getElementsByTagName("td")[2];
	oldCd = tr[i].getElementsByTagName("td")[3];
    if(input.checked == true) {
	  if (newCd.innerText != oldCd.innerText) {
		tr[i].style.display = "";
	  } else {
		tr[i].style.display = "none";
	  }
    } else {
	  tr[i].style.display = "";
    }
  }
}

</script>

  </body>
</html>
<?php
ob_end_flush();
?>