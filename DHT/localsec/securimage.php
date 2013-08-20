<?php

// error_reporting(E_ALL); ini_set('display_errors', 1); // uncomment this line for debugging

/**
 * Project:     Securimage: A PHP class for creating and managing form CAPTCHA images<br />
 * File:        securimage.php<br />
 *
 * Copyright (c) 2011, Drew Phillips
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.<br /><br />
 *
 * If you found this script useful, please take a quick moment to rate it.<br />
 * http://www.hotscripts.com/rate/49400.html  Thanks.
 *
 * @link http://www.phpcaptcha.org Securimage PHP CAPTCHA
 * @link http://www.phpcaptcha.org/latest.zip Download Latest Version
 * @link http://www.phpcaptcha.org/Securimage_Docs/ Online Documentation
 * @copyright 2011 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
 * @version 3.0 (October 2011)
 * @package Securimage
 *
 */

/**
 ChangeLog
 
 3.0
 - Rewrite class using PHP5 OOP
 - Remove support for GD fonts, require FreeType
 - Remove support for multi-color codes
 - Add option to make codes case-sensitive
 - Add namespaces to support multiple captchas on a single page or page specific captchas
 - Add option to show simple math problems instead of codes
 - Remove support for mp3 files due to vulnerability in decoding mp3 audio files
 - Create new flash file to stream wav files instead of mp3
 - Changed to BSD license

 2.0.2
 - Fix pathing to make integration into libraries easier (Nathan Phillip Brink ohnobinki@ohnopublishing.net)

 2.0.1
 - Add support for browsers with cookies disabled (requires php5, sqlite) maps users to md5 hashed ip addresses and md5 hashed codes for security
 - Add fallback to gd fonts if ttf support is not enabled or font file not found (Mike Challis http://www.642weather.com/weather/scripts.php)
 - Check for previous definition of image type constants (Mike Challis)
 - Fix mime type settings for audio output
 - Fixed color allocation issues with multiple colors and background images, consolidate allocation to one function
 - Ability to let codes expire after a given length of time
 - Allow HTML color codes to be passed to Securimage_Color (suggested by Mike Challis)

 2.0.0
 - Add mathematical distortion to characters (using code from HKCaptcha)
 - Improved session support
 - Added Securimage_Color class for easier color definitions
 - Add distortion to audio output to prevent binary comparison attack (proposed by Sven "SavageTiger" Hagemann [insecurity.nl])
 - Flash button to stream mp3 audio (Douglas Walsh www.douglaswalsh.net)
 - Audio output is mp3 format by default
 - Change font to AlteHaasGrotesk by yann le coroller
 - Some code cleanup 

 1.0.4 (unreleased)
 - Ability to output audible codes in mp3 format to stream from flash

 1.0.3.1
 - Error reading from wordlist in some cases caused words to be cut off 1 letter short

 1.0.3
 - Removed shadow_text from code which could cause an undefined property error due to removal from previous version

 1.0.2
 - Audible CAPTCHA Code wav files
 - Create codes from a word list instead of random strings

 1.0
 - Added the ability to use a selected character set, rather than a-z0-9 only.
 - Added the multi-color text option to use different colors for each letter.
 - Switched to automatic session handling instead of using files for code storage
 - Added GD Font support if ttf support is not available.  Can use internal GD fonts or load new ones.
 - Added the ability to set line thickness
 - Added option for drawing arced lines over letters
 - Added ability to choose image type for output

 */


/**
 * Securimage CAPTCHA Class.
 *
 * @version    3.0
 * @package    Securimage
 * @subpackage classes
 * @author     Drew Phillips <drew@drew-phillips.com>
 *
 */
class Securimage
{
	// All of the public variables below are securimage options
	// They can be passed as an array to the Securimage constructor, set below,
	// or set from securimage_show.php and securimage_play.php
	
    /**
     * Renders captcha as a JPEG image
     * @var int
     */
    const SI_IMAGE_JPEG = 1;
    /**
     * Renders captcha as a PNG image (default)
     * @var int
     */
    const SI_IMAGE_PNG  = 2;
    /**
     * Renders captcha as a GIF image
     * @var int
     */
    const SI_IMAGE_GIF  = 3;
    
    /**
     * Create a normal alphanumeric captcha
     * @var int
     */
    const SI_CAPTCHA_STRING     = 0;
    /**
     * Create a captcha consisting of a simple math problem
     * @var int
     */
    const SI_CAPTCHA_MATHEMATIC = 1;
    /**
     * Create a normal alphanumeric captcha
     * @var int
     */
    const SI_CAPTCHA_CHINESE     = 2;
    /**
     * The width of the captcha image
     * @var int
     */
    public $image_width = 215;
    /**
     * The height of the captcha image
     * @var int
     */
    public $image_height = 80;
    /**
     * The type of the image, default = png
     * @var int
     */
    public $image_type   = self::SI_IMAGE_PNG;

    /**
     * The background color of the captcha
     * @var Securimage_Color
     */
    public $image_bg_color = '#ffffff';
    /**
     * The color of the captcha text
     * @var Securimage_Color
     */
    public $text_color     = '#707070';
    /**
     * The color of the lines over the captcha
     * @var Securimage_Color
     */
    public $line_color     = '#707070';
    /**
     * The color of the noise that is drawn
     * @var Securimage_Color 
     */
    public $noise_color    = '#707070';
    
