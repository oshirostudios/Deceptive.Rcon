<?php
define("MODE_OVERVIEW", 0); 
define("MODE_SERVERS", 1);
define("MODE_HISTORY", 2);
define("MODE_STATISTICS", 3);
define("MODE_RESTRICTIONS", 4);
define("MODE_ROTATION", 5);
define("MODE_MESSAGES", 6);
define("MODE_COMMANDS", 7);
define("MODE_CONSOLE", 8);
define("MODE_ACCESS", 9);

class User
{
	private $user_id;
	private $user_name;
	private $user_password;
	private $user_email;
	private $user_servers;
	private $user_activation;
	private $user_activated;
	
	private $error = '';
	
	public function Login($username, $password)
	{
		$this->ResetData();

		if (empty($password))
		{
			$this->error = 'A password is required';
			return false;
		}

		if (is_string($password))
		{
			// load the data for this user
			$this->GetData($username);
		
			// Get the salt from the stored password
			$salt = $this->FindSalt($this->user_password);
			
			// Create a hashed password using the salt from the stored password
			$password = self::PasswordHash($password, $salt);
		}
		
		if ($this->user_password == $password)
		{
			if ($this->user_activated == 1)
			{
				$this->UpdateLastLogin();
				return true;
			}
			
			$this->error = 'Your account has not been activated';
		}
		else
		{
			$this->error = 'Login failed';
		}
			
		$this->ResetData();

		return false;
	}
	
	public function Logout()
	{
		$this->ResetData();
	}
	
	public function Error()
	{
		return $this->error;
	}
	
	public static function Create($username, $email, $password)
	{
		global $adb;

		$password_hash = self::PasswordHash($password);
		$activation = uniqid();
		
		$query = "INSERT INTO {$adb->prefix}users (user_name, user_email, user_password, user_activation, user_activated) VALUES ('$username', '$email', '$password_hash', '$activation', 0)";
		$adb->query($query, true);
		
		return $activation;
	}
	
	public static function Activate($activation)
	{
		global $adb;

		$query = "SELECT * FROM {$adb->prefix}users WHERE user_activation = '{$activation}'";
		$result = $adb->query($query, false);

		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$user_id = $adb->query_result($result, 0, 'user_id');
			 
			$query = "UPDATE {$adb->prefix}users SET user_activated = 1 WHERE user_id = {$user_id}";
			$adb->query($query, true);
			
			return true;
		}
		
