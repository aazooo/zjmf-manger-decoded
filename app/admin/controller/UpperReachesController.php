<?php

namespace app\admin\controller;

/**
 * @title 上游资源管理模块
 */
class UpperReachesController extends AdminBaseController
{
	public $control_mode = [["name" => "不支持", "value" => "not_support", "disabled" => false], ["name" => "IPMI", "value" => "ipmi", "disabled" => false], ["name" => "魔方DCIM客户端", "value" => "dcim_client", "disabled" => false], ["name" => "OVH(开发中)", "value" => "ovh", "disabled" => true]];
	public $ipmi_button = ["status", "on", "off", "reboot", "vnc"];
	public $dcim_client_button = ["status", "on", "off", "reboot", "vnc", "reinstall", "crackPass"];
	/**
	 * @title 上游列表
	 * @description 接口说明:
	 * @author lgd
	 * @url admin/upper/index
	 * @method get
	 * @param .name:id type:string require:0  other: desc:id
	 * @param .name:name type:string require:0  other: desc:用户名
	 * @param .name:phone type:string require:0  other: desc:联系方式
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:type type:string require:0  other: desc:查询类型(all为查全部)
	 * @return data:基础数据@
	 * @data  id:id
	 * @data  name:姓名
	 * @data  phone:联系方式
	 * @data  bz:备注
	 */
	public function index(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$id = !empty($params["id"]) ? trim($params["id"]) : "";
			$type = !empty($params["type"]) ? trim($params["type"]) : "";
			$name = !empty($params["name"]) ? trim($params["name"]) : "";
			$phone = !empty($params["phone"]) ? trim($params["phone"]) : "";
			$page = input("page") ?? config("page");
			$limit = input("limit") ?? config("limit");
			$order = input("order");
			$sort = input("sort") ?? "desc";
			if (!empty($id)) {
				$where[] = ["id", "=", $id];
			}
			if (!empty($name)) {
				$where[] = ["name", "like", "%" . $name . "%"];
			}
			if (!empty($phone)) {
				$where[] = ["contact_way", "like", "%" . $phone . "%"];
			}
			if ($type == "all") {
				$data = \think\Db::name("zjmf_finance_api")->field("id,name,contact_way as phone,des as bz,create_time")->where($where)->where("is_resource", 0)->order($order, $sort)->select()->toArray();
				$total = \think\Db::name("zjmf_finance_api")->where("type", "manual")->where($where)->count();
			} else {
				$data = \think\Db::name("zjmf_finance_api")->field("id,name,contact_way as phone,des as bz,create_time")->where($where)->where("is_resource", 0)->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
				$total = \think\Db::name("zjmf_finance_api")->where("type", "manual")->where($where)->count();
			}
			return jsonrule(["status" => 200, "total" => $total, "data" => $data]);
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 上游添加
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/addpost
	 * @method post
	 * @param .name:name type:string require:1  other: desc:用户名
	 * @param .name:phone type:string require:1  other: desc:联系方式
	 * @param .name:bz type:string require:0  other: desc:备注
	 */
	public function addPost(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$name = !empty($params["name"]) ? trim($params["name"]) : "";
			if (empty($name)) {
				return jsonrule(["status" => 400, "msg" => "姓名不能为空"]);
			}
			$phone = !empty($params["phone"]) ? $params["phone"] : "";
			$bz = !empty($params["bz"]) ? trim($params["bz"]) : "";
			$data = ["name" => $name, "phone" => $phone, "bz" => $bz, "create_time" => time()];
			$res = \think\Db::name("upper_reaches")->insertGetId($data);
			active_log(sprintf($this->lang["Ur_admin_add"], $res));
			if ($res) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 上游修改
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/edituppost
	 * @method post
	 * @param .name:id type:int require:0  other: desc:id
	 * @param .name:name type:string require:0  other: desc:用户名
	 * @param .name:phone type:string require:0  other: desc:联系方式
	 * @param .name:bz type:string require:0  other: desc:备注
	 */
	public function editupPost(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$id = !empty($params["id"]) ? trim($params["id"]) : "";
			$name = !empty($params["name"]) ? trim($params["name"]) : "";
			$phone = !empty($params["phone"]) ? $params["phone"] : "";
			$bz = !empty($params["bz"]) ? trim($params["bz"]) : "";
			if (empty($name)) {
				return jsonrule(["status" => 400, "msg" => "姓名不能为空"]);
			}
			$desc = "";
			$ur = \think\Db::name("upper_reaches")->where("id", $id)->find();
			if (empty($ur)) {
				return jsonrule(["status" => 400, "msg" => "没有这个上游"]);
			}
			if ($ur["name"] != $name) {
				$desc .= "上游姓名由“" . $ur["name"] . "”改为“" . $name . "”，";
			}
			if ($ur["phone"] != $phone) {
				$desc .= "上游联系方式由“" . $ur["phone"] . "”改为“" . $phone . "”，";
			}
			if ($ur["bz"] != $bz) {
				$desc .= "上游备注由“" . $ur["bz"] . "”改为“" . $bz . "”，";
			}
			$data = ["name" => $name, "phone" => $phone, "bz" => $bz];
			$data = \think\Db::name("upper_reaches")->where("id", $id)->update($data);
			if (empty($desc)) {
				$desc .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Ur_admin_edit"], $id, $desc));
			if ($data) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 上游删除
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/del
	 * @method post
	 * @param .name:id type:int require:0  other: desc:id
	 */
	public function delup(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("pid", $id)->find();
		if (!empty($re)) {
			return jsonrule(["status" => 400, "msg" => "此上游有关联，不能删除"]);
		}
		$data = \think\Db::name("upper_reaches")->where("id", $id)->delete();
		active_log(sprintf($this->lang["Ur_admin_del"], $id));
		if ($data) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 资源管理列表
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/upperindex
	 * @method get
	 * @param .name:api_id type:string require:0  other: desc:接口ID(接口里传)
	 * @param .name:in_ip type:string require:0  other: desc:主ip
	 * @param .name:pid type:string require:0  other: desc:上游 ID
	 * @param .name:keyword type:string require:0  other: desc:关键字
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:type type:string require:0  other: desc:查询类型(all为查全部)
	 * @return data:基础数据@
	 * @data  id:id
	 * @data  uname:上游姓名
	 * @data  ip:ip
	 * @data  pz:配置
	 * @data  mark:备注
	 * @data  ipmi:ipmi
	 * @data  ipmijq:ipmi鉴权
	 * @data  total:成本
	 * @data  names:关联客户
	 * @data  nextduedate:到期时间1
	 * @data  paidtime:到期时间2
	 * @data  button:控制方式支持功能(status电源状态,on开机,off关机,reboot重启,vnc)
	 * @data  username:用户名
	 * @data  password:密码
	 * @return apis:上游@
	 * @id 上游ID
	 * @name 名称
	 */
	public function upperIndex(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$id = !empty($params["id"]) ? trim($params["id"]) : "";
			if (!empty($id)) {
				$where[] = ["urr.id", "=", $id];
			}
			if (!empty($params["api_id"])) {
				$where[] = ["urr.pid", "=", $params["api_id"]];
			}
			$in_ip = !empty($params["in_ip"]) ? trim($params["in_ip"]) : "";
			if (!empty($in_ip)) {
				$where[] = ["urr.in_ip", "like", "%" . $in_ip . "%"];
			}
			$pid = !empty($params["pid"]) ? trim($params["pid"]) : "";
			if (!empty($pid)) {
				$where[] = ["urr.pid", "=", $pid];
			}
			$page = input("page") ?? config("page");
			$limit = input("limit") ?? config("limit");
			$order = input("order");
			$sort = input("sort") ?? "desc";
			$type = !empty($params["type"]) ? trim($params["type"]) : "";
			if ($type == "all") {
				$data = \think\Db::name("upper_reaches_res")->alias("urr")->join("zjmf_finance_api ur", "ur.id=urr.pid")->leftJoin("host h", "h.id=urr.hid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("products p", "p.id=h.productid")->where("ur.is_resource", 0)->field("urr.*,p.name,c.username as cname,ur.name as uname,h.nextduedate,h.domainstatus,h.id as hostid,c.id as uid")->withAttr("domainstatus", function ($value) {
					$domainstatus = [];
					$domainstatus["color"] = config("public.domainstatus")[$value]["color"];
					$domainstatus["name"] = $value;
					$domainstatus["name_zh"] = config("public.domainstatus")[$value]["name"];
					return $domainstatus;
				})->where(function (\think\db\Query $query) use($params) {
					$keyword = $params["keyword"];
					if (!empty($keyword)) {
						$query->where("ur.name like '%" . $keyword . "%' or c.username like '%" . $keyword . "%' or p.name like '%" . $keyword . "%' or urr.ip like '%" . $keyword . "%' or urr.pz like '%" . $keyword . "%' or urr.total like '%" . $keyword . "%'");
					}
				})->where($where)->order($order, $sort)->select()->toArray();
			} else {
				$data = \think\Db::name("upper_reaches_res")->alias("urr")->join("zjmf_finance_api ur", "ur.id=urr.pid")->leftJoin("host h", "h.id=urr.hid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("products p", "p.id=h.productid")->where("ur.is_resource", 0)->field("urr.*,p.name,c.username as cname,ur.name as uname,h.nextduedate,h.domainstatus,h.id as hostid,c.id as uid")->withAttr("domainstatus", function ($value) {
					$domainstatus = [];
					$domainstatus["color"] = config("public.domainstatus")[$value]["color"];
					$domainstatus["name"] = $value;
					$domainstatus["name_zh"] = config("public.domainstatus")[$value]["name"];
					return $domainstatus;
				})->where(function (\think\db\Query $query) use($params) {
					$keyword = $params["keyword"];
					if (!empty($keyword)) {
						$query->where("ur.name like '%" . $keyword . "%' or c.username like '%" . $keyword . "%' or p.name like '%" . $keyword . "%' or urr.ip like '%" . $keyword . "%' or urr.pz like '%" . $keyword . "%' or urr.total like '%" . $keyword . "%'");
					}
				})->where($where)->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
			}
			$total = \think\Db::name("upper_reaches_res")->alias("urr")->join("zjmf_finance_api ur", "ur.id=urr.pid")->leftJoin("host h", "h.id=urr.hid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("products p", "p.id=h.productid")->where("ur.is_resource", 0)->field("urr.*,p.name,c.username as cname,ur.name as uname,h.nextduedate,h.domainstatus,h.id as hostid,c.id as uid")->withAttr("domainstatus", function ($value) {
				$domainstatus = [];
				$domainstatus["color"] = config("public.domainstatus")[$value]["color"];
				$domainstatus["name"] = $value;
				$domainstatus["name_zh"] = config("public.domainstatus")[$value]["name"];
				return $domainstatus;
			})->where(function (\think\db\Query $query) use($params) {
				$keyword = $params["keyword"];
				if (!empty($keyword)) {
					$query->where("ur.name like '%" . $keyword . "%' or c.username like '%" . $keyword . "%' or p.name like '%" . $keyword . "%' or urr.ip like '%" . $keyword . "%' or urr.pz like '%" . $keyword . "%' or urr.total like '%" . $keyword . "%'");
				}
			})->where($where)->count();
			foreach ($data as $k => $v) {
				if (!empty($v["hostid"])) {
					$str = $data[$k]["cname"] . "-" . $data[$k]["name"];
					$data[$k]["names"] = "<a class=\"el-link el-link--primary is-underline\" 
                href=\"#/customer-view/product-innerpage?hid=" . $v["hostid"] . "&id=" . $v["uid"] . "\">
                <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;color:" . $v["domainstatus"]["color"] . "\">" . $str . "</span></a>";
				} else {
					$data[$k]["names"] = "";
				}
				$ip = \think\Db::name("upper_reaches_ip")->field("ip")->where("resid", $v["id"])->select()->toArray();
				if (count($ip) >= 1) {
					$data[$k]["ips"] = true;
				} else {
					$data[$k]["ips"] = false;
				}
				$data[$k]["ipcount"] = count($ip) + 1;
				array_unshift($ip, ["ip" => $data[$k]["in_ip"]]);
				$data[$k]["ip"] = $ip;
				if (!empty($v["root"]) && !empty($v["pwd"])) {
					$data[$k]["ipmijq"] = $v["root"] . "/" . $v["pwd"];
				}
				if ($v["control_mode"] == "ipmi") {
					$data[$k]["button"] = $this->ipmi_button;
				} elseif ($v["control_mode"] == "dcim_client") {
					$data[$k]["button"] = $this->dcim_client_button;
				} else {
					$data[$k]["button"] = [];
				}
			}
			$apis = \think\Db::name("zjmf_finance_api")->whereIn("type", ["manual", "zjmf_api"])->where("is_resource", 0)->select()->toArray();
			return jsonrule(["status" => 200, "total" => $total, "data" => $data, "apis" => $apis]);
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 资源配置添加界面
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/addupperpage
	 * @method get
	 * @return data:基础数据@
	 * @data  id:id
	 * @data  name:上游姓名
	 * @data  phone:手机
	 * @data  bz:配置
	 */
	public function addUpperPage(\think\Request $request)
	{
		$data = \think\Db::name("zjmf_finance_api")->field("id,name,contact_way as phone,des as bz")->where("is_resource", 0)->select()->toArray();
		return jsonrule(["status" => 200, "data" => $data, "control_mode" => $this->control_mode]);
	}
	/**
	 * @title 资源配置添加
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/addupperpost
	 * @method post
	 * @param .name:in_ip type:string require:1  other: desc:主ip
	 * @param .name:ip type:string require:1  other: desc:ip
	 * @param .name:ipmi type:string require:1  other: desc:ipmi
	 * @param .name:pz type:string require:1  other: desc:配置
	 * @param .name:id type:string require:1  other: desc:上游id
	 * @param .name:root type:string require:1  other: desc:用户名
	 * @param .name:pwd type:string require:1  other: desc:密码
	 * @param .name:total type:float require:1  other: desc:成本
	 * @param .name:paid_time type:int require:1  other: desc:到期时间
	 * @param .name:control_mode type:string require:1  other: desc:控制方式(ipmi,not_support)
	 * @param .name:ipmi_version type:string require:1  other: desc:ipmi版本(1.5,2.0)
	 * @param .name:dcim_client_url type:string require:1  other: desc:DCIM客户端地址(http头加域名)
	 * @param .name:dcim_client_id type:int require:1  other: desc:服务器ID(用户名为IP时可不传)
	 * @param .name:mark type:string require:0  other: desc:备注
	 */
	public function addUpperPost(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$data["in_ip"] = !empty($params["in_ip"]) ? trim($params["in_ip"]) : "";
			if (!checkip($data["in_ip"])) {
				return jsonrule(["status" => 400, "msg" => "主ip地址不合法"]);
			}
			$exist = \think\Db::name("upper_reaches_res")->where("in_ip", $params["in_ip"])->find();
			if (!empty($exist)) {
				return jsons(["status" => 400, "msg" => "主ip地址已存在,不可重复添加"]);
			}
			$data["username"] = !empty($params["username"]) ? trim($params["username"]) : "";
			if (empty($data["username"])) {
				return jsonrule(["status" => 400, "msg" => "用户名不能为空"]);
			}
			$data["password"] = !empty($params["password"]) ? html_entity_decode(trim($params["password"]), ENT_QUOTES) : "";
			if (empty($data["password"])) {
				return jsonrule(["status" => 400, "msg" => "密码不能为空"]);
			}
			$ip = $params["ip"];
			$data["pid"] = !empty($params["pid"]) ? trim($params["pid"]) : "";
			if (empty($data["pid"])) {
				return jsonrule(["status" => 400, "msg" => "上游未选择"]);
			}
			$data["control_mode"] = !empty($params["control_mode"]) ? trim($params["control_mode"]) : "";
			if (empty($data["control_mode"])) {
				return jsonrule(["status" => 400, "msg" => "未选择控制方式"]);
			} else {
				if (!in_array($data["control_mode"], ["ipmi", "not_support", "dcim_client"])) {
					return jsonrule(["status" => 400, "msg" => "控制方式有误"]);
				}
			}
			if ($data["control_mode"] == "ipmi") {
				$data["ipmi_version"] = !empty($params["ipmi_version"]) ? trim($params["ipmi_version"]) : "";
				if (empty($data["ipmi_version"])) {
					return jsonrule(["status" => 400, "msg" => "未选择IPMI版本"]);
				} else {
					if (!in_array($data["ipmi_version"], ["1.5", "2.0"])) {
						return jsonrule(["status" => 400, "msg" => "IPMI版本有误"]);
					}
				}
				$data["ipmi"] = !empty($params["ipmi"]) ? trim($params["ipmi"]) : "";
				if (empty($data["ipmi"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI IP不能为空"]);
				} else {
					if (!checkip($data["ipmi"])) {
						return jsonrule(["status" => 400, "msg" => "ip地址不合法"]);
					}
				}
				$data["root"] = !empty($params["root"]) ? trim($params["root"]) : "";
				if (empty($data["root"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI用户名不能为空"]);
				}
				$data["pwd"] = !empty($params["pwd"]) ? html_entity_decode(trim($params["pwd"]), ENT_QUOTES) : "";
				if (empty($data["pwd"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI密码不能为空"]);
				}
			} elseif ($data["control_mode"] == "dcim_client") {
				$data["dcim_client_url"] = !empty($params["dcim_client_url"]) ? trim($params["dcim_client_url"]) : "";
				if (empty($data["dcim_client_url"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端地址不能为空"]);
				}
				if (!checkip($params["root"])) {
					$data["dcim_client_id"] = !empty($params["dcim_client_id"]) ? intval($params["dcim_client_id"]) : 0;
					if (empty($data["dcim_client_id"])) {
						return jsonrule(["status" => 400, "msg" => "服务器ID不能为空"]);
					}
				} else {
					$data["dcim_client_id"] = !empty($params["dcim_client_id"]) ? intval($params["dcim_client_id"]) : 0;
				}
				$data["root"] = !empty($params["root"]) ? trim($params["root"]) : "";
				if (empty($data["root"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端用户名不能为空"]);
				}
				$data["pwd"] = !empty($params["pwd"]) ? html_entity_decode(trim($params["pwd"]), ENT_QUOTES) : "";
				if (empty($data["pwd"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端密码不能为空"]);
				}
			}
			$data["pz"] = !empty($params["pz"]) ? trim($params["pz"]) : "";
			$data["total"] = !empty($params["total"]) ? trim($params["total"]) : "";
			$data["paid_time"] = $params["paid_time"];
			$data["create_time"] = time();
			$data["mark"] = $params["mark"] ?: "";
			$res = \think\Db::name("upper_reaches_res")->insertGetId($data);
			if ($res) {
				foreach ($ip as $key => $value) {
					$data1["ip"] = $value;
					$data1["resid"] = $res;
					\think\Db::name("upper_reaches_ip")->insertGetId($data1);
				}
			}
			active_log(sprintf($this->lang["Ur_admin_addupper"], $res));
			if ($data) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "id" => $res]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 资源配置修改界面
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/editupperpage
	 * @method get
	 * @param .name:id type:string require:0  other: desc:id
	 *
	 */
	public function editUpperPage(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$data = \think\Db::name("zjmf_finance_api")->field("id,name,contact_way as phone,des as bz")->where("is_resource", 0)->select()->toArray();
		$data1 = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		$data2 = \think\Db::name("upper_reaches_ip")->field("ip")->where("resid", $id)->select()->toArray();
		return jsonrule(["status" => 200, "data" => $data, "res" => $data1, "ip" => $data2, "control_mode" => $this->control_mode]);
	}
	/**
	 * @title 资源配置修改
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/editupperpost
	 * @method post
	 * @param .name:in_ip type:string require:1  other: desc:主ip
	 * @param .name:ip type:string require:1  other: desc:ip
	 * @param .name:ipmi type:string require:1  other: desc:ipmi
	 * @param .name:pz type:string require:1  other: desc:配置
	 * @param .name:id type:string require:1  other: desc:上游id
	 * @param .name:root type:string require:1  other: desc:用户名
	 * @param .name:pwd type:string require:1  other: desc:密码
	 * @param .name:total type:float require:1  other: desc:成本
	 * @param .name:paid_time type:int require:1  other: desc:到期时间
	 * @param .name:control_mode type:string require:1  other: desc:控制方式(ipmi,not_support)
	 * @param .name:ipmi_version type:string require:1  other: desc:ipmi版本(1.5,2.0)
	 * @param .name:dcim_client_url type:string require:1  other: desc:DCIM客户端地址(http头加域名)
	 * @param .name:dcim_client_id type:int require:0  other: desc:服务器ID(用户名为IP时可不传)
	 * @param .name:mark type:string require:0  other: desc:备注
	 */
	public function editUpperPost(\think\Request $request)
	{
		try {
			$params = $this->request->param();
			$id = !empty($params["id"]) ? trim($params["id"]) : "";
			$data["in_ip"] = !empty($params["in_ip"]) ? trim($params["in_ip"]) : "";
			if (!checkip($data["in_ip"])) {
				return jsonrule(["status" => 400, "msg" => "主ip地址不合法"]);
			}
			$exist = \think\Db::name("upper_reaches_res")->where("in_ip", $params["in_ip"])->where("id", "<>", intval($params["id"]))->find();
			if (!empty($exist)) {
				return jsons(["status" => 400, "msg" => "主ip地址已存在,不可重复添加"]);
			}
			$data["username"] = !empty($params["username"]) ? trim($params["username"]) : "";
			if (empty($data["username"])) {
				return jsonrule(["status" => 400, "msg" => "用户名不能为空"]);
			}
			$data["password"] = !empty($params["password"]) ? html_entity_decode(trim($params["password"]), ENT_QUOTES) : "";
			if (empty($data["password"])) {
				return jsonrule(["status" => 400, "msg" => "密码不能为空"]);
			}
			$ip = $params["ip"];
			$data["pid"] = !empty($params["pid"]) ? trim($params["pid"]) : "";
			if (empty($data["pid"])) {
				return jsonrule(["status" => 400, "msg" => "上游未选择"]);
			}
			$data["control_mode"] = !empty($params["control_mode"]) ? trim($params["control_mode"]) : "";
			if (empty($data["control_mode"])) {
				return jsonrule(["status" => 400, "msg" => "未选择控制方式"]);
			} else {
				if (!in_array($data["control_mode"], ["ipmi", "not_support", "dcim_client"])) {
					return jsonrule(["status" => 400, "msg" => "控制方式有误"]);
				}
			}
			if ($data["control_mode"] == "ipmi") {
				$data["ipmi_version"] = !empty($params["ipmi_version"]) ? trim($params["ipmi_version"]) : "";
				if (empty($data["ipmi_version"])) {
					return jsonrule(["status" => 400, "msg" => "未选择IPMI版本"]);
				} else {
					if (!in_array($data["ipmi_version"], ["1.5", "2.0"])) {
						return jsonrule(["status" => 400, "msg" => "IPMI版本有误"]);
					}
				}
				$data["ipmi"] = !empty($params["ipmi"]) ? trim($params["ipmi"]) : "";
				if (empty($data["ipmi"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI IP不能为空"]);
				} else {
					if (!checkip($data["ipmi"])) {
						return jsonrule(["status" => 400, "msg" => "ip地址不合法"]);
					}
				}
				$data["root"] = !empty($params["root"]) ? trim($params["root"]) : "";
				if (empty($data["root"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI用户名不能为空"]);
				}
				$data["pwd"] = !empty($params["pwd"]) ? html_entity_decode(trim($params["pwd"]), ENT_QUOTES) : "";
				if (empty($data["pwd"])) {
					return jsonrule(["status" => 400, "msg" => "IPMI密码不能为空"]);
				}
			} elseif ($data["control_mode"] == "dcim_client") {
				$data["dcim_client_url"] = !empty($params["dcim_client_url"]) ? trim($params["dcim_client_url"]) : "";
				if (empty($data["dcim_client_url"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端地址不能为空"]);
				}
				if (!checkip($params["root"])) {
					$data["dcim_client_id"] = !empty($params["dcim_client_id"]) ? intval($params["dcim_client_id"]) : 0;
					if (empty($data["dcim_client_id"])) {
						return jsonrule(["status" => 400, "msg" => "服务器ID不能为空"]);
					}
				} else {
					$data["dcim_client_id"] = !empty($params["dcim_client_id"]) ? intval($params["dcim_client_id"]) : 0;
				}
				$data["root"] = !empty($params["root"]) ? trim($params["root"]) : "";
				if (empty($data["root"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端用户名不能为空"]);
				}
				$data["pwd"] = !empty($params["pwd"]) ? html_entity_decode(trim($params["pwd"]), ENT_QUOTES) : "";
				if (empty($data["pwd"])) {
					return jsonrule(["status" => 400, "msg" => "DCIM客户端密码不能为空"]);
				}
			}
			$data["pz"] = !empty($params["pz"]) ? trim($params["pz"]) : "";
			$data["total"] = !empty($params["total"]) ? trim($params["total"]) : "";
			$data["paid_time"] = $params["paid_time"];
			$data["update_time"] = time();
			$data["mark"] = $params["mark"] ?: "";
			$desc = "";
			$res1 = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
			if ($res1["ip"] != $data["ip"]) {
				$desc = "ip由“" . $res1["ip"] . "“改为”" . $data["ip"] . "”，";
			}
			if ($res1["pid"] != $data["pid"]) {
				$name1 = \think\Db::name("zjmf_finance_api")->where("id", $data["pid"])->value("name");
				$name = \think\Db::name("zjmf_finance_api")->where("id", $res1["pid"])->value("name");
				$desc = "上游由“" . $name . "“改为”" . $name1 . "”，";
			}
			if ($res1["pz"] != $data["pz"]) {
				$desc = "配置由“" . $res1["pz"] . "“改为”" . $data["pz"] . "”，";
			}
			if ($res1["root"] != $data["root"]) {
				$desc = "root由“" . $res1["root"] . "“改为”" . $data["root"] . "”，";
			}
			if ($res1["pwd"] != $data["pwd"]) {
				$desc = "密码有修改，";
			}
			if ($res1["total"] != $data["total"]) {
				$desc = "成本由“" . $res1["total"] . "“改为”" . $data["total"] . "”，";
			}
			if ($res1["paid_time"] != $data["paid_time"]) {
				$desc = "到期时间由“" . $res1["paid_time"] . "“改为”" . $data["paid_time"] . "”，";
			}
			if ($res1["control_mode"] != $data["control_mode"]) {
				$desc = "控制方式由“" . $res1["control_mode"] . "“改为”" . $data["control_mode"] . "”，";
			}
			if ($res1["ipmi"] != $data["ipmi"]) {
				$desc = "IPMI IP由“" . $res1["ipmi"] . "“改为”" . $data["ipmi"] . "”，";
			}
			if ($res1["ipmi_version"] != $data["ipmi_version"]) {
				$desc = "IPMI版本由“" . $res1["ipmi_version"] . "“改为”" . $data["ipmi_version"] . "”，";
			}
			if ($res1["dcim_client_url"] != $data["dcim_client_url"]) {
				$desc = "DCIM客户端地址由“" . $res1["dcim_client_url"] . "“改为”" . $data["dcim_client_url"] . "”，";
			}
			if ($res1["dcim_client_id"] != $data["dcim_client_id"]) {
				$desc = "服务器ID由“" . $res1["dcim_client_id"] . "“改为”" . $data["dcim_client_id"] . "”，";
			}
			if ($res1["mark"] != $data["mark"]) {
				$desc = "备注由“" . $res1["mark"] . "“改为”" . $data["mark"] . "”，";
			}
			$res = \think\Db::name("upper_reaches_res")->where("id", $id)->update($data);
			$ips = \think\Db::name("upper_reaches_ip")->where("resid", $id)->select()->toArray();
			\think\Db::name("upper_reaches_ip")->where("resid", $id)->delete();
			foreach ($ip as $key => $value) {
				$data1["ip"] = $value;
				$data1["resid"] = $id;
				\think\Db::name("upper_reaches_ip")->insertGetId($data1);
			}
			if (empty($desc)) {
				$desc .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Ur_admin_editupper"], $id, $desc));
			if (!empty($res1["hid"])) {
				\think\Db::name("host")->where("id", $res1["hid"])->update(["dedicatedip" => $data["in_ip"], "assignedips" => implode(",", $ip), "username" => $data["username"], "password" => cmf_encrypt($data["password"])]);
			}
			if ($data) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
	}
	/**
	 * @title 资源配置删除
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/upper/delupper
	 * @method post
	 * @param .name:id type:int require:0  other: desc:id
	 */
	public function delUpper(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if (!empty($re["hid"])) {
			return jsonrule(["status" => 400, "msg" => "此资源配置关联了用户,不能删除"]);
		}
		$data = \think\Db::name("upper_reaches_res")->where("id", $id)->delete();
		active_log(sprintf($this->lang["Ur_admin_delupper"], $id));
		if ($data) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 资源分配
	 * @description 接口说明:资源分配
	 * @author xj
	 * @url /admin/upper/allotupper
	 * @method post
	 * @param .name:id type:int require:0  other: desc:id
	 * @param .name:hid type:int require:0  other: desc:主机id
	 */
	public function allotUpper(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? intval($params["id"]) : 0;
		$hid = !empty($params["hid"]) ? intval($params["hid"]) : 0;
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if (!empty($re["hid"])) {
			$host = \think\Db::name("host")->where("id", $re["hid"])->find();
			if (!empty($host)) {
				return jsonrule(["status" => 400, "msg" => "此资源配置已经关联了用户,不能再次分配"]);
			} else {
				\think\Db::name("upper_reaches_res")->where("id", $id)->update(["hid" => 0]);
			}
		}
		$host = \think\Db::name("host")->where("id", $hid)->find();
		if (empty($host)) {
			return jsonrule(["status" => 400, "msg" => "没有此主机"]);
		}
		$products = \think\Db::name("products")->where("id", $host["productid"])->find();
		if ($products["api_type"] != "manual") {
			return jsonrule(["status" => 400, "msg" => "产品类型不是手动资源类型,不可分配"]);
		}
		\think\Db::name("upper_reaches_res")->where("hid", $hid)->update(["hid" => 0]);
		$data = \think\Db::name("upper_reaches_res")->where("id", $id)->update(["hid" => $hid]);
		\think\Db::name("products")->where("id", $host["productid"])->update(["upper_reaches_id" => $re["pid"]]);
		$ip = \think\Db::name("upper_reaches_ip")->field("ip")->where("resid", $id)->select()->toArray();
		$ip = array_column($ip, "ip");
		\think\Db::name("host")->where("id", $hid)->update(["dedicatedip" => $re["in_ip"], "assignedips" => implode(",", $ip), "username" => $re["username"], "password" => cmf_encrypt($re["password"])]);
		active_log(sprintf("分配主机%d到资源配置#%d", $hid, $id));
		if ($data) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 资源空闲
	 * @description 接口说明:资源空闲
	 * @author xj
	 * @url /admin/upper/emptyupper
	 * @method post
	 * @param .name:id type:int require:0  other: desc:id
	 */
	public function emptyUpper(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? intval($params["id"]) : 0;
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if (empty($re["hid"])) {
			return jsonrule(["status" => 400, "msg" => "此资源配置未关联主机,无需空闲"]);
		}
		$data = \think\Db::name("upper_reaches_res")->where("id", $id)->update(["hid" => 0]);
		\think\Db::name("host")->where("id", $re["hid"])->update(["dedicatedip" => "", "assignedips" => "", "username" => "", "password" => ""]);
		active_log(sprintf("空闲资源配置#%d,主机%d", $id, $re["hid"]));
		if ($data) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title IPMI获取电源状态
	 * @description 接口说明:IPMI获取电源状态
	 * @author xj
	 * @url /admin/upper/ipmi/status
	 * @method get
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function ipmiStatus(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "ipmi") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/status", $data, 10);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "电源状态获取成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE"), "power_status" => "error"];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => "error"]);
		}
		return jsonrule($result);
	}
	/**
	 * @title IPMI开机
	 * @description 接口说明:IPMI开机
	 * @author xj
	 * @url /admin/upper/ipmi/on
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function ipmiOn()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "ipmi") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/on", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "开机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d开机成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d开机失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title IPMI关机
	 * @description 接口说明:IPMI关机
	 * @author xj
	 * @url /admin/upper/ipmi/off
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function ipmiOff()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "ipmi") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/off", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "关机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d关机成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d关机失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title IPMI重启
	 * @description 接口说明:IPMI重启
	 * @author xj
	 * @url /admin/upper/ipmi/reboot
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function ipmiReboot()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "ipmi") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/reboot", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "重启成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d重启成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d重启失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title IPMI VNC
	 * @description 接口说明:IPMI VNC
	 * @author xj
	 * @url /admin/upper/ipmi/vnc
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return vnc_url:VNC地址
	 */
	public function ipmiVnc()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "ipmi") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("vnc", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "VNC开启成功", "vnc_url" => $res["vnc_url"]];
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return jsonrule($result);
	}
	private function ipmiRequest($app = "", $data = [], $timeout = 30, $method = "POST")
	{
		$result = commonCurl("http://public.api.idcsmart.com/v1/" . $app, $data, $timeout, $method);
		return $result;
	}
	/**
	 * @title DCIM客户端获取电源状态
	 * @description 接口说明:DCIM客户端获取电源状态
	 * @author xj
	 * @url /admin/upper/dcim_client/status
	 * @method get
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function dcimClientStatus(\think\Request $request)
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "refreshPower", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 10);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "电源状态获取成功", "power_status" => $res["msg"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["msg"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE"), "power_status" => "error"];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => "error"]);
		}
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端开机
	 * @description 接口说明:DCIM客户端开机
	 * @author xj
	 * @url /admin/upper/dcim_client/on
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function dcimClientOn()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "on", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "开机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d开机成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d开机失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端关机
	 * @description 接口说明:DCIM客户端关机
	 * @author xj
	 * @url /admin/upper/dcim_client/off
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function dcimClientOff()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "off", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "关机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d关机成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d关机失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端重启
	 * @description 接口说明:DCIM客户端重启
	 * @author xj
	 * @url /admin/upper/dcim_client/reboot
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return power_status:电源状态on开机,off关机,error错误
	 */
	public function dcimClientReboot()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "reboot", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "重启成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $id)->update(["power_status" => $res["power"]]);
			$description = sprintf("资源配置#%d重启成功", $id);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			$description = sprintf("资源配置#%d重启失败,原因:%s", $id, $result["msg"]);
		}
		active_log($description);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端VNC
	 * @description 接口说明:DCIM客户端VNC
	 * @author xj
	 * @url /admin/upper/dcim_client/vnc
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return data:基础数据@
	 * @data  password:密码
	 * @data  url:VNC地址
	 */
	public function dcimClientVnc()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "novnc", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "VNC开启成功";
			$url = "";
			if ($res["data"]["ssl"] == true) {
				$url = "wss://" . $res["data"]["host"];
			} else {
				$url = "ws://" . $res["data"]["host"];
			}
			$url .= "/websockify_" . $res["data"]["house"] . "?token=" . $res["data"]["token"];
			$result["data"]["password"] = $res["data"]["pass"];
			$result["data"]["url"] = urlencode(base64_encode($url));
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return jsonrule($result);
	}
	/**
	 * @title novnc页面
	 * @description novnc页面
	 * @url /admin/upper/dcim_client/vnc
	 * @method  GET
	 * @author xj
	 * @version v1
	 * @param   .name:password type:string require:1 desc:novnc返回的密码
	 * @param   .name:url type:int require:1 desc:novnc返回的url
	 */
	public function dcimClientVncPage()
	{
		$password = input("get.password");
		$url = input("get.url");
		$url = base64_decode(urldecode($url));
		$this->assign("url", $url);
		$this->assign("password", $password);
		return $this->fetch("./themes/default/home/dcim/novnc.html");
	}
	/**
	 * @title DCIM客户端重装系统
	 * @description 接口说明:DCIM客户端重装系统
	 * @author xj
	 * @url /admin/upper/dcim_client/reinstall
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @param   .name:os type:int require:1 desc:操作系统ID
	 * @param   .name:password type:string require:1 desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param   .name:port type:int require:1 desc:端口号
	 * @param   .name:part_type type:int require:0 desc:分区类型(windows才有0全盘格式化1第一分区格式化) default:0
	 */
	public function dcimClientReinstall()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$password = !empty($params["password"]) ? trim($params["password"]) : "";
		$os = !empty($params["os"]) ? intval($params["os"]) : 0;
		$port = !empty($params["port"]) ? intval($params["port"]) : 0;
		$part_type = !empty($params["part_type"]) ? intval($params["part_type"]) : 0;
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["password" => $password, "os" => $os, "port" => $port, "part_type" => $part_type];
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientReinstall($re, $data);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端破解密码
	 * @description 接口说明:DCIM客户端破解密码
	 * @author xj
	 * @url /admin/upper/dcim_client/crack_pass
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @param .name:password type:string require:1  other: desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param .name:other_user type:int require:1  other: desc:是否破解其他用户(0:否1:是)
	 * @param .name:user type:string require:0  other: desc:要破解的其他用户名称
	 */
	public function dcimClientCrackPass()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$password = !empty($params["password"]) ? trim($params["password"]) : "";
		$other_user = !empty($params["other_user"]) ? intval($params["other_user"]) : 0;
		$user = !empty($params["user"]) ? trim($params["user"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["password" => $password, "other_user" => $other_user, "user" => $user];
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientCrackPass($re, $data);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端取消重装,救援,重置密码
	 * @description 接口说明:DCIM客户端取消重装,救援,重置密码
	 * @author xj
	 * @url /admin/upper/dcim_client/cancel_task
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 */
	public function dcimClientCancelReinstall()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientCancelReinstall($re);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端获取重装,重置密码进度
	 * @description 接口说明:DCIM客户端获取重装,重置密码进度
	 * @author xj
	 * @url /admin/upper/dcim_client/resintall_status
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return  disk_check:弹出错误时@
	 * @disk_check  value:disk_part的值
	 * @disk_check  description:描述
	 * @return  error_type:0,1,2,其他(当error_type>0并且progress>=20时弹出磁盘分区错误提示,1Windows磁盘错误,2Windows分区错误,其他Windows磁盘分区提示)
	 * @return  error_msg:当error_type>0时弹出磁盘分区错误提示信息
	 * @return  disk_info:当显示弹出磁盘分区错误提示@
	 * @disk_info  disk:磁盘
	 * @disk_info  part:分区
	 * @disk_info  size:大小
	 * @disk_info  type:类型
	 * @disk_info  windows:类型
	 * @return  progress:进度
	 * @return  windows_finish:是否是windows已完成
	 * @return  hostid:当前产品ID
	 * @return  task_type:类型(0重装系统,1救援系统,2重置密码,3获取硬件信息)
	 * @return  reinstall_msg:重装信息
	 * @return  crackPwd:当有数据返回时,弹出重置密码用户选择@
	 * @crackPwd  user:可选择的用户
	 * @crackPwd  password:重置的密码
	 * @return  step:当前步骤描述
	 * @return  last_result:上次执行结果@
	 * @last_result  act:操作名称
	 * @last_result  status:1成功
	 * @last_result  msg:描述
	 */
	public function dcimClientReinstallStatus()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientReinstallStatus($re);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端获取操作系统
	 * @description 接口说明:DCIM客户端获取操作系统
	 * @author xj
	 * @url /admin/upper/dcim_client/get_os
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 */
	public function dcimClientGetOs()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientGetOs($re);
		return jsonrule($result);
	}
	private function dcimClientRequest($url = "", $data = [], $timeout = 30, $method = "POST")
	{
		$result = commonCurl($url, $data, $timeout, $method);
		return $result;
	}
}