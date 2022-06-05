<?php

include("../db-a.php");


$sql = "select ifnull(b.county, a.county) as county, ifnull(replace(b.town, ' (partial)', ''), a.muni) as town, cd, ld,
chair_name, committee_email, website, address, bylaws,
ward, precinct, gender, member_name
from dem_committee a
left join municipal_list b on b.ssn = a.muni_id
left join dem_committee_members c on a.muni_id = c.muni_id
-- where a.muni <> '' 
order by 1, 2, ward, precinct, gender";

//$sql = "select distinct county, muni as town, chair_name, ward, precinct from dem_committee where muni <> '' order by 1, 2";

$query = mysqli_query($conn, $sql);

$query2 = mysqli_query($conn, $sql);

//echo "<br>".$sql."<br>";

?>


<html>
<head>
<title>BlueCompass.org</title>
<style type="text/css">	
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

ul.tree li {
    list-style-type: none;
    position: relative;
}

ul.tree li ul {
    display: none;
}

ul.tree li.open > ul {
    display: block;
}

ul.tree li a {
    color: black;
    text-decoration: none;
}

ul.tree li a:before {
    height: 1em;
    padding:0 .1em;
    font-size: .8em;
    display: block;
    position: absolute;
    left: -1.3em;
    top: .2em;
}

ul.tree li > a:not(:last-child):before {
    content: '+';
}

ul.tree li.open > a:not(:last-child):before {
    content: '-';
}

tr {
	border-bottom: 1pt solid black;
}

</style>
</head>

<body>
<table class="menu" border="0">
<tr>
<td><a href="../index.html" class="aa">Home</a></td>
<td><a href="../aboutus.html" class="aa">About Us</a></td>
<td><a href="../congress.html" class="aa">2018 NJ Congressional Elections</a></td>
<td><a href="../njcongress.html" class="aa">2019 NJ Assembly Elections</a></td>
<td><a href="../memories" class="aa">Memories</a></td>
<td><a href="../party" class="aa">Party Connection</a></td>
</tr>
</table>

<H2><center>Democratic Party Information</center></H2>

<a href="index.html">Party Home</a>&nbsp;
<a href="party_survey.html">Party Survey</a>&nbsp;
<a href="party.php">Party Committees</a>&nbsp;
<a href="https://drive.google.com/open?id=1vwfg4734qTQkKakjXUHpDw4xHFd7ApzB">Committee Member Role</a>

<br>
<br>
Find out local Democratic Party information by county and town. If you are a registered voter, you can find out your town Ward and Precinct as well as your Democratic Party Municipal Committee information and the members representing your Precinct <a href="party.html">here</a>.
<br>
<br>
<!--ul class="tree"-->
<?php
$i = 0;
$county = "";
$town = "";
//while($row = mysqli_fetch_array($query2)) {
//	$row = mysqli_fetch_array($query2);
	//print_r($row);
	
//	$county = $row["county"];
//	$town = $row["town"];
//	while($county == $row["county"]) {
//		echo "<li><a href=#t>".$county."</a>";
//		echo "<ul>";
//		while($town == $row["town"]) {
//			echo "<li><a href=#t>".$town."</a></li>";
//			$row = mysqli_fetch_array($query2);
//			$county = $row["county"];
//			$town = $row["town"];
//			$i++;
//			if($i > 30) {
//				break;
//			}
//		}
//		echo "</ul>"; // close town list
//		echo "</li>"; // close county list item
//		if($i > 30) {
//			break;
//		}
//	}

$ward = "";
$prec = "";
//while($row = mysqli_fetch_array($query2)) {
	//print_r($row);
//	if($county != $row["county"]) {
//		if($county != "") {
//			echo "</ul>"; // close town list
//		}
//		$county = $row["county"];
//		echo "<li><a href=#t>".$county."</a>";
//		echo "<ul>";
//	}
//	if($town != $row["town"]) {
//		$town = $row["town"];
//		echo "<li><a href=#t>".$town."</a> ".$row["chair_name"]."</li>";
//		echo "<ul>";
//	}
//	if($ward != $row["ward"]) {
//		if($muni != "") {
//			echo "</ul>"; // close ward list
//		}
//		$ward = $row["ward"];
//		echo "<li><a href=#t>".$ward."</a> ".$row["precinct"]."</li>";
//	}
//}
//echo "</ul>"; // close town list
//echo "</li>"; // close county list item
?>
<!--/ul-->

