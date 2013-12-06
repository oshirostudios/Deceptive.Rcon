<?php
defined('RCON_PATH') or die('No direct script access.');

define('ACTION_NONE',	0);
define('ACTION_JOIN',	1);
define('ACTION_QUIT',	2);
define('ACTION_NAME',	3);
define('ACTION_LOG',	4);
define('ACTION_BAN',	5);
define('ACTION_KICK',	6);
define('ACTION_WARN',	7);

class Player
{
	private $id;
	
	private $guid;
	private $name;
	private $ip;
	
	private $stats;

	private $score;
	private $expected_score;
	
	private $kills;
	private $deaths;
	private $suicides;
	private $captures;
	private $carrier_kills;
	private $assists;
	private $returns;

	private $config;
	
	public function __construct($config)
	{
		$this->config = $config;
		$this->Reset();
	}
	
	private function Initialise($guid)
	{
		$this->Reset();
		
		$this->guid = $guid;
		$this->id = $this->GetPlayer($guid);

		if ($this->id === false)
			$this->id = $this->CreatePlayer($guid);
		
		global $adb;

		$query = "SELECT * FROM {$adb->prefix}players WHERE player_id = {$this->id}";
		$result = $adb->query($query, false);
		
		$this->name = addslashes($adb->query_result($result, 0, 'player_name'));
		$this->ip = $adb->query_result($result, 0, 'player_last_ip');
		
		$this->stats = $this->GetPlayerStats();
	}
	
	public function Reset()
	{
		$this->ClearWarnings();
		
		$this->id = -1;
		
		$this->name = '';
		$this->score = 0;
		$this->expected_score = 0;
		$this->ip = '';
		
		$this->kills = 0;
		$this->deaths = 0;
		$this->suicides = 0;
		$this->captures = 0;
		$this->carrier_kills = 0;
		$this->assists = 0;
		$this->returns = 0;
	}
	
	public function Update($guid, $name, $score, $ip)
	{
		if ($guid == 0)
			return;
		
		if ($this->guid != $guid)
			$this->Initialise($guid);
		
		$name = addslashes($name);
		
		if ($this->name != $name)
			$this->UpdateName($name);

		$this->UpdateScore($score);

		if ($this->ip != $ip)
			$this->UpdateLastIP($ip);
			
		$this->UpdatePlayerStats();
	}
	
	public function PerformAction($guid, $action, $reason, $text = '', $timestamp = '')
	{
		if ($this->guid != $guid)
			$this->Initialise($guid);
		
		if ($timestamp == '')
			$timestamp = time();
		
		$this->Action($action, $reason, $text, $timestamp);
	}
	
	private function Action($action, $reason, $text, $timestamp)
	{
		global $adb;
		
		$reason = addslashes($reason);
		$text = addslashes($text);
		
		$query = "INSERT INTO {$adb->prefix}player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES ({$this->id}, {$this->config['id']}, {$action}, '{$timestamp}', '{$this->name}', '{$reason}', '{$text}')";
		$adb->query($query, true);
	}
	
	public function Kill()
	{
		$this->kills++;
		$this->expected_score += 100;
	}
	
	public function Death()
	{
		$this->deaths++;
	}
	
	public function Suicide()
	{
		$this->Death();
		$this->suicides++;
	}
	
	private function UpdatePlayerStats()
	{
		global $adb;
		
		$query = "UPDATE {$adb->prefix}player_stats SET player_stats_kills = player_stats_kills + {$this->kills}, 
																										player_stats_deaths = player_stats_deaths + {$this->deaths}, 
																										player_stats_suicides = player_stats_suicides + {$this->suicides}, 
																										player_stats_captures = player_stats_captures + {$this->captures},
																										player_stats_assists = player_stats_assists + {$this->assists},
																										player_stats_returns = player_stats_returns + {$this->returns},
																										player_stats_carrier_kills = player_stats_carrier_kills + {$this->carrier_kills},
																										player_stats_max_score = GREATEST(player_stats_max_score, {$this->score})
																							WHERE player_stats_id = {$this->stats}";
		$adb->query($query, true);
		
		$this->kills = 0;
		$this->deaths = 0;
		$this->suicides = 0;
		$this->captures = 0;
		$this->carrier_kills = 0;
		$this->assists = 0;
		$this->returns = 0;
	}
	
