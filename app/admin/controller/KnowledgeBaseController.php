<?php

namespace app\admin\controller;

/**
 * @title 后台帮助中心
 * @description 接口说明
 */
class KnowledgeBaseController extends AdminBaseController
{
	private $imagesave = "../public/upload/admin/knowledge/";
	private $getimage = WEB_ROOT . "upload/admin/knowledge/";
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->validate = new \app\admin\validate\KnowledgeBaseValidate();
	}
	/**
	 * @title 首页
	 * @description 接口说明:首页
	 * @author wyh
	 * @url /admin/knowledge_base/index
	 * @method GET
	 * @return  categories:文章种类@
	 * @categories  id:种类ID
	 * @categories  name:种类名称
	 * @categories  description:种类描述
	 * @categories  hidden:0显示，1隐藏
	 * @categories  num:文章数量(当前种类下)
	 * @return  tags:标签@ (标签云形式)
	 * @tags  tag:标签名称
	 * @tags  num:标签数量
	 */
	public function index()
	{
		$param = $this->request->param();
		$order = isset($param["order"][0]) ? trim($param["order"]) : "id";
		$sort = isset($param["sort"][0]) ? trim($param["sort"]) : "DESC";
		$categories = \think\Db::name("knowledge_base_links")->alias("kbl")->field("kbc.id,name,description,hidden,count(kbl.category_id) as num")->leftJoin("knowledge_base_cats kbc", "kbl.category_id = kbc.id")->group("kbl.category_id")->order($order, $sort)->select();
		$tags = \think\Db::name("knowledge_base_tags")->field("tag,count(tag) as num")->group("tag")->select();
		foreach ($tags as $key => $tag) {
			$tags[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $tag);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "categories" => $categories, "tags" => $tags]);
	}
	/**
	 * @title 按种类ID取文章数据
	 * @description 接口说明:按种类ID取文章数据
	 * @author wyh
	 * @url /admin/knowledge_base/category_list/:cid
	 * @method GET
	 * @return  article:文章@
	 * @article  id:文章ID
	 * @article  title:标题
	 * @article  article:内容
	 * @article  views:查看次数
	 * @article  useful:点赞次数
	 * @article  hidden:是否隐藏:0默认显示,1隐藏(单选框)
	 * @article  login_view：登录查看:0默认所有人都可以看，1登录才能查看(单选框)
	 * @article  host_view：是否有激活产品才能查看:0默认所有人可看，1有激活产品才能查看(单选框)
	 * @article  order:排序
	 * @article  tag:标签
	 * @article  public_by:发布人(文本框)
	 * @article  public_time:发布时间(文本框,选择时间)
	 */
	public function categoryList()
	{
		$params = $this->request->param();
		$cid = isset($params["cid"]) && !empty($params["cid"]) ? intval($params["cid"]) : "";
		if (!$cid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$article = \think\Db::name("knowledge_base_links")->alias("kbl")->field("kb.id,title,article,views,useful,kb.hidden,login_view,host_view,order,public_by,public_time")->leftJoin("knowledge_base_cats kbc", "kbc.id = kbl.category_id")->leftJoin("knowledge_base kb", "kb.id = kbl.article_id")->where("category_id", $cid)->select();
		foreach ($article as $key => $value) {
			$value["tag"] = $this->getTagString($value["id"]);
			$value["article"] = mb_substr($value["article"], 0, 20);
			$value = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
			$article[$key] = $value;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "article" => $article]);
	}
	protected function getTagString($articleId)
	{
		$tags = \think\Db::name("knowledge_base_tags")->field("tag")->where("article_id", $articleId)->select();
		if (empty($tags[0])) {
			return "";
		} else {
			$tagstring = "";
			foreach ($tags as $tag) {
				if ($tagstring) {
					$tagstring .= "," . $tag["tag"];
				} else {
					$tagstring .= $tag["tag"];
				}
			}
			return $tagstring;
		}
	}
	/**
	 * @title 按标签取数据
	 * @description 接口说明:按标签取数据
	 * @author wyh
	 * @url /admin/knowledge_base/tags_list
	 * @method POST
	 * @param .name:tag type:string require:1 default:1 other: desc:可选参数：标签名称
	 * @return  article:文章@
	 * @article  id:文章ID
	 * @article  title:标题
	 * @article  article:内容
	 * @article  views:查看次数
	 * @article  useful:点赞次数
	 * @article  hidden:是否隐藏:0默认显示,1隐藏(单选框)
	 * @article  login_view：登录查看:0默认所有人都可以看，1登录才能查看(单选框)
	 * @article  host_view：是否有激活产品才能查看:0默认所有人可看，1有激活产品才能查看(单选框)
	 * @article  order:排序
	 * @article  tag:标签
	 * @article  public_by:发布人(文本框)
	 * @article  public_time:发布时间(文本框,选择时间)
	 */
	public function tagsList()
	{
		$params = $this->request->param();
		$tag = trim($params["tag"]);
		$article = \think\Db::name("knowledge_base_tags")->alias("kbt")->field("kb.id,title,article,views,useful,kb.hidden,login_view,host_view,order,public_by,public_time")->leftJoin("knowledge_base kb", "kb.id = kbt.article_id")->where("tag", $tag)->select();
		foreach ($article as $key => $value) {
			$value["tag"] = $this->getTagString($value["id"]);
			$value["article"] = mb_substr($value["article"], 0, 20);
			$value = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
			$article[$key] = $value;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "article" => $article]);
	}
	/**
	 * @title 添加文章种类
	 * @description 接口说明:添加文章种类
	 * @author wyh
	 * @url /admin/knowledge_base/add_category
	 * @method POST
	 * @param .name:name type:string require:1 default:1 other: desc:种类名称
	 * @param .name:description type:string require:1 default:1 other: desc:种类描述
	 * @param .name:hidden type:int require:1 default:1 other: desc:0默认显示，1隐藏
	 * @return .cid:种类ID
	 */
	public function addCategory()
	{
		if ($this->request->isPost()) {
			$data = $this->request->only("id,name,description,hidden");
			if (!$this->validate->scene("edit_category")->check($data)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$data["description"] = isset($data["description"]) ? $data["description"] : "";
			$data = array_map("trim", $data);
			$categoryid = \think\Db::name("knowledge_base_cats")->insertGetId($data);
			if ($categoryid) {
				return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "cid" => $categoryid]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 添加文章
	 * @description 接口说明:添加文章
	 * @author wyh
	 * @url /admin/knowledge_base/add_category
	 * @method POST
	 * @param .name:cid type:int require:1 default:1 other: desc:种类ID
	 * @param .name:title type:string require:1 default:1 other: desc:
	 * @return .aid:文章ID
	 */
	public function addArticle()
	{
		if ($this->request->isPost()) {
			$data = $this->request->param();
			$cid = isset($data["cid"]) && !empty($data["cid"]) ? intval($data["cid"]) : "";
			if (!$cid) {
				return jsonrule(["status" => 400, "msg" => lang("KNOWLEDGE_CATEGORY_EMPTY")]);
			}
			if (!isset($data["title"])) {
				return jsonrule(["status" => 400, "msg" => lang("KNOWLEDGE_ARTICLE_EMPTY")]);
			}
			$add = [];
			$add["title"] = htmlspecialchars(trim($data["title"]));
			$add["hidden"] = 1;
			\think\Db::startTrans();
			try {
				$aid = \think\Db::name("knowledge_base")->insertGetId($add);
				$link = [];
				$link["category_id"] = $cid;
				$link["article_id"] = $aid;
				\think\Db::name("knowledge_base_links")->insertGetId($link);
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL") . $e->getMessage()]);
			}
			return jsonrule(["status" => 400, "msg" => lang("ADD SUCCESS"), "aid" => $aid]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑文章页面
	 * @description 接口说明: 编辑文章页面
	 * @author wyh
	 * @url /admin/knowledge_base/edit_article/:id
	 * @method GET
	 * @param .name:id type:int require:0 default:1 other: desc:文章ID
	 * @return  article:文章信息@
	 * @article  id:文章ID
	 * @article  title:标题
	 * @article  article:内容
	 * @article  views:查看次数
	 * @article  useful:点赞次数
	 * @article  hidden:是否隐藏:0默认显示,1隐藏(单选框)
	 * @article  login_view：登录查看:0默认所有人都可以看，1登录才能查看(单选框)
	 * @article  host_view：是否有激活产品才能查看:0默认所有人可看，1有激活产品才能查看(单选框)
	 * @article  order:排序
	 * @article  tag:标签
	 * @article  public_by:发布人(文本框)
	 * @article  public_time:发布时间(文本框,选择时间)
	 * @return  category:种类列表@
	 * @category  id:种类ID
	 * @category  name:种类名称
	 * @category  description:种类描述
	 * @return  .cid:已选种类
	 */
	public function editArticle()
	{
		$data = $this->request->param();
		$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$article = \think\Db::name("knowledge_base")->field("id,title,article,views,useful,hidden,login_view,host_view,order,public_by,public_time")->where("id", $id)->find();
		$category = \think\Db::name("knowledge_base_cats")->where("hidden", 0)->select();
		$cid = \think\Db::name("knowledge_base_links")->field("category_id")->where("article_id", $id)->select();
		$article["tag"] = $this->getTagString($id);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "article" => $article, "category" => $category, "cid" => $cid]);
	}
	/**
	 * @title 编辑文章页面提交
	 * @description 接口说明: 编辑文章页面提交
	 * @author wyh
	 * @url /admin/knowledge_base/edit_article_post
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:文章id
	 * @param .name:title type:string require:1 default:1 other: desc:标题
	 * @param .name:article type:string require:1 default:1 other: desc:内容(富文本编辑)
	 * @param .name:categories[] type:array require:1 default:1 other: desc:种类，多选数组传值
	 * @param .name:views type:int require:1 default:1 other: desc:查看次数
	 * @param .name:useful type:int require:1 default:1 other: desc:点赞次数
	 * @param .name:hidden type:int require:1 default:1 other: desc:是否隐藏:0默认显示,1隐藏
	 * @param .name:login_view type:int require:1 default:1 other: desc:登录查看:0默认所有人都可以看，1登录才能查看
	 * @param .name:host_view type:int require:1 default:1 other: desc:是否有激活产品才能查看:0默认所有人可看，1有激活产品才能查看
	 * @param .name:order type:int require:1 default:1 other: desc:排序
	 * @param .name:tag type:string require:0 default:1 other: desc:标签,','隔开
	 * @param .name:public_by type:string require:1 default:1 other: desc:发布人(文本框)
	 * @param .name:public_time type:string require:1 default:1 other: desc:发布时间(文本框,选择时间)
	 */
	public function editArticlePost()
	{
		if ($this->request->isPost()) {
			$data = $this->request->only("id,title,article,categories,views,useful,hidden,login_view,host_view,order,tag,public_by,public_time");
			$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if (!$this->validate->scene("edit_article")->check($data)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$tag = isset($data["tag"]) && !empty($data["tag"]) ? trim($data["tag"]) : "";
			if ($tag) {
				$tags = explode(",", $tag);
			}
			$categories = $data["categories"];
			unset($data["tag"]);
			unset($data["categories"]);
			$data["create_by"] = cmf_get_current_admin_id();
			$data["create_time"] = time();
			\think\Db::startTrans();
			try {
				\think\Db::name("knowledge_base")->where("id", $id)->update($data);
				\think\Db::name("knowledge_base_links")->where("article_id", $id)->delete();
				foreach ($categories as $category) {
					$link["article_id"] = $id;
					$link["category_id"] = $category;
					\think\Db::name("knowledge_base_links")->insertGetId($link);
				}
				\think\Db::name("knowledge_base_tags")->where("article_id", $id)->delete();
				if ($tags) {
					foreach ($tags as $tag) {
						$taglink["article_id"] = $id;
						$taglink["tag"] = htmlspecialchars(trim($tag));
						\think\Db::name("knowledge_base_tags")->insertGetId($taglink);
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("EDIT FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除文章
	 * @description 接口说明: 删除文章
	 * @author wyh
	 * @url /admin/knowledge_base/delete_article/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:文章id
	 */
	public function deleteArticle()
	{
		$data = $this->request->param();
		$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("knowledge_base")->where("id", $id)->delete();
			\think\Db::name("knowledge_base_links")->where("article_id", $id)->delete();
			\think\Db::name("knowledge_base_tags")->where("article_id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 编辑种类页面
	 * @description 接口说明: 编辑种类页面
	 * @author wyh
	 * @url /admin/knowledge_base/edit_category/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:种类id
	 * @return  category:文章种类信息@
	 * @category  name:名称
	 * @category  description:描述
	 * @category  hidden:0默认显示，1隐藏
	 */
	public function editCategory()
	{
		$data = $this->request->param();
		$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$category = \think\Db::name("knowledge_base_cats")->where("id", $id)->find();
		$category = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $category);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "category" => $category]);
	}
	/**
	 * @title 编辑种类页面提交
	 * @description 接口说明: 编辑种类页面提交
	 * @author wyh
	 * @url /admin/knowledge_base/edit_category_psot
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:种类id
	 * @param .name:name type:string require:1 default:1 other: desc:名称
	 * @param .name:description type:string require:1 default:1 other: desc:描述
	 * @param .name:hidden type:int require:1 default:1 other: desc:0默认显示，1隐藏
	 */
	public function editCategoryPost()
	{
		if ($this->request->isPost()) {
			$data = $this->request->only("id,name,description,hidden");
			$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if (!$this->validate->scene("edit_category")->check($data)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$data["description"] = isset($data["description"]) ? $data["description"] : "";
			$data = array_map("trim", $data);
			$result = \think\Db::name("knowledge_base_cats")->update($data);
			if ($result) {
				return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			}
			return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除文章种类
	 * @description 接口说明: 删除文章种类
	 * @author wyh
	 * @url /admin/knowledge_base/delete_category/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:种类id
	 */
	public function deleteCategory()
	{
		$data = $this->request->param();
		$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("knowledge_base_cats")->where("id", $id)->delete();
			$articleids = \think\Db::name("knowledge_base_links")->field("article_id")->where("category_id", $id)->select();
			foreach ($articleids as $articleid) {
				$aid = $articleid["id"];
				\think\Db::name("knowledge_base_tags")->where("article_id", $aid)->delete();
				\think\Db::name("knowledge_base")->where("id", $aid)->delete();
			}
			\think\Db::name("knowledge_base_links")->where("category_id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 富文本上传图片
	 * @description 接口说明: 富文本上传图片
	 * @author wyh
	 * @url /admin/knowledge_base/upload
	 * @method POST
	 * @param .name:file[] type:int require:1 default:1 other: desc:文件(数组)多文件上传
	 */
	public function uploadHandle()
	{
		if ($this->request->isPost()) {
			$files = $this->request->file("file");
			$re = $savenames = [];
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
				$info = $file->rule("uniqid")->move($this->imagesave, md5(uniqid()) . time() . $originalName);
				if ($info) {
					if (!isset($savename)) {
						$savename = $info->getSaveName();
					} else {
						$savename = $savename . "," . $info->getSaveName();
					}
					array_push($savenames, $this->getimage . $info->getSaveName());
					$re["savename_array"] = $savenames;
					$re["status"] = 200;
					$re["msg"] = lang("SUCCESS MESSAGE");
					$re["savename"] = $savename;
				} else {
					$re["status"] = 400;
					$re["msg"] = $file->getError();
				}
			}
			if (isset($re["savename"])) {
				unset($re["savename"]);
			}
			return $re;
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
}