<?php
	require_once("YinXiangMaLib.php");
	$memcache = new Memcached();
	$memcache->addServer('127.0.0.1',11211);
	$YinXiangMa_response=YinXiangMa_ValidResult(@$_POST['YinXiangMa_challenge'],@$_POST['YXM_level'][0],@$_POST['YXM_input_result']);
	if($YinXiangMa_response == "true") 
	{ 
		if ($row = $memcache->get(@$_POST['id']))
		{
			echo "magnet:?xt=urn:btih:".$row['Hash'];
			return;
		}
	}

	echo null;
?>
