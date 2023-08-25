
{if $ErrorMsg}
{include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
{include file="error/notifications" value="$SuccessMsg" url="/clientarea"}
{/if}

<script src="/themes/clientarea/default/assets/js/public.js?v={$Ver}"></script>
<style>
	.input-group-prepend { 
		width: 100px;  
	}
	.auth-full-bg .bg-overlay {
		background: url(/themes/clientarea/default/assets_custom/img/new-background.jpg)no-repeat left top / 100% 1400px;
		background-size: cover;
		opacity:1;
	}
  .form-control,.input-group-append{
    height: 46px;
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
                    <div class="owl-carousel owl-theme auth-review-carousel" id="auth-review-carousel">
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

					{if $Pwreset.allow_login_phone}
						<li class="nav-item">
							<a class="nav-link fs-14 bg-transparent active" data-toggle="tab" href="#phone" role="tab" aria-selected="false">{$Lang.mobile_phone_retrieval}</a>
						</li>
					{/if}

					{if $Pwreset.allow_login_email}
						<li class="nav-item">
							<a class="nav-link fs-14 bg-transparent {if $Pwreset.allow_login_email && !$Pwreset.allow_login_phone}active{/if}" data-toggle="tab" href="#email" role="tab" aria-selected="true">{$Lang.email_retrieval}</a>
						</li>
					{/if}

				</ul>

				<div class="mt-4">
					<div class="tab-content">
						{if $Pwreset.allow_login_email}
						<div id="email" class="tab-pane {if $Pwreset.allow_login_email && !$Pwreset.allow_login_phone}active{/if}" role="tabpanel">
							<form method="post"  action="/pwreset?action=email">
								<div class="form-group">
									<label for="username">{$Lang.mailbox}</label>
									<input type="text" class="form-control" id="emailInp" name="email" placeholder="{$Lang.please_input_email}" required>
								</div>
								{if $Verify.allow_email_forgetpwd_captcha==1}
								{include file="includes/verify"  type="allow_email_forgetpwd_captcha" positon="top"}
								{/if}
								<div class="form-group">
									<label for="code">{$Lang.verification_code}</label>
									<div class="input-group">
										<input type="text" class="form-control" id="code" name="code" placeholder="{$Lang.please_enter_code}" required>
										<div class="input-group-append">
											<button class="btn btn-primary" type="button"  style="line-height:33px;" onclick="getCode(this,'reset_email_send','allow_email_forgetpwd_captcha')">{$Lang.get_code}</button>
										</div>
									</div>
								</div> 
								<div class="form-group">
									<label for="userpassword">{$Lang.password}</label>
									<input type="password" class="form-control" name="password" id="emailPwd" placeholder="{$Lang.please_enter_password}" required>
								</div>
								<div class="form-group">
									<label for="userpassword">{$Lang.confirm_password}</label>
									<input type="password" class="form-control" name="checkPassword" id="emailPwdCheck" placeholder="{$Lang.please_password_again}" required>
								</div>
								<div class="mt-3">
									<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light" type="submit">{$Lang.reset}</button>
								</div>
							</form>
						</div>
						{/if}
						{if $Pwreset.allow_login_phone}
						<div id="phone" class="tab-pane active" role="tabpanel">
							<form method="post"  action="/pwreset?action=phone">
								<div class="form-group">
									<label for="username">{$Lang.phone_number}</label>
									<div class="input-group">
										{if $Pwreset.allow_login_register_sms_global==1}
										<div class="input-group-prepend">
											<select class="form-control select2 select2-hidden-accessible" data-select2-id="1" tabindex="-1" aria-hidden="true" name="phone_code" id="phoneCodeSel">
												{foreach $SmsCountry as $list}
												<option value="{$list.phone_code}" {if $list.phone_code=="+86"}selected {/if}>{$list.link}</option>
												{/foreach}
											</select>
										</div> 
										{/if}
										<input type="text" class="form-control" id="phoneInp" name="phone" placeholder="{$Lang.please_enter_your_mobile_phone_number}" required>
									</div>
								</div>
								{if $Verify.allow_phone_forgetpwd_captcha==1}
								{include file="includes/verify"  type="allow_phone_forgetpwd_captcha" positon="top"}
								{/if}
								<div class="form-group">
									<label for="code">{$Lang.verification_code}</label>
									<div class="input-group">
										<input type="text" class="form-control" id="code" name="code" placeholder="{$Lang.please_enter_code}" required>
										<div class="input-group-append">
											<button class="btn btn-primary" type="button" style="line-height:33px;" onclick="getCode(this,'reset_phone_send','allow_phone_forgetpwd_captcha')">{$Lang.get_code}</button>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label for="userpassword">{$Lang.password}</label>
									<input type="password" class="form-control" name="password" id="phonePwd" placeholder="{$Lang.please_enter_password}" required>
								</div>
								<div class="form-group">
									<label for="userpassword">{$Lang.confirm_password}</label>
									<input type="password" class="form-control" name="checkPassword" id="phonePwdCheck"  placeholder="{$Lang.please_password_again}" required>
								</div>

								<div class="mt-3">
									<button class="btn btn-primary py-2 fs-14 btn-block waves-effect waves-light" type="submit">{$Lang.reset}</button>
								</div>

							</form>
						</div>
						{/if}
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