		return false;
	}
	
	public function Access()
	{
		$modules = array('overview.secure', 'servers.secure', 'history.secure', 'statistics.secure', 'restrictions.secure', 'rotation.secure', 'messages.secure', 'commands.secure', 'console.secure');
		$modify_access = false;
		
		$servers = $this->Servers();
		
		for ($server = 0; $server < count($servers); $server++)
		{
			if ($servers[$server]->Creator() == $this->user_id)
				$modify_access = true;
		}
		
		if ($modify_access)
			$modules[] = 'access.secure';
		
		$modules[] = 'logout';
		
		return $modules; 
	}
	
	public function HasAccess($server, $mode)
	{
		$servers = $this->Servers();

		for ($count = 0; $count < count($servers); $count++)
		{
			if ($servers[$count]->ID() == $server)
				return $servers[$count]->Access($this->user_id, $mode);
		}
		
		return false;
	}
	
	public function ID()
	{
		return $this->user_id;
	}

	public function Name()
	{
		return $this->user_name;
	}
	
	public function Password()
	{
		return $this->user_password;
	}
	
	public function Email()
	{
		return $this->user_email;
	}
	
	public function Servers($reload = false)
	{
		if ($this->user_servers == null || $reload)
		{
			global $adb;
			
			$this->user_servers = array();
			
			$query = "SELECT * FROM {$adb->prefix}servers WHERE user_id = {$this->user_id}";
			$result = $adb->query($query, false);

			if (!empty($result))
			{
				$total_servers = $adb->num_rows($result);
				
				for ($count = 0; $count < $total_servers; $count++)
				{
					$server_id = $adb->query_result($result, $count, 'server_id');
					$this->user_servers[] = new Server($server_id);
				}
			}
			
			$query = "SELECT * FROM {$adb->prefix}server_users WHERE user_id = {$this->user_id}";
			$result = $adb->query($query, false);

			if (!empty($result))
			{
				$total_servers = $adb->num_rows($result);
				
				for ($count = 0; $count < $total_servers; $count++)
				{
					$server_id = $adb->query_result($result, $count, 'server_id');
					$this->user_servers[] = new Server($server_id);
				}
			}
		}
		
		return $this->user_servers;
	}
	
	public function Server($server_id)
	{
		for ($count = 0; $count < count($this->user_servers); $count++)
		{
			if ($server_id == $this->user_servers[$count]->ID())
				return $this->user_servers[$count];
		}
		
		return false;
	}
	
	public static function PasswordHash($password, $salt = false)
	{
		global $CONFIG;
		
		if ($salt === FALSE)
		{
			// create a salt seed, same length as the number of offsets in the pattern
			$salt = substr(hash('sha1', uniqid(NULL, TRUE)), 0, count($CONFIG['salt_pattern']));
		}

		// password hash that the salt will be inserted into
		$hash = hash('sha1', $salt.$password);
		
		// change salt to an array
		$salt = str_split($salt, 1);

		// returned password
		$password = '';
		
		// used to calculate the length of splits
		$last_offset = 0;

		foreach ($CONFIG['salt_pattern'] as $offset)
		{
			// split a new part of the hash off
			$part = substr($hash, 0, $offset - $last_offset);

			// cut the current part out of the hash
			$hash = substr($hash, $offset - $last_offset);

			// add the part to the password, appending the salt character
			$password .= $part.array_shift($salt);

			// set the last offset to the current offset
			$last_offset = $offset;
		}

		// return the password, with the remaining hash appended
		return $password.$hash;
	}
	
	public function FindSalt($password)
	{
		global $CONFIG;
		
		$salt = '';

		foreach ($CONFIG['salt_pattern'] as $i => $offset)
		{
			// Find salt characters, take a good long look...
			$salt .= substr($password, $offset + $i, 1);
		}

		return $salt;
	}
	
	private function UpdateLastLogin()
	{
		global $adb;
		
		$timestamp = time();
		
		$query = "UPDATE {$adb->prefix}users SET user_logins = user_logins + 1, user_last_login = '{$timestamp}' WHERE user_id = {$this->user_id}";
		$adb->query($query, true);
	}
	
	private function GetData($username)
	{
		global $adb;
		
		$data_found = false;
		
		if (strstr($username, '@') === false)
			$query = "SELECT * FROM {$adb->prefix}users WHERE user_name = '$username'";
		else
			$query = "SELECT * FROM {$adb->prefix}users WHERE user_email = '$username'";
		
		// Check if the user exists in the database
		$result = $adb->query($query, false);

		if (!empty($result))
		{
			if ($adb->num_rows($result) > 0)
			{
				$data_found = true;
				
				// Retrieve the password from the database
				$this->user_id = $adb->query_result($result, 0, 'user_id');
				$this->user_name = $adb->query_result($result, 0, 'user_name');
				$this->user_password = $adb->query_result($result, 0, 'user_password');
				$this->user_email = $adb->query_result($result, 0, 'user_email');
				$this->user_activation = $adb->query_result($result, 0, 'user_activation');
				$this->user_activated = $adb->query_result($result, 0, 'user_activated');
			}
		}
		
		if (!$data_found)
			$this->ResetData();
	}

	public function ShowServerSelect($mode, $reload = false)
	{
		// server status... select servers from accessible server ID's
		$servers = $this->Servers($reload);
		$server_id = $_SESSION['server-id'];

		// if this user doesn't have access, reset the active server		
		if (!$this->HasAccess($server_id, $mode))
			$server_id = $_SESSION['server-id'] = 0;
		
		echo '<div class="page_center">' . "\n";
		echo '<form method="post">' . "\n";
		echo '<b>Select a server:</b> <select class="important" name="server-id" onchange="this.form.submit();">' . "\n";
		
		if ($server_id == 0)
			echo '<option selected value="0">None</option>' . "\n";
		else
			echo '<option value="0">None</option>' . "\n";
				
		// put inside a combo box
		for ($count = 0; $count < count($servers); $count++)
		{
			if ($this->HasAccess($servers[$count]->ID(), $mode))
			{
				if ($server_id == $servers[$count]->ID())
					echo '<option selected value="' . $servers[$count]->ID() . '">' . $servers[$count]->Name() . '</option>' . "\n";
				else
					echo '<option value="' . $servers[$count]->ID() . '">' . $servers[$count]->Name() . '</option>' . "\n";
			}
		}
		
		echo '</select>' . "\n";
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	}
	
	private function ResetData()
	{
		$this->user_id = 0;
		$this->user_name = '';
		$this->user_password = '';
		$this->user_email = '';
		$this->user_servers = null;
	}
}

