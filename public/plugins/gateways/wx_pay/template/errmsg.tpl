<html class="weui-msg">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>微信支付</title>
    <link href="//cdn.staticfile.org/weui/2.5.12/style/weui.min.css" rel="stylesheet">
    <style>.page{ position:absolute;top:0;right:0;bottom:0;left:0;overflow-y:auto;-webkit-overflow-scrolling:touch;box-sizing:border-box}</style>
</head>
<body>
<div class="container">
<div class="page">
<div class="weui-form">
    <div class="weui-msg__icon-area" id="show_icon">
        <i class="weui-icon-warn weui-icon_msg"></i>
    </div>
    <div class="weui-msg__text-area">
        <h2 class="weui-msg__title" id="show_msg">{$msg}</h2>
    </div>
    <div class="weui-form__opr-area" id="close_btn">
        <a href="javascript:;" class="weui-btn weui-btn_warn" id="Close">关闭</a>
    </div>
    <div class="weui-form__extra-area">
        <div class="weui-footer"><p class="weui-footer__links"></p><p class="weui-footer__text">{$company_name}</p></div>
    </div>
</div>
</div>
</div>
<script src="//cdn.staticfile.org/jquery/1.12.4/jquery.min.js"></script>
<script src="//cdn.staticfile.org/layer/3.1.1/layer.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });

function jsApiCall() {
    $('#Close').click(function() {
        WeixinJSBridge.call('closeWindow');
    });
}
window.onload = function(){
    if (typeof WeixinJSBridge == "undefined"){
        if( document.addEventListener ){
            document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
        }else if (document.attachEvent){
            document.attachEvent('WeixinJSBridgeReady', jsApiCall);
            document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
        }
    }else{
        jsApiCall();
    }
};
</script>
</body>
</html>