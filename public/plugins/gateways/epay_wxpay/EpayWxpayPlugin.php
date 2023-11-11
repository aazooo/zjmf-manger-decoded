<?php

namespace gateways\epay_wxpay;

use app\admin\lib\Plugin;
use gateways\epay_wxpay\lib\EpayCore;
use think\Db;

class EpayWxpayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'EpayWxpay',
        'title'       => '微信支付',
        'description' => '易支付-微信支付',
        'status'      => 1,
        'author'      => '白猿科技',
        'version'     => '1.0',
        'module'        => 'gateways',
    );

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    public function EpayWxpayHandle($param)
    {
		$config = $this->config();
		$domain = configuration('domain');
		$parameter = array(
			"pid" => trim($config['pid']),
			"type" => 'wxpay',
			"notify_url"	=> $domain."/gateway/epay_wxpay/index/notifyHandle",
			"return_url"	=> $domain."/gateway/epay_wxpay/index/returnHandle",
			"out_trade_no"	=> $param['out_trade_no'],
			"name"	=> $param['product_name'],
			"money"	=> $param['total_fee']
		);
		$epaySubmit = new EpayCore($config);
        $url = $epaySubmit->getPayLink($parameter);

		$reData = array(
            'type' => 'jump',
            'data' => $url,
        );
		return $reData;
    }

	public function config()
    {
        $name = $this->info['name'];

        $config = db('plugin')->where('name', $name)->value('config');
        if (!empty($config) && $config != "null") {
            $config = json_decode($config, true);
        } else {
            return json(['msg'=>'请先将配置好商户信息','status'=>400]);
        }
		$config['sign_type'] = 'MD5';
		$config['input_charset'] = 'utf-8';
        return $config;
    }

}