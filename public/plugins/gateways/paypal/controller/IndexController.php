<?php
namespace gateways\paypal\controller;

use app\home\controller\OrderController;
use think\Controller;
use \PayPal\Auth\OAuthTokenCredential;
use \PayPal\Rest\ApiContext;
use \PayPal\Api\Payment;
use \PayPal\Api\PaymentExecution;

class IndexController extends Controller
{
    /**
     * 异步回调
     */
    public function notify_handle()
    {
        $arr = $this->request->param();
        trace('回调开始标记_input:'.json_encode($arr),'info_paypal_notice_log');
        if(!isset($arr['token'],$arr['paymentId'], $arr['PayerID'])){
            die('fail');
        }
        $paymentID = $arr['paymentId'];
        $payerId = $arr['PayerID'];

        $config = ConfigController::getConfig();
        $paypal = new ApiContext(new OAuthTokenCredential($config['clientId'],$config['clientSecret']));
        $paypal->setConfig(array('mode' => $config['mode']));
        $payment = Payment::get($paymentID, $paypal);

        $execute = new PaymentExecution();
        $execute->setPayerId($payerId);
        try{
            $result = $payment->execute($execute, $paypal);
            trace('回调结束标记_input:' . json_encode(json_decode($result,true)),'info_paypal_notice_log');
            if ($result && isset ( $result->state ) && $result->state == 'approved') {
                $data = json_decode($result,true)['transactions'][0];
                $up_data = [];
                $up_data['invoice_id'] = $data['invoice_number']; //账单ID
                $up_data['amount_in'] = $data['amount']['total']??0;//账单总价
                $up_data['trans_id'] = $data['related_resources'][0]['sale']['id']??'';//交易流水号
                $up_data['currency'] = $data['amount']['currency'] ?? 'CNY';//货币
                $up_data['paid_time'] = $data['related_resources'][0]['sale']['create_time']??date('Y-m-d H:i:s');//支付时间
                $up_data['payment'] = 'Paypal';//支付网关名称
                //> 支付成功 回调处理订单
                $Order = new OrderController();
                $Order->orderPayHandle($up_data);
                return redirect(config('return_url'));
            }else{
                echo "fail";
            }
        }catch(\Exception $e){
            die($e);
        }
        return redirect(config('return_url'));
    }

    /**
     * 同步回调
     */
    public function return_handle()
    {
        return redirect(config('return_url'));
    }

    public function cancel_handle()
    {
        //echo "取消支付";
        return redirect(config('return_url'));
    }
}