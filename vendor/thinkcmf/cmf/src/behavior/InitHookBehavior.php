<?php
// +---------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +---------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +---------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +---------------------------------------------------------------------
namespace cmf\behavior;

use think\db\Query;
use think\exception\HttpResponseException;
use think\facade\Hook;
use think\Db;
use think\facade\Response;
use think\facade\Route;

class InitHookBehavior
{

    // 行为扩展的执行入口必须是run
    public function run($param)
    {
        # Route::any('plugin/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\PluginController@index");
        Route::any('gateway/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\GatewayController@index");
        # WYH 添加插件路由 20200930
        $admin_application = adminAddress();
        # 插件后台路由
        # Route::any($admin_application.'/addons/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\AddonController@index");
        Route::any($admin_application.'/addons', "\\cmf\\controller\\AddonController@index"); // 参数 ?_plugin=client_care&_controller=client_care&_action=index
        # 插件前台路由
        # Route::any('addons/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\AddonHomeController@index");
        Route::any('addons', "\\cmf\\controller\\AddonHomeController@index")->middleware('UserCheck');  // 参数 ?_plugin=205&_controller=client_care&_action=index

        # TODO WYH 20210330 实名认证
        Route::any('certification', "\\cmf\\controller\\CertificationHomeController@index")->middleware('UserCheck');

        Route::get('new_captcha', "\\cmf\\controller\\CaptchaController@index");
        if (!cmf_is_installed()) {
            return;
        }

        # wyh 20210113 加载插件钩子文件,且插件需要安装及启用状态,且只加载系统钩子，配置文件app.php里添加shd_hooks
        $base_dir = WEB_ROOT . 'plugins/addons/';
        $module_dirs = array_map('basename', glob($base_dir . '*', GLOB_ONLYDIR));
        foreach ($module_dirs as $module_dir){
            $parse_name = cmf_parse_name($module_dir,1);
            $find = Db::name('plugin')->where('name',$parse_name)->where('status',1)->find();
            if ($find && is_file($base_dir . $module_dir . '/hooks.php')){
                include $base_dir . $module_dir . '/hooks.php';
            }
        }

        # 加载系统钩子
        $systemHookPlugins = '';//cache('init_hook_plugins_system_hook_plugins');
        if (empty($systemHookPlugins)) {
            $systemHooks = getSystemHook();
            $systemHookPlugins = Db::name('hook_plugin')->field('hook,plugin')->where('status', 1)
                ->where('hook', 'in', $systemHooks)
                ->order('list_order ASC')
                ->select()->toArray();
            if (!empty($systemHookPlugins)) {
                //cache('init_hook_plugins_system_hook_plugins', $systemHookPlugins, null, 'init_hook_plugins');
            }
        }
        # var_dump($systemHookPlugins);die;
        if (!empty($systemHookPlugins)) {
            foreach ($systemHookPlugins as $hookPlugin) {
				$class=cmf_get_plugin_class_shd($hookPlugin['plugin'],'addons');
				if (!class_exists($class)) { // 实例化插件失败忽略
					continue;
                }
                Hook::add($hookPlugin['hook'],$class);
            }
        }


    }
}