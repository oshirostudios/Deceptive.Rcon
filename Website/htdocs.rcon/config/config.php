<?php
$CONFIG['default_module'] = 'overview';
$CONFIG['default_action'] = 'index';

$CONFIG['title'] = 'Deceptive {rcon}';

$CONFIG['style'] = 'default';

$CONFIG['db_table_prefix'] = 'rcon_';

$CONFIG['salt_pattern'] = Array(1, 3, 5, 9, 14, 15, 20, 21, 28, 30);

$CONFIG['db_disabled'] = false;
$CONFIG['db_settings'] = Array(
	'db_type'=>'mysql',
	'db_host'=>'localhost:3306',
	'db_username'=>'rcon_deceptive',
	'db_password'=>'rcondb',
	'db_name'=>'rcon_deceptivestudios'
);

$CONFIG['smtp_settings'] = Array(
	'host'=>'smtp.gmail.com',
	'username'=>'rcon@deceptivestudios.com',
	'password'=>'rconp455',
	'port'=>587,
	'auth'=>true
);
?>
