<?php
defined('RCON_PATH') or die('No direct script access.');

require_once RCON_PATH . 'config/config.php';
require_once RCON_PATH . 'lib/Database.php';

class RconController
{
	private $processes;

	public function __construct()
	{
		$this->processes = array();
	}
	
	public function Update()
	{
		global $adb;
		
		// check for dead pids
		foreach ($this->processes as $server_id => $pid)
		{
			if (!$this->IsRunning($pid))
			{
				System_Daemon::log(System_Daemon::LOG_INFO, "Server '{$server_id}' not running");
				unset($this->processes[$server_id]);
			}
		}
		
		$query = "SELECT server_id FROM {$adb->prefix}servers WHERE LENGTH(IFNULL(server_ip, '')) > 0 AND
																																LENGTH(IFNULL(server_port, '')) > 0 AND
																																LENGTH(IFNULL(server_log_url, '')) > 0 AND
																																LENGTH(IFNULL(server_rcon_password, '')) > 0 AND
																																server_monitor = 1";
		$result = $adb->query($query, false);

		if (!empty($result))
		{
			$total_servers = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_servers; $count++)
			{
				$server_id = $adb->query_result($result, $count, 'server_id');
				
				if (!array_key_exists($server_id, $this->processes))
					$this->Start($server_id);
			}
		}
		
		return true;
	}
	
	private function SetPID($server_id, $pid)
	{
		global $adb;
		
		$query = "UPDATE {$adb->prefix}servers SET server_pid = {$pid} WHERE server_id = {$server_id}";
		$adb->query($query, true);
	}
	
	private function Start($server_id)
	{
		$cmd = RCON_PATH . "RconServer.php --server={$server_id} &> /dev/null & echo $!";
		exec($cmd, $output);
		
		if (count($output) == 1 && $output[0] > 0)
			$this->processes[$server_id] = $output[0];
			
		$this->SetPID($server_id, $output[0]);

		System_Daemon::log(System_Daemon::LOG_INFO, "Started server '{$server_id}' with pid {$output[0]}");
	}
	
	private function IsRunning($pid)
	{
		global $adb;
		
		$cmd = "ps {$pid}";
		exec($cmd, $output, $result);
		
		if (count($output) >= 2)
		{
			// check last run was less that 2 minutes ago
			$query = "SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) AS time_now, UNIX_TIMESTAMP(IFNULL(server_last_run, CURRENT_TIMESTAMP)) AS time_update FROM {$adb->prefix}servers WHERE server_pid = {$pid}";
			$result = $adb->query($query, false);
	
			if (!empty($result) && $adb->num_rows($result) == 1)
			{
				$time_now = $adb->query_result($result, 0, 'time_now');
				$time_update = $adb->query_result($result, 0, 'time_update');
				
				if (($time_now - $time_update) < 120)
					return true;
				
				$this->Kill($pid);
			}
		}

		return false;
	}
	
	private function Kill($pid)
	{
		$cmd = "kill 9 {$pid}";
		exec($cmd, $output, $result);
	}
}

?>
