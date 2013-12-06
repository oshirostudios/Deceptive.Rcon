<?php

class Rotation_Plugin implements Plugin
{
	private $config;
	private $state;
	
	private $last_rotation;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Rotation_Plugin'");
		
		$this->config = $config;
		$this->state = RconServer::GetServerStatus(1);
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Rotation_Plugin'");
			
			$rotate_required = false;
			
			$current_status = RconServer::GetServerStatus(1);
			$next_status = RconServer::GetServerStatus(2);
			
			// don't do anything while the current status is unknown
			if ($current_status !== false)
			{
				if (($current_status['map'] != $this->state['map']) || 
						(isset($current_status['playlist']) && isset($this->state['playlist']) && $current_status['playlist'] != $this->state['playlist']) ||
						(!isset($current_status['playlist']) && !isset($this->state['playlist']) && $current_status['game'] != $this->state['game']) ||
						(isset($current_status['playlist']) && !isset($this->state['playlist'])) ||
						(!isset($current_status['playlist']) && isset($this->state['playlist'])))
				{
					$this->ClearWarnings();
					
					// is the next status available?
					if ($next_status === false)
					{
						$rotate_required = true;
						$next_rotation = 1;
					}
					// is the current status the same as the next status?
					else if ($current_status['map'] == $next_status['map'] && 
						(isset($current_status['playlist']) && isset($next_status['playlist']) && ($current_status['playlist'] == $next_status['playlist'])) ||
						(!isset($current_status['playlist']) && !isset($next_status['playlist']) && ($current_status['game'] == $next_status['game'])))
					{
						if (isset($next_status['playlist']))
							RconServer::SetServerStatus(1, $next_status['map'], $next_status['playlist'], $next_status['rotation']);
						else
							RconServer::SetServerStatus(1, $next_status['map'], -1, $next_status['rotation'], $next_status['game']);
						
						$rotate_required = true;
						$next_rotation = $next_status['rotation'] + 1;
					}
					// set the rotation back to the current order
					else
					{
						if (isset($next_status['playlist']))
							$this->SetRotation($next_status['map'], $next_status['playlist']);
						else
							$this->SetGame($next_status['map'], $next_status['game']);
					}
					
					$this->state = $current_status;
				}
				else
				{
					if ($next_status === false)
					{
						$rotate_required = true;
					}
				}
				
				if ($rotate_required)
				{
					$rotation = $this->GetRotation($next_rotation, $current_status);
					
					if (isset($rotation['playlist']))
					{
						if ($this->SetRotation($rotation['map'], $rotation['playlist']))
							RconServer::SetServerStatus(2, $rotation['map'], $rotation['playlist'], $rotation['rotation']);
					}
					else
					{
						if ($this->SetGame($rotation['map'], $rotation['game']))
							RconServer::SetServerStatus(2, $rotation['map'], -1, $rotation['rotation'], $rotation['game']);
					}
				}
			}
			
			$messages = $this->ProcessMessages($this->config["id"]);
			$total_messages = count($messages);	
		
