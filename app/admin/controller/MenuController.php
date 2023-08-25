<?php

namespace app\admin\controller;

/**
 * @title 菜单/导航管理
 * @description 接口说明: 菜单/导航管理
 */
class MenuController extends AdminBaseController
{
	/**
	 * @title 管理位置页面
	 * @description 接口说明:管理位置页面数据
	 * @author hh
	 * @url /admin/menu/position_page
	 * @method GET
	 * @return client_menu_item:会员中心可选菜单@
	 * @client_menu_item id:菜单ID
	 * @client_menu_item name:菜单名称
	 * @return index_menu_item:官网可选菜单@
	 * @index_menu_item id:菜单ID
	 * @index_menu_item name:菜单名称
	 * @return  client_menu:当前会员中心菜单ID
	 * @return  www_top_menu:当前官网顶部菜单ID
	 * @return  www_bottom_menu:当前官网底部菜单ID
	 */
	public function managePositionPage()
	{
		$active = \think\Db::name("menu_active")->field("type,menuid")->select()->toArray();
		$active = array_column($active, "menuid", "type");
		$data["client_menu_item"] = \think\Db::name("menu")->field("id,name")->where("type", "client")->select()->toArray();
		$data["index_menu_item"] = \think\Db::name("menu")->field("id,name")->where("type", "www")->select()->toArray();
		$data["client_menu"] = $active["client"] ?? 0;
		$data["www_top_menu"] = $active["www_top"] ?? 0;
		$data["www_bottom_menu"] = $active["www_bottom"] ?? 0;
		return jsonrule(["data" => $data, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 菜单设置页面
	 * @description 接口说明:菜单设置页面
	 * @author hh
	 * @url /admin/menu/setting_page
	 * @method GET
	 * @param .name:menuid type:int require:1 default:0 other: desc:菜单ID
	 * @return menu:会员中心可选菜单@
	 * @menu id:菜单ID
	 * @menu name:菜单名称
	 * @menu type:菜单类型,client=会员中心,www官网
	 * @menu active_type:菜单使用类型,空未使用,client=会员中心,www_top官网顶部,www_bottom官网底部
	 * @return  nav:所选菜单导航数据@
	 * @nav id:导航ID
	 * @nav name:导航名称
	 * @nav url:导航url
	 * @nav fa_icon:导航图标
	 * @nav pid:导航上级ID
	 * @nav nav_type:导航类型
	 * @nav relid:导航关联商品ID
	 */
	public function menuManagePage()
	{
		$menuid = request()->param("menuid");
		$active = \think\Db::name("menu_active")->field("type,menuid")->select()->toArray();
		$active = array_column($active, "type", "menuid");
		$data["menu"] = \think\Db::name("menu")->select()->toArray();
		foreach ($data["menu"] as $k => $v) {
			if (isset($active[$v["id"]])) {
				$data["menu"][$k]["active_type"] = $active[$v["id"]];
			} else {
				$data["menu"][$k]["active_type"] = "";
			}
		}
		$menu = new \app\common\logic\Menu();
		$data["nav"] = $menu->getNav($menuid, 0, "", true);
		return jsonrule(["data" => $data, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 保存管理位置
	 * @description 接口说明:保存管理位置
	 * @author hh
	 * @url /admin/menu/save_position
	 * @method POST
	 * @param .name:menu['client'] type:int require:1 default:0 other: desc:会员中心菜单ID
	 * @param .name:menu['www_top'] type:int require:1 default:0 other: desc:官网顶部菜单ID
	 * @param .name:menu['www_bottom'] type:int require:1 default:0 other: desc:当前官网底部菜单ID
	 */
	public function savePosition()
	{
		$menu = request()->param("menu");
		$menu_obj = new \app\common\logic\Menu();
		if (isset($menu["client"])) {
			$menu_obj->activeMenu(0, $menu["client"]);
		}
		if (isset($menu["www_top"])) {
			$menu_obj->activeMenu(0, $menu["www_top"]);
		}
		if (isset($menu["www_bottom"])) {
			$menu_obj->activeMenu(0, $menu["www_bottom"]);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 添加菜单
	 * @description 接口说明:添加菜单
	 * @author hh
	 * @url /admin/menu/create
	 * @method POST
	 * @param .name:name type:string require:1 default: other: desc:菜单名称
	 * @param .name:type type:int require:0 default:0 other: desc:菜单类型 client=会员中心,www=官网
	 */
	public function createMenu()
	{
		$param = request()->only(["name", "type"]);
		$validate = new \app\admin\validate\MenuValidate();
		if (!$validate->check($param)) {
			return jsonrule(["status" => 400, "msg" => $validate->getError()]);
		}
		$id = \think\Db::name("menu")->insertGetId($param);
		return jsonrule(["status" => 200, "msg" => "添加成功"]);
	}
	/**
	 * @title 修改菜单
	 * @description 接口说明:修改菜单
	 * @author hh
	 * @url /admin/menu/edit
	 * @method POST
	 * @param .name:id type:int require:1 default: other: desc:菜单ID
	 * @param .name:name type:string require:1 default: other: desc:菜单名称
	 * @param .name:nav type:array require:1 default: other: desc:导航数据,二维数组,里面每个格式['id'=>'自定义ID','name'=>'名称','url'=>'地址','pid'=>'上级ID,顶级传0','order'=>'排序','order'=>'排序','fa_icon'=>'图标','nav_type'=>'0,1,2','relid'=>[1,2]];
	 *   
	 */
	public function editMenu()
	{
		$param = request()->only(["id", "name", "nav"]);
		$validate = new \app\admin\validate\MenuValidate();
		if (!$validate->scene("edit")->check($param)) {
			return jsonrule(["status" => 400, "msg" => $validate->getError()]);
		}
		$menu = \think\Db::name("menu")->where("id", \intval($param["id"]))->find();
		if (empty($menu)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$exist = \think\Db::name("menu")->where("id", "<>", $param["id"])->where("name", $param["name"])->find();
		if (!empty($exist)) {
			return jsonrule(["status" => 400, "msg" => "名称已使用"]);
		}
		$menu_obj = new \app\common\logic\Menu();
		\think\Db::startTrans();
		try {
			$ids = [];
			$nav_id = array_column($param["nav"], "id");
			$has_top = false;
			\think\Db::name("nav")->where("menuid", $menu["id"])->delete();
			foreach ($param["nav"] as $v) {
				if (empty($v["id"]) || !isset($v["pid"])) {
					throw new \Exception("导航参数错误:缺少ID");
				}
				if ($v["pid"] != 0 && !in_array($v["pid"], $nav_id)) {
					throw new \Exception("导航参数错误:上级ID错误");
				}
				if ($v["pid"] == 0) {
					$has_top = true;
				}
				$ids[$v["id"]]["fake_pid"] = $v["pid"];
				$v["pid"] = 0;
				$ids[$v["id"]]["navid"] = $menu_obj->addNav($v, $menu["id"]);
			}
			if (!$has_top) {
				throw new \Exception("导航参数错误:没有顶级导航");
			}
			foreach ($ids as $k => $v) {
				\think\Db::name("nav")->where("id", $v["navid"])->update(["pid" => \intval($ids[$ids[$v["fake_pid"]]["navid"]])]);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
		return jsonrule(["status" => 200, "msg" => "修改成功"]);
	}
	/**
	 * @title 删除菜单
	 * @description 接口说明:删除菜单
	 * @author hh
	 * @url /admin/menu/delete
	 * @method DELETE
	 * @param .name:id type:string require:1 default: other: desc:菜单
	 */
	public function deleteMenu()
	{
		$id = request()->param("id");
		if (empty($id)) {
			return jsonrule(["status" => 400, "msg" => "请选择要删除的菜单"]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("menu")->where("id", $id)->delete();
			\think\Db::name("nav")->where("menuid", $id)->delete();
			\think\Db::name("menu_active")->where("menuid", $id)->update(["menuid" => 0]);
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		} catch (\Exception $e) {
			return jsonrule(["status" => 200, "msg" => "删除失败"]);
		}
	}
	/**
	 * @title 添加导航
	 * @description 接口说明:添加导航
	 * @author hh
	 * @url /admin/menu/create_nav
	 * @method POST
	 * @param .name:menuid type:int require:1 default: other: desc:菜单ID
	 * @param .name:name type:string require:1 default: other: desc:导航名称
	 * @param .name:nav_type type:int require:0 default:0 other: desc:导航类型0系统类型,1自定义页面,2产品中心
	 * @param .name:url type:string require:1 default: other: desc:导航类型0,1有效
	 * @param .name:fa_icon type:string require:1 default: other: desc:icon
	 * @param .name:relid type:array require:1 default: other: desc:导航类型为2时传入商品ID
	 */
	public function createNav()
	{
		$param = request()->only(["menuid", "name", "nav_type", "url", "fa_icon", "relid"]);
		$validate = new \app\admin\validate\NavValidate();
		if (!$validate->check($param)) {
			return jsonrule(["status" => 400, "msg" => $validate->getError()]);
		}
		$menu = \think\Db::name("menu")->where("id", \intval($param["menuid"]))->find();
		if (empty($menu)) {
			return jsonrule(["status" => 400, "msg" => "菜单错误"]);
		}
		$menu_obj = new \app\common\logic\Menu();
		try {
			$menu_obj->addNav($param, $menu["id"]);
		} catch (\Exception $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
		return jsonrule(["status" => 200, "msg" => "添加成功"]);
	}
	/**
	 * @title 删除导航
	 * @description 接口说明:删除导航
	 * @author hh
	 * @url /admin/menu/delete_nav
	 * @method DELETE
	 * @param .name:id type:int require:1 default: other: desc:导航ID
	 */
	public function deleteNav()
	{
		$id = \intval(request()->param("id"));
		$nav = \think\Db::name("nav")->where("id", $id)->find();
		if (empty($nav)) {
			return jsonrule(["status" => 400, "msg" => "导航已删除"]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("nav")->where("id", $id)->delete();
			\think\Db::name("nav")->where("pid", $id)->update(["pid" => $nav["pid"], "order" => $nav["order"]]);
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 200, "msg" => "删除失败"]);
		}
	}
}