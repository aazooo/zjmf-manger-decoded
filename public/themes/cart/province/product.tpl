{include file="cart/province/topbar-categories"}
<style>
  .card-footer a {
    color: #f1672a;
    display: inline-block;
    width: 100%;
    line-height: 40px;
    background: #fff;
  }

  .cartitem {
    background-color: #f5f7f9;
  }

  .cartitem.active {
    background: #fff;
  }
  .cartitem.active .card-footer {
    background-color: #f1672a!important;
  }
  

  .cartitem.active .card-footer a {
    background-color: #f1672a!important;
    color: #fff;
    z-index: 99;
  }
</style>
<link rel="stylesheet" href="/themes/cart/province/assets/fonts/iconfont.css?v={$Ver}">
<div class="card">
  <div class="card-body p-4">
    <div class="row">
      {foreach $Cart.products as $list}
      <div class="col-sm-3 mb-3">
        <div class="card cartitem">
          <div class="card-body row">
            <h5>{$list.name}</h5>
            <div class="col-sm-12">
              <p class="card-text">{$list.description}</p>
              {if $list.stock_control==1}
              <p class="card-text">{$Lang.stock}： {$list.qty}</p>
              {/if}

              {if $list.sale_price>0}
              <div class="text-right" style="color:#f1672a;">{$Cart.currency.prefix} {$list.sale_price} {$Cart.currency.suffix}</div>
              {if $list.ontrial==1}
              <div class="text-right" style="color: #e64a19;">
									<small> {$Cart.currency.prefix}
										{$list.ontrial_setup_fee+$list.ontrial_price} / {$Lang.on_trial} {$list.ontrial_cycle} {$list.ontrial_cycle_type == 'day' ? $Lang.day : $Lang.hour}
									</small>
							  </div>
              {/if}                  
              <div class="text-right color-999"><small>({$Lang.original_price}：{$Cart.currency.prefix} {$list.product_price} / {$list.billingcycle_zh})</small></div>
              {else}
              <div class="text-right" style="color:#f1672a;">{$Cart.currency.prefix} {$list.product_price} {$Cart.currency.suffix} / {$list.billingcycle_zh}</div>
              {if $list.ontrial==1}
              <div class="text-right" style="color: #e64a19;">
									<small> {$Cart.currency.prefix}
										{$list.ontrial_setup_fee+$list.ontrial_price} / {$Lang.on_trial} {$list.ontrial_cycle} {$list.ontrial_cycle_type == 'day' ? $Lang.day : $Lang.hour}
									</small>
							  </div>
              {/if}
              
              {/if}

              {if $list.stock_control==1 && $list.qty<1} 
                
                <img src="/themes/cart/province/assets/img/saleout.svg" style="position: absolute;top: -40px;right: 20px;width: 50px;" alt="">
                {else /}
                <!-- <a href="/cart?action=configureproduct&pid={$list.id}"
                  class=" btn btn-sm btn-primary waves-effect waves-light  mt-3">立即购买</a> -->
                {/if}
            </div>
          </div>
          <div class="card-footer text-center p-0" style="box-shadow: 0px 4px 20px 2px rgba(6, 75, 179, 0.08);">
            {if $list.stock_control==1 && $list.qty<1} 
            <a href="javascript:void(0)" style="cursor: not-allowed">{$Lang.buy_now}</a>
            {else}
            <a href="/cart?action=configureproduct&pid={$list.id}{if $Get.site}&site={$Get.site}{/if}">{$Lang.buy_now}</a>
            {/if}
          </div>
        </div>
      </div>
      {/foreach}

      <div class="table-footer mt-4 d-flex justify-content-center">
        <ul class="pagination pagination-sm">
          {$Pages}
        </ul>
      </div>

    </div>
  </div>
</div>

<script>
  $(function () {
    $('.cartitem').on('mouseover', function () {
      $(this).addClass('active')
    })
    $('.cartitem').on('mouseleave', function () {
      $(this).removeClass('active')
    })
  })
</script>