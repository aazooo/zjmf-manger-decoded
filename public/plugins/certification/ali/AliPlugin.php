<?php
namespace certification\ali;

use app\admin\lib\Plugin;
use certification\ali\logic\Ali;
use cmf\phpqrcode\QRcode;

class AliPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Ali',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '芝麻信用',
        'description' => '芝麻信用',
        'status'      => 1,
        'author'      => '顺戴网络',
        'version'     => '1.0',
        'help_url'    => 'https://bbs.idcsmart.com/forum.php?mod=viewthread&tid=67&extra=page%3D1%26filter%3Dtypeid%26typeid%3D7'
    );

    # 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    # 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }
    /*
         * 数据返回格式
         *  $res = [
         *         'status' => 1, //状态 1已通过，0未通过
         *         'msg' => '', //认证信息
         *         'certify_id' => '认证证书', //可选
         *         'data' => '',  //返回的url链接,没有则为空
         *         'ping' => true,  //是否需要ping轮询 可选 true|false
         *   ]
         *
         */
    # 个人认证
    public function personal($certifi)
    {
        # 自定义字段 自行操作
        # $custom1 = $certifi['test_field'];

        $logic = new Ali();
        $res1 = $logic->getCertifyId($certifi['name'],$certifi['card']);
        $data = [
            'status' => 4,
            'auth_fail' => '',
            'certify_id' => '',
            'notes' => '', # 其他信息:这里可以存储自定义的实名认证返回数据,后台实名认证详情可查看
        ];
        if ($res1['status'] == 200){
            $certify_id = $res1['certify_id'];
            $data['certify_id'] = $certify_id;
            $res2 = $logic->generateScanForm($certify_id);
            $url = $res2['url'];

            # 其他信息
            $time = date('Y-m-d H:i:s',time());
            $data['notes'] = "支付宝记录号:{$certify_id};\r\n"."实名认证方式:{$this->info['title']};\r\n"."实名认证接口提交时间:{$time}\r\n";

            $uid = \request()->uid;
            $filename = md5($uid . '_zjmf_' . time()) . '.png';
            $file = WEB_ROOT . "upload/{$filename}"; # 临时存放二维码图片
            QRcode::png($url,$file);
            $base64 = base64EncodeImage($file);
            unlink($file);# 删除临时文件
            updatePersonalCertifiStatus($data);
            return "<h5 class=\"pt-2 font-weight-bold h5 py-4\">请使用支付宝扫描二维码</h5><img height='200' width='200' src=\"{$base64}\" alt=\"\">";
            updatePersonalCertifiStatus($data);
            return [
                'type' => 'html',
                'data' => $url,
            ];
        }else{
            $data['auth_fail'] = $res1['msg']?:'实名认证接口配置错误,请联系管理员';
            return "<h3 class=\"pt-2 font-weight-bold h2 py-4\"><img src=\"\" alt=\"\">{$data['auth_fail']}</h3>";
        }
    }

    # 企业认证
    public function company($certifi)
    {
        # 自定义字段 自行操作
        # $custom1 = $certifi['test_field'];

        $logic = new Ali();
        $res1 = $logic->getCertifyId($certifi['name'],$certifi['card']);
        $data = [
            'status' => 4,
            'auth_fail' => '',
            'certify_id' => '',
            'notes' => '',
        ];
        if ($res1['status'] == 200){
            $certify_id = $res1['certify_id'];
            $data['certify_id'] = $certify_id;
            $res2 = $logic->generateScanForm($certify_id);
            $url = $res2['url'];
            # 其他信息
            $time = date('Y-m-d H:i:s',time());
            $data['notes'] = "支付宝记录号:{$certify_id};\r\n"."实名认证方式:{$this->info['title']};\r\n"."实名认证接口提交时间:{$time}\r\n";
            $uid = \request()->uid;
            $filename = md5($uid . '_zjmf_' . time()) . '.png';
            $file = WEB_ROOT . "upload/{$filename}"; # 临时存放二维码图片
            QRcode::png($url,$file); # 使用phpqrcode扩展
            $base64 = base64EncodeImage($file); # 图片文件转base64
            unlink($file);# 删除临时文件
            updateCompanyCertifiStatus($data);
            return "<h5 class=\"pt-2 font-weight-bold h5 py-4\">请使用支付宝扫描二维码</h5><img height='200' width='200' src=\"{$base64}\" alt=\"\">";

            updateCompanyCertifiStatus($data);
            # 阿里此处做特殊处理
            return [
                'type' => 'html',
                'data' => $url,
            ];
        }else{
            $data['auth_fail'] = $res1['msg']?:'实名认证接口配置错误,请联系管理员';
            return "<h3 class=\"pt-2 font-weight-bold h2 py-4\"><img src=\"\" alt=\"\">{$data['auth_fail']}</h3>";
        }
    }

    # 前台自定义字段输出
    public function collectionInfo()
    {
        $config = $this->getConfig();
        $type = $config['biz_code'];
        if ($type == 'SMART_FACE'){
            $data = [
            ];
        }elseif ($type == 'FACE'){
            $data = [
            ];
        }elseif ($type == 'CERT_PHOTO'){
            $data = [
            ];
        }elseif ($type == 'CERT_PHOTO_FACE'){
            $data = [
            ];
        }else{
            $data = [];
        }
        return $data;
    }

    # 当返回数据中ping为true时,需要实现此方法,系统轮询调用
    public function getStatus($certifi)
    {
        $logic = new Ali();
        $certify_id = $certifi['certify_id'];
        $res = $logic->getAliyunAuthStatus($certify_id);
        return $res;
    }
}