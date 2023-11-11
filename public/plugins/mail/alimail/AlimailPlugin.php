<?php
namespace mail\alimail;

use app\admin\lib\Plugin;
use mail\alimail\lib\AliyunDm;

class AlimailPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Alimail',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '阿里云',
        'description' => '阿里云',
        'status'      => 1,
        'author'      => '智简魔方',
        'version'     => '1.0',
        'help_url'    => 'https://help.aliyun.com/product/29412.html',//申请接口地址
    );

    const ATTACHMENTS_ADDRESS = './upload/common/email/';

    private $isDebug = 0;

    # 插件安装
    public function install()
    {
		return true;
    }

    # 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    public function send($params)
    {
        $mail = new AliyunDm($params['config']['accessKeyId'], $params['config']['accessKeySecret']);
		$result = $mail->send($params['email'], $params['subject'], $params['content'], $params['config']['accountName'], $params['config']['fromAlias']);
        if($result === true){
            return ['status' => 'success'];
        }else{
            return ['status' => 'error', 'msg' => $result];
        }
    }
}