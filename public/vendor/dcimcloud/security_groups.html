<style type="text/css">
  .invalid-feedback {
    display: block;
  }
</style>
<div class="securityGroup_btn">
  <button type="button" class="btn btn-primary waves-effect waves-light" data-toggle="modal"
    data-target="#myModal">新建安全组</button>
</div>
<div class="container">
  <!-- 新建安全组模态框 -->
  <div class="modal fade" id="myModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <span class="modal-title">新建安全组</span>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- 模态框主体 -->
        <div class="modal-body">
          <div class="modal_limit">
            <div class="modal_main">
              <form>
                <input style="width:48%" name="name" type="text" placeholder="安全组名称" class="form-control" id="name">
                <input style="width:48%" name="description" type="text" placeholder="备注" class="form-control"
                  id="remark">
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary waves-effect waves-light confirm-btn createSecurityGroup"
                data-dismiss="modal" style="margin-left:10px">确定</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 新增策略模态框 -->
  <div class="modal fade" id="securityGroupAddModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <span class="modal-title">新增策略</span>
          <button type="button" class="close" data-dismiss="modal" onclick="closeAddModal()">&times;</button>
        </div>

        <!-- 模态框主体 -->
        <div class="modal-body">
          <div class="modal_limit">
            <div class="modal_main">
              <form>
                <input type="hidden" name="id">
                {if $host_type != 'host'}
                <div class="form-group">
                  <label for="action">授权策略</label>
                  <div class="selectItem">
                    <div class="action">
                      <div class="filter-text">
                        <input class="filter-title" type="text" readonly placeholder="请选择" />
                        <i class="icon icon-filter-arrow"></i>
                      </div>
                      <select name="action">
                        <option value="accept">允许</option>
                        <option value="drop">拒绝</option>
                      </select>
                    </div>
                  </div>
                </div>
                {/if}
                <div class="form-group">
                  <label for="direction">规则方向</label>
                  <div class="selectItem">
                    <div class="direction">
                      <div class="filter-text">
                        <input class="filter-title" type="text" readonly placeholder="请选择" />
                        <i class="icon icon-filter-arrow"></i>
                      </div>
                      <select name="direction">
                        <option value="out">出</option>
                        <option value="in">入</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="protocol">协议</label>
                  <div class="selectItem">
                    <div class="protocol">
                      <div class="filter-text">
                        <input class="filter-title" type="text" readonly placeholder="请选择" />
                        <i class="icon icon-filter-arrow"></i>
                      </div>
                      <select name="protocol">
                        {foreach $protocols as $key=>$vo}
                        <option value="{$vo.value}" data-port="{$vo.port}">{$vo.name}</option>
                        {/foreach}
                      </select>
                    </div>
                  </div>
                </div>
                {if $host_type == 'host'}
                <div class="form-group">
                  <label for="portRange">
                    <span style="color:#f00">*</span>
                    端口范围</label>
                  <input required name="port" type="input" class="form-control" id="portRange"
                    placeholder="例如:22或者22-12345">
                  <div class="invalid-feedback" id="port-feedback"></div>
                </div>
                {else /}
                <div class="form-group">
                  <label for="startPort">
                    <span style="color:#f00">*</span>
                    端口范围</label>
                  <div class="col-12 row">
                    <input required name="start_port" type="input" class="form-control col-5" id="startPort"
                      placeholder="起始端口">
                    <span class="col-1"></span>
                    <input required name="end_port" type="input" class="form-control col-5" id="endPort"
                      placeholder="结束端口">
                  </div>
                  <div class="invalid-feedback" id="port-feedback"></div>
                </div>
                {/if}
                {if $host_type == 'lightHost'}
                <div class="form-group">
                  <label for="IP">
                    <span style="color:#f00">*</span>
                    授权IP
                  </label>
                  <div class="col-12 row">
                    <input required name="start_ip" type="input" class="form-control col-5" id="startIP"
                      placeholder="起始IP">
                    <span class="col-1"></span>
                    <input required name="end_ip" type="input" class="form-control col-5" id="endIP" placeholder="结束IP">
                  </div>
                  <div class="invalid-feedback" id="IP-feedback"></div>
                </div>
                <div class="form-group">
                  <label for="priority">
                    <span style="color:#f00">*</span>
                    优先级
                  </label>
                  <input required name="priority" type="input" class="form-control" id="priority"
                    placeholder="优先级1-1000（值越小越优先）">
                  <div class="invalid-feedback" id="priority-feedback"></div>
                </div>
                {else/}
                <div class="form-group">
                  <label for="IP">
                    <span style="color:#f00">*</span>
                    授权IP
                  </label>
                  <input required name="ip" type="input" class="form-control" id="IP" placeholder="例如:10.x.y.z/32">
                  <div class="invalid-feedback" id="IP-feedback"></div>
                </div>
                {/if}
                <div class="form-group">
                  <label for="description">描述</label>
                  <textarea name="description" class="form-control" rows="5" id="description"></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary waves-effect waves-light confirm-btn createSecurityRule"
                style="margin-left:10px">确定</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table mb-0 mt-3">
    <thead class="thead-light">
      <tr>
        <th>名称</th>
        <th>描述</th>
        <th style="width:285px">操作</th>
      </tr>
    </thead>
    <tbody>
      {foreach $list as $key=>$vo }
      <tr>
        <td>{$vo.id == $used ? '(当前) ' : ''}{$vo.name}</td>
        <td>{$vo.description}</td>
        <td style="width:300px">
          {if $vo.id != $used}
          <button type="button" class="btn btn-link apply" data-id="{$vo.id}">应用</button>
          {/if}
          <button type="button" class="btn btn-link trView" data-id="{$vo.id}">查看</button>
          <button type="button" class="btn btn-link" data-toggle="modal" data-target="#securityGroupAddModal"
            data-id="{$vo.id}" onclick="addId($(this))">新增策略</button>
          {if $vo.id != $used}
          <button type="button" class="btn btn-link deleteGroup redtxt" data-id="{$vo.id}">删除</button>
          {/if}
        </td>
      </tr>
      {/foreach}
    </tbody>
  </table>
