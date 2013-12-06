<?php

class Groups_Plugin implements Plugin
{
	private $config;
	private $scrim_on;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Groups_Plugin'");
		
		$this->config = $config;
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Groups_Plugin'");
			
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
			
			if ($this->scrim_on)
				$this->ScrimMode();
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
	
	private function GetPlayerRank($player_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}player_groups WHERE server_id = {$this->config['id']} AND player_id = {$player_id}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) == 1)
			return $adb->query_result($result, 0, 'group_id');
		
		$query = "INSERT INTO {$adb->prefix}player_groups(player_id, server_id, group_id) VALUES ({$player_id}, {$this->config['id']}, 6)";
		$adb->query($query, true);
		
		// default to lowest rank
		return 6;
	}
	
	private function GetPlayer($name)
	{
		global $adb;
		
		if (is_numeric($name))
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} AND server_slot = {$name}";
		else
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} AND server_player_name LIKE '%{$name}%'";
		$result = $adb->query($query, false);
		
		if (empty($result) || $adb->num_rows($result) != 1)
			return false;
		
		$player_guid = $adb->query_result($result, 0, 'server_player_guid');
		
		return RconPlayers::Player($player_guid);
	}
	
	private function GetName($name)
	{
		global $adb;
		
		if (is_numeric($name))
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} AND server_slot = {$name}";
		else
			$query = "SELECT * FROM {$adb->prefix}players WHERE player_name LIKE '{$name}%'";
		$result = $adb->query($query, false);
		
		if (empty($result) || $adb->num_rows($result) != 1)
			return false;
		
		if (is_numeric($name))
			$player_name = $adb->query_result($result, 0, 'server_player_name');
		else
			$player_name = $adb->query_result($result, 0, 'player_name');
			
		return $player_name;
	}
		
	private function GetGUID($name)
	{
		global $adb;
		
		if (is_numeric($name))
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} AND server_slot = {$name}";
		else
			$query = "SELECT * FROM {$adb->prefix}players WHERE player_name LIKE '{$name}%'";
		$result = $adb->query($query, false);
		
		if (empty($result) || $adb->num_rows($result) != 1)
			return false;
		
		if (is_numeric($name))
			$player_guid = $adb->query_result($result, 0, 'server_player_guid');
		else
			$player_guid = $adb->query_result($result, 0, 'player_guid');
			
		return $player_guid;
	}
	
	private function ProcessCommand($data)
	{
		global $adb;
		
		$rcon_commands = array();
		
		$message_data = explode(" ", $data['message']);
		$command = substr($message_data[0], 1);
		
		if ($command == 'promote' || $command == 'demote')
		{
			$player = RconPlayers::Player($data['guid']);
			
			if ($player !== false)
			{
				if (count($message_data) == 2 || count($message_data) == 3)
				{
					$target_player = $this->GetPlayer($message_data[1]);
					
					if ($target_player !== false)
					{
						$player_rank = $this->GetPlayerRank($player);
						$current_rank = $this->GetPlayerRank($target_player);
						
						if (count($message_data) == 3)
						{
							if (is_numeric($message_data[2]))
								$target_rank = $message_data[2];
							else
								$target_rank = $this->GroupID($message_data[2]);
						}
						else if ($command == 'promote')
						{
							$target_rank = $current_rank - 1;
						}
						else
						{
							$target_rank = $current_rank + 1;
						}
	
						if ($target_rank > 0 && $player_rank < $current_rank && $player_rank <= $target_rank)
						{
							$query = "UPDATE {$adb->prefix}player_groups SET group_id = {$target_rank} WHERE server_id = {$this->config['id']} AND player_id = {$target_player}";
							$adb->query($query, true);
							
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Success";
							$rcon_commands[] = "say ^1" . $this->PlayerName($target_player) . " ^7is now in the ^1" . $this->GroupName($target_rank) . " ^7 group";
						}
						else if ($target_rank <= 0)
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7The group name you entered doesn't exist";
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You cannot promote/demote above your level";
						}
					}
					else
					{
						$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Target player not found.";
					}
				}
			}
			else
			{
				$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Could not find your user data, please try again in a moment.";
			}
			
			if (count($rcon_commands) > 0)
				$this->ProcessRcon($rcon_commands);
			
			return true;
		}
		else if ($command == 'group')
		{
			if (count($message_data) == 1)
			{
				// what is your rank
				$target_player = RconPlayers::Player($data['guid']);

				if ($target_player !== false)
				{
					$target_rank = $this->GetPlayerRank($target_player);
					$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You are in the ^1" . $this->GroupName($target_rank) . " ^7group";
				}
			}
			else
			{
				// who is in group #?
				if (is_numeric($message_data[1]))
					$which_group = $message_data[1];
				else
					$which_group = $this->GroupID($message_data[1]);
					
				$query = "SELECT * FROM {$adb->prefix}players INNER JOIN {$adb->prefix}player_groups ON {$adb->prefix}players.player_id = {$adb->prefix}player_groups.player_id WHERE server_id = {$this->config['id']} AND group_id = {$which_group} ORDER BY player_name";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					// player group... crazy results
					if ($which_group == 6)
					{
						$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7There are a total of ^1" . $adb->num_rows($result) . " ^7 in the ^1" . $this->GroupName($which_group) . " ^7group";
					}
					else
					{
						$next_command = '';
						$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7The following players are in the ^1" . $this->GroupName($which_group) . " ^7group";
						
						for ($count = 0; $count < $adb->num_rows($result); $count++)
						{
							if ($next_command != '')
								$next_command .= ', ';
							
							$name = $adb->query_result($result, $count, 'player_name');
							$next_command .= "{$name}";
							
							if (strlen($next_command) > 50)
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7{$next_command}";
								$next_command = '';
							}
						}
	
						if ($next_command != '')
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7{$next_command}";
					}
				}
				else
				{
					$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7There are no players in the ^1" . $this->GroupName($which_group) . " ^7group";
				}
			}
			
			if (count($rcon_commands) > 0)
				$this->ProcessRcon($rcon_commands);
			
			return true;
		}
		else if ($command == 'players')
		{
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} ORDER BY server_slot";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				$next_command = '';
				
				for ($count = 0; $count < $adb->num_rows($result); $count++)
				{
					if ($next_command != '')
						$next_command .= ', ';
					
					$slot = $adb->query_result($result, $count, 'server_slot');
					$name = $adb->query_result($result, $count, 'server_player_name');
					
					$next_command .= "^1{$slot}. ^7{$name}";
					
					if (strlen($next_command) > 50)
					{
						$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7{$next_command}";
						$next_command = '';
					}
				}

				if ($next_command != '')
						$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7{$next_command}";
			}
					
			if (count($rcon_commands) > 0)
				$this->ProcessRcon($rcon_commands);
			
			return true;
		}
		else if ($command == 'vip')
		{
			$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} ORDER BY server_player_score";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				if ($adb->num_rows($result) < 18)
				{
					$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7There are slots free";
				}
				else
				{
					for ($count = 0; $count < $adb->num_rows($result); $count++)
					{
						$guid = $adb->query_result($result, $count, 'server_player_guid');
						$player = RconPlayers::Player($guid);
						
						if ($player !== false)
						{
							$player_rank = $this->GetPlayerRank($player);
							
							if ($player_rank == 6)
							{
								// get the player with the lowest score who is rank 6
								$slot = $adb->query_result($result, $count, 'server_slot');
								$name = $adb->query_result($result, $count, 'server_name');
								
								$rcon_commands[] = "clientkick {$slot} Reserved_Slot";
								$rcon_commands[] = "say {$name} kicked for Reserved Slot";
								
								break;
							}
						}
					}
					
					if (count($rcon_commands) == 0)
						$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7There are no VIP slots available";
				}
			}

			if (count($rcon_commands) > 0)
				$this->ProcessRcon($rcon_commands);
			
			return true;
		}
		else if ($command == 'timedban')
		{
			$message_data = explode(" ", $data['message'], 4);
			
			$player_name = -1;
			$player_guid = -1;
			$player_slot = 0;
			
			if (count($message_data) == 4)
			{
				$ban_length = $message_data[2];
				$ban_reason = $message_data[3];
				
				if (!is_numeric($ban_length))
				{
					$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7The length of the timed ban needs to be numeric";
				}
				else
				{
					$player_guid = $this->GetGUID($message_data[1]);
					$player_name = $this->GetName($message_data[1]);
					
					if ($player_guid == false || $player_name == false)
					{
						$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7That player was not found, try using slot number instead";
					}
					else
					{
						$player = RconPlayers::Player($player_guid);
						
						if ($player !== false)
						{
							if ($player_slot == 0)
							{
								// find slot by guid
								$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_player_guid = '{$player_guid}' AND server_id = {$this->config['id']}";
								$result = $adb->query($query, false);
								
								if (!empty($result) && $adb->num_rows($result) == 1)
									$player_slot = $adb->query_result($result, 0, 'server_slot');
							}
							
							$query = "INSERT INTO {$adb->prefix}server_bans (player_id, server_id, server_ban_length, server_ban_reason) VALUES ({$player}, {$this->config['id']}, {$message_data[2]}, '{$message_data[3]}')";
							$adb->query($query, true);
							
							$query = "INSERT INTO {$adb->prefix}player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES ({$player}, {$this->config['id']}, 5, UNIX_TIMESTAMP(NOW()), '{$player_name}',  'Timed Ban ({$message_data[2]} days)', '{$message_data[3]}')";
							$adb->query($query, true);
							
							if ($player_slot != 0)
							{
								if ($ban_length == -1)
								{
									$rcon_commands[] = "banclient {$player_slot}";
									$rcon_commands[] = "clientkick {$player_slot} Banned_for_" . str_replace(" ", "_" , $ban_reason);
								}
								else
								{
									$rcon_commands[] = "tempbanclient {$player_slot} " . str_replace(" ", "_", "Ban Length (days): {$ban_length} Reason: {$ban_reason}");
								}
							}
							
							if ($ban_length == -1)
								$rcon_commands[] = "say ^1{$player_name} ^7has been permanently banned for ^1{$ban_reason}";
							else
								$rcon_commands[] = "say ^1{$player_name} ^7has been banned for ^1{$ban_length} ^7days because of ^1{$ban_reason}";
						}
						else
						{
							$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7That player was not found, try using slot number instead";
						}
					}
				}
			}
			else
			{
				$rcon_commands[]  = "tell {$data['slot']} ^1pm: ^7Required format '!timedban Player Days Reason'";
			}
			
			if (count($rcon_commands) > 0)
				$this->ProcessRcon($rcon_commands);
			
			return true;
		}
		else if ($command == 'scrim')
		{
			if (strtolower($message_data[1]) == 'on' || $message_data[1] == '1')
			{
				$this->scrim_on = true;
				Rcon::Command("tell {$data['slot']} ^1pm: ^7Success: Scrim mode is now on" , false);
			}
			else if (strtolower($message_data[1]) == 'off' || $message_data[1] == '0')
			{
				$this->scrim_on = false;
				Rcon::Command("tell {$data['slot']} ^1pm: ^7Success: Scrim mode is now off" , false);
			}
			else
				Rcon::Command("tell {$data['slot']} ^1pm: ^7Command is !scrim on, !scrim off, !scrim 1 or !scrim 0" , false);
			
			return true;
		}
		else if ($command == 'guid')
		{
			if (count($message_data) == 1)
			{
				Rcon::Command("tell {$data['slot']} ^1pm: ^7Your GUID is ^1" . $this->GetGUID($data['slot']));
			}
			else
			{
				$message_data = explode(" ", $data['message'], 2);
				
				$player = $this->GetName($message_data[1]);
				$guid = $this->GetGUID($message_data[1]);
				
				if ($player == false || $guid == false)
					Rcon::Command("tell {$data['slot']} ^1pm: ^7Unknown player '{$message_data[1]}'");
				else
					Rcon::Command("tell {$data['slot']} ^1pm: ^7{$player}'s GUID is ^1{$guid}");
			}
		}
		
		return false;
	}
	
	private function ScrimMode()
	{
		global $adb;
		
		$rcon_commands = array();
		
		// check for players that are in group 6
		$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} ORDER BY server_slot";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$player_slot = $adb->query_result($result, 0, 'server_slot');
				$player_guid = $adb->query_result($result, 0, 'server_player_guid');
				
				$player = RconPlayers::Player($player_guid);
				
				if ($player !== false)
				{
					$player_rank = $this->GetPlayerRank($player);
					
					if ($player_rank == 6)
						$rcon_commands[] = "clientkick {$player_slot} Sorry_currently_Scrimming";
				}
			}
		}
		
		if (count($rcon_commands) > 0)
		{
			$rcon_commands[] = "say Kicking players because the server is in scrim mode - to disable use ^1!scrim off";
			$this->ProcessRcon($rcon_commands);
		}
	}
	
	private function ProcessRcon($commands)
	{
		$success = true;
		
		for ($count = 0; $count < count($commands) && $success; $count++)
		{
			$success = Rcon::Command($commands[$count], false);
		}
		
		return $success;
	}
	
	private function PlayerName($player_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}players WHERE player_id = {$player_id}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) == 1)
			return $adb->query_result($result, 0, 'player_name');
			
		return $player_id;
	}

	private function GroupID($group_name)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}groups WHERE group_name = '{$group_name}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) == 1)
			return $adb->query_result($result, 0, 'group_id');
			
		return -1;
	}

	private function GroupName($group_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}groups WHERE group_id = {$group_id}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) == 1)
			return $adb->query_result($result, 0, 'group_name');
			
		return $group_id;
	}
	
	private function ProcessMessages($server_id)
	{
		global $adb;
		
		$data = array();
		$total_processed = 0;
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = 'P' OR server_log_command = 'G') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = $adb->query_result($result, $count, 'server_log_data');
				$message_data = $this->SplitMessage($message);
				
				if ($message_data['message'][0] == '!')
				{
					$data[$total_processed] = $message_data;
					$data[$total_processed]['log_id'] = $adb->query_result($result, $count, 'server_log_id');
				
					$total_processed++;
				}
			}
		}
		
		return $data;
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
	
	private function RemoveRecord($log_id)
	{
		global $adb;
				
		$clear_data = "DELETE FROM {$adb->prefix}server_log WHERE server_log_id = {$log_id}";
		$adb->query($clear_data, true);
	}
}
?>
