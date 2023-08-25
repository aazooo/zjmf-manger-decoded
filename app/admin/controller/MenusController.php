<?php

namespace app\admin\controller;

class MenusController extends AdminBaseController
{
	const SERVICE_NAME = "service";
	const ORDER_FUC = "cart";
	protected $navType = ["系统页面", "自定义页面", "产品管理", "hook/插件"];
	protected $webNavType = ["系统页面", "一级-商品分组", "商品分组-商品", "URL", "自定义页面"];
	protected $menuType = ["client" => ["field" => "client", "name" => "会员中心导航", "desc" => "此处可对会员中心导航进行修改，并放置在会员中心左侧"], "www_top" => ["field" => "www_top", "name" => "官网顶部导航", "desc" => "此处可对官网顶部导菜单航进行修改，并放置在官网页面顶部"], "www_bottom" => ["field" => "www_bottom", "name" => "官网底部导航", "desc" => "此处可对官网底部导菜单航进行修改，并放置在官网页面底部"]];
	public $menu_table = "menus";
	protected $hook_name = "plugin";
	protected static $order = 0;
	/**
	 * @title 获取导航以及导航对应的菜单列表
	 * @description 接口说明:获取导航以及导航对应的菜单列表
	 * @param .name:menu_id type:int require:0 default:菜单列表中第一个 other: desc:菜单ID
	 * @return $_model:array
	 * @author xue
	 * @url /admin/menus/getMenu
	 * @method post
	 */
	public function getMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$model = \think\Db::name($this->menu_table)->select()->toArray();
			if (count($model) == 0) {
				return [];
			}
			$models = array_column($model, null, "id");
			$menu_id = $param["menu_id"] ?: key($models);
			$_model[$menu_id] = $models[$menu_id];
			$_model = $this->setCnName($_model);
			if (!$_model[$menu_id]["nav_list"]) {
				return array_pop($_model);
			}
			$nav_list = json_decode($_model[$menu_id]["nav_list"], true);
			$nav_list = $this->changeTwoArr($nav_list);
			$list_ids = array_column($nav_list, "id");
			$nav_data = \think\Db::name("nav")->field("url,id,nav_type")->whereIn("id", $list_ids)->select()->toArray();
			switch ($_model[$menu_id]["type"]) {
				case 1:
					$_model[$menu_id]["nav_list"] = $this->packData($nav_list, $nav_data);
					break;
				case 2:
				case 3:
					$_model[$menu_id]["nav_list"] = $this->webPackData($nav_list, $nav_data);
					break;
			}
			return array_pop($_model);
		});
	}
	/**
	 * @title 获取菜单列表table
	 * @description 接口说明:获取菜单列表table
	 * @param .name:page type:int require:0 default:1 other: desc:分页
	 * @param .name:limit type:int require:0 default:50 other: desc:偏移量
	 * @param .name:nav_type type:int require:0 default: other: desc:navID
	 * @return $_model:array
	 * @author xue
	 * @url /admin/menus/getMenuList
	 * @method post
	 */
	public function getMenuList()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$model = \think\Db::name($this->menu_table);
			$count = $model->count();
			if ($param["nav_type"]) {
				$model->where("type", $param["nav_type"]);
			}
			$active_menu = array_filter(\think\Db::name("menu_active")->column("menuid"));
			\think\Db::name($this->menu_table)->whereNotIn("id", $active_menu)->update(["sort" => 0]);
			\think\Db::name($this->menu_table)->whereIn("id", $active_menu)->update(["sort" => 1]);
			$model = $model->page(max(1, $param["page"]), $this->getLimit())->order(["sort" => "desc", "id" => "desc"])->select()->toArray();
			$model = array_map(function ($v) use($active_menu) {
				$v["is_active"] = in_array($v["id"], $active_menu) ? 1 : 0;
				return $v;
			}, $model);
			return ["count" => $count, "list" => $this->setCnName($model)];
		});
	}
	/**
	 * @title 设置导航对应的菜单列表
	 * @description 接口说明:设置导航对应的菜单列表
	 * @param .name:menu_id type:int require:1 default: other: desc:菜单ID
	 * @param .name:nav_list type:array require:1 default: other: desc:菜单列表项（tree）
	 * @param .name:menu_name type:array require:1 default: other: desc:菜单name
	 * @author xue
	 * @url /admin/menus/setNavList
	 * @method post
	 */
	public function setNavList()
	{
		$param = $this->request->param();
		$db_param = ["name", "pid", "order", "fa_icon", "nav_type", "relid", "lang"];
		return $this->tryCatch(function () use($param, $db_param) {
			$this->delCustomProMenu($param);
			$this->setNavListCheck($param);
			$nav_list = $this->changeTwoArr($param["nav_list"]);
			$nav_list_ids = [];
			if (!empty($nav_list)) {
				$nav_list_ids = array_column($nav_list, "id");
			}
			$model_ids = \think\Db::name("nav")->whereIn("id", $nav_list_ids)->column("id");
			$diff_arr = array_diff($nav_list_ids, $model_ids);
			if ($diff_arr) {
				$msg = [];
				$nav_list_arr = array_column($nav_list, null, "id");
				foreach ($diff_arr as $val) {
					$msg[] = $nav_list_arr[$val]["name"];
				}
				throw new \think\Exception(implode("、", $msg) . "菜单不存在！");
			}
			foreach ($nav_list as $key => $val) {
				if ($nav_list[$key]["lang"]) {
					$nav_list[$key]["lang"] = is_array($nav_list[$key]["lang"]) ? json_encode($nav_list[$key]["lang"]) : $nav_list[$key]["lang"];
				}
				foreach ($db_param as $k => $v) {
					$nav_list[$key][$v] = $val[$v] ?? "";
					if (!$nav_list[$key]["name"]) {
						throw new \think\Exception("菜单名称不能为空");
					}
				}
				if ($val["nav_type"] == 1) {
					$val["url"] && \think\Db::name("nav")->where("id", $val["id"])->update(["url" => $val["url"]]);
				}
				if ($val["nav_type"] == 2) {
					$nav_list[$key]["senior"] = $val["senior"] ?? 0;
					$nav_list[$key]["templatePage"] = $val["templatePage"] ?: $this->getTemplatePage($val["relid"]);
					$nav_list[$key]["orderFuc"] = $val["orderFuc"] ?? 1;
					$orderFunUrl = $this->getOrderFucUrl($val["relid"]);
					$val["orderFucUrl"] = $val["orderFucUrl"] ?: $orderFunUrl;
					if (!$val["is_custom"] || !getEdition()) {
						$val["orderFucUrl"] = $orderFunUrl;
					}
					$nav_list[$key]["orderFucUrl"] = $val["orderFucUrl"];
				}
			}
			return \think\Db::name($this->menu_table)->where("id", $param["menu_id"])->update(["nav_list" => json_encode($this->getTree($nav_list)), "name" => $param["menu_name"]]);
		});
	}
	/**
	 * @title 设置web导航对应的菜单列表
	 * @description 接口说明:设置web导航对应的菜单列表
	 * @param .name:menu_id type:int require:1 default: other: desc:菜单ID
	 * @param .name:nav_list type:array require:1 default: other: desc:菜单列表项（tree）
	 * @param .name:menu_name type:array require:1 default: other: desc:菜单name
	 * @author xue
	 * @url /admin/menus/setNavList
	 * @method post
	 */
	public function setWebNavList()
	{
		$param = $this->request->param();
		$db_param = ["name", "pid", "order", "fa_icon", "nav_type", "relid", "lang"];
		return $this->tryCatch(function () use($param, $db_param) {
			$this->delWebCustomMenu($param);
			$this->setNavListCheck($param);
			$nav_list = $this->changeTwoArr($param["nav_list"]);
			$nav_list_ids = [];
			if (!empty($nav_list)) {
				$nav_list_ids = array_column($nav_list, "id");
			}
			$model_ids = \think\Db::name("nav")->whereIn("id", $nav_list_ids)->column("id");
			$diff_arr = array_diff($nav_list_ids, $model_ids);
			if ($diff_arr) {
				$msg = [];
				$nav_list_arr = array_column($nav_list, null, "id");
				foreach ($diff_arr as $val) {
					$msg[] = $nav_list_arr[$val]["name"];
				}
				throw new \think\Exception(implode("、", $msg) . "菜单不存在！");
			}
			foreach ($nav_list as $key => $val) {
				if ($nav_list[$key]["lang"]) {
					$nav_list[$key]["lang"] = is_array($nav_list[$key]["lang"]) ? json_encode($nav_list[$key]["lang"]) : $nav_list[$key]["lang"];
				}
				foreach ($db_param as $k => $v) {
					$nav_list[$key][$v] = $val[$v] ?? "";
					if (!$nav_list[$key]["name"]) {
						throw new \think\Exception("菜单名称不能为空");
					}
				}
			}
			return \think\Db::name($this->menu_table)->where("id", $param["menu_id"])->update(["nav_list" => json_encode($this->getTree($nav_list)), "name" => $param["menu_name"]]);
		});
	}
	protected function setNavListCheck($param)
	{
		if (!$param["menu_name"]) {
			throw new \think\Exception("菜单名称不能为空");
		}
		$menu_model = \think\Db::name($this->menu_table)->where("name", $param["menu_name"])->where("id", "<>", $param["menu_id"])->find();
		if ($menu_model) {
			throw new \think\Exception("菜单名称已存在");
		}
		$model = \think\Db::name($this->menu_table)->find(intval($param["menu_id"]));
		if (!$model) {
			throw new \think\Exception("导航不存在");
		}
	}
	protected function getOrderFucUrl($pIds)
	{
		$domain = configuration("domain");
		if (empty($pIds)) {
			return $domain . "/" . self::ORDER_FUC;
		}
		$ids = is_array($pIds) ? $pIds : explode(",", $pIds);
		foreach ($ids as $key => $val) {
			if (count(explode("-", $val)) == 1) {
				$ids[0] = $val;
				break;
			}
		}
		$info = \think\Db::name("products")->where("id", $ids[0])->find();
		$url_suffix = $info ? "?gid=" . $info["gid"] : "";
		return $domain . "/" . self::ORDER_FUC . $url_suffix;
	}
	/**
	 * 获取导航产品默认模板页
	 */
	protected function getTemplatePage($pIds)
	{
		if (empty($pIds)) {
			return self::SERVICE_NAME;
		}
		$ids = is_array($pIds) ? $pIds : explode(",", $pIds);
		$info = \think\Db::name("products")->where("id", $ids[0])->find();
		$tpl_name = config("product_type_tpl")[$info["type"]];
		if (file_exists(CMF_ROOT . "public/themes/clientarea/" . configuration("clientarea_default_themes") . "/" . $tpl_name . ".tpl")) {
			return $tpl_name;
		}
		return self::SERVICE_NAME;
	}
	/**
	 * @title 根据第一个产品获取高级设置
	 * @description 接口说明:根据第一个产品获取高级设置
	 * @param .name:pIds type:int|string require:1 default: other: desc:产品id
	 * @author xue
	 * @url /admin/menus/getDefaultSenior
	 * @method post
	 */
	public function getDefaultSenior()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			return ["orderFucUrl" => $this->getOrderFucUrl($param["pIds"]), "templatePage" => $this->getTemplatePage($param["pIds"])];
		});
	}
	/**
	 * @title 添加自定义页面
	 * @description 接口说明:添加自定义页面
	 * @param .name:url type:string require:1 default: other: desc:自定义页面url
	 * @param .name:name type:string require:1 default: other: desc:自定义页面名称
	 * @param .name:menu_id type:int require:1 default: other: desc:当前所编辑的菜单id
	 * @author xue
	 * @url /admin/menus/addCustomPage
	 * @method post
	 */
	public function addCustomPage()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$check_param = ["url" => "链接", "name" => "名称", "menu_id" => "菜单id"];
			foreach ($check_param as $key => $val) {
				if (!$param[$key]) {
					throw new \think\Exception($val . "不能为空");
				}
			}
			$menu_model = \think\Db::name($this->menu_table)->find(intval($param["menu_id"]));
			if (!$menu_model) {
				throw new \think\Exception("菜单项不存在！");
			}
			$data = ["url" => $param["url"], "name" => $param["name"], "pid" => 0, "order" => 0, "fa_icon" => $param["fa_icon"] ?: "", "nav_type" => 1, "relid" => $param["relid"] ?: "", "menuid" => 0, "lang" => $param["lang"] ?: "", "menu_type" => $menu_model["type"] ?: 1];
			return \think\Db::name("nav")->insertGetId($data);
		});
	}
	/**
	 * @title 添加产品中心页面
	 * @description 接口说明:添加产品中心页面
	 * @param .name:name type:string require:1 default: other: desc:自定义页面名称
	 * @param .name:menu_id type:int require:1 default: other: desc:当前所编辑的菜单id
	 * @author xue
	 * @url /admin/menus/addProductPage
	 * @method post
	 */
	public function addProductPage()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$check_param = ["name" => "名称", "menu_id" => "菜单id"];
			foreach ($check_param as $key => $val) {
				if (!$param[$key]) {
					throw new \think\Exception($val . "不能为空");
				}
			}
			$menu_model = \think\Db::name($this->menu_table)->find(intval($param["menu_id"]));
			if (!$menu_model) {
				throw new \think\Exception("菜单项不存在！");
			}
			$data = ["url" => "", "name" => $param["name"], "pid" => 0, "order" => 0, "fa_icon" => $param["fa_icon"] ?: "", "nav_type" => 2, "relid" => $param["relid"] ?: "", "menuid" => 0, "lang" => $param["lang"] ?: "", "menu_type" => $menu_model["type"] ?: 1];
			$pid = \think\Db::name("nav")->insertGetId($data);
			\think\Db::name("nav")->where("id", $pid)->update(["url" => "service?groupid=" . $pid]);
			return $pid;
		});
	}
	/**
	 * @title 获取指定的页面
	 * @description 接口说明:获取指定的页面
	 * @param .name:menu_type type:int require:1 default: other: desc:menu_type（会员中心的，还是头部的.....）
	 * @param .name:nav_type type:int require:1 default: other: desc:nav_type（系统页面，还是插件页面。。。）
	 * @author xue
	 * @url /admin/menus/getSystemNav
	 * @method post
	 */
	public function getSystemNav()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$nav = \think\Db::name("nav")->field("id,name,pid")->where("menu_type", intval($param["menu_type"]))->where("nav_type", intval($param["nav_type"]))->where("menuid", 1)->order("order")->select()->toArray();
			$displayNav = (new \app\common\logic\Menu())->getDisplayNav();
			foreach ($nav as &$val) {
				$val["is_display"] = 0;
				if (in_array($val["id"], $displayNav)) {
					$val["name"] .= "(该功能已停用)";
					$val["is_display"] = 1;
				}
			}
			return $this->getTree($nav);
		});
	}
	/**
	 * @title 导航类型为产品管理时： 获取产品列表
	 * @description 接口说明:导航类型为产品管理时： 获取产品列表
	 * @author xue
	 * @url /admin/menus/getProductList
	 * @method post
	 */
	public function getProductList()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$p_arr = \think\Db::name($this->menu_table)->field("nav_list")->where("id", intval($param["m_id"]))->column("nav_list");
			$relid = [0];
			foreach ($p_arr as $val) {
				if ($val) {
					$info_arr = $this->changeTwoArr(json_decode($val, true));
					foreach ($info_arr as $key => $info) {
						if ($info["nav_type"] == 2 && $info["relid"]) {
							$relid = $info["id"] != intval($param["id"]) ? array_merge($relid, explode(",", $info["relid"])) : $relid;
						}
					}
				}
			}
			$relid = array_merge($relid, is_string($param["ids"]) ? explode(",", $param["ids"]) : $param["ids"]);
			$model = \think\Db::name("products")->field("id,gid,name,type")->whereNotIn("id", array_unique($relid))->select()->toArray();
			if (!$model) {
				return ["tplList" => $this->getTplList()];
			}
			$model_ids = array_column($model, "gid");
			$model_group = \think\Db::name("product_groups")->field("id,name")->whereIn("id", $model_ids)->select()->toArray();
			$model_group = array_column($model_group, null, "id");
			foreach ($model as $key => $val) {
				$gid = $val["gid"];
				$val["gid"] = $val["gid"] . "-" . $val["gid"];
				$model_group[$gid]["son"][] = $val;
			}
			foreach ($model_group as $k => $v) {
				$model_group[$k]["id"] = $v["id"] . "-" . $v["id"];
			}
			$menu_id = \think\Db::name("nav")->where("nav_type", 2)->find();
			return ["list" => array_values($model_group), "id" => $menu_id ? $menu_id["id"] : 0, "tplList" => $this->getTplList()];
		});
	}
	/**
	 * @title 创建web页面
	 * @description 接口说明:创建web页面
	 * @author xue
	 * @url /admin/menus/createWebPage
	 * @method post
	 */
	public function createWebPage()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["name"]) {
				throw new \think\Exception("菜单名称不能为空");
			}
			if (!isset($this->webNavType[$param["type"]])) {
				throw new \think\Exception("页面类型不存在");
			}
			if (!$param["menu_type"]) {
				throw new \think\Exception("导航类型不存在");
			}
			return \think\Db::name("nav")->insertGetId(["name" => $param["name"], "url" => $param["url"], "pid" => 0, "nav_type" => $param["type"], "menuid" => 0, "lang" => "", "plugin" => "", "menu_type" => $param["menu_type"]]);
		});
	}
	protected function getTplList()
	{
		$path = CMF_ROOT . "public/themes/clientarea/" . configuration("clientarea_default_themes");
		if (!is_dir($path)) {
			return [];
		}
		$arr = [self::SERVICE_NAME => self::SERVICE_NAME . ".tpl"];
		$handler = opendir($path);
		while (($filename = readdir($handler)) !== false) {
			if ($filename != "." && $filename != "..") {
				if (is_dir($path . "/" . $filename)) {
				} else {
					$str = "service_";
					$end = ".tpl";
					$search = "/^{$str}.*?{$end}\$/";
					if (!preg_match($search, $filename)) {
					} else {
						$key = explode(".", $filename);
						$arr[$key[0]] = $filename;
					}
				}
			}
		}
		closedir($handler);
		return $arr;
	}
	protected function getWebTplList()
	{
		$path = CMF_ROOT . "public/themes/web/" . configuration("themes_templates");
		if (!is_dir($path)) {
			return [];
		}
		$arr = [];
		$handler = opendir($path);
		while (($filename = readdir($handler)) !== false) {
			if ($filename != "." && $filename != "..") {
				if (is_dir($path . "/" . $filename)) {
				} else {
					$str = "";
					$end = ".html";
					$search = "/^{$str}.*?{$end}\$/";
					if (!preg_match($search, $filename)) {
					} else {
						$key = explode(".", $filename);
						$arr[$key[0]] = $filename;
					}
				}
			}
		}
		closedir($handler);
		return $arr;
	}
	/**
	 * @title 获取菜单项类型（会员中心菜单，头部菜单， 尾部菜单）
	 * @description 接口说明:获取菜单项类型（会员中心菜单，头部菜单， 尾部菜单）
	 * @author xue
	 * @url /admin/menus/getMenuType
	 * @method post
	 */
	public function getMenuType()
	{
		return $this->tryCatch(function () {
			$data = \think\Db::name("menu_active")->select()->toArray();
			$list_data = \think\Db::name($this->menu_table)->field("id,name,type")->order(["sort" => "desc", "id" => "desc"])->select()->toArray();
			foreach ($data as $key => $val) {
				if (!$this->menuType[$val["type"]]) {
					unset($data[$key]);
					continue;
				}
				$data[$key]["name"] = $this->menuType[$val["type"]]["name"] ?? "其他";
				$data[$key]["desc"] = $this->menuType[$val["type"]]["desc"] ?? "";
				$data[$key]["menuid"] = $val["menuid"] ?: "";
				$data[$key]["list"] = array_filter($list_data, function ($v) use($val) {
					if ($v["type"] == $val["id"]) {
						return true;
					}
					return false;
				});
			}
			return $data;
		});
	}
	/**
	 * @title 获取除菜单id之外的菜单
	 * @description 接口说明:获取除菜单id之外的菜单
	 * @author xue
	 * @url /admin/menus/getOtherMenu
	 * @method post
	 */
	public function getOtherMenu()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["id"]) {
				throw new \think\Exception("id不能为空");
			}
			$model = \think\Db::name($this->menu_table)->where("id", $param["id"])->find();
			if (!$model) {
				throw new \think\Exception("数据不存在");
			}
			$data = \think\Db::name($this->menu_table)->where("id", "<>", $param["id"])->where("type", $model["type"])->select()->toArray();
			return $data;
		});
	}
	/**
	 * @title 添加菜单项
	 * @description 接口说明:添加菜单项
	 * @param .name:menu_type type:int require:1 default: other: desc:菜单项类型
	 * @param .name:name type:string require:1 default: other: desc:菜单项名称
	 * @method post
	 * @author xue
	 * @url /admin/menus/addMenu
	 */
	public function addMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			if (!$param["name"]) {
				throw new \think\Exception("菜单项名称不能为空");
			}
			$model = \think\Db::name("menu_active")->find(intval($param["menu_type"]));
			if (!$model) {
				throw new \think\Exception("菜单类型不存在");
			}
			$menu_model = \think\Db::name($this->menu_table)->where("name", $param["name"])->find();
			if ($menu_model) {
				throw new \think\Exception("菜单名称已存在");
			}
			$nav_arr = \think\Db::name("nav")->where("menu_type", intval($param["menu_type"]));
			if ($param["menu_type"] == 1) {
				$nav_arr->where("nav_type", 0);
			}
			$nav_arr = $nav_arr->where("menuid", 1)->order("order")->select()->toArray();
			switch (intval($param["menu_type"])) {
				case 1:
					$this->clentareaDefaultNav($nav_arr);
					break;
				case 2:
					$this->webHeadNav($nav_arr);
					break;
				case 3:
					$this->webFootNav($nav_arr);
					break;
			}
			foreach ($nav_arr as $key => $val) {
				$nav_arr[$key]["order"] = $key;
				$nav_arr[$key]["muti"] = $val["lang"] ? 1 : 0;
			}
			return \think\Db::name($this->menu_table)->insertGetId(["name" => $param["name"], "type" => intval($param["menu_type"]), "nav_list" => json_encode($this->getTree($nav_arr)), "active" => 1]);
		});
	}
	/**
	 * @title 修改菜单项
	 * @description 接口说明:修改菜单项
	 * @param .name:menu_type type:int require:1 default: other: desc:菜单项类型
	 * @param .name:name type:string require:1 default: other: desc:菜单项名称
	 * @param .name:menu_id type:int require:1 default: other: desc:菜单项id
	 * @method post
	 * @author xue
	 * @url /admin/menus/editMenu
	 */
	public function editMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			if (!\think\Db::name($this->menu_table)->find($param["menu_id"])) {
				throw new \think\Exception("要修改的菜单不存在");
			}
			if (!$param["name"]) {
				throw new \think\Exception("菜单项名称不能为空");
			}
			$model = \think\Db::name("menu_active")->find(intval($param["menu_type"]));
			if (!$model) {
				throw new \think\Exception("菜单类型不存在");
			}
			$menu_model = \think\Db::name($this->menu_table)->where("name", $param["name"])->where("id", "<>", $param["menu_id"])->find();
			if ($menu_model) {
				throw new \think\Exception("菜单名称已存在");
			}
			return \think\Db::name($this->menu_table)->update(["name" => $param["name"], "type" => intval($param["menu_type"]), "nav_list" => "", "active" => 1, "id" => $param["menu_id"]]);
		});
	}
	/**
	 * @title 删除菜单项
	 * @description 接口说明:删除菜单项
	 * @param .name:menu_id type:int require:1 default: other: desc:菜单项id
	 * @method post
	 * @author xue
	 * @url /admin/menus/delMenu
	 */
	public function delMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			return \think\Db::transaction(function () use($param) {
				if (\think\Db::name("menu_active")->where("menuid", intval($param["menu_id"]))->find()) {
					throw new \think\Exception("菜单正被使用....");
				}
				$this->directDel($param);
				return \think\Db::name($this->menu_table)->delete($param["menu_id"]);
			});
		});
	}
	/**
	 * @title 删除菜单项(二次)
	 * @description 接口说明:删除菜单项
	 * @param .name:menu_id type:int require:1 default: other: desc:菜单项id
	 * @param .name:old_menu_id type:int require:1 default: other: desc:old_菜单项id
	 * @method post
	 * @author xue
	 * @url /admin/menus/delTwoMenu
	 */
	public function delTwoMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			return \think\Db::transaction(function () use($param) {
				if (!$param["menu_id"] || !$param["old_menu_id"]) {
					throw new \think\Exception("参数错误");
				}
				\think\Db::name("menu_active")->where("menuid", intval($param["old_menu_id"]))->update(["menuid" => intval($param["menu_id"])]);
				$this->directDel($param);
				return \think\Db::name($this->menu_table)->delete($param["old_menu_id"]);
			});
		});
	}
	/**
	 * @title 获取导航对应的菜单项列表
	 * @description 接口说明:获取导航对应的菜单项列表
	 * @author xue
	 * @url /admin/menus/getTypeAllMenu
	 * @param .name:menu_type type:int require:1 default: other: desc:菜单项类型
	 * @method post
	 */
	public function getTypeAllMenu()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			$menu_type = $param["menu_type"] ?: 1;
			return \think\Db::name($this->menu_table)->field("id,name")->where("type", $menu_type)->select()->toArray();
		});
	}
	/**
	 * @title 修改默认菜单
	 * @description 接口说明:修改默认菜单
	 * @author xue
	 * @url /admin/menus/editMenuActive
	 * @param .name:data type:array require:1 default: other: desc:例子[{id:1,val:2},{id:2,val:3}]
	 * @method post
	 */
	public function editMenuActive()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			if (!$param["data"] || !is_array($param["data"])) {
				throw new \think\Exception("参数错误");
			}
			foreach ($param["data"] as $key => $val) {
				\think\Db::name("menu_active")->where("id", $val["id"])->update(["menuid" => $val["val"]]);
			}
			return 1;
		});
	}
	/**
	 * @title 获取页面类型
	 * @description 接口说明:获取页面类型
	 * @author xue
	 * @url /admin/menus/getNavType
	 * @method post
	 */
	public function getNavType()
	{
		return $this->tryCatch(function () {
			$param = $this->request->param();
			if (!$param["menu_type"]) {
				return $this->navType;
			}
			return $this->webNavType;
		});
	}
	/**
	 * @title 获取创建web导航所需数据
	 * @description 接口说明:获取创建web导航所需数据
	 * @author xue
	 * @url /admin/menus/getCreateWebData
	 * @method get
	 */
	public function getCreateWebData()
	{
		return $this->tryCatch(function () {
			$tpl = $this->getWebTplList();
			$topGroups = \think\Db::name("product_first_groups")->field("id,name")->select()->toArray();
			$groups = \think\Db::name("product_groups")->field("id,name")->select()->toArray();
			$sys_tpl = [];
			$web_default_url = config("web_default_url");
			foreach ($tpl as $key => $val) {
				if (in_array($key, $web_default_url)) {
					$sys_tpl[$key] = $val;
				}
			}
			return ["all_tpl" => $tpl, "topGroups" => $topGroups ? array_column($topGroups, "name", "id") : [], "groups" => $groups ? array_column($groups, "name", "id") : [], "sys_tpl" => $sys_tpl];
		});
	}
	/**
	 * @title 获取语言列表
	 * @description 接口说明:获取语言列表
	 * @author xue
	 * @url /admin/menus/getLang
	 * @method post
	 */
	public function getLang()
	{
		$param = $this->request->param();
		return $this->tryCatch(function () use($param) {
			return get_lang();
		});
	}
	public function directDel($param)
	{
		$model = \think\Db::name($this->menu_table)->find($param["menu_id"]);
		if (!$model["nav_list"]) {
			return \think\Db::name($this->menu_table)->delete($param["menu_id"]);
		}
		$nav_arr = $this->changeTwoArr(json_decode($model["nav_list"], true));
		$del_ids = array_column($nav_arr, "id");
		if ($model["type"] == 1) {
			return $this->delCustomProMenu(["del_ids" => $del_ids]);
		}
		return $this->delWebCustomMenu(["del_ids" => $del_ids]);
	}
	protected function clentareaDefaultNav(&$nav_arr)
	{
		$data = ["name" => "所有产品", "url" => "", "pid" => 2, "order" => 0, "fa_icon" => "", "nav_type" => 2, "relid" => "", "menuid" => 1, "lang" => json_encode(["chinese" => "所有产品", "chinese_tw" => "所有產品", "english" => "All products"]), "plugin" => "", "menu_type" => 1];
		$all_p_id = \think\Db::name("nav")->insertGetId($data);
		\think\Db::name("nav")->where("id", $all_p_id)->update(["url" => "service?groupid=" . $all_p_id]);
		$p_ids = \think\Db::name("products")->column("id");
		$nav_arr = array_column($nav_arr, null, "id");
		$relid = $p_ids ? implode(",", $p_ids) : "";
		$other_data = ["relid" => $relid, "id" => $all_p_id, "order" => $all_p_id, "senior" => 0, "templatePage" => $this->getTemplatePage($relid), "orderFuc" => 1, "is_custom" => 0, "orderFucUrl" => $this->getOrderFucUrl($relid)];
		$data = array_merge($data, $other_data);
		$nav_arr[$all_p_id] = $data;
	}
	protected function webHeadNav(&$nav_arr)
	{
		foreach ($nav_arr as $key => $val) {
			if (in_array($val["nav_type"], [1, 2])) {
				$nav_arr[$key]["custom"] = "all";
				$nav_arr[$key]["relid"] = "";
			}
		}
	}
	protected function webFootNav(&$nav_arr)
	{
		foreach ($nav_arr as $key => $val) {
			if (in_array($val["nav_type"], [1, 2])) {
				$nav_arr[$key]["custom"] = "all";
				$nav_arr[$key]["relid"] = "";
			}
		}
	}
	private function delWebCustomMenu($param)
	{
		if (!$param["del_ids"]) {
			return true;
		}
		$param["del_ids"] = is_array($param["del_ids"]) ? $param["del_ids"] : explode(",", $param["del_ids"]);
		$param["del_ids"] = array_filter($param["del_ids"], function ($v) {
			if (strpos($v, "-") !== false) {
				return false;
			}
			return true;
		});
		return \think\Db::name("nav")->whereIn("id", array_unique($param["del_ids"]))->where("menuid", 0)->whereIn("menu_type", [2, 3])->delete();
	}
	private function delCustomProMenu($param)
	{
		if (!$param["del_ids"]) {
			return true;
		}
		$param["del_ids"] = is_array($param["del_ids"]) ? $param["del_ids"] : explode(",", $param["del_ids"]);
		if (!\think\Db::name("nav")->whereIn("id", array_unique($param["del_ids"]))->whereIn("nav_type", [1, 2])->select()) {
			return true;
		}
		return \think\Db::name("nav")->whereIn("id", array_unique($param["del_ids"]))->whereIn("nav_type", [1, 2])->delete();
	}
	public function addHookMenu($data)
	{
		return $this->tryCatch(function () use($data) {
			if (!$data["menu_type"]) {
				throw new \think\Exception("定义菜单归属，参考" . json_encode($this->menuType));
			}
			if (!$data[$this->hook_name]) {
				throw new \think\Exception("插件名称?");
			}
			if (!\think\Db::name("nav")->where($this->hook_name, $data[$this->hook_name])->find()) {
				throw new \think\Exception("插件名称已存在");
			}
			$arr = ["name" => $data["name"], "url" => $data["url"], "pid" => 0, "order" => 0, "fa_icon" => $data["fa_icon"] ?: "", "nav_type" => 3, "relid" => "", "lang" => $data["lang"] ?: "", "menu_type" => $data["menu_type"], $this->hook_name => $data[$this->hook_name]];
			return \think\Db::name("nav")->insert($arr);
		});
	}
	public function delHookMenu($name)
	{
		return $this->tryCatch(function () use($name) {
			return \think\Db::name("nav")->where($this->hook_name, $name)->delete();
		});
	}
	public function getOneNavs($type, $id, $p_name = "relid")
	{
		try {
			$menuid = \think\Db::name("menu_active")->where("type", $type)->value("menuid");
			if (!$menuid) {
				return [];
			}
			$model = \think\Db::name($this->menu_table)->find($menuid);
			if (!$model || !$model["nav_list"]) {
				return [];
			}
			$nav_list = array_column($this->changeTwoArr(json_decode($model["nav_list"], true)), null, "id");
			return $nav_list[$id][$p_name] ?? "";
		} catch (\Throwable $e) {
			return [$e->getMessage(), $e->getLine()];
		}
	}
	private function setCnName($model)
	{
		$nav_model = \think\Db::name("menu_active")->select()->toArray();
		foreach ($nav_model as $key => $val) {
			$nav_model[$key]["cn_name"] = $this->menuType[$val["type"]]["name"];
		}
		if ($nav_model) {
			$nav_model = array_column($nav_model, "cn_name", "id");
		}
		foreach ($model as $key => $val) {
			$model[$key]["cn_name"] = $nav_model[$val["type"]] ?? "未知类型";
		}
		return $model;
	}
	private function packData($model, $data)
	{
		$_data = array_column($data, null, "id");
		$result = [];
		foreach ($model as $key => $val) {
			if (!isset($_data[$val["id"]])) {
				continue;
			}
			$val["is_display"] = 0;
			$val["other_name"] = "";
			$displayNav = (new \app\common\logic\Menu())->getDisplayNav();
			if (in_array($val["id"], $displayNav)) {
				$val["is_display"] = 1;
				$val["other_name"] = "(该功能已停用)";
			}
			if (!getEdition()) {
				if (isset($val["orderFuc"])) {
					$val["orderFuc"] = 0;
				}
			}
			$val["nav_type_name"] = $this->navType[$_data[$val["id"]]["nav_type"]] ?: "其他";
			$val["url"] = $_data[$val["id"]]["url"] ?? "";
			$result[] = $val;
		}
		return $this->getTree($result);
	}
	/**
	 * @title 组装web数据
	 * @param $model
	 * @param $data
	 * @return array
	 */
	private function webPackData($model, $data)
	{
		$_data = array_column($data, null, "id");
		$result = [];
		foreach ($model as $key => $val) {
			if (!isset($_data[$val["id"]])) {
				continue;
			}
			switch ($val["nav_type"]) {
				case 1:
					$val["son"] = $this->getTopGroupData($val);
					break;
				case 2:
					$val["son"] = $this->getProductGroups($val);
					break;
			}
			$val["nav_type_name"] = $this->webNavType[$_data[$val["id"]]["nav_type"]] ?: "其他";
			$result[] = $val;
		}
		return $this->getTree($result);
	}
	private function getTopGroupData($data)
	{
		$data["custom"] = $data["custom"] ?? "all";
		if ($data["custom"] == "all") {
			$data["relid"] = \think\Db::name("product_first_groups")->order("order", "desc")->column("id");
		}
		$data["relid"] = is_array($data["relid"]) ? $data["relid"] : explode(",", $data["relid"]);
		$first_group = \think\Db::name("product_first_groups")->field("id, name, order")->whereIn("id", $data["relid"])->select();
		$result = [];
		foreach ($first_group as $k => $v) {
			$result_val = ["id" => "0-" . $v["id"], "name" => $v["name"], "nav_type_name" => "一级分组", "is_display" => 1, "son" => $this->getGroupsData([["gid", "=", $v["id"]]], true)];
			$result[] = $result_val;
		}
		return $result;
	}
	private function getProductGroups($data)
	{
		$data["custom"] = $data["custom"] ?? "all";
		if ($data["custom"] == "all") {
			$data["relid"] = \think\Db::name("product_groups")->order("order", "desc")->column("id");
		}
		$data["relid"] = is_array($data["relid"]) ? $data["relid"] : explode(",", $data["relid"]);
		return $this->getGroupsData([["id", "in", $data["relid"]]]);
	}
	/**
	 * 获取商品分组
	 */
	private function getGroupsData($where, $url = false)
	{
		$data = \think\Db::name("product_groups")->where($where)->select();
		$result = [];
		foreach ($data as $k => $v) {
			$result_val = ["id" => $v["gid"] . "-" . $v["id"], "name" => $v["name"], "nav_type_name" => "商品分组", "is_display" => 1];
			$son = $this->getProductData([["gid", "=", $v["id"]]], $url);
			if ($url) {
				$result_val["url"] = $son["web_url"];
			}
			if (!$url) {
				$result_val["son"] = $son;
			}
			$result[] = $result_val;
		}
		return $result;
	}
	/**
	 * 获取商品
	 */
	private function getProductData($where, $url)
	{
		$data = \think\Db::name("products")->where($where);
		if ($url) {
			$result = $data->find();
			$result["web_url"] = config("product_type_web_tpl")[$result["type"]] ?? "";
			$result["web_url"] .= $result["web_url"] ? ".html" : "";
			return $result;
		}
		$result = $data->select();
		$arr = [];
		foreach ($result as $k => $v) {
			$result_val = ["id" => "0-" . $v["gid"] . "-" . $v["id"], "name" => $v["name"], "nav_type_name" => "商品", "is_display" => 1, "son" => []];
			$arr[] = $result_val;
		}
		return $arr;
	}
	private function changeTwoArr($tree, $children = "son", $order = true)
	{
		$imparr = [];
		foreach ($tree as $w) {
			$w_lang = $w["lang"];
			$w["lang"] = (function () use($w_lang) {
				if (is_array($w_lang)) {
					return json_encode($w_lang);
				}
				return json_decode($w_lang, true);
			})();
			if (!isset($w["muti"])) {
				$w["muti"] = $w["lang"] ? 1 : 0;
			}
			$w["muti"] = $w["muti"] ? 1 : 0;
			$order && ($w["order"] = self::$order++);
			if (in_array($w["menu_type"], [2, 3]) && in_array($w["nav_type"], [1, 2])) {
				unset($w[$children]);
			}
			if (isset($w["url"])) {
				$w["url"] = htmlspecialchars_decode($w["url"]);
			}
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
	/**
	 * @title 友情连接保存
	 * @description 接口说明:获保存友情连接,id存在为更新
	 * @param .name:id type:int require:0 default: other: desc:修改记录的id
	 * @param .name:name type:string require:0  other: desc:名称
	 * @param .name:domain type:string require:0  other: desc:地址
	 * @param .name:link_tag type:string require:0  other: desc:标签
	 * @param .name:is_open type:int require:0  other: desc:状态
	 * @return json
	 * @author x
	 * @url menus/saveLinks
	 * @method post
	 */
	public function saveLinks()
	{
		$param = $this->request->param();
		$data_ins["name"] = $param["name"];
		$data_ins["domain"] = $param["domain"];
		$data_ins["link_tag"] = $param["link_tag"];
		$data_ins["is_open"] = $param["is_open"];
		$data_ins["id"] = $param["id"] ?? 0;
		$validate = new \think\Validate(["name" => "require", "domain" => "url", "link_tag" => "require"]);
		$validate->message(["name" => "名称不能为空", "domain" => "地址不正确", "link_tag" => "标签不能为空"]);
		if (!$validate->check($data_ins)) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$FriendlyLinks = new \app\admin\model\FriendlyLinksModel();
		try {
			$FriendlyLinks->saveData($data_ins);
		} catch (\Exception $exception) {
			return json(["status" => 400, "msg" => $exception->getMessage()]);
		}
		return json(["status" => 200, "msg" => "保存成功"]);
	}
	/**
	 * @title 友情连接删除
	 * @description 接口说明:友情连接删除
	 * @param .name:id type:int require:0 default: other: desc:记录id
	 * @return json
	 * @author x
	 * @url menus/deleteLinks
	 * @method post
	 */
	public function deleteLinks()
	{
		$param = $this->request->param();
		$FriendlyLinks = new \app\admin\model\FriendlyLinksModel();
		try {
			$FriendlyLinks->deleteData($param["id"]);
		} catch (\Exception $exception) {
			return json(["status" => 400, "msg" => $exception->getMessage()]);
		}
		return json(["status" => 200, "msg" => "删除成功"]);
	}
	/**
	 * @title 友情连接列表
	 * @description 接口说明:友情连接删除
	 * @param .name:keywords type:int require:0 default: other: desc:关键字
	 * @param .name:page type:int require:0 default: other: desc:分页
	 * @param .name:limit type:int require:0 default: other: desc:每页条数
	 * @param .name:order type:int require:0 default: other: desc:排序字段
	 * @param .name:sorting type:int require:0 default: other: desc:排序规则
	 * @return json
	 * @author x
	 * @url menus/allLinks
	 * @method get
	 */
	public function allLinks()
	{
		$param = $this->request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$order = strval($param["order"]) ? strval($param["order"]) : "create_time";
		$sorting = $param["sorting"] ?? "DESC";
		$FriendlyLinks = new \app\admin\model\FriendlyLinksModel();
		try {
			$data = $FriendlyLinks->getAllPage($param, $page, $limit, $order, $sorting);
		} catch (\Exception $exception) {
			return json(["status" => 400, "msg" => $exception->getMessage()]);
		}
		return json(["status" => 200, "data" => $data]);
	}
}