<?php
date_default_timezone_set("Australia/Melbourne");

// Is the user logged in?
if (!isset($_SESSION['{$adb->prefix}user']))
{
	$logged_in = false;
	$expired_login = false;
	
	// Check for login attempt
	if (isset($_REQUEST['Login']))
	{
		$user_name = $_REQUEST['UserName'];
		$user_password = $_REQUEST['Password'];
		
		$user = new User();
		
		if ($user->Login($user_name, $user_password))
		{
			$_SESSION['{$adb->prefix}user'] = $user;
			$logged_in = true;
		}
		
		if (!$logged_in)
		{
			// Set login failure message
			$login_message = $user->Error();
		}
	}
	
	if (!$logged_in)
	{
		if (strlen($login_message) > 0)
			header("Location: /?module=login&message=ERROR: {$login_message}");
		else
			header("Location: /?module=login");
	}
}
?>
