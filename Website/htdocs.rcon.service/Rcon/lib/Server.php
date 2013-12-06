<?php
defined('RCON_PATH') or die('No direct script access.');

error_reporting(E_ERROR | E_PARSE);

require_once RCON_PATH . 'config/config.php';

require_once RCON_PATH . 'lib/Database.php';
require_once RCON_PATH . 'lib/Log.php';
require_once RCON_PATH . 'lib/Players.php';
require_once RCON_PATH . 'lib/Plugin.php';
require_once RCON_PATH . 'lib/Rcon.php';

class RconServer
{
	private static $config;
	private static $plugins;
	private static $forced_run;
	
	public static function Initialise($server_id, $forced_run)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$server_id}";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) == 1)
		{
			self::$config = array();
			
			self::$config['id'] = $server_id;
			self::$config['name'] = $adb->query_result($result, 0, 'server_name');
			self::$config['ip'] = $adb->query_result($result, 0, 'server_ip');
			self::$config['port'] = $adb->query_result($result, 0, 'server_port');
			self::$config['url'] = $adb->query_result($result, 0, 'server_log_url');
			self::$config['desc'] = $adb->query_result($result, 0, 'server_description');
			self::$config['rcon'] = $adb->query_result($result, 0, 'server_rcon_password');
			self::$config['ranked'] = $adb->query_result($result, 0, 'server_ranked');
			self::$config['warnings'] = $adb->query_result($result, 0, 'server_warnings');
			self::$config['show_restrictions'] = $adb->query_result($result, 0, 'server_show_restrictions');
			self::$config['max_ping'] = $adb->query_result($result, 0, 'server_max_ping');
			self::$config['debug'] = false;
		}
		
		Rcon::Setup(self::$config['ip'], self::$config['port'], self::$config['rcon']);
		RconPlayers::Initialise(self::$config);
		
		self::$forced_run = $forced_run;
		
		self::$plugins = array();
		self::Update();
	}
	
	public static function Update()
	{
		System_Daemon::log(System_Daemon::LOG_INFO, "Updating plugins...");
		
		$plugin_folder = RCON_PATH . 'plugins/';
		$search = scandir($plugin_folder);
		
		foreach ($search as $file)
		{
			if ($file == '.' || $file == '..' || !is_file($plugin_folder . $file) || substr($file, -4) != '.php')
				continue;
			
			$plugin_name = basename($file, '.php');
			
			if (array_key_exists($plugin_name, self::$plugins))
				continue;
		
			require_once $plugin_folder . $file;
			
			$class_name = ucfirst($plugin_name) . '_Plugin';
			
			if (!class_exists($class_name))
				continue;
				
			$class = new $class_name;
			
			if (!($class instanceof Plugin))
				continue;
				
			self::$plugins[$plugin_name] = $class;
			self::$plugins[$plugin_name]->Load(self::$config);
		}
	}
	
	public static function Run($first_run)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, "Running plugins...");
		
		for ($priority = PRIORITY_HIGHEST; $priority >= PRIORITY_LAST; $priority--)
		{
			foreach (self::$plugins as $class_name => $object)
			{
				if ($object->Priority() == $priority)
				{
					try
					{
						$object->Run($first_run);
					}
					catch (Exception $e)
					{
						System_Daemon::log(System_Daemon::LOG_INFO, " - exception caught: " . $e->getMessage());
					}
					
					// should only be one final priority item
					if ($priority == PRIORITY_LAST)
						break;
				}
			}
		}
		
		self::SetLastUpdate();
	}
	
	public static function State()
	{
		System_Daemon::log(System_Daemon::LOG_INFO, "Checking state...");

		foreach (self::$plugins as $class_name => $object)
		{
			if (!$object->State())
				return false;
		}
		
		return !self::NeedsRestart();
	}
	
	public static function SetServerStatus($status_id, $map_id, $playlist, $rotation, $game = -1)
	{
		global $adb;
		
		if ($game == -1)
		{
			if (self::CheckServerStatus($status_id))
				$query = "UPDATE {$adb->prefix}server_status SET map_id = {$map_id}, mode_type_id = {$playlist}, rotation_sort = {$rotation} WHERE server_id = " . self::$config['id'] . " AND server_status_id = {$status_id}";
			else
				$query = "INSERT INTO {$adb->prefix}server_status (server_id, server_status_id, map_id, mode_type_id, rotation_sort) VALUES (" . self::$config['id'] . ", {$status_id}, {$map_id}, {$playlist}, {$rotation})";
		}
		else
		{
			if (self::CheckServerStatus($status_id))
				$query = "UPDATE {$adb->prefix}server_status SET map_id = {$map_id}, mode_type_id = -1, game_id = {$game}, rotation_sort = {$rotation} WHERE server_id = " . self::$config['id'] . " AND server_status_id = {$status_id}";
			else
				$query = "INSERT INTO {$adb->prefix}server_status (server_id, server_status_id, map_id, mode_type_id, game_id, rotation_sort) VALUES (" . self::$config['id'] . ", {$status_id}, {$map_id}, -1, {$game}, {$rotation})";
		}
		
		$adb->query($query, true);
	}
	
	public static function CheckServerStatus($status_id)
	{
		if (self::GetServerStatus($status_id) !== false)
			return true;
		
		return false;
	}
	
	public static function GetServerStatus($status_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_status WHERE server_id = " . self::$config['id'] . " AND server_status_id = {$status_id}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			
			$data['map'] = $adb->query_result($result, 0, 'map_id');
			$data['playlist'] = $adb->query_result($result, 0, 'mode_type_id');
			$data['game'] = $adb->query_result($result, 0, 'game_id');
			$data['rotation'] = $adb->query_result($result, 0, 'rotation_sort');
			
			return $data;
		}
		
		return false;
	}
	
	public static function GetPlaylist($mode, $type, $players = 18)
	{
		global $adb;
		$playlist = -1;
		
		$query = "SELECT * FROM {$adb->prefix}mode_types WHERE mode_id = {$mode} AND type_id = {$type} AND mode_type_players = {$players}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$playlist = $adb->query_result($result, 0, 'mode_type_id');
		}
		
		return $playlist;
	}
	
	public static function GetGame($game)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}game WHERE game_id = {$game}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
			return $adb->query_result($result, 0, 'game_value');
		
		return "tdm";
	}
	
	public static function GetMapID($map)
	{
		global $adb;
		$map_id = -1;
		
		if (strpos($map, "mp_") === false)
			$query = "SELECT map_id FROM {$adb->prefix}maps WHERE map_name LIKE '{$map}%'";
		else
			$query = "SELECT map_id FROM {$adb->prefix}maps WHERE map_file = '{$map}'";
			
		$result = $adb->query($query, false);

		// only ever returns the first item		
		if (!empty($result) && $adb->num_rows($result) > 0)
			$map_id = $adb->query_result($result, 0, 'map_id');
		
		return $map_id;
	}
	
	public static function GetMapFile($map_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}maps WHERE map_id = '{$map_id}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$map = $adb->query_result($result, 0, 'map_file');
			return $map;
		}
	}
	
	public static function GetInvertedMaps($map_id)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}maps WHERE NOT map_id = '{$map_id}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$maps = '';
			
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				if ($count > 0)
					$maps .= ' ';
				$maps .= $adb->query_result($result, $count, 'map_file');
			}
			
			return $maps;
		}
	}
	
	public static function IsHardcore()
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_status INNER JOIN {$adb->prefix}mode_types ON {$adb->prefix}server_status.mode_type_id = {$adb->prefix}mode_types.mode_type_id WHERE server_id = " . self::$config['id'] . " AND server_status_id = 1";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) == 1)
		{
			// 2 is hardcore
			return ($adb->query_result($result, 0, 'mode_id') == 2);
		}
		
		return false;
	}
	
	private static function NeedsRestart()
	{
		global $adb;
		
		$query = "SELECT server_ip, server_port, server_log_url, server_rcon_password, server_monitor FROM {$adb->prefix}servers WHERE server_id = " . self::$config['id'];
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) == 1)
		{
			if (!self::$forced_run && $adb->query_result($result, 0, 'server_monitor') == 0)
				return true;
			
			if (self::$config['ip'] != $adb->query_result($result, 0, 'server_ip') ||
					self::$config['port'] != $adb->query_result($result, 0, 'server_port') ||
					self::$config['url'] != $adb->query_result($result, 0, 'server_log_url') ||
					self::$config['rcon'] != $adb->query_result($result, 0, 'server_rcon_password'))
				return true;
		}
		
		return false;
	}
	
	private static function SetLastUpdate()
	{
		global $adb;
		
		$query = "UPDATE {$adb->prefix}servers SET server_last_run = CURRENT_TIMESTAMP WHERE server_id = " . self::$config['id'];
		$adb->query($query, true);
	}
}

?>
