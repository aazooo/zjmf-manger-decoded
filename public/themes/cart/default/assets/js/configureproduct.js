
init();
function init () {
    $('.selectpicker').selectpicker();
    if ($(".configoption_os_group").length > 0) {
        configoption_os($(".configoption_os_group option:selected").data("os"));
    } else {
        configoption_ajax();
    }

}
function osGroupChange (_this) {
    var os_group = $(_this).find("option:selected").data("os");
    configoption_os(os_group);
}

function configoption_os (os_group) {
    var option = "";
    $.each(os_group, function (key, val) {
        if ($(".configoption_os").data("os-selected") == val.id) st = "selected"; else st = "";
        option += '<option  id="sub' + val.id + '" ' + st + ' value="' + val.id + '">' + val.version + '</option>';
    })
    $("select.configoption_os").html(option);

    configoption_ajax();

}
//下拉
$(".configoption_form select").change(function () {
    configoption_ajax();
})
//单选
$(document).on('click', ".configoption_form input[type='radio'],.configoption_form input[type='checkbox'],.configoption_form input[type='radio']", function () {
    var cid = $(this).data('optionid')
        , sub_id = $(this).data('subid')
        , pid = $('input[name="pid"]').val();
    var data = { cid: cid, sub_id: sub_id, pid: pid }
    if (cid) {
        $.ajax({
            url: '/getLinkAgeList'
            , data: data
            , dataType: 'json'
            , type: 'get'
            , success: function (e) {
                if (e.status != 200) {
                    toastr.error('配置项错误');
                    return false;
                }
                appendLinkAge(data, e);
                configoption_ajax();
            }
        })
        // return false;
    } else {
        configoption_ajax();
    }

})

function appendLinkAge (data, e) {
    var son = e.data[0].son;
    var html = '';
    for (var i in son) {
        html += '<div class="form-group row configureproduct">'
            + '<label for="example-search-input" class="col-md-2 col-form-label">' + son[i].option_name;
        if (son[i].notes) {
            html += '<span data-toggle="tooltip" data-placement="right" title="' + son[i].notes + '">\n' +
                '<i class="bx bxs-help-circle pointer text-primary"></i>\n' +
                '</span>';
        }
        html += '</label> <div class="col-md-10"><div class="btn-group btn-group-toggle mt-2 mt-xl-0" data-toggle="buttons">'

        for (var j in son[i].sub) {
            var label_check = son[i].checkSubId == son[i].sub[j].id ? 'active' : '';
            html += '<label class="btn btn-primary btn-sm ' + label_check + '">' +
                '<input id="config' + son[i].id + '_' + son[i].sub[j].id + '" type="radio" data-optionid="' + data.cid + '" data-subid="' + son[i].sub[j].id + '" name="configoption[' + son[i].id + ']"\n' +
                '  value="' + son[i].sub[j].id + '" class="form-check-input"';
            if (son[i].checkSubId == son[i].sub[j].id) {
                html += ' checked=""';
            }
            html += '>' + son[i].sub[j].option_name + '</label>';
        }
        html += '</div> </div></div>'
    }
    $('.lingAge-' + data.cid + '-son').html(html);
}
//滑块
/* $(".configoption_range").change(function(){
    $(this).data("active",true);
    //rangeChange($(this));
    $(this).removeData("active");
	
}); */
$(".configoption_range").mousedown(function () {
    $(this).data("active", true);
});
$(".configoption_range").mouseup(function () {
    rangeChange($(this));
    $(this).removeData("active");
    configoption_ajax();
});
var range = 0;
var dataSub;
var sectionShow;
function rangeNum (val, min, max, qty_stage) {
    sectionShow = false;
    if (val % qty_stage == 0 || val == min || val == max) {
        dataSub.map(item => {
            if (val >= item.qty_minimum && val <= item.qty_maximum) {
                sectionShow = true;
            }
        })
        if (sectionShow) {
            range = val;
            return
        } else {
            val++;
            rangeNum(val, min, max, qty_stage)
        }
    } else {
        val++;
        rangeNum(val, min, max, qty_stage)
    }
}
function rangeChange (_this) {
    let qty_stage = parseInt($(_this).attr('qty_stage'));
    let minNum = parseInt($(_this).attr('min'));
    let maxNum = parseInt($(_this).attr('max'));
    dataSub = JSON.parse($(_this).attr('data-sub'));
    range = $(_this).val();
    rangeNum(parseInt(range), parseInt(minNum), parseInt(maxNum), parseInt(qty_stage));
    // if (range_sub($(_this), range)) {
    //     $(_this).siblings(".configoption_range_val").val(range);		
    // } else {
    //     // $(_this).val($(_this).siblings(".configoption_range_val").val());
    //     $(_this).val(range);
    // }
    $(_this).siblings(".configoption_range_val").val(range);
    $(_this).val(range);
    $(_this).siblings('.range_none').each(function () {
        if (parseFloat($(this).attr('title')) < parseFloat(range)) {
            $(this).hide();
        } else {
            $(this).show();
        }
    })
    let blNum = (parseInt(range) - parseInt(minNum)) / (parseInt(maxNum) - parseInt(minNum)) * 100
    $(_this).css('background', 'linear-gradient(to right, #2948df, #F1F3F8 ' + blNum + '%, #F1F3F8)');
}
// 移动端滑动
$(".main-content").on("touchend", ".configoption_range", function (e) {
    rangeChange(this)
    configoption_ajax();
})


