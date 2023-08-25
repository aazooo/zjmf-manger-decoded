**顺戴财务系统实名认证插件开发文档**
========================

## 实名认证

### 开发流程
1 在public/plugins/certification目录下添加实名认证目录(如：threehc)

2 创建入口文件

3 确定是否要后台配置文件，如果需要在网关根目录加上config.php，格式查看下方“配置文件”

4 如需外部访问，请加controller目录，再添加Controller文件

5 到后台实名设置--接口设置 刷新界面就会看到你新添加的实名认证插件

### 创建目录
网关目录在程序的根目录 `/plugins/certification`
目录名应字母小写+下划线形式，并且必须以字母开头 例：`public/plugins/certification/threehc`
### 入口文件
文件名应为目录名大驼峰+Plugin.php(注意：首字母大写,命名空间)，创建在你的实名认证目录下，例：
`threehc/ThreehcPlugin.php`
#### info属性 必须
在实名认证插件入口文件定义类属性`info`来配置网关的基本信息（见示例）

##### install方法 必须
实名插件预安装

##### uninstall方法 必须
实名插件卸载

##### personal方法 可选
个人认证,可选方法,如果实现,表示支持个人认证;

参数

1.系统参数
```
$certifi['name'] # 姓名
$certifi['card'] # 身份证号
$certifi['phone'] # 手机号 phone字段需要开发者在collectionInfo()中定义,否则返回空
$certifi['bank'] # 银行卡号 bank字段需要开发者在collectionInfo()中定义,否则返回空
$certifi['company_name'] # 公司名
$certifi['company_organ_code'] # 公司代码
```
2.自定义参数(collectionInfo方法对应参数)
```
$certifi['custom_field1'] # 自定义字段1  注意：文件类型参数会传 文件地址绝对路径
$certifi['custom_field2'] # 自定义字段2 
```

响应参数
```
html代码
```

##### company方法 可选
企业认证,可选方法,如果实现,表示支持企业认证;
参数:参考个人认证

##### collectionInfo方法 可选 目前支持最多10个自定义字段
实名认证前台自定义字段输出

##### getStatus方法 可选
实名认证时,系统会轮询调用此方法

参数

```
$certifi['certify_id'] # 认证证书
```
响应参数

```
return [
        'status'=>$status, # 1审核通过,2未通过,4已提交资料 默认为4
        'msg'=>$msg # 提示信息
      ];
      
或者直接 return true;
```

