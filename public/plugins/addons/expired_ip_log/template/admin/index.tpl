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
            <div class="tabs">
              <div class="tab-item selected">日志</div>
            </div>
            <div class="tab-content mt-4">
              <div class="table-header">
                <div class="table-tools">
                  <input type="text" class="form-control" name="keywords" value="{$keywords}">
                  <btn id="search" class="btn btn-primary w-xs"><i class="fas fa-search"></i> 搜索</btn>
                </div>
                <div class=""></div>
              </div>
              <div class="table-body table-responsive">
                <table class="table table-bordered table-hover">
                  <caption></caption>
                  <thead class="thead-light">
                    <tr>
                      <th class="t4">IP地址</th>
                      <th>使用人</th>
                      <th>开始时间</th>
                      <th>删除时间</th>
                    </tr>
                  </thead>
                  <tbody>
                    {foreach $list as $v}
                      <tr>
                        <td>{$v.ip}</td>
                        <td><a href="{$v.url}">{$v.username}{if $v.email}({$v.email}){/if}</a></td>
                        <td>{$v.host_create_time}</td>
                        <td>{$v.create_time}</td>
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
                      <li class="page-item {if $pageInfo.curPage==1} disabled {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}{if $keywords}&keywords={$keywords}{/if}&page={$pageInfo.prev}">上一页</a></li>
                      {foreach $pageInfo.pages as $v}
                        <li class="page-item {if $v==$pageInfo.curPage} active {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}{if $keywords}&keywords={$keywords}{/if}&page={$v}">{$v}</a></li>
                      {/foreach}
                      <li class="page-item {if $pageInfo.curPage==$pageInfo.page} disabled {/if}"><a class="page-link" href="{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}{if $keywords}&keywords={$keywords}{/if}&page={$pageInfo.next}">下一页</a></li>
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
  <script type="text/javascript">
    $('#search').click(function(){
      var url = "{:shd_addon_url('ExpiredIpLog://AdminIndex/index')}"
      var keywords = $("input[name='keywords']").val()
      if(keywords!=''){
        url = url+'&keywords='+keywords
      }
      window.location.href = url
    });
  </script>
