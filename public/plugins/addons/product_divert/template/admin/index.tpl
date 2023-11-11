<link type="text/css" href="{$Themes}/assets/libs/toastr/build/toastr.min.css" rel="stylesheet" />
<script src="{$Themes}/assets/libs/toastr/build/toastr.min.js"></script>
<link rel="stylesheet" href="{$Themes}/assets/libs/bootstrap-select/css/bootstrap-select.min.css?v={$Ver}">
<script src="{$Themes}/assets/libs/bootstrap-select/js/bootstrap-select.min.js?v={$Ver}"></script>
 <style type="text/css">
  .table-responsive{
    min-height: 385px;
  }
  .table-container .table td,.table-container .table thead th{padding: .75rem;}
  .table td, .table thead th, .box_nowrap {
    white-space: nowrap;
}
  .table {
    width: 100%;
    margin-bottom: 1rem;
    color: #495057;
}
.bg-light {
    background-color: #eff2f7 !important;
}
  .table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #eff2f7;
}

</style>
 <section class="admin-main">
    <div class="container-fluid">
      <div class="page-container">
        <div class="card">
          <div class="card-body">
              <div class="table-container">
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

                        <th class="pointer" prop="id">
                          <span>产品</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                        </th>
                        <th class="pointer" prop="type">
                          <span>转出人</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                        </th>
                        <th class="pointer" prop="subtotal">
                          <span>转入人</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                        </th>
                        <th class="pointer" prop="paid_time">
                          <span>发起时间</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                        </th>
                        <th class="pointer" prop="due_time">
                          <span>完成时间</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i>
                          </span>
                        </th>
                        <th class="pointer" prop="status">
                          <span>费用</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i> 
                          </span>
                        </th>
                        <th class="pointer" prop="status">
                          <span>状态</span>
                          <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
                            <i class="bx bx-caret-up"></i>
                            <i class="bx bx-caret-down"></i> 
                          </span>
                        </th>
                        <th width="180px">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                    {foreach $list as $product}
                     <tr>
                        <td>{$product.product_name}</td>
                        <td>{$product.push_username}</td>
                        <td>{$product.pull_username}</td>
                        <td>{$product.create_time}</td>
                        <td>{$product.end_time}</td>
                        <td>转出:{$product.push_cost}/转入:{$product.pull_cost}</td>
                        <td>{$product.status}</td>
                        <td>

                        </td>
                      </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
                <!-- 表单底部调用开始 -->
                <div class="table-footer">
                  <div class="table-tools " >
                    <!-- <button class="btn btn-primary mr-1 " id="readBtn" type="submit">{$Lang.consolidated_payment}</button>
                    <span id="pay-combine">{$Lang.you_have}{$Count}{$Lang.paid_total}{$Total_money}{$Lang.element}<span> -->
                  </div>
                  <div class="table-pagination">
                    <div class="table-pageinfo mr-2">
                      <span>共 {$pageInfo.count} 条</span>
                      <span class="mx-2">
                        每页
                        <select name="limit" id="limitSel">
                          <option value="10" {if $pageInfo.limit==10}selected{/if}>10</option>
                          <option value="15" {if $pageInfo.limit==15}selected{/if}>15</option>
                          <option value="20" {if $pageInfo.limit==20}selected{/if}>20</option>
                          <option value="50" {if $pageInfo.limit==50}selected{/if}>50</option>
                          <option value="100" {if $pageInfo.limit==100}selected{/if}>100</option>
                        </select>
                        条
                      </span>
                    </div>
                    <ul class="pagination pagination-sm">
                      {foreach $pageInfo.pages as $v}
                        <a href="{:shd_addon_url('ProductDivert://AdminIndex/index')}&page={$v}&limit={$pageInfo.limit}">{$v}</a>
                      {/foreach}
                    </ul>
                  </div>
                </div>
              </div>
            </div>
        </div>
         </div>
    </div>
  </section>
<script>
  $("#limitSel").blur(function () {
   var limit = '&limit='+this.value;
    window.location.href="{:shd_addon_url('ProductDivert://AdminIndex/index')}"+limit;
  });

</script>