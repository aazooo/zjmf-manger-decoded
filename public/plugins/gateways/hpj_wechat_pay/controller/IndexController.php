<?php

namespace gateways\hpj_wechat_pay\controller;

use app\home\controller\OrderController;
use think\Controller;
use gateways\hpj_wechat_pay\HpjWechatPayPlugin;
use gateways\hpj_wechat_pay\lib\XunhupayClient;

class IndexController extends Controller
{

    /**
     * 异步回调
     */
    public function notifyHandle()
    {
        if(!isset($_POST) || !isset($_POST['hash']) || !isset($_POST['trade_order_id'])) {
            echo 'data_fail';
            exit;
        }

        $class = new HpjWechatPayPlugin();
        $config = $class->config();
        $client = new XunhupayClient($config);
        $verify_result = $client->verify($_POST);
        if(!$verify_result) {
            echo 'sign_fail';
            exit;
        }
        
        if($_POST['status']=='OD'){
            echo "success";

            $data = array(
                'invoice_id' =>  $_POST['trade_order_id'],
                'trans_id' => $_POST['transaction_id'],
                'currency' => "CNY",
                'payment' => $class->info['name'],
                'amount_in' =>  $_POST['total_fee'],
                'paid_time' => date('Y-m-d H:i:s'),
            );

            $Order = new OrderController();
            $Order->orderPayHandle($data);

            exit;
        }
        echo 'fail';
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