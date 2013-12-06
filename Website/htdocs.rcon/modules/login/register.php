<?php
require_once '/usr/share/pear/Mail.php';

if (isset($_REQUEST['activation']))
{
	if (User::Activate($_REQUEST['activation']))
	{
		header("Location: /?module=login&message=Account activated successfully#login");
	}
	else
	{
		header("Location: /?module=login&message=ERROR: Account activation failed#login");
	}
}
else
{
	if (isset($_REQUEST['username']) &&
			isset($_REQUEST['email']) &&
			isset($_REQUEST['password']) &&
			isset($_REQUEST['password-check']))
	{
		$username = trim($_REQUEST['username']);
		$email = trim($_REQUEST['email']);
		$password = trim($_REQUEST['password']);
		$password_check = trim($_REQUEST['password-check']);
		
		if (strlen($username) >= 4)
		{
			if ($password == $password_check)
			{
				$activation = User::Create($username, $email, $password);
				
				$smtp_data = array();
				$smtp_data['host'] = "smtp.gmail.com";
				$smtp_data['username'] = "rcon@deceptivestudios.com";
				$smtp_data['password'] = "rconp455";
				$smtp_data['port'] = 587;
				$smtp_data['auth'] = true;
				
				$smtp = Mail::factory('smtp', $smtp_data);
				
				// sendmail etc
				$email_content = "Hello {$username},\n\nTo activate your account, please follow the link below:\n\nhttp://rcon.deceptivestudios.com/?module=login&action=register&activation={$activation}\n\nOnce your activation is complete you will be able to add a server to control from the 'Servers' menu.\n\nDeceptive {rcon}\n";
				$headers = array('To' => "{$username} <{$email}>",
					'From' => 'Deceptive Rcon <rcon@deceptivestudios.com>',
					'Subject' => 'Deceptive Rcon - User Registration',
					'X-Mailer' => 'PHP/' . phpversion());
				
				$mail = $smtp->send($email, $headers, $email_content);
				
				if ($mail !== false)
				{
					header("Location: /?module=login&message=Please check your email for activation link#login");
				}
				else
				{
					header("Location: /?module=login&rmessage=ERROR: Registration email failed to send#register");
				}
			}
			else
			{
				header("Location: /?module=login&rmessage=ERROR: Password mismatch#register");
			}
		}
		else
		{
			header("Location: /?module=login&rmessage=ERROR: Username must be at least 4 characters in length#register");
		}
	}
	else
	{
		header("Location: /?module=login&rmessage=ERROR: All fields are required#register");
	}
}
?>
