<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-Type: text/html; charset=utf-8");

//注意文件的编码格式需要保存为为UTF-8格式
require ( "sphinxapi.php" );

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
$cl->SetLimits(0,20);

//在做索引时，没有进行 sql_attr_类型 设置的字段，可以作为“搜索字符串”，进行全文搜索

$keyword = '';

if ($_GET)
    $keyword = $_GET['q'];

//如果需要搜索指定全文字段的内容，可以使用扩展匹配模式：
//$cl->SetMatchMode(SPH_MATCH_EXTENDED);
//$res=cl->Query( '@title (测试)' , "*");
//$res=cl->Query( '@title (测试) @content ('网络')' , "*");

function queryContent(&$connection,$query_id)
{
    if ($connection == NULL)
    {
        $connection = mysql_connect('192.168.5.1','root','1qaz2wsx');
        if (!$connection)
            die('Could not connect:'.mysql_error());
		mysql_query("SET NAMES utf8");
    }

    $result = mysql_query("Select Name,Description,Hash FROM test.dht where Id = $query_id");

    if($result === FALSE) {
        die(mysql_error()); // TODO: better error handling
    }
	
    return mysql_fetch_array($result);
}

/*
echo '<pre>';
print_r($res['matches']);
print_r($res);
print_r($cl->GetLastError());
print_r($cl->GetLastWarning());
echo '</pre>';
*/
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
	margin:10px 5px 10px 5px;
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
	word-break:keep-all;
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


</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script>
	$(document).ready(function() {
		// Toggle the dropdown menu's
		$(".dropdown .ddb,.sF").click(function () {
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
			
			if (dd_slider.text().indexOf(' - ') == -1)
				return false;
			var temp = '';
			var content = dd_slider.text().split(' - ');
			dd_slider.text('');
		
			for (var i in content)
			{	
				temp = '';
				temp = '<p>' + content[i] + '</p>';
				dd_slider.append(temp);
			}
			
			return false;
		});
							
	// Launch TipTip tooltip
	//$('.tiptip a.button, .tiptip button').tipTip();
						
	});
	
	// Close open dropdown slider by clicking elsewhwere on page
	$(document).bind('click', function (e) {
		if (e.target.id != $('.dropdown').attr('class')) {
			$('.dropdown-slider').slideUp();
			$('span.toggle').removeClass('active');
		}
	});
</script>
 </head>
 <body>
 	<div id='search_bar'>
		<form method='get'>
			<input type='text' id='search_text' name='q' />
			<button class="action blue search_button"><span class="label">Search</span></button>
		</form>
	</div>
	<div id='result_count'>找到约 98,800 条结果 （用时 0.29 秒） </div>
	<div id='Content'>
<?php
if ($keyword)
{
    $res = $cl->Query ( $keyword, "*" );    //"*"表示在所有索引里面同时搜索，"索引名称（例如test或者test,test2）"则表示搜索指定的
    $connection = NULL;
	if (!array_key_exists('matches',$res))
		echo 'No Result.';
	else
	{
    	foreach ($res['matches'] as $ids)
		{
	        $row = queryContent($connection,$ids['id']);

			echo <<<EOT
			<div class='iP dropdown'>
			<div class='mM'>
				<div class='dF'>
					<span role='button' class='sF' tabindex='0' title='{$row['Name']}'>
						{$row['Name']}
					</span>
				</div>
				<div class='tA'>
					<div class='fK'>
						<a href="#" class="button ddb"><span class="label">Files</span><span class="toggle"></span></a>
						<a href="magnet:?xt=urn:btih:{$row['Hash']}" class="button" title="磁力链接"><span class="icon icon185"></span></a>
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
	}
}
?>
	</div><!--class Content-->
</body>
</html>

