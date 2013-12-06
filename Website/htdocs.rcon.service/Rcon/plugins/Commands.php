<?php

class Commands_Plugin implements Plugin
{
	private $config;
	private $command_keys;
	private $restart;
	private $error_message;
	
	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Commands_Plugin'");
		$this->config = $config;
		
		$this->command_keys = array();
		
		$this->command_keys['&Mode'] = 'Mode';
		$this->command_keys['&Type'] = 'Type';
		$this->command_keys['&Map'] = 'Map';
		$this->command_keys['&Player'] = 'Player or Slot';
		$this->command_keys['&Players'] = 'Players';
		$this->command_keys['&Playlist'] = 'Playlist';
		
		$restart = false;
	}
	
	public function Run($first_run)
	{
		if (!$first_run)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Commands_Plugin'");
			
			$messages = $this->ProcessMessages($this->config["id"], 'P');
			$total_messages = count($messages);
			
			if ($total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_messages} team messages");
			
				for ($count = 0; $count < $total_messages; $count++)
				{
					if ($this->ProcessCommand($messages[$count]))
						$this->RemoveRecord($messages[$count]['log_id']); 
				}
			}
			
			$messages = $this->ProcessMessages($this->config["id"], 'G');
			$total_messages = count($messages);
			
			if ($total_messages > 0)
			{
				System_Daemon::log(System_Daemon::LOG_INFO, " - processing {$total_messages} global messages");
				
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
		return !$this->restart;
	}
	
	public function Priority()
	{
		return PRIORITY_NORMAL;
	}
	
	private function ProcessCommand($data)
	{
		$this->error_message = "";
		$message_data = explode(" ", $data['message']);
		
		$command = substr($message_data[0], 1);
		$command_data = $this->GetCommand($command);
		
		if ($command_data !== false)
		{
			if ($command_data['name'] == "help")
			{
				$this->DisplayHelp($data);
				return true;
			}
			else if ($command_data['name'] == "claimserver")
			{
				$this->ClaimServer($data, $message_data[1]);
				return true;
			}
			
			if (!$this->HasAccess($this->config['id'], $data['guid'], $command_data['name']))
			{
				Rcon::Tell($data['slot'], "You do not have access to ^1'!{$command_data['name']}'");
				return true;
			}
			
			if ($command_data['name'] == 'restart')
			{
				$this->restart = true;
				Rcon::Tell($data['slot'], "Rcon system will restart soon");
				
				return true;
			}
			else if ($command_data['name'] == 'shorthand')
			{
				if (count($message_data) == 0)
					$this->ListShorthand($data['slot']);
				else
					$this->ListShorthand($data['slot'], $message_data[1]);
				
				return true;
			}
			
			if ($command_data['response'] == 'EXTERNAL' || $command_data['format'] == 'EXTERNAL')
			{
				// external command
				if ($command != $command_data['name'])
				{
					// need to reset the command line to use the real command and not the alias
					$message = "{$data['guid']};{$data['slot']};{$data['name']}; !{$command_data['name']}";
					
					for ($count = 1; $count < count($message_data); $count++)
					{
						$message .= " {$message_data[$count]}";
					}
					
					$this->UpdateMessage($data['log_id'], $message);
				}
				
				return false;
			}
			
			$message_format = explode(" ", $command_data['format']);
			$values = array();
			
			$values['&Server'] = array('id' => $this->config['id'], 'value' => $this->config['id'], 'name' => $this->config['id']);
			$values['&Sender'] = array('id' => $data['slot'], 'value' => $data['slot'], 'name' => $data['slot']);
			
			// determine the matching commands
			if (count($message_data) >= count($message_format))
			{
				for ($count = 1; $count < count($message_format); $count++)
				{
					$request = $message_format[$count];
					$values[$request] = $this->GetData($request, $message_data[$count]);
					
					if ($values[$request] === false)
					{
						// error in command
						if (strlen($this->error_message) == 0)
							Rcon::Tell($data['slot'], "Option '" . $message_data[$count] . "' is invalid for '" . $this->GetDataType($request) . "'");
						else
							Rcon::Tell($data['slot'], $this->error_message);
						
						return true;
					}
				}

				$values['&Final'] = array();
				$values['&Final']['id'] = 0;
				
				$final_value = '';
				
				// add the rest to &Final
				for ($count = count($message_format); $count < count($message_data); $count++)
				{
					if (strlen($final_value) > 0)
						$final_value .= '_';
					
					$final_value .= implode("_", $this->ExpandShorthand($message_data[$count]));
				}

				if (strlen($final_value) == 0)
					$final_value = 'Unspecified';
				
				$values['&Final']['value'] = $final_value;
				$values['&Final']['name'] = $final_value;
			}
			else
			{
				// for now, just tell the user the required format
				Rcon::Tell($data['slot'], "Required format '!". $this->GetMessageFormatFull($command_data['format'], false) . "'");
				return true;
			}
			
			if (array_key_exists('&Player', $values))
			{
				$values['&Name'] = $this->GetData('&Name', $values['&Player']['name']);
				$values['&PID'] = $this->GetData('&PID', $values['&Player']['id']);
			}
			
			if (array_key_exists('&Map', $values))
			{
				$values['&!Map'] = $this->GetData('&!Map', $values['&Map']['value']);
			}
			
			if (array_key_exists('&Mode', $values) && array_key_exists('&Type', $values) && array_key_exists('&Players', $values))
			{
				$values['&Playlist'] = $this->GetData('&Playlist', array('Mode' => $values['&Mode']['value'], 'Type' => $values['&Type']['value'], 'Players' => $values['&Players']['value']));
			}
			
			$rcon_commands = $command_data['commands'];
			$rcon_response = $command_data['response'];
			$sql_command = $command_data['sql'];
			
			// process command here
			foreach ($values as $request => $detail)
			{
				$rcon_commands = str_replace($request, $detail['value'], $rcon_commands);
				$rcon_response = str_replace($request, $detail['name'],  $rcon_response);
				$sql_command =   str_replace($request, addslashes($detail['name']),  $sql_command);
			}
			
			$this->ProcessSQL($sql_command);
			
			if ($this->ProcessRcon($rcon_commands))
			{
				if (strlen($rcon_response) == 0)
					Rcon::Tell($data['slot'], "Command successful, '" . $data['message'] . "'");
				else
					$this->ProcessRcon($rcon_response);
				
				return true;
			}
			
			return false;
		}
		else
		{
			Rcon::Tell($data['slot'], "Invalid command '!{$command}'");
			return true;
		}
	}
	
	private function ProcessRcon($rcon_commands)
	{
		$commands = explode("|", $rcon_commands);
		$success = true;
		
		for ($count = 0; $count < count($commands) && $success; $count++)
		{
			$success = Rcon::Command(html_entity_decode($commands[$count]), false);
		}
		
		return $success;
	}
	
	private function ProcessSQL($sql_command)
	{
		global $adb;
		
		if (strlen($sql_command) > 0)
			$adb->query($sql_command, true);
	}
	
	private function GetData($request, $value)
	{
		global $adb;
		
		switch ($request)
		{
			case '&Mode':
			{
				$query = "SELECT * FROM {$adb->prefix}modes WHERE mode_shortcode = '{$value}' OR mode_longcode = '{$value}'";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'mode_id');
					$data['value'] = $adb->query_result($result, 0, 'mode_id');
					$data['name'] = $adb->query_result($result, 0, 'mode_name');
					
					return $data;
				}
			} break;
			
			case '&Type':
			{
				$query = "SELECT * FROM {$adb->prefix}types WHERE type_shortcode = '{$value}'";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'type_id');
					$data['value'] = $adb->query_result($result, 0, 'type_id');
					$data['name'] = $adb->query_result($result, 0, 'type_name');
					
					return $data;
				}
			} break;
			
			case '&Map':
			{
				if (substr($value, 0, 3) == 'mp_')
					$query = "SELECT * FROM {$adb->prefix}maps WHERE map_file = '{$value}'";
				else
					$query = "SELECT * FROM {$adb->prefix}maps WHERE map_name LIKE '{$value}%'";

				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'map_id');
					$data['value'] = $adb->query_result($result, 0, 'map_file');
					$data['name'] = $adb->query_result($result, 0, 'map_name');
					
					return $data;
				}
			} break;
			
			case '&!Map':
			{
				$query = "SELECT * FROM {$adb->prefix}maps WHERE NOT map_file = '{$value}'";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = 0;
					$data['value'] = '';
					$data['name'] = '';
					
					for ($count = 0; $count < $adb->num_rows($result); $count++)
					{
						if ($count > 0)
							$data['value'] .= ' ';
						$data['value'] .= $adb->query_result($result, $count, 'map_file');
					}
					
					return $data;
				}
			} break;
			
			case '&Game':
			{
				$query = "SELECT * FROM {$adb->prefix}game WHERE game_shorthand = '{$value}'";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'game_id');
					$data['value'] = $adb->query_result($result, 0, 'game_value');
					$data['name'] = $adb->query_result($result, 0, 'game_name');
					
					return $data;
				}
			} break;
			
			case '&Playlist':
			{
				$query = "SELECT * FROM {$adb->prefix}mode_types WHERE mode_id = {$value['Mode']} AND type_id = {$value['Type']} AND mode_type_players = {$value['Players']}";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) > 0)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'mode_type_id');
					$data['value'] = $adb->query_result($result, 0, 'mode_type_id');
					$data['name'] = $adb->query_result($result, 0, 'mode_type_players') . "-man";
					
					return $data;
				}
			} break;
			
			case '&Player':
			{
				// is it a slot number?
				if (is_numeric($value))
				{
					// select player by slot
					$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_slot = {$value} AND server_id = {$this->config['id']}";
					$result = $adb->query($query, false);
					
					if (!empty($result) && $adb->num_rows($result) == 1)
					{
						$data = array();
						
						$data['id'] = $adb->query_result($result, 0, 'server_player_guid');
						$data['value'] = $value;
						$data['name'] = $adb->query_result($result, 0, 'server_player_name');
						
						return $data;
					}
				}
				else
				{
					// lookup player id
					$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_player_name LIKE '%{$value}%' AND server_id = {$this->config['id']}";
					$result = $adb->query($query, false);
					
					if (!empty($result))
					{
						if ($adb->num_rows($result) == 1)
						{
							$data = array();
							
							$data['id'] = $adb->query_result($result, 0, 'server_player_guid');
							$data['value'] = $adb->query_result($result, 0, 'server_slot');
							$data['name'] = $adb->query_result($result, 0, 'server_player_name');
							
							return $data;
						}
						else if ($adb->num_rows($result) == 0)
						{
							$this->error_message = "Player name '{$value}' not found on the server";
						}
						else
						{
							$this->error_message = "There were " . $adb->num_rows($result) . " player's found matching '{$value}'";
						}
					}
				}
			} break;
			
			case '&PID':
			{
				$query = "SELECT * FROM {$adb->prefix}players WHERE player_guid = '{$value}'";
				$result = $adb->query($query, false);
				
				if (!empty($result) && $adb->num_rows($result) == 1)
				{
					$data = array();
					
					$data['id'] = $adb->query_result($result, 0, 'player_id');
					$data['value'] = $adb->query_result($result, 0, 'player_id');
					$data['name'] = $adb->query_result($result, 0, 'player_id');
					
					return $data;
				}
			} break;
			
			case '&Players':
			{
				if ($value == 12 || $value == 18)
				{
					$data = array();
					
					$data['id'] = $value;
					$data['value'] = $value;
					$data['name'] = '';
					
					return $data;
				}
			} break;
			
			case '&Name':
			{
				$data = array();
				
				$data['id'] = $value;
				$data['value'] = $value;
				$data['name'] = $value;
				
				return $data;
			} break;
			
			default:
			{
				if (strpos($request, "&Value") == 0)
				{
					$data = array();
						
					$data['id'] = substr($request, 6);
					$data['value'] = $value;
					$data['name'] = '';
					
					return $data;
				}
			}
		}
		
		return false;
	}
	
	private function GetSlot($guid)
	{
		global $adb;
		
		$slot = -1;
		
		$query = "SELECT server_slot FROM {$adb->prefix}server_players WHERE server_id = {$this->config['id']} AND server_player_guid = '{$guid}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) == 1)
		{
			$slot = $adb->query_result($result, 0, 'server_slot');
		}
		
		return $slot;
	}
	
	private function GetDataType($request)
	{
		if (array_key_exists($request, $this->command_keys))
			return $this->command_keys[$request];
			
		return 'Unknown {$request}';
	}
	
	private function GetMessageFormatFull($format, $optional = true)
	{
		foreach ($this->command_keys as $key => $command)
		{
			$format = str_replace($key, $command, $format);
		}
		
		if (!$optional)
		{
			$format = str_replace("[", "", $format);
			$format = str_replace("]", "", $format);
		}

		$format = str_replace("&", "", $format);
		
		return $format;
	}
	
	private function GetCommand($command, $by_name = true)
	{
		global $adb;
		
		if ($by_name)
			$query = "SELECT * FROM {$adb->prefix}commands WHERE (server_id = {$this->config['id']} OR server_id = 0) AND command_name = '{$command}'";
		else
			$query = "SELECT * FROM {$adb->prefix}commands WHERE (server_id = {$this->config['id']} OR server_id = 0) AND command_id = {$command}";
		
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			
			$data['id'] = $adb->query_result($result, 0, 'command_id');
			$data['name'] = $adb->query_result($result, 0, 'command_name');
			$data['format'] = $adb->query_result($result, 0, 'command_format');
			$data['commands'] = $adb->query_result($result, 0, 'command_rcon_command');
			$data['response'] = $adb->query_result($result, 0, 'command_response');
			$data['sql'] = $adb->query_result($result, 0, 'command_sql_command');
			
			return $data;
		}		
		else if ($by_name)
		{
			// look in subcommands
			$query = "SELECT * FROM {$adb->prefix}command_alias WHERE command_alias = '{$command}'";
			$result = $adb->query($query, false);

			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				return $this->GetCommand($adb->query_result($result, 0, 'command_id'), false);
			}
		}
		
		return false;
	}
	
	private function ExpandShorthand($message)
	{
		global $adb;
		
		$expanded_message = "";
		$full_message = explode(" ", $message);
		
		for ($count = 0; $count < count($full_message); $count++)
		{
			if (strlen($expanded_message) > 0)
				$expanded_message .= " ";
			
			$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$this->config['id']} AND server_message_type = 3 AND server_message_detail LIKE '{$full_message[$count]}=%'";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) == 1)
			{
				$shorthand = $adb->query_result($result, 0, 'server_message_detail');
				$shorthand_data = explode("=", $shorthand, 2);
				
				$expanded_message .= $shorthand_data[1];
			}
			else
			{
				$expanded_message .= $full_message[$count];
			}
		}
		
		$message_data = explode(" ", $expanded_message);
		
		return $message_data;
	}
	
	private function ProcessMessages($server_id, $type)
	{
		global $adb;
		
		$data = array();
		$total_processed = 0;
		
		$query = "SELECT * FROM {$adb->prefix}server_log WHERE server_id = {$server_id} AND server_log_command = '{$type}' ORDER BY server_log_timestamp";
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
	
	private function HasAccess($server_id, $guid, $command)
	{
		global $adb;
		
		if ($guid == 'CONSOLE')
			return true;

		// get the players group
		$group = RconPlayers::Group($guid);

		$query = "SELECT * FROM {$adb->prefix}commands INNER JOIN {$adb->prefix}server_commands ON {$adb->prefix}commands.command_id = {$adb->prefix}server_commands.command_id WHERE {$adb->prefix}server_commands.server_id = {$server_id} AND group_id = {$group} AND command_name = '{$command}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return ($adb->query_result($result, 0, 'server_command_access') == 1);
		
		return false;
	}
	
	private function UpdateMessage($log_id, $message)
	{
		global $adb;
				
		$clear_data = "UPDATE {$adb->prefix}server_log SET server_log_data = '{$message}' WHERE server_log_id = {$log_id}";
		$adb->query($clear_data, true);
	}
	
	private function DisplayHelp($data)
	{
		global $adb;
		
		if ($data['slot'] <= 0)
			return false;
		
		$query = "SELECT * FROM {$adb->prefix}commands ORDER BY command_name";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$rcon_commands = array();
			$next_command = '';
			
			$rcon_commands[] = "tell {$data['slot']} You have access to the following commands:";
			
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$command = $adb->query_result($result, $count, 'command_name');
				
				if ($this->HasAccess($this->config['id'], $data['guid'], $command))
				{
					if ($next_command != '')
						$next_command .= '^7, ';
				
					$next_command .= "^1{$command}";
					
					if (strlen($next_command) > 50)
					{
						$rcon_commands[] = "tell {$data['slot']} {$next_command}";
						$next_command = '';
					}
				}
			}

			if (count($rcon_commands) > 0)
				$this->ProcessRcon(implode('|', $rcon_commands));
		}
		
		return true;
	}
	
	private function ListShorthand($slot, $shorthand = '')
	{
		global $adb;
		$rcon_commands = array();
		
		if (strlen($shorthand) == 0)
		{
			$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$this->config['id']} AND server_message_type = 3";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				$next_command = '';
				$rcon_commands[] = "tell {$slot} ^1pm: ^7The following abbreviations are available:";
			
				for ($count = 0; $count < $adb->num_rows($result); $count++)
				{
					$shorthand = $adb->query_result($result, $count, 'server_message_detail');
					$shorthand_data = explode("=", $shorthand, 2);
					
					if ($next_command != '')
						$next_command .= '^7, ';
				
					$next_command .= "^1{$shorthand_data[0]}";
					
					if (strlen($next_command) > 50)
					{
						$rcon_commands[] = "tell {$slot} ^1pm: ^7 {$next_command}";
						$next_command = '';
					}
				}
				
				if (strlen($next_command) > 0)
					$rcon_commands[] = "tell {$slot} ^1pm: ^7 {$next_command}";
			}
			else
			{
				$rcon_commands[] = "tell {$slot} ^1pm: ^7The server has no abbreviations available";
			}
		}
		else
		{
			$rcon_commands[] = "tell {$slot} ^1pm: ^7'{$shorthand}' is equal to '" . implode(" ", $this->ExpandShorthand($shorthand)) . "'";
		}
		
		if (count($rcon_commands) > 0)
			$this->ProcessRcon(implode('|', $rcon_commands));
	}
	
	private function ClaimServer($data, $code)
	{
		global $adb;
		
		if ($data['slot'] <= 0)
			return false;
		
		$query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$this->config['id']} AND server_owner_id = -1";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$activation_code = $adb->query_result($result, 0, 'server_activation');
			
			if ($activation_code == $code)
			{
				$player = RconPlayers::Player($data['guid']);
				
				if ($player !== false)
				{
					$query = "UPDATE {$adb->prefix}servers SET server_owner_id = {$player} WHERE server_id = {$this->config['id']}";
					$adb->query($query, true);
					
					$query = "SELECT * FROM {$adb->prefix}player_groups WHERE server_id = {$this->config['id']} AND player_id = {$player}";
					$group_result = $adb->query($query, false);
					
					if (!empty($group_result) && $adb->num_rows($group_result) > 0)
						$query = "UPDATE {$adb->prefix}player_groups SET group_id = 1 WHERE server_id = {$this->config['id']} AND player_id = {$player}";
					else
						$query = "INSERT INTO {$adb->prefix}player_groups (server_id, player_id, group_id) VALUES ({$this->config['id']}, {$player}, 1)";
					
					$adb->query($query, true);
					
					Rcon::Tell($data['slot'], "Congratulations! You are now the owner of the server.  Type '!help' to see your available commands.");
				}
				else
				{
					$query = "UPDATE {$adb->prefix}servers SET server_activation = '" . uniqid() . "' WHERE server_id = {$this->config['id']}";
					$adb->query($query, true);
					
					Rcon::Tell($data['slot'], "Your player information is not yet available, wait a few moments and try again.");
					Rcon::Tell($data['slot'], "A new activation code has been created for your protection, please check the web interface for details.");
				}
			
				return true;
			}
		}
		
		Rcon::Tell($data['slot'], "Invalid command '!claimserver'");
		return false;
	}
}

?>
