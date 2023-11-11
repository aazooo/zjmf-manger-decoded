<?php

namespace gateways\ali_pay\lib;

/**
 * 支付宝交易服务类
 */
class AlipayTradeService extends AlipayService
{

    /**
     * @param $config 支付宝配置信息
     */
    public function __construct($config)
    {
        parent::__construct($config);
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
    }

    /**
     * 付款码支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","open_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号"}
     * @see https://opendocs.alipay.com/open/02ekfp?ref=api&scene=32
     */
    public function scanPay($bizContent)
    {
        $apiName = 'alipay.trade.pay';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 扫码支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"out_trade_no":"商户订单号","qr_code":"二维码链接"}
     * @see https://opendocs.alipay.com/open/02ekfg?ref=api&scene=19
     */
    public function qrPay($bizContent)
    {
        $apiName = 'alipay.trade.precreate';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * JS支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号"}
     * @see https://opendocs.alipay.com/open/02ekfj?ref=api
     */
    public function jsPay($bizContent)
    {
        $apiName = 'alipay.trade.create';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * APP支付
     * @param $bizContent 请求参数的集合
     * @return string SDK请求串
     * @see https://opendocs.alipay.com/open/02e7gq?ref=api&scene=20
     */
    public function appPay($bizContent)
    {
        $apiName = 'alipay.trade.app.pay';
        return $this->aopSdkExecute($apiName, $bizContent);
    }

    /**
     * 电脑网站支付
     * @param $bizContent 请求参数的集合
     * @return string html表单
     * @see https://opendocs.alipay.com/open/028r8t?ref=api&scene=22
     */
    public function pagePay($bizContent)
    {
        $apiName = 'alipay.trade.page.pay';
        $bizContent['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * 手机网站支付
     * @param $bizContent 请求参数的集合
     * @return string html表单
     * @see https://opendocs.alipay.com/open/02ivbs?ref=api&scene=21
     */
    public function wapPay($bizContent)
    {
        $apiName = 'alipay.trade.wap.pay';
        $bizContent['product_code'] = 'QUICK_WAP_WAY';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * 交易查询
     * @param $trade_no 支付宝交易号
     * @param $out_trade_no 商户订单号
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","open_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号","trade_status":"TRADE_SUCCESS","total_amount":88.88}
     */
    public function query($trade_no = null, $out_trade_no = null)
    {
        $apiName = 'alipay.trade.query';
        $bizContent = [];
        if ($trade_no) {
            $bizContent['trade_no'] = $trade_no;
        }
        if ($out_trade_no) {
            $bizContent['out_trade_no'] = $out_trade_no;
        }
        return $this->aopExecute($apiName, $bizContent);
    }
    
    /**
     * 交易是否成功
     * @param $trade_no 支付宝交易号
     * @param $out_trade_no 商户订单号
     * @return bool
     */
    public function queryResult($trade_no = null, $out_trade_no = null)
    {
        $result = $this->query($trade_no, $out_trade_no);
        if (isset($result['code']) && $result['code'] == '10000') {
            if ($result['trade_status'] == 'TRADE_SUCCESS' || $result['trade_status'] == 'TRADE_FINISHED' || $result['trade_status'] == 'TRADE_CLOSED') {
                return true;
            }
        }
        return false;
    }

    /**
     * 交易退款
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","buyer_user_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号","fund_change":"Y","refund_fee":88.88}
     * @see https://opendocs.alipay.com/open/02ekfk?ref=api
     */
    public function refund($bizContent)
    {
        $apiName = 'alipay.trade.refund';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易退款查询
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","out_request_no":"退款请求号","refund_status":"REFUND_SUCCESS","total_amount":88.88,"refund_amount":88.88}
     */
    public function refundQuery($bizContent)
    {
        $apiName = 'alipay.trade.fastpay.refund.query';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易撤销
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","retry_flag":"N是否需要重试","action":"close本次撤销触发的交易动作"}
     */
    public function cancel($bizContent)
    {
        $apiName = 'alipay.trade.cancel';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易关闭
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号"}
     */
    public function close($bizContent)
    {
        $apiName = 'alipay.trade.close';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 查询对账单下载地址
     * @param $bizContent 请求参数的集合
     * @return mixed {"bill_download_url":"账单下载地址"}
     */
    public function downloadurlQuery($bizContent)
    {
        $apiName = 'alipay.data.dataservice.bill.downloadurl.query';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 支付回调验签
     * @param $params 支付宝返回的信息
     * @return bool
     */
    public function check($params){
        $result = $this->client->verify($params);
        if($result){
            $result = $this->queryResult($params['trade_no']);
        }
        return $result;
    }

}