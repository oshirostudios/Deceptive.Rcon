<?php

class Cleanup_Plugin implements Plugin
{
	private $config;

	public function Load($config)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - loading 'Cleanup_Plugin'");
		
		$this->config = $config;
	}
	
	public function Run($first_run)
	{
		System_Daemon::log(System_Daemon::LOG_INFO, " - running 'Cleanup_Plugin'");
		
		global $adb;
		
		// remove all logs except the current message timestamp
		$query = "DELETE FROM {$adb->prefix}server_log WHERE server_id = {$this->config['id']} AND NOT (server_log_command = 'T' OR server_log_command = 'I') AND NOT server_log_data LIKE 'CONSOLE;0;CONSOLE%'";
		$adb->query($query, true);
	}
	
	public function State()
	{
		return true;
	}
	
	public function Priority()
	{
		return PRIORITY_LAST;
	}
}

?>
