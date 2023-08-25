<!DOCTYPE html>
<!--                                             
	__  ___          __      __          ____                                            
   /  |/  ____  ____/ __  __/ ___  _____/ __ \________  ____ _____   _________  ____ ___ 
  / /|_/ / __ \/ __  / / / / / _ \/ ___/ / / / ___/ _ \/ __ `/ __ \ / ___/ __ \/ __ `__ \
 / /  / / /_/ / /_/ / /_/ / /  __(__  / /_/ / /__/  __/ /_/ / / / _/ /__/ /_/ / / / / / /
/_/  /_/\____/\__,_/\__,_/_/\___/____/\____/\___/\___/\__,_/_/ /_(_\___/\____/_/ /_/ /_/ 

Design production By ModulesOcean.com
-->
<html lang="zh">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>插件列表 - 魔方财务</title>
  <!-- Styling -->
  <link rel="stylesheet" href="{$Themes}/assets/css/bootstrap.min.css" />
  <!--  <link rel="stylesheet" href="{$Themes}/assets/css/bootstrap-icons.css"> -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.0/font/bootstrap-icons.css">
  <link href="{$Themes}/assets/css/fontawesome-all.min.css" rel="stylesheet">
  <link href="{$Themes}/assets/css/style.css" rel="stylesheet">
  <script src="{$Themes}/assets/js/popper.min.js"></script>
  <script src="{$Themes}/assets/js/jquery.min.js"></script>
  <script src="{$Themes}/assets/js/bootstrap.min.js"></script>
  <script src="{$Themes}/assets/js/packery.pkgd.min.js"></script>
  <script src="{$Themes}/assets/js/draggabilly.pkgd.min.js"></script>
  <link href="{$Themes}/assets/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
  <script src="{$Themes}/assets/js/bootstrap-datetimepicker.min.js"></script>
  <script src="{$Themes}/assets/js/moment.js"></script>
  <script src="{$Themes}/assets/js/daterangepicker.js"></script>
  <script src="{$Themes}/assets/js/bootstrapValidator.min.js"></script>
  <link href="{$Themes}/assets/css/daterangepicker.css" rel="stylesheet">
  <style>
    /*@media screen and (max-width: 992px) {
      .logo-div {
        display: none;
      }
    }*/
    .logo-div {
      width: 200px;
      height: 30px;
    }
    .nav-item {
      padding: 0 10px;
    }
    .dropdown-menu {
      min-width: auto;
      border: none;
      border-radius: 2px;
      left: 15%;
    }


    .sub-nav {
      background:#fff;
      color:#333;
    }
    .sub-nav:hover {
      background:#eceff4;
    }
    .navbar .navbar-nav .link-item {
      height:36px;
      line-height:36px;
      margin-right: 0;
      padding:0 8px;
    }
    .dropdown .nav-link.dropdown-toggle{
      margin-right:0;
      padding: 0 9px;
    }
    .dropdown-toggle::after {
      display:none;
    }
    .nav-item.dropdown {
      padding-left: 2px;
    }
    .dropdown-menu {
      display:none;
    }
    .nav-item.dropdown:hover .dropdown-menu
    {
      display:block;
    }
    .nav-item.dropdown:hover .arrow {
      transform:rotate(180deg);
      transition: .3s;
    }


  </style>
  <!-- wyh + -->
  <script src="{$Themes}/assets/libs/toastr/build/toastr.min.js"></script>
  <link rel="stylesheet" href="{$Themes}/assets/libs/toastr/build/toastr.min.css" />
</head>
<body>
<header class="header">
  <div class="container-fluid">
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="logo-div">
        <a class="navbar-brand" href="/{:config('database.admin_application')}/#/home-page"></a>
      </div>
      <button class="navbar-toggler" type="button" id="menu-button">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <!-- 给li下的nav-link添加class:selected即可实现选中效果 -->
        <ul class="navbar-nav" >
          {foreach $topMenu as $val}
            {if $val.child}
              <li class="nav-item dropdown" >
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                   aria-expanded="false" href="#">{$val.title}
                  <img class="arrow" src="{$svg_menu}" style="width:10px;height:auto;margin-left:4px;margin-top:-4px;">
                </a>
                <ul class="dropdown-menu" style="background: #fff;">
                  {foreach $val.child as $val1}
                    <li class="sub-nav"><a class="nav-link link-item" href="{$val1.url}" style="color: #333;">{$val1.title}</a></li>
                  {/foreach}
                </ul>
              </li>
            {else/}
              <li class="nav-item">
                <a class="nav-link {$val.is_active?'selected':''}" href="{$val.url}">
                  {$val.title}
                </a>
              </li>
            {/if}
          {/foreach}
        </ul>
        <!--<ul class="navbar-nav navbar-right ml-auto">
          <li>
            <div class="top-search input-group">
              <span class="input-group-btn">
              <i class="bi bi-search"></i>
              </span>
              <input type="text" class="form-control" placeholder="请搜索...">
            </div>
          </li>-->
        <!-- <li>
          <div class="btn-group top-country-btn-group">
            <div class="dropdown-toggle top-country-img" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <img src="https://w2.test.idcsmart.com/upload/common/country/CN.png">
            </div>
            <ul class="dropdown-menu top-country-dropdown-menu">
              <li>
                <img src="https://w2.test.idcsmart.com/upload/common/country/HK.png" >
                <span>繁體中文</span>
              </li>
              <li>
                <img src="https://w2.test.idcsmart.com/upload/common/country/CN.png" >
                <span>中文简体</span>
              </li>
              <li>
              <img src="https://w2.test.idcsmart.com/upload/common/country/US.png" >
                <span>English</span>
              </li>
            </ul>
          </div>
        </li> -->
        <!--<li>
        <div class="btn-group">
            <div class="dropdown-toggle top-user-logo" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

            </div>
            <ul class="dropdown-menu top-user-dropdown-menu">
              <li class="top-username">

              </li>
              <li class="update-password">
                修改密码
              </li>
              <li class="logout">
                退出登录
              </li>
            </ul>
          </div>
        </li>-->
        <!-- <li class="nav-item">
          <a class="nav-link" href="javascript:void(0);">
            <i class="bi bi-search"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="javascript:void(0);">
            <i class="bi bi-bell-fill"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="javascript:void(0);">
            <i class="bi bi-question-circle-fill"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link avatar-img" href="javascript:void(0);">
            <img class="avatar avatar-sm"
              src="https://gravatar.loli.net/avatar/99a1148b81c606f1ec711152e525d833?s=25&d=mp" />
          </a>
        </li> -->
        </ul>
      </div>
    </nav>
  </div>
</header>
</body>
<script type="text/javascript">
  $(function() {
    // logo hover打开
    var timer = ''
    $('.top-user-logo').mouseover(function() {
      $('.top-user-dropdown-menu').addClass('show');
      $('.top-country-dropdown-menu').removeClass('show');
    })
    $('.top-user-logo').mouseout(function() {
      timer = setTimeout(() => {
        $('.top-user-dropdown-menu').removeClass('show');
        clearTimeout(timer)
      }, 3000);
    });
    $('.top-user-dropdown-menu').mouseover(function() {
      $('.top-user-dropdown-menu').addClass('show');
      clearTimeout(timer)
    })
    $('.top-user-dropdown-menu').mouseout(function() {
      $(this).removeClass('show');
    })
    // country hover打开
    // var timer2 = ''
    // $('.top-country-img').mouseover(function() {
    //   $('.top-country-dropdown-menu').addClass('show');
    //   $('.top-user-dropdown-menu').removeClass('show');
    // })
    // $('.top-country-img').mouseout(function() {
    //     timer2 = setTimeout(() => {
    //     $('.top-country-dropdown-menu').removeClass('show');
    //     clearTimeout(timer2)
    //    }, 3000);
    // });
    // $('.top-country-dropdown-menu').mouseover(function() {
    //   $('.top-country-dropdown-menu').addClass('show');
    //   clearTimeout(timer2)
    // })
    // $('.top-country-dropdown-menu').mouseout(function() {
    //   $(this).removeClass('show');
    // })
    // 用户信息start
    let userInfo = window.localStorage.getItem('userInfo') ? JSON.parse(window.localStorage.getItem('userInfo')) : ''
    $('.top-user-logo').text(userInfo && userInfo.user_nickname && userInfo.user_nickname.charAt(0).toUpperCase())
    $('.top-username').text(userInfo && userInfo.user_nickname)
    // 修改密码
    $(".update-password").click(function(){
      window.location.href =  window.location.origin + '/admin/#/edit-person'
    });
    // 退出登录

    $('.logout').click(function(){
      $.ajax({
        type: "GET",
        url: window.location.origin + "/admin/logout",
        success: function success(res) {
          window.location.href =  window.location.origin + '/admin/#/login'
        }
      });
    })
    // 用户信息end

    window.directory = 'admin'; // 管理端目录

    var $grid = $('.home-dashboard-container').packery({
      itemSelector: ".dashboard-item",
      columnWidth: ".dashboard-sizer",
      percentPosition: "true"
    });

    // make all grid-items draggable
    $grid.find('.dashboard-item').each(function(i, gridItem) {
      var draggie = new Draggabilly(gridItem, {
        handle: ".card-header"
      });
      // bind drag events to Packery
      $grid.packery('bindDraggabillyEvents', draggie);
    });

    // tooltip
    $('[data-toggle="tooltip"]').tooltip({ boundary: 'window' });

    // popover
    $('[data-toggle="popover"]').popover();

    // 左侧菜单展开/收起
    $('#side-menu .menu').bind('click', function(event) {
      const c = $(this).attr('class');
      console.log(c);
      // console.log(c);
      if (c.indexOf('active') >= 0) {
        $(this).removeClass('active');
        $(this).find('ul').eq(0).removeClass('mm-show');
        $(this).find('i').eq(0).removeClass('rotate');
      } else {
        $(this).addClass('active');
        $(this).find('ul').eq(0).addClass('mm-show');
        $(this).find('i').eq(0).addClass('rotate');
      }
      event.stopPropagation();
    });

    // 左侧菜单点击
    $('#side-menu .link').bind('click', function(event) {
      const c = $(this).attr('data-url');
      // console.log(c);
      if (c) {
        window.location.href = c;
      }

      event.stopPropagation();
    })

    // 更多搜索
    $('#search-more').bind('click', function(aaa) {
      console.log(aaa)
      console.log('show');
      if ($('.more-search').is(':visible')) {
        $('.more-search').slideUp(300);
        $(this).html('高级搜索');
      } else {
        $('.more-search').slideDown(300);
        $(this).html('收起搜索');
      }
    });

    // 顶部显示/隐藏菜单按钮
    $('#menu-button').bind('click', function() {
      // console.log($('.vertical-menu').is(':visible'));
      if ($('.vertical-menu').is(':visible')) {
        $('.vertical-menu').hide();
      } else {
        $('.vertical-menu').show();
      }
    });

    // 日期控件
    $('.datetime').datetimepicker();

    // 日期范围
    $('.daterange').daterangepicker({
              ranges: {
                '今天': [moment(), moment()],
                '本周': [moment().startOf('week'), moment().endOf('week')],
                '本月': [moment().startOf('month'), moment().endOf('month')],
                '今年': [moment().startOf('year'), moment().endOf('year')]
              },
              startDate: moment(),
              endDate: moment().endOf('month')
            },
            function(start, end) {
              $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
    );

    $('#navTabs').on("click", "div", function(e) {
      $('#typeValue').val(e.target.dataset.value);
      document.getElementById('navTabsForm').submit();
    });

    // 全选/全不选
    $('#selectAll').bind('click', function() {
      var selectAll = $(this).is(":checked");
      if (selectAll) {
        $('.row-checkbox').prop('checked', true);
      } else {
        $('.row-checkbox').prop('checked', false);
      }
    })


  });

  // 获取选中的表格id列表
  function getSelectedRow() {
    const selectdId = [];
    $(".row-checkbox:checked").each(function() {
      selectdId.push($(this).attr('id'));
    });
    console.log(selectdId);
    return selectdId;
  }

  (function($) {
    $.fn.serializeJson = function() {
      var serializeObj = {};
      $(this.serializeArray()).each(function() {
        serializeObj[this.name] = this.value;
      });
      return serializeObj;
    };
  })(jQuery);

  function Toast(msg, duration) {
    duration = isNaN(duration) ? 1000 : duration;
    var m = document.createElement('div');
    m.innerHTML = msg;
    m.style.cssText =
            "max-width:60%;min-width: 150px;padding:0 14px;height: 40px;color: rgb(255, 255, 255);line-height: 40px;text-align: center;border-radius: 4px;position: fixed;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 999999;background: rgba(0, 0, 0,.7);font-size: 16px;";
    document.body.appendChild(m);
    setTimeout(function() {
      var d = 0.5;
      m.style.webkitTransition = '-webkit-transform ' + d + 's ease-in, opacity ' + d +
              's ease-in';
      m.style.opacity = '0';
      setTimeout(function() {
        document.body.removeChild(m)
      }, d * 1000);
    }, duration);
  }

  function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      if(pair[0] == variable){return pair[1];}
              }
    return (false);
  }
</script>