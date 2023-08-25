<?php
namespace addons\demo_style\controller;

use app\admin\controller\PluginAdminBaseController;

/*
 *  @author 顺戴
 *  继承app\admin\controller\PluginAdminBaseController;
 *  后台基础设置
 */
class AdminIndexController extends PluginAdminBaseController
{
    # 配置
    private $_config = [];

    private $lang;

    public function initialize()
    {
        parent::initialize();
        if (file_exists(dirname(__DIR__).'/config/config.php')){
            $con = require dirname(__DIR__).'/config/config.php';
        }else{
            $con = [];
        }

        $this->_config = array_merge($con,$this->getPlugin()->getConfig());

        $lang = request()->languagesys;
        if (empty($lang)){
            $lang=configuration("language")?configuration("language"):config("default_lang");
        }
        if ($lang == 'CN'){
            $lang = 'chinese';
        }elseif ($lang == 'US'){
            $lang = 'english';
        }elseif ($lang == 'HK'){
            $lang = 'chinese_tw';
        }
        $this->lang = $lang;
    }

    public function addhelp()
    {
        # 自定义title 处理多语言
        if ($this->lang == 'chinese'){
            $title = 'Demo样式1';
        }elseif ($this->lang == 'english'){
            $title = 'Demo Style 1';
        }elseif ($this->lang == 'chinese_tw'){
            $title = 'Demo樣式1';
        }
        $this->assign('Title',$title);
        return $this->fetch('/addhelp');
    }

    public function customerdetail1()
    {
        $this->assign('Title','Demo样式2');
        return $this->fetch('/customerdetail1');
    }

    public function customerdetail2()
    {
        $this->assign('Title','Demo样式3');
        return $this->fetch('/customerdetail2');
    }

    public function customerdetail3()
    {
        $this->assign('Title','Demo样式4');
        return $this->fetch('/customerdetail3');
    }

    public function customerdetail4()
    {
        $this->assign('Title','Demo样式5');
        return $this->fetch('/customerdetail4');
    }

    public function helplist()
    {
        $this->assign('Title','Demo样式6');
        return $this->fetch('/helplist');
    }
}