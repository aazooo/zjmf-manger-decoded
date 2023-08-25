{include file="cart/area/topbar-categories"}

<link rel="stylesheet" href="/themes/cart/area/assets/css/product.css?v={$Ver}">

<!-- 配置 -->
<div class="configinfo_box">
  <div class="configinfo_box_option mb-3">
    <div class="box_option_left">
      <span style="writing-mode: tb;">{$Lang.product_configuration}</span>
    </div>
    <div class="box_option_right p-2">
      {include file="cart/area/config"}
    </div>
  </div>
  <!-- <div class="configinfo_box_cycle">
    <div class="box_cycle_left">
      <span style="writing-mode: tb;">周期</span>
    </div>
    <div class="box_cycle_right"></div>
  </div> -->

  <div class="configinfo_box_options wrap configoption_total">

  </div>

</div>
<script>
  $(function () {
    // 已选配置
    var showDetail = true
    $(document).on('click', '.bottom_left_expand', function () {
      showDetail = !showDetail
      if (!showDetail) {
        $('.box_options_inner').slideDown();
        $('.bottom_left_expand').html('{$Lang.put_away}'+`<i class="bx bx-caret-up"></i>`);
      } else {
        $('.box_options_inner').slideUp()
        $('.bottom_left_expand').html('{$Lang.open}'+`<i class="bx bx-caret-down"></i>`);
      }
    });

  })

 
</script>