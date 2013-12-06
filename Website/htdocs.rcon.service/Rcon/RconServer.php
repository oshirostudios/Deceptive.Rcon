#!/usr/bin/php -q
<?php
define('RCON_PATH', dirname(__FILE__).'/');

define('PRIORITY_LAST', 0);
define('PRIORITY_LOWEST', 1);
define('PRIORITY_LOW', 2);
define('PRIORITY_NORMAL', 3);
define('PRIORITY_HIGH', 4);
define('PRIORITY_HIGHEST', 5);

require_once RCON_PATH . 'lib/Server.php';

$server_id = -1;
$forced_run = false;

foreach ($argv as $k => $arg)
{
	if (strstr($arg, "--server=") !== false)
		$server_id = substr($arg, 9);
		
	if ($arg == "--force")
		$forced_run = true;
}

if ($server_id == -1)
	die();

error_reporting(E_ERROR | E_PARSE);
require_once '/usr/share/pear/System/Daemon.php';
 
$options = array(
    'appName' => 'rconserver-' . $server_id,
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Monitors RCON servers and logs to control the game',
    'authorName' => 'Jonathan Lowden',
    'authorEmail' => 'jonathan@deceptivestudios.com',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M'
);
 
System_Daemon::setOptions($options);

RconServer::Initialise($server_id, $forced_run);

$first_run = true;

// start stuff here
while (!System_Daemon::isDying() && RconServer::State())
{
		// check for new plugins
		RconServer::Update();
		// run plugins
		RconServer::Run($first_run);
		
		$first_run = false;
		
		// wait 1 second
		System_Daemon::iterate(1);
}

die();

?>
