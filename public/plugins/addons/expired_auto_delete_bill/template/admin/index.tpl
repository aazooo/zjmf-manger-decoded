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
              <div class="tab-item">设置</div>
              <div class="tab-item selected">记录</div>
            </div> -->
            <div class="tab-content mt-4">
              <div class="table-body table-responsive">
                <table class="table table-bordered table-hover">
                  <caption></caption>
                  <thead class="thead-light">
                    <tr>
                      <th class="t4">账单号</th>
                      <th>状态</th>
                      <th>处理时间</th>
                      <th>关联产品</th>
                    </tr>
                  </thead>
                  <tbody>
                    {foreach $list as $v}
                      <tr>
                        <td>{if $v.status=='Cancelled'}<a href="{$v.url}">{/if}#{$v.invoiceid}{if $v.status=='Cancelled'}</a>{/if}</td>
                        <td>{if $v.status=='Cancelled'}<a href="{$v.url}">{/if}<font color="{$v.color}">{$v.status_zh}</font>{if $v.status=='Cancelled'}</a>{/if}</td>
                        <td>{$v.create_time}</td>
                        <td>{$v.domain}{if $v.dedicatedip!=''}-{$v.dedicatedip}{/if}</td>
                      </tr>
                    {/foreach}
                  </tbody>
                </table>
              </div>
              <div class="table-footer">
                <div class="table-pagination">
                  <div class="table-pageinfo">
                    <span>共 {$pageInfo.count} 条</span>
                    <span class="mx-2"> 每页显示 10 条数据</span>
                  </div>
                  <nav>
                    <ul class="pagination">
                      <li class="page-item {if $pageInfo.curPage==1} disabled {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}&page={$pageInfo.prev}">上一页</a></li>
                      {foreach $pageInfo.pages as $v}
                        <li class="page-item {if $v==$pageInfo.curPage} active {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}&page={$v}">{$v}</a></li>
                      {/foreach}
                      <li class="page-item {if $pageInfo.curPage==$pageInfo.page} disabled {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}&page={$pageInfo.next}">下一页</a></li>
                    </ul>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
