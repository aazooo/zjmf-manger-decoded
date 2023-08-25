**顺戴财务系统网关模块开发文档**
========================

## 支付网关

### 开发流程
1 在public/plugins/gateways目录下添加网关目录(如：demo)(之前是在modules/gateways目录里添加这个网关目录,目前已移动)
2 创建入口文件
3 确定是否要后台配置文件，如果需要在网关根目录加上config.php，格式查看下方“配置文件”
4 如需外部访问，请加controller目录，再添加Controller文件
5 到后台插件管理刷新界面就会看到你新添加的插件

### 创建目录
网关目录在程序的根目录 `public/plugins/gateways`
目录名应字母小写+下划线形式，并且必须以字母开头 例：`public/plugins/gateways`
### 入口文件
文件名应为目录名大驼峰+Plugin.php(注意：首字母大写)，创建在你的网关目录下，例：
`demo/DemoPlugin.php`
#### info属性
在网关入口文件定义类属性`info`来配置网关的基本信息（见示例）
#### 发起支付

##### 支付参数
网关的支付方法会以数组的形式接受支付参数
```
$param['product_name'] #  产品名
$param['out_trade_no'] # 订单编号
$param['total_fee'] # 金额
```
##### 响应参数
发起支付需要统一以数组的形式返回
```php
<?php
    return [
        'type'   => $type,
        'data'  =>$data,
];
```
财务系统目前支持三种形式的支付请求

1.当 `type=url` 时，[data]值为 转换二维码的url地址 由系统自动转换

2.当 `type=insert` 时，[data]值为 第三方支付系统提供的二维码地址 由系统嵌入该二维码

3.当 `type=jump` 时，[data]值为 需要跳转到第三方的支付链接网址

4.当 `type=html` 时，[data]值为  需要提交的html表单

#### 示例
```php
<?php
namespace gateways\demo;//Demo插件英文名，改成你的插件英文就行了
use app\admin\lib\Plugin;
use gateways\wx_pay\validate\WxPayValidate;

//Demo插件英文名，改成你的插件英文就行了
class DemoPlugin extends Plugin
{
    public $info = array(
        'name'        => 'Demo',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '演示',
        'description' => '演示',
        'status'      => 1,
        'author'      => '顺戴网络',
        'version'     => '1.0'
    );

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        // 在这里不要try catch数据库异常，直接抛出上层会处理异常后回滚的
        return true;//卸载成功返回true，失败false
    }

    //发起支付
    public function AliPayHandle($param)
    {
        //> 处理配置参数
        
        //> 发起支付

        //> 返回支付数据
        return ['url'   =>  'url',
               'data'  =>  'weixin://wxpay/bizpayurl/up?prasdSD23d0'
                ];
    }
}
```

### 配置文件
在你的网关根目录下加上config.php 即可定义配置

