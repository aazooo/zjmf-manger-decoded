<?php

namespace app\admin\command;

class Cron extends \think\console\Command
{
	protected function configure()
	{
		$this->setName("cron");
	}
	public function execute(\think\console\Input $input, \think\console\Output $output)
	{
		$output->writeln("自动任务开始:" . date("Y-m-d H:i:s"));
		$config = $this->getCronConfig();
		if (!configuration("cron_current_time_first_once")) {
			updateConfiguration("cron_current_time_first_once", time());
		}
		if (86400 - intval(configuration("cron_current_time_first_once")) + $config["cron_day_start_time"] > time()) {
			return null;
		}
		updateConfiguration("cron_last_run_time", time());
		hook("before_cron");
		if (configuration("cron_last_run_lock_status")) {
			updateConfiguration("cron_last_run_time", time());
			updateConfiguration("cron_last_run_time_over", time());
			$output->writeln("自动任务结束:" . date("Y-m-d H:i:s"));
			if (time() - configuration("cron_last_run_time_over_in") >= 600) {
				updateConfiguration("cron_last_run_lock_status", 0);
			}
			return null;
		}
		updateConfiguration("cron_last_run_lock_status", 1);
		$this->dailyCronJob($config);
		$this->hourlyCronJob($config);
		$this->halfHourCronJob($config, $output);
		$this->fiveMinuteCronJob($config);
		$this->onlyOnce();
		updateConfiguration("cron_last_run_time_over", time());
		updateConfiguration("cron_last_run_time_over_in", time());
		updateConfiguration("cron_last_run_lock_status", 0);
		hook("after_cron");
		$output->writeln("自动任务结束:" . date("Y-m-d H:i:s"));
	}
	public function fiveMinuteCronJob($config)
	{
		$this->hostInfo();
		hook("after_five_minute_cron");
	}
	public function halfHourCronJob($config, $output)
	{
		$this_time = time();
		if (($this_time - $config["last_halfhourcron_invocation_time"] ?? 0) < 1800) {
			return null;
		}
		updateConfiguration("last_halfhourcron_invocation_time", time());
		$this->updateHostFlow();
		$output->writeln("更新流量结束:" . date("Y-m-d H:i:s"));
		$this->updateDcimCloudHostFlow();
		$output->writeln("更新魔方云流量结束:" . date("Y-m-d H:i:s"));
		$this->UsageUpdate();
		$output->writeln("模块定时任务结束:" . date("Y-m-d H:i:s"));
		$this->updateUpstreamBw();
		$output->writeln("更新代理商品流量结束:" . date("Y-m-d H:i:s"));
		$this->host($config);
		$output->writeln("自动暂停、删除结束:" . date("Y-m-d H:i:s"));
		$this->moduleCron("FiveMinuteCron");
		$output->writeln("模块五分钟定时任务结束:" . date("Y-m-d H:i:s"));
		$this->contractHostSuspended();
		$output->writeln("未签订合同产品暂停结束:" . date("Y-m-d H:i:s"));
		$this->syncUpstreamProductInfo();
		$output->writeln("同步商品信息结束:" . date("Y-m-d H:i:s"));
		hook("after_half_hour_minute_cron");
	}
	public function hourlyCronJob($config)
	{
		$this_time = time();
		if (($this_time - $config["last_hourlycron_invocation_time"] ?? 0) < 3600) {
			return null;
		}
		updateConfiguration("last_hourlycron_invocation_time", time());
	}
	public function dailyCronJob($config)
	{
		$this_time = time();
		if (($this_time - $config["last_dailycron_invocation_time"] ?? 0) < 86400 || date("G") < $config["cron_day_start_time"]) {
			return null;
		}
		$time_day = strtotime(date("Y-m-d")) + intval($config["cron_day_start_time"]) * 60 * 60;
		if (time() < $time_day || time() > $time_day + 900) {
			return null;
		}
		if (date("Y-m-d", $config["last_dailycron_invocation_time"]) == date("Y-m-d")) {
			return null;
		}
		hook("before_daily_cron");
		updateConfiguration("last_dailycron_invocation_time", time());
		$this->invoice($config);
		$this->ticket($config);
		$this->dcimFlowCls();
		$this->dcimCloudFlowCls();
		$this->order($config);
		$this->unCertifi($config);
		$this->updateCommission();
		$this->cancellations();
		$this->moduleCron("DailyCron");
		$this->deleteLogs();
		$this->generateRepaymentBill();
		$this->creditLimitInvoice($config);
		$this->creditLimit($config);
		hook("after_daily_cron");
		$path = "/tmp/session";
		if (is_dir($path)) {
			$dirs = scandir($path);
			foreach ($dirs as $dir) {
				if ($dir != "." && $dir != "..") {
					$filename = $path . "/" . $dir;
					$day = strtotime(date("Y-m-d 00:00:00"));
					$filetime = fileatime($filename);
					if ($filetime < $day) {
						@unlink($filename);
					}
				}
			}
		}
	}
	public function onlyOnce()
	{
		if (configuration("get_hot_order_only_once")) {
			return null;
		}
		$this->getHotOrder();
		updateConfiguration("get_hot_order_only_once", 1);
		if (configuration("update_dcim_only_once")) {
			return null;
		}
		$this->updateDcim();
		updateConfiguration("update_dcim_only_once", 1);
	}
	public function dataRetentionPruning()
	{
	}
	public function currencyRatesUpdate($config)
	{
	}
	public function cancellations()
	{
		$cancels = \think\Db::name("cancel_requests")->field("id,relid")->where("type", "Immediate")->where("delete_time", 0)->select()->toArray();
		$host_logic = new \app\common\logic\Host();
		foreach ($cancels as $cancel) {
			$hid = $cancel["relid"];
			$host = \think\Db::name("host")->where("id", $hid)->find();
			if ($host["domainstatus"] != "Deleted") {
				$result = $host_logic->terminate($hid);
				$logic_run_map = new \app\common\logic\RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $hid;
				$data_i["active_type_param"] = [$hid, "cron"];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 用户提交 - 删除 Host ID:{$data_i["host_id"]}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 4);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 100, 4);
					}
				} else {
					$data_i["description"] = " 用户提交 - 删除 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 4);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 100, 4);
					}
				}
				if ($result["status"] == 200) {
					\think\Db::name("cancel_requests")->where("id", $cancel["id"])->update(["delete_time" => time(), "status" => 1]);
				} else {
					\think\Db::name("cancel_requests")->where("id", $cancel["id"])->update(["status" => 2]);
				}
			}
		}
	}
	public function atonSuspensions()
	{
	}
	public function fixedTermination()
	{
	}
	public function updateServerUsage()
	{
	}
	public function atonUserStatusSync()
	{
	}
	public function autoClientStatusSync()
	{
	}
	public function databaseBackup($config)
	{
		$res_ftp = 0;
		$res_email = 0;
		$backup_class = new \app\admin\lib\Backup();
		try {
			$filename = $backup_class->backupAll(DATABASE_DOWN_PATH, config("database.database"));
			$local_file = DATABASE_DOWN_PATH . $filename;
		} catch (\Exception $e) {
			active_log_final("备份数据库失败：" . $e->getMessage(), 0, 5);
			return 0;
		}
		if ($config["daily_ftp_backup_status"]) {
			$ftp_backup_hostname = $config["ftp_backup_hostname"];
			$ftp_backup_port = $config["ftp_backup_port"];
			$ftp_backup_username = $config["ftp_backup_username"];
			$ftp_backup_password = cmf_decrypt($config["ftp_backup_password"]);
			$ftp_backup_destination = $config["ftp_backup_destination"];
			$resource = ftp_connect($ftp_backup_hostname, $ftp_backup_port, 10);
			if ($resource) {
				$remote_file = $ftp_backup_destination . $filename;
				$res = ftp_put($resource, $remote_file, $local_file, FTP_BINARY);
				if ($res) {
					$res_ftp = 1;
					active_log_final("备份数据库：成功，文件名 - " . $filename, 0, 5);
				} else {
					active_log_final("备份数据库 - 提交到远程FTP失败", 0, 5);
				}
			} else {
				active_log_final("备份数据库：连接FTP失败", 0, 5);
			}
		}
		if ($config["daily_email_backup_status"]) {
			$daily_email_backup = $config["daily_email_backup"];
			$message = date("Y-m-d H:i:s") . ":数据库备份已发送至您的邮箱，请妥善保存，用于数据恢复";
			$attachments = $local_file . "^" . $filename;
			$email_logic = new \app\common\logic\Email();
			$email_logic->is_admin = true;
			$email_logic->sendEmailDirct($daily_email_backup, "数据库备份", $message, $attachments);
			active_log("数据库备份 - 成功将数据库备份发送至设置邮箱", 0, 5);
			$res_email = 1;
		}
		if ($res_ftp && $res_email) {
			return 1;
		}
		return 0;
	}
	public function runJobsQueue()
	{
	}
	public function checkForCwxtUpdate()
	{
	}
	public function hostInfo()
	{
		$pushhost = \think\Db::name("zjmf_pushhost")->field("host_id,url,post_data")->where("status", 0)->where("num", "<", 5)->select()->toArray();
		foreach ($pushhost as $v) {
			$res = commonCurl($v["url"], json_decode($v["post_data"], true), 30);
			if ($res["status"] == 200) {
				$update = ["status" => 1, "time" => time(), "num" => $v["num"] + 1];
			} else {
				$update = ["status" => 0, "time" => time(), "num" => $v["num"] + 1];
			}
			\think\Db::name("zjmf_pushhost")->where("id", $v["id"])->update($update);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domainstatus,a.regdate,a.dcimid,b.welcome_email,b.type,a.billingcycle,b.pay_type,b.name,a.nextduedate,a.billingcycle,a.dedicatedip,a.domain,a.username,a.password,a.os,a.assignedips,a.create_time,a.stream_info,b.api_type,b.zjmf_api_id,b.upstream_pid,b.server_group")->leftJoin("products b", "a.productid=b.id")->where("a.domainstatus", "=", "Pending")->where("b.api_type", "=", "resource")->where("a.regdate", "<", time() - 300)->select()->toArray();
		$curl_multi_data = [];
		if (!empty($host)) {
			foreach ($host as $k => $v) {
				if (!empty($v["dcimid"])) {
					$post_data = [];
					$post_data["host_id"] = $v["dcimid"];
					$module_res = resourceCurl($v["productid"], "/host/header", $post_data, 30, "GET");
					if ($module_res["status"] == 200 && $module_res["data"]["host_data"]["domainstatus"] == "Active") {
						$update = [];
						$update["dedicatedip"] = $module_res["data"]["host_data"]["dedicatedip"] ?? "";
						$update["assignedips"] = implode(",", $module_res["data"]["host_data"]["assignedips"]) ?? "";
						$update["domain"] = $module_res["data"]["host_data"]["domain"] ?? "";
						$update["username"] = $module_res["data"]["host_data"]["username"] ?? "";
						$update["password"] = cmf_encrypt($module_res["data"]["host_data"]["password"]);
						$update["port"] = \intval($module_res["data"]["host_data"]["port"]);
						$update["os"] = $module_res["data"]["host_data"]["os"];
						if ($module_res["data"]["host_data"]["nextduedate"]) {
							$update["nextduedate"] = $module_res["data"]["host_data"]["nextduedate"];
						}
						$update["domainstatus"] = $module_res["data"]["host_data"]["domainstatus"];
						$update_pushhost = ["status" => 1, "time" => time()];
						\think\Db::name("zjmf_pushhost")->where("id", $v["id"])->update($update_pushhost);
						\think\Db::name("host")->where("id", $v["id"])->update($update);
						pushHostInfo($v["id"]);
					}
				} else {
					$curl_multi_data[$k] = ["url" => "async_create", "data" => ["hid" => $v["id"]]];
				}
			}
		}
		if (count($curl_multi_data) > 0) {
			$zjmf_authorize = configuration("zjmf_authorize");
			$auth = \de_authorize($zjmf_authorize);
			request()->queue_domain = $auth["domain"];
			asyncCurlMulti($curl_multi_data);
		}
	}
	public function host($config)
	{
		$logic_host = new \app\common\logic\Host();
		$logic_host->is_admin = true;
		$now = time();
		$logic_run_map = new \app\common\logic\RunMap();
		$model_host = new \app\common\model\HostModel();
		if (!empty($config["cron_host_suspend"])) {
			$time = intval($config["cron_host_suspend_time"]);
			$send = $config["cron_host_suspend_send"] ?? 0;
			$host = \think\Db::name("host")->field("id,uid,overideautosuspend,overidesuspenduntil")->whereIn("domainstatus", "Active")->where("nextduedate", ">", 0)->where("nextduedate", "<=", time() - $time * 24 * 3600)->where("billingcycle", "<>", "free")->select();
			$reason = lang("SERVICE_HAS_EXPIRED");
			if (!empty($host[0])) {
				foreach ($host as $v) {
					if ($v["overideautosuspend"] > 0 && $now < $v["overidesuspenduntil"]) {
						continue;
					}
					$result = $logic_host->suspend($v["id"], "due", $reason, $send);
					if ($result["status"] == 200) {
						active_log_final("产品到期暂停 - 暂停产品 Host ID:" . $v["id"] . "成功", $v["uid"], 5);
						$data_i["host_id"] = $v["id"];
						$data_i["description"] = "产品到期暂停 Host ID:{$v["id"]}成功";
						$data_i["active_type_param"] = [$v["id"], "due", $reason, $send];
						$is_zjmf = $model_host->isZjmfApi($v["id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 2);
						}
						$this->ad_log(1, 2, 1, $v["id"]);
						$logic_run_map->cronSuccess(time(), 1, $v["id"]);
					} else {
						active_log_final("产品到期暂停 - 暂停产品 Host ID:" . $v["id"] . "失败,失败原因:" . $result["msg"], $v["uid"], 5);
						$data_i["host_id"] = $v["id"];
						$data_i["description"] = "产品到期暂停 Host ID:{$v["id"]}失败。原因:{$result["msg"]}";
						$data_i["active_type_param"] = [$v["id"], "due", $reason, $send];
						$is_zjmf = $model_host->isZjmfApi($v["id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 2);
						}
						$this->ad_log(1, 2, 0, $v["id"]);
					}
				}
			} else {
				active_log_final("产品到期暂停 - 无到期产品", 0, 5);
			}
		}
		$invoice_logic = new \app\common\logic\Invoices();
		if (!empty($config["cron_host_terminate"])) {
			$product_type = array_keys(config("product_type"));
			if ($config["cron_host_terminate_high"]) {
				$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.suspend_time,b.type,a.nextduedate")->leftJoin("products b", "a.productid = b.id")->where(function (\think\db\Query $query) {
					$query->where("a.domainstatus", "Suspended")->whereOr("a.domainstatus", "Active");
				})->where("a.nextduedate", ">", 0)->select()->toArray();
				if (!empty($host[0])) {
					foreach ($host as $v) {
						foreach ($product_type as $vv) {
							if ($v["type"] == $vv) {
								$cron_name = "cron_host_terminate_time_{$vv}";
								$time = intval($config[$cron_name]);
								if ($v["nextduedate"] <= time() - $time * 24 * 3600) {
									$result1 = $logic_host->terminate($v["id"], "cron");
									if ($result1["status"] == 200) {
										$invoice_logic->cancelInvoices($v["id"]);
										\think\Db::name("cancel_requests")->where("relid", $v["id"])->update(["delete_time" => time(), "status" => 1]);
										active_log_final("产品到期后删除 - 删除到期产品 Host ID:" . $v["id"] . "成功", $v["uid"], 5);
										$data_i["host_id"] = $v["id"];
										$data_i["description"] = "产品到期删除 Host ID:{$v["id"]}成功";
										$data_i["active_type_param"] = [$v["id"], "cron"];
										$is_zjmf = $model_host->isZjmfApi($v["id"]);
										if ($is_zjmf) {
											$logic_run_map->saveMap($data_i, 1, 400, 4);
										}
										if (!$is_zjmf) {
											$logic_run_map->saveMap($data_i, 1, 100, 4);
										}
										$this->ad_log(4, 4, 1, $v["id"]);
										$logic_run_map->cronSuccess(time(), 4, $v["id"]);
									} else {
										active_log_final("产品到期后删除 - 删除到期产品 Host ID:" . $v["id"] . "失败,失败原因:" . $result1["msg"], $v["uid"], 5);
										$data_i["host_id"] = $v["id"];
										$data_i["description"] = "产品到期删除 Host ID:{$v["id"]}失败。原因:{$result["msg"]}";
										$data_i["active_type_param"] = [$v["id"], "cron"];
										$is_zjmf = $model_host->isZjmfApi($v["id"]);
										if ($is_zjmf) {
											$logic_run_map->saveMap($data_i, 0, 400, 4);
										}
										if (!$is_zjmf) {
											$logic_run_map->saveMap($data_i, 0, 100, 4);
										}
										$this->ad_log(4, 4, 0, $v["id"]);
									}
								}
							}
						}
					}
				} else {
					active_log_final("产品到期后删除 - 无到期产品", 0, 5);
				}
			} else {
				$time = intval($config["cron_host_terminate_time"]);
				$host = \think\Db::name("host")->field("id,uid")->where(function (\think\db\Query $query) {
					$query->where("domainstatus", "Suspended")->whereOr("domainstatus", "Active");
				})->where("nextduedate", ">", 0)->where("nextduedate", "<=", time() - $time * 24 * 3600)->select()->toArray();
				if (!empty($host[0])) {
					foreach ($host as $v) {
						$result1 = $logic_host->terminate($v["id"], "cron");
						if ($result1["status"] == 200) {
							$invoice_logic->cancelInvoices($v["id"]);
							\think\Db::name("cancel_requests")->where("relid", $v["id"])->update(["delete_time" => time(), "status" => 1]);
							active_log_final("产品到期后删除 - 删除到期产品 Host ID:" . $v["id"] . "成功", $v["uid"], 5);
							$data_i["host_id"] = $v["id"];
							$data_i["description"] = "产品到期删除 Host ID:{$v["id"]}成功";
							$data_i["active_type_param"] = [$v["id"], "cron"];
							$is_zjmf = $model_host->isZjmfApi($v["id"]);
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 400, 4);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 100, 4);
							}
							$this->ad_log(4, 4, 1, $v["id"]);
							$logic_run_map->cronSuccess(time(), 4, $v["id"]);
						} else {
							active_log_final("产品到期后删除 - 删除到期产品 Host ID:" . $v["id"] . "失败,失败原因:" . $result1["msg"], $v["uid"], 5);
							$data_i["host_id"] = $v["id"];
							$data_i["description"] = "产品到期删除 Host ID:{$v["id"]}失败。原因:{$result["msg"]}";
							$data_i["active_type_param"] = [$v["id"], "cron"];
							$is_zjmf = $model_host->isZjmfApi($v["id"]);
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 400, 4);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 100, 4);
							}
							$this->ad_log(4, 4, 0, $v["id"]);
						}
					}
				} else {
					active_log_final("产品到期后删除 - 无到期产品", 0, 5);
				}
			}
		}
	}
	public function invoice($config)
	{
		$recharge = $config["cron_invoice_recharge_delete"];
		$time = $config["cron_invoice_recharge_delete_time"];
		$start_time = 0;
		$end_time = time() - $time * 24 * 3600;
		if (!empty($recharge)) {
			$ids = \think\Db::name("invoices")->where("type", "recharge")->where("status", "Unpaid")->where("create_time", ">=", $start_time)->where("create_time", "<=", $end_time)->where("delete_time", 0)->column("id");
			if ($ids) {
				$res = \think\Db::name("invoices")->where("type", "recharge")->where("status", "Unpaid")->where("create_time", ">=", $start_time)->where("create_time", "<=", $end_time)->where("delete_time", 0)->delete();
				$str = [];
				foreach ($ids as $key => $value) {
					$str[] = "Invoice ID:" . $value;
				}
				if ($res) {
					active_log_final("未付款充值账单 - User ID:" . $res["uid"] . "删除未付款充值账单" . implode(", ", $str) . "成功", $res["uid"], 5);
				} else {
					active_log_final("未付款充值账单 - User ID:" . $res["uid"] . "删除未付款充值账单" . implode(", ", $str) . "失败", $res["uid"], 5);
				}
			} else {
				active_log_final("未付款充值账单 - 无未付款充值账单", 0, 5);
			}
		}
		$days = $config["cron_invoice_create_default_days"];
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.initiative_renew")->leftJoin("clients b", "a.uid=b.id")->whereIn("a.domainstatus", "Active,Suspended")->where("a.nextduedate", "<=", strtotime(date("Y-m-d")) + $days * 24 * 3600 + 86400)->where("a.billingcycle", "<>", "free")->where("a.billingcycle", "<>", "onetime")->where("a.billingcycle", "<>", "ontrial")->select()->toArray();
		if (!empty($host[0])) {
			$invoice = new \app\common\logic\Invoices();
			$is_pay = $config["auto_pay_renew"];
			if (!empty($is_pay)) {
				foreach ($host as $v) {
					if (!checkHostIsCancel($v["id"])) {
						$result = $invoice->createRenew($v["id"], $v["initiative_renew"], true);
						if ($result["status"] == 200) {
							$this->ad_log(13, 0, 1);
						}
					}
				}
			} else {
				if (configuration("no_auto_apply_credit") == 1) {
					foreach ($host as $v) {
						if (!checkHostIsCancel($v["id"])) {
							$result = $invoice->createRenew($v["id"], $v["initiative_renew"], true);
							if ($result["status"] == 200) {
								$this->ad_log(13, 0, 1);
							}
						}
					}
				} else {
					foreach ($host as $v) {
						if (!checkHostIsCancel($v["id"])) {
							$result = $invoice->createRenew($v["id"], $v["initiative_renew"], true);
							if ($result["status"] == 200) {
								$this->ad_log(13, 0, 1);
							}
						}
					}
				}
			}
		} else {
			active_log_final("续费账单 - 无续费账单", 0, 5);
		}
		if ($config["cron_invoice_pay_email"] > 0 && $config["cron_invoice_unpaid_email"] > 0) {
			$day = $config["cron_invoice_unpaid_email"];
			$host_second = \think\Db::name("host")->alias("a")->field("a.id,b.initiative_renew,a.uid,p.name,a.domain,a.nextinvoicedate,a.create_time,a.nextduedate")->leftJoin("clients b", "a.uid=b.id")->join("products p", "p.id=a.productid")->whereIn("a.domainstatus", "Active,Suspended")->where("a.nextduedate", ">=", strtotime(date("Y-m-d")) + $day * 24 * 3600)->where("a.nextduedate", "<=", strtotime(date("Y-m-d")) + $day * 24 * 3600 + 86400 - 1)->where("a.billingcycle", "<>", "free")->where("a.billingcycle", "<>", "onetime")->where("a.billingcycle", "<>", "ontrial")->select()->toArray();
			if (!empty($host_second[0])) {
				$email = new \app\common\logic\Email();
				foreach ($host_second as $vv) {
					if (!cancelRequest($vv["id"])) {
						$result = $email->sendEmailBase($vv["id"], "产品到期续费提示(第二次)", "invoice", true);
						$message_template_type = array_column(config("message_template_type"), "id", "name");
						$sms = new \app\common\logic\Sms();
						$client = check_type_is_use($message_template_type[strtolower("second_renew_product_reminder")], $vv["uid"], $sms);
						if ($client) {
							$params = ["product_name" => $vv["name"], "hostname" => $vv["domain"], "product_end_time" => date("Y-m-d H:i:s", $vv["nextduedate"]), "product_terminate_time" => date("Y-m-d H:i:s", $vv["nextduedate"])];
							$sms->sendSms($message_template_type[strtolower("second_renew_product_reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $vv["uid"]);
						}
						if ($result) {
							active_log_final("账单未付款提醒 -  User ID:" . $vv["uid"] . "产品#Host ID:" . $vv["id"] . "发送邮件成功", $vv["uid"], 5);
							$this->ad_log(12, 0, 1);
						} else {
							active_log_final("账单未付款提醒 -  User ID:" . $vv["uid"] . "产品#Host ID:" . $vv["id"] . "发送邮件失败", $vv["uid"], 5);
						}
					}
				}
			} else {
				active_log_final("账单未付款提醒 - 无续费产品", 0, 5);
			}
		}
		if (!empty($config["cron_invoice_pay_email"])) {
			if ($config["cron_invoice_third_overdue_email"] > 0) {
				$this->invoiceDueSend($config, 2);
			}
			if ($config["cron_invoice_second_overdue_email"] > 0) {
				$this->invoiceDueSend($config, 1);
			}
			if ($config["cron_invoice_first_overdue_email"] > 0) {
				$this->invoiceDueSend($config, 0);
			}
		}
	}
	private function invoiceDueSend($config, $times = 0)
	{
		if ($times == 0) {
			$str = "first";
		} elseif ($times == 1) {
			$str = "second";
		} else {
			$str = "third";
		}
		$day = $config["cron_invoice_{$str}_overdue_email"];
		$before_day_start_time = strtotime("-{$day} days", strtotime(date("Y-m-d")));
		$before_day_end_time = strtotime("+1 days -1 seconds", $before_day_start_time);
		$host = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid,f.id as hostid,p.name,f.domain,e.create_time")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->leftJoin("orders e", "e.invoiceid = b.id")->leftJoin("host f", "e.id = f.orderid")->leftJoin("products p", "p.id = f.productid")->withAttr("total", function ($value, $data) {
			return $value . $data["suffix"];
		})->where("b.status", "Unpaid")->where("b.due_time", "<=", $before_day_end_time)->where("b.due_email_times", $times)->where("b.delete_time", 0)->select()->toArray();
		if (!empty($host[0])) {
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$hostids = [];
			foreach ($host as $v) {
				if ($times == 0) {
					$times_zh = "First";
					$email_name = "订单未支付提示(第一次)";
				} elseif ($times == 1) {
					$times_zh = "Second";
					$email_name = "订单未支付提示(第二次)";
				} else {
					$email_name = "订单未支付提示(第三次)";
					$times_zh = "Third";
				}
				if ($v["hostid"]) {
					$email = new \app\common\logic\Email();
					$result = $email->sendEmailBase($v["hostid"], $email_name, "invoice", true);
					$sms = new \app\common\logic\Sms();
					$client = check_type_is_use($message_template_type[strtolower($times_zh . "_Invoice_Payment_Reminder")], $host["uid"], $sms);
					if ($client) {
						$params = ["product_first_time" => date("Y-m-d", $host["create_time"]), "product_name" => $host["name"], "hostname" => $v["domain"]];
						$sms->sendSms($message_template_type[strtolower($times_zh . "_Invoice_Payment_Reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
					}
					if ($result) {
						active_log_final("账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒成功", $v["uid"], 5);
						\think\Db::name("invoices")->where("id", $v["id"])->where("delete_time", 0)->where("due_email_times", $times)->setInc("due_email_times");
						$this->ad_log(12, 0, 1);
					} else {
						active_log_final("账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒失败", $v["uid"], 5);
					}
				} else {
					$hostids[] = $v["id"];
				}
			}
			if (count($host) == count($hostids)) {
				active_log_final("账单逾期提醒 - 无第" . ($times + 1) . "次逾期提醒账单", 0, 5);
			}
		} else {
			active_log_final("账单逾期提醒 - 无第" . ($times + 1) . "次逾期提醒账单", 0, 5);
		}
	}
	public function ticket($config)
	{
		$hour = $config["cron_ticket_close_time"];
		if ($hour > 0) {
			$tickets = \think\Db::name("ticket")->field("tid,id,uid")->where("status", "<>", 4)->where("status", 2)->where("last_reply_time", ">", 0)->where("last_reply_time", "<=", time() - $hour * 3600)->select()->toArray();
			$id = array_column($tickets, "id") ?: [];
			if (!empty($id)) {
				$result = \think\Db::name("ticket")->whereIn("id", $id)->update(["status" => 4, "update_time" => time()]);
				if ($result) {
					foreach ($id as $v) {
						$email_logic = new \app\common\logic\Email();
						$email_logic->sendEmailBase($v, "工单自动关闭提醒", "support", true);
					}
					foreach ($tickets as $key => $value) {
						active_log_final("关闭工单:工单未回复,自动关闭成功#Ticket ID:" . $value["tid"] . " - User ID:" . $value["uid"], $value["uid"], 5);
						$this->ad_log(9, 0, 1);
					}
				} else {
					active_log_final("自动关闭工单 - 关闭工单" . implode(",", $id) . "失败", 0, 5);
				}
			} else {
				active_log_final("账单逾期提醒 - 无需要关闭工单", 0, 5);
			}
		}
	}
	public function order($config)
	{
		$invoiceids = \think\Db::name("invoices")->where("type", "<>", "credit_limit")->where("status", "Unpaid")->where("is_delete", 1)->where("create_time", "<", time() - 7200)->column("id");
		\think\Db::name("invoice_items")->whereIn("invoice_id", $invoiceids)->delete();
		\think\Db::name("invoices")->whereIn("id", $invoiceids)->delete();
		$cron_order_unpaid_time_high = $config["cron_order_unpaid_time_high"];
		$action = $config["cron_order_unpaid_action"];
		$day = intval($config["cron_order_unpaid_time"]);
		if ($cron_order_unpaid_time_high == 1 && $day > 0) {
			$before_day_start_time = strtotime("-{$day} days", strtotime(date("Y-m-d")));
			$before_day_end_time = strtotime("+1 days -1 seconds", $before_day_start_time);
			$orders = \think\Db::name("orders")->alias("a")->leftJoin("invoices b", "a.invoiceid = b.id")->field("a.id,a.uid,b.id as invoiceid,b.create_time")->whereBetweenTime("a.create_time", $before_day_start_time, $before_day_end_time)->where("b.status", "Unpaid")->where("a.delete_time", 0)->where("b.delete_time", 0)->where("b.type", "<>", "credit_limit")->select()->toArray();
			$ids = array_column($orders, "id");
			$invoiceids = array_column($orders, "invoiceid");
			\think\Db::name("invoice_items")->whereIn("invoice_id", $invoiceids)->select()->toArray();
			$hosts = \think\Db::name("host")->field("id,productid")->whereIn("orderid", $ids)->select()->toArray();
			$hostids = array_column($hosts, "id");
			$productids = array_column($hosts, "productid");
			$customfields = \think\Db::name("customfields")->field("id")->where("type", "product")->whereIn("relid", $productids)->select()->toArray();
			$customfieldids = array_column($customfields, "id");
			if (!empty($ids[0])) {
				if ($action == "Delete") {
					$delete = false;
					\think\Db::startTrans();
					try {
						\think\Db::name("orders")->whereIn("id", $ids)->useSoftDelete("delete_time", time())->delete();
						\think\Db::name("host_config_options")->whereIn("relid", $hostids)->delete();
						\think\Db::name("host")->whereIn("orderid", $ids)->delete();
						\think\Db::name("invoice_items")->whereIn("invoice_id", $invoiceids)->useSoftDelete("delete_time", time())->delete();
						\think\Db::name("invoices")->whereIn("id", $invoiceids)->useSoftDelete("delete_time", time())->delete();
						foreach ($hostids as $hostid) {
							\think\Db::name("customfieldsvalues")->whereIn("fieldid", $customfieldids)->where("relid", $hostid)->delete();
						}
						\think\Db::name("products")->whereIn("id", $productids)->setInc("qty", 1);
						\think\Db::commit();
						$delete = true;
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
					if ($delete) {
						foreach ($ids as $id) {
							$o = \think\Db::name("orders")->field("uid")->where("id", $id)->find();
							$description = "删除未付款订单成功 -#Order ID:" . $id . " - User ID:" . $o["uid"];
							active_log_final($description, $orders["uid"], 5);
							$this->ad_log(6, 0, 1);
						}
						$ids = implode(",", $ids);
						if ($ids) {
							active_log_final("删除未付款订单 - " . "删除订单{$ids}成功", 0, 5);
						} else {
							active_log_final("删除未付款订单 - 无未付款订单", 0, 5);
						}
					} else {
						$ids = implode(",", $ids);
						active_log_final("删除未付款订单 - " . "删除订单{$ids}失败", 0, 5);
					}
				} else {
					$cancelled = false;
					\think\Db::startTrans();
					try {
						\think\Db::name("orders")->whereIn("id", $ids)->where("delete_time", 0)->update(["status" => "Cancelled", "update_time" => time()]);
						foreach ($invoiceids as $invoiceid) {
							$invoice_status = \think\Db::name("invoices")->where("delete_time", 0)->where("id", $invoiceid)->value("status");
							if ($invoice_status != "Paid") {
								\think\Db::name("invoices")->where("delete_time", 0)->where("id", $invoiceid)->update(["status" => "Cancelled"]);
							}
						}
						\think\Db::name("host")->whereIn("id", array_column($hostids, "rel_id"))->update(["domainstatus" => "Cancelled"]);
						\think\Db::name("products")->whereIn("id", $productids)->setInc("qty", 1);
						\think\Db::commit();
						$cancelled = true;
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
					$ids = implode(",", $ids);
					if ($ids) {
						foreach ($ids as $id) {
							$o = \think\Db::name("orders")->field("uid")->where("id", $id)->find();
							$description = "取消未付款订单成功 -#Order ID:" . $id . " - User ID:" . $o["uid"];
							active_log_final($description, $orders["uid"], 5);
							$this->ad_log(6, 0, 1);
						}
						if ($cancelled) {
							active_log_final("取消未付款订单 - " . "取消订单{$ids}成功", 0, 5);
						} else {
							active_log_final("取消未付款订单 - " . "取消订单{$ids}失败", 0, 5);
						}
					} else {
						active_log_final("取消未付款订单 - 无未付款订单", 0, 5);
					}
				}
			}
		}
	}
	public function unCertifi($config)
	{
		$use = $config["certifi_is_stop"];
		$day = intval($config["certifi_stop_day"]);
		$certifi_open = intval($config["certifi_open"]);
		if (!empty($use) && $day >= 0 && $certifi_open == 1) {
			$before_day_start_time = strtotime("-{$day} days", strtotime(date("Y-m-d")));
			$before_day_end_time = strtotime("+1 days -1 seconds", $before_day_start_time);
			$hosts = \think\Db::name("host")->whereIn("domainstatus", "Active")->where("regdate", "<=", $before_day_end_time)->select()->toArray();
			$hids = [];
			$logic_run_map = new \app\common\logic\RunMap();
			$model_host = new \app\common\model\HostModel();
			foreach ($hosts as $host) {
				$uid = $host["uid"];
				if (!checkCertify($uid)) {
					$hids[] = $host["id"];
					$host_logic = new \app\common\logic\Host();
					$host_logic->is_admin = true;
					$res = $host_logic->suspend($host["id"], "uncertifi", "未实名认证");
					if ($res["status"] == 200) {
						$email = new \app\common\logic\Email();
						$email->sendEmailBase($host["id"], "未实名暂停提示", "product", true);
						$message_template_type = array_column(config("message_template_type"), "id", "name");
						$tmp = \think\Db::name("host")->field("uid,productid,domain,billingcycle,regdate")->where("id", $host["id"])->find();
						$product = \think\Db::name("products")->field("name")->where("id", $tmp["productid"])->find();
						$sms = new \app\common\logic\Sms();
						$client = check_type_is_use($message_template_type[strtolower("uncertify_reminder")], $tmp["uid"], $sms);
						if ($client && $tmp["billingcycle"] != "free") {
							$params = ["product_name" => $product["name"], "hostname" => $tmp["domain"], "product_end_time" => date("Y-m-d H:i:s", $tmp["regdate"])];
							$sms->sendSms($message_template_type[strtolower("uncertify_reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $tmp["uid"]);
						}
						active_log_final("未实名客户产品暂停 - 暂停 Host ID:{$host["id"]}的产品成功", $host["uid"], 5);
						$data_i["host_id"] = $host["id"];
						$data_i["description"] = "未实名客户产品暂停 Host ID:{$host["id"]}成功";
						$data_i["active_type_param"] = [$host["id"], "uncertifi", "未实名认证", 0];
						$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 2);
						}
						$this->ad_log(2, 2, 1, $host["id"]);
						$logic_run_map->cronSuccess(time(), 2, $host["id"]);
					} else {
						active_log_final("未实名客户产品暂停 - 暂停 Host ID:{$host["id"]}的产品失败,失败原因:" . $res["msg"], $host["uid"], 5);
						$data_i["host_id"] = $host["id"];
						$data_i["description"] = "未实名客户产品暂停 Host ID:{$host["id"]}失败。原因:{$res["msg"]}";
						$data_i["active_type_param"] = [$host["id"], "uncertifi", "未实名认证", 0];
						$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 2);
						}
						$this->ad_log(2, 2, 0, $host["id"]);
					}
				}
			}
		}
	}
	public function updateCommission()
	{
		$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.status,i.type,i.subtotal,i.paid_time,c.username,o.id,o.uid,c.id as uid,o.status,o.create_time,o.invoiceid,o.amount,o.payment,cu.prefix,cu.suffix")->where("i.delete_time", 0)->where("i.status", "=", "Paid")->where("i.is_aff", 0)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->where(function (\think\db\Query $query) {
			$time = time() - configuration("affiliate_delay_commission") * 24 * 60 * 60;
			$start_time = strtotime(date("Y-m-d", $time));
			$end_time = strtotime(date("Y-m-d", $time)) + 86400;
			$query->where("i.paid_time", "<=", $end_time);
			$query->where("i.paid_time", ">=", $start_time);
		})->select()->toArray();
		foreach ($rows as $k => $value) {
			$arr = [];
			$af = \think\Db::name("affiliates_user")->where("uid", $value["uid"])->find();
			if (!empty($af)) {
				$affi = \think\Db::name("affiliates")->where("id", $af["affid"])->find();
				if (!empty($affi)) {
					\think\Db::startTrans();
					try {
						$arr[] = $value;
						$uids = getids($affi["uid"]);
						$ladder = getLadder($affi["uid"], $uids);
						$rows = dealCommissionaffs($arr, $ladder, $affi["uid"]);
						$res = \think\Db::name("invoices")->where("id", $value["invoiceid"])->update(["is_aff" => 1, "aff_sure_time" => time(), "aff_commission" => $rows[0]["commission"], "aff_commmission_bates" => $rows[0]["commission_bates"], "aff_commmission_bates_type" => $rows[0]["commission_bates_type"]]);
						foreach ($rows[0]["child"] as $k => $val) {
							$res = \think\Db::name("invoice_items")->where("id", $val["inid"])->update(["is_aff" => 1, "aff_sure_time" => time(), "aff_commission" => $val["commission"], "aff_commmission_bates" => $val["commission_bates"], "aff_commmission_bates_type" => $val["commission_bates_type"]]);
						}
						$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["balance" => bcadd($rows[0]["commission"], $affi["balance"], 2), "updated_time" => time()]);
						\think\Db::commit();
						active_log_final("更新用户可提现余额余额: " . $affi["balance"] . " 改为 :" . bcadd($rows[0]["commission"], $affi["balance"], 2), $affi["uid"], 5);
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
				}
			}
		}
	}
	public function autoReply()
	{
		$ticket = \think\Db::name("ticket")->where("is_auto_reply", 0)->select()->toArray();
		foreach ($ticket as $key => $value) {
			$ticket_deliver = \think\Db::name("ticket_deliver")->alias("a")->leftJoin("ticket_deliver_products b", "b.tdid = a.id")->leftJoin("ticket_deliver_department c", "c.tdid=b.tdid")->leftJoin("ticket d", "d.dptid=c.dptid")->leftJoin("host e", "e.id=d.host_id")->leftJoin("products f", "f.id=e.productid")->leftJoin("ticket_department_upstream g", "g.dptid=c.dptid AND f.zjmf_api_id=g.api_id")->field("a.is_open_auto_reply,a.bz,a.id")->where("e.productid=b.pid")->where("d.id", $value["id"])->find();
			$td = \think\Db::name("ticket_department")->where("id", $value["dptid"])->find();
			if (!empty($ticket_deliver["id"])) {
				$td["is_open_auto_reply"] = $ticket_deliver["is_open_auto_reply"];
				$td["bz"] = $ticket_deliver["bz"];
			}
			if ($td["is_open_auto_reply"] == 1) {
				if ($td["time_type"] == 1) {
					$td["minutes"] = $td["minutes"] * 60;
				}
				if ($value["create_time"] + $td["minutes"] <= time()) {
					$data["tid"] = $value["id"];
					$data["uid"] = intval($value["uid"]);
					$data["create_time"] = time();
					$data["content"] = $td["bz"];
					$data["admin_id"] = 1;
					$data["admin"] = "admin";
					$data["attachment"] = "";
					\think\Db::name("ticket_reply")->insertGetId($data);
					\think\Db::name("ticket")->where("id", $data["tid"])->update(["is_auto_reply" => 1, "admin_unread" => 1, "last_reply_time" => time()]);
					active_log_final(sprintf("自动回复工单成功#User ID:%d - Ticket ID:%s - %s", $value["uid"], $value["tid"], $value["title"]), $value["uid"], 5);
				}
			}
		}
	}
	public function dcimFlowCls()
	{
		$time = time();
		$date = date("Y-m-d");
		$month = date("Y-m-01");
		$today = strtotime(date("Y-m-d 00:00:00"));
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.dcimid,a.regdate,a.nextduedate,c.secure,c.hostname,c.username,c.password,c.port,c.accesshash,d.bill_type,d.flow_remind,e.percent,e.send_email")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->leftJoin("dcim_servers d", "a.serverid=d.serverid")->leftJoin("dcim_percent e", "a.id=e.hostid")->where("b.type", "dcim")->whereIn("b.api_type", ["", "normal"])->where("c.server_type", "dcim")->where("a.dcimid", ">", 0)->where("b.config_option1", "rent")->where("a.domainstatus=\"Active\" OR a.domainstatus=\"Suspended\"")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->select()->toArray();
		$dcim = new \app\common\logic\Dcim();
		foreach ($host as $v) {
			$dcim->init($v);
			if ($v["bill_type"] == "last_30days") {
				$diff = $today - $v["regdate"];
				$diff = floor($diff / 24 / 3600 / 30) - 1;
				$i = $diff >= 1 ? $diff : 1;
				$max = $i + 30;
				while ($i < $max) {
					$target = date("Y-m-d", strtotime(date("Y-m-d", $v["regdate"]) . " +" . $i . " month"));
					if ($target == $date) {
						$result = $dcim->resetFlow($v["id"], $v["dcimid"], $v["nextduedate"]);
						$logic_run_map = new \app\common\logic\RunMap();
						$data_i = [];
						$data_i["host_id"] = $v["id"];
						$data_i["active_type_param"] = [$v, $v["id"], $v["dcimid"], $v["nextduedate"]];
						if ($result["status"] == 200) {
							$data_i["description"] = "DCIM流量 - 周期重置 Host ID:{$data_i["host_id"]}的产品成功";
							$logic_run_map->saveMap($data_i, 1, 100, 7);
							$this->ad_log(7, 7, 1, $v["id"]);
							$logic_run_map->cronSuccess(time(), 7, $v["id"]);
						} else {
							$data_i["description"] = "DCIM流量 - 周期重置 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
							$logic_run_map->saveMap($data_i, 0, 100, 7);
							$this->ad_log(7, 7, 0, $v["id"]);
						}
					}
					$i++;
				}
			} else {
				if ($date == $month) {
					$result = $dcim->resetFlow($v["id"], $v["dcimid"], $v["nextduedate"]);
					$logic_run_map = new \app\common\logic\RunMap();
					$data_i = [];
					$data_i["host_id"] = $v["id"];
					$data_i["active_type_param"] = [$v, $v["id"], $v["dcimid"], $v["nextduedate"]];
					if ($result["status"] == 200) {
						$data_i["description"] = "DCIM流量 - 自然月重置 Host ID:{$data_i["host_id"]}的产品成功";
						$logic_run_map->saveMap($data_i, 1, 100, 7);
						$this->ad_log(7, 7, 1, $v["id"]);
						$logic_run_map->cronSuccess(time(), 7, $v["id"]);
					} else {
						$data_i["description"] = "DCIM流量 - 自然月重置 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						$logic_run_map->saveMap($data_i, 0, 100, 7);
						$this->ad_log(7, 7, 0, $v["id"]);
					}
				}
			}
		}
	}
	public function updateDcimOs()
	{
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port,accesshash")->where("server_type", "dcim")->select()->toArray();
		$data = [];
		foreach ($server_info as $v) {
			$protocol = $v["secure"] == 1 ? "https://" : "http://";
			$url = $protocol . $v["hostname"];
			if (!empty($v["port"])) {
				$url .= ":" . $v["port"];
			}
			$data[$v["id"]] = ["url" => $url . "/index.php?m=api&a=getAllMirrorOs", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? ""]];
		}
		if (!empty($data)) {
			$res = batch_curl_post($data, 15);
			foreach ($res as $k => $v) {
				if ($v["http_code"] == 200 && $v["data"]["status"] == "success") {
					\think\Db::name("dcim_servers")->where("serverid", $k)->update(["os" => json_encode($v["data"]["data"]) ?? ""]);
				}
			}
		}
	}
	public function updateDcimArea()
	{
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port,accesshash")->where("server_type", "dcim")->select()->toArray();
		$data = [];
		foreach ($server_info as $v) {
			$protocol = $v["secure"] == 1 ? "https://" : "http://";
			$url = $protocol . $v["hostname"];
			if (!empty($v["port"])) {
				$url .= ":" . $v["port"];
			}
			$data[$v["id"]] = ["url" => $url . "/index.php?m=api&a=getArea", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? ""]];
		}
		if (!empty($data)) {
			$res = batch_curl_post($data, 15);
			foreach ($res as $k => $v) {
				if ($v["http_code"] == 200 && $v["status"] == 200 && empty($v["data"]["status"]) && !empty($v["data"])) {
					$area = \think\Db::name("dcim_servers")->where("serverid", $k)->value("area");
					if (!empty($area)) {
						$area = json_decode($area, true);
						$area_name = [];
						foreach ($area as $kk => $vv) {
							$area_name[$vv["id"]] = \strval($vv["name"]) ?? "";
						}
						foreach ($v["data"] as $kk => $vv) {
							$v["data"][$kk]["name"] = $area_name[$vv["id"]];
						}
					} else {
						foreach ($v["data"] as $kk => $vv) {
							$v["data"][$kk]["name"] = "";
						}
					}
					\think\Db::name("dcim_servers")->where("serverid", $k)->update(["area" => json_encode($v["data"]) ?? ""]);
				}
			}
		}
	}
	public function updateHostFlow()
	{
		$time = time();
		$date = date("Y-m-d");
		$month = date("Y-m-01");
		$today = strtotime(date("Y-m-d 00:00:00"));
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.serverid,a.dcimid,a.regdate,c.secure,c.hostname,c.username,c.password,c.port,c.accesshash,d.bill_type,d.flow_remind,e.percent,e.send_email")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->leftJoin("dcim_servers d", "a.serverid=d.serverid")->leftJoin("dcim_percent e", "a.id=e.hostid")->where("b.type", "dcim")->whereIn("b.api_type", ["", "normal"])->where("c.server_type", "dcim")->where("a.dcimid", ">", 0)->where("b.config_option1", "rent")->where("a.domainstatus", "Active")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->select()->toArray();
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$email = new \app\common\logic\Email();
		$email->is_admin = true;
		foreach ($host as $v) {
			$dcim->init($v);
			$res = $dcim->serverDetailflowData($v["id"], $v["dcimid"]);
			if ($res["status"] == "success") {
				$bill_type = $v["bill_type"] ?: "month";
				if ($res["limit"] != 0) {
					$res["limit"] = $res["limit"] + $res["temp_traffic"];
				}
				$update["bwlimit"] = \intval($res["limit"]);
				$update["bwusage"] = round(str_replace("GB", "", $res["data"][$bill_type][$res["type"]]), 2);
				$update["lastupdate"] = $time;
				\think\Db::name("host")->where("id", $v["id"])->update($update);
				$data = $res["data"];
				$now_percent = str_replace("%", "", $data[$bill_type]["used_percent"]);
				$flow_remind = json_decode($v["flow_remind"], true);
				if (!empty($flow_remind)) {
					$template = 0;
					$over = false;
					$set_over_percent = 0;
					foreach ($flow_remind as $k => $v) {
						if ($v["percent"] < $now_percent) {
							$over = true;
							$template = $v["tid"];
							if ($set_over_percent < $v["percent"]) {
								$set_over_percent = $v["percent"];
							}
						}
					}
					if ($over) {
						if (!empty($v["percent"]) && $v["percent"] == $set_over_percent && $v["send_email"] == 1) {
						} else {
							$r = $email->sendEmail($template, $v["id"]);
							if ($r) {
								$template_name = \think\Db::name("email_templates")->where("id", $template)->value("name");
								$description = "流量使用超过{$set_over_percent}%, 邮件发送成功, 模板{$template_name} - Host ID:{$v["id"]}";
							} else {
								$description = "流量使用超过{$set_over_percent}%, 邮件发送失败 - Host ID:{$v["id"]}";
							}
							active_log_final($description, $v["uid"], 5);
							$is_set = \think\Db::name("dcim_percent")->where("hostid", $v["id"])->find();
							if (!empty($is_set)) {
								\think\Db::name("dcim_percent")->where("hostid", $v["id"])->update(["percent" => $set_over_percent, "send_email" => 0]);
							} else {
								\think\Db::name("dcim_percent")->insert(["hostid" => $v["id"], "percent" => $set_over_percent, "send_email" => 0]);
							}
						}
					}
				}
				if ($now_percent >= 100) {
					$dcim->overTraffic($v["id"], $v["dcimid"], $bill_type);
				}
			}
		}
	}
	public function updateDcimCloudHostFlow()
	{
		$time = time();
		$host_count = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("b.type", "dcimcloud")->whereIn("b.api_type", ["", "normal"])->where("a.dcimid", ">", 0)->whereIn("a.domainstatus", "Active,Suspended")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->count();
		$pagelimit = 50;
		$dcimcloud_hostflow = intval(configuration("cron_update_dcimcloud_hostflow")) ?? 1;
		$page_sum = ceil($host_count / $pagelimit);
		if ($dcimcloud_hostflow <= 0 || $page_sum < $dcimcloud_hostflow) {
			$dcimcloud_hostflow = 1;
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.serverid,a.dcimid,a.regdate,a.domainstatus,a.suspendreason,c.hostname,c.username,c.password,c.secure,c.port,c.accesshash")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("b.type", "dcimcloud")->whereIn("b.api_type", ["", "normal"])->where("a.dcimid", ">", 0)->whereIn("a.domainstatus", "Active,Suspended")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->page($dcimcloud_hostflow, $pagelimit)->select()->toArray();
		$page = $dcimcloud_hostflow + 1;
		updateConfiguration("cron_update_dcimcloud_hostflow", $page);
		$dcimcloud = new \app\common\logic\DcimCloud();
		$dcimcloud->is_admin = true;
		$serverid = array_unique(array_column($host, "serverid"));
		$token = [];
		foreach ($serverid as $k => $v) {
			$dcimcloud->setUrl($v);
			$access_token = $dcimcloud->login();
			if (!empty($access_token)) {
				$token[$v] = $access_token;
			}
		}
		$host_obj = new \app\common\logic\Host();
		$data = [];
		$host_status = [];
		foreach ($host as $v) {
			if (!empty($token[$v["serverid"]])) {
				$url = $v["secure"] == 1 ? "https://" : "http://";
				$url .= $v["hostname"];
				if (!empty($v["port"])) {
					$url .= ":" . $v["port"];
				}
				$host_status[$v["id"]]["domainstatus"] = $v["domainstatus"];
				$host_status[$v["id"]]["suspendreason"] = $v["suspendreason"];
				$data[$v["id"]] = ["url" => $url . "/v1/net_info?host_id=" . $v["dcimid"], "data" => [], "header" => ["access-token: " . $token[$v["serverid"]]]];
			}
		}
		$res = batch_curl($data, "GET", 300);
		foreach ($res as $k => $v) {
			if ($v["status"] == 200 && $v["http_code"] == 200 && !empty($v["data"]["meta"])) {
				if ($v["data"]["meta"]["traffic_quota"] != 0) {
					$v["data"]["meta"]["traffic_quota"] += $v["data"]["meta"]["tmp_traffic"];
				}
				$update["bwlimit"] = \intval($v["data"]["meta"]["traffic_quota"]);
				$usage = 0;
				if ($v["data"]["meta"]["traffic_type"] == 1) {
					$usage = round($v["data"]["info"]["30_day"]["accept"] / pow(1024, 3), 2);
				} elseif ($v["data"]["meta"]["traffic_type"] == 2) {
					$usage = round($v["data"]["info"]["30_day"]["send"] / pow(1024, 3), 2);
				} elseif ($v["data"]["meta"]["traffic_type"] == 3) {
					$usage = round($v["data"]["info"]["30_day"]["total"] / pow(1024, 3), 2);
				}
				$update["bwusage"] = $usage;
				$update["lastupdate"] = time();
				\think\Db::name("host")->where("id", $k)->update($update);
				if ($host_status[$k]["domainstatus"] == "Active" && $v["data"]["info"]["30_day"]["float"] > 100) {
					$host_obj->is_admin = true;
					$result = $host_obj->suspend($k, "flow", "流量使用超额");
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $k;
					$data_i["active_type_param"] = [$k, "flow", "流量使用超额", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 流量使用超额 - 暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 2);
						}
					} else {
						$data_i["description"] = " 流量使用超额 - 暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 2);
						}
					}
				}
				if ($host_status[$k]["domainstatus"] == "Suspended" && $v["data"]["info"]["30_day"]["float"] < 100 && (explode("-", $host_status[$k]["suspendreason"])[0] == "用量超额" || explode("-", $host_status[$k]["suspendreason"])[0] == "flow")) {
					$host_obj->is_admin = true;
					$result = $host_obj->unsuspend($k);
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $k;
					$data_i["active_type_param"] = [$k, 0, "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 满足超额解除暂停条件 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 3);
						}
					} else {
						$data_i["description"] = " 满足超额解除暂停条件 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 3);
						}
					}
				}
			}
		}
	}
	public function UsageUpdate()
	{
		$time = time();
		$host = \think\Db::name("host")->alias("a")->field("a.id,c.type,a.uid")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("a.domainstatus", "Active")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->where("a.serverid", ">", 0)->whereIn("b.api_type", ["", "normal"])->where("c.server_type", "normal")->where("c.type", "<>", "")->select()->toArray();
		$provision = new \app\common\logic\Provision();
		$data = [];
		if (!empty($host)) {
			foreach ($host as $v) {
				$data[$v["type"]][] = $v["id"];
			}
			foreach ($data as $k => $v) {
				$provision->usageUpdate($k, $v);
			}
		}
	}
	public function dcimCloudFlowCls()
	{
		$time = time();
		$date = date("Y-m-d");
		$month = date("Y-m-01");
		$today = strtotime(date("Y-m-d 00:00:00"));
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcimid,a.domainstatus,a.regdate,a.nextduedate,c.secure,c.hostname,c.username,c.password,c.port,c.accesshash,d.bill_type,d.flow_remind,e.percent,e.send_email")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->leftJoin("dcim_servers d", "a.serverid=d.serverid")->leftJoin("dcim_percent e", "a.id=e.hostid")->where("b.type", "dcimcloud")->whereIn("b.api_type", ["", "normal"])->where("a.dcimid", ">", 0)->where("a.domainstatus=\"Active\" OR a.domainstatus=\"Suspended\"")->where("a.nextduedate=0 OR a.nextduedate>" . $time)->select()->toArray();
		$dcimcloud = new \app\common\logic\DcimCloud();
		$dcimcloud->is_admin = true;
		$config_option_ids = [];
		$options = [];
		foreach ($host as $v) {
			$dcimcloud->setUrlByServer($v);
			if (!isset($config_option_ids[$v["productid"]])) {
				$gids = \think\Db::name("product_config_links")->where("pid", $v["productid"])->column("gid");
				if (empty($gids)) {
					$config_option_ids[$v["productid"]] = 0;
				} else {
					$config_option_ids[$v["productid"]] = \think\Db::name("product_config_options")->whereIn("gid", $gids)->whereLike("option_name", "traffic_bill_type|%")->value("id") ?? 0;
				}
			}
			$traffic_bill_type_config_options = [];
			if (!empty($config_option_ids[$v["productid"]])) {
				$optionid = \think\Db::name("host_config_options")->where("relid", $v["id"])->where("configid", $config_option_ids[$v["productid"]])->value("optionid");
				if (!empty($optionid)) {
					if (isset($options[$optionid])) {
						$traffic_bill_type_config_options = $options[$optionid];
					} else {
						$traffic_bill_type_config_options = \think\Db::name("product_config_options_sub")->where("id", $optionid)->value("option_name");
						$traffic_bill_type_config_options = explode("|", $traffic_bill_type_config_options);
						$traffic_bill_type_config_options = $traffic_bill_type_config_options[0];
						$options[$optionid] = $traffic_bill_type_config_options;
					}
				}
			}
			if ($traffic_bill_type_config_options == "last_30days") {
				$diff = $today - $v["regdate"];
				$diff = floor($diff / 24 / 3600 / 30) - 1;
				$i = $diff >= 1 ? $diff : 1;
				$max = $i + 30;
				while ($i < $max) {
					$target = date("Y-m-d", strtotime(date("Y-m-d", $v["regdate"]) . " +" . $i . " month"));
					if ($target == $date) {
						$result = $dcimcloud->resetFlow($v["id"], $v["dcimid"], $v["nextduedate"], $v["domainstatus"]);
						$logic_run_map = new \app\common\logic\RunMap();
						$data_i = [];
						$data_i["host_id"] = $v["id"];
						$data_i["active_type_param"] = [$v, $v["id"], $v["dcimid"], $v["nextduedate"], $v["domainstatus"]];
						if ($result["status"] == 200) {
							$data_i["description"] = "魔方云流量 - 周期重置 Host ID:{$data_i["host_id"]}的产品成功";
							$logic_run_map->saveMap($data_i, 1, 100, 8);
							$this->ad_log(8, 8, 1, $v["id"]);
							$logic_run_map->cronSuccess(time(), 8, $v["id"]);
						} else {
							$data_i["description"] = "魔方云流量 - 周期重置 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
							$logic_run_map->saveMap($data_i, 0, 100, 8);
							$this->ad_log(8, 8, 0, $v["id"]);
						}
					}
					$i++;
				}
			} else {
				if ($date == $month) {
					$result = $dcimcloud->resetFlow($v["id"], $v["dcimid"], $v["nextduedate"], $v["domainstatus"]);
					$logic_run_map = new \app\common\logic\RunMap();
					$data_i = [];
					$data_i["host_id"] = $v["id"];
					$data_i["active_type_param"] = [$v, $v["id"], $v["dcimid"], $v["nextduedate"]];
					if ($result["status"] == 200) {
						$data_i["description"] = "魔方云流量 - 自然月重置 Host ID:{$data_i["host_id"]}的产品成功";
						$logic_run_map->saveMap($data_i, 1, 100, 8);
						$this->ad_log(8, 8, 1, $v["id"]);
						$logic_run_map->cronSuccess(time(), 8, $v["id"]);
					} else {
						$data_i["description"] = "魔方云流量 - 自然月重置 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						$logic_run_map->saveMap($data_i, 0, 100, 8);
						$this->ad_log(8, 8, 0, $v["id"]);
					}
				}
			}
		}
		unset($config_option_ids);
		unset($options);
	}
	public function syncUpstreamProductInfo()
	{
		(new \app\common\logic\Product())->cronSyncProduct();
	}
	public function syncUpstreamProductInfo1()
	{
		$products = \think\Db::name("products")->where("api_type", "zjmf_api")->where("upstream_version", ">", 0)->select()->toArray();
		$percent = [];
		if (!empty($products[0])) {
			$i = 0;
			foreach ($products as $product) {
				$pid = $product["id"];
				$rate = $product["rate"];
				$group = \think\Db::name("product_groups")->where("id", $product["gid"])->find();
				$res = getZjmfUpstreamProductConfig($product["zjmf_api_id"], $product["upstream_pid"]);
				if ($res["data"]["products"] && $res["data"]["products"]["location_version"] > $product["upstream_version"]) {
					if ($product["upstream_price_type"] == "percent") {
						$percent[] = $pid;
						$product_model = new \app\common\model\ProductModel();
						$res = $product_model->syncProductInfo($pid, $product["api_type"], $product["zjmf_api_id"], $product["upstream_pid"], false, false, true, $rate);
					} else {
						$product_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.pay_type")->leftJoin("products b", "a.relid = b.id")->where("a.type", "product")->where("b.id", $pid)->select()->toArray();
						$configoptions_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,f.type")->leftJoin("product_config_options_sub b", "a.relid = b.id")->leftJoin("product_config_options c", "b.config_id = c.id")->leftJoin("product_config_links d", "c.gid = d.gid")->leftJoin("product_config_groups e", "d.gid = e.id")->leftJoin("products f", "d.pid = f.id")->where("a.type", "configoptions")->where("f.id", $pid)->select()->toArray();
						$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
						$price_type = config("price_type");
						$origin_price_cost = [];
						foreach ($price_type as $kkk => $vvv) {
							$currency_cost = [];
							foreach ($currencies as $currency) {
								$cost = 0;
								foreach ($product_pricings as $k => $v) {
									if ($currency["id"] == $v["currency"]) {
										$x1 = $v[$vvv[0]] < 0 ? 0 : $v[$vvv[0]];
										$y1 = $v[$vvv[1]] < 0 ? 0 : $v[$vvv[1]];
										$cost += floatval($x1) + floatval($y1);
										foreach ($configoptions_pricings as $kk => $vv) {
											if ($v["currency"] == $vv["currency"]) {
												$cost += $vv[$vvv[0]] + $vv[$vvv[1]];
											}
										}
									}
								}
								$currency_cost[$currency["code"]] = $cost;
							}
							$origin_price_cost[$kkk] = $currency_cost;
						}
						$upstream_product_pricings = $res["data"]["product_pricings"];
						$upstream_config_groups = $res["data"]["config_groups"];
						$upstream_price_cost = [];
						foreach ($price_type as $jjj => $hhh) {
							$upstream_currency_cost = [];
							foreach ($currencies as $currency) {
								$upstream_cost = 0;
								foreach ($upstream_product_pricings as $j => $h) {
									if ($currency["code"] == $h["code"]) {
										$x = $h[$hhh[0]] < 0 ? 0 : $h[$hhh[0]];
										$y = $h[$hhh[1]] < 0 ? 0 : $h[$hhh[1]];
										$upstream_cost += floatval($x) + floatval($y);
										foreach ($upstream_config_groups as $jj => $hh) {
											$options = $hh["options"];
											foreach ($options as $j4 => $h4) {
												$subs = $h4["sub"];
												foreach ($subs as $j5 => $h5) {
													$pricings = $h5["pricings"];
													foreach ($pricings as $j6 => $h6) {
														if ($h["code"] == $h6["code"]) {
															$upstream_cost += $h6[$hhh[0]] + $h6[$hhh[1]];
														}
													}
												}
											}
										}
									} else {
										$x = $h[$hhh[0]] < 0 ? 0 : $h[$hhh[0]] * $rate;
										$y = $h[$hhh[1]] < 0 ? 0 : $h[$hhh[1]] * $rate;
										$upstream_cost += floatval($x) + floatval($y);
										foreach ($upstream_config_groups as $jj => $hh) {
											$options = $hh["options"];
											foreach ($options as $j4 => $h4) {
												$subs = $h4["sub"];
												foreach ($subs as $j5 => $h5) {
													$pricings = $h5["pricings"];
													foreach ($pricings as $j6 => $h6) {
														if ($h["code"] == $h6["code"]) {
															$upstream_cost += $h6[$hhh[0]] + $h6[$hhh[1]];
														} else {
															$upstream_cost += ($h6[$hhh[0]] + $h6[$hhh[1]]) * $rate;
														}
													}
												}
											}
										}
									}
								}
								$upstream_currency_cost[$currency["code"]] = $upstream_cost;
							}
							$upstream_price_cost[$jjj] = $upstream_currency_cost;
						}
						$bilingcycle = config("billing_cycle");
						$dec = [];
						$pay_type = json_decode($product["pay_type"], true);
						$origin_price_cost_filter = [];
						if ($pay_type["pay_type"] == "onetime") {
							$origin_price_cost_filter["onetime"] = $origin_price_cost["onetime"];
						} elseif ($pay_type["pay_type"] == "recurring") {
							unset($origin_price_cost["onetime"]);
							unset($origin_price_cost["ontrial"]);
							$origin_price_cost_filter = $origin_price_cost;
						}
						if ($pay_type["pay_ontrial_status"]) {
							$origin_price_cost_filter["ontrial"] = $origin_price_cost["ontrial"];
						}
						foreach ($origin_price_cost_filter as $u => $w) {
							foreach ($currencies as $currency) {
								if ($w[$currency["code"]] < $upstream_price_cost[$u][$currency["code"]]) {
									$info = "系统检测到产品组'{$group["name"]}'中产品'{$product["name"]}'在货币为{$currency["code"]},周期为{$bilingcycle[$u]}时, 销售价低于成本价,已开启此产品库存控制,请尽快同步更新!";
									$dec[] = $info;
								}
							}
						}
						if (!empty($dec)) {
							\think\Db::name("products")->where("id", $pid)->update(["stock_control" => 1, "qty" => 0]);
							$info = implode("\n", $dec) ?? "";
							$exist = \think\Db::name("info_notice")->where("relid", $pid)->where("type", "product")->where("admin", 1)->find();
							if ($exist) {
								\think\Db::name("info_notice")->where("relid", $pid)->where("type", "product")->where("admin", 1)->update(["info" => $info, "update_time" => time()]);
							} else {
								\think\Db::name("info_notice")->insert(["relid" => $pid, "type" => "product", "info" => $info, "admin" => 1, "create_time" => time(), "update_time" => 0]);
							}
						}
					}
					active_log_final("自动任务同步上游产品信息#PRODUCT ID:" . $pid, 0, 5);
					$i++;
					if ($i > 5) {
						break;
					}
				}
			}
		}
		if (empty($percent)) {
			active_log_final("同步上游产品信息至本地-无产品需要同步", 0, 5);
		}
	}
	public function updateUpstreamBw()
	{
		return null;
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.dcimid,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->whereIn("b.api_type", "zjmf_api")->where("a.dcimid", ">", 0)->where("a.domainstatus", "Active")->where("a.nextduedate=0 OR a.nextduedate>" . time())->select()->toArray();
		if (empty($host)) {
			return null;
		}
		$zjmf_api_id = array_column($host, "zjmf_api_id");
		$token = [];
		foreach ($zjmf_api_id as $v) {
			$api = \think\Db::name("zjmf_finance_api")->field("hostname,username,password")->where("id", $v)->find();
			$url = rtrim($api["hostname"], "/");
			$login_url = $url . "/zjmf_api_login";
			$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"])];
			$jwt = zjmfApiLogin($v, $login_url, $login_data);
			if ($jwt["status"] == 200) {
				$token[$v] = ["url" => $url, "jwt" => $jwt["jwt"]];
			}
		}
		$data = [];
		foreach ($host as $v) {
			if (empty($token[$v["zjmf_api_id"]])) {
				continue;
			}
			$data[$v["id"]] = ["url" => $token[$v["zjmf_api_id"]]["url"] . "/host/header?host_id=" . $v["dcimid"], "header" => ["Authorization: Bearer " . $token[$v["zjmf_api_id"]]["jwt"]]];
		}
		$res = batch_curl($data, "GET", 30);
		foreach ($res as $k => $v) {
			if ($v["status"] == 200 && $v["http_code"] == 200 && $v["data"]["status"] == 200) {
				$update["dedicatedip"] = $v["data"]["data"]["host_data"]["dedicatedip"] ?? "";
				$update["assignedips"] = implode(",", $v["data"]["data"]["host_data"]["assignedips"]) ?? "";
				$update["domain"] = $v["data"]["data"]["host_data"]["domain"] ?? "";
				$update["bwlimit"] = \intval($v["data"]["data"]["host_data"]["bwlimit"]);
				$update["bwusage"] = \floatval($v["data"]["data"]["host_data"]["bwusage"]);
				$update["username"] = $v["data"]["data"]["host_data"]["username"] ?? "";
				$update["password"] = cmf_encrypt($v["data"]["data"]["host_data"]["password"]);
				$update["port"] = \intval($v["data"]["data"]["host_data"]["port"]);
				\think\Db::name("host")->where("id", $k)->update($update);
			}
		}
	}
	public function deleteLogs()
	{
		$deletelogtime = configuration("deletelogtime") ?? 0;
		$lastdeletelogtime = configuration("lastdeletelogtime") ?? 0;
		if (empty($deletelogtime) || $deletelogtime == 0) {
			deleteLog(CMF_ROOT . "data/runtime", true);
			updateConfiguration("deletelogtime", 15);
		}
		if (empty($lastdeletelogtime) || $lastdeletelogtime == 0) {
			deleteLog(CMF_ROOT . "data/runtime", true);
			updateConfiguration("lastdeletelogtime", time());
		}
		if ($lastdeletelogtime + $deletelogtime * 24 * 60 * 60 <= time()) {
			deleteLog(CMF_ROOT . "data/runtime", true);
			updateConfiguration("lastdeletelogtime", time());
		}
	}
	public function updateDcim()
	{
		$svg = [1 => "Windows", 2 => "CentOS", 3 => "Ubuntu", 4 => "Debian", 5 => "ESXi", 6 => "XenServer", 7 => "FreeBSD", 8 => "Fedora", 9 => "其他"];
		$server = \think\Db::name("servers")->alias("a")->field("a.id,a.gid,b.area,b.os")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcim")->select()->toArray();
		if (empty($server)) {
			return false;
		}
		$group_area = [];
		$group_os = [];
		$product_area_config = [];
		$product_os_config = [];
		$price = [];
		foreach ($server as $k => $v) {
			$v["area"] = json_decode($v["area"], true);
			if (!empty($v["area"])) {
				foreach ($v["area"] as $vv) {
					$group_area[$v["gid"]][] = ["id" => $vv["id"], "option_name" => $vv["id"] . "|" . $vv["area"] . "^" . ($vv["name"] ?: $vv["area"])];
				}
			}
			$v["os"] = json_decode($v["os"], true);
			$os_group = array_column($v["os"]["group"], "svg", "id");
			foreach ($v["os"]["os"] as $vv) {
				$group_os[$v["gid"]][] = ["id" => $vv["id"], "option_name" => $vv["id"] . "|" . $svg[$os_group[$vv["group_id"]]] . "^" . $vv["name"]];
			}
		}
		$products = \think\Db::name("products")->field("id,server_group")->where("type", "dcim")->whereIn("server_group", array_column($server, "gid"))->whereIn("api_type", ["", "normal"])->select()->toArray();
		if (empty($products)) {
			return false;
		}
		foreach ($products as $k => $v) {
			$gid = \think\Db::name("product_config_links")->where("pid", $v["id"])->value("gid");
			if (empty($gid)) {
				continue;
			}
			$configid = \think\Db::name("product_config_options")->where("gid", $gid)->where("option_type", 12)->value("id");
			if (empty($configid)) {
				$configid = \think\Db::name("product_config_options")->insertGetId(["gid" => $gid, "option_name" => "area|区域", "option_type" => 12, "qty_minimum" => 0, "qty_maximum" => 0, "order" => 0, "hidden" => 0, "upgrade" => 0, "upstream_id" => 0]);
			}
			$product_area_config[$v["id"]] = $configid;
			foreach ($group_area[$v["server_group"]] as $vv) {
				$is_add = \think\Db::name("product_config_options_sub")->where("config_id", $configid)->whereLike("option_name", $vv["id"] . "|%")->value("id");
				if ($is_add) {
					continue;
				}
				$sub_id = \think\Db::name("product_config_options_sub")->insertGetId(["config_id" => $configid, "qty_minimum" => 0, "qty_maximum" => 0, "option_name" => $vv["option_name"]]);
				$pricing[] = ["type" => "configoptions", "relid" => $sub_id, "monthly" => 0];
			}
			$configid = \think\Db::name("product_config_options")->where("gid", $gid)->where("option_type", 5)->value("id");
			if (empty($configid)) {
				$configid = \think\Db::name("product_config_options")->insertGetId(["gid" => $gid, "option_name" => "os|操作系统", "option_type" => 5, "qty_minimum" => 0, "qty_maximum" => 0, "order" => 1, "hidden" => 0, "upgrade" => 0, "upstream_id" => 0]);
			}
			$product_os_config[$v["id"]] = $configid;
			foreach ($group_os[$v["server_group"]] as $vv) {
				$is_add = \think\Db::name("product_config_options_sub")->where("config_id", $configid)->whereLike("option_name", $vv["id"] . "|%")->value("id");
				if ($is_add) {
					continue;
				}
				$sub_id = \think\Db::name("product_config_options_sub")->insertGetId(["config_id" => $configid, "qty_minimum" => 0, "qty_maximum" => 0, "option_name" => $vv["option_name"]]);
				$pricing[] = ["type" => "configoptions", "relid" => $sub_id, "monthly" => 0];
			}
		}
		if (!empty($pricing)) {
			$currency = \think\Db::name("currencies")->column("id");
			foreach ($currency as $v) {
				foreach ($pricing as $kk => $vv) {
					$pricing[$kk]["currency"] = $v;
				}
				\think\Db::name("pricing")->data($pricing)->limit(50)->insertAll();
			}
			unset($pricing);
		}
		$product_ids = array_column($products, "id");
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcim_area,a.os,b.gid")->leftJoin("servers b", "a.serverid=b.id")->whereIn("a.productid", $product_ids)->select()->toArray();
		foreach ($host as $v) {
			if (!empty($product_area_config[$v["productid"]])) {
				$sub_id = \think\Db::name("product_config_options_sub")->where("config_id", $product_area_config[$v["productid"]])->whereLike("option_name", $v["dcim_area"] . "|%")->value("id");
				if (!empty($sub_id)) {
					$r = \think\Db::name("host_config_options")->where("relid", $v["id"])->where("configid", $product_area_config[$v["productid"]])->value("id");
					if (!empty($r)) {
						\think\Db::name("host_config_options")->where("id", $r)->update(["optionid" => $sub_id, "qty" => 0]);
					} else {
						\think\Db::name("host_config_options")->insert(["relid" => $v["id"], "configid" => $product_area_config[$v["productid"]], "optionid" => $sub_id, "qty" => 0]);
					}
				}
			}
			if (!empty($product_os_config[$v["productid"]])) {
				$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $product_os_config[$v["productid"]])->whereLike("option_name", "%" . $v["os"] . "%")->find();
				if (!empty($sub)) {
					$r = \think\Db::name("host_config_options")->where("relid", $v["id"])->where("configid", $product_os_config[$v["productid"]])->value("id");
					if (!empty($r)) {
						\think\Db::name("host_config_options")->where("id", $r)->update(["optionid" => $sub["id"], "qty" => 0]);
					} else {
						\think\Db::name("host_config_options")->insert(["relid" => $v["id"], "configid" => $product_os_config[$v["productid"]], "optionid" => $sub["id"], "qty" => 0]);
					}
					$os_url = explode("^", explode($sub["option_name"], "|")[1])[0] ?: "";
					\think\Db::name("host")->strict(false)->where("id", $v["id"])->update(["os_url" => $os_url]);
				}
			}
		}
		return true;
	}
	public function moduleCron($type = "FiveMinuteCron")
	{
		$dir = CMF_ROOT . "modules/servers/";
		$dir2 = CMF_ROOT . "public/plugins/servers/";
		$module = \think\Db::name("servers")->where("server_type", "normal")->column("type");
		$module = array_unique($module);
		foreach ($module as $v) {
			if (file_exists($dir . $v . "/" . $v . ".php")) {
				require_once $dir . $v . "/" . $v . ".php";
				$func = $v . "_" . $type;
				if (function_exists($func)) {
					call_user_func($func);
				}
			} else {
				if (file_exists($dir2 . $v . "/" . $v . ".php")) {
					require_once $dir2 . $v . "/" . $v . ".php";
					$func = $v . "_" . $type;
					if (function_exists($func)) {
						call_user_func($func);
					}
				}
			}
		}
	}
	private function ad_log($cron_type = 0, $active_type = 0, $status = 0, $unique_id = 0)
	{
		$datetime = date("Ymd");
		$idata = ["cron_type" => \intval($cron_type), "active_type" => \intval($active_type), "status" => \intval($status), "create_time" => time(), "datetime" => $datetime, "unique_tab" => $datetime . $cron_type . $unique_id];
		$id = \think\Db::name("run_croning")->insertGetId($idata);
		return $id;
	}
	private function getCronConfig()
	{
		$cron_config = config("cron_config");
		$keys = array_keys($cron_config);
		$keys[] = "auto_pay_renew";
		$config = getConfig($keys);
		$config = array_merge($cron_config, $config);
		return $config;
	}
	private function doSyustemqueue()
	{
		$sys = system("php -v", $retval);
		var_dump($retval);
		exit;
	}
	private function getAppHeat()
	{
		$app_get_heat_time = !!configuration("app_get_heat_time") ? strtotime(configuration("app_get_heat_time")) : 0;
		$now = date("Y-m-d H") . ":00";
		$date = date("Y-m-d") . " 10:00";
		if (strtotime($date) >= $app_get_heat_time + 86400 && $now == $date) {
			updateConfiguration("app_get_heat_time", $date);
		} else {
			return false;
		}
		$products = \think\Db::name("products")->field("id")->where("p_uid", ">", 0)->where("app_status", 1)->where("retired", 0)->where("hidden", 0)->select()->toArray();
		foreach ($products as $key => $value) {
			$count = \think\Db::name("host")->field("id")->where("productid", $value["id"])->where("domainstatus", "Active")->count();
			$count2 = \think\Db::name("evaluation")->field("id")->where("rid", $value["id"])->where("type", "products")->where("score", ">", "4")->count();
			$heat = $count * 7 + $count2 * 2;
			\think\Db::name("products")->where("id", $value["id"])->update(["app_hot_heat" => $heat]);
		}
		$products = \think\Db::name("products")->where("p_uid", ">", 0)->where("hidden", 0)->where("retired", 0)->where("app_status", 1)->where("app_hot_lock!=1")->order("app_hot_heat", "desc")->select()->toArray();
		foreach ($products as $key => $value) {
			\think\Db::name("products")->where("id", $value["id"])->update(["app_hot_order" => $key + 1]);
		}
		$unlocks = \think\Db::name("products")->where("p_uid", ">", 0)->where("hidden", 0)->where("retired", 0)->where("app_status", 1)->where("app_hot_lock!=1")->where("app_hot_order!=0")->order("app_hot_heat", "desc")->select()->toArray();
		$unlocks = array_column($unlocks, "id", "app_hot_order");
		$locks = \think\Db::name("products")->where("p_uid", ">", 0)->where("hidden", 0)->where("retired", 0)->where("app_status", 1)->where("app_hot_lock=1")->where("app_hot_order!=0")->order("app_hot_order", "asc")->select()->toArray();
		$locks = array_column($locks, "id", "app_hot_order");
		$data = getNewOrderArr($unlocks, $locks);
		foreach ($data as $key => $value) {
			\think\Db::name("products")->where("id", $value)->update(["app_hot_order" => $key]);
		}
	}
	private function getHotOrder()
	{
		$app_hot_order = \think\Db::name("products")->order("app_hot_order", "desc")->select()->toArray();
		$app_hot_order = !empty($app_hot_order[0]) ? $app_hot_order[0]["app_hot_order"] : 0;
		$products = \think\Db::name("products")->where("p_uid", ">", 0)->where("hidden", 0)->where("retired", 0)->where("app_status", 1)->where("app_hot_order", 0)->order("app_hot_heat", "desc")->select()->toArray();
		foreach ($products as $key => $value) {
			$app_hot_order++;
			\think\Db::name("products")->where("id", $value["id"])->update(["app_hot_order" => $app_hot_order]);
		}
	}
	private function generateRepaymentBill()
	{
		if (cache("?generate_repayment_bill")) {
			return false;
		}
		$year = \intval(date("Y"));
		$month = \intval(date("m"));
		$day = \intval(date("d"));
		$days = date("t", strtotime($year . "-" . $month));
		if ($day == $days) {
			if ($day == 28) {
				$whereIn = [28, 29, 30, 31];
			} elseif ($day == 29) {
				$whereIn = [29, 30, 31];
			} elseif ($day == 30) {
				$whereIn = [30, 31];
			} else {
				$whereIn = [31];
			}
		} else {
			$whereIn = [$day];
		}
		$clients = \think\Db::name("clients")->field("id")->whereIn("bill_generation_date", $whereIn)->select()->toArray();
		if (count($clients) == 0) {
			return false;
		}
		cache("generate_repayment_bill", 1, 3600);
		$invoice = new \app\common\logic\Invoices();
		foreach ($clients as $key => $value) {
			$invoice->createCreditLimit($value["id"]);
		}
		cache("generate_repayment_bill", null);
	}
	public function creditLimitInvoice($config)
	{
		if ($config["cron_credit_limit_invoice_unpaid_email"] > 0) {
			$day = $config["cron_credit_limit_invoice_unpaid_email"];
			$host = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->withAttr("total", function ($value, $data) {
				return $value . $data["suffix"];
			})->where("b.status", "Unpaid")->where("b.due_time", ">=", strtotime(date("Y-m-d")) + $day * 24 * 3600)->where("b.due_time", "<=", strtotime(date("Y-m-d")) + $day * 24 * 3600 + 86400 - 1)->where("b.delete_time", 0)->where("b.type", "credit_limit")->select()->toArray();
			if (!empty($host[0])) {
				foreach ($host as $vv) {
					if (!cancelRequest($vv["id"])) {
						$email = new \app\common\logic\Email();
						$result = $email->sendEmailBase($vv["id"], "信用额账单已生成", "credit_limit", true);
						$message_template_type = array_column(config("message_template_type"), "id", "name");
						$tmp = \think\Db::name("invoices")->field("id,total")->where("id", $vv["id"])->find();
						$sms = new \app\common\logic\Sms();
						$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_notice")], $vv["uid"], $sms);
						if ($client) {
							$params = ["invoiceid" => $vv["id"], "total" => $vv["total"]];
							$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_notice")], $client["phone_code"] . $client["phonenumber"], $params, false, $vv["uid"]);
						}
						if ($result) {
							active_log_final("信用额账单未付款提醒 -  User ID:" . $vv["uid"] . "发送邮件成功", $vv["uid"], 5);
							$this->ad_log(10, 0, 1);
						} else {
							active_log_final("信用额账单未付款提醒 -  User ID:" . $vv["uid"] . "发送邮件失败", $vv["uid"], 5);
						}
					}
				}
			}
		}
		if ($config["cron_credit_limit_invoice_third_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 2);
		}
		if ($config["cron_credit_limit_invoice_second_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 1);
		}
		if ($config["cron_credit_limit_invoice_first_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 0);
		}
	}
	private function creditLimitInvoiceDueSend($config, $times = 0)
	{
		if ($times == 0) {
			$str = "first";
		} elseif ($times == 1) {
			$str = "second";
		} else {
			$str = "third";
		}
		$day = $config["cron_credit_limit_invoice_{$str}_overdue_email"];
		$before_day_start_time = strtotime("-{$day} days", strtotime(date("Y-m-d")));
		$before_day_end_time = strtotime("+1 days -1 seconds", $before_day_start_time);
		$host = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->withAttr("total", function ($value, $data) {
			return $value . $data["suffix"];
		})->where("b.status", "Unpaid")->where("b.due_time", "<=", $before_day_end_time)->where("b.due_email_times", $times)->where("b.delete_time", 0)->where("b.type", "credit_limit")->select()->toArray();
		if (!empty($host[0])) {
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$hostids = [];
			foreach ($host as $v) {
				$email = new \app\common\logic\Email();
				$result = $email->sendEmailBase($v["id"], "信用额账单逾期提醒", "credit_limit", true);
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_payment_reminder")], $v["uid"], $sms);
				if ($client) {
					$params = ["invoiceid" => $v["id"], "total" => $v["total"]];
					$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_payment_reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $v["uid"]);
				}
				if ($result) {
					active_log_final("信用额账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒成功", $v["uid"], 5);
					\think\Db::name("invoices")->where("id", $v["id"])->where("delete_time", 0)->where("due_email_times", $times)->setInc("due_email_times");
					$this->ad_log(11, 0, 1);
				} else {
					active_log_final("信用额账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒失败", $v["uid"], 5);
				}
			}
		}
	}
	public function creditLimit($config)
	{
		$logic_host = new \app\common\logic\Host();
		$logic_host->is_admin = true;
		$now = time();
		$shd_credit_limit_liquidated_damages = configuration("shd_credit_limit_liquidated_damages") ?? 0;
		$shd_credit_limit_liquidated_damages_percent = configuration("shd_credit_limit_liquidated_damages_percent") ?? 0;
		if ($shd_credit_limit_liquidated_damages == 1) {
			$invoices = \think\Db::name("invoices")->alias("b")->field("b.id,b.total,b.uid,c.amount")->leftJoin("invoice_items c", "c.invoice_id=b.id")->where("b.status", "Unpaid")->where("b.due_time", ">=", 0)->where("b.due_time", "<=", time())->where("b.delete_time", 0)->where("b.type", "credit_limit")->where("c.type", "credit_limit")->select()->toArray();
			if (count($invoices) > 0) {
				foreach ($invoices as $key => $value) {
					\think\Db::name("invoice_items")->insertGetId(["invoice_id" => $value["id"], "uid" => $value["uid"], "type" => "liquidated_damages", "rel_id" => 0, "description" => date("Y-m-d") . "逾期违约金", "amount" => round($value["amount"] * $shd_credit_limit_liquidated_damages_percent / 100, 2)]);
					\think\Db::name("invoices")->where("id", $value["id"])->update(["subtotal" => $value["total"] + round($value["amount"] * $shd_credit_limit_liquidated_damages_percent / 100, 2), "total" => $value["total"] + round($value["amount"] * $shd_credit_limit_liquidated_damages_percent / 100, 2)]);
				}
			}
		}
		$time = intval($config["cron_credit_limit_suspend_time"]);
		$send = $config["cron_credit_limit_suspend_time"] ?? 0;
		if ($time > 0) {
			$invoices = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->withAttr("total", function ($value, $data) {
				return $value . $data["suffix"];
			})->where("b.status", "Unpaid")->where("b.due_time", ">=", 0)->where("b.due_time", "<=", time() - $time * 24 * 3600)->where("b.delete_time", 0)->where("b.type", "credit_limit")->select()->toArray();
			if (count($invoices) > 0) {
				$host = \think\Db::name("host")->alias("b")->field("b.id,b.uid,b.domain,b.nextinvoicedate,e.name")->leftJoin("orders c", "c.id = b.orderid")->leftJoin("invoices d", "d.id=c.invoiceid")->leftJoin("products e", "e.id = b.productid")->whereIn("b.domainstatus", "Active")->whereIn("d.invoice_id", array_column($invoices, "id"))->select()->toArray();
				$reason = lang("SERVICE_HAS_EXPIRED");
				if (!empty($host[0])) {
					foreach ($host as $v) {
						$result = $logic_host->suspend($v["id"], "due", $reason);
						$logic_run_map = new \app\common\logic\RunMap();
						$model_host = new \app\common\model\HostModel();
						$data_i = [];
						$data_i["host_id"] = $v["id"];
						$data_i["active_type_param"] = [$v["id"], "due", $reason, 0];
						$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
						if ($result["status"] == 200) {
							$data_i["description"] = "信用额账单未支付 - 暂停 Host ID:{$data_i["host_id"]}的产品成功";
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 400, 2);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 100, 2);
							}
							$this->ad_log(3, 2, 1, $v["id"]);
							$logic_run_map->cronSuccess(time(), 3, $v["id"]);
						} else {
							$data_i["description"] = "信用额账单未支付 - 暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 400, 2);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 100, 2);
							}
							$this->ad_log(3, 2, 0, $v["id"]);
						}
						$email = new \app\common\logic\Email();
						$result = $email->sendEmailBase($v["id"], "信用额账单未支付暂停产品", "product", true);
						$sms = new \app\common\logic\Sms();
						$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_payment_reminder_host_suspend")], $v["uid"], $sms);
						if ($client) {
							$params = ["product_name" => $v["name"], "hostname" => $v["domain"], "product_end_time" => date("Y-m-d H:i:s", $v["nextinvoicedate"]), "product_terminate_time" => date("Y-m-d H:i:s", $v["nextinvoicedate"])];
							$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_payment_reminder_host_suspend")], $client["phone_code"] . $client["phonenumber"], $params, false, $v["uid"]);
						}
						if ($result["status"] == 200) {
							active_log_final("信用额账单未支付产品到期暂停 - 暂停产品 Host ID:" . $v["id"] . "成功", $v["uid"], 5);
						} else {
							active_log_final("信用额账单未支付产品到期暂停 - 暂停产品 Host ID:" . $v["id"] . "失败,失败原因:" . $result["msg"], $v["uid"], 5);
						}
					}
				}
			}
		}
	}
	public function contractHostSuspended()
	{
		$host_logic = new \app\common\logic\Host();
		$host_logic->is_admin = true;
		$contracts = \think\Db::name("contract")->where("status", 1)->where("force", 1)->select()->toArray();
		$all = \think\Db::name("clients")->column("id");
		foreach ($contracts as $contract) {
			$signed = \think\Db::name("contract_pdf")->where("contract_id", $contract["id"])->whereIn("status", [1, 3, 4])->column("uid");
			$day = intval($contract["suspended"]);
			if ($contract["base"]) {
				$unsigned = array_diff($all, $signed);
				if ($contract["suspended_type"] == "suspended" && !empty($unsigned)) {
					$hosts = \think\Db::name("host")->whereIn("uid", $unsigned)->where("domainstatus", "Active")->where("regdate", "<", time() - $day * 24 * 3600)->select()->toArray();
				}
			} else {
				$pids = explode(",", $contract["product_id"]);
				if ($contract["suspended_type"] == "suspended" && !empty($pids)) {
					$hosts = \think\Db::name("host")->whereIn("productid", $pids)->whereNotIn("uid", $signed)->where("domainstatus", "Active")->where("regdate", "<", time() - $day * 24 * 3600)->select()->toArray();
				}
			}
			$logic_run_map = new \app\common\logic\RunMap();
			$model_host = new \app\common\model\HostModel();
			$reason = "强制合同未签订暂停";
			$send = 0;
			if (!empty($hids)) {
				foreach ($hosts as $v) {
					$result = $host_logic->suspend($v["id"], "contract", $reason);
					if ($result["status"] == 200) {
						active_log_final("强制合同未签订暂停 - 暂停产品 Host ID:" . $v["id"] . "成功", $v["uid"], 5);
						$data_i["host_id"] = $v["id"];
						$data_i["description"] = "强制合同未签订暂停 Host ID:{$v["id"]}成功";
						$data_i["active_type_param"] = [$v["id"], "due", $reason, $send];
						$is_zjmf = $model_host->isZjmfApi($v["id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 2);
						}
						$this->ad_log(1, 2, 1, $v["id"]);
						$logic_run_map->cronSuccess(time(), 1, $v["id"]);
					} else {
						active_log_final("强制合同未签订暂停 - 暂停产品 Host ID:" . $v["id"] . "失败,失败原因:" . $result["msg"], $v["uid"], 5);
						$data_i["host_id"] = $v["id"];
						$data_i["description"] = "强制合同未签订暂停 Host ID:{$v["id"]}失败。原因:{$result["msg"]}";
						$data_i["active_type_param"] = [$v["id"], "due", $reason, $send];
						$is_zjmf = $model_host->isZjmfApi($v["id"]);
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 2);
						}
						$this->ad_log(1, 2, 0, $v["id"]);
					}
				}
			}
		}
		return true;
	}
}