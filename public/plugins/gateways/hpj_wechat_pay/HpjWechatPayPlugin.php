<?php

namespace gateways\hpj_wechat_pay;

use app\admin\lib\Plugin;
use gateways\hpj_wechat_pay\lib\XunhupayClient;

class HpjWechatPayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'HpjWechatPay',
        'title'       => '微信支付',
        'description' => '虎皮椒-微信支付',
        'status'      => 1,
        'author'      => '虎皮椒',
        'version'     => '1.0',
        'module'      => 'gateways',
    );

    public $hasAdmin = 0;

    // 插件安装
    public function install()
    {
        return true;
    }

    // 插件卸载
    public function uninstall()
    {
        return true;
    }

    public function HpjWechatPayHandle($param)
    {
        $config = $this->config();
        $domain = configuration('domain');

        $param = [
            'version'   => '1.1',
            'trade_order_id'=> $param['out_trade_no'],
            'payment'   => 'wechat',
            'total_fee' => $param['total_fee'],
            'title'     => $param['product_name'],
            'notify_url'=> $domain . '/gateway/hpj_wechat_pay/index/notifyHandle',
            'return_url'=> $domain . '/gateway/hpj_wechat_pay/index/returnHandle',
        ];
        if($this->isMobile() && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')===false){
			$param['type'] = 'WAP';
			$param['wap_url'] = $domain;
			$param['wap_name'] = configuration('company_name');
		}

        $client = new XunhupayClient($config);
        try{
            $result = $client->do_payment($param);
        }catch(\Exception $e){
            throw new \Exception('微信支付下单失败:'.$e->getMessage());
        }

        $reData = array(
            'type' => 'jump',
            'data' => $result['url'],
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
        return $config;
    }

    private function isMobile()
    {
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $ualist = array('android', 'midp', 'nokia', 'mobile', 'iphone', 'ipod', 'blackberry', 'windows phone');
        foreach($ualist as $ua){
            if(strpos($useragent, $ua)!==false){
                return true;
            }
        }
        return false;
    }
}
