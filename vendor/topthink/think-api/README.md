# Think Api SDK For PHP

## `ThinkAPI`

`ThinkAPI`是`ThinkPHP`官方推出的统一`API`接口服务，提供接口调用服务及开发`SDK`，旨在帮助`ThinkPHP`开发者更方便的调用官方及第三方的提供的各类`API`接口及服务，从而更好的构建开发者生态，详细[参考这里](https://docs.topthink.com/think-api/)。


## 安装依赖

如果已在系统上[全局安装 Composer](https://getcomposer.org/doc/00-intro.md#globally) ，请直接在项目目录中运行以下内容来安装 Think Api SDK For PHP 作为依赖项：
```
composer require topthink/think-api
```
> 一些用户可能由于网络问题无法安装，可以使用[阿里云 Composer 全量镜像](https://developer.aliyun.com/composer) 。


## 快速使用

以查询[身份证所属地区]()接口为例

~~~
use think\api\Client;

$client = new Client("YourAppCode");

$result = $client->idcardIndex()
    ->withCardno('身份证号码')
    ->request();
~~~

所有的接口服务和方法都支持IDE自动提示和完成（请务必注意方法大小写必须保持一致），基本上不需要文档即可完成接口开发工作，`ThinkAPI`所有的API调用服务必须设置`appCode`值，用于接口调用的身份认证。

>`AppCode`的值可以在[官方服务市场](https://market.topthink.com/)`->`个人中心`->`[API管理](https://market.topthink.com/setting/api)上方查询到，每个用户账号拥有一个唯一的`AppCode`值（请不要随意泄露）。

该SDK服务仅支持官方已经接入的API接口（所有支持的接口都在官方[API市场](https://market.topthink.com/api)），目前接口数量正在扩充中，你可以联系我们反馈你需要的API接口，我们来统一进行接入。

## 返回数据

`ThinkAPI`所有的接口返回数据为`JSON`格式，通用规范如下：

| 名称 | 类型 | 说明 |
| --- | --- | --- |
| code | int | 返回码,0 表示成功 其它表示失败 |
| message| string | 返回提示信息 |
| data| object | 返回数据 |

> 如果为付费接口，则当`code`为0的时候计费，其中`data`包含的数据请参考具体的接口说明。
