<?php

define('MESSAGE_AUTOMATIC', 1);
define('MESSAGE_RULE', 2);

class Messages_Plugin implements Plugin
{
	private $config;
	
	private $last_update;
	private $next_message;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Messages_Plugin'");
		
		$this->config = $config;
		
		$this->last_update = time() - 60;
		$this->next_message = 1;
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			$current = time();
			
			if (($current - $this->last_update) >= 60)
			{
				$this->last_update = $current;
				System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Messages_Plugin'");
		
				$message = htmlspecialchars_decode($this->GetMessage($this->config['id'], $this->next_message));
				
				if ($message !== false)
				{
					Rcon::Command("say {$message}", false);
					$this->next_message++;
				}
				else
				{
					Rcon::Command("say ^3deceptive ^1{^7rcon^1} ^7will be shutting down permanently on the 5th of December", false);
					$this->next_message = 1;
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
	
	private function GetMessage($server, $message, $type = MESSAGE_AUTOMATIC)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$server} AND server_message_order = {$message} AND server_message_type = {$type}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'server_message_detail');
		
		return false;
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

		if ($data['message'] == '!rules')
		{
			if ($this->HasRules($this->config['id']))
			{
				$result = Rcon::Command("say This server has the following rules:", false);
				$message_id = 1;
				
				while (($message = $this->GetMessage($this->config['id'], $message_id, MESSAGE_RULE)) !== false)
				{
					$result = Rcon::Command("say ^1{$message_id}. ^7{$message}", false);
					$message_id++;
				}
				
				if ($this->config['show_restrictions'] && $this->HasRestrictions($this->config['id']))
				{
					//$result = Rcon::Command("say The following items are restricted:", false);
					$item_id = 1;
					$item_list = '';

					while (($item = $this->GetItem($this->config['id'], $item_id)) !== false)
					{
						if ($item_list != '')
							$item_list .= ', ';
						
						$item_list .= $item;

						if (strlen($item_list) > 40)
						{
							$result = Rcon::Command("say ^1{$message_id}. ^3Restricted Items: ^7{$item_list}", false);
							$item_list = '';
							$message_id++;
						}

						$item_id++;
					}

					if ($item_list != '')
						$result = Rcon::Command("say ^1{$message_id}. ^3Restricted Items: ^7{$item_list}", false);
				}
			
				return $result;
			}
			else
			{
				return Rcon::Command("say This server has ^1No Rules!", false);
			}
		}
		
		return false;
	}
	
	private function GetItem($server, $row)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_restrictions INNER JOIN {$adb->prefix}items ON {$adb->prefix}server_restrictions.item_id = {$adb->prefix}items.item_id WHERE server_id = {$server} ORDER BY item_name";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) >= $row)
			return $adb->query_result($result, ($row - 1), 'item_name');
		
		return false;
	}
	
	private function RemoveRecord($log_id)
	{
		global $adb;
				
		$clear_data = "DELETE FROM {$adb->prefix}server_log WHERE server_log_id = {$log_id}";
		$adb->query($clear_data, true);
	}
	
	private function HasRules($server)
	{
		global $adb;
				
		$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$server} AND server_message_type = " . MESSAGE_RULE;
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return true;
		
		return $this->HasRestrictions($server);
	}
	
	private function HasRestrictions($server)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_restrictions WHERE server_id = {$server}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return true;
			
		return false;
	}
}

?>
