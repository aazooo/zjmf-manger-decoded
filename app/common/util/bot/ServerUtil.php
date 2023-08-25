<?php

namespace app\common\util\bot;

class ServerUtil
{
	/**
	 * @title 服务器重启
	 * @description
	 * @keyword i_power_supply
	 * @matching_text 重启::重启一下::MTI0MTI0::c2dhZ2E=
	 * @assembly dcim,other,culoddcim
	 * @param .title:ip地址 keyword:ip matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 * @param .title:ip地址2 keyword:ip2 matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 */
	public function restart($params = [])
	{
		return "完成重启";
	}
	/**
	 * @title 服务器开机
	 * @description
	 * @keyword i_power_supply
	 * @matching_text 开机::开机一下::MTI0MTI0::c2dhZ2E=
	 * @assembly dcim,other,culoddcim
	 * @param .title:ip地址 keyword:ip matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 * @param .title:ip地址2 keyword:ip2 matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 */
	public function power_on($ip)
	{
		return "完成开机";
	}
	/**
	 * @title 服务器关机
	 * @description
	 * @keyword
	 * @matching_text 开机::开机一下::MTI0MTI0::c2dhZ2E=
	 * @assembly dcim,other,culoddcim
	 * @param .title:ip地址 keyword:ip matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 * @param .title:ip地址2 keyword:ip2 matching_text_preg:L14oPzooPzoyWzAtNF1bMC05XVwuKXwoPzoyNVswLTVdXC4pfCg/OjFbMC05XVswLTldXC4pfCg/OlsxLTldWzAtOV1cLil8KD86WzAtOV1cLikpezN9KD86KD86MlswLTVdWzAtNV0pfCg/OjI1WzAtNV0pfCg/OjFbMC05XVswLTldKXwoPzpbMS05XVswLTldKXwoPzpbMC05XSkpJC8=
	 */
	public function power_off($ip)
	{
		return "完成关机";
	}
}