

var url = window.location.protocol + '//' + window.location.host;


function ajax (options) {
  options = options || {};  //调用函数时如果options没有指定，就给它赋值{},一个空的Object
  options.type = (options.type || "GET").toUpperCase();/// 请求格式GET、POST，默认为GET
  options.dataType = options.dataType || "json";    //响应数据格式，默认json

  var params = formatParams(options.data);//options.data请求的数据

  var xhr;

  //考虑兼容性
  if (window.XMLHttpRequest) {
    xhr = new XMLHttpRequest();
  } else if (window.ActiveObject) {//兼容IE6以下版本
    xhr = new ActiveXobject('Microsoft.XMLHTTP');
  }

  //启动并发送一个请求
  if (options.type == "GET") {
    xhr.open("GET", options.url + "?" + params, true);
    xhr.send(null);
  } else if (options.type == "POST") {
    xhr.open("post", options.url, true);

    //设置表单提交时的内容类型
    //Content-type数据请求的格式
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send(params);
  }

  //    设置有效时间
  setTimeout(function () {
    if (xhr.readySate != 4) {
      xhr.abort();
    }
  }, options.timeout)

  //    接收
  //     options.success成功之后的回调函数  options.error失败后的回调函数
  //xhr.responseText,xhr.responseXML  获得字符串形式的响应数据或者XML形式的响应数据
  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4) {
      var status = xhr.status;
      if (status >= 200 && status < 300 || status == 304) {
        options.success && options.success(xhr.responseText, xhr.responseXML);
      } else {
        options.error && options.error(status);
      }
    }
  }
}

//格式化请求参数
function formatParams (data) {
  var arr = [];
  for (var name in data) {
    arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
  }
  arr.push(("v=" + Math.random()).replace(".", ""));
  return arr.join("&");

}

//版本号
ajax({
  url: url + "/api/install/version",
  type: 'get',
  dataType: 'json',
  timeout: 10000,
  contentType: "application/json",
  success: function (res) {
    var data = JSON.parse(res)

    if (data.status == 200) {
      var edition = document.getElementsByClassName('edition')
      for (var i = 0; i < edition.length; i++) {
        edition[i].innerHTML = data.data.version
      }
    } else {
      window.history.go(-1);
      alert(data.msg);
    }
    //服务器返回响应，根据响应结果，分析是否登录成功
  },
  //异常处理
  error: function (e) {
    console.log(e);
  }
})


//检测
var envs = document.getElementById('envs')
var modules = document.getElementById('modules')
// var modules = document.getElementById('modules')
// var modules = document.getElementById('modules')
var folders = document.getElementById('folders')
function testing (params) {
  envs.innerHTML = '<li class="table_title"><span>环境检测</span><span>系统要求</span><span>当前状态</span></li>'
  // envs.innerHTML += '<li class="jc_loading"><span>所有检测项</span><span class="ztri"><img style="width:14px" src="./static/images/5-121204193R5-50.gif" alt=""></span></li>'
  modules.innerHTML = ' <li class="table_title"><span>模块检测</span></li><li class="row"><span style="width:35%;">项目</span><span>建议</span><span>当前</span><span>状态</span></li>'
  // modules.innerHTML += '<li class="jc_loading"><span>所有检测项</span><span class="ztri"><img style="width:14px" src="./static/images/5-121204193R5-50.gif" alt=""></span></li>'
  folders.innerHTML = '<li class="table_title"><span style="width:60%;">目录、文件权限检查</span><span>写入</span><span>读取</span></li>'
  // folders.innerHTML += '<li class="jc_loading"><span>所有检测项</span><span class="ztri" style="margin-left:184px"><img src="./static/images/5-121204193R5-50.gif" alt=""></span><span class="ztri"><img style="width:14px" src="./static/images/5-121204193R5-50.gif" alt=""></span></li>'
  $('.jc-button').css({ background: '#f1f1f1', color: '#999', borderColor: '#ddd', cursor: 'not-allowed' }).attr({ "disabled": "disabled" });

  ajax({
    url: url + "/api/install/envmonitor",
    type: 'get',
    dataType: 'json',
    timeout: 10000,
    contentType: "application/json",
    success: function (res) {
      var data = JSON.parse(res)
      if (data.status == 200) {
        $('.jc_loading').remove();
        $('.jc-button').removeAttr('style').removeAttr('disabled').css({ background: '#3699FF', color: 'white' });
        if (data.data.envs.length <= 0) {
          envs.innerHTML += '<li><span>所有检测项</span><span class="ztri"><img src="./static/images/dui.png" alt=""></span></li>'
        } else {
          data.data.envs.forEach(function (item, index) {
            envs.innerHTML += '<li class="row"><span>' + item.name + '</span><span>' + item.suggest + '</span><span><img src="' + (item.status == 1 ? './static/images/dui.png' : './static/images/cuo.png') + '" alt=""></span></li>'
          });
          // $('.nextStep').css({ background: '#f1f1f1', color: '#999', borderColor: '#ddd', cursor: 'not-allowed' }).attr({ "disabled": "disabled", "title": "环境错误" });
        }
        if (data.data.modules.length <= 0) {
          modules.innerHTML += '<li><span>所有检测项</span><span class="ztri"><img src="./static/images/dui.png" alt=""></span></li>'
        } else {
          data.data.modules.forEach(function (item, index) {
            modules.innerHTML += '<li class="row"><span style="width:35%;">' + item.name + '</span><span>' + item.suggest + '</span><span>' + item.current + '</span><span><img src="' + (item.status == 1 ? './static/images/dui.png' : './static/images/cuo.png') + '" alt=""></span></li>'
          });
          // $('.nextStep').css({ background: '#f1f1f1', color: '#999', borderColor: '#ddd', cursor: 'not-allowed' }).attr({ "disabled": "disabled", "title": "环境错误" });
        }
        if (data.data.folders.length <= 0) {
          folders.innerHTML += '<li><span>所有检测项</span><span class="ztri" style="margin-left:184px"><img src="./static/images/dui.png" alt=""></span><span class="ztri"><img src="./static/images/dui.png" alt=""></span></li>'
        } else {
          data.data.folders.forEach(function (item, index) {
            folders.innerHTML += '<li><span style="width:60%;">' + item.name + '</span><span><img src="' + (item.write == 1 ? './static/images/dui.png' : './static/images/cuo.png') + '" alt=""></span><span><img src="' + (item.read == 1 ? './static/images/dui.png' : './static/images/cuo.png') + '" alt=""></span></li>'
          });
          // $('.nextStep').css({ background: '#f1f1f1', color: '#999', borderColor: '#ddd', cursor: 'not-allowed' }).attr({ "disabled": "disabled", "title": "环境错误" });
        }
        if (data.data.error > 0) {
          $('.nextStep').css({ background: '#f1f1f1', color: '#999', borderColor: '#ddd', cursor: 'not-allowed' }).attr({ "disabled": "disabled", "title": "环境错误" });
        }
      } else {
        // alert('请求失败')
      }
      //服务器返回响应，根据响应结果，分析是否登录成功
    },
    //异常处理
    error: function (e) {
      console.log(e);
    }
  })
}


