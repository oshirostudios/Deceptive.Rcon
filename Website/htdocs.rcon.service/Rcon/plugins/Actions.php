<?php

class Actions_Plugin implements Plugin
{
	private $config;
	private $state;
	
	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Actions_Plugin'");
		
		$this->config = $config;
		$this->state = RconServer::GetServerStatus(1);
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Actions_Plugin'");
			
			$actions = $this->ProcessMessages($this->config["id"]);
			$total_actions = count($actions);
			
			if ($total_actions > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_actions} actions");
				
				for ($count = 0; $count < $total_actions; $count++)
				{
					if ($this->UpdatePlayer($actions[$count]))
						$this->RemoveRecord($actions[$count]['log_id']); 
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
		return PRIORITY_NORMAL;
	}
	
	private function ProcessMessages($server_id, $type)
	{
		global $adb;
		
		$data = array();
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = 'J' OR server_log_command = 'Q') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = $adb->query_result($result, $count, 'server_log_data');
				$message_data = explode(";", $message);
				
				$data[$count]['type'] = $adb->query_result($result, $count, 'server_log_command');
				$data[$count]['guid'] = $message_data[0];
				$data[$count]['slot'] = $message_data[1];
				$data[$count]['name'] = $message_data[2];
				$data[$count]['timestamp'] = $adb->query_result($result, $count, 'server_log_timestamp');
				$data[$count]['log_id'] = $adb->query_result($result, $count, 'server_log_id');
			}
		}
		
		return $data;
	}
	
	private function UpdatePlayer($detail)
	{
		if ($detail['type'] == 'J')
		{
			RconPlayers::Connected($detail['slot'], $detail['guid'], $detail['name']);
			$this->BanCheck($detail['slot'], $detail['guid'], $detail['name']);
			
			return true;
		}
		else if ($detail['type'] == 'Q')
		{
			RconPlayers::Disconnected($detail['slot'], $detail['guid']);
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
	
	private function BanCheck($slot, $guid, $name)
	{
		global $adb;
		
		$player = RconPlayers::Player($guid);
		
		if ($player !== false)
		{
			$query = "SELECT server_ban_length, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(server_ban_when, INTERVAL server_ban_length DAY)) AS server_ban_remaining, server_ban_reason FROM {$adb->prefix}server_bans WHERE server_id = {$this->config['id']} AND player_id = {$player} AND (NOW() < DATE_ADD(server_ban_when, INTERVAL server_ban_length DAY) OR server_ban_length == -1)";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				// they have a current ban
				$ban_length = $adb->query_result($result, 0, 'server_ban_length');
				$ban_remaining = $adb->query_result($result, 0, 'server_ban_remaining');
				$ban_reason = $adb->query_result($result, 0, 'server_ban_reason');
				
				if ($ban_length == -1)
				{
					// perform permaban
					$rcon_commands[] = "banclient {$slot}";
					$rcon_commands[] = "clientkick {$slot} Banned_for_" . str_replace(" ", "_" , $ban_reason);
					$rcon_commands[] = "say ^1{$player_name} ^7has been permanently banned for ^1{$ban_reason}";
				}
				else
				{
					$ban_days = floor($ban_remaining / 86400);
					$ban_hours = ($ban_remaining / 3600) % 24;
					$ban_minutes = ($ban_remaining / 60) % 60;
					$ban_seconds = $ban_remaining % 60;
					
					if ($ban_days > 0)
						$reason = "Ban Remaining (days): {$ban_days}";
					else if ($ban_hours > 0)
						$reason = "Ban Remaining (hours): {$ban_hours}";
					else if ($ban_minutes > 0)
						$reason = "Ban Remaining (minutes): {$ban_minutes}";
					else
						$reason = "Ban Remaining (seconds): {$ban_seconds}";
					
					$reason .= " Reason: {$ban_reason}";
					
					if ($ban_days > 0 || $ban_hours > 0 || $ban_minutes >= 5)
						$rcon_commands[] = "tempbanclient {$slot} " . str_replace(" ", "_" , $reason);
					else
						$rcon_commands[] = "clientkick {$slot} " . str_replace(" ", "_" , $reason);
					
					$rcon_commands[] = "say ^1{$player_name} ^7has been banned for ^1{$ban_length} ^7days because of ^1{$ban_reason}";
				}
				
				$this->ProcessRcon($rcon_commands);
				
				return true;
			}
		}
		
		// no active bans, clear any old bans
		$query = "DELETE FROM {$adb->prefix}server_bans WHERE server_id = {$this->config['id']} AND player_id = {$player}";
		$adb->query($query, true);
		
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
}

?>
