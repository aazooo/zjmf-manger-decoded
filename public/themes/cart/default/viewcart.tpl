<script>
	var _url = '';
	$(function(){
		// 购物车返回按钮
		// $('.backBtn').show();
		$(".checkbox").on('change','.payment-checkbox',function(){
			if($(this).parent().siblings(".checkboxDiv").length!=0){
				if($(this).parent().siblings(".checkboxDiv").hasClass('checkboxSelect')==true) $(this).parent().siblings(".checkboxDiv").removeClass("checkboxSelect")
			}
			if($(this).prop('checked')){
				$(this).parent().addClass("checkboxSelect");
				if($(this).parent().siblings(".checkboxDiv").length!=0){
					$(this).parent().siblings(".checkboxDiv").find('input').prop("checked",false)
				}
				// 移除支付方式选中
				$(".addfunds-payment").removeClass("active").find(".hidden").removeAttr("checked")
			}else{
				$(this).parent().removeClass("checkboxSelect");
			}
		})
		$(".addfunds-payment").click(function(){
			$(this).find(".hidden").attr("checked","checked");
			$(this).addClass('active').parent().siblings('.addfunds').find(".addfunds-payment").removeClass("active").find(".hidden").removeAttr("checked");
			$('input[name="paymt"]').prop('checked', false).parent().removeClass("checkboxSelect");
		})
		// 配置详情展开折叠
		$('.card-body').on('click','.goods_info .title',function(){
			$(this).find('font').toggleClass('zk');
			$(this).siblings('.info').slideToggle();
		})
		$('.card-body').on('click','.all_checkbox',function(_this){

			let arr=$('.son_check')   //所有商品
			//总价
			let price = '0.00'
				,len = 0;
			if(arr.length!=0)
			{
				if(_this.target.checked) {
					for (var i = 0; i < arr.length; i++) {
						arr[i].checked = _this.target.checked
						price = (parseFloat($(arr[i]).data('price').trim()) + parseFloat(price)).toFixed(2);
						len++;
					}
				}else{
					for (var i = 0; i < arr.length; i++) {
						arr[i].checked = _this.target.checked
					}
				}
			}
			$('.len-num').text(len);
			$('.price-num').text(price);
		})
		$('.card-body').on('click','.son_check',function(_this){

			if(!_this.target.checked) $('.all_checkbox')[0].checked=false
			let arr=$('.son_check')   //所有商品
			//			总价
			let price = '0.00'
					,len = 0;
			for (var i = 0; i < arr.length; i++) {
				if(arr[i].checked)
				{
					price = (parseFloat($(arr[i]).data('price').trim()) + parseFloat(price)).toFixed(2);
					len++;
				}
			}

			if((arr.filter((index,item)=> item.checked==true).length) == arr.length) $('.all_checkbox')[0].checked=true

			$('.len-num').text(len);
			$('.price-num').text(price);
		})
		$('.administrationBtn').click(function(){
			$('.administrationBtn').hide()
			$('.completeBtn').show()
			$('.payDiv').hide()
			$('.deleteBtn').show()
		})
		$('.completeBtn').click(function(){
			$('.administrationBtn').show()
			$('.completeBtn').hide()
			$('.payDiv').show()
			$('.deleteBtn').hide()
		})
	//	删除按钮点击时
		$('.deleteBtn > button').click(function() {
			let arr = $('.son_check')   //所有商品
			if(arr.length == 0)
			{
				return;
			}
			var is = [];
			for(var i=0;i<arr.length;i++){
				if(arr[i].checked)
				{
					is.push($(arr[i]).data('val'));
				}
			}
			if(is.length <= 0)
			{
				//提示框
				toastr.error('请选择要删除的商品！')
				return false;
			}
			removeItem('cart?action=viewcart&statuscart=remove', '{$Lang.delete_item}', '您确定要删除这'+ is.length +'种商品吗？', {i: is});
		})
	//	立即结账按钮
	// 	$('.submit-btn').click(function(){
	// 		let arr=$('.son_check:checked');
	// 		if(arr.length <= 0)
	// 		{
	// 			//提示框
	// 			toastr.error('请至少选择一个商品！')
	// 			return false;
	// 		}
	// 	})
	})