	private function GetPlayerStats()
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}player_stats WHERE server_id = {$this->config['id']} AND player_id = {$this->id}";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'player_stats_id');
			
		$query = "INSERT INTO {$adb->prefix}player_stats (player_id, server_id) VALUES ({$this->id}, {$this->config['id']})";
		$adb->query($query, true);
		
		return $this->GetPlayerStats();
	}
	
	private function UpdateScore($score)
	{
		if ($score != $this->expected_score)
		{
			if (($score - $this->expected_score) < -1000)
				$this->carrier_kills++;
			else if (($score - $this->expected_score) >= 250)
				$this->captures++;
			else if (($score - $this->expected_score) >= 100)
				$this->returns++;
			else
				$this->assists++;
		}
		
		$this->expected_score = $this->score = $score;
	}
	
	private function ClearWarnings()
	{
		global $adb;
		
		if (isset($this->id))
		{
			$query = "DELETE FROM {$adb->prefix}warnings WHERE user_id = {$this->id}";
			$adb->query($query, true);
		}
	}
	
	private function UpdateName($name)
	{
		global $adb;
		
		$query = "UPDATE {$adb->prefix}players SET player_name = '{$name}' WHERE player_id = {$this->id}";
		$adb->query($query, true);
		
		$old_name = $this->name;
		$this->name = $name;
		
		if (strlen($old_name) > 0 && $name != $old_name)
			$this->Action(ACTION_NAME, 'Name changed', $old_name, time());
	}
	
	private function UpdateLastIP($ip)
	{
		global $adb;
		
		$query = "UPDATE {$adb->prefix}players SET player_last_ip = '{$ip}' WHERE player_id = {$this->id}";
		$adb->query($query, true);
		
		$this->ip = $ip;
	}
	
	private function GetPlayer($guid)
	{
		global $adb;
		
		$query = "SELECT player_id FROM {$adb->prefix}players WHERE player_guid = '{$guid}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'player_id');
		
		return false;
	}
	
	private function CreatePlayer($guid)
	{
		global $adb;
		
		$query = "INSERT INTO {$adb->prefix}players (player_guid, player_name, player_last_ip) VALUES ('{$guid}', '', '')";
		$adb->query($query, true);

		$player = $this->GetPlayer($guid, $name);
		
		$query = "INSERT INTO {$adb->prefix}player_groups (player_id, server_id, group_id) VALUES ({$player}, {$this->config['id']}, 6)";
		$adb->query($query, true);
		
		return $player;
	}
}

class PlayerNew
{
	private $id;
	
	private $guid;
	private $name;
	
	private $slot;
	private $ip;
	private $score;
	private $ping;
	
	private $statistics;
	
	private $config;
	
	public function __construct($config)
	{
		$this->config = $config;
	}
	
	public function Initialise($guid)
	{
		$this->id = $this->GetPlayer($guid);
		$this->guid = $guid;
		
		global $adb;

		$query = "SELECT * FROM {$adb->prefix}players WHERE player_id = {$this->id}";
		$result = $adb->query($query, false);
		
		$this->name = addslashes($adb->query_result($result, 0, 'player_name'));
		$this->ip = $adb->query_result($result, 0, 'player_last_ip');
		
		$this->statistics = new PlayerStatistics($this->id, $this->config['id']);
	}
	
	public function ID()
	{
		return $this->name;
	}
	
	public function Name()
	{
		return $this->name;
	}
	
	public function GUID()
	{
		return $this->guid;
	}
	
	public function Slot()
	{
		return $this->slot;
	}
	
	public function Score()
	{
		return $this->score;
	}
	
	public function Ping()
	{
		return $this->ping;
	}
	
