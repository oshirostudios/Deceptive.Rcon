<?php
function StartAppPage()
{
	echo '<div class="page">' . "\n";
}

function ShowApplication($section, $app_title, $path, $last = false)
{
	if (!$last)
		echo '<article>' . "\n";
	else
		echo '<article class="last">' . "\n";
		
	echo '<a href="#' . $app_title . '" class="top">' . "\n";
	echo '<img src="/modules/'. $section . '/images/' . $app_title . '/banner.png" alt="">' . "\n";
	echo '</a>' . "\n";
	
	echo '<h3><a href="#' . $app_title . '">' . ucwords(str_replace('-', ' ', $app_title)) . '</a></h3>' . "\n";
	
	require($path);
	
	echo '</article>' . "\n";
}

function ShowWebsite($url, $site_title, $path, $last = false)
{
	if (!$last)
		echo '<article>' . "\n";
	else
		echo '<article class="last">' . "\n";
		
	echo '<a href="' . $url . '" target="_blank" class="top">' . "\n";
	echo '<img src="/modules/websites/images/' . $site_title . '/banner.png" alt="">' . "\n";
	echo '</a>' . "\n";
	
	echo '<h3><a href="' . $url . '" target="_blank">' . ucwords(str_replace('-', ' ', $site_title)) . '</a></h3>' . "\n";
	
	require($path);
	
	echo '</article>' . "\n";
}

function EndAppPage()
{
	echo '</div>' . "\n";
}
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<title><?php echo $CONFIG['title']; ?></title>
	
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">

	<link rel="shortcut icon" href="favicon.png">
	<link rel="stylesheet" href="/style/default/style.css">
	<link rel="stylesheet" href="/images/maps.css.php">

	<script src="/include/libraries/modernizr-2.0.6.min.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="/js/libs/jquery-1.6.2.min.js"><\/script>')</script>
	
</head>

<body class="black">
<div id="background"></div>

<div id="container">
