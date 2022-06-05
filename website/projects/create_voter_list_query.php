<?php
session_start();

if(!isset($_SESSION['myusername'])) {
	$_SESSION['origin'] = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
	header("Location:../main_login.php");
}
//$_SESSION['origin'] = "";

include("../db-a.php");

$county = $_GET["county"];
$muni = $_GET["muni"];
$year = $_GET["year"];
$type = $_GET["type"];
$muni_id = $_GET["muni_id"];

/*
$sql="SELECT * FROM turnout_precincts a
join party_density_by_precinct b on a.precinct_id = b.precinct_id 
where muni_name='".$muni."' and county='".$county."' 
and election_year='".$year."' and election_type='".$type."' 
and party_code='DEM'  
order by a.precinct_id";

if(!empty($muni_id)) {
	$sql="SELECT * FROM turnout_precincts a
	join party_density_by_precinct b on a.precinct_id = b.precinct_id 
	where muni_id='".$muni_id."' 
	and election_year='".$year."' and election_type='".$type."' 
	and party_code='DEM'  
	order by a.precinct_id";
	
	$sql2 = "select town from municipal_list where ssn='".$muni_id."'";
	$query2 = mysqli_query($conn, $sql2);
	$row2 = mysqli_fetch_row($query2);
	$muni = $row2[0];
}
*/
//echo "$sql";

//$query = mysqli_query($conn, $sql);

?>

<html>
  <head>
    <title>Blue Compass Voter Outreach</title>
	<script src="../sorttable.js"></script>
<style type="text/css">	.
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}	
</style>

<script>
function getTowns(elem_id) {
  console.log("getTowns")
  //if (id=="") {
   // document.getElementById(elem_id).innerHTML="";
  //  return;
  //}
  e = document.getElementById(elem_id);
  console.log("dropdown element:", e);
  val = e.options[e.selectedIndex].value;
  //alert(val);
  
  if(elem_id == "cong_dist") {
	  document.getElementById("leg_dist").selectedIndex = 0;
		document.getElementById("county").selectedIndex = 0;
  } else if(elem_id == "leg_dist") {
    document.getElementById("cong_dist").selectedIndex = 0;
    document.getElementById("county").selectedIndex = 0;
  } else if(elem_id == "county") {
    document.getElementById("cong_dist").selectedIndex = 0;
    document.getElementById("leg_dist").selectedIndex = 0;
  }
  
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      //document.getElementById("txtHint").innerHTML=this.responseText;
	    //alert(this.responseText);

      const data = JSON.parse(this.responseText);
	    //alert(data);
	
      let dropdown = document.getElementById('town-dropdown');
      dropdown.length = 0;
	  
      let option;
      option = document.createElement('option');
      option.text = "";
      option.value = "";
      dropdown.add(option);
        
      for (let i = 0; i < data.length; i++) {
          option = document.createElement('option');
        //alert(data[i]);
          option.text = data[i].town;
          option.value = data[i].muni_id;
          dropdown.add(option);
      }
  	}
  }
  xmlhttp.open("GET","get_towns_2022.php?"+elem_id+"="+val,true);
  xmlhttp.send();
}
window.onload = getTowns("cong_dist");
</script>
	
</head>
<body>

<a href='../index.html'>Home</a>
<a href='index.php'>Projects</a>

<CENTER><H2>Create a voters list</H2></CENTER>

<form action="create_voter_list3.php">

<table border="1">
<tr>
<td valign="top"style="padding:10px;">
<table>
<tr>
<th colspan="2">Where</th></th>
</tr>
<tr>
<td>Congressional District</td>
<td>
<select name="cong_dist" id="cong_dist" onchange="getTowns('cong_dist');">
<option></option>
<option value="NJ01">NJ01</option>
<option value="NJ02">NJ02</option>
<option value="NJ03">NJ03</option>
<option value="NJ04">NJ04</option>
<option value="NJ05">NJ05</option>
<option value="NJ06">NJ06</option>
<option value="NJ07">NJ07</option>
<option value="NJ08">NJ08</option>
<option value="NJ09">NJ09</option>
<option value="NJ10">NJ10</option>
<option value="NJ11">NJ11</option>
<option value="NJ12">NJ12</option>
</select>
</td>
</tr>
<tr>
<td>Legislative District</td>
<td>
<select name="leg_dist" id="leg_dist" onchange="getTowns('leg_dist');">
<option value=""></option>
<option value="LD21">LD21</option>
<option value="LD22">LD22</option>
<option value="LD23">LD23</option>
<option value="LD24">LD24</option>
<option value="LD25">LD25</option>
<option value="LD26">LD26</option>
</select>
</td>
</tr>
<tr>
<td>County</td>
<td>
<select name="county" id="county" onchange="getTowns('county');">
<option value=""></option>
<option value="Atlantic">Atlantic</option>
<option value="Bergen">Bergen</option>
<option value="Burlington">Burlington</option>
<option value="Camden">Camden</option>
<option value="Cape May">Cape May</option>
<option value="Cumberland">Cumberland</option>
<option value="Essex">Essex</option>
<option value="Gloucester">Gloucester</option>
<option value="Hudson">Hudson</option>
<option value="Hunterdon">Hunterdon</option>
<option value="Mercer">Mercer</option>
<option value="Middlesex">Middlesex</option>
<option value="Monmouth">Monmouth</option>
<option value="Morris">Morris</option>
<option value="Ocean">Ocean</option>
<option value="Passaic">Passaic</option>
<option value="Salem">Salem</option>
<option value="Somerset">Somerset</option>
<option value="Sussex">Sussex</option>
<option value="Union">Union</option>
<option value="Warren">Warren</option>
</select>
</td>
</tr>
<tr>
<td>Town</td>
<td>
<select name="town" id="town-dropdown" style="width:200;">
<option value=""></option>
</select>
</tr>
<tr>
<td>Ward</td>
<td>
<input type="text" name="ward" id="ward-dropdown" style="width:100;">
</tr>
<tr>
<td>Precinct</td>
<td>
<input type="text" name="precinct" id="precinct-dropdown" style="width:100;">
</tr>
</table>
</td>
<td valign="top"style="padding:10px;">
<table border="0">
<tr><th colspan="4">Who</th></tr>
<tr>
<td><b>Age Group</b></td>
<td>&nbsp;</td>
<td><b>Gender</b></td>
<td>
<select name="gender">
<option name="gender" value=""></option>
<option name="gender" value="F">Female</option>
<option name="gender" value="M">Male</option>
</select>
</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="between 18 and 24">18-24</td>