// $(document).on('click', ':not(.configoption_range_val)', function () {
//     $(".configoption_range_val").each(function (i) {
//         if ($(this).data("active")) {
//             $(this).removeData("active");
//             numberKeyup2($(this));
//         }
//     })
// })

function numberKeyup (_this) {
    $(_this).data("active", true);

    const min = $(_this).attr("min"),
        max = $(_this).attr("max"),
        val = $(_this).val();
    console.log(val, typeof (val), min, typeof (min), max)

    if (parseFloat(val) > parseFloat(max)) {

        $(_this).val(parseFloat(max))
    }
    if (parseFloat(val) < parseFloat(min)) {
        $(_this).val(parseFloat(min))
    }


    $(".configoption_range_val").each(function (i) {
        console.log($(this), $(this).data("active"))
        if ($(this).data("active")) {
            $(this).removeData("active");
            numberKeyup2($(this));
        }
    })
}
function numberKeyup2 (_this) {
    //输入
    _this.val(_this.val().replace(/[^\d]/g, ''));
    var min = _this.attr("min");
    var max = _this.attr("max");
    var val = (min != "") ? (min) : "0";
    if (_this.val() == "") _this.val(val);
    var number = _this.val();
    if (range_sub(_this.siblings(".configoption_range"), number)) {
        _this.siblings(".configoption_range").val(number);

    } else {
        _this.val(_this.siblings(".configoption_range").val());
        number = _this.siblings(".configoption_range").val()
    }
    let blNum = (parseInt(_this.val()) - parseInt(min)) / (parseInt(max) - parseInt(min)) * 100
    _this.siblings(".configoption_range").css('background', 'linear-gradient(to right, #2948df, #ebeff4 ' + blNum + '%, #ebeff4)');
    if ($(_this).val() % $(_this).attr('qty_stage') != 0 && $(_this).val() != $(_this).attr('min') && $(_this).val() != $(_this).attr('max')) {
        toastr.error(`请输入${$(_this).attr('qty_stage')}的倍数`);
        _this.val($(_this).attr('min'));
        _this.siblings(".configoption_range").css('background', '#ebeff4').val($(_this).attr('min'));
    }
    _this.siblings('.range_none').each(function () {
        if (parseFloat($(this).attr('title')) < parseFloat(number)) {
            $(this).hide();
        } else {
            $(this).show();
        }
    })
    configoption_ajax();
}

