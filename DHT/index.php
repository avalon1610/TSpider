<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-Type: text/html; charset=utf-8");
$memcache = new Memcached();
$memcache->addServer('127.0.0.1',11211);

//注意文件的编码格式需要保存为为UTF-8格式
require ( "sphinxapi.php" );
require ( "page.php");
require ( "getConfig.php");

$cl = new SphinxClient ();
$cl->SetServer ( '127.0.0.1', 9312);

//以下设置用于返回数组形式的结果
$cl->SetArrayResult ( true );

/*
//ID的过滤
$cl->SetIDRange(3,4);
 
//sql_attr_uint等类型的属性字段，需要使用setFilter过滤，类似SQL的WHERE group_id=2
$cl->setFilter('group_id',array(2));
 
//sql_attr_uint等类型的属性字段，也可以设置过滤范围，类似SQL的WHERE group_id2>=6 AND group_id2<=8
$cl->SetFilterRange('group_id2',6,8);
*/

//取从头开始的前20条数据，0,20类似SQl语句的LIMIT 0,20
//$cl->SetLimits(0,20);

//在做索引时，没有进行 sql_attr_类型 设置的字段，可以作为“搜索字符串”，进行全文搜索

$keyword = '';
$page = 1;

if ($_GET)
{
	if (array_key_exists('q',$_GET))
   		$keyword = $_GET['q'];
	if (array_key_exists('page',$_GET))
		$page = $_GET['page'];
	if (array_key_exists('auth',$_GET))
		$auth = $_GET['auth'];
}


//如果需要搜索指定全文字段的内容，可以使用扩展匹配模式：
//$cl->SetMatchMode(SPH_MATCH_EXTENDED);
//$res=cl->Query( '@title (测试)' , "*");
//$res=cl->Query( '@title (测试) @content ('网络')' , "*");
$search_DB_HOST = get_config("./config.php","DB_host");
$search_DB_USER =  get_config("./config.php","DB_user");
$search_DB_PASSWORD =  get_config("./config.php","DB_passwd");

function queryContent(&$connection,$query_id)
{
	global $search_DB_HOST;
	global $search_DB_USER;
	global $search_DB_PASSWORD;
    if ($connection == NULL)
    {
        $connection = mysql_connect($search_DB_HOST,$search_DB_USER,$search_DB_PASSWORD);
        if (!$connection)
            die('Could not connect:'.mysql_error());
		mysql_query("SET NAMES utf8");
    }

    $result = mysql_query("Select Name,Description,Hash,Updatetime,Rank FROM test.dht where Id = $query_id");

    if($result === FALSE) {
        die(mysql_error()); // TODO: better error handling
    }
	
    return mysql_fetch_array($result);
}

function isAjax()
{
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest')
		return true;
	else
		return false;
}

function display($keyword,$page,$cl)
{
	global $memcache;
	$response = "";
	if ($keyword)
	{
		$cl->SetLimits(($page-1)*10,10);
   		$res = $cl->Query ( $keyword, "*" );    //"*"表示在所有索引里面同时搜索，"索引名称（例如test或者test,test2）"则表示搜索指定的
    	$connection = NULL;
		$response .= "<div id='Content'>";
		if (!array_key_exists('matches',$res))
			$response .= 'No Result.';
		else
		{
			$response .= "<div id='result_count'>找到约".$res['total_found']."条结果（用时 ".$res['time']." 秒）</div>";
    		foreach ($res['matches'] as $ids)
			{
				if (!($row = $memcache->get($ids['id'])))
				{
					if ($memcache->getResultCode() == Memcached::RES_NOTSTORED ||
						$memcache->getResultCode() == Memcached::RES_NOTFOUND)
					{
	        			$row = queryContent($connection,$ids['id']);
						$memcache->set($ids['id'],$row);
					}
					else
						die("memcache error:".$memcache->getResultCode());
				}

				$response .= <<<EOT
				<script>
				$('#search_bar').removeClass('firstshow');
				</script>
				<div class='iP dropdown'>
				<div class='mM'>
					<div class='dF'>
						<span role='button' class='sF' tabindex='0' title='{$row['Name']}'>
							{$row['Name']}
						</span>
					</div>
					<div class='tA'>
						<div class='Pa'>
							热度:<span>{$row['Rank']}</span>
						</div>
						<div class='Gg'>
							更新时间:<span>{$row['Updatetime']}</span>
						</div>
						<div class='fK'>
							<a href="#" class="button ddb"><span class="label">Files</span><span class="toggle"></span></a>
							<a id="{$ids['id']}" class="button magnet" title="磁力链接"><span class="icon icon185"></span></a>
						</div>
					</div>
				</div> <!--mM-->
				<div class='Su'>
					<div class="dropdown-slider">
						{$row['Description']}
					</div> <!-- /.dropdown-slider -->
				</div><!--Su-->
			</div><!--iP-->
EOT;
			}

			// the pages
			$response .= "<div class='page'>";
			$params = array('total_rows'=>$res['total_found'],
							'method'=>'ajax',
							'ajax_func_name'=>'goToPage',
							'now_page'=>$page,
							'list_rows'=>10
					);
			$page = new Core_Lib_Page($params);
			$response .= $page->show(1);
			$response .= "</div>";
		}

		$response .= "</div><!--class Content-->";
	}
	return $response;
}

