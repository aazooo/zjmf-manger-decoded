<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;
use think\facade\App;
use think\Loader;

/*
 * 插件前台控制
 */
class CertificationHomeController extends HomeBaseController
{
    public function index($_plugin, $_controller, $_action, $language='')
    {
        $_controller = Loader::parseName($_controller, 1);

        if (!preg_match('/^[A-Za-z](\w|\.)*$/', $_controller)) {
            abort(404, 'controller not exists:' . $_controller);
        }

        $_plugin = intval($_plugin);
        $_plugin = Db::name('plugin')->where('id',$_plugin)->value('name');
        $_plugin = cmf_parse_name($_plugin,0);
        if (!preg_match('/^[A-Za-z](\w|\.)*$/', $_plugin)) {
            abort(404, 'plugin not exists:' . $_plugin);
        }

        $this->request->_plugin = $_plugin;
        $pluginControllerClass = "certification\\{$_plugin}\\controller\\clientarea\\{$_controller}Controller";
        if (!class_exists($pluginControllerClass)){
            $pluginControllerClass = "certification\\{$_plugin}\\controller\\Clientarea\\{$_controller}Controller";
        }
        $vars = [];
        $this->request->language = $language;
        # wyh 20210114
        $_action = cmf_parse_name($_action,1);
        return App::invokeMethod([$pluginControllerClass, $_action, $vars]);
    }

}
