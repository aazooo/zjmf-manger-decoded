<?php

namespace app\admin\controller;

class CommunityController
{
	public function systemLangConfig()
	{
		$domain = config("database.admin_application") ?? "admin";
		$opendir = CMF_ROOT . "/public/{$domain}/lang/";
		$country_img_dir = configuration("domain") . "/upload/common/country/";
		$display_config = [];
		if (is_dir($opendir)) {
			$handler = opendir($opendir);
			while (($filename = readdir($handler)) !== false) {
				if ($filename == "." || $filename == "..") {
				} else {
					if (file_exists($opendir . $filename)) {
						$str = file_get_contents($opendir . $filename);
						preg_match("/display_name(.+?),/", $str, $display_name_ing);
						preg_match("/display_flag(.+?),/", $str, $display_flag_ing);
						$display_name = preg_replace("/:|'|,|\"/", "", $display_name_ing[1]);
						$display_flag = preg_replace("/:|'|,|\"/", "", $display_flag_ing[1]);
						$file_name = str_replace(strrchr($filename, "."), "", $filename);
						$display_config_now["display_name"] = trim($display_name);
						$display_config_now["display_flag"] = trim($display_flag);
						$display_config_now["file_name"] = trim($file_name);
						$display_config_now["country_imgUrl"] = $country_img_dir . $display_config_now["display_flag"] . ".png";
						$display_config[] = $display_config_now;
					}
				}
			}
		}
		return json(["status" => 200, "data" => $display_config]);
	}
	public function clientLangConfig()
	{
		$get_lang = get_lang("", true);
		$display_config["allow_user_language"] = configuration("allow_user_language");
		$display_config["language"] = configuration("language");
		$display_config["languageSystem"] = configuration("language_system");
		$lang_file_config = $get_lang["lang_file_config"];
		if (empty($display_config["languageSystem"])) {
			foreach ($lang_file_config as $k => $v) {
				if ($v["display_lang"] == $display_config["language"]) {
					$display_config["languageSystem"] = $v["display_flag"];
					break;
				}
			}
		}
		return json(["status" => 200, "data" => $display_config]);
	}
}