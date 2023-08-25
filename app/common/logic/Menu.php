<?php

namespace app\common\logic;

class Menu
{
	const SERVICE_NAME = "service";
	const ORDER_FUC = "cart";
	/**
	 * 导航类型
	 * @var string[]
	 */
	protected $menuType = ["client" => "产品中心", "www_top" => "www头部导航", "www_bottom" => "www尾部导航"];
	protected $webNavType = ["系统页面", "一级-商品分组", "商品分组-商品", "URL", "自定义页面"];
	/**
	 * 根据后台功能总开关，设置菜单的显示
	 * 参数格式：配置表中的key => 对应功能菜单的url | id
	 * @var string[]
	 */
	protected $displayNav = ["allow_resource_api" => "apimanage", "affiliate_enabled" => ["url" => "affiliates"], "voucher_manager" => ["id" => [20, 21, 22, 23]], "credit_limit" => ["url" => "credit"], "addfunds_enabled" => ["url" => "addfunds"], "certifi_open" => ["url" => "verified"], "contract_open" => "contract"];
	/**
	 * 免费版菜单不显示
	 * @var array
	 */
	protected $verisonDisplayNav = [["id" => [20, 21, 22, 23]], ["url" => "credit"], ["url" => "contract"]];
	protected $hook_name = "plugin";
	protected static $order = 0;
	public $menu_table = "menus";
	/**
	 * 时间 2021-01-22
	 * @title 获取前台会员中心/官网菜单
	 * @param integer $type client=会员中心,www_top=官网顶部,www_bottom=官网底部
	 * @param string $domain 当前域名
	 * @return  array
	 * @author hh
	 */
	public function getClientMenu($type = "client", $domain = "", $language = "")
	{
		$menu = \think\Db::name("menu_active")->where("type", $type)->value("menuid");
		if (empty($menu)) {
			return [];
		} else {
			return $this->getNav($menu, 0, $domain, false, $language);
		}
	}
	public function getNavs($type = "client", $domain = "", $language = "", $is_admin = false)
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
			$nav_list = array_column($this->changeTwoArr(json_decode($model["nav_list"], true), "son", false), null, "id");
			$nav_list_ids = array_column($nav_list, "id");
			$nav_model = \think\Db::name("nav")->field("id,url,lang")->whereIn("id", $nav_list_ids);
			$uid = request()->uid;
			$is_open_credit_limit = \think\Db::name("clients")->where("id", $uid)->where("status", 1)->value("is_open_credit_limit");
			if (!$is_open_credit_limit) {
				$nav_model->where("url", "<>", "credit");
			}
			$display_nav = array_merge($this->getDisplayNav(), $this->getVerisonDisplayNav());
			if ($display_nav) {
				$nav_model->whereNotIn("id", $display_nav);
			}
			$addons = \think\Db::name("plugin")->where("module", "addons")->where("status", 0)->column("name");
			$addons = $addons ?: [];
			if (!empty($addons)) {
				$nav_model->whereNotIn("plugin", $addons);
			}
			$nav_model = $nav_model->select()->toArray();
			$new_nav_list = [];
			foreach ($nav_model as $key => $val) {
				$nav_list[$val["id"]]["url"] = $val["url"];
				$nav_list[$val["id"]]["lang"] = $nav_list[$val["id"]]["muti"] ? $nav_list[$val["id"]]["lang"] : $val["lang"];
				if ($nav_list[$val["id"]]["lang"]) {
					$lang = json_decode($nav_list[$val["id"]]["lang"], true);
					if (!empty($language)) {
						$nav_list[$val["id"]]["name"] = $lang[$language] ?: $nav_list[$val["id"]]["name"];
					}
				}
				if (!$is_admin) {
					unset($nav_list[$val["id"]]["nav_type"]);
					unset($nav_list[$val["id"]]["relid"]);
					unset($nav_list[$val["id"]]["is_display"]);
					unset($nav_list[$val["id"]]["other_name"]);
				}
				$new_nav_list[] = $nav_list[$val["id"]];
			}
			$order_nav_list = array_column($new_nav_list, null, "order");
			ksort($order_nav_list);
			return $this->getTree($order_nav_list, "child");
		} catch (\Throwable $e) {
			return [];
		}
	}
	public function getWebNav($type = "www_top", $domain = "", $language = "", $is_admin = false)
	{
		try {
			$menuid = \think\Db::name("menu_active")->where("type", $type)->value("menuid");
			if (!$menuid) {
				return [];
			}
			$model = \think\Db::name($this->menu_table)->where("id", $menuid)->find();
			if (!$model || !$model["nav_list"]) {
				return [];
			}
			$nav_list = array_column($this->changeTwoArr(json_decode($model["nav_list"], true), "son", false), null, "id");
			$nav_list_ids = array_column($nav_list, "id");
			$nav_model = \think\Db::name("nav")->field("id,url,lang")->whereIn("id", $nav_list_ids)->select()->toArray();
			$new_nav_list = $this->webPackData($nav_list, $nav_model);
			$order_nav_list = array_column($new_nav_list, null, "order");
			ksort($order_nav_list);
			return $this->getTree($order_nav_list, "child");
		} catch (\Throwable $e) {
			return [];
		}
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
			if (in_array($val["nav_type"], ["0", "4"])) {
				$val["url"] .= $val["url"] ? ".html" : "";
				$val["url"] = "/" . $val["url"];
			}
			$result[] = $val;
		}
		return $this->getTree($result);
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
			$result_val = ["id" => $v["id"], "gid" => $v["gid"], "name" => $v["name"], "nav_type_name" => "商品分组", "is_display" => 1];
			$son = $this->getProductData([["gid", "=", $v["id"]]], $url);
			if ($url) {
				$result_val["url"] = "/" . $son["web_url"];
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
			$result_val = ["id" => $v["id"], "gid" => $v["gid"], "name" => $v["name"], "nav_type_name" => "商品", "is_display" => 1, "son" => [], "url" => config("product_type_web_tpl")[$v["type"]] ?? ""];
			$result_val["url"] .= $result_val["url"] ? ".html" : "";
			$result_val["url"] = "/" . $result_val["url"];
			$arr[] = $result_val;
		}
		return $arr;
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
			$result_val = ["id" => $v["id"], "name" => $v["name"], "nav_type_name" => "一级分组", "is_display" => 1, "son" => $this->getGroupsData([["gid", "=", $v["id"]]], true)];
			$result[] = $result_val;
		}
		return $result;
	}
	public function getOneNavs($type, $id, $p_name = "relid")
	{
		try {
			$menuid = \think\Db::name("menu_active")->where("type", $type)->value("menuid");
			if (!$menuid) {
				return [];
			}
			$model = \think\Db::name($this->menu_table)->find($menuid);
			if (!$model && !$model["nav_list"]) {
				return [];
			}
			$nav_list = array_column($this->changeTwoArr(json_decode($model["nav_list"], true)), null, "id");
			if ($id === null) {
				return $nav_list;
			}
			if ($nav_list[$id]["muti"] && $nav_list[$id]["lang"] && $p_name == "name") {
				$lang = get_lang("all");
				$language = load_lang($lang);
				$name_lang = json_decode($nav_list[$id]["lang"], true);
				return $name_lang[$language] ?? $nav_list[$id][$p_name];
			}
			return $nav_list[$id][$p_name] ?? "";
		} catch (\Throwable $e) {
			return [$e->getMessage(), $e->getLine()];
		}
	}
	public function editDefaultNav($data, $type = "client")
	{
		try {
			$menuid = \think\Db::name("menu_active")->where("type", $type)->value("menuid");
			if (!$menuid) {
				return [];
			}
			$data = $this->defaultNavUnique($data);
			return \think\Db::name($this->menu_table)->where("id", $menuid)->update(["nav_list" => json_encode($this->getTree($data))]);
		} catch (\Throwable $e) {
			return [$e->getMessage(), $e->getLine()];
		}
	}
	public function defaultNavUnique($data = [])
	{
		if (!$data) {
			return [];
		}
		$_data = [];
		foreach ($data as $key => $val) {
			$_data[$val["id"]] = ["name" => $val["name"] ?? "", "pid" => $val["pid"] ?? 0, "nav_type" => $val["nav_type"] ?? 0, "menu_type" => $val["menu_type"] ?? 1];
		}
		$data = array_column($data, null, "id");
		foreach ($_data as $k => $v) {
			foreach ($_data as $k1 => $v1) {
				if ($k != $k1 && $v == $v1) {
					unset($_data[$k]);
					unset($data[$k]);
				}
			}
		}
		return array_values($data);
	}
	public function addNavToMenus($menu_type = 1, $nav_type = 0)
	{
		try {
			$nav_list = $this->getOneNavs("client", null);
			if (empty($nav_list)) {
				return [];
			}
			$nav_list_system = array_filter($nav_list, function ($v) use($menu_type, $nav_type) {
				if ($v["menu_type"] == $menu_type && $v["nav_type"] == $nav_type) {
					return true;
				}
				return false;
			});
			$nav_list_system_ids = array_column($nav_list_system, "id");
			$system_list = \think\Db::name("nav")->where("menu_type", $menu_type)->where("nav_type", $nav_type)->where("menuid", 1)->order("order")->select()->toArray();
			if (empty($system_list)) {
				return [];
			}
			$system_list = array_column($system_list, null, "id");
			$system_list_ids = array_keys($system_list);
			$diff_ids = array_diff($system_list_ids, $nav_list_system_ids);
			foreach ($diff_ids as $val) {
				$system_list[$val]["order"] = $val;
				$system_list[$val]["muti"] = $system_list[$val]["lang"] ? 1 : 0;
				$nav_list[] = $system_list[$val];
			}
			return $this->editDefaultNav($nav_list);
		} catch (\Throwable $e) {
			\think\facade\Log::record(date("Y-m-d H:i:s") . $e->getMessage(), "MENUS_ERROR");
			return [];
		}
	}
	/**
	 * 创建菜单
	 * @return mixed
	 */
	public function addMenu($param)
	{
		try {
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
		} catch (\Throwable $e) {
			return 0;
		}
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
	private function changeTwoArr($tree, $children = "son", $order = true)
	{
		$imparr = [];
		foreach ($tree as $w) {
			self::$order++;
			if (isset($w[$children])) {
				$order && ($w["order"] = self::$order);
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
				$order && ($w["order"] = self::$order);
				$imparr[] = $w;
			}
		}
		return $imparr;
	}
	/**
	 * 前后台菜单 显隐的统一处理
	 * @return array
	 */
	public function getDisplayNav()
	{
		$ids = $editotIds = [];
		if (empty($this->displayNav)) {
			return $ids;
		}
		$conf = configuration(array_keys($this->displayNav));
		foreach ($this->displayNav as $key => $val) {
			if ($conf[$key] == 1) {
				continue;
			}
			$id = [];
			if (is_string($val)) {
				$id = \think\Db::name("nav")->where("url", $val)->column("id");
			}
			if (isset($val["id"])) {
				$id = is_array($val["id"]) ? $val["id"] : [$val["id"]];
			}
			if (isset($val["url"])) {
				$url = is_array($val["url"]) ? $val["url"] : [$val["url"]];
				$id = \think\Db::name("nav")->whereIn("url", $url)->column("id");
			}
			$ids = array_merge($ids, $id);
		}
		return array_merge($ids, $editotIds);
	}
	public function getVerisonDisplayNav()
	{
		$ids = [];
		if (empty($this->verisonDisplayNav) || getEdition()) {
			return $ids;
		}
		foreach ($this->verisonDisplayNav as $key => $val) {
			$id = [];
			if (is_string($val)) {
				$id = \think\Db::name("nav")->where("url", $val)->column("id");
			}
			if (isset($val["id"])) {
				$id = is_array($val["id"]) ? $val["id"] : [$val["id"]];
			}
			if (isset($val["url"])) {
				$url = is_array($val["url"]) ? $val["url"] : [$val["url"]];
				$id = \think\Db::name("nav")->whereIn("url", $url)->column("id");
			}
			$ids = array_merge($ids, $id);
		}
		return $ids;
	}
	public function proGetNavId($pid)
	{
		$list = (new Menu())->getOneNavs("client", null);
		$p_list = array_filter($list, function ($v) {
			return $v["nav_type"] == 2;
		});
		foreach ($p_list as $key => $val) {
			if (in_array($pid, explode(",", $val["relid"]))) {
				$val["url"] = \think\Db::name("nav")->where("id", $val["id"])->value("url");
				return $val;
			}
		}
		return [];
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
	/**
	 * 时间 2021-01-22
	 * @title 获取指定菜单导航
	 * @param int $menuid 菜单ID
	 * @param integer $pid 上级ID
	 * @param string $domain 当前域名
	 * @param bool $is_admin 是否管理员
	 * @return  array
	 * @author hh
	 */
	public function getNav($menuid, $pid = 0, $domain = "", $is_admin = false, $language = "")
	{
		if (!is_array($pid)) {
			$pid = [$pid];
		}
		$uid = request()->uid;
		$is_open_credit_limit = \think\Db::name("clients")->where("id", $uid)->where("status", 1)->value("is_open_credit_limit");
		$is_open_credit_limit = configuration("credit_limit") == 1 ? $is_open_credit_limit : 0;
		$where = function (\think\db\Query $query) use($is_open_credit_limit) {
			if (!$is_open_credit_limit) {
				$query->where("url", "<>", "credit");
			}
			$addons = \think\Db::name("plugin")->where("module", "addons")->where("status", 0)->column("name");
			$addons = $addons ?: [];
			if (!empty($addons)) {
				$query->whereNotIn("plugin", $addons);
			}
		};
		$data = \think\Db::name("nav")->field("id,name,url,fa_icon,pid,nav_type,relid,lang")->whereIn("pid", $pid)->where("menuid", $menuid)->where($where)->order("order", "asc")->order("id", "asc")->select()->toArray();
		if (!empty($data)) {
			$pids = array_column($data, "id");
			$res = $this->getNav($menuid, $pids, $domain, false, $language);
			$tmp = [];
			foreach ($res as $v) {
				$tmp[$v["pid"]][] = $v;
			}
		}
		foreach ($data as $k => $v) {
			if ($v["lang"]) {
				$lang = json_decode($v["lang"], true);
				if (!empty($language)) {
					$data[$k]["name"] = $lang[$language];
				}
				unset($data[$k]["lang"]);
			}
			if ($v["nav_type"] == 2) {
				$v["url"] = "service?menu=" . $v["id"];
			} elseif ($v["nav_type"] == 1) {
			}
			if (!empty($v["url"]) && strpos($v["url"], "http://") !== 0 && strpos($v["url"], "https://") !== 0) {
				$data[$k]["url"] = $domain . "/" . ltrim($v["url"]);
			}
			$data[$k]["child"] = $tmp[$v["id"]] ?? [];
			if (!$is_admin) {
				unset($data[$k]["nav_type"]);
				unset($data[$k]["relid"]);
			}
		}
		return $data;
	}
	/**
	 * 时间 2021-01-22
	 * @title 将导航移动至菜单最上层
	 * @param int $id 导航ID
	 * @return  bool
	 */
	public function moveNavTop($id)
	{
		$nav = \think\Db::name("nav")->where("id", $id)->find();
		if (empty($nav)) {
			return false;
		}
		$top = \think\Db::name("nav")->where("menuid", $nav["menuid"])->where("pid", 0)->order("order", "asc")->order("id", "asc")->find();
		if ($id == $top["id"]) {
			return true;
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("nav")->where("menuid", $nav["menuid"])->where("pid", 0)->setInc("order");
			\think\Db::name("nav")->where("id", $id)->update(["pid" => 0, "order" => 0]);
			\think\Db::name("nav")->where("pid", $nav["pid"])->where("order", ">=", $nav["order"])->setInc("order");
			\think\Db::name("nav")->where("pid", $id)->update(["pid" => $nav["pid"], "order" => $nav["order"]]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
		}
	}
	/**
	 * 时间 2021-01-22
	 * @title 使用菜单
	 * @param int $type client=会员中心,www_top=官网顶部,www_bottom=官网底部
	 * @param integer $id 菜单ID
	 */
	public function activeMenu($type, $menuid = 0)
	{
		if (!empty($menuid)) {
			$where = [];
			$where[] = ["id", "=", $menuid];
			if ($type == "client") {
				$where[] = ["type", "=", "client"];
			} else {
				$where[] = ["type", "=", "www"];
			}
			$menuid = \think\Db::name("menu")->where($where)->value("id");
			$menuid = \intval($menuid);
			if (empty($menuid)) {
				return false;
			}
		}
		$exist = \think\Db::name("menu_active")->where("type", $type)->find();
		if (!empty($exist)) {
			\think\Db::name("menu_active")->where("id", $exist["id"])->update(["type" => $type, "menuid" => $menuid]);
		} else {
			\think\Db::name("menu_active")->insert(["type" => $type, "menuid" => $menuid]);
		}
		return true;
	}
	public function addNav($params, $menuid)
	{
		$params["nav_type"] = \intval($params["nav_type"]);
		$params["pid"] = \intval($params["pid"]);
		$params["order"] = \intval($params["order"]);
		$params["menuid"] = $menuid;
		if (!empty($params["pid"])) {
			$pid = \think\Db::name("nav")->where("menuid", $menuid)->where("id", $params["pid"])->find();
			if (empty($pid)) {
				throw new \Exception("上级ID错误");
			}
		}
		if ($params["nav_type"] == 0) {
			$params["url"] = "";
			$params["relid"] = "";
		} elseif ($params["nav_type"] == 1) {
			if (strpos($params["url"], "http://") !== 0 || strpos($params["url"], "https://") !== 0) {
				$params["url"] = "http://" . $params["url"];
			}
			$params["relid"] = "";
		} elseif ($params["nav_type"] == 2) {
			if (empty($params["relid"])) {
				throw new \Exception("产品ID不能为空");
			}
			if (!is_array($params["relid"])) {
				throw new \Exception("产品ID错误");
			}
			$product = \think\Db::name("products")->whereIn("id", $params["relid"])->column("id");
			if (count($product) != count($params["relid"])) {
				throw new \Exception("部分产品ID错误");
			}
			$params["url"] = "";
			$params["relid"] = implode(",", $product);
		} else {
			throw new \Exception("导航类型错误");
		}
		$id = \think\Db::name("nav")->field("name,url,pid,order,fa_icon,nav_type,relid")->insertGetID($params);
		return $id;
	}
	public function addHookNav($data)
	{
		try {
			return \think\Db::transaction(function () use($data) {
				if (empty($data)) {
					throw new \think\Exception("插件名称不能为空");
				}
				$model = \think\Db::name("nav")->whereIn("plugin", $data)->select()->toArray();
				if (empty($model)) {
					return true;
				}
				$model = array_map(function ($v) {
					$v["order"] = $v["id"];
					$v["muti"] = $v["lang"] ? 1 : 0;
					return $v;
				}, $model);
				$nav_list = $this->getOneNavs("client", null);
				$menuid = \think\Db::name("menu_active")->where("type", "client")->value("menuid");
				return \think\Db::name($this->menu_table)->where("id", $menuid)->update(["nav_list" => json_encode($this->getTree(array_merge($nav_list, array_column($model, null, "id"))))]);
			});
		} catch (\Throwable $e) {
			echo json(["code" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function productMenu()
	{
		try {
			return \think\Db::transaction(function () {
				$m_active_model = \think\Db::name("menu_active")->where("id", 1)->find();
				if (!$m_active_model) {
					return false;
				}
				$m_model = \think\Db::name($this->menu_table)->find($m_active_model["menuid"]);
				if (!$m_model && !$m_model["nav_list"]) {
					return false;
				}
				$m_model_arr = array_column($this->changeTwoArr(json_decode($m_model["nav_list"], true), "son", false), null, "id");
				$nav_group = \think\Db::name("nav_group")->select()->toArray();
				if (!$nav_group) {
					return false;
				}
				$arr = [];
				foreach ($this->setPlang($nav_group) as $key => $val) {
					$data = ["url" => "", "name" => $val["groupname"], "pid" => 2, "order" => 0, "fa_icon" => "", "nav_type" => 2, "relid" => "", "menuid" => 0, "lang" => isset($val["lang"]) ? json_encode($val["lang"]) : "", "menu_type" => 1];
					$is_set = \think\Db::name("nav")->where("name", $val["groupname"])->find();
					if ($is_set) {
						\think\Db::name("nav")->where("id", $is_set["id"])->update(["url" => "service?groupid=" . $is_set["id"]]);
						$data["id"] = $is_set["id"];
						$data["muti"] = $val["muti"] ?? 0;
						$m_model_arr[$is_set["id"]] = $data;
						$arr[$val["id"]] = $is_set["id"];
						continue;
					}
					$id = \think\Db::name("nav")->insertGetId($data);
					\think\Db::name("nav")->where("id", $id)->update(["url" => "service?groupid=" . $id]);
					$data["id"] = $id;
					$data["muti"] = $val["muti"] ?? 0;
					$m_model_arr[$id] = $data;
					$arr[$val["id"]] = $id;
				}
				foreach ($arr as $key => $val) {
					$ids = \think\Db::name("products")->where("groupid", $key)->column("id");
					$m_model_arr[$val]["relid"] = implode(",", $ids);
					$m_model_arr[$val]["order"] = $val;
				}
				return \think\Db::name($this->menu_table)->where("id", $m_active_model["menuid"])->update(["nav_list" => json_encode($this->getTree($m_model_arr))]);
			});
		} catch (\Throwable $e) {
			return false;
		}
	}
	public function setPlang($nav_group)
	{
		$arr = ["Cloud" => "云服务器", "Server" => "独立服务器", "Other" => "其他"];
		$arr_lang = ["Cloud" => ["chinese" => "云服务器", "chinese_tw" => "雲服務器", "english" => "Cloud"], "Server" => ["chinese" => "独立服务器", "chinese_tw" => "獨立服務器", "english" => "Server"], "Other" => ["chinese" => "其他", "chinese_tw" => "其他", "english" => "Other"]];
		foreach ($nav_group as $key => $val) {
			$nav_group[$key]["muti"] = 0;
			$is_exit = array_search($val["groupname"], $arr);
			if ($is_exit) {
				$nav_group[$key]["lang"] = $arr_lang[$is_exit];
				$nav_group[$key]["muti"] = 1;
			}
		}
		return $nav_group;
	}
	public function createMenus()
	{
		return \think\Db::transaction(function () {
			$nav_arr = \think\Db::name("nav")->where("menu_type", 1)->where("nav_type", 0)->where("menuid", 1)->order("order")->select()->toArray();
			foreach ($nav_arr as $key => $val) {
				$nav_arr[$key]["order"] = $key;
				$nav_arr[$key]["muti"] = $val["lang"] ? 1 : 0;
			}
			$model_id = \think\Db::name($this->menu_table)->insertGetId(["name" => "默认菜单(系统-多语言)", "type" => 1, "nav_list" => json_encode($this->getTree($nav_arr)), "active" => 1]);
			return \think\Db::name("menu_active")->where("type", "client")->update(["menuid" => $model_id]);
		});
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
	public function setNavSenior()
	{
		try {
			$nav_list = $this->getOneNavs("client", null);
			if (empty($nav_list)) {
				return [];
			}
			$nav_list = array_column($nav_list, null, "id");
			foreach ($nav_list as $key => &$val) {
				if ($val["nav_type"] != 2) {
					continue;
				}
				$val["senior"] = 0;
				$val["templatePage"] = $this->getTemplatePage($val["relid"]);
				$val["orderFuc"] = 1;
				$val["is_custom"] = 0;
				$val["orderFucUrl"] = $this->getOrderFucUrl($val["relid"]);
			}
			return $this->editDefaultNav($nav_list);
		} catch (\Throwable $e) {
			\think\facade\Log::record(date("Y-m-d H:i:s") . $e->getMessage(), "MENUS_ERROR");
			return [];
		}
	}
}