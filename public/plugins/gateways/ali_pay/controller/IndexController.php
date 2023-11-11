<?php

namespace gateways\ali_pay\controller;

use app\home\controller\OrderController;
use think\Controller;
use gateways\ali_pay\AliPayPlugin;
use gateways\ali_pay\lib\AlipayTradeService;

class IndexController extends Controller
{

    /**
     * 异步回调
     */
    public function notifyHandle()
    {
        $class = new AliPayPlugin();
        $config = $class->config();

        $aop = new AlipayTradeService($config);
        $verify_result = $aop->check($_POST);
        if($verify_result){
            echo "success";

            $data = array(
                'invoice_id' => $_POST['out_trade_no'],
                'trans_id' => $_POST['trade_no'],
                'currency' => $_POST['currency'] ?? 'CNY',
                'payment' => $class->info['name'],
                'amount_in' =>  $_POST['total_amount'],
                'paid_time' => $_POST['gmt_payment'],
            );

            $Order = new OrderController();
            $Order->orderPayHandle($data);
        }else{
            echo 'fail';
        }
        exit;
    }

    /**
     * 同步回调
     */
    public function returnHandle()
    {
        $class = new AliPayPlugin();
        $config = $class->config();

        $aop = new AlipayTradeService($config);
        $verify_result = $aop->check($_GET);
        if($verify_result){
            return redirect(config('return_url'));
        }else{
            return '签名验证失败';
        }
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