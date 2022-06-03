<html>
<head>
<title>BlueCompass.org - New Jersey Elections Candidates</title>
</head>
<?php
include("../db-a.php");

$sql = "select distinct county from alpha_voter_list_state order by 1";
$query = mysqli_query($conn, $sql);

$el_type = $_GET["el_type"];
$year = $_GET["year"];

?>
<body>
<!--
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
-->

<H2><center>Who is running in <?php echo $year; ?></center></H2>
<br>
<br>
Providing an address you will be able to see the candidates running in the  <?php echo $year; ?> Elections (Primary or General).
<br>
<hr>
<form action="candidates.php">
<table style="border-spacing: 5px;">
<tr>
  <td>Street Number:</td>
  <td><input style="height:20px;" type="text" name="street_number"></td>
</tr>
<tr>
  <td>Street name:</td>
  <td><input style="height:20px;" type="text" name="street_name"></td>
</tr>
<tr>
  <td>Municipality:</td>
  <td><input style="height:20px;" type="text" name="town"></td>
</tr>
<tr>
  <td>County:</td>
  <td>
  <select name="county">
	<option value=""></option>
	<?php
	while($row = mysqli_fetch_array($query)) {
		echo "<option value=".$row["county"].">".ucfirst(strtolower($row["county"]))."</option>";
	}
	?>
  </select>
  </td>
</tr>
<tr>
  <td>Election:</td>
  <td>
  <select name="election_type">
	<option value="GEN">General</option>
	<option value="PRI">Primary</option>
  </select>
  </td>
<tr>
</table>
  <br>
  <input type="submit" value="Submit">
  <input type="hidden" name="election_year" value='<?php echo $year; ?>'>
</form> 
<br>
<i>
Addresses are extracted from the voter list, addresses of not registered voters are not in the database.
<br>
This service is purely for informational purpose, please ensure to review the official ballot that you receive from the county.
</i>
<hr>
<br>
</body>
</html>