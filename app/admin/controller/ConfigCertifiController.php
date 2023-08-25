<?php

namespace app\admin\controller;

/**
 * @title 实名认证配置
 * @description 实名认证配置
 */
class ConfigCertifiController extends AdminBaseController
{
	protected $type;
	protected $three;
	protected $alipay_biz_code;
	/**
	 * @title 实名认证设置
	 * @description 接口说明:实名认证设置
	 * @return .certifi_is_stop:未实名暂停产品1=开0=关
	 * @return .certifi_stop_day:未实名暂停产品时间/天
	 * @return .certifi_is_upload:是否上传身份证1=是0=否
	 * @return .certifi_open:是否开启身份认证  1=开启0=关闭
	 * @return .certifi_isbindphone:认证手机号是否必须与绑定一致
	 * @return .certifi_realname:是否自动更新姓名
	 * @return .certifi_select:选中的认证类型
	 * @return .certifi_select_all:所有认证类型
	 * 时间 2021/4/13
	 * @author wyh
	 * @url /admin/config_certifi/setting
	 * @method GET
	 */
	public function setting()
	{
		$config = ["certifi_is_upload", "certifi_is_stop", "certifi_stop_day", "certifi_open", "certifi_select", "certifi_realname", "certifi_isbindphone", "artificial_auto_send_msg", "certifi_business_btn", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path"];
		$data = configuration($config);
		if (empty($data["certifi_select"])) {
			$data["certifi_select"] = "artificial";
		} else {
			$certifi_select = explode(",", $data["certifi_select"]);
			foreach ($certifi_select as &$value) {
				if ($value == "phonethree") {
					$value = "Phonethree";
				} elseif ($value == "three") {
					$value = "Threehc";
				} elseif ($value == "ali") {
					$value = "Ali";
				}
			}
			$data["certifi_select"] = implode(",", $certifi_select);
		}
		$certifi_plugin = array_column(getPluginsList(), "title", "name") ?: [];
		$data["certifi_select_all"] = array_merge(["artificial" => "人工审核"], $certifi_plugin);
		$data["certifi_business_author_path_url"] = $data["certifi_business_author_path"] ? config("author_attachments_url") . $data["certifi_business_author_path"] : "";
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data, "edition" => getEdition()]);
	}
	/**
	 * 时间 2021/4/13
	 * @title 实名认证设置提交
	 * @desc 实名认证设置提交
	 * @url /admin/config_certifi/settingPost
	 * @method  post
	 * @param   .name:certifi_realname type:string require:1 default: other: desc:是否同步姓名
	 * @param   .name:certifi_is_upload type:int require:1 default: other: desc:是否上传图片1=上传0=不上传
	 * @param   .name:certifi_is_stop type:int require:1 default: other: desc:未实名暂停产品1=开0=关
	 * @param   .name:certifi_stop_day type:int require:1 default: other: desc:未实名暂停产品/天
	 * @param   .name:certifi_open type:int require:1 default: other: desc:是否开启身份认证/天 1=开启0=关闭
	 * @param   .name:certifi_isbindphone type:string require:1 default: other: desc:绑定手机是否一致
	 * @param   .name:certifi_select[] type:string require:1 default: other: desc:认证类型 数组
	 * @param   .name:artificial_auto_send_msg type:int require:1 default: other: desc:人工审核自动发送短信
	 * @param   .name:certifi_business_open type:string require:1 default: other: desc:企业高级设置
	 * @param   .name:certifi_business_is_upload type:string require:1 default: other: desc:营业执照上传
	 * @param   .name:certifi_business_is_author type:string require:1 default: other: desc:授权书上传
	 * @param   .name:certifi_business_author_path type:string require:1 default: other: desc:授权书路径
	 * 时间 2021/4/13 15:42
	 * @author wyh
	 * @version v1
	 */
	public function settingPost()
	{
		$arr = ["certifi_is_upload", "certifi_is_stop", "certifi_stop_day", "certifi_open", "certifi_select", "certifi_realname", "certifi_isbindphone", "artificial_auto_send_msg", "certifi_business_btn", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path"];
		$param = $this->request->only($arr);
		if (!getEdition()) {
			if ($param["artificial_auto_send_msg"] == 1 || $param["certifi_business_open"] == 1) {
				updateConfiguration("artificial_auto_send_msg", 0);
				updateConfiguration("certifi_business_open", 0);
				updateConfiguration("certifi_business_is_upload", 0);
				updateConfiguration("certifi_business_is_author", 0);
				updateConfiguration("certifi_business_author_path", "");
				return jsonrule(["status" => 400, "msg" => "该功能仅专业版可用"]);
			}
		}
		$param["certifi_select"] = implode(",", $param["certifi_select"]);
		$tmp = configuration($arr);
		if (!getEdition()) {
			$param["artificial_auto_send_msg"] = 0;
			$param["certifi_business_open"] = 0;
			$param["certifi_business_is_upload"] = 0;
			$param["certifi_business_is_author"] = 0;
			$param["certifi_business_author_path"] = "";
		}
		if ($param["certifi_business_open"] && $param["certifi_business_is_author"] && empty($param["certifi_business_author_path"])) {
			return jsonrule(["status" => 400, "msg" => "请上传授权书模板"]);
		}
		$dec = "";
		foreach ($param as $k => $v) {
			if ($k == "certifi_is_stop") {
				if ($v == $tmp[$k]) {
					continue;
				}
				$tmp["setting"] = "未实名暂停产品";
				if ($tmp[$k] == 1) {
					$dec .= $tmp["setting"] . "由“开启”改为“关闭”，";
				} else {
					$dec .= $tmp["setting"] . "由“关闭”改为“开启”，";
				}
			}
			updateConfiguration($k, $v);
		}
		$dec && active_log_final(sprintf($this->lang["ConfigCer_admin_update"], $dec), 0, 4);
		return jsonrule(["status" => 200, "msg" => "设置成功"]);
	}
	/**
	 * 时间 2021/4/13
	 * @title 授权书下载
	 * @desc 授权书下载
	 * @url /admin/config_certifi/authorDown
	 * @method  get
	 * 时间 2021/4/13 15:42
	 * @author xue
	 * @version v1
	 */
	public function authorDown()
	{
		try {
			$auth_path = configuration("certifi_business_author_path");
			if (!$auth_path) {
				throw new \think\Exception("文件资源不存在");
			}
			return download(config("author_attachments") . $auth_path, "shouQuan");
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * 时间 2021/4/13
	 * @title 授权书删除
	 * @desc 授权书删除
	 * @url /admin/config_certifi/authorDel
	 * @method  get
	 * 时间 2021/4/13 15:42
	 * @author xue
	 * @version v1
	 */
	public function authorDel()
	{
		try {
			$auth_path = configuration("certifi_business_author_path");
			if (!$auth_path) {
				throw new \think\Exception("文件资源不存在");
			}
			unlink(config("author_attachments") . $auth_path);
			updateConfiguration("certifi_business_author_path", "");
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function initialize()
	{
		parent::initialize();
		$this->type = config("certi_type");
		$this->three = [["name" => "两要素", "value" => "two"], ["name" => "三要素", "value" => "three"], ["name" => "四要素", "value" => "four"]];
		$this->alipay_biz_code = [["name" => "快捷认证（无需识别）", "value" => "SMART_FACE"], ["name" => "人脸识别", "value" => "FACE"], ["name" => "身份证识别", "value" => "CERT_PHOTO"], ["name" => "人脸+身份证", "value" => "CERT_PHOTO_FACE"]];
	}
	/**
	 * @title 阿里认证配置数据
	 * @description 接口说明:阿里认证配置数据
	 * @return .certifi_alipay_biz_code:类型
	 * @return .certifi_alipay_public_key:公钥
	 * @return .certifi_app_id:appid
	 * @return .certifi_merchant_private_key:私钥
	 * @return .certifi_is_stop:未实名暂停产品1=开2=关
	 * @return .certifi_stop_day:未实名暂停产品时间/天
	 * @return .certifi_is_upload:是否上传身份证1=是2=否
	 * @return .certifi_open:是否开启身份认证  1=开启2=关闭
	 * @return .certifi_type:认证类型
	 * @return .certifi_phonethree_appcode:手机三要素认证appcode
	 * 时间 2020/5/15 15:42
	 * @author liyongjun
	 * @url /admin/certifi_alipay_detail
	 * @method GET
	 */
	public function detail()
	{
		$where[] = ["setting", "like", "certifi%"];
		$data = \think\Db::name("configuration")->where($where)->select()->toArray();
		if (isset($data[0])) {
			$data = array_column($data, "value", "setting");
		}
		if (empty($data["certifi_select"])) {
			if (empty($data["certifi_select"]) || ($data["certifi_select"] = null)) {
				$data["certifi_select"] = "artificial";
			} else {
				$data["certifi_select"] = configuration("certifi_type");
			}
		}
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * 时间 2020/5/15 15:42
	 * @title 获取阿里认证类型
	 * @desc 获取阿里认证类型
	 * @url /admin/certifi_alipay_biz_code
	 * @method  get
	 * @return  string name -配置名称
	 * @return  string value -配置值
	 * @author liyongjun
	 * @version v1
	 */
	public function alipay_biz_code()
	{
		return jsonrule(["status" => 200, "data" => $this->alipay_biz_code]);
	}
	/**
	 * 时间 2020/7/24 15:42
	 * @title 获取三要素认证类型
	 * @desc 获取三要素认证类型
	 * @url /admin/certifi_three_type
	 * @method  get
	 * @return  string name -配置名称
	 * @return  string value -配置值
	 * @author lgd
	 * @version v1
	 */
	public function alipay_three_type()
	{
		return jsonrule(["status" => 200, "data" => $this->three]);
	}
	/**
	 * 时间 2020/5/15 15:42
	 * @title 获取认证类型
	 * @desc 获取认证类型
	 * @url /admin/certifi_type
	 * @method  get
	 * @return  string name -配置名称
	 * @return  string value -配置值
	 * @author liyongjun
	 * @version v1
	 */
	public function type()
	{
		return jsonrule(["status" => 200, "data" => $this->type]);
	}
	/**
	 * 时间 2020/5/15 15:42
	 * @title 获取认证类型
	 * @desc 获取认证类型
	 * @url /admin/certifi_types
	 * @method  get
	 * @return  string name -配置名称
	 * @return  string value -配置值
	 * @author lgd
	 * @version v1
	 */
	public function types()
	{
		$type = $this->type;
		foreach ($this->type as $key => $value) {
			if ($type[$key]["value"] == "artificial") {
				unset($type[$key]);
			}
		}
		$type = array_merge($type);
		return jsonrule(["status" => 200, "data" => $type]);
	}
	/**
	 * 时间 2020/5/15 15:42
	 * @title 修改阿里认证数据
	 * @desc 修改阿里认证数据
	 * @url /admin/certifi_alipay
	 * @method  put
	 * @param   .name:certifi_type type:string require:1 default: other: desc:认证类型
	 * @param   .name:certifi_realname type:string require:1 default: other: desc:是否同步姓名
	 * @param   .name:certifi_appcode type:string require:1 default: other: desc:appcode
	 * @param   .name:certifi_three_type type:string require:1 default: other: desc:三要素
	 * @param   .name:certifi_alipay_biz_code type:string require:1 default: other: desc:阿里认证类型
	 * @param   .name:certifi_alipay_public_key type:string require:1 default: other: desc:阿里认证公钥
	 * @param   .name:certifi_app_id type:string require:1 default: other: desc:阿里认证appid
	 * @param   .name:certifi_merchant_private_key type:string require:1 default: other: desc:阿里认证私钥
	 * @param   .name:certifi_is_upload type:int require:1 default: other: desc:是否上传图片1=上传2=不上传
	 * @param   .name:certifi_is_stop type:int require:1 default: other: desc:未实名暂停产品1=开2=关
	 * @param   .name:certifi_stop_day type:int require:1 default: other: desc:未实名暂停产品/天
	 * @param   .name:certifi_open type:int require:1 default: other: desc:是否开启身份认证/天 1=开启2=关闭
	 * @param   .name:name type:string require:1 default: other: desc:自定义名字
	 * @param   .name:certifi_isbindphone type:string require:1 default: other: desc:绑定手机是否一致
	 * @param   .name:certifi_isrealname type:string require:1 default: other: desc:是否实名
	 * @param   .name:certifi_phonethree_appcode type:string require:1 default: other: desc:手机三要素appcode
	 * @return  string name -配置名称
	 * @return  string value -配置值
	 * @author liyongjun
	 * @version v1
	 */
	public function update()
	{
		$param = $this->request->only(["certifi_alipay_biz_code", "certifi_alipay_public_key", "certifi_app_id", "certifi_merchant_private_key", "certifi_type", "certifi_is_upload", "certifi_is_stop", "certifi_stop_day", "certifi_open", "certifi_appcode", "certifi_three_type", "certifi_select", "certifi_realname", "certifi_isbindphone", "certifi_isrealname", "name", "certifi_phonethree_appcode"]);
		$param["certifi_select"] = implode(",", $param["certifi_select"]);
		\think\Db::startTrans();
		try {
			$dec = "";
			$arr = array_column($this->type, "name", "value");
			$arr1 = array_column($this->alipay_biz_code, "name", "value");
			$arr2 = array_column($this->three, "name", "value");
			$arrs = $this->type;
			if (!empty($param["certifi_type"]) && !empty($param["name"])) {
				foreach ($arrs as $k => $v) {
					if ($v["value"] == $param["certifi_type"]) {
						$arrs[$k]["name"] = $param["name"];
					}
				}
				$param["certi_typename"] = json_encode($arrs);
			}
			foreach ($param as $k => $v) {
				$tmp = \think\Db::name("configuration")->where("setting", $k)->find();
				updateConfiguration($k, $v);
				if ($v != $tmp["value"]) {
					if ($v == "ali") {
						$tmp["setting"] = "认证接口";
						$tmp["value"] = $arr[$tmp["value"]];
						$v = $arr[$v];
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == "certifi_alipay_biz_code") {
						$tmp["setting"] = "认证方式";
						$tmp["value"] = $arr1[$tmp["value"]];
						$v = $arr1[$v];
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == "certifi_app_id") {
						$tmp["setting"] = "APP ID";
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == "certifi_alipay_public_key") {
						$tmp["setting"] = "支付宝公钥";
						$dec .= $tmp["setting"] . "有修改，";
					} elseif ($k == " certifi_merchant_private_key") {
						$tmp["setting"] = "商户私钥";
						$dec .= $tmp["setting"] . "有修改，";
					} elseif ($k == " certifi_appcode") {
						$tmp["setting"] = "三要素code";
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == " certifi_three_type") {
						$tmp["setting"] = "三要素类型";
						$dec .= $arr2[$tmp["setting"]] . "由“" . $tmp["value"] . "”改为“" . $arr2[$v] . "”，";
					} elseif ($k == " certifi_realname") {
						$tmp["setting"] = "同步名字";
						$dec .= $arr2[$tmp["setting"]] . "由“" . $tmp["value"] . "”改为“" . $arr2[$v] . "”，";
					} elseif ($k == "certifi_is_stop") {
						$tmp["setting"] = "未实名暂停产品";
						if ($tmp["value"] == 1) {
							$dec .= $tmp["setting"] . "由“开启”改为“关闭”，";
						} else {
							$dec .= $tmp["setting"] . "由“关闭”改为“开启”，";
						}
					} elseif ($k == "certifi_stop_day") {
						$tmp["setting"] = "暂停期限";
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == "certifi_isbindphone") {
						$tmp["setting"] = "绑定手机是否一致";
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} elseif ($k == "certifi_isrealname") {
						$tmp["setting"] = "是否实名";
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					} else {
						$dec .= $tmp["setting"] . "由“" . $tmp["value"] . "”改为“" . $v . "”，";
					}
				}
			}
			if (empty($dec)) {
				$dec .= "没有任何修改";
			}
			active_log_final(sprintf($this->lang["ConfigCer_admin_update"], $dec), 0, 4);
			unset($dec);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
		return jsonrule(["status" => 200, "data" => $param, "msg" => "设置成功"]);
	}
}