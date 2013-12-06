<?php
header('Content-type: text/css');

$folder = dirname(__FILE__) . "/maps";
$files = array_diff(scandir($folder), array('.', '..'));

foreach ($files as $key => $file)
{
	$filepath = "{$folder}/{$file}";
	$fileinfo = pathinfo($filepath);
	
	$filename = $fileinfo['filename'];
	$extension = $fileinfo['extension'];
	
	echo ".map-{$filename} { background: url('/images/maps/{$filename}.{$extension}') left bottom no-repeat; }\n";
}
?>