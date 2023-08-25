<link rel="stylesheet" href="/themes/cart/area/assets/css/topbar.css">

<div class="firstgroup_box mb-2">
  <div class="firstgroup_box_prov mr-2">{$Lang.select_group}</div>
  <div class="firstgroup_box_group">
    {foreach $Cart.product_groups as $index=>$first} 
    {if $first.id==$Get.fid || (!$Get.fid && $index==0)}
	<div class="firstgroup_item pointer active"><a class="text-white" href="/cart?fid={$first.id}{if $Get.site}&site={$Get.site}{/if}">{$first.name}</a></div>
	{assign name="cart_first_id" value="$first.id" /}  
	{assign name="cart_second" value="$first.second" /}  
	{else/}
	<div class="firstgroup_item pointer"><a href="/cart?fid={$first.id}{if $Get.site}&site={$Get.site}{/if}">{$first.name}</a></div>
	{/if}
	{/foreach}
  </div>
</div>

<div class="secondgroup_box mb-2">
  <div class="secondgroup_box_area mr-2">{$Lang.select_type}</div>
  <div class="secondgroup_box_group">
	{foreach $cart_second as $index=>$secondItem}
	{if $secondItem.id == $Get.gid || (!$Get.gid && $index==0)}
    <div class="secondgroup_item pointer active"><a class="text-white" href="/cart?fid={$cart_first_id}&gid={$secondItem.id}{if $Get.site}&site={$Get.site}{/if}">{$secondItem.name}</a></div>
	{assign name="cart_gid" value="$secondItem.id" /} 
	{else/}
	<div class="secondgroup_item pointer"><a href="/cart?fid={$cart_first_id}&gid={$secondItem.id}{if $Get.site}&site={$Get.site}{/if}">{$secondItem.name}</a></div>
	
	{/if}
	{/foreach}
  </div>
</div>
<div class="pro_box mb-2">
  <div class="pro_box_area mr-2">{$Lang.select_area}</div>
  <div class="pro_box_group">
    {foreach $Cart.products as $index=>$list}
	{if $list.id==$Get.pid || (!$Get.pid && $index==0)}
    <div class="pro_item pointer active"><a class="text-white" href="/cart?fid={$cart_first_id}&gid={$cart_gid}&pid={$list.id}{if $Get.site}&site={$Get.site}{/if}">{$list.name}</a></div>
	{else/}
    <div class="pro_item pointer"><a href="/cart?fid={$cart_first_id}&gid={$cart_gid}&pid={$list.id}{if $Get.site}&site={$Get.site}{/if}">{$list.name}</a></div>
	{/if}
    {/foreach}
  </div>
</div>