	public function Group()
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}player_groups WHERE player_id = {$player_id} AND server_id = {$this->$config['id']}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'group_id');

		$query = "INSERT INTO {$adb->prefix}player_groups (player_id, server_id, group_id) VALUES ({$player_id}, {$this->$config['id']}, 6)";
		$adb->query($query, true);
		
		return 6;
	}
	
	public function Kill($team_kill = false)
	{
		$this->statistics->Kill($team_kill);
	}
	
	public function Death($suicide = false)
	{
		$this->statistics->Death($suicide);
	}
	
	public function Damage($amount)
	{
		return $this->statistics->Damage($amount);
	}
	
	public function Update($score)
	{
		$this->statistics->Update($score);
	}
	
	private function GetPlayer($guid)
	{
		global $adb;
		
		$query = "SELECT player_id FROM {$adb->prefix}players WHERE player_guid = '{$guid}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'player_id');
		
		$query = "INSERT INTO {$adb->prefix}players (player_guid, player_name, player_last_ip) VALUES ('{$guid}', '', '')";
		$adb->query($query, true);

		return $this->GetPlayer($guid);
	}
}

class PlayerStatistics
{
	private $player_id;
	private $server_id;
	
	private $statistics_id;
	
	private $actual_score;
	private $expected_score;
	
	private $kills;
	private $deaths;
	private $suicides;
	private $assists;
	
	private $captures;
	private $returns;
	private $carrier_kills;
	
	private $second_chance_test;
	
	private $last_update;
	
	public function __construct($player_id, $server_id)
	{
		$this->player_id = $player_id;
		$this->server_id = $server_id;
		
		$this->statistics_id = $this->GetStatistics($player_id, $server_id);
		
		$this->second_chance_test = 0;
	}
	
	public function Kill($team_kill)
	{
		if ($team_kill)
		{
			$this->kills++;
			$this->expected_score += 100;
		}
		else
		{
			$this->expected_score -= 100;
		}
	}
	
	public function Death($suicide)
	{
		$this->deaths++;
		
		if ($suicide)
			$this->suicides++;
	}
	
	public function Damage($damage)
	{
		global $adb;
		$current_damage = $damage;
		
		if ($damage == -1)
		{
			$current_damage = 0;
			
			// clear damage
			$query = "DELETE FROM {$adb->prefix}server_damage WHERE server_id = {$this->server_id} AND player_id = {$this->player_id}";
			$adb->query($query, true);
		
			$this->second_chance_test--;
		}
		else
		{
			$query = "SELECT * FROM {$adb->prefix}server_damage WHERE server_id = {$this->server_id} AND player_id = {$this->player_id}";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				$current_damage += $adb->query_result($result, 0, 'server_damage_total');
				$query = "UPDATE {$adb->prefix}server_damage SET server_damage_total = server_damage_total + {$damage} WHERE server_id = {$this->server_id} AND player_id = {$this->player_id}";
			}
			else
			{
				$query = "INSERT INTO {$adb->prefix}server_damage (server_id, player_id, server_damage_total) VALUES ({$this->server_id}, {$this->player_id}, {$damage})";
			}
			
			$adb->query($query, true);
		}
			
		if (RconServer::IsHardcore())
		{
			if ($current_damage >= 30)
				$this->second_chance_test += 2;
		}
		else 
		{
			if ($current_damage >= 100)
				$this->second_chance_test += 2;
		}
			
