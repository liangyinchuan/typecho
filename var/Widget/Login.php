<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 登录动作
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 登录组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Login extends Widget_Abstract_Users implements Widget_Interface_Do
{
    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** 如果已经登录 */
        if ($this->user->hasLogin()) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }

        /** 初始化验证类 */
        $validator = new Typecho_Validate();
        $validator->addRule('name', 'required', _t('请输入用户名'));
        $validator->addRule('password', 'required', _t('请输入密码'));

        // 查询当日是否有错误登录验证记录 防止暴力破解
        $errValidKey = '__err_valid_num_key_'.$this->request->name.date('Ymd');
        $errValidNum = Typecho_Cache::getObj()->get($errValidKey);
        if (5<=$errValidNum){
            $this->widget('Widget_Notice')->set(_t('今日错误次数到达上限'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        /** 截获验证异常 */
        if ($error = $validator->run($this->request->from('name', 'password'))) {
            Typecho_Cookie::set('__typecho_remember_name', $this->request->name);

            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set($error);
            $this->response->goBack();
        }
        
        /** 开始验证用户 **/
        $valid = $this->user->login($this->request->name, $this->request->password,
        false, 1 == $this->request->remember ? $this->options->time + $this->options->timezone + 30*24*3600 : 0);

        /** 比对密码 */
        if (!$valid) {
            // 每天的错误次数记录一天
            Typecho_Cache::getObj()->set($errValidKey,++$errValidNum,86400);
            /** 防止穷举,休眠2秒 */
            sleep(2);

            $this->pluginHandle()->loginFail($this->user, $this->request->name,
            $this->request->password, 1 == $this->request->remember);

            Typecho_Cookie::set('__typecho_remember_name', $this->request->name);
            $this->widget('Widget_Notice')->set(_t('用户名或密码无效'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        $this->pluginHandle()->loginSucceed($this->user, $this->request->name,
        $this->request->password, 1 == $this->request->remember);

        /** 跳转验证后地址 */
        if (NULL != $this->request->referer) {
            $this->response->redirect($this->request->referer);
        } else if (!$this->user->pass('contributor', true)) {
            /** 不允许普通用户直接跳转后台 */
            $this->response->redirect($this->options->profileUrl);
        } else {
            $this->response->redirect($this->options->adminUrl);
        }
    }
}