    /**
     * How transparent to make the text 0 = completely opaque, 100 = invisible
     * @var int
     */
    public $text_transparency_percentage = 50;
    /**
     * Whether or not to draw the text transparently, true = use transparency, false = no transparency
     * @var bool
     */
    public $use_transparent_text         = false;
    
    /**
     * The length of the captcha code
     * @var int
     */
    public $code_length    = 6;
    /**
     * Whether the captcha should be case sensitive (not recommended, use only for maximum protection)
     * @var bool
     */
    public $case_sensitive = false;
    /**
     * The character set to use for generating the captcha code
     * @var string
     */
    public $charset        = 'ABCDEFGHKLMNPRSTUVWYZabcdefghklmnprstuvwyz23456789';
	public $charset_zh ='的一是在了不和有大这主中人上为们地个用工时要动国产以我到他会作来分生对于学下级就年阶义发成部民可出能方进同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批如应形想制心样干都向变关点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵闭孩释巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫康遵牧遭幅园腔订香肉弟屋敏恢忘衣孙龄岭骗休借丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩';
    
	public $switch_type        =1;
	
	
	
	/**
     * How long in seconds a captcha remains valid, after this time it will not be accepted
     * @var unknown_type
     */
    public $expiry_time    = 900;
    
    /**
     * The session name securimage should use, only set this if your application uses a custom session name
     * It is recommended to set this value below so it is used by all securimage scripts
     * @var string
     */
    public $session_name   = null;
    
    /**
     * true to use the wordlist file, false to generate random captcha codes
     * @var bool
     */
    public $use_wordlist   = false;

    /**
     * The level of distortion, 0.75 = normal, 1.0 = very high distortion
     * @var double
     */
    public $perturbation = 0.75;
    /**
     * How many lines to draw over the captcha code to increase security
     * @var int
     */
    public $num_lines    = 8;
    /**
     * The level of noise (random dots) to place on the image, 0-10
     * @var int
     */
    public $noise_level  = 0;
    
    /**
     * The signature text to draw on the bottom corner of the image
     * @var string
     */
    public $image_signature = '';
    /**
     * The color of the signature text
     * @var Securimage_Color
     */
    public $signature_color = '#707070';
    /**
     * The path to the ttf font file to use for the signature text, defaults to $ttf_file (AHGBold.ttf)
     * @var string
     */
    public $signature_font;
    
    /**
     * Use an SQLite database to store data (for users that do not support cookies)
     * @var bool
     */
    public $use_sqlite_db = false;
    
    /**
     * The type of captcha to create, either alphanumeric, or a math problem<br />
     * Securimage::SI_CAPTCHA_STRING or Securimage::SI_CAPTCHA_MATHEMATIC
     * @var int
     */
    public $captcha_type  = self::SI_CAPTCHA_STRING;
    
    /**
     * The captcha namespace, use this if you have multiple forms on a single page, blank if you do not use multiple forms on one page
     * @var string
     * <code>
     * <?php
     * // in securimage_show.php (create one show script for each form)
     * $img->namespace = 'contact_form';
     * 
     * // in form validator
     * $img->namespace = 'contact_form';
     * if ($img->check($code) == true) {
     *     echo "Valid!";
     *  }
     * </code>
     */
    public $namespace;
    
    /**
     * The font file to use to draw the captcha code, leave blank for default font AHGBold.ttf
     * @var string
     */
    public $ttf_file;
    /**
     * The path to the wordlist file to use, leave blank for default words/words.txt
     * @var string
     */
    public $wordlist_file;
    /**
     * The directory to scan for background images, if set a random background will be chosen from this folder
     * @var string
     */
    public $background_directory;
    /**
     * The path to the SQLite database file to use, if $use_sqlite_database = true, should be chmod 666
     * @var string
     */
    public $sqlite_database;
    /**
     * The path to the securimage audio directory, can be set in securimage_play.php
     * @var string
     * <code>
     * $img->audio_path = '/home/yoursite/public_html/securimage/audio/';
     * </code>
     */
    public $audio_path;

    
    
    protected $im;
    protected $tmpimg;
    protected $bgimg;
    protected $iscale = 5;
    
    protected $securimage_path = null;
    
    protected $code;
    protected $code_display;
    
    protected $captcha_code;
    protected $sqlite_handle;
    
    protected $gdbgcolor;
    protected $gdtextcolor;
    protected $gdlinecolor;
    protected $gdsignaturecolor;
    