		return ($this->second_chance_test >= 5);
	}
	
	public function Update($score)
	{
		$this->CheckScore($score);
		
		global $adb;
		$query = "UPDATE {$adb->prefix}player_stats SET player_stats_kills = {$this->kills}, 
																										player_stats_deaths = {$this->deaths}, 
																										player_stats_suicides = {$this->suicides}, 
																										player_stats_assists = {$this->assists},
																										player_stats_captures = {$this->captures},
																										player_stats_returns = {$this->returns},
																										player_stats_carrier_kills = {$this->carrier_kills},
																										player_stats_max_score = GREATEST(player_stats_max_score, {$this->actual_score})
																							WHERE player_stats_id = {$this->statistics_id}";
		$adb->query($query, true);
		
		$this->last_update = time();
	}
	
	private function CheckScore($score)
	{
		$this->actual_score = $score;
		
		if ($this->actual_score != $this->expected_score)
		{
			if (($this->actual_score - $this->expected_score) < -1000)
				$this->carrier_kills++;
			else if (($this->actual_score - $this->expected_score) >= 250)
				$this->captures++;
			else if (($this->actual_score - $this->expected_score) >= 100)
				$this->returns++;
			else
				$this->assists++;
		}
		
		$this->expected_score = $this->actual_score;
	}
	
	private function GetStatistics($player_id, $server_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}player_stats WHERE player_id = {$player_id} AND server_id = {$server_id}";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$this->kills = $adb->query_result($result, 0, 'player_stats_kills');
			$this->deaths = $adb->query_result($result, 0, 'player_stats_deaths');
			$this->suicides = $adb->query_result($result, 0, 'player_stats_suicides');
			$this->assists = $adb->query_result($result, 0, 'player_stats_assists');
			
			$this->captures = $adb->query_result($result, 0, 'player_stats_captures');
			$this->returns = $adb->query_result($result, 0, 'player_stats_returns');
			$this->carrier_kills = $adb->query_result($result, 0, 'player_stats_carrier_kills');
			
			return $adb->query_result($result, 0, 'player_stats_id');
		}
			
		$query = "INSERT INTO {$adb->prefix}player_stats (player_id, server_id) VALUES ({$player_id}, {$server_id})";
		$adb->query($query, true);
		
		return $this->GetStatistics($player_id, $server_id);
	}
}

class RconPlayers
{
	private static $config;
	private static $players;
	
	public static function Initialise($config)
	{
		self::$config = $config;
		self::$players = array();
	}
	
	public static function Connected($slot, $guid, $name)
	{
		$slot = intval($slot);
		
		self::$players[$slot] = new Player(self::$config);
		self::$players[$slot]->PerformAction($guid, ACTION_JOIN, 'Player connected');
	}
	
	public static function Disconnected($slot, $guid)
	{
		$slot = intval($slot);
		
		if (array_key_exists($slot, self::$players))
		{
			self::$players[$slot]->PerformAction($guid, ACTION_QUIT, 'Player left server');
			unset($players[$slot]);
		}
	}
	
	public static function Log($slot, $guid, $text)
	{
		$slot = intval($slot);
		
		if (array_key_exists($slot, self::$players))
		{
			self::$players[$slot]->PerformAction($guid, ACTION_LOG, 'Chat Log', $text);
			return true;
		}
		
		return false;
	}

	public static function Player($guid)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}players WHERE player_guid = '{$guid}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'player_id');
		
		return false;
	}
	
	public static function Group($guid)
	{
		global $adb;
		
		$player_id = self::Player($guid);
		
		if ($player_id !== false)
		{
			$query = "SELECT * FROM {$adb->prefix}player_groups WHERE player_id = {$player_id} AND server_id = " . self::$config['id'];
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
				return $adb->query_result($result, 0, 'group_id');

			$query = "INSERT INTO {$adb->prefix}player_groups (player_id, server_id, group_id) VALUES ({$player_id}, " . self::$config['id'] . ", 6)";
			$adb->query($query, true);
		}
		
		return 6;
	}

	public static function Death($slot)
	{
		$slot = intval($slot);
		
		if (array_key_exists($slot, self::$players))
		{
			self::$players[$slot]->Death();
			return true;
		}
		
		return false;
	}
	
	public static function Update($slot, $guid, $name, $score, $ip)
	{
		$slot = intval($slot);
		
		if (!array_key_exists($slot, self::$players))
			self::Connected($slot, $guid, $name);
			
		self::$players[$slot]->Update($guid, $name, $score, $ip);
	}
	
	public static function Kill($slot)
	{
		$slot = intval($slot);
		
		if (array_key_exists($slot, self::$players))
		{
			self::$players[$slot]->Kill();
			return true;
		}
		
		return false;
	}
	
	public static function Suicide($slot)
	{
		$slot = intval($slot);
		
		if (array_key_exists($slot, self::$players))
		{
			self::$players[$slot]->Suicide();
			return true;
		}
		
		return false;
	}
}

?>