//滑块
function range_sub (range, val) {
    var sub = range.data("sub");
    var range_v = 0, range_i;
    $.each(sub, function (i, v) {
        range_i = i + 1;
        if (v.qty_minimum > val || v.qty_maximum < val) range_v += 1;
    })
    if (range_i == range_v) return false;
    return true;
    //console.log(range_i+":"+range_v);
}
function configoption_ajax () {
    //config_options_links();
    config_options_links();//对前面匹配的再次匹配，防止无效的条件判断。
    //$('.selectpicker_refresh').selectpicker("refresh");
    $('.selectpicker').selectpicker("refresh");
    $.post("?action=ordersummary&order_frm_tpl=" + order_frm_tpl + "&tpl_type=" + tpl_type + "&date=" + new Date().getTime(), $(".configoption_form").serialize(), function (configoption_total) {
        $(".configoption_total").html(configoption_total);
    }, "html");

}
window.onload = function () {

}


//高级配置项
function config_options_links () {
    if (!links) return false;
    clear_disabled();
    var config_result_array = {}; var key_num = 0;
    $.each(links, function (key, val) {
        //条件
        var configcondition = config_condition(val);

        // console.log(val.result);
        //结果
        if (configcondition) {
            $.each(val.result, function (result_key, result_val) {
                var val_config = {}, sneq = {}, seq = {}, config_result_json = {};
                if (config_result_array[result_val.config_id] == undefined) config_result_array[result_val.config_id] = {};
                else config_result_json = config_result_array[result_val.config_id];
                //console.log(config_result_json);
                val_config['config_id'] = result_val.config_id;
                val_config['sneq'] = config_result_json.sneq;
                val_config['seq'] = config_result_json.seq;
                if (result_val.relation == "sneq") {
                    if (!config_result_json.hasOwnProperty('sneq')) {
                        sneq = result_val.sub_id;
                    } else {
                        $.each(config_result_json.sneq, function (seq_key, seq_val) {
                            sneq[seq_key] = seq_val;
                        });
                        $.each(result_val.sub_id, function (seq_key, seq_val) {
                            sneq[seq_key] = seq_val;
                        });
                    }
                    val_config['sneq'] = sneq;
                } else if (result_val.relation == "seq") {
                    if (!config_result_json.hasOwnProperty('seq')) {
                        seq = result_val.sub_id;
                    } else {
                        $.each(config_result_json.seq, function (seq_key, seq_val) {
                            seq[seq_key] = seq_val;
                        });
                        $.each(result_val.sub_id, function (seq_key, seq_val) {
                            seq[seq_key] = seq_val;
                        });
                    }
                    val_config['seq'] = seq;
                }

                //val_config['seq']=seq;
                config_result_array[result_val.config_id] = val_config;

                /* if(config_result_array[result_val.config_id]==undefined) config_result_array[result_val.config_id]={};
                if(result_val.relation=="sneq"){
                    result_val["sneq"]=result_val.sub_id;
                }else if(result_val.relation=="seq"){
                    result_val["seq"]=result_val.sub_id;
                }
                config_result_array[result_val.config_id][key_num++]=result_val; */

            });
            //console.log(val.result);
            //config_result(val.result);
        }

    });
    $.each(config_result_array, function (key, val) {
        var seq_sub_id = {}, result = {}, result_v = {};
        if (val.seq && val.sneq) {
            $.each(val.seq, function (seq_key, seq_val) {
                $.each(val.sneq, function (sneq_key, sneq_val) {
                    if (sneq_key != seq_key) {
                        seq_sub_id[seq_key] = seq_val;
                    }
                })

            })
            result_v['config_id'] = val.config_id;
            result_v['relation'] = "seq";
            result_v['sub_id'] = seq_sub_id;
        } else if (val.seq) {
            result_v['config_id'] = val.config_id;
            result_v['relation'] = "seq";
            result_v['sub_id'] = val.seq;
        } else if (val.sneq) {
            result_v['config_id'] = val.config_id;
            result_v['relation'] = "sneq";
            result_v['sub_id'] = val.sneq;
        }
        result[0] = result_v;
        config_result(result);
        //console.log(result);
    });

    //console.log(config_result_array);

}
//清除disabled
function clear_disabled () {
    $(".configureproduct input").parent().removeClass("disabled");
    $(".configureproduct input").removeAttr("disabled");
    $(".configureproduct select option").removeAttr("disabled");
    $(".configureproduct select").removeClass("disabled");
}
//判断条件是否成立
function config_condition (condition) {
    var _condition = false, _condition2 = true;
    if (condition.relation == "seq") {
        $.each(condition.sub_id, function (sub_key, sub_val) {
            if ($("#config" + condition.config_id).length > 0) {
                conditionTagName = $("#config" + condition.config_id)[0].tagName.toLowerCase();
                if (conditionTagName == "select" && $("#config" + condition.config_id).val() == sub_key) {
                    _condition = true;
                } else if (conditionTagName == "input" && $("#config" + condition.config_id).val() >= sub_val.qty_minimum && $("#config" + condition.config_id).val() <= sub_val.qty_maximum) {
                    _condition = true;
                }

            } else if ($("#config" + condition.config_id + "_" + sub_key).length > 0) {
                if ($("#config" + condition.config_id + "_" + sub_key + ":checked").val() == sub_key) {
                    _condition = true;
                }
            }
        });
    } else if (condition.relation == "sneq") {
        $.each(condition.sub_id, function (sub_key, sub_val) {
            if ($("#config" + condition.config_id).length > 0) {
                conditionTagName = $("#config" + condition.config_id)[0].tagName.toLowerCase();
                if (conditionTagName == "select") {
                    if ($("#config" + condition.config_id).val() != sub_key) {
                        _condition = true;
                    } else {
                        _condition2 = false;
                    }
                } else if (conditionTagName == "input") {
                    if ($("#config" + condition.config_id).val() <= sub_val.qty_minimum || $("#config" + condition.config_id).val() >= sub_val.qty_maximum) {
                        _condition = true;
                    } else {
                        _condition2 = false;
                    }
                }

            } else if ($("#config" + condition.config_id + "_" + sub_key).length > 0) {
                if ($("#config" + condition.config_id + "_" + sub_key + ":checked").val() != sub_key) {
                    _condition = true;
                } else {
                    _condition2 = false;
                }
            }
            //console.log($("#config"+condition.config_id).val());
            //console.log(sub_key);
        });
        //_condition=false;
    }
    if (_condition2 == false) return _condition2;
    return _condition;
}
//结果
function config_result (result) {
    $.each(result, function (result_key, result_val) {
        var configoption = $("input[name='configoption[" + result_val.config_id + "]']");//匹配的数据绑定的标签

        //console.log(result_val);
        var conditionConfig = "#config" + result_val.config_id;
        if ($(conditionConfig).length > 0) {
            //console.log($(conditionConfig).length);
            conditionTagName = $(conditionConfig)[0].tagName.toLowerCase();
            if (result_val.relation == "seq") {
                if (conditionTagName == "select") {
                    var selected_val = $(conditionConfig).val();
                    $(conditionConfig).find("option").attr("disabled", "disabled");
                } else if (conditionTagName == "input") {
                    $(conditionConfig).attr("disabled", "disabled");
                    $(conditionConfig).parent().addClass("disabled");
                }
            } else {
                if (conditionTagName == "select") {
                    var selected_val = $(conditionConfig).val();
                    $(conditionConfig).find("option").removeAttr("disabled");
                } else if (conditionTagName == "input") {
                    $(conditionConfig).removeAttr("disabled");
                    $(conditionConfig).parent().removeClass("disabled");
                }
            }

        } else {
            if (result_val.relation == "seq") {
                configoption.attr("disabled", "disabled");
                configoption.parent().addClass("disabled");
            } else if (result_val.relation == "sneq") {
                configoption.removeAttr("disabled");
                configoption.parent().removeClass("disabled");
            }
        }
        var subSeq = [], subSeqChecked = false, subSneq = [], subSneqChecked = false,
            subSeqNumber = [], subSeqNum = false, subSeqQtyMin;
        var count = Object.keys(result_val.sub_id).length, num = 0;
        $.each(result_val.sub_id, function (key, val) {
            var conditionConfigId = conditionConfig + "_" + key;

            if ($(conditionConfig).length > 0) {
                //配置项ID
                condition_subid = Number($(conditionConfig).val());

                if (result_val.relation == "seq") {
                    //匹配的数据相等 除了相等的值，其它禁选择

                    if ($(conditionConfig).data("type") == "number") {
                        //数字类型
                        $(conditionConfig).removeAttr("disabled");
                        $(conditionConfig).parent().removeClass("disabled");
                        if (!subSeqQtyMin) subSeqQtyMin = val.qty_minimum;
                        if (condition_subid < val.qty_minimum || condition_subid > val.qty_maximum) {
                            num++;
                            if (count == num) {
                                number = subSeqQtyMin;
                                $(conditionConfig).val(number);
                                $(conditionConfig).siblings(".configoption_range").val(number);
                            }
                            //console.log("count:"+count);
                            //console.log("num:"+num);
                        }
                    } else {
                        $(conditionConfig).find("#sub" + key).removeAttr("disabled");
                        //$(conditionConfig).find("#sub" + key).prop("selected", false);
                        //if ($(conditionConfig).is(":selected")) subSneqChecked = true;
                        if (selected_val == $("#sub" + key).val()) subSeqChecked = true;
                        subSeq[key] = conditionConfig;
                    }
                } else if (result_val.relation == "sneq") {
                    //匹配的数据不相等 相等的值禁选择
                    if ($(conditionConfig).data("type") == "number") {
                        //数字类型

                    } else {
                        $(conditionConfig).find("#sub" + key).attr("disabled", "disabled");
                        // $(conditionConfig).find("#sub" + key).prop("selected", false);
                        //if ($(conditionConfig).is(":selected")) subSeqChecked = true;
                        if (selected_val == $("#sub" + key).val()) subSneqChecked = true;
                        subSneq[key] = conditionConfig;
                    }

                }
            } else if ($(conditionConfigId).length > 0) {
                //配置项配置ID
                if (result_val.relation == "seq") {
                    //匹配的数据相等
                    subSeq[key] = conditionConfigId;
                    if ($(conditionConfigId).is(":checked")) subSeqChecked = true;
                    $(conditionConfigId).removeAttr("disabled");
                    $(conditionConfigId).parent().removeClass("disabled");
                } else if (result_val.relation == "sneq") {
                    subSneq[key] = conditionConfigId;
                    if ($(conditionConfigId).is(":checked")) subSneqChecked = true;
                    //匹配的数据不相等 相等的值禁选择
                    $(conditionConfigId).attr("disabled", "disabled");
                    $(conditionConfigId).parent().addClass("disabled");
                    $(conditionConfigId).prop("checked", false);
                    $(conditionConfigId).parent().removeClass("active");
                }
            }

        });
        var checkedSubSeq = false, checkedSnubSeq = false;
        if ($(conditionConfig).length > 0) {
            //conditionConfig 类型结果判断
            $.each($(conditionConfig).find("option"), function () {
                if (subSeq.length > 0) {
                    //console.log(subSeq);
                    if ($(this).attr("disabled")) {
                        $(this).prop("selected", false);
                    } else if (!checkedSubSeq && subSeq[$(this).val()] && !subSeqChecked) {
                        $(this).prop("selected", true);
                        checkedSubSeq = true;
                    }
                } else if (subSneq.length > 0) {
                    if ($(this).attr("disabled")) {
                        $(this).prop("selected", false);
                    } else if (!checkedSnubSeq && subSneqChecked) {
                        $(this).prop("selected", true);
                        checkedSnubSeq = true;
                    }
                }

            });
        } else {
            //conditionConfigId 类型结果判断
            $.each(configoption, function (i, v) {
                if (subSeq.length > 0) {
                    //console.log(subSeq);
                    if ($(this).attr("disabled")) {
                        //console.log($(this).val());
                        $(this).prop("checked", false);
                        $(this).parent().removeClass("active");
                    } else if (!checkedSubSeq && subSeq[$(this).val()] && !subSeqChecked) {
                        $(this).prop("checked", true);
                        $(this).parent().addClass("active");
                        checkedSubSeq = true;
                    }
                } else if (subSneq.length > 0) {

                    if ($(this).attr("disabled")) {
                        $(this).prop("checked", false);
                        $(this).parent().removeClass("active");
                    } else if (!checkedSnubSeq && subSneqChecked) {
                        $(this).prop("checked", true);
                        $(this).parent().addClass("active");
                        checkedSnubSeq = true;
                    }
                }

            });
        }
    });
}


