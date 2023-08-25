<?php

namespace app\admin\controller;

/**
 * @title 后台自动任务
 * @description 接口说明
 */
class CronController extends AdminBaseController
{
	/**
	 * @title 自动任务设置
	 * @description 自动任务设置
	 * @author huanghao
	 * @url         admin/cron_page
	 * @method      GET
	 * @time        2019-12-06
	 * @return      .cron_command:CronCommand
	 * @return      .cron_day_start_time:每天定时任务开始时间
	 * @return      .cron_host_suspend:是否启用暂停功能
	 * @return      .cron_host_suspend_time:暂停时间(天)
	 * @return      .cron_host_suspend_send:是否发送暂停邮件
	 * @return      .cron_host_unsuspend:是否启用解除暂停
	 * @return      .cron_host_unsuspend_send:是否发送解除暂停邮件
	 * @return      .cron_host_terminate:是否启用删除
	 * @return      .cron_host_terminate_time:删除时间
	 * @return      .cron_invoice_create_default_days:生成到期账单前天数
	 * @return      .cron_invoice_create_hour:小时付
	 * @return      .cron_invoice_create_day:天付
	 * @return      .cron_invoice_create_monthly:月付
	 * @return      .cron_invoice_create_quarterly:季付
	 * @return      .cron_invoice_create_semiannually:半年付
	 * @return      .cron_invoice_create_annually:年付
	 * @return      .cron_invoice_create_biennially:两年付
	 * @return      .cron_invoice_create_triennially:三年付
	 * @return      .cron_invoice_create_fourly:四年付
	 * @return      .cron_invoice_create_fively:五年付
	 * @return      .cron_invoice_create_sixly:六年付
	 * @return      .cron_invoice_create_sevenly:七年付
	 * @return      .cron_invoice_create_eightly:八年付
	 * @return      .cron_invoice_create_ninely:九年付
	 * @return      .cron_invoice_create_tenly:十年付
	 * @return      .cron_invoice_pay_email:付款提醒邮件
	 * @return      .cron_invoice_unpaid_email:账单未付款提醒
	 * @return      .cron_invoice_first_overdue_email:第1次逾期提醒
	 * @return      .cron_invoice_second_overdue_email:第2次逾期提醒
	 * @return      .cron_invoice_third_overdue_email:第3次逾期提醒
	 * @return      .cron_ticket_close_time:关闭工单时间
	 * @return      .cron_client_delete:自动删除不活跃用户
	 * @return      .cron_client_delete_time:不活跃月份
	 * @return      .cron_other_cancel_request:取消服务请求
	 * @return      .cron_other_client_update:客户状态更新,0,1,2
	 * @return      .cron_last_run_time:上次自动任务执行开始时间
	 * @return      .cron_last_run_time_over:上次自动任务执行结束时间
	 * @return      .diff_run_time:自动任务执行了多久:状态，绿色 或者红色，如果上次时间小于等于5分钟，状态就是正常，否则就是异常的红色
	 * @return      .cron_host_terminate_high:是否开启产品删除功能高级设置
	 * @return      .cron_host_terminate_time_hostingaccount:虚拟主机删除时间
	 * @return      .cron_host_terminate_time_server:独立服务器删除时间
	 * @return      .cron_host_terminate_time_cloud:云服务器删除时间
	 * @return      .cron_host_terminate_time_dcimcloud:魔方云删除时间
	 * @return      .cron_host_terminate_time_dcim:魔方裸金属删除时间
	 * @return      .cron_host_terminate_time_software:软件产品删除时间
	 * @return      .cron_host_terminate_time_cdn:CDN删除时间
	 * @return      .cron_host_terminate_time_other:其他服务删除时间
	 * @return      .cron_credit_limit_suspend_time:暂停时间(信用额)
	 * @return      .cron_credit_limit_invoice_unpaid_email:账单未付款提醒(信用额)
	 * @return      .cron_credit_limit_invoice_first_overdue_email:第1次逾期提醒(信用额)
	 * @return      .cron_credit_limit_invoice_second_overdue_email:第2次逾期提醒(信用额)
	 * @return      .cron_credit_limit_invoice_third_overdue_email:第3次逾期提醒(信用额)
	 */
	public function detail()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			\compareLicense();
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			if (time() > $auth["last_license_time"] + 86400) {
				\compareLicense();
			}
		}
		$cron_config = config("cron_config");
		$data = getConfig(array_keys($cron_config));
		$data = array_merge($cron_config, $data);
		$data["cron_command"] = config("cron_command");
		$data["marking_cron_command"] = config("marking_cron_command");
		$data["cron_last_run_time_over"] = configuration("cron_last_run_time_over");
		if ($data["cron_last_run_time_over"] - $data["cron_last_run_time"] > 1200) {
			$data["diff_run_time"] = -1;
		} else {
			$data["diff_run_time"] = 1;
		}
		if (time() - $data["cron_last_run_time_over"] > 900) {
			$data["diff_run_time"] = -1;
		}
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 保存自动任务
	 * @description 保存自动任务
	 * @author huanghao
	 * @url         /admin/save_cron
	 * @method      POST
	 * @time        2019-12-06
	 * @param       .name:cron_day_start_time type:int require:0 default: other: desc:每天定时任务开始时间0-23对应0点到23点
	 * @param       .name:cron_host_suspend type:int require:0 default: other: desc:是否启用暂停功能,0禁用1启用
	 * @param       .name:cron_host_suspend_time type:int require:0 default: other: desc:暂停时间
	 * @param       .name:cron_host_suspend_send type:int require:0 default: other: desc:是否发送暂停邮件,0禁用1启用
	 * @param       .name:cron_host_unsuspend type:int require:0 default: other: desc:是否启用解除暂停,0禁用1启用
	 * @param       .name:cron_host_unsuspend_send type:int require:0 default: other: desc:是否发送解除暂停邮件,0禁用1启用
	 * @param       .name:cron_host_unsuspend_send type:int require:0 default: other: desc:是否发送解除暂停邮件,0禁用1启用
	 * @param       .name:cron_host_terminate type:int require:0 default: other: desc:是否启用删除,0禁用1启用
	 * @param       .name:cron_host_terminate_time type:int require:0 default: other: desc:删除时间
	 * @param       .name:cron_invoice_create_default_days type:string require:0 default: other: desc:生成到期账单前天数
	 * @param       .name:cron_invoice_create_hour type:string require:0 default: other: desc:小时付
	 * @param       .name:cron_invoice_create_day type:string require:0 default: other: desc:天付
	 * @param       .name:cron_invoice_create_monthly type:string require:0 default: other: desc:月付
	 * @param       .name:cron_invoice_create_quarterly type:string require:0 default: other: desc:季付
	 * @param       .name:cron_invoice_create_semiannually type:string require:0 default: other: desc:半年付
	 * @param       .name:cron_invoice_create_annually type:string require:0 default: other: desc:年付
	 * @param       .name:cron_invoice_create_biennially type:string require:0 default: other: desc:两年付
	 * @param       .name:cron_invoice_create_triennially type:string require:0 default: other: desc:三年付
	 * @param       .name:cron_invoice_create_fourly type:string require:0 default: other: desc:四年付
	 * @param       .name:cron_invoice_create_fively type:string require:0 default: other: desc:五年付
	 * @param       .name:cron_invoice_create_sixly type:string require:0 default: other: desc:六年付
	 * @param       .name:cron_invoice_create_sevenly type:string require:0 default: other: desc:七年付
	 * @param       .name:cron_invoice_create_eightly type:string require:0 default: other: desc:八年付
	 * @param       .name:cron_invoice_create_ninely type:string require:0 default: other: desc:九年付
	 * @param       .name:cron_invoice_create_tenly type:string require:0 default: other: desc:十年付
	 * @param       .name:cron_invoice_pay_email type:int require:0 default: other: desc:付款提醒邮件
	 * @param       .name:cron_invoice_unpaid_email type:int require:0 default: other: desc:账单未付款提醒
	 * @param       .name:cron_invoice_first_overdue_email type:int require:0 default: other: desc:第1次逾期提醒
	 * @param       .name:cron_invoice_second_overdue_email type:int require:0 default: other: desc:第2次逾期提醒
	 * @param       .name:cron_invoice_third_overdue_email type:int require:0 default: other: desc:第3次逾期提醒
	 * @param       .name:cron_ticket_close_time type:int require:0 default: other: desc:关闭工单时间
	 * @param       .name:cron_client_delete type:int require:0 default: other: desc:自动删除不活跃用户
	 * @param       .name:cron_client_delete_time type:int require:0 default: other: desc:不活跃月份
	 * @param       .name:cron_other_cancel_request type:int require:0 default: other: desc:取消服务请求
	 * @param       .name:cron_other_client_update type:int require:0 default: other: desc:客户状态更新,0,1,2
	 *
	 * @param       .name:cron_order_unpaid_time_high type:int require:0 default: other: desc:是否开启删除取消功能,0禁用1启用
	 * @param       .name:cron_order_unpaid_time type:int require:0 default: other: desc:订单未付款多少天后删除或取消
	 * @param       .name:cron_order_unpaid_action type:int require:0 default: other: desc:订单未付款多少天后动作,取消或者删除(Delete,Cancelled)
	 *
	 * @param       .name:cron_credit_limit_suspend_time type:int require:0 default: other: desc:暂停时间(信用额)
	 * @param       .name:cron_credit_limit_invoice_unpaid_email type:int require:0 default: other: desc:账单未付款提醒(信用额)
	 * @param       .name:cron_credit_limit_invoice_first_overdue_email type:int require:0 default: other: desc:第1次逾期提醒(信用额)
	 * @param       .name:cron_credit_limit_invoice_second_overdue_email type:int require:0 default: other: desc:第2次逾期提醒(信用额)
	 * @param       .name:cron_credit_limit_invoice_third_overdue_email type:int require:0 default: other: desc:第3次逾期提醒(信用额)
	 *
	 * @param       .name:cron_recharge_invoice_unpaid_delete type:int require:0 default: other: desc:是否开启自动删除未支付的充值账单功能,0禁用1启用
	 * @param       .name:cron_recharge_invoice_unpaid_delete_time type:int require:0 default: other: desc:未支付的充值账单多少天后删除
	 */
	public function saveCron()
	{
		$params = input("post.");
		$rule = ["cron_day_start_time" => "number|between:0,23", "cron_host_suspend_time" => "number", "cron_host_terminate_time" => "number", "cron_invoice_first_overdue_email" => "number", "cron_invoice_second_overdue_email" => "number", "cron_invoice_third_overdue_email" => "number", "cron_ticket_close_time" => "number", "cron_client_delete_time" => "number", "cron_other_client_update" => "in:0,1,2", "cron_host_terminate_high" => "in:0,1", "cron_host_terminate_time_hostingaccount" => "number", "cron_host_terminate_time_server" => "number", "cron_host_terminate_time_cloud" => "number", "cron_host_terminate_time_dcimcloud" => "number", "cron_host_terminate_time_dcim" => "number", "cron_host_terminate_time_cdn" => "number", "cron_host_terminate_time_other" => "number", "cron_credit_limit_suspend_time" => "number", "cron_credit_limit_invoice_unpaid_email" => "number", "cron_credit_limit_invoice_first_overdue_email" => "number", "cron_credit_limit_invoice_second_overdue_email" => "number", "cron_credit_limit_invoice_third_overdue_email" => "number", "cron_order_unpaid_time_high" => "in:0,1", "cron_invoice_recharge_delete" => "in:0,1", "cron_invoice_recharge_delete_time" => "number"];
		$msg = ["cron_day_start_time.number" => lang("FORMAT_ERROR"), "cron_day_start_time.between" => lang("FORMAT_ERROR"), "cron_host_suspend_time.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time.number" => lang("FORMAT_ERROR"), "cron_invoice_first_overdue_email.number" => lang("FORMAT_ERROR"), "cron_invoice_second_overdue_email.number" => lang("FORMAT_ERROR"), "cron_invoice_third_overdue_email.number" => lang("FORMAT_ERROR"), "cron_ticket_close_time.number" => lang("FORMAT_ERROR"), "cron_client_delete_time.number" => lang("FORMAT_ERROR"), "cron_other_client_update.in" => lang("FORMAT_ERROR"), "cron_host_terminate_high.in" => lang("FORMAT_ERROR"), "cron_host_terminate_time_hostingaccount.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_server.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_cloud.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_dcimcloud.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_dcim.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_cdn.number" => lang("FORMAT_ERROR"), "cron_host_terminate_time_other.number" => lang("FORMAT_ERROR"), "cron_credit_limit_suspend_time.number" => lang("FORMAT_ERROR"), "cron_credit_limit_invoice_unpaid_email.number" => lang("FORMAT_ERROR"), "cron_credit_limit_invoice_first_overdue_email.number" => lang("FORMAT_ERROR"), "cron_credit_limit_invoice_second_overdue_email.number" => lang("FORMAT_ERROR"), "cron_credit_limit_invoice_third_overdue_email.number" => lang("FORMAT_ERROR"), "cron_order_unpaid_time_high.in" => lang("FORMAT_ERROR"), "cron_invoice_recharge_delete.in" => lang("FORMAT_ERROR"), "cron_invoice_recharge_delete_time.number" => lang("FORMAT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$cron_config = config("cron_config");
		$data = getConfig(array_keys($cron_config));
		$data = array_merge($cron_config, $data);
		unset($data["cron_last_run_time"]);
		$dec = "";
		foreach ($data as $k => $v) {
			if (isset($params[$k]) && $v != $params[$k]) {
				$company_name = configuration($k);
				updateConfiguration($k, $params[$k]);
				if ($k == "cron_host_unsuspend") {
					if ($params[$k] == 1) {
						$dec .= " -  启用解除暂停功能";
					} else {
						$dec .= " -  禁用解除暂停功能";
					}
				} elseif ($k == "cron_host_unsuspend_send") {
					if ($params[$k] == 1) {
						$dec .= " -  发送解除暂停邮件";
					} else {
						$dec .= " -  发送解除不暂停邮件";
					}
				} elseif ($k == " cron_host_suspend_send") {
					if ($params[$k] == 1) {
						$dec .= " -  发送暂停邮件";
					} else {
						$dec .= " -  发送不暂停邮件";
					}
				} elseif ($k == "cron_host_suspend") {
					if ($params[$k] == 1) {
						$dec .= " -  启用暂停功能";
					} else {
						$dec .= " -  不启用暂停功能";
					}
				} elseif ($k == "cron_host_suspend_time") {
					$dec .= " - 暂停时间:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_host_terminate_time") {
					$dec .= " - 删除时间:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_day_start_time") {
					$dec .= " - 每天何时:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_default_days") {
					$dec .= " - 生成账单:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_pay_email") {
					if ($params[$k] == 1) {
						$dec .= " -  付款提醒邮件";
					} else {
						$dec .= " -  付款提醒邮件";
					}
				} elseif ($k == "cron_invoice_pay_email") {
					if ($params[$k] == 1) {
						$dec .= " -  付款提醒邮件";
					} else {
						$dec .= " -  关闭付款提醒邮件";
					}
				} elseif ($k == "cron_invoice_create_hour") {
					$dec .= " - 小时付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_day") {
					$dec .= " - 天付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_monthly") {
					$dec .= " - 月付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_quarterly") {
					$dec .= " - 季付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_semiannually") {
					$dec .= " - 半年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_annually") {
					$dec .= " - 年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_biennially") {
					$dec .= " - 两年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_triennially") {
					$dec .= " - 三年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_fourly") {
					$dec .= " - 四年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_fively") {
					$dec .= " - 五年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_sixly") {
					$dec .= " - 六年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_sevenly") {
					$dec .= " - 七年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_eightly") {
					$dec .= " - 八年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_ninely") {
					$dec .= " - 九年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_create_tenly") {
					$dec .= " - 十年付:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_unpaid_email") {
					$dec .= " - 账单未付款提醒天数:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_first_overdue_email") {
					$dec .= " - 第 1 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_second_overdue_email") {
					$dec .= " - 第 2 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_third_overdue_email") {
					$dec .= " - 第 3 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_ticket_close_time") {
					$dec .= " - 关闭工单:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_order_unpaid_time_high") {
					if ($params[$k] == 1) {
						$dec .= " -  删除取消功能启用";
					} else {
						$dec .= " -  删除取消功能关闭";
					}
					$dec .= " - 删除取消:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_order_unpaid_time") {
					$dec .= " - 删除取消:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_order_unpaid_action:Delete") {
					$dec .= " - 操作类型:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_credit_limit_suspend_time") {
					$dec .= " - 信用额账单未付款到期暂停天数:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_credit_limit_invoice_unpaid_email") {
					$dec .= " - 信用额账单未付款提醒天数:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_credit_limit_invoice_first_overdue_email") {
					$dec .= " - 信用额账单第 1 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_credit_limit_invoice_second_overdue_email") {
					$dec .= " - 信用额账单第 2 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_credit_limit_invoice_third_overdue_email") {
					$dec .= " - 信用额账单第 3 次逾期提醒:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_recharge_delete") {
					if ($params[$k] == 1) {
						$dec .= " -  自动删除未支付的充值账单功能启用";
					} else {
						$dec .= " -  自动删除未支付的充值账单功能关闭";
					}
					$dec .= " - 自动删除未支付的充值账单:" . $company_name . "改为" . $params[$k];
				} elseif ($k == "cron_invoice_recharge_delete_time") {
					$dec .= " - 自动删除未支付的充值账单天数:" . $company_name . "改为" . $params[$k];
				} else {
					$dec .= " - " . $k . ":" . $company_name . "改为" . $params[$k];
				}
			}
		}
		hook("cron_config_save", ["adminid" => cmf_get_current_admin_id()]);
		active_log(sprintf($this->lang["Cron_home_editCron"], $dec));
		unset($dec);
		unset($company_name);
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
}