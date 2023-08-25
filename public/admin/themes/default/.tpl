{include file="header"}
{include file="menu"}

<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body">
          <!--<div class="btn-group" role="group" aria-label="...">
						{foreach name="navTabs" item="tab"}
						<button type="button" class="btn btn-default">{$tab}</button>
						{/foreach}
					</div>-->
          <div class="help-block">
            {$tip}
          </div>
          <div class="table-container">
            <div class="table-header">
              <div class="table-tools">
                <a href="#" class="btn btn-success w-sm nohide">
                  <i class="fas fa-plus-circle"></i> {$btnText}
                </a>
                <!-- <select class="form-control" id="sort">
									{foreach name="search" item="sh" key="kk"}
										<option value="{$kk}" {if $kk==$_GET['sort']}selected{/if}>{$sh}</option>
									{/foreach}
								</select>
								<input type="text" class="form-control" placeholder="输入关键字" value="{$_GET['searchText']}" id="searchText">
								<btn class="btn btn-primary w-xs" onClick="searchClick()"><i class="fas fa-search"></i> 搜索</btn> -->
                <!-- <btn class="btn btn-success w-xs"><i class="fas fa-plus-circle"></i> {$btnText}</btn> -->
                <btn class="btn btn-outline-secondary w-xs" id="search-more">高级搜索</btn>
                <form id="navTabsForm" action="" method="GET">
                  <input id="typeValue" name="type" hidden="hidden" value="{$type}" />
                </form>
                <div class="status-filter" id="navTabs">
                  {foreach name="navTabs" item="nt"}
                  <div class="filter-item {if $nt.name==$type}selected{/if}" data-value="{$nt.name}">{$nt.label}</div>
                  {/foreach}
                </div>
              </div>
            </div>
            <form id="searchForm" action="" method="GET">
              <div class="more-search">
                <div class="search-row">
                  {foreach name="seachList" item="sl"}
                  {if $sl.type == 'date'}
                  <div class="search-item">
                    <label class="label">{$sl.label}</label>
                    <div class="input-container">
                      <input type="text" name="{$sl.name}" class="form-control datetime" value="{$sl.content}">
                    </div>
                  </div>
                  {elseif $sl.type == 'select'}
                  <div class="search-item">
                    <label class="label">{$sl.label}</label>
                    <div class="input-container">
                      <select class="form-control" name="{$sl.name}">
                        {foreach name="sl.list" item="list"}
                        <option value="{$list.value}" {if $list.value==$sl.content}selected="selected" {/if}>{$list.label}</option>
                        {/foreach}
                      </select>
                    </div>
                  </div>
                  {else}
                  <div class="search-item">
                    <label class="label">{$sl.label}</label>
                    <div class="input-container">
                      <input type="text" name="{$sl.name}" class="form-control" value="{$sl.content}">
                    </div>
                  </div>
                  {/if}
                  {/foreach}
                  <input name="page" value="{$page}" hidden="hidden" />
                  <input id="typeValue" name="type" hidden="hidden" value="{$type}" />
                  <div class="search-item">
                    <label class="label"></label>
                    <div class="input-container">
                      <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i>
                        搜索 </button>
                      <button class="btn btn-outline-secondary" type="reset">重置</button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
            <div class="table-body table-responsive">
              <table class="table table-bordered table-hover">
                <caption>

                  <div class="action">
                    选中的项目：
                    <button type="button" class="btn btn-success btn-sm">核验通过</button>
                    <button type="button" class="btn btn-warning btn-sm">取消订单</button>
                    <button type="button" class="btn btn-danger btn-sm">批量删除</button>
                  </div>
                  <div class="static">
                    总计：<span class="amount">
                      {if $datalist.0 && $datalist.0.prefix}
                      {$datalist.0.prefix}
                      {else}
                      ¥
                      {/if}
                      {$price_total}
                      {if $datalist.0 && $datalist.0.suffix}
                      {$datalist.0.suffix}
                      {else}
                      元
                      {/if}
                    </span>
                    &nbsp;&nbsp;
                    本页总计：<span class="amount">
                      {if $datalist.0 && $datalist.0.prefix}
                      {$datalist.0.prefix}
                      {else}
                      ¥
                      {/if}
                      {$price_total_page}
                      {if $datalist.0 && $datalist.0.suffix}
                      {$datalist.0.suffix}
                      {else}
                      元
                      {/if}
                    </span>
                  </div>
                </caption>
                <thead class="thead-light">
                  <tr>
                    <!-- <th class="checkbox">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="customCheckHead"
													name="headCheckbox">
												<label class="custom-control-label" for="customCheckHead">&nbsp;</label>
											</div>
										</th> -->
                    {foreach name="titleList" item="tl"}
                    <th class="center">{$tl}</th>
                    {/foreach}
                  </tr>
                </thead>
                <tbody>
                  {foreach name="datalist" item="dd"}
                  <tr>
                    <td><a>{$dd.id}</a></td>
                    <td><a>{$dd.username}</a></td>
                    <td>
                      <div><a>{$dd.hosts.0.name}</a></div>
                      <div>{$dd.hosts.0.domain}</div>
                    </td>
                    <td>{if $dd.hosts.0}{$dd.hosts.0.dedicatedip}{else}-{/if}</td>
                    <td>{$dd.create_time_format}</td>
                    <td>
                      {if $dd.cycle}
                      {$dd.amount}/{$dd.cycle}
                      {else}
                      {$dd.amount}
                      {/if}
                    </td>
                    <td>
                      <!-- <div >{$dd.pay_status.name}</div>
                    <div style="color: #999;">{$dd.payment}</div> -->
                      {if $dd.pay_status.name&&$dd.pay_status.color}
                      <div style="color:{$dd.pay_status.color};">{$dd.pay_status.name}</div>
                      <div style="color: #999;">{$dd.payment}</div>
                      {else}
                      <div> {$dd.pay_status}</div>
                      <div style="color: #999;">{$dd.payment}</div>
                      {/if}

                    </td>
                    <!-- <td>{$dd.order_status.name}</td> -->
                    <td>
                      <div style="color:{$dd.order_status.color}">
                        {$dd.order_status.name}
                      </div>

                    </td>
                    <td>
                      {$dd.sum}
                    </td>
                  </tr>
                  {/foreach}
                </tbody>
              </table>
            </div>
            {include file="pageSize"}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 添加弹窗 -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog " role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{$modalTitle}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="modalForm" class="form" method="get">
          {foreach name="modalList" item="ml"}
          {if $ml.type == 'date'}
          <div class="form-group row {if $ml.required}invalid{/if}">
            <label class="{if $ml.required}require{/if}">{$ml.label}</label>
            <div class="col-sm-9">
              <input type="text" name="{$ml.name}" class="form-control datetime" value="{$ml.content}">
              {if $ml.required}
              <div class="invalid-feedback">
                {$ml.tip}
              </div>
              {/if}
            </div>
          </div>
          {elseif $ml.type == 'select'}
          <div class="form-group row {if $ml.required}invalid{/if}">
            <label class="{if $ml.required}require{/if}">{$ml.label}</label>
            <div class="col-sm-9">
              <select class="form-control" name="{$ml.name}">
                {foreach name="ml.list" item="ll"}
                <option value="{$ll.value}">{$ll.label}
                  {if $ll.uid}
                  <input name="uid" hidden="hidden" value="{$ll.uid}" />
                  {/if}
                </option>
                {/foreach}
              </select>
            </div>
          </div>
          {elseif $ml.type == 'checkbox'}
          <div>{$ml.label} 组件暂缺</div>
          {else}
          <div class="form-group row {if $ml.required}invalid{/if}">
            <label class="{if $ml.required}require{/if}">{$ml.label}</label>
            <div class="col-sm-9">
              <input type="text" name="{$ml.name}" class="form-control" value="{$ml.content}">
              {if $ml.required}
              <div class="invalid-feedback">
                {$ml.tip}
              </div>
              {/if}
            </div>
          </div>
          {/if}
          {/foreach}

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">关闭</button>
            <button type="button" id="submit" class="btn btn-primary">保存</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  $('#submit').click(function() {
    var modalForm = $("#modalForm").serialize();
    $.post("accounts", modalForm, function(res) {
      alert(res.msg);
    });
  })

  function deleteClick(id) {
    var cfm = confirm('此操作将永久删除该文件, 是否继续?');
    if (cfm) {
      $.ajax({
        type: 'POST',
        url: 'delete_ticket_status',
        contentType: 'application/json;charset=UTF-8',
        dataType: 'json',
        data: JSON.stringify({
          id: id
        }),
        success: function(res, textStatus, jqXHR) {
          alert(res.msg);
          if (res.status == 200) {
            setTimeout(function() {
              location.reload();
            }, 1000);
          }
        }
      });
    } else {
      console.log('取消');
    }
  }
</script>
{include file="footer"}