<?php
namespace certification\idcsmartali\logic;
use certification\idcsmartali\IdcsmartaliPlugin;
class Idcsmartali
{
    public $_config;

    public function __construct()
    {
        if (file_exists(dirname(__DIR__).'/config/config.php')){
            $con = require dirname(__DIR__).'/config/config.php';
        }else{
            $con = [];
        }
        $config = (new IdcsmartaliPlugin())->getConfig();
        $this->_config = array_merge($con,$config);
        
    }

    //实名认证初始化，获取certify_id
    public function getCertifyId($realname,$idcard,$cert_type)
    {
        $params['outer_order_no'] = "ZGYD20180913232" . time();
        $params['biz_code'] = $this->_config['biz_code'];
        $params['cert_type'] = $cert_type;
        $params['cert_name'] = $realname;
        $params['cert_no'] = $idcard;
        $params['return_url'] = $this->_config['return_url'];
		//var_dump($params);exit;
		$result=$this->APIHttpRequestCURL("initialize",$params,$this->_config);
        if($result['status']==200){
            $output['status'] = 200;
            $output['msg'] = '请求成功';
            $output['certify_id'] = $result['certify_id'];
            return $output;
        } else {
            $output['status'] = 400;
            $output['msg'] = $result['msg'];
            return $output;
        }
    }

    # 通过获取的 certify_id 写入一个可手机访问调用文件
    public function generateScanForm($certify_id)
    {
        $params['certify_id'] = $certify_id;
        $result=$this->APIHttpRequestCURL("certify",$params,$this->_config);
        $jsonMsg['status'] = 200;
        $jsonMsg['msg'] = "请使用支付宝扫描二维码";
        $jsonMsg['url'] = $result['url'];
        return $jsonMsg;
    }

    //查询实名认证结果
    public function getAliyunAuthStatus($certify_id)
    {
        $params['certify_id'] = $certify_id;
		$result=$this->APIHttpRequestCURL("query",$params,$this->_config);
        $status = 4; # 已提交资料
        if($result['status']==200){
			$status = 1; # 通过
			$msg = '审核通过';
        }else{
            $msg = $result['msg'];
            $status = 2;
        }
        return ['status'=>$status,'msg'=>$msg];
    }
	private function APIHttpRequestCURL($action,$param,$config,$method='POST'){			
		$api='http://api1.idcsmart.com/certapi.php?action='.$action;
		$headers = array(
			"api:".$config['api'],
			"key:".$config['key'],
			"Content-Type: application/x-www-form-urlencoded"
		);
		$postfields=http_build_query($param);
		if($method!='GET'){
            $ch = curl_init();
            curl_setopt_array($ch, array(
               CURLOPT_URL => $api,
               CURLOPT_RETURNTRANSFER => true,
               CURLOPT_POSTFIELDS => $postfields,
               CURLOPT_CUSTOMREQUEST => strtoupper($method),
               CURLOPT_HTTPHEADER => $headers
            ));
        }else{
            $url=$api."&".$postfields;
            $ch = curl_init($url) ;
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1) ;
        }
        $output = curl_exec($ch);
        curl_close($ch);
        $output = trim($output, "\xEF\xBB\xBF");
        return json_decode($output,true);
    }
}