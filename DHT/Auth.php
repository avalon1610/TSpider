<?php
echo <<<EOT
<script type='text/javascript'>
function YXM_valided_true()
{
	document.getElementById('ajax_valid_result').value = '验证码输入正确！';
}
function YXM_valided_false()
{
	document.getElementById('ajax_valid_result').value = '验证码输入错误！';
}
</script>
<script type='text/javascript'>
var YXM_PUBLIC_KEY = 'aa35d82e53f29c3cfb7cf855dff2eede';//这里填写PUBLIC_KEY
var YXM_localsec_url = '/localsec/';//这里设置应急策略路径
function YXM_local_check()
{
	if(typeof(YinXiangMaDataString)!='undefined')return;
	YXM_oldtag = document.getElementById('YXM_script');
	var YXM_local=document.createElement('script');
	YXM_local.setAttribute("type","text/javascript");
	YXM_local.setAttribute("id","YXM_script");
	YXM_local.setAttribute("src",YXM_localsec_url+'yinxiangma.js?pk='+YXM_PUBLIC_KEY+'&v=YinXiangMa_PHPSDK_4.0');
	YXM_oldtag.parentNode.replaceChild(YXM_local,YXM_oldtag);  
}
setTimeout("YXM_local_check()",3000);
document.write("<input type='hidden' id='YXM_here' /><script type='text/javascript' charset='gbk' id='YXM_script' src='http://api.yinxiangma.com/api3/yzm.yinxiangma.php?pk="+YXM_PUBLIC_KEY+"&v=YinXiangMaPHPSDK_4.0'><"+"/script>");
</script> 
EOT;
?>
