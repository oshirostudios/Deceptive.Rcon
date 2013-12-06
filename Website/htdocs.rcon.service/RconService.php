#!/usr/bin/php -q
<?php
$runmode = array('help' => false, 'initd' => false, 'nodaemon' => false);

foreach ($argv as $k => $arg)
{
	if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)]))
	{
		$runmode[substr($arg, 2)] = true;
	}
}

if ($runmode['help'])
{
	echo "Usage: {$argv[0]} [runmode]\n";
	echo "Available runmodes:\n";
	
	foreach ($runmode as $mode => $val)
	{
		echo " --{$mode}\n";
	}

	die();
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once '/usr/share/pear/System/Daemon.php';
 
// Setup
$options = array(
    'appName' => 'rconservice',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Monitors RCON servers and logs to control the game',
    'authorName' => 'Jonathan Lowden',
    'authorEmail' => 'jonathan@deceptivestudios.com',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M'
);
 
System_Daemon::setOptions($options);
 
if ($runmode['initd'])
{
	if (($initd_location = System_Daemon::writeAutoRun()) === false)
	{
		System_Daemon::info("Unable to write init.d script");
	}
	else
	{
		System_Daemon::info("Successfully written startup script: %s", $initd_location);
	}
	
	die();
}

try
{
	if (!$runmode['nodaemon'])
		System_Daemon::start();
	
	define('ROOT_PATH', dirname(__FILE__).'/');
	define('RCON_PATH', dirname(__FILE__).'/Rcon/');
	
	require_once RCON_PATH . 'RconController.php';
	
	$shutdown = false;
	$controller = new RconController();
	
	while (!System_Daemon::isDying() && !$shutdown)
	{
		// check for new servers
		if (!$controller->Update())
			$shutdown = true;
		
		// wait 10 seconds
		System_Daemon::iterate(10);
	}
	
	System_Daemon::stop();
}
catch (Exception $e)
{
	echo 'Caught exception: ' . $e->getMessage() . "\n";
}
?>
