<?php

namespace app\admin\controller;

class PluginBaseController extends \cmf\controller\BaseController
{
	/**
	 * @var \cmf\lib\Plugin
	 */
	private $plugin;
	/**
	 * 前置操作方法列表
	 * @var array $beforeActionList
	 * @access protected
	 */
	protected $beforeActionList = [];
	public $module = "addons";
	/**
	 * 构造函数
	 * @access public
	 */
	public function __construct()
	{
		sessionInit();
		$this->app = \think\Container::get("app");
		$this->request = $this->app["request"];
		$this->getPlugin();
		$this->view = $this->plugin->getView();
		$siteInfo = cmf_get_site_info();
		$this->assign("site_info", $siteInfo);
		$this->assign("Weburl", request()->domain());
		$this->initialize();
	}
	public function getPlugin()
	{
		if (is_null($this->plugin)) {
			$pluginName = $this->request->param("_plugin");
			$pluginName = cmf_parse_name($pluginName, 1);
			$class = cmf_get_plugin_class_shd($pluginName, $this->module);
			$this->plugin = new $class();
		}
		$methods = get_class_methods($this->plugin);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				echo "插件未授权";
				exit;
			}
		}
		return $this->plugin;
	}
	protected function initialize()
	{
	}
	/**
	 * 加载模板输出(支持:/index/index,index/index,index,空,:index,/index)
	 * @access protected
	 * @param string $template 模板文件名
	 * @param array  $vars     模板输出变量
	 * @param array  $replace  模板替换
	 * @param array  $config   模板参数
	 * @return mixed|string
	 * @throws \Exception
	 */
	protected function fetch($template = "", $vars = [], $replace = [], $config = [])
	{
		$template = $this->parseTemplate($template);
		if (!is_file($template)) {
			throw new \think\exception\TemplateNotFoundException("template not exists:" . $template, $template);
		}
		return $this->view->fetch($template, $vars, $replace, $config);
	}
	/**
	 * 自动定位模板文件
	 * @access private
	 * @param string $template 模板文件规则
	 * @return string
	 */
	private function parseTemplate($template)
	{
		$viewEngineConfig = \think\facade\Config::get("template.");
		$path = $this->plugin->getThemeRoot();
		$depr = $viewEngineConfig["view_depr"];
		$data = $this->request->param();
		$controller = $data["_controller"];
		$action = $data["_action"];
		if (0 !== strpos($template, "/")) {
			$template = str_replace(["/", ":"], $depr, $template);
			$controller = \think\Loader::parseName($controller);
			if ($controller) {
				if ("" == $template) {
					$template = str_replace(".", DIRECTORY_SEPARATOR, $controller) . $depr . $action;
				} else {
					if (false === strpos($template, $depr)) {
						$template = str_replace(".", DIRECTORY_SEPARATOR, $controller) . $depr . $template;
					}
				}
			}
		} else {
			$template = str_replace(["/", ":"], $depr, substr($template, 1));
		}
		$viewEngineConfig["view_suffix"] = $this->plugin->suffix;
		return $path . ltrim($template, "/") . "." . ltrim($viewEngineConfig["view_suffix"], ".");
	}
	/**
	 * 渲染内容输出
	 * @access protected
	 * @param string $content 模板内容
	 * @param array  $vars    模板输出变量
	 * @param array  $replace 替换内容
	 * @param array  $config  模板参数
	 * @return mixed
	 */
	protected function display($content = "", $vars = [], $replace = [], $config = [])
	{
		return $this->view->display($content, $vars, $replace, $config);
	}
	/**
	 * 模板变量赋值
	 * @access protected
	 * @param mixed $name  要显示的模板变量
	 * @param mixed $value 变量的值
	 * @return void
	 */
	protected function assign($name, $value = "")
	{
		$this->view->assign($name, $value);
	}
	/**
	 * 设置验证失败后是否抛出异常
	 * @access protected
	 * @param  bool $fail 是否抛出异常
	 * @return $this
	 */
	protected function validateFailException($fail = true)
	{
		$this->failException = $fail;
		return $this;
	}
	/**
	 * 验证数据
	 * @access protected
	 * @param  array        $data     数据
	 * @param  string|array $validate 验证器名或者验证规则数组
	 * @param  array        $message  提示信息
	 * @param  bool         $batch    是否批量验证
	 * @param  mixed        $callback 回调方法（闭包）
	 * @return array|string|true
	 * @throws ValidateException
	 */
	protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
	{
		if (is_array($validate)) {
			$v = $this->app->validate();
			$v->rule($validate);
		} else {
			if (strpos($validate, ".")) {
				list($validate, $scene) = explode(".", $validate);
			}
			$v = $this->app->validate("\\plugins\\" . cmf_parse_name($this->plugin->getName()) . "\\validate\\" . $validate . "Validate");
			if (!empty($scene)) {
				$v->scene($scene);
			}
		}
		if ($batch || $this->batchValidate) {
			$v->batch(true);
		}
		if (is_array($message)) {
			$v->message($message);
		}
		if ($callback && is_callable($callback)) {
			call_user_func_array($callback, [$v, &$data]);
		}
		if (!$v->check($data)) {
			if ($this->failException) {
				throw new \think\exception\ValidateException($v->getError());
			}
			return $v->getError();
		}
		return true;
	}
	protected function ajaxPages($showdata = [], $listRow = 10, $curpage = 1, $total = 0, $isHome = false)
	{
		if ($isHome) {
			$url = "/" . request()->action();
		} else {
			$url = "/" . adminAddress() . "/addons/" . request()->action();
		}
		$p = \think\paginator\driver\Bootstrap::make($showdata, $listRow, $curpage, $total, false, ["var_page" => "page", "path" => $url, "fragment" => "", "query" => $_GET]);
		$pages = $p->render();
		$default_pages = "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&laquo;</a></li>
	<li class=\"page-item active\"><a class=\"page-link\" href=\"#\">1</a></li>
	<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&raquo;</a></li>";
		$pages = !empty($pages) ? $pages : $default_pages;
		return $pages;
	}
}