注意：该文件必须加下面配置，配置内容为，改支付接口支持货币单位(All)为支持所有货币
```php
<?php
    [
    'currency' => [
                 'title' => '支持货币单位',
                 'type'  => 'text',
                 'value' => '',
                 'tip'   => '',
             ],
    ];
````
```php
<?php
$domain = configuration('domain');
return [
    'custom_config' => [// 在后台插件配置表单中的键名 ,会是config[custom_config]，这个键值很特殊，是自定义插件配置的开关
        'title' => '自定义配置处理', // 表单的label标题
        'type'  => 'text', // 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '0', // 如果值为1，表示由插件自己处理插件配置，配置入口在 AdminIndex/setting
        'tip'   => '自定义配置处理', //表单的帮助提示
        'attribute'=> 'disabled', // 可选 表单附加属性,有此属性表示不可以更改
    ],
    'text'          => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '999文本', // 表单的label标题
        'type'  => 'text', // 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => 'hello,ThinkCMF!', // 表单的默认值
        'tip'   => '这是文本组件的演示', //表单的帮助提示
    ],
    'password'      => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title' => '密码',
        'type'  => 'password',
        'value' => '',
        'tip'   => '这是密码组件',
    ],
    'number'        => [
        'title' => '数字',
        'type'  => 'number',
        'value' => '1.0',
        'tip'   => '这是数字组件的演示',
    ],
    'select'        => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '下拉列表',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            '1' => 'ThinkCMFX', // 值=>显示
            '2' => 'ThinkCMF',
            '3' => '跟猫玩糗事',
            '4' => '门户应用',
        ],
        'value'   => '1',
        'tip'     => '这是下拉列表组件',
    ],
    'checkbox'      => [
        'title'   => '多选框',
        'type'    => 'checkbox',
        'options' => [
            '1' => 'genmaowan.com',
            '2' => 'www.thinkcmf.com',
        ],
        'value'   => 1,
        'tip'     => '这是多选框组件',
    ],
    'radio'         => [
        'title'   => '单选框',
        'type'    => 'radio',
        'options' => [
            '1' => 'ThinkCMFX',
            '2' => 'ThinkCMF',
        ],
        'value'   => '1',
        'tip'     => '这是单选框组件',
    ],
    'radio2'        => [
        'title'   => '单选框2',
        'type'    => 'radio',
        'options' => [
            '1' => 'ThinkCMFX',
            '2' => 'ThinkCMF',
        ],
        'value'   => '1',
        'tip'     => '这是单选框组件2',
    ],
    'textarea'      => [
        'title' => '多行文本',
        'type'  => 'textarea',
        'value' => '这里是你要填写的内容',
        'tip'   => '这是多行文本组件',
    ],
    'date'          => [
        'title' => '日期',
        'type'  => 'date',
        'value' => '2017-05-20',
        'tip'   => '这是日期组件的演示',
    ],
    'datetime'      => [
        'title' => '时间',
        'type'  => 'datetime',
        'value' => '2017-05-20',
        'tip'   => '这是时间组件的演示',
    ],
    'color'         => [
        'title' => '颜色',
        'type'  => 'color',
        'value' => '#103633',
        'tip'   => '这是颜色组件的演示',
    ],
    'image'         => [
        'title' => '图片',
        'type'  => 'image',
        'value' => '',
        'tip'   => '这是图片组件的演示',
    ],
    'file'          => [
        'title' => '文件',
        'type'  => 'file',
        'value' => '',
        'tip'   => '这是文件组件的演示',
    ],
    'location'      => [
        'title' => '地理坐标',
        'type'  => 'location',
        'value' => '',
        'tip'   => '这是地理坐标组件的演示',
    ],
    'currency' => [ 
        //注意：该文件必须加下面配置，配置内容为，改支付接口支持货币单位(All)为支持所有货币
                     'title' => '支持货币单位',
                     'type'  => 'text',
                     'value' => '',
                     'tip'   => '',
                 ],
    // 如果需要展示回调地址,才配置此值！
    'notify_url'  => [ //此回调地址只会展示在后台配置中(只有显示作用),并不会保存至数据库,请将同步、异步回调地址配置在config/config.php中,有问题请参考ali_pay配置
            'title'     => '回调地址',
            'type'      => 'text',
            'value'     => $domain . '/gateway/payssion/index/notifyHandle',
            'tip'       => '回调地址',
            'attribute' => 'disabled', // 属性
        ],
];
```

### 回调文件
#### 异步回调
异步回调统一放在网关根目录下的`controller/IndexController.php`由 `notifyHandle` 方法处理
当然你也可以在controller中自定义回调方法, 回调地址为:完整域名/gateway/payssion/index/notifyHandle
回调地址范例：域名/gateway/ali_pay/index/notify_handle
方法中你可以处理相关验证，然后调用系统函数 `check_pay` 传入数组参数:
```
$data['invoice_id'] // 订单id
$data['payment']    // 支付方式
$data['paid_time']  // 支付时间
$data['trans_id']   // 三方交易id
$data['amount_in']  // 金额
$data['currency']   // 货币

check_pay($data)    // 调用系统函数进行验证及后续支付处理
```
例：`IndexController.php`
```php
<?php
namespace gateways\wx_pay\controller;
use think\Controller;

class IndexController extends Controller
{
    public function notifyHandle()
    {
        $data['invoice_id'] = $_POST['invoice_id'];
        $data['payment'] = $_POST['payment'];
        $data['paid_time'] = $_POST['paid_time'];
        $data['trans_id'] = $_POST['trans_id'];
        $data['amount_in'] = $_POST['total_fee'];
        $data['currency'] = $_POST['currency'];
        check_pay($data);
    }
}
```
#### 回调账单处理
支付回调之后账单处理如下,注意需要引入use app\home\controller\OrderController;
```
$data = array(
    'invoice_id'=>$order_id,  // 订单号(账单ID)
    'trans_id'=>$_POST['transaction_id'], // 交易流水号
    'currency'=>$currency, // 货币的3个字母的ISO代码：例如 USD CNY等
    'payment'=>'Payssion', // 网关名称,当前支付网关名称
    'amount_in'=>$amount,   // 支付金额 金额元
    'paid_time'=>date('Y-m-d H:i:s'),    // 支付时间
);
$Order = new OrderController();
$Order->orderPayHandle($data);
```
#### 支付图片地址(两种放置方式)
系统先读取方式一,若方式一图片不存在,则读取方式二;若都没有,会显示系统默认图标
一、放在public/upload/pay/下：
图片格式：支付插件名称.png;
例：AliPay.png;
二、直接放在插件根目录下：
图片格式:支付插件名称.png;
例：paypal/Paypal.png

#### 注意 异步/同步地址配置
异步、同步回调地址读取config/config.php的notify_url、return_url的值,请将同步、异步回调地址配置在config/config.php中,比如ali_pay的配置
```
<?php
$domain = configuration('domain');
return array (
		//应用ID,您的APPID。
		'app_id' => '',
		//商户私钥
		'merchant_private_key' => '',
        //异步通知地址
		'notify_url' => "{$domain}/gateway/ali_pay/index/notify_handle",
		//同步跳转
		'return_url' => "{$domain}/gateway/ali_pay/index/return_handle",
		//编码格式
		'charset' => "UTF-8",
		//签名方式
		'sign_type'=>"RSA2",
		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
		//'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",
		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
)
```