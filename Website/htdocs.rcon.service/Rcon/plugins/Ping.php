<?php

class Ping_Plugin implements Plugin
{
	private $config;
	private $last_update = -1;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Ping_Plugin'");
		
		$this->config = $config;
		$this->last_update = time();
	}
	
	public function Run($first_run)
	{
		global $adb;
		
		if (!$first_run)
		{
			$current_time = time();
			
			// run only if max_ping is set and the last run time was over 20 seconds ago
			if ($this->config['max_ping'] > 0 && ($current_time - $this->last_update) > 20)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Ping_Plugin'");
				
				$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']}";
				$result = $adb->query($query, false);
	
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$total_players = $adb->num_rows($result);
					
					for ($count = 0; $count < $total_players; $count++)
					{
						$player_ping = $adb->query_result($result, $count, 'server_player_ping');
						
						// ignore pings with error
						if ($player_ping != 999)
						{
							if ($player_ping > $this->config['max_ping'])
							{
								// warn or kick player
								$player_slot = $adb->query_result($result, $count, 'server_slot');
								$player_name = $adb->query_result($result, $count, 'server_player_name');
								$player_guid = $adb->query_result($result, $count, 'server_player_guid');
								
								$player = RconPlayers::Player($player_guid);
								
								if ($player !== false)
								{
									$restriction_count = $this->GetRestrictionCount($player);
									$messages = array();
									
									if ($restriction_count <= $this->config['warnings'])
									{
										$messages[] = "tell {$player_slot} ^1pm: ^4Warning: ^7Your ping is too high or fluctuating (limit {$this->config['max_ping']}) ^7[^1{$restriction_count} of {$this->config['warnings']}^7]";
									}
									else
									{
										$messages[] = "say ^4Kicking: ^3{$player_name} ^7for high/fluctuating ping";
										$messages[] = "clientkick {$player_slot} Your_ping_is_too_high_or_fluctuating";
										
										$this->ClearWarnings($player);
									}
								}
							}
						}
					}
				}
				
				// set it to the finished time, minimum 20 seconds
				$this->last_update = time();
				
				return $this->ProcessRcon($messages);
			}
		}
	}
	
	private function GetRestrictionCount($user)
	{
		global $adb;
		
		$restriction_count = 1;
		
		// restriction id is -1 for pings
		$query = "SELECT * FROM {$adb->prefix}warnings WHERE user_id = {$user} AND server_restriction_id = -1";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$restriction_count = $adb->query_result($result, 0, 'warning_count');
			$restriction_count++;

			$query = "UPDATE {$adb->prefix}warnings SET warning_count = {$restriction_count} WHERE user_id = {$user} AND server_restriction_id = -1";
			$adb->query($query, true);
		}
		else
		{
			$query = "INSERT INTO {$adb->prefix}warnings (user_id, server_restriction_id, warning_count) VALUES ({$user}, -1, 1)";
			$adb->query($query, true);
		}
		
		return $restriction_count;
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
	
	private function ClearWarnings($user_id)
	{
		global $adb;
		
		$query = "DELETE FROM {$adb->prefix}warnings WHERE user_id = {$user_id} AND server_restriction_id = -1";
		$adb->query($query, true);
	}
	
	public function State()
	{
		return true;
	}
	
	public function Priority()
	{
		return PRIORITY_LOW;
	}
}

?>
