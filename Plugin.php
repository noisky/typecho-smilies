<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho 表情插件：贴吧表情专用版
 * 
 * @package Smilies
 * @author 饭饭
 * @version 2.0.2
 * @dependence 14.10.10-*
 * @link https://github.com/noisky/typecho-smilies
 */
class Smilies_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('Smilies_Plugin','showsmilies');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Smilies_Plugin','showsmilies');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Smilies_Plugin','showsmilies');

		Typecho_Plugin::factory('Widget_Archive')->footer = array('Smilies_Plugin','insertjs');
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('Smilies_Plugin', 'insertjs');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('Smilies_Plugin', 'insertjs');

		Typecho_Plugin::factory('admin/write-post.php')->option = array('Smilies_Plugin', 'render');
		Typecho_Plugin::factory('admin/write-page.php')->option = array('Smilies_Plugin', 'render');
	}

	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate(){}

	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$usage = "<?php if (\\Typecho\\Plugin::exists('Smilies') && class_exists('Smilies_Plugin')): ?>\n"
			. "    <?php Smilies_Plugin::output(); ?>\n"
			. "<?php endif; ?>";
		echo '<div class="typecho-option" style="padding:16px 0;">'
			. '<h3 style="margin:0 0 10px;">' . _t('使用说明') . '</h3>'
			. '<p>' . _t('请在主题评论框的合适位置添加以下代码：') . '</p>'
			. '<pre style="margin:8px 0;padding:12px;background:#f6f6f6;border:1px solid #ddd;overflow:auto;"><code>'
			. htmlspecialchars($usage, ENT_QUOTES, 'UTF-8')
			. '</code></pre>'
			. '</div>';

		$cdn = new Typecho_Widget_Helper_Form_Element_Text('cdn',
		NULL,'',_t('表情图片CDN地址'),_t('留空使用插件本地图片；填写对应 usr/plugins/ 目录的地址，例如 https://cdn.example.com/usr/plugins/，样式表仍从本地加载'));
		$cdn->input->setAttribute('class','w-50');
		$form->addInput($cdn);

		$textareaid = new Typecho_Widget_Helper_Form_Element_Text('textareaid',
		NULL,_t('一般无需填写'),_t('指定评论框ID'),_t('若插件识别出现问题可在此指定主题使用的评论框id'));
		$textareaid->input->setAttribute('class','w-20');
		$form->addInput($textareaid);

		$postmode = new Typecho_Widget_Helper_Form_Element_Radio('postmode',
		array(1=>_t('开启'),0=>_t('关闭')),1,_t('正文使用表情'),_t('编辑文章或页面时也可以插入表情代码并在前台显示'));
		$form->addInput($postmode);

//输出面板效果
?>