</script>
<link type="text/css" href="/themes/cart/default/assets/js/toastr/build/toastr.min.css?v={$Ver}"
	rel="stylesheet" />
<script src="/themes/cart/default/assets/js/toastr/build/toastr.min.js?v={$Ver}"></script>
<script src="/themes/cart/default/assets/js/viewcart.js?v={$Ver}"></script>
<style>
@media (min-width: 560px) and (max-width: 1355px) {
	.addfunds-payment img{
		height: 16px;
	}
}
.modal-body{
  -moz-box-sizing: border-box;  
     -webkit-box-sizing: border-box; 
     -o-box-sizing: border-box; 
     -ms-box-sizing: border-box; 
}
.goods_info{
	width:100%;
	padding: 10px 0px;
}
.goods_info span {
	display: block;
}
.goods_info .title{
	text-indent: 10px;
	color: #409eff;
	cursor: pointer;
}
.goods_info .title font {
	float: left;
	color: #666;
	font-family: cursive;
	font-size: 12px;
	margin-top: 1px;
}
.goods_info .title .zk {
	transform: rotate(90deg);
	margin-top: -4px;
}
.goods_info .info{
	width: 100%;
	color: #333;
	text-indent: 27px;
	line-height: 24px;
}
.custom-controlTwo{
    position: relative;
    z-index: 1;
    display: block;
    min-height: 1.21875rem;
    padding-left: 1.5rem;
    -webkit-print-color-adjust: exact;
}
.completeBtn{
	display:none;
}
.deleteBtn{
	display:none;
}
.mobile-bottom-total{
		display: none;
		position: fixed;
		width: 100%;
    padding-right: 1.25rem;
		background: #FFFFFF;
		box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.1);
		z-index: 99999;
		bottom: 0;
		left: 0;
	}
	.mobile-flex{
		display: flex;
    align-items: center;
    justify-content: space-between;
    padding-left: 1rem;
	}
</style>

