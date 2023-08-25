{if $ErrorMsg}
{include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
{include file="error/notifications" value="$SuccessMsg"}
{/if}
  
 
<script src="/themes/clientarea/default/assets/js/crypto-js.min.js" type="text/javascript"></script>
<script src="/themes/clientarea/default/assets/js/public.js" type="text/javascript"></script>

<style>
		.logo.text-center img{height:50px;}
    .list-inline-item .icon {
        width: 2rem;
        height: 2rem;
    }
    .social-list-item {
        border: none;
    }
    .input-group-prepend {
        width: 100px;
    }
	.allow_login_code_captcha{display:none;}
	.auth-full-bg .bg-overlay {
		background: url(/themes/clientarea/default/assets_custom/img/new-background.jpg)no-repeat left top / 100% 1400px;
		background-size: cover;
		opacity:1;
	}
  .form-control,.input-group-append{
    height: 46px;
  }
</style>
<script type="text/javascript">
    var mk = '{$Setting.msfntk}';
</script>
<div class="container-fluid p-0">
    {if $Setting.login_header}
    <div class="text-center">{$Setting.login_header}</div>
    {/if}
    <div class="row no-gutters">

        <div class="col-xl-7 bglogo">
            <div class="auth-full-bg pt-lg-5 p-4">
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
            <div class="auth-full-page-content p-md-5 p-4">
                <div class="login_right mx-auto">
                    <div class="d-flex flex-column h-100">
                        <div class="my-auto">
                            <div  class="logo text-center" >
                              <a href="{$Setting.web_jump_url}"><img  src="{$Setting.web_logo}" alt="" class="cursor"></a>
                            </div>
                            <ul class="affs-nav nav nav-tabs nav-tabs-custom nav-justified" role="tablist">

								<!-- 手机 -->
								{if $Login.allow_login_phone==1}
									<li class="nav-item">
										<a class="nav-link fs-14 bg-transparent {if $Get.action=="phone" || $Get.action=="phone_code" || !$Get.action}active{/if}" data-toggle="tab" href="#phone" role="tab" aria-selected="false">{$Lang.mobile_login}
										</a>
									</li>
								{/if}

								{if $Login.allow_login_email==0 && $Login.allow_id==1}
									<li class="nav-item">
										<a class="nav-link fs-14 bg-transparent {if ($Login.allow_login_phone==0 && $Login.allow_id == 1)}active{/if}" data-toggle="tab" href="#email" role="tab" aria-selected="false">{$Lang.id_login}</a>
									</li>
								{/if}

								<!-- 邮箱 -->
                                {if $Login.allow_login_email}
                                <li class="nav-item">
                                    <a class="nav-link fs-14 bg-transparent {if ($Login.allow_login_phone==0 && $Login.allow_login_email == 1  && $Login.allow_id == 0) || $Get.action=="email"}active{/if} " data-toggle="tab" href="#email" role="tab" aria-selected="true">{$Lang.email_login}</a>
                                </li>
                                {/if}
                                
                            </ul>

                            <div class="mt-4">
								<div class="tab-content">
									{if $Login.allow_login_email || $Login.allow_id}
									<div id="email" class="tab-pane  {if ($Login.allow_login_phone==0 && ($Login.allow_login_email == 1  || $Login.allow_id == 1)) || $Get.action=="email"}active{/if}" role="tabpanel">
										<form method="post" action="/login?action=email" onsubmit="return encryptPass('emailPwdInp');" >										
											<div class="form-group">
												<label for="username">{if $Login.allow_login_email}{$Lang.mailbox}{else}ID{/if}</label>
												<input type="text" class="form-control" id="emailInp" name="email" value="{$Post.email}" placeholder="{$Lang.please_enter_your}{if $Login.allow_login_email}{$Lang.mailbox}{if $Login.allow_id==1}{$Lang.ors}{/if}{/if}{if $Login.allow_id==1}ID{/if}">
											</div>
											<div class="form-group">
												<div class="d-flex justify-content-between">
													<label for="userpassword">{$Lang.password}</label>
												</div>
												<input type="password" class="form-control" id="emailPwdInp" name="password" placeholder="{$Lang.please_enter_password}">
											</div>
											{if $Login.allow_login_email_captcha==1 && $Login.is_captcha==1}
											{include file="includes/verify"  type="allow_login_email_captcha" positon="top"}
											{/if}				
                      <div class="d-flex justify-content-between">
													<label for="userpassword"></label>
														<a href="pwreset" class="text-primary mr-0">{$Lang.forget_the_password}</a>
												</div>							
											<div class="mt-3">
												{if $Login.second_verify_action_home_login==1}
												<!--二次登录验证-->
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  d-flex justify-content-center align-items-center"
													type="button"  onclick="loginBefore('email');">{$Lang.sign_in}</button>
												{else/}
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light d-flex justify-content-center align-items-center"
													type="submit">{$Lang.sign_in}</button>
												{/if}												
											</div>
										</form>
									</div>
									{/if}
									{if $Login.allow_login_phone}
									<div id="phone" class="tab-pane {if $Get.action=="phone" || $Get.action=="phone_code" || !$Get.action}active{/if}" role="tabpanel">
										<form method="post" action="/login?action=phone" onsubmit="return encryptPass('phonePwdInp');" >
											<div class="form-group">
												<label for="username">{$Lang.phone_number}</label>
												<div class="input-group">
													{if $Login.allow_login_register_sms_global==1}
													<div class="input-group-prepend">
														<select class="form-control select2 select2-hidden-accessible"
															data-select2-id="1" tabindex="-1" aria-hidden="true"
															name="phone_code"  value="{$Post.phone_code}"  id="phoneCodeSel">
															{foreach $SmsCountry as $list}
															<option value="{$list.phone_code}" {if $list.phone_code=="+86"}selected {/if}>
																{$list.link}
															</option>
															{/foreach}
														</select>
													</div>
													{/if}
													<input type="text" class="form-control" id="phoneInp" name="phone"  value="{$Post.phone}"  placeholder="{$Lang.please_enter_your_mobile_phone_number}">
												</div>
											</div>
											<div class="form-group allow_login_phone_captcha">
												<div class="d-flex justify-content-between">
													<label for="userpassword">{$Lang.password}</label>
												</div>
												<input type="password" class="form-control" id="phonePwdInp" name="password" placeholder="{$Lang.please_enter_password}">
											</div>
											{if $Login.allow_login_phone_captcha==1 && $Login.is_captcha==1}
											{include file="includes/verify"  type="allow_login_phone_captcha" positon="top"}
											{/if}
											{if $Login.allow_login_code_captcha==1 && $Login.is_captcha==1}
											{include file="includes/verify"  type="allow_login_code_captcha" positon="top"}
											{/if}
                      
											<div class="form-group allow_login_code_captcha">
												<label for="code">{$Lang.verification_code}</label>
												<div class="input-group">
													<input type="text" class="form-control" id="phoneCodeInp" name="code"  value="{$Post.code}" placeholder="{$Lang.please_enter_code}">
													<div class="input-group-append"  style="height:46px;">
														<button class="btn btn-primary" type="button" style="line-height:33px;"  onclick="getCode(this,'login_send','allow_login_code_captcha')">{$Lang.get_code}</button>
													</div>
												</div>
											</div>

											
											
											<div class="d-flex justify-content-between align-items-center">
												<div onclick="phoneCheck(this,'allow_login_phone_captcha')" class="text-primary mr-0 pointer" {if $Get.action=="phone_code"} style="display:none;" {/if}>
													{$Lang.verification_code_login}
												</div>
												<div onclick="phoneCheck(this,'allow_login_code_captcha')" class="text-primary mr-0 pointer" {if $Get.action!="phone_code"} style="display:none;" {/if}>
													{$Lang.password_login}
												</div>
                        <a href="pwreset" class="text-primary mr-0">{$Lang.forget_the_password}</a> 
											</div>
											<div class="mt-3">
												{if $Login.second_verify_action_home_login==1}
												<!--二次登录验证-->
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  justify-content-center align-items-center allow_login_phone_captcha" type="button"  onclick="loginBefore('phone');">{$Lang.sign_in}</button>
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  justify-content-center align-items-center allow_login_code_captcha" type="submit">{$Lang.sign_in}</button>
												{else/}
                      
												<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light  justify-content-center align-items-center"
													type="submit">{$Lang.sign_in}</button>
												{/if}												
											</div>
										</form>
									</div>
									{/if}
								</div>
                            </div>

                            {if $Oauth}
                            <div class="mt-4 text-center">
                                <h5 class="font-size-14 mb-3">{$Lang.use_other_login}</h5>

                                <ul class="list-inline">
                                    {foreach $Oauth as $list}
                                    <li class="list-inline-item">
                                        <a href="{$list.url}" class="social-list-item text-white" target="blank">
                                            {$list.img}
                                        </a>
                                    </li>
                                    {/foreach}
                                </ul>
                            </div>
                            {/if}

                            <div class="mt-5 text-center">
                                <p>{$Lang.no_account_yet} <a href="register" class="font-weight-medium text-primary"> {$Lang.register_now} </a>
                                </p>
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

{if $Login.second_verify_action_home_login==1}
<!--登录二次验证 模态框-->
<div class="modal fade" id="secondVerifyModal" tabindex="-1" role="dialog" aria-labelledby="secondVerifyModal"
	aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">{$Lang.secondary_verification}</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form>
					<input type="hidden" value="{$Token}" />
					<input type="hidden" value="closed" name="action" />
					<div class="form-group row mb-4">
						<label class="col-sm-3 col-form-label text-right">{$Lang.verification_method}</label>
						<div class="col-sm-8">
							<select class="form-control" class="second_type" name="type" id="secondVerifyType">
								
							</select>
						</div>
					</div>
            	<!--忘记密码-->
                       
					<div class="form-group row mb-0">
						<label class="col-sm-3 col-form-label text-right">{$Lang.verification_code}</label>
						<div class="col-sm-8">
							<div class="input-group">
								<input type="text" name="code" id="secondVerifyCode" class="form-control" placeholder="{$Lang.please_enter_code}" />
								<div class="input-group-append" style="height:46px;" id="getCodeBox">
									<button class="btn btn-secondary"  type="button"  onclick="getCode(this,'login/second_verify_send')"  style="line-height:33px;" type="button">{$Lang.get_code}</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-light" data-dismiss="modal">{$Lang.cancel}</button>
				<button type="button" class="btn btn-primary mr-2" id="secondVerifySubmit">{$Lang.determine}</button>
			</div>
		</div>
	</div>
</div>
{/if}

<script type="text/javascript">
{if $Get.action=="phone_code"} 
phoneCheck("","allow_login_phone_captcha")
{else/} 
phoneCheck("","allow_login_code_captcha")
{/if}

</script>
