<?php

namespace app\home\controller;

/**
 * @title 前台帮助中心
 * @description 接口说明
 */
class xKnowledgeBaseController extends CommonController
{
	/**
	 * @title 首页(默认显示第一个种类的文章)
	 * @description 接口说明:首页
	 * @author wyh
	 * @url /admin/knowledge_base/index
	 * @method GET
	 * @param .name:id type:int require:0 default:1 other: desc:种类ID(可选参数)
	 * @return  categories:文章种类@
	 * @categories  id:种类ID
	 * @categories  name:种类名称
	 * @categories  description:种类描述
	 * @categories  num:文章数量(当前种类下)
	 * @return  tags:标签@ (标签云形式)
	 * @tags  tag:标签名称
	 * @tags  num:标签数量
	 * @return  article:文章@
	 * @article  id:文章ID
	 * @article  title:标题
	 * @article  article:内容
	 * @article  views:查看次数
	 * @article  useful:点赞次数
	 * @article  public_by:发布人
	 * @article  public_time:发布时间
	 */
	public function index()
	{
		$data = $this->request->param();
		$categories = \think\Db::name("knowledge_base_links")->alias("kbl")->field("kbc.id,name,description,count(kbl.category_id) as num")->leftJoin("knowledge_base_cats kbc", "kbl.category_id = kbc.id")->where("")->where("hidden", 0)->group("kbl.category_id")->select();
		$tags = \think\Db::name("knowledge_base_tags")->field("tag,count(tag) as num")->group("tag")->select();
		foreach ($tags as $key => $tag) {
			$tags[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $tag);
		}
		if (isset($data["id"]) && !empty($data["id"])) {
			$cid = $data["id"];
		} else {
			$cid = $categories[0]["id"];
		}
		$article = \think\Db::name("knowledge_base_links")->alias("kbl")->field("kb.id,title,article,views,useful,public_by,public_time")->leftJoin("knowledge_base kb", "kbl.article_id = kb.id")->leftJoin("knowledge_base_cats kbc", "kbl.category_id = kbc.id")->where("kbc.hidden", 0)->where("kb.hidden", 0)->where("kbl.category_id", $cid)->where(function (\think\db\Query $query) {
			$uid = request()->uid;
			if (!$uid) {
				$query->where("login_view", 0);
			}
			$hostcount = \think\Db::name("host")->where("domainstatus", "Active")->where("uid", $uid)->count();
			if (!$hostcount) {
				$query->where("host_view", 0);
			}
		})->order("kb.order asc")->select();
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "categories" => $categories, "tags" => $tags, "article" => $article]);
	}
	/**
	 * @title 按文章进行搜索
	 * @description 接口说明:按文章进行搜索
	 * @author wyh
	 * @url /admin/knowledge_base/search
	 * @method POST
	 * @param .name:keyword type:string require:1 default:1 other: desc:搜素关键字
	 */
	public function searchArticle()
	{
		if ($this->request->isPost()) {
			$keyword = trim($this->request->param("keyword"));
			$result = \think\Db::name("knowledge_base")->field("id,title,article,views,useful,public_by,public_time")->where("title|article", "like", "%{$keyword}%")->where("hidden", 0)->where(function (\think\db\Query $query) {
				$uid = request()->uid;
				if (!$uid) {
					$query->where("login_view", 0);
				}
				$hostcount = \think\Db::name("host")->where("domainstatus", "Active")->where("uid", $uid)->count();
				if (!$hostcount) {
					$query->where("host_view", 0);
				}
			})->select();
			foreach ($result as $key => $value) {
				$value["article"] = mb_substr($value["article"], 0, 20);
				$value = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $value);
				$result[$key] = $value;
			}
			if (!empty($result[0])) {
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "result" => $result]);
			} else {
				return json(["status" => 400, "msg" => lang("KNOWLEDGE_NO_SIMILAR_ARTICLE")]);
			}
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
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
	 * @article  tag:标签
	 * @article  public_by:发布人(文本框)
	 * @article  public_time:发布时间(文本框,选择时间)
	 */
	public function tagsList()
	{
		$params = $this->request->param();
		$tag = trim($params["tag"]);
		$article = \think\Db::name("knowledge_base_tags")->alias("kbt")->field("kb.id,title,article,views,useful,public_by,public_time")->leftJoin("knowledge_base kb", "kb.id = kbt.article_id")->where("tag", $tag)->select();
		foreach ($article as $key => $value) {
			$value["article"] = mb_substr($value["article"], 0, 20);
			$value = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
			$article[$key] = $value;
		}
		if (!empty($article[0])) {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "article" => $article]);
		} else {
			return json(["status" => 400, "msg" => lang("KNOWLEDGE_NO_SIMILAR_ARTICLE")]);
		}
	}
	/**
	 * @title 查看文章
	 * @description 接口说明:查看文章
	 * @author wyh
	 * @url /admin/knowledge_base/view_article/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:文章id
	 * @return  categories:文章种类
	 * @return  article:文章@
	 * @article  id:文章ID
	 * @article  title:标题
	 * @article  article:内容
	 * @article  views:查看次数
	 * @article  useful:点赞次数
	 * @article  public_by:发布人
	 * @article  public_time:发布时间
	 */
	public function viewArticle()
	{
		$data = $this->request->param();
		$id = isset($data["id"]) && !empty($data["id"]) ? intval($data["id"]) : "";
		if (!$id) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$categories = \think\Db::name("knowledge_base_cats")->field("id,name")->where("hidden", 0)->select();
		$article = \think\Db::name("knowledge_base")->field("id,title,article,views,useful,public_by,public_time")->where("id", $id)->where("hidden", 0)->where(function (\think\db\Query $query) {
			$uid = request()->uid;
			if (!$uid) {
				$query->where("login_view", 0);
			}
			$hostcount = \think\Db::name("host")->where("domainstatus", "Active")->where("uid", $uid)->count();
			if (!$hostcount) {
				$query->where("host_view", 0);
			}
		})->find();
		$article = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $article);
		return json(["status" => 200, "msg" => "请求成功", "categories" => $categories, "article" => $article]);
	}
}