</div>
<div style="display: none" id="loading-circle">
  <div class="loading_limit">
    <div class="loading_inner">
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
    </div>
  </div>
</div>
<link rel="stylesheet" href="{$Request.domain}{$Request.rootUrl}/vendor/dcimcloud/css/loading.css">
<script type="text/javascript" src="{$Request.domain}{$Request.rootUrl}/vendor/dcimcloud/js/selectFilter.js"></script>
<script>
  var host_type = '{$host_type}';

  // 初始化下拉选择
  $('.direction').selectFilter({
    callBack: function (val) {
      // console.log(val + '是返回的值')
    }
  });
  $('.protocol').selectFilter({
    callBack: function (val) {
      let port = $("select[name='protocol']").find('option:selected').data('port')
      if (port) {
        if (port == '1-65535') {
          $('#portRange').val('1-65535')
          $('#startPort').val('1')
          $('#endPort').val('65535')
        } else {
          $('#portRange').val(port)
          $('#startPort').val(port)
          $('#endPort').val(port)
        }
        $('#portRange').attr("disabled", true);
        $('#startPort').attr("disabled", true);
        $('#endPort').attr("disabled", true);
      } else {
        $('#portRange').val('')
        $('#startPort').val('')
        $('#endPort').val('')
        $('#portRange').removeAttr("disabled");
        $('#startPort').removeAttr("disabled");
        $('#endPort').removeAttr("disabled");
      }
      // console.log(val + '是返回的值')
    }
  });
  // $('.strategy').selectFilter({
  //   callBack: function (val) {
  //     console.log(val + '是返回的值')
  //   }
  // });
  // $('.authorizationType').selectFilter({
  //   callBack: function (val) {
  //     console.log(val + '是返回的值')
  //   }
  // });

  // 安全组行内 查看按钮
  /*$('.trView').on('click', function () {
    $(this).parent().parent().next('.expandLine').toggle();
  });*/

  // 安全组行内 应用按钮
  $('.apply').on('click', function () {
    if ($(this).data('disabled') == 'true') {
      return;
    }
    apply_btn = $(this)
    var id = $(this).data("id")
    Swal.fire({
      position: 'top',
      title: '确定应用此安全组吗？',
      type: 'question',
      showCancelButton: true,
      confirmButtonColor: '#6e9aff',
      cancelButtonColor: '#d33',
      confirmButtonText: '确认应用',
      cancelButtonText: '取消'
    }).then((result) => {
      if (result.value) {
        apply_btn.html($('#loading-circle').html());
        apply_btn.data('disabled', 'true')
        ajax({
          type: "post",
          url: "{$MODULE_CUSTOM_API}",
          data: { "func": "linkSecurityGroup", "id": id },
          success: function (data) {
            if (data.status == 200) {
              Swal.fire({
                position: 'top',
                title: '应用成功',
                type: 'success',
                confirmButtonColor: '#6e9aff',
              }).then((isConfirm) => window.location.reload());
            } else {
              apply_btn.html('应用')
              apply_btn.data('disabled', 'false')
              Swal.fire("应用失败", data.msg, "error");
            }

          }
        })
      }
    })
  });

  // 安全组行内 删除按钮
  $('.deleteGroup').on('click', function () {
    if ($(this).data('disabled') == 'true') {
      return;
    }
    delete_group_btn = $(this)
    var id = $(this).data("id")
    Swal.fire({
      position: 'top',
      title: '确定删除此安全组吗？',
      type: 'question',
      showCancelButton: true,
      confirmButtonColor: '#6e9aff',
      cancelButtonColor: '#d33',
      confirmButtonText: '确认删除',
      cancelButtonText: '取消'
    }).then((result) => {
      if (result.value) {
        delete_group_btn.html($('#loading-circle').html());
        delete_group_btn.data('disabled', 'true')
        ajax({
          type: "post",
          url: "{$MODULE_CUSTOM_API}",
          data: { "func": "delSecurityGroup", "id": id },
          success: function (data) {
            if (data.status == 200) {
              Swal.fire({
                position: 'top',
                title: '删除成功',
                type: 'success',
                confirmButtonColor: '#6e9aff',
              }).then((isConfirm) => window.location.reload());
            } else {
              delete_group_btn.html('删除');
              delete_group_btn.data('disabled', 'false')
              Swal.fire("删除失败", data.msg, "error");
            }
          }
        })
      }
    })
  });

  // 策略删除
  $(document).on('click', '.deleteStrategy', function () {
    if ($(this).data('disabled') == 'true') {
      return;
    }
    delete_rule_btn = $(this)
    var id = $(this).data("id")
    var group = $(this).data('group')
    Swal.fire({
      position: 'top',
      title: '确定删除此策略吗？',
      type: 'question',
      showCancelButton: true,
      confirmButtonColor: '#6e9aff',
      cancelButtonColor: '#d33',
      confirmButtonText: '确认删除',
      cancelButtonText: '取消'
    }).then((result) => {
      if (result.value) {
        delete_rule_btn.html($('#loading-circle').html());
        delete_rule_btn.data('disabled', 'true');
        ajax({
          type: "post",
          url: "{$MODULE_CUSTOM_API}",
          data: { "func": "delSecurityRule", "id": id, "group": group },
          success: function (data) {
            if (data.status == 200) {
              Swal.fire({
                position: 'top',
                title: '删除成功',
                type: 'success',
                confirmButtonColor: '#6e9aff',
              }).then((isConfirm) => window.location.reload());
            } else {
              delete_rule_btn.html('删除')
              delete_rule_btn.data('disabled', 'false')
              Swal.fire("删除失败", data.msg, "error");
            }

          }
        })
      }
    })
  });

  // 新增策略表单验证
  function checkForm() {

  }

  // 关闭模态框
  function closeAddModal() {
    const IP = document.getElementById("IP"); // 输入框的值
    const startIP = document.getElementById("startIP"); // 输入框的值
    const endIP = document.getElementById("endIP"); // 输入框的值
    const startPort = document.getElementById("startPort"); // 输入框的值
    const endPort = document.getElementById("endPort"); // 输入框的值
    const priority = document.getElementById("priority"); // 输入框的值
    const description = document.getElementById("description");

    removeValid(IP);
    removeValid(startIP);
    removeValid(endIP);
    removeValid(startPort);
    removeValid(endPort);
    removeValid(priority);

    if (IP) IP.value = ''
    if (startIP) startIP.value = ''
    if (endIP) endIP.value = ''
    if (startPort) startPort.value = ''
    if (endPort) endPort.value = ''
    if (priority) priority.value = ''
    description.value = ''

    $('.invalid-feedback').html('')
  }

  function addId(_this) {
    $("#securityGroupAddModal").find("input[name='id']").val(_this.data("id"))
  }

  // 点击查看控制显隐
  $(document).on('click', '.trView', function () {
    var _this = $(this)
    var id = _this.data("id");
    if (_this.data("checked")) return false;
    if ($("#view_" + id).length > 0) {
      $("#view_" + id).remove()
    } else {
      _this.data("checked", "checked")
      ajax({
        type: "post",
        url: "{$MODULE_CUSTOM_API}",
        data: { "func": "showSecurityRules", "id": id },
        success: function (data) {
          if (host_type == 'hyperv') {
            var _html = '<tr class="expandLine" id="view_' + id + '"><td colspan="3"><table class="table mb-0 mt-3"><thead class="thead-light"><tr><th>规则描述</th><th>授权策略</th><th>规则方向</th><th>协议</th><th>端口范围</th><th>授权IP</th><th style="width:110px">操作</th></tr>'
            $.each(data.list, function (i, v) {
              _html += '<tr style="text-indent: 18px;"><td>' + v.description + '</td><td>' + (v.action == 'accept' ? '允许' : '拒绝') + '</td><td>' + (v.direction == 'in' ? '入方向' : '出方向') + '</td><td>' + v.protocol + '</td><td>' + (v.start_port == v.end_port ? v.start_port : v.start_port + '-' + v.end_port) + '</td><td>' + v.ip + '</td><td><button type="button" data-id="' + v.id + '" data-group="' + id + '" class="btn btn-link deleteStrategy redtxt">删除</button></td></tr>'
            })
            _html += '</thead></table></td></tr>'
          } else if (host_type == 'lightHost') {
            var _html = '<tr class="expandLine" id="view_' + id + '"><td colspan="3"><table class="table mb-0 mt-3"><thead class="thead-light"><tr><th>规则描述</th><th>授权策略</th><th>规则方向</th><th>协议</th><th>端口范围</th><th>授权IP</th><th>优先级</th><th style="width:110px">操作</th></tr>'
            $.each(data.list, function (i, v) {
              _html += '<tr style="text-indent: 18px;"><td>' + v.description + '</td><td>' + (v.action == 'accept' ? '允许' : '拒绝') + '</td><td>' + (v.direction == 'in' ? '入方向' : '出方向') + '</td><td>' + v.protocol + '</td><td>' + (v.start_port == v.end_port ? v.start_port : v.start_port + '-' + v.end_port) + '</td><td>' + v.start_ip + '-' + v.end_ip + '</td><td>' + v.priority + '</td><td><button type="button" data-id="' + v.id + '" data-group="' + id + '" class="btn btn-link deleteStrategy redtxt">删除</button></td></tr>'
            })
            _html += '</thead></table></td></tr>'
          } else {
            var _html = '<tr class="expandLine" id="view_' + id + '"><td colspan="3"><table class="table mb-0 mt-3"><thead class="thead-light"><tr><th>规则描述</th><th>规则方向</th><th>协议</th><th>端口范围</th><th>授权IP</th><th style="width:110px">操作</th></tr>'
            $.each(data.list, function (i, v) {
              _html += '<tr style="text-indent: 18px;"><td>' + v.description + '</td><td>' + (v.direction == 'in' ? '入方向' : '出方向') + '</td><td>' + v.protocol + '</td><td>' + v.port + '</td><td>' + v.ip + '</td><td><button type="button" data-id="' + v.id + '" data-group="' + id + '" class="btn btn-link deleteStrategy redtxt">删除</button></td></tr>'
            })
            _html += '</thead></table></td></tr>'
          }
          _this.parents("tr").after(_html)
          _this.parent().parent().next('.expandLine').css({ 'display': 'table-row' })
          _this.removeData("checked")
        }
      })
    }
  })

  $('.createSecurityGroup').on('click', function () {
    if (!$(this).data('submit')) {
      $(this).html($('#loading-circle').html());
      $(this).data('submit', 'submit')
      ajax({
        type: "post",
        url: "{$MODULE_CUSTOM_API}",
        data: $("#myModal").find("form").serialize() + "&func=createSecurityGroup",
        success: function (data) {
          $(".createSecurityGroup").html('确认')
          $(".createSecurityGroup").data('submit', '')
          if (data.status == 200) {
            $("#myModal").modal('hide')
            Swal.fire({
              position: 'top',
              title: '创建成功',
              type: 'success',
              confirmButtonColor: '#6e9aff',
            }).then((isConfirm) => window.location.reload());
          } else {
            Swal.fire("创建失败", data.msg, "error");
          }
        },
        error: function () {
          $(".createSecurityGroup").html('确认')
          $(".createSecurityGroup").data('submit', '')
        }
      })
    }
  });

  $('.createSecurityRule').on('click', function () {
    const IP = document.getElementById("IP"); // 输入框的值
    const startIP = document.getElementById("startIP"); // 输入框的值
    const endIP = document.getElementById("endIP"); // 输入框的值
    const startPort = document.getElementById("startPort"); // 输入框的值
    const endPort = document.getElementById("endPort"); // 输入框的值
    const priority = document.getElementById("priority"); // 输入框的值
    const IPfeedback = document.getElementById("IP-feedback"); // 输入框的验证提示
    const portRange = document.getElementById("portRange"); // 输入框的值
    const portFeedback = document.getElementById("port-feedback"); // 输入框的验证提示
    const priorityFeedback = document.getElementById("priority-feedback"); // 输入框的验证提示

    //不能为空
    function testRange(num) {
      if (/^[0-9]\d*$/.test(num) && parseInt(num) <= 65535 && parseInt(num) > 0) {
        return true
      } else {
        return false
      }
    }
    let result = false
    if (portRange) {
      let arr = portRange.value.split(',')
      let isError = 0
      arr.forEach(e => {
        const val = e.split('-')
        if (val.length == 1 && testRange(val[0])) {
          // callback()
          isError++
        } else if (val.length == 2 && val[1] >= val[0] && testRange(val[1]) && testRange(val[0])) {
          // callback()
          isError++
        } else {
          result = false
        }
      })
      if (isError == arr.length) {
        result = true
      }
      if (portRange.value === "" || !result) {
        portFeedback.innerHTML = "请填写正确的端口范围";
        portRange.classList.remove("is-valid"); //清除合法状态
        portRange.classList.add("is-invalid"); //添加非法状态
        return true
      } else {
        portRange.classList.remove("is-invalid");
        portRange.classList.add("is-valid");
        portFeedback.innerHTML = "";
      }
    }
    if (startPort && endPort) {
      let start_port_error = false
      let end_port_error = false
      if (startPort.value < 1 || startPort.value > 65535) {
        start_port_error = true
      }
      if (endPort.value < 1 || endPort.value > 65535) {
        end_port_error = true
      }
      if (startPort.value > endPort.value) {
        start_port_error = true
        end_port_error = true
      }
      if (start_port_error || end_port_error) {
        portFeedback.innerHTML = "请填写正确的端口范围";
        if (start_port_error) {
          addError(startPort)
        } else {
          removeError(startPort)
        }
        if (end_port_error) {
          addError(endPort)
        } else {
          removeError(endPort)
        }
        return true
      } else {
        removeError(startPort)
        removeError(endPort)
        portFeedback.innerHTML = "";
      }
    }
    //ip
    let ipTest = false
    if (IP) {
      const val = IP.value.split('/')
      if (/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/.test(val[0]) && val.length == 1) {
        ipTest = true
      } else if (/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/.test(val[0]) && val.length == 2 && parseInt(val[1]) <= 65535 && parseInt(val[1]) >= 0) {
        ipTest = true
      } else {
        ipTest = false
      }
      if (IP.value === "" && !ipTest) {
        IPfeedback.innerHTML = "ip不符合格式要求，请参考格式输入";
        IP.classList.remove("is-valid"); //清除合法状态
        IP.classList.add("is-invalid"); //添加非法状态
        return true
      } else {
        //清除错误提示，改成成功提示
        IP.classList.remove("is-invalid");
        IP.classList.add("is-valid");
        IPfeedback.innerHTML = "";
      }
    }
    if (startIP && endIP) {
      let start_ip_error = false
      let end_ip_error = false
      if (!/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/.test(startIP.value)) {
        start_ip_error = true
      }
      if (!/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/.test(endIP.value)) {
        end_ip_error = true
      }
      if (!start_ip_error && !end_ip_error) {
        if (startIP.value.replace('.', '') > endIP.value.replace('.', '')) {
          start_ip_error = true
          end_ip_error = true
        }
      }
      if (start_ip_error || end_ip_error) {
        IPfeedback.innerHTML = "ip不符合格式要求，请参考格式输入";
        if (start_ip_error) {
          addError(startIP)
        } else {
          removeError(startIP)
        }
        if (end_ip_error) {
          addError(endIP)
        } else {
          removeError(endIP)
        }
        return true
      } else {
        removeError(startIP)
        removeError(endIP)
        IPfeedback.innerHTML = "";
      }
    }
    if (priority) {
      if (priority.value < 1 || priority.value > 1000) {
        priorityFeedback.innerHTML = "优先级不符合格式要求，请参考格式输入";
        addError(priority)
        return true;
      } else {
        priorityFeedback.innerHTML = "";
        removeError(priority)
      }
    }

    if (!$(this).data('submit')) {
      $(this).data('submit', 'submit')
      ajax({
        type: "post",
        url: "{$MODULE_CUSTOM_API}",
        data: $("#securityGroupAddModal").find("form").serialize() + "&func=createSecurityRule",
        success: function (data) {
          $(".createSecurityRule").data('submit', '')
          if (data.status == 200) {
            $("#securityGroupAddModal").modal('hide')
            Swal.fire({
              position: 'top',
              title: '创建成功',
              type: 'success',
              confirmButtonColor: '#6e9aff',
            }).then((isConfirm) => window.location.reload());
          } else {
            Swal.fire("创建失败", data.msg, "error");
          }
        },
        error: function () {
          $(".createSecurityRule").data('submit', '')
        }
      })
    }
  });

  function addError(obj) {
    if (!obj) return;
    obj.classList.remove("is-valid"); //清除合法状态
    obj.classList.add("is-invalid"); //添加非法状态
  }

  function removeError(obj) {
    if (!obj) return;
    obj.classList.remove("is-invalid");
    obj.classList.add("is-valid");
  }

  function removeValid(obj) {
    if (!obj) return;
    obj.classList.remove("is-invalid");
    obj.classList.remove("is-valid");
  }

  function ajax(options) {
    //创建一个ajax对象
    var xhr = new XMLHttpRequest() || new ActiveXObject("Microsoft,XMLHTTP");
    //数据的处理 {a:1,b:2} a=1&b=2;
    if (typeof (options.data) != 'string') {
      var str = "";
      for (var key in options.data) {
        str += "&" + key + "=" + options.data[key];
      }
      str = str.slice(1)
    } else {
      var str = options.data;
    }

    options.dataType = options.dataType || 'json';
    if (options.type == "get") {
      var url = options.url + "?" + str;
      xhr.open("get", url);
      xhr.setRequestHeader("Authorization", "JWT {$Think.get.jwt}");
      xhr.send();
    } else if (options.type == "post") {
      xhr.open("post", options.url);
      xhr.setRequestHeader("content-type", "application/x-www-form-urlencoded");
      xhr.setRequestHeader("Authorization", "JWT {$Think.get.jwt}");
      xhr.send(str)
    }
    //监听
    xhr.onreadystatechange = function () {
      //当请求成功的时候
      if (xhr.readyState == 4 && xhr.status == 200) {
        var d = xhr.responseText;
        d = JSON.parse(d);
        //将请求的数据传递给成功回调函数
        options.success && options.success(d, xhr.responseXML)
      } else if (xhr.status != 200) {
        //当失败的时候将服务器的状态传递给失败的回调函数
        options.error && options.error(xhr.status);
      }
    }
  }
</script>