//handle ajax request, changing page etc.
if (isAjax())
{
	if (array_key_exists('auth',$_GET))
	{
		echo showAuth();	
	}
	else
		echo display($keyword,$page,$cl);
	return;
}

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="mystyle.css" />
<style type="text/css">

body {
	background-color:#e5e5e5;
}

#search_bar {
	width: auto;
   	height:39px;
}

#search_text {
	float:left;
  	height: 29px;
	width: 450px;
   	margin:5px;
}

#search_button {
	float:left;
}

#result_count {
	font-size:13px;
	color:#999;
  	width: auto;
  	margin:5px;
}

#Content {
	width: 600px;
	height: auto;
	margin:10px 5px 10px 5px;
	overflow: hidden;
	float:left;
}

.iP {
	background-color:#f8f8f8;
	margin-bottom:10px;
}

.mM {
	height:80px;
   	background-color:#fff;
}

.dF {
	width:580px;
  	height:21px;
   	overflow:hidden;
 	padding:10px 10px;
	text-overflow:ellipsis;
	display:block;
	white-space:nowrap;
}

.sF {
	cursor:pointer;
   	outline:none;
}

.sF:hover {
	color:#427fed;
}

.Su {
	width: 600px;
   	font-size: 13px;
}

.tA {
	height: 39px;
	width: 600px;

}

.fK {
	height:39px;
	width:145px;
	float:right;
	margin-right:10px;
	overflow:hidden;
}

.Gg {
	height:39px;
	line-height:39px;
	float:left;
	margin-left:10px;
	overflow:hidden;
	font-size:13px;
	color:#999;
}

.Pa {
	height:39px;
	line-height:39px;
	float:left;
	margin-left:10px;
	overflow:hidden;
	font-size:13px;
	color:#999;
}

dd {
	margin-left:20px;
	overflow:hidden;
	text-overflow:ellipsis;
	white-space:nowrap;
}

dd:hover {
	white-space:normal;
}

.highlight { background-color: yellow }
.page {font:12px/16px arial;}
.page span{float:left;margin:0px 3px;}
.page a{float:left;margin:0 3px;border:1px solid #ddd;padding:3px 7px; text-decoration:none;color:#666}
.page a.now_page,#page a:hover{color:#fff;background:#05c}

#AuthFloating {
	position:fixed;
	width:331px;
	height:275px;
	top:50%;
	left:50%;
	margin:-100px 0 0 -150px;
	z-index:1;
	-webkit-backface-visibility:hidden;
	-webkit-transform: translateZ(0);
}

