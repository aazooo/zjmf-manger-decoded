<?php

namespace app\admin\controller;

/**
 * @title 后台设置
 * @description 接口说明
 */
class SetController extends AdminBaseController
{
	/**
	 * 网站信息
	 * @adminMenu(
	 *     'name'   => '网站信息',
	 *     'parent' => 'default',
	 *     'display'=> true,
	 *     'hasView'=> true,
	 *     'order'  => 0,
	 *     'icon'   => '',
	 *     'remark' => '网站信息',
	 *     'param'  => ''
	 * )
	 */
	public function site()
	{
		$content = hook_one("admin_setting_site_view");
		if (!empty($content)) {
			return $content;
		}
		$noNeedDirs = [".", "..", ".svn", "fonts"];
		$adminThemesDir = WEB_ROOT . config("template.cmf_admin_theme_path") . config("template.cmf_admin_default_theme") . "/public/assets/themes/";
		$adminStyles = cmf_scan_dir($adminThemesDir . "*", GLOB_ONLYDIR);
		$adminStyles = array_diff($adminStyles, $noNeedDirs);
		$cdnSettings = cmf_get_option("cdn_settings");
		$cmfSettings = cmf_get_option("cmf_settings");
		$adminSettings = cmf_get_option("admin_settings");
		$adminThemes = [];
		$themes = cmf_scan_dir(WEB_ROOT . config("template.cmf_admin_theme_path") . "/*", GLOB_ONLYDIR);
		foreach ($themes as $theme) {
			if (strpos($theme, "admin_") === 0) {
				array_push($adminThemes, $theme);
			}
		}
		if (APP_DEBUG && false) {
			$apps = cmf_scan_dir(APP_PATH . "*", GLOB_ONLYDIR);
			$apps = array_diff($apps, $noNeedDirs);
			$this->assign("apps", $apps);
		}
		$this->assign("site_info", cmf_get_option("site_info"));
		$this->assign("admin_styles", $adminStyles);
		$this->assign("templates", []);
		$this->assign("admin_themes", $adminThemes);
		$this->assign("cdn_settings", $cdnSettings);
		$this->assign("admin_settings", $adminSettings);
		$this->assign("cmf_settings", $cmfSettings);
		return $this->fetch();
	}
	/**
	 * 网站信息设置提交
	 * @adminMenu(
	 *     'name'   => '网站信息设置提交',
	 *     'parent' => 'site',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '网站信息设置提交',
	 *     'param'  => ''
	 * )
	 */
	public function sitePost()
	{
		if ($this->request->isPost()) {
			$result = $this->validate($this->request->param(), "SettingSite");
			if ($result !== true) {
				$this->error($result);
			}
			$options = $this->request->param("options/a");
			cmf_set_option("site_info", $options);
			$cmfSettings = $this->request->param("cmf_settings/a");
			$bannedUsernames = preg_replace("/[^0-9A-Za-z_\\x{4e00}-\\x{9fa5}-]/u", ",", $cmfSettings["banned_usernames"]);
			$cmfSettings["banned_usernames"] = $bannedUsernames;
			cmf_set_option("cmf_settings", $cmfSettings);
			$cdnSettings = $this->request->param("cdn_settings/a");
			cmf_set_option("cdn_settings", $cdnSettings);
			$adminSettings = $this->request->param("admin_settings/a");
			$routeModel = new \app\admin\model\RouteModel();
			if (!empty($adminSettings["admin_password"])) {
				$routeModel->setRoute($adminSettings["admin_password"] . "\$", "admin/Index/index", [], 2, 5000);
			} else {
				$routeModel->deleteRoute("admin/Index/index", []);
			}
			$routeModel->getRoutes(true);
			if (!empty($adminSettings["admin_theme"])) {
				$result = cmf_set_dynamic_config(["template" => ["cmf_admin_default_theme" => $adminSettings["admin_theme"]]]);
				if ($result === false) {
					$this->error("配置写入失败!");
				}
			}
			cmf_set_option("admin_settings", $adminSettings);
			$this->success("保存成功！", "");
		}
	}
	/**
	 * 密码修改
	 * @adminMenu(
	 *     'name'   => '密码修改',
	 *     'parent' => 'default',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '密码修改',
	 *     'param'  => ''
	 * )
	 */
	public function password()
	{
		return $this->fetch();
	}
	/**
	 * @title 管理员密码修改
	 * @description 接口说明:管理员密码修改
	 * @author wyh
	 * @url /admin/password_reset
	 * @method POST
	 * @param .name:old_password type:string require:1 default:1 other: desc:原始密码
	 * @param .name:password type:string require:1 default:1 other: desc:新密码
	 * @param .name:re_password type:string require:1 default:1 other: desc:重复新密码
	 */
	public function passwordPost()
	{
		if ($this->request->isPost()) {
			$data = $this->request->param();
			if (empty($data["old_password"])) {
				return jsonrule(["status" => 406, "msg" => "原始密码不能为空"]);
			}
			if (empty($data["password"])) {
				return jsonrule(["status" => 406, "msg" => "新密码不能为空"]);
			}
			$userId = cmf_get_current_admin_id();
			$admin = \think\Db::name("user")->where("id", $userId)->find();
			$oldPassword = $data["old_password"];
			$password = $data["password"];
			$rePassword = $data["re_password"];
			if (cmf_compare_password($oldPassword, $admin["user_pass"])) {
				if ($password == $rePassword) {
					if (cmf_compare_password($password, $admin["user_pass"])) {
						return jsonrule(["status" => 406, "msg" => "新密码不能和原始密码相同！"]);
					} else {
						\think\Db::name("user")->where("id", $userId)->update(["user_pass" => cmf_password($password)]);
						return jsonrule(["status" => 200, "msg" => "密码修改成功！"]);
					}
				} else {
					return jsonrule(["status" => 401, "msg" => "两次密码不同！"]);
				}
			} else {
				return jsonrule(["status" => 406, "msg" => "原始密码不正确"]);
			}
		}
		return jsonrule(["status" => 400, "msg" => "请求错误！"]);
	}
	/**
	 * 上传限制设置界面
	 * @adminMenu(
	 *     'name'   => '上传设置',
	 *     'parent' => 'default',
	 *     'display'=> true,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '上传设置',
	 *     'param'  => ''
	 * )
	 */
	public function upload()
	{
		$uploadSetting = cmf_get_upload_setting();
		$this->assign("upload_setting", $uploadSetting);
		return $this->fetch();
	}
	/**
	 * 上传限制设置界面提交
	 * @adminMenu(
	 *     'name'   => '上传设置提交',
	 *     'parent' => 'upload',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '上传设置提交',
	 *     'param'  => ''
	 * )
	 */
	public function uploadPost()
	{
		if ($this->request->isPost()) {
			$uploadSetting = $this->request->post();
			cmf_set_option("upload_setting", $uploadSetting);
			$this->success("保存成功！");
		}
	}
	/**
	 * @title 清除缓存
	 * @description 接口说明:清除缓存（包括前后台）
	 * @author wyh
	 * @url /admin/clear_cache
	 * @method GET
	 */
	public function clearCache()
	{
		cmf_clear_cache();
		return jsonrule(["status" => 200, "msg" => "清除缓存成功！"]);
	}
	/**
	 * @title 用户自定义字段配置页面
	 * @description 接口说明:用户自定义字段配置页面
	 * @author 萧十一郎
	 * @url /admin/custom_fields
	 * @method GET
	 * @return customfields:自定义字段数据@
	 * @customfields  id:自定义字段id
	 * @customfields  fieldname:自定义字段标题
	 * @customfields  fieldtype:自定义字段类型（text，link，password，dropdown，tickbox，textarea）
	 * @customfields  description:自定义字段描述
	 * @customfields  fieldoptions:自定义字段选项，为dropdown时使用
	 * @customfields  regexpr:验证数据
	 * @customfields  adminonly:是否管理员可见
	 * @customfields  required:是否必填
	 * @customfields  showorder:是否在订单上显示
	 * @customfields  showinvoice:是否在账单上显示
	 * @customfields  sortorder:排序字段
	 * @customfields  showdetail:是否在产品内页显示
	 * @return type_list:类型列表@
	 */
	public function getCustomFields()
	{
		$customfields = \think\Db::name("customfields")->where("type", "client")->order("sortorder asc")->select()->toArray();
		$returndata = [];
		$returndata["type_list"] = config("customfields");
		$returndata["customfields"] = $customfields;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * @title 保存用户自定义字段
	 * @description 接口说明:保存用户自定义字段
	 * @author 萧十一郎
	 * @url /admin/custom_fields
	 * @method POST
	 * @param name:addfieldname type:string require:0 default: other: desc:添加的字段名称
	 * @param name:addfieldtype type:string require:0 default:dropdown other: desc:添加的字段类型
	 * @param name:addcustomfielddesc type:string require:0 default: other: desc:添加的字段描述
	 * @param name:addfieldoptions type:string require:0 default: other: desc:添加字段的选项
	 * @param name:addregexpr type:string require:0 default: other: desc:该字段的正则匹配
	 * @param name:addadminonly type:string require:0 default: other: desc:选中为仅管理员可见
	 * @param name:addrequired type:string require:0 default: other: desc:该字段必填，值为on时
	 * @param name:addshoworder type:string require:0 default: other: desc:在订单上显示，值为on时
	 * @param name:addshowinvoice type:string require:0 default: other: desc:在账单上显示，值为on时
	 * @param name:addsortorder type:int require:0 default: other: desc:排序数值
	 * @param name:customfieldname type:array require:0 default: other: desc:修改的字段名称  eg. customfieldname['89'] = "新字段名"
	 * @param name:customfieldtype type:array require:0 default:dropdown other: desc:修改的字段类型
	 * @param name:customfielddesc type:array require:0 default: other: desc:修改的字段描述
	 * @param name:customfieldoptions type:array require:0 default: other: desc:修改的字段的选项
	 * @param name:customfieldregexpr type:array require:0 default: other: desc:修改的字段的正则匹配
	 * @param name:customadminonly type:array require:0 default: other: desc:修改选中为仅管理员可见
	 * @param name:customrequired type:array require:0 default: other: desc:修改该字段必填，值为on时
	 * @param name:customshoworder type:array require:0 default: other: desc:修改在订单上显示，值为on时
	 * @param name:customshowinvoice type:array require:0 default: other: desc:修改在账单上显示，值为on时
	 * @param name:customsortorder type:array require:0 default: other: desc:修改排序数值
	 * @param name:configoptionlinks type:array require:0 default: other: desc:关联的可配置选项，一维数组，值为int型
	 * @param name:upgradepackages type:array require:0 default: other: desc:可升级更改产品的数组，一维数组，值为int型
	 */
	public function postCustomFields(\think\Request $request)
	{
		$param = $request->param();
		$custom_logic = new \app\common\logic\Customfields();
		$re = $custom_logic->add(0, "client", $param);
		if ($re["status"] == "error") {
			return jsonrule(["status" => 406, "msg" => $re["msg"]]);
		}
		if (!empty($re["dec"])) {
			active_log(sprintf($this->lang["Set_admin_postCustomFields_add"], $re["dec"]));
		}
		$re = $custom_logic->edit(0, "client", $param);
		if ($re["status"] == "error") {
			return jsonrule(["status" => 406, "msg" => $re["msg"]]);
		}
		if (!empty($re["dec"])) {
			active_log(sprintf($this->lang["Set_admin_postCustomFields_edit"], $re["dec"]));
		}
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 删除用户自定义字段配置
	 * @description 接口说明:删除用户自定义字段配置
	 * @author 萧十一郎
	 * @url /admin/del_custom_fields
	 * @method post
	 * @param name:id type:int require:1 default: other: desc:删除的自定义字段id
	 * @param name:type type:string require:1 default: other: desc:删除的自定义字段类型(client:用户，product：产品，ticket:工单，)
	 */
	public function delCustomFields(\think\Request $request)
	{
		$param = $request->param();
		$id = $param["id"];
		$type = $param["type"];
		if (empty($id) || empty($type)) {
			return jsonrule(["status" => 406, "msg" => lang("ID_OR_TYPE_CAN_NOT_EMPTY")]);
		}
		$custom_data = \think\Db::name("customfields")->where("type", $type)->where("id", $id)->find();
		if (empty($custom_data)) {
			return jsonrule(["status" => 406, "msg" => lang("UN_FIND_CUSTOM_FIELDS")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("customfields")->where("id", $id)->delete();
			\think\Db::name("customfieldsvalues")->where("fieldid", $id)->delete();
			\think\Db::commit();
			active_log(sprintf($this->lang["Set_admin_postCustomFields_delete"], $custom_data["fieldname"], $id));
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => lang("DELETE FAIL")]);
		}
	}
	/**
	 * @title 获取备份配置
	 * @description 接口说明:获取备份配置
	 * @author 萧十一郎
	 * @url /admin/database_backup
	 * @method get
	 * @return daily_email_backup_status:是否启用邮件备份
	 * @return daily_email_backup:邮件备份地址
	 * @return daily_ftp_backup_status:是否启用FTP远程备份
	 * @return ftp_backup_hostname:FTP主机
	 * @return ftp_backup_username:用户名
	 * @return ftp_backup_password:密码
	 * @return ftp_backup_destination:远程FTP备份路径
	 * @return ftp_secure_mode:SFTP模式
	 * @return ftp_passive_mode:FTP被动模式
	 * 
	 */
	public function databaseBackups()
	{
		$keys = ["daily_email_backup_status", "daily_email_backup", "daily_ftp_backup_status", "ftp_backup_hostname", "ftp_backup_port", "ftp_backup_username\n            ", "ftp_backup_password", "ftp_backup_destination", "ftp_secure_mode", "ftp_passive_mode"];
		$config_data = getConfig($keys);
		if ($config_data["ftp_backup_password"]) {
			$config_data["ftp_backup_password"] = str_pad("", strlen(cmf_decrypt($config_data["ftp_backup_password"])), "*");
		}
		$returndata = [];
		$returndata["config_data"] = $config_data;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 测试/保存ftp连接
	 * @description 接口说明:测试/保存ftp连接
	 * @author 萧十一郎
	 * @url /admin/backup_ftp
	 * @method post
	 * @param .name:ftp_backup_hostname type:string require:1 default: other: desc:FTP主机
	 * @param .name:ftp_backup_port type:number require:1 default: other: desc:端口号
	 * @param .name:ftp_backup_username type:string require:1 default: other: desc:用户名
	 * @param .name:ftp_backup_password type:string require:1 default: other: desc:密码
	 * @param .name:ftp_backup_destination type:string require:1 default: other: desc:远程FTP备份路径
	 * @param .name:ftp_secure_mode type:int require: default: other: desc:SFTP模式1,0（与ftp_passive_mode 有一个至少一个勾选）
	 * @param .name:ftp_passive_mode type:int require: default: other: desc:FTP被动模式
	 * @param .name:type type:string require:1 default: other: desc:类型(test:测试链接，save:保存)
	 */
	public function backupDatabaseFtp(\think\Request $request)
	{
		$param = $request->param();
		$rule = ["ftp_backup_hostname" => "require", "ftp_backup_port" => "require|number", "ftp_backup_username" => "require", "ftp_backup_password" => "require", "ftp_backup_destination" => "require", "ftp_secure_mode" => "in:0,1", "ftp_passive_mode" => "in:0,1", "type" => "in:test,save"];
		$msg = ["ftp_backup_hostname.require" => "FTP主机名不能为空", "ftp_backup_port.require" => "FTP端口号不能为空", "ftp_backup_port.number" => "FTP端口号必须为数字", "ftp_backup_username.require" => "FTP用户名不能为空", "ftp_backup_password.require" => "FTP密码不能为空", "ftp_backup_destination.require" => "FTP路径不能为空"];
		$validate = new \think\Validate($rule, $msg);
		$result = $validate->check($param);
		if (!$result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$password = $param["ftp_backup_password"];
		if (str_pad("", strlen($password), "*") == $password) {
			$password = getConfig("ftp_backup_password");
			$password = cmf_decrypt($password);
		}
		if ($param["ftp_secure_mode"]) {
			$ftp_resource = ftp_ssl_connect($param["ftp_backup_hostname"], $param["ftp_backup_port"], 5);
		} elseif ($param["ftp_passive_mode"]) {
			$ftp_resource = ftp_connect($param["ftp_backup_hostname"], $param["ftp_backup_port"], 5);
		} else {
			return jsonrule(["status" => 406, "msg" => "请选择连接模式"]);
		}
		if (!$ftp_resource) {
			return jsonrule(["status" => 406, "msg" => "FTP服务器连接失败"]);
		}
		if (@ftp_login($ftp_resource, $param["ftp_backup_username"], $param["ftp_backup_password"])) {
			if (ftp_chdir($ftp_resource, $param["ftp_backup_destination"])) {
				ftp_close($conn_id);
				if ($param["type"] == "save") {
					updateConfiguration("daily_ftp_backup_status", 1);
					updateConfiguration("ftp_backup_hostname", $param["ftp_backup_hostname"]);
					updateConfiguration("ftp_backup_port", $param["ftp_backup_port"]);
					updateConfiguration("ftp_backup_username", $param["ftp_backup_username"]);
					updateConfiguration("ftp_backup_password", cmf_encrypt($password));
					updateConfiguration("ftp_backup_destination", $param["ftp_backup_destination"]);
					updateConfiguration("ftp_secure_mode", $param["ftp_secure_mode"]);
					updateConfiguration("ftp_passive_mode", $param["ftp_passive_mode"]);
					return jsonrule(["status" => 200, "msg" => "保存成功"]);
				}
				return jsonrule(["status" => 200, "msg" => "连接FTP服务器成功"]);
			} else {
				return jsonrule(["status" => 406, "msg" => "切换到目录失败"]);
			}
		} else {
			return jsonrule(["status" => 406, "msg" => "FTP服务器登录失败"]);
		}
	}
	/**
	 * @title 停用FTP备份
	 * @description 接口说明:停用FTP备份
	 * @author 萧十一郎
	 * @url /admin/deactivete_ftp
	 * @method post
	 */
	public function deactivateFtp()
	{
		updateConfiguration("daily_ftp_backup_status", 0);
		return jsonrule(["status" => 200, "msg" => "执行成功"]);
	}
	/**
	 * @title 保存并启用邮箱备份
	 * @description 接口说明:保存并启用邮箱备份
	 * @author 萧十一郎
	 * @url /admin/backup_email
	 * @method post
	 * @param .name:daily_email_backup type:int require: default: other: desc:邮箱地址
	 */
	public function backupEmail(\think\Request $request)
	{
		$param = $request->param();
		$daily_email_backup = strval(trim($param["daily_email_backup"]));
		if (empty($daily_email_backup)) {
			return jsonrule(["status" => 406, "msg" => "邮箱不能为空"]);
		}
		$reg = "/^([a-zA-Z0-9_\\-\\+]+)@([a-zA-Z0-9_\\-\\+]+)\\.([a-zA-Z]{0,5})\$/";
		if (!preg_match($reg, $daily_email_backup)) {
			return jsonrule(["status" => 406, "msg" => "邮箱格式错误"]);
		}
		updateConfiguration("daily_email_backup", $param["daily_email_backup"]);
		updateConfiguration("daily_email_backup_status", 1);
		return jsonrule(["status" => 200, "msg" => "保存成功"]);
	}
	/**
	 * @title 停用邮箱备份
	 * @description 接口说明:停用邮箱备份
	 * @author 萧十一郎
	 * @url /admin/deactivete_email
	 * @method post
	 */
	public function deactivateEmail(\think\Request $request)
	{
		updateConfiguration("daily_email_backup_status", 0);
		return jsonrule(["status" => 200, "msg" => "执行成功"]);
	}
}