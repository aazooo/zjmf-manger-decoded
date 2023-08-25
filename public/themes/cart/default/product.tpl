{include file="cart/default/sidebar-categories"}

<div class="{if count($Cart.product_groups) > 1}col-sm-9{else}col-sm-10{/if}">
	<div class="card">
		<div class="card-body p-4" style="min-height: calc(100vh - 200px);">
			{if $Cart.products}
			<div class="row">
				{foreach $Cart.products as $list}
				<div class="col-sm-4">
					<div class="card">
						<div class="card-header">
							<h5>{$list.name}</h5>
						</div>
						<div class="card-body row d-flex align-items-center">
							<div class="col-sm-12">
								<p class="card-text"><pre style="white-space: pre-wrap;word-wrap: break-word;">{$list.description}</pre></p>
								{if $list.stock_control==1}
								<p class="card-text"><pre style="white-space: pre-wrap;word-wrap: break-word;">{$Lang.stock}： {$list.qty}</pre></p>
								{/if}
								{if $list.has_bates}
								<div class="text-right" style="color: #e64a19;">{$Cart.currency.prefix} {$list.sale_price} 起/ {$list.billingcycle_zh}</div>
								{if $list.ontrial==1}
								<div class="text-right" style="color: #e64a19;">
									<small>
										{$Cart.currency.prefix}{$list.ontrial_setup_fee+$list.ontrial_price} / {$list.ontrial_cycle}
										{$list.ontrial_cycle_type == 'day' ? $Lang.day : $Lang.hour} 
									</small>
								</div>
								{/if}
								<div class="text-right color-999"><small>({$Lang.original_price}：{$Cart.currency.prefix} {$list.product_price} / {$list.billingcycle_zh})</small></div>
								{else}
								<div class="text-right" style="color: #e64a19;">{$Cart.currency.prefix} {$list.product_price} {$Lang.rise}/ {$list.billingcycle_zh}</div>
								{if $list.ontrial==1}
								<div class="text-right" style="color: #e64a19;">
									<small> {$Cart.currency.prefix}
										{$list.ontrial_setup_fee+$list.ontrial_price} / {$Lang.on_trial} {$list.ontrial_cycle} {$list.ontrial_cycle_type == 'day' ? $Lang.day : $Lang.hour}
									</small>
								</div>
								{/if}
								
								{/if}

								{if $list.stock_control==1 && $list.qty<1}
									<i class="iconfont icon-yishouqing fs-50" style="position: absolute;top: -50px;right: 16px;"></i>
								{else /}
								<div class="text-right">
									<a href="/cart?action=configureproduct&pid={$list.id}{if $Get.site}&site={$Get.site}{/if}" class="btn btn-sm btn-primary waves-effect waves-light mt-3 px-3" style="background-color: #316eea!important;">{$Lang.buy_now}</a>
								</div>
								{/if}

							</div>

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
			{else}
			<div class="d-flex align-items-center justify-content-center" style="height: 600px">{$Lang.no_data_available}</div>
			{/if}
		</div>
	</div>
</div>
</div>
