<?php

namespace app\admin\controller;

/**
 * @title 沟通能力分类
 * @description 沟通能力分类
 */
class LinkCauseController extends AdminBaseController
{
	const CAUSE_NAME_NOT_NULL = "分类名称必填";
	const CAUSE_LINT_TYPE_NOT_NULL = "请选择分类类型";
	const PARAM_ERROR = "参数错误";
	const DATA_NOT_FOUND = "数据信息不存在";
	const LINT_TYPE_NOT_FOUND = "分类类型不存在";
	/**
	 * @title 沟通能力-分类列表
	 * @description 接口说明:沟通能力-分类列表
	 * @author xue
	 * @url /admin/link_cause/list
	 * @method GET
	 * @param .name:name type:string require:0 desc:分类名称（搜索条件）
	 * @param .name:link_type type:int require:0 desc:分类类型（搜索条件）
	 * @return .data
	 * @return .name:分类名称
	 * @return .type_name:分类类型
	 * @return .keywords:关键字
	 * @return .create_time:创建时间
	 * @return .son:分类子集
	 */
	public function index()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$data = \think\Db::name("link_cause");
			if ($param["name"]) {
				$data->whereLike("name", "%" . $param["name"] . "%");
			}
			if (isset($param["parent_id"])) {
				$data->where("parent_id", $param["parent_id"]);
			}
			if ($param["link_type"]) {
				$data->where("link_type", $param["link_type"]);
			}
			if ($param["level"]) {
				$data->where("level", "<=", $param["level"]);
			}
			$result = $data->order("id", "desc")->select()->toArray();
			$result_ids = [];
			if ($result) {
				$result_ids = array_column($result, "id");
				$result = array_column($result, null, "id");
			}
			foreach ($this->getKeywords($result_ids, "cause") as $k => $val) {
				if (isset($result[$val["relid"]])) {
					$result[$val["relid"]]["keywords"][] = $val["keyword"];
				}
			}
			$link_types = \think\Db::name("link_types")->select()->toArray();
			if ($link_types) {
				$link_types = array_column($link_types, "name", "id");
			}
			$result = array_map(function ($v) use($link_types) {
				$v["type_name"] = isset($link_types[$v["link_type"]]) ? $link_types[$v["link_type"]] : "";
				$v["pid"] = $v["parent_id"];
				$v["keywords"] = implode(",", $v["keywords"]);
				unset($v["parent_id"]);
				return $v;
			}, $result);
			return $this->getTree($result);
		});
	}
	/**
	 * @title 沟通能力-分类编辑
	 * @description 接口说明:沟通能力-分类编辑
	 * @author xue
	 * @url /admin/link_cause/edit
	 * @method GET
	 * @param .name:id type:string require:1 desc:分类id
	 * @return array|\think\Response|\think\response\Json
	 * @return .data.name:分类名称
	 * @return .data.type_name:分类类型
	 * @return .data.keywords:关键字
	 * @return .data.create_time:创建时间
	 * @return .link_type:分类列表
	 */
	public function edit()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["id"]) {
				throw new \Exception(self::PARAM_ERROR);
			}
			$data = \think\Db::name("link_cause")->where("id", $param["id"])->find();
			if (!$data) {
				throw new \Exception(self::DATA_NOT_FOUND);
			}
			foreach ($this->getKeywords($data["id"], "cause") as $k => $v) {
				$data["keywords"][] = $v["keyword"];
			}
			$data["keywords"] = implode(",", $data["keywords"]);
			return ["data" => $data, "link_type" => $this->getLinkType()];
		});
	}
	/**
	 * @title 沟通能力-分类编辑保存
	 * @description 接口说明:沟通能力-分类编辑保存
	 * @author xue
	 * @url /admin/link_cause/save
	 * @method POST
	 * @param .name:id type:string require:1 desc:分类id
	 * @param .name:name type:string require:1 desc:分类名称
	 * @param .name:link_types type:int require:1 desc:分类类型
	 * @param .name:keyword type:string require:0 desc:关键字
	 * @return array|\think\Response|\think\response\Json
	 */
	public function save()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$this->causeSaveCheck($param);
			if (!$param["id"]) {
				throw new \Exception(self::PARAM_ERROR);
			}
			$old_data = \think\Db::name("link_cause")->where("id", $param["id"])->find();
			if (!$old_data) {
				throw new \Exception(self::DATA_NOT_FOUND);
			}
			if (!\think\Db::name("link_types")->where("id", $param["link_type"])->find()) {
				throw new \Exception("LINT_TYPE_NOT_FOUND");
			}
			$data = ["name" => $param["name"], "link_type" => $param["link_type"], "update_time" => time()];
			\think\Db::name("link_cause")->where("id", $param["id"])->update($data);
			$this->saveCauseKeywords($param["keyword"], $param["id"], "cause");
			return true;
		});
	}
	/**
	 * @title 沟通能力-分类创建
	 * @description 接口说明:沟通能力-分类创建
	 * @author xue
	 * @url /admin/link_cause/create
	 * @method GET
	 * @return array|\think\Response|\think\response\Json
	 */
	public function create()
	{
		return $this->tryCatch(function () {
			return ["link_type" => $this->getLinkType()];
		});
	}
	/**
	 * @title 沟通能力-分类创建保存
	 * @description 接口说明:沟通能力-分类创建保存
	 * @author xue
	 * @url /admin/link_cause/add
	 * @method POST
	 * @param .name:parent_id type:int require:1 desc:父级id(没有默认为 0)
	 * @param .name:name type:string require:1 desc:分类名称
	 * @param .name:link_types type:int require:1 desc:分类类型
	 * @param .name:keyword type:string require:0 desc:关键字
	 * @return array|\think\Response|\think\response\Json
	 */
	public function add()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$this->causeSaveCheck($param);
			if ($param["parent_id"]) {
				$parent_data = \think\Db::name("link_cause")->where("id", $param["parent_id"])->find();
				$level = $parent_data["level"] + 1;
				$level_view = explode("-", $parent_data["level_view"]);
			} else {
				$level = 1;
				$level_view = [];
			}
			$data = ["name" => $param["name"], "link_type" => $param["link_type"], "parent_id" => $param["parent_id"] ?: 0, "level" => $level, "create_time" => time()];
			$cause_id = \think\Db::name("link_cause")->insertGetId($data);
			$level_view[] = "(" . $cause_id . ")";
			\think\Db::name("link_cause")->where("id", $cause_id)->update(["level_view" => implode("-", $level_view)]);
			return $this->saveCauseKeywords($param["keyword"], $cause_id, "cause");
		});
	}
	private function saveCauseKeywords($keywords, $id, $belong)
	{
		\think\Db::name("link_keywords")->where("relid", $id)->where("belong", $belong)->delete();
		$keywords = explode(",", $keywords);
		$insertAll = [];
		foreach ($keywords as $key => $val) {
			$insertAll[] = ["keyword" => $val, "belong" => $belong, "relid" => $id, "status" => 1, "create_time" => time()];
		}
		$insertAll && \think\Db::name("link_keywords")->insertAll($insertAll);
		return true;
	}
	private function causeSaveCheck($param)
	{
		if (!$param["name"]) {
			throw new \Exception(self::CAUSE_NAME_NOT_NULL);
		}
		if (!$param["link_type"]) {
			throw new \Exception(self::CAUSE_LINT_TYPE_NOT_NULL);
		}
	}
	private function getLinkType()
	{
		return \think\Db::name("link_types")->field("id,name")->select()->toArray();
	}
	private function getKeywords($relid, $belong = "knowledge")
	{
		$relid = is_array($relid) ? $relid : [$relid];
		return \think\Db::name("link_keywords")->where("belong", $belong)->whereIn("relid", $relid)->cursor();
	}
	private function tryCatch(\Closure $closure)
	{
		try {
			return $this->toJson(call_user_func($closure));
		} catch (\Throwable $exception) {
			return $this->errorJson($exception);
		}
	}
	private function getPage()
	{
		return max(1, $this->request->page);
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
	private function getLimit()
	{
		$limit = max(1, $this->request->limit);
		if ($limit > 50) {
			$limit = 50;
		}
		return intval($limit);
	}
	private function errorJson(\Throwable $exception)
	{
		return json(["status" => 406, "msg" => $exception->getMessage()]);
	}
	private function toJson($result)
	{
		return json(["status" => 200, "msg" => "请求成功", "data" => $result]);
	}
}