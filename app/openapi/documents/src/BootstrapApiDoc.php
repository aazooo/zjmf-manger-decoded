<?php

namespace itxq\apidoc;

/**
 * BootstrapAPI文档生成
 * Class BootstrapApiDoc
 * @package itxq\apidoc
 */
class BootstrapApiDoc extends ApiDoc
{
	/**
	 * @var string - Bootstrap CSS文件路径
	 */
	private $bootstrapCss = __DIR__ . "/../assets/css/bootstrap.min.css";
	/**
	 * @var string - Bootstrap JS文件路径
	 */
	private $bootstrapJs = __DIR__ . "/../assets/js/bootstrap.min.js";
	/**
	 * @var string - jQuery Js文件路径 
	 */
	private $jQueryJs = __DIR__ . "/../assets/js/jquery.min.js";
	/**
	 * @var string - 自定义CSS
	 */
	private $customCss = "<style type=\"text/css\">
		body{font-size:14px;}
        ::-webkit-scrollbar {width: 5px;}
        .navbar-collapse.collapse.show::-webkit-scrollbar {width: 0; height: 0;background-color: rgba(255, 255, 255, 0);}
        ::-webkit-scrollbar-track {background-color: rgba(255, 255, 255, 0.2);-webkit-border-radius: 2em;-moz-border-radius: 2em;border-radius: 2em;}
        ::-webkit-scrollbar-thumb {background-color: rgba(0, 0, 0, 0.8);-webkit-border-radius: 2em;-moz-border-radius: 2em;border-radius: 2em;}
        ::-webkit-scrollbar-button {-webkit-border-radius: 2em;-moz-border-radius: 2em;border-radius: 2em;height: 0;background-color: rgba(0, 0, 0, 0.9);}
        ::-webkit-scrollbar-corner {background-color: rgba(0, 0, 0, 0.9);}
        #list-tab-left-nav{display: none;}
		.class-item .class-title {text-indent: 0.6em;border-left: 5px solid lightseagreen;font-size: 24px;margin: 15px 0;}
		.navbar{height:60px;}
		.side-nav {
        position: absolute;
    width: 300px;
    left: 0;
    bottom: 0px;
    top: 60px;
    overflow: auto;
}
.side-content{
	position: absolute;
    right: 0;   
    bottom: 0px;
    top: 60px;
    overflow-y: auto;
}
        .side-nav-item {
display: block;
padding: 10px 15px 10px 15px;
background-color: #FFFFFF;
cursor: pointer;
box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
}

.item-title {
background-color: #F5F5F5;
border-top-left-radius: 3px;
border-top-right-radius: 3px;
border-bottom: 1px solid #DDDDDD;
}

.panel-heading {
margin-top: 5px;
padding: 0;
border-radius: 3px;
border: 1px solid transparent;
border-color: #DDDDDD;
}

.item-body {
padding: 10px 15px 5px 15px;
border-bottom: 1px solid #DDDDDD;
}

.item-second {
margin-top: 5px;
cursor: pointer;
}

.item-second a {
display: block;
height: 100%;
width: 100%;
}
.at{ color:red;}
.container-fluid{padding-right:0px;}
.list-unstyled {
    padding-left: 10px;
    list-style: none;
	font-size:13px;
}
.child{display:inline-block;width:20px; height:20px; line-height: 10px;text-align: center;margin-right: 10px;cursor: pointer; font-size: 20px;color: #888;}
.child_1{display:inline-block;width:20px; height:20px; color: #999;line-height: 15px;text-align: center;margin-right: 10px;font-size: 16px;}
    </style>";
	/**
	 * @var string - 自定义JS
	 */
	private $customJs = "<script type=\"text/javascript\">
         \$(document).ready(function(){
var path=window.location.pathname;  //先得到地址栏内容
var regExp=/[\\/\\.\\?]+/;
str=path.split(regExp);
var node=str.slice(-2,-1);   //截取地址栏信息得到文件名
//\$(#'+node+' a').addClass('at');  //提前写好对应的id,菜单加亮
//\$(\"#\"+node).parent().parent().parent().addClass(\"in\"); //id父级的父级的父级添加展开class 
\$(\".table .child\").click(function(){
	var clas=\$(this).parent().parent().next().attr(\"class\");
	if(\$(\".\"+clas).is(\":hidden\")){
		\$(\".\"+clas).show();
		\$(this).html(\" _ \");
	}else{
		if(\$(this).parent().parent().data(\"child\")==\"child\"){
			var child=\$(this).parent().parent().next().data(\"child\");
			
			var tr=\$(this).parent().parent().siblings();
			tr.each(function(i){
				if(\$(this).data(\"child\")==child){
					\$(this).hide();
					\$(this).find(\".child\").html(\" + \");
				}
			});
		}
		\$(\".\"+clas).hide();
		\$(this).html(\" + \");
	}
});
})

    </script>";
	/**
	 * Bootstrap 构造函数.
	 * @param array $config - 配置信息
	 */
	public function __construct($config)
	{
		parent::__construct($config);
		$this->bootstrapJs = lib\Tools::getSubValue("bootstrap_js", $config, $this->bootstrapJs);
		$this->jQueryJs = lib\Tools::getSubValue("jquery_js", $config, $this->jQueryJs);
		$this->customJs .= lib\Tools::getSubValue("custom_js", $config, "");
		$this->bootstrapCss = lib\Tools::getSubValue("bootstrap_css", $config, $this->bootstrapCss);
		$this->customCss .= lib\Tools::getSubValue("custom_css", $config, "");
		$this->_getCss();
		$this->_getJs();
		$this->config = (include __DIR__ . "/../config.php");
	}
	/**
	 * 输出HTML
	 * @param int $type - 方法过滤，默认只获取 public类型 方法
	 * ReflectionMethod::IS_STATIC
	 * ReflectionMethod::IS_PUBLIC
	 * ReflectionMethod::IS_PROTECTED
	 * ReflectionMethod::IS_PRIVATE
	 * ReflectionMethod::IS_ABSTRACT
	 * ReflectionMethod::IS_FINAL
	 * @return string
	 */
	public function getHtml($type = \ReflectionMethod::IS_PUBLIC)
	{
		$_readDir = $this->config;
		$docTitle = [];
		$doc = [];
		foreach ($_readDir as $key => $classFile) {
			$file = __DIR__ . "/../json/" . $classFile . ".php";
			if (file_exists($file)) {
				$docClass = (include $file);
				$name = $classFile;
				if (is_array($docClass["class"])) {
					foreach ($docClass["class"] as $class) {
						$file2 = __DIR__ . "/../json/" . $class . ".php";
						if (file_exists($file2)) {
							$docClass["itemArr"][] = (include $file2);
						}
					}
				}
				unset($docClass["class"]);
				$doc[$name] = $docClass;
			}
		}
		$html = "        <!DOCTYPE html>
        <html lang=\"zh-CN\">
        <head>
            <meta charset=\"utf-8\">
            <meta name=\"renderer\" content=\"webkit\">
            <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge,chrome=1\">
            <!-- 禁止浏览器初始缩放 -->
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1, user-scalable=0\">
            <title>魔方财务API文档</title>
            {$this->customCss}
        </head>
        <body>
		<nav class=\"navbar navbar-expand-sm navbar-dark bg-dark\">
			<a class=\"navbar-brand\" href=\"#\">魔方财务API文档</a>
			<button class=\"navbar-toggler\" type=\"button\" data-toggle=\"collapse\" data-target=\"#navbarColor01\" >
			   <span class=\"navbar-toggler-icon\"></span>
			</button>
        </nav>
        <div class=\"container-fluid\">	
			<div class=\"row\">
				<div class=\"col-md-2 side-nav\">{$this->_getTopNavList($doc)}</div>
				<div class=\"col-md-10 side-content\">{$this->_getDocList($doc)}</div>
			</div>
        </div>
        {$this->customJs}
        </body>
        </html>";
		if (isset($_GET["download"]) && $_GET["download"] === "api_doc_php") {
			lib\Tools::downloadFile($html);
			return true;
		}
		return $html;
	}
	/**
	 * 解析return 并生成HTML
	 * @param array $data
	 * @return string
	 */
	private function _getReturnData($data = [], $class_action_Name)
	{
		$html = "";
		if (!is_array($data) || count($data) < 1) {
			return $html;
		}
		$html .= "<div class=\"table-item col-md-12\"><p class=\"table-title\"><span class=\"btn  btn-sm btn-success\">返回参数</span></p>";
		$html .= "<table class=\"table table-bordered\"><tr><td width=\"350\">参数</td><td width=\"100\">类型</td><td width=\"100\">验证规则</td><td width=\"100\">最大长度</td><td>描述</td><td>示例</td></tr>";
		$html .= _docHtml($data, $class_action_Name . "_return");
		$html .= "</table></div>";
		return $html;
	}
	/**
	 * 解析param 并生成HTML
	 * @param array $data
	 * @return string
	 */
	private function _getParamData($data = [], $class_action_Name)
	{
		$html = "";
		if (!is_array($data) || count($data) < 1) {
			return $html;
		}
		$html .= "<div class=\"table-item col-md-12\"><p class=\"table-title\"><span class=\"btn  btn-sm btn-danger\">请求参数</span></p>";
		$html .= "<table class=\"table table-bordered\"><tr><td width=\"350\">参数</td><td width=\"100\">类型</td><td width=\"100\">验证规则</td><td width=\"100\">最大长度</td><td>描述</td><td>示例</td></tr>";
		$html .= _docHtml($data, $class_action_Name . "_param");
		$html .= "</table></div>";
		return $html;
	}
	/**
	 * 解析public 并生成HTML
	 * @param array $data
	 * @return string
	 */
	private function _getBasiceData($data = [], $class_action_Name)
	{
		$html = "";
		if (!is_array($data) || count($data) < 1) {
			return $html;
		}
		$html .= "<div class=\"table-item col-md-12\"><p class=\"table-title\"><span class=\"btn  btn-sm btn-primary\">参数说明</span></p>";
		$html .= "<table class=\"table table-bordered\"><tr><td width=\"350\">参数</td><td width=\"100\">类型</td><td width=\"100\">验证规则</td><td width=\"100\">最大长度</td><td>描述</td><td>示例</td></tr>";
		$html .= _docHtml($data, $class_action_Name . "_param");
		$html .= "</table></div>";
		return $html;
	}
	/**
	 * 解析code 并生成HTML
	 * @param array $data
	 * @return string
	 */
	private function _getCodeData($data = [], $class_action_Name)
	{
		$html = "";
		if (!is_array($data) || count($data) < 1) {
			return $html;
		}
		$html .= "<div class=\"table-item col-md-12\"><p class=\"table-title\"><span class=\"btn  btn-sm btn-warning\">状态码说明</span></p>";
		$html .= "<table class=\"table table-bordered\"><tr><td>状态码</td><td>描述</td></tr>";
		foreach ($data as $v) {
			$html .= "<tr>
                        <td>" . $v["name"] . "</td>
                        <td>" . $v["desc"] . "</td>
                      </tr>";
		}
		$html .= "</table></div>";
		return $html;
	}
	/**
	 * 获取指定接口操作下的文档信息
	 * @param $className - 类名
	 * @param $actionName - 操作名
	 * @param $actionItem - 接口数据
	 * @return string
	 */
	private function _getActionItem($className, $actionName, $actionItem)
	{
		$html = "";
		if ($className == "Basice") {
			if ($actionItem["method"]) {
				$html .= "<p>请求方式：<span class=\"btn btn-info btn-sm\">{$actionItem["method"]}</span></p>";
			}
			if ($actionItem["desc"]) {
				$html .= "<p>描述：{$actionItem["desc"]}</p>";
			}
			if ($actionItem["version"]) {
				$html .= "<p>版本：{$actionItem["version"]}</p>";
			}
		} else {
			$html .= "<p>描述：{$actionItem["desc"]}</p>";
			$html .= "<p>请求方式：<span class=\"btn btn-info btn-sm\">{$actionItem["method"]}</span></p>";
			$html .= "<p>请求地址：<span>{$actionItem["url"]}</span></p>";
			$html .= "<p>版本：{$actionItem["version"]}</p>";
			$html .= "<p>内部API调用方法名：{$className}_{$actionName}</p>";
		}
		$html = "                <div class=\"list-group-item list-group-item-action action-item  col-md-12\" id=\"{$className}_{$actionName}\">
                    <div class=\"table-item col-md-12\">
					<h4 class=\"action-title\">API - {$actionItem["title"]}</h4>
					{$html}
					</div>
                    {$this->_getBasiceData($actionItem["basice"], $className . "_" . $actionName)}
                    {$this->_getParamData($actionItem["param"], $className . "_" . $actionName)}
                    {$this->_getReturnData($actionItem["return"], $className . "_" . $actionName)}
                    {$this->_getCodeData($actionItem["code"], $className . "_" . $actionName)}
                </div>";
		return $html;
	}
	/**
	 * 获取指定API类的文档HTML
	 * @param $className - 类名称
	 * @param $classItem - 类数据
	 * @return string
	 */
	private function _getClassItem($className, $classItem, $action)
	{
		$title = $classItem["title"];
		$actionHtml = "";
		$i = 0;
		if (is_array($classItem["itemArr"])) {
			foreach ($classItem["itemArr"] as $itemArr) {
				foreach ($itemArr["item"] as $actionName => $actionItem) {
					if ($action == $actionName) {
						$actionHtml .= $this->_getActionItem($className, $actionName, $actionItem);
						continue;
					} else {
						if (empty($action) && $i == 0) {
							$actionHtml .= $this->_getActionItem($className, $actionName, $actionItem);
							$i = 1;
							continue;
						}
					}
				}
			}
		} else {
			foreach ($classItem["item"] as $actionName => $actionItem) {
				if ($action == $actionName) {
					$actionHtml .= $this->_getActionItem($className, $actionName, $actionItem);
					continue;
				} else {
					if (empty($action) && $i == 0) {
						$actionHtml .= $this->_getActionItem($className, $actionName, $actionItem);
						$i = 1;
						continue;
					}
				}
			}
		}
		$html = "                    <div class=\"class-item\" id=\"{$className}\">
                        <h2 class=\"class-title\">{$title}</h2>
                        <div class=\"list-group\">{$actionHtml}</div>
                    </div>";
		return $html;
	}
	/**
	 * 获取API文档HTML
	 * @param array $data - 文档数据
	 * @return string
	 */
	private function _getDocList($data)
	{
		$html = "";
		if (count($data) < 1) {
			return $html;
		}
		$html .= "<div class=\"doc-content\">";
		$module = $_GET["module"];
		$action = $_GET["action"];
		$i = 0;
		foreach ($data as $className => $classItem) {
			if ($module == $className) {
				$html .= $this->_getClassItem($className, $classItem, $action);
				continue;
			} else {
				if (empty($module) && $i == 0) {
					$html .= $this->_getClassItem($className, $classItem, $action);
					$i = 1;
					continue;
				}
			}
		}
		$html .= "</div>";
		return $html;
	}
	/**
	 * 获取顶部导航HTML
	 * @param $data -API文档数据
	 * @return string
	 */
	private function _getTopNavList($data)
	{
		$html = "<div class=\"panel-group\" id=\"accordion\">";
		$module = $_GET["module"];
		$i = 0;
		foreach ($data as $className => $classItem) {
			$show = "";
			if ($module == $className) {
				$show = "show";
			} else {
				if (empty($module) && $i == 0) {
					$show = "show";
					$i = 1;
				}
			}
			$html .= "<div class=\"panel-heading panel\">";
			$html .= "<a data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#item-" . $className . "\" class=\"side-nav-item item-title\">" . $classItem["title"] . "</a>";
			$html .= "<div id=\"item-" . $className . "\" class=\"panel-collapse collapse " . $show . "\"><div class=\"item-body\"><ul class=\"list-unstyled\">";
			if (is_array($classItem["itemArr"])) {
				foreach ($classItem["itemArr"] as $itemArr) {
					$html .= "<li class=\"item-second\"><strong>" . $itemArr["title"] . "</strong></li>";
					foreach ($itemArr["item"] as $actionName => $actionItem) {
						$id = "module=" . $className . "&action=" . $actionName;
						$html .= "<li class=\"item-second\"><a href=\"?" . $id . "\"> " . $actionItem["title"] . "</a></li>";
					}
				}
			} else {
				foreach ($classItem["item"] as $actionName => $actionItem) {
					$id = "module=" . $className . "&action=" . $actionName;
					$html .= "<li class=\"item-second\"><a href=\"?" . $id . "\">" . $actionItem["title"] . "</a></li>";
				}
			}
			$html .= "</ul></div></div></div>";
		}
		$html .= "</div>";
		return $html;
	}
	/**
	 * 获取文档CSS
	 * @return string
	 */
	private function _getCss()
	{
		$path = realpath($this->bootstrapCss);
		if (!$path || !is_file($path)) {
			return $this->customCss;
		}
		$bootstrapCss = file_get_contents($path);
		if (empty($bootstrapCss)) {
			return $this->customCss;
		}
		$this->customCss = "<style type=\"text/css\">" . $bootstrapCss . "</style>" . $this->customCss;
		return $this->customCss;
	}
	/**
	 * 获取文档JS
	 * @return string
	 */
	private function _getJs()
	{
		$bootstrapJs = realpath($this->bootstrapJs);
		$jQueryJs = realpath($this->jQueryJs);
		if (!$bootstrapJs || !$jQueryJs || !is_file($bootstrapJs) || !is_file($jQueryJs)) {
			$this->customJs = "";
			return $this->customCss;
		}
		$bootstrapJs = file_get_contents($bootstrapJs);
		$jQueryJs = file_get_contents($jQueryJs);
		if (empty($bootstrapJs) || empty($jQueryJs)) {
			$this->customJs = "";
			return $this->customJs;
		}
		$js = "<script type=\"text/javascript\">" . $jQueryJs . "</script>" . "<script type=\"text/javascript\">" . $bootstrapJs . "</script>";
		$this->customJs = $js . $this->customJs;
		return $this->customJs;
	}
}
function _docRreadDir($dir = "", $type = "fileDisk")
{
	if (!is_dir($dir)) {
		return false;
	}
	$handle = opendir($dir);
	$readDir = [];
	while (($file = readdir($handle)) !== false) {
		if ($file != "." && $file != "..") {
			if (!is_dir("{$dir}/{$file}") && $type != "fileDir") {
				if ($type == "fileDisk") {
					$readDir[] = "{$dir}/{$file}";
				} elseif ($type == "fileName") {
					$readDir[] = \strval($file);
				}
			} else {
				if ($type == "fileDir") {
					$readDir[] = "{$dir}/{$file}";
				}
			}
		}
	}
	if (!readdir($handle)) {
		closedir($handle);
	}
	return $readDir;
}
function _docHtml($data, $class_action_Name, $type = 0, $key = 0)
{
	$html = "";
	foreach ($data as $k => $v) {
		if (is_array($v["child"]) && count($v["child"]) > 0) {
			$type = $type ?: 1;
			$class = $class_action_Name . "_type_{$key}_{$type}";
			$nbsp = "";
			for ($i = 0; $i <= $type; $i++) {
				$nbsp .= "&nbsp;";
			}
			if ($type > 1) {
				$child = "<span class=\"child_1\">L</span>";
				$class2 = $class_action_Name . "_type_{$key}";
			} else {
				$style = "";
				$child = "";
				$key = $k;
				$class2 = "child";
			}
			$html .= "<tr style=\"" . $style . "\" class=\"" . $class . "\" data-child=\"" . $class2 . "\">
			<td>" . $nbsp . $child . "<span class=\"child\">_</span>" . $v["name"] . "</td>
			<td>" . $v["type"] . "</td>
			<td>" . $v["require"] . "</td>
			<td>" . $v["max"] . "</td>
			<td>" . $v["desc"] . "</td>
			<td>" . $v["example"] . "</td>
			</tr>";
			$html .= _docHtml($v["child"], $class_action_Name, $type + 1, $key);
		} else {
			$child = $nbsp = "";
			for ($i = 0; $i <= $type; $i++) {
				$nbsp .= "&nbsp;&nbsp;&nbsp;";
			}
			if ($type == 1) {
				$nbsp = "&nbsp;&nbsp;&nbsp;";
			}
			if ($type > 1) {
				$child = "<span class=\"child_1\">L</span>";
			} else {
				$style = "";
				$child = "";
				$key = $k;
			}
			$class = $class_action_Name . "_type_{$key}_{$type}";
			$class2 = $class_action_Name . "_type_{$key}";
			$html .= "<tr style=\"" . $style . "\" class=\"" . $class . "\" data-child=\"" . $class2 . "\">
			<td>" . $nbsp . $child . $v["name"] . "</td>
			<td>" . $v["type"] . "</td>
			<td>" . $v["require"] . "</td>
			<td>" . $v["max"] . "</td>
			<td>" . $v["desc"] . "</td>
			<td>" . $v["example"] . "</td>
			</tr>";
		}
	}
	return $html;
}