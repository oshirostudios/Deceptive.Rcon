<?php

class Restrictions_Plugin implements Plugin
{
	private $config;
	private $current_warns;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Restrictions_Plugin'");
		$this->config = $config;
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Restrictions_Plugin'");
			
			$messages = $this->ProcessMessages($this->config["id"]);
			$total_messages = count($messages);	
		
			if ($messages !== false && $total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_messages} damage data");
				
				$this->current_warns = array();
			
				for ($count = 0; $count < $total_messages; $count++)
				{
					// remove data that is restricted to remove their effect on statistics
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
		return PRIORITY_NORMAL;
	}
	
	private function ProcessData($data)
	{
		global $adb;
		
		$target = $this->GetPlayer($data['target']['guid']);
		
		if ($data['attacker']['slot'] == -1)
			$attacker = $target;
		else
			$attacker = $this->GetPlayer($data['attacker']['guid']);
			
		if ($attacker !== false && $target !== false)
		{
			if (!array_key_exists($attacker, $this->current_warns))
				$this->current_warns[$attacker] = array();
			
			$restricted = $this->IsRestricted($data['weapon']['name'], $data['weapon']['type']);
			
			// warn the user that the item is restricted
			if ($restricted !== false)
			{
				if (!array_key_exists($restricted['restriction'], $this->current_warns[$attacker]))
				{
					$this->current_warns[$attacker][$restricted['restriction']] = true;
					
					// check for current warnings
					$restriction_count = $this->GetRestrictionCount($attacker, $restricted['restriction']);
					$messages = array();
					
					if ($restriction_count <= $this->config['warnings'])
					{
						$messages[] = "say ^4Warning: ^3{$data['attacker']['name']} ^7[^1{$restriction_count} of {$this->config['warnings']}^7] for using ^1{$restricted['name']}";
					}
					else
					{				
						$messages[] = "say ^4Kicking: ^3{$data['attacker']['name']} ^7for using ^1{$restricted['name']}";
						$reason = str_replace(" ", "_", "For using {$restricted['name']}");
						
						if ($data['attacker']['slot'] != -1)
							$messages[] = "clientkick {$data['attacker']['slot']} {$reason}";
						else
							$messages[] = "clientkick {$data['target']['slot']} {$reason}"; // suicide
					}
					
					return $this->ProcessRcon($messages);
				}
				else
				{
					// already warned for this
					return true;
				}
			}
		}
		
		return false;
	}
	
	private function GetRestrictionCount($user, $restriction)
	{
		global $adb;
		
		$restriction_count = 1;
		
		$query = "SELECT * FROM {$adb->prefix}warnings WHERE user_id = {$user} AND server_restriction_id = {$restriction}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$restriction_count = $adb->query_result($result, 0, 'warning_count');
			$restriction_count++;

			$query = "UPDATE {$adb->prefix}warnings SET warning_count = {$restriction_count} WHERE user_id = {$user} AND server_restriction_id = {$restriction}";
			$adb->query($query, true);
		}
		else
		{
			$query = "INSERT INTO {$adb->prefix}warnings (user_id, server_restriction_id, warning_count) VALUES ({$user}, {$restriction}, 1)";
			$adb->query($query, true);
		}
		
		return $restriction_count;
	}
	
	private function IsRestricted($weapon, $type)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}items INNER JOIN {$adb->prefix}server_restrictions ON {$adb->prefix}items.item_id = {$adb->prefix}server_restrictions.item_id WHERE server_id = {$this->config['id']}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$item_code = $adb->query_result($result, $count, 'item_code');
				$item_damage = $adb->query_result($result, $count, 'item_damage');
				
				if (strpos($weapon, $item_code) !== false && (strlen($item_damage) == 0 || (strlen($item_damage) > 0 && strpos($item_damage, $type) !== false)))
				{
					$data = array();
					
					$data['restriction'] = $adb->query_result($result, $count, 'server_restriction_id');
					$data['id'] = $adb->query_result($result, $count, 'item_id');
					$data['name'] = $adb->query_result($result, $count, 'item_name');
					
					return $data;
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
	
	private function GetPlayer($guid)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}players WHERE player_guid = '{$guid}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'player_id');
		
		return false;
	}
	
	private function ProcessMessages($server_id)
	{
		global $adb;
		
		// OR server_log_command = 'W'
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = 'D' OR server_log_command = 'K') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = $adb->query_result($result, $count, 'server_log_data');
				$command = $adb->query_result($result, $count, 'server_log_command');
				
				//if ($command == 'W')
				//else
					$data[$count] = $this->SplitDamage($message);
				
				$data[$count]['log_id'] = $adb->query_result($result, $count, 'server_log_id');
				$data[$count]['type'] = $command;
			}
		
			return $data;
		}
		
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
		$data['target']['guid'] =     $message_data[0];
		$data['target']['slot'] =     $message_data[1];
		$data['target']['name'] =     $message_data[3];
		
		$data['attacker'] = array();
		$data['attacker']['guid'] =   $message_data[4];
		$data['attacker']['slot'] =   $message_data[5];
		$data['attacker']['name'] =   $message_data[7];
		
		$data['weapon'] = array();
		$data['weapon']['name'] =     $message_data[8];
		$data['weapon']['damage'] =   $message_data[9];
		$data['weapon']['type'] =     $message_data[10];
		$data['weapon']['location'] = $message_data[11];
		
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