<td>&nbsp;</td>
<td><b>Has voted</b> </td>
<td>
<select name="has_voted">
  <option value=""></option>
  <option value="1">1 or 2</option>
  <option value="3">3 or 4</option>
</select>
 of 4 last Gen. Elect.
</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="between 25 and 34">25-34</td>
<td>&nbsp;</td>
<td><b>Registered as</b></td>
<td>
<select name="party">
  <option value=""></option>
  <option name="party" value="DEM">Dem</option>
  <option name="party" value="REP">Rep</option>
  <option name="party" value="UNA">Una</option>
  <!--<option name="party" value="UNA">Other</option>-->
</select>
</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="between 35 and 44">35-44</td>
<td>&nbsp;</td>
<td><b>Registered Since</b></td>
<td>
<select name="registered_since">
  <option value=""></option>
  <option name="registered_since" value="90">3 Months</option>
  <option name="registered_since" value="180">6 Months</option>
  <option name="registered_since" value="365">1 Year</option>
</select>
</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="between 45 and 54">45-54</td>
<td>&nbsp;</td>
<td><b>VBM Only</b></td>
<td><input type='checkbox' name='vbm' value="vbm"></td>
</tr>
<td><input type='checkbox' name='age[]' value="between 55 and 64">55-64</td>
<td>&nbsp;</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="between 65 and 74">65-74</td>
<td>&nbsp;</td>
</tr>
<tr>
<td><input type='checkbox' name='age[]' value="> 74">Over 74</td>
<td>&nbsp;</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td></td>
<td><b>Address like</b></td>
<td colspan="2"><input type="text" name="address"></td>
</tr>
<tr>
<td></td>
<td><b>Name like</b></td>
<td colspan="2"><input type="text" name="name"></td>
</tr>
</table>
<td valign="top" style="padding:10px;">
<table>
<tr>
<th colspan="3">Voting History (General Elections)</th>
</tr>
<tr>
<td><b>2021</b></td>
<td><input type="radio" name="2021" value="1">Voted</td>
<td><input type="radio" name="2021" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2021-AND-OR" value="AND">AND<input type="radio" name="2021-AND-OR" value="OR">OR</td></tr>
<tr>
<td><b>2020</b></td>
<td><input type="radio" name="2020" value="1">Voted</td>
<td><input type="radio" name="2020" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2020-AND-OR" value="AND">AND<input type="radio" name="2020-AND-OR" value="OR">OR</td></tr>
<tr>
<tr>
<td><b>2019</b></td>
<td><input type="radio" name="2019" value="1">Voted</td>
<td><input type="radio" name="2019" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2019-AND-OR" value="AND">AND<input type="radio" name="2019-AND-OR" value="OR">OR</td></tr>
<tr>
<tr>
<td><b>2018</b></td>
<td><input type="radio" name="2018" value="1">Voted</td>
<td><input type="radio" name="2018" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2018-AND-OR" value="AND">AND<input type="radio" name="2018-AND-OR" value="OR">OR</td></tr>
<tr>
<td><b>2017</b></td>
<td><input type="radio" name="2017" value="1">Voted</td>
<td><input type="radio" name="2017" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2017-AND-OR" value="AND">AND<input type="radio" name="2017-AND-OR" value="OR">OR</td></tr>
<tr>
<td><b>2016</b></td>
<td><input type="radio" name="2016" value="1">Voted</td>
<td><input type="radio" name="2016" value="2">Didn't Vote</td>
</tr>
<!--
<tr><td><input type="radio" name="2016-AND-OR" value="AND">AND<input type="radio" name="2016-AND-OR" value="OR">OR</td></tr>
<tr>
<td><b>2015</b></td>
<td><input type="radio" name="2015" value="1">Voted</td>
<td><input type="radio" name="2015" value="2">Didn't Vote</td>
</tr>
<tr><td><input type="radio" name="2015-AND-OR" value="AND">AND<input type="radio" name="2015-AND-OR" value="OR">OR</td></tr>
<tr>
<td><b>2014</b></td>
<td><input type="radio" name="2014" value="1">Voted</td>
<td><input type="radio" name="2014" value="2">Didn't Vote</td>
</tr>
-->
</table>
</td>
</form>
</td>
</tr>
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><center><input type="submit" value='Run Query'></center></td></tr>
</table>


  </body>
</html>
<?php
ob_end_flush();
?>