<?php
	}

	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}

	/**
	 * 兼容中文文件名
	 * 
	 * @access private
	 * @return array
	 */
	private static function cname(&$value) {
		$value = is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
		if (function_exists('iconv')) {
			// 目录或文件名已经是 UTF-8 时直接保留，避免 iconv() 报错。
			$isUtf8 = function_exists('mb_check_encoding')
				? mb_check_encoding($value, 'UTF-8')
				: (preg_match('//u', $value) === 1);

			if (!$isUtf8) {
				$converted = @iconv('GBK', 'UTF-8//IGNORE', $value);
				if ($converted !== false) {
					$value = $converted;
				}
			}
		}
		$normalized = preg_replace('/^.+[\\\\\\/]/','',$value);
		if ($normalized !== null) {
			$value = $normalized;
		}
	}

	/**
	 * 整理表情数据
	 * 
	 * @access private
	 * @return array
	 */
	/**
	 * 获取表情插件资源地址
	 *
	 * CDN配置填写插件资源根地址，留空时使用本地插件目录。
	 *
	 * @access private
	 * @param string $path 资源相对路径
	 * @param mixed $options Typecho选项
	 * @param mixed $settings 插件配置
	 * @return string
	 */
	private static function assetUrl($path, $options, $settings)
	{
		$cdn = trim(self::stringValue(self::settingValue($settings, 'cdn', '')));
		if ($cdn) {
			return rtrim($cdn, '/') . '/' . ltrim($path, '/');
		}

		return Typecho_Common::url('Smilies/' . ltrim($path, '/'), $options->pluginUrl);
	}

	private static function parsesmilies()
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');

		$smiliestrans = array(
			'@(呵呵)'        =>    '呵呵@2x.png',
			'@(哈哈)'        =>    '哈哈@2x.png',
			'@(吐舌)'        =>    '吐舌@2x.png',
			'@(太开心)'        =>    '太开心@2x.png',
			'@(笑眼)'        =>    '笑眼@2x.png',
			'@(花心)'        =>    '花心@2x.png',
			'@(小乖)'        =>    '小乖@2x.png',
			'@(乖)'        =>    '乖@2x.png',
			'@(捂嘴笑)'        =>    '捂嘴笑@2x.png',
			'@(滑稽)'        =>    '滑稽@2x.png',
			'@(你懂的)'        =>    '你懂的@2x.png',
			'@(不高兴)'        =>    '不高兴@2x.png',
			'@(怒)'        =>    '怒@2x.png',
			'@(汗)'        =>    '汗@2x.png',
			'@(黑线)'        =>    '黑线@2x.png',
			'@(泪)'        =>    '泪@2x.png',
			'@(真棒)'        =>    '真棒@2x.png',
			'@(喷)'        =>    '喷@2x.png',
			'@(惊哭)'        =>    '惊哭@2x.png',
			'@(阴险)'        =>    '阴险@2x.png',
			'@(鄙视)'        =>    '鄙视@2x.png',
			'@(酷)'        =>    '酷@2x.png',
			'@(啊)'        =>    '啊@2x.png',
			'@(狂汗)'        =>    '狂汗@2x.png',
			'@(what)'        =>    'what@2x.png',
			'@(疑问)'        =>    '疑问@2x.png',
			'@(酸爽)'        =>    '酸爽@2x.png',
			'@(呀咩爹)'        =>    '呀咩爹@2x.png',
			'@(委屈)'        =>    '委屈@2x.png',
			'@(惊讶)'        =>    '惊讶@2x.png',
			'@(睡觉)'        =>    '睡觉@2x.png',
			'@(笑尿)'        =>    '笑尿@2x.png',
			'@(挖鼻)'        =>    '挖鼻@2x.png',
			'@(吐)'        =>    '吐@2x.png',
			'@(犀利)'        =>    '犀利@2x.png',
			'@(小红脸)'        =>    '小红脸@2x.png',
			'@(懒得理)'        =>    '懒得理@2x.png',
			'@(勉强)'        =>    '勉强@2x.png',
			'@(爱心)'        =>    '爱心@2x.png',
			'@(心碎)'        =>    '心碎@2x.png',
			'@(玫瑰)'        =>    '玫瑰@2x.png',
			'@(礼物)'        =>    '礼物@2x.png',
			'@(彩虹)'        =>    '彩虹@2x.png',
			'@(太阳)'        =>    '太阳@2x.png',
			'@(星星月亮)'        =>    '星星月亮@2x.png',
			'@(钱币)'        =>    '钱币@2x.png',
			'@(茶杯)'        =>    '茶杯@2x.png',
			'@(蛋糕)'        =>    '蛋糕@2x.png',
			'@(大拇指)'        =>    '大拇指@2x.png',
			'@(胜利)'        =>    '胜利@2x.png',
			'@(OK)'        =>    'OK@2x.png',
			'@(沙发)'        =>    '沙发@2x.png',
			'@(手纸)'        =>    '手纸@2x.png',
			'@(香蕉)'        =>    '香蕉@2x.png',
			'@(便便)'        =>    '便便@2x.png',
			'@(药丸)'        =>    '药丸@2x.png',
			'@(红领巾)'        =>    '红领巾@2x.png',
			'@(蜡烛)'        =>    '蜡烛@2x.png',
			'@(音乐)'        =>    '音乐@2x.png',
			'@(灯泡)'        =>    '灯泡@2x.png',
		);

		$smiliesurl = self::assetUrl('paopao/', $options, $settings);
		$smiliesurlHtml = htmlspecialchars($smiliesurl, ENT_QUOTES, 'UTF-8');
		$smiled = array();
		$smiliesicon = array();
		$smiliestag = array();
		$smiliesimg = array();
		$smilies = '<div class="btn ">选择表情</div>';

		foreach ($smiliestrans as $tag=>$grin) {
			$tagHtml = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
			$tagJs = htmlspecialchars((string) json_encode($tag, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
			$grinHtml = htmlspecialchars($grin, ENT_QUOTES, 'UTF-8');

			if (!in_array($grin,$smiled)) {
				$smiled[] = $grin;
				$smiliesicon[] = '<span onclick="Smilies.grin('.$tagJs.');" data-tag=" '.$tagHtml.' " class="face"><img src="'.$smiliesurlHtml.$grinHtml.'" loading="lazy" decoding="async" width="30" height="30" alt="'.$grinHtml.'"/></span>';
			}

			$smiliestag[] = $tag;
			$grin =str_replace("@2x", "", $grin);
			$grinHtml = htmlspecialchars($grin, ENT_QUOTES, 'UTF-8');
			$smiliesimg[] = '<img class="smilies"  src="'.$smiliesurlHtml.$grinHtml.'" loading="lazy" decoding="async" alt="'.$grinHtml.'"/>';
		}

		return array($smilies,$smiliesicon,$smiliestag,$smiliesimg);
	}

	/**
	 * 后台编辑选项
	 * 
	 * @access public
	 * @return void
	 */
	public static function render()
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');
		// CDN只替换表情图片，样式表使用插件本地文件，避免CDN未同步CSS时图片按原始尺寸显示。
		$smiliedcss = Typecho_Common::url('Smilies/smilies.css', $options->pluginUrl);
		if (self::settingEnabled($settings, 'postmode', false)) {
			echo '<link href="'.$smiliedcss.'" rel="stylesheet" type="text/css" />';
			echo '<section class="typecho-post-option"><label for="template" class="typecho-label">'._t('选择表情').'</label><p>';
			self::output();
			echo '</p></section>
';
		}
	}

	/**
	 * 解析表情图片
	 * 
	 * @access public
	 * @param string $content 评论内容
	 * @return string
	 */
	public static function showsmilies($content,$widget,$lastResult)
	{
		$content = is_string($lastResult) && $lastResult !== ''
			? $lastResult
			: self::stringValue($content);

		$options = Helper::options();
		// 每个请求只追加一次图片标签白名单，避免多条评论重复累加。
		static $allowedImgTagAdded = false;
		if (!$allowedImgTagAdded) {
			$imgTag = '<img src="" alt="" style="" loading="" decoding=""/>';
			$allowedTags = self::stringValue($options->commentsHTMLTagAllowed);
			if (false === strpos($allowedTags, $imgTag)) {
				$options->commentsHTMLTagAllowed = $allowedTags . $imgTag;
			}
			$allowedImgTagAdded = true;
		}

		if ($widget instanceof Widget_Abstract_Comments
			|| ($widget instanceof Widget_Archive
				&& self::settingEnabled($options->plugin('Smilies'), 'postmode', false))) {
			$arrays = self::parsesmilies();
			$content = str_replace($arrays['2'],$arrays['3'],$content);
		}

		return $content;
	}

	/**
	 * 输出表情选框
	 * 
	 * @access public
	 * @return void
	 */
	public static function output()
	{
		$options = Helper::options();
		// 固定为始终展开，不再显示弹窗按钮。
		$smiliesdisplay = ' style="display:block;"';

		//罗列表情图标
		$arrays = self::parsesmilies();
		$smilies = '';
		foreach ($arrays['1'] as $icon) {
			$smilies .= $icon;
		}

		$output = '<div id="smiliesbox"'.$smiliesdisplay.'>';
		$output .= $smilies;
		$output .= '</div>';

		echo $output;
	}

	/**
	 * 输出js脚本
	 * 
	 * @access public
	 * @return void
	 */
	public static function insertjs($widget)
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');
		$textareaid = self::stringValue(self::settingValue($settings, 'textareaid', ''));
		$textareaid = $textareaid !== '' ? $textareaid : _t('一般无需填写');

		$idset = $widget->is('single') ? $textareaid : 'text';
		$txtid = $idset;
		$txtdom = 'domId("'.$txtid.'")';
		if ($widget->is('single') && $idset==_t('一般无需填写')) {
			$txtid = 'textarea';
			$txtdom = 'domTag("'.$txtid.'")';
		}

		//固定使用原生 JavaScript，不依赖 jQuery。
		$js = '<script type="text/javascript">
