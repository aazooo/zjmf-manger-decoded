<?php

namespace gateways\epay_wxpay\controller;

use app\home\controller\OrderController;
use think\Controller;
use gateways\epay_wxpay\EpayWxpayPlugin;
use gateways\epay_wxpay\lib\EpayCore;

class IndexController extends Controller
{

    /**
     * 异步回调
     */
    public function notifyHandle()
    {
        $class = new EpayWxpayPlugin();
        $config = $class->config();
		$epayNotify = new EpayCore($config);
		$verify_result = $epayNotify->verifyNotify();
		if($verify_result){
			if ($_GET['trade_status'] == 'TRADE_SUCCESS'){
				echo "success";

				$data = array(
					'invoice_id' =>  $_GET['out_trade_no'],
					'trans_id' => $_GET['trade_no'],
					'currency' => "CNY",
					'payment' => $class->info['name'],
					'amount_in' =>  $_GET['money'],
					'paid_time' => date('Y-m-d H:i:s'),			
				 );

				 $Order = new OrderController();
				 $Order->orderPayHandle($data);

				 return;
			}
		}
		echo "fail";
    }

    /**
     * 同步回调
     */
    public function returnHandle()
    {
		$class = new EpayWxpayPlugin();
        $config = $class->config();
		$epayNotify = new EpayCore($config);
		$verify_result = $epayNotify->verifyReturn();
		if($verify_result){
			if ($_GET['trade_status'] == 'TRADE_SUCCESS'){
				$data = array(
					'invoice_id' =>  $_GET['out_trade_no'],
					'trans_id' => $_GET['trade_no'],
					'currency' => "CNY",
					'payment' => $class->info['name'],
					'amount_in' =>  $_GET['money'],
					'paid_time' => date('Y-m-d H:i:s'),			
				 );

				 $Order = new OrderController();
				 $Order->orderPayHandle($data);

				 return redirect(config('return_url'));
			}
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