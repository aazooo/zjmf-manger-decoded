<?php

namespace app\admin\controller;

/**
 * @title 沟通能力知识库
 * @description 沟通能力知识库
 */
class LinkKnowledgeController extends AdminBaseController
{
	const CAUSE_TITLE_NOT_NULL = "标题必填";
	const CAUSE_LINT_TYPE_NOT_NULL = "请选择分类类型";
	const PARAM_ERROR = "参数错误";
	const DATA_NOT_FOUND = "数据信息不存在";
	const LINT_TYPE_NOT_FOUND = "分类类型不存在";
	/**
	 * @title 沟通能力-知识库列表
	 * @description 接口说明:沟通能力-知识库列表
	 * @author xue
	 * @url /admin/link_knowledge/list
	 * @method GET
	 * @param .name:title type:string require:0 desc:标题（搜索条件）
	 * @param .name:link_cause type:int require:0 desc:分类类型（搜索条件）
	 * @return array|\think\Response|\think\response\Json
	 * @return .title:标题
	 * @return .level_view_name:分类
	 * @return .keywords:关键字
	 * @return .create_time:创建时间
	 */
	public function index()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$data = \think\Db::name("link_knowledge");
			if ($param["title"]) {
				$data->where("title", $param["title"]);
			}
			if ($param["link_cause"]) {
				$data->where("link_cause", $param["link_cause"]);
			}
			if ($param["type"]) {
				$data->where("type", $param["type"]);
			}
			if ($param["status"]) {
				$data->where("status", $param["status"]);
			}
			$result = $data->order("id", "desc")->page($this->getPage(), $this->getLimit())->select()->toArray();
			$k_ids = $k_cause_ids = [];
			if ($result) {
				$k_ids = array_column($result, "id");
				$k_cause_ids = array_column($result, "link_cause");
				$result = array_column($result, null, "id");
			}
			foreach ($this->getKeywords($k_ids, "knowledge") as $k => $val) {
				if (isset($result[$val["relid"]])) {
					$result[$val["relid"]]["keywords"][] = $val["keyword"];
				}
			}
			$link_cause = $this->getLinkCause($k_cause_ids);
			$type_arr = $this->getKnowkedgeType();
			if ($type_arr) {
				$type_arr = array_column($type_arr, "name", "id");
			}
			$result = array_map(function ($val) use($link_cause, $type_arr) {
				$val["level_view_name"] = "";
				if (isset($link_cause[$val["link_cause"]])) {
					$val["level_view_name"] = implode("/", $link_cause[$val["link_cause"]]);
				}
				$val["keywords"] = implode(",", $val["keywords"]);
				$val["type_name"] = $type_arr[$val["type"]] ?? "";
				return $val;
			}, $result);
			return array_values($result);
		});
	}
	/**
	 * @title 沟通能力-知识库编辑
	 * @description 接口说明:沟通能力-知识库编辑
	 * @author xue
	 * @url /admin/link_knowledge/edit
	 * @method GET
	 * @param .name:id type:string require:1 desc:id
	 * @return array|\think\Response|\think\response\Json
	 * @return .title:标题
	 * @return .level_view_name:分类
	 * @return .keywords:关键字
	 * @return .type:类型列表
	 */
	public function edit()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["id"]) {
				throw new \Exception(self::PARAM_ERROR);
			}
			$data = \think\Db::name("link_knowledge")->where("id", $param["id"])->find();
			if (!$data) {
				throw new \Exception(self::DATA_NOT_FOUND);
			}
			foreach ($this->getKeywords($data["id"]) as $k => $v) {
				$data["keywords"][] = $v["keyword"];
			}
			$data["keywords"] = implode(",", $data["keywords"]);
			return ["data" => $data, "type" => $this->getKnowkedgeType()];
		});
	}
	/**
	 * @title 沟通能力-知识库编辑保存
	 * @description 接口说明:沟通能力-知识库编辑保存
	 * @author xue
	 * @url /admin/link_knowledge/save
	 * @method POST
	 * @param .name:id type:int require:1 desc:id
	 * @param .name:title type:string require:1 desc:标题
	 * @param .name:link_cause type:int require:1 desc:分类
	 * @param .name:type type:int require:1 desc:类型
	 * @param .name:keyword type:string require:0 desc:关键字
	 * @return array|\think\Response|\think\response\Json
	 */
	public function save()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$this->knowledgeSaveCheck($param);
			if (!$param["id"]) {
				throw new \Exception(self::PARAM_ERROR);
			}
			$old_data = \think\Db::name("link_knowledge")->where("id", $param["id"])->find();
			if (!$old_data) {
				throw new \Exception(self::DATA_NOT_FOUND);
			}
			if (!\think\Db::name("link_cause")->where("id", $param["link_cause"])->find()) {
				throw new \Exception("分类信息不存在");
			}
			$data = ["title" => $param["title"], "link_cause" => $param["link_cause"], "type" => $param["type"], "status" => $param["status"] ?? 1, "module" => $param["module"] ?? "", "reply" => $param["reply"], "update_time" => time()];
			\think\Db::name("link_knowledge")->where("id", $param["id"])->update($data);
			return $this->saveKnowledgeKeywords($param["keyword"], $param["id"], "knowledge");
		});
	}
	/**
	 * @title 沟通能力-知识库创建
	 * @description 接口说明:沟通能力-知识库创建
	 * @author xue
	 * @url /admin/link_knowledge/create
	 * @method GET
	 * @return array|\think\Response|\think\response\Json
	 */
	public function create()
	{
		return $this->tryCatch(function () {
			return ["type" => $this->getKnowkedgeType()];
		});
	}
	/**
	 * @title 沟通能力-知识库创建保存
	 * @description 接口说明:沟通能力-知识库创建保存
	 * @author xue
	 * @url /admin/link_knowledge/add
	 * @method POST
	 * @param .name:title type:string require:1 desc:标题
	 * @param .name:link_cause type:int require:1 desc:分类
	 * @param .name:type type:int require:1 desc:类型
	 * @param .name:keyword type:string require:0 desc:关键字
	 * @return array|\think\Response|\think\response\Json
	 */
	public function add()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			$this->knowledgeSaveCheck($param);
			if (!\think\Db::name("link_cause")->where("id", $param["link_cause"])->find()) {
				throw new \Exception("分类信息不存在");
			}
			$data = ["title" => $param["title"], "link_cause" => $param["link_cause"], "type" => $param["type"], "status" => $param["status"] ?? 1, "module" => $param["module"] ?? "", "reply" => $param["reply"], "create_time" => time()];
			$id = \think\Db::name("link_knowledge")->insertGetId($data);
			return $this->saveKnowledgeKeywords($param["keyword"], $id, "knowledge");
		});
	}
	public function delete()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["id"]) {
				throw new \Exception(self::PARAM_ERROR);
			}
			return \think\Db::name("link_knowledge")->where("id", $param["id"])->delete();
		});
	}
	private function getKnowkedgeType()
	{
		return [["id" => 1, "name" => "文本回复"], ["id" => 2, "name" => "图片回复"]];
	}
	private function getLinkCause($cause_ids)
	{
		$data = \think\Db::name("link_cause")->whereIn("id", array_unique($cause_ids))->select()->toArray();
		if (!$data) {
			return [];
		}
		$parent_ids = [];
		foreach ($data as $key => $val) {
			$val["level_view"] = trim(trim($val["level_view"], ")"), "(");
			$data[$key]["level_view"] = explode(")-(", $val["level_view"]);
			$parent_ids = array_merge($parent_ids, $data[$key]["level_view"]);
		}
		$parent_data = \think\Db::name("link_cause")->whereIn("id", array_unique($parent_ids))->cursor();
		foreach ($parent_data as $k => $val) {
			$parent_data_arr[$val["id"]] = $val["name"];
		}
		foreach ($data as $k => $val) {
			$data[$key]["level_view"] = array_map(function ($v) use($parent_data_arr) {
				return $parent_data_arr[$v] ?? $v;
			}, $val["level_view"]);
		}
		return array_column($data, "level_view", "id");
	}
	private function saveKnowledgeKeywords($keywords, $id, $belong)
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
	private function knowledgeSaveCheck($param)
	{
		if (!$param["title"]) {
			throw new \Exception(self::CAUSE_TITLE_NOT_NULL);
		}
		if (!$param["link_cause"]) {
			throw new \Exception("请选择知识库分类");
		}
		if (!$param["type"]) {
			throw new \Exception("请选择知识库类型");
		}
		if (!$param["reply"]) {
			throw new \Exception("回复不能为空");
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
		return max(1, $this->request->limit ?? config("limit"));
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