class Server
{
	private $server_id;
	private $server_name;
	private $server_ip;
	private $server_port;
	private $server_log_url;
	private $server_description;
	private $server_rcon_password;
	private $server_ranked;
	private $server_access;
	private $server_activation;
	private $server_warnings;
	private $server_show_restrictions;
	private $server_max_ping;
	
	public function __construct($server_id)
	{
		global $adb;

		$this->server_id = -1;
		$this->user_id = -1;
		$this->server_name = "INVALID";
		$this->server_ip = "INVALID";
		$this->server_port = 0;
		$this->server_log_url = "INVALID";
		$this->server_description = "INVALID";
		$this->server_rcon_password = "INVALID";
		$this->server_ranked = 0;
		$this->server_owner = -1;
		$this->server_activation = '';
		$this->server_monitor = 0;
		$this->server_warnings = 2;
		$this->server_show_restrictions = 1;
		$this->server_max_ping = 0;
		
		$server_query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$server_id}";
		$server_result = $adb->query($server_query, false);

		if (!empty($server_result) && $adb->num_rows($server_result) > 0)
		{
			$this->server_id = $adb->query_result($server_result, 0, 'server_id');
			$this->user_id = $adb->query_result($server_result, 0, 'user_id');
			$this->server_name = $adb->query_result($server_result, 0, 'server_name');
			$this->server_ip = $adb->query_result($server_result, 0, 'server_ip');
			$this->server_port = $adb->query_result($server_result, 0, 'server_port');
			$this->server_log_url = $adb->query_result($server_result, 0, 'server_log_url');
			$this->server_description = $adb->query_result($server_result, 0, 'server_description');
			$this->server_rcon_password = $adb->query_result($server_result, 0, 'server_rcon_password');
			$this->server_ranked = $adb->query_result($server_result, 0, 'server_ranked');
			$this->server_owner = $adb->query_result($server_result, 0, 'server_owner_id');
			$this->server_activation = $adb->query_result($server_result, 0, 'server_activation');
			$this->server_monitor = $adb->query_result($server_result, 0, 'server_monitor');
			$this->server_warnings = $adb->query_result($server_result, 0, 'server_warnings');
			$this->server_show_restrictions = $adb->query_result($server_result, 0, 'server_show_restrictions');
			$this->server_max_ping = $adb->query_result($server_result, 0, 'server_max_ping');
		}
	}

	public function ID()
	{
		return $this->server_id;
	}
	
	public function Creator()
	{
		return $this->user_id;
	}

	public function Name()
	{
		return $this->server_name;
	}
	
	public function IP()
	{
		return $this->server_ip;
	}
	
	public function Port()
	{
		return $this->server_port;
	}
	
	public function LogURL()
	{
		return $this->server_log_url;
	}
	
	public function Description()
	{
		return $this->server_description;
	}
	
	public function Warnings()
	{
		return $this->server_warnings;
	}
	
	public function MaxPing()
	{
		return $this->server_max_ping;
	}
	
	public function RconPassword()
	{
		return $this->server_rcon_password;
	}
	
	public function Ranked()
	{
		return $this->server_ranked;
	}
	
	public function Activation()
	{
		return $this->server_activation;
	}
	
	public function Owner()
	{
		return $this->server_owner;
	}
	
	public function Running()
	{
		return ($this->server_monitor == 1);
	}
	
	public function ShowRestrictions()
	{
		return $this->server_show_restrictions;
	}
	
	public function Access($user, $mode)
	{
		if ($user == $this->Creator())
			return true;
		
		// to change access you must be the owner
		if ($mode == MODE_ACCESS)
			return false;
		
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}server_users WHERE server_id = {$this->server_id} AND user_id = {$user}";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$server_access = $adb->query_result($result, 0, 'server_user_permissions');
			
			if ($server_access[$mode] == '1')
				return true;
		}
		
		return false;
	}
	
	public function Rotation($rotation_id = 1)
	{
		global $adb;

		$query = "SELECT * FROM {$adb->prefix}rotation WHERE rotation_group_id = {$rotation_id} ORDER BY rotation_sort";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$data[$count]['id'] = $adb->query_result($result, $count, 'server_rotation_id');
				$data[$count]['order'] = $adb->query_result($result, $count, 'rotation_sort');
				$data[$count]['map'] = new Map($adb->query_result($result, $count, 'map_id'));
				
				if ($adb->query_result($result, $count, 'mode_type_id') != -1)
					$data[$count]['playlist'] = new Playlist($adb->query_result($result, $count, 'mode_type_id'));
				else
					$data[$count]['game'] = new Game($adb->query_result($result, $count, 'game_id'));
			}
			
			return $data;
		}
		
		return false;
	}
	
	public function RotationGroup()
	{
		global $adb;
		
		$group_id = -1;

		$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE server_id = {$this->server_id} AND rotation_group_active = 1";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$group_id = $adb->query_result($result, $count, 'rotation_group_id');
		}
		else
		{
			$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE server_id = {$this->server_id} ORDER BY rotation_group_id";
			$result = $adb->query($query, false);
			
			if (!empty($result) && $adb->num_rows($result) > 0)
			{
				$group_id = $adb->query_result($result, $count, 'rotation_group_id');

				$query = "UPDATE {$adb->prefix}rotation_group SET rotation_group_active = 1 WHERE server_id = {$this->server_id} AND rotation_group_id = {$group_id}";
				$adb->query($query, true);
			}
		}

		return $group_id;
	}
	
	public function GroupDetail($group)
	{
		global $adb;
		
		$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE server_id = {$this->server_id} ORDER BY rotation_group_name";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$data = array();
			
			if ($adb->num_rows($result) == 1)
			{
				$data['name'] = $adb->query_result($result, $count, 'rotation_group_name');
			}
			else
			{
				$next = -1; $previous = -1;
			
				for ($count = 0; $count < $adb->num_rows($result); $count++)
				{
					$group_id = $adb->query_result($result, $count, 'rotation_group_id');
					
					if ($group == $group_id)
					{
						$data['name'] = $adb->query_result($result, $count, 'rotation_group_name');
							
						if ($count == 0)
							$previous = $adb->num_rows($result) - 1;
						
						$next = $count + 1;
						if ($next == $adb->num_rows($result))
							$next = 0;

						break;
					}
					else
					{
						$previous = $count;
					}
				}

				$data['next'] = $group_id = $adb->query_result($result, $next, 'rotation_group_id');
				$data['previous'] = $group_id = $adb->query_result($result, $previous, 'rotation_group_id');
			}
			
			return $data;
		}
		
		return false;
	}
}

