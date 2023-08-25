<style>
	@media (min-width: 560px) and (max-width: 930px) {
    .addfunds-payment img{
		height: 16px;
	}
}
</style>
<div class="card">
	<div class="card-body">
		<div class="beforecheck-box">
			<div class="alert alert-danger alert-dismissible fade hidden beforecheck" role="alert">
				<i class="mdi mdi-block-helper mr-2"></i>
				<span class="msg-box">{$Lang.the_maximum_allowed_balance_exceeded}:{$Addfunds.addfunds.addfunds_maximum_balance}</span>
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button>
			</div>
		</div>
		<div method="post">
			<div class="form-group row mb-4 align-items-center">
				<label for="horizontal-firstname-input" class="col-sm-1 col-form-label">{$Lang.current_balance}</label>
				<div class="col-sm-9">
					<span class="fs-28 text-primary">{$Addfunds.addfunds.credit}</span>{$Addfunds.addfunds.currency.suffix}
				</div>
			</div>
			<div class="form-group row mb-4 align-items-center">
				<label for="horizontal-email-input" class="col-3 col-sm-1 col-form-label">{$Lang.recharge_amount}</label>
				<div class="col-sm-2 col-6">
					<input style="width: 100%;" type="text" name="amount" class="form-control text-center" id="addfundsInp"
						   value="{$Addfunds.addfunds.addfunds_minimum}" onblur="addfundsMaxMin()" />
				</div>
				<div class="col-sm-2 col-2">{$Addfunds.addfunds.currency.suffix}</div>
			</div>
			<div class="form-group row mb-3">
				<label for="horizontal-password-input" class="col-sm-1 col-form-label">{$Lang.payment_method}</label>
				<div class="col-sm-10">
					<div class="row">
						{foreach $Addfunds.addfunds.gateways as $index=>$gateways}
							<div class="col-sm-3 addfunds" onclick="addfundsBtn(this)">
								<div class="addfunds-payment {if $index==0}active{/if}" data-payment="{$gateways.name}" title="{$gateways.title}"
									 data-toggle="tooltip" data-placement="bottom">
									<input type="radio" name="payment" class="hidden" value="{$gateways.name}" {if $index==0}checked{/if} />
									{if $gateways.author_url}
										<img src="{$gateways.author_url}" />
									{else}
										{$gateways.title}
									{/if}
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="form-group row">
				<div class="col-sm-1">
				</div>
				<div class="col-sm-4">
					<button type="button"  class="btn btn-primary btn-block pay-now-btn" style="width: auto;" onclick="formSubmitBtn();return false;">{$Lang.confirm_recharge}</button>
				</div>
			</div>
		</div>
	</div>
</div>

{include file="includes/paymodal"}

<script type="text/javascript" src="/themes/clientarea/default/assets/libs/qrcode/jquery.qrcode.min.js?v={$Ver}"></script>
<script src="/themes/clientarea/default/assets/libs/dropzone/min/dropzone.min.js?v={$Ver}"></script>
<script type="text/javascript">

	var intervalBox;
	var max = '{$Addfunds.addfunds.addfunds_maximum}',
			min = '{$Addfunds.addfunds.addfunds_minimum}'
		,_url = '';
</script>
<script src="/themes/clientarea/default/assets/js/addfunds.js?v={$Ver}"></script>
