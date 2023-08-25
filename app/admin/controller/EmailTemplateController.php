<?php

namespace app\admin\controller;

/**
 * @title 邮件模板
 * @description 接口说明
 */
class EmailTemplateController extends AdminBaseController
{
	private $attachment_path = "";
	private $attachment_url = "";
	private $imagesave = "../public/upload/admin/emailattachments/";
	private $getattachments = WEB_ROOT . "upload/admin/emailattachments/";
	private $split = "^";
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->validate = new \app\admin\validate\EmailTemplateValidate();
		$this->attachment_url = $this->request->host() . config("email_url");
		$this->attachment_path = config("email_attachments");
	}
	public function sendEmail1()
	{
		$emailObject = new \app\common\logic\Email();
		$emailObject->is_admin = true;
		$params = $this->request->param();
		$email = $params["email"];
		$result = $emailObject->batchSendEmail($email);
		return jsonrule($result);
	}
	public function sendEmail()
	{
		$modules = ["admin"];
		$i = 0;
		foreach ($modules as $module) {
			$all_controller = $this->getController($module);
			foreach ($all_controller as $controller) {
				$all_action = $this->getAction($module, $controller);
				foreach ($all_action as $action) {
					$controller = str_replace("Controller", "", $controller);
					$data[$i]["module"] = $module;
					$data[$i]["controller"] = $controller;
					$data[$i]["action"] = $action;
					if (!empty($module) && !empty($controller) && !empty($action)) {
						$rule_name = "app\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
						$rule = db("auth_rule")->where("lower(name)=\"" . strtolower($rule_name) . "\"")->find();
						if (empty($rule)) {
							$idata = [];
							$idata["name"] = $rule_name;
							$idata["status"] = 1;
							$idata["app"] = "admin";
							$idata["type"] = "admin_url";
							db("auth_rule")->insert($idata);
						}
					}
					$i++;
				}
			}
		}
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	private function getController($module)
	{
		if (empty($module)) {
			return null;
		}
		$module_path = APP_PATH . "/" . $module . "/controller/";
		if (!is_dir($module_path)) {
			return null;
		}
		$module_path .= "/*.php";
		$ary_files = glob($module_path);
		foreach ($ary_files as $file) {
			if (is_dir($file)) {
				continue;
			} else {
				$files[] = basename($file, ".php");
			}
		}
		return $files;
	}
	protected function getAction($module, $controller)
	{
		if (empty($controller)) {
			return null;
		}
		$customer_functions = [];
		$file = APP_PATH . $module . "/controller/" . $controller . ".php";
		if (file_exists($file)) {
			$content = file_get_contents($file);
			preg_match_all("/.*?public.*?function(.*?)\\(.*?\\)/i", $content, $matches);
			$functions = $matches[1];
			$inherents_functions = ["_initialize", "__construct", "getActionName", "isAjax", "display", "show", "fetch", "buildHtml", "assign", "__set", "get", "__get", "__isset", "__call", "error", "success", "ajaxReturn", "redirect", "__destruct", "_empty"];
			foreach ($functions as $func) {
				$func = trim($func);
				if (!in_array($func, $inherents_functions)) {
					$customer_functions[] = $func;
				}
			}
			return $customer_functions;
		} else {
			return false;
		}
		return null;
	}
	/**
	 * @title 邮件模板列表
	 * @description 接口说明:邮件模板列表
	 * @param .name:page type:int require:0 default:1 other: desc:第几页
	 * @param .name:limit type:int require:0 default:1 other: desc:每页几条
	 * @param .name:keyword type:int require:0 default:1 other: desc:关键字搜索
	 * @return .total:总数
	 * @return .totalPage:总页数
	 * @return .email_list:邮件列表信息(按type分组显示)
	 * @return .email_list.type:类型
	 * @return .email_list.name:名称
	 * @return .email_list.disabled:0显示默认，1隐藏
	 * @return .email_list.custom:0系统邮件默认，1自定义
	 * @author wyh
	 * @url /admin/email_template/email_list
	 * @method GET
	 */
	public function emailList()
	{
		$params = $this->request->param();
		$order = isset($params["order"][0]) ? trim($params["order"]) : "id";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$page = isset($params["page"]) && !empty($params["page"]) ? intval($params["page"]) : config("page");
		$total = \think\Db::name("email_templates")->where(function (\think\db\Query $query) {
			$data = $this->request->param();
			if (!empty($data["keyword"])) {
				$keyword = $data["keyword"];
				$query->where("name", "like", "%{$keyword}%");
			}
		})->where("language", "")->count();
		$results = \think\Db::name("email_templates")->field("id,type,disabled,name,custom")->where(function (\think\db\Query $query) {
			$data = $this->request->param();
			if (!empty($data["keyword"])) {
				$keyword = $data["keyword"];
				$query->where("name", "like", "%{$keyword}%");
			}
		})->where("language", "")->order($order, $sort)->select();
		foreach ($results as $key => $value) {
			$elist[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		$arr = [];
		foreach ($elist as $k => $v) {
			if (isset($arr[config("email_template_type")[$v["type"]]])) {
				array_push($arr[config("email_template_type")[$v["type"]]], $v);
			} else {
				$arr[config("email_template_type")[$v["type"]]][0] = $v;
			}
		}
		$pluginModel = new \app\admin\model\PluginModel();
		$plugins = $pluginModel->getList("mail");
		$pluginsFilter = [];
		foreach ($plugins as $k => $v) {
			$pluginsnOne = [];
			if ($v["status"] == 1) {
				$pluginsnOne["label"] = strtolower($v["name"]);
				$pluginsnOne["value"] = $v["title"];
				$pluginsFilter[] = $pluginsnOne;
			}
		}
		return jsonrule(["status" => 200, "msg" => "请求成功", "total" => $total, "email_list" => $arr, "email_operator" => configuration("email_operator"), "mail" => $pluginsFilter]);
	}
	/**
	 * @title 邮件默认接口修改
	 * @description 接口说明:邮件默认接口修改
	 * @param .name:email_operator type:string require:1 default:'' other: desc:邮件value 值
	 * @author wyh
	 * @url /admin/email_template/operator_switch
	 * @method post
	 */
	public function emailOperatorSwitch()
	{
		$param = $this->request->param();
		updateConfiguration("email_operator", $param["email_operator"] ? strtolower($param["email_operator"]) : "");
		return jsonrule(["status" => 200, "msg" => "修改成功"]);
	}
	/**
	 * @title 创建邮件模板页面
	 * @description 接口说明:创建邮件模板页面
	 * @return .type:下拉框：general(普通),product(产品/服务),invoice(账单),support(工单),notification(Notification)
	 * @author wyh
	 * @url /admin/email_template/create_template
	 * @method GET
	 */
	public function createTemplate()
	{
		$type = config("email_template_type");
		array_shift($type);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "type" => $type]);
	}
	/**
	 * @title 创建邮件模板提交()
	 * @description 接口说明:创建邮件模板提交
	 * @param .name:type type:string require:1 default:1 other: desc:邮件模板类型：下拉框：general(普通),product(产品/服务),invoice(账单),support(工单),notification(Notification)
	 * @param .name:name type:string require:1 default:1 other: desc:邮件识别名称
	 * @return .id:邮件模板ID
	 * @author wyh
	 * @url /admin/email_template/create_template_post
	 * @method POST
	 */
	public function createTemplatePost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$type = htmlspecialchars(trim(strtolower($params["type"])));
			$name = htmlspecialchars(trim($params["name"]));
			if (!$this->validate->scene("email")->check($params)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$isname = \think\Db::name("email_templates")->where("name", $name)->find();
			if (!empty($isname)) {
				return jsonrule(["status" => 400, "msg" => lang("EMAIL_TEMPLATE_NAME_MUST_UNIQUE")]);
			}
			$new = [];
			\think\Db::startTrans();
			try {
				$newtemplate["type"] = $type;
				$newtemplate["name"] = $name;
				$newtemplate["custom"] = 1;
				$newtemplate["create_time"] = time();
				$newtemplate["language"] = "";
				$templateid = \think\Db::name("email_templates")->insertGetId($newtemplate);
				$new[0]["language"] = "";
				$new[0]["id"] = $templateid;
				$allowedlanguage = get_language_list();
				$langs = \think\Db::name("email_templates")->field("language")->group("language")->select();
				if (!empty($langs)) {
					foreach ($langs as $key => $lang) {
						if (in_array($lang["language"], $allowedlanguage)) {
							$newtemplate["language"] = $lang["language"];
							$newtemplateid = \think\Db::name("email_templates")->insertGetId($newtemplate);
							$new[$key]["language"] = $newtemplate["language"];
							$new[$key]["id"] = $newtemplateid;
							active_log(sprintf($this->lang["EmailTem_admin_createTemplatePost"], $newtemplateid));
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL") . $e->getMessage()]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "id" => $templateid, "new" => $new, "send" => configuration("company_name"), "send_email" => configuration("company_email")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 管理邮件模板语言页面
	 * @description 接口说明:添加语言，则复制所有模板并用此语言；禁用语言，则删除所有此语言的模板
	 * @return .langs:语言列表
	 * @return .lang_used:被使用的语言
	 * @author wyh
	 * @url /admin/email_template/manage_language
	 * @method GET
	 */
	public function manageLanguages()
	{
		$allowedlanguage = $this->emailTemplateAllowedLang();
		$langsused = \think\Db::name("email_templates")->field("language")->where("language", "<>", "")->group("language")->select();
		$languse = [];
		foreach ($langsused as $key => $value) {
			if (in_array($value["language"], $allowedlanguage)) {
				array_push($languse, $value["language"]);
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "langs" => $allowedlanguage, "lang_used" => $languse]);
	}
	/**
	 * @title 管理邮件模板语言页面提交
	 * @description 接口说明:添加语言，则复制所有模板并用此语言；禁用语言，则删除所有此语言的模板
	 * @param .name:language type:int require:1 default:1 other: desc:语言
	 * @author wyh
	 * @url /admin/email_template/manage_language_post
	 * @method POST
	 */
	public function manageLanguagesPost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$language = trim($params["language"]);
			if (!in_array($language, $this->emailTemplateAllowedLang())) {
				return jsonrule(["status" => 400, "msg" => lang("EMAIL_TEMPLATE_LANG_NOT_ALLOW")]);
			}
			\think\Db::startTrans();
			try {
				$defaults = \think\Db::name("email_templates")->where("language", "")->select();
				foreach ($defaults as $default) {
					$name = $default["name"];
					$existlang = \think\Db::name("email_templates")->where("name", $name)->where("language", $language)->find();
					if (empty($existlang)) {
						unset($default["id"]);
						$default["language"] = $language;
						\think\Db::name("email_templates")->insertGetId($default);
					}
				}
				\think\Db::commit();
				active_log(sprintf($this->lang["EmailTem_admin_add"], $language));
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 禁用语言
	 * @description 接口说明:禁用语言
	 * @param .name:language type:int require:1 default:1 other: desc:语言
	 * @author wyh
	 * @url /admin/email_template/disabled
	 * @method POST
	 */
	public function disabled()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$language = $params["language"];
			if (!in_array($language, $this->emailTemplateAllowedLang())) {
				return jsonrule(["status" => 400, "msg" => lang("EMAIL_TEMPLATE_LANG_NOT_ALLOW")]);
			}
			\think\Db::startTrans();
			try {
				\think\Db::name("email_templates")->where("language", $language)->delete();
				\think\Db::commit();
				active_log(sprintf($this->lang["EmailTem_admin_disabled"], $language));
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("EMAIL_TEMPLATE_LANG_DISABLE_FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("EMAIL_TEMPLATE_LANG_DISABLE_SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除模板
	 * @description 接口说明:删除模板
	 * @param .name:id type:int require:1 default:1 other: desc:模板ID
	 * @author wyh
	 * @url /admin/email_template/delete_template/:id
	 * @method GET
	 */
	public function deleteTemplate()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$custom = \think\Db::name("email_templates")->field("custom")->where("id", $id)->find();
		if ($custom["custom"] != "0") {
			$attach = \think\Db::name("email_templates")->field("attachments")->where("id", $id)->find();
			if (!empty($attach)) {
				$attachments = explode(",", $attach["attachments"]);
				foreach ($attachments as $attachment) {
					unlink($this->getattachments . $attachment);
				}
			}
			$delete = \think\Db::name("email_templates")->where("id", $id)->delete();
			if ($delete) {
				active_log(sprintf($this->lang["EmailTem_admin_delete"], $id));
				return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("SYSTEM_EMAIL_TEMPLATE_CAN_NOT_DELETE")]);
	}
	/**
	 * @title 编辑邮件模板页面
	 * @description 接口说明:编辑邮件模板页面
	 * @param .name:id type:int require:1 default:1 other: desc:邮件模板ID
	 * @return .emailtemplate:邮件模板信息
	 * @return .emailtemplate.type:邮件模板类型
	 * @return .emailtemplate.name:邮件模板名称
	 * @return .emailtemplate.subject:邮件模板主题
	 * @return .emailtemplate.message:邮件模板消息
	 * @return .emailtemplate.attachments:附件，返回的数组 "attachments": ["099072^20191105184745.csv","953393^test1.jpeg"],
	 * @return .emailtemplate.fromname:发件人
	 * @return .emailtemplate.fromemail:发件人邮箱
	 * @return .emailtemplate.disabled:0默认显示，1隐藏
	 * @return .emailtemplate.custom:0系统默认，1自定义
	 * @return .emailtemplate.language:邮件模板信息
	 * @return .emailtemplate.copyto:副本
	 * @return .emailtemplate.blind_copy_to:绑定发送人邮件
	 * @return .emailtemplate.plaintext:0默认，1纯文本
	 * @return .emailtemplate.child:不同语言模板的主题和内容
	 * @return .base_args:基础参数
	 * @return .client_args:客户相关参数
	 * @return .combine:模板类型相关参数
	 * @author wyh
	 * @url /admin/email_template/edit_template/:id
	 * @method GET
	 */
	public function editTemplate()
	{
		$params = $this->request->param();
		$templateid = intval($params["id"]);
		if (!$templateid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$emailtemplate = \think\Db::name("email_templates")->field("id,type,name,attachments,fromname,fromemail,disabled,custom,copyto,blind_copy_to,plaintext")->where("id", $templateid)->find();
		$emailtemplate["fromemail"] = !empty($emailtemplate["fromemail"]) ? $emailtemplate["fromemail"] : configuration("company_email");
		$emailtemplate["fromname"] = !empty($emailtemplate["fromname"]) ? $emailtemplate["fromname"] : configuration("company_name");
		$email = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $emailtemplate);
		$name = $email["name"];
		$difflangs = \think\Db::name("email_templates")->field("id,subject,message,language")->where("name", $name)->select();
		foreach ($difflangs as $key => $value) {
			$diff[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		$email["child"] = $diff;
		if (isset($email["attachments"][0])) {
			$url = $this->attachment_url;
			$email["attachments"] = array_map(function ($v) use($url) {
				return ["savename" => $v, "url" => $url . $v];
			}, explode(",", $email["attachments"]));
		} else {
			$email["attachments"] = [];
		}
		$type = $emailtemplate["type"];
		$emailarg = new \app\common\logic\Email();
		$emailarg->is_admin = true;
		$argsbase = $emailarg->getBaseArg();
		if ($type == "product" || $type == "invoice") {
			$clientarg = $emailarg->getReplaceArg("general");
		} else {
			$clientarg = [];
		}
		$argsarray = $emailarg->getReplaceArg($type);
		return jsonrule(["status" => 200, "msg" => "请求成功", "emailtemplate" => $email, "base_args" => $argsbase, "client_args" => $clientarg, "combine" => $argsarray]);
	}
	/**
	 * @title 编辑邮件模板页面提交
	 * @description 接口说明:编辑邮件模板页面提交
	 * @param .name:id type:int require:1 default:1 other: desc:邮件模板ID
	 * @param .name:fromname type:string require:1 default:1 other: desc:
	 * @param .name:fromemail type:string require:1 default:1 other: desc:
	 * @param .name:copyto type:string require:0 default:1 other: desc:
	 * @param .name:blind_copy_to type:string require:0 default:1 other: desc:
	 * @param .name:plaintext type:int require:1 default:1 other: desc:
	 * @param .name:disabled type:int require:1 default:1 other: desc:
	 * @param .name:oldattachments[] type:string require:0 default:1 other: desc:旧的附件名称,参数形式oldattachments[0],oldattachments[1]
	 * @param .name:file[] type:string require:1 default:0 other: desc:新上传的附件，有，则显示
	 * @param .name:subject[id] type:string require:1 default:1 other: desc:其中id是difflangs中的id，同一name不同语言模板的id
	 * @param .name:message[id] type:string require:1 default:1 other: desc:必传参数
	 * @author wyh
	 * @url /admin/email_template/edit_template_post
	 * @method POST
	 */
	public function editTemplatePost()
	{
		if ($this->request->isPost()) {
			$dec = "";
			$params = $this->request->param();
			$id = isset($params["id"]) && !empty($params["id"]) ? intval($params["id"]) : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if (!$this->validate->scene("edit_email")->check($params)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			unset($params["id"]);
			$fromname = isset($params["fromname"]) ? $params["fromname"] : "";
			$fromemail = isset($params["fromemail"]) ? $params["fromemail"] : "";
			$copyto = isset($params["copyto"]) ? $params["copyto"] : "";
			$blindcopyto = isset($params["blind_copy_to"]) ? $params["blind_copy_to"] : "";
			$plaintext = isset($params["plaintext"]) ? \boolval($params["plaintext"]) : 0;
			$disabled = isset($params["disabled"]) ? \boolval($params["disabled"]) : 0;
			$tmp = \think\Db::name("email_templates")->where("id", $id)->find();
			$attachaddres = explode(",", $tmp["attachments"]);
			if (isset($params["file"][0][0])) {
				$rmfile = array_diff($attachaddres, $params["attachments"]);
				if (isset($rmfile[0])) {
					foreach ($rmfile as $v) {
						unlink($this->attachment_path . $v);
					}
				}
				$upload = new \app\common\logic\Upload();
				$tmp = $upload->moveTo($params["file"], $this->attachment_path);
				if (isset($tmp["error"])) {
					return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
				}
				if (is_array($tmp)) {
					$newtemplate["attachments"] = implode(",", $tmp);
				} else {
					$newtemplate["attachments"] = $tmp;
				}
			} else {
				if (isset($attachaddres[0][0])) {
					foreach ($attachaddres as $v) {
						unlink($this->attachment_path . $v);
					}
					$newtemplate["attachments"] = "";
				}
			}
			$newtemplate["fromname"] = $fromname;
			if ($tmp["fromname"] != $fromname) {
				$dec .= " - 发送者名称" . $tmp["fromname"] . "改为" . $fromname;
			}
			$newtemplate["fromemail"] = $fromemail;
			if ($tmp["fromemail"] != $fromemail) {
				$dec .= " - 发送者邮件" . $tmp["fromemail"] . "改为" . $fromemail;
			}
			$newtemplate["copyto"] = $copyto;
			if ($tmp["copyto"] != $copyto) {
				$dec .= " - 副本" . $tmp["copyto"] . "改为" . $copyto;
			}
			$newtemplate["blind_copy_to"] = $blindcopyto;
			if ($tmp["blind_copy_to"] != $blindcopyto) {
				$dec .= " - 绑定发送人邮箱" . $tmp["blind_copy_to"] . "改为" . $blindcopyto;
			}
			$newtemplate["plaintext"] = $plaintext;
			if ($tmp["plaintext"] != $plaintext) {
				$dec .= " - 附件" . $tmp["plaintext"] . "改为" . $plaintext;
			}
			$newtemplate["disabled"] = $disabled;
			if ($tmp["disabled"] != $disabled) {
				if ($disabled == 1) {
					$dec .= " - 未禁用";
				} else {
					$dec .= " - 禁用";
				}
			}
			$newtemplate["update_time"] = time();
			\think\Db::startTrans();
			try {
				$subjects = $params["subject"];
				$messages = $params["message"];
				foreach ($subjects as $key => $value) {
					$newtemplate["subject"] = isset($value) ? $value : "";
					$newtemplate["message"] = isset($messages[$key]) ? $messages[$key] : "";
					$newtemplate = array_map("trim", $newtemplate);
					\think\Db::name("email_templates")->where("id", $key)->update($newtemplate);
				}
				\think\Db::commit();
				active_log(sprintf($this->lang["EmailTem_admin_edit"], $id, $dec));
				unset($dec);
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("EDIT FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	protected function getOriginalName($attachaddress)
	{
		if (is_string($attachaddress)) {
			$attachments = explode(",", $attachaddress);
			$attachmentsfilter = [];
			foreach ($attachments as $key => $attachment) {
				$currentname = explode($this->split, $attachment)[0];
				$originalname = explode($this->split, $attachment)[1];
				$attachmentsfilter[$currentname] = $originalname;
			}
			return $attachmentsfilter;
		} else {
			return false;
		}
	}
	protected function emailTemplateAllowedLang()
	{
		return array_values(get_language_list());
	}
	protected function uploadHandle($files)
	{
		$re = [];
		foreach ($files as $file) {
			$data = ["file" => $file];
			if (!$this->validate->scene("upload")->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $this->validate->getError();
				if (!empty($re["savename"])) {
					$addresses = explode(",", $re["savename"]);
					foreach ($addresses as $address) {
						$path = $this->imagesave . $address;
						if (file_exists($path)) {
							unset($info);
							unlink($path);
							unset($re["savename"]);
						}
					}
				}
				return $re;
			}
			$originalName = $file->getInfo("name");
			$info = $file->rule("uniqid")->move($this->imagesave, md5(uniqid()) . time() . $this->split . $originalName);
			if ($info) {
				if (!isset($savename)) {
					$savename = $info->getSaveName();
				} else {
					$savename = $savename . "," . $info->getSaveName();
				}
				$re["status"] = 200;
				$re["savename"] = $savename;
			} else {
				$re["status"] = 400;
				$re["msg"] = $file->getError();
			}
		}
		return $re;
	}
	/**
	 * @title 禁用/启用模板
	 * @description 接口说明:禁用/启用模板
	 * @param .name:id type:int require:1 default:1 other: desc:模板id
	 * @param .name:disabled type:int require:1 default:1 other: desc:0禁用,1启用
	 * @author wyh
	 * @time 2020-12-14
	 * @url /admin/email_template/disabled_template
	 * @method POST
	 */
	public function disabledTemplate()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$id = intval($params["id"]);
			$tmp = \think\Db::name("email_templates")->where("id", $id)->find();
			if (empty($tmp)) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if (!in_array($params["disabled"], [0, 1])) {
				$params["disabled"] = 0;
			}
			\think\Db::name("email_templates")->where("id", $id)->update(["disabled" => intval($params["disabled"]), "update_time" => time()]);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
}