class Blackops
{
	private $maps;
	private $playlists;
	private $games;
	
	private $dlc;
	
	public function __construct()
	{
		global $adb;
		
		$map_query = "SELECT map_id FROM {$adb->prefix}maps ORDER BY map_id";
		$map_result = $adb->query($map_query, false);

		if (!empty($map_result) && $adb->num_rows($map_result) > 0)
		{
			$this->maps = array();
			
			for ($count = 0; $count < $adb->num_rows($map_result); $count++)
			{
				$this->maps[$count] = new Map($adb->query_result($map_result, $count, 'map_id'));
			}
		}
		
		$playlist_query = "SELECT mode_type_id FROM {$adb->prefix}mode_types ORDER BY mode_type_players DESC, mode_id, type_id";
		$playlist_result = $adb->query($playlist_query, false);

		if (!empty($playlist_result) && $adb->num_rows($playlist_result) > 0)
		{
			$this->playlists = array();
			
			for ($count = 0; $count < $adb->num_rows($playlist_result); $count++)
			{
				$this->playlists[$count] = new Playlist($adb->query_result($playlist_result, $count, 'mode_type_id'));
			}
		}
		
		$game_query = "SELECT game_id FROM {$adb->prefix}game ORDER BY game_name";
		$game_result = $adb->query($game_query, false);

		if (!empty($game_result) && $adb->num_rows($game_result) > 0)
		{
			$this->games = array();
			
			for ($count = 0; $count < $adb->num_rows($game_result); $count++)
			{
				$this->games[$count] = new Game($adb->query_result($game_result, $count, 'game_id'));
			}
		}
	}
	
