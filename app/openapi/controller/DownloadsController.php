<?php

namespace app\openapi\controller;

/**
 * @title 资源下载
 * @description 接口描述
 */
class DownloadsController extends \cmf\controller\HomeBaseController
{
	public function downloads(\think\Request $request)
	{
		$userGetCookie = userGetCookie();
		if ($userGetCookie) {
			$header["authorization"] = "JWT " . $userGetCookie;
		} else {
			$jwt = $request->jwt;
			$header["authorization"] = "JWT " . $jwt;
		}
		$type = $request->type;
		$check = new \app\http\middleware\Check();
		$res = $check->checkTokenDownloads($header);
		if ($res["status"] < 1000) {
			$uid = 0;
		} else {
			$uid = $res["id"];
		}
		$id = \intval($request->id);
		if (empty($id)) {
			return json(["status" => 400, "msg" => "ID error"]);
		}
		$download_data = \think\Db::name("downloads")->where("id", $id)->find();
		if (empty($download_data)) {
			return json(["status" => 400, "msg" => "Download file not found"]);
		}
		$clientsonly = $download_data["clientsonly"];
		$productdownload = $download_data["productdownload"];
		if ($clientsonly && empty($uid)) {
			return json(["status" => 400, "msg" => "Please login before downloading", "type" => 1]);
		}
		if ($productdownload) {
			$product_download_data = \think\Db::name("product_downloads")->field("id,product_id")->where("download_id", $id)->select()->toArray();
			$need_product = array_column($product_download_data, "product_id");
			if (!empty($need_product)) {
				$exists_data = \think\Db::name("host")->field("id, domainstatus")->where("uid", $uid)->whereIn("productid", $need_product[0])->where("domainstatus", "Active")->select()->toArray();
				if (empty($exists_data[0])) {
					$product_data = \think\Db::name("products")->field("*")->whereIn("id", $need_product[0])->select()->toArray();
					$currency = $this->currencyPriority("", $uid);
					$currencyid = $currency["id"];
					foreach ($product_data as $key => $v) {
						if (!empty($v)) {
							$paytype = (array) json_decode($v["pay_type"]);
							$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
							if (!empty($paytype["pay_ontrial_status"])) {
								if ($pricing["ontrial"] >= 0) {
									$v["product_price"] = $pricing["ontrial"];
									$v["setup_fee"] = $pricing["ontrialfee"];
									$v["billingcycle"] = "ontrial";
									$v["billingcycle_zh"] = lang("ONTRIAL");
								} else {
									$v["product_price"] = number_format(0, 2);
									$v["setup_fee"] = number_format(0, 2);
									$v["billingcycle"] = "";
									$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
								}
							}
							if ($paytype["pay_type"] == "free") {
								$v["product_price"] = number_format(0, 2);
								$v["setup_fee"] = number_format(0, 2);
								$v["billingcycle"] = "free";
								$v["billingcycle_zh"] = lang("FREE");
							} elseif ($paytype["pay_type"] == "hour") {
								if ($pricing["hour"] >= 0) {
									$v["product_price"] = $pricing["hour"];
									$v["setup_fee"] = $pricing["hsetupfee"];
									$v["billingcycle"] = "hour";
									$v["billingcycle_zh"] = lang("HOUR");
								} else {
									$v["product_price"] = number_format(0, 2);
									$v["setup_fee"] = number_format(0, 2);
									$v["billingcycle"] = "";
									$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
								}
							} elseif ($paytype["pay_type"] == "day") {
								if ($pricing["day"] >= 0) {
									$v["product_price"] = $pricing["day"];
									$v["setup_fee"] = $pricing["dsetupfee"];
									$v["billingcycle"] = "day";
									$v["billingcycle_zh"] = lang("DAY");
								} else {
									$v["product_price"] = number_format(0, 2);
									$v["setup_fee"] = number_format(0, 2);
									$v["billingcycle"] = "";
									$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
								}
							} elseif ($paytype["pay_type"] == "onetime") {
								if ($pricing["onetime"] >= 0) {
									$v["product_price"] = $pricing["onetime"];
									$v["setup_fee"] = $pricing["osetupfee"];
									$v["billingcycle"] = "onetime";
									$v["billingcycle_zh"] = lang("ONETIME");
								} else {
									$v["product_price"] = number_format(0, 2);
									$v["setup_fee"] = number_format(0, 2);
									$v["billingcycle"] = "";
									$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
								}
							} elseif (!empty($pricing) && $paytype["pay_type"] == "recurring") {
								if ($pricing["monthly"] >= 0) {
									$v["product_price"] = $pricing["monthly"];
									$v["setup_fee"] = $pricing["msetupfee"];
									$v["billingcycle"] = "monthly";
									$v["billingcycle_zh"] = lang("MONTHLY");
								} elseif ($pricing["quarterly"] >= 0) {
									$v["product_price"] = $pricing["quarterly"];
									$v["setup_fee"] = $pricing["qsetupfee"];
									$v["billingcycle"] = "quarterly";
									$v["billingcycle_zh"] = lang("QUARTERLY");
								} elseif ($pricing["semiannually"] >= 0) {
									$v["product_price"] = $pricing["semiannually"];
									$v["setup_fee"] = $pricing["ssetupfee"];
									$v["billingcycle"] = "semiannually";
									$v["billingcycle_zh"] = lang("SEMIANNUALLY");
								} elseif ($pricing["annually"] >= 0) {
									$v["product_price"] = $pricing["annually"];
									$v["setup_fee"] = $pricing["asetupfee"];
									$v["billingcycle"] = "annually";
									$v["billingcycle_zh"] = lang("ANNUALLY");
								} elseif ($pricing["biennially"] >= 0) {
									$v["product_price"] = $pricing["biennially"];
									$v["setup_fee"] = $pricing["bsetupfee"];
									$v["billingcycle"] = "biennially";
									$v["billingcycle_zh"] = lang("BIENNIALLY");
								} elseif ($pricing["triennially"] >= 0) {
									$v["product_price"] = $pricing["triennially"];
									$v["setup_fee"] = $pricing["tsetupfee"];
									$v["billingcycle"] = "triennially";
									$v["billingcycle_zh"] = lang("TRIENNIALLY");
								} elseif ($pricing["fourly"] >= 0) {
									$v["product_price"] = $pricing["fourly"];
									$v["setup_fee"] = $pricing["foursetupfee"];
									$v["billingcycle"] = "fourly";
									$v["billingcycle_zh"] = lang("FOURLY");
								} elseif ($pricing["fively"] >= 0) {
									$v["product_price"] = $pricing["fively"];
									$v["setup_fee"] = $pricing["fivesetupfee"];
									$v["billingcycle"] = "fively";
									$v["billingcycle_zh"] = lang("FIVELY");
								} elseif ($pricing["sixly"] >= 0) {
									$v["product_price"] = $pricing["sixly"];
									$v["setup_fee"] = $pricing["sixsetupfee"];
									$v["billingcycle"] = "sixly";
									$v["billingcycle_zh"] = lang("SIXLY");
								} elseif ($pricing["sevenly"] >= 0) {
									$v["product_price"] = $pricing["sevenly"];
									$v["setup_fee"] = $pricing["sevensetupfee"];
									$v["billingcycle"] = "sevenly";
									$v["billingcycle_zh"] = lang("SEVENLY");
								} elseif ($pricing["eightly"] >= 0) {
									$v["product_price"] = $pricing["eightly"];
									$v["setup_fee"] = $pricing["eightsetupfee"];
									$v["billingcycle"] = "eightly";
									$v["billingcycle_zh"] = lang("EIGHTLY");
								} elseif ($pricing["ninely"] >= 0) {
									$v["product_price"] = $pricing["ninely"];
									$v["setup_fee"] = $pricing["ninesetupfee"];
									$v["billingcycle"] = "ninely";
									$v["billingcycle_zh"] = lang("NINELY");
								} elseif ($pricing["tenly"] >= 0) {
									$v["product_price"] = $pricing["tenly"];
									$v["setup_fee"] = $pricing["tensetupfee"];
									$v["billingcycle"] = "tenly";
									$v["billingcycle_zh"] = lang("TENLY");
								} else {
									$v["product_price"] = number_format(0, 2);
									$v["setup_fee"] = number_format(0, 2);
									$v["billingcycle"] = "";
									$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
								}
							} else {
								$v["product_price"] = number_format(0, 2);
								$v["setup_fee"] = number_format(0, 2);
								$v["billingcycle"] = "";
								$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
							}
						}
						$product_data[$key] = $v;
					}
					$product_name_arr = array_column($product_data, "name");
					$info = "您需要购买并激活产品" . implode(",", $product_name_arr) . "才能下载此文件";
					return json(["status" => 400, "msg" => $info, "type" => 2, "pid" => $need_product[0], "cylcle" => $product_data[0]["billingcycle"]]);
				}
			} else {
				return json(["status" => 400, "msg" => "The file you downloaded has not been bound with related products, so it can't be downloaded for the time being", "data" => ["type" => 3]]);
			}
		}
		if ($type == 1) {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		$filename = $download_data["location"];
		if ($download_data["filetype"] == "remote") {
			\think\Db::name("downloads")->where("id", $id)->setInc("downloads");
			\ob_clean();
			return json(["status" => 200, "data" => $this->redirect($download_data["locationname"], 302)]);
			exit;
		}
		if (file_exists(UPLOAD_PATH_DWN . "support/" . $filename)) {
			\think\Db::name("downloads")->where("id", $id)->setInc("downloads");
			\ob_clean();
			return download(UPLOAD_PATH_DWN . "support/" . $filename, $download_data["locationname"]);
			return json(["status" => 200, "data" => $this->download(UPLOAD_PATH_DWN . "support/" . $filename, explode("^", $filename)[1])]);
			exit;
			return $this->download(UPLOAD_PATH_DWN . "support/" . $filename, $filename);
		} else {
			return json(["status" => 400, "msg" => "Resources lost"]);
		}
	}
	private function download($file_url, $new_name = "")
	{
		if (!isset($file_url) || trim($file_url) == "") {
			echo "500";
		}
		if (!file_exists($file_url)) {
			echo "404";
		}
		$filename = $new_name;
		$file = $file_url;
		if (!file_exists($file)) {
			exit("抱歉，文件不存在！");
		}
		$type = filetype($file);
		$today = date("F j, Y, g:i a");
		$time = time();
		header("Content-type: {$type}");
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
		header("Pragma: no-cache");
		header("Expires: 0");
		header("Content-Type: application/zip");
		ob_clean();
		flush();
		set_time_limit(0);
		echo readfile($file);
	}
	private function currencyPriority($currencyId = "", $uid = "")
	{
		if (!empty($currencyId)) {
			$currencyId = intval($currencyId);
			$currency = \think\Db::name("currencies")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("clients")->field("currency")->where("id", $uid)->find();
			if (!empty($currency["currency"])) {
				$currency = \think\Db::name("currencies")->where("id", $currency["currency"])->find();
			} else {
				$currency = \think\Db::name("currencies")->where("default", 1)->find();
			}
		}
		$currency = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $currency);
		unset($currency["format"]);
		unset($currency["rate"]);
		unset($currency["default"]);
		return $currency;
	}
	public function getDownloads(\think\Request $request)
	{
		$param = $request->param();
		$cate_id = $param["cate_id"] ? intval($param["cate_id"]) : 0;
		$check = new \app\http\middleware\Check();
		$res = $check->checkToken($request);
		if ($res["status"] < 1000) {
			$uid = 0;
		} else {
			$uid = $res["id"];
		}
		$data = [];
		$download_logic = new \app\common\logic\Download();
		if ($cate_id == 0) {
			$cats_data = $download_logic->getCatesDownload1(0);
			$cate_id = $cats_data[0]["id"];
		}
		$cate_data = $download_logic->getClassifiedDownloadRecords($cate_id, $uid);
		foreach ($cate_data as $k => $v) {
			$cate_data[$k]["count"] = $v["file_count"];
			unset($cate_data[$k]["file_count"]);
			unset($cate_data[$k]["parentid"]);
		}
		$data["cate"] = $cate_data;
		if ($cate_id) {
			$data["downloads"] = \app\common\model\DownloadsModel::getAllowDownListHome($cate_id, $uid);
		} else {
			$data["downloads"] = [];
		}
		foreach ($data["downloads"] as $k => $v) {
			$data["downloads"][$k]["description"] = $v["description"] ?? "";
			$data["downloads"][$k]["update_time"] = strtotime($data["downloads"][$k]["update_time"]);
			$data["downloads"][$k]["link"] = request()->domain() . "/" . $data["downloads"][$k]["down_link"];
			unset($data["downloads"][$k]["down_link"]);
			unset($data["downloads"][$k]["clientsonly"]);
			unset($data["downloads"][$k]["location"]);
			unset($data["downloads"][$k]["locationname"]);
		}
		return json(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 返回搜索数据
	 * @description 接口说明:返回分类数据
	 * @author 萧十一郎
	 * @url download/search
	 * @method POST
	 * @param .name:search type:string require:1 default: other: desc:搜索关键字
	 * @return downloads:下载数据@
	 * @downloads  id:文件id
	 * @downloads  title:文件id
	 * @downloads  description:文件描述
	 * @downloads  downloads:下载数
	 * @downloads  down_link:下载链接
	 */
	public function search(\think\Request $request)
	{
		if ($request->isPost()) {
			$param = $request->param();
			$uid = cmf_get_current_user_id();
			$search = strval($param["search"]);
			if (empty($search)) {
				return json(["status" => 200, "data" => []]);
			}
			$returndata["downloads"] = \app\common\model\DownloadsModel::seachFileHome($search, $uid);
			return json(["status" => 200, "data" => $returndata]);
		}
	}
}