<?php
$keyword = "人人影视";
$c=array("Name"=>"《迷离档案 第2季》Fringe.S02.连载.双语字幕.RMVB-YYeTs人人影视,离离原上草，一岁一枯荣。
野火烧不尽，春风吹又生。
远芳侵古道，晴翠接荒城。
又送王孙去，萋萋满别情。离离原上草，一岁一枯荣。
野火烧不尽，春风吹又生。
远芳侵古道，晴翠接荒城。
又送王孙去，萋萋满别情。离离原上草，一岁一枯荣。
野火烧不尽，春风吹又生。
远芳侵古道，晴翠接荒城。
又送王孙去，萋萋满别情。离离原上草，一岁一枯荣。
野火烧不尽，春风吹又生。
远芳侵古道，晴翠接荒城。
又送王孙去，萋萋满别情。",

		"Description"=>"- 迷离档案.Fringe.S02E01.Chi_Eng.HDTVrip.720X396-YYeTs人人影视V2.rmvb - 迷离档案.Fringe.S02E05.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E13.Chi_Eng.HDTVrip.720X396-YYeTs人人影视V2.rmvb - 迷离档案.Fringe.S02E14.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E15.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E02.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E10.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E04.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E08.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E06.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E07.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E09.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E03.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - 迷离档案.Fringe.S02E12.Chi_Eng.HDTVrip.720X396-YYeTs人人影视.rmvb - BT发布站：oabt.org - 最新最快最全的影视BT发布站 oaBT.org - 中文字幕下载站 YYeTs.net "
		);
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
	width:600px;
	height:21px;
	overflow:hidden;
	padding:10px 0;
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
			var CloseAction = false;
			if ($(this).find('span.toggle').hasClass('active'))
				CloseAction = true;
			// hide any open menus (Yuneekguy)
			$('.dropdown-slider').slideUp();
			$('span.toggle').removeClass('active');
			
			if (CloseAction)
				return false;
				
			// open selected dropown
			alert($(this).attr('class'));
			if ($(this).attr('class') == 'sF')
				
			var parent = $(this).parent().parent().parent().parent();
			var dd_slider = parent.find('.Su').find('.dropdown-slider');
			dd_slider.slideToggle('fast');
			$(this).find('span.toggle').toggleClass('active');
			
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
		<div class='iP dropdown'>
			<div class='mM'>
				<div class='dF'>
					<span role='button' class='sF' tabindex='0'>
						<?php echo $c['Name'];?>
					</span>
				</div>
				<div class='tA'>
					<div class='fK'>
						<a href="#" class="button ddb"><span class="label">Files</span><span class="toggle"></span></a>
						<a href="#" class="button" title=".icon185 - Thunder"><span class="icon icon185"></span></a>
					</div>
				</div>
			</div>
			<div class='Su'>
				<div class="dropdown-slider">
					  <?php echo $c['Description'];?>	
				</div> <!-- /.dropdown-slider -->
			</div>
		</div>
		
		<div class='iP dropdown'>
			<div class='mM'>
				<div class='dF'>
					<span role='button' class='sF' tabindex='0'>
						<?php echo $c['Name'];?>
					</span>
				</div>
				<div class='tA'>
					<div class='fK'>
						<a href="#" class="button ddb"><span class="label">Files</span><span class="toggle"></span></a>
						<a href="#" class="button" title=".icon185 - Thunder"><span class="icon icon185"></span></a>
					</div>
				</div>
			</div>
			<div class='Su'>
				<div class="dropdown-slider">
					  <!--<a href="#" class="ddm"><span class="label">New</span></a>
					  <a href="#" class="ddm"><span class="label">Save</span></a>
					  -->
					  <?php echo $c['Description'];?>	
				</div> <!-- /.dropdown-slider -->
			</div>
		</div>
		
	</div>
	
</body>
</html>