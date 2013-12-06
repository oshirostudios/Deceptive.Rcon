<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_OVERVIEW))
		die();
	
	$query = "SELECT * FROM {$adb->prefix}server_status WHERE server_id = $server ORDER BY server_status_id";
	$result = $adb->query($query, false);
	
	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$type = "Current";
		
		for ($count = 0; $count < 2; $count++)
		{
			$map_id = $adb->query_result($result, $count, 'map_id');
			$mode_type_id = $adb->query_result($result, $count, 'mode_type_id');
		
			$map_query = "SELECT map_name FROM {$adb->prefix}maps WHERE map_id = {$map_id}";
			$map_result = $adb->query($map_query, false);
			$map_name = $adb->query_result($map_result, 0, 'map_name');
		
			$game_query = "SELECT mode_name, type_name, mode_type_players FROM (({$adb->prefix}mode_types INNER JOIN {$adb->prefix}modes ON {$adb->prefix}mode_types.mode_id = {$adb->prefix}modes.mode_id) INNER JOIN {$adb->prefix}types ON {$adb->prefix}mode_types.type_id = {$adb->prefix}types.type_id) WHERE mode_type_id = {$mode_type_id}";
			$game_result = $adb->query($game_query, false);
			
			$mode_name = $adb->query_result($game_result, 0, 'mode_name');
			$type_name = $adb->query_result($game_result, 0, 'type_name');
			$players = $adb->query_result($game_result, 0, 'mode_type_players');
			
			if ($mode_name != "Regular")
				$type_name = "{$mode_name} {$type_name}";
		
			echo "<div class=\"serverstate map-{$map_id}\"><span style=\"font-size: 18px;\">{$type}:</span><br />{$players}-man<br />{$type_name}<br />{$map_name}</div><div style=\"clear: both;\"></div>\n";
			$type = "Next";
		}
	}
	else
	{
		echo 'The server status has not yet updated' . "\n";
	}
}
else
{
	echo 'Your session is invalid' . "\n";
}

?>
