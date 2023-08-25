<?php

namespace app\home\controller;

/**
 * @title 前台合同模块
 * @description 接口说明: 合同模块
 */
class ContractController extends CommonController
{
	public function initialize()
	{
		parent::initialize();
		if (!configuration("contract_open")) {
			echo json_encode(["status" => 400, "msg" => "合同功能未开启"]);
			exit;
		}
		if (!getEdition()) {
			echo json_encode(["status" => 400, "msg" => "合同功能仅专业版可用"]);
			exit;
		}
	}
	/**
	 * @title 产品列表,可签订合同产品
	 * @description 接口说明:产品列表,可签订合同产品
	 * @author wyh
	 * @time 2021-07-21
	 * @url /contract/host
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:keywords type:string require:0 default:1 other: desc:按关键字搜索
	 * @param .name:type type:string require:0 default:1 other: desc:时间筛选类型create_time,nextduedate
	 * @param .name:start_time type:int require:0  other: desc:时间筛选:开始时间(到期时间),传时间戳(注意：精确到秒)
	 * @param .name:end_time type:int require:0  other: desc:时间筛选:结束时间
	 * @return  hosts:产品列表@
	 * @hosts  id:产品ID
	 * @hosts  name:产品名
	 * @hosts  dedicatedip：ip
	 * @hosts  domain:主机
	 * @hosts  domainstatus：状态
	 * @hosts  amount:金额
	 * @hosts  create_time:下单时间
	 * @hosts  nextduedate:到期时间
	 * @hosts  pdf_num:合同编号(关联合同)
	 * @hosts  status_zh:付款状态
	 */
	public function host()
	{
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$order = !empty($param["order"]) ? trim($param["order"]) : "b.create_time";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
		$where = function (\think\db\Query $query) use($param) {
			$start_time = $param["start_time"] ?: 0;
			$end_time = intval($param["end_time"]);
			if (!empty($param["type"])) {
				if ($param["type"] == "create_time") {
					$query->where("b.create_time", ">=", $start_time)->where("b.create_time", "<=", $end_time);
				} elseif ($param["type"] == "nextduedate") {
					$query->where("a.nextduedate", ">=", $start_time);
					if ($end_time) {
						$query->where("a.nextduedate", "<=", $end_time);
					}
				}
			}
			if (!empty($param["keywords"])) {
				$query->where("a.id|c.name|a.dedicatedip", "like", "%{$param["keywords"]}%");
			}
			$query->where("a.uid", request()->uid);
			$query->whereIn("a.domainstatus", ["Active", "Suspended"]);
			$query->whereIn("a.productid", (new \app\common\logic\Contract())->getContractProducts());
			$query->where("d.id", null)->whereOr(function (\think\db\Query $query) {
				$query->where("d.id", ">", 0)->where("d.status", 0);
			});
		};
		$total = \think\Db::name("host")->alias("a")->leftJoin("orders b", "a.orderid=b.id")->leftJoin("products c", "a.productid=c.id")->leftJoin("clients e", "a.uid=e.id")->leftJoin("contract_pdf d", "a.id=d.host_id")->leftJoin("invoices f", "f.id=b.invoiceid")->where($where)->group("a.id", "desc")->count();
		$hosts = \think\Db::name("host")->alias("a")->field("a.id,c.name,a.dedicatedip,a.domain,a.domainstatus,a.domainstatus as domainstatus_zh,
            a.amount,b.create_time,a.nextduedate,d.pdf_num,e.companyname,e.phone_code,e.phonenumber,
            e.email,e.address1,f.status,f.status as status_zh,d.status as pdf_status,d.id as pdf_id")->leftJoin("orders b", "a.orderid=b.id")->leftJoin("products c", "a.productid=c.id")->leftJoin("clients e", "a.uid=e.id")->leftJoin("contract_pdf d", "a.id=d.host_id")->leftJoin("invoices f", "f.id=b.invoiceid")->where($where)->withAttr("status_zh", function ($value) {
			if (is_null($value)) {
				return [];
			}
			return config("invoice_payment_status")[$value];
		})->limit($limit)->page($page)->group("a.id", "desc")->order($order, $sort)->select()->toArray();
		$i = 0;
		$hosts_filter = [];
		foreach ($hosts as $k => $host) {
			$count = \think\Db::name("contract_pdf")->where("host_id", $host["id"])->where("status", "<>", 0)->count();
			if ($count > 0) {
				++$i;
			} else {
				$hosts_filter[] = $host;
			}
		}
		$data = ["count" => $total - $i, "hosts" => $hosts_filter, "limit" => $limit, "currency" => getUserCurrency(request()->uid)];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 签订合同甲方信息管理
	 * @description 接口说明:签订合同甲方信息管理
	 * @author wyh
	 * @url /contract/base_info
	 * @method POST
	 * @param .name:address1 type:int require:1 default:1 other: desc:地址
	 * @param .name:phonenumber type:int require:1 default:1 other: desc:联系电话
	 * @param .name:email type:int require:1 default:1 other: desc:电子邮箱
	 */
	public function contractBaseInfo()
	{
		$param = $this->request->param();
		\think\Db::name("clients")->where("id", request()->uid)->update(["address1" => trim($param["address1"]), "phonenumber" => trim($param["phonenumber"]), "email" => trim($param["email"])]);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 创建合同
	 * @description 接口说明:创建合同
	 * @author wyh
	 * @url /contract/contract
	 * @method POST
	 * @param .name:hid type:int require:1 default:1 other: desc:购买产品的ID（hostID） 和下面参数二选一传
	 * @param .name:tplid type:int require:1 default:1 other: desc:合同模板ID
	 */
	public function contractCreate()
	{
		$param = $this->request->param();
		$hid = intval($param["hid"]);
		$tplid = intval($param["tplid"]);
		$uid = request()->uid;
		if (empty($hid) && empty($tplid)) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if (!(new \app\home\model\ClientsModel())->getUserCertifi($uid)) {
			return json(["status" => 400, "msg" => "请完善甲方信息"]);
		}
		if ($hid) {
			$contract = \think\Db::name("contract_pdf")->alias("a")->leftJoin("contract b", "a.contract_id=b.id")->where("a.uid", $uid)->where("a.host_id", $hid)->where("b.base", "<>", 1)->where("b.status", 1)->where("a.status", "<>", 0)->order("a.id", "desc")->find();
		} else {
			if ($tplid) {
				$contract = \think\Db::name("contract_pdf")->alias("a")->leftJoin("contract b", "a.contract_id=b.id")->where("a.contract_id", $tplid)->where("a.uid", $uid)->where("b.base", 1)->where("b.status", 1)->where("a.status", "<>", 0)->order("a.id", "desc")->find();
			}
		}
		if (!empty($contract)) {
			return json(["status" => 400, "msg" => "合同已存在"]);
		}
		if (!empty($tplid)) {
			\think\Db::name("contract_pdf")->where("contract_id", $tplid)->whereIn("status", [0, 2])->delete();
		}
		$contrac_logic = new \app\common\logic\Contract();
		$pdf_num = $contrac_logic->createContractNum();
		if (empty($hid)) {
			$contract_id = $tplid;
		} else {
			$contract_id = $contrac_logic->getTplId($hid);
		}
		if (empty($contract_id)) {
			return jsons(["status" => 400, "msg" => "产品无合同模板"]);
		}
		$res = [];
		$res["pdf_num"] = $pdf_num;
		$res["contract_id"] = $contract_id;
		$res["uid"] = $uid;
		$res["host_id"] = $hid;
		$res["status"] = 2;
		$res["pdf_address"] = "";
		$res["information"] = $_SERVER["HTTP_USER_AGENT"];
		$res["ip"] = get_client_ip();
		$res["create_time"] = time();
		$res["remark"] = "";
		$id = \think\Db::name("contract_pdf")->insertGetId($res);
		$data = ["id" => $id];
		$id = intval($data["id"]);
		$this->request->id = $id;
		$res = $this->contract();
		return $res;
	}
	/**
	 * @title 签订合同页面
	 * @description 接口说明:签订合同页面
	 * @author wyh
	 * @url /contract/contract_page
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function contract()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		if ($contract_pdf["status"] != 2) {
			return jsons(["status" => 400, "msg" => "合同非待签订状态"]);
		}
		$client = \think\Db::name("clients")->field("phonenumber,username,email,address1,companyname")->where("id", $uid)->find();
		(new \app\home\model\ClientsModel())->replaceClientName($uid, $client);
		$pdf_logo = config("contract_get") . configuration("contract_pdf_logo");
		$company_logo = config("contract_get") . configuration("contract_company_logo");
		$tpl_id = $contract_pdf["contract_id"];
		$contract = \think\Db::name("contract")->field("id,name,represent,phonenumber,content,email,inscribe_custom")->where("id", $tpl_id)->where("status", 1)->find();
		if (empty($contract)) {
			return jsons(["status" => 400, "msg" => "合同模板不存在"]);
		}
		$hid = $contract_pdf["host_id"];
		$contract_logic = new \app\common\logic\Contract();
		if (!empty($hid)) {
			$contract["content"] = $contract_logic->replaceArg($contract["content"], $hid);
		} else {
			$contract["content"] = $contract_logic->replaceArg($contract["content"], $hid, 1, $uid);
		}
		$host = \think\Db::name("host")->alias("a")->field("b.ordernum,d.name,c.description,a.amount,a.create_time,a.nextduedate")->leftJoin("orders b", "a.orderid=b.id")->leftJoin("products d", "a.productid=d.id")->leftJoin("invoice_items c", "a.id=c.rel_id")->where("c.type", "host")->where("a.id", $hid)->find();
		if ($contract["inscribe_custom"]) {
			$party_b = ["institutions" => $contract["represent"], "addr" => configuration("contract_address"), "username" => configuration("contract_username"), "phone" => $contract["phonenumber"], "email" => $contract["email"]];
		} else {
			$party_b = ["institutions" => configuration("contract_institutions"), "addr" => configuration("contract_address"), "username" => configuration("contract_username"), "phone" => configuration("contract_phonenumber"), "email" => configuration("contract_email")];
		}
		$company = $client["companyname"];
		$addr = $client["address1"];
		$username = $client["username"];
		if (file_exists(CMF_ROOT . "app/res/common.php") && function_exists("resourceCurl")) {
			if ($contract["id"] == 1) {
				$supplier = \think\Db::name("supplier")->where("uid", $uid)->find();
				$company = $supplier["company"];
				$addr = $supplier["address"];
				$username = $supplier["name"];
			} elseif ($contract["id"] == 2) {
				$agent = \think\Db::name("agent")->where("uid", $uid)->find();
				$username = $agent["name"];
				$addr = $agent["address"];
				$company = $agent["company"] ?: $company;
			}
		}
		$data = ["party" => ["institutions" => $company, "addr" => $addr, "username" => $username, "phone" => $client["phonenumber"], "email" => $client["email"]], "party_b" => $party_b, "pdf_logo" => $pdf_logo, "company_logo" => $company_logo, "contract" => $contract, "host" => $host ?: [], "id" => $id];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 提交签名
	 * @description 接口说明:提交签名
	 * @author wyh
	 * @url /contract/contract_sign
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @param .name:sign type:int require:1 default:1 other: desc:签名base64字符串
	 * @return  addr:签名保存地址
	 */
	public function contractSign()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$count = \think\Db::name("contract_pdf")->where("id", $id)->count();
		if ($count < 1) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		$str = trim($param["sign"]);
		$img = base64DecodeImage($str, config("contract_sign"));
		if (!$img) {
			return jsons(["status" => 400, "msg" => "签名失败"]);
		}
		\think\Db::name("contract_pdf")->where("id", $id)->update(["sign_addr" => $img]);
		$data = ["addr" => config("contract_sign_get") . $img];
		return jsons(["status" => 200, "msg" => "签名成功", "data" => $data]);
	}
	/**
	 * @title 签订合同
	 * @description 接口说明:签订合同(生成PDF文档)
	 * @author wyh
	 * @url /contract/contract/:id
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @param .name:sign type:int require:1 default:1 other: desc:签名base64字符串
	 * @param .name:content type:int require:1 default:1 other: desc:合同内容,传html
	 * @param .name:type type:int require:1 default:1 other: desc:类型：I输出到浏览器 F输出到指定路径
	 */
	public function contractPost()
	{
		$uid = request()->uid;
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		$param = $this->request->param();
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		if (!empty($param["sign"])) {
			$str = trim($param["sign"]);
			$img = base64DecodeImage($str, config("contract_sign"));
			if (!$img) {
				return jsons(["status" => 400, "msg" => "签名失败"]);
			}
		}
		$contract = \think\Db::name("contract")->where("id", $contract_pdf["contract_id"])->find();
		$info["user"] = $client["username"];
		$info["title"] = $contract["name"];
		$info["subject"] = "";
		$info["keywords"] = "";
		$info["content"] = html_entity_decode($param["content"]);
		$info["HT"] = true;
		$pdf_address = time() . uniqid($uid) . ".pdf";
		$info["path"] = config("contract") . $pdf_address;
		if (!file_exists(dirname($info["path"]))) {
			mkdir(dirname($info["path"]), 744);
		}
		$info["html_align"] = "";
		$locator_image = config("contract") . configuration("contract_company_logo");
		$sign_image = config("contract_sign") . $img;
		$cover_image = config("contract") . configuration("contract_pdf_logo");
		if (!is_file($locator_image)) {
			return jsons(["status" => 400, "msg" => "系统合同印章暂未准备就绪，目前暂不支持签订！"]);
		}
		$string = "浏览器:%s, 操作系统:%s, 时间:%s, IP:%s";
		$paf_head = sprintf($string, getUserAgent(), getOS(), date("Y-m-d H:i"), get_client_ip() . (getPort() ? ":" . getPort() : ""));
		$pdf_logic = new \app\common\logic\Pdf();
		$pdf_logic->setPdfHead($paf_head);
		$pdf_logic->createPDFConfig($info);
		$pdfObj = $pdf_logic->getPdfObject();
		$pdfObj->SetFont("stsongstdlight", "", 10);
		if (is_file($cover_image)) {
			$locator_data["p"] = 1;
			$locator_data["x"] = 15;
			$locator_data["y"] = 15;
			$pdf_logic->createSeal($cover_image, $pdfObj->getPageWidth(), $pdfObj->getPageHeight(), $locator_data);
			$pdfObj->lastPage();
			$pdfObj->AddPage();
		}
		$pdfObj->writeHTML($info["content"], true, false, true, false);
		$pdfObj->lastPage();
		$location_page = $pdfObj->getNumPages();
		$location_y = $pdfObj->getY();
		$location_x = $pdfObj->getPageWidth();
		$image_w = $image_h = 50;
		$locator_data["p"] = $location_page;
		$locator_data["x"] = $location_x - 95;
		$locator_data["y"] = $location_y - $image_h - 5;
		$pdf_logic->createSeal($locator_image, $image_w, $image_h, $locator_data);
		$image_user_w_h = 30;
		$locator_data["p"] = $location_page;
		$locator_data["x"] = $image_user_w_h;
		$locator_data["y"] = $location_y - $image_user_w_h - 5;
		$pdf_logic->createSeal($sign_image, $image_user_w_h * 2, $image_user_w_h, $locator_data);
		if (empty($contract["base"])) {
			$pdfObj->AddPage();
			$pdfObj->writeHTML(html_entity_decode($param["enclosure"]), true, false, true, false);
		}
		$pdfObj->Output($info["path"], "F");
		unlink(config("contract_sign") . $contract_pdf["sign_addr"]);
		unlink(config("contract") . $contract_pdf["pdf_address"]);
		if ($param["type"] == "I") {
			\think\Db::name("contract_pdf")->where("id", $id)->update(["pdf_address" => $pdf_address]);
		} else {
			$up = \think\Db::name("contract_pdf")->where("id", $id)->update(["sign_addr" => $img ?: "", "pdf_address" => $pdf_address, "status" => 1]);
			if ($up) {
				hook("after_sign_contract", ["id" => $id]);
				$send = 0;
				$type = "contract";
				$host_logic = new \app\common\logic\Host();
				$hosts = \think\Db::name("host")->where("suspendreason", "like", "contract%")->where("domainstatus", "Suspended")->where("uid", $uid)->select()->toArray();
				foreach ($hosts as $host) {
					$result = $host_logic->unsuspend($host["id"], $send, $type);
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $host["id"];
					$data_i["active_type_param"] = [$host["id"], $send, $type, 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 满足签订合同解除暂停条件 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 3);
						}
					} else {
						$data_i["description"] = " 满足签订合同解除暂停条件 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
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
		return json(["status" => 200, "msg" => "签订成功"]);
	}
	/**
	 * @title 合同管理
	 * @description 接口说明:合同管理
	 * @author wyh
	 * @url /contract/contract
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:domainstatus type:string require:0 default:1 other: desc:按产品状态搜索
	 * @param .name:status type:string require:0 default:1 other: desc:按合同状态搜索
	 * @return  lists:合同列表@
	 * lists  id:
	 * lists  pdf_num:合同编号
	 * lists  name:产品名
	 * lists  domain：主机
	 * lists  dedicatedip：ip
	 * lists  amount：金额
	 * lists  create_time：下单时间
	 * lists  nextduedate：到期时间
	 * lists  domainstatus：产品状态
	 * lists  domainstatus_zh
	 * lists  status：合同状态
	 * lists  status_zh:
	 * lists  express_company:（邮寄信息）快递公司
	 * lists  express_order:快递单号
	 * lists  force:是否强制：0否(默认)，1是
	 */
	public function contractList()
	{
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$order = !empty($param["order"]) ? trim($param["order"]) : "a.id";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
		$where = function (\think\db\Query $query) use($param) {
			if (!empty($param["domainstatus"])) {
				$query->where("b.domainstatus", $param["domainstatus"]);
			}
			if (isset($param["status"]) && \strval($param["status"]) !== "") {
				$query->where("a.status", $param["status"]);
			}
			if (!empty($param["uid"])) {
				$query->where("a.uid", $param["uid"]);
			}
		};
		$total = \think\Db::name("contract_pdf")->alias("a")->leftJoin("host b", "a.host_id=b.id")->leftJoin("products c", "b.productid=c.id")->leftJoin("contract d", "a.contract_id=d.id")->leftJoin("orders e", "b.orderid=e.id")->where($where)->count();
		$lists = \think\Db::name("contract_pdf")->alias("a")->field("a.id,a.pdf_num,c.name,b.domain,b.dedicatedip,b.amount,e.create_time,b.nextduedate,b.domainstatus,d.force,
            b.domainstatus as domainstatus_zh,a.status,a.status as status_zh,a.express_company,a.express_order,a.cancel_post,a.host_id")->leftJoin("host b", "a.host_id=b.id")->leftJoin("products c", "b.productid=c.id")->leftJoin("contract d", "a.contract_id=d.id")->leftJoin("orders e", "b.orderid=e.id")->where($where)->withAttr("domainstatus_zh", function ($value) {
			return config("public.domainstatus")[$value];
		})->withAttr("status_zh", function ($value) {
			return config("contract_status")[$value];
		})->withAttr("create_time", function ($value) {
			return empty($value) ? "" : date("Y-m-d H:i", $value);
		})->withAttr("nextduedate", function ($value) {
			return empty($value) ? "" : date("Y-m-d H:i", $value);
		})->limit($limit)->page($page)->order($order, $sort)->select()->toArray();
		foreach ($lists as &$v) {
			if ($v["status"] != 3) {
				continue;
			}
			$invoice_id = \think\Db::name("contract_pdf")->alias("a")->leftJoin("invoice_items b", "a.id=b.rel_id")->where("a.id", $v["id"])->where("b.type", "contract")->value("b.invoice_id");
			$v["invoice_id"] = $invoice_id;
		}
		$contract_status = config("contract_status");
		$contract_status = array_reverse($contract_status);
		$contract_status["All"] = "全部";
		$data = ["total" => $total, "lists" => $lists, "domainstatus" => config("domainstatus"), "status" => array_reverse($contract_status), "currency" => getUserCurrency(request()->uid), "bases" => (new \app\common\logic\Contract())->getUnsignedBaseContract(request()->uid)];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 查看下载
	 * @description 接口说明:查看合同详情
	 * @author wyh
	 * @url /contract/download/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @return .pdf_address:合同地址
	 */
	public function download()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->field("pdf_address")->where("id", $id)->where("uid", $uid)->find();
		if (empty($contract_pdf)) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		$data = ["pdf_address" => config("contract_get") . $contract_pdf["pdf_address"]];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 申请邮寄页面
	 * @description 接口说明:申请邮寄页面,收件人信息
	 * @author wyh
	 * @url /contract/post/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @return voucher:收件人信息@
	 * @voucher usernmae:姓名
	 * @voucher detail:地址
	 * @voucher phone：电话
	 */
	public function postPage()
	{
		$uid = request()->uid;
		$voucher = \think\Db::name("voucher_post")->field("id,username,detail,phone")->where("uid", $uid)->where("default", 1)->find();
		$data = ["voucher" => $voucher ?: []];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 申请邮寄
	 * @description 接口说明:申请邮寄:若邮费>0,需要生成邮费账单
	 * @author wyh
	 * @url /contract/post/:id
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @param .name:voucher_id type:int require:1 default:1 other: desc:地址信息ID(仅有返回值时才传)
	 * @param .name:username type:int require:1 default:1 other: desc:姓名
	 * @param .name:phone type:int require:1 default:1 other: desc:电话
	 * @param .name:detail type:int require:1 default:1 other: desc:地址
	 * @return invoice_id:账单ID
	 */
	public function postPost()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("uid", $uid)->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		if (!in_array($contract_pdf["status"], [1, 2])) {
			return jsons(["status" => 400, "msg" => "仅待签订或已签订可申请邮寄"]);
		}
		$invoice_id = \think\Db::name("invoice_items")->alias("a")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.rel_id", $id)->where("a.type", "contract")->where("b.status", "Unpaid")->value("a.invoice_id");
		$amount = floatval(configuration("contract_postcode_fee"));
		if ($amount > 0 && empty($invoice_id)) {
			$inc_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $amount, "total" => $amount, "status" => "Unpaid", "type" => "contract", "url" => "/contract"];
			$item_data = ["uid" => $uid, "rel_id" => $id, "type" => "contract", "description" => "合同邮费", "amount" => $amount, "due_time" => strtotime("+365 day")];
			\think\Db::startTrans();
			try {
				$invoice_id = \think\Db::name("invoices")->insertGetId($inc_data);
				$item_data["invoice_id"] = $invoice_id;
				\think\Db::name("invoice_items")->insert($item_data);
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsons(["status" => 400, "msg" => $e->getMessage()]);
			}
		}
		if ($amount <= 0) {
			\think\Db::name("contract_pdf")->where("id", $id)->update(["status" => 3]);
		}
		if (!empty($param["voucher_id"])) {
			\think\Db::name("voucher_post")->where("id", intval($param["voucher_id"]))->update(["username" => trim($param["username"]), "phone" => trim($param["phone"]), "detail" => trim($param["detail"])]);
		} else {
			\think\Db::name("voucher_post")->insertGetId(["uid" => $uid, "username" => trim($param["username"]), "phone" => trim($param["phone"]), "detail" => trim($param["detail"]), "default" => 1]);
		}
		$data = ["invoice_id" => $invoice_id];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 邮寄信息
	 * @description 接口说明:邮寄信息
	 * @author wyh
	 * @url /contract/mail/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @return
	 * type 当前类型。4待邮寄、5已邮寄
	 * data 快递信息
	 */
	public function mail()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("uid", $uid)->where("id", $id)->find();
		$express["express_company"] = "快递公司: " . $contract_pdf["express_company"];
		$express["express_order"] = "快递单号: " . $contract_pdf["express_order"];
		if ($contract_pdf["status"] == 3) {
			return jsons(["status" => 200, "msg" => "您的邮寄申请已提交，后台审核盖章中，请耐心等待。", "data" => ["type" => 3, "data" => ""]]);
		} elseif ($contract_pdf["status"] == 4) {
			return jsons(["status" => 200, "msg" => "", "data" => ["type" => 4, "data" => $express]]);
		}
	}
	/**
	 * @title 作废
	 * @description 接口说明:作废
	 * @author wyh
	 * @url /contract/cancel/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function cancel()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("uid", $uid)->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsons(["status" => 400, "msg" => "合同不存在"]);
		}
		$contract = \think\Db::name("contract")->where("id", $contract_pdf["contract_id"])->find();
		if ($contract["force"]) {
			return jsons(["status" => 400, "msg" => "强制合同不可作废"]);
		}
		if ($contract_pdf["status"] != 2) {
			return jsons(["status" => 400, "msg" => "非待签订合同不可作废"]);
		}
		\think\Db::name("contract_pdf")->where("id", $id)->update(["status" => 0]);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 删除
	 * @description 接口说明:删除
	 * @author wyh
	 * @url /contract/delete/:id
	 * @method DELETE
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function delete()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("uid", $uid)->where("id", $id)->find();
		if ($contract_pdf["status"] != 0) {
			return jsons(["stauts" => 400, "msg" => lang("DELETE FAIL")]);
		}
		\think\Db::name("contract_pdf")->where("uid", $uid)->where("id", $id)->delete();
		return jsons(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}