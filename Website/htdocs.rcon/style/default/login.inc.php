<?php
		
echo '<section id="login" class="login selected">';
echo '<div class="content">';

echo '<!-- Login System -->';
echo '<h2>Login</h2>';

echo '<!-- Navigation -->';
echo '<nav class="main">';
echo '<ul>';
echo '<li><a id="login-1" href="#login" class="selected">Login</a></li>' . "\n";
echo '<li><a id="regis-1" href="#register">Register</a></li>' . "\n";
echo '</ul>';
echo '</nav>';

echo '<div class="view">';
echo '<div class="pages">';

echo '<div class="page_center">';

echo '<form method="post">';
echo '<table><tr><td>Username:</td><td>Password:</td></tr>';
echo '<tr><td><input type="user" name="UserName" size="30"></td><td><input type="password" name="Password" size="30"></td></tr>';
echo '<tr><td>';

if (isset($login_message))
{
	echo "<font color=\"red\">ERROR: $login_message</font>\n";
}

echo '</td><td><input style="float: right" type="submit" name="Login" value="    Login    " /></td></tr></table>';
echo '</form>';

echo '</div>';

echo '</div>';
echo '</div>';

echo '</div>';
echo '</section>';

echo '<section id="register" class="register">';
echo '<div class="content">';

echo '<!-- Login System -->';
echo '<h2>Register</h2>';

echo '<!-- Navigation -->';
echo '<nav class="main">';
echo '<ul>';
echo '<li><a id="login-2" href="#login">Login</a></li>' . "\n";
echo '<li><a id="regis-2" href="#register" class="selected">Register</a></li>' . "\n";
echo '</ul>';
echo '</nav>';

echo '<div class="view">';
echo '<div class="pages">';

echo '<div class="page_center">';
echo 'Test';
echo '</div>';

echo '</div>';
echo '</div>';

echo '</div>';
echo '</section>';

echo '<!-- JavaScript -->';
echo '<script src="/include/libraries/jquery.scrollTo-1.4.2.js"></script>';
echo '<script src="/include/libraries/jquery.easing.min.js"></script>';
echo '<script src="/include/libraries/jquery.fancybox-1.3.4.pack.js"></script>';
echo '<script src="/include/script.js"></script>';

echo '<!--[if lt IE 7 ]>';
echo '<script src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js"></script>';
echo '<script>window.attachEvent("onload",function(){CFInstall.check({mode:"overlay"})})</script>';
echo '<![endif]-->';
?>