.firstshow {
	position:absolute;
	top:50%;
	left:50%;
	margin:-20px 0 0 -280px;
}
</style>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="jquery.highlight.js"></script>
<script>
	var t;
	$(document).on('click','.dropdown .ddb,.sF',function(){
		var b_click_fbutton = true;
		if ($(this).attr('class') == 'sF')
			b_click_fbutton = false; 

		var CloseAction = false;
		if (b_click_fbutton)
		{
			if ($(this).find('span.toggle').hasClass('active'))
			CloseAction = true;
		}
		else
		{ 
			if ($(this).parent().parent().find('.tA').find('.fK').find('span.toggle').hasClass('active'))
			CloseAction = true;
		}

		// hide any open menus (Yuneekguy)
		$('.dropdown-slider').slideUp();
		$('span.toggle').removeClass('active');

		if (CloseAction)
			return false;
	
		// open selected dropown
		var parent;
		if (b_click_fbutton)
		{
			parent = $(this).parent().parent().parent().parent();
			$(this).find('span.toggle').toggleClass('active');
		}
		else
		{
			parent = $(this).parent().parent().parent();
			parent.find('.mM').find('.tA').find('.fK').find('span.toggle').toggleClass('active');
		}

		//alert(parent.attr('class'));
		var dd_slider = parent.find('.Su').find('.dropdown-slider');
		dd_slider.slideToggle('fast');
		
		if (dd_slider.has('dl').length != 0)
			return false;

		var temp = '';
		var content = dd_slider.text().split('\n');
		dd_slider.text('');
	
		dd_slider.append('<dl>');
		dl = dd_slider.find('dl');
		for (var i in content)
		{	
			if (content[i].indexOf(' - ') == -1)
				temp = '<dt>' + content[i] + '</dt>';
			else
				temp = '<dd>' + content[i] + '</dd>';
			dl.append(temp);
		}
		
		$('dd').highlight($('#search_text').attr('value'));
		return false;
					
	});

	// Close open dropdown slider by clicking elsewhwere on page
	$(document).bind('click', function (e) {
		$('.dropdown-slider').slideUp();
		$('span.toggle').removeClass('active');
		
		if (!$(e.srcElement||e.target).is("#AuthFloating,#AuthFloating *")) {
			if (('#AuthFloating').length>0)
			{
				$('#AuthFloating').remove();
				clearTimeout(t);
			}
		}
	});

	$(document).ready(function(){
		var keyword = $('#search_text').attr('value');
		$('.dF').highlight(keyword);
	});

	$(document).on('click','.magnet',function(e){
		if (e.target.href)
			return;
		if ($('#AuthFloating').length>0)
		{
			$('#AuthFloating').remove();
			clearTimeout(t);
		}
		var id = $(this).attr('id');
		url = "?auth='"+id+"'"+"&t='"+(new Date().getTime())+"'";
		$.get(url,function(data){
			$('#container').before(data);
			$('#confirm').attr('value',id);
		});
	});

	$(document).on('click','#confirm',function(e){
		query_id = $('#confirm').val();
		challenge = $('#YinXiangMa_challenge').val();
		level = $('#YXM_level').val();
		result = $('#YXM_input_result').val();
		query_data = {id:query_id,YinXiangMa_challenge:challenge,YXM_level:level,YXM_input_result:result};
		$.post('Auth.php',query_data,function(result){
			$(('#'+query_id)).attr('href',result);
			$(('#'+query_id)).get(0).click();
		});
		$('#AuthFloating').remove();
		clearTimeout(t);
	});

	function goToPage(page)
	{
		var keyword = $('#search_text').attr('value');
		url = '?page='+page+'&q='+keyword;
		$.get(url,function(data){
			$('#container').text('');
			$('#container').append(data);
			$('body').animate({scrollTop:0}, 'slow'); 
			$('.iP').highlight(keyword);
		});
	}

</script>
<title>搜种子网</title>
</head>
<body>
 	<div id='search_bar' class='firstshow'>
		<form method='get'>
			<input type='text' id='search_text' name='q' value='<?php echo $keyword;?>' />
			<button class="action blue search_button"><span class="label">Search</span></button>
		</form>
	</div>
	<div id="container">
		<?php echo display($keyword,1,$cl); ?>
	</div>
<?php
function showAuth()
{
	$res = <<<EOT
	<div id='AuthFloating'>
		<script type='text/javascript'>
		function YXM_valided_true()
	  	{
			$('#confirm').slideToggle('fast');
		}
		function YXM_valided_false()
	  	{
			$('#confirm').slideUp();
		}
		</script>
		<script type='text/javascript'>
		var YXM_PUBLIC_KEY = 'aa35d82e53f29c3cfb7cf855dff2eede';//这里填写PUBLIC_KEY
		var YXM_localsec_url = 'http://127.0.0.1/localsec/';//这里设置应急策略路径
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
		t = setTimeout("YXM_local_check()",3000);
		$('#YXM').html("<input type='hidden' id='YXM_here' /><script type='text/javascript' charset='gbk' id='YXM_script' src='http://api.yinxiangma.com/api3/yzm.yinxiangma.php?pk="+YXM_PUBLIC_KEY+"&v=YinXiangMaPHPSDK_4.0'><"+"/script>");
		</script>
		<div id='YXM'></div>
		<button id='confirm' class='action blue' style='width:320px;display:none;'><span class='label' style='float:center;'>点击下载</span></button>
	</div>
EOT;
	return $res;
}
?>
	<div style='display:none;'>
		<script language="javascript" type="text/javascript" src="http://js.users.51.la/16206355.js"></script>
		<noscript><a href="http://www.51.la/?16206355" target="_blank"><img alt="&#x6211;&#x8981;&#x5566;&#x514D;&#x8D39;&#x7EDF;&#x8BA1;" src="http://img.users.51.la/16206355.asp" style="border:none" /></a></noscript>
	</div>
</body>
</html>

