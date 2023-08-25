<?php

namespace app\admin\controller;

/**
 * @title 后台合同模块
 * @description 接口说明: 合同模块
 */
class ContractController extends AdminBaseController
{
	private $_config = ["contract_open" => "合同管理", "contract_institutions" => "单位名称", "contract_address" => "单位地址", "contract_username" => "联系人", "contract_email" => "邮箱", "contract_phonenumber" => "电话", "contract_consignee_address" => "收件地址", "contract_postcode" => "邮编", "contract_postcode_fee" => "邮费", "contract_number_custom" => "合同编号是否自定义", "contract_number" => "合同编号", "contract_number_prefix" => "编号自定义时前缀", "contract_pdf_logo" => "PDF LOGO", "contract_company_logo" => "公司印章"];
	private $_arg;
	private $notes = ["不提示", "消息中心推送", "产品内页提示"];
	public function initialize()
	{
		parent::initialize();
		if (!getEdition()) {
			echo json_encode(["status" => 400, "msg" => "合同功能仅专业版可用"]);
			exit;
		}
		$this->_arg = [["title" => lang("CONTRACT_CLIENT"), "data" => [["name" => lang("CONTRACT_CLIENT_USERNAME"), "arg" => "{\$client_username}"], ["name" => lang("CONTRACT_CLIENT_COMPANY_NAME"), "arg" => "{\$client_company_name}"], ["name" => lang("CONTRACT_CLIENT_TELEPHONE"), "arg" => "{\$client_telephone}"], ["name" => lang("CONTRACT_CLIENT_EMAIL"), "arg" => "{\$client_email}"], ["name" => lang("CONTRACT_CLIENT_ADDRESS"), "arg" => "{\$client_address}"]]], ["title" => lang("CONTRACT_CLIENT_PRODUCT"), "data" => [["name" => lang("CONTRACT_CLIENT_PRODUCT_NAME"), "arg" => "{\$client_product_name}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_DOMAIN"), "arg" => "{\$client_product_domain}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_STATUS"), "arg" => "{\$client_product_status}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_STRART_DATE"), "arg" => "{\$client_product_startdate}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_END_DATE"), "arg" => "{\$client_product_enddate}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_BILLING_CYCLE"), "arg" => "{\$client_product_billingcycle}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_PRICE"), "arg" => "{\$client_product_price}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_PRICE_WRITE"), "arg" => "{\$client_product_pricewrite}"], ["name" => lang("CONTRACT_CLIENT_PRODUCT_VALID_TIME"), "arg" => "{\$client_product_validtime}"]]]];
	}
	/**
	 * @title 合同模块基础设置
	 * @description 接口说明:合同模块基础设置
	 * @author wyh
	 * @time 2021-07-20
	 * @url /admin/contract/setting
	 * @method GET
	 * @return  contract_open:是否开启合同管理
	 * @return  contract_limit_custom:合同申请时间限制是否自定义：1是，0否
	 * @return  contract_limit:合同申请时间限制
	 * @return  contract_institutions:单位名称
	 * @return  contract_phonenumber:电话
	 * @return  contract_consignee_address:收件地址
	 * @return  contract_postcode:邮编
	 * @return  contract_postcode_fee:邮费
	 * @return  contract_number_custom:合同编号是否自定义：0默认，1自定义
	 * @return  contract_number:合同编号
	 * @return  contract_number_prefix:编号自定义时前缀
	 * @return  contract_pdf_logo:pdf logo
	 * @return  contract_company_logo:公司印章
	 * @return  currency:货币信息
	 */
	public function setting()
	{
		$data = configuration(array_keys($this->_config));
		if (!empty($data["contract_pdf_logo"])) {
			$data["contract_pdf_logo"] = request()->domain() . config("contract_get") . configuration("contract_pdf_logo");
		}
		if (!empty($data["contract_company_logo"])) {
			$data["contract_company_logo"] = request()->domain() . config("contract_get") . configuration("contract_company_logo");
		}
		$currency = \think\Db::name("currencies")->field("code,prefix,suffix")->where("default", 1)->find();
		$data["currency"] = $currency;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 合同模块基础设置
	 * @description 接口说明:合同模块基础设置
	 * @author wyh
	 * @time 2021-07-20
	 * @url /admin/contract/setting
	 * @method POST
	 * @param  name:contract_open type:int require:1 default:1 other: desc:是否开启合同管理
	 * @param  name:contract_limit_custom type:int require:1 default:1 other: desc:合同申请时间限制是否自定义：1是，0否
	 * @param  name:contract_limit type:int require:1 default:1 other: desc:合同申请时间限制
	 * @param  name:contract_institutions type:int require:1 default:1 other: desc:单位名称
	 * @param  name:contract_phonenumber type:int require:1 default:1 other: desc:电话
	 * @param  name:contract_consignee_address type:int require:1 default:1 other: desc:收件地址
	 * @param  name:contract_postcode type:int require:1 default:1 other: desc:邮编
	 * @param  name:contract_postcode_fee type:int require:1 default:1 other: desc:邮费
	 * @param  name:contract_number_custom type:int require:1 default:1 other: desc:合同编号是否自定义：0默认，1自定义
	 * @param  name:contract_number type:int require:1 default:1 other: desc:合同编号长度
	 * @param  name:contract_number_prefix type:int require:1 default:1 other: desc:编号自定义时前缀
	 * @param  name:contract_pdf_logo type:int require:1 default:1 other: desc:pdf logo,这里传文件名(文件上传调admin/upload_image接口)
	 * @param  name:contract_company_logo type:int require:1 default:1 other: desc:公司印章,这里传文件名(文件上传调admin/upload_image接口)
	 * @param  name:contract_address type:int require:1 default:1 other: desc:单位地址
	 * @param  name:contract_username type:int require:1 default:1 other: desc:联系人
	 * @param  name:contract_email type:int require:1 default:1 other: desc:邮箱
	 */
	public function settingPost()
	{
		$param = $this->request->param();
		if (intval($param["contract_number"]) < 8 || intval($param["contract_number"]) > 25) {
			return jsonrule(["status" => 400, "msg" => "合同编号长度至少8位,至多25位"]);
		}
		$data = array_filter($param, function (&$value, $key) {
			if (!array_key_exists($key, $this->_config)) {
				return false;
			}
			if (in_array($key, ["contract_postcode_fee"])) {
				$value = floatval($value);
			}
			if (in_array($key, ["contract_open", "contract_limit_custom", "contract_limit", "contract_number"])) {
				$value = intval($value);
			}
			return true;
		}, ARRAY_FILTER_USE_BOTH);
		\think\Db::startTrans();
		try {
			$description = "";
			array_walk($data, function ($v, $k) use(&$description) {
				$old = configuration($k);
				if (!empty($old) && $v != $old) {
					$description .= $this->_config[$k] . "由{$old}改为{$v};";
				}
				updateConfiguration($k, $v);
			});
			if (!empty($description)) {
				active_log_final(sprintf($this->lang["Contract_setting"], $description));
			}
			unset($description);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("EDIT FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
	}
	/**
	 * @title 添加/编辑合同页面
	 * @description 接口说明:添加/编辑合同页面
	 * @author wyh
	 * @url /admin/contract/detail/[:id]
	 * @method GET
	 * @param .name:id type:int require:0 default:1 other: desc:合同ID
	 * @return  products:产品组--产品信息
	 * @return  contract:合同信息@
	 * @contract  id:ID
	 * @contract  name:名称
	 * @contract  remark:备注
	 * @contract  status:状态：0关闭(默认)，1显示
	 * @contract  force:1强制合同，0否
	 * @contract  suspended:未签订xx天后操作
	 * @contract  suspended_type:未签订xx天后暂停或无法访问产品内页
	 * @contract  base:是否基础合同：1是，0否
	 * @contract  is_post:1支持邮寄，0否
	 * @contract  nocheck:1无需审核，0否
	 * @contract  product_id:已选择的产品ID
	 * @contract  inscribe_custom:落款信息是否自定义：1自定义，0默认
	 * @contract  notes:提示：0不提示(默认)，1全局提示，2产品页提示
	 * @contract  represent:授权代表
	 * @contract  phonenumber:代表电话
	 * @contract  email:电子邮箱
	 * @contract  content:合同内容
	 * @return  contract_args:合同参数
	 */
	public function detail()
	{
		$param = $this->request->param();
		$contract = [];
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$contract = \think\Db::name("contract")->where("id", $id)->find();
			foreach ($contract as &$item) {
				array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v) : $v;
				}, $item);
			}
			if (empty($contract)) {
				return jsonrule(["status" => 400, "msg" => "合同不存在"]);
			}
			$contract["product_id"] = explode(",", $contract["product_id"]);
		}
		if (empty($contract["inscribe_custom"])) {
			$contract["represent"] = configuration("contract_institutions");
			$contract["phonenumber"] = configuration("contract_phonenumber");
			$contract["email"] = configuration("contract_email");
		}
		$groups = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->select()->toArray();
		$product_ids = \think\Db::name("contract")->where("product_id", "<>", "")->column("product_id");
		$pid_arr = [];
		foreach ($product_ids as $product_id) {
			$pids = explode(",", $product_id);
			$pid_arr = array_merge($pid_arr, $pids);
		}
		$pid_arr = array_unique($pid_arr);
		if (!empty($contract["product_id"])) {
			$pid_arr = array_diff($pid_arr, $contract["product_id"]);
		}
		foreach ($groups as &$group) {
			$products = \think\Db::name("products")->field("id,name")->where("gid", $group["id"])->whereNotIn("id", $pid_arr)->select()->toArray();
			$group["child"] = $products;
		}
		$data = ["products" => $groups, "contract" => $contract, "contract_args" => $this->_arg, "notes" => $this->notes];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	* @title 创建\编辑合同页面提交
	* @description 接口说明:创建\编辑合同页面提交
	* @author wyh
	* @url /admin/contract/detail/[:id]
	* @method POST
	* @param .name:id type:string require:1 default:1 other: desc:合同ID
	* @param .name:name type:string require:1 default:1 other: desc:合同名称
	* @param .name:status type:tinyint require:1 default:1 other: desc:状态：0停用，1启用
	* @param .name:force type:tinyint require:1 default:1 other: desc:是否强制：0否(默认)，1是
	@param .name:notes type:string require:1 default:1 other: desc:提示：0不提示(默认)，1全局提示，2产品页提示
	* @param .name:pids[] type:int require:1 default:1 other: desc:产品ID(多选)
	* @param .name:represent type:string require:1 default:1 other: desc:授权代表
	* @param .name:phonenumber type:string require:1 default:1 other: desc:代表电话
	* @param .name:remark type:string require:1 default:1 other: desc:备注
	* @param .name:content type:string require:1 default:1 other: desc:合同内容
	* @param .name:suspended type:string require:1 default:1 other: desc:未签订xx天后暂停或产品内容页无法访问
	* @param .name:suspended_type type:string require:1 default:1 other: desc:未签订xx天后暂停或产品内容页无法访问:suspended暂停产品，noaccess产品内容页无法访问
	@param .name:nocheck type:string require:1 default:1 other: desc:1无需审核，0否
	@param .name:is_post type:string require:1 default:1 other: desc:1支持邮寄，0否
	* @param .name:inscribe_custom type:string require:1 default:1 other: desc:落款信息是否自定义：1自定义，0默认
	* @param .name:base type:string require:1 default:1 other: desc:是否基础合同：1是，0否
	* @param .name:email type:string require:1 default:1 other: desc:邮箱
	*/
	public function detailPost()
	{
		$param = $this->request->only(["id", "name", "status", "notes", "force", "pids", "represent", "phonenumber", "suspended_type", "remark", "content", "suspended", "is_post", "nocheck", "inscribe_custom", "base", "email"]);
		$validate = new \app\admin\validate\ContractValidate();
		if (!$validate->scene("tpl")->check($param)) {
			return jsonrule(["status" => 400, "msg" => $validate->getError()]);
		}
		if ($param["force"]) {
			$param["suspended"] = intval($param["suspended"]);
			if (!in_array($param["suspended_type"], ["suspended", "noaccess"])) {
				return jsonrule(["status" => 400, "msg" => "请选择强制签订类型"]);
			}
		}
		if (empty($param["base"])) {
			if (!empty($param["pids"]) && is_array($param["pids"])) {
				foreach ($param["pids"] as $v) {
					$count = \think\Db::name("products")->where("id", $v)->count();
					if ($count < 1) {
						return jsonrule(["status" => 400, "msg" => "产品不存在"]);
					}
				}
			} else {
				return jsonrule(["status" => 400, "msg" => "此为非基础合同，需指定签订产品"]);
			}
		}
		$param["product_id"] = implode(",", $param["pids"]) ?? "";
		unset($param["pids"]);
		if (empty($param["inscribe_custom"])) {
			$param["represent"] = configuration("contract_institutions");
			$param["phonenumber"] = configuration("contract_phonenumber");
			$param["email"] = configuration("contract_email");
		}
		if (isset($param["id"])) {
			$param["update_time"] = time();
			\think\Db::name("contract")->where("id", $param["id"])->update($param);
		} else {
			$param["create_time"] = time();
			\think\Db::name("contract")->insert($param);
		}
		return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
	}
	/**
	 * @title 合同模板列表
	 * @description 接口说明:合同模板列表
	 * @author wyh
	 * @url /admin/contract/tpl
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:1 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:1 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:status type:int require:0 default:1 other: desc:按合同状态搜索
	 * @param .name:keyword type:int require:0 default:1 other: desc:关键字搜索
	 * @return contracts:合同列表信息@
	 * @contract  id:ID
	 * @contract  name:名称
	 * @contract  remark:备注
	 * @contract  status:状态：0关闭(默认)，1显示
	 * @contract  force:1强制合同，0否
	 * @contract  suspended:未签订xx天后暂停
	 * @contract  is_post:1支持邮寄，0否
	 * @contract  nocheck:1无需审核，0否
	 * @contract  product_id:已选择的产品ID
	 * @contract  inscribe_custom:落款信息是否自定义：1自定义，0默认
	 * @contract  notes:提示：0不提示(默认)，1全局提示，2产品页提示
	 * @contract  represent:授权代表
	 * @contract  phonenumber:代表电话
	 * @contract  content:合同内容
	 * @contract  signed:已签订
	 * @contract  sign:待签订
	 */
	public function tpl()
	{
		$data = $this->request->param();
		$page = isset($data["page"]) && !empty($data["page"]) ? intval($data["page"]) : 1;
		$limit = isset($data["limit"]) && !empty($data["limit"]) ? intval($data["limit"]) : 10;
		$order = isset($data["order"]) && !empty($data["order"]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"]) && !empty($data["sort"]) ? trim($data["sort"]) : "DESC";
		$where = function (\think\db\Query $query) use($data) {
			if (!is_null($data["status"])) {
				$status = intval($data["status"]);
				$query->where("status", $status);
			}
			if (!is_null($data["keyword"])) {
				$query->where("name|notes|remark", "like", "%{$data["keyword"]}%");
			}
		};
		$total = \think\Db::name("contract")->where($where)->count("id");
		$contracts = \think\Db::name("contract")->field("id,name,status,force,notes,product_id,remark,create_time")->where($where)->limit($limit)->page($page)->order($order, $sort)->select()->toArray();
		foreach ($contracts as $key => $contract) {
			$contract = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $contract);
			$contract["signed"] = \think\Db::name("contract_pdf")->where("contract_id", $contract["id"])->whereIn("status", [1, 3, 4, 5])->count();
			$contract["sign"] = \think\Db::name("contract_pdf")->where("contract_id", $contract["id"])->where("status", 2)->count();
			$pids = explode(",", $contract["product_id"]);
			foreach ($pids as $pid) {
				$product = \think\Db::name("products")->field("name")->where("id", $pid)->find();
				$product = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $product);
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $pid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $product["name"] . "</span></a>";
				if (!isset(${$key})) {
					${$key} = $url;
				} else {
					${$key} = ${$key} . "," . $url;
				}
			}
			$contract["product_name"] = ${$key};
			$contract["notes"] = $this->notes[$contract["notes"]];
			unset($contract["product_id"]);
			$contracts[$key] = $contract;
		}
		$data = ["total" => $total, "contracts" => $contracts];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 删除合同模板
	 * @description 接口说明:删除合同模板
	 * @author wyh
	 * @url /admin/contract/tpl/:id
	 * @method DELETE
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function deleteTpl()
	{
		$param = $this->request->param();
		if (empty($param["id"])) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$count = \think\Db::name("contract_pdf")->where("contract_id", intval($param["id"]))->count();
		if ($count >= 1) {
			return jsonrule(["status" => 400, "msg" => "模板使用中,不可删除"]);
		}
		\think\Db::name("contract")->where("id", intval($param["id"]))->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 合同列表
	 * @description 接口说明:合同列表
	 * @author wyh
	 * @url /admin/contract/contract
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:1 other: desc:每页多少条
	 * @param .name:order type:int require:1 default:1 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:domainstatus type:string require:0 default:1 other: desc:按产品状态搜索
	 * @param .name:status type:string require:0 default:1 other: desc:按合同状态搜索
	 * @return  contracts:合同列表@
	 * @contracts  id:合同ID
	 * @contracts  username:用户名
	 * @contracts  phonenumber:电话
	 * @contracts  companyname:产品名称
	 * @contracts  name:产品名称
	 * @contracts  domain:主机名
	 * @contracts  dedicatedip:ip
	 * @contracts  domainstatus_zh:产品状态中文
	 * @contracts  status_zh:合同状态中文
	 * @contracts  status:	合同状态:0已作废，1已签订，2待签订，3待支付
	 * @contracts  create_time:签订时间
	 * @contracts  paid_time:付款时间
	 * @contracts  express_company:快递公司
	 * @contracts  express_order:快递单号
	 * @contracts  username:收件人
	 * @contracts  detail:收件地址
	 * @contracts  phone:手机
	 * @contracts  is_post:是否邮寄：1是，0否
	 * @contracts  force:是否强制：0否(默认)，1是
	 */
	public function contract()
	{
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$order = !empty($param["order"]) ? trim($param["order"]) : "cp.id";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
		$where = function (\think\db\Query $query) use($param) {
			if (!empty($param["keyword"])) {
				$keyword = $param["keyword"];
				$query->where("p.name|cp.status|c.username|c.email", "like", "%{$keyword}%");
			}
			if (!empty($param["domainstatus"]) || $param["domainstatus"]) {
				$query->where("h.domainstatus", $param["domainstatus"]);
			}
			if (!is_null($param["status"]) || $param["status"]) {
				$query->where("cp.status", $param["status"]);
			}
			$query->where("vp.default = 1 or vp.default is null");
		};
		$total = \think\Db::name("contract_pdf")->alias("cp")->leftJoin("host h", "cp.host_id = h.id")->leftJoin("clients c", "h.uid = c.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("orders o", "h.orderid=o.id")->leftJoin("voucher_post vp", "cp.uid=vp.uid")->leftJoin("invoices i", "o.invoiceid=i.id")->leftJoin("contract con", "cp.contract_id=con.id")->where($where)->count();
		$contracts = \think\Db::name("contract_pdf")->alias("cp")->field("i.paid_time,cp.id,c.username,c.email,c.phonenumber,c.companyname,p.name,
            h.domain,h.dedicatedip,h.domainstatus,h.domainstatus as domainstatus_zh,cp.status,
            cp.status as status_zh,cp.create_time,cp.is_post,cp.express_company,cp.express_order,
            vp.username as vp_username,vp.detail,vp.phone,h.id as hostid,cp.uid,con.force")->leftJoin("host h", "cp.host_id = h.id")->leftJoin("clients c", "cp.uid = c.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("orders o", "h.orderid=o.id")->leftJoin("voucher_post vp", "cp.uid=vp.uid")->leftJoin("invoices i", "o.invoiceid=i.id")->leftJoin("contract con", "cp.contract_id=con.id")->where($where)->withAttr("domainstatus_zh", function ($value) {
			return config("public.domainstatus")[$value];
		})->withAttr("status_zh", function ($value) {
			return config("contract_status")[$value];
		})->limit($limit)->page($page)->order($order, $sort)->select()->toArray();
		$data = ["total" => $total, "contracts" => $contracts, "domainstatus" => config("domainstatus"), "status" => config("contract_status")];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 合同作废
	 * @description 接口说明:合同作废
	 * @author wyh
	 * @url /admin/contract/cancel
	 * @method POST
	 * @param .name:ids[] type:int require:1 default:1 other: desc:合同ID
	 */
	public function cancel()
	{
		$param = $this->request->param();
		if (empty($param["ids"])) {
			return jsonrule(["status" => 400, "msg" => lang("CONTRACT_MULTI_CANCEL")]);
		}
		$ids = $param["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		array_map(function (&$value) {
			$value = intval($value);
			return $value;
		}, $ids);
		\think\Db::name("contract_pdf")->whereIn("id", $ids)->update(["status" => 0]);
		return jsonrule(["status" => 200, "msg" => lang("CONTRACT_MULTI_CANCEL_SUCCESS")]);
	}
	/**
	 * @title 合同查看下载
	 * @description 接口说明:合同查看下载
	 * @author wyh
	 * @url /admin/contract/download/:id
	 * @method GET
	 * @param  .name:id type:int require:1 default:1 other: desc:合同ID
	 * @return  pdf:合同地址
	 */
	public function download()
	{
		$param = $this->request->param();
		if (empty($param["id"])) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$result = \think\Db::name("contract_pdf")->field("pdf_address")->where("id", intval($param["id"]))->find();
		if ($result) {
			$pdfaddress = request()->domain() . config("contract_get") . $result["pdf_address"];
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["pdf" => $pdfaddress]]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 合同邮寄管理
	 * @description 接口说明:合同邮寄管理
	 * @author wyh
	 * @url /admin/contract/contract/:id
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @param .name:is_post type:int require:1 default:1 other: desc:是否邮寄：1是，0否
	 * @param .name:express_company type:int require:1 default:1 other: desc:快递公司
	 * @param .name:express_order type:int require:1 default:1 other: desc:快递单号
	 */
	public function contractPost()
	{
		$param = $this->request->param();
		$pdf = \think\Db::name("contract_pdf")->where("id", intval($param["id"]))->find();
		if (empty($pdf)) {
			return jsonrule(["status" => 400, "msg" => "合同不存在"]);
		}
		$valiate = new \app\admin\validate\ContractValidate();
		if (!$valiate->scene("post")->check($param)) {
			return jsonrule(["status" => 400, "msg" => $valiate->getError()]);
		}
		\think\Db::name("contract_pdf")->where("id", intval($param["id"]))->update(["is_post" => intval($param["is_post"]), "express_company" => trim($param["express_company"]), "express_order" => trim($param["express_order"]), "status" => 4]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 取消邮寄
	 * @description 接口说明:取消邮寄
	 * @author wyh
	 * @url /admin/contract/cancel_post/:id
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function cancelPost()
	{
		$param = $this->request->param();
		\think\Db::name("contract_pdf")->where("id", intval($param["id"]))->update(["is_post" => 0]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 审核通过
	 * @description 接口说明:审核通过
	 * @author wyh
	 * @url /admin/contract/check
	 * @method POST
	 * @param .name:ids[] type:int require:1 default:1 other: desc:合同ID,数组
	 */
	public function check()
	{
		$param = $this->request->param();
		if (empty($param["ids"])) {
			return jsonrule(["status" => 400, "msg" => lang("CONTRACT_MULTI_CHECK")]);
		}
		$ids = $param["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		array_map(function (&$value) {
			$value = intval($value);
			return $value;
		}, $ids);
		\think\Db::name("contract_pdf")->whereIn("id", $ids)->update(["status" => 1]);
		return jsonrule(["status" => 200, "msg" => lang("CONTRACT_MULTI_CHECK_SUCCESS")]);
	}
	/**
	 * @title 删除合同
	 * @description 接口说明:删除合同
	 * @author wyh
	 * @url /admin/contract/delete
	 * @method POST
	 * @param .name:ids[] type:int require:1 default:1 other: desc:合同ID
	 */
	public function delete()
	{
		$param = $this->request->param();
		if (empty($param["ids"])) {
			return jsonrule(["status" => 400, "msg" => lang("CONTRACT_MULTI_CHECK")]);
		}
		$ids = $param["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		array_map(function (&$value) {
			$value = intval($value);
			return $value;
		}, $ids);
		\think\Db::name("contract_pdf")->whereIn("id", $ids)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 签订合同页面
	 * @description 接口说明:签订合同页面
	 * @author wyh
	 * @url /admin/contract/contract_page
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 */
	public function contractPage()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsonrule(["status" => 400, "msg" => "合同不存在"]);
		}
		if ($contract_pdf["status"] != 2) {
			return jsonrule(["status" => 400, "msg" => "合同非待签订状态"]);
		}
		$client = \think\Db::name("clients")->field("phonenumber,username,email,address1,companyname")->where("id", $uid)->find();
		$pdf_logo = config("contract_get") . configuration("contract_pdf_logo");
		$company_logo = config("contract_get") . configuration("contract_company_logo");
		$tpl_id = $contract_pdf["contract_id"];
		$contract = \think\Db::name("contract")->field("id,name,represent,phonenumber,content,email,inscribe_custom")->where("id", $tpl_id)->where("status", 1)->find();
		if (empty($contract)) {
			return jsonrule(["status" => 400, "msg" => "合同模板不存在"]);
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
		$data = ["party" => ["institutions" => $client["companyname"], "addr" => $client["address1"], "username" => $client["username"], "phone" => $client["phonenumber"], "email" => $client["email"]], "party_b" => $party_b, "pdf_logo" => $pdf_logo, "company_logo" => $company_logo, "contract" => $contract, "host" => $host, "id" => $id];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 签订合同
	 * @description 接口说明:签订合同(生成PDF文档)
	 * @author wyh
	 * @url /admin/contract/contract_page/:id
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:合同ID
	 * @param .name:sign type:int require:1 default:1 other: desc:签名base64字符串
	 * @param .name:content type:int require:1 default:1 other: desc:合同内容,传html
	 * @param .name:type type:int require:1 default:1 other: desc:类型：I输出到浏览器 F输出到指定路径
	 */
	public function contractPagePost()
	{
		$uid = request()->uid;
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		$param = $this->request->param();
		$id = intval($param["id"]);
		$contract_pdf = \think\Db::name("contract_pdf")->where("id", $id)->find();
		if (empty($contract_pdf)) {
			return jsonrule(["status" => 400, "msg" => "合同不存在"]);
		}
		if (!empty($param["sign"])) {
			$str = trim($param["sign"]);
			$img = base64DecodeImage($str, config("contract_sign"));
			if (!$img) {
				return jsonrule(["status" => 400, "msg" => "签名失败"]);
			}
		}
		$contract = \think\Db::name("contract")->where("id", $contract_pdf["contract_id"])->find();
		$info["user"] = $client["username"];
		$info["title"] = $contract["name"];
		$info["subject"] = "";
		$info["keywords"] = "";
		$info["content"] = trim($param["content"]);
		$info["HT"] = true;
		$pdf_address = time() . uniqid($uid) . ".pdf";
		$info["path"] = config("contract") . $pdf_address;
		if (!file_exists(dirname($info["path"]))) {
			mkdir(dirname($info["path"]), 744);
		}
		$pdf = new \app\common\logic\Pdf();
		$pdf->createPDF($info);
		unlink(config("contract_sign") . $contract_pdf["sign_addr"]);
		unlink(config("contract") . $contract_pdf["pdf_address"]);
		if ($param["type"] == "I") {
			\think\Db::name("contract_pdf")->where("id", $id)->update(["pdf_address" => $pdf_address]);
		} else {
			\think\Db::name("contract_pdf")->where("id", $id)->update(["sign_addr" => $img ?: "", "pdf_address" => $pdf_address, "status" => 1]);
		}
		return jsonrule(["status" => 200, "msg" => "签订成功"]);
	}
}