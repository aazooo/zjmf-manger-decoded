<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\view\driver;

use think\App;
use think\exception\TemplateNotFoundException;
use think\Loader;
use think\Request;
use think\Template;

class Think
{
    // 模板引擎实例
    private $template;
    private $app;

    // 模板引擎参数
    protected $config = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule'   => 1,
        // 视图基础目录（集中式）
        'view_base'   => '',
        // 模板起始路径
        'view_path'   => '',
        // 模板文件后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr'   => DIRECTORY_SEPARATOR,
        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'tpl_cache'   => true,
    ];

    public function __construct(App $app, $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, (array) $config);

        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $app->getModulePath() . 'view' . DIRECTORY_SEPARATOR;
        }
        # 20201117 新增
		if(VIEW_TEMPLATE_WEBSITE===true || VIEW_TEMPLATE_ADMIN===true){			
			$directory=VIEW_TEMPLATE_DIRECTORY!=="VIEW_TEMPLATE_DIRECTORY" ? VIEW_TEMPLATE_DIRECTORY : "";	
			$themes_templates=configuration(VIEW_TEMPLATE_SETTING_NAME);
			$themes_templates=!empty($themes_templates) ? $themes_templates : VIEW_TEMPLATE_DEFAULT;
			if(VIEW_TEMPLATE_WEBSITE===true){
				$publicFile='public/themes/'.$directory."/";
			}else if(VIEW_TEMPLATE_ADMIN===true){
                $adminAddress = adminAddress();
				$publicFile="public/{$adminAddress}/themes/";
			}
			$this->config['view_suffix']=VIEW_TEMPLATE_SUFFIX;
			$this->config['cmf_default_theme']=$themes_templates;
			$this->config['view_directory_themes']=$directory."/".$themes_templates.'/';
			$this->config['view_themes']=CMF_ROOT .'public/themes/';
			$this->config['view_path']=CMF_ROOT . $publicFile.$themes_templates.'/';
			$this->config['view_base']=CMF_ROOT . $publicFile.$themes_templates.'/';
			//$this->config['view_url']= ($directory)?'/themes/'.$directory."/".$themes_templates:'/themes/'.$themes_templates;
            $this->config['type']='Think';           
            $this->config['view_depr']='/';
            $this->config['tpl_begin']='{';
            $this->config['tpl_end']='}';
            $this->config['taglib_begin']='{';
            $this->config['taglib_end']='}';   
            $this->config['default_filter']='';   
			$yaml=view_tpl_yaml($this->config['view_path']);
			if(count($yaml)>0){
				$parent=$yaml['config-parent-theme'];
				$this->config['view_directory_themes_parent']=$directory."/".$parent.'/';
				$this->config['view_path_parent']=CMF_ROOT . $publicFile.$parent.'/';
				$this->config['view_base_parent']=CMF_ROOT . $publicFile.$parent.'/';
				$site=request()->get()['site'];
				
				
				if($site){
					$view_header_and_footer=$site;
				}else if(request()->uid){
					$view_header_and_footer=$yaml['loggedheader'];
				}else{
					$view_header_and_footer=$yaml['nologinheader'];	
				}	
				if($view_header_and_footer=="web"){
					$webtplname=configuration('themes_templates');
					if(empty($webtplname)){
						$webtplname='clientareaonly';
					}
					$this->config['view_header_and_footer']=$view_header_and_footer."/".$webtplname;
				}
				
				//$this->config['view_url_parent']='/themes/'.$directory."/".$parent;	
			}
			if($directory=="web" && request()->isMobile()  && file_exists($this->config['view_path']."wap")){
				$this->config['view_path']=$this->config['view_path']."wap/";
				$this->config['view_base']=$this->config['view_base']."wap/";
				$this->config['view_path_parent']=$this->config['view_path_parent']."wap/";
				$this->config['view_base_parent']=$this->config['view_base_parent']."wap/";
			}			
        }
        $this->template = new Template($app, $this->config);
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string    $template 模板文件
     * @param  array     $data 模板变量
     * @param  array     $config 模板参数
     * @return void
     */
    public function fetch($template, $data = [], $config = [])
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }

        // 模板不存在 抛出异常
        if (!is_file($template)) {
			echo 'template not exists:' . $template;
            //throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }

        // 记录视图信息
        $this->app
            ->log('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]');

        $this->template->fetch($template, $data, $config);
    }

    /**
     * 渲染模板内容
     * @access public
     * @param  string    $template 模板内容
     * @param  array     $data 模板变量
     * @param  array     $config 模板参数
     * @return void
     */
    public function display($template, $data = [], $config = [])
    {
        $this->template->display($template, $data, $config);
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param  string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template)
    {
        // 分析模板文件规则
        $request = $this->app['request'];

        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($module, $template) = explode('@', $template);
        }

        if ($this->config['view_base']) {
            // 基础视图目录
            $module = isset($module) ? $module : $request->module();
			if(VIEW_TEMPLATE_WEBSITE===true || VIEW_TEMPLATE_ADMIN===true)  $module ="";
            $path   = $this->config['view_base'] . ($module ? $module . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($module) ? $this->app->getAppPath() . $module . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->config['view_path'];
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = Loader::parseName($request->controller());
			if(VIEW_TEMPLATE_WEBSITE===true || VIEW_TEMPLATE_ADMIN===true)  $controller ="";
            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认规则定位
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $this->getActionTemplate($request);
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        } 
		$file=$path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
		if(!file_exists($file) && VIEW_TEMPLATE_WEBSITE===true && $this->config['view_base_parent']){
			$file=$this->config['view_base_parent'] . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
		}
		return $file;
		//return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    protected function getActionTemplate($request)
    {
        $rule = [$request->action(true), Loader::parseName($request->action(true)), $request->action()];
        $type = $this->config['auto_rule'];

        return isset($rule[$type]) ? $rule[$type] : $rule[0];
    }

    /**
     * 配置或者获取模板引擎参数
     * @access private
     * @param  string|array  $name 参数名
     * @param  mixed         $value 参数值
     * @return mixed
     */
    public function config($name, $value = null)
    {
        if (is_array($name)) {
            $this->template->config($name);
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return $this->template->config($name);
        } else {
            $this->template->$name = $value;
            $this->config[$name]   = $value;
        }
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->template, $method], $params);
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }
}
