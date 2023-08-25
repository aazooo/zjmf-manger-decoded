<?php

namespace app\admin\controller;

class RuleMiddleController extends AdminBaseController
{
	public $table = "auth_role_middle";
	protected static $order = 1;
	protected $arr = ["add_role_menu", "cat_role_menu", "del_role_menu", "edit_role_menu"];
	public function getMenuList()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$model = \think\Db::name($this->table)->order("order")->order("id", "ASC")->select()->toArray();
			$model = array_map(function ($v) {
				foreach ($v as $k => $val) {
					if (in_array($k, $this->arr)) {
						$v[$k] = explode(",", $val);
					}
				}
				return $v;
			}, $model);
			return $this->getTree($model);
		});
	}
	public function addMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$data = ["name" => $param["name"], "add_role" => $param["add_role"] ?? 1, "add_role_menu" => $param["add_role_menu"] ?: "", "cat_role" => $param["cat_role"] ?? 1, "cat_role_menu" => $param["cat_role_menu"] ?: "", "del_role" => $param["del_role"] ?? 1, "del_role_menu" => $param["del_role_menu"] ?: "", "edit_role" => $param["edit_role"] ?? 1, "edit_role_menu" => $param["edit_role_menu"] ?: "", "order" => 1, "pid" => 0];
			return \think\Db::name($this->table)->insertGetId($data);
		});
	}
	public function editMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$data = ["name" => $param["name"], "add_role" => $param["add_role"] ?? 1, "add_role_menu" => $param["add_role_menu"] ?: "", "cat_role" => $param["cat_role"] ?? 1, "cat_role_menu" => $param["cat_role_menu"] ?: "", "del_role" => $param["del_role"] ?? 1, "del_role_menu" => $param["del_role_menu"] ?: "", "edit_role" => $param["edit_role"] ?? 1, "edit_role_menu" => $param["edit_role_menu"] ?: "", "order" => 1, "pid" => 0];
			if (!$data["title"]) {
				throw new \think\Exception("title不能为空");
			}
			return \think\Db::name($this->table)->update($data);
		});
	}
	public function saveMenuList()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			return \think\Db::transaction(function () use($param) {
				\think\Db::name($this->table)->where("id", ">", 0)->delete();
				$two_arr = $this->changeTwoArr($param["list"]);
				$two_arr = array_map(function ($v) {
					foreach ($v as $k => $val) {
						if (in_array($k, $this->arr)) {
							$v[$k] = is_array($val) ? implode(",", array_filter($val)) : $val;
						}
					}
					$v["id"] = intval($v["id"]);
					$v["update_time"] = time();
					$v["create_time"] = time();
					ksort($v);
					return $v;
				}, $two_arr);
				return \think\Db::name($this->table)->insertAll($two_arr);
			});
		});
	}
	public function getNav()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$model = \think\Db::name("auth_rule")->select()->toArray();
			$eum = ["(隐藏|接口)", "(显示|页面)"];
			$model = array_map(function ($v) use($eum) {
				$v["title"] .= $eum[$v["is_display"]];
				return $v;
			}, $model);
			return $this->getTree($model);
		});
	}
	private function changeTwoArr($tree, $children = "son", $order = true)
	{
		$imparr = [];
		foreach ($tree as $w) {
			$order && ($w["order"] = self::$order++);
			unset($w["cn_name"]);
			if (isset($w[$children])) {
				foreach ($w[$children] as $key => $val) {
					$w[$children][$key]["pid"] = $w["id"];
				}
				$t = $w[$children];
				unset($w[$children]);
				$imparr[] = $w;
				if (is_array($t)) {
					$imparr = array_merge($imparr, $this->changeTwoArr($t, $children, $order));
				}
			} else {
				$imparr[] = $w;
			}
		}
		return $imparr;
	}
	private function getTree($data, $son = "son")
	{
		if (empty($data)) {
			return [];
		}
		$_data = array_column($data, null, "id");
		$result = [];
		foreach ($_data as $key => $val) {
			if (isset($_data[$val["pid"]])) {
				$_data[$val["pid"]][$son][] =& $_data[$key];
			} else {
				$result[] =& $_data[$key];
			}
		}
		return $result;
	}
	private function tryCatch(\Closure $closure)
	{
		try {
			return $this->toJson(call_user_func($closure));
		} catch (\Throwable $exception) {
			return $this->errorJson($exception);
		}
	}
	private function getLimit()
	{
		$limit = max(1, $this->request->limit);
		if ($limit > 50) {
			$limit = 50;
		}
		return $limit;
	}
	private function errorJson(\Throwable $exception)
	{
		return json(["status" => 406, "msg" => $exception->getMessage()]);
	}
	private function toJson($result)
	{
		return json(["status" => 200, "msg" => "Success", "data" => $result]);
	}
}