<?php

namespace gateways\wx_pay\controller;


use gateways\wx_pay\behavior\ConfigController;
use gateways\wx_pay\lib\WxPayApi;
use gateways\wx_pay\lib\WxPayNotify;
use app\home\controller\OrderController;
use gateways\wx_pay\lib\WxPayOrderQuery;
use think\facade\Log;

class NotifyController extends WxPayNotify
{

    private $config = null;

    //查询订单
    public function queryOrder($transaction_id,$config=[])
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($config, $input);
//        Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }
    /**
     *
     * 回包前的回调方法
     * 业务可以继承该方法，打印日志方便定位
     * @param string $xmlData 返回的xml参数
     *
     **/
    public function LogAfterProcess($xmlData)
    {
        Log::ERROR("call back， return xml:" . $xmlData);
        return;
    }
    //重写回调处理函数
    /**
     * @param WxPayNotifyResults $data 回调解释出的参数
     * @param WxPayConfigInterface $config
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    public function NotifyProcess($objData, $config, &$msg)
    {

        $data = $objData->GetValues();

        $this->queryOrder($data["transaction_id"], $config);
        trace('记录的NotifyProcess:start1:' . json_encode($data), 'info');
        //> 1、进行参数校验
        if (!array_key_exists("return_code", $data)
            || (array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            return false;
        }
        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //> 2、进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if ($checkResult == false) {
                //签名错误
                \Log::ERROR("签名错误...");
                return false;
            }

        } catch (\Exception $e) {
            \Log::ERROR($e->getMessage());
        }
        //> 3、处理业务逻辑
        //查询订单，判断订单真实性
        if (!$this->queryOrder($data["transaction_id"], $config)) {
                $msg = "订单查询失败";
                return false;
            }
        //>: 是否再次需要验证订单金额
        return $this->orderHandle($data);
    }

    //> 支付成功 处理订单
    /*private function orderHandle($data)
    {
        trace('ali_data_start'.json_encode($data));
        //> 暂不使用拼接数据（有的支付可能不支持）
//        $attach = $data['attach'];
//        $attachArr = explode('@', $attach);

        //> 货币转换
        $config = (new ConfigController())->getConfig();
        $userCurrency = get_user_currency($data['out_trade_no']);
        $convertTotal = convert_currency($data['total_amount'], $config['currency'], $userCurrency['id']);
        $invoice_id =  explode('shd_'.date('Ymd'),$data['out_trade_no'])[1];
        $newData =  array(
//            'invoice_type' => $attachArr[0], //>账单类型 ： 充值or产品开通or续费
//            'uid' => $attachArr[1],
            'invoice_id' => $invoice_id,
//            'attachMoney' => $attachArr[3] * 100,
            'payment' => 'WxPay',
            'paid_time' => $data['time_end'],
            'trans_id' => $data['transaction_id'], //> 平台交易id
            'total_fee' => $convertTotal,
            'currency' => $userCurrency['code'],
        );
        // 交易日志
        payment_log($invoice_id,$data['transaction_id'],'WxPay',$data['total_amount'],'success');
        return check_pay($newData);
    }*/
    //> 支付成功 处理订单
    private function orderHandle($data)
    {
        $Order = new OrderController();
        trace('Wx_data_start'.json_encode($data));

        $attach = $data['attach'];
        $attachArr = explode('@', $attach);
        $invoice_id =  explode('shd_'.date('Ymd'),$data['out_trade_no'])[1];
        $newData =  array(
            'invoice_type' => $attachArr[0], //>账单类型 ： 充值or产品开通or续费
            'uid' => $attachArr[1],
            'invoice_id' => $attachArr[2],
            'attachMoney' => $attachArr[3] * 100,
            'payment' => $attachArr[4],
            'paid_time' => $data['time_end'],
            'trans_id' => $data['transaction_id'], //> 平台交易id
            'total_fee' => $data['total_fee'],
            'currency' => $data['fee_type'],
        );
        payment_log($invoice_id,$data['fee_type'],$data['transaction_id'],'WxPay',$data['total_fee']/100,'success');

        $up_data = [];
        $up_data['invoice_id'] = $attachArr[2]; //账单ID
        $up_data['amount_in'] = $data['total_fee']/100;//账单总价
        $up_data['trans_id'] = $data['transaction_id'];//交易流水号
        $up_data['currency'] = $data['fee_type'] ?? 'CNY';//货币
        $up_data['paid_time'] = $data['time_end'];//支付时间
        $up_data['payment'] = 'WxPay';//支付网关名称
        $Order->orderPayHandle($up_data);

        //$Order->orderPayHandle($newData,'wx_pay');
//        $Order->addInvoicePayment($invoiceId,$trade_no,$paymentAmount,$feeAmount,$gatewaymodule);
    }
}