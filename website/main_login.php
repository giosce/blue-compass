<?php
session_start();
//echo "Session: ".$_SESSION["origin"];
?>

<table width="300" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="form1" method="post" action="checklogin.php">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">
<tr>
<td colspan="3"><strong>Member Login </strong></td>
</tr>
<tr>
<td width="78">Username</td>
<td width="6"></td>
<td width="294"><input name="myusername" type="text" id="myusername"></td>
</tr>
<tr>
<td>Password</td>
<td></td>
<td><input name="mypassword" type="password" id="mypassword"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>
<input type="hidden" name="path" value="<?php echo $_SESSION['origin'];?>">
<input type="submit" name="Submit" value="Login">&nbsp;
<input class="button" type="button" onclick="window.history.back()" value="Cancel" />
</td>
</tr>
</table>
</td>
</form>
</tr>
</table>