    /**
     * Create a new securimage object, pass options to set in the constructor.<br />
     * This can be used to display a captcha, play an audible captcha, or validate an entry
     * @param array $options
     * <code>
     * $options = array(
     *     'text_color' => new Securimage_Color('#013020'),
     *     'code_length' => 5,
     *     'num_lines' => 5,
     *     'noise_level' => 3,
     *     'font_file' => Securimage::getPath() . '/custom.ttf'
     * );
     * 
     * $img = new Securimage($options);
     * </code>
     */
    public function __construct($options = array())
    {
        $this->securimage_path = dirname(__FILE__);
        
        if (is_array($options) && sizeof($options) > 0) {
            foreach($options as $prop => $val) {
                $this->$prop = $val;
            }
        }

        $this->image_bg_color  = $this->initColor($this->image_bg_color,  '#ffffff');
        $this->text_color      = $this->initColor($this->text_color,      '#616161');
        $this->line_color      = $this->initColor($this->line_color,      '#616161');
        $this->noise_color     = $this->initColor($this->noise_color,     '#616161');
        $this->signature_color = $this->initColor($this->signature_color, '#616161');

        if ($this->ttf_file == null) {
            $this->ttf_file = $this->securimage_path . '/AHGBold.ttf';
        }
        
        $this->signature_font = $this->ttf_file;
        
        if ($this->wordlist_file == null) {
            $this->wordlist_file = $this->securimage_path . '/words/words.txt';
        }
        
        if ($this->sqlite_database == null) {
            $this->sqlite_database = $this->securimage_path . '/database/securimage.sqlite';
        }
        
        if ($this->audio_path == null) {
            $this->audio_path = $this->securimage_path . '/audio/';
        }
        
        if ($this->code_length == null || $this->code_length < 1) {
            $this->code_length = 6;
        }
        
        if ($this->perturbation == null || !is_numeric($this->perturbation)) {
            $this->perturbation = 0.75;
        }
        
        if ($this->namespace == null || !is_string($this->namespace)) {
            $this->namespace = 'default';
        }

        // Initialize session or attach to existing
        if ( session_id() == '' ) { // no session has been started yet, which is needed for validation
            if ($this->session_name != null && trim($this->session_name) != '') {
                session_name(trim($this->session_name)); // set session name if provided
            }
            session_start();
        }
    
	}
        
    /**
     * Return the absolute path to the Securimage directory
     * @return string The path to the securimage base directory
     */
    public static function getPath()
    {
        return dirname(__FILE__);
    }
    
	/**
     * Return the captcha code 
     */
    public function getCodeResult()
    {
        return $this->code;
    }
	
    /**
     * Used to serve a captcha image to the browser
     * @param string $background_image The path to the background image to use
     * <code> 
     * $img = new Securimage();
     * $img->code_length = 6;
     * $img->num_lines   = 5;
     * $img->noise_level = 5;
     * 
     * $img->show(); // sends the image to browser
     * exit;
     * </code>
     */
    public function show($background_image = '')
    {
        if($background_image != '' && is_readable($background_image)) {
            $this->bgimg = $background_image;
        }

        $this->doImage();
    }
    
    /**
     * Check a submitted code against the stored value
     * @param string $code  The captcha code to check
     * <code>
     * $code = $_POST['code'];
     * $img  = new Securimage();
     * if ($img->check($code) == true) {
     *     $captcha_valid = true;
     * } else {
     *     $captcha_valid = false;
     * }
     * </code>
     */
    public function check($code)
    {
        $this->code_entered = $code;
        $this->validate();
        return $this->correct_code;
    }
    
    /**
     * Output a wav file of the captcha code to the browser
     * 
     * <code>
     * $img = new Securimage();
     * $img->outputAudioFile(); // outputs a wav file to the browser
     * exit;
     * </code>
     */
    public function outputAudioFile()
    {
        $ext = 'wav'; // force wav - mp3 is insecure
        
        header("Content-Disposition: attachment; filename=\"securimage_audio.{$ext}\"");
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: Sun, 1 Jan 2000 12:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Content-type: audio/x-wav');
        
        $audio = $this->getAudibleCode($ext);

        header('Content-Length: ' . strlen($audio));

        echo $audio;
        exit;
    }
    
    /**
     * The main image drawing routing, responsible for constructing the entire image and serving it
     */
    protected function doImage()
    {
        if( ($this->use_transparent_text == true || $this->bgimg != '') && function_exists('imagecreatetruecolor')) {
            $imagecreate = 'imagecreatetruecolor';
        } else {
            $imagecreate = 'imagecreate';
        }
        
        $this->im     = $imagecreate($this->image_width, $this->image_height);
        $this->tmpimg = $imagecreate($this->image_width * $this->iscale, $this->image_height * $this->iscale);
        
        $this->allocateColors();
        imagepalettecopy($this->tmpimg, $this->im);

        $this->setBackground();
		$this->createCode();

        if ($this->noise_level > 0) {
            $this->drawNoise();
        }
        
        $this->drawWord();
        
        if ($this->perturbation > 0 && is_readable($this->ttf_file)) {
            $this->distortedCopy();
        }

        if ($this->num_lines > 0) {
            $this->drawLines();
        }

        if (trim($this->image_signature) != '') {
            $this->addSignature();
        }

        $this->output();
    }
    
    /**
     * Allocate the colors to be used for the image
     */
    protected function allocateColors()
    {
        // allocate bg color first for imagecreate
        $this->gdbgcolor = imagecolorallocate($this->im,
                                              $this->image_bg_color->r,
                                              $this->image_bg_color->g,
                                              $this->image_bg_color->b);
        
        $alpha = intval($this->text_transparency_percentage / 100 * 127);
        
        if ($this->use_transparent_text == true) {
            $this->gdtextcolor = imagecolorallocatealpha($this->im,
                                                         $this->text_color->r,
                                                         $this->text_color->g,
                                                         $this->text_color->b,
                                                         $alpha);
            $this->gdlinecolor = imagecolorallocatealpha($this->im,
                                                         $this->line_color->r,
                                                         $this->line_color->g,
                                                         $this->line_color->b,
                                                         $alpha);
            $this->gdnoisecolor = imagecolorallocatealpha($this->im,
                                                          $this->noise_color->r,
                                                          $this->noise_color->g,
                                                          $this->noise_color->b,
                                                          $alpha);
        } else {
            $this->gdtextcolor = imagecolorallocate($this->im,
                                                    $this->text_color->r,
                                                    $this->text_color->g,
                                                    $this->text_color->b);
            $this->gdlinecolor = imagecolorallocate($this->im,
                                                    $this->line_color->r,
                                                    $this->line_color->g,
                                                    $this->line_color->b);
            $this->gdnoisecolor = imagecolorallocate($this->im,
                                                          $this->noise_color->r,
                                                          $this->noise_color->g,
                                                          $this->noise_color->b);
        }
    
        $this->gdsignaturecolor = imagecolorallocate($this->im,
                                                     $this->signature_color->r,
                                                     $this->signature_color->g,
                                                     $this->signature_color->b);

    }
    
