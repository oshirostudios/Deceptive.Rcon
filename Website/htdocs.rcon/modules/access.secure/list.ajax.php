<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_ACCESS))
		die();

	$userid = $_REQUEST['userid'];

	$query = "SELECT * FROM {$adb->prefix}server_users WHERE server_id = {$server} AND user_id = {$userid}";
	$result = $adb->query($query, false);

	$access = array('Overview', 'Servers', 'History', 'Statistics', 'Restrictions', 'Map Rotation', 'Messages', 'Commands', 'Console');

	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$permissions = $adb->query_result($result, 0, 'server_user_permissions');

		for ($count = 0; $count < count($access); $count++)
		{
			if ($count < strlen($permissions) && $permissions[$count] == '1')
				echo "<label for=\"access-{$count}\" class=\"fixed-140\"><input id=\"access-{$count}\" class=\"basic\" type=\"checkbox\" name=\"access-{$count}\" value=\"1\" checked> {$access[$count]}</label>\n";
			else
				echo "<label for=\"access-{$count}\" class=\"fixed-140\"><input id=\"access-{$count}\" class=\"basic\" type=\"checkbox\" name=\"access-{$count}\" value=\"1\"> {$access[$count]}</label>\n";
				
			if (($count + 1) % 4 == 0)
				echo "<br/>\n";
		}
	}
}
else
{
	echo 'Your session is invalid' . "\n";
}

?>
