
{if $ErrorMsg}
{include file="error/alert" value="$ErrorMsg"}
{/if}
{if $SuccessMsg} 
{include file="error/notifications" value="$SuccessMsg"}
{/if}
<script src="/themes/clientarea/default/assets/js/crypto-js.min.js" type="text/javascript"></script>
<script src="/themes/clientarea/default/assets/js/public.js?v={$Ver}"></script>

<style>
		.logo.text-center img{height:50px;}
    .input-group-prepend {
        width: 100px;
    }
	.auth-full-bg .bg-overlay {
		background: url(/themes/clientarea/default/assets_custom/img/new-background.jpg)no-repeat left top / 100% 1400px;
		background-size: cover;
		opacity:1;
	}
  .form-control,.btn-primary,.input-group-append{
    height: 46px;
  }
  .btn-primary{
    line-height: 28px;
  }
</style>
<script>
    var mk = '{$Setting.msfntk}';
</script>
<div class="container-fluid p-0">
    {if $Setting.login_header}
    <div class="text-center">{$Setting.login_header}</div>
    {/if}
    <div class="row no-gutters">
        <div class="col-xl-7 bglogo">
            <div class="auth-full-bg pt-lg-5 p-4" style="height:100%">
                <div class="w-100">
                    {if $Setting.custom_login_background_img}
                    <div class="bg-overlay" style="background: url({$Setting.custom_login_background_img}) center no-repeat !important; background-size:cover !important;"></div>
					{else/}
                    <div class="bg-overlay"></div>
					{/if}

                    <div class="d-flex h-100 flex-column justify-content-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-7">
                                <div class="text-center">
                                    <div dir="ltr">
                                        <div class="owl-carousel owl-theme auth-review-carousel"
                                            id="auth-review-carousel">
                                            <div class="item">
                                                <div class="py-3">
                                                    <h1 class="text-white text-left">
                                                        {$Setting.custom_login_background_char}</h1>
                                                    <p class="text-white-50 text-left">
                                                        {$Setting.custom_login_background_description}</p>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end col -->

        <div class="col-xl-5">
            <div class="auth-full-page-content p-4">
                <div class="login_right mx-auto">
                    <div class="d-flex flex-column h-100">
                        <div class="my-auto">
                            <div  class="logo text-center" >
                              <a href="{$Setting.web_jump_url}"><img  src="{$Setting.web_logo}" alt="" class="cursor" ></a>
                            </div>
                            <ul class="affs-nav nav nav-tabs nav-tabs-custom nav-justified" role="tablist">

								{if $Register.allow_register_phone}
									<li class="nav-item">
										<a class="nav-link fs-14 bg-transparent {if $Get.action=="phone" || !$Get.action}active{/if}" data-toggle="tab" href="#phone" role="tab" aria-selected="false"> {$Lang.mobile_registration}</a>
									</li>
								{/if}

								{if $Register.allow_register_email}
									<li class="nav-item">
										<a class="nav-link fs-14 bg-transparent {if ($Register.allow_register_email && !$Register.allow_register_phone) || $Get.action=="email"}active{/if}" data-toggle="tab" href="#email" role="tab" aria-selected="true">{$Lang.email_registration}</a>
									</li>
								{/if}

                            </ul>

                            <div class="mt-4">
								<div class="tab-content">	
									{if $Register.allow_register_email}
									<div id="email" class="tab-pane {if ($Register.allow_register_email && !$Register.allow_register_phone) || $Get.action=="email"}active{/if}" role="tabpanel">
										<form class="needs-validation" novalidate method="post" action="/register?action=email" onsubmit="encryptPass('phonePwd');encryptPass('phonePwdCheck');" >
											<div class="form-group">
												<label for="email">{$Lang.mailbox}</label>
												<input type="text" class="form-control" id="emailInp" name="email"
													placeholder="{$Lang.please_input_email}" value="{$Post.email}" required>
												<div class="invalid-feedback">{$Lang.please_input_email}</div>
											</div>
											{if $Verify.allow_register_email_captcha==1}
											{include file="includes/verify"  type="allow_register_email_captcha" positon="top"}
											{/if}
											{if $Register.allow_email_register_code==1}
											<div class="form-group">
												<label for="code">{$Lang.verification_code}</label>
												<div class="input-group">
													<input type="text" class="form-control" id="code" name="code"
														placeholder="{$Lang.please_enter_code}" value="{$Post.code}" required>
													<div class="input-group-append">
														<button class="btn btn-primary" type="button"  onclick="getCode(this,'register_email_send','allow_register_email_captcha')">{$Lang.get_code}</button>
													</div>
												</div>
											</div>
											{/if}
											<div class="form-group">
												<label for="password">{$Lang.password}</label>
												<input type="password" class="form-control" name="password"
													id="phonePwd" placeholder="{$Lang.please_enter_password}" required>
											</div>
											<div class="form-group">
												<label for="checkPassword">{$Lang.confirm_password}</label>
												<input type="password" class="form-control" name="checkPassword"
													id="phonePwdCheck" placeholder="{$Lang.please_password_again}" required>
											</div>
											{foreach $Register.login_register_custom_require as $custom}
											<div class="form-group">
												<label for="{$custom.name}">{$Register[login_register_custom_require_list][$custom.name]}</label>
												<input type="{if $custom.name=='password'}password{else}text{/if}" class="form-control" name="{$custom.name}" id="{$custom.name}" value="{$Post[$custom.name]}" >
											</div>
											{/foreach}

											{foreach $Register.fields as $k => $list}
												<div class="form-group">
													<label for="{$list.id}">{$list.fieldname}</label>
													{if $list.fieldtype == 'dropdown'}
														<!-- 下拉 -->
														<select name="fields[{$list.id}]" class="form-control ">
															{foreach $list.dropdown_option as $key => $val}
																<option value="{$key}" {if(isset($_fields[$key]))} selected {/if}>{$val}</option>
															{/foreach}
														</select>
													{elseif $list.fieldtype == 'password'}
														<!-- 密码 -->
														<input name="fields[{$list.id}]" type="password" {if(isset($_fields[$list['id']]))} value="{$_fields[$list['id']]}" {/if}class="form-control" placeholder="{$Lang.custom_password_box}" />
													{elseif $list.fieldtype == 'text' || $list.fieldtype == 'link'}
														<!-- 文本框、链接 -->
														<input name="fields[{$list.id}]" type="text" class="form-control" {if(isset($_fields[$list['id']]))} value="{$_fields[$list['id']]}" {/if} placeholder="{$list.fieldname}" />
													{elseif $list.fieldtype == 'tickbox'}
														<!-- 选项框 -->
														<input type="checkbox" name="fields[{$list.id}]" {if(isset($_fields[$list['id']]))} checked {/if}>{$list.fieldname}
													{elseif $list.fieldtype == 'textarea'}
														<!-- 文本域 -->
														<textarea name="fields[{$list.id}]" cols="30" rows="10" class="form-control">{if(isset($_fields[$list['id']]))} {$_fields[$list['id']]} {/if}</textarea>
													{/if}
												</div>
											{/foreach}
											<!--销售-->
											{if $setsaler == '2'}
											<div class="form-group">
												<label for="checkPassword">{$Lang.sales_representative}</label>
												<select name="sale_id" class="form-control">
													<option value="0">{$Lang.nothing}</option>
													{foreach $saler as $list}
													<option {if $list.id==$Post.id}selected{/if} value="{$list.id}">{$list.user_nickname}</option>
													{/foreach}
												</select>
											</div>
											{/if}
											<div class="mt-3">                                       
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  d-flex justify-content-center align-items-center"
													type="submit" onclick="if(!beforeSubmit(this)){ return false;}">{$Lang.register}</button>
											</div>

										</form>
									</div>
									{/if}
									{if $Register.allow_register_phone}
									<div id="phone" class="tab-pane {if $Get.action=="phone" || !$Get.action}active{/if}" role="tabpanel">
										<form class="needs-validation" novalidate method="post" action="/register?action=phone" onsubmit="encryptPass('emailPwd');encryptPass('emailPwdCheck');">
											<div class="form-group">
												<label for="username">{$Lang.phone_number}</label>
												<div class="input-group">
													{if $Register.allow_login_register_sms_global==1}
													<div class="input-group-prepend">
														<select class="form-control select2 select2-hidden-accessible"
															data-select2-id="1" tabindex="-1" aria-hidden="true"
															name="phone_code" value="{$Post.phone_code}" id="phoneCodeSel">
															{foreach $SmsCountry as $list}
															<option value="{$list.phone_code}"  {if $list.phone_code=="+86"}selected {/if}>
																{$list.link}
															</option>
															{/foreach}
														</select>
													</div>
													{/if}
													<input type="text" class="form-control" id="phoneInp" name="phone"
														placeholder="{$Lang.please_enter_your_mobile_phone_number}"  value="{$Post.phone}" required>
												</div>
											</div>
											{if $Verify.allow_register_phone_captcha==1}
											{include file="includes/verify"  type="allow_register_phone_captcha" positon="top"}
											{/if}
											<div class="form-group">
												<label for="code">{$Lang.verification_code}</label>
												<div class="input-group">
													<input type="text" class="form-control" id="code" name="code"
														placeholder="{$Lang.please_enter_code}" value="{$Post.code}" required>
													<div class="input-group-append"> 
														<button class="btn btn-primary" type="button"  onclick="getCode(this,'register_phone_send','allow_register_phone_captcha')">{$Lang.get_code}</button>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label for="password">{$Lang.password}</label>
												<input type="password" class="form-control" name="password" 
													id="emailPwd" placeholder="{$Lang.please_enter_password}" required>
											</div>
											<div class="form-group">
												<label for="checkPassword">{$Lang.confirm_password}</label>
												<input type="password" class="form-control" name="checkPassword" 
													id="emailPwdCheck" placeholder="{$Lang.please_password_again}" required>
											</div>
											{foreach $Register.login_register_custom_require as $custom}
											<div class="form-group">
												<label for="{$custom.name}">{$Register[login_register_custom_require_list][$custom.name]}</label>
												<input type="{if $custom.name=='password'}password{else}text{/if}" class="form-control" name="{$custom.name}" id="{$custom.name}"  value="{$Post[$custom.name]}" >
											</div>
											{/foreach}

											{foreach $Register.fields as $k => $list}
											<div class="form-group">
												<label for="{$list.id}">{$list.fieldname}</label>															
												{if $list.fieldtype == 'dropdown'}
													<!-- 下拉 -->
													<select name="fields[{$list.id}]" class="form-control ">
														{foreach $list.dropdown_option as $key => $val}
															<option value="{$key}" {if(isset($_fields[$key]))} selected {/if}>{$val}</option>
														{/foreach}
													</select>
												{elseif $list.fieldtype == 'password'}
												<!-- 密码 -->
													<input name="fields[{$list.id}]" type="password" {if(isset($_fields[$list['id']]))} value="{$_fields[$list['id']]}" {/if}class="form-control" placeholder="{$Lang.custom_password_box}" />
												{elseif $list.fieldtype == 'text' || $list.fieldtype == 'link'}
													<!-- 文本框、链接 -->
													<input name="fields[{$list.id}]" type="text" class="form-control" {if(isset($_fields[$list['id']]))} value="{$_fields[$list['id']]}" {/if} placeholder="{$list.fieldname}" />
												{elseif $list.fieldtype == 'tickbox'}
													<!-- 选项框 -->
													<input type="checkbox" name="fields[{$list.id}]" {if(isset($_fields[$list['id']]))} checked {/if}>{$list.fieldname}
												{elseif $list.fieldtype == 'textarea'}
													<!-- 文本域 -->
													<textarea name="fields[{$list.id}]" cols="30" rows="10" class="form-control">{if(isset($_fields[$list['id']]))} {$_fields[$list['id']]} {/if}</textarea>
												{/if}												
											</div>														
											{/foreach}
											<!--销售-->
											{if $setsaler == '2'}
											<div class="form-group">
												<label for="checkPassword">{$Lang.sales_representative}</label>
												<select name="sale_id" class="form-control">
													<option value="0">{$Lang.nothing}</option>
													{foreach $saler as $list}
													<option value="{$list.id}" {if($Post.sale_id==$list.id)}selected{/if}>{$list.user_nickname}</option>
													{/foreach}
												</select>
											</div>
											{/if}
											<div class="mt-3">                                       
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  d-flex justify-content-center align-items-center" type="submit" onclick="if(!beforeSubmit(this)) {return false;}">{$Lang.register}</button>
											</div>

										</form>
									</div>
									{/if}
								</div>
                                <div class="mt-5 text-center">
                                    <p>
                                        <input type="checkbox" id="agreePrivacy">
                                        <span>
                                            {$Lang.have_read_agree}
                                            <a href="{$Setting.web_tos_url}" class="font-weight-medium text-primary"
                                                target="_blank">{$Lang.terms_service}</a>
                                            {$Lang.ands}
                                            <a href="{$Setting.web_privacy_url}" class="font-weight-medium text-primary"
                                                target="_blank">{$Lang.privacy_policy}</a>
                                        </span>
                                    </p>
                                </div>
                                <div class="mt-5 text-center">
                                    <p>{$Lang.there_already_account} <a href="login" class="font-weight-medium text-primary"> {$Lang.sign_in}</a> </p>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
        <!-- end col -->
    </div>

    {if $Setting.login_footer}
    <div class="text-center">{$Setting.login_footer}</div>
    {/if}
    <!-- end row -->

</div>
<!-- end container-fluid -->
<script src="/themes/clientarea/default/assets/js/public.js"></script>
<script>
	function beforeSubmit(_this)
	{
		var is_checked = $('#agreePrivacy:checked')
		if(is_checked.length == 0)
		{
			toastr.error('{$Lang.check_privacy}');
			return false;
		}
		$(_this).parents('form').submit();
	}
</script>
