<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_COMMANDS))
		die();

	$group = $_REQUEST['group'];

	$query = "SELECT * FROM {$adb->prefix}server_commands WHERE server_id = {$server} AND group_id = {$group}";
	$result = $adb->query($query, false);

	$access = array();

	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		for ($count = 0; $count < $adb->num_rows($result); $count++)
		{
			$command = $adb->query_result($result, $count, 'command_id');
			$access[$command] = $adb->query_result($result, $count, 'server_command_access');
		}
	}

	if ($user->Server($server)->Ranked())
		$query = "SELECT command_id, command_name FROM {$adb->prefix}commands WHERE (server_id = {$server} OR server_id = 0) AND command_visible = 1 AND command_unranked = 0 ORDER BY command_name";
	else
		$query = "SELECT command_id, command_name FROM {$adb->prefix}commands WHERE (server_id = {$server} OR server_id = 0) AND command_visible = 1 ORDER BY command_name";
	
	$result = $adb->query($query, false);

	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		for ($count = 0; $count < $adb->num_rows($result); $count++)
		{
			$command = $adb->query_result($result, $count, 'command_id');
			$name = $adb->query_result($result, $count, 'command_name');
			
			$has_access = false;
			
			if (array_key_exists($command, $access))
				$has_access = $access[$command];
				
			if ($has_access == 1)
				echo "<label for=\"command-{$command}\" class=\"fixed-140\"><input id=\"command-{$command}\" class=\"basic\" type=\"checkbox\" name=\"command-{$command}\" value=\"1\" checked> {$name}</label>\n";
			else
				echo "<label for=\"command-{$command}\" class=\"fixed-140\"><input id=\"command-{$command}\" class=\"basic\" type=\"checkbox\" name=\"command-{$command}\" value=\"1\"> {$name}</label>\n";
				
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
