<?php

namespace app\home\controller;

/**
 * @title 前台实名认证资料提交
 * @description 接口说明
 */
class CertificationController extends CommonController
{
	private $imagesave = "../public/upload/home/certification/image/";
	private $getimage;
	private $type;
	public function initialize()
	{
		parent::initialize();
		$this->getimage = $this->request->host() . "/upload/home/certification/image/";
		$this->type = 1;
	}
	/**
	 * @title 认证首页(判断)
	 * @description 接口说明:认证首页（空：未认证，1已认证，2未通过，3待审核，4提交资料）验证此用户是否已认证（包括个人，企业）
	 * @return .certifi_message:认证信息，空，则未认证过;
	 * @return .auth_user_id:用户id
	 * @return .auth_rela_name:真实姓名
	 * @return .auth_card_type:认证方式1=大陆 0 =非大陆
	 * @return .auth_card_number:认证卡号
	 * @return .company_name:公司名称
	 * @return .company_organ_code:公司代码
	 * @return .img_one:正面照片
	 * @return .img_two:反面照片
	 * @return .img_three:公司执照
	 * @return .status:认证状态1已认证，2未通过，3待审核，4已提交资料0为认证
	 * @return .certifi_is_upload:是否上传图片1=上传2=不上传
	 * @return .cerify_id:阿里认证id
	 * @return .auth_fail:失败原因
	 * @return .create_time:创建时间
	 * @return .update_time:修改时间
	 * @return .certifi.type 认证类型certifi_company=企业认证，certifi_person=个人认证
	 * @author wyh
	 * @url /certifi
	 * @method GET
	 */
	public function certifi()
	{
		$uid = $this->request->uid;
		$data = ["type" => "certifi_company", "status" => 0, "certifi_person_status" => 0, "certifi_is_upload" => configuration("certifi_is_upload") ?? 1];
		$arr = [];
		$param = $this->request->param();
		$log = \think\Db::name("certifi_log")->where("uid", $uid)->order("id", "DESC")->find();
		if (isset($param["action"])) {
			$action = $param["action"];
			if ($action == "personal") {
				$data["type"] = "certifi_person";
			}
		} else {
			if ($log["type"] == "1") {
				$data["type"] = "certifi_person";
				$action = "personal";
			} else {
				$action = "enterprises";
			}
		}
		if ($action) {
			$all_plugins = getPluginsList("certification", $action);
			if (empty($all_plugins[0])) {
				$arr[] = ["name" => "人工审核", "value" => "artificial", "custom_fields" => []];
			} else {
				foreach ($all_plugins as $all_plugin) {
					$arr[] = ["name" => $all_plugin["title"], "value" => $all_plugin["name"], "custom_fields" => $all_plugin["custom_fields"] ?: []];
				}
			}
		}
		$default = $log["certifi_type"] ?: ($arr[0]["name"] ?: "artificial");
		if (!isset($log["id"])) {
			$certifi = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->find();
			if (!isset($certifi["id"])) {
				$return = ["certifi_message" => $data, "certifi_select" => $arr, "default" => $default];
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
			}
		} else {
			$certifi = \think\Db::name($data["type"])->where("auth_user_id", $uid)->find();
			if (!isset($certifi["id"]) || $certifi["status"] == 2 && $param["status"] == "closed") {
				$certifi = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->find();
				$certifi["type"] = "certifi_person";
			}
		}
		if (!isset($certifi["id"])) {
			$return = ["certifi_message" => $data, "certifi_select" => $arr, "default" => $default];
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
		}
		$certifi["auth_real_name"] = mb_substr($certifi["auth_real_name"], 0, 1) . "**";
		$certifi["auth_card_number"] = mb_substr($certifi["auth_card_number"], 0, 1) . str_repeat("*", strlen($certifi["auth_card_number"]) - 2) . mb_substr($certifi["auth_card_number"], strlen($certifi["auth_card_number"]) - 1, 1);
		$data = array_merge($data, $certifi);
		if (!isset($data["img_four"])) {
			$data["img_four"] = "";
		}
		if (!empty($data["img_one"])) {
			$data["img_one"] = $this->getimage . $data["img_one"];
		}
		if (!empty($data["img_two"])) {
			$data["img_two"] = $this->getimage . $data["img_two"];
		}
		if (!empty($data["img_three"])) {
			$data["img_three"] = $this->getimage . $data["img_three"];
		}
		if (!empty($data["img_four"])) {
			$data["img_four"] = $this->getimage . $data["img_four"];
		}
		unsuspendAfterCertify($uid);
		$return = ["certifi_message" => $data, "certifi_select" => $arr, "default" => $default];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
	}
	public function personCertifiPost()
	{
		$certifi_open = configuration("certifi_open");
		if ($certifi_open != 1) {
			return json(["status" => 400, "msg" => lang("CERTIFI_CLOSE")]);
		}
		$re = [];
		$clientid = $uid = $this->request->uid;
		$tmp = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
		if (isset($tmp["id"]) && $tmp["status"] !== 2) {
			if ($tmp["status"] === 1) {
				$msg = lang("CERTIFI_COMPANY_COMPLETE");
			} elseif ($tmp["status"] === 3) {
				$msg = "企业认证待审核,不可提交";
			} elseif ($tmp["status"] === 4) {
				$msg = "企业认证已提交资料,不可提交";
			} else {
				$msg = "状态错误";
			}
			return json(["status" => 400, "msg" => $msg]);
		}
		$data = $this->request->param();
		$idcard = $data["idcard"];
		$certifimodel = new \app\home\model\CertificationModel();
		if ($certifimodel->checkOtherUsed($idcard, $clientid)) {
			return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
		}
		$certifi_type = $data["certifi_type"];
		if (empty($certifi_type)) {
			return json(["status" => 400, "msg" => lang("CFCATION_TYPE_IS_NOT_NULL")]);
		}
		$pluginName = $certifi_type;
		$class = cmf_get_plugin_class_shd($pluginName, "certification");
		$methods = get_class_methods($class);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return json($res);
			}
		}
		$all_type = array_column(getPluginsList("certification", "personal"), "name") ?: [];
		array_unshift($all_type, "artificial");
		if (!in_array($certifi_type, $all_type)) {
			return json(["status" => 400, "msg" => "认证方式错误"]);
		}
		$custom_fields_log = [];
		if ($certifi_type != "artificial") {
			$cname = cmf_parse_name($certifi_type);
			$customfields = getCertificationCustomFields($cname, "certification");
			if (!empty($customfields[0])) {
				$i = 0;
				foreach ($customfields as $customfield) {
					if ($customfield["type"] == "text") {
						if ($customfield["required"] && empty($data[$customfield["field"]])) {
							return json(["status" => 400, "msg" => $customfield["title"] . "必须填写"]);
						}
					} elseif ($customfield["type"] == "file") {
						if ($customfield["required"] && empty($_FILES[$customfield["field"]]["name"])) {
							return json(["status" => 400, "msg" => $customfield["title"] . "必须上传"]);
						}
						$image = request()->file($customfield["field"]);
						$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
						if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
							return json(["status" => 400, "msg" => "文件名只允许数字，字母，还有汉字"]);
						}
						$upload = new \app\common\logic\Upload();
						$resultUpload = $upload->uploadHandle($image);
						if ($resultUpload["status"] != 200) {
							return json(["status" => 400, "msg" => $resultUpload["msg"]]);
						}
						$avatar = $upload->moveTo($resultUpload["savename"], config("certificate"));
						if (isset($avatar["error"])) {
							return json(["status" => 400, "msg" => $avatar["error"]]);
						}
						$data[$customfield["field"]] = $avatar;
					} else {
						if ($customfield["type"] == "select" && !in_array($data[$customfield["field"]], array_keys($customfield["options"]))) {
							return json(["status" => 400, "msg" => $customfield["title"] . "在" . implode(",", array_values($customfield["options"])) . "之中"]);
						}
					}
					$i++;
					if ($i <= 10) {
						$certifi["custom_fields" . $i] = $data[$customfield["field"]];
						$custom_fields_log["custom_fields" . $i] = $data[$customfield["field"]];
					}
				}
			}
		}
		if (configuration("certifi_isbindphone") == 1 && !empty($data["phone"])) {
			$id = \think\Db::name("clients")->field("phonenumber")->where("id", $clientid)->find();
			if (!empty($id["phonenumber"]) && $id["phonenumber"] != $data["phone"]) {
				return json(["status" => 400, "msg" => lang("CFCATION_ISBINDPHONE_ERROR")]);
			}
		}
		$validate = new \app\home\validate\CertificationValidate();
		$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
		$data_re = [];
		if ($cardtype == 0) {
			$othercard["auth_user_id"] = $clientid;
			$othercard["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$othercard["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$othercard["auth_real_name"] = $data["real_name"];
			$othercard["auth_card_type"] = 0;
			$othercard["auth_card_number"] = $idcard;
			if (!$validate->scene("gatRegion")->check($othercard)) {
				$re["status"] = 406;
				$re["msg"] = $validate->getError();
				return json($re);
			}
			$pic = "";
			$othercard["img_one"] = "";
			$othercard["img_two"] = "";
			$othercard["idimage"] = $this->request->param("idimage");
			if (!isset($othercard["idimage"][0][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
				return json($re);
			}
			if (!isset($othercard["idimage"][1][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
				return json($re);
			}
			if (isset($othercard["idimage"][0][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($othercard["idimage"], config("certificate"));
				if (isset($avatar["error"])) {
					return json(["status" => 400, "msg" => $avatar["error"]]);
				}
				$pic = implode(",", $othercard["idimage"]);
				$othercard["img_one"] = $avatar[0] ?? "";
				$othercard["img_two"] = $avatar[1] ?? "";
				unset($othercard["idimage"]);
			}
			$othercard["status"] = 3;
			$checkuser = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->find();
			if (!empty($checkuser)) {
				$othercard["update_time"] = time();
				\think\Db::name("certifi_person")->where("auth_user_id", $clientid)->update($othercard);
			} else {
				$othercard["create_time"] = time();
				\think\Db::name("certifi_person")->insertGetId($othercard);
			}
			$othercard["pic"] = $pic;
			$this->save_log($othercard, $this->type, $certifi_type, $othercard["status"], $custom_fields_log);
			active_logs(sprintf($this->lang["Certification_home_personCertifiPost"], $othercard["auth_real_name"]), $clientid);
			active_logs(sprintf($this->lang["Certification_home_personCertifiPost"], $othercard["auth_real_name"]), $clientid, "", 2);
		}
		if ($cardtype == 1) {
			$certifi["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$certifi["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$certifi["auth_user_id"] = $clientid;
			$certifi["auth_real_name"] = $data["real_name"];
			$certifi["auth_card_type"] = $data["card_type"];
			$certifi["auth_card_number"] = $data["idcard"];
			if (!$validate->scene("personedit")->check($certifi)) {
				$re["status"] = 406;
				$re["msg"] = $validate->getError();
				return json($re);
			}
			$pic = "";
			$othercard["img_one"] = "";
			$othercard["img_two"] = "";
			$certifi_is_upload = configuration("certifi_is_upload");
			if ($certifi_is_upload == 1) {
				$othercard["idimage"] = $this->request->param("idimage");
				if (!isset($othercard["idimage"][0][0])) {
					$re["status"] = 400;
					$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
					return json($re);
				}
				if (!isset($othercard["idimage"][1][0])) {
					$re["status"] = 400;
					$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
					return json($re);
				}
				if (isset($othercard["idimage"][0][0])) {
					$upload = new \app\common\logic\Upload();
					$avatar = $upload->moveTo($othercard["idimage"], config("certificate"));
					if (isset($avatar["error"])) {
						return json(["status" => 400, "msg" => $avatar["error"]]);
					}
					$pic = implode(",", $othercard["idimage"]);
					$certifi["img_one"] = $othercard["idimage"][0];
					$certifi["img_two"] = $othercard["idimage"][1];
				}
			}
			$isPerson = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->find();
			if ($certifi_type == "artificial") {
				$certifi["status"] = 3;
			} else {
				$certifi["status"] = 4;
			}
			$flag = true;
			\think\Db::startTrans();
			try {
				if (!empty($isPerson)) {
					$certifi["update_time"] = time();
					$res1 = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					$res1 = \think\Db::name("certifi_person")->insertGetId($certifi);
				}
				$certifi["pic"] = $pic;
				$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
				\think\Db::commit();
			} catch (\Exception $e) {
				$flag = false;
				\think\Db::rollback();
			}
			if ($flag && $certifi_type != "artificial") {
				$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $certifi_type)->value("config");
				$_config = json_decode($_config, true);
				$free = intval($_config["free"]);
				$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("card_type", 1)->where("certifi_type", $certifi_type)->count();
				$pay = false;
				if ($free == 0 || $free > 0 && $free < $count) {
					$pay = true;
				}
				if (floatval($_config["amount"]) > 0 && $pay) {
					$amount = floatval($_config["amount"]);
					$client = \think\Db::name("clients")->where("id", $uid)->find();
					$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $amount, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $amount, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $client["defaultgateway"] ?: "", "notes" => "", "type" => "certifi_person", "url" => "verified?action=personal&step=authstart&type={$certifi_type}"];
					$invoices_items = ["uid" => $uid, "type" => "certifi_person", "description" => "个人认证", "description2" => "个人认证", "amount" => $amount, "due_time" => time(), "payment" => $client["defaultgateway"] ?: "", "rel_id" => $uid];
					\think\Db::startTrans();
					try {
						$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
						$invoices_items["invoice_id"] = $invoiceid;
						\think\Db::name("invoice_items")->insert($invoices_items);
						\think\Db::commit();
						$data_re["invoice_id"] = $invoiceid;
					} catch (\Exception $e) {
						\think\Db::rollback();
						return json(["status" => 400, "msg" => "生成账单失败:" . $e->getMessage()]);
					}
				}
			}
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data_re]);
	}
	/**
	 * @title 企业认证资料提交
	 * @description 接口说明:企业认证资料提交接口
	 * @param .name:certifi_type type:string require:0 default:1 other: desc:选择类型
	 * @param .name:company_name type:string require:1 default:1 other: desc:企业名称
	 * @param .name:company_organ_code type:string require:1 default:1 other: desc:营业执照号码
	 * @param .name:real_name type:string require:1 default:1 other: desc:提交人姓名
	 * @param .name:card_type type:tinyint require:1 default:1 other: desc:card类型：1内地身份证(默认)；0港澳台身份证
	 * @param .name:idcard type:string require:1 default:1 other: desc:身份证号
	 * @param .name:idimage[] type:image require:1 default:1 other: desc:身份证正面、反面、公司营业执照（多文件上传）
	 * @author wyh
	 * @url /company_certifi_post
	 * @method POST
	 */
	public function companyCertifiPost()
	{
		if ($this->request->isPost()) {
			$certifi_open = configuration("certifi_open");
			if ($certifi_open == 2) {
				return json(["status" => 400, "msg" => lang("CERTIFI_CLOSE")]);
			}
			if ($this->type === 1) {
				$this->type = 2;
			}
			$clientid = $uid = $this->request->uid;
			$data = $this->request->param();
			try {
				$img_three = $this->companyBusinessUpload();
				$img_four = $this->companyAuthorUpload();
			} catch (\Throwable $e) {
				return json(["status" => 400, "msg" => $e->getMessage()]);
			}
			$certifi_type = $data["certifi_type"];
			$pluginName = $certifi_type;
			$class = cmf_get_plugin_class_shd($pluginName, "certification");
			$methods = get_class_methods($class);
			if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
				$res = pluginIdcsmartauthorize($pluginName);
				if ($res["status"] != 200) {
					return jsonrule($res);
				}
			}
			$all_type = array_column(getPluginsList("certification", "enterprises"), "name") ?: [];
			array_unshift($all_type, "artificial");
			if (!in_array($certifi_type, $all_type)) {
				return json(["status" => 400, "msg" => "认证方式错误"]);
			}
			$custom_fields_log = [];
			if ($certifi_type != "artificial") {
				$cname = cmf_parse_name($certifi_type);
				$customfields = getCertificationCustomFields($cname, "certification");
				if (!empty($customfields[0])) {
					$i = 0;
					foreach ($customfields as $customfield) {
						if ($customfield["type"] == "text") {
							if ($customfield["required"] && empty($data[$customfield["field"]])) {
								return json(["status" => 400, "msg" => $customfield["title"] . "必须填写"]);
							}
						} elseif ($customfield["type"] == "file") {
							if ($customfield["required"] && empty($_FILES[$customfield["field"]]["name"])) {
								return json(["status" => 400, "msg" => $customfield["title"] . "必须上传"]);
							}
							$image = request()->file($customfield["field"]);
							$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
							if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
								return json(["status" => 400, "msg" => "文件名只允许数字，字母，还有汉字"]);
							}
							$upload = new \app\common\logic\Upload();
							$resultUpload = $upload->uploadHandle($image);
							if ($resultUpload["status"] != 200) {
								return json(["status" => 400, "msg" => $resultUpload["msg"]]);
							}
							$avatar = $upload->moveTo($resultUpload["savename"], config("certificate"));
							if (isset($avatar["error"])) {
								return json(["status" => 400, "msg" => $avatar["error"]]);
							}
							$data[$customfield["field"]] = $avatar;
						} else {
							if ($customfield["type"] == "select" && !in_array($data[$customfield["field"]], array_keys($customfield["options"]))) {
								return json(["status" => 400, "msg" => $customfield["title"] . "在" . implode(",", array_values($customfield["options"])) . "之中"]);
							}
						}
						$i++;
						if ($i <= 10) {
							$certifi["custom_fields" . $i] = $data[$customfield["field"]];
							$custom_fields_log["custom_fields" . $i] = $data[$customfield["field"]];
						}
					}
				}
			}
			$certifi["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$certifi["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$certifi["auth_user_id"] = $clientid;
			$certifi["company_name"] = $data["company_name"];
			$certifi["company_organ_code"] = $data["company_organ_code"];
			$certifi["auth_real_name"] = $data["real_name"];
			$certifi["auth_card_number"] = $data["idcard"];
			$certifi["idimage"] = $data["idimage"];
			$certifimodel = new \app\home\model\CertificationModel();
			$idcard = $certifi["auth_card_number"];
			if ($certifimodel->checkOtherUsed($idcard, $clientid)) {
				return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
			}
			$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
			$certifi["auth_card_type"] = $cardtype;
			$validate = new \app\home\validate\CertificationValidate();
			if ($cardtype == 0) {
				if (!$validate->scene("companygatregion")->check($certifi)) {
					$re["status"] = 400;
					$re["msg"] = $validate->getError();
					return $re;
				}
				$res = $this->checkCompanyUpload($certifi);
				if ($res["status"] === 400) {
					return $res;
				}
				unset($certifi["idimage"]);
				$certifi["img_one"] = "";
				$certifi["img_two"] = "";
				$certifi["img_three"] = $img_three;
				$certifi["img_four"] = $img_four;
				if (isset($res[0])) {
					$certifi["img_one"] = $res[0];
					$certifi["img_two"] = $res[1];
				}
				$pic = implode(",", [$certifi["img_one"], $certifi["img_two"], $certifi["img_three"], $certifi["img_four"]]);
				$certifi["status"] = 3;
				$checkuser = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
				if (!empty($checkuser)) {
					$certifi["update_time"] = time();
					\think\Db::name("certifi_company")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					\think\Db::name("certifi_company")->insertGetId($certifi);
				}
				$certifi["pic"] = $pic;
				$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
			}
			if ($cardtype == 1) {
				if (!$validate->scene("companyedit")->check($certifi)) {
					$re["status"] = 400;
					$re["msg"] = $validate->getError();
					return $re;
				}
				$upload = $this->checkCompanyUpload($certifi);
				if ($upload["status"] === 400) {
					return json($upload);
				}
				unset($certifi["idimage"]);
				$certifi["img_one"] = "";
				$certifi["img_two"] = "";
				$certifi["img_three"] = $img_three;
				$certifi["img_four"] = $img_four;
				if (isset($upload[0])) {
					$certifi["img_one"] = $upload[0];
					$certifi["img_two"] = $upload[1];
				}
				$pic = implode(",", [$certifi["img_one"], $certifi["img_two"], $certifi["img_three"], $certifi["img_four"]]);
				$isCompany = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
				$certifi["status"] = 4;
				if (!empty($isCompany)) {
					$certifi["update_time"] = time();
					$res1 = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					$res1 = \think\Db::name("certifi_company")->insertGetId($certifi);
				}
				if ($res1 && $certifi_type != "artificial") {
					$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $certifi_type)->value("config");
					$_config = json_decode($_config, true);
					$free = intval($_config["free"]);
					$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("card_type", 1)->where("certifi_type", $certifi_type)->count();
					$pay = false;
					if ($free == 0 || $free > 0 && $free <= $count) {
						$pay = true;
					}
					if (floatval($_config["amount"]) > 0 && $pay) {
						$amount = floatval($_config["amount"]);
						$client = \think\Db::name("clients")->where("id", $uid)->find();
						$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $amount, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $amount, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $client["defaultgateway"] ?: "", "notes" => "", "type" => "certifi_company", "url" => "verified?action=enterprises&step=authstart&type={$certifi_type}"];
						$invoices_items = ["uid" => $uid, "type" => "certifi_company", "description" => "企业认证", "description2" => "企业认证", "amount" => $amount, "due_time" => time(), "payment" => $client["defaultgateway"] ?: "", "rel_id" => $uid];
						\think\Db::startTrans();
						try {
							$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
							$invoices_items["invoice_id"] = $invoiceid;
							\think\Db::name("invoice_items")->insert($invoices_items);
							\think\Db::commit();
							$data_re["invoice_id"] = $invoiceid;
						} catch (\Exception $e) {
							\think\Db::rollback();
							return json(["status" => 400, "msg" => "生成账单失败:" . $e->getMessage()]);
						}
					}
				}
			}
			$certifi["pic"] = $pic;
			$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $certifi["company_name"]), $clientid);
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $certifi["company_name"]), $clientid, "");
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data_re]);
	}
	protected function companyBusinessUpload()
	{
		$img_three = "";
		$conf = $this->getConfInfo();
		if (!$conf["certifi_business_is_upload"]) {
			return $img_three;
		}
		if (empty($_FILES["business_license"]["name"][0])) {
			throw new \think\Exception(lang("CERTIFI_CARD_IMAGE3"));
		}
		$attachments = request()->file("business_license");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				throw new \think\Exception("文件名只允许数字，字母，还有汉字");
			}
		}
		$upload = new \app\common\logic\Upload(config("certificate"));
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				throw new \think\Exception(lang("ERROR MESSAGE"));
			}
			if ($resultUpload["status"] == 200) {
				$img_three = $resultUpload["savename"];
			} else {
				throw new \think\Exception(lang("ERROR MESSAGE"));
			}
		}
		return $img_three;
	}
	protected function companyAuthorUpload()
	{
		$img_four = "";
		$conf = $this->getConfInfo();
		if (!$conf["certifi_business_is_author"]) {
			return $img_four;
		}
		if (empty($_FILES["certifi_author"]["name"][0])) {
			throw new \think\Exception(lang("CERTIFI_CARD_IMAGE4"));
		}
		$attachments = request()->file("certifi_author");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				throw new \think\Exception("文件名只允许数字，字母，还有汉字");
			}
		}
		$upload = new \app\common\logic\Upload(config("certificate"));
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				throw new \think\Exception(lang("ERROR MESSAGE"));
			}
			if ($resultUpload["status"] == 200) {
				$img_four = $resultUpload["savename"];
			} else {
				throw new \think\Exception(lang("ERROR MESSAGE"));
			}
		}
		return $img_four;
	}
	private function checkCompanyUpload($arr = [])
	{
		$re = [];
		$certifi_is_upload = configuration("certifi_is_upload");
		if ($certifi_is_upload == 1) {
			if (empty($arr["idimage"][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
				return $re;
			}
			if (empty($arr["idimage"][1])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
				return $re;
			}
			if (!empty($arr["idimage"][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($arr["idimage"], config("certificate"));
				if (isset($avatar["error"])) {
					return ["status" => 400, "msg" => $avatar["error"]];
				}
				return $avatar;
			}
		}
		return [];
	}
	/**
	 * @title 个人认证转企业认证
	 * @description 接口说明:已认证时，个人认证转企业认证接口
	 * @param .name:company_name type:string require:1 default:1 other: desc:企业名称
	 * @param .name:company_organ_code type:string require:1 default:1 other: desc:营业执照号码
	 * @param .name:real_name type:string require:1 default:1 other: desc:提交人姓名
	 * @param .name:card_type type:tinyint require:1 default:1 other: desc:card类型：1内地身份证(默认)；0港澳台身份证
	 * @param .name:idcard type:string require:1 default:1 other: desc:身份证号
	 * @param .name:idimage[] type:image require:1 default:1 other: desc:身份证正面、反面、公司营业执照（多文件上传）
	 * @author wyh
	 * @url /person_to_company
	 * @method POST
	 */
	public function personToCompany()
	{
		$this->type = 3;
		$clientid = $this->request->uid;
		$res = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->where("status", 1)->find();
		if (!empty($res)) {
			$result = $this->companyCertifiPost();
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $res["auth_real_name"]), $res["auth_user_id"]);
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $res["auth_real_name"]), $res["auth_user_id"], "", 2);
			return $result;
		} else {
			return json(["status" => 400, "msg" => lang("CFCATION_PERSONAL_AUTH_NOT_PASS")]);
		}
	}
	/**
	 * @title 查询认证是否完成
	 * @description 接口说明:查询认证是否完成
	 * @return status:400失败，200成功
	 * @author wyh
	 * @url /certifi_ping
	 * @method get
	 */
	public function ping()
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$type = $param["type"] ?: "";
		$data = [];
		$log = \think\Db::name("certifi_log")->where("uid", $uid)->order("id", "DESC")->find();
		if (!isset($log["id"])) {
			return json(["status" => 400, "msg" => lang("FAIL MESSAGE"), "certifi_message" => ["status" => 0]]);
		}
		$table = "certifi_company";
		if ($log["type"] == "1") {
			$table = "certifi_person";
		}
		$certifi = \think\Db::name($table)->where("auth_user_id", $uid)->find();
		if (empty($certifi["certify_id"])) {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		$data = array_merge($data, $certifi);
		if (($data["status"] == 1 || $data["status"] == 2) && $log["certifi_type"] != "Ali" && $log["certifi_type"] != "Idcsmartali") {
			if ($data["status"] == 1) {
				$status = $table === "certifi_company" ? 3 : 1;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "auth_fail" => "", "update_time" => time()]);
				if ($status == 1 && configuration("certifi_realname") == 1) {
					$cl = \think\Db::name("certifi_log")->where("id", $log["id"])->find();
					\think\Db::name("clients")->where("id", $cl["uid"])->update(["username" => $cl["certifi_name"]]);
				}
				unsuspendAfterCertify($uid);
			}
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		if (($data["status"] == 4 || $data["status"] == 2) && $data["auth_card_type"] == 1) {
			try {
				$res = zjmfhook($type, "certification", ["certify_id" => $certifi["certify_id"]], "getStatus");
			} catch (\Exception $e) {
				return json(["status" => 400, "msg" => $e->getMessage()]);
			}
			if ($res["status"] == 1) {
				$status = $table === "certifi_company" ? 3 : 1;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "auth_fail" => "", "update_time" => time()]);
				if ($status == 1 && configuration("certifi_realname") == 1) {
					$cl = \think\Db::name("certifi_log")->where("id", $log["id"])->find();
					\think\Db::name("clients")->where("id", $cl["uid"])->update(["username" => $cl["certifi_name"]]);
				}
				unsuspendAfterCertify($uid);
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} elseif ($res["status"] == 2) {
				$status = 2;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status, "error" => $res["msg"]]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "update_time" => time(), "auth_fail" => $res["msg"]]);
			}
		}
		return json(["status" => 400, "msg" => lang("CFCATION_PERSONAL_AUTH_NOT_PASS")]);
	}
	private function save_log($arr, $type, $certifi_type, $status, $custom_fields_log = [])
	{
		$data = ["uid" => $this->request->uid, "certifi_name" => $arr["auth_real_name"], "card_type" => $arr["auth_card_type"], "idcard" => $arr["auth_card_number"], "company_name" => $arr["company_name"] ?? "", "bank" => $arr["bank"] ?? "", "phone" => $arr["phone"] ?? "", "company_organ_code" => $arr["company_organ_code"] ?? "", "pic" => $arr["pic"], "create_time" => $this->request->time(), "status" => $status, "type" => $type, "certifi_type" => $certifi_type, "custom_fields_log" => json_encode($custom_fields_log)];
		$res = \think\Db::name("certifi_log")->insert($data);
		return $res;
	}
	protected function getConfInfo()
	{
		$conf = configuration(["certifi_open", "certifi_is_upload", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path"]);
		$conf["certifi_is_upload"] = $conf["certifi_is_upload"] == 2 ? 0 : 1;
		$conf["certifi_open"] = $conf["certifi_open"] == 2 ? 0 : 1;
		if (!$conf["certifi_open"]) {
			$conf["certifi_business_open"] = 0;
		}
		$data = ["certifi_is_upload" => $conf["certifi_open"] ? $conf["certifi_is_upload"] : 0, "certifi_business_is_author" => $conf["certifi_business_open"] ? $conf["certifi_business_is_author"] : 0, "certifi_business_author_path" => $conf["certifi_business_author_path"] ? config("author_attachments_url") . $conf["certifi_business_author_path"] : ""];
		$data["certifi_business_is_upload"] = $conf["certifi_business_open"] ? $conf["certifi_business_is_upload"] : $data["certifi_is_upload"];
		return $data;
	}
}