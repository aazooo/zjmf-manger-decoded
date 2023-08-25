<?php

return ["title" => "公共接口", "item" => ["secondVerify" => ["title" => "提交二次验证", "desc" => "提交二次验证,在其它接口返回的数据中提示需要二次验证的，第一步，“获取验证码”接口，第二步，提交用户填的验证码到该接口", "url" => "/v1/second_verify", "method" => "POST", "auth" => "智简魔方", "version" => "v1", "param" => [["name" => "account", "type" => "string", "require" => "必填", "max" => "-", "desc" => "发送验证码的手机号或者邮箱", "example" => "", "child" => []], ["name" => "code", "type" => "string", "require" => "必填", "max" => "-", "desc" => "验证码", "example" => ""]]], "code" => ["title" => "获取验证码", "desc" => "发送手机或邮件验证码", "url" => "/v1/code", "method" => "POST", "auth" => "智简魔方", "version" => "v1", "param" => [["name" => "action", "type" => "string", "require" => "必填", "max" => "-", "desc" => "验证码支持的方式：<br> 
					login_phone_code 手机验证码登录<br> 
					register_email 邮箱注册<br> 
					register_phone 手机注册<br> 
					pwreset_email 邮箱找回密码<br> 
					pwreset_phone 手机找回密码<br> 
					second_phone 手机二次验证<br> 
					second_email 邮箱二次验证<br> 
					bind_phone 手机绑定<br> 
					bind_email 邮箱绑定<br> 
					login_notice_phone 登录短信提醒<br> 
					login_notice_email 登录邮件提醒<br> 
					", "example" => ""], ["name" => "type", "type" => "string", "require" => "必填", "max" => "-", "desc" => "发送类型有：<br> phone 手机<br> email 邮箱<br> ", "example" => "phone"], ["name" => "phone_code", "type" => "string", "require" => "", "max" => "-", "desc" => "发送类型是手机号时要传手机区号，不传默认+86", "example" => "", "child" => []], ["name" => "account", "type" => "string", "require" => "必填", "max" => "-", "desc" => "手机号或者邮箱", "example" => "", "child" => []], ["name" => "captcha", "type" => "string", "require" => "开启状态验证码必填", "max" => "-", "desc" => "图形验证码，对应的接口开启了才需要", "example" => "", "child" => []], ["name" => "idtoken", "type" => "string", "require" => "开启状态验证码必填", "max" => "-", "desc" => "获取图形验证码图片时返回的idtoken的值", "example" => "", "child" => []]]], "captcha" => ["title" => "获取图形验证码图片", "desc" => "", "url" => "/v1/captcha", "method" => "GET", "auth" => "智简魔方", "version" => "v1", "return" => [["name" => "img", "type" => "string", "require" => "必填", "max" => "-", "desc" => "base64的图片", "example" => "", "child" => []], ["name" => "idtoken", "type" => "string", "require" => "必填", "max" => "-", "desc" => "提交图片验证码的时候要提交这个参数", "example" => "", "child" => []]]], "gateway" => ["title" => "获取支付方式", "desc" => "", "url" => "/v1/gateway", "method" => "GET", "auth" => "智简魔方", "version" => "v1", "return" => [["name" => "name", "type" => "string", "require" => "", "max" => "-", "desc" => "名称", "example" => "", "child" => []], ["name" => "title", "type" => "string", "require" => "", "max" => "-", "desc" => "名称", "example" => "", "child" => []], ["name" => "img", "type" => "string", "require" => "", "max" => "-", "desc" => "支付接口图标", "example" => "", "child" => []]]]]];