$(document).on('click', '.create_random_pass', function () {
    $('input[name="password"]').val(createRandPassword(Number(pwdRule.len_num), Number(pwdRule.num), Number(pwdRule.upper), Number(pwdRule.lower), Number(pwdRule.special)))
})
// /**
//  * 生成密码字符串
//  * 33~47：!~/
//  * 48~57：0~9
//  * 58~64：:~@
//  * 65~90：A~Z
//  * 91~96：[~`
//  * 97~122：a~z
//  * 123~127：{~
//  * @param length 长度
//  * @param hasNum 是否包含数字 1-包含 0-不包含
//  * @param hasUpper 是否包含大写字母 1-包含 0-不包含
//  * @param hasLower 是否包含小写字母 1-包含 0-不包含
//  * @param hasSpecial 是否包含特殊字符 1-包含 0-不包含
//  */
// function createRandPassword (length, hasNum, hasUpper, hasLower, hasSpecial) {
//     var pwd = ''
//     if (hasNum === 0 && hasUpper === 0 && hasLower === 0 && hasSpecial === 0) return pwd
//     for (var i = 0; i < length; i++) {
//         var num = Math.floor((Math.random() * 94) + 33)
//         console.log(num)
//         if (((hasNum === 0) && ((num >= 48) && (num <= 57))) ||
//             ((hasUpper === 0) && ((num >= 65) && (num <= 90))) ||
//             ((hasLower === 0) && ((num >= 97) && (num <= 122))) ||
//             ((hasSpecial === 0) && (((num >= 33) && (num <= 47)) || ((num >= 58) && (num <= 64)) || ((num >= 91) && (num <= 96)) || ((num >= 123) && (num <= 127))))
//         ) {
//             i--
//             continue
//         }
//         pwd += String.fromCharCode(num)
//         console.log(pwd)
//     }
//     return pwd
// }

