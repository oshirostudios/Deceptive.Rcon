<?php

class Chat_Plugin implements Plugin
{
	private $config;
	private $state;
	
	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Chat_Plugin'");
		
		$this->config = $config;
		$this->state = RconServer::GetServerStatus(1);
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Chat_Plugin'");
			
			$actions = $this->ProcessMessages($this->config['id']);
			$total_actions = count($actions);
			
			if ($total_actions > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_actions} chat messages");
				
				for ($count = 0; $count < $total_actions; $count++)
				{
					if ($actions[$count]['guid'] != 'CONSOLE')
						RconPlayers::Log($actions[$count]['slot'], $actions[$count]['guid'], $actions[$count]['message'], $actions[$count]['timestamp']);
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
		// process before anything is removed
		return PRIORITY_HIGH;
	}
	
	private function ProcessMessages($server_id)
	{
		global $adb;
		
		$data = array();
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND (server_log_command = 'P' OR server_log_command = 'G') ORDER BY server_log_timestamp";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$total_messages = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_messages; $count++)
			{
				$message = $adb->query_result($result, $count, 'server_log_data');
				$data[$count] = $this->SplitMessage($message);
				$data[$count]['timestamp'] = $adb->query_result($result, $count, 'server_log_timestamp');
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
}

?>
