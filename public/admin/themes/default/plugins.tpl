{if $ErrorMsg}
    {include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
    {include file="error/notifications" value="$SuccessMsg" url=""}
{/if}
<style>
    .page-item{
        width: 26px;
        text-align: center;
    }
</style>
<section class="admin-main">
    <div class="container-fluid">
        <div class="page-container">
            <div class="card">
                <div class="card-body">
                    <div class="help-block">
                        {$Lang.plug_in_tips}

                    </div>
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-filter">
                                <div class="row">
                                    <div class="col">
                                         <btn class="btn btn-primary w-xs"
                                             onclick="window.open('https://market.idcsmart.com/shop/#/app-store?app_type=addons')">
                                              {$Lang.moreInterFace}
                                         </btn>
                                    </div>
                                </div>
                            </div>
                            <div class="table-search">
                                <div class="row justify-content-end">
                                    <div class="search-box" style="padding-right:15px;">
                                        <div class="position-relative table-tools">
                                            <input type="text" class="form-control" id="searchInp" placeholder="{$Lang.enter_keywords}">
                                            <btn class="btn btn-primary w-xs" id="searchIcon"><i class="fas fa-search"></i> {$Lang.search}</btn>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-body table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                <tr>
                                    <th class="center ">{$Lang.plug_in_name}</th>
                                    <th>{$Lang.plug_in_id}</th>
                                    <th>{$Lang.describe}</th>
                                    <th>{$Lang.author}</th>
                                    <th>{$Lang.current_version}</th>
                                    <th>{$Lang.latest_version}</th>
                                    <th>{$Lang.state}</th>
                                    <th>{$Lang.operating}</th>
                                </tr>
                                </thead>

                                <tbody>
                                {foreach name="Plugins" item="vo"}
                                    <tr>
                                        <td>{$vo.title}<br>{$vo.menu}</td>
                                        <td>{$vo.name}</td>
                                        <td>{$vo.description}</td>
                                        <td>{$vo.author}</td>
                                        <td>{$vo.version}</td>
                                        <td>{$vo.app_version}</td>
                                        <td>{$status[$vo['status']]}</td>
                                        <td>
                                            {if condition="$vo['status']==3"}
                                                <button  class="btn btn-xs btn-primary js-ajax-dialog-btn" type="button"
                                                        onclick="plinstall('{$vo.name}')">{$Lang.install}</button>
                                            {else/}
                                                {if $vo.update_btn}

                                                        <button  class="btn btn-xs btn-success" type="button"
                                                                onclick="plupdate('{$vo.name}','{$vo.app_id}')" {if $vo.update_disable} disabled {/if}>
                                                            <span {if $vo.update_disable} data-toggle="tooltip" data-placement="top" title="{$Lang.to_update_tips}" {/if}>{$Lang.to_update} </span></button>

                                                {/if}

                                                {if condition="$vo['status']==0"}
                                                    <button class="btn btn-xs btn-success" type="button"
                                                       onclick="pltoggle('{$vo.id}','{$vo.status}')">{$Lang.enable}</button>
                                                {else/}
                                                <button class="btn btn-xs btn-warning" type="button"
                                                       onclick="pltoggle('{$vo.id}','{$vo.status}')">{$Lang.disable}</button>
                                                {/if}
                                                 <button class="btn btn-xs btn-danger" type="button"
                                                      onclick="pluninstall('{$vo.id}')">{$Lang.uninstall}</button>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                        <div class="table-footer">
                            <div class="table-pagination">
                                <div class="table-pageinfo mr-2">
                                    <span>{$Lang.common} {$pageInfo.count} {$Lang.strips}</span>
                                    <span class="mx-2">{$Lang.each_page}
                                        <select name="limit" id="limitSel" style="width: 60px;border-color:#ddd;border-radius:4px;background:#fff">
                                              <option value="10" {if $pageInfo.limit==10}selected{/if}>10</option>
                                              <option value="15" {if $pageInfo.limit==15}selected{/if}>15</option>
                                              <option value="20" {if $pageInfo.limit==20}selected{/if}>20</option>
                                              <option value="50" {if $pageInfo.limit==50}selected{/if}>50</option>
                                              <option value="100" {if $pageInfo.limit==100}selected{/if}>100</option>
                                        </select> {$Lang.strips}</span>
                                </div>
                                <ul class="pagination pagination-sm">
                                    {$pageInfo.pages}
                                </ul>
                            </div>
                        </div>
                        <!--<div class="table-footer">
                            <div class="table-pagination">
                                <div class="table-pageinfo">
                                    每页显示 10 条数据
                                </div>
                                <nav>
                                    <ul class="pagination">
                                        <li class="page-item disabled"><a class="page-link" href="#">上一页</a></li>
                                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item active"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item"><a class="page-link" href="#">下一页</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>-->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 提示框 -->
<div class="modal" tabindex="-1" role="dialog" id="comfirmModal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$Lang.prompt}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="tipContent">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{$Lang.cancel}</button>
                <button type="button" class="btn btn-primary" id="sureBtn">{$Lang.determine}</button>
            </div>
        </div>
    </div>
</div>
</div>
<script>
console.log(localStorage.getItem('zjmf_lang_file_name'))
    $(function(){
        $('[data-toggle="tooltip"]').tooltip();
    })
    // 安装
    function plinstall(name) {
        $('#comfirmModal').modal('show')
        $('#tipContent').text('{$Lang.are_you_sure_install}')
        $(document).on('click', '#sureBtn', function () {
            $.ajax({
                type: "POST",
                url: '' + '/{$Admin}/pl_install',
                data: {
                    name: name,
                    module: 'addons'
                },
                success: function (data) {
                    if (data.status == 200) {
                        toastr.success(data.msg)
                        location.reload()
                    } else
                        toastr.error(data.msg)
                }

            });
        });
    }

    // 更新
    function plupdate(name, app_id) {
        $('#comfirmModal').modal('show')
        $('#tipContent').text('{$Lang.are_you_sure_to_update}')
        $(document).on('click', '#sureBtn', function () {
            $(this).html('<i class="fa fa-spinner fa-pulse fa-3x fa-fw" style="\n' +
                '    font-size: 14px;\n' +
                '    margin-right: 4px;\n' +
                '"></i>{$Lang.determine}');
            $(this).attr('disabled', true);
            var _this = this;
            $.ajax({
                type: "POST",
                url: '' + '/{$Admin}/pl_update',
                data: {
                    name: name,
                    app_id: app_id,
                    module: 'addons'
                },
                success: function (data) {
                    $(_this).html('{$Lang.determine}');
                    $(_this).attr('disabled', false);
                    if (data.status == 200) {
                        toastr.success(data.msg)
                        location.reload()
                    } else
                        toastr.error(data.msg)
                }

            });
        });
    }

    // 卸载
    function pluninstall(id) {
        $('#comfirmModal').modal('show')
        $('#tipContent').text('{$Lang.are_you_sure_uninstall}')
        $(document).on('click', '#sureBtn', function () {
            $.ajax({
                type: "POST",
                url: '' + '/{$Admin}/pl_uninstall',
                data: {
                    id: id,
                },
                success: function (data) {

                    if (data.status == 200) {
                        toastr.success(data.msg)
                        location.reload()
                    } else
                        toastr.error(data.msg)
                }


            });
        });

    }

    // 启用/禁用
    function pltoggle(id, status) {
        $('#comfirmModal').modal('show')
        var obj = {}
        if (status == 0) {
            obj = {
                id: id,
                enable: '1'
            }
            $('#tipContent').text('{$Lang.are_you_sure_to_enable_it}')
        } else {
            obj = {
                id: id,
                disable: '1',
            }
            $('#tipContent').text('{$Lang.are_you_sure_to_disable_it}')
        }
        $(document).on('click', '#sureBtn', function () {
            $.ajax({
                type: "POST",
                url: '' + '/{$Admin}/pl_toggle',
                data: obj,
                success: function (data) {

                    if (data.status == 200) {
                        toastr.success(data.msg)
                        location.reload()
                    } else
                        toastr.error(data.msg)
                }

            });
        });

    }

    // 每页条数
    $("#limitSel").blur(function () {
        var limit = 'limit='+this.value;
        window.location.href="/{:adminAddress()}/plugins?"+limit+'&languagesys={$Think.get.languagesys}'
    });
    // 关键字搜索
    $('#searchInp').val('{$Think.get.keywords}')
    $('#searchInp').on('keydown', function (e) {
        if (e.keyCode == 13) {
            location.href = '/{:adminAddress()}/plugins?'+
                'keywords=' + $('#searchInp').val()+'&page={$Think.get.page}&limit={$Think.get.limit}'+'&languagesys={$Think.get.languagesys}'
        }
    })
    $('#searchIcon').on('click', function () {
        location.href = '/{:adminAddress()}/plugins?'+
            'keywords=' + $('#searchInp').val()+'&page={$Think.get.page}&limit={$Think.get.limit}'+'&languagesys={$Think.get.languagesys}'
    });

</script>