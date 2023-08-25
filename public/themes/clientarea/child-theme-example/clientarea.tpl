<script src="/themes/clientarea/default/assets/libs/echarts/echarts.min.js?v={$Ver}"></script>
<script>
  $(function () {
    var credit = '{$ClientArea.index.client.credit}' // 余额
    var unpaid = '{$ClientArea.index.invoice_unpaid}' // 未支付
    var creditNum = parseFloat(credit.replace('{$Lang.element}', ''))
    var unpaidNum = parseFloat(unpaid.replace('{$Lang.element}', ''))

    var percentage = parseFloat(creditNum / (creditNum + unpaidNum)).toFixed(2)

    if (creditNum === 0 || (creditNum === 0 && unpaidNum === 0)) {
      percentage = 0
    }
    var myChart = echarts.init(document.getElementById("balanceCharts"));
    var
      option = {
        title: {
          text: '{a|' + credit + '}\n\n{c|' + '{$Lang.current_balance}' + '}',
          x: 'center',
          y: 'center',
          textStyle: {
            rich: {
              a: {
                fontSize: 18,
                color: '#007bfc'
              },
              c: {
                fontSize: 12,
                color: '#000000',
                padding: [5, 0]
              },

            }
          }
        },
        series: [
          {
            type: 'gauge',
            radius: '80%',
            clockwise: true,
            startAngle: '270',
            endAngle: '-89.9999',
            //调整间隔距离
            splitNumber: 0,
            detail: {
              offsetCenter: [0, -20],
              formatter: ' '
            },
            pointer: {
              show: false
            },
            axisLine: {
              show: true,
              lineStyle: {
                color: [
                  [0, '#228cfc'],
                  //计算比例
                  [percentage, '#228cfc'],

                  [1, '#efefef']
                ],
                width: 9
              }
            },
            axisTick: {
              show: false
            },
            splitLine: {
              show: false,
              length: 32,
              lineStyle: {
                color: '#fff',
                width: 6
              }
            },
            axisLabel: {
              show: false
            }
          }
        ]
      };
    myChart.setOption(option, true);

    // 资源列表
    getSourceList()
  })

  function getSourceList() {
    $('#sourceListBox').html('<div class="h-100 d-flex align-items-center justify-content-center">{$Lang.data_loading}......</div>')
    $.ajax({
      type: "get",
      url: '' + '/clientarea',
      data: {
        action: 'list'
      },
      success: function (data) {
        $('#sourceListBox').html(data)
      }
    });
  }

</script>

