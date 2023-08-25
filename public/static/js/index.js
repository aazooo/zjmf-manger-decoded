
var showBox = document.getElementsByClassName('show_box')   //步骤显示框
var nextSteps = document.getElementsByClassName('nextStep') //下一步
var lastStep = document.getElementsByClassName('lastStep')  //上一步
var databaseFiv = document.getElementsByClassName('databaseFiv') //数据库输入框
var inputList = document.getElementsByClassName('inputList')    //网站配置输入框
var daFail = document.getElementById('daFail')        //数据库 文字输入框错误提示
var rqstbc = document.getElementById('rqstbc')        //数据库 输入框错误提示显示隐藏
var tipsInfo = document.getElementById('tipsInfo')      //网站配置 文字输入框错误提示
var tipsInfoText = document.getElementById('tipsInfoText')    //网站配置 文字输入框错误提示
// var houzuiemail = document.getElementById('houzuiemail')    //电子邮件下拉选项
// var stepList = document.getElementById('stepList')          //安装步骤  读条
var stepList = $("#stepList")
var progress = document.getElementById('progress')          //进度条显示
var loginInput = document.getElementsByClassName('loginInput')  //登录部分的input
let sqlIndex = 0;
let totalIndex = 0;


stepList.append('<li><img src="./static/images/dui.png" alt=""><span>1.进行安装!</span></li>')
stepList.append('<li class="azgif"><img src="./static/images/5-121204193R5-50.gif" alt=""><span>正在写入数据库配置！</span></li>')
function randomWord (randomFlag, min, max) {
  arrNum = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  let str = "",
    range = min,
    arr = [
      'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
      'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
      'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

  if (randomFlag) {
    range = Math.round(Math.random() * (max - min)) + min;// 任意长度
  }
  for (let i = 0; i < range; i++) {
    if (i == 0) {
      pos = Math.round(Math.random() * (arr.length - 10));
      str += arr[pos];
    } else if (i == 1) {
      pos = Math.round(Math.random() * (arrNum.length - 1));
      str += arrNum[pos];
    } else {
      pos = Math.round(Math.random() * (arr.length - 1));
      str += arr[pos];
    }
  }
  return str;
}
$('#htlj').val(randomWord(false, 8))
$('#glyzh').val(randomWord(false, 8))
$('#pwd-rand').val(randomWord(false, 12))
$('.get-wzym').val(location.origin)
$('.rand-pwd-btn').click(function () {
  $('#pwd-rand').val(randomWord(false, 12))
})
// 下一步
function xiayibu (index) {
  for (var j = 0; j < showBox.length; j++) {
    showBox[j].style.display = 'none'
  }
  showBox[index + 1].style.display = 'block'
}
for (var i = 0; i < nextSteps.length; i++) {
  nextSteps[i].index = i;
  nextSteps[i].onclick = function (e) {
    var index = this.index
    switch (index) {
      case 0:
        xiayibu(index)
        testing()
        break;
      case 1:
        xiayibu(index)
        break;
      case 2:
        // 数据库输入框验证
        for (var l = databaseFiv.length - 1; l >= 0; l--) {
          if (databaseFiv[l].value == '') {
            switch (l) {
              case 4:
                rqstbc.style.display = 'block'
                daFail.innerHTML = '请输入数据库名'
                break;
              case 3:
                rqstbc.style.display = 'block'
                daFail.innerHTML = '请输入数据库密码'
                break;
              case 2:
                rqstbc.style.display = 'block'
                daFail.innerHTML = '请输入数据库用户名'
                break;
              case 1:
                rqstbc.style.display = 'block'
                daFail.innerHTML = '请输入数据库端口'
                break;
              case 0:
                rqstbc.style.display = 'block'
                daFail.innerHTML = '请输入数据库服务器'
                break;
            }
          } else if (databaseFiv[0].value != '' && databaseFiv[1].value != '' && databaseFiv[2].value != '' && databaseFiv[3].value != '' && databaseFiv[4].value != '' && l == 4) {
            //数据库信息
            rqstbc.style.display = 'none'
            daFail.innerHTML = ''
            nextSteps[2].innerHTML = '<img src="./static/images/5-121204193R5-50.gif" class="loding" alt="">下一步'
            ajax({
              url: url + "/api/install/dbmonitor",
              type: 'post',
              data: {
                "hostname": databaseFiv[0].value,
                "hostport": databaseFiv[1].value,
                "username": databaseFiv[2].value,
                "password": databaseFiv[3].value,
                "dbname": databaseFiv[4].value,
              },
              dataType: 'json',
              timeout: 10000,
              contentType: "application/json",
              success: function (res) {
                nextSteps[2].innerHTML = '下一步'
                var data = JSON.parse(res)
                if (data.status == 200) {
                  xiayibu(index)
                } else {
                  rqstbc.style.display = 'block'
                  daFail.innerHTML = data.msg
                }
                //服务器返回响应，根据响应结果，分析是否登录成功
              },
              //异常处理
              error: function (e) {
                rqstbc.style.display = 'block'
                daFail.innerHTML = e
                nextSteps[2].innerHTML = '下一步'
              }
            })
          }
        }
        // xiayibu(index)
        break;
      case 3:
        for (var l = inputList.length - 1; l >= 0; l--) {
          if (inputList[l].value.match(/^[ ]*$/)) {
            switch (l) {
              case 1:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入系统名称!'
                break;
              case 2:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入网站域名'
                break;
              case 3:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入后台路径'
                break;
              case 4:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入管理员账号'
                break;
              case 5:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入密码'
                break;
              case 7:
                tipsInfo.style.display = 'block'
                tipsInfoText.innerHTML = '请输入Email'
                break;
            }
          } else if (inputList[6].value != inputList[5].value) {
            tipsInfo.style.display = 'block'
            tipsInfoText.innerHTML = '两次密码不一致！'
          } else if (inputList[1].value != '' && inputList[2].value != '' && inputList[3].value != '' && inputList[4].value != '' && inputList[5].value != '' && inputList[6].value != '' && inputList[7].value != '' && l == 7) {

            if (!new RegExp(/(http|https):\/\/([\w.]+\/?)\S*/).test(inputList[2].value) || inputList[2].value.substr(inputList[2].value.length - 1, 1) == '/') {
              tipsInfo.style.display = 'block'
              tipsInfoText.innerHTML = '请输入合法网站域名，必须以http://或https://开头，且不能以 / 结尾';
              return;
            }
            if (!new RegExp(/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{2,32}$/).test(inputList[3].value)) {
              tipsInfo.style.display = 'block'
              tipsInfoText.innerHTML = '后台路径必须是数字+字母，且长度2到32位';
              return;
            }
            // if(!new RegExp(/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{2,32}$/).test(inputList[4].value)){
            //   tipsInfo.style.display = 'block'
            //   tipsInfoText.innerHTML = '管理员账号必须是数字+字母，且长度2到32位';
            //   return;
            // }
            // 网站配置
            tipsInfo.style.display = 'none'
            tipsInfoText.innerHTML = ''
            nextSteps[3].innerHTML = '<img src="./static/images/5-121204193R5-50.gif" class="loding" alt="">下一步'
            ajax({
              url: url + '/api/install/codemonitor',
              type: 'POST',
              dataType: 'json',
              data: { "license": inputList[0].value },
              async: false,
              timeout: 10000,
              contentType: "application/json",
              success: function (res) {
                var data = JSON.parse(res)
                if (data.status == 200) {
                  ajax({
                    url: url + "/api/install/envsystem",
                    type: 'post',
                    data: {
                      "license": inputList[0].value,
                      "sitename": inputList[1].value,
                      "domain": inputList[2].value,
                      "admin_application": inputList[3].value,
                      "manager": inputList[4].value,
                      "manager_pwd": inputList[5].value,
                      "manager_ckpwd": inputList[6].value,
                      "manager_email": inputList[7].value,
                    },
                    dataType: 'json',
                    timeout: 10000,
                    contentType: "application/json",
                    success: function (res) {
                      nextSteps[3].innerHTML = '下一步'
                      var data = JSON.parse(res)
                      if (data.status == 200) {
                        xiayibu(index)
                        // data.data.sql_num-1
                        totalIndex = parseInt(data.data.sql_num);
                        ImplementationSteps(0);
                      } else {
                        tipsInfo.style.display = 'block'
                        tipsInfoText.innerHTML = data.msg
                      }
                      //服务器返回响应，根据响应结果，分析是否登录成功
                    },
                    //异常处理
                    error: function (e) {
                      tipsInfo.style.display = 'block'
                      tipsInfoText.innerHTML = '配置失败！'
                      nextSteps[3].innerHTML = '下一步'
                    }
                  })

                } else {
                  tipsInfo.style.display = 'block'
                  nextSteps[3].innerHTML = '下一步'
                  tipsInfoText.innerHTML = data.msg
                }

              },
            })


          }
        }

        // xiayibu(index)
        // ImplementationSteps()
        break;
    }
  }
}
// 上一步
function shangyibu (index) {
  if (index === 0) {
    testing()
  }
  if (index > 0 && index < 4) {
    for (var j = 0; j < showBox.length; j++) {
      showBox[j].style.display = 'none'
    }
    showBox[index].style.display = 'block'
  }
}
for (var i = 0; i < lastStep.length; i++) {
  lastStep[i].index = i;
  lastStep[i].onclick = function (params) {
    var index = this.index
    shangyibu(index)
  }
}


// 密码显示
var passShow = document.getElementsByClassName('showBtn')
var passwordInput = document.getElementsByClassName('password')
for (var i = 0; i < passShow.length; i++) {
  passShow[i].index = i
  passShow[i].onclick = function (params) {
    var index = this.index
    if (passwordInput[index].type == "password") {
      passwordInput[index].type = "text";
    } else {
      passwordInput[index].type = "password";
    }
  }
}

// function rqst(path,type,params) {
//   var recordname
//   ajax({
//     url:url+path,
//     type:type,
//     dataType:'json',
//     data:params,
//     async:false,
//     timeout:10000,
//     contentType:"application/json",
//     success:function(res){
//       var data = JSON.parse(res)
//         if(data.status==200){

//         }else{
//           alert(data.msg)
//         }
//         recordname = data
//   　　　　　　//服务器返回响应，根据响应结果，分析是否登录成功
//     },
//     //异常处理
//     error:function(e){
//         console.log(e);
//     }
//   })
//   return recordname
// }
// function add() {
//   let sqlIndex = 0;
//   return function () {
//       return sqlIndex++
//   }

// }
// console.log(add());
// 执行步骤
// cishuzhuangtia 接口状态为200的时候执行
function ImplementationSteps (num) {
  // 进行安装
  // 初始化赋值
  // let sqlIndex=add()
  // sessionStorage.setItem('num',sqlIndex)
  // 通过 num判断执行多少次sql接口  num 通过上一个接口返回
  ajax({
    url: url + '/api/install/installing',
    type: 'POST',
    dataType: 'json',
    // 作为动态传参这个动态递增到25
    data: { "sql_index": num },
    async: false,
    timeout: 100000,
    contentType: "application/json",
    success: function (res) {

      // 自身加1
      // sqlIndex = sqlIndex + 1;
      // sqlIndex++
      // console.log(sqlIndex);
      // console.log(res);
      let installData = JSON.parse(res);
      if (installData.status == 200) {
        if (installData.msg == "安装完成！") {
          stepList.find('.azgif').remove();
          ajax({
            url: url + '/api/install/setdbconfig',
            type: 'POST',
            dataType: 'json',
            async: false,
            timeout: 10000,
            contentType: "application/json",
            success: function (res) {
              var data = JSON.parse(res)
              if (data.status == 200) {
                stepList.append('<li><img src="./static/images/dui.png" alt=""><span>3.' + data.msg + '</span></li><li><img src="./static/images/5-121204193R5-50.gif" alt=""><span>4.写入数据！</span></li>')
                stepList.scrollTop(stepList.prop("scrollHeight"))
                progress.style.width = '74%'

                ajax({
                  url: url + '/api/install/setsite',
                  type: 'POST',
                  dataType: 'json',
                  async: false,
                  timeout: 100000,
                  contentType: "application/json",
                  success: function (res) {
                    var data = JSON.parse(res)
                    if (data.status == 200) {
                      stepList.append('<li><img src="./static/images/dui.png" alt=""><span>4.' + data.msg + '</span></li><li><img src="./static/images/5-121204193R5-50.gif" alt=""><span>5.写入钩子！</span></li>')
                      stepList.scrollTop(stepList.prop("scrollHeight"))
                      progress.style.width = '80%'


                      ajax({
                        url: url + '/api/install/installapphooks',
                        type: 'POST',
                        dataType: 'json',
                        async: false,
                        timeout: 10000,
                        contentType: "application/json",
                        success: function (res) {
                          var data = JSON.parse(res)
                          if (data.status == 200) {
                            stepList.append('<li><img src="./static/images/dui.png" alt=""><span>5.' + data.msg + '</span></li><li><img src="./static/images/dui.png" alt=""><span>6.正在写入行为！</span></li>')
                            stepList.scrollTop(stepList.prop("scrollHeight"))
                            progress.style.width = '87%'

                            ajax({
                              url: url + '/api/install/installappuseractions',
                              type: 'POST',
                              dataType: 'json',
                              async: false,
                              timeout: 10000,
                              contentType: "application/json",
                              success: function (res) {
                                var data = JSON.parse(res)
                                if (data.status == 200) {
                                  stepList.append('<li><img src="./static/images/dui.png" alt=""><span>6.' + data.msg + '</span></li><li><img src="./static/images/dui.png" alt=""><span>7.正在步骤检测与锁定！</span></li>')
                                  stepList.scrollTop(stepList.prop("scrollHeight"))
                                  progress.style.width = '94%'

                                  ajax({
                                    url: url + '/api/install/stepLast',
                                    type: 'POST',
                                    dataType: 'json',
                                    async: false,
                                    timeout: 10000,
                                    contentType: "application/json",
                                    success: function (res) {
                                      var data = JSON.parse(res)
                                      if (data.status == 200) {
                                        stepList.append('<li><img src="./static/images/dui.png" alt=""><span>7.' + data.msg + '</span></li>')
                                        stepList.scrollTop(stepList.prop("scrollHeight"))

                                        loginInput[0].value = data.data.admin_url
                                        loginInput[1].value = data.data.admin_name
                                        loginInput[2].value = data.data.admin_pass

                                        progress.style.width = '100%'
                                        setTimeout(() => {
                                          xiayibu(4)
                                        }, 1000);
                                      } else {
                                        stepList.append('<li><img src="./static/images/dui.png" alt=""><span>7.' + data.msg + '</span></li>')
                                        stepList.scrollTop(stepList.prop("scrollHeight"))
                                      }

                                    },
                                    //异常处理
                                    error: function (e) {
                                      console.log(e);
                                    }
                                  })

                                } else {
                                  stepList.append('<li><img src="./static/images/cuo.png" alt=""><span>6.' + data.msg + '</span></li>')
                                  stepList.scrollTop(stepList.prop("scrollHeight"))
                                }

                              },
                              //异常处理
                              error: function (e) {
                                console.log(e);
                              }
                            })

                          } else {
                            stepList.append('<li><img src="./static/images/cuo.png" alt=""><span>5.' + data.msg + '</span></li>')
                            stepList.scrollTop(stepList.prop("scrollHeight"))
                          }
                        },
                        //异常处理
                        error: function (e) {
                          console.log(e);
                        }
                      })

                    } else {
                      stepList.append('<li><img src="./static/images/cuo.png" alt=""><span>4.' + data.msg + '</span></li>')
                      stepList.scrollTop(stepList.prop("scrollHeight"))
                    }

                  },
                  //异常处理
                  error: function (e) {
                    console.log(e);
                  }
                })

              } else {
                stepList.append('<li><img src="./static/images/cuo.png" alt=""><span>3.' + data.msg + '</span></li>')
                stepList.scrollTop(stepList.prop("scrollHeight"))
              }

            },
            //异常处理
            error: function (e) {
              console.log(e);
            }
          })
        }
        else {
          sqlIndex++;
          stepList.find('.azgif').remove();
          stepList.append('<li><img src="./static/images/dui.png" alt=""><span>' + installData.msg + '</span></li>')
          stepList.append('<li class="azgif"><img src="./static/images/5-121204193R5-50.gif" alt=""><span>正在写入数据库配置！</span></li>')
          progress.style.width = "" + (70 / totalIndex) * sqlIndex + "%"
          stepList.scrollTop(stepList.prop("scrollHeight"))
          ImplementationSteps(sqlIndex);
        }




      } else {
        stepList.append('<img src="./static/images/cuo.png" alt=""><span>sql执行失败</span>')
      }

    },
    //异常处理
    error: function (e) {
      console.log(e);
    }
  })





}

// 登录

function login () {
  if (loginInput[0].value.indexOf('http') == 0) {
    // location.href = loginInput[0].value;
    window.open(loginInput[0].value);
  } else {
    // location.href = window.location.protocol+'//'+loginInput[0].value
    window.open(window.location.protocol + '//' + loginInput[0].value);
  }
  // ajax({
  //   url:url+'/admin/login',
  //   type:'POST',
  //   data:{
  //     "username":loginInput[1].value,
  //     "password":loginInput[2].value,
  //     "captcha":loginInput[0].value,
  //     "request_time":new Date().getTime(),
  //   },
  //   dataType:'json',
  //   async:false,
  //   timeout:10000,
  //   contentType:"application/json",
  //   success:function(res){
  //     var data = JSON.parse(res)
  //       if(data.status==200){
  //         location.href = loginInput[0].value
  //       }else{ alert(data.msg)}

  //   },
  //   //异常处理
  //   error:function(e){
  //       console.log(e);
  //   }
  // })
}
