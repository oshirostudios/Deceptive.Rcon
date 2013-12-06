<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

require_once("include/Rcon.php");

function GetServerData($server_id)
{
	global $adb;
	
	$query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$server_id}";
	$result = $adb->query($query, false);

	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$server_ip = $adb->query_result($result, 0, 'server_ip');
		$server_port = $adb->query_result($result, 0, 'server_port');
		$rcon_password = $adb->query_result($result, 0, 'server_rcon_password');
		
		Rcon::Setup($server_ip, $server_port, $rcon_password);
			
		$data = array();
		
		$response = Rcon::Command('sv_mapRotation', true);
		$response = str_replace("^7", "", $response);
		
		$start = strpos($response, 'is: "');
		
		if ($start !== false)
		{
			$start += 5;
			$end = strpos($response, '"', $start);
			
			$data['map'] = substr($response, $start, $end - $start);
			
			if (strlen($data['map']) == 0)
				return false;
		}
		
		$response = Rcon::Command('playlist', true);
		$response = str_replace("^7", "", $response);
		
		$start = strpos($response, 'is: "');
		
		if ($start !== false)
		{
			$start += 5;
			$end = strpos($response, '"', $start);
			
			$data['playlist'] = substr($response, $start, $end - $start);
		}

		$response = Rcon::Command('g_gametype', true);
		$response = str_replace("^7", "", $response);
		
		$start = strpos($response, 'Unknown command');
		
		if (strpos($response, 'Unknown command') != 0)
		{
			$game_type = 'tdm';
			$data['game'] = $game_type;
			
			if ($data['playlist'] == 0 && $data['game'] == 'tdm')
				$data['playlist'] = 1;
		}
		
		if (count($data) == 3)
			return $data;
	}
	
	return false;
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_OVERVIEW))
		die();
	
	$query = "SELECT * FROM {$adb->prefix}server_status WHERE server_id = $server AND server_status_id = 1";
	$result = $adb->query($query, false);
	
	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$map_id = $adb->query_result($result, 0, 'map_id');
		$mode_type_id = $adb->query_result($result, 0, 'mode_type_id');
		$game_id = $adb->query_result($result, 0, 'game_id');
		
		$map_query = "SELECT map_name FROM {$adb->prefix}maps WHERE map_id = {$map_id}";
		$map_result = $adb->query($map_query, false);
		$map_name = $adb->query_result($map_result, 0, 'map_name');
		
		if ($mode_type_id != -1)
		{
			$game_query = "SELECT mode_name, type_name, mode_type_players FROM (({$adb->prefix}mode_types INNER JOIN {$adb->prefix}modes ON {$adb->prefix}mode_types.mode_id = {$adb->prefix}modes.mode_id) INNER JOIN {$adb->prefix}types ON {$adb->prefix}mode_types.type_id = {$adb->prefix}types.type_id) WHERE mode_type_id = {$mode_type_id}";
			$game_result = $adb->query($game_query, false);
			
			$mode_name = $adb->query_result($game_result, 0, 'mode_name');
			$type_name = $adb->query_result($game_result, 0, 'type_name');
			$players = $adb->query_result($game_result, 0, 'mode_type_players');
			
			if ($mode_name != "Regular")
				$type_name = "{$mode_name} {$type_name}";
			
			echo "<div class=\"serverstate map-{$map_id}\"><span style=\"font-size: 18px;\">Current:</span><br />{$players}-man<br />{$type_name}<br />{$map_name}</div><div style=\"clear: both;\"></div>\n";
		}
		else
		{
			$game_query = "SELECT game_name FROM {$adb->prefix}game WHERE game_id = {$game_id}";
			$game_result = $adb->query($game_query, false);
			$game_name = $adb->query_result($game_result, 0, 'game_name');
			
			echo "<div class=\"serverstate map-{$map_id}\"><span style=\"font-size: 18px;\">Current:</span><br />{$game_name}<br />{$map_name}</div><div style=\"clear: both;\"></div>\n";
		}
	
		$server_data = GetServerData($server);
			
		if ($server_data !== false)
		{
			$map_query = "SELECT * FROM {$adb->prefix}maps WHERE map_file = '{$server_data['map']}'";
			$map_result = $adb->query($map_query, false);
			$map_id = $adb->query_result($map_result, 0, 'map_id');
			$map_name = $adb->query_result($map_result, 0, 'map_name');
		
			if ($data['playlist'] == '0')
			{
				$game_query = "SELECT game_name FROM {$adb->prefix}game WHERE game_value = {$server_data['game']}";
				$game_result = $adb->query($game_query, false);
				
				$game_name = $adb->query_result($game_result, 0, 'game_name');

				echo "<div class=\"serverstate map-{$map_id}\"><span style=\"font-size: 18px;\">Next:</span><br />{$game_name}<br />{$map_name}</div><div style=\"clear: both;\"></div>\n";
			}
			else
			{
				$game_query = "SELECT mode_name, type_name, mode_type_players FROM (({$adb->prefix}mode_types INNER JOIN {$adb->prefix}modes ON {$adb->prefix}mode_types.mode_id = {$adb->prefix}modes.mode_id) INNER JOIN {$adb->prefix}types ON {$adb->prefix}mode_types.type_id = {$adb->prefix}types.type_id) WHERE mode_type_id = {$server_data['playlist']}";
				$game_result = $adb->query($game_query, false);
				
				$mode_name = $adb->query_result($game_result, 0, 'mode_name');
				$type_name = $adb->query_result($game_result, 0, 'type_name');
				$players = $adb->query_result($game_result, 0, 'mode_type_players');
				
				if ($mode_name != "Regular")
					$type_name = "{$mode_name} {$type_name}";
				
				echo "<div class=\"serverstate map-{$map_id}\"><span style=\"font-size: 18px;\">Next:</span><br />{$players}-man<br />{$type_name}<br />{$map_name}</div><div style=\"clear: both;\"></div>\n";
			}
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
