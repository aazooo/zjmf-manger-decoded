<?php

namespace gateways\hpj_alipay_pay;

use app\admin\lib\Plugin;
use gateways\hpj_alipay_pay\lib\XunhupayClient;

class HpjAlipayPayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'HpjAlipayPay',
        'title'       => '支付宝支付',
        'description' => '虎皮椒-支付宝支付',
        'status'      => 1,
        'author'      => '虎皮椒',
        'version'     => '1.0',
        'module'        => 'gateways',
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

    public function HpjAlipayPayHandle($param)
    {
        $config = $this->config();
        $domain = configuration('domain');

        $param = [
            'version'   => '1.1',
            'trade_order_id'=> $param['out_trade_no'],
            'payment'   => 'alipay',
            'total_fee' => $param['total_fee'],
            'title'     => $param['product_name'],
            'notify_url'=> $domain . '/gateway/hpj_alipay_pay/index/notifyHandle',
            'return_url'=> $domain . '/gateway/hpj_alipay_pay/index/returnHandle',
        ];

        $client = new XunhupayClient($config);
        try{
            $result = $client->do_payment($param);
        }catch(\Exception $e){
            throw new \Exception('支付宝下单失败:'.$e->getMessage());
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

}
