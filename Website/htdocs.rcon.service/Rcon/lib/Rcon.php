<?php

class Rcon
{
	private static $socket = false;
	
	private static $host = '';
	private static $port = 0;
	private static $password = '';
	private static $format = '';
	
	private static $last_message;
	
	public static function Setup($host, $port, $password, $format = "\xff\xff\xff\xff\x00PWD\x20CMD\x00")
	{
		self::$host = $host;
		self::$port = $port;
		self::$password = $password;
		self::$format = $format;
		
		self::$last_message = round(microtime(true) * 1000) - 1500;
		
		self::Command("g_logsync 1", false);
		self::Command("g_logTimeStampInSeconds 1", false);
		
		self::Command("playlist_excludeDlc2 0", false);
		self::Command("playlist_excludeDlc3 0", false);
		self::Command("playlist_excludeDlc4 0", false);
	}
	
	public static function Tell($slot, $message)
	{
		if ($slot > 0)
		{
			$command = "tell {$slot} ^1pm: ^7{$message}";
			self::Command($command, false);
		}
	}
	
	public static function Command($command, $response = true)
	{
		self::Connect();

		$now = round(microtime(true) * 1000);
		$remaining = 50 - (abs(self::$last_message - $now));

		if ($remaining > 0)
			usleep($remaining * 1000);
			
		for ($count = 0; $count < 3 && $return == ''; $count++)
		{
			if (self::Send($command))
				$return = self::Receive();
				
			if ($return == '')
				usleep(50000);
		}
		
		self::$last_message = round(microtime(true) * 1000);
		self::Disconnect();
			
		if ($response)
			return $return;
		else
			return $return != '';
	}
	
	private static function HasError()
	{
		if (($error = socket_last_error(self::$socket)) == 0)
			return false;
	
		System_Daemon::log(System_Daemon::LOG_INFO, " - connection error [{$error}] " . socket_strerror($error));
		return true;
	}
	
	private static function IsConnected()
	{
		if (self::$socket !== false)
			return true;
		
		return false;
	}
	
	private static function Connect()
	{
		if (self::IsConnected())
			self::Disconnect();
		
		$error = 0;
		$error_string = '';
		
		System_Daemon::log(System_Daemon::LOG_INFO, " - connecting to " . self::$host . ":" . self::$port);

		self::$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		
		if (self::$socket === false)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - connection failed (" . socket_strerror(socket_last_error($socket)) . ")");
			return false;
		}
		
		$result = socket_connect(self::$socket, self::$host, self::$port);
		
		if ($result === false)
		{
			System_Daemon::log(System_Daemon::LOG_INFO, " - connection failed ({$result} - " . socket_strerror(socket_last_error($socket)) . ")");
			return false;
		}
		
		socket_set_option(self::$socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0));
		
		return true;
	}
	
	private static function Disconnect()
	{
		if (self::IsConnected())
			socket_close(self::$socket);
		
		self::$socket = false;
	}
	
	private static function Send($command)	
	{
		if (self::$socket === false || !is_resource(self::$socket))
			return false;
			
		$message = str_replace("PWD", self::$password, self::$format);
		$message = str_replace("CMD", $command, $message);
		$message_length = strlen($message);

		if (socket_write(self::$socket, $message, $message_length) == $message_length)
			return true;
		
		return false;
	}
	
	private static function Receive()
	{
		$response = '';
		$stream_read = '';
		
		while (socket_recv(self::$socket, $stream_read, 4096, 0) !== false)
		{
			$response .= $stream_read;
		}
		
		socket_clear_error(self::$socket);
		
		return $response;
	}
}

?>
