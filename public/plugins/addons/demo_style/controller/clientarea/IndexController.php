<?php
namespace addons\demo_style\controller\clientarea;

use app\home\controller\PluginHomeBaseController;

/*
 *  @author 顺戴
 *  继承app\home\controller\PluginHomeBaseController;
 */
class IndexController extends PluginHomeBaseController
{
    public function addhelp()
    {
        # 自定义title
        $this->assign('Title','Demo样式1');
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