<?php

error_reporting(0);

class Rcon
{
	private static $socket = false;
	
	private static $host = '';
	private static $port = 0;
	private static $password = '';
	
	public static function Setup($host, $port, $password)
	{
		self::$host = $host;
		self::$port = $port;
		self::$password = $password;
		
		$_SESSION['last_message'] = round(microtime(true) * 1000) - 250;
	}

	public static function Command($command, $response = true)
	{
		$return = '';
	
		self::Connect();
		$now = round(microtime(true) * 1000);
		$remaining = 50 - (abs($_SESSION['last_message'] - $now));

		if ($remaining > 0)
			usleep($remaining * 1000);
			
		for ($count = 0; $count < 3 && $return == ''; $count++)
		{
			if (self::Send($command))
				$return = self::Receive();
				
			if ($return == '')
				usleep(50000);
		}
		
		$_SESSION['last_message'] = round(microtime(true) * 1000);
			
		if ($response)
			return $return;
		else
			return $return != '';
	}
	
	private static function HasError()
	{
		if (($error = socket_last_error(self::$socket)) == 0)
			return false;
	
		return true;
	}
	
	private static function IsConnected()
	{
		if (self::$socket !== false)
		{
			if (!self::HasError())
				return true;
		
			self::Disconnect();
		}
		
		return false;
	}
	
	private static function Connect()
	{
		if (!self::IsConnected())
		{		
			$error = 0;
			$error_string = '';
			
			self::$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			
			if (self::$socket === false)
			{
				return false;
			}
			
			$result = socket_connect(self::$socket, self::$host, self::$port);
			
			if ($result === false)
			{
				return false;
			}
			
			socket_set_option(self::$socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0));
		}
		
		return true;
	}
	
	private static function Disconnect()
	{
		if (self::$socket !== false)
			socket_close(self::$socket);
			
		self::$socket = false;
	}
	
	private static function Send($command)	
	{
		if (self::$socket === false || !is_resource(self::$socket))
			return false;
		
		$message = "\xff\xff\xff\xff\x00" . self::$password . "\x20" . $command . "\x00";
		$message_length = 7 + strlen(self::$password) + strlen($command);

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
