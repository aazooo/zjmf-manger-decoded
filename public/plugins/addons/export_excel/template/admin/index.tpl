<style>
    html,body{
        height: 100%;
    }
    body{
        font-family: Poppins, sans-serif;
        font-size: 0.8125rem;
        font-weight: 400;
        line-height: 1.5;
    }
    ul, li{
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .export-container{
        min-height: 100%;
        height: auto;
        padding: 30px;
        box-sizing: border-box;
        background-color: #ffffff;
    }
    .export-container .row{
        height: auto;

    }
    .param-container{
        background-color: #e2e2e2;
        padding: 4px 7px;
        box-sizing: border-box;
        border-radius: 5px;
        font-size: 0.8125rem;
        font-weight: revert;
        position: relative;
    }
    .close-btn{
        display: none;
        position: absolute;
        top: -5px;
        right: -8px;
        background-color: #FF5722;
        border-radius: 50%;
        color: #ffffff !important;
        padding: 0;
        width: 15px;
        height: 15px;
        font-size: 83%;
        line-height: 13px;
    }
    .close-btn.active{
        display: block;
    }
    .param-box{
        display: flex;
        flex-flow: row wrap;
        padding: 8px 5px;
    }
    .param-box .param-box-child{
        margin-right: 12px;
        margin-bottom: 7px;
    }
    .close-btn:hover{
        cursor:pointer;
    }
    .bootstrap-table .fixed-table-container .fixed-table-body .fixed-table-loading .loading-wrap .loading-text
    {
        font-size: 0.825rem !important;
    }
</style>
  <section class="admin-main">
    <div class="container-fluid">
      <div class="page-container">
        <div class="card">
          <div class="card-body export-container">
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
    <div class="row">
        <div class="table-container" style="padding: 10px;width: 100%">
            <div style="margin: 10px 0">
                <button class="btn btn-default" style="border:1px solid #e2e2e2;border-radius: 10px;" onclick="addExport()">新增导出项</button>
            </div>
            <table id="export"></table>
        </div>
    </div>
</div>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <!--      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">×</span>
                      </button> -->
                <h4 class="modal-title" id="myModalLabel">新增导出项</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id="auditForm">

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="modalSaveFun()">保存</button>
            </div>
        </div>
    </div>
</div>

            <div class="modal fade" id="datePicker" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <!--      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">×</span>
                                  </button> -->
                            <h4 class="modal-title" id="myModalLabel">时间筛选</h4>
                        </div>
                        <div class="modal-body">
                            <form class="form-horizontal" id="datePickerForm">

                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                            <button type="button" class="btn btn-primary" onclick="datePickerSaveFun()">保存</button>
                        </div>
                    </div>
                </div>
            </div>
       </div>
      </div>
    </div>
  </section>
<div style="width: 100%;height:100%;position:fixed;display:flex;justify-content: center;align-items: center;top:0;left:0;z-index: 100000;">
    <span class="bi bi-reception-0" style="font-size:30px" id="loadding"></span>
</div>
<link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.15.3/dist/bootstrap-table.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/i18n/defaults-*.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.15.3/dist/bootstrap-table.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap-table/1.16.0/locale/bootstrap-table-zh-CN.js"></script>
<!-- 时间选择器-->
<link href="https://cdn.bootcdn.net/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script>
    var i = 1;
    var loddingTimer;
    function loadStart(){
        $('#loadding').parent().show();
        clearInterval(loddingTimer);
        loddingTimer = setInterval(function(){
            $('#loadding').attr('class', 'bi bi-reception-' + i);
            ++i;
            if(i >= 5)
            {
                i = 1;
            }
        },200)
    }

    function removeLoad(){
        $('#loadding').parent().hide();
        clearInterval(loddingTimer);
    }
    removeLoad();
    var limit = {$limit};
    var _column = [
        {field: 'custom_name',title: '规则名称', width: '120px'}
        ,{field: 'name',title: '数据类型', width: '120px'}
        ,{field: 'ep_param',title: '导出选项', formatter: epParamFormatter}
        ,{field: 'id',title: '操作', width: '250px', formatter: function(v, r, i){
                return examActionFormatter(v, r, i);
            }}
    ];
    var setting = {
        url: "{:shd_addon_url('ExportExcel://AdminIndex/getExportList')}"
        ,columns: _column
        ,method: 'post'
        ,sortable: true
        ,classes: 'table table-borderless'
        ,theadClasses: 'thead-light'
        ,pagination: true
        ,pageSize: limit
        ,pageList: [10, 25, 50, 100]
        ,sidePagination: 'server'
        ,queryParams: function(params) {
            // console.log(params);
            return {
                limit: params.limit,
                page: (params.offset / params.limit) + 1,
                sort: params.sort,
                sortOrder: params.order
            };
        }
        ,onLoadSuccess: function(data){

        }
    }
    $('#export').bootstrapTable(setting);

    function addExport()
    {
        var _html = '<div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">规则名称</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <input type="text" class="form-control" name="cname">' +
            '                        </div>\n' +
            '                    </div>'+
            ' <div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">数据类型</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <select class="form-control" name="exportName">\n'+
            '                            </select>\n' +
            '                        </div>\n' +
            '                    </div>\n' +
            '                    <div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">导出选项</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <select id="usertype" name="exportParam[]" title="请选择" class="selectpicker show-tick form-control" multiple data-live-search="false" ></select>' +
            '                        </div>\n' +
            '                    </div>';
        $('#auditForm').html(_html);
        $('#myModalLabel').html('新增导出项');
        $('#myModal').modal('show');
        $('#usertype').selectpicker({
            'selectedText': 'cat'
        });
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/getExportName')}"
            ,data: {}
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){

                var option = '<option value="0">请选择导出列表</option>';
                for(var i in e.data)
                {
                    option += '<option value="'+ i +'">'+ e.data[i] +'</option>';

                }
                $('select[name="exportName"]').html(option);
            }
        })
    }

    $(document).on('change', 'select[name="exportName"]', function(){
        loadStart()
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/getExportParam')}"
            ,data: {id:$(this).val()}
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){
                removeLoad();
                var option = '';
                for(var i in e.data)
                {
                    option += '<option value="'+ i +'">'+ e.data[i] +'</option>';

                }
                $('select[name="exportParam[]"]').html(option);
                // 缺一不可
                $('.selectpicker').selectpicker('refresh');
                $('.selectpicker').selectpicker('render');
            }
        })
    })

    function modalSaveFun()
    {
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/addExport')}"
            ,data: $('#auditForm').serialize()
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){
                if(e.status != 200)
                {
                    toastr.error(e.msg);
                    return false;
                }
                $('#myModal').modal('hide');
                $('#export').bootstrapTable('refresh');
            }
        })
    }

    $(document).on('click', '.close-btn', function(){
        var _this = this
            ,id = $(this).data('id')
            ,param = $(this).data('param');
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/delExportParam')}"
            ,data: {id: id, param: param}
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){
                if(e.status != 200)
                {
                    toastr.error(e.msg);
                    return false;
                }
                $(_this).parents('li').remove();
            }
        })
    })

    function epParamFormatter(v, r, i)
    {
        var string = '<ul class="param-box param-index'+ r.id +'">\n';

        for(var i in v)
        {
            string += '<li class="param-box-child">\n' +
                '                <h4><span class="label label-default param-container">'+ v[i] +'<span class="badge close-btn" data-id="'+ r.id +'" data-param="'+ i +'">×</span></span></h4>\n' +
                '            </li>';
        }

        string += '</ul>'
        return string;
    }

    function examActionFormatter(v, r, i)
    {
        var string = '<button type="button" class="btn btn-default btn-lg" onclick="exportBtn('+ r.id +')">\n' +
            '  <span class="bi bi-box-arrow-in-up" aria-hidden="true"></span> 导出\n' +
            '</button>';

        string += '<button type="button" class="btn btn-default btn-lg" onclick="editBtn('+ v +', '+ r.id +', '+ i +')">\n' +
            '  <span class="bi  bi-pencil" aria-hidden="true"></span> 编辑\n' +
            '</button>';

        string += '<button type="button" class="btn btn-default btn-lg" style="color: #d43f3a" onclick="delBtn('+ r.id +')">\n' +
            '  <span class="bi bi-trash" aria-hidden="true"></span> 删除\n' +
            '</button>';

        return string;
    }

    function editBtn(v, r, i)
    {
        var _html = '<div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">规则名称</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <input type="hidden" class="form-control" name="id" value="'+ r +'">' +
            '                            <input type="text" class="form-control" name="cname">' +
            '                        </div>\n' +
            '                    </div>'+
            ' <div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">数据类型</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <select class="form-control" name="exportName">\n'+
            '                            </select>\n' +
            '                        </div>\n' +
            '                    </div>\n' +
            '                    <div class="form-group">\n' +
            '                        <label for="remark" class="col-sm-2 control-label">导出选项</label>\n' +
            '                        <div class="col-sm-10">\n' +
            '                            <select id="usertype" name="exportParam[]" title="请选择" class="selectpicker show-tick form-control" multiple data-live-search="false" ></select>' +
            '                        </div>\n' +
            '                    </div>';
        $('#auditForm').html(_html);
        $('#myModalLabel').html('编辑导出项');
        $('#myModal').modal('show');
        $('#usertype').selectpicker({
            'selectedText': 'cat'
        });
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/getEditExport')}"
            ,data: {id: r}
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){

                var option = '<option value="0">请选择导出列表</option>';
                for(var i in e.data.cname)
                {
                    if(i == e.data.model.name)
                    {
                        option += '<option value="'+ i +'" selected>'+ e.data.cname[i] +'</option>';
                        continue;
                    }
                    option += '<option value="'+ i +'">'+ e.data.cname[i] +'</option>';
                }
                $('input[name="cname"]').val(e.data.model.custom_name);
                $('select[name="exportName"]').html(option);
                option = '';
                for(var i in e.data.exportParam)
                {
                    option += '<option value="'+ i +'">'+ e.data.exportParam[i] +'</option>';
                }
                $('select[name="exportParam[]"]').html(option);
                $('select[name="exportParam[]"]').val(e.data.model.ep_param).trigger('change');
                // 缺一不可
                $('.selectpicker').selectpicker('refresh');
                $('.selectpicker').selectpicker('render');

            }
        })
        // $('.param-index' + r + ' .close-btn').toggleClass('active');
    }

    function exportBtn(id)
    {
        // datePickerForm
        var html = '<div class="input-group input-daterange">\n' +
            '    <input type="text" class="form-control" value="" placeholder="开始时间" name="startAt">\n' +
            '    <div class="input-group-addon" style="display: flex;justify-content: center;align-items: center;">to</div>\n' +
            '    <input type="text" class="form-control" value="" placeholder="结束时间" name="endAt">\n' +
            '</div>';
        html += '<input type="hidden" name="id" value="'+ id +'">';
        $('#datePickerForm').html(html);
        $('#datePicker').modal('show');
        $('.input-daterange input').each(function() {
            // $(this).datepicker('clearDates');
            $(this).datepicker({keyboardNavigation:!1,forceParse:!1,autoclose:!0,language: 'zh-CN'});
        });
        return false;
    }

    function datePickerSaveFun()
    {
        var _val = $('#datePickerForm').serialize();
        window.open("{:shd_addon_url('ExportExcel://AdminIndex/exportExcel')}" + "&" + _val);
    }

    function delBtn(id)
    {
        $.ajax({
            url: "{:shd_addon_url('ExportExcel://AdminIndex/delExport')}"
            ,data: {id:id}
            ,type: 'post'
            ,dataType: 'json'
            ,success: function(e){
                if(e.status != 200)
                {
                    toastr.error(e.msg);
                    return false;
                }
                $('#export').bootstrapTable('refresh');
            }
        })
    }
</script>