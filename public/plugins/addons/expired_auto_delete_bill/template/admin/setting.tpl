  <section class="admin-main">
    <div class="container-fluid">
      <div class="page-container">
        <div class="card">
          <div class="card-body">
            <div class="card-title row"> <div style="padding:0 15px;">{$Title}</div>
              <div class="col-lg-8 col-md-12 col-sm-12">
                {foreach $PluginsAdminMenu as $v}
                  {if $v['custom']}
                    <span  class="ml-2"><a  class="h5" href="{$v.url}" target="_blank">{$v.name}</a></span>
                  {else/}
                    <span  class="ml-2"> <a  class="h5" href="{$v.url}">{$v.name}</a></span>
                  {/if}
                {/foreach}
              </div>
            </div>
            <!-- <div class="tabs">
              <div class="tab-item selected">设置</div>
              <div class="tab-item">记录</div>
            </div> -->
            <div class="tab-content mt-4">
              <div class="table-body">
                <form method="post" class="form" action="{:shd_addon_url('ExpiredAutoDeleteBill://AdminIndex/setting')}">
                  <div class="form-group row"><div class="col-sm-1"><label>产品到期账单处理方式</label></div>
                    <div class="col-sm-1">
                      <select class="form-control" name="expired_bill_action">
                        <option value="">无</option>
                        <option value="delete" {if $system.expired_bill_action=="delete"}selected{/if}>直接删除</option>
                        <option value="cancel" {if $system.expired_bill_action=="cancel"}selected{/if}>标记取消</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row">
                    <div class="col-sm-10">
                      <button type="submit" class="btn btn-primary w-md">保存更改</button>
                      <button type="button" class="btn btn-outline-secondary w-md" onclick="javascript:location.reload();">取消更改</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>