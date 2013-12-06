<?php

class Statistics_Plugin implements Plugin
{
	private $config;
	private $reset_string;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Statistics_Plugin'");
		$this->config = $config;
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Statistics_Plugin'");
			
			$messages = $this->ProcessMessages($this->config["id"]);
			$total_messages = count($messages);	
		
			if ($messages !== false && $total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_messages} damage data");
			
				for ($count = 0; $count < $total_messages; $count++)
				{
					if ($this->ProcessData($messages[$count]))
						$this->RemoveRecord($messages[$count]['log_id']); 
				}
			}
	
			$messages = $this->ProcessMessages($this->config["id"], array('P', 'G'));
			$total_messages = count($messages);	
		
			if ($messages !== false && $total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - found {$total_messages} messages");
			
				for ($count = 0; $count < $total_messages; $count++)
				{
					if ($this->ProcessData($messages[$count]))
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
	
	private function ProcessData($data)
	{
		global $adb;
		
		// was this kill data?
		if ($data['type'] == 'K')
		{
			// was this a suicide
			if ($data['attacker']['slot'] == -1)
			{
				RconPlayers::Suicide($data['target']['slot']);
			}
			else
			{
				RconPlayers::Kill($data['attacker']['slot']);
				RconPlayers::Death($data['target']['slot']);
			}
			
			$this->UpdateDamage($data['target']['guid'], -1);
			
			return true;
		}
		// was this damage data?
		else if ($data['type'] == 'D')
		{
			if ($this->UpdateDamage($data['target']['guid'], $data['weapon']['damage']))
			{
				// create a new damage command here that has second_chance in the damage type
				
				//$rcon_commands[] = "tell {$data['target']['slot']} ^1pm: ^7Are you using 2nd chance?";
				//$this->ProcessRcon($rcon_commands);
			}
			
			return true;
		}
		// was this a command message?
		else if ($data['type'] == 'P' || $data['type'] == 'G')
		{
			$player = RconPlayers::Player($data['guid']);
			
			if ($player !== false)
			{
				$processed = false;
				$rcon_commands = array();
				
				$command_data = explode(" ", $data['message']);
				
				switch ($command_data[0])
				{
					case '!statreset':
					{
						if (count($command_data) == 1)
						{
							$this->reset_string = substr(str_shuffle(str_repeat('0123456789abcdef', 4)), 0, 4);
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7To reset the server statistics, use ^1!statreset {$this->reset_string}";
						}
						else if (count($command_data) == 2)
						{
							if (strlen($this->reset_string) == 0)
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7There was no reset request";
							}
							else
							{
								if ($this->reset_string == $command_data[1])
								{
									$query = "DELETE FROM {$adb->prefix}player_stats WHERE server_id = {$this->config['id']}";
									$adb->query($query, true);
									
									$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7The server statistics have been reset";
								}
								else
								{
									$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Invalid usage, use ^1!statreset ^7to start again";
								}
							}
							
							$this->reset_string = "";
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Invalid usage, use ^1!statreset ^7to start again";
							$this->reset_string = "";
						}
						
						$processed = true;
					} break;
					
					case '!rank':
					{
						$query = "SELECT (COUNT(*) + 1) AS player_rank FROM {$adb->prefix}player_stats WHERE server_id = {$this->config['id']} AND (player_stats_kills > (SELECT player_stats_kills FROM {$adb->prefix}player_stats WHERE player_id = {$player} AND server_id = {$this->config['id']}))";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rank = $adb->query_result($result, 0, 'player_rank');
							
							if ($rank == 1)
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You're currently ranked first!";
								$rcon_commands[] = "say {$data['name']} is currently ranked first!";
							}
							else
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Your rank is " . $this->OrdSuffix($rank);
								$rcon_commands[] = "say {$data['name']}'s rank is " . $this->OrdSuffix($rank);
							}
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} You're stats are not yet available";
						}
						
						$processed = true;
					} break;
					
					case '!globalrank':
					{
						$query = "SELECT player_id, SUM(player_stats_kills) AS total_kills FROM {$adb->prefix}player_stats GROUP BY player_id ORDER BY total_kills DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$total_rows = $adb->num_rows($result);
							$found = false;
							
							$player_id = intval($player);
							$rank = 0;
							
							for ($count = 0; $count < $total_rows && !$found; $count++)
							{
								if ($adb->query_result($result, $count, 'player_id') == $player_id)
									$found = true;
								$rank++;
							}
							
							if ($rank == 1)
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You're currently ranked ^1first ^7out of all servers!";
								$rcon_commands[] = "say ^3{$data['name']} ^7is currently ranked first out of all servers!";
							}
							else
							{
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Your global rank is ^1" . $this->OrdSuffix($rank);
								$rcon_commands[] = "say ^3{$data['name']}'s ^7global rank is ^1" . $this->OrdSuffix($rank);
							}
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} You're stats are not yet available";
						}
						
						$processed = true;
					} break;
					
					case '!closekills':
					{
						$player_query = "SELECT player_stats_kills FROM {$adb->prefix}player_stats WHERE server_id = {$this->config['id']} AND player_id = {$player}";
						$player_result = $adb->query($player_query, false);
						
						if (!empty($player_result) && $adb->num_rows($player_result) > 0)
						{
							$player_kills = $adb->query_result($player_result, 0, 'player_stats_kills');
							
							$close_query[] = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE player_stats_kills > {$player_kills} AND NOT {$adb->prefix}players.player_id = {$player} ORDER BY player_stats_kills";
							$close_query[] = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE player_stats_kills <= {$player_kills} AND NOT {$adb->prefix}players.player_id = {$player} ORDER BY player_stats_kills DESC";
							
							for ($query_no = 0; $query_no < count($close_query); $query_no++)
							{
								$close_result = $adb->query($close_query[$query_no], false);
								
								if (!empty($close_result) && $adb->num_rows($close_result) > 0)
								{
									for ($count = 0; $count < 2 && $count < $adb->num_rows($close_result); $count++)
									{
										$name = $adb->query_result($close_result, $count, 'player_name');
										$kills = $adb->query_result($close_result, $count, 'player_stats_kills');
										
										$required = $kills - $player_kills;
										
										if ($required > 0)
										{
											$required += 1;
											$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You need {$required} kills to overtake {$name}";
										}
										else
										{
											$required = ($required * -1) + 1;
											$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7{$name} needs {$required} kills to overtake you";
										}
									}
								}
							}
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Your stats are not yet available";
						}
						
						$processed = true;
					} break;
					
					case '!globalstats':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id ORDER BY player_stats_kills DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say Top players overall by kills:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$kills = $adb->query_result($result, $count, 'player_stats_kills');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$kills} kills";
							}
						}
						
						$processed = true;
					} break;
					
					case '!topstats':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} ORDER BY player_stats_kills DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say Top players overall by kills:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$kills = $adb->query_result($result, $count, 'player_stats_kills');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$kills} kills";
							}
						}
						
						$processed = true;
					} break;
				
					case '!topkdr':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} ORDER BY (player_stats_kills / player_stats_deaths) DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say Top players overall by kill/death ratio:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$deaths = $adb->query_result($result, $count, 'player_stats_deaths');
								$kills = $adb->query_result($result, $count, 'player_stats_kills');
								
								if ($deaths == 0)
									$deaths = 1;
								
								$kdr = sprintf("%01.2f", ($kills / $deaths));
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$kdr} kdr and {$kills} kills";
							}
						}
						
						$processed = true;
					} break;
					
					case '!topdeaths':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} ORDER BY player_stats_deaths DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say Those who have died the most:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$deaths = $adb->query_result($result, $count, 'player_stats_deaths');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$deaths} deaths";
							}
						}
						
						$processed = true;
					} break;
				
					case '!topsuicides':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} AND player_stats_suicides > 0 ORDER BY player_stats_suicides DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say The Darwin Award goes to:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$deaths = $adb->query_result($result, $count, 'player_stats_suicides');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$deaths} suicides";
							}
						}
						
						$processed = true;
					} break;
					
					case '!topcaptures':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} ORDER BY player_stats_captures DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say Those who have captured the most flags:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$deaths = $adb->query_result($result, $count, 'player_stats_captures');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^7{$name} with {$deaths} captures";
							}
						}
						
						$processed = true;
					} break;
					
					case '!wallofshame':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$this->config['id']} AND player_stats_carrier_kills > 0 ORDER BY player_stats_carrier_kills DESC";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$rcon_commands[] = "say The Wall of Shame:";
							
							for ($count = 0; $count < $adb->num_rows($result) && $count < 5; $count++)
							{
								$name = $adb->query_result($result, $count, 'player_name');
								$deaths = $adb->query_result($result, $count, 'player_stats_carrier_kills');
								
								$rcon_commands[] = "say ^1" . ($count + 1) . ". ^3{$name} ^7with ^3{$deaths} ^7kills of the flag carrier";
							}
						}
						
						$processed = true;
					} break;
					
					case '!me':
					{
						$query = "SELECT * FROM {$adb->prefix}player_stats WHERE player_id = {$player} AND server_id = {$this->config['id']}";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$kills = $adb->query_result($result, 0, 'player_stats_kills');
							$deaths = $adb->query_result($result, 0, 'player_stats_deaths');
							$captures = $adb->query_result($result, 0, 'player_stats_captures');
							$suicides = $adb->query_result($result, 0, 'player_stats_suicides');
							$kdr = sprintf("%01.2f", ($kills / $deaths));

							
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have a total of {$kills} kills and {$deaths} deaths, giving {$kdr} as your kdr";
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have a total of {$captures} objective captures";
							
							if ($suicides > 0)
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have also killed yourself {$suicides} times";
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Your stats are not yet available";
						}
						
						$processed = true;
					} break;
					
					case '!globalme':
					{
						$query = "SELECT SUM(player_stats_kills) AS total_kills, SUM(player_stats_deaths) AS total_deaths, SUM(player_stats_captures) AS total_captures, SUM(player_stats_suicides) AS total_suicides FROM {$adb->prefix}player_stats WHERE player_id = {$player} GROUP BY player_id";
						$result = $adb->query($query, false);
						
						if (!empty($result) && $adb->num_rows($result) > 0)
						{
							$kills = $adb->query_result($result, 0, 'player_stats_kills');
							$deaths = $adb->query_result($result, 0, 'player_stats_deaths');
							$captures = $adb->query_result($result, 0, 'player_stats_captures');
							$suicides = $adb->query_result($result, 0, 'player_stats_suicides');
							$kdr = sprintf("%01.2f", ($kills / $deaths));

							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have a total of {$kills} kills and {$deaths} deaths, giving {$kdr} as your kdr on all servers";
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have a total of {$captures} objective captures on all servers";
							
							if ($suicides > 0)
								$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7You have also killed yourself {$suicides} times on all servers";
						}
						else
						{
							$rcon_commands[] = "tell {$data['slot']} ^1pm: ^7Your stats are not yet available";
						}
						
						$processed = true;
					} break;
				}
				
				if ($processed)
				{
					if (count($rcon_commands) > 0)
						$this->ProcessRcon($rcon_commands);
						
					return true;
				}
			}
		}
		
		return false;
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
	
	private function ProcessMessages($server_id, $type = array('D', 'K'))
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = '{$type[0]}' OR server_log_command = '{$type[1]}') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = htmlspecialchars_decode($adb->query_result($result, $count, 'server_log_data'));

				if ($type[0] == 'D' && $type[1] == 'K')
					$data[$count] = $this->SplitDamage($message);
				else
					$data[$count] = $this->SplitMessage($message);
				
				$data[$count]['log_id'] = $adb->query_result($result, $count, 'server_log_id');
				$data[$count]['type'] = $adb->query_result($result, $count, 'server_log_command');
			}
		
			return $data;
		}
		
		return false;
	}
	
	private function UpdateDamage($guid, $damage)
	{
		global $adb;
		
		// use this to determine assists and current damage for second chance
		$player = RconPlayers::Player($data['guid']);
		$current_damage = $damage;
		
		if ($player !== false)
		{
			if ($damage == -1)
			{
				$query = "SELECT * FROM {$adb->prefix}server_damage WHERE server_id = {$this->config['id']} AND player_id = {$player}";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
					$current_damage = $adb->query_result($result, 0, 'server_damage_total');
				
				// clear damage
				$query = "DELETE FROM {$adb->prefix}server_damage WHERE server_id = {$this->config['id']} AND player_id = {$player}";
				$adb->query($query, true);
			}
			else
			{
				$query = "SELECT * FROM {$adb->prefix}server_damage WHERE server_id = {$this->config['id']} AND player_id = {$player}";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$current_damage += $adb->query_result($result, 0, 'server_damage_total');
					$query = "UPDATE {$adb->prefix}server_damage SET server_damage_total = server_damage_total + {$damage} WHERE server_id = {$this->config['id']} AND player_id = {$player}";
				}
				else
				{
					$query = "INSERT INTO {$adb->prefix}server_damage (server_id, player_id, server_damage_total) VALUES ({$this->config['id']}, {$player}, {$damage})";
				}
				
				$adb->query($query, true);
			}
		}
		
		$mode = 0; // softcore
		$mode = 1; // hardcore
		
		// if hardcore and $current_damage > 30, then player has 2nd chance
		// if softcore and $current_damage > 100, then player has 2nd chance
		if (($mode == 0 && $current_damage > 100) ||
				($mode == 1 && $current_damage > 30))
			return true;
			
		return false;
	}
	
	private function SplitDamage($message)
	{
		// target   74128803;1;allies;[BtR]Briggsy;
		// attacker 89383512;2;axis;spoorsra;
		// detail   hk21_mp;40;MOD_RIFLE_BULLET;right_foot
		
		// target   17227719;1;axis;[BtR]DSXC;
		// attacker ;-1;world;;
		// detail   none;16;MOD_FALLING;none
		
		$message_data = split(";", $message);
		$data = array();
		
		$data['target'] = array();
		$data['target']['guid'] =   $message_data[0];
		$data['target']['slot'] =   $message_data[1];
		$data['target']['name'] =   $message_data[3];
		
		$data['attacker'] = array();
		$data['attacker']['guid'] = $message_data[4];
		$data['attacker']['slot'] = $message_data[5];
		$data['attacker']['name'] = $message_data[7];
		
		$data['weapon'] = array();
		$data['weapon']['name'] =   $message_data[8];
		$data['weapon']['damage'] = $message_data[9];
		$data['weapon']['type'] =   $message_data[10];
		
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
	
	private function OrdSuffix($n) 
	{
    $str = "$n";
    
    $t = $n > 9 ? substr($str, -2, 1) : 0;
    $u = substr($str,-1);
    
    if ($t == 1) 
    {
    	return $str . 'th';
    }
    else 
    {
    	switch ($u) 
    	{
        case 1: return $str . 'st';
        case 2: return $str . 'nd';
        case 3: return $str . 'rd';
        default: return $str . 'th';
    	}
    }
	}
}

?>