// 20220301 删除%^
function createRandPassword (length, hasNum, hasUpper, hasLower, hasSpecial) {
    let num = '0123456789';
    let lower = 'abcdefghijklmnopqrstuvwxyz';
    let upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let special = '~@#$&*(){}[]|';
    let tail = '';
    let all = '';
    let str = '';
    if (hasUpper == 1) {
        tail += upper[parseInt(Math.random() * 26)];
        all += upper;
    }
    if (hasLower == 1) {
        tail += lower[parseInt(Math.random() * 26)];
        all += lower;
    }
    if (hasNum == 1) {
        tail += num[parseInt(Math.random() * 10)];
        all += num;
    }
    if (hasSpecial == 1) {
        tail += special[parseInt(Math.random() * 15)];
        all += special;
    }
    for (let i = 0; i < length - tail.length; i++) {
        str += all[parseInt(Math.random() * all.length)];
    }
    str = str + tail;
    return str;
}

// 查看密码
function showPwd () {
    if ($('input[name="password"]').attr('type') == 'password') {
        $('input[name="password"]').attr('type', 'text')
    } else {
        $('input[name="password"]').attr('type', 'password')
    }
}


// 自定义字段 验证必填
function checkListFormVerify (arr) {
    // console.log('arr: ', arr);
    var result = false
    arr.forEach(function (item) {
        if ($(item).attr('required') && $(item).val() == '') {
            $(item).addClass("is-invalid"); //添加非法状态
            result = true
        } else {
            $(item).removeClass("is-invalid");
        }
    })
    return result
}

