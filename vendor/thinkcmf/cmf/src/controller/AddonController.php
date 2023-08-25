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

use think\facade\App;
use think\Loader;

class AddonController extends HomeBaseController
{
    public function index($_plugin, $_controller, $_action)
    {
        $_controller = Loader::parseName($_controller, 1);

        if (!preg_match('/^[A-Za-z](\w|\.)*$/', $_controller)) {
            abort(404, 'controller not exists:' . $_controller);
        }

        if (!preg_match('/^[A-Za-z](\w|\.)*$/', $_plugin)) {
            abort(404, 'plugin not exists:' . $_plugin);
        }

        $pluginControllerClass = "addons\\{$_plugin}\\controller\\{$_controller}Controller";
        $vars = [];
        # wyh 20210114
        $_action = cmf_parse_name($_action,1);
        return App::invokeMethod([$pluginControllerClass, $_action, $vars]);
    }

}