<form id="submit-form" method="post" action="cart?action=viewcart&statuscart=checkout">
	<input type="hidden" name="register_or_login" value="register">
	<div class="row">
		<div class="col-md-8">
			{if $ErrorMsg}
			<div class="alert alert-danger">
				<a href="#" class="close" data-dismiss="alert">
					&times;
				</a>
				<strong>{$ErrorMsg}</strong>
			</div>
			{/if}
			<div class="card">
				<div class="card-body">
					<h5 class="mb-0">{$Lang.payment_order}</h5>
				</div>
				<hr class="mb-0 mt-0" />
				<div class="card-body">
					{if $Userinfo}
					<div class="col-sm-12 mb-3 mt-4">
						<span style="color:#979699;">{$Lang.dear_customers}：</span>
							{if $Userinfo.user.username}
								{$Userinfo.user.username}
							{else}
								{$Userinfo.user.email}
							{/if}
							,{$Lang.hello}!
					</div>
					<div class="col-sm-12 mb-3 mt-4">
						<span style="color:#979699;">手机： </span>
						{if $Userinfo.user.phonenumber} {$Userinfo.user.phonenumber} {else} - {/if}
					</div>
					<div class="col-sm-12 mb-3 mt-4">
						<span style="color:#979699;">邮箱： </span>
						{if $Userinfo.user.email} {$Userinfo.user.email} {else} - {/if}
					</div>
					<div class="col-sm-12 mb-3 mt-4">
						<span style="color:#979699;">地址： </span>
						{if $Userinfo.user.address1} {$Userinfo.user.address1} {else} - {/if}
					</div>
					<div class="col-sm-12 mb-3 mt-4">
						<span style="color:#979699;">销售： </span>
						{if $Userinfo.sale_name} {$Userinfo.sale_name} {else} - {/if}
					</div>
					{else/}
					<div class="d-flex justify-content-between align-items-center">
						<p class="mb-0">{$Lang.registration_login}</p>
						<span class="btn btn-sm btn-primary old-user" onClick="changeType('old')">{$Lang.please_click_here}</span>
						<span class="btn btn-sm btn-warning new-user" onClick="changeType('new')">{$Lang.new_account}</span>
					</div>
					<div class="old-user">
						<div class="row mt-4">
							{if $Register.allow_register_phone==1 && $Register.allow_register_email==1}
							<div class="col-sm-12 mb-3">
								<div class="btn-group btn-group-toggle mt-2 mt-xl-0 cart-register" data-toggle="buttons" id="register">
									<label class="btn btn-primary btn-sm active"><input type="radio" class="input_active" checked=""
											value="phone">{$Lang.mobile_registration}</label>
									<label class="btn btn-primary btn-sm"><input type="radio" value="email">{$Lang.email_registration}</label>

								</div>
							</div>
							{/if}
							{if $Register.allow_register_phone==1}
							<div class="col-sm-6 mb-3 registerphone">
								<div style="float:left;padding:0px;" class="col-sm-3" id="register_phone_code">
									<select class="form-control" name="phone_code" id="phoneCodeSel">
										{foreach $SmsCountry as $list}
										<option value="{$list.phone_code}" {if $list.phone_code=="+86" } selected{/if}>
											{$list.link}
										</option>
										{/foreach}
									</select>
								</div>
								<input class="form-control col-sm-9" name="phone" id="register_phone" placeholder="{$Lang.phone_number}" />
							</div>
							{if $Register.is_captcha==1 && $Register.allow_register_phone_captcha==1}
							<div class="col-sm-6 mb-3 registerphone">
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="captcha" placeholder="{$Lang.graphic_verification_code}" />
									</div>
									<div class="input-group-append ml-2">
										<img onclick="reloadcode(this,'allow_register_phone_captcha')"
											src="/verify?name=allow_register_phone_captcha" alt="" height="36px">

									</div>
								</div>
							</div>
							{/if}
							<div class="col-sm-6 mb-3 registerphone">
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" id="phone_code" name="code" placeholder="{$Lang.phone_verification_code}" />
									</div>
									<div class="input-group-append">
										<button id="register_phone_get_code" class="btn btn-primary" type="button"
											onclick="getCode('phone',this)">{$Lang.get_mobile_phone_verification_code}</button>
									</div>
								</div>
							</div>
							{/if}
							{if $Register.allow_register_email==1}
							<div class="col-sm-6 mb-3 registeremail" {if $Register.allow_register_phone==1 &&
								$Register.allow_register_email==1} style="display:none;" {/if}>
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="email" id="register_email" placeholder="{$Lang.email_address}" />
									</div>
								</div>
							</div>
							{if $Register.is_captcha==1 && $Register.allow_register_email_captcha==1}
							<div class="col-sm-6 mb-3 registeremail" {if $Register.allow_register_phone==1 &&
								$Register.allow_register_email==1} style="display:none;" {/if}>
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="captcha" placeholder="{$Lang.graphic_verification_code}" />
									</div>
									<div class="input-group-append ml-2">
										<img onclick="reloadcode(this,'allow_register_email_captcha')"
											src="/verify?name=allow_register_email_captcha" alt="" height="36px">

									</div>
								</div>
							</div>
							{/if}
							{if $Register.allow_email_register_code==1}
							<div class="col-sm-6 mb-3 registeremail" {if $Register.allow_register_phone==1 &&
								$Register.allow_register_email==1} style="display:none;" {/if}>
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" id="email_code" name="code" placeholder="邮箱验证码" />
									</div>
									<div class="input-group-append">
										<button id="register_email_get_code" class="btn btn-primary" type="button"
											onclick="getCode('email',this)">{$Lang.get_email_verification_code}</button>
									</div>
								</div>
							</div>
							{/if}
							{/if}
							<div class="col-sm-6 mb-3">
								<input name="password" type="password" class="form-control" placeholder="{$Lang.password}" />
							</div>
							<div class="col-sm-6 mb-3">
								<input name="repassword" type="password" class="form-control" placeholder="{$Lang.confirm_password}" />
							</div>
							{foreach $Register.login_register_custom_require as $list}
							<div class="col-sm-6 mb-3">
								<input name="{$list.name}" class="form-control"
									placeholder="{$Register.login_register_custom_require_list[$list.name]}" />
							</div>
							{/foreach}

							{foreach $Register.fields as $k => $list}
							<div class="col-sm-6 mb-3">
								{if $list.fieldtype == 'dropdown'}
								<!-- 下拉 -->
								<select name="fields[{$list.id}]" class="form-control ">
									{foreach $list.dropdown_option as $key => $val}
									<option value="{$key}">{$val}</option>
									{/foreach}
								</select>
								{elseif $list.fieldtype == 'password'}
								<!-- 密码 -->
								<input name="fields[{$list.id}]" type="password" class="form-control" placeholder="{$list.fieldname}" />
								{elseif $list.fieldtype == 'text'}
								<!-- 文本框 -->
								<input name="fields[{$list.id}]" type="text" class="form-control" placeholder="{$list.fieldname}" />
								{elseif $list.fieldtype == 'link'}
								<!-- 链接输入框 -->
								<input name="fields[{$list.id}]" type="text" class="form-control" placeholder="{$list.fieldname}" />
								{elseif $list.fieldtype == 'tickbox'}
								<!-- 选项框 -->
								<input type="checkbox" name="fields[{$list.id}]">{$list.fieldname}
								{elseif $list.fieldtype == 'textarea'}
								<!-- 文本域 -->
								<textarea name="fields[{$list.id}]" cols="30" rows="10" class="form-control"></textarea>
								{/if}
							</div>
							{/foreach}


							<!--销售-->
							{if $setsaler == '2'}
							<div class="col-sm-12 mb-3" style="color:#979699;">{$Lang.sales_representative}</div>
							<div class="col-sm-6">
								<select name="sale_id" class="form-control ">
									<option value="0">{$Lang.nothing}</option>
									{foreach $saler as $saler}
									<option value="{$saler.id}" {if($sale && $sale == $saler.id)} selected {/if}>{$saler.user_nickname}</option>
									{/foreach}
								</select>
							</div>
							{/if}
						</div>
					</div>

					<div class="new-user">
						<div class="row mt-4">
							{if $Login.allow_login_phone==1 && $Login.allow_login_email==1}
							<div class="col-sm-12 mb-3">
								<div class="btn-group btn-group-toggle mt-2 mt-xl-0 cart-login" data-toggle="buttons" id="login">
									<label class="btn btn-primary btn-sm active"><input type="radio" class="input_active" checked=""
											value="phone">{$Lang.mobile_login}</label>
									<label class="btn btn-primary btn-sm"><input type="radio" value="email">{$Lang.email_login}</label>

								</div>
							</div>
							{/if}
							{if $Login.allow_login_phone==1}
							<div class="col-sm-6 mb-3 loginphone">
								<div style="float:left;padding:0px;" class="col-sm-3" id="register_phone_code">
									<select class="form-control" name="phone_code" id="phoneCodeSel">
										{foreach $SmsCountry as $list}
										<option value="{$list.phone_code}" {if $list.phone_code=="+86" } selected{/if}>
											{$list.link}
										</option>
										{/foreach}
									</select>
								</div>
								<input class="form-control col-sm-9" name="phone" id="register_phone" placeholder="{$Lang.phone_number}" />
							</div>
							{if $Login.is_captcha==1 && $Login.allow_login_phone_captcha==1}
							<div class="col-sm-6 mb-3 loginphone">
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="captcha" placeholder="{$Lang.graphic_verification_code}" />
									</div>
									<div class="input-group-append ml-2">
										<img onclick="reloadcode(this,'allow_login_phone_captcha')"
											src="/verify?name=allow_login_phone_captcha" alt="" height="36px">

									</div>
								</div>
							</div>
							{/if}
							<!--<div class="col-sm-6 mb-3 loginphone">
							<div class="input-group">
								<div class="custom-file">
									<input class="form-control" id="phone_code" name="code" placeholder="手机验证码" />
								</div>
								<div class="input-group-append">
									<button id="register_phone_get_code" class="btn btn-primary" type="button" >获取手机验证码</button>
								</div>
							</div>
						</div>-->
							{/if}
							{if $Login.allow_login_email==1 || $Login.allow_id}
							<div class="col-sm-6 mb-3 loginemail" {if $Login.allow_login_phone==1 && $Login.allow_login_email==1}
								style="display:none;" {/if}>
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="email"
											placeholder="{if $Login.allow_login_email && $Login.allow_id}{$Lang.input_email_id}{elseif $Login.allow_login_email && !$Login.allow_id}{$Lang.input_email}{elseif !$Login.allow_login_email && $Login.allow_id}{$Lang.input_id}{/if}" />
									</div>
								</div>
							</div>
							{if $Login.is_captcha==1 && $Login.allow_login_email_captcha==1}
							<div class="col-sm-6 mb-3 loginemail" {if $Login.allow_login_phone==1 && $Login.allow_login_email==1}
								style="display:none;" {/if}>
								<div class="input-group">
									<div class="custom-file">
										<input class="form-control" name="captcha" placeholder="{$Lang.graphic_verification_code}" />
									</div>
									<div class="input-group-append ml-2">
										<img onclick="reloadcode(this,'allow_login_email_captcha')"
											src="/verify?name=allow_login_email_captcha" alt="" height="36px">

									</div>
								</div>
							</div>
							{/if}
							{/if}


							{if $Login.allow_login_email || $Login.allow_login_phone || $Login.allow_id}
							<div class="col-sm-6 mb-3">
								<input class="form-control" name="password" type="password" placeholder="{$Lang.password}" />
							</div>
							{else /}
							{$Lang.login_not_open}
							{/if}
						</div>

					</div>
					{/if}
					{if $Setting.cart_product_description}
					<div class="alert alert-success alert-dismissable mt-5">{$Setting.cart_product_description}</div>
					{/if}
					<!--销售-->
					{if $Userinfo && $Userinfo.user.sale_id == '0' && $sale_setting == '2'}
					<div class="col-sm-12 mb-3" style="color:#979699;">{$Lang.sales_representative}</div>
					<div class="col-sm-6">
						<select name="sale_id" class="form-control ">
							<option value="0">{$Lang.nothing_two}</option>
							{foreach $saler as $saler}
							<option value="{$saler.id}" {if($sale && $sale == $saler.id)} selected {/if}>{$saler.user_nickname}</option>
							{/foreach}
						</select>
					</div>
					{/if}

					<div class="col-sm-12 mb-3" style="color:#979699;margin-top:16px;">客户备注</div>
					<div class="col-sm-6">
						<input type="text" class="form-control remarksInput" placeholder="选填，请先和商家协商一致" value="" maxLength="200" name="notes">
					</div>

					<p class="mt-5">{$Lang.payment_method}</p>

					<div class="col-sm-12 mb-3 checkbox checkDiv" style="display: flex;align-items: center;">
						<!-- 其他方式：
						<input type="radio" name="paymt" data-name="switch" value="" id="paymt"> -->
						{if $Userinfo.user.credit > 0}
							<div class="checkboxDiv">	
								<input class="payment-checkbox" type="checkbox" name="paymt" data-name="switch" value="credit" id="paymt" style="margin-right:5px"
										{if (isset($ShopData.total_price) && $ShopData.total_price <= $Userinfo.user.credit)} checked {/if} > 使用余额支付
								<span class="mr-1" style="margin-left: 35px;">
									<img width="20" src="/themes/clientarea/default/assets/images/gold.svg" alt="">
								</span>
							{$ShopData.currency.prefix}{$Userinfo.user.credit}
							</div>
							
						{/if}

						{if !empty($client.is_open_credit_limit) && $client.credit_limit_balance >= $ShopData.total_price && $is_open_shd_credit_limit}
							<div class="checkboxDiv">
								<input class="payment-checkbox" type="checkbox" name="paymt" data-name="switch" value="credit_limit" id="paymt" style="margin-right:5px;"> 使用信用额支付
								<span class="mr-1" style="margin-left: 35px;padding-bottom:5px;">
									<img width="20" src="/themes/clientarea/default/assets/images/CreditCard.png" alt="">
								</span>
								{$ShopData.currency.prefix}{$client.credit_limit_balance}
							</div>
						{/if}
					</div>

					<div class="row">
						{foreach $ShopData.gateway_list as $list}
						<div class="col-sm-3 addfunds">

							<div class="addfunds-payment {if $list.name==$ShopData.default_gateway}active{/if}"
								data-payment="{$list.name}" title="" data-toggle="tooltip" data-placement="bottom"
								data-original-title="{$list.title}">
								<input type="radio" name="payment" class="hidden" value="{$list.name}" {if
									$list.name==$ShopData.default_gateway}checked="checked" {/if}>
								{if $list.author_url}
								<img src="{$list.author_url}" />
								{else/}
								{$list.title}
								{/if}
							</div>
						</div>
						{/foreach}
					</div>

					<p class="mt-5">{$Lang.discount_code}</p>

					{if $ShopData.promo}
					<div class="input-group">
						{$ShopData.promo.promo_desc_str}
						<a href="javascript:;" style="margin-left:20px;" id="removepromo">{$Lang.remove}</a>
					</div>
					{else/}
					<div class="input-group" id="promo">
						<div class="custom-file">
							<input type="text" class="form-control" name="promo" value="{$promocode}" placeholder="{$Lang.discount_code}">
						</div>
						<div class="input-group-append">
							<button class="btn btn-primary" type="button">{$Lang.application}</button>
						</div>

					</div>
					{/if}

				</div>
			</div>
		</div>


		<div class="col-md-4">
			<div class="card">
				<div class="card-body">
					<div style="display:flex;align-items: center;justify-content: space-between;">
						<div class="invoice-title" style="display:flex;">
							<!-- <div class="custom-controlTwo custom-checkbox" style="margin-bottom: 8px;">
								<input type="checkbox" class="custom-control-input all_checkbox" id="select_all_check">
								<label class="custom-control-label" for="select_all_check"></label>
							</div> -->
							<h4 class=" font-size-16">{$Lang.products_purchased}</h4>
						</div>
						<!-- <div>
							<button class="btn btn-sm btn-primary administrationBtn" type="button">管理</button>
							<button class="btn btn-sm btn-primary completeBtn" type="button">完成</button>
						</div> -->
					</div>
					<hr>
					<div style="max-height: 50vh; overflow: auto; padding:0px 20px">
						{foreach $ShopData.cart_products as $cart_val=>$cart}
						<address>
							<div class="d-flex justify-content-between">
								<div style="display:flex;">

									<strong>{$cart.productsname}:</strong>
								</div>
								{if $cart.allow_qty==1}
								<div class="cart_qty">
									<input type="hidden" name="i" value="{$cart_val}">
									<input type="number" name="qty" class="number" value="{$cart.qty}" style="width: 70px;">
									<button type="button" class="btn btn-sm" style="background: #efefef;" onclick="cartQtyBtn(this)">{$Lang.to_update}</button>
								</div>
								{/if}
								<div>
									<a href="cart?action=configureproduct&pid={$cart.productid}&i={$cart_val}"><i
											class="fas fa-pen mr-1"></i></a>
									<a href="javascript:;" onclick="removeItem('cart?action=viewcart&statuscart=remove', '{$Lang.delete_item}', '{$Lang.sure_delete}', {i: {$cart_val}})">
										<i class="fas fa-times-circle"></i>
									</a>
								</div>
							</div>
							<div class="goods_info">
								<span class="title"><font class="zk"> &gt; </font>{$cart.conf.host}</span>
								{foreach $cart.conf_child as $son_v}
									<div class="info">{$son_v.name}：<font>{$son_v.sub_name}</font></div>
								{/foreach}

							</div>
							{foreach $cart.configoptions as $configoptions_key=>$configoptions_val}
							<p class="mb-0">{$configoptions_key} : <span class="font-weight-medium">{$configoptions_val.value}</span>
							</p>
							{/foreach}

						</address>
							{if $cart.type}
								<div class="font-size-16 mt-2 d-flex justify-content-between" style=""><span style="font-size: 12px;">{$Lang.price}:</span>
									{if $ShopData.cart_products}
										<span style="color:#666">{$ShopData.currency.prefix}{$cart.product_pricing}</span>
									{else/}
										0.00
									{/if}
								</div>


								<div class="font-size-16 mt-2 d-flex justify-content-between" style="">
									{if $cart.type.type  == '1'}
										<span style="font-size: 12px;">{$Lang.customer_discount_price}
											(<span class="discount-num">{$cart.type.bates}</span>{$Lang.fracture}):
										</span>
									{elseif $cart.type.type  == '2'}
										<span style="font-size: 12px;">{$Lang.customer_discount_province}
											<span class="discount-num">{$ShopData.currency.prefix}{$cart.type.bates}):</span>
										</span>
									{/if}

									<span style="color:#666">-{$ShopData.currency.prefix}{$cart.saleproducts}</span>
								</div>
							{/if}
							<div class="font-size-16 mt-2 d-flex justify-content-between"><span style="font-size: 12px;">{$Lang.subtotal}:</span>
								{if $ShopData.cart_products}
									{if $cart.type}
										<span style="font-size: 12px;">{$ShopData.currency.prefix}<strong
													class="font-size-18">{:bcmul($cart._sale_price,$cart.qty,2)}</strong></span>
									{else}
										<span style="font-size: 12px;">{$ShopData.currency.prefix}<strong
													class="font-size-18">{:bcmul($cart.product_pricing,$cart.qty,2)}</strong></span>
									{/if}
								{else/}
									0.00
								{/if}
							</div>
							<!-- 优惠码 -->
							<!-- <div class="font-size-16 mt-2 d-flex justify-content-between" style="padding-bottom: 10px;margin-bottom: 20px;border-bottom: #ddd 1px solid;">
										<span style="font-size: 12px;">优惠：</span>

									<span class="font-size-18">-$120</span>
								</div> -->
						{/foreach}

					</div>
					<hr style="border-top: 13px solid #F8F8FB;" />
					<div class="font-size-16 mt-2 d-flex justify-content-between mobile-hide" style="padding:0px 20px">
						<span>{$Lang.total}:</span>
						{if $ShopData.cart_products}
						<span style="color:#999999; font-size:12px">共<span class="len-num">{:count($ShopData.cart_products)}</span>件<strong
								class="font-size-18 text-primary" style="margin-left: 10px;">{$ShopData.currency.prefix}<span class="price-num">{$ShopData.total_price}</span></strong></span>
						{else/}
						0.00
						{/if}
					</div>
					<div class="d-print-none mobile-hide">
						<div class="payDiv">
							<div class="custom-control custom-checkbox  align-items-end mr-2">
								<input type="checkbox" class="custom-control-input"  id="terms" name="terms" value="1" required>
								<label class="custom-control-label" for="terms">	{$Lang.agree}<a href="{$Setting.web_tos_url}" target="blank">{$Lang.terms_service}</a></label>
								<!-- <div class="invalid-feedback">你在提交之前必须同意。</div> -->

								<!-- <input type="checkbox" class="custom-control-input" id="terms" name="terms" value="1">
								<label class="custom-control-label" for="terms">
									{$Lang.agree}<a href="{$Setting.web_tos_url}" target="blank">{$Lang.terms_service}</a>
								</label>  -->
							</div>
							<div class="text-sm-right mt-2">
								<button class="btn btn-primary w-100 submit-btn" type="submit" {if !$ShopData.cart_products}disabled{/if}><i
										class="mdi mdi-truck-fast mr-1"></i>{$Lang.check_out_now}</button>
							</div>
						</div>
						<!-- <div class="text-sm-right mt-2 deleteBtn">
							<button class="btn btn-danger w-100" type="button">{$Lang.delete}</button>
						</div> -->

					</div>

				</div>

			</div>
		</div>
		<!-- 移动端底部价格展示 -->
		<div class="mobile-bottom-total">
			<div class="font-size-16 mt-2 d-flex justify-content-between" style="padding:0px 20px">
				<span>{$Lang.total}:</span>
				{if $ShopData.cart_products}
				<span style="color:#999999; font-size:12px">共<span class="len-num">{:count($ShopData.cart_products)}</span>件<strong
						class="font-size-18 text-primary" style="margin-left: 10px;">{$ShopData.currency.prefix}<span class="price-num">{$ShopData.total_price}</span></strong></span>
				{else/}
				0.00
				{/if}
			</div>
			<div class="d-print-none">
				<div class="payDiv mobile-flex">
					<div class="custom-control custom-checkbox  align-items-end mr-2">
						<input type="checkbox" class="custom-control-input"  id="terms" name="terms" value="1" required>
						<label class="custom-control-label" for="terms">	{$Lang.agree}<a href="{$Setting.web_tos_url}" target="blank">{$Lang.terms_service}</a></label>
						<!-- <div class="invalid-feedback">你在提交之前必须同意。</div> -->

						<!-- <input type="checkbox" class="custom-control-input" id="terms" name="terms" value="1">
						<label class="custom-control-label" for="terms">
							{$Lang.agree}<a href="{$Setting.web_tos_url}" target="blank">{$Lang.terms_service}</a>
						</label>  -->
					</div>
					<div class="text-sm-right mt-2">
					<button class="btn btn-primary w-100" type="submit" {if !$ShopData.cart_products}disabled{/if}><i
								class="mdi mdi-truck-fast mr-1"></i>{$Lang.check_out_now}</button>
					</div>
				</div>
				<!-- <div class="text-sm-right mt-2 deleteBtn">
					<button class="btn btn-danger w-100" type="button">{$Lang.delete}</button>
				</div> -->
			</div>
		</div>
	</div>