<br>
<table border="0" style="border-collapse:collapse;">
<tr>
<th><th>County</th><th width="200px">Municipality</th><th width="200px">Chairperson</th><th width="220px">Email</th>
<th width="230px">Website</th><th width="120px">Bylaws</th>
<th>Ward</th><th>Precinct</th><th width="180px">Member</th><th>Member</th>
</tr>
<?php
$county = "";
$town = "";
$ward = "";
$precinct = "";
$c = false;
$t = false;
$i = 0;
$parent = 0;
$parent2 = 0;
$wp = "";
$wp2 = "";
while($row = mysqli_fetch_array($query)) {
	//if($i < 4) {
	//	print_r($row);
	//}
	$i++;
	$closetr = false;
	if($county != $row["county"]) {
		$county = $row["county"];
		$town = "";
		$c = false;
		$parent++;
		echo "<tr bgcolor='#ADD8E6'>";
		echo "<td onclick=showhide(".$parent."); onMouseOver=this.style.cursor='pointer'><img id='".$parent."-img' src='../img/arrow-horiz.gif' width='15' height='15'></td>";
		echo "<td onclick=showhide(".$parent."); onMouseOver=this.style.cursor='pointer'>".$row["county"]."</td>";
		echo "<td></td>";
		echo "<td>".$row["chair_name"]."</td>";
		echo "<td>".$row["committee_email"]."</td>";
		echo "<td><a target='new' href='".$row["website"]."'>".$row["website"]."</td>";
		if(empty($row["bylaws"])) {
			echo "<td></td>";
		} else {
			echo "<td><a target='new' href='".$row["bylaws"]."'>Bylaws</td>";
		}
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
	} else {
		$c = true;
	}
	if($town != $row["town"]) {
		$town = $row["town"];
		$t = false;
		//echo "<tr>";
		$parent2++;
		echo "<tr bgcolor='#00BFFF' name='".$parent."' style='display:none;'>";
		echo "<td></td>";
		echo "<td onclick=showhide('".$parent."-".$parent2."'); onMouseOver=this.style.cursor='pointer'><img id='".$parent."-".$parent2."-img' src='../img/arrow-horiz.gif' width='15' height='15'></td>";
		echo "<td onclick=showhide('".$parent."-".$parent2."'); onMouseOver=this.style.cursor='pointer'>".$row["town"]."</td>";
		echo "<td>".$row["chair_name"]."</td>";
		echo "<td>".$row["committee_email"]."</td>";
		if(empty($row["website"])) {
			echo "<td></td>";
		} else {
			echo "<td><a target='new' href='".$row["website"]."'>".substr($row["website"],0, 30)."</td>";
		}
		if(empty($row["bylaws"])) {
			echo "<td></td>";
		} else {
			echo "<td><a target='new' href='".$row["bylaws"]."'>Bylaws</td>";
		}
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		$ward = $row["ward"];
		$precinct = $row["precinct"];
		$member = $row["member_name"];
	} else {
		$t = true;
	}
	if($c and $t) { // county and town rows displayed
		$wp = $row["ward"]."-".$row["precinct"];
		// display hidden members rows
		if(!empty($ward)) {
			// display the member row which came with the municipal row
			echo "<tr name='".$parent."-".$parent2."' style='display:none;'>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td>".$ward."</td>";
			echo "<td>".$precinct."</td>";
			echo "<td>".$member."</td>";
			$ward = "";
			$precinct = "";
			$member = "";
			echo "<td>".$row["member_name"]."</td>";
		} else {
			if($wp != $wp2) {
				$wp2 = $wp;				
				echo "<tr name='".$parent."-".$parent2."' style='display:none;'>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$row["ward"]."</td>";
				echo "<td>".$row["precinct"]."</td>";
				echo "<td>".$row["member_name"]."</td>";
				$closetr = false;
			} else {
				echo "<td>".$row["member_name"]."</td>";
			}
		}
	}
	if($closetr) {
		echo "</tr>";
	}
}
?>
</table>
<br>
<br>

<script>
var tree = document.querySelectorAll('ul.tree a:not(:last-child)');
for(var i = 0; i < tree.length; i++){
    tree[i].addEventListener('click', function(e) {
        var parent = e.target.parentElement;
        var classList = parent.classList;
        if(classList.contains("open")) {
            classList.remove('open');
            var opensubs = parent.querySelectorAll(':scope .open');
            for(var i = 0; i < opensubs.length; i++){
                opensubs[i].classList.remove('open');
            }
        } else {
            classList.add('open');
        }
    });
}

function showhide(p) {
	//alert("called showhide table-row function with " + p);
	var x = document.getElementsByName(p);
	var i;
	var disp;
	for (i = 0; i < x.length; i++) {
		if(x[i].style.display == "none") {
			x[i].style.display = "table-row";
			disp = true;
		} else {
			x[i].style.display = "none";
			disp = false;
		}
	}
	
	var a = p+"-img";
	var el = document.getElementById(a);
	//alert("change image for " + el);
	if(disp) {
		el.src = "../img/arrow-down.gif";
	} else {
		el.src = "../img/arrow-horiz.gif";
	}
}
</script>
<br>
</body>
</html>