<?php

namespace app\admin\controller;

/**
 * @title 后台新闻
 * @description 接口说明
 */
class NewsController extends AdminBaseController
{
	protected $upload_path = "";
	protected $upload_url = "";
	protected function initialize()
	{
		parent::initialize();
		$this->upload_url = $this->request->host() . "/upload/news/";
		$this->upload_path = CMF_ROOT . "/public/upload/news/";
	}
	/**
	 * @title 新闻列表页
	 * @description 接口说明:新闻列表页
	 * @param name:parent_id type:int  require:0  default:1 other: desc:父级id1=新闻公告2=帮助中心
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:search type:string  require:0  default: other: desc:搜索关键词
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:page type:int  require:0  default:asc other: desc:分页
	 * @param name:limit type:int  require:0  default:asc other: desc:页面展示数量
	 * @return list:新闻数据@
	 * @list  parent[]:新闻分类数据@
	 * @list  id:文章id
	 * @list  title:新闻标题
	 * @list  hidden:是否隐藏(1/0)
	 * @list  sort:排序id
	 * @return pagecount:每页显示条数
	 * @return page:当前页码
	 * @return search:搜索关键字
	 * @return orderby:排序字段
	 * @return sorting:asc/desc,顺序或倒叙
	 * @return total_page:总页码
	 * @return count:总新闻数量
	 * @author 萧十一郎
	 * @url /admin/news/list
	 * @method GET
	 */
	public function getList(\think\Request $request)
	{
		$param = $request->param();
		$orderby = $param["orderby"] ?? "push_time";
		$sorting = $param["sorting"] == "ASC" ? "ASC" : "DESC";
		$where = [];
		if (isset($param["search"][0])) {
			$where[] = ["title", "like", "%" . $param["search"] . "%"];
		}
		if (isset($param["status"])) {
			$where[] = ["status", "=", \intval($param["status"])];
		}
		if (isset($param["parent_id"]) && $param["parent_id"] > 0) {
			$tmp = \think\Db::name("news_type")->where([["parent_id", "=", $param["parent_id"]]])->select();
			if (isset($tmp[0]["id"])) {
				$tmp_ids = array_column($tmp->toArray(), "id");
				$tmp_ids[] = $param["parent_id"];
				$where[] = ["parent_id", "in", $tmp_ids];
			} else {
				$where[] = ["parent_id", "=", $param["parent_id"]];
			}
		}
		$news_menu = \think\Db::name("news_menu")->where($where)->order($orderby, $sorting)->page($this->page, $this->limit)->select()->toArray();
		$count = \think\Db::name("news_menu")->where($where)->count();
		$news_type = \think\Db::name("news_type")->select()->toArray();
		$tmp = array_column($news_type, null, "id");
		foreach ($news_menu as &$v) {
			$v["parent"] = $tmp[$v["parent_id"]] ?? (object) [];
		}
		$returndata = [];
		$url = $this->upload_url;
		$returndata["list"] = array_map(function ($v) use($url) {
			$v["head_img"] = isset($v["head_img"][0]) ? $url . $v["head_img"] : "";
			return $v;
		}, $news_menu);
		$returndata["limit"] = $this->limit;
		$returndata["page"] = $this->page;
		$returndata["param"] = $param;
		$returndata["orderby"] = $orderby;
		$returndata["sorting"] = $sorting;
		$returndata["total_page"] = ceil($count / $this->limit);
		$returndata["count"] = $count;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 新闻分类页面数据
	 * @description 接口说明:新闻分类页
	 * @param name:title type:string  require:0  default: other: desc:搜索标题
	 * @param name:status type:string  require:0  default: other: desc:搜索状态
	 * @param name:page type:int  require:0  default:asc other: desc:分页
	 * @param name:limit type:int  require:0  default:asc other: desc:页面展示数量
	 * @param name:parent_id type:int  require:0  default:asc other: desc:父级id
	 * @return list:分类数据@
	 * @list  id:分类id
	 * @list  parent_id:父级id
	 * @list  title:分类名
	 * @list  status:是否禁用(1/0)
	 * @list  sort:排序id
	 * @return meta:分页数据@
	 * @meta  limit:分页
	 * @meta  page:页码
	 * @meta  total:总数
	 * @author 萧十一郎
	 * @url /admin/news/catspage
	 * @method GET
	 */
	public function getCatsPage()
	{
		$param = $this->request->param();
		$where = [];
		if (isset($param["title"][0])) {
			$where[] = ["title", "like", "%" . $param["search"] . "%"];
		}
		if (isset($param["status"])) {
			$where[] = ["status", "=", \intval($param["status"])];
		}
		if (isset($param["parent_id"]) && $param["parent_id"] > 0) {
			$where[] = ["parent_id", "=", \intval($param["parent_id"])];
		}
		$list = \think\Db::name("news_type")->where($where)->page($this->page, $this->limit)->order(["id" => "DESC"])->select();
		$count = \think\Db::name("news_type")->where($where)->count();
		return jsonrule(["status" => 200, "data" => ["list" => $list, "meta" => ["total" => $count, "page" => $this->page, "limit" => $this->limit]]]);
	}
	/**
	 * @title 新闻分类所有数据
	 * @description 接口说明:新闻分类所有数据
	 * @param name:parent_id type:int  require:0  default: other: desc:父级id，默认获取所有分页数据，此参数传0获取所有顶级分类
	 * @param name:status type:string  require:0  default: other: desc:搜索状态
	 * @return list:分类数据@
	 * @list  id:分类id
	 * @list  parent_id:父级id
	 * @list  title:分类名
	 * @list  status:是否禁用(1/0)
	 * @list  sort:排序id
	 * @list  list:子集数据
	 * @author 萧十一郎
	 * @url /admin/news/catelist
	 * @method GET
	 */
	public function getCateList()
	{
		$param = $this->request->param();
		$where = [];
		if (isset($param["status"])) {
			$where[] = ["status", "=", \intval($param["status"])];
		}
		if (isset($param["parent_id"])) {
			$where[] = ["parent_id", "=", \intval($param["parent_id"])];
		}
		$list = \think\Db::name("news_type")->where($where)->order(["parent_id" => "ASC", "id" => "DESC"])->select()->toArray();
		$data = [];
		foreach ($list as $v) {
			if ($v["parent_id"] > 0 && isset($data[$v["parent_id"]])) {
				$data[$v["parent_id"]]["list"][$v["id"]] = $v;
			} else {
				$data[$v["id"]] = $v;
				$data[$v["id"]]["list"] = [];
			}
		}
		return jsonrule(["status" => 200, "data" => array_values($data)]);
	}
	/**
	 * @title 获取分类id数据
	 * @description 接口说明:编辑分类页面使用
	 * @param .name:id type:int  require:1  default: other: desc:分类id，传递时返回该分类数据
	 * @return id:分类id
	 * @return title:分类名
	 * @return parent_id:父级id
	 * @return status:1=正常0=禁用
	 * @return sort:排序号
	 * *@author 萧十一郎
	 * @url /admin/news/editcat
	 * @method GET
	 */
	public function getCatData(\think\Request $request)
	{
		$id = $request->param("id");
		if (empty($id)) {
			return jsonrule(["status" => 406, "msg" => Lang("ID ERROR")]);
		}
		$data = \think\Db::name("news_type")->where("id", $id)->find();
		if (empty($data)) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_EXIST")]);
		}
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 添加/编辑分类
	 * @description 接口说明:保存添加/编辑分类数据
	 * @param .name:id type:int  require:0  default: other: desc:分类id，传递时为编辑，为空时为添加
	 * @param .name:title type:string  require:1 default: other: desc:分类名称
	 * @param .name:parent_id type:int  require:0  default: other: desc:父级分类id顶级为0
	 * @param .name:status type:int  require:0  default:0 other: desc:是否禁用分类
	 * @param .name:hidden type:int  require:0  default:0 other: desc:是否隐藏1=是0否
	 * @param .name:sort type:int  require:0  default:0 other: desc:排序号
	 * @author 萧十一郎
	 * @url /admin/news/editcat
	 * @method POST
	 */
	public function postEditCat(\think\Request $request)
	{
		$param = $request->param();
		if (isset($param["id"]) && \intval($param["id"]) == 0) {
			unset($param["id"]);
		}
		if (isset($param["id"]) && $param["id"] < 3) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_DELETE")]);
		}
		$validate = new \app\admin\validate\NewsTypeValidate();
		if (!$validate->check($param)) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data = ["title" => $param["title"], "parent_id" => $param["parent_id"] ?? 0, "hidden" => $param["hidden"] ?? 0, "status" => $param["status"] ?? 1, "sort" => $param["sort"] ?? 0];
		if ($param["alias"]) {
			$alias = str_replace(["/", "\\"], "", $param["alias"]);
			$model = \think\Db::name("news_type")->where("alias", $alias);
			if ($param["id"]) {
				$model = $model->where("id", "<>", $param["id"]);
			}
			$model = $model->find();
			if ($model) {
				return jsonrule(["status" => 406, "msg" => lang("ALIAS_IS_USE_ERROR")]);
			}
			$data["alias"] = $alias;
		}
		if (!empty($param["id"])) {
			$menu_type = \think\Db::name("news_type")->where("id", $param["id"])->find();
			if (!isset($menu_type["id"])) {
				return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_EXIST")]);
			}
			\think\Db::name("news_type")->where("id", $param["id"])->update($data);
			if ($param["hidden"] == 1) {
				active_log(sprintf($this->lang["News_admin_postEditCat1"], $param["id"], $menu_type["title"], $param["title"]));
			} else {
				active_log(sprintf($this->lang["News_admin_postEditCat2"], $param["id"], $menu_type["title"], $param["title"]));
			}
		} else {
			$nt = \think\Db::name("news_type")->insertGetId($data);
			active_log(sprintf($this->lang["News_admin_postaddCat"], $nt));
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
	}
	/**
	 * @title 验证新闻别名
	 * @description 接口说明:验证别名
	 * @author xue
	 * @url /admin/news/checkalias
	 * @method get
	 * @param .name:alias type:string require:1 default: other: desc:别名
	 */
	public function getCheckalias(\think\Request $request)
	{
		$param = $request->param();
		$alias = trim($param["alias"]);
		if (!$alias) {
			return json(["status" => 400, "msg" => "别名不能为空"]);
		}
		$model = \think\Db::name("news_type")->field("id")->where("alias", $alias);
		if ($param["id"]) {
			$model = $model->where("id", "<>", $param["id"]);
		}
		$model = $model->find();
		return json(["status" => 200, "msg" => "success", "data" => $model ? 0 : 1]);
	}
	/**
	 * @title 删除分类
	 * @description 接口说明:删除分类将删除分类下的所有数据
	 * @param .name:id type:int  require:0  default: other: desc:分类id
	 * @author 萧十一郎
	 * @url /admin/news/cat
	 * @method DELETE
	 */
	public function deleteCat(\think\Request $request)
	{
		$id = $request->param("id");
		if (empty($id)) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_EXIST")]);
		}
		if ($id <= 2) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_DELETE")]);
		}
		$tmp = \think\Db::name("news_type")->where("id", $id)->find();
		if (!isset($tmp["id"])) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_EXIST")]);
		}
		$news_data = \think\Db::name("news_menu")->where("parent_id", $id)->select()->toArray();
		if (isset($news_data["id"])) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_DELETE_ERROR")]);
		}
		$news_type = \think\Db::name("news_type")->where("parent_id", $id)->select()->toArray();
		if (isset($news_type["id"])) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_DELETE_ERROR_1")]);
		}
		try {
			\think\Db::name("news_type")->where("id", $id)->delete();
			active_log(sprintf($this->lang["News_admin_deletecat"], $id));
		} catch (\Exception $e) {
			return jsonrule(["status" => 406, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 编辑新闻页面数据
	 * @description 接口说明:新闻页面数据，传递id时返回文章，和组数据
	 * @param .name:id type:int  require:0  default: other: desc:新闻id
	 * @return new_content:新闻数据
	 * @new_content  id:新闻id
	 * @new_content  parent_id:新闻分类id
	 * @new_content  title:新闻标题
	 * @new_content  keywords:新闻关键字
	 * @new_content  description:新闻描述
	 * @new_content  read:新闻阅读量
	 * @new_content  head_img:新闻封面图
	 * @new_content  content:新闻内容(转义后)
	 * @new_content  hidden:是否隐藏
	 * @new_content  sort:排序
	 * @return cat_data:分类数据
	 * @cat_data  id:分类id
	 * @cat_data  title:分类名称
	 * @author 萧十一郎
	 * @url /admin/news/content
	 * @method GET
	 */
	public function getContent(\think\Request $request)
	{
		$id = \intval($request->param("id", 0));
		if (!$id) {
			return jsonrule(["status" => 200, "data" => []]);
		}
		$new_data = \think\Db::name("news_menu")->find($id);
		if (empty($new_data)) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_NOT_EXIST")]);
		}
		$content = \think\Db::name("news")->where("relid", $id)->find();
		$new_data["content"] = htmlspecialchars_decode($content["content"]);
		return jsonrule(["status" => 200, "data" => $new_data]);
	}
	/**
	 * @title 添加/编辑新闻
	 * @description 接口说明:保存新闻数据
	 * @param .name:id type:int  require:0  default: other: desc:新闻id，传递时为编辑，为空时为添加
	 * @param .name:parent_id type:int  require:0  default: other: desc:分类id，不能为0
	 * @param .name:title type:string  require:1 default: other: desc:新闻名称
	 * @param .name:keywords type:string  require:0 default: other: desc:新闻关键字
	 * @param .name:label type:string  require:0 default: other: desc:标签
	 * @param .name:description type:string  require:0  default: other: desc:新闻描述
	 * @param .name:read type:int  require:0  default: other: desc:阅读量
	 * @param .name:head_img type:file  require:0  default: other: desc:封面图（没有读取内容，内容没有随机）
	 * @param .name:content type:string  require:0  default: other: desc:新闻内容
	 * @param .name:hidden type:int  require:0  default:0 other: desc:是否隐藏
	 * @param .name:sort type:int  require:0  default:0 other: desc:排序号
	 * @author 萧十一郎
	 * @url /admin/news/editcontent
	 * @method POST
	 */
	public function postEditContent(\think\Request $request)
	{
		$param = $request->param();
		$rule = ["id" => "number", "parent_id" => "require|number", "title" => "require|length:1,30", "read" => "number", "hidden" => "in:0,1", "sort" => "number"];
		$msg = ["parent_id.require" => "分类id不能为空", "title.require" => "新闻标题不能为空", "title.length" => "新闻标题为1到30的字符"];
		$validate = new \think\Validate($rule, $msg);
		$res = $validate->check($param);
		if (!$res) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		if (!empty($param["id"])) {
			$new_data = \think\Db::name("news_menu")->where("id", $param["id"])->find();
			if (empty($new_data)) {
				return jsonrule(["status" => 406, "msg" => lang("NEW_NOT_EXIST")]);
			}
		}
		if (empty($param["parent_id"])) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_TYPE_NOT_EXIST")]);
		}
		if (isset($param["head_img"][0])) {
			$upload = new \app\common\logic\Upload();
			$ret = $upload->moveTo($param["head_img"], $this->upload_path);
			if (isset($ret["error"])) {
				return ["status" => 400, "msg" => lang("IMAGE_UPLOAD_FAILED")];
			}
		}
		$id = $param["id"];
		$content = htmlspecialchars($param["content"]);
		$news_menu_data = ["parent_id" => $param["parent_id"], "admin_id" => cmf_get_current_admin_id(), "title" => $param["title"], "label" => $param["label"], "keywords" => $param["keywords"] ?? "", "description" => $param["description"] ?? "", "read" => $param["read"] ?? 0, "head_img" => $param["head_img"] ?? "", "hidden" => $param["hidden"] ?? 0, "sort" => $param["sort"] ?? 0, "push_time" => $param["push_time"] ?? time()];
		$new_content["content"] = $content;
		\think\Db::startTrans();
		try {
			if (!empty($id)) {
				$news_menu_data["update_time"] = time();
				$news = \think\Db::name("news")->field("content")->where("relid", $id)->find();
				$newsm = \think\Db::name("news_menu")->where("id", $id)->find();
				\think\Db::name("news_menu")->where("id", $id)->update($news_menu_data);
				\think\Db::name("news")->where("relid", $id)->update($new_content);
				$dec = "";
				if (!empty($param["title"]) && $param["title"] != $newsm["title"]) {
					$dec .= "标题由“" . $newsm["title"] . "“改为”" . $param["title"] . "”，";
				}
				if (!empty($param["parent_id"]) && $param["parent_id"] != $newsm["parent_id"]) {
					$dec .= "分类由“" . $newsm["parent_id"] . "“改为”" . $param["parent_id"] . "”，";
				}
				if ($param["hidden"] != $newsm["hidden"]) {
					if ($param["hidden"] == 1) {
						$dec .= "由“显示”改为“隐藏”，";
					} else {
						$dec .= "由“隐藏”改为“显示”，";
					}
				}
				if (empty($dec)) {
					$dec .= "什么都没有修改";
				}
				active_log(sprintf($this->lang["News_admin_postEditContent"], $id, $dec));
				unset($dec);
			} else {
				$news_menu_data["create_time"] = time();
				$new_id = \think\Db::name("news_menu")->insertGetId($news_menu_data);
				$new_content["relid"] = $new_id;
				\think\Db::name("news")->insertGetId($new_content);
				active_log(sprintf($this->lang["News_admin_postaddContent"], $new_id, $param["title"]));
			}
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			if (!empty($file_name)) {
				if (\file_exists($this->upload_path . $file_name)) {
					@unlink($this->upload_path . $file_name);
				}
			}
			return jsonrule(["status" => 406, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title 删除新闻
	 * @description 接口说明:删除分类将删除分类下的所有数据
	 * @param .name:id type:int  require:0  default: other: desc:新闻id
	 * @author 萧十一郎
	 * @url /admin/news/content
	 * @method DELETE
	 */
	public function deleteContent(\think\Request $request)
	{
		$id = $request->param("id");
		$id = intval($id);
		if (empty($id)) {
			return jsonrule(["status" => 406, "msg" => lang("ID_ERROR")]);
		}
		$tmp = \think\Db::name("news_menu")->where("id", "=", $id)->find();
		if (!isset($tmp["id"])) {
			return jsonrule(["status" => 406, "msg" => lang("NEW_NOT_EXIST")]);
		}
		\think\Db::name("news_menu")->delete($id);
		active_log(sprintf($this->lang["News_admin_delete"], $id, $tmp["title"]));
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 获取站务自定义字段列表
	 * @description 接口说明:获取站务自定义字段列表
	 * @param .name:page type:int  require:0  default: 1: desc: 页码
	 * @param .name:limit type:int  require:0  default: 10: desc: 偏移量
	 * @author xue
	 * @url /admin/news/getCustomParam
	 * @method get
	 */
	public function getGetCustomParam(\think\Request $request)
	{
		try {
			$model = \think\Db::name("customfields")->field("id,fieldname")->where("type", "depot");
			$count = $model->count();
			$model = $model->page($this->page, $this->limit)->order("id", "desc")->select()->toArray();
			if (!$model) {
				return jsonrule(["status" => 200, "msg" => "success", "data" => ["count" => 0, "list" => []]]);
			}
			$ids = array_column($model, "id");
			$vals_data = \think\Db::name("customfieldsvalues")->whereIn("fieldid", $ids)->select()->toArray();
			$vals = array_column($vals_data, "value", "fieldid");
			foreach ($model as $key => $val) {
				$model[$key]["value"] = $vals[$val["id"]];
			}
			return jsonrule(["status" => 200, "msg" => "success", "data" => ["count" => $count, "list" => $model]]);
		} catch (\Throwable $e) {
			echo $e->getLine();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 添加站务自定义字段
	 * @description 接口说明:添加站务自定义字段
	 * @param .name:fieldname type:string  require:1  default: : desc: 字段名
	 * @param .name:value type:string  require:1  default: : desc: 字段值
	 * @author xue
	 * @url /admin/news/addCustomParam
	 * @method get
	 */
	public function getAddCustomParam(\think\Request $request)
	{
		\think\Db::startTrans();
		try {
			$param = $request->param();
			$this->checkCustomParam($request);
			$data = ["type" => "depot", "relid" => 0, "fieldname" => $param["fieldname"], "fieldtype" => "text", "description" => "站务自定义字段", "create_time" => time(), "update_time" => time()];
			$fields_id = \think\Db::name("customfields")->insertGetId($data);
			\think\Db::name("customfieldsvalues")->insert(["fieldid" => $fields_id, "relid" => 0, "value" => $param["value"]]);
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} catch (\Throwable $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 修改站务自定义字段
	 * @description 接口说明:修改站务自定义字段
	 * @param .name:id type:int  require:1  default: : desc: id
	 * @param .name:fieldname type:string  require:1  default: : desc: 字段名
	 * @param .name:value type:string  require:1  default: : desc: 字段值
	 * @author xue
	 * @url /admin/news/updateCustomParam
	 * @method get
	 */
	public function getUpdateCustomParam(\think\Request $request)
	{
		\think\Db::startTrans();
		try {
			$param = $request->param();
			if (!$request->param("id")) {
				throw new \think\Exception("id不能为空");
			}
			$this->checkCustomParam($request);
			$data = ["fieldname" => $param["fieldname"], "update_time" => time()];
			\think\Db::name("customfields")->where("type", "depot")->where("id", $param["id"])->update($data);
			\think\Db::name("customfieldsvalues")->where("fieldid", $param["id"])->update(["value" => $param["value"]]);
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		} catch (\Throwable $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 删除站务自定义字段的值
	 * @description 接口说明:删除站务自定义字段的值
	 * @param .name:id type:int  require:1  default: : desc: id
	 * @author xue
	 * @url /admin/news/delCustomParam
	 * @method get
	 */
	public function getDelCustomParam(\think\Request $request)
	{
		\think\Db::startTrans();
		try {
			$param = $request->param();
			\think\Db::name("customfields")->where("id", $param["id"])->where("type", "depot")->delete();
			\think\Db::name("customfieldsvalues")->where("fieldid", $param["id"])->delete();
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} catch (\Throwable $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 获取要修改的站务自定义字段的值
	 * @description 接口说明:获取要修改的站务自定义字段的值
	 * @param .name:id type:int  require:1  default: : desc: id
	 * @author xue
	 * @url /admin/news/getCustomUpdateVal
	 */
	public function getGetCustomUpdateVal(\think\Request $request)
	{
		try {
			$param = $request->param();
			$model = \think\Db::name("customfields")->where("id", intval($param["id"]))->find();
			if (!$model) {
				throw new \think\Exception("记录不存在");
			}
			$value = \think\Db::name("customfieldsvalues")->where("fieldid", intval($param["id"]))->find();
			if (!$value) {
				throw new \think\Exception("记录值不存在");
			}
			$data = ["id" => intval($param["id"]), "field" => $model["fieldname"], "value" => htmlspecialchars_decode($value["value"])];
			return jsonrule(["status" => 200, "msg" => "success", "data" => $data]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	private final function checkCustomParam(\think\Request $request, $e = "")
	{
		if (!trim($request->param("fieldname"))) {
			throw new \think\Exception("自定义字段不能为空");
		}
		if (!trim($request->param("value"))) {
			throw new \think\Exception("自定义字段的值不能为空");
		}
		$model = \think\Db::name("customfields")->where(["fieldname" => $request->param("fieldname"), "type" => "depot"]);
		if (intval($request->param("id"))) {
			$model = $model->where("id", "<>", $request->param("id"));
		}
		$model = $model->find();
		if ($model) {
			if ($e) {
				throw new \think\Exception($e);
			}
			throw new \think\Exception("该自定义字段已存在");
		}
	}
	private final function getCustomId()
	{
		return \think\Db::name("customfields")->field("fieldname,id")->where("type", "depot")->page($this->page, $this->limit)->select()->order("id", "desc")->toArray();
	}
}