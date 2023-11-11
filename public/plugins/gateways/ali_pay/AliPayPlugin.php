<?php

namespace gateways\ali_pay;

use app\admin\lib\Plugin;
use gateways\ali_pay\lib\AlipayTradeService;

class AliPayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'AliPay',
        'title'       => '支付宝支付',
        'description' => '支付宝官方接口',
        'status'      => 1,
        'author'      => '顺戴网络',
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

    public function AliPayHandle($param)
    {
        $config = $this->config();

        if(in_array('wap', $config['product']) && $this->isMobile()){
            return $this->wapPay($config, $param);
        }elseif(in_array('pc', $config['product']) && !$this->isMobile()){
            return $this->pcPay($config, $param);
        }elseif(in_array('qr', $config['product'])){
            return $this->qrPay($config, $param);
        }else{
            throw new \Exception('未选择任何可用的支付产品');
        }
    }

    private function pcPay($config, $param)
    {
        $domain = configuration('domain');

        $config['notify_url'] = $domain . '/gateway/ali_pay/index/notifyHandle';
        $config['return_url'] = $domain . '/gateway/ali_pay/index/returnHandle';
        $bizContent = [
			'out_trade_no' => $param['out_trade_no'],
			'total_amount' => $param['total_fee'],
			'subject' => $param['product_name'],
		];
		$bizContent['business_params'] = ['mc_create_trade_ip' => get_client_ip()];

        try{
			$aop = new AlipayTradeService($config);
			$url = $aop->pagePay($bizContent);
		}catch(\Exception $e){
            throw new \Exception('支付宝下单失败:'.$e->getMessage());
		}

        $reData = ['type' => 'jump', 'data' => $url];
        return $reData;
    }

    private function wapPay($config, $param)
    {
        $domain = configuration('domain');

        $config['notify_url'] = $domain . '/gateway/ali_pay/index/notifyHandle';
        $config['return_url'] = $domain . '/gateway/ali_pay/index/returnHandle';
        $bizContent = [
			'out_trade_no' => $param['out_trade_no'],
			'total_amount' => $param['total_fee'],
			'subject' => $param['product_name'],
		];
		$bizContent['business_params'] = ['mc_create_trade_ip' => get_client_ip()];

        try{
			$aop = new AlipayTradeService($config);
			$url = $aop->wapPay($bizContent);
		}catch(\Exception $e){
            throw new \Exception('支付宝下单失败:'.$e->getMessage());
		}

        $reData = ['type' => 'jump', 'data' => $url];
        return $reData;
    }

    private function qrPay($config, $param)
    {
        $domain = configuration('domain');

        $config['notify_url'] = $domain . '/gateway/ali_pay/index/notifyHandle';
        $bizContent = [
			'out_trade_no' => $param['out_trade_no'],
			'total_amount' => $param['total_fee'],
			'subject' => $param['product_name'],
		];
		$bizContent['business_params'] = ['mc_create_trade_ip' => get_client_ip()];

        try{
			$aop = new AlipayTradeService($config);
			$result = $aop->qrPay($bizContent);
		}catch(\Exception $e){
            throw new \Exception('支付宝下单失败:'.$e->getMessage());
		}
		$code_url = $result['qr_code'];

        $reData = ['type' => 'url', 'data' => $code_url];
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

        $product = [];
        if($config['product_pc'] == 1) $product[] = 'pc';
        if($config['product_wap'] == 1) $product[] = 'wap';
        if($config['product_qr'] == 1) $product[] = 'qr';

        return [
            'app_id' => $config['app_id'],
            'alipay_public_key' => $config['alipay_public_key'],
            'app_private_key' => $config['merchant_private_key'],
            'pageMethod' => '1',
            'product' => $product
        ];
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
