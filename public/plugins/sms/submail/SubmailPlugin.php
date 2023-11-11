<?php
namespace sms\submail;

use app\admin\lib\Plugin;

class SubmailPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Submail',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '赛邮',
        'description' => '赛邮',
        'status'      => 1,
        'author'      => '智简魔方',
        'version'     => '1.0',
        'help_url'     => 'https://www.mysubmail.com/',//申请接口地址
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
    public function getCnTemplate($params)
    {
        $param['template_id'] = trim($params['template_id']);
        $api_url = 'message/template.json';
        $resultTemplate = $this->APIHttpRequestCURL('cn', $api_url, $param, $params['config'], 'GET');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
            if ($resultTemplate['template']) {
                $data['template']['template_id'] = $resultTemplate['template']['template_id'];
                $data['template']['template_status'] = $resultTemplate['template']['template_status'];
            }
        } else {
            $data['status'] = 'error';
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    public function createCnTemplate($params)
    {
        $param['sms_title'] = trim($params['title']);
        $param['sms_signature'] = $params['config']['app_sign'];
        $param['sms_content'] = trim($params['content']);
        $api_url = 'message/template.json';
        $resultTemplate = $this->APIHttpRequestCURL('cn', $api_url, $param, $params['config'], 'POST');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
            $data['template']['template_id'] = $resultTemplate['template_id'];
            $data['template']['template_status'] = 1;
        } else {
            $data['status'] = 'error';
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    public function putCnTemplate($params)
    {
        $param['template_id'] = trim($params['template_id']);
        if (!empty($params['title'])) {
            $param['sms_title'] = trim($params['title']);
        }
        $param['sms_signature'] = $params['config']['app_sign'];
        $param['sms_content'] = trim($params['content']);
        $api_url = 'message/template.json';
        $resultTemplate = $this->APIHttpRequestCURL('cn', $api_url, $param, $params['config'], 'PUT');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
            $data['template']['template_status'] = 1;
        } else {
            $data['status'] = 'error';
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    public function deleteCnTemplate($params)
    {
        $param['template_id'] = trim($params['template_id']);
        $api_url = 'message/template.json';
        $resultTemplate = $this->APIHttpRequestCURL('cn', $api_url, $param, $params['config'], 'DELETE');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
        } else {
            $data['status'] = 'error';
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    public function sendCnSms($params)
    {
        $content = $this->templateParam($params['content'], $params['templateParam']);
        $param['to'] = trim($params['mobile']);
        $param['content'] = $this->templateSign($params['config']['app_sign']) . $content;
        $api_url = 'message/send.json';
        $resultTemplate = $this->APIHttpRequestCURL('cn', $api_url, $param, $params['config'], 'POST');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
            $data['content'] = $content;
        } else {
            $data['status'] = 'error';
            $data['content'] = $content;
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    public function getGlobalTemplate($params)
    {
        return $this->getCnTemplate($params);
    }
    public function createGlobalTemplate($params)
    {
        return $this->createCnTemplate($params);
    }
    public function putGlobalTemplate($params)
    {
        return $this->putCnTemplate($params);
    }
    public function deleteGlobalTemplate($params)
    {
        return $this->deleteCnTemplate($params);
    }
    public function sendGlobalSms($params)
    {
        $content = $this->templateParam($params['content'], $params['templateParam']);
        $param['to'] = trim($params['mobile']);
        $param['content'] = $this->templateSign($params['config']['international_app_sign']) . $content;
        $api_url = 'internationalsms/send.json';
        $resultTemplate = $this->APIHttpRequestCURL('global', $api_url, $param, $params['config'], 'POST');
        if ($resultTemplate['status'] == 'success') {
            $data['status'] = 'success';
            $data['content'] = $content;
        } else {
            $data['status'] = 'error';
            $data['content'] = $content;
            $data['msg'] = $resultTemplate['msg'];
        }
        return $data;
    }
    private function APIHttpRequestCURL($sms_type = 'cn', $api_url, $post_data, $params, $method = 'POST')
    {
        $this->base_url = 'http://api.mysubmail.com/';
        if ($sms_type == 'cn') {
            $request['appid'] = $params['app_id'];
            $request['appkey'] = $params['app_key'];
        } else {
            if ($sms_type == 'global') {
                $request['appid'] = $params['international_app_id'];
                $request['appkey'] = $params['international_app_key'];
            }
        }
        $api = $this->base_url . $api_url;
        $request['timestamp'] = $this->getTimestamp();
        $request['signature'] = $request['appkey'];
        $post_data = array_merge($request, $post_data);
        if ($method != 'GET') {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL => $api, CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => http_build_query($post_data), CURLOPT_CUSTOMREQUEST => strtoupper($method), CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        } else {
            $url = $api . '?' . http_build_query($post_data);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        $output = trim($output, '﻿');
        return json_decode($output, true);
    }
    private function getTimestamp()
    {
        $api = $this->base_url . 'service/timestamp.json';
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);
        $timestamp = json_decode($output, true);
        return $timestamp['timestamp'];
    }
    private function templateParam($content, $templateParam)
    {
        foreach ($templateParam as $key => $para) {
            $content = str_replace('@var(' . $key . ')', $para, $content);
        }
        $content = preg_replace('/@var\\(.*?\\)/is', '', $content);
        return $content;
    }
    private function templateSign($sign)
    {
        $sign = str_replace('【', '', $sign);
        $sign = str_replace('】', '', $sign);
        $sign = '【' . $sign . '】';
        return $sign;
    }
}

?>