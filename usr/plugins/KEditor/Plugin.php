<?php
/**
 * KindEditor编辑器
 * 
 * @package KEditor 
 * @author 37th
 * @version 1.0.0
 * @link http://www.xueyangsheng.com
 */
class KEditor_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('KEditor_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('KEditor_Plugin', 'render');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('KEditor_Plugin', 'filter_content');
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
        /** 换行符设置 */
        $newlineTag = new Typecho_Widget_Helper_Form_Element_Text('newlineTag', NULL, 'p', _t('设置回车换行符'), _t('可选参数：p, br'));
		$form->addInput($newlineTag);

        $skinTab = new Typecho_Widget_Helper_Form_Element_Text('skinTab', NULL, 'default', _t('设置编辑器风格'), _t('可选参数：default, tinymce'));
		$form->addInput($skinTab);

		$moreTitle = new Typecho_Widget_Helper_Form_Element_Text('moreTitle', NULL, '阅读剩余部分...
', _t('阅读更多文本'), _t('摘要输出时，more的文本内容'));
		$form->addInput($moreTitle);
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
     * 过滤more标记
     * 
     * @access public
     * @return string
     */
    public static function filter_content($content, Widget_Archive $archive)
	{
		// 首页和分类、归档页
		if ($archive->is('index') || $archive->is('archive'))
		{		
			if (strpos($content, '<div class="ke-more-excerpt"></div>'))
			{
				// 如果含有摘要标签
			    $archive->text = str_replace('<div class="ke-more-excerpt"></div>', '<!--more-->', $content);
				$plugin_options = Typecho_Widget::widget('Widget_Options')->plugin('KEditor');
				return $archive->excerpt . "<p class=\"more\"><a href=\"{$archive->permalink}\" title=\"{$archive->title}\">{$plugin_options->moreTitle}</a></p>";
			}
			return $content;
		}
        return str_replace(array('<!--more-->', '<div class="ke-more-excerpt"></div>'), '', $content);
    }

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $options = Helper::options();
		$plugin_options = Typecho_Widget::widget('Widget_Options')->plugin('KEditor');
		$newlineTag = $plugin_options->newlineTag;
		$skinTab = $plugin_options->skinTab;
		$editor_path = Typecho_Common::url('KEditor/editor', $options->pluginUrl);
		echo "
<style type=\"text/css\" rel=\"stylesheet\">
	.ke-icon-excerpt {
	  background: url({$editor_path}/excerpt_ico.gif);
	  width: 16px;
	  height: 16px;
	}
</style>
<script type=\"text/javascript\" charset=\"utf-8\" src=\"{$editor_path}/kindeditor.js\"></script> 
<script type=\"text/javascript\">

	/** 加入摘要分割支持 */
	KE.lang['excerpt'] = '摘要分割';
	KE.plugin['excerpt'] = {
		click : function(id) {
			insertHtml('text', '<div class=\"ke-more-excerpt\"></div>');
		}
	};
    KE.show({
		cssPath : ['{$editor_path}/excerpt.css'],
	    skinType : '{$skinTab}',
	    newlineTag : '{$newlineTag}',
        id : 'text'
    });

	/** 加入事件，提交前把编辑器的内容设置到原TEXTAREA控件里 */
	$('btn-save').addEvent('mouseover', function (e) {
		KE.util.setData('text'); 
	});
	$('btn-submit').addEvent('mouseover', function (e) {
		KE.util.setData('text'); 
	});
    function insertHtml(id, html) {
        KE.util.focus(id);
        KE.util.selection(id);
        KE.util.insertHtml(id, html);
    }
    /** 附件插入实现 */
    var insertImageToEditor = function (title, url, link) {
        insertHtml('text', '<a href=\"' + link + '\" title=\"' + title + '\"><img src=\"' + url + '\" alt=\"' + title + '\" /></a>');
    };
    
    var insertLinkToEditor = function (title, url, link) {
        insertHtml('text', '<a href=\"' + url + '\" title=\"' + title + '\">' + title + '</a>');
    };
</script>";
    }

}