    /**
     * The the background color, or background image to be used
     */
    protected function setBackground()
    {
        // set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
        imagefilledrectangle($this->im, 0, 0,
                             $this->image_width, $this->image_height,
                             $this->gdbgcolor);
        imagefilledrectangle($this->tmpimg, 0, 0,
                             $this->image_width * $this->iscale, $this->image_height * $this->iscale,
                             $this->gdbgcolor);
    
        if ($this->bgimg == '') {
            if ($this->background_directory != null && 
                is_dir($this->background_directory) &&
                is_readable($this->background_directory))
            {
                $img = $this->getBackgroundFromDirectory();
                if ($img != false) {
                    $this->bgimg = $img;
                }
            }
        }
        
        if ($this->bgimg == '') {
            return;
        }

        $dat = @getimagesize($this->bgimg);
        if($dat == false) { 
            return;
        }

        switch($dat[2]) {
            case 1:  $newim = @imagecreatefromgif($this->bgimg); break;
            case 2:  $newim = @imagecreatefromjpeg($this->bgimg); break;
            case 3:  $newim = @imagecreatefrompng($this->bgimg); break;
            default: return;
        }

        if(!$newim) return;

        imagecopyresized($this->im, $newim, 0, 0, 0, 0,
                         $this->image_width, $this->image_height,
                         imagesx($newim), imagesy($newim));
    }
    
    /**
     * Scan the directory for a background image to use
     */
    protected function getBackgroundFromDirectory()
    {
        $images = array();

        if ( ($dh = opendir($this->background_directory)) !== false) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/(jpg|gif|png)$/i', $file)) $images[] = $file;
            }

            closedir($dh);

