<?php

class Status_Plugin implements Plugin
{
	private $config;
	private $last_update = 0;
	
	private $last_init_string = '';
	private $server_state;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Status_Plugin'");
		
		$this->config = $config;
		$this->server_state = array();
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Status_Plugin'");
			
			$state = Rcon::Command("teamstatus");
		
			if (strlen($state) > 30)
			{
				// breakup last_state and store data in DB
				$players = $this->Process($this->config['id'], $state);
				System_Daemon::log(System_Daemon::LOG_INFO, " - server " . $this->config["id"] . " has {$players} players");
			}
		
			$now = time();
	
			if (($now - $this->last_update) > 20)
			{
				$this->last_update = $now;
				$this->UpdateServerStatus();
			}
		}
	}
	
	public function State()
	{
		return true;
	}
	
	public function Priority()
	{
		return PRIORITY_LOWEST;
	}
	
	private function Process($server_id, $state)
	{
		$total_players = 0;
		
		$state = str_replace("\xFF\xFF\xFF\xFF\x01print\n", "", $state);
		$state = str_replace("\x3F\x3F\x3F\x3F\x01print\n", "", $state);
		$rows = explode("\n", $state);
		
		$slots = array();
		
		for ($count = 0; $count < count($rows); $count++)
		{
			$row = trim($rows[$count]);
			
			if (strlen($row) > 0 && strpos($row, " ZMBI ") === false && 
					strpos($row, "qport") === false && strpos($row, "lastmsg") === false && 
					strpos($row, "????") === false && strpos($row, "map: ") === false && 
					strpos($row, "--- ----- ----") === false && strpos($row, "democlient^7") === false)
			{
				$values = $this->SplitRow($row);
				
				if (strpos($values[7], ":") !== false)
					$values[7] = substr($values[7], 0, strpos($values[7], ":"));
				
				$slots[$values[0]] = true;
				
				if (!$this->HasPlayer($server_id, $values[0]))
					$this->AddPlayer($server_id, $values[0], $values[3], $values[4], $values[1], $values[7], $values[2]);
				else
					$this->UpdatePlayer($server_id, $values[0], $values[3], $values[4], $values[1], $values[7], $values[2]);
		
				RconPlayers::Update($values[0], $values[3], $values[4], $values[1], $values[7]);
				
				$total_players++;
			}
		}
		
		for ($slot = 1; $slot <= 18; $slot++)
		{
			if (!array_key_exists($slot, $slots))
				$this->RemovePlayer($server_id, $slot);
		}
		
		return $total_players;
	}
	
	private function HasPlayer($server_id, $slot)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$server_id} AND server_slot = {$slot}";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) > 0)
			return true;
			
		return false;
	}
	
	private function RemovePlayer($server_id, $slot)
	{
		global $adb;
		
		$query = "DELETE FROM {$adb->prefix}server_players WHERE server_id = {$server_id} AND server_slot = {$slot}";
		$adb->query($query, true);
	}
	
	private function AddPlayer($server_id, $server_slot, $player_guid, $player_name, $player_score, $player_ip, $player_ping)
	{
		global $adb;

		if (!is_numeric($player_ping))
			$player_ping = 999;
			
		$player_name = addslashes($player_name);
		$player_name = str_replace(" ", "", $player_name);
		
		$query = "INSERT INTO {$adb->prefix}server_players (server_id, server_slot, server_player_guid, server_player_name, server_player_score, server_player_ip, server_player_ping) VALUES ({$server_id}, {$server_slot}, '{$player_guid}', '{$player_name}', {$player_score}, '{$player_ip}', {$player_ping})";
		$adb->query($query, true);
	}
	
	private function UpdatePlayer($server_id, $server_slot, $player_guid, $player_name, $player_score, $player_ip, $player_ping)
	{
		global $adb;

		if (!is_numeric($player_ping))
			$player_ping = 999;
		
		$player_name = addslashes($player_name);
		$player_name = str_replace(" ", "", $player_name);
		
		$query = "UPDATE {$adb->prefix}server_players SET server_player_guid = '{$player_guid}', server_player_name = '{$player_name}', server_player_score = {$player_score}, server_player_ip = '{$player_ip}', server_player_ping = {$player_ping} WHERE server_id = {$server_id} AND server_slot = {$server_slot}";
		$adb->query($query, true);
	}
	
	private function SplitRow($row)
	{
		$values = array();
		
		$str1 = trim($row);
		
		while (strpos($str1, '  ') !== false)
		{
			$str1 = str_replace('  ', ' ', $str1);
		}
		
		$strArray1 = explode('^7 ', $str1, 2);
		$strArray2 = explode(' ', $strArray1[0], 5);
		$strArray3 = explode(' ', $strArray1[1]);

		$values[0] = $strArray2[0]; // slot
		$values[1] = $strArray2[1]; // score
		$values[2] = $strArray2[2]; // ping
		$values[3] = $strArray2[3]; // guid
		$values[4] = $strArray2[4]; // name
		
		$values[5] = $strArray3[0]; // team
		$values[6] = $strArray3[1]; // lastmsg
		$values[7] = $strArray3[2]; // ip
		$values[8] = $strArray3[3]; // qport
		$values[9] = $strArray3[4]; // rate

		return $values;
		
		/*		
		$values = array();
		
		$current_value = 0;
		$is_space = ($row[0] == ' ');
		
		if ($is_space)
			$current_value = -1;
		
		for ($count = 0; $count < strlen($row); $count++)
		{
			if ($is_space && ($row[$count] != ' '))
			{
				$is_space = false;
				$current_value++;
				
				$values[$current_value] = $row[$count];
			}
			else if ($row[$count] != ' ')
			{
				$is_space = false;
				$values[$current_value] .= $row[$count];
			}
			else
			{
				$is_space = true;
			}
		}
		
		return $values;
		*/
	}

	private function GetServerStatus($server_id)
	{
		global $adb;
		
		$query = "SELECT server_log_data, server_log_timestamp FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND server_log_command = 'I' ORDER BY server_log_timestamp DESC";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$init_string = $adb->query_result($result, 0, 'server_log_data');
			$timestamp = $adb->query_result($result, 0, 'server_log_timestamp');
		
			$clear_data = "DELETE FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND server_log_command = 'I' AND server_log_timestamp <= '{$timestamp}'";
			$adb->query($clear_data, true);
			
			if ($init_string != $this->last_init_string)
			{
				$this->last_init_string = $init_string;
			
				$init_values = explode("\\", $this->last_init_string);
				$this->server_state = array();
				
				for ($count = 0; $count < count($init_values); $count += 2)
				{
					$this->server_state[$init_values[$count]] = $init_values[$count + 1];
				}
				
				if (count($this->server_state) > 0)
					return true;
			}
		}
		
		return false;
	}
	
	private function UpdateServerStatus()
	{
		$server_id = $this->config["id"];
		
		if (!$this->GetServerStatus($server_id))
			return false;
		
		// Data in InitString:
		// com_maxclients\19\g_gametype\ctf\mapname\mp_kowloon\playlist\30\playlist_enabled\1\playlist_entry\42\protocol\2117\scr_team_fftype\1\shortversion\7\sv_disableClientConsole\0\sv_floodprotect\4\sv_hostname\^0=^1BtR^0= ^@HC ^4Events\sv_maxclients\18\sv_maxPing\350\sv_maxRate\25000\sv_minPing\0\sv_pure\1\sv_ranked\2\sv_security\1\sv_voice\1\xblive_basictraining\0\xblive_privatematch\0\xblive_rankedmatch\0\xblive_wagermatch\0\g_logTimeStampInSeconds\1
		$map_id =    RconServer::GetMapID($this->server_state["mapname"]);
		$playlist =  $this->server_state["playlist"];
		
		RconServer::SetServerStatus(1, $map_id, $playlist, 0);
		
		return true;
	}
}

?>
