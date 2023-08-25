<?php

namespace app\admin\controller;

/**
 * @title 对外API
 * @description 接口说明: API管理
 */
class ApiController extends AdminBaseController
{
	/**
	 * @time 2020-05-28
	 * @title API授权管理
	 * @description API授权列表
	 * @url /admin/api
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(id,username,ip) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:desc
	 * @param   .name:search type:string require:0 desc:搜索
	 * @return  list:列表数据@
	 * @list  id:ID
	 * @list  username:用户名
	 * @list  ip:允许IP
	 * @list  create_time:创建时间
	 */
	public function index()
	{
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$orderby = input("get.orderby", "id");
		$sort = input("get.sort", "asc");
		$search = input("get.search", "");
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "username", "ip"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "desc";
		}
		$count = \think\Db::name("api")->whereLike("username|ip", "%{$search}%")->where("is_auto", 0)->count();
		$data = \think\Db::name("api")->field("id,username,ip,create_time")->whereLike("username|ip", "%{$search}%")->where("is_auto", 0)->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$max_page = ceil($count / $limit);
		$result["status"] = 200;
		$result["data"]["page"] = $page;
		$result["data"]["limit"] = $limit;
		$result["data"]["sum"] = $count;
		$result["data"]["max_page"] = $max_page;
		$result["data"]["orderby"] = $orderby;
		$result["data"]["sort"] = $sort;
		$result["data"]["list"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-28
	 * @title 添加API授权
	 * @description 添加API授权
	 * @url /admin/api
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:username type:string require:1 desc:用户名
	 * @param   .name:password type:string require:1 desc:密码
	 * @param   .name:ip type:string require:1 desc:允许IP多个,分割
	 */
	public function add()
	{
		$params = input("post.");
		$validate = new \app\admin\validate\ApiValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$username_exist = \think\Db::name("api")->where("username", $params["username"])->find();
		if (!empty($username_exist)) {
			$result["status"] = 400;
			$result["msg"] = "用户名已存在";
			return jsonrule($result);
		}
		$insert = ["username" => $params["username"], "password" => md5($params["password"]), "ip" => $params["ip"], "create_time" => time()];
		\think\Db::name("api")->insert($insert);
		$result["status"] = 200;
		$result["msg"] = lang("ADD SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-28
	 * @title 删除API授权
	 * @description 删除API授权
	 * @url /admin/api
	 * @method  DELETE
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:API授权ID
	 */
	public function delete()
	{
		$id = input("post.id", 0, "intval");
		\think\Db::name("api")->where("id", $id)->where("is_auto", 0)->delete();
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
}