<div class="row">
  <!-- start：个人信息 -->
  <section class="col-md-12 col-xl-4">
    <div class="card card-body user-center_h300 p-0 pb-3">
      <!-- old -->
      <!-- <div class="d-flex align-items-center justify-content-center mb-4">
        <div class="mr-4 d-flex align-items-center justify-content-center user-center_header">{if
          preg_match("/[\x7f-\xff]/", substr($Userinfo.user.username,0,3))} {$Userinfo.user.username|substr=0,3} {else}
          {$Userinfo.user.username|substr=0,1|upper} {/if}</div>
        <div class="ml-20">
          <p>
            <span class="user-center_name">{$Userinfo.user.username}</span>
            <span>ID:{$Userinfo.user.id}</span>
          </p>
          <span>{if
            $Userinfo.user.phonenumber}{$Userinfo.user.phonenumber|substr=0,3}***{$Userinfo.user.phonenumber|substr=7}{/if}</span>
        </div>
      </div>
      <div class="d-flex justify-content-around user-center_safety_wrapper mb-3">
        <a href="security" class="user-center_safety" {if $Userinfo.user.certifi.status!=1}
          style="background-color:#aaa;" {/if}>
          <i class="bx bx-check-shield"></i>
        </a>
        <a href="security" class="user-center_safety" {if !$Userinfo.user.phonenumber} style="background-color:#aaa;"
          {/if}>
          <i class="bx bx-phone-call"></i>
        </a>
        <a href="security" class="user-center_safety" {if !$Userinfo.user.email} style="background-color:#aaa;" {/if}>
          <i class="bx bx-mail-send"></i>
        </a>
      </div> -->
      <!-- old -->
      <div class="h-75">
        <div class="h-75 d-flex align-items-center justify-content-around flex-column" style="margin-top:20px">
          <div class=" d-flex align-items-center justify-content-center" style="">
            <div
              style="background-color: #fff;position: relative;left:-15px;top: 8px; width: 62px;height: 62px;border-radius: 50%;padding: 3px">
              <div class="mr-4 d-flex align-items-center justify-content-center user-center_header">
                {if preg_match("/^[0-9]*[A-Za-z]+$/is", substr($Userinfo.user.username,0,1))} 
                  {$Userinfo.user.username|substr=0,1|upper} 
                {elseif preg_match("/^[\x7f-\xff]*$/", substr($Userinfo.user.username,0,3))} 
                  {$Userinfo.user.username|substr=0,3}
                {else}
                  {$Userinfo.user.username|substr=0,1|upper} 
                {/if}
              </div>
            </div>
            <div class="ml-20">
              <div>
                <span class="user-center_name">{$Userinfo.user.username}</span>
                <span>ID:{$Userinfo.user.id}</span>
              </div>
              <span>{if
                $Userinfo.user.phonenumber}{$Userinfo.user.phonenumber|substr=0,3}***{$Userinfo.user.phonenumber|substr=7}{/if}</span>
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-around user-center_safety_wrapper" style="margin-bottom:-25px">
            <a href="security" class="user-center_safety" {if $Userinfo.user.certifi.status!=1}
              style="background-color:#aaa;" {/if}>
              <i class="bx bx-check-shield"></i>
            </a>
            <a href="security" class="user-center_safety" {if !$Userinfo.user.phonenumber}
              style="background-color:#aaa;" {/if}>
              <i class="bx bx-phone-call"></i>
            </a>
            <a href="security" class="user-center_safety" {if !$Userinfo.user.email} style="background-color:#aaa;"
              {/if}>
              <i class="bx bx-mail-send"></i>
            </a>
          </div> 
        </div>
      </div>
      <hr>
      <div class="d-flex justify-content-around align-items-center h-25">
        <a href="supporttickets" class="text-dark">
          <div class="text-center text-warning">{$ClientArea.index.ticket_count}</div>
          <span>{$Lang.pending_work_order}</span>
        </a>
        <a href="billing" class="text-dark">
          <div class="text-center text-primary">{$ClientArea.index.order_count}</div>
          <span>{$Lang.unpaid_order}</span>
        </a>
        <div>
          <div class="text-center text-danger">{$ClientArea.index.host}</div>
          <span>{$Lang.number_of_products}</span>
        </div>
      </div>
    </div>
  </section>

  <!-- start：财务信息 -->
  <section class="col-md-12 col-xl-4">
    <div class="card card-body user-center_h300">
      <div class="d-flex h100p">
        <div class="d-flex flex-column align-items-center justify-content-center flex1">
          <div class="w-100 h-100 d-flex justify-content-center" id="balanceCharts"></div>
        </div>
        <div class="d-flex flex-column justify-content-center flex1">
          <span class="d-inline-flex fz-12 text-black-50 mb-2">{$Lang.records_of_consumption}</span>
          <span class="d-inline-flex fz-14 text-black-80">{$Lang.consumption_this_month}：{$ClientArea.index.intotal}</span>
          <span class="d-inline-flex fz-12 text-black-50 mt-4 mb-2">{$Lang.order_records}</span>
          <span class="d-inline-flex fz-14 text-black-80">{$Lang.unpaid}：{$ClientArea.index.invoice_unpaid}</span>
          {if $ClientArea.index.allow_recharge == '1'}
          <a href="/addfunds" class="btn btn-primary mt-5 w-50">{$Lang.recharge}</a>
          {/if}
        </div>
      </div>
    </div>
  </section>

  <!-- start：已开通产品 -->
  <section class="col-md-12 col-xl-4">
    <div class="card card-body user-center_h300">
      <h4 class="card-title mt-0">{$Lang.products_launched_all}</h4>
      <div class="user-center_product_grid mt-3">
        {foreach $ClientArea.index.host_nav as $list}
        <a href="service?groupid={$list.id}" class="user-center_product">
          <span>
            <i class="bx bxs-grid-alt"></i>
            {$list.groupname}
          </span>
          <span>({$list.count})</span>
        </a>
        {/foreach}
      </div>
    </div>
  </section>


  <!-- start：资源列表 -->
  <section class="col-md-12 col-xl-8">

    <div class="card card-body user-center_calc" id="sourceListBox">
		测试子主题，本主题其它内容，会自动引用default主题内容
    </div>
  </section>


  <!-- start：公告通知 -->
  <section class="col-md-12 col-xl-4">
    <div class="card card-body user-center_calc">
      <h4 class="mb-4 card-title d-flex justify-content-between">
        <span>{$Lang.announcement}</span>
        <a href="news" class="fs-12 font-weight-normal">{$Lang.view_more}</a>
      </h4>
      <div class="user-center_notice h100p">
        <ul class="user-center_notice_ul pl-0">
          {if $ClientArea.index.news}
          {foreach $ClientArea.index.news as $list}
          <li class="user-center_notice_item">
            <span class="notice_item_time text-black-50">{$list.create_time|date="Y-m-d H:i"}</span>
            <a href="newsview?id={$list.id}" class="notice_item_title">{$list.title}</a>
          </li>
          {/foreach}
          {else}
          <tr>
            <td colspan="2">
              <div class="no-data">{$Lang.nothing}</div>
            </td>
          </tr>
          {/if}
        </ul>
      </div>
    </div>
  </section>
</div>