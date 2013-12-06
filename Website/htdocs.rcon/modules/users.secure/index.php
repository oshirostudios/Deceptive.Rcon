<?php
require_once('modules/index.php');

$user = $_SESSION['{$adb->prefix}user'];

if ($user->ID() != 1)
	header("Location: /");

require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>User Creation</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');

		echo "test = " . $user->PasswordHash('test') . "<br />\n";
		echo "uniqid = " . uniqid();
		
		?>

	</div>
</section>

<?php
require_once('style/' . $CONFIG['style'] . '/footer.inc.php');
?>
