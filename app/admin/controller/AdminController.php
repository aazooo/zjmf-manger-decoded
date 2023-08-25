<?php

namespace app\admin\controller;

/**
 * @title 管理员管理
 * @description 接口说明
 */
class AdminController extends AdminBaseController
{
	/**
	 * 管理员列表
	 * @adminMenu(
	 *     'name'   => '管理员',
	 *     'parent' => 'default',
	 *     'parent' => 'default',
	 *     'display'=> true,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员管理',
	 *     'param'  => ''
	 * )
	 */
	public function index()
	{
		$content = hook_one("admin_user_index_view");
		if (!empty($content)) {
			return $content;
		}
		$data = $this->request->param();
		$order = isset($data["order"]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"]) ? trim($data["sort"]) : "DESC";
		$userLogin = $this->request->param("user_login");
		$userEmail = trim($this->request->param("user_email"));
		$users = \think\Db::name("user")->where("user_type", 1)->where(function (\think\db\Query $query) use($userLogin, $userEmail) {
			if ($userLogin) {
				$query->where("user_login", "like", "%{$userLogin}%");
			}
			if ($userEmail) {
				$query->where("user_email", "like", "%{$userEmail}%");
			}
		})->order($order, $sort)->paginate(10);
		$users->appends(["user_login" => $userLogin, "user_email" => $userEmail]);
		$page = $users->render();
		$rolesSrc = \think\Db::name("role")->select();
		$roles = [];
		foreach ($rolesSrc as $r) {
			$roleId = $r["id"];
			$roles[\strval($roleId)] = $r;
		}
		$this->assign("page", $page);
		$this->assign("roles", $roles);
		$this->assign("users", $users);
		$description = "查看管理员列表 - 用户关键字:" . $userLogin . ",邮箱:" . $userEmail;
		active_log_final($description);
		return $this->fetch();
	}
	/**
	 *
	 * @title 登录
	 * @description 接口说明: 验证码/new_captcha.html?height=高&width=宽&font_size=字体大小&time=时间戳
	 * @author 上官磨刀
	 * @url /admin/login
	 * @method POST
	 * @param name:username type:str require:1 default:1 other: desc:用户名
	 * @param name:password type:str require:1 default:1 other: desc:密码
	 * @param name:captcha type:str require:1 default:1 other: desc:验证码
	 *
	 */
	public function add()
	{
		$content = hook_one("admin_user_add_view");
		if (!empty($content)) {
			return $content;
		}
		$roles = \think\Db::name("role")->where("status", 1)->order("id DESC")->select();
		$this->assign("roles", $roles);
		$description = "进入管理员添加页面";
		active_log_final($description);
		return $this->fetch();
	}
	/**
	 * 管理员添加提交
	 * @adminMenu(
	 *     'name'   => '管理员添加提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员添加提交',
	 *     'param'  => ''
	 * )
	 */
	public function addPost()
	{
		if ($this->request->isPost()) {
			if (!empty($_POST["role_id"]) && is_array($_POST["role_id"])) {
				$role_ids = $_POST["role_id"];
				unset($_POST["role_id"]);
				$result = $this->validate($this->request->param(), "User");
				if ($result !== true) {
					$this->error($result);
				} else {
					$_POST["user_pass"] = cmf_password($_POST["user_pass"]);
					$result = \think\Db::name("user")->insertGetId($_POST);
					if ($result !== false) {
						foreach ($role_ids as $role_id) {
							if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							\think\Db::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $result]);
						}
						$description = "管理员添加成功 - user_id:" . $result . ",权限集:" . $role_ids;
						active_log_final($description, $result);
						$this->success("添加成功！", url("user/index"));
					} else {
						$description = "管理员添加失败";
						active_log_final($description);
						$this->error("添加失败！");
					}
				}
			} else {
				$this->error("请为此用户指定角色！");
			}
		}
	}
	/**
	 * 管理员编辑
	 * @adminMenu(
	 *     'name'   => '管理员编辑',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员编辑',
	 *     'param'  => ''
	 * )
	 */
	public function edit()
	{
		$content = hook_one("admin_user_edit_view");
		if (!empty($content)) {
			return $content;
		}
		$id = $this->request->param("id", 0, "intval");
		$roles = \think\Db::name("role")->where("status", 1)->order("id DESC")->select();
		$this->assign("roles", $roles);
		$role_ids = \think\Db::name("RoleUser")->where("user_id", $id)->column("role_id");
		$this->assign("role_ids", $role_ids);
		$user = \think\Db::name("user")->where("id", $id)->find();
		$this->assign($user);
		$description = "进入管理员编辑页面";
		active_log_final($description);
		return $this->fetch();
	}
	/**
	 * 管理员编辑提交
	 * @adminMenu(
	 *     'name'   => '管理员编辑提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员编辑提交',
	 *     'param'  => ''
	 * )
	 */
	public function editPost()
	{
		if ($this->request->isPost()) {
			if (!empty($_POST["role_id"]) && is_array($_POST["role_id"])) {
				if (empty($_POST["user_pass"])) {
					unset($_POST["user_pass"]);
				} else {
					$_POST["user_pass"] = cmf_password($_POST["user_pass"]);
				}
				$role_ids = $this->request->param("role_id/a");
				unset($_POST["role_id"]);
				$result = $this->validate($this->request->param(), "User.edit");
				$uid = $this->request->param("id", 0, "intval");
				if ($result !== true) {
					$this->error($result);
				} else {
					$result = \think\Db::name("user")->update($_POST);
					if ($result !== false) {
						\think\Db::name("RoleUser")->where("user_id", $uid)->delete();
						foreach ($role_ids as $role_id) {
							if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							\think\Db::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
						}
						$description = "管理员保存成功 - user_id:" . $uid . ",权限集:" . $role_ids;
						active_log_final($description, $uid);
						$this->success("保存成功！");
					} else {
						$description = "管理员保存失败 - user_id:" . $uid . ",权限集:" . $role_ids;
						active_log_final($description, $uid);
						$this->error("保存失败！");
					}
				}
			} else {
				$this->error("请为此用户指定角色！");
			}
		}
	}
	/**
	 * 管理员个人信息修改
	 * @adminMenu(
	 *     'name'   => '个人信息',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员个人信息修改',
	 *     'param'  => ''
	 * )
	 */
	public function userInfo()
	{
		$id = cmf_get_current_admin_id();
		$user = \think\Db::name("user")->where("id", $id)->find();
		$this->assign($user);
		active_log_final("进入管理员个人信息修改界面");
		return $this->fetch();
	}
	/**
	 * 管理员个人信息修改提交
	 * @adminMenu(
	 *     'name'   => '管理员个人信息修改提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员个人信息修改提交',
	 *     'param'  => ''
	 * )
	 */
	public function userInfoPost()
	{
		if ($this->request->isPost()) {
			$data = $this->request->post();
			$data["birthday"] = strtotime($data["birthday"]);
			$data["id"] = cmf_get_current_admin_id();
			$create_result = \think\Db::name("user")->update($data);
			if ($create_result !== false) {
				$description = "管理员个人信息修改成功 - data:" . $data;
				active_log_final($description);
				$this->success("保存成功！");
			} else {
				$description = "管理员个人信息修改失败 - data:" . $data;
				active_log_final($description);
				$this->error("保存失败！");
			}
		}
	}
	/**
	 * 管理员删除
	 * @adminMenu(
	 *     'name'   => '管理员删除',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员删除',
	 *     'param'  => ''
	 * )
	 */
	public function delete()
	{
		$id = $this->request->param("id", 0, "intval");
		if ($id == 1) {
			$this->error("最高管理员不能删除！");
		}
		if (\think\Db::name("user")->delete($id) !== false) {
			\think\Db::name("RoleUser")->where("user_id", $id)->delete();
			$description = "管理员删除成功 - user_id:" . $id;
			active_log_final($description, $id);
			$this->success("删除成功！");
		} else {
			$description = "管理员删除失败 - user_id:" . $id;
			active_log_final($description, $id);
			$this->error("删除失败！");
		}
	}
	/**
	 * 停用管理员
	 * @adminMenu(
	 *     'name'   => '停用管理员',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '停用管理员',
	 *     'param'  => ''
	 * )
	 */
	public function ban()
	{
		$id = $this->request->param("id", 0, "intval");
		if (!empty($id)) {
			$result = \think\Db::name("user")->where(["id" => $id, "user_type" => 1])->setField("user_status", "0");
			if ($result !== false) {
				$description = "管理员停用成功 - user_id:" . $id;
				active_log_final($description, $id);
				$this->success("管理员停用成功！", url("user/index"));
			} else {
				$description = "管理员停用失败 - user_id:" . $id;
				active_log_final($description, $id);
				$this->error("管理员停用失败！");
			}
		} else {
			$this->error("数据传入失败！");
		}
	}
	/**
	 * 启用管理员
	 * @adminMenu(
	 *     'name'   => '启用管理员',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '启用管理员',
	 *     'param'  => ''
	 * )
	 */
	public function cancelBan()
	{
		$id = $this->request->param("id", 0, "intval");
		if (!empty($id)) {
			$result = \think\Db::name("user")->where(["id" => $id, "user_type" => 1])->setField("user_status", "1");
			if ($result !== false) {
				$description = "管理员启用成功 - user_id:" . $id;
				active_log_final($description, $id);
				$this->success("管理员启用成功！", url("user/index"));
			} else {
				$description = "管理员启用失败 - user_id:" . $id;
				active_log_final($description, $id);
				$this->error("管理员启用失败！");
			}
		} else {
			$this->error("数据传入失败！");
		}
	}
	/**
	 * @return mixed
	 */
	public function admin_action_log()
	{
		$data = $this->request->param();
		$date = isset($data["time"]) ? $data["time"] : date("Y-m-d");
		$filename = CMF_ROOT . "data/journal/" . $date . ".log";
		$logs = [];
		if (file_exists_case($filename)) {
			fopen($filename, "r");
			$num = count(file($filename));
			$file_hwnd = fopen($filename, "r");
			$content = explode("\r\n", fread($file_hwnd, filesize($filename)));
			fclose($file_hwnd);
			foreach ($content as $k => $v) {
				if ($v) {
					$logs[$k] = json_decode($v, true);
				}
			}
		} else {
			$num = 0;
		}
		$this->assign("content", array_reverse($logs, true));
		$this->assign("time", $date);
		$this->assign("num", $num);
		return $this->fetch("/admin_index");
	}
}