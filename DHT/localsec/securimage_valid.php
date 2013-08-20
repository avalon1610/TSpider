<?php
require_once 'securimage.php';
require_once '../YinXiangMaLibConfig.php';
header('Content-type: text/javascript; charset=gb2312'); 
header("Cache-Control:no-cache");
header("Pragma: no-cache");

$img = new securimage();
$code = $_GET['c'];
$token = $_GET['t'];
$img  = new Securimage();
$valid_result = 'false'; 
if ($img->check($code) == true) { $valid_result = 'true'; }
else { $valid_result = 'false'; }
$valid_result_hash=md5($valid_result.PRIVATE_KEY.$token);
echo "var YXM_result_text ='$valid_result_hash';";
echo "var YXM_ajax_result = '$valid_result';";
?>