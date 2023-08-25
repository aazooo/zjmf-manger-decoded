<?php

namespace app\admin\controller;

class RuleManageController extends AdminBaseController
{
	public $table = "auth_rule";
	protected static $order = 1;
	public function getMenuList()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$model = \think\Db::name($this->table)->order("order")->order("id", "ASC")->select()->toArray();
			$model = array_map(function ($v) {
				$v["cn_name"] = $v["is_display"] ? "前台页面" : "接口！";
				return $v;
			}, $model);
			return $this->getTree($model);
		});
	}
	public function addMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$data = ["status" => 1, "app" => "admin", "type" => "admin_url", "name" => $param["name"] ?: "", "param" => "", "title" => $param["title"] ?: "", "condition" => "", "pid" => 0, "url" => $param["url"], "is_display" => $param["is_display"], "order" => 1];
			if (!$data["title"]) {
				throw new \think\Exception("title不能为空");
			}
			return \think\Db::name($this->table)->insertGetId($data);
		});
	}
	public function editMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$data = ["app" => "admin", "type" => "admin_url", "name" => $param["name"] ?: "", "title" => $param["title"] ?: "", "is_display" => $param["is_display"], "url" => $param["url"], "id" => $param["id"]];
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
				foreach ($two_arr as $key => $val) {
					$data = ["id" => $val["id"], "status" => $val["status"] ?? 1, "app" => $val["app"] ?? "admin", "type" => $val["type"] ?? "admin_url", "param" => $val["param"] ?? "", "title" => $val["title"] ?? "", "condition" => $val["condition"] ?? "", "pid" => $val["pid"] ?? 0, "is_display" => $val["is_display"] ?? 0, "url" => $val["url"] ?? "", "order" => $val["order"]];
					$two[$key] = $data;
				}
				return \think\Db::name($this->table)->insertAll($two_arr);
			});
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