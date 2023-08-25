<?php

namespace cmf\controller;

class BaseController extends \think\Controller
{
	public $page = 1;
	public $limit = 10;
	public $lang = "";
	/**
	 * BaseController constructor.
	 */
	public function __construct()
	{
		$this->app = \think\Container::get("app");
		$this->request = $this->app["request"];
		if (!cmf_is_installed() && $this->request->module() != "api" && $this->request->controller() != "Install") {
			if ($this->request->module() == (config("database.admin_application") ?: "admin")) {
				echo json(["status" => 302, "msg" => "系统未安装"], 200);
			} else {
				return redirect(cmf_get_root() . "/install.html");
			}
		}
		$this->_initializeView();
		$this->view = \think\facade\View::init(\think\facade\Config::get("template."));
		$this->initialize();
		if ($this->request->get("page") && $this->request->get("page") >= 1) {
			$this->page = $this->request->get("page");
		}
		if ($this->request->get("limit") && $this->request->get("limit") >= 1) {
			$this->limit = \intval($this->request->get("limit"));
		}
		foreach ((array) $this->beforeActionList as $method => $options) {
			is_numeric($method) ? $this->beforeAction($options) : $this->beforeAction($method, $options);
		}
	}
	protected function _initializeView()
	{
	}
	/**
	 *  排序 排序字段为list_orders数组 POST 排序字段为：list_order
	 */
	protected function listOrders($model)
	{
		$modelName = "";
		if (is_object($model)) {
			$modelName = $model->getName();
		} else {
			$modelName = $model;
		}
		$pk = \think\Db::name($modelName)->getPk();
		$ids = $this->request->post("list_orders/a");
		if (!empty($ids)) {
			foreach ($ids as $key => $r) {
				$data["list_order"] = $r;
				\think\Db::name($modelName)->where($pk, $key)->update($data);
			}
		}
		return true;
	}
}