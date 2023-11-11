<?php

namespace gateways\wx_pay;

use app\admin\lib\Plugin;
use gateways\wx_pay\lib\PaymentService;

class WxPayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'WxPay',
        'title'       => '微信支付',
        'description' => '微信支付官方接口',
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

    public function WxPayHandle($param)
    {
        $config = $this->config();

        if(in_array('wap', $config['product']) && $this->isMobile() && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')===false){
            return $this->wapPay($config, $param);
        }elseif(in_array('native', $config['product'])){
            return $this->nativePay($config, $param);
        }elseif(in_array('jsapi', $config['product'])){
            return $this->jsapiPay($config, $param);
        }else{
            throw new \Exception('未选择任何可用的支付产品');
        }
    }

    private function nativePay($config, $param){
        
        $domain = configuration('domain');

        $out_trade_no = date('Ymd').$param['out_trade_no'];

        $params = [
            'body' => $param['product_name'],
            'out_trade_no' => $out_trade_no,
            'total_fee' => strval($param['total_fee']*100),
            'spbill_create_ip' => get_client_ip(),
            'notify_url' => $domain . '/gateway/wx_pay/index/notifyHandle',
            'product_id' => '01001',
        ];
        $client = new PaymentService($config);
        try{
            $result = $client->nativePay($params);
            $code_url = $result['code_url'];
        }catch(\Exception $e){
            throw new \Exception('微信支付下单失败:'.$e->getMessage());
        }

        $reData = ['type' => 'url', 'data' => $code_url];
        return $reData;
    }

    private function jsapiPay($config, $param){
        cache('wxjspay_'.$param['out_trade_no'], $param, 600);

        $domain = configuration('domain');
        $url = $domain . '/gateway/wx_pay/index/jsapipay?orderid='.$param['out_trade_no'];

        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
            $reData = ['type' => 'jump', 'data' => $url];
        }else{
            $reData = ['type' => 'url', 'data' => $url];
        }
        return $reData;
    }

    private function wapPay($config, $param){
        
        $domain = configuration('domain');

        $out_trade_no = date('Ymd').$param['out_trade_no'];

        $scene_info = [
            'h5_info' => [
                'type' => 'Wap',
                'wap_url' => $domain,
                'wap_name' => configuration('company_name')
            ]
        ];
        $params = [
            'body' => $param['product_name'],
            'out_trade_no' => $out_trade_no,
            'total_fee' => strval($param['total_fee']*100),
            'spbill_create_ip' => get_client_ip(),
            'notify_url' => $domain . '/gateway/wx_pay/index/notifyHandle',
            'scene_info' => json_encode($scene_info, JSON_UNESCAPED_UNICODE),
        ];
        $client = new PaymentService($config);
        try{
            $result = $client->h5Pay($params);
            $redirect_url = $domain . '/gateway/wx_pay/index/returnHandle';
            $url = $result['mweb_url'].'&redirect_url='.urlencode($redirect_url);
        }catch(\Exception $e){
            throw new \Exception('微信支付下单失败:'.$e->getMessage());
        }

        $reData = ['type' => 'jump', 'data' => $url];
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
        if($config['ProductNative'] == 1) $product[] = 'native';
        if($config['ProductJsapi'] == 1) $product[] = 'jsapi';
        if($config['ProductWap'] == 1) $product[] = 'wap';
        
        return [
            'appid' => $config['AppId'],
            'mchid' => $config['MerchantId'],
            'apikey' => $config['Key'],
            'appsecret' => $config['AppSecret'],
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
