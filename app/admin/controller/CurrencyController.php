<?php

namespace app\admin\controller;

/**
 * @title 货币配置
 * @description 接口说明：汇率接口用哪个?:https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml
 */
class CurrencyController extends AdminBaseController
{
	private $getUrlMethod = "json";
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->lang = get_system_langs();
		$this->validate = new \app\admin\validate\CurrencyValidate();
	}
	/**
	 * @title 货币配置首页Currencies
	 * @description 接口说明:货币配置首页
	 * @author wyh
	 * @url /admin/currency/currency_list
	 * @method GET
	 * @param .name:page type:int require:0 default:1 other: desc:第几页
	 * @param .name:limit type:int require:0 default:1 other: desc:每页几条
	 * @return .total:总数
	 * @return .totalPage:总页数
	 * @return .currencies:货币信息
	 * @return .currencies.code:
	 * @return .currencies.prefix:
	 * @return .currencies.suffix:
	 * @return .currencies.format:
	 * @return .currencies.rate:
	 * @return .currencies.default:默认为1，，否则为0
	 */
	public function currencyList()
	{
		$data = $this->request->param();
		$order = isset($data["order"][0]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"][0]) ? trim($data["sort"]) : "DESC";
		$page = isset($data["page"]) && !empty($data["page"]) ? intval($data["page"]) : config("page");
		$limit = isset($data["page"]) && !empty($data["limit"]) ? intval($data["limit"]) : config("limit");
		$total = \think\Db::name("product_config_groups")->count("id");
		$totalPage = ceil($total / $limit);
		$currencies = \think\Db::name("currencies")->order($order, $sort)->limit($limit * ($page - 1), $limit)->select();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "totalPage" => $totalPage, "currencies" => $currencies]);
	}
	/**
	 * @title 添加货币种类
	 * @description 接口说明:添加货币种类
	 * @author wyh
	 * @url /admin/currency/add_currency
	 * @method POST
	 * @param .name:code type:string require:1 default:1 other: desc:币种
	 * @param .name:prefix type:string require:1 default:1 other: desc:
	 * @param .name:suffix type:string require:1 default:1 other: desc:
	 * @param .name:format type:string require:1 default:1 other: desc:货币格式(下拉框)：1：1234.56；2：1,234.56 ;3:1.234,56; 4:1,234
	 * @param .name:rate type:float require:1 default:1 other: desc:税率
	 */
	public function addCurrency()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$param = array_map("trim", $param);
			$currency = ["code" => $param["code"] ?? "", "prefix" => $param["prefix"] ?? "", "suffix" => $param["suffix"] ?? "", "format" => $param["format"] ?? "", "rate" => $param["rate"] ?? ""];
			unset($currency["request_time"]);
			if (!$this->validate->scene("add_currency")->check($currency)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$exist = \think\Db::name("currencies")->where("code", $param["code"])->find();
			if ($exist) {
				return jsonrule(["status" => 400, "msg" => lang("CURRENCY_EXIST")]);
			}
			$cid = \think\Db::name("currencies")->insertGetId($currency);
			if (!$cid) {
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			active_log(sprintf($this->lang["Currency_admin_addCurrency"], $cid));
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑货币种类页面
	 * @description 接口说明:编辑货币种类页面
	 * @author wyh
	 * @url /admin/currency/edit_currency/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:货币ID
	 * @return .currency:货币信息
	 * @return .currency.code:
	 * @return .currency.prefix:
	 * @return .currency.suffix:
	 * @return .currency.format:货币格式(下拉框)：1：1234.56；2：1,234.56 ;3:1.234,56; 4:1,234
	 * @return .currency.rate:
	 * @return .currency.default:默认为1，，否则为0
	 */
	public function editCurrency()
	{
		$id = $this->request->param("id");
		$currency = \think\Db::name("currencies")->where("id", intval($id))->find();
		if ($currency) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "currency" => $currency]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 编辑货币种类页面提交
	 * @description 接口说明:编辑货币种类页面提交
	 * @author wyh
	 * @url /admin/currency/edit_currency_post
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:币种ID
	 * @param .name:code type:string require:1 default:1 other: desc:币种
	 * @param .name:prefix type:string require:1 default:1 other: desc:
	 * @param .name:suffix type:string require:1 default:1 other: desc:
	 * @param .name:format type:tinyint require:1 default:1 other: desc: 货币格式(下拉框)：1：1234.56；2：1,234.56 ;3:1.234,56; 4:1,234
	 * @param .name:rate type:number require:1 default:1 other: desc:税率
	 * @param .name:updatepricing type:string require:0 default:1 other: desc:是否更新价格信息：是(on) , 否(不传此值)
	 */
	public function editCurrencyPost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$id = isset($param["id"]) && !empty($param["id"]) ? intval($param["id"]) : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$param = array_map("trim", $param);
			$currency = ["code" => $param["code"] ?? "", "prefix" => $param["prefix"] ?? "", "suffix" => $param["suffix"] ?? "", "format" => $param["format"] ?? "", "rate" => $param["rate"] ?? ""];
			if (!$this->validate->scene("add_currency")->check($currency)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$exist = \think\Db::name("currencies")->where("id", "<>", $id)->where("code", $currency["code"])->find();
			if ($exist) {
				return jsonrule(["status" => 400, "msg" => lang("CURRENCY_EXIST")]);
			}
			$exist = \think\Db::name("currencies")->where("id", $id)->find();
			if (isset($param["updatepricing"]) && $param["updatepricing"] == "on") {
				$result = $this->currencyUpdatePricing($id);
				if (!$result) {
					return jsonrule(["status" => 400, "msg" => lang("UPDATE_PRICE_FAIL")]);
				}
				unset($currency["updatepricing"]);
			}
			$dev = "";
			if ($exist["code"] != $param["code"]) {
				$dev .= "货币代码由“" . $exist["code"] . "”改为“" . $param["code"] . "”，";
			}
			if ($exist["prefix"] != $param["prefix"]) {
				$dev .= "前缀由“" . $exist["prefix"] . "”改为“" . $param["prefix"] . "”，";
			}
			if ($exist["suffix"] != $param["suffix"]) {
				$dev .= "后缀由“" . $exist["suffix"] . "”改为“" . $param["suffix"] . "”，";
			}
			if ($exist["format"] != $param["format"]) {
				$dev .= "格式由“" . $exist["format"] . "”改为“" . $param["format"] . "”，";
			}
			if ($exist["rate"] != $param["rate"]) {
				$dev .= "汇率由“" . $exist["rate"] . "”改为“" . $param["rate"] . "”，";
			}
			$res = \think\Db::name("currencies")->where("id", $id)->update($currency);
			if (empty($dev)) {
				$dev .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Currency_admin_updateCurrency"], $id, $dev));
			if ($res) {
				return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除货币种类
	 * @description 接口说明:删除货币种类
	 * @author wyh
	 * @url /admin/currency/delete_currency/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:币种ID
	 */
	public function deleteCurrency()
	{
		$id = $this->request->param("id");
		$currency = \think\Db::name("currencies")->where("id", intval($id))->find();
		$client_exist = \think\Db::name("clients")->where("currency", intval($id))->find();
		if ($currency["default"] == 1) {
			return jsonrule(["status" => 400, "msg" => lang("DEFAULT_CURRENCY_NOT_DELETE")]);
		} else {
			if ($client_exist > 0) {
				return jsonrule(["status" => 400, "msg" => lang("存在用户使用此货币，不可删除")]);
			} else {
				\think\Db::startTrans();
				try {
					\think\Db::name("currencies")->where("id", $id)->delete();
					\think\Db::name("pricing")->where("currency", $id)->delete();
					\think\Db::commit();
					active_log(sprintf($this->lang["Currency_admin_deleteCurrency"], $id));
				} catch (\Exception $e) {
					\think\Db::rollback();
					return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
				}
				return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
			}
		}
	}
	/**
	 * @title 更新汇率
	 * @description 接口说明:更新汇率
	 * @author wyh
	 * @url /admin/currency/update_rate
	 * @method GET
	 */
	public function updateRate()
	{
		active_log($this->lang["Currency_admin_updateRate"]);
		$method = $this->getUrlMethod;
		$exchangerate = $this->getRate($method);
		if (empty($exchangerate)) {
			return jsonrule(["status" => 400, "msg" => lang("GET_RATE_FAIL")]);
		}
		$currencies = \think\Db::name("currencies")->field("code,rate")->select();
		$basecurrecy = \think\Db::name("currencies")->field("code")->where("default", 1)->find();
		if (!array_key_exists($basecurrecy["code"], $exchangerate)) {
			return jsonrule(["status" => 400, "msg" => lang("BASE_CURRENCY_ERROR")]);
		}
		$msg = [];
		$msg["status"] = 200;
		foreach ($currencies as $key => $currency) {
			$code = $currency["code"];
			if (!array_key_exists($code, $exchangerate)) {
				$result = ["status" => 400, "msg" => lang("UPDATE_CURRENCY_RATE") . $code . lang("UPDATE_CURRENCY_RATE_FAIL")];
				array_push($msg, $result);
			} else {
				$baserate = $exchangerate[$basecurrecy["code"]];
				$rate = $exchangerate[$code];
				$updaterate = $rate / $baserate;
				$updatedata["rate"] = $updaterate;
				\think\Db::name("currencies")->where("code", $code)->update($updatedata);
				$result = ["msg" => lang("UPDATE_CURRENCY_RATE") . $code . lang("UPDATE_CURRENCY_RATE_SUCCESS")];
				$msg["data"][] = $result;
			}
		}
		return jsonrule($msg);
	}
	private function getRate($method)
	{
		$rawFeed = getCurlRequest(config("app.getRateUrl." . $method));
		if ($method == "xml") {
			$rawFeed = explode("\n", $rawFeed);
			$exchangeRates = [];
			$exchangeRates["EUR"] = 1;
			foreach ($rawFeed as $line) {
				$line = trim($line);
				$matchString = "currency='";
				$pos1 = strpos($line, $matchString);
				if ($pos1) {
					$currencySymbol = substr($line, $pos1 + strlen($matchString), 3);
					$matchString = "rate='";
					$pos2 = strpos($line, $matchString);
					$rateString = substr($line, $pos2 + strlen($matchString));
					$pos3 = strpos($rateString, "'");
					$rate = substr($rateString, 0, $pos3);
					$exchangeRates[$currencySymbol] = $rate;
				}
			}
			return $exchangeRates;
		}
		if ($method == "json") {
			$exchangeRates = (array) json_decode($rawFeed)->rates;
			return $exchangeRates;
		}
		exit;
	}
	/**
	 * @title 选择默认货币(可能不需要)
	 * @description 接口说明:选择默认货币
	 * @author wyh
	 * @url /admin/currency/default_currency/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:货币ID
	 */
	public function defaultCurrency()
	{
		$id = $this->request->param("id");
		\think\Db::startTrans();
		try {
			\think\Db::name("currencies")->where("default", 1)->update(["default" => 0]);
			\think\Db::name("currencies")->where("id", intval($id))->update(["default" => 1]);
			\think\Db::commit();
			active_log(sprintf($this->lang["Currency_admin_default"], $id));
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 更新价格
	 * @description 更新价格,所有涉及到价格的都要按当前汇率进行价格更新!
	 * @author wyh
	 * @url /admin/currency/update_price
	 * @method GET
	 */
	public function updatePrice()
	{
		$result = $this->currencyUpdatePricing();
		active_log($this->lang["Currency_admin_updatePrice"]);
		if ($result) {
			return jsonrule(["status" => 200, "msg" => lang("UPDATE_PRICE_SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("UPDATE_PRICE_FAIL")]);
		}
	}
	private function currencyUpdatePricing($currencyid = "")
	{
		$defaultid = \think\Db::name("currencies")->where("default", 1)->value("id");
		if ($currencyid) {
			$defaultid = $currencyid;
		}
		\think\Db::startTrans();
		try {
			$defaultpricings = \think\Db::name("pricing")->where("currency", $defaultid)->select();
			foreach ($defaultpricings as $defaultpricing) {
				$type = $defaultpricing["type"];
				$relid = $defaultpricing["relid"];
				unset($defaultpricing["id"]);
				unset($defaultpricing["type"]);
				unset($defaultpricing["currency"]);
				unset($defaultpricing["relid"]);
				$currencies = \think\Db::name("currencies")->select();
				foreach ($currencies as $k => $currency) {
					$currencyid = $currency["id"];
					$rate = \think\Db::name("currencies")->where("id", $currencyid)->value("rate");
					$fun = function ($price) use($rate) {
						if ($price == "-1.00") {
							return $price;
						}
						return round($price * $rate, 2);
					};
					$newpricing = array_map($fun, $defaultpricing);
					$pricing = \think\Db::name("pricing")->where("type", $type)->where("relid", $relid)->where("currency", $currencyid)->find();
					if (!empty($pricing)) {
						\think\Db::name("pricing")->where("type", $type)->where("relid", $relid)->where("currency", $currencyid)->update($newpricing);
					}
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return false;
		}
		return true;
	}
}