<?php

class ServerLog
{
	private $url = '';
	private $data;
	
	public function __construct($url)
	{
		$this->url = $url;
		$this->data = array();
	}
	
	public function Download($last = '0')
	{
		$start = time();
		
		$ch = curl_init();
		$timeout = 5;
		
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate,sdch");
		
		$download = curl_exec($ch);
		curl_close($ch);
		
		$rows = explode("\r\n", $download);
		$current_row = 0;
		
		if (count($rows) > 0)
		{
			$this->data = array();
			
			// skip the first line as it it usually incomplete
			for ($count = 1; $count < count($rows); $count++)
			{
				if (strlen($rows[$count]) > 10 && $rows[$count][10] == ' ')
				{
					$timestamp = substr($rows[$count], 0, 10);
					$message = substr($rows[$count], 11);
					
					if ($last == null || $last < $timestamp)
					{
						$this->data[$current_row] = array('timestamp' => $timestamp, 'message' => $message);
						$current_row++;
					}
				}
			}
		}
		
		if ($current_row > 0)
			return true;

		return false;
	}
	
	public function Data()
	{
		return $this->data;
	}
	
	public function Count()
	{
		return count($this->data);
	}
	
	public function Last()
	{
		$lastrow = count($this->data);
		
		if ($lastrow > 0)
		{
			return $this->data[($lastrow - 1)]['timestamp'];
		}
		
		return '';
	}
}

?>