</form>


<script>
	$(function() {
		if(navigator.userAgent.match(/mobile/i)) {
			$('.mobile-bottom-total').show()
			$('.mobile-hide').remove()
		}else{
			$('.mobile-bottom-total').remove()
		}
	})
</script>
<!-- 删除确认 -->
<div class="modal fade" id="customModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
	aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="customTitle">{$Lang.prompt}</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="customBody">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-light" data-dismiss="modal">{$Lang.cancel}</button>
				<button type="button" class="btn btn-primary" id="customSureBtn">{$Lang.determine}</button>
			</div>
		</div>
	</div>
</div>

<style>
	.list-inline-item .icon {
		width: 2rem;
		height: 2rem;
	}

	.social-list-item {
		border: none;
	}
</style>



<style>
	.checkboxDiv{
    	width: 50%;
		border-radius: 0.25rem;
		height: 40px;
		display: flex;
		align-items: center;
		padding-left:10px;
		border: 1px solid #fff;
	}
	.checkboxSelect{
		border-color:  #007bfc;
	}
	@media screen and (max-width: 755px) {
		.checkboxDiv {
			width: 100% !important;
		}
		.checkDiv{
			display:block !important;
		}
	}
	.payType {
		cursor: pointer;
	}

	.payType.active,
	.payType:hover {
		border-color: #2948df !important;
	}

	.new-user {
		display: none;
	}

	.fas {
		cursor: pointer;
	}

	.number {
		width: 50px;
	}

	.remarksInput::-webkit-input-placeholder{
		color: #CACACA;
	}
</style>

<script>
	function init_tpl()
	{
		var checkbox = $('.payment-checkbox');
		checkbox.map(function (k, v) {
			if($(v).prop('checked'))
			{
				// 移除支付方式选中
				$(".addfunds-payment").removeClass("active").find(".hidden").removeAttr("checked")
			}
		})
	}
	init_tpl();
</script>