	public function Maps()
	{
		return $this->maps;
	}
	
	public function Playlists()
	{
		return $this->playlists;
	}
	
	public function Games()
	{
		return $this->games;
	}
}

class Map
{
	private $map_id;
	private $map_file;
	private $map_name;
	
	public function __construct($map_id)
	{
		global $adb;
		
		$this->map_id = -1;
		$this->map_file = "INVALID";
		$this->map_name = "INVALID";

		$map_query = "SELECT * FROM {$adb->prefix}maps WHERE map_id = {$map_id}";
		$map_result = $adb->query($map_query, false);

		if (!empty($map_result) && $adb->num_rows($map_result) > 0)
		{
			$this->map_id = $adb->query_result($map_result, 0, 'map_id');
			$this->map_file = $adb->query_result($map_result, 0, 'map_file');
			$this->map_name = $adb->query_result($map_result, 0, 'map_name');
		}
	}
	
	public function ID()
	{
		return $this->map_id;
	}
	
	public function File()
	{
		return $this->map_file;
	}
	
	public function Name()
	{
		return $this->map_name;
	}
}

class Playlist
{
	private $id;
	private $mode_id;
	private $mode_name;
	private $type_id;
	private $type_name;
	private $players;
	
	public function __construct($id, $type_id = -1, $players = -1)
	{
		global $adb;
		
		if ($type_id == -1)
			$query = "SELECT * FROM {$adb->prefix}mode_types WHERE mode_type_id = {$id}";
		else
			$query = "SELECT * FROM {$adb->prefix}mode_types WHERE mode_id = {$id} AND type_id = {$type_id} AND mode_type_players = {$players}";
			
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$this->id = $adb->query_result($result, 0, 'mode_type_id');
			$this->mode_id = $adb->query_result($result, 0, 'mode_id');
			$this->type_id = $adb->query_result($result, 0, 'type_id');
			$this->players = $adb->query_result($result, 0, 'mode_type_players');
			
			$mode_query = "SELECT * FROM {$adb->prefix}modes WHERE mode_id = {$this->mode_id}";
			$mode_result = $adb->query($mode_query, false);
			$this->mode_name = $adb->query_result($mode_result, 0, 'mode_name');
			
			$type_query = "SELECT * FROM {$adb->prefix}types WHERE type_id = {$this->type_id}";
			$type_result = $adb->query($type_query, false);
			$this->type_name = $adb->query_result($type_result, 0, 'type_name');
		}
	}
	
	public function ID()
	{
		return $this->id;
	}
	
	public function ModeID()
	{
		return $this->mode_id;
	}
	
	public function ModeName()
	{
		return $this->mode_name;
	}
	
	public function TypeID()
	{
		return $this->type_id;
	}
	
	public function TypeName()
	{
		return $this->type_name;
	}
	
	public function Players()
	{
		return $this->players;
	}
}

class Game
{
	private $game_id;
	private $game_name;
	private $game_value;
	
	public function __construct($game_id)
	{
		global $adb;
		
		$this->game_id = -1;
		$this->game_name = "INVALID";
		$this->game_value = "INVALID";

		$game_query = "SELECT * FROM {$adb->prefix}game WHERE game_id = {$game_id}";
		$game_result = $adb->query($game_query, false);

		if (!empty($game_result) && $adb->num_rows($game_result) > 0)
		{
			$this->game_id = $adb->query_result($game_result, 0, 'game_id');
			$this->game_name = $adb->query_result($game_result, 0, 'game_name');
			$this->game_value = $adb->query_result($game_result, 0, 'game_value');
		}
	}
	
	public function ID()
	{
		return $this->game_id;
	}
	
	public function Name()
	{
		return $this->game_name;
	}
	
	public function Value()
	{
		return $this->game_value;
	}
}

?>
