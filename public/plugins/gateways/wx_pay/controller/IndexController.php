<?php

namespace gateways\wx_pay\controller;

use app\home\controller\OrderController;
use think\Controller;
use gateways\wx_pay\WxPayPlugin;
use gateways\wx_pay\lib\PaymentService;
use gateways\wx_pay\lib\JsApiTool;

class IndexController extends Controller
{

    /**
     * 微信JSAPI支付
     */
    public function jsapipay()
    {
        $orderid = input('orderid');
        if(!$orderid) return $this->showerrmsg('no orderid');
        $param = cache('wxjspay_'.$orderid);
        if(!$param) return $this->showerrmsg('订单不存在或已超时');

        $class = new WxPayPlugin();
        $config = $class->config();

        try{
			$tools = new JsApiTool($config['appid'], $config['appsecret']);
			$openid = $tools->GetOpenid();
		}catch(\Exception $e){
            return $this->showerrmsg($e->getMessage());
		}

        $domain = configuration('domain');

        $out_trade_no = date('Ymd').$param['out_trade_no'];

        $params = [
            'body' => $param['product_name'],
            'out_trade_no' => $out_trade_no,
            'total_fee' => strval($param['total_fee']*100),
            'spbill_create_ip' => get_client_ip(),
            'notify_url' => $domain . '/gateway/wx_pay/index/notifyHandle',
            'openid' => $openid,
        ];
        $client = new PaymentService($config);
        try{
            $result = $client->jsapiPay($params);
            $jsapidata = json_encode($result);
        }catch(\Exception $e){
            return $this->showerrmsg('微信支付下单失败:'.$e->getMessage());
        }

        $this->assign('company_name', configuration('company_name'));
        $this->assign('jsapidata', $jsapidata);
        $tpl_path = CMF_ROOT.'public/plugins/gateways/wx_pay/template/jsapipay.tpl';
        return $this->fetch($tpl_path);
    }

    private function showerrmsg($msg){
        $this->assign('company_name', configuration('company_name'));
        $this->assign('msg', $msg);
        $tpl_path = CMF_ROOT.'public/plugins/gateways/wx_pay/template/errmsg.tpl';
        return $this->fetch($tpl_path);
    }

    /**
     * 异步回调
     */
    public function notifyHandle()
    {
        $isSuccess = true;
        $class = new WxPayPlugin();
        $config = $class->config();
		try{
			$client = new PaymentService($config);
			$result = $client->notify();

            $invoice_id = substr($result['out_trade_no'], 8);
            $data = array(
                'invoice_id' => $invoice_id,
                'trans_id' => $result['transaction_id'],
                'currency' => $result['fee_type'] ?? 'CNY',
                'payment' => $class->info['name'],
                'amount_in' => $result['total_fee']/100,
                'paid_time' => $result['time_end'],
            );

            $Order = new OrderController();
            $Order->orderPayHandle($data);

            if(cache('wxjspay_'.$invoice_id)){
                cache('wxjspay_'.$invoice_id, null);
            }

		}catch(Exception $e){
			$isSuccess = false;
			$errmsg = $e->getMessage();
		}

		$client->replyNotify($isSuccess, $errmsg);
        exit;
    }

    /**
     * 同步回调
     */
    public function returnHandle()
    {
        return redirect(config('return_url'));
    }

    /**
     * 获取支付信息
     */
    public function getPayment($payCode)
    {
        $payment = $this->where("enabled=1 AND payCode='$payCode' AND isOnline=1")->find();
        $payConfig = json_decode($payment["payConfig"]);
        foreach ($payConfig as $key => $value) {
            $payment[$key] = $value;
        }
        return $payment;
    }

}