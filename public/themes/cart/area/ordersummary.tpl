
<style>
  .box_options_inner {
    display: none;
  }
  /* .configinfo_box .configinfo_box_options{
    position: relative;
  } */
  @media (max-width:576px) {
    .configinfo_box_bottom .box_bottom_left .bottom_left_expand{
      width: 100%;
      height: auto;
      display: block;
      padding: 5px 8px;
      margin: 0 !important;
    }
    .yixuanbeiz{
      width: 100%;
      font-size: 14px;
      width: 100%;
      display: block;
      white-space: nowrap;
    }
    .jinehuan{
      white-space: nowrap;
      font-size: 0.8rem;
    }
    .box_bottom_right .footer_box_btn button{
      width: auto;
    }
  }
  .footer_box_btn.configoption_total {
  min-width: 80px;
  }
</style>

<div class="configinfo_box_options">
  <div class="box_options_inner">
    <div class="options_inner_title fs-16 font-weight-bold mb-4">{$Lang.order_summary}：</div>
    <div class="row">
      <div class="col-xl-4 mb-2 d-flex align-items-center justify-content-between options_inner_item">
        <div class="text-muted">{$ConfigureTotal.product_name}：</div>
        <div class="text-balck">{$ConfigureTotal.product_price}</div>
      </div>
      {foreach $ConfigureTotal.child as $configure}
      <div class="col-xl-4 mb-2 d-flex align-items-center justify-content-between options_inner_item">
        <div class="text-muted">{$configure.option_name}:{if
          $configure.qty}{$configure.qty}{else/}{$configure.suboption_name}{/if}</div>
        <div class="text-balck">{$configure.suboption_price_total}</div>
      </div>
      {/foreach}

    </div>

  </div>
</div>
<div class="configinfo_box_bottom px-3">
  <div class="box_bottom_left">
    <span class="fs-14 text-black-50 mr-2 yixuanbeiz">{$Lang.selected_configuration}</span>
    <span class="fs-12 text-black pointer bottom_left_expand">{$Lang.open}<i class="bx bx-caret-down"></i></span>
  </div>
  <div class="box_bottom_right">
    <div class="footer_box_price mr-3">
      <div class="jinehuan">
        {$ConfigureTotal.currency.prefix}
        {$ConfigureTotal.bates?$ConfigureTotal.sale_price:$ConfigureTotal.total}
        {$ConfigureTotal.currency.suffix} 
        /
        {if $ConfigureTotal.billingcycle == 'day'}
          {$ConfigureTotal.pay_day_cycle} 
          {$ConfigureTotal.billingcycle_zh}
        {elseif $ConfigureTotal.billingcycle == 'hour'}
          {$ConfigureTotal.pay_hour_cycle} 
          {$ConfigureTotal.billingcycle_zh}
        {else}
          {$ConfigureTotal.billingcycle_zh}
        {/if}
        {if $ConfigureTotal.billingcycle == 'ontrial'}
          {$ConfigureTotal.pay_ontrial_cycle} {$ConfigureTotal.pay_ontrial_cycle_type === 'day' ? $Lang.day : $Lang.hour}
        {/if}
      </div>
      {if $ConfigureTotal.bates}
      <div class="fs-12 text-muted">
        {if $ConfigureTotal.total}
          ({$Lang.original_price}：{$ConfigureTotal.currency.prefix}{$ConfigureTotal.total}{$ConfigureTotal.currency.suffix}/{$ConfigureTotal.billingcycle_zh})
        {else}
          ''
        {/if}
      </div>
      {/if}
    </div>
    <div class="footer_box_btn configoption_total">
      <button type="button" class="row btn btn-primary" id="buyNowBtn">{$Lang.buy_now}</button>
    </div>
  </div>
</div>


<script>
	$(function() {
    console.log($(".getPassword"))
		// 加入购物车 提交按钮
    var timer=null
    let result = {flag:true}
    if(passwordRules != null && showPassword == 1) {
			result = checkingPwd($(".getPassword").val(), passwordRules.num, passwordRules.upper, passwordRules.lower, passwordRules.special)
		}
    $(document).on("click", "#buyNowBtn", function () {
      if($(".is-invalid").length == 0) {
        if (timer) {
          clearTimeout(timer)
        } 
        timer = setTimeout(function(){
          var InpCheck = $( "input[name^='customfield']")
          var textareaCheck = $( "textarea[name^='customfield']")
          if (checkListFormVerify([...InpCheck, ...textareaCheck])){
            var position = $(".is-invalid:first").offset();
              scrolltop = position.top-70;
              $("html,body").animate({scrollTop:scrolltop}, 1000);
              return false;
          }else{
            if(result.flag) {
              $('#addCartForm').submit()
            }
          }
        }, 500);
      }
    })
		
	})
</script>