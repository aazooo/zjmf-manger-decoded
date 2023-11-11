{include file="includes/tablestyle"}

{include file="includes/deleteConfirm"}
{if $ErrorMsg}
    {include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
    {include file="error/notifications" value="$SuccessMsg" url=""}
{/if}
<style>
</style>

<!-- <div class="modal-backdrop fade show" style="display:none"></div> -->
<script>
    // $('#pullModal').modal('show');
    // $('#pullModal').modal('hide');
</script>
<script>
    window.onload = function() {
        var oldURL = document.referrer;
        var allargs = oldURL.split("=")[1];//配合push检测
        var nowAllargs = '{$hostid}';//push检测
        var is_open_pull_div = '{$is_open_pull_div}';//pull检测
        var pay_invoice_id = '{$pay_invoice_id}';//支付检测

        //PUSH模态框的唤醒
        if (allargs == nowAllargs) {
            $('#pushModal').modal('show');
        }

        //模拟点击唤醒支付窗口
        if (pay_invoice_id>0) {
            // IE
            if(document.all) {
                document.getElementById(pay_invoice_id).click();
            }
            //  兼容其它浏览器
            else {
                var e = document.createEvent("MouseEvents");
                e.initEvent("click", true, true);
                document.getElementById(pay_invoice_id).dispatchEvent(e);
            }
        }else {
            //PULL模态框的唤醒 - 唤醒权限在支付窗口之下
            if (is_open_pull_div == 1 ) {
                $('#pullModal').modal('show');
            }
        }
    }

    $('#search').blur(function () {
        var search = this.value;
        $.ajax({
            type: "POST",
            url: '/product_divert/postNameToUser',
            data: {
                tranfer_name: search
            },
            dataType: "json",
            success: function (res) {
                if (res.status == 200) {
                    var name = res.data.username;
                    var long = name.length;
                    var start = Math.floor(long / 2);
                    var nametext = name.substring(-1, start);
                    for (var i = 0; i < start; i++) {
                        nametext += '*';
                    }
                    $('#userid').val(res.data.id);
                    $('#usertext').html(nametext);
                    $("#usertext").css("color", "red");
                    $("#submit").show();
                } else {
                    alert(res.msg);
                    $('#usertext').html('');
                    $('#userid').val('');
                    $("#submit").hide();
                }
            }
        });
    });

    $("#cancel").on('click',function(params) {
        window.history.go(-1);
    });

    $("#cancel1").on('click',function(params) {
        window.history.go(-1);
    });

</script>
<script src="/themes/clientarea/default/assets/js/billing.js?v={$Ver}"></script>