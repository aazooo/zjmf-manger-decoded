<?php

namespace app\admin\lib;

/**
 * 插件类
 */
abstract class Plugin
{
	/**
	 * 视图实例对象
	 * @var view
	 * @access protected
	 */
	private $view;
	private $template = "template";
	public $suffix = "tpl";
	public static $vendorLoaded = [];
	/**
	 * $info = array(
	 *  'name'=>'HelloWorld',
	 *  'title'=>'HelloWorld',
	 *  'description'=>'HelloWorld',
	 *  'status'=>1,
	 *  'author'=>'ThinkCMF',
	 *  'version'=>'1.0'
	 *  )
	 */
	public $info = [];
	private $pluginPath = "";
	private $name = "";
	private $configFilePath = "";
	private $themeRoot = "";
	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
		define("VIEW_TEMPLATE_ADMIN_PLUGINS", true);
		define("VIEW_TEMPLATE_SETTING_NAME", "admin_default_theme");
		define("VIEW_TEMPLATE_DEFAULT", "default");
		define("VIEW_TEMPLATE_SUFFIX", "tpl");
		$request = request();
		$engineConfig = \think\facade\Config::pull("template");
		$this->name = $this->getName();
		$nameCStyle = \think\Loader::parseName($this->name);
		$module = explode("\\", get_class($this))[0] ?: "addons";
		$module = $module . "/";
		$this->pluginPath = CMF_ROOT . "modules/" . $module . $nameCStyle . "/";
		if (!is_dir($this->pluginPath)) {
			$this->pluginPath = WEB_ROOT . "plugins/" . $module . $nameCStyle . "/";
		}
		$this->configFilePath = $this->pluginPath . "config.php";
		if (empty(self::$vendorLoaded[$this->name])) {
			$pluginVendorAutoLoadFile = $this->pluginPath . "vendor/autoload.php";
			if (file_exists($pluginVendorAutoLoadFile)) {
				require_once $pluginVendorAutoLoadFile;
			}
			self::$vendorLoaded[$this->name] = true;
		}
		$config = $this->getConfig();
		$theme = isset($config["theme"]) ? $config["theme"] : "";
		$root = cmf_get_root();
		$themeDir = empty($theme) ? "" : "/" . $theme;
		$themePath = $this->template . $themeDir;
		$this->themeRoot = $this->pluginPath . $themePath . "/";
		$engineConfig["view_base"] = $this->themeRoot;
		$pluginRoot = "plugins/{$nameCStyle}";
		$adminTheme = adminTheme();
		$adminThemePath = adminAddress() . "/themes/" . $adminTheme;
		$cdnSettings = cmf_get_option("cdn_settings");
		if (empty($cdnSettings["cdn_static_root"])) {
			$replaceConfig = ["__ROOT__" => $root, "__PLUGIN_TMPL__" => $root . "/" . $pluginRoot . "/" . $themePath, "__PLUGIN_ROOT__" => $root . "/" . $pluginRoot, "__ADMIN_TMPL__" => "{$root}/{$adminThemePath}", "__PLUGIN_ADMIN_TMPL__" => $root . "/" . $pluginRoot . "/" . $themePath . "/admin", "__PLUGIN_CLIENTAREA_TMPL__" => $root . "/" . $pluginRoot . "/" . $themePath . "/clientarea", "__STATIC__" => "{$root}/static", "__WEB_ROOT__" => $root];
		} else {
			$cdnStaticRoot = rtrim($cdnSettings["cdn_static_root"], "/");
			$replaceConfig = ["__ROOT__" => $root, "__PLUGIN_TMPL__" => $cdnStaticRoot . "/" . $pluginRoot . "/" . $themePath, "__PLUGIN_ROOT__" => $cdnStaticRoot . "/" . $pluginRoot, "__ADMIN_TMPL__" => "{$cdnStaticRoot}/{$adminThemePath}", "__PLUGIN_ADMIN_TMPL__" => $root . "/" . $pluginRoot . "/" . $themePath . "/admin", "__PLUGIN_CLIENTAREA_TMPL__" => $root . "/" . $pluginRoot . "/" . $themePath . "/clientarea", "__STATIC__" => "{$cdnStaticRoot}/static", "__WEB_ROOT__" => $cdnStaticRoot];
		}
		$view = new \think\View();
		$engineConfig["view_suffix"] = $this->suffix;
		$this->view = $view->init($engineConfig);
		$this->view->config("tpl_replace_string", $replaceConfig);
		$lang = request()->param("language");
		if ($lang == "chinese") {
			$langSet = "zh-cn";
		} elseif ($lang == "chinese_tw") {
			$langSet = "zh-cn";
		} elseif ($lang == "english") {
			$langSet = "en-us";
		} else {
			$langSet = "zh-cn";
		}
		$lang_file = $this->pluginPath . "lang/" . $langSet . ".php";
		\think\facade\Lang::load($lang_file);
	}
	/**
	 * 加载模板输出
	 * @access protected
	 * @param string $template 模板文件名
	 * @return string
	 * @throws \Exception
	 */
	protected final function fetch($template)
	{
		if (!is_file($template)) {
			$engineConfig = \think\facade\Config::pull("template");
			$template = $this->themeRoot . $template . ".tpl";
		}
		if (!is_file($template)) {
			throw new \think\exception\TemplateNotFoundException("template not exists:" . $template, $template);
		}
		return $this->view->fetch($template);
	}
	/**
	 * 渲染内容输出
	 * @access protected
	 * @param string $content 模板内容
	 * @return mixed
	 */
	protected final function display($content = "")
	{
		return $this->view->display($content);
	}
	/**
	 * 模板变量赋值
	 * @access protected
	 * @param mixed $name  要显示的模板变量
	 * @param mixed $value 变量的值
	 * @return void
	 */
	protected final function assign($name, $value = "")
	{
		$this->view->assign($name, $value);
	}
	/**
	 * 获取插件名
	 * @return string
	 */
	public final function getName()
	{
		if (empty($this->name)) {
			$class = get_class($this);
			$this->name = substr($class, strrpos($class, "\\") + 1, -6);
		}
		return $this->name;
	}
	/**
	 * 检查插件信息完整性
	 * @return bool
	 */
	public final function checkInfo()
	{
		$infoCheckKeys = ["name", "title", "description", "status", "author", "version"];
		foreach ($infoCheckKeys as $value) {
			if (!array_key_exists($value, $this->info)) {
				return false;
			}
		}
		return true;
	}
	/**
	 * 获取插件根目录绝对路径
	 * @return string
	 */
	public final function getPluginPath()
	{
		return $this->pluginPath;
	}
	/**
	 * 获取插件配置文件绝对路径
	 * @return string
	 */
	public final function getConfigFilePath()
	{
		return $this->configFilePath;
	}
	/**
	 *
	 * @return string
	 */
	public final function getThemeRoot()
	{
		return $this->themeRoot;
	}
	/**
	 * @return View
	 */
	public function getView()
	{
		return $this->view;
	}
	/**
	 * 获取插件的配置数组
	 * @return array
	 */
	public final function getConfig()
	{
		$name = $this->getName();
		if (PHP_SAPI != "cli") {
			static $_config = [];
			if (isset($_config[$name])) {
				return $_config[$name];
			}
		}
		$config = \think\Db::name("plugin")->where("name", $name)->value("config");
		if (!empty($config) && $config != "null") {
			$config = json_decode($config, true);
		} else {
			$config = $this->getDefaultConfig();
		}
		$_config[$name] = $config;
		return $config;
	}
	/**
	 * 获取插件的配置数组
	 * @return array
	 */
	public final function getDefaultConfig()
	{
		$config = [];
		if (file_exists($this->configFilePath)) {
			$tempArr = (include $this->configFilePath);
			if (!empty($tempArr) && is_array($tempArr)) {
				foreach ($tempArr as $key => $value) {
					if ($value["type"] == "group") {
						foreach ($value["options"] as $gkey => $gvalue) {
							foreach ($gvalue["options"] as $ikey => $ivalue) {
								$config[$ikey] = $ivalue["value"];
							}
						}
					} else {
						$config[$key] = $tempArr[$key]["value"];
					}
				}
			}
		}
		return $config;
	}
	public abstract function install();
	public abstract function uninstall();
}