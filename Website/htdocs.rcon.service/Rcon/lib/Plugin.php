<?php

interface Plugin
{
	public function Load($config);
	public function Run($first_run);
	public function State();
	public function Priority();
}

?>
