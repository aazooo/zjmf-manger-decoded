<style>
	.prolist ul {
		padding-left: 0;
	}
	.prolist li {
		list-style: none;
		text-align: center;
    line-height: 40px;
	}
	.firstbox,
	.secondbox {
		min-height: 560px;
	}
	.firstbox .active a {
		background-color: #f8f8fb;
		color: #2f46b0 !important;
	}
	.secondbox {
		background-color: #f8f8fb;
	}
	.secondbox .active a {
		color: #2f46b0 !important;
	}
	.firstbox a,
	.secondbox a {
		color: #74788d;
	}

	.pro-search-box .border-light {
		border-color: rgba(239, 242, 247, 0.5) !important;
	}
	.pro-search-box .rounded {
		border-radius: 2rem !important;
	}
	.pro-search-box .bg-light,
	.pro-search-box .form-control {
		background-color: rgba(255,255,255,0.15) !important;
		border: 1px solid rgba(255,255,255,0.15) !important;
	}
	
	.pro-search-box input::-webkit-input-placeholder {
      color: #fff;
	}
	.pro-search-box input::-moz-placeholder {
		color: #fff;
	}
	@media screen and (max-width: 768px) {
		.firstbox,
		.secondbox {
			min-height: 200px;
		}
	}
</style>
<div class="row">
	<div class="{if count($Cart.product_groups) > 1}col-sm-3{else}col-sm-2{/if}">
		<div class="card bg-primary text-white-50" style="background-color: #316eea!important;">
			<div class="card-body">
				{if $Get.keywords /}
				<h5 class="mt-0 mb-4 text-white"><i class="mdi mdi-alert-circle-outline mr-3"></i>{$Lang.search} "{$Get.keywords}" {$Lang.result}
				</h5>
				<p class="card-text">{$Lang.product}：{:count($Cart.products)}{$Lang.individual}</p>
				{else /}
				<h5 class="mt-0 mb-4 text-white"><i
						class="mdi mdi-alert-circle-outline mr-3"></i>{$Cart.product_groups_checked.name}</h5>
				<p class="card-text">{$Cart.product_groups_checked.headline}</p>
				<p class="card-text">{$Cart.product_groups_checked.tagline}</p>
				{/if}
				<div class="search-box pro-search-box">

					<div class="position-relative">
						<input type="text" id="searchInp" name="keywords" class="form-control rounded bg-light border-light" placeholder="{$Lang.search_products}"
							value="{$Get.keywords}">
						<i class="mdi mdi-magnify search-icon text-white"></i>
					</div>


				</div>
			</div>
		</div>

		<!-- 正常情况产品分类 -->
		<div class="card">
			<div class="card-body p-4">

				{if $Cart.product_groups}
				<div class="prolist">
					<div class="row">
						{if count($Cart.product_groups) > 1}
						<div class="col-6 firstbox p-0">
							<ul>
								{foreach $Cart.product_groups as $firstIndex=>$groups}
								<li class="{if ($Get.fid == $groups.id) || (!$Get.fid && $firstIndex==0)}active{/if}">
									<a href="/cart?fid={$groups.id}&gid={$groups.second.0.id}" class="d-block">{$groups.name}</a>
								</li>
								{/foreach}
							</ul>
						</div>
						{/if}
						<div class="{if count($Cart.product_groups) > 1}col-6{else}col-12{/if} secondbox">
							<ul>
								{foreach $Cart.product_groups as $firstIndex=>$groups}
									{if ($groups.id == $Get.fid) || (!$Get.fid && $firstIndex==0)}
									<li>
										{foreach $groups.second as $secondIndex=>$second}
										<ul>
											<li class="{if ($Get.gid == $second.id) || (!$Get.gid && $secondIndex==0)}active{/if}">
												<a {if($second.id==$Get.gid)} {/if} href="/cart?fid={$second.gid}&gid={$second.id}" class="d-block">{$second.name}</a>
											</li>
										</ul>
										{/foreach}
									</li>
									{/if}
								{/foreach}
							</ul>
						</div>
					</div>
				</div>
				{else}
					<div class="d-flex align-items-center justify-content-center" style="height: 560px">{$Lang.no_data_available}</div>
				{/if}

			</div>
		</div>

		<!-- 手机端产品分类 -->
		<!-- <div class="normalhide">
			<select class="form-control selectpicker mr-2" data-style="btn-default">
				{foreach $Cart.product_groups as $firstIndex=>$groups}
				<option value="{$groups.id}" {if ($Get.fid==$groups.id) || (!$Get.fid && $firstIndex==0)}selected="" {/if} data-content='<a href="/cart?fid={$groups.id}&gid={$groups.second.0.id}" class="d-block">{$groups.name}</a>'>

				</option>
				{/foreach}
			</select>
			{foreach $Cart.product_groups as $firstIndex=>$groups}
				{if ($Get.fid==$groups.id) || (!$Get.fid && $firstIndex==0)}
				<select class="form-control selectpicker my-2" data-style="btn-default">
						{foreach $groups.second as $secondIndex=>$second}
						<option value="{$second.id}" {if ($Get.gid==$second.id) || (!$Get.gid && $secondIndex==0)}selected="" {/if} data-content='<a href="/cart?fid={$second.gid}&gid={$second.id}" class="d-block">{$second.name}</a>'>
							
						</option>
						{/foreach}
					</select>
				{/if}
			{/foreach}
		</div> -->
	</div>
<!-- select -->
<link rel="stylesheet"
	href="/themes/cart/default/assets/js/bootstrap-select/css/bootstrap-select.min.css?v={$Ver}">
<script src="/themes/cart/default/assets/js/bootstrap-select/js/bootstrap-select.min.js?v={$Ver}"></script>

	<script>
		// 搜索
		$('#searchInp').on('keydown', function (e) {
			if (e.keyCode == 13) {
				location.href = '/cart?action=product&keywords=' + $('#searchInp').val()
			}
		})
		$('#searchIcon').on('click', function () {
			location.href = '/cart?action=product&keywords=' + $('#searchInp').val()
		});
		// function formSubmitBtn(_this)
		// {
		// 	$(this).parent().submit();
		// }
	</script>