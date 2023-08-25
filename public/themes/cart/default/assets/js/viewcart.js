
var timer;
function cartQtyBtn (_this) {
    var cart_qty = $(_this).parent();
    i = cart_qty.find("input[name='i']").val();
    qty = cart_qty.find("input[name='qty']").val();
    $.ajax({
        url: _url + '/cart?action=viewcart&statuscart=change&ajax=true'
        , data: { i: i, qty: qty }
        , dataType: 'json'
        , type: 'post'
        , success: function (data) {
            location.reload();
        }
    })
}

function init() {
    //登录
    login_secected();
    //注册
    register_secected();
    changeType("new");
}
//刷新图形验证码
function reloadcode (obj, action) {
    obj.setAttribute('src', '/verify?name=' + action + "&request_time=" + Math.random());
}
// 设置倒计时
function setCutdown (_type) {
    clearInterval(timer)
    var seconds = 60
    timer = setInterval(function(){
        if (seconds == 0) {
            $('#register_' + _type + '_get_code').removeAttr('disabled')          
            $('#register_' + _type + '_get_code').text('获取验证码')
            clearInterval(timer)
            return
        }
        seconds--
        $('#register_' + _type + '_get_code').text(seconds + 's后重试')
        $('#register_' + _type + '_get_code').attr('disabled', 'disabled')
    }, 1000);
}
//获取验证码
function getCode (type, _this) { 
    _type = type;
    setCutdown(_type)
    var url = 'register_' + type + '_send'
        , data = {
            mk: '{$Setting.msfntk}'
            , captcha: $('.register' + type + ' input[name="captcha"]').val()
        }
        , _text = type == 'phone' ? '手机' : '邮箱';
    data[type] = $('#register_' + type).val();
    if (type == 'phone') {
        data.phone_code = $('#phoneCodeSel').val();
    }
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        dataType: "json",
        success: function (res) {
            $(_this).parent().parent().parent().prev().find('img').click();
            if (res.status !== 200) {
                $('#register_' + type + '_get_code').text('获取' + _text + '验证码')
                clearInterval(timer)
                toastr.error(res.msg)
            } else {
                setCutdown(_type)
                toastr.success(res.msg)
            }
        },
        error: function (e) {
            toastr.error(res.msg)
        }
    });
}

// 提示层
function removeItem (url, title, text, data) {
  // console.log(321);
    $(".statuscart_remove").show();
    $(".statuscart_remove #inputRemoveItemRef").val(data);

    if (!title) {
        title = '提示';
    }
    if (text) {
        content = '<div class="d-flex align-items-center"><i class="fas fa-exclamation-circle fs-20 text-warning mr-2"></i> ' + text + '</div>';
        area = ['420px'];
    } else {
        content = $('.' + action).html();
        area = ['500px'];
    }

    $('#customModal').modal('show')
    $('#customBody').html(content)
    $(document).on('click', '#customSureBtn', function () {
        var WebUrl = _url + '/';
        if (data && !$('#customBody').find('form').eq(0).serialize()) {
            data = data;
        } else {
            data = $('#customBody').find('form').eq(0).serialize();
        }
        if ($('#customSureBtn').find('.bx-loader').length < 1) {
            $('#customSureBtn').prepend('<i class="bx bx-loader bx-spin font-size-16 align-middle mr-2 text-white"></i>')
            $('#customSureBtn').attr('disabled')
            $('#customSureBtn').css('cursor', 'not-allowed')
        }
        $.ajax({
            url: WebUrl + url,
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function () {
            },
            success: function (data) {
                if (data.status == '200') {
                    toastr.success(data.msg);

                } else {
                    // toastr.error(data.msg);
                }
                location.reload()
            }
        });
    });
}

// 登录
function login_secected () {
    var checked = $("#login .input_active").val();
    //console.log(checked);
    if (checked == "email") {
        $(".loginemail").show();
        $(".loginphone").hide();
        $(".loginphone input,.loginphone select,.loginphone button").attr("disabled", "disabled");
        $(".loginemail input,.loginemail button").removeAttr("disabled");
    } else if (checked == "phone") {
        $(".loginemail").hide();
        $(".loginphone").show();
        $(".loginemail input,.loginemail button").attr("disabled", "disabled");
        $(".loginphone input,.loginphone select,.loginphone button").removeAttr("disabled");
    }
}

// 注册
function register_secected () {
    var checked = $("#register .input_active").val();
    //console.log(checked);
    if (checked == "email") {
        $(".registeremail").show();
        $(".registerphone").hide();
        $(".registerphone input,.registerphone select,.registerphone button").attr("disabled", "disabled");
        $(".registeremail input,.registeremail button").removeAttr("disabled");
    } else if (checked == "phone") {
        $(".registeremail").hide();
        $(".registerphone").show();
        $(".registeremail input,.registeremail button").attr("disabled", "disabled");
        $(".registerphone input,.registerphone select,.registerphone button").removeAttr("disabled");
    }
}

// changeType
function changeType (type) {
    if (type == 'old') {
        $("input[name='register_or_login']").val("login");
        $('.new-user').css('display', 'block');
        $('.old-user').css('display', 'none');
        $('.old-user').find("input,select,textarea").attr("disabled", "disabled");
        $('.new-user').find("input,select,textarea").removeAttr("disabled");
        login_secected();
    } else {
        $("input[name='register_or_login']").val("register");
        $('.new-user').css('display', 'none');
        $('.old-user').css('display', 'block');
        $('.old-user').find("input,select,textarea").removeAttr("disabled");
        $('.new-user').find("input,select,textarea").attr("disabled", "disabled");
        register_secected();
    }
}

$(function(){
var timer, _type = '';
init();

$("#login input").click(function () {
    $("#login input").removeClass("input_active");
    $(this).addClass("input_active");
    login_secected();
});

$("#register input").click(function () {
    $("#register input").removeClass("input_active");
    $(this).addClass("input_active");
    register_secected();
});

$(".addfunds-payment").click(function (data) {
    $(".addfunds-payment").removeClass("active");
    $(".addfunds-payment").find("input").prop("checked", false);
    $(this).addClass("active");
    $(this).find("input").prop("checked", true);
});

//---------------------------优惠码-----------------start

//使用
$("#promo button").click(function () {
    var promo = $("#promo input[name='promo']").val();
    if (!promo) {
        toastr.error("优惠码不能为空")
        return false;
    }
    $.ajax({
        url: _url + "/cart?action=viewcart&statuscart=promo&ajax=true",
        type: 'POST',
        data: { "promo": promo },
        dataType: 'json',
        success: function (res) {

            if (res.SuccessMsg) location.reload();
            else toastr.error(res.ErrorMsg);

        }
    });
});
//移除

$("#removepromo").click(function () {
    $.ajax({
        url: _url + "/cart?action=viewcart&statuscart=removepromo&ajax=true",
        type: 'POST',
        data: [],
        dataType: 'json',
        success: function (data) {
            location.reload();
        }
    });
});
//---------------------------优惠码-----------------end
});