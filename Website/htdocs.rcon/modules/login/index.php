<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>
		
<section id="login" class="login selected">
<div class="content">

<!-- Login System -->
<h2>Login</h2>

<!-- Navigation -->
<nav class="main">
<ul>
<li><a href="#login" class="selected">Login</a></li>
<li><a href="#register">Register</a></li>
</ul>
</nav>

<div class="view">
<div class="pages">

<div class="page_center">

<form method="post" action="/?module=overview">
<table>
<tr><td>Username:</td><td>Password:</td></tr>
<tr><td><input type="user" name="UserName" size="30"></td><td><input type="password" name="Password" size="30"></td></tr>
<tr>
<td colspan="2">
<input style="float: right; margin-top: 5px; margin-bottom: 5px;" type="submit" name="Login" value="Login" />
<span style="float: right; margin-top: 10px; margin-right: 10px;"><?php
if (isset($_REQUEST['message']))
{
	if (strpos($_REQUEST['message'], 'ERROR:') !== false)
		echo "<font color=\"red\">{$_REQUEST['message']}</font>\n";
	else
		echo "<font color=\"green\">{$_REQUEST['message']}</font>\n";
}
?></span>
</td>
</tr>
<tr>
<td colspan="2">
<br /><br />
</td>
</tr>
</table>
</form>

</div>

</div>
</div>

</div>
</section>
	
<section id="register" class="register">
<div class="content">

<!-- Login System -->
<h2>Register</h2>

<!-- Navigation -->
<nav class="main">
<ul>
<li><a href="#login">Login</a></li>
<li><a href="#register" class="selected">Register</a></li>
</ul>
</nav>

<div class="view">
<div class="pages">

<div class="page_center">

<form method="post" action="/?module=login&action=register">
<table>
<tr><td>Username:</td><td>Password:</td></tr>
<tr><td><input type="user" name="username" size="30"></td><td><input type="password" name="password" size="30"></td></tr>
<tr><td>Email:</td><td>Confirm Password:</td></tr>
<tr><td><input type="email" name="email" size="30"></td><td><input type="password" name="password-check" size="30"></td></tr>
<tr>
<td colspan="2">
<input style="float: right; margin-top: 5px; margin-bottom: 5px;" type="submit" name="Login" value="Login" />
<span style="float: right; margin-top: 10px; margin-right: 10px;"><?php
if (isset($_REQUEST['message']))
{
	if (strpos($_REQUEST['message'], 'ERROR:') !== false)
		echo "<font color=\"red\">{$_REQUEST['message']}</font>\n";
	else
		echo "<font color=\"green\">{$_REQUEST['message']}</font>\n";
}
?></span>
</td>
</tr>
<tr>
<td colspan="2">
<br /><br />
</td>
</tr>
</table>
</form>

</div>

</div>
</div>

</div>
</section>

<!-- JavaScript -->
<script src="/include/libraries/jquery.scrollTo-1.4.2.js"></script>
<script src="/include/libraries/jquery.easing.min.js"></script>
<script src="/include/libraries/jquery.fancybox-1.3.4.pack.js"></script>
<script src="/include/script.js"></script>

<!--[if lt IE 7 ]>
<script src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js"></script>
<script>window.attachEvent("onload",function(){CFInstall.check({mode:"overlay"})})</script>
<![endif]-->

<?php
require_once('style/' . $CONFIG['style'] . '/footer.inc.php');
?>
