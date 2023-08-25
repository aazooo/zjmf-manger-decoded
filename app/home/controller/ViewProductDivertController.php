<?php

namespace app\home\controller;

define("VIEW_TEMPLATE_DIRECTORY", "clientarea");
define("VIEW_TEMPLATE_WEBSITE", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SUFFIX", "tpl");
class ViewProductDivertController extends ViewBaseController
{
	/** 转移页面 与操作
	 * @param Request $request
	 * @return string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function pushserver(\think\Request $request)
	{
		$param = $request->param();
		$res = \think\Db::name("plugin")->where("name", "ProductDivert")->find();
		$system = json_decode($res["config"], true);
		$host_id = $param["id"];
		$product = \think\Db::name("host")->alias("h")->leftJoin("products p", "h.productid=p.id")->field("h.domain,h.dedicatedip,p.name,p.id,h.id as hid")->where(["h.id" => $host_id, "h.uid" => $param["uid"]])->find();
		if (!in_array($product["id"], $system["product_range"])) {
			$this->error("该服务不支持转移");
		}
		$product_divert = \think\Db::name("product_divert")->field("id")->where(["hostid" => $host_id, "status" => 1])->find();
		if ($product_divert) {
			$this->error("该服务正在转移流程，不能再次转移");
		}
		$key = substr(md5(config("auth_token") . $host_id), 16);
		if ($request->isPost()) {
			$token = $param["token"];
			if ($token !== $key) {
				$this->error("系统繁忙请稍后再试...");
			}
			if ($param["uid"] == $param["userid"]) {
				$this->error(lang("YOU_CANNOT_TRANSFER_THE_PRODUC"));
			}
			$touser = \think\Db::name("clients")->field("id,username")->where(["id" => $param["userid"]])->find();
			$dataArr = [];
			$dataArr["hostid"] = $host_id;
			$dataArr["product_name"] = $product["name"] ?? "NAME:N/A";
			$dataArr["product_domain"] = $product["domain"] ?? "DOMAIN:N/A";
			$dataArr["product_ip"] = $product["dedicatedip"] ?? "IP:N/A";
			$dataArr["push_userid"] = $param["uid"];
			$dataArr["push_username"] = $param["uname"];
			$dataArr["pull_userid"] = $touser["id"];
			$dataArr["pull_username"] = $touser["username"];
			$dataArr["create_time"] = time();
			$dataArr["due_time"] = time() + 86400 * $system["validity_period"];
			$dataArr["push_cost"] = $system["push_cost"];
			$dataArr["pull_cost"] = $system["pull_cost"];
			\think\Db::startTrans();
			try {
				$pd_id = \think\Db::name("product_divert")->insertGetId($dataArr);
				if ($pd_id) {
					$ProductDivert = controller("ProductDivert");
					$param["product_divert_id"] = $pd_id;
					$param["type"] = "PUSH";
					$res = $ProductDivert->divertInvoice($param, $dataArr);
					if ($res["status"] == 200) {
						\think\Db::commit();
					}
				}
			} catch (\Exception $e) {
				\think\Db::rollback();
				$this->error($e->getMessage());
			}
			if ($res) {
				header("location:/product_divert/pushpulllist");
				exit;
			}
		}
		$data["product"] = $product;
		$data["system"] = $system;
		$data["token"] = $key;
		return $this->view("product_divert/pushserver", $data);
	}
	/** 转入 - 转出 列表
	 * @param Request $request
	 * @return string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function pushpulllist(\think\Request $request)
	{
		$status = [1 => "待接收", 2 => "已完成", 3 => "已关闭", 4 => "已拒绝"];
		$param = $request->param();
		$product_divert = \think\Db::name("product_divert")->field("id,push_invoice_id,pull_invoice_id,product_name,product_domain,product_ip,push_userid,pull_userid,push_username,pull_username,push_cost,pull_cost,status")->whereOr(["push_userid" => $param["uid"], "pull_userid" => $param["uid"]])->withAttr("create_time", function ($value) {
			return date("Y-m-d H:i", $value);
		})->withAttr("end_time", function ($value) {
			return $value ? date("Y-m-d H:i", $value) : "N/A";
		})->withAttr("status", function ($value) use($status) {
			return $status[$value];
		})->select()->toArray();
		foreach ($product_divert as $k => $v) {
			if ($v["push_invoice_id"]) {
				$product_divert[$k]["push_pay_status"] = \think\Db::name("invoices")->where(["id" => $v["push_invoice_id"]])->value("status");
			}
			if ($v["pull_invoice_id"]) {
				$product_divert[$k]["pull_pay_status"] = \think\Db::name("invoices")->where(["id" => $v["pull_invoice_id"]])->value("status");
			}
		}
		$data["product_divert"] = $product_divert;
		$data["user_now"] = $param["uid"];
		return $this->view("product_divert/pushpulllist", $data);
	}
	public function pullserver(\think\Request $request)
	{
		$param = $request->param();
		$product_divert = \think\Db::name("product_divert")->alias("p")->leftJoin("invoices i", "p.push_invoice_id=i.id")->where(["p.id" => $param["id"], "p.pull_userid" => $param["uid"]])->field("p.*,i.status as pay_status")->find();
		if (!$product_divert) {
			$this->error("未检测到该条记录");
		}
		if ($product_divert["pay_status"] == "Unpaid") {
			$this->error("转让方还未付款，接收方不能进行操作");
		}
		if ($request->isPost()) {
			$ProductDivert = controller("ProductDivert");
			$param["product_divert_id"] = $param["id"];
			$param["type"] = "PULL";
			$res = $ProductDivert->divertInvoice($param, $product_divert);
			if ($res) {
				header("location:/product_divert/pushpulllist");
				exit;
			}
		}
		$data["product"] = $product_divert;
		return $this->view("product_divert/pullserver", $data);
	}
	public function pullrefuse(\think\Request $request)
	{
		$param = $request->param();
		\think\Db::startTrans();
		try {
			$invoice = \think\Db::name("product_divert")->alias("p")->leftJoin("invoices i", "p.push_invoice_id=i.id")->where(["p.id" => $param["id"], "p.pull_userid" => $param["uid"]])->field("i.status as pay_status,i.subtotal,p.push_invoice_id,p.push_userid")->find();
			if ($invoice["pay_status"] == "Unpaid") {
				\think\Db::name("product_divert")->where(["id" => $param["id"], "pull_userid" => $param["uid"]])->update(["status" => 4]);
				\think\Db::name("invoices")->where(["id" => $invoice["push_invoice_id"]])->update(["status" => "Cancelled"]);
				\think\Db::commit();
			}
			if ($invoice["pay_status"] == "Paid") {
				\think\Db::name("product_divert")->where(["id" => $param["id"], "pull_userid" => $param["uid"]])->update(["status" => 4]);
				\think\Db::name("invoices")->where(["id" => $invoice["push_invoice_id"]])->update(["status" => "Refunded"]);
				\think\Db::name("clients")->where(["id" => $invoice["push_userid"]])->setInc("credit", $invoice["subtotal"]);
				\think\Db::commit();
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
		}
		header("location:/product_divert/pushpulllist");
		exit;
	}
	public function pushrefuse(\think\Request $request)
	{
		$param = $request->param();
		$divert = \think\Db::name("product_divert")->where(["id" => $param["id"], "push_userid" => $param["uid"], "status" => 1])->find();
		if (!$divert) {
			$this->error("未找到该记录,稍后请重试");
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("product_divert")->where(["id" => $param["id"], "push_userid" => $param["uid"]])->update(["status" => 3]);
			\think\Db::name("invoices")->where(["id" => $divert["push_invoice_id"]])->update(["status" => "Cancelled"]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
		}
		header("location:/product_divert/pushpulllist");
		exit;
	}
	public function verificationResult(\think\Request $request)
	{
		$ProductDivert = controller("ProductDivert");
		$ProductDivert->checkPayStatus($request);
		header("location:/product_divert/pushpulllist");
		exit;
	}
}