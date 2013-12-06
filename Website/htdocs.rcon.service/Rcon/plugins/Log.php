<?php

class Log_Plugin implements Plugin
{
	private $config;
	
	private $log;
	private $last_update;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Log_Plugin'");
		
		$this->config = $config;
		$this->log = new ServerLog($config["url"]);
		
		$this->last_update = $this->GetLastTimestamp($config["id"]);
	}
	
	public function Run($first_run)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Log_Plugin'");
		System_Daemon::log(System_Daemon::LOG_INFO, " - current update {$this->last_update}");
	
		if ($this->log->Download($this->last_update))
		{
			$this->last_update = $this->log->Last();
			$data = $this->log->Data();
			
			foreach ($data as $row)
			{
				$store = false;
				
				$timestamp = $row['timestamp'];
				$message = $row['message'];
				
				if (!$first_run)
				{
					$command = substr($message, 0, 1);
					$command_data = '';
					
					switch ($command)
					{
						// say
						case 's':
						{
							// say or sayteam
							if (strpos($message, "sayteam") === false)
							{
								$command = 'P'; // say all
								$command_data = trim(substr($message, 4));
							}
							else
							{
								$command = 'G'; // say all
								$command_data = trim(substr($message, 8));
							}
							
							$store = true;
						} break;
						
						// join
						case 'J':
						// quit
						case 'Q':
						// damage
						case 'D':
						// kill
						case 'K':
						{
							$command_data = trim(substr($message, 2));
							$store = true;
						} break;
						
						// weapon - scavenge, switch etc
						case 'W':
						{
							$command_data = trim(substr($message, 7));
							$store = true;
						} break;
						
						// init game
						case 'I':
						{
							$command_data = trim(substr($message, 11));
							$store = true;
						} break;
					}
					
					if ($store)
						$this->StoreLogData($this->config["id"], $timestamp, $command, addslashes($command_data));
				}
			}
			
			// update with the latest timestamp
			$this->SetLastTimestamp($this->config["id"], $timestamp);
		}
	}
	
	public function State()
	{
		return true;
	}
	
	public function Priority()
	{
		return PRIORITY_HIGHEST;
	}

	private function StoreLogData($server_id, $timestamp, $command, $data)
	{
		global $adb;
		
		$query = "INSERT INTO {$adb->prefix}server_log (server_id, server_log_timestamp, server_log_command, server_log_data) VALUES ({$server_id}, '{$timestamp}', '{$command}', '{$data}')";
		$adb->query($query, true);
	}
	
	private function GetLastTimestamp($server_id)
	{
		global $adb;
		
		$timestamp = '0';

		$query = "SELECT server_log_timestamp FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND server_log_command = 'T'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$timestamp = $adb->query_result($result, 0, 'server_log_timestamp');
		}
		
		return $timestamp;
	}
	
	private function SetLastTimestamp($server_id, $timestamp)
	{
		global $adb;
		
		if ($this->GetLastTimestamp($server_id) == '0')
			$query = "INSERT INTO {$adb->prefix}server_log (server_id, server_log_timestamp, server_log_command, server_log_data) VALUES ({$server_id}, '{$timestamp}', 'T', '')";
		else
			$query = "UPDATE {$adb->prefix}server_log SET server_log_timestamp = {$timestamp} WHERE server_id = {$server_id} AND server_log_command = 'T'";
			
		$adb->query($query, true);
	}
}

?>