/*
  正则匹配验证密码规则
  val：校验的值
  num：数字
  capital：大写字母
  lowercase：小写字母
  character： 特殊字符
  minLength：最小长度
  maxLength： 最大长度
  去掉了^和%的校验
*/
function checkingPwd1 (val, num, capital, lowercase, character, minLength = 6, maxLength = 20) {
   
    let checkNum = /.*[0-9]{1,}.*/; // 判断是否包含数字
    let checkCapital = /.*[a-z]{1,}.*/ // 判断是否包含小写字母
    let checkLc = /.*[A-Z]{1,}.*/ // 判断是否包含大写字母
    let checkCharacter = /[`~!@#$&^%*()_\-+=<>?:"{}|,.\/;'\\[\]·~！@#￥……&*（）——\-+={}|《》？：“”【】、；‘'，。、]/ //判断是否包含特殊字符
    let noSomeReg = /[\^%]+/ //是否包含^和%
    let msg = '密码由 ' // 提示语句
    let result = {}
    let flagNum = false, flagCap = false, flagLow = false, flagSpec = false;

    if (val.length < minLength) {
        //console.log("密码最短" + minLength + "位");
        result.msg = "密码最短" + minLength + "位 "
        result.flag = false
        return result
    }
    if (val.length > maxLength) {
        //console.log("密码最长" + maxLength + "位");
        result.msg = "密码最长" + maxLength + "位 "
        result.flag = false
        return result
    }
    if (parseInt(num)) {
        msg += '数字 '
        if (!checkNum.test(val)) {
            //console.log("缺少数字");
            flagNum = false
        } else {
            //console.log("有数字，正确！")
            flagNum = true
        }
    } else {
        if (checkNum.test(val)) {
            //console.log("不应该有数字");
            flagNum = false
        } else {
            //console.log("没有数字，正确！")
            flagNum = true
        }
    }
    if (parseInt(lowercase)) {
        msg += '小写字母 '
        if (!checkCapital.test(val)) {
            //console.log("缺少小写字母");
            flagLow = false
        } else {
            //console.log("有小写字母，正确！")
            flagLow = true
        }
    } else {
        if (checkCapital.test(val)) {
            //console.log("不应该有小写字母");
            flagLow = false
        } else {
            //console.log("没有小写字母，正确！")
            flagLow = true
        }
    }
    if (parseInt(capital)) {
        msg += '大写字母 '
        if (!checkLc.test(val)) {
            //console.log("缺少大写字母");
            flagCap = false
        } else {
            //console.log("有大写字母，正确！")
            flagCap = true
        }
    } else {
        if (checkLc.test(val)) {
            //console.log("不应该有大写字母");
            flagCap = false
        } else {
            //console.log("没有大写字母，正确！")
            flagCap = true
        }
    }
    
    if (parseInt(character)) {
        msg += '特殊字符 且不含^%'
        if (!checkCharacter.test(val)) {
            //console.log("缺少特殊字符");
            flagSpec = false
        } else {
            //console.log("有特殊字符，正确！")
            flagSpec = true
        }
        if (noSomeReg.test(val)) {
           
            flagSpec = false
        } else {
            
            flagSpec = true
        }
    } else {
        if (checkCharacter.test(val)) {
            // console.log("不应该有特殊字符222");
            flagSpec = false
        } else {
            // console.log("没有特殊字符，正确！")
            flagSpec = true
        }
      
    }
   
    result.msg = msg + ' 组成'
    result.flag = flagNum && flagCap && flagLow && flagSpec
    return result
}
/*
  正则匹配验证密码规则
  val：校验的值
  num：数字
  capital：大写字母
  lowercase：小写字母
  character： 特殊字符
  minLength：最小长度
  maxLength： 最大长度
*/
function checkingPwd (val, num, capital, lowercase, character, minLength = 6, maxLength = 20) {
    let checkNum = /.*[0-9]{1,}.*/; // 判断是否包含数字
    let checkCapital = /.*[a-z]{1,}.*/ // 判断是否包含小写字母
    let checkLc = /.*[A-Z]{1,}.*/ // 判断是否包含大写字母
    let checkCharacter = /[`~!@#$%^&*()_\-+=<>?:"{}|,.\/;'\\[\]·~！@#￥%……&*（）——\-+={}|《》？：“”【】、；‘'，。、]/ //判断是否包含特殊字符
    let msg = '密码由 ' // 提示语句
    let result = {}
    let flagNum = false, flagCap = false, flagLow = false, flagSpec = false;

    if (val.length < minLength) {
        //console.log("密码最短" + minLength + "位");
        result.msg = "密码最短" + minLength + "位 "
        result.flag = false
        return result
    }
    if (val.length > maxLength) {
        //console.log("密码最长" + maxLength + "位");
        result.msg = "密码最长" + maxLength + "位 "
        result.flag = false
        return result
    }
    if (parseInt(num)) {
        msg += '数字 '
        if (!checkNum.test(val)) {
            //console.log("缺少数字");
            flagNum = false
        } else {
            //console.log("有数字，正确！")
            flagNum = true
        }
    } else {
        if (checkNum.test(val)) {
            //console.log("不应该有数字");
            flagNum = false
        } else {
            //console.log("没有数字，正确！")
            flagNum = true
        }
    }
    if (parseInt(lowercase)) {
        msg += '小写字母 '
        if (!checkCapital.test(val)) {
            //console.log("缺少小写字母");
            flagLow = false
        } else {
            //console.log("有小写字母，正确！")
            flagLow = true
        }
    } else {
        if (checkCapital.test(val)) {
            //console.log("不应该有小写字母");
            flagLow = false
        } else {
            //console.log("没有小写字母，正确！")
            flagLow = true
        }
    }
    if (parseInt(capital)) {
        msg += '大写字母 '
        if (!checkLc.test(val)) {
            //console.log("缺少大写字母");
            flagCap = false
        } else {
            //console.log("有大写字母，正确！")
            flagCap = true
        }
    } else {
        if (checkLc.test(val)) {
            //console.log("不应该有大写字母");
            flagCap = false
        } else {
            //console.log("没有大写字母，正确！")
            flagCap = true
        }
    }
    if (parseInt(character)) {
        msg += '特殊字符 '
        if (!checkCharacter.test(val)) {
            //console.log("缺少特殊字符");
            flagSpec = false
        } else {
            //console.log("有特殊字符，正确！")
            flagSpec = true
        }
    } else {
        if (checkCharacter.test(val)) {
            //console.log("不应该有特殊字符");
            flagSpec = false
        } else {
            //console.log("没有特殊字符，正确！")
            flagSpec = true
        }
    }

    result.msg = msg + ' 组成'
    result.flag = flagNum && flagCap && flagLow && flagSpec
    return result
}
