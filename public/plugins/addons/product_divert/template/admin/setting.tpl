
{if $ErrorMsg}
    {include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
    {include file="error/notifications" value="$SuccessMsg" url=""}
{/if}

<style type="text/css">
ul{padding: 0;}
.form-control{
  display:inline-block;
}
.biaoti{
  display: block;
  width: 90px;
  position: relative;
  float: left;
  margin-right: 20px
}
.inputwidht{width: 40%;}
.wenhaotishi{
  z-index: 999;
  padding: 10px;
  font-style: initial;
  color: black;
  min-width: 130px;
  background: white;
  box-shadow: 1px 1px 7px 0px #b7b7b7;
  bottom: 22px;
  left: -19px;
  display: none;
  position: absolute;
  border-radius: 6px;
  font-size: 14px;
}
.wenhaotishi::before{
  content: '';
    position: absolute;
    bottom: -10px;
    left: 20px;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 10px solid white;
}

.biaoti .bi{width: 12px;height: 12px;position: relative;}
.biaoti .bi:hover .wenhaotishi{
  display: block;
}
</style>
<form method="post" action="{:shd_addon_url('ProductDivert://AdminIndex/setting')}" class="needs-validation" novalidate>
 <section class="admin-main">
    <div class="container-fluid">
      <div class="page-container">
        <div class="card">
          <div class="card-body">
            <h3 class="font-weight-bold">产品转移</h3>
            <h6 class="text-black-50">在这里可以配置、查看产品转移相关内容</h6>
    <ul class="rs mt-2">
        <li class="row my-3">
          <span class="col-md-6 col-xs-12"><span>是否启用产品转移</span>
                      <div class="custom-control custom-switch" dir="ltr">
                        <input type="checkbox" type="checkbox" name="is_open" value="{$system['is_open']}" {if $system['is_open']==1}checked{/if} class="custom-control-input" id="customSwitchsizemd">
                        <label class="custom-control-label" for="customSwitchsizemd"></label>
                      </div>
        </span><span class="col-md-6 col-xs-12"></span></li>
        <li class="row my-3"><span class="col-md-6 col-xs-12"><span class="biaoti">转移有效期<i style="color:#3699ff;margin-left:5px;" class="bi bi-question-circle-fill"><div class="wenhaotishi">超过该时间未接受的转出，将会被自动关闭</div></i></span>
        <input class="form-control inputwidht" min="1" type="number"  name="validity_period" value="{$system['validity_period']}" onkeyup="f(this)">
        天</span><span class="col-md-6 col-xs-12"></span></li>
        <li class="row my-3"><span class="col-md-6 col-xs-12"><span class="biaoti">转出费用</span>
        <input class="form-control inputwidht" min="0" type="number" name="push_cost" value="{$system['push_cost']}" onkeyup="f(this)"> 元</span><span class="col-md-6 col-xs-12" ></span></li>
        <li class="row my-3"><span class="col-md-6 col-xs-12"><span class="biaoti">转入费用</span>
        <input class="form-control inputwidht" min="0" type="number" name="pull_cost" value="{$system['pull_cost']}" onkeyup="f(this)"> 元</span><span class="col-md-6 col-xs-12" ></span></li>
        <li class="row my-3"><span class="col-md-6 col-xs-12"><span class="biaoti">保护期<i style="color:#3699ff;margin-left:5px;" class="bi bi-question-circle-fill"><div class="wenhaotishi">产品订购后多久才能转移</div></i></span>
        <input class="form-control inputwidht" min="0" type="number" name="protection_period" value="{$system['protection_period']}" onkeyup="f(this)"> 天</span><span class="col-md-6 col-xs-12" ></span></li>
        <li class="row my-3"><span class="col-md-6 col-xs-12"><span class="biaoti">转移产品范围<i style="color:#3699ff;margin-left:5px;" class="bi bi-question-circle-fill"><div class="wenhaotishi">多选选择支持自助转移的产品</div></i></span>
              <select id="usertype" style="width: 60%;" name="product_range[]" class="selectpicker show-tick form-control inputwidht" multiple data-live-search="false" multiple="multiple">
                  {foreach $productgroups as $group}
                      <option>{$group.name}</option>
                      {foreach $res_products[$group.id] as $product}
                            <option value="{$product.id}" {if in_array($product.id,$selected)}selected{/if} >--{$product.name}</option>
                      {/foreach}
                  {/foreach}
              </select>

        </span><span class="col-md-6 col-xs-12"></span></li>
    </ul>
       <div class="form-group row">
                    <div class="col-sm-10">
                      <button type="submit" class="btn btn-primary w-md">保存更改</button>
                      <button type="button" class="btn btn-outline-secondary w-md" onclick="javascript:location.reload();">取消更改</button>
                    </div>
                  </div>
  </div>
</div>
  </div>
    </div>
  </section>
</form>
<script>
    $('#customSwitchsizemd').click(function () {
            var is_open = this.value;
            if (is_open == 1){
                $('#customSwitchsizemd').val(0);
            }else {
                $('#customSwitchsizemd').val(1);
            }
        }
    );
   function f(d) {
       d.value=d.value.replace(/\-/g,"");
   }
</script>

