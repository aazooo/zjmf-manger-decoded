<?php

namespace app\admin\controller;

/**
 * @title 后台预设回复
 * @description 接口说明
 */
class TicketPrereplyController extends AdminBaseController
{
	/**
	 * @title 预设回复列表
	 * @description 预设回复列表
	 * @author wyh
	 * @url         admin/ticket_prereply_list
	 * @method      GET
	 * @time        2020-04-09
	 * @return   prereply:列表@
	 * @prereply  id:预设回复组ID
	 * @prereply  name:预设回复组名称
	 *
	 * @prereply  child:预设回复@
	 * @child  id:
	 * @child  title:标题
	 * @child  content:内容
	 */
	public function replyList()
	{
		$categories = \think\Db::name("ticket_prereply_category")->field("id,name")->select();
		$categoriesFilter = [];
		foreach ($categories as $key => $category) {
			$son = \think\Db::name("ticket_prereply")->field("id,title,content")->where("cid", $category["id"])->select();
			$categoriesFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $category);
			$categoriesFilter[$key]["child"] = $son;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "prereply" => $categoriesFilter]);
	}
	/**
	 * @title 添加预设回复分类
	 * @description 添加预设回复分类
	 * @author wyh
	 * @url         admin/add_ticket_prereply_category
	 * @method      POST
	 * @time        2020-04-09
	 * @param       .name:name type:string require:1 default: other: desc:分类名称
	 * @param       .name:parent type:int require:0 default: other: desc:上级分类id(顶级分类不用传)
	 * @return     :新增分类id
	 */
	public function addCategory()
	{
		$params = input("post.");
		$rule = ["name" => "require"];
		$msg = ["name.require" => "名称不能为空"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data["name"] = trim($params["name"]);
		$newid = \think\Db::name("ticket_prereply_category")->insertGetId($data);
		if ($newid) {
			active_log("添加预设回复分类成功,分类名称:{$data["name"]},ID:{$newid}");
		}
		$result["status"] = 200;
		$result["msg"] = "添加成功";
		$result["data"] = $newid;
		return jsonrule($result);
	}
	/**
	 * @title 编辑预设回复分类页面
	 * @description 编辑预设回复分类页面
	 * @author wyh
	 * @url         admin/save_ticket_prereply_category/page
	 * @method      GET
	 * @time        2020-04-09
	 * @param       .name:id type:int require:1 default: other: desc:分类ID
	 * @return     category:新增分类id
	 */
	public function editCategoryPage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$category = \think\Db::name("ticket_prereply_category")->field("id,name")->where("id", $id)->find();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "category" => $category]);
	}
	/**
	 * @title 编辑预设回复分类
	 * @description 编辑预设回复分类
	 * @author wyh
	 * @url         admin/save_ticket_prereply_category
	 * @method      POST
	 * @time        2020-04-09
	 * @param       .name:id type:int require:1 default: other: desc:分类ID
	 * @param       .name:name type:int require:1 default: other: desc:分类名称
	 */
	public function editCategory()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$id = isset($params["id"]) ? intval($params["id"]) : "";
			if (!$id) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$rule = ["name" => "require"];
			$msg = ["name.require" => "名称不能为空"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			\think\Db::name("ticket_prereply_category")->where("id", $id)->update(["name" => trim($params["name"])]);
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除预设回复分类
	 * @description 删除预设回复分类
	 * @author wyh
	 * @url         admin/delete_ticket_prereply_category/:id
	 * @method      GET
	 * @time        2020-04-09
	 * @param       .name:id type:int require:1 default: other: desc:分类id
	 * @return
	 */
	public function deleteCategory()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("ticket_prereply")->where("cid", $id)->delete();
			\think\Db::name("ticket_prereply_category")->where("id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 添加预设回复页面
	 * @description 添加预设回复页面
	 * @author huanghao
	 * @url         admin/add_ticket_prereply/page
	 * @method      GET
	 * @time        2020-04-09
	 * @return     .categories.id:分类id
	 * @return     .categories.name:分类名称
	 */
	public function addPrereplyPage()
	{
		$categories = \think\Db::name("ticket_prereply_category")->field("id,name")->select()->toArray();
		return jsonrule(["status" => 200, "mag" => lang("SUCCESS MESSAGE"), "categories" => $categories]);
	}
	/**
	 * @title 添加预设回复
	 * @description 添加预设回复
	 * @author huanghao
	 * @url         admin/add_ticket_prereply
	 * @method      POST
	 * @time        2019-11-26
	 * @param       .name:cid type:int require:1 default: other: desc:分类id
	 * @param       .name:title type:string require:1 default: other: desc:文章标题
	 * @param       .name:content type:string require:1 default: other: desc:文章内容
	 */
	public function addPrereply()
	{
		$params = input("post.");
		$rule = ["cid" => "require", "title" => "require"];
		$msg = ["cid.require" => "分类不能为空", "title.require" => "请输入标题"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$cid = intval($params["cid"]);
		$category = \think\Db::name("ticket_prereply_category")->where("id", $cid)->find();
		if (empty($category)) {
			$result["status"] = 406;
			$result["msg"] = "分类不存在";
			return jsonrule($result);
		}
		$data["cid"] = $cid;
		$data["title"] = trim($params["title"]);
		$data["content"] = $params["content"] ?: "";
		$r = \think\Db::name("ticket_prereply")->insertGetId($data);
		if ($r) {
			active_log("添加预设回复成功,标题:{$data["title"]},ID:{$r}");
		}
		$result["status"] = 200;
		$result["msg"] = "添加成功";
		$result["data"] = $r;
		return jsonrule($result);
	}
	/**
	 * @title 编辑预设回复页面
	 * @description 编辑预设回复页面
	 * @author wyh
	 * @url         admin/save_ticket_prereply/page
	 * @method      GET
	 * @time        2020-04-09
	 * @param       .name:id type:int require:1 default: other: desc:预设回复id
	 * @return categories:分类@
	 * @categories  id:分类id
	 * @categories  name:分类名称
	 * @return list:详情@
	 * @list  id:当前预设回复id
	 * @list  cid:当前预设回复分类id
	 * @list  title:当前预设回复标题
	 * @list  content:当前预设回复内容
	 */
	public function savePrereplyPage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$categories = \think\Db::name("ticket_prereply_category")->field("id,name")->select()->toArray();
		$pre = \think\Db::name("ticket_prereply")->field("id,cid,title,content")->where("id", $id)->find();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "categories" => $categories, "list" => array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $pre)]);
	}
	/**
	 * @title 编辑预设回复
	 * @description 编辑预设回复
	 * @author huanghao
	 * @url         admin/save_ticket_prereply
	 * @method      POST
	 * @time        2019-11-26
	 * @param       .name:id type:int require:1 default: other: desc:预设回复id
	 * @param       .name:cid type:int require:0 default: other: desc:分类id
	 * @param       .name:title type:string require:1 default: other: desc:文章标题
	 * @param       .name:content type:string require:0 default: other: desc:文章内容
	 */
	public function savePrereply()
	{
		$params = input("post.");
		$rule = ["title" => "require"];
		$msg = ["title.require" => "请输入标题"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$id = intval($params["id"]);
		$exist = \think\Db::name("ticket_prereply")->where("id", $id)->find();
		if (empty($exist)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$cid = intval($params["cid"]);
		$category = \think\Db::name("ticket_prereply_category")->where("id", $cid)->find();
		if (!empty($category)) {
			$data["cid"] = $cid;
		}
		$data["title"] = trim($params["title"]);
		if (isset($params["content"])) {
			$data["content"] = $params["content"] ?: "";
		}
		$r = \think\Db::name("ticket_prereply")->where("id", $id)->update($data);
		if ($r) {
			active_log("修改预设回复成功,ID:{$id}");
		}
		$result["status"] = 200;
		$result["msg"] = "修改成功";
		return jsonrule($result);
	}
	/**
	 * @title 搜索预设回复
	 * @description 搜索预设回复
	 * @author huanghao
	 * @url         admin/search_ticket_prereply
	 * @method      POST
	 * @time        2019-11-26
	 * @param       .name:title type:string require:0 default: other: desc:搜索的标题
	 * @param       .name:content type:string require:0 default: other: desc:搜索的内容
	 * @return      .id:预设回复id
	 * @return      .title:预设回复标题
	 * @return      .content:预设回复内容
	 */
	public function searchPrereply()
	{
		$title = input("post.title", "");
		$content = input("post.content", "");
		$data = \think\Db::name("ticket_prereply")->field("id,title,content")->whereLike("title", "%{$title}%")->whereLike("content", "%{$content}%")->select();
		$result["status"] = 200;
		$result["msg"] = "搜索成功";
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 删除预设回复
	 * @description 删除预设回复
	 * @author wyh
	 * @url         admin/ticket_prereply/:id/
	 * @method      DELETE
	 * @time        2020-04-09
	 * @param       .name:id type:string require:0 default: other: desc:预设回复ID
	 */
	public function deletePrereply()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		\think\Db::name("ticket_prereply")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}