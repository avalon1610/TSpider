<?php
/*
 * Copyright (c) 2011 by YinXiangMa.com
 * Author: HongXiang Duan, YiQiang Wang, ShuMing Hu
 * Created: 2011-5-5
 * Function: YinXiangMa API php code
 * Version: v3.0
 * Date: 2012-12-01
 * PHP library for YinXiangMa - 印象码 - 验证码广告云服务平台.
 *    - Documentation and latest version
 *          http://www.YinXiangMa.com/
 *    - Get a YinXiangMa API Keys
 *          http://www.YinXiangMa.com/server/signup.php
 */


/********************************************************************************************
 * 以下内容请不要改动。如果改动，可能会有错误发生。
 * "印象码 - 验证码广告云服务平台"。
 ********************************************************************************************
 */
require_once ("YinXiangMaLibConfig.php");
session_start(); 
function YinXiangMa_ValidResult($YinXiangMaToken,$level,$YXM_input_result){	
	if($YXM_input_result==md5("true".PRIVATE_KEY.$YinXiangMaToken)) { $result= "true"; }
	else { $result= "false"; }
	return $result;
}
?>