            if (sizeof($images) > 0) {
                return rtrim($this->background_directory, '/') . '/' . $images[rand(0, sizeof($images)-1)];
            }
        }

        return false;
    }
    
    /**
     * Generates the code or math problem and saves the value to the session
     */
    public function createCode()
    {
        $this->code = false;

        switch($this->captcha_type) {
            case self::SI_CAPTCHA_MATHEMATIC:
            {
                $signs = array('+', '-', 'x');
                $left  = rand(6, 10);
                $right = rand(1, 5);
                $sign  = $signs[rand(0, 2)];
                
                switch($sign) {
                    case 'x': $c = $left * $right; break;
                    case '-': $c = $left - $right; break;
                    default:  $c = $left + $right; break;
                }
                
                $this->code         = $c;
                $this->code_display = "$left $sign $right=";
                break;
            }
            case self::SI_CAPTCHA_CHINESE:
            {
                $this->code = iconv("GB2312","UTF-8",$this->generateCode_zh($this->code_length));                
                $this->code_display = $this->code;
                $this->code         = $this->code;
            }
            default:
            {
                if ($this->use_wordlist && is_readable($this->wordlist_file)) {
                    $this->code = $this->readCodeFromFile();
                }

                if ($this->code == false) {
                    $this->code = iconv("GB2312","UTF-8",$this->generateCode($this->code_length));
                }
                
                $this->code_display = $this->code;
                $this->code         = ($this->case_sensitive) ? $this->code : strtolower($this->code);
            } // default
        }
        
        $this->saveData();
    }
    
    /**
     * Draws the captcha code on the image
     */
    protected function drawWord()
    {
        $width2  = $this->image_width * $this->iscale;
        $height2 = $this->image_height * $this->iscale;
        
		switch($this->switch_type)
		{
			case 1:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 2.5 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 1.3 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			case 2:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 2.5 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 1.3 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			case 3:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 2.5 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 2.7 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			case 4:	
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 2.5 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 2.7 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			case 5:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 3.2 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 2.2 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			case 6:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 3.2 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 2.2 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
			default:
				if (!is_readable($this->ttf_file)) {
					imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
				} else {
					if ($this->perturbation > 0) {
						$font_size = $height2 * .17;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($width2 / 2.5 - $tx / 2 - $bb[0]);
						$y  = round($height2 / 1.3 - $ty / 2 - $bb[1]);

						imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					} else {
						$font_size = $this->image_height * .4;
						$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
						$tx = $bb[4] - $bb[0];
						$ty = $bb[5] - $bb[1];
						$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
						$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);

						imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
					}
				}
				break;
		}
        
        // DEBUG
        //$this->im = $this->tmpimg;
        //$this->output();
        
    }
    
    /**
     * Copies the captcha image to the final image with distortion applied
     */
    protected function distortedCopy()
    {
        $numpoles = 3; // distortion factor
        // make array of poles AKA attractor points
        for ($i = 0; $i < $numpoles; ++ $i) {
            $px[$i]  = rand($this->image_width  * 0.2, $this->image_width  * 0.8);
            $py[$i]  = rand($this->image_height * 0.2, $this->image_height * 0.8);
            $rad[$i] = rand($this->image_height * 0.2, $this->image_height * 0.8);
            $tmp     = ((- $this->frand()) * 0.15) - .15;
            $amp[$i] = $this->perturbation * $tmp;
        }
        
        $bgCol = imagecolorat($this->tmpimg, 0, 0);
        $width2 = $this->iscale * $this->image_width;
        $height2 = $this->iscale * $this->image_height;
        imagepalettecopy($this->im, $this->tmpimg); // copy palette to final image so text colors come across
        // loop over $img pixels, take pixels from $tmpimg with distortion field
        for ($ix = 0; $ix < $this->image_width; ++ $ix) {
            for ($iy = 0; $iy < $this->image_height; ++ $iy) {
                $x = $ix;
                $y = $iy;
                for ($i = 0; $i < $numpoles; ++ $i) {
                    $dx = $ix - $px[$i];
                    $dy = $iy - $py[$i];
                    if ($dx == 0 && $dy == 0) {
                        continue;
                    }
                    $r = sqrt($dx * $dx + $dy * $dy);
                    if ($r > $rad[$i]) {
                        continue;
                    }
                    $rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
                    $x += $dx * $rscale;
                    $y += $dy * $rscale;
                }
                $c = $bgCol;
                $x *= $this->iscale;
                $y *= $this->iscale;
                if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
                    $c = imagecolorat($this->tmpimg, $x, $y);
                }
                if ($c != $bgCol) { // only copy pixels of letters to preserve any background image
                    imagesetpixel($this->im, $ix, $iy, $c);
                }
            }
        }
    }
    
    /**
     * Draws distorted lines on the image
     */
    protected function drawLines()
    {
        for ($line = 0; $line < $this->num_lines; ++ $line) {
            $x = $this->image_width * (1 + $line) / ($this->num_lines + 1);
            $x += (0.5 - $this->frand()) * $this->image_width / $this->num_lines;
            $y = rand($this->image_height * 0.1, $this->image_height * 0.9);
            
            $theta = ($this->frand() - 0.5) * M_PI * 0.7;
            $w = $this->image_width;
            $len = rand($w * 0.4, $w * 0.7);
            $lwid = rand(0, 2);
            
            $k = $this->frand() * 0.6 + 0.2;
            $k = $k * $k * 0.5;
            $phi = $this->frand() * 6.28;
            $step = 0.5;
            $dx = $step * cos($theta);
            $dy = $step * sin($theta);
            $n = $len / $step;
            $amp = 1.5 * $this->frand() / ($k + 5.0 / $len);
            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);
            
            $ldx = round(- $dy * $lwid);
            $ldy = round($dx * $lwid);
            
            for ($i = 0; $i < $n; ++ $i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdlinecolor);
            }
        }
    }
    
    /**
     * Draws random noise on the image
     */
    protected function drawNoise()
    {
        if ($this->noise_level > 10) {
            $noise_level = 10;
        } else {
            $noise_level = $this->noise_level;
        }

        $t0 = microtime(true);
        
        $noise_level *= 125; // an arbitrary number that works well on a 1-10 scale
        
        $points = $this->image_width * $this->image_height * $this->iscale;
        $height = $this->image_height * $this->iscale;
        $width  = $this->image_width * $this->iscale;
        for ($i = 0; $i < $noise_level; ++$i) {
            $x = rand(10, $width);
            $y = rand(10, $height);
            $size = rand(7, 10);
            if ($x - $size <= 0 && $y - $size <= 0) continue; // dont cover 0,0 since it is used by imagedistortedcopy
            imagefilledarc($this->tmpimg, $x, $y, $size, $size, 0, 360, $this->gdnoisecolor, IMG_ARC_PIE);
        }
        
        $t1 = microtime(true);
        
        $t = $t1 - $t0;
        
        /*
        // DEBUG
        imagestring($this->tmpimg, 5, 25, 30, "$t", $this->gdnoisecolor);
        header('content-type: image/png');
        imagepng($this->tmpimg);
        exit;
        */
    }
    
	/**
	* Print signature text on image
	*/
    protected function addSignature()
    {
        if ($this->use_gd_font) {
            imagestring($this->im, 5, $this->image_width - (strlen($this->image_signature) * 10), $this->image_height - 20, $this->image_signature, $this->gdsignaturecolor);
        } else {
             
            $bbox = imagettfbbox(10, 0, $this->signature_font, $this->image_signature);
            $textlen = $bbox[2] - $bbox[0];
            $x = $this->image_width - $textlen - 5;
            $y = $this->image_height - 3;
             
            imagettftext($this->im, 10, 0, $x, $y, $this->gdsignaturecolor, $this->signature_font, $this->image_signature);
        }
    }
    
    /**
     * Sends the appropriate image and cache headers and outputs image to the browser
     */
    protected function output()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        switch ($this->image_type) {
            case self::SI_IMAGE_JPEG:
                header("Content-Type: image/jpeg");
                imagejpeg($this->im, null, 90);
                break;
            case self::SI_IMAGE_GIF:
                header("Content-Type: image/gif");
                imagegif($this->im);
                break;
            default:
                header("Content-Type: image/png");
                imagepng($this->im);
                break;
        }
        
        imagedestroy($this->im);
        exit();
    }
    
    /**
     * Gets the code and returns the binary audio file for the stored captcha code
     * @param string $format WAV only
     */
    protected function getAudibleCode($format = 'wav')
    {
        // override any format other than wav for now
        // this is due to security issues with MP3 files
        $format  = 'wav';
        
        $letters = array();
        $code    = $this->getCode();

        if ($code == '') {
            $this->createCode();
            $code = $this->getCode();
        }

        for($i = 0; $i < strlen($code); ++$i) {
            $letters[] = $code{$i};
        }
        
        if ($format == 'mp3') {
            return $this->generateMP3($letters);
        } else {
            return $this->generateWAV($letters);
        }
    }

    /**
     * Gets a captcha code from a wordlist
     */
    protected function readCodeFromFile()
    {
        $fp = @fopen($this->wordlist_file, 'rb');
        if (!$fp) return false;

        $fsize = filesize($this->wordlist_file);
        if ($fsize < 128) return false; // too small of a list to be effective

        fseek($fp, rand(0, $fsize - 64), SEEK_SET); // seek to a random position of file from 0 to filesize-64
        $data = fread($fp, 64); // read a chunk from our random position
        fclose($fp);
        $data = preg_replace("/\r?\n/", "\n", $data);

        $start = @strpos($data, "\n", rand(0, 56)) + 1; // random start position
        $end   = @strpos($data, "\n", $start);          // find end of word
        
        if ($start === false) {
            return false;
        } else if ($end === false) {
            $end = strlen($data);
        }

        return strtolower(substr($data, $start, $end - $start)); // return a line of the file
    }
    
    /**
     * Generates a random captcha code from the set character set
     */
    protected function generateCode()
    {
        $code = '';

        for($i = 1, $cslen = strlen($this->charset); $i <= $this->code_length; ++$i) {
            $code .= $this->charset{rand(0, $cslen - 1)};			
        }
        
        //return 'testing';  // debug, set the code to given string
        
        return $code;
    }
    
	protected function generateCode_zh()
    {
        $code = '';

        for($i = 1, $cslen = strlen($this->charset_zh); $i <= $this->code_length; ++$i) {
			$rand_index = rand(0, $cslen/2 - 1);
			$code .=substr($this->charset_zh,$rand_index*2,2);
			
        }
        
        //return 'testing';  // debug, set the code to given string
        
        return $code;
    }
    /**
     * Checks the entered code against the value stored in the session or sqlite database, handles case sensitivity
     * Also clears the stored codes if the code was entered correctly to prevent re-use
     */
    protected function validate()
    {
        $code = $this->getCode();
        // returns stored code, or an empty string if no stored code was found
        // checks the session and sqlite database if enabled
        
        if ($this->case_sensitive == false && preg_match('/[A-Z]/', $code)) {
            // case sensitive was set from securimage_show.php but not in class
            // the code saved in the session has capitals so set case sensitive to true
            $this->case_sensitive = true;
        }
        
        $code_entered = trim( (($this->case_sensitive) ? $this->code_entered
                                                       : strtolower($this->code_entered))
                        );
        $this->correct_code = false;
        
        if ($code != '') {
            if ($code == $code_entered) {
                $this->correct_code = true;
                //$_SESSION['securimage_code_value'][$this->namespace] = '';
                //$_SESSION['securimage_code_ctime'][$this->namespace] = '';
                $this->clearCodeFromDatabase();
            }
        }
    }
    
    /**
     * Return the code from the session or sqlite database if used.  If none exists yet, an empty string is returned
     */
    protected function getCode()
    {
        $code = '';
        
        if (isset($_SESSION['securimage_code_value'][$this->namespace]) &&
         trim($_SESSION['securimage_code_value'][$this->namespace]) != '') {
            if ($this->isCodeExpired(
            $_SESSION['securimage_code_ctime'][$this->namespace]) == false) {
                $code = $_SESSION['securimage_code_value'][$this->namespace];
            }
        } else if ($this->use_sqlite_db == true && function_exists('sqlite_open')) {
            // no code in session - may mean user has cookies turned off
            $this->openDatabase();
            $code = $this->getCodeFromDatabase();
        } else { /* no code stored in session or sqlite database, validation will fail */ }
        
        return $code;
    }
    
    /**
     * Save data to session namespace and database if used
     */
    protected function saveData()
    {
        $_SESSION['securimage_code_value'][$this->namespace] = $this->code;
        $_SESSION['securimage_code_ctime'][$this->namespace] = time();
        
        $this->saveCodeToDatabase();
    }
    
    /**
     * Saves the code to the sqlite database
     */
    protected function saveCodeToDatabase()
    {
        $success = false;
        
        $this->openDatabase();
        
        if ($this->use_sqlite_db && $this->sqlite_handle !== false) {
            $ip      = $_SERVER['REMOTE_ADDR'];
            $time    = time();
            $code    = $_SESSION['securimage_code_value'][$this->namespace]; // if cookies are disabled the session still exists at this point
            $success = sqlite_query($this->sqlite_handle,
                                    "INSERT OR REPLACE INTO codes(ip, code, namespace, created)
                                    VALUES('$ip', '$code', '{$this->namespace}', $time)");
        }
        
        return $success !== false;
    }
    
    /**
     * Open sqlite database
     */
    protected function openDatabase()
    {
        $this->sqlite_handle = false;
        
        if ($this->use_sqlite_db && function_exists('sqlite_open')) {
            $this->sqlite_handle = sqlite_open($this->sqlite_database, 0666, $error);
            
            if ($this->sqlite_handle !== false) {
                $res = sqlite_query($this->sqlite_handle, "PRAGMA table_info(codes)");
                if (sqlite_num_rows($res) == 0) {
                    sqlite_query($this->sqlite_handle, "CREATE TABLE codes (ip VARCHAR(32) PRIMARY KEY, code VARCHAR(32) NOT NULL, namespace VARCHAR(32) NOT NULL, created INTEGER)");
                }
            }
            
            return $this->sqlite_handle != false;
        }
        
        return $this->sqlite_handle;
    }
    
    /**
     * Get a code from the sqlite database for ip address
     */
    protected function getCodeFromDatabase()
    {
        $code = '';

        if ($this->use_sqlite_db && $this->sqlite_handle !== false) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ns = sqlite_escape_string($this->namespace);

            $res = sqlite_query($this->sqlite_handle, "SELECT * FROM codes WHERE ip = '$ip' AND namespace = '$ns'");
            if ($res && sqlite_num_rows($res) > 0) {
                $res = sqlite_fetch_array($res);

                if ($this->isCodeExpired($res['created']) == false) {
                    $code = $res['code'];
                }
            }
        }
        return $code;
    }
    
    /**
     * Remove an entered code from the database
     */
    protected function clearCodeFromDatabase()
    {
        if (is_resource($this->sqlite_handle)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ns = sqlite_escape_string($this->namespace);
            
            sqlite_query($this->sqlite_handle, "DELETE FROM codes WHERE ip = '$ip' AND namespace = '$ns'");
        }
    }
    
    /**
     * Deletes old codes from sqlite database
     */
    protected function purgeOldCodesFromDatabase()
    {
        if ($this->use_sqlite_db && $this->sqlite_handle !== false) {
            $now   = time();
            $limit = (!is_numeric($this->expiry_time) || $this->expiry_time < 1) ? 86400 : $this->expiry_time;
            
            sqlite_query($this->sqlite_handle, "DELETE FROM codes WHERE $now - created > $limit");
        }
    }
    
    /**
     * Checks to see if the captcha code has expired and cannot be used
     * @param unknown_type $creation_time
     */
    protected function isCodeExpired($creation_time)
    {
        $expired = true;
        
        if (!is_numeric($this->expiry_time) || $this->expiry_time < 1) {
            $expired = false;
        } else if (time() - $creation_time < $this->expiry_time) {
            $expired = false;
        }
        
        return $expired;
    }
    
    /**
     * 
     * Generate an MP3 audio file of the captcha image
     * 
     * @deprecated 3.0
     */
    protected function generateMP3()
    {
        return false;
    }
    
    /**
     * Generate a wav file given the $letters in the code
     * @todo Add ability to merge 2 sound files together to have random background sounds
     * @param array $letters
     * @return string The binary contents of the wav file
     */
    protected function generateWAV($letters)
    {
        $data_len       = 0;
        $files          = array();
        $out_data       = '';
        $out_channels   = 0;
        $out_samplert   = 0;
        $out_bpersample = 0;
        $numSamples     = 0;
        $removeChunks   = array('LIST', 'DISP', 'NOTE');

        for ($i = 0; $i < sizeof($letters); ++$i) {
            $letter   = $letters[$i];
            $filename = $this->audio_path . strtoupper($letter) . '.wav';
            $file     = array();
            $data     = @file_get_contents($filename);
            
            if ($data === false) {
                // echo "Failed to read $filename";
                return $this->audioError();
            }

            $header = substr($data, 0, 36);
            $info   = unpack('NChunkID/VChunkSize/NFormat/NSubChunk1ID/'
                            .'VSubChunk1Size/vAudioFormat/vNumChannels/'
                            .'VSampleRate/VByteRate/vBlockAlign/vBitsPerSample',
                             $header);
            
            $dataPos        = strpos($data, 'data');
            $out_channels   = $info['NumChannels'];
            $out_samplert   = $info['SampleRate'];
            $out_bpersample = $info['BitsPerSample'];
            
            if ($dataPos === false) {
                // wav file with no data?
                // echo "Failed to find DATA segment in $filename";
                return $this->audioError();
            }
            
            if ($info['AudioFormat'] != 1) {
                // only work with PCM audio
                // echo "$filename was not PCM audio, only PCM is supported";
                return $this->audioError();
            }
            
            if ($info['SubChunk1Size'] != 16 && $info['SubChunk1Size'] != 18) {
                // probably unsupported extension
                // echo "Bad SubChunk1Size in $filename - Size was {$info['SubChunk1Size']}";
                return $this->audioError();
            }
            
            if ($info['SubChunk1Size'] > 16) {
                $header .= substr($data, 36, $info['SubChunk1Size'] - 16);
            }
            
            if ($i == 0) {
                // create the final file's header, size will be adjusted later
                $out_data = $header . 'data';
            }
            
            $removed = 0;
            
            foreach($removeChunks as $chunk) {
                $chunkPos = strpos($data, $chunk);
                if ($chunkPos !== false) {
                    $listSize = unpack('VSize', substr($data, $chunkPos + 4, 4));
                    
                    $data = substr($data, 0, $chunkPos) .
                            substr($data, $chunkPos + 8 + $listSize['Size']);
                            
                    $removed += $listSize['Size'] + 8;
                }
            }
            
            $dataSize    = unpack('VSubchunk2Size', substr($data, $dataPos + 4, 4));
            $dataSize['Subchunk2Size'] -= $removed;
            $out_data   .= substr($data, $dataPos + 8, $dataSize['Subchunk2Size'] * ($out_bpersample / 8));
            $numSamples += $dataSize['Subchunk2Size'];
        }

        $filesize  = strlen($out_data);
        $chunkSize = $filesize - 8;
        $dataCSize = $numSamples;
        
        $out_data = substr_replace($out_data, pack('V', $chunkSize), 4, 4);
        $out_data = substr_replace($out_data, pack('V', $numSamples), 40 + ($info['SubChunk1Size'] - 16), 4);

        $this->scrambleAudioData($out_data, 'wav');
        
        return $out_data;
    }
    
    /**
     * Randomizes the audio data to add noise and prevent binary recognition
     * @param string $data  The binary audio file data
     * @param string $format The format of the sound file (wav only)
     */
    protected function scrambleAudioData(&$data, $format)
    {
        $start = strpos($data, 'data') + 4; // look for "data" indicator
        if ($start === false) $start = 44;  // if not found assume 44 byte header
         
        $start  += rand(1, 4); // randomize starting offset
        $datalen = strlen($data) - $start;
        $step    = 1;
        
        for ($i = $start; $i < $datalen; $i += $step) {
            $ch = ord($data{$i});
            if ($ch == 0 || $ch == 255) continue;
            
            if ($ch < 16 || $ch > 239) {
                $ch += rand(-6, 6);
            } else {
                $ch += rand(-12, 12);
            }
            
            if ($ch < 0) $ch = 0; else if ($ch > 255) $ch = 255;

            $data{$i} = chr($ch);
            
            $step = rand(1,4);
        }

        return $data;
    }
    
    /**
     * Return a wav file saying there was an error generating file
     * 
     * @return string The binary audio contents
     */
    protected function audioError()
    {
        return @file_get_contents(dirname(__FILE__) . '/audio/error.wav');
    }
    
    function frand()
    {
        return 0.0001 * rand(0,9999);
    }
    
    /**
     * Convert an html color code to a Securimage_Color
     * @param string $color
     * @param Securimage_Color $default The defalt color to use if $color is invalid
     */
    protected function initColor($color, $default)
    {
        if ($color == null) {
            return new Securimage_Color($default);
        } else if (is_string($color)) {
            try {
                return new Securimage_Color($color);
            } catch(Exception $e) {
                return new Securimage_Color($default);
            }
        } else if (is_array($color) && sizeof($color) == 3) {
            return new Securimage_Color($color[0], $color[1], $color[2]);
        } else {
            return new Securimage_Color($default);
        }
    }
}


