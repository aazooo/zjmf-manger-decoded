<?php
namespace certification\ali\logic;

use certification\ali\AliPlugin;
require_once dirname(__DIR__).'/vendor/alipay/lib/aop/AopClient.php';
require_once dirname(__DIR__).'/vendor/alipay/lib/request/AlipayUserCertifyOpenQueryRequest.php';
require_once dirname(__DIR__).'/vendor/alipay/lib/request/AlipayUserCertifyOpenInitializeRequest.php';
require_once dirname(__DIR__).'/vendor/alipay/lib/request/AlipayUserCertifyOpenCertifyRequest.php';

class Ali
{
    private $aop;

    public $_config;

    public function __construct()
    {
        if (file_exists(dirname(__DIR__).'/config/config.php')){
            $con = require dirname(__DIR__).'/config/config.php';
        }else{
            $con = [];
        }
        $config = (new AliPlugin())->getConfig();
        $this->_config = array_merge($con,$config);
        $this->aop = new \AopClient();
        $this->aop->gatewayUrl = $this->_config['gateway_url'];
        $this->aop->appId = $this->_config['app_id'];
        $this->aop->rsaPrivateKey = str_replace(PHP_EOL, '', $this->_config['private_key']);//这个获取格式有问题,需要去除换行
        $this->aop->alipayrsaPublicKey = $this->_config['public_key'];
        $this->aop->apiVersion = $this->_config['api_version'];
        $this->aop->signType = $this->_config['sign_type'];
        $this->aop->postCharset = $this->_config['post_charset'];
        $this->aop->format = $this->_config['format'];
    }

    //实名认证初始化，获取certify_id
    public function getCertifyId($realname,$idcard)
    {
        $request = new \AlipayUserCertifyOpenInitializeRequest ();
        $bizContent['outer_order_no'] = "ZGYD20180913232" . time();
        $bizContent['biz_code'] = $this->_config['biz_code'];
        $bizContent['identity_param'] = [
            "identity_type" => "CERT_INFO",
            "cert_type" => "IDENTITY_CARD",
            "cert_name" => $realname,
            "cert_no" => $idcard,
        ];
        $bizContent['merchant_config'] =  array("return_url" => $this->_config['return_url']);
        $request->setBizContent( json_encode($bizContent) );
        try{
            $result = $this->aop->execute ( $request);
        } catch (\Exception $e) {
            return array("status" => 400, "msg" => $e->getMessage());
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $certify_id = $result->$responseNode->certify_id;
            $output['status'] = 200;
            $output['msg'] = '请求成功';
            $output['certify_id'] = $certify_id;
            return $output;
        } else {
            $output['status'] = 400;
            $output['msg'] = $result->$responseNode->sub_msg;
            return $output;
        }
    }

    # 通过获取的 certify_id 写入一个可手机访问调用文件
    public function generateScanForm($certify_id)
    {
        $request = new \AlipayUserCertifyOpenCertifyRequest ();
        $bizContent['certify_id'] = $certify_id;
        $request->setBizContent( json_encode($bizContent) );
        $result = $this->aop->pageExecute( $request,'GET');
        $jsonMsg['status'] = 200;
        $jsonMsg['msg'] = "请使用支付宝扫描二维码";
        $jsonMsg['url'] = $result;
        return $jsonMsg;
    }

    //查询实名认证结果
    public function getAliyunAuthStatus($certify_id)
    {
        $request = new \AlipayUserCertifyOpenQueryRequest();
        $bizContent['certify_id'] = $certify_id;
        $request->setBizContent( json_encode($bizContent) );
        $result = $this->aop->execute ( $request );
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $status = 4; # 已提交资料
        if(!empty($resultCode)&&$resultCode == 10000){
            $passed = $result->$responseNode->passed;
            if ($passed === 'T'){
                $status = 1; # 通过
                $msg = '审核通过';
            }
            if ($passed === 'F'){
                $msg = '阿里审核未通过';
                $status = 2; # 未通过
            }
        }else{
            $msg = $result->$responseNode->msg;
            $status = 2;
        }
        return ['status'=>$status,'msg'=>$msg];
    }

}