<?php
// Simple find of the installation folder
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$CONFIG['root_directory'] = dirname(__FILE__);

if(!is_file($CONFIG['root_directory'] . '/config/config.php'))
{
	header("Location: /config/install.php");
}
else
{
	require_once('config/config.php');
	require_once('modules/objects.php');

	if (!$CONFIG['db_disabled']) 
		require_once('include/PearDatabase.php');
	
	if (isset($_REQUEST['PHPSESSID']))
	{
		session_id($_REQUEST['PHPSESSID']);
	  $sid = $_REQUEST['PHPSESSID'];
	}	
	
	session_start();
	
	$_SESSION['action'] = '';
	$_SESSION['module'] = '';
	
	if (isset($_REQUEST['action']))
	{
		$_SESSION['action'] = $_REQUEST['action'];
	}
	else
	{
		$_SESSION['action'] = $CONFIG['default_action'];
	}
	
	if (isset($_REQUEST['module']))
	{
		$_SESSION['module'] = $_REQUEST['module'];
	}
	else
	{
		$_SESSION['module'] = $CONFIG['default_module'];
	}
	
	if (isset($_SESSION['module']) && isset($_SESSION['action']))
	{
		$is_module = false;
		$is_action = false;
		$secure_module = false;
		$hidden_module = false;
		$secure_action = false;
		$hidden_action = false;
		
		$module_dir = @scandir($CONFIG['root_directory']."/modules");
		if (@in_array($_SESSION['module'], $module_dir)) $is_module = true;
		if (@in_array($_SESSION['module'].".secure", $module_dir)) $secure_module = true;
		if (@in_array($_SESSION['module'].".hidden", $module_dir)) $hidden_module = true;

		if ($is_module) $module_name = $_SESSION['module'];
		if ($secure_module) $module_name = $_SESSION['module'].".secure";
		if ($hidden_module) $module_name = $_SESSION['module'].".hidden";
		
		$action_dir = @scandir($CONFIG['root_directory']."/modules/".$module_name);
		
		if (@in_array($_SESSION['action'].".php", $action_dir)) $is_action = true;
		if (!$is_action && @in_array($_SESSION['action'].".secure.php", $action_dir)) $secure_action = true;
		if (!$is_action && @in_array($_SESSION['action'].".hidden.php", $action_dir)) $hidden_action = true;
		
		if (!$is_module && !$secure_module && !$hidden_module) die("Module ".$_SESSION['module']." is missing. Please check the module name.");
		if (!$is_action && !$secure_action && !$hidden_action) die("Action ".$_SESSION['action']." is missing from module ".$_SESSION['module'].". Please check the action name.");

		$_SESSION['hidden_module'] = $hidden_module;
		$_SESSION['hidden_action'] = $hidden_action;
		$_SESSION['secure_module'] = $secure_module;
		$_SESSION['secure_action'] = $secure_action;
		
		if ($hidden_action)
		{
			require_once("modules/".$module_name."/".$_SESSION['action'].".hidden.php");
		}
		else if ($secure_action)
		{
			require_once("modules/".$module_name."/".$_SESSION['action'].".secure.php");
		}
		else
		{
			require_once("modules/".$module_name."/".$_SESSION['action'].".php");
		}
	}
}
?>
