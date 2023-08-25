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

use think\Container;
use think\Controller;
use think\Db;
use think\facade\View;
use think\facade\Config;

class BaseController extends Controller
{
    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->app     = Container::get('app');
        $this->request = $this->app['request'];

        //TODO:x-2021-42 改版安装页面，修改触发条件 author:x
        //if (!cmf_is_installed() && $this->request->module() != 'install') {
        if (!cmf_is_installed() && $this->request->module() !='api' && $this->request->controller() !='Install') {
            if($this->request->module() == (config('database.admin_application')?:'admin')){ # 后台重定向
                echo json(['status'=>302,'msg'=>'系统未安装'],200);
            }else{ # 前台重定向
                //TODO:x-2021-42 改版安装页面，修改跳转路由
                //return redirect(cmf_get_root() . '/?s=install');
                return redirect(cmf_get_root() . '/install.html');
            }
        }

        $this->_initializeView();
        $this->view = View::init(Config::get('template.'));

        // 控制器初始化
        $this->initialize();

        // 前置操作方法 即将废弃
        foreach ((array)$this->beforeActionList as $method => $options) {
            is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
        }

        # 检验授权:仅在安装后检验,否则安装报错
        if (cmf_is_installed()){
            $zjmf_authorize = configuration('zjmf_authorize');
            if(empty($zjmf_authorize)){
                \compareLicense();
            }else{
                $auth = \de_authorize($zjmf_authorize);
                $ip = \de_systemip(configuration('authsystemip'));
                if($ip!=$auth['ip'] && !empty($ip)){
                    return jsonrule(['status' => 307, 'msg' => '授权错误,请检查ip']);
                }
                if(time()>$auth['last_license_time']+7*24*3600 || ltrim(str_replace('https://','',str_replace('http://','',$auth['domain'])),'www.')!=ltrim(str_replace('https://','',str_replace('http://','',$_SERVER['HTTP_HOST'])),'www.') || $auth['installation_path']!=CMF_ROOT || $auth['license']!=configuration('system_license')){
                    return json(['status' => 307, 'msg' => '授权错误,请检查域名或ip']);
                }
                if(!empty($auth['facetoken'])){
                    return json(['status' => 307, 'msg' => '您的授权已被暂停,请前往智简魔方会员中心检查授权状态']);
                }
                if($auth['status']=='Suspend'){
                    return json(['status' => 307, 'msg' => '您的授权已被暂停,请前往智简魔方会员中心检查授权状态']);
                }
            }
        }
    }


    // 初始化视图配置
    protected function _initializeView()
    {
    }

    /**
     *  排序 排序字段为list_orders数组 POST 排序字段为：list_order
     */
    protected function listOrders($model)
    {
        $modelName = '';
        if (is_object($model)) {
            $modelName = $model->getName();
        } else {
            $modelName = $model;
        }

        $pk  = Db::name($modelName)->getPk(); //获取主键名称
        $ids = $this->request->post("list_orders/a");

        if (!empty($ids)) {
            foreach ($ids as $key => $r) {
                $data['list_order'] = $r;
                Db::name($modelName)->where($pk, $key)->update($data);
            }
        }

        return true;
    }

}