#### 示例
```php
<?php
namespace certification\threehc;

use app\admin\lib\Plugin;
use certification\threehc\logic\Threehc;

class ThreehcPlugin extends Plugin
{
    # 基础信息
    public $info = array(
        'name'        => 'Threehc',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '三要素--深圳华辰', //名称
        'description' => '三要素--深圳华辰', //描述
        'status'      => 1, // 状态 启用
        'author'      => '顺戴网络', //作者
        'version'     => '1.0',//版本
        'help_url'    => 'https://market.aliyun.com/products/57000002/cmapi025566.html?spm=5176.10695662.1996646101.searchclickresult.52b1749a9QYHYu'//帮助文档
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
     * 个人认证
     * certifi参数为系统参数+自定义参数数组:
     * $certifi = [
     *    # 系统参数
     *    'name' => '姓名',
     *    'card' => '身份证号',
     *    'phone' => '手机号', # phone字段需要开发者在collectionInfo()中定义,否则返回空
     *    'bank' => '银行卡号', # bank字段需要开发者在collectionInfo()中定义,否则返回空
     *    'company_name' => '公司名',
     *    'company_organ_code' => '公司代码',
     *    # 插件自定义参数 collectionInfo()中返回,列：
     *    'custom_field1' => '自定义字段1' 文件类型参数会传 绝对路径
     * ]
     */
    public function personal($certifi)
    {
        if (file_exists(__DIR__.'/config/config.php')){
            $con = require __DIR__.'/config/config.php';
        }else{
            $con = [];
        }
        $config = $this->getConfig();
        $_config = array_merge($con,$config);
        $type = $_config['type'];
        if ($type == 2){
            $param = [
                'name'=>  $certifi['name'],
                'bank'=>  $certifi['bank'],
            ];
        }elseif ($type == 3){
            $param=[
                'bank'=>  $certifi['bank'],
                'name'=>  $certifi['name'],
                'number'=>   $certifi['card'],
                'type'=>   0,
            ];
        }else{
            $param=[
                'bank'=>  $certifi['bank'],
                'mobile'=>  $certifi['phone'],
                'name'=>   $certifi['name'],
                'number'=>   $certifi['card'],
                'type'=>   0,
            ];
        }
        # TODO WYH 自定义字段 自行操作
        # $custom1 = $certifi['custom_field1'];

        $logic = new Threehc();
        $query = $logic->createLinkstrings($param);
        $appcode = $_config['app_code'];
        $result = $logic->httpsPhoneThree($appcode,$query,$_config['threehc_url'],$type);
        $data = [
            'status' => 2, # 1通过，2未通过
            'auth_fail' => '',
            'certify_id' => $result['log_id']?:'',
        ];
        if ($result['ret'] == 200){
            if (isset($result['data']['desc']) && $result['data']['desc'] == '一致'){ # 通过
                $data['status'] = 1;
            }else{
                $data['auth_fail'] = $result['data']['desc']?:'';
            }
        }else{
            $data['auth_fail'] = $result['msg']?:'实名认证接口配置错误,请联系管理员';
        }
        # 调用系统方法，更新个人实名认证状态
        updatePersonalCertifiStatus($data);
        return "<h3 class=\"pt-2 font-weight-bold h2 py-4\"><img src=\"\" alt=\"\"> 正在认证,请稍等...</h3>";
    }

    # 企业认证
    public function company($certifi)
    {
        if (file_exists(__DIR__.'/config/config.php')){
            $con = require __DIR__.'/config/config.php';
        }else{
            $con = [];
        }
        $config = $this->getConfig();
        $_config = array_merge($con,$config);
        $type = $_config['type'];
        if ($type == 2){
            $param = [
                'name'=>  $certifi['name'],
                'bank'=>  $certifi['bank'],
            ];
        }elseif ($type == 3){
            $param=[
                'bank'=>  $certifi['bank'],
                'name'=>  $certifi['name'],
                'number'=>   $certifi['card'],
                'type'=>   0,
            ];
        }else{
            $param=[
                'bank'=>  $certifi['bank'],
                'mobile'=>  $certifi['phone'],
                'name'=>   $certifi['name'],
                'number'=>   $certifi['card'],
                'type'=>   0,
            ];
        }
        # TODO WYH 自定义字段 自行操作
        # $custom1 = $certifi['custom_field1'];

        $logic = new Threehc();
        $query = $logic->createLinkstrings($param);
        $appcode = $_config['app_code'];
        $result = $logic->httpsPhoneThree($appcode,$query,$_config['threehc_url'],$type);
        $data = [
            'status' => 2, # 1通过，2未通过
            'auth_fail' => '',
            'certify_id' => $result['log_id']?:'',
        ];
        if ($result['ret'] == 200){
            if (isset($result['data']['desc']) && $result['data']['desc'] == '一致'){ # 通过
                $data['status'] = 1;
            }else{
                $data['auth_fail'] = $result['data']['desc']?:'';
            }
        }else{
            $data['auth_fail'] = $result['msg']?:'实名认证接口配置错误,请联系管理员';
        }
        # 调用系统方法，更新企业实名认证状态
        updateCompanyCertifiStatus($data);
        return "<h3 class=\"pt-2 font-weight-bold h2 py-4\"><img src=\"\" alt=\"\"> 正在认证,请稍等...</h3>";
    }

    # 前台自定义字段输出
    public function collectionInfo()
    {
        $config = $this->getConfig();
        $type = $config['type']; 
        if ($type == 2){
            $data = [
                'bank' => [
                    'title' => '银行卡号',
                    'type'  => 'text', # 字段类型：text文本,file文件,select下拉,目前仅支持文本、文件、下拉!
                    'value' => '',
                    'tip'   => '请输入银行卡号',
                    'required'   => true, # 是否必填
                ],
                'face' => [
                    'title' => '人脸照片',
                    'type'  => 'file', # 字段类型：text文本,file文件,select下拉,目前仅支持文本、文件、下拉!
                    'value' => '',
                    'tip'   => '请上传人脸照片',
                    'required'   => true, # 是否必填
                ],
                'cert_type' => [
                    'title' => '证件类型',
                    'type'  => 'select',# 字段类型：text文本,file文件,select下拉,目前仅支持文本、文件、下拉!
                    'options' => [
                        'IDENTITY_CARD'=>'身份证',
                        'HOME_VISIT_PERMIT_HK_MC'=>'港澳通行证',
                        'HOME_VISIT_PERMIT_TAIWAN'=>'台湾通行证',
                        'RESIDENCE_PERMIT_HK_MC'=>'港澳居住证',
                        'RESIDENCE_PERMIT_TAIWAN'=>'台湾居住证',
                    ],
                    'tip'   => '',
                    'required'   => true, # 是否必填
               ],
            ];
        }elseif ($type == 3){
            $data = [
                'bank' => [
                    'title' => '银行卡号',
                    'type'  => 'text', 
                    'value' => '',
                    'tip'   => '请输入银行卡号',
                    'required'   => true, # 是否必填
                ]
            ];
        }elseif ($type == 4){
            $data = [
                'bank' => [
                    'title' => '银行卡号',
                    'type'  => 'text',
                    'value' => '',
                    'tip'   => '请输入银行卡号',
                    'required'   => true, # 是否必填
                ],
                'phone' => [
                    'title' => '手机号',
                    'type'  => 'text',
                    'value' => '',
                    'tip'   => '请输入手机号',
                    'required'   => true, # 是否必填
                ],
            ];
        }else{
            $data = [];
        }
        return $data;
    }
    
    public function getStatus($certifi)
    {
        return true;
    }
}
```

### 配置文件
在你的实名认证根目录下加上config.php 即可定义配置

配置文件中需要加入两个系统字段:amount,free;

amount大于0时,表示需要支付;

free大于0时,表示免费次数;

```php
<?php
return [
    # 系统默认字段
    'amount' => [ # 无此配置,默认为0
        'title' => '金额',
        'type'  => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => 0,
        'tip'   => '支付金额',
    ],
    'free' => [ # 无此配置,默认为0
        'title' => '免费认证次数',
        'type'  => 'text',
        'value' => 0,
        'tip'   => '免费认证次数',
    ],
    # 开发者自定义字段
    'app_code'      => [
        'title' => 'appCode',
        'type'  => 'text',
        'value' => '',
        'tip'   => '',
    ],
    'type'        => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '认证方式',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            2 => '两要素', // 值=>显示  姓名 银行卡号
            3 => '三要素', //  姓名 身份证号 银行卡号
            4 => '四要素', //  姓名 身份证号 银行卡号 手机号
        ],
        'value'   => '2',
        'tip'     => '认证方式',
    ],
];
```