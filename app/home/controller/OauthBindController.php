<?php

namespace app\home\controller;

/**
 * @title 绑定、解绑三方登录
 * @description 三方登录绑定账户、解绑账户
 */
class OauthBindController extends CommonController
{
	private $modules = "oauth";
	public function __construct()
	{
		$this->domain = configuration("domain");
		$this->OauthModel = new \app\home\model\OauthModel();
	}
	/**
	 * @title 所有三方登录
	 * @description 接口说明:所有三方登录
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /oauthBind
	 * @method GET
	 * @return .dirName:'模块目录名称',
	 * @return .img:'三方logo图片',
	 * @return .name:'模块名称',
	 * @return .oauth:'bind已经绑定，unbind未绑定',
	 * @return .username:'已绑定用户的昵称',
	 * @return .url:'绑定授权地址',
	 */
	public function listing(\think\Request $request)
	{
		$plugins = [];
		$list = \think\Db::name("plugin")->where(["module" => "oauth", "status" => 1])->order("order", "asc")->select();
		$clients_oauth = \think\Db::name("clients_oauth")->where(["uid" => $request->uid])->select()->toArray();
		$clients_oauth = array_column($clients_oauth, "oauth", "type");
		$oauth = array_map("basename", glob(CMF_ROOT . "modules/{$this->modules}/*", GLOB_ONLYDIR));
		$oauth2 = array_map("basename", glob(WEB_ROOT . "plugins/{$this->modules}/*", GLOB_ONLYDIR));
		foreach ($list as $k => $plugin) {
			if (!$plugin["config"]) {
				continue;
			}
			$file = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$plugin["url"]}";
			$plugins[$k]["name"] = $plugin["title"];
			$plugins[$k]["dirName"] = $plugin["name"];
			$class = "{$this->modules}\\{$plugin["name"]}\\{$plugin["name"]}";
			$obj = new $class();
			$meta = $obj->meta();
			if (in_array($plugin["name"], $oauth)) {
				$oauth_img = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
			}
			if (in_array($plugin["name"], $oauth2)) {
				$oauth_img = WEB_ROOT . "plugins/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
			}
			if (stripos($oauth_img, ".svg") === false) {
				$plugins[$k]["img"] = "<img width=30 height=30 src=\"" . base64EncodeImage($oauth_img) . "\" />";
			} else {
				$plugins[$k]["img"] = file_get_contents($oauth_img);
			}
			if (!empty($clients_oauth[$plugin["name"]])) {
				$oauth = json_decode($clients_oauth[$plugin["name"]], true);
				$plugins[$k]["username"] = $oauth["username"];
				$plugins[$k]["oauth"] = "bind";
			} else {
				$plugins[$k]["url"] = $this->domain . "/oauth/url/" . $plugin["name"];
				$plugins[$k]["oauth"] = "unbind";
			}
		}
		$plugins = array_merge($plugins);
		return json(["data" => $plugins, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 解绑三方账号
	 * @description 接口说明:解绑三方账号,登录状态才能操作
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url oauthBind/untie/[:dirName]
	 * @method POST
	 */
	public function untie(\think\Request $request)
	{
		$params = ["type" => $request->dirName, "uid" => $request->uid];
		$arr = $this->OauthModel->untie($params);
		return json($arr);
	}
}