			if ($messages !== false && $total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - found {$total_messages} messages");
			
				for ($count = 0; $count < $total_messages; $count++)
				{
					if ($this->ProcessCommand($messages[$count]))
						$this->RemoveRecord($messages[$count]['log_id']); 
				}
			}
		}
	}
	
	public function State()
	{
		return true;
	}
	
	public function Priority()
	{
		return PRIORITY_LOW;
	}
	
	private function GetActiveRotationGroup()
	{
		global $adb;
		
		$group_id = -1;

		$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE server_id = {$this->config['id']} AND rotation_group_active = 1";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$group_id = $adb->query_result($result, 0, 'rotation_group_id');
		}
		else
		{
			$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE server_id = {$this->config['id']} ORDER BY rotation_group_id";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				$group_id = $adb->query_result($result, 0, 'rotation_group_id');
	
				$query = "UPDATE {$adb->prefix}rotation_group SET rotation_group_active = 1 WHERE server_id = {$this->config['id']} AND rotation_group_id = {$group_id}";
				$adb->query($query, true);
			}
		}
		
		return $group_id;
	}
	
	private function GetRotation($next_rotation, $current_status)
	{
		global $adb;
		$data = array();
		
		$group_id = $this->GetActiveRotationGroup();
		
		$query = "SELECT * FROM {$adb->prefix}rotation WHERE rotation_group_id = {$group_id} ORDER BY rotation_sort";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$total_rotations = $adb->num_rows($result);
			
			if ($next_rotation == 0 || $next_rotation > $total_rotations)
				$next_rotation = 1;
				
			for ($count = 0; $count < $total_rotations; $count++)
			{
				$rotation = $adb->query_result($result, $count, 'rotation_sort');
				
				if ($rotation == $next_rotation)
				{
					$data['map'] = $adb->query_result($result, $count, 'map_id');
					
					if ($adb->query_result($result, $count, 'mode_type_id') != -1)
						$data['playlist'] = $adb->query_result($result, $count, 'mode_type_id');
					else
						$data['game'] = $adb->query_result($result, $count, 'game_id');
				}
			}
		}
		else
		{
			// randomise the standard maps in the current playlist
			$data['map'] = rand(1, 14);
			
			if (isset($current_status['playlist']))
				$data['playlist'] = $current_status['playlist'];
			else
				$data['game'] = $current_status['game'];

			$next_rotation = 0;
		}

		$data['rotation'] = $next_rotation;
		
		return $data;
	}
	
	private function SetRotation($map_id, $playlist, $announce = false)
	{
		$commands = array();
		$success = true;
		
		$map_file = RconServer::GetMapFile($map_id);
		$inverted_maps = RconServer::GetInvertedMaps($map_id);
		
		$commands[] = "setadmindvar sv_mapRotation {$map_file}";
		$commands[] = "setadmindvar playlist {$playlist}";
		$commands[] = "setadmindvar playlist_excludeMap {$inverted_maps}";
		$commands[] = "setadmindvar playlist_enabled 1";
		
		if ($announce)
			$commands[] = "say Rotation set to '{$map_file} (" . $playlist . ")'";
		
		for ($count = 0; $count < count($commands) && $success; $count++)
		{
			$success = Rcon::Command($commands[$count], false);
		}

		if ($success)
			System_Daemon::log(System_Daemon::LOG_INFO, " - rotation set to '{$map_file} (" . $playlist . ")'");
		
		return $success;
	}
	
	private function SetGame($map_id, $game, $announce = false)
	{
		$commands = array();
		$success = true;
		
		$map_file = RconServer::GetMapFile($map_id);
		$inverted_maps = RconServer::GetInvertedMaps($map_id);
		$game_value = RconServer::GetGame($game);
		
		$commands[] = "setadmindvar sv_mapRotation {$map_file}";
		$commands[] = "setadmindvar playlist 0";
		$commands[] = "setadmindvar playlist_enabled 0";
		$commands[] = "setadmindvar g_gametype {$game_value}";
		
		if ($announce)
			$commands[] = "say Rotation set to '{$map_file} ({$game_value})'";
		
		for ($count = 0; $count < count($commands) && $success; $count++)
		{
			$success = Rcon::Command($commands[$count], false);
		}

		if ($success)
			System_Daemon::log(System_Daemon::LOG_INFO, " - rotation set to '{$map_file} ({$game_value})'");
		
		return $success;
	}
	
	private function ProcessMessages($server_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = 'P' OR server_log_command = 'G') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = $adb->query_result($result, $count, 'server_log_data');

				$data[$count] = $this->SplitMessage($message);
				$data[$count]['log_id'] = $adb->query_result($result, $count, 'server_log_id');
			}
		
			return $data;
		}
		
		return false;
	}
	
	private function SplitMessage($message)
	{
		$data = array();
		
		$start = 0; $end = strpos($message, ";");
		$data['guid'] = substr($message, $start, $end);
		
		$start = $end + 1; $end = strpos($message, ";", $start);
		$data['slot'] = substr($message, $start, ($end - $start));
		
		$start = $end + 1; $end = strpos($message, ";", $start);
		$data['name'] = substr($message, $start, ($end - $start));
		
		$start = $end + 2;
		$data['message'] = substr($message, $start);
		
		return $data;
	}
	
	private function ProcessCommand($data)
	{
		global $adb;

		if ($data['message'] == '!nextmap')
		{
			$message = 'say The next map is not currently set';
			$server_data = $this->GetServerData();
			
			if ($server_data !== false)
			{
				$map_query = "SELECT map_name FROM {$adb->prefix}maps WHERE map_file = '{$server_data['map']}'";
				$map_result = $adb->query($map_query, false);
				$map_name = $adb->query_result($map_result, 0, 'map_name');
			
				$game_query = "SELECT mode_name, type_name, mode_type_players FROM (({$adb->prefix}mode_types INNER JOIN {$adb->prefix}modes ON {$adb->prefix}mode_types.mode_id = {$adb->prefix}modes.mode_id) INNER JOIN {$adb->prefix}types ON {$adb->prefix}mode_types.type_id = {$adb->prefix}types.type_id) WHERE mode_type_id = {$server_data['playlist']}";
				$game_result = $adb->query($game_query, false);
				
				$mode_name = $adb->query_result($game_result, 0, 'mode_name');
				$type_name = $adb->query_result($game_result, 0, 'type_name');
				$players = $adb->query_result($game_result, 0, 'mode_type_players');
				
				if ($mode_name != "Regular")
					$type_name = "{$mode_name} {$type_name}";
				
				$message = "say The next map is ^1{$map_name} ^7playing ^1{$type_name} ^7with ^1{$players} slots";
			}
			else
			{
				$query = "SELECT * FROM {$adb->prefix}server_status WHERE server_id = {$this->config['id']} AND server_status_id = 2";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$map_id = $adb->query_result($result, 0, 'map_id');
					$mode_type_id = $adb->query_result($result, 0, 'mode_type_id');
				
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
					
					$message = "say The current rotation is set to ^1{$map_name} ^7playing ^1{$type_name} ^7with ^1{$players} slots";
				}
			}
			
			return Rcon::Command($message, false);
		}
		else if ($data['message'] == '!stuck')
		{
			$next_rotation = 1;
			$current_status = RconServer::GetServerStatus(1);
			$next_status = RconServer::GetServerStatus(2);
			
			if ($next_status !== false)
				$next_rotation = $next_status['rotation'] + 1;
			
			$rotation = $this->GetRotation($next_rotation, $current_status);
			
			if (isset($rotation['playlist']))
			{
				if ($this->SetRotation($rotation['map'], $rotation['playlist'], true))
					RconServer::SetServerStatus(2, $rotation['map'], $rotation['playlist'], $rotation['rotation']);
			}
			else
			{
				if ($this->SetGame($rotation['map'], $rotation['game'], true))
					RconServer::SetServerStatus(2, $rotation['map'], -1, $rotation['rotation'], $rotation['game']);
			}
						
			return true;
		}
		
		return false;
	}
	
	private function RemoveRecord($log_id)
	{
		global $adb;
				
		$clear_data = "DELETE FROM {$adb->prefix}server_log WHERE server_log_id = {$log_id}";
		$adb->query($clear_data, true);
	}
	
	private function GetServerData()
	{
		$data = array();
		
		$response = Rcon::Command('sv_mapRotation', true);
		$response = str_replace("^7", "", $response);
		
		$start = strpos($response, 'is: "');
		
		if ($start !== false)
		{
			$start += 5;
			$end = strpos($response, '"', $start);
			
			$data['map'] = substr($response, $start, $end - $start);
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
		
		if (count($data) == 2)
			return $data;
			
		return false;
	}
	
	public function ClearWarnings()
	{
		global $adb;
		
		$query = "SELECT server_restriction_id FROM {$adb->prefix}server_restrictions WHERE server_id = {$this->config['id']}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$restriction = $adb->query_result($result, $count, 'server_restriction_id');
				$adb->query("DELETE FROM {$adb->prefix}warnings WHERE server_restriction_id = {$restriction}", true);
			}
		}
		
		$adb->query("DELETE FROM {$adb->prefix}server_damage WHERE server_id = {$this->config['id']}", true);
	}
}

?>
