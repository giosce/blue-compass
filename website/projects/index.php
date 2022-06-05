<?php
session_start();
if(!isset($_SESSION['myusername'])) {
	$_SESSION['origin'] = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
	header("Location:../main_login.php");
}

include("../db.php");

$sql="SELECT *,
(select count(*) from project_contact pc where pc.proj_id = p.proj_id) as voter_qty,
(select count(distinct concat(street_num, street_name)) 
from voter v, project_contact pc where pc.proj_id = p.proj_id and v.voter_id = pc.voter_id) as door_qty 
FROM project p order by proj_create_date desc";

//echo "$sql";

$query = mysqli_query($conn, $sql);

?>

<html>
  <head>
    <title>BlueCompass.org - Voter Outreach Projects</title>
	<script src="sorttable.js"></script>
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
</head>

<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../njlocal.html" class="aa">2019 Other NJ Local Elections</a></td>
<td><a href="index.php" class="aa">Projects</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">Party Connection</a></td>
<td><a href="" class="aa">Forum</a></td>
<td><a href="" class="aa">Blog</a></td>
</tr>
</table>

<center><H2>Voter Outreach Projects</H2></center>


<table border=1 class="sortable">
<tr>
<th>Project Name</th><th>Project Type</th><th>Create Date</th>
<th>Organization</th><th>Person</th><th>Doors</th><th>Voters</th><th>Status</th>
<?php
while($row = mysqli_fetch_array($query)) {
	echo "<tr>";
	echo "<td><a href='project_details.php?proj_id=".$row["proj_id"]."'>".$row["proj_name"]."</a></td>";
	echo "<td>".$row["proj_type_code"]."</td>";
	echo "<td>".$row["proj_create_date"]."</td>";
	echo "<td>".$row["proj_org_name"]."</td>";
	echo "<td>".$row["proj_creator_name"]."</td>";
	echo "<td>".$row["door_qty"]."</td>";
	echo "<td>".$row["voter_qty"]."</td>";
	echo "<td>".$row["proj_status"]."</td>";
	echo "</tr>";
}
?>
</table>
<BR>
<a href="create_project.php">Create Project</a>
&nbsp;&nbsp;
<a href="create_voter_list_query.php">Create Voter List</a>
<BR>
</body>
</html>
<?php
ob_end_flush();
?>