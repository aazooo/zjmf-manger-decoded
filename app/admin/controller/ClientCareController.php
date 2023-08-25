<?php

namespace app\admin\controller;

/**
 * @title 客户关怀模块
 * @description 接口说明: 客户关怀模块
 */
class ClientCareController extends AdminBaseController
{
	private $method = ["email", "message", "wechat"];
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->validate = new \app\admin\validate\ClientCareValidate();
	}
	public function test()
	{
		return cmf_plugin_url("ClientCare://ClientCare/searchCondition", ["id" => 1], true);
	}
	/**
	 * @title 搜索条件
	 * @description 接口说明:搜索条件
	 * @author wyh
	 * @url /admin/client_care/search_condition
	 * @method GET
	 * @return  .trigger:搜索条件
	 */
	public function searchCondition()
	{
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "trigger" => config("app.client_care_trigger")]);
	}
	/**
	 * @title 客戶关怀首页(搜索)
	 * @description 接口说明:客戶关怀首页
	 * @author wyh
	 * @url /admin/client_care/care_list
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:1 other: desc:每页多少条
	 * @param .name:order type:int require:1 default:1 other: desc:排序字段
	 * @param .name:order_method type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:name type:string require:0 default:1 other: desc:按关怀名称搜索
	 * @param .name:trigger type:string require:0 default:1 other: desc:按触发条件搜索
	 * @return  care_list:客户关怀列表@
	 * @care_list  care_id:客户关怀列表id
	 * @care_list  name:关怀名称
	 * @care_list  trigger:触发条件
	 * @care_list  method:关怀方式
	 * @care_list  time:天数
	 * @care_list  email_template:邮件模板
	 * @care_list  message_template:短信模板
	 * @care_list  range_type:1大陆，0非大陆
	 * @care_list  status:状态:1可用，0不可用
	 * @care_list  create_time:创建时间
	 * @care_list  update_time:更新时间
	 */
	public function careList()
	{
		$data = $this->request->param();
		$page = isset($data["page"]) && !empty($data["page"]) ? intval($data["page"]) : 1;
		$limit = isset($data["limit"]) && !empty($data["limit"]) ? intval($data["page"]) : config("app.page_size");
		$order = isset($data["order"]) && !empty($data["order"]) ? trim($data["order"]) : "care_id";
		$orderfield = ["care_id", "name", "trigger", "time", "method", "email_template", "message_template", "range_type", "status", "create_time", "update_time"];
		if (!in_array($order, $orderfield)) {
			return jsonrule(["status" => 400, "msg" => lang("OERDER_FIELD_ERROR")]);
		}
		$ordermethod = isset($data["order_method"]) && !empty($data["order_method"]) ? strtoupper(trim($data["order_method"])) : "DESC";
		$total = \think\Db::name("client_care")->count("id");
		$results = \think\Db::name("client_care")->alias("cc")->field("cc.id as care_id,cc.name,cc.trigger,cc.time,cc.method,et.name as email_template,mt.title as message_template,cc.range_type
            ,cc.status,cc.create_time,cc.update_time")->leftJoin("email_templates et", "et.id = cc.email_template_id")->leftJoin("message_template mt", "mt.id = cc.message_template_id")->where(function (\think\db\Query $query) use($data) {
			if (!empty($data["name"])) {
				$name = trim($data["name"]);
				$query->where("name", "like", "%{$name}%");
			}
			if (!empty($data["trigger"])) {
				$trigger = trim($data["trigger"]);
				$query->where("trigger", "like", "%{$trigger}%");
			}
		})->limit($limit * ($page - 1), $limit)->order($order . " " . $ordermethod)->select();
		$resultsfilter = [];
		foreach ($results as $k => $result) {
			$result = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $result);
			$result["trigger"] = $this->argToLang($result["trigger"]);
			$resultsfilter[$k] = $result;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "care_list" => $resultsfilter, "total" => $total]);
	}
	/**
	 * @title 添加关怀条件页面
	 * @description 接口说明:添加关怀条件页面
	 * @author wyh
	 * @url /admin/client_care/create_care
	 * @method GET
	 * @return .products:产品组--产品信息
	 * @return .trigger:触发条件
	 * @return .method:关怀方式
	 * @return .email_template:邮件模板
	 * @return .message_tmeplate:短信模板
	 */
	public function createCare()
	{
		$groups = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->select();
		$groupsfilter = [];
		foreach ($groups as $key => $group) {
			$group = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $group);
			$products = \think\Db::name("products")->field("id,name")->where("gid", $group["id"])->select();
			$productsfilter = [];
			foreach ($products as $k => $product) {
				$productsfilter[$k] = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $product);
			}
			$group["child"] = [];
			$group["child"] = $productsfilter;
			$groupsfilter[$key] = $group;
		}
		$triggers = config("app.client_care_trigger");
		$method = $this->method;
		$emailtemplate = \think\Db::name("email_templates")->field("id,name")->where("name", "like", "%Care%")->select();
		$messagetmep = \think\Db::name("message_template")->field("id,title")->select();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "products" => $groupsfilter, "trigger" => $triggers, "method" => $method, "email_template" => $emailtemplate, "message_tmeplate" => $messagetmep]);
	}
	/**
	 * @title 添加关怀条件页面提交
	 * @description 接口说明:添加关怀条件页面提交
	 * @author wyh
	 * @url /admin/client_care/create_care_post
	 * @method POST
	 * @param .name:name type:string require:1 default:1 other: desc:
	 * @param .name:trigger type:string require:1 default:1 other: desc:触发条件
	 * @param .name:ids[] type:string require:1 default:1 other: desc:产品ID（数组）
	 * @param .name:time type:string require:1 default:1 other: desc:天数
	 * @param .name:method[] type:string require:1 default:1 other: desc:(多选框)关怀方式(邮件email、短信message、微信wechat(暂不考虑))
	 * @param .name:range_type type:string require:1 default:1 other: desc:1大陆，0非大陆(选择)
	 * @param .name:mailtemp_id type:string require:1 default:1 other: desc:邮件模板ID:根据关怀方式弹出对应的模板选择.
	 * @param .name:message_id type:string require:1 default:1 other: desc:短信模板ID
	 * @param .name:status type:string require:1 default:1 other: desc:状态：1可用(默认),0未用
	 */
	public function createCarePost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$trigger = strtolower(trim($param["trigger"]));
			if (!in_array($trigger, array_column(config("app.client_care_trigger"), "name"))) {
				return jsonrule(["status" => 400, "msg" => lang("CLIENT_CARE_TIRGGER_NO_EXIST")]);
			}
			$type = explode("_", $trigger)[0];
			if ($type == "product") {
				if (!$this->validate->scene("create_care_product")->check($param)) {
					return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
				}
			} else {
				if (!$this->validate->scene("create_care_register")->check($param)) {
					return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
				}
			}
			$care["name"] = $param["name"];
			$care["trigger"] = $trigger;
			$care["time"] = isset($param["time"]) ? intval($param["time"]) : 0;
			$method = $param["method"];
			if (!is_array($method)) {
				$method = [$method];
			}
			foreach ($method as $k => $v) {
				if (!in_array($v, $this->method)) {
					return jsonrule(["status" => 400, "msg" => lang("CLIENT_CARE_METHOD_NO_EXIST")]);
				}
			}
			$care["method"] = implode(",", $method);
			$care["range_type"] = isset($param["range_type"]) ? intval($param["range_type"]) : 1;
			$care["email_template_id"] = isset($param["mailtemp_id"]) ? intval($param["mailtemp_id"]) : "";
			$care["message_template_id"] = isset($param["message_id"]) ? intval($param["message_id"]) : "";
			$care["status"] = intval($param["status"]);
			$care["create_time"] = time();
			$trim = array_map("trim", $care);
			$carefilter = array_map("htmlspecialchars", $trim);
			\think\Db::startTrans();
			try {
				$careid = \think\Db::name("client_care")->insertGetId($carefilter);
				$ids = $param["ids"];
				if (!is_array($ids)) {
					$ids = [$ids];
				}
				if (!empty($ids)) {
					foreach ($ids as $id) {
						$links["care_id"] = $careid;
						$links["product_id"] = $id;
						\think\Db::name("client_care_product_links")->insert($links);
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	protected function argToLang($trigger)
	{
		$triggers = config("app.client_care_trigger");
		foreach ($triggers as $key => $value) {
			if ($value["name"] == $trigger) {
				$trigger = $value["name_zh"];
			}
		}
		return $trigger;
	}
	/**
	 * @title 编辑关怀条件页面
	 * @description 接口说明:编辑关怀条件页面
	 * @author wyh
	 * @url /admin/client_care/edit_care/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:关怀条件ID
	 * @return .care:关怀条件信息
	 * @return .products:产品信息
	 * @return .link_products:选中的产品
	 * @return .triggers:触发条件
	 * @return .method:关怀方式
	 * @return .email_template:所有类型为care的邮件模板
	 * @return .message_template:所有短信模板
	 */
	public function editCare()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) && !empty($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$care = \think\Db::name("client_care")->where("id", $id)->find();
		$care = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $care);
		$method = isset($care["method"]) ? explode(",", $care["method"]) : "";
		if ($care) {
			$care["method"] = $method;
		}
		$linkproducts = \think\Db::name("client_care_product_links")->field("product_id")->where("care_id", $id)->select();
		$link = [];
		foreach ($linkproducts as $linkproduct) {
			array_push($link, $linkproduct["product_id"]);
		}
		$groups = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->select();
		$groupsfilter = [];
		foreach ($groups as $key => $group) {
			$group = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $group);
			$products = \think\Db::name("products")->field("id,name")->where("gid", $group["id"])->select();
			$productsfilter = [];
			foreach ($products as $k => $product) {
				$productsfilter[$k] = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $product);
			}
			$group["child"] = [];
			$group["child"] = $productsfilter;
			$groupsfilter[$key] = $group;
		}
		$triggers = config("app.client_care_trigger");
		$method = $this->method;
		$emailtemplate = \think\Db::name("email_templates")->field("id,name")->where("name", "like", "%Care%")->select();
		$messagetmep = \think\Db::name("message_template")->field("id,title")->select();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "care" => $care, "products" => $groupsfilter, "link_products" => $link, "triggers" => $triggers, "method" => $method, "email_template" => $emailtemplate, "message_template" => $messagetmep]);
	}
	/**
	 * @title 编辑关怀条件页面提交
	 * @description 接口说明:编辑关怀条件页面提交
	 * @author wyh
	 * @url /admin/client_care/edit_care_post
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:关怀条件ID
	 * @param .name:name type:string require:1 default:1 other: desc:关怀名称
	 * @param .name:trigger type:string require:1 default:1 other: desc:触发条件
	 * @param .name:ids[] type:string require:1 default:1 other: desc:产品ID（数组）
	 * @param .name:time type:string require:1 default:1 other: desc:天数
	 * @param .name:method[] type:string require:1 default:1 other: desc:(多选框)关怀方式(邮件email、短信message、微信wechat(暂不考虑))
	 * @param .name:range_type type:string require:1 default:1 other: desc:1大陆，0非大陆(选择)
	 * @param .name:mailtemp_id type:string require:1 default:1 other: desc:邮件模板ID:根据关怀方式弹出对应的模板选择.
	 * @param .name:message_id type:string require:1 default:1 other: desc:短信模板ID
	 * @param .name:status type:string require:1 default:1 other: desc:状态：1可用(默认),0未用
	 */
	public function editCarePost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$id = isset($param["id"]) && !empty($param["id"]) ? $param["id"] : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$trigger = strtolower(trim($param["trigger"]));
			if (!in_array($trigger, array_column(config("app.client_care_trigger"), "name"))) {
				return jsonrule(["status" => 400, "msg" => lang("CLIENT_CARE_TIRGGER_NO_EXIST")]);
			}
			$type = explode("_", $trigger)[0];
			if ($type == "product") {
				if (!$this->validate->scene("create_care_product")->check($param)) {
					return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
				}
			} else {
				if (!$this->validate->scene("create_care_register")->check($param)) {
					return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
				}
			}
			$care["name"] = $param["name"];
			$care["trigger"] = $trigger;
			$care["time"] = isset($param["time"]) ? intval($param["time"]) : 0;
			$method = $param["method"];
			if (!is_array($method)) {
				$method = [$method];
			}
			foreach ($method as $k => $v) {
				if (!in_array($v, $this->method)) {
					return jsonrule(["status" => 400, "msg" => lang("CLIENT_CARE_METHOD_NO_EXIST")]);
				}
			}
			$care["method"] = implode(",", $method);
			$care["range_type"] = isset($param["range_type"]) ? intval($param["range_type"]) : 1;
			$care["email_template_id"] = isset($param["mailtemp_id"]) ? intval($param["mailtemp_id"]) : "";
			$care["message_template_id"] = isset($param["message_id"]) ? intval($param["message_id"]) : "";
			$care["status"] = intval($param["status"]);
			$care["create_time"] = time();
			$trim = array_map("trim", $care);
			$carefilter = array_map("htmlspecialchars", $trim);
			\think\Db::startTrans();
			try {
				$careid = \think\Db::name("client_care")->where("id", $id)->update($carefilter);
				$ids = $param["ids"];
				if (!is_array($ids)) {
					$ids = [$ids];
				}
				if (!empty($ids)) {
					\think\Db::name("client_care_product_links")->where("care_id", $id)->delete();
					foreach ($ids as $id) {
						$links["care_id"] = $careid;
						$links["product_id"] = $id;
						\think\Db::name("client_care_product_links")->insert($links);
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除关怀条件
	 * @description 接口说明:删除关怀条件
	 * @author wyh
	 * @url /admin/client_care/delete_care/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:关怀条件ID
	 */
	public function deleteCare()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) && !empty($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("client_care")->where("id", $id)->delete();
			\think\Db::name("client_care_product_links")->where("care_id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}