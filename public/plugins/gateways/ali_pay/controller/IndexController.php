<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

namespace gateways\ali_pay\controller;

//Demo插件英文名，改成你的插件英文就行了

use app\home\controller\OrderController;
use think\Controller;
use gateways\ali_pay\pagepay\service\AlipayTradeService;
use think\Db;

/**
 * Class IndexController.
 */
class IndexController extends Controller
{

    /**
     * 异步回调
     */
    public function notify_handle()
    {
        $arr = $_POST;
        trace('回调开始标记_input:'.json_encode(file_get_contents('php://input')),'info_ali_notice_log');
        $config = ConfigController::getConfig();
        //> 校验
        //> 1.商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        //> 2.判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        # TODO wyh 20201029
        //$arr['out_trade_no'] = intval(explode('b',$arr['out_trade_no'])[0]);
        $where = array(
            'id' => $arr['out_trade_no'],
            //'total' => $arr['total_amount'],
        );
        if (!db('invoices')->where($where)->find()) {
            echo 'fail';
            exit;
        }
        //3.校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        /*if ($arr['seller_id'] != $config['seller_id']){
            echo 'fail';
            exit;
        }*/

        //> 4.验证app_id是否为该商户本身，
        if ($arr['app_id'] != $config['app_id']) {
            echo 'fail';
            exit;
        }

        //trace('验证标记点1_post:'.json_encode($config),'info_ali_notice_log');
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($result) {
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            } elseif ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //> 逻辑处理
                $this->orderHandle($_POST);


            }
            echo "success";    //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }

    }

    //> 支付成功 回调处理订单
    private function orderHandle($data)
    {
        //$data['out_trade_no'] = intval(explode('a',$data['out_trade_no'])[0]);
        //trace('ali_data_start'.json_encode($data,true));
        $up_data = [];
        $up_data['invoice_id'] = $data['out_trade_no']; //账单ID
        $up_data['amount_in'] = $data['total_amount'];//账单总价
        $up_data['trans_id'] = $data['trade_no'];//交易流水号
        $up_data['currency'] = $data['currency'] ?? 'CNY';//货币
        $up_data['paid_time'] = $data['gmt_payment'];//支付时间
        $up_data['payment'] = 'AliPay';//支付网关名称
        $Order = new OrderController();
        $Order->orderPayHandle($up_data);

    }

    /**
     * 同步回调
     */
    public function return_handle()
    {
        return redirect(config('return_url'));
        if($this->aliCheck($_GET)){
            return json(['status'=>200,'msg'=>'支付成功']);
        }else{
            return json(['status'=>400,'msg'=>'支付失败']);
        }
    }


    /**
     * 验证签名
     */
    function aliCheck($params)
    {
        require dirname(__DIR__) . '/aop/AopClient.php';
        $aop = new \AopClient;

        $config = ConfigController::getConfig();
        $aop->alipayrsaPublicKey = $config["alipay_public_key"];
        $flag = $aop->rsaCheckV1($params, NULL, "RSA2");
        return $flag;
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

    /*
     *  支付宝订单查询
     *
     */
    public function check_order()
    {
        require dirname(__DIR__) . '/aop/AopClient.php';
        require dirname(__DIR__) . "/aop/request/AlipayTradeQueryRequest.php";
        $params = $this->request->param();
        $invoice_id = intval($params['invoice_id']);
        if (!$invoice_id){
            throw new \Exception(lang('ID_ERROR'));
        }
        if (!Db::name('invoices')->where('id',$invoice_id)->count()){
            throw new \Exception(lang('账单不存在'));
        }
        //Db::name('accounts')->where('')
        $config = ConfigController::getConfig();
        $aop = new \AopClient();
        $aop->gatewayUrl = $config['gatewayUrl'];
        $aop->appId = $config['app_id'];
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->alipayrsaPublicKey=$config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = $config['sign_type'];
        $aop->postCharset=$config['charset'];
        $aop->format='json';
        $request = new \AlipayTradeQueryRequest ();
        $data = [
            'out_trade_no' => $invoice_id
        ];
        $request->setBizContent(json_encode($data));
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $tradeStatus = $result->$responseNode->trade_status;
        if(!empty($resultCode)){
            if ($resultCode == 10000){
                if ($tradeStatus == 'TRADE_SUCCESS'){
                    return json(['status' => 10000 , 'trade_status' => 'TRADE_SUCCESS']);
                }else{
                    return json(['status' => 10000 , 'trade_status' => 'TRADE_FAIL']);
                }
            }else{
                return json(['status' => 20000 , 'trade_status' => '正在请求接口']);
            }
        } else {
            throw new \Exception(lang('账单不存在'));
        }
    }

}