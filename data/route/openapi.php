<?php

$origin = request()->header("origin");
think\facade\Route::get("document", "openapi/Document/index");
think\facade\Route::group("v1", function () {
    think\facade\Route::get("captcha", "openapi/Public/captcha");
    think\facade\Route::post("code", "openapi/Public/code");
    think\facade\Route::post("second_verify", "openapi/Public/secondVerify");
    think\facade\Route::get("gateway", "openapi/Public/gateway");
    think\facade\Route::get("login", "openapi/Login/loginPage");
    think\facade\Route::post("login_api", "openapi/Login/loginAPI");
    think\facade\Route::post("login", "openapi/Login/login");
    think\facade\Route::get("register", "openapi/Login/registerPage");
    think\facade\Route::post("register", "openapi/Login/register");
    think\facade\Route::get("pwreset", "openapi/Login/pwresetPage");
    think\facade\Route::post("pwreset", "openapi/Login/pwreset");
    think\facade\Route::get("goods/[:fgid]/[:gid]/[:pid]", "openapi/Cart/goods");
    think\facade\Route::get("goodsconfig", "openapi/Cart/goodsConfig");
    think\facade\Route::post("goods/total", "openapi/Cart/goodsTotal");
    think\facade\Route::delete("cart/goods/:position", "openapi/Cart/cartRemove");
    think\facade\Route::get("cart/goods/:position", "openapi/Cart/cartEditPage");
    think\facade\Route::put("cart/goods/:position", "openapi/Cart/cartEdit");
    think\facade\Route::put("cart/goods/:position/qty", "openapi/Cart/cartModifyQty");
    think\facade\Route::post("cart/goods", "openapi/Cart/addGoods");
    think\facade\Route::get("products", "openapi/Cart/products");
    think\facade\Route::get("productsconfig", "openapi/Cart/productsConfig");
    think\facade\Route::post("products/total", "openapi/Cart/productsTotal");
    think\facade\Route::get("cart", "openapi/Cart/cartPage");
    think\facade\Route::delete("cart/products/:position", "openapi/Cart/cartRemove");
    think\facade\Route::get("cart/products/:position", "openapi/Cart/cartEditPage");
    think\facade\Route::put("cart/products/:position", "openapi/Cart/cartEdit");
    think\facade\Route::put("cart/products/:position/qty", "openapi/Cart/cartModifyQty");
    think\facade\Route::post("cart/products", "openapi/Cart/addProducts");
    think\facade\Route::post("cart/promo", "openapi/Cart/cartAddPromo");
    think\facade\Route::delete("cart/promo", "openapi/Cart/cartRemovePromo");
    think\facade\Route::get("cart/goods/:position", "openapi/Cart/cartEditPage");
    think\facade\Route::put("cart/goods/:position", "openapi/Cart/cartEdit");
})->header("Access-Control-Allow-Origin", $origin)->middleware("UserCheck")->allowCrossDomain();
think\facade\Route::group("v1", function () {
    think\facade\Route::get("user", "openapi/User/userPage");
    think\facade\Route::post("user", "openapi/User/user");
    think\facade\Route::get("security_info", "openapi/User/securityinfo");
    think\facade\Route::put("password", "openapi/User/password");
    think\facade\Route::put("phone_bind", "openapi/User/phoneBind");
    think\facade\Route::put("email_bind", "openapi/User/emailBind");
    think\facade\Route::put("login_notice", "openapi/User/loginNotice");
    think\facade\Route::get("real_name_auth", "openapi/User/realNameAuth");
    think\facade\Route::post("real_name_auth/person", "openapi/User/personRealNameAuth");
    think\facade\Route::post("real_name_auth/company", "openapi/User/companyRealNameAuth");
    think\facade\Route::get("real_name_auth/status", "openapi/User/realNameAuthStatus");
    think\facade\Route::get("products/cates", "openapi/Product/getProductsCates");
    think\facade\Route::get("products/cates/:id", "openapi/Product/getProducts");
    think\facade\Route::get("products/:id", "openapi/Product/getProductDetail");
    think\facade\Route::get("products/:id/renew", "openapi/Product/renewPage");
    think\facade\Route::post("products/:id/renew", "openapi/Product/renew");
    think\facade\Route::put("products/:id/renew", "openapi/Product/renewAuto");
    think\facade\Route::get("products/renew/batch", "openapi/Product/renewBatchPage");
    think\facade\Route::post("products/renew/batch", "openapi/Product/renewBatch");
    think\facade\Route::get("products/:id/actions/upgradeconfig", "openapi/Product/upgradeConfigPage");
    think\facade\Route::post("products/:id/actions/upgradeconfig", "openapi/Product/upgradeConfig");
    think\facade\Route::post("products/:id/actions/upgradeconfig/checkout", "openapi/Product/upgradeConfigCheckout");
    think\facade\Route::put("products/:id/actions/upgradeconfig/promo", "openapi/Product/upgradeConfigPromo");
    think\facade\Route::delete("products/:id/actions/upgradeconfig/promo", "openapi/Product/upgradeConfigPromoRemove");
    think\facade\Route::get("hosts/:id/actions/upgradeconfig", "openapi/Product/upgradeConfigPage1");
    think\facade\Route::post("hosts/:id/actions/upgradeconfig", "openapi/Product/upgradeConfig1");
    think\facade\Route::post("hosts/:id/actions/upgradeconfig/checkout", "openapi/Product/upgradeConfigCheckout1");
    think\facade\Route::get("hosts/cates", "openapi/Host/getHostsCates");
    think\facade\Route::get("hosts", "openapi/Host/getHosts");
    think\facade\Route::get("hosts/:id", "openapi/Host/getHostDetail");
    think\facade\Route::get("hosts/:id/logs", "openapi/Host/getHostLogs");
    think\facade\Route::get("hosts/:id/downloads", "openapi/Host/getHostDownloads");
    think\facade\Route::get("hosts/:id/downloads/:download", "openapi/Host/hostDownloadFile");
    think\facade\Route::get("hosts/:id/renew", "openapi/Host/renewPage");
    think\facade\Route::post("hosts/:id/renew", "openapi/Host/renew");
    think\facade\Route::put("hosts/:id/renew", "openapi/Host/renewAuto");
    think\facade\Route::get("hosts/renew/batch", "openapi/Host/renewBatchPage");
    think\facade\Route::post("hosts/renew/batch", "openapi/Host/renewBatch");
    think\facade\Route::get("hosts/:id/cancel", "openapi/Host/getCancelPage");
    think\facade\Route::post("hosts/:id/cancel", "openapi/Host/postCancel");
    think\facade\Route::delete("hosts/:id/cancel", "openapi/Host/deleteCancel");
    think\facade\Route::get("hosts/:id/actions/upgradeconfig", "openapi/Host/upgradeConfigPage");
    think\facade\Route::post("hosts/:id/actions/upgradeconfig", "openapi/Host/upgradeConfig");
    think\facade\Route::get("hosts/:id/actions/upgradeconfig/select", "openapi/Host/upgradeConfigSelect");
    think\facade\Route::post("hosts/:id/actions/upgradeconfig/checkout", "openapi/Host/upgradeConfigCheckout");
    think\facade\Route::put("hosts/:id/actions/upgradeconfig/promo", "openapi/Product/upgradeConfigPromo");
    think\facade\Route::delete("hosts/:id/actions/upgradeconfig/promo", "openapi/Host/upgradeConfigPromoRemove");
    think\facade\Route::get("hosts/:id/actions/upgrade", "openapi/Host/upgradeHostPage");
    think\facade\Route::post("hosts/:id/actions/upgrade", "openapi/Host/upgradeHost");
    think\facade\Route::put("hosts/:id/actions/upgrade/promo", "openapi/Host/upgradeHostAddPromo");
    think\facade\Route::delete("hosts/:id/actions/upgrade/promo", "openapi/Host/upgradeHostRemovePromo");
    think\facade\Route::post("hosts/:id/actions/upgrade/checkout", "openapi/Host/upgradeHostCheckout");
    think\facade\Route::get("hosts/:id/module", "openapi/Host/module");
    think\facade\Route::put("hosts/:id/module/repassword", "openapi/Host/repassword");
    think\facade\Route::get("hosts/:id/module/reinstall", "openapi/Host/getReinstall");
    think\facade\Route::put("hosts/:id/module/reinstall", "openapi/Host/reinstall");
    think\facade\Route::put("hosts/:id/module/on", "openapi/Host/on");
    think\facade\Route::put("hosts/:id/module/off", "openapi/Host/off");
    think\facade\Route::put("hosts/:id/module/reboot", "openapi/Host/reboot");
    think\facade\Route::put("hosts/:id/module/hard_off", "openapi/Host/hardOff");
    think\facade\Route::put("hosts/:id/module/hard_reboot", "openapi/Host/hardReboot");
    think\facade\Route::put("hosts/:id/module/bmc", "openapi/Host/bmc");
    think\facade\Route::put("hosts/:id/module/kvm", "openapi/Host/kvm");
    think\facade\Route::put("hosts/:id/module/ikvm", "openapi/Host/ikvm");
    think\facade\Route::put("hosts/:id/module/vnc", "openapi/Host/vnc");
    think\facade\Route::put("hosts/:id/module/rescue", "openapi/Host/rescue");
    think\facade\Route::get("hosts/:id/module/charts", "openapi/Host/charts");
    think\facade\Route::get("hosts/:id/module/custom", "openapi/Host/custom");
    think\facade\Route::get("hosts/:id/module/status", "openapi/Host/status");
    think\facade\Route::post("hosts/:id/module/reinstall_buy", "openapi/Host/reinstallBuy");
    think\facade\Route::get("tickets", "openapi/Ticket/getTickets");
    think\facade\Route::get("tickets/:id", "openapi/Ticket/ticketDetail");
    think\facade\Route::post("tickets", "openapi/Ticket/createTicket");
    think\facade\Route::post("tickets/:id/reply", "openapi/Ticket/replyTicket");
    think\facade\Route::get("tickets/page", "openapi/Ticket/getOpenTicketPage");
    think\facade\Route::delete("cart/clear", "openapi/Cart/cartClear");
    think\facade\Route::post("cart/checkout", "openapi/Cart/cartCheckout");
    think\facade\Route::post("pay", "openapi/Pay/pay");
    think\facade\Route::post("invoices/:id/fund", "openapi/Pay/fund");
    think\facade\Route::delete("invoices/:id/fund", "openapi/Pay/fundDelete");
    think\facade\Route::post("invoices/:id/credit", "openapi/Pay/credit");
    think\facade\Route::get("invoices/:id/status", "openapi/Pay/status");
    think\facade\Route::get("invoices/:id", "openapi/Invoices/invoices");
    think\facade\Route::post("invoices/combines", "openapi/Invoices/combineInvoices");
    think\facade\Route::post("funds", "openapi/Invoices/funds");
    think\facade\Route::get("funds", "openapi/Invoices/fundsInfo");
    think\facade\Route::get("transactions/funds", "openapi/Invoices/accountsRecord");
    think\facade\Route::get("affiliates", "openapi/Affiliate/affiliate");
    think\facade\Route::put("affiliates", "openapi/Affiliate/affiliateActive");
    think\facade\Route::post("affiliates/withdraw", "openapi/Affiliate/withdraw");
    think\facade\Route::get("affiliates/withdraw_record", "openapi/Affiliate/withdrawRecord");
    think\facade\Route::get("affiliates/record", "openapi/Affiliate/affiliateRecord");
    think\facade\Route::get("affiliates/user", "openapi/Affiliate/user");
    think\facade\Route::get("news", "openapi/News/news");
    think\facade\Route::get("news/:id", "openapi/News/newsContent");
    think\facade\Route::get("knowledgebase", "openapi/Knowledgebase/knowledgebase");
    think\facade\Route::get("knowledgebase/:id", "openapi/Knowledgebase/knowledgebaseContent");
    think\facade\Route::get("downloads", "openapi/Downloads/getDownloads");
    think\facade\Route::get("downloads/:id", "openapi/Downloads/Downloads");
    think\facade\Route::get("log/system", "openapi/Log/systemLog");
    think\facade\Route::get("log/login", "openapi/Log/loginLog");
    think\facade\Route::get("log/api", "openapi/Log/apiLog");
    think\facade\Route::get("message", "openapi/Message/message");
    think\facade\Route::put("message/:id", "openapi/Message/readMessage");
    think\facade\Route::delete("message/:id", "openapi/Message/deleteMessage");
})->header("Access-Control-Allow-Origin", $origin)->middleware("Check")->allowCrossDomain();

?>