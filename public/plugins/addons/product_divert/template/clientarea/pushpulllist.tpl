{include file="includes/tablestyle"}

{include file="includes/deleteConfirm"}
{if $ErrorMsg}
    {include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
    {include file="error/notifications" value="$SuccessMsg" url=""}
{/if}
{include file="includes/paymodal"}
<style>
</style>
<div class="card">
<div class="card-body">
    <form action="combinebilling">
        <div class="table-container">
            <div class="table-header">
                <div class="table-filter">

                </div>
                <div class="table-search d-flex justify-content-end">
                    <select class="form-control" id="statusSel" title="请选择状态" style="width: 150px">
                        <option value="">{$Lang.whole}</option>
                        <option value="1">{$status_describe.1}</option>
                        <option value="2">{$status_describe.2}</option>
                        <option value="3">{$status_describe.3}</option>
                        <option value="4">{$status_describe.4}</option>
                    </select>
                </div>

            </div>
            <div class="table-responsive">
                <table class="table tablelist">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
            <thead class="bg-light">
            <tr>
                <th class="pointer" prop="">
                    <span>{$Lang.table_title_goods}</span>
                    <!--<span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>-->
                </th>
                <th class="pointer" prop="">
                    <span>{$Lang.table_title_counterparty}</span>
                    <!--<span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>-->
                </th>
                <th class="pointer" prop="">
                    <span>{$Lang.cost}</span>
                    <!--<span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>-->
                </th>
                <th class="pointer" prop="create_time">
                    <span>{$Lang.table_title_launch_at}</span>
                    <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                </th>
                <th class="pointer" prop="end_time">
                    <span>{$Lang.table_title_end_at}</span>
                    <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                </th>
                <th class="pointer" prop="">
                    <span>{$Lang.table_title_divert_type}</span>
                    <!--<span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>-->
                </th>
                <th class="pointer" prop="">
                    <span>{$Lang.table_title_divert_status}</span>
                    <!--<span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>-->
                </th>
                <th width="180px">{$Lang.table_title_divert_handle}</th>
            </tr>
            </thead>
            <tbody>
            {if !empty($product_divert)}
                {foreach $product_divert as $pro}
                    <tr>
                        <td>{$pro.product_name} {$pro.product_domain} {$pro.product_ip}</td>
                        <td>{if $pro.push_userid == $user_now}
                                {$pro.pull_username}
                            {else}
                                {$pro.push_username}
                            {/if}
                        </td>
                        <td>{if $pro.push_userid == $user_now}
                                ￥{$pro.push_cost}元
                            {else}
                                ￥{$pro.pull_cost}元
                            {/if}
                        </td>
                        <td>{$pro.create_time}</td>
                        <td>{$pro.end_time}</td>
                        <td>{if $pro.push_userid == $user_now}
                                转出
                            {else}
                                转入
                            {/if}
                        </td>
                        <td>{$pro.status_text}</td>
                        {if $pro.push_userid == $user_now}
                        <td>
                              {if $pro.push_pay_status == 'Unpaid'}
                                  <a href="javascript: payamount({$pro['push_invoice_id']});" class="text-primary mr-2"><i class="fas fa-check-circle"></i> {$Lang.payment}</a>
                                  <a href="javascript:;" onclick="getid(`{$pro['id']}`,0)" data-id="{$pro['id']}" class="text-primary mr-2">取消</a>
                              {elseif $pro.push_pay_status == 'Paid' && $pro.status == 1}
                                   <a href="javascript:;" onclick="getid(`{$pro['id']}`,0)" data-id="{$pro['id']}" class="text-primary mr-2">取消</a>
                              {/if}

                        </td>
                        {else}
                            <td>
                                {if empty($pro.pull_invoice_id) && $pro.push_pay_status == 'Paid' && $pro.status == 1}
                                    <a href="{:shd_addon_url("ProductDivert://Index/pullserver",['id'=>$pro['id']],true)}" class="text-success mr-2"><i class="fas fa-eye"></i> 接收</a>
                                    <a href="javascript:;" onclick="getid(`{$pro['id']}`,1)" data-id="{$pro['id']}" class="text-primary mr-2">拒绝</a>
                                {elseif $pro.pull_pay_status == 'Unpaid'}
                                    <a href="javascript: payamount({$pro['pull_invoice_id']});" class="text-primary mr-2"><i class="fas fa-check-circle"></i> {$Lang.payment}</a>
                                    <a href="javascript:;" onclick="getid(`{$pro['id']}`,1)" data-id="{$pro['id']}" class="text-primary mr-2">拒绝</a>
                                {elseif $pro.pull_pay_status == 'Paid' && $pro.status == 1}
                                    <a href="{:shd_addon_url("ProductDivert://Index/verificationResult",['id'=>$pro['id']],true)}" class="text-success mr-2"><i class="fas fa-eye"></i> 手动检测</a>
                                {/if}
                            </td>
                        {/if}
                    </tr>
                {/foreach}
            {else}
                <tr>
                    <td colspan="8">
                        <div class="no-data">{$Lang.nothing_content}</div>
                    </td>
                </tr>
            {/if}
            </tbody>
        </table>
    </div>


            <div class="table-footer">
                <div class="table-pagination">
                    <div class="table-pageinfo mr-2">
                        <span>{$Lang.common} {$page_data.Count} {$Lang.strips}</span>
                        <span class="mx-2">
                        {$Lang.each_page}
                        <select name="" id="limitSel">
                          <option value="10" {if $page_data.Limit==10}selected{/if}>10</option>
                          <option value="15" {if $page_data.Limit==15}selected{/if}>15</option>
                          <option value="20" {if $page_data.Limit==20}selected{/if}>20</option>
                          <option value="50" {if $page_data.Limit==50}selected{/if}>50</option>
                          <option value="100" {if $page_data.Limit==100}selected{/if}>100</option>
                        </select>
                        条
                      </span>
                    </div>
                    <ul class="pagination pagination-sm">
                        {$page_data.Pages}
                    </ul>
                </div>
            </div>

    </form>

    <!-- 取消弹框 -->
    <div id="CloseModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title mt-0" id="myModalLabel">提示</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body" id="Closebody">
                是否取消
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary waves-effect waves-light" id="cancisok"></button>
                  <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal" id="canctow">取消</button>
              </div>
          </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->

     <!-- PUSH Modal content for the above example -->
     <div id="pushModal" class="modal fade bs-example-modal-xl show" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <form method="post" action="{:shd_addon_url("ProductDivert://Index/pushserver",[],true)}" class="modal-content">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title mt-0" id="myExtraLargeModalLabel">产品转出</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">

                <div class="form-group row">
                    <label for="example-text-input" class="col-md-2 col-form-label">产品：</label>
                    <div class="col-md-10" style="line-height: 36px;">{$push_data.product.name} {$push_data.product.domain} {$push_data.product.dedicatedip}</div>
                </div>

                <div class="form-group row">
                    <label for="example-text-input" class="col-md-2 col-form-label">接收方：</label>
                    <div class="col-md-10"><h4 id="usertext"></h4>
                      <input class="form-control" id="search" type="text" value="" id="example-text-input" palceholder="请输入接受方手机号或邮箱">
                    </div>
                    <label for="example-text-input" class="col-md-2 col-form-label"></label>
                    <div class="col-md-10" style="color: #FBAE14;font-size: 0.6rem;">接收方需填写邮箱/手机</div>
                </div>


                <div class="form-group row">
                    {if $push_data.system.push_cost > 0}
                        <label for="example-text-input" class="col-md-2 col-form-label">转出费用：</label>
                        <div class="col-md-10" style="line-height: 36px;">{$push_data.system.push_cost}元</div>
                    {/if}
                </div>

                <div class="form-group row" style="color: #FBAE14;font-size: 0.6rem;">
                  <label for="example-text-input" class="col-md-2 col-form-label"></label>
                  <div class="col-md-10">本次转移您需要支付<span>{$push_data.system.push_cost}元</span>，支付后，接收方会收到产品转入通知 </div>
                </div>
                  <input type="hidden" name="hostid" value="{$hostid}" class="form-control">
                  <input type="hidden" name="userid" id="userid" value="" class="form-control">
                <div class="form-group mb-0">
                  <div class="modal-footer">
                      <button type="submit" class="btn btn-primary waves-effect waves-light mr-1 disClass" disabled="disabled">
                          确认
                      </button>
                      <button type="reset" id="cancel" class="btn btn-secondary waves-effect">
                          取消
                      </button>
                  </div>
              </div>

              </div>
          </div><!-- /.modal-content -->
          </form>
      </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
    <!-- 表单底部调用开始 -->
   <!-- PULL Modal content for the above example -->
   <div id="pullModal" class="modal fade bs-example-modal-xl show" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="{:shd_addon_url("ProductDivert://Index/pullserver",[],true)}" class="modal-content">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myExtraLargeModalLabel">产品接收</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

              <div class="form-group row">
                  <label for="example-text-input" class="col-md-2 col-form-label">产品：</label>
                  <div class="col-md-10" style="line-height: 36px;">{$pull_data.product_name} {$pull_data.product_domain} {$pull_data.product_ip}</div>
              </div>

              <div class="form-group row">
                  <label for="example-text-input" class="col-md-2 col-form-label">转出方：</label>
                  <div class="col-md-10">{$pull_data.push_username}</div>
              </div>

              <div class="form-group row">
                  {if $pull_data.pull_cost > 0}
                      <label for="example-text-input" class="col-md-2 col-form-label">转入费用：</label>
                      <div class="col-md-10" style="line-height: 36px;">{$pull_data.pull_cost}元</div>
                  {/if}
              </div>

              <div class="form-group row" style="color: #FBAE14;font-size: 0.6rem;">
                <label for="example-text-input" class="col-md-2 col-form-label"></label>
                <div class="col-md-10">本次转移您需要支付<span>{$pull_data.pull_cost}元</span>，支付后，该产品会立刻转移到您的账户中 </div>
              </div>
                <input type="hidden" name="id" id="id" value="{$pull_data.id}" class="form-control">
              <div class="form-group mb-0">
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">
                        确认
                    </button>
                    <button type="reset" id="cancel1" class="btn btn-secondary waves-effect">
                        取消
                    </button>
                </div>
            </div>

            </div>
        </div><!-- /.modal-content -->
        </form>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</div>
</div>
</div>
<!-- <div class="modal-backdrop fade show" style="display:none"></div> -->
<script>
   function getid(params,type) {
    //  console.log(type);
    $("#CloseModal").modal('show')
    $("#cancisok").empty()

    if(type==0){
      $("#Closebody").empty()
      $("#Closebody").append('取消转移将导致对方无法接受，您还要继续吗？')
      var url = '{:shd_addon_url("ProductDivert://Index/pushrefuse",[],true)}';
      url += '&id='+params;
      $("#cancisok").append('<a href="'+url+'" style="color:white!important;" class="text-primary mr-2"><i class="fas fa-check-circle"></i> 确认</a>')
    }else {
      $("#Closebody").empty()
      $("#Closebody").append('拒绝将无法接收对交易方转移给您的产品，您还要继续吗？')
      var url = '{:shd_addon_url("ProductDivert://Index/pullrefuse",[],true)}';
      url += '&id='+params;
      $("#cancisok").append('<a href="'+url+'" style="color:white!important;" class="text-primary mr-2"><i class="fas fa-check-circle"></i> 确认</a>')
    }
   }


  $("#canctow").on('click',function (msg) {
    $("#CloseModal").modal('hide')
  })
        // $('#pullModal').modal('show');
        // $('#pullModal').modal('hide');
</script>
<script>
    window.onload = function() {
        var oldURL = document.referrer;
        var allargs = oldURL.split("=")[1];//配合push检测
        var nowAllargs = '{$hostid}';//push检测
        var is_open_pull_div = '{$is_open_pull_div}';//pull检测
        var pay_invoice_id = '{$pay_invoice_id}';//支付检测

        //PUSH模态框的唤醒
        if (allargs == nowAllargs) {
          $('#pushModal').modal('show');
        }

        //模拟点击唤醒支付窗口
        if (pay_invoice_id >= 1) {
            // IE
            if(document.all) {
                document.getElementById(pay_invoice_id).click();
            }
            //  兼容其它浏览器
            else {
                var e = document.createEvent("MouseEvents");
                e.initEvent("click", true, true);
                document.getElementById(pay_invoice_id).dispatchEvent(e);
            }
        }else {
            //PULL模态框的唤醒 - 唤醒权限在支付窗口之下
            if (is_open_pull_div >= 1 ) {
                $('#pullModal').modal('show');
            }
        }
    }

    $('#search').blur(function () {
        var search = this.value;
        $(".disClass").attr("disabled","disabled")
        $.ajax({
            type: "POST",
            url: '/product_divert/postNameToUser',
            data: {
                tranfer_name: search
            },
            dataType: "json",
            success: function (res) {
                if (res.status == 200) {
                    var name = res.data.username;
                    var long = name.length;
                    var start = Math.floor(long / 2);
                    var nametext = name.substring(-1, start);
                    for (var i = 0; i < start; i++) {
                        nametext += '*';
                    }
                    $('#userid').val(res.data.id);
                    $('#usertext').html(nametext);
                    $("#usertext").css("color", "red");
                    $(".disClass").removeAttr("disabled");
                } else {
                    toastr.error(res.msg);
                    $('#usertext').html('');
                    $('#userid').val('');$(".disClass").attr("disabled","disabled")
                }
            }
        });
    });

  $("#cancel").on('click',function(params) {
    window.history.go(-1);
  });

  $("#cancel1").on('click',function(params) {
        window.history.go(-1);
    });

</script>

<script>
    var _url = '';
    var status = '{$Think.get.status}';
    // 排序
    $('.bg-light .pointer').on('click', function () {
        var sort = '{$Think.get.sort}'
        location.href = '{:shd_addon_url("ProductDivert://Index/pushpulllist",[],true)}' + '&status={$Think.get.status}&sort=' + (sort == 'desc' ? 'asc' : 'desc') + '&orderby=' + $(this).attr('prop') + '&page={$Think.get.page}&limit={$Think.get.limit}'

    })
        //排序样式
        changeStyle()
        function changeStyle() {
        $('.bg-light th.pointer').children().children().css('color','rgba(0, 0, 0, 0.1)')
        var sort = '{$Think.get.sort}'
        let orderby = '{$Think.get.orderby}'
        let index,
        n
        if(orderby === 'create_time') {
            n = 0
        } else if (orderby ===  'end_time') {
            n = 1
        }
        if (sort === 'desc') {
        index = 1 + 2 * n
        } else if(sort === 'asc'){
        index = 0 + 2 * n
        }
        $('.bg-light th.pointer').children().children().eq(index).css('color','rgba(0, 0, 0, 0.8)')
        }

    // 状态筛选
    $('#statusSel').on('change', function () {
        location.href = '{:shd_addon_url("ProductDivert://Index/pushpulllist",[],true)}' +"&status=" + $('#statusSel').val() + "&sort={$Think.get.sort}&orderby={$Think.get.orderby}&page={$Think.get.page}&limit={$Think.get.limit}"

    });
    // 每页数量选择改变
    $('#limitSel').on('change', function () {
        location.href = '{:shd_addon_url("ProductDivert://Index/pushpulllist",[],true)}' + '&status={$Think.get.status}&sort={$Think.get.sort}&orderby={$Think.get.orderby}&page=1&limit=' + $('#limitSel').val()

    })
</script>
<script src="/themes/clientarea/default/assets/js/billing.js?v={$Ver}"></script>