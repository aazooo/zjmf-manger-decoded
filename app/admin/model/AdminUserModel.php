<?php

namespace app\admin\model;

class AdminUserModel extends \think\Model
{
	protected $pk = "id";
	protected $type = ["more" => "array"];
	public function adminVerify($adminuser)
	{
		$re = [];
		$result = $this->where("username", $adminuser["username"])->find();
		if (!empty($result)) {
			$comparePasswordResult = cmf_compare_password($adminuser["password"], $result["password"]);
			$clientIP = get_client_ip(0, true);
			if ($comparePasswordResult) {
				session("user", $result->toArray());
				$data = ["last_login_time" => time(), "last_login_ip" => $clientIP];
				$this->where("id", $result["id"])->update($data);
				$re["jwt"] = createJwt($result);
				$re["status"] = 200;
				$re["msg"] = "登录成功";
				return $re;
			}
		}
		$re["status"] = 401;
		$re["msg"] = "此管理员不存在";
		return $re;
	}
	/**
	 * 获取用户角色权限列表
	 */
	public function get_auth_role($uid)
	{
		if ($uid > 1) {
			$where["ru.user_id"] = $uid;
			$role = \think\Db::name("role_user ru")->leftJoin("role r", "r.id = ru.role_id")->where($where)->field("r.auth_role")->find();
		} else {
			$role = \think\Db::name("auth_rule")->field("id")->order("pid", "ASC")->order("id", "ASC")->select()->toArray();
			$res = "";
			foreach ($role as $key => $value) {
				if ($key == 0) {
					$res = $value["id"];
				} else {
					$res .= "," . $value["id"];
				}
			}
			$role["auth_role"] = $res;
		}
		return $role;
	}
	/**
	 * 获取用户所有权限并递归
	 */
	public function get_rule($uid)
	{
		$where["is_display"] = 1;
		$user = \think\Db::name("role_user")->where("user_id", $uid)->field("role_id")->find();
		if ($uid > 1 && $user["role_id"] > 1) {
			$where["ru.user_id"] = $uid;
			$role = \think\Db::name("role_user ru")->leftJoin("auth_access aa", "aa.role_id = ru.role_id")->leftJoin("auth_rule ar", "aa.rule_id = ar.id")->where($where)->field("ar.*")->order("order")->order("id", "ASC")->select()->toArray();
			foreach ($role as $key => $v) {
				$rolecount = \think\Db::name("role_user ru")->leftJoin("auth_access aa", "aa.role_id = ru.role_id")->leftJoin("auth_rule ar", "aa.rule_id = ar.id")->where($where)->where("ar.pid", $v["id"])->count();
				$rolecount1 = \think\Db::name("auth_rule")->where("is_display", 1)->where("pid", $v["id"])->count();
				if ($rolecount <= 0 && $rolecount1 > 0) {
					unset($role[$key]);
				}
			}
			$role = array_values($role);
		} else {
			$role = \think\Db::name("auth_rule")->where($where)->order("order")->order("id", "ASC")->select()->toArray();
		}
		if (!isset($role[0])) {
			return [];
		}
		$ret = [];
		$tmp = array_column($role, "pid", "id");
		$user_language = \think\Db::name("user")->where("id", $uid)->value("language");
		$languagesys = \think\facade\Request::param("languagesys");
		$now_language = $languagesys ?? $user_language;
		foreach ($role as &$v) {
			$language_map = json_decode($v["language_map"], 1);
			if (empty($language_map)) {
				continue;
			}
			if (!$now_language) {
				continue;
			}
			$v["title"] = $language_map[$now_language] ?? $v["title"];
		}
		$ret = $this->getTree($role);
		$ret = self::array_deal(array_values($ret));
		return $ret;
	}
	private function getTree($data, $son = "list")
	{
		if (empty($data)) {
			return [];
		}
		$_data = array_column($data, null, "id");
		$result = [];
		foreach ($_data as $key => $val) {
			if (isset($_data[$val["pid"]])) {
				if (!empty($val["url"]) && empty($_data[$val["pid"]]["url"])) {
					if ($val["url"] == "/dcim-traffic") {
						$val["url"] = "/product-server";
					}
					$_data[$val["pid"]]["url"] = $val["url"];
				}
				$_data[$val["pid"]][$son][] =& $_data[$key];
			} else {
				$result[] =& $_data[$key];
			}
		}
		return $result;
	}
	public static function array_deal($arr = null)
	{
		foreach ($arr as $k => $v) {
			if (empty($v["list"]) || !is_array($v["list"])) {
				unset($arr[$k]);
			}
		}
		return array_values($arr);
	}
}