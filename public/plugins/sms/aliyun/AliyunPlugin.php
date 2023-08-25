<?php
namespace sms\aliyun;

use app\admin\lib\Plugin;


class AliyunPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Aliyun',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '阿里云',
        'description' => '阿里云',
        'status'      => 1,
        'author'      => '智简魔方',
        'version'     => '1.0',
        'help_url'     => 'https://www.aliyun.com/product/sms',//申请接口地址
    );

    # 插件安装
    public function install()
    {
		//导入模板
		$smsTemplate= [];
		if (file_exists(__DIR__.'/config/smsTemplate.php')){
            $smsTemplate = require __DIR__.'/config/smsTemplate.php';
        }
		
        return $smsTemplate;
    }

    # 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }
	
	# 后台页面创建模板时可用参数
	public function description()
	{
		return file_get_contents(__DIR__.'/config/description.html');    
    } 
	
	#获取国内模板
	/*
	返回数据格式
	status//状态只有两种（成功success，失败error）
	template_id//模板的ID,
	template_status//只能是1,2,3（1正在审核，2审核通过，3未通过审核）
	msg//接口返回的错误消息传给msg参数
	[
		'status'=>'success',
		'template'=>[
			'template_id'=>'w34da',
			'template_status'=>2,
			'msg'=>"模板审核失败",
		]
	]
	获取失败
	[
		'status'=>'error',
		'msg'=>'模板ID错误',
	]
	*/
	public function getCnTemplate($params)
	{		
		$param['TemplateCode']=trim($params['template_id']);		
		$param['Action']='QuerySmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']="success";
			$data['template']['template_id']=$resultTemplate['TemplateCode'];
			if($resultTemplate['TemplateStatus']==0) $template_status=1;
			else if($resultTemplate['TemplateStatus']==1) $template_status=2;
			else if($resultTemplate['TemplateStatus']==2) $template_status=3;
			$data['template']['template_status']=$template_status;
			if($resultTemplate['Reason']) $data['template']['msg']=$resultTemplate['Reason'];
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}

		return $data;
	}
	#创建国内模板
	/*
	返回数据格式
	status//状态只有两种（成功success，失败error）
	template_id//模板的ID,
	template_status//只能是1,2,3（1正在审核，2审核通过，3未通过审核）
	msg//接口返回的错误消息传给msg参数
	成功
	[
		'status'=>'success',
		'template'=>[
			'template_id'=>'w34da',
			'template_status'=>1,
		]
	]
	失败
	[
		'status'=>'error',
		'msg'=>'模板ID错误',
	]
	*/
	public function createCnTemplate($params)
	{		
		if(strpos($params['content'],"验证码")!==false){
			$TemplateType=0;
		}else{
			$TemplateType=1;
		}
		$param['TemplateType']=$TemplateType;	
		$param['TemplateName']=trim($params['title']);	
		$param['TemplateContent']=trim($params['content']);
		$param['Remark']=trim($params['remark']);
        $param['Action']='AddSmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['template']['template_id']=$resultTemplate['TemplateCode'];
			$data['template']['template_status']=1;
		}else{
			$data['status']="error";
			if($resultTemplate['Message']=="创建工单错误"){
				$resultTemplate['Message']="每次提交审核一个模板，模板提交间隔建议您控制在30S以上。"; 
			}	
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#修改国内模板
	/*
	返回数据格式
	status//状态只有两种（成功success，失败error）
	template_status//只能是1,2,3（1正在审核，2审核通过，3未通过审核）
	msg//接口返回的错误消息传给msg参数
	成功
	[
		'status'=>'success',
		'template'=>[
			'template_status'=>2,
		]
	]
	失败
	[
		'status'=>'error',
		'msg'=>'模板ID错误',
	]
	*/
	public function putCnTemplate($params)
	{
		if(strpos($params['content'],"验证码")!==false){
			$TemplateType=0;
		}else{
			$TemplateType=1;
		}
		$param['TemplateType']=$TemplateType;	
		$param['TemplateCode']=trim($params['template_id']);		
		$param['TemplateName']=trim($params['title']);	
		$param['TemplateContent']=trim($params['content']);
		$param['Remark']=trim($params['remark']);
		$param['Action']='ModifySmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['template']['template_status']=1;
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#删除国内模板
	/*
	返回数据格式
	status//状态只有两种（成功success，失败error）
	msg//接口返回的错误消息传给msg参数
	成功
	[
		'status'=>'success',
	]
	失败
	[
		'status'=>'error',
		'msg'=>'模板ID错误',
	]
	*/
	public function deleteCnTemplate($params)
	{
		$param['TemplateCode']=trim($params['template_id']);
        $param['Action']='DeleteSmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#发送国内短信
	/*
	返回数据格式
	status//状态只有两种（成功success，失败error）
	content//替换参数过后的模板内容
	msg//接口返回的错误消息传给msg参数
	成功
	[
		'status'=>'success',
		'content'=>'success',
	]
	失败
	[
		'status'=>'error',
		'content'=>'error',
		'msg'=>'手机号错误',
	]
	*/
    public function sendCnSms($params)
    {	
		$content=$this->templateParam($params['content'],$params['templateParam']);
    	$param['TemplateCode']=trim($params['template_id']);
    	$param['PhoneNumbers']=trim($params['mobile']);
		$param['TemplateParam']=$this->templateParamArray($params['content'],$params['templateParam']);

        $param['Action']='SendSms';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['content']=$content;
		}else{
			$data['status']="error";
			$data['content']=$content;
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
    }	
	#获取国际模板
	public function getGlobalTemplate($params)
	{		
		$param['TemplateCode']=trim($params['template_id']);		
		$param['Action']='QuerySmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']="success";
			$data['template']['template_id']=$resultTemplate['TemplateCode'];
			if($resultTemplate['TemplateStatus']==0) $template_status=1;
			else if($resultTemplate['TemplateStatus']==1) $template_status=2;
			else if($resultTemplate['TemplateStatus']==2) $template_status=3;
			$data['template']['template_status']=$template_status;
			if($resultTemplate['Reason']) $data['template']['msg']=$resultTemplate['Reason'];
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}

		return $data;
	}
	#创建国际模板
	public function createGlobalTemplate($params)
	{
		$param['TemplateType']=3;	
		$param['TemplateName']=trim($params['title']);	
		$param['TemplateContent']=trim($params['content']);
		$param['Remark']=trim($params['remark']);
        $param['Action']='AddSmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['template']['template_id']=$resultTemplate['TemplateCode'];
			$data['template']['template_status']=1;
		}else{
			$data['status']="error";
			if($resultTemplate['Message']=="创建工单错误"){
				$resultTemplate['Message']="每次提交审核一个模板，模板提交间隔建议您控制在30S以上。"; 
			}
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#修改国际模板
	public function putGlobalTemplate($params)
	{
		$param['TemplateCode']=trim($params['template_id']);	
		$param['TemplateType']=3;	
		$param['TemplateName']=trim($params['title']);	
		$param['TemplateContent']=trim($params['content']);
		$param['Remark']=trim($params['remark']);
		$param['Action']='ModifySmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['template']['template_status']=1;
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#删除国际模板
	public function deleteGlobalTemplate($params)
	{
		$param['TemplateCode']=trim($params['template_id']);
        $param['Action']='DeleteSmsTemplate';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
		}else{
			$data['status']="error";
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
	}
	#发送国际短信
    public function sendGlobalSms($params)
    {
		$content=$this->templateParam($params['content'],$params['templateParam']);
		$params['mobile']=str_replace('+','',$params['mobile']);
    	$param['TemplateCode']=trim($params['template_id']);
    	$param['PhoneNumbers']=trim($params['mobile']);
		$param['TemplateParam']=$this->templateParamArray($params['content'],$params['templateParam']);
        $param['Action']='SendSms';
		$resultTemplate=$this->APIHttpRequestCURL('cn',$param,$params['config'],'POST');
		if($resultTemplate['Code']=="OK"){
			$data['status']='success';
			$data['content']=$content;
		}else{
			$data['status']="error";
			$data['content']=$content;
			$data['msg']=$resultTemplate['Message'];
		}
		return $data;
    }	

	
	# 以下函数名自定义

	private function APIHttpRequestCURL($sms_type='cn',$params,$config,$method='POST'){
		$url='https://dysmsapi.aliyuncs.com/';
		if($sms_type=='cn'){			
			
		}else if($sms_type=="global"){
			
		}
		$params['SignName']=$config['SignName'];
		
		$fixedParams = [
            'Format'            => 'json',
            'RegionId'          => 'cn-hangzhou',
            'SignatureMethod'     => 'HMAC-SHA1',
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            'SignatureVersion'    => "1.0",
            'Timestamp'             => gmdate("Y-m-d\TH:i:s\Z"),
            'Version'              => '2017-05-25',
			"AccessKeyId" => $config['AccessKeyId'],
        ];
		$apiParams=array_merge($fixedParams,$params);
		ksort($apiParams);
		$sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        } 
		$stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));
		$sign = base64_encode(hash_hmac("sha1", $stringToSign, $config['AccessKeySecret'] . "&",true));
        $signature = $this->encode($sign);
		
		
		$body="Signature={$signature}{$sortedQueryStringTmp}";
		//var_dump($fixedParams);exit;
		try {
			$ch = curl_init();
			if($method == 'POST') {
				curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			} else {
				$url .= '?'.$body;
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"x-sdk-client" => "php/2.0.0"
			));
			if(substr($url, 0,5) == 'https') {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			}
			$result = curl_exec($ch);
			if($result === false) {
				return false;
				// 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
				// 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
				trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
			}
			curl_close($ch);
			return json_decode($result,true);
		} catch( \Exception $e) {
            return false;
        }
    }
    private function encode($str){
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }
	private function templateParam($content,$templateParam){
        foreach ($templateParam as $key => $para) {
            $content = str_replace('${' . $key . '}', $para, $content);//模板中的参数替换
        }       
		$content =preg_replace("/\\$\{.*?\}/is","",$content);
        return $content;
    }

    private function templateParamArray($content,$templateParam){
        $params=[];
		foreach ($templateParam as $key => $val) {
			if(strpos($content,'${'.$key.'}')!==false){
				$params[$key]=$val;
			}
        }    
		if(!empty($params) && is_array($params)) {
			$params = json_encode($params, JSON_UNESCAPED_UNICODE);
		}		
        return $params;
    }
}