//<![CDATA[
Smilies = {
    domId : function(id) {
        return document.getElementById(id);
    },
    domTag : function(id) {
        return document.getElementsByTagName(id)[0];
    },
    focus : function(field) {
        try {
            field.focus({preventScroll: true});
        } catch (e) {
            field.focus();
        }
    },
    restoreScroll : function(field, scrollTop, scrollLeft, pageX, pageY) {
        field.scrollTop = scrollTop;
        field.scrollLeft = scrollLeft;
        if (window.scrollTo) {
            window.scrollTo(pageX, pageY);
        }
    },
    grin : function (tag) {
        tag = \' \' + tag + \' \';
        var myField = this.'.$txtdom.';
        if (!myField) {
            return;
        }

        var pageX = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
        var pageY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        var scrollTop = myField.scrollTop;
        var scrollLeft = myField.scrollLeft;

        if (document.selection && document.selection.createRange) {
            this.focus(myField);
            var sel = document.selection.createRange();
            sel.text = tag;
            this.focus(myField);
            this.restoreScroll(myField, scrollTop, scrollLeft, pageX, pageY);
            return;
        }

        this.insertTag(tag);
    },
    insertTag : function (tag) {
        var myField = Smilies.'.$txtdom.';
        if (!myField) {
            return;
        }

        var startPos = myField.selectionStart;
        var endPos = myField.selectionEnd;
        var scrollTop = myField.scrollTop;
        var scrollLeft = myField.scrollLeft;
        var pageX = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
        var pageY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

        if (typeof startPos === "number" && typeof endPos === "number") {
            myField.value = myField.value.substring(0, startPos)
                + tag
                + myField.value.substring(endPos);

            var cursorPos = startPos + tag.length;
            Smilies.focus(myField);
            myField.selectionStart = cursorPos;
            myField.selectionEnd = cursorPos;
        } else {
            myField.value += tag;
            Smilies.focus(myField);
        }

        Smilies.restoreScroll(myField, scrollTop, scrollLeft, pageX, pageY);
    }
}
//]]>
</script>';

		if ($widget->is('single')) {
			echo $js;
		}
		if (($widget instanceof Widget_Contents_Post_Edit || $widget instanceof \Widget\Contents\Page\Edit)
			&& self::settingEnabled($settings, 'postmode', false)) {
			echo $js;
		}

	}

	/**
	 * 读取插件配置，兼容 Typecho_Config 和数组配置。
	 */
	private static function settingValue($settings, $name, $default = null)
	{
		if (is_array($settings) && array_key_exists($name, $settings)) {
			return $settings[$name];
		}

		if ($settings instanceof ArrayAccess && isset($settings[$name])) {
			return $settings[$name];
		}

		if (is_object($settings) && property_exists($settings, $name)) {
			return $settings->{$name};
		}

		return $default;
	}

	/**
	 * 将外部值安全转换为字符串。
	 */
	private static function stringValue($value)
	{
		return is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
	}

	/**
	 * 读取 1/0、true/false 形式的开关配置。
	 */
	private static function settingEnabled($settings, $name, $default = false)
	{
		$value = self::settingValue($settings, $name, $default);

		return in_array($value, array(true, 1, '1', 'true', 'on', 'yes'), true);
	}

}