/**
 * Color object for Securimage CAPTCHA
 *
 * @version 3.0
 * @since 2.0
 * @package Securimage
 * @subpackage classes
 *
 */
class Securimage_Color
{
    public $r;
    public $g;
    public $b;

    /**
     * Create a new Securimage_Color object.<br />
     * Constructor expects 1 or 3 arguments.<br />
     * When passing a single argument, specify the color using HTML hex format,<br />
     * when passing 3 arguments, specify each RGB component (from 0-255) individually.<br />
     * $color = new Securimage_Color('#0080FF') or <br />
     * $color = new Securimage_Color(0, 128, 255)
     * 
     * @param string $color
     * @throws Exception
     */
    public function __construct($color = '#ffffff')
    {
        $args = func_get_args();
        
        if (sizeof($args) == 0) {
            $this->r = 255;
            $this->g = 255;
            $this->b = 255;
        } else if (sizeof($args) == 1) {
            // set based on html code
            if (substr($color, 0, 1) == '#') {
                $color = substr($color, 1);
            }
            
            if (strlen($color) != 3 && strlen($color) != 6) {
                throw new InvalidArgumentException(
                  'Invalid HTML color code passed to Securimage_Color'
                );
            }
            
            $this->constructHTML($color);
        } else if (sizeof($args) == 3) {
            $this->constructRGB($args[0], $args[1], $args[2]);
        } else {
            throw new InvalidArgumentException(
              'Securimage_Color constructor expects 0, 1 or 3 arguments; ' . sizeof($args) . ' given'
            );
        }
    }
    
    /**
     * Construct from an rgb triplet
     * @param int $red The red component, 0-255
     * @param int $green The green component, 0-255
     * @param int $blue The blue component, 0-255
     */
    protected function constructRGB($red, $green, $blue)
    {
        if ($red < 0)     $red   = 0;
        if ($red > 255)   $red   = 255;
        if ($green < 0)   $green = 0;
        if ($green > 255) $green = 255;
        if ($blue < 0)    $blue  = 0;
        if ($blue > 255)  $blue  = 255;
        
        $this->r = $red;
        $this->g = $green;
        $this->b = $blue;
    }
    
    /**
     * Construct from an html hex color code
     * @param string $color
     */
    protected function constructHTML($color)
    {
        if (strlen($color) == 3) {
            $red   = str_repeat(substr($color, 0, 1), 2);
            $green = str_repeat(substr($color, 1, 1), 2);
            $blue  = str_repeat(substr($color, 2, 1), 2);
        } else {
            $red   = substr($color, 0, 2);
            $green = substr($color, 2, 2);
            $blue  = substr($color, 4, 2); 
        }
        
        $this->r = hexdec($red);
        $this->g = hexdec($green);
        $this->b = hexdec($blue);
    }
}
