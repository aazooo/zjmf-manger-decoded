<?php
namespace sms\qcloudsms;

use app\admin\lib\Plugin;

require_once 'vendor/autoload.php';

class QcloudsmsPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Qcloudsms',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '腾讯云SMS',
        'description' => '腾讯云SMS',
        'status'      => 1,
        'author'      => '云外科技',
        'version'     => '1.5',
        'help_url'     => 'https://cloud.tencent.com/product/sms',//申请接口地址
    );

    public function install()
    {
        $smsTemplate = [];
        if (file_exists(__DIR__ . '/config/smsTemplate.php')) {
            $smsTemplate = (require __DIR__ . '/config/smsTemplate.php');
        }
        return $smsTemplate;
    }
    public function uninstall()
    {
        return true;
    }
    public function description()
    {
        return file_get_contents(__DIR__ . '/config/description.html');
    }
    public function descriptionTemplate()
    {
        $data = ['cn' => '注意区分营销类型短信，验证码类型短信仅允许验证码变量，不支持其他变量添加', 'global' => '', 'cnpro' => '营销推广性质的短信，属于运营类短信，运营类短信需要加入退订回 N 的提示。'];
        return $data;
    }
    public function getCnTemplate($params)
    {
        $resultTemplate = $this->APICX($params, $params['config']);
        return $resultTemplate;
    }
    public function createCnTemplate($params)
    {
        $resultTemplate = $this->APIPOST($params, $params['config']);
        return $resultTemplate;
    }
    public function putCnTemplate($params)
    {
        if (strpos($params['content'], '验证码') !== false) {
            $TemplateType = 0;
        } else {
            $TemplateType = 1;
        }
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\ModifySmsTemplateRequest();
            $param['TemplateName'] = trim($params['title']);
            $param['TemplateContent'] = trim($this->contentParamReplace($params['content']));
            $param['Remark'] = trim($params['remark']);
            $param['TemplateId'] = $params['template_id'];
            $param['SmsType'] = '0';
            $param['International'] = '0';
            $params = ['TemplateId' => (int) $params['template_id'], 'TemplateName' => trim($params['title']), 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 0, 'International' => 0, 'Remark' => trim($params['remark'])];
            $req->fromJsonString(json_encode($params));
            $resp = $client->ModifySmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function deleteCnTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\DeleteSmsTemplateRequest();
            $params = ['TemplateId' => $params['template_id']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DeleteSmsTemplate($req);
            $resultTemplate = json_decode($resp->toJsonString(), true);
            if (isset($resultTemplate['Error'])) {
                if ($resultTemplate['Error']['Code'] == 'FailedOperation.TemplateIdNotExist') {
                    $resultTemplate['Error']['Message'] = '模板ID错误';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($resultTemplate['Error']['Code'], $resultTemplate['Error']['Message']);
            }
            $data['status'] = 'success';
            return $data;
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function sendCnSms($params)
    {
        try {
            $content = $params['content'];
            $templateParam = $params['templateParam'];
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\SendSmsRequest();
            if (strpos($params['content'], '验证码') !== false) {
                $str = $this->templateParamArray($content, $templateParam);
                preg_match_all('/(?:\\[)(.*)(?:\\])/i', $str, $result);
                $str = [$result[1][0]];
            } else {
                $str = json_decode($this->templateParamArray($content, $templateParam));
            }
            $params = ['PhoneNumberSet' => [trim($params['mobile'])], 'SmsSdkAppId' => $params['config']['SmsSdkAppId'], 'SignName' => $params['config']['SignName'], 'TemplateId' => trim($params['template_id']), 'TemplateParamSet' => $str];
            $req->fromJsonString(json_encode($params));
            $resp = $client->SendSms($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'content' => $this->templateParam($params['content'], $params['templateParam'])];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'content' => $this->templateParam($params['content'], $params['templateParam']), 'msg' => $e->getMessage()];
        }
    }
    public function getCnProTemplate($params)
    {
        $resultTemplate = $this->APICX($params, $params['config']);
        return $resultTemplate;
    }
    public function createCnProTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\AddSmsTemplateRequest();
            $params = ['TemplateName' => $params['title'], 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 1, 'International' => 0, 'Remark' => $params['remark']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->AddSmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                if ($result['Error']['Code'] == 'RequestLimitExceeded') {
                    $result['Error']['Message'] = '每次提交审核一个模板，模板提交间隔建议您控制在30S以上。';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_id' => $result['AddTemplateStatus']['TemplateId'], 'template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function putCnProTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\ModifySmsTemplateRequest();
            $param['TemplateName'] = trim($params['title']);
            $param['TemplateContent'] = trim($this->contentParamReplace($params['content']));
            $param['Remark'] = trim($params['remark']);
            $param['TemplateId'] = $params['template_id'];
            $param['SmsType'] = '1';
            $param['International'] = '0';
            $params = ['TemplateId' => (int) $params['template_id'], 'TemplateName' => trim($params['title']), 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 1, 'International' => 0, 'Remark' => trim($params['remark'])];
            $req->fromJsonString(json_encode($params));
            $resp = $client->ModifySmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function deleteCnProTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\DeleteSmsTemplateRequest();
            $params = ['TemplateId' => $params['template_id']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DeleteSmsTemplate($req);
            $resultTemplate = json_decode($resp->toJsonString(), true);
            if (isset($resultTemplate['Error'])) {
                if ($resultTemplate['Error']['Code'] == 'FailedOperation.TemplateIdNotExist') {
                    $resultTemplate['Error']['Message'] = '模板ID错误';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($resultTemplate['Error']['Code'], $resultTemplate['Error']['Message']);
            }
            $data['status'] = 'success';
            return $data;
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function sendCnProSms($params)
    {
        try {
            $content = $params['content'];
            $templateParam = $params['templateParam'];
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\SendSmsRequest();
            if (strpos($params['content'], '验证码') !== false) {
                $str = $this->templateParamArray($content, $templateParam);
                preg_match_all('/(?:\\[)(.*)(?:\\])/i', $str, $result);
                $str = [$result[1][0]];
            } else {
                $str = json_decode($this->templateParamArray($content, $templateParam));
            }
            $param = ['PhoneNumberSet' => [trim($params['mobile'])], 'SmsSdkAppId' => $params['config']['SmsSdkAppId'], 'SignName' => $params['config']['SignName'], 'TemplateId' => trim($params['template_id']), 'TemplateParamSet' => $str];
            $req->fromJsonString(json_encode($param));
            $resp = $client->SendSms($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'content' => $this->templateParam($params['content'], $params['templateParam'])];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'content' => $this->templateParam($params['content'], $params['templateParam']), 'msg' => $e->getMessage()];
        }
    }
    public function getGlobalTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\DescribeSmsTemplateListRequest();
            $params = ['TemplateIdSet' => [$params['template_id']], 'International' => 1];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DescribeSmsTemplateList($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            $templates = array_column($result['DescribeTemplateStatusSet'], NULL, 'TemplateId');
            if ($templates[$params['template_id']]['StatusCode'] == 0) {
                $template_status = 2;
            } else {
                if ($templates[$params['template_id']]['StatusCode'] == 1) {
                    $template_status = 1;
                } else {
                    if ($templates[$params['template_id']]['StatusCode'] == -1) {
                        $template_status = 3;
                    }
                }
            }
            $data['template']['template_status'] = $template_status;
            return ['status' => 'success', 'template' => ['template_id' => $templates[$params['template_id']]['TemplateId'], 'template_status' => $data['template']['template_status'], 'msg' => $templates[$params['template_id']]['ReviewReply']]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function createGlobalTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\AddSmsTemplateRequest();
            $params = ['TemplateName' => $params['title'], 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 0, 'International' => 1, 'Remark' => $params['remark']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->AddSmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                if ($result['Error']['Code'] == 'RequestLimitExceeded') {
                    $result['Error']['Message'] = '每次提交审核一个模板，模板提交间隔建议您控制在30S以上。';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_id' => $result['AddTemplateStatus']['TemplateId'], 'template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function putGlobalTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\ModifySmsTemplateRequest();
            $param['TemplateName'] = trim($params['title']);
            $param['TemplateContent'] = trim($this->contentParamReplace($params['content']));
            $param['Remark'] = trim($params['remark']);
            $param['TemplateId'] = $params['template_id'];
            $param['SmsType'] = '0';
            $param['International'] = '1';
            $params = ['TemplateId' => (int) $params['template_id'], 'TemplateName' => trim($params['title']), 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 0, 'International' => 1, 'Remark' => trim($params['remark'])];
            $req->fromJsonString(json_encode($params));
            $resp = $client->ModifySmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function deleteGlobalTemplate($params)
    {
        try {
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\DeleteSmsTemplateRequest();
            $params = ['TemplateId' => $params['template_id']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DeleteSmsTemplate($req);
            $resultTemplate = json_decode($resp->toJsonString(), true);
            if (isset($resultTemplate['Error'])) {
                if ($resultTemplate['Error']['Code'] == 'FailedOperation.TemplateIdNotExist') {
                    $resultTemplate['Error']['Message'] = '模板ID错误';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($resultTemplate['Error']['Code'], $resultTemplate['Error']['Message']);
            }
            $data['status'] = 'success';
            return $data;
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    public function sendGlobalSms($params)
    {
        try {
            $content = $params['content'];
            $templateParam = $params['templateParam'];
            $cred = new \TencentCloud\Common\Credential($params['config']['SecretId'], $params['config']['SecretKey']);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\SendSmsRequest();
            if (strpos($params['content'], '{code}') !== false) {
                $str = $this->templateParamArray($content, $templateParam);
                preg_match_all('/(?:\\[)(.*)(?:\\])/i', $str, $result);
                $str = [$result[1][0]];
            } else {
                $str = json_decode($this->templateParamArray($content, $templateParam));
            }
            $param = ['PhoneNumberSet' => [$params['mobile']], 'SmsSdkAppId' => $params['config']['SmsSdkAppId'], 'TemplateId' => trim($params['template_id']), 'TemplateParamSet' => $str];
            $req->fromJsonString(json_encode($param));
            $resp = $client->SendSms($req);
            $result = json_decode($resp->toJsonString(), true);
            var_dump($result);
            exit;
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'content' => $this->templateParam($params['content'], $params['templateParam']), 'msg' => $e->getMessage()];
        }
    }
    private function APIPOST($params, $config)
    {
        try {
            $SecretId = $config['SecretId'];
            $SecretKey = $config['SecretKey'];
            $cred = new \TencentCloud\Common\Credential($SecretId, $SecretKey);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\AddSmsTemplateRequest();
            $params = ['TemplateName' => $params['title'], 'TemplateContent' => trim($this->contentParamReplace($params['content'])), 'SmsType' => 0, 'International' => 0, 'Remark' => $params['remark']];
            $req->fromJsonString(json_encode($params));
            $resp = $client->AddSmsTemplate($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                if ($result['Error']['Code'] == 'RequestLimitExceeded') {
                    $result['Error']['Message'] = '每次提交审核一个模板，模板提交间隔建议您控制在30S以上。';
                }
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            return ['status' => 'success', 'template' => ['template_id' => $result['AddTemplateStatus']['TemplateId'], 'template_status' => 1]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    private function APICX($param, $config)
    {
        try {
            $SecretId = $config['SecretId'];
            $SecretKey = $config['SecretKey'];
            $cred = new \TencentCloud\Common\Credential($SecretId, $SecretKey);
            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');
            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou', $clientProfile);
            $req = new \TencentCloud\Sms\V20210111\Models\DescribeSmsTemplateListRequest();
            $params = ['TemplateIdSet' => [(int) $param['template_id']], 'International' => 0];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DescribeSmsTemplateList($req);
            $result = json_decode($resp->toJsonString(), true);
            if (isset($result['Error'])) {
                throw new \TencentCloud\Common\Exception\TencentCloudSDKException($result['Error']['Code'], $result['Error']['Message']);
            }
            $templates = array_column($result['DescribeTemplateStatusSet'], NULL, 'TemplateId');
            if ($templates[$param['template_id']]['StatusCode'] == 0) {
                $template_status = 2;
            } else {
                if ($templates[$param['template_id']]['StatusCode'] == 1) {
                    $template_status = 1;
                } else {
                    if ($templates[$param['template_id']]['StatusCode'] == -1) {
                        $template_status = 3;
                    }
                }
            }
            $data['template']['template_status'] = $template_status;
            return ['status' => 'success', 'template' => ['template_id' => $templates[$param['template_id']]['TemplateId'], 'template_status' => $data['template']['template_status'], 'msg' => $templates[$param['template_id']]['ReviewReply']]];
        } catch (\TencentCloud\Common\Exception\TencentCloudSDKException $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    private function templateParam($content, $templateParam)
    {
        foreach ($templateParam as $key => $para) {
            $content = str_replace('{' . $key . '}', $para, $content);
        }
        return $content;
    }
    private function templateParamArray($content, $templateParam)
    {
        if (!$content) {
            return [];
        }
        preg_match_all('/(?<=\\{)([^\\}]*?)(?=\\})/', $content, $ary);
        if (!$ary[0]) {
            return [];
        }
        $params = [];
        foreach ($ary[0] as $k => $v) {
            $params[] = $templateParam[$v];
        }
        if (!empty($params) && is_array($params)) {
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        return $params;
    }
    private function contentParamReplace($content)
    {
        if (!$content) {
            return $content;
        }
        preg_match_all('/(?<=\\{)([^\\}]*?)(?=\\})/', $content, $ary);
        if (!$ary[0]) {
            return $content;
        }
        foreach ($ary[0] as $k => $v) {
            $content = str_replace('{' . $v . '}', '{' . ($k + 1) . '}', $content);
        }
        return $content;
    }
    private function paramCode()
    {
        return ['system_companyname' => 1, 'code' => 2, 'send_time' => 3, 'system_url' => 4, 'system_web_url' => 5, 'system_email_logo_url' => 6, 'username' => 7, 'epw_account' => 8, 'account_email' => 9, 'register_time' => 10, 'user_address' => 11, 'qq' => 12, 'user_company' => 13, 'login_data_time' => 14, 'action_ip' => 15, 'product_name' => 16, 'hostname' => 17, 'product_user' => 18, 'product_mainip' => 19, 'product_passwd' => 20, 'product_dcimbms_os' => 21, 'product_addonip' => 22, 'product_end_time' => 23, 'product_binlly_cycle' => 24, 'order_create_time' => 25, 'order_id' => 26, 'order_total_fee' => 27, 'invoice_paid_time' => 28, 'ticket_reply_time' => 29, 'ticket_department' => 30, 'auto_ticket_close_time' => 31, 'ticket_createtime' => 31, 'product_first_time' => 33, 'ticket_level' => 34, 'admin_account_name' => 35, 'admin_login_data_time' => 36, 'admin_action_ip' => 37, 'invoiceid' => 38, 'total' => 39, 'subject' => 40, 'description' => 41, 'account' => 42, 'time' => 43, 'address' => 44, 'product_terminate_time' => 45, 'ticketnumber_tickettitle' => 46, 'epw_type' => 47];
    }
}

?>