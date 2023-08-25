<?php

namespace app\common\logic;

class ConfigOptions
{
	private $pid;
	private $imageaddress;
	private $allowSystem;
	private $system;
	private $osIco;
	private $ext = "svg";
	public function __construct()
	{
		$this->allowSystem = config("allow_system");
		$this->system = config("system_list");
		$this->imageaddress = config("servers");
		$this->osIco = config("system");
	}
	public function filterConfigOptions($pid, $configoptions)
	{
		$allConfigArr = \think\Db::name("product_config_options")->alias("a")->field("a.*")->leftJoin("product_config_links b", "b.gid=a.gid")->where("b.pid", $pid)->where("a.hidden", 0)->order("a.order", "asc")->select()->toArray();
		$config_keys = array_keys($configoptions);
		$configoptions_filter = [];
		if (!empty($allConfigArr[0])) {
			foreach ($allConfigArr as $k => $v) {
				if (!in_array($v["id"], $config_keys)) {
					continue;
				}
				$option_type = $v["option_type"];
				$config_id = $v["id"];
				if (judgeQuantity($option_type)) {
					$qty_minimum = $v["qty_minimum"];
					$qty_maximum = $v["qty_maximum"];
					if ($configoptions[$config_id]) {
						$qty = $configoptions[$config_id] < $qty_minimum ? $qty_minimum : $configoptions[$config_id];
						$qty = $qty_maximum < $qty ? $qty_maximum : $qty;
						$configoptions_filter[$v["id"]] = $qty;
					} else {
						$configoptions_filter[$v["id"]] = $qty_minimum;
					}
				} else {
					if ($option_type == 3) {
						if ($configoptions[$config_id]) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("hidden", 0)->where("config_id", $config_id)->where("id", $configoptions[$config_id])->find();
							if (!empty($exists_data)) {
								$configoptions_filter[$config_id] = $configoptions[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("hidden", 0)->where("config_id", $config_id)->order("sort_order asc")->order("id asc")->find();
								if (!empty($sub_data)) {
									$configoptions_filter[$config_id] = $sub_data["id"];
								}
							}
						} else {
							$configoptions_filter[$config_id] = "";
						}
					} else {
						if ($configoptions[$config_id]) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("id", $configoptions[$config_id])->find();
							if (!empty($exists_data)) {
								$configoptions_filter[$config_id] = $configoptions[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("hidden", 0)->where("config_id", $config_id)->order("sort_order asc")->order("id asc")->find();
								if (!empty($sub_data)) {
									$configoptions_filter[$config_id] = $sub_data["id"];
								}
							}
						} else {
							$sub_data = \think\Db::name("product_config_options_sub")->where("hidden", 0)->where("config_id", $config_id)->order("sort_order asc")->order("id asc")->find();
							if (!empty($sub_data)) {
								$configoptions_filter[$config_id] = $sub_data["id"];
							}
						}
					}
				}
			}
		}
		return $configoptions_filter;
	}
	public function getConfigInfo($pid, $admin = false)
	{
		$pid = intval($pid);
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id as pcgid,p.id as pid,pcg.name,pcg.description,pco.id,pco.is_discount,pco.option_name,pco.linkage_pid,pco.linkage_top_pid,pco.option_type,pco.qty_minimum,pco.qty_maximum,pco.order,pco.hidden,pco.upstream_id,pco.upgrade,pco.auto,pco.notes,pco.qty_stage,pco.unit")->join("product_config_links pcl", "pcl.pid = p.id")->join("product_config_groups pcg", "pcg.id = pcl.gid")->leftJoin("product_config_options pco", "pco.gid = pcg.id")->where("p.id", $pid)->where(function (\think\db\Query $query) use($admin) {
			if (!$admin) {
				$query->where("pco.hidden", 0);
			}
		})->order("pco.order ASC")->order("pco.id asc")->select()->toArray();
		$product = \think\Db::name("products")->field("api_type,upstream_price_type,upstream_price_value")->where("id", $pid)->find();
		$alloption = [];
		if (!empty($configgroups)) {
			foreach ($configgroups as $okey => $option) {
				if (!empty($option)) {
					$cid = $option["id"];
					$option["option_name"] = explode("|", $option["option_name"])[1] ? explode("|", $option["option_name"])[1] : $option["option_name"];
					if (!getEdition()) {
						$option["qty_stage"] = 1;
					}
					if ($option["option_type"] == 3) {
						$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where(function (\think\db\Query $query) use($admin) {
							if (!$admin) {
								$query->where("hidden", 0);
							}
						})->order("sort_order ASC")->order("id asc")->limit(1)->select()->toArray();
					} else {
						$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where(function (\think\db\Query $query) use($admin) {
							if (!$admin) {
								$query->where("hidden", 0);
							}
						})->order("sort_order", "ASC")->order("id asc")->select()->toArray();
					}
					if ($option["option_type"] == 5) {
						foreach ($suboptions as $subkey => $suboption) {
							if (!empty($suboption)) {
								$subid = $suboption["id"];
								$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
								$replace = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "."];
								$suboption_name = $suboption["option_name"];
								$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
								$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
								if (explode("^", $suboption["option_name"])[1]) {
									$os = explode("^", $suboption["option_name"])[0];
									$os = str_replace($replace, "", $os);
									$suboption["option_name"] = $os;
								} else {
									$os = $suboption["option_name"];
									$os = str_replace($replace, "", $os);
									$suboption["option_name"] = "os";
								}
								$os = strtolower(trim($os));
								$version = explode("^", $original_name)[1] ? explode("^", $original_name)[1] : $original_name;
								$icoName = implode("_", explode(" ", $os));
								if (strtolower($os) == "windows") {
									$base = 1;
								} elseif (strtolower($os) == "centos") {
									$base = 2;
								} elseif (strtolower($os) == "ubuntu") {
									$base = 3;
								} elseif (strtolower($os) == "debian") {
									$base = 4;
								} elseif (strtolower($os) == "esxi") {
									$base = 5;
								} elseif (strtolower($os) == "xenserver") {
									$base = 6;
								} elseif (strtolower($os) == "freebsd") {
									$base = 7;
								} elseif (strtolower($os) == "fedora") {
									$base = 8;
								} else {
									$base = 9;
								}
								$subkey = $this->osIco . $base . "." . $this->ext;
								$suboption["version"] = $version;
								$suboption["ico_url"] = $subkey;
								$suboption["system"] = $os;
								if (!empty($pricings[0])) {
									foreach ($pricings as $pkey => $pricing) {
										if (!empty($pricing)) {
											if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
												$pricing = array_map(function ($value) use($product) {
													return $value * $product["upstream_price_value"] / 100;
												}, $pricing);
											}
											$suboption["pricing"][] = $pricing;
										}
									}
									$option["sub"][] = $suboption;
								}
							}
						}
					} else {
						if (judgeNoc($option["option_type"])) {
							foreach ($suboptions as $subkey => $suboption) {
								if (!empty($suboption)) {
									$subid = $suboption["id"];
									$suboption_name = $suboption["option_name"];
									$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
									$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
									if (explode("^", $original_name)[2]) {
										$tmp = explode("^", $original_name);
										$suboption["country_code"] = trim(strtoupper($tmp[0]));
										$suboption["option_name"] = trim($tmp[1]);
										$suboption["area"] = $tmp[2];
										$suboption["area_zh"] = $tmp[2];
									} elseif (explode("^", $original_name)[1]) {
										$tmp = explode("^", $original_name);
										$suboption["country_code"] = trim(strtoupper($tmp[0]));
										$suboption["option_name"] = trim($tmp[1]);
										$suboption["area"] = "";
										$suboption["area_zh"] = "";
									} else {
										$suboption["country_code"] = "";
										$suboption["option_name"] = "";
										$suboption["area"] = "";
										$suboption["area_zh"] = "";
									}
									$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
									if (!empty($pricings[0])) {
										foreach ($pricings as $pkey => $pricing) {
											if (!empty($pricing)) {
												if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
													$pricing = array_map(function ($value) use($product) {
														return $value * $product["upstream_price_value"] / 100;
													}, $pricing);
												}
												$suboption["pricing"][] = $pricing;
											}
										}
										$option["sub"][] = $suboption;
									}
								}
							}
						} else {
							foreach ($suboptions as $subkey => $suboption) {
								if (!empty($suboption)) {
									$subid = $suboption["id"];
									$suboption_name = $suboption["option_name"];
									$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
									$suboption["option_name"] = explode("^", $suboption["option_name"])[1] ? explode("^", $suboption["option_name"])[1] : $suboption["option_name"];
									$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
									$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
									if (!empty($pricings[0])) {
										foreach ($pricings as $pkey => $pricing) {
											if (!empty($pricing)) {
												if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
													$pricing = array_map(function ($value) use($product) {
														return $value * $product["upstream_price_value"] / 100;
													}, $pricing);
												}
												$suboption["pricing"][] = $pricing;
											}
										}
										$option["sub"][] = $suboption;
									}
								}
							}
						}
					}
					$alloption[] = $option;
				}
			}
		}
		return $alloption;
	}
	public function configShow($configInfo, $user_currcy_id, $billingcycle, $uid = 0, $upgrade = false)
	{
		$currency = \think\Db::name("currencies")->field("id,prefix,suffix")->where("id", $user_currcy_id)->find();
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		if ($billingcycle != "free") {
			$price_type = config("price_type")[$billingcycle];
		}
		$config_array = [];
		$i = 0;
		foreach ($configInfo as $key => $val) {
			$j = 0;
			$config_array[$i]["id"] = $val["id"];
			$config_array[$i]["pid"] = $val["pid"];
			$config_array[$i]["option_name"] = $val["option_name"];
			$config_array[$i]["option_type"] = $val["option_type"];
			$config_array[$i]["notes"] = $val["notes"];
			$config_array[$i]["qty_minimum"] = $val["qty_minimum"];
			$config_array[$i]["qty_maximum"] = $val["qty_maximum"];
			$config_array[$i]["upgrade"] = $val["upgrade"];
			$config_array[$i]["is_discount"] = $val["is_discount"];
			$config_array[$i]["qty_stage"] = $val["qty_stage"];
			$config_array[$i]["unit"] = $val["unit"];
			$config_array[$i]["linkage_pid"] = $val["linkage_pid"];
			$config_array[$i]["linkage_top_pid"] = $val["linkage_top_pid"];
			$sub = $val["sub"];
			$option_sub = [];
			if (!$upgrade) {
				$bates = 0;
				$flags = false;
				$flag = getSaleProductUser($val["pid"], $uid);
				if ($flag && $flag["type"] == 1 && $val["is_discount"] == 1) {
					$bates = 1 - $flag["bates"] / 100;
					$flags = true;
				}
			} else {
				$bates = 0;
				$flags = false;
			}
			$qty_minimum = [];
			$qty_maximum = [];
			if ($val["option_type"] == 5) {
				foreach ($sub as $k => $v) {
					$version = [];
					if (!isset($option_sub[$v["option_name"]]["child"])) {
						$option_sub[$v["option_name"]]["child"] = [];
					}
					$version["id"] = $v["id"];
					$version["version"] = $v["version"];
					$show_pricing = "";
					$pricing = $v["pricing"];
					if ($billingcycle != "free") {
						foreach ($pricing as $s => $t) {
							if ($t["currency"] == $user_currcy_id) {
								if ($flags) {
									$t[$price_type[0]] = bcsub($t[$price_type[0]], bcmul($t[$price_type[0]], $bates, 2), 2);
								}
								if ($t[$price_type[0]] > 0) {
									$show_pricing = " " . $prefix . $t[$price_type[0]] . $suffix;
								} else {
									$show_pricing = " " . $prefix . "0.00" . $suffix;
								}
								$show_pricing1 = $t[$price_type[0]];
							}
						}
					}
					$version["pricing"] = $show_pricing1;
					$version["show_pricing"] = $v["version"] . $show_pricing;
					$option_sub[$v["option_name"]]["ico_url"] = $v["ico_url"];
					$option_sub[$v["option_name"]]["child"][] = $version;
					$option_sub[$v["option_name"]]["qty_stage"] = $val["qty_stage"];
				}
			} else {
				if (judgeNoc($val["option_type"])) {
					foreach ($sub as $k => $v) {
						$area = [];
						if (!isset($option_sub[$v["option_name"]])) {
							$option_sub[$v["option_name"]] = [];
						}
						$area["id"] = $v["id"];
						$show_pricing = "";
						$pricing = $v["pricing"];
						if ($billingcycle != "free") {
							foreach ($pricing as $s => $t) {
								if ($t["currency"] == $user_currcy_id) {
									if ($flags) {
										$t[$price_type[0]] = bcsub($t[$price_type[0]], bcmul($t[$price_type[0]], $bates, 2), 2);
									}
									if ($t[$price_type[0]] > 0) {
										$show_pricing = " " . $prefix . $t[$price_type[0]] . $suffix;
									} else {
										$show_pricing = " " . $prefix . "0.00" . $suffix;
									}
									$show_pricing1 = $t[$price_type[0]];
								}
							}
						}
						$area["pricing"] = $show_pricing1;
						$area["area"] = $v["area"];
						$area["area_zh"] = $v["area_zh"];
						$area["show_pricing"] = $v["area"] . $show_pricing;
						$option_sub[$v["option_name"]]["option_name"] = $v["option_name"];
						$option_sub[$v["option_name"]]["option_name_first"] = $v["option_name_first"];
						$option_sub[$v["option_name"]]["country_code"] = strtoupper($v["country_code"]);
						$option_sub[$v["option_name"]]["area"][] = $area;
						$option_sub[$v["option_name"]]["qty_stage"] = $val["qty_stage"];
					}
					$option_sub = combine($option_sub, "option_name", "area");
				} else {
					$j = 0;
					foreach ($sub as $k => $v) {
						$qty_minimum[] = $v["qty_minimum"];
						$qty_maximum[] = $v["qty_maximum"];
						$option_sub[$j]["id"] = $v["id"];
						$option_sub[$j]["config_id"] = $v["config_id"];
						$option_sub[$j]["qty_minimum"] = $v["qty_minimum"];
						$option_sub[$j]["qty_maximum"] = $v["qty_maximum"];
						$option_sub[$j]["option_name"] = $v["option_name"];
						$option_sub[$j]["option_name_first"] = $v["option_name_first"];
						$show_pricing = "";
						$pricing = $v["pricing"];
						if ($billingcycle != "free") {
							foreach ($pricing as $s => $t) {
								if ($t["currency"] == $user_currcy_id) {
									if ($flags) {
										$t[$price_type[0]] = bcsub($t[$price_type[0]], bcmul($t[$price_type[0]], $bates, 2), 2);
									}
									if ($t[$price_type[0]] > 0) {
										$show_pricing = " " . $prefix . $t[$price_type[0]] . $suffix;
									} else {
										$show_pricing = " " . $prefix . "0.00" . $suffix;
									}
									$show_pricing1 = $t[$price_type[0]];
								}
							}
						}
						$option_sub[$j]["pricing"] = $show_pricing1;
						$option_sub[$j]["qty_stage"] = $val["qty_stage"];
						$option_sub[$j]["show_pricing"] = $v["option_name"] . $show_pricing;
						$j++;
					}
				}
			}
			if (count($qty_minimum) > 0) {
				$config_array[$i]["qty_minimum"] = min($qty_minimum);
			}
			if (count($qty_maximum) > 0) {
				$config_array[$i]["qty_maximum"] = max($qty_maximum);
			}
			$config_array[$i]["sub"] = $option_sub;
			$i++;
		}
		return $config_array;
	}
	/**
	 * @title 前台产品内页可配置选项显示数组
	 * @param .name:hostid desc:主机id
	 */
	public function showInfo($pid, $host_id, $currency, $billingcycle, $show_price = true)
	{
		$currencyid = $currency["id"];
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$config_show = [];
		$pid = intval($pid);
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id,pcg.name,pcg.description")->join(["shd_product_config_links" => "pcl"], "pcl.pid = p.id")->join(["shd_product_config_groups" => "pcg"], "pcg.id = pcl.gid")->where("p.id", $pid)->select()->toArray();
		$host_config = \think\Db::name("host_config_options")->where("relid", $host_id)->order("configid")->column("configid");
		$alloption = [];
		foreach ($configgroups as $ckey => $configgroup) {
			if (!empty($configgroup)) {
				$gid = $configgroup["id"];
				$options = \think\Db::name("product_config_options")->where("gid", $gid)->where("hidden", 0)->select()->toArray();
				foreach ($options as $okey => $option) {
					if ($option["option_type"] == 20 && !in_array($option["id"], $host_config)) {
						continue;
					}
					if (!empty($option)) {
						$cid = $option["id"];
						$host_config_data = \think\Db::name("host_config_options")->where("configid", $cid)->where("relid", $host_id)->find();
						$suboptions = [];
						if (!empty($host_config_data)) {
							$host_optionid = $host_config_data["optionid"];
							$host_config_id = $host_config_data["id"];
							$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where("id", $host_optionid)->find();
						} else {
							$host_config_data = ["relid" => $host_id, "configid" => $cid];
							$host_config_id = \think\Db::name("host_config_options")->insertGetId($host_config_data);
						}
						if (empty($suboptions)) {
							$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where("hidden", 0)->find();
							if (empty($suboptions)) {
								continue;
							}
						}
						$config_show[$cid]["id"] = $option["id"];
						$config_show[$cid]["pid"] = $option["linkage_top_pid"];
						$config_show[$cid]["option_type"] = $option["option_type"];
						$config_show[$cid]["name"] = explode("|", $option["option_name"])[1] ? explode("|", $option["option_name"])[1] : $option["option_name"];
						$config_show[$cid]["name_k"] = explode("|", $option["option_name"])[0] ? explode("|", $option["option_name"])[0] : $option["option_name"];
						$suboptions_id = $suboptions["id"];
						$host_config_data["optionid"] = $suboptions_id;
						$price_data = \think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currencyid)->where("relid", $suboptions_id)->find();
						$now_price = $price_data[$billingcycle];
						if ($option["option_type"] == 3) {
							if ($host_config_data["qty"] == 1) {
								$config_show[$cid]["sub_name"] = "是";
								if (!empty($now_price) && $show_price) {
									$config_show[$cid]["sub_name"] .= " " . $prefix . $now_price . $suffix;
								}
							} else {
								$host_config_data["qty"] = 0;
								$config_show[$cid]["sub_name"] = "否";
							}
						} else {
							if (judgeQuantity($option["option_type"])) {
								$qty_minimum = $option["qty_minimum"];
								$config_show[$cid]["sub_name"] = $host_config_data["qty"] . $option["unit"];
								if (!empty($now_price) && $show_price) {
									$config_show[$cid]["sub_name"] .= " " . $prefix . $now_price . $suffix;
								}
							} else {
								$config_show[$cid]["sub_name"] = explode("|", $suboptions["option_name"])[1] ? explode("|", $suboptions["option_name"])[1] : $suboptions["option_name"];
								$pos = strpos($config_show[$cid]["sub_name"], "^");
								if ($pos !== false) {
									$sub_arr = explode("^", $config_show[$cid]["sub_name"]);
									$config_show[$cid]["sub_name"] = $sub_arr[1];
									if ($option["option_type"] == 5) {
										$config_show[$cid]["os_group"] = $sub_arr[0];
									} elseif ($option["option_type"] == 12) {
										$config_show[$cid]["code"] = $sub_arr[0];
									}
								}
								if (!empty($now_price) && $show_price) {
									$config_show[$cid]["sub_name"] .= " " . $prefix . $now_price . $suffix;
								}
							}
						}
						\think\Db::name("host_config_options")->where("id", $host_config_id)->update($host_config_data);
					}
				}
			}
		}
		return changeTwoArr(toTree($config_show));
	}
	/**
	 * @title 更新产品可配置选项后台
	 * @param .name:hostid desc:主机id
	 * configoption[11]: 21
	 * configoption[12]: 23
	 */
	public function updateConfig($hostid, $configoption, $new_productid = 0, $product_info = [])
	{
		$log_desc = "";
		$pid = \think\Db::name("host")->where("id", $hostid)->value("productid");
		$host_config_options = \think\Db::name("host_config_options")->where("relid", $hostid)->select()->toArray();
		if ($pid != $new_productid && $new_productid > 0) {
			$host_del = [];
			$config_links = \think\Db::name("product_config_links")->where("pid", $pid)->select()->toArray();
			$new_config_links = \think\Db::name("product_config_links")->where("pid", $new_productid)->select()->toArray();
			$intersect = array_intersect(array_column($config_links, "gid"), array_column($new_config_links, "gid"));
			if (count($intersect) > 0) {
				$old_config_options = \think\Db::name("product_config_options")->alias("o")->field("o_sub.config_id,o_sub.id")->leftJoin("product_config_options_sub o_sub", "o_sub.config_id=o.id")->whereIn("o.gid", $intersect)->select()->toArray();
				$old_configoptions = array_column($old_config_options, "id", "config_id");
				foreach ($host_config_options as $v) {
					if ($old_configoptions[$v["configid"]] != $v["optionid"]) {
						$host_del[] = $v["id"];
					}
				}
			}
			if ($product_info["api_type"] != "zjmf_api") {
				\think\Db::name("host")->where("id", $hostid)->update(["serverid" => 0]);
			}
			$new_config = \think\Db::name("product_config_options_sub")->alias("o_sub")->field("o.hidden,o.option_name,o.qty_minimum,o.qty_maximum,o_sub.option_name as option_name_sub,o_sub.qty_minimum as qty_minimum_sub,o_sub.qty_maximum as qty_maximum_sub,o_sub.id,o_sub.config_id,o.option_type,o.is_discount,o.option_name,o.is_rebate")->leftJoin("product_config_options o", "o_sub.config_id=o.id")->leftJoin("product_config_links links", "o.gid=links.gid")->where("links.pid", $new_productid)->order("o_sub.sort_order ASC")->order("o_sub.id ASC")->select()->toArray();
			$old_config = \think\Db::name("host_config_options")->alias("h")->field("o.option_name,o.qty_minimum,o.qty_maximum,o_sub.option_name as option_name_sub,o_sub.qty_minimum as qty_minimum_sub,o_sub.qty_maximum as qty_maximum_sub,h.qty,o.option_type")->leftJoin("product_config_options o", "o.id=h.configid")->leftJoin("product_config_options_sub o_sub", "o_sub.id=h.optionid")->where("h.relid", $hostid)->select()->toArray();
			$old_con = [];
			$qty = [];
			foreach ($old_config as $v) {
				$config = md5($v["option_name"] . $v["qty_minimum"] . $v["qty_maximum"]);
				$option = md5($v["option_name_sub"] . $v["qty_minimum_sub"] . $v["qty_maximum_sub"]);
				$old_con[$config] = $option;
				$qty[$config] = $v["qty"];
			}
			$new_con = [];
			$host_configoptions = [];
			$config_id = [];
			foreach ($new_config as $v) {
				$host = [];
				$config = md5($v["option_name"] . $v["qty_minimum"] . $v["qty_maximum"]);
				$option = md5($v["option_name_sub"] . $v["qty_minimum_sub"] . $v["qty_maximum_sub"]);
				if ($old_con[$config] == $option) {
					$host["relid"] = $hostid;
					$host["configid"] = $v["config_id"];
					$host["optionid"] = $v["id"];
					$host["qty"] = $qty[$config];
					$config_id[] = $v["config_id"];
				} else {
					if (!in_array($v["config_id"], $config_id)) {
						$host["relid"] = $hostid;
						$host["configid"] = $v["config_id"];
						$host["optionid"] = $v["id"];
						$host["qty"] = 0;
						$config_id[] = $v["config_id"];
					}
				}
				if (count($host) > 0) {
					$host_configoptions[$v["config_id"]] = $host;
				}
			}
			if (count($host_del) > 0) {
				\think\Db::name("host_config_options")->whereIn("id", $host_del)->delete();
			} else {
				\think\Db::name("host_config_options")->where("relid", $hostid)->delete();
			}
			if (count($host_configoptions) > 0) {
				\think\Db::name("host_config_options")->insertAll($host_configoptions);
			}
		} else {
			$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id,pcg.name,pcg.description,p.type")->join(["shd_product_config_links" => "pcl"], "pcl.pid = p.id")->join(["shd_product_config_groups" => "pcg"], "pcg.id = pcl.gid")->where("p.id", $pid)->select()->toArray();
			$before_config = \think\Db::name("host_config_options")->where("relid", $hostid)->select()->toArray();
			$host_del_ids = [];
			foreach ($before_config as $key => $val) {
				if (!isset($configoption[$val["configid"]])) {
					$host_del_ids[] = $val["id"];
				}
			}
			$host_del_ids && \think\Db::name("host_config_options")->whereIn("id", $host_del_ids)->delete();
			$alloption = [];
			foreach ($configgroups as $ckey => $configgroup) {
				if (!empty($configgroup)) {
					$gid = $configgroup["id"];
					$options = \think\Db::name("product_config_options")->where("gid", $gid)->select()->toArray();
					foreach ($options as $okey => $option) {
						if ($option["option_type"] == 20 && !isset($configoption[$option["id"]])) {
							continue;
						}
						if (!empty($option)) {
							$cid = $option["id"];
							$option_type = $option["option_type"];
							$option_qty_minimum = $option["qty_minimum"];
							$option_qty_maximum = $option["qty_maximum"];
							if (judgeQuantity($option_type)) {
								$config_value = $config_value_old = $configoption[$cid] ?: 0;
								$config_value = $config_value <= $option_qty_minimum ? $option_qty_minimum : $config_value;
								$config_value = $option_qty_maximum <= $config_value ? $option_qty_maximum : $config_value;
								$suboptions = \think\Db::name("product_config_options_sub")->where("qty_minimum", "<=", $config_value)->where("qty_maximum", ">=", $config_value)->where("config_id", $cid)->order("sort_order asc")->order("id asc")->find();
								if (!empty($suboptions)) {
									$suboptions_id = $suboptions["id"];
									$this->saveHostConfig($hostid, $cid, $suboptions_id, $config_value_old);
								} else {
									continue;
								}
							} else {
								if (judgeYesNo($option_type)) {
									$config_value = $configoption[$cid] ?: 0;
									if (!empty($config_value)) {
										$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", "=", $cid)->find();
										if (!empty($suboptions)) {
											$suboptions_id = $suboptions["id"];
											$this->saveHostConfig($hostid, $cid, $suboptions_id, 1);
										} else {
											continue;
										}
									} else {
										\think\Db::name("host_config_options")->where("relid", $hostid)->where("configid", $cid)->delete();
										continue;
									}
								} else {
									$config_value = $configoption[$cid] ?: 0;
									if (!empty($config_value)) {
										$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", "=", $cid)->where("id", $config_value)->find();
										if (!empty($exists_data)) {
											$suboptions_id = $exists_data["id"];
											$this->saveHostConfig($hostid, $cid, $suboptions_id, 0);
										} else {
											$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", "=", $cid)->find();
											if (!empty($suboptions)) {
												$suboptions_id = $suboptions["id"];
												$this->saveHostConfig($hostid, $cid, $suboptions_id, 0);
											} else {
												continue;
											}
										}
									} else {
										$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", "=", $cid)->find();
										if (!empty($suboptions)) {
											$suboptions_id = $suboptions["id"];
											$this->saveHostConfig($hostid, $cid, $suboptions_id, 0);
										} else {
											continue;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return ["status" => 200, "log_desc" => $log_desc];
	}
	private function saveHostConfig($hostid, $cid, $suboptions_id, $qty = 0)
	{
		$exists_data = \think\Db::name("host_config_options")->where("relid", $hostid)->where("configid", $cid)->find();
		if (!empty($exists_data)) {
			\think\Db::name("host_config_options")->where("relid", $hostid)->where("configid", $cid)->update(["optionid" => $suboptions_id, "qty" => $qty]);
		} else {
			$idata = ["relid" => $hostid, "configid" => $cid, "optionid" => $suboptions_id, "qty" => $qty];
			\think\Db::name("host_config_options")->insert($idata);
		}
	}
	public function setHostid($host_id)
	{
		$this->host_id = $host_id;
	}
	public function getOs($configid, $subid)
	{
		$data = ["os" => "", "os_url" => ""];
		$config = \think\Db::name("product_config_options")->where("id", $configid)->find();
		if (!judgeOs($config["option_type"])) {
			return $data;
		}
		$suboption = \think\Db::name("product_config_options_sub")->where("id", $subid)->find();
		$replace = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "."];
		$suboption["option_name"] = explode("|", $suboption["option_name"])[1] ? explode("|", $suboption["option_name"])[1] : $suboption["option_name"];
		if (explode("^", $suboption["option_name"])[1]) {
			$os = explode("^", $suboption["option_name"])[1];
			$os_url = explode("^", $suboption["option_name"])[0];
			$os_url = str_replace($replace, "", $os_url);
		} else {
			$os_url = "";
			$os = $suboption["option_name"];
		}
		return $data = ["os" => $os, "os_url" => $os_url];
	}
	public function getLinkAgeList($req)
	{
		$cid = intval($req->cid);
		$all_linkage = \think\Db::name("product_config_options")->where("id = " . $cid . " and linkage_pid = 0");
		if ($req->sub_id) {
			$sub_data = \think\Db::name("product_config_options_sub")->where("id", $req->sub_id)->value("linkage_level");
			if ($sub_data) {
				$sub_data_arr = explode("-", $sub_data);
				unset($sub_data_arr[0]);
				$config_id = \think\Db::name("product_config_options_sub")->whereIn("id", $sub_data_arr)->whereOr("linkage_pid", "in", $sub_data_arr)->column("config_id");
				$focus_query = "id in (" . implode(",", $config_id) . ")";
				$all_linkage->whereOr($focus_query);
			}
		}
		$all_linkage = $all_linkage->select()->toArray();
		if (!$all_linkage) {
			return [];
		}
		$all_linkage = array_column($all_linkage, null, "id");
		$all_linkage_ids = array_column($all_linkage, "id");
		foreach ($all_linkage as $k => $v) {
			$all_linkage[$k]["sub_son"] = [];
		}
		$linkage_sub = \think\Db::name("product_config_options_sub")->whereIn("config_id", $all_linkage_ids)->order("sort_order", "asc")->cursor();
		foreach ($linkage_sub as $key => $val) {
			$val["disabled"] = false;
			if (isset($all_linkage[$val["config_id"]])) {
				$all_linkage[$val["config_id"]]["sub_son"][] = $val;
			}
		}
		unset($linkage_sub);
		return $all_linkage;
	}
	public function setLinkAgeListDefaultVal($list, $param)
	{
		if (!$list || !$param) {
			return $list;
		}
		$check_data = \think\Db::name("product_config_options")->where("id", $param["cid"])->find();
		if ($check_data && $check_data["linkage_pid"] != 0) {
			$check_data = \think\Db::name("product_config_options")->where("id", $check_data["linkage_top_pid"])->find();
		}
		if (!$check_data) {
			return $list;
		}
		if (!isset($param["sub_id"])) {
			$param["sub_id"] = $this->getSubId($list);
		}
		$sub_data_val = \think\Db::name("product_config_options_sub")->where("id", $param["sub_id"])->value("linkage_level");
		$sub_data_val_arr = explode("-", $sub_data_val);
		$tree_data = $this->getTree($list);
		$data = $this->changeTwoArr($tree_data, "son", $sub_data_val_arr);
		return $data;
	}
	public function getSubId(&$list)
	{
		$sub_id = 0;
		foreach ($list as $key => &$val) {
			if (!isset($val["sub_son"]) || empty($val["sub_son"])) {
				continue;
			}
			if ($val["linkage_pid"] == 0) {
				return $val["sub_son"][0]["id"];
			}
		}
		return $sub_id;
	}
	public function getTree($data, $son = "son")
	{
		if (empty($data)) {
			return [];
		}
		$_data = array_column($data, null, "id");
		$result = [];
		foreach ($_data as $key => $val) {
			if (isset($_data[$val["linkage_pid"]])) {
				$_data[$val["linkage_pid"]][$son][] =& $_data[$key];
			} else {
				$result[] =& $_data[$key];
			}
		}
		return $result;
	}
	private function changeTwoArr($tree, $children = "son", $arr = [], $i = 1, $sub_pid = 0)
	{
		$imparr = [];
		foreach ($tree as $w) {
			if (isset($arr[$i])) {
				$w["checkSubId"] = $arr[$i];
			} else {
				$arr[] = $w["sub_son"][0]["id"] ?? 0;
				$w["checkSubId"] = $w["sub_son"][0]["id"] ?? 0;
				unset($w[$children]);
			}
			foreach ($w["sub_son"] as $sub_k => $sub_v) {
				if ($sub_v["linkage_pid"] != $sub_pid) {
					unset($w["sub_son"][$sub_k]);
				}
			}
			if (isset($w[$children])) {
				$t = $w[$children];
				unset($w[$children]);
				$imparr[] = $w;
				if (is_array($t)) {
					$imparr = array_merge($imparr, $this->changeTwoArr($t, $children, $arr, ++$i, $w["checkSubId"]));
				}
			} else {
				$imparr[] = $w;
			}
		}
		return $imparr;
	}
	public function webGetLinkAgeList($req)
	{
		$cid = intval($req->cid);
		$param = $req->param();
		if (!isset($param["sub_id"])) {
			$param["sub_id"] = $this->webGetSubId($req);
		}
		$all_linkage = \think\Db::name("product_config_options")->where("id = " . $cid . " and linkage_pid = 0");
		$sub_data = \think\Db::name("product_config_options_sub")->where("id", $param["sub_id"])->value("linkage_level");
		if ($sub_data) {
			$sub_data = explode("-", $sub_data);
			unset($sub_data[0]);
			$config_id = \think\Db::name("product_config_options_sub")->whereIn("id", $sub_data)->whereOr("linkage_top_pid", "in", $sub_data)->column("config_id");
			if (!!array_unique($config_id)) {
				$focus_query = "id in (" . implode(",", array_unique($config_id)) . ")";
				$all_linkage->whereOr($focus_query);
			}
		}
		$all_linkage = $all_linkage->select()->toArray();
		if (!$all_linkage) {
			return [];
		}
		$all_linkage = array_column($all_linkage, null, "id");
		$all_linkage_ids = array_column($all_linkage, "id");
		foreach ($all_linkage as $k => $v) {
			$all_linkage[$k]["sub_son"] = [];
		}
		$linkage_sub = \think\Db::name("product_config_options_sub")->whereIn("config_id", $all_linkage_ids)->order("sort_order", "asc")->cursor();
		foreach ($linkage_sub as $key => $val) {
			if (isset($all_linkage[$val["config_id"]])) {
				$all_linkage[$val["config_id"]]["sub_son"][] = $val;
			}
		}
		unset($linkage_sub);
		return $all_linkage;
	}
	public function webSetLinkAgeListDefaultVal($list, $req)
	{
		if ($req->cid) {
			$param["cid"] = $req->cid;
		}
		if ($req->sub_id) {
			$param["sub_id"] = $req->sub_id;
		}
		if (!$list || !$param) {
			return $list;
		}
		if (!isset($param["sub_id"])) {
			$param["sub_id"] = $this->webGetSubId($req);
		}
		$check_data = \think\Db::name("product_config_options")->where("id", $param["cid"])->find();
		if ($check_data && $check_data["linkage_pid"] != 0) {
			$check_data = \think\Db::name("product_config_options")->where("id", $check_data["linkage_top_pid"])->find();
		}
		if (!$check_data) {
			return $list;
		}
		$sub_data_val = \think\Db::name("product_config_options_sub")->where("id", $param["sub_id"])->value("linkage_level");
		$sub_data_val_arr = explode("-", $sub_data_val);
		$tree_data = $this->getTree($list);
		$data = $this->webChangeTwoArr($tree_data, "son", $sub_data_val_arr);
		return $data;
	}
	public function webGetSubId($req)
	{
		$sub_id = 0;
		$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $req->cid)->order("sort_order", "asc")->find();
		if ($sub_data) {
			$sub_id = $sub_data["id"];
		}
		return $sub_id;
	}
	private function webChangeTwoArr($tree, $children = "son", $arr = [], $i = 1, $sub_pid = 0)
	{
		$imparr = [];
		if (!isset($arr[$i])) {
			if ($i > 1) {
				$config_id = \think\Db::name("product_config_options_sub")->where("linkage_pid", $arr[$i - 1])->column("config_id");
				$in_array = 0;
				foreach ($tree as $val) {
					if (in_array($val["id"], $config_id)) {
						$in_array = 1;
						$tree = [$val];
						break;
					}
				}
				if ($in_array == 0) {
					unset($tree);
				}
			}
		} else {
			$config_id = \think\Db::name("product_config_options_sub")->where("id", $arr[$i])->value("config_id");
			$tree = array_column($tree, null, "id");
			$tree = [$tree[$config_id]];
		}
		foreach ($tree as $w) {
			if (isset($arr[$i])) {
				$w["checkSubId"] = $arr[$i];
			} else {
				$arr[$i] = $w["sub_son"][0]["id"] ?? 0;
				$w["checkSubId"] = $w["sub_son"][0]["id"] ?? 0;
			}
			foreach ($w["sub_son"] as $sub_k => $sub_v) {
				if ($sub_v["linkage_pid"] != $sub_pid) {
					unset($w["sub_son"][$sub_k]);
				}
			}
			if (isset($w[$children])) {
				$t = $w[$children];
				unset($w[$children]);
				$imparr[] = $w;
				if (is_array($t)) {
					$imparr = array_merge($imparr, $this->webChangeTwoArr($t, $children, $arr, ++$i, $w["checkSubId"]));
				}
			} else {
				$imparr[] = $w;
			}
		}
		return $imparr;
	}
	public function openAPIconfig($pid, $admin = false)
	{
		$pid = intval($pid);
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id as pcgid,p.id as pid,pcg.name,pcg.description,pco.id,pco.is_discount,pco.option_name,pco.linkage_pid,pco.linkage_top_pid,pco.option_type,pco.qty_minimum,pco.qty_maximum,pco.order,pco.hidden,pco.upstream_id,pco.upgrade,pco.auto,pco.notes,pco.qty_stage,pco.unit")->join("product_config_links pcl", "pcl.pid = p.id")->join("product_config_groups pcg", "pcg.id = pcl.gid")->leftJoin("product_config_options pco", "pco.gid = pcg.id")->where("p.id", $pid)->where(function (\think\db\Query $query) use($admin) {
			if (!$admin) {
				$query->where("pco.hidden", 0);
			}
		})->order("pco.order ASC")->order("pco.id asc")->select()->toArray();
		$product = \think\Db::name("products")->field("api_type,upstream_price_type,upstream_price_value")->where("id", $pid)->find();
		$alloption = [];
		if (!empty($configgroups)) {
			foreach ($configgroups as $okey => $option) {
				if (!empty($option)) {
					$cid = $option["id"];
					$option["option_name"] = explode("|", $option["option_name"])[1] ? explode("|", $option["option_name"])[1] : $option["option_name"];
					if (!getEdition()) {
						$option["qty_stage"] = 1;
					}
					if ($option["option_type"] == 3) {
						$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where(function (\think\db\Query $query) use($admin) {
							if (!$admin) {
								$query->where("hidden", 0);
							}
						})->order("sort_order ASC")->order("id asc")->limit(1)->select()->toArray();
					} else {
						$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->where(function (\think\db\Query $query) use($admin) {
							if (!$admin) {
								$query->where("hidden", 0);
							}
						})->order("sort_order", "ASC")->order("id asc")->select()->toArray();
					}
					if ($option["option_type"] == 5) {
						foreach ($suboptions as $subkey => $suboption) {
							if (!empty($suboption)) {
								$subid = $suboption["id"];
								$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
								$replace = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "."];
								$suboption_name = $suboption["option_name"];
								$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
								$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
								if (explode("^", $suboption["option_name"])[1]) {
									$os = explode("^", $suboption["option_name"])[0];
									$os = str_replace($replace, "", $os);
									$suboption["option_name"] = $os;
								} else {
									$os = $suboption["option_name"];
									$os = str_replace($replace, "", $os);
									$suboption["option_name"] = "os";
								}
								$os = strtolower(trim($os));
								$version = explode("^", $original_name)[1] ? explode("^", $original_name)[1] : $original_name;
								$icoName = implode("_", explode(" ", $os));
								if (strtolower($os) == "windows") {
									$base = 1;
								} elseif (strtolower($os) == "centos") {
									$base = 2;
								} elseif (strtolower($os) == "ubuntu") {
									$base = 3;
								} elseif (strtolower($os) == "debian") {
									$base = 4;
								} elseif (strtolower($os) == "esxi") {
									$base = 5;
								} elseif (strtolower($os) == "xenserver") {
									$base = 6;
								} elseif (strtolower($os) == "freebsd") {
									$base = 7;
								} elseif (strtolower($os) == "fedora") {
									$base = 8;
								} else {
									$base = 9;
								}
								$subkey = $this->osIco . $base . "." . $this->ext;
								$suboption["version"] = $version;
								$suboption["ico_url"] = $subkey;
								$suboption["system"] = $os;
								if (!empty($pricings[0])) {
									foreach ($pricings as $pkey => $pricing) {
										if (!empty($pricing)) {
											if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
												$pricing = array_map(function ($value) use($product) {
													return $value * $product["upstream_price_value"] / 100;
												}, $pricing);
											}
											$suboption["pricing"][] = $pricing;
										}
									}
									$option["sub"][] = $suboption;
								}
							}
						}
					} else {
						if (judgeNoc($option["option_type"])) {
							foreach ($suboptions as $subkey => $suboption) {
								if (!empty($suboption)) {
									$subid = $suboption["id"];
									$suboption_name = $suboption["option_name"];
									$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
									$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
									if (explode("^", $original_name)[2]) {
										$tmp = explode("^", $original_name);
										$suboption["country_code"] = trim(strtoupper($tmp[0]));
										$suboption["option_name"] = trim($tmp[1]);
										$suboption["area"] = $tmp[2];
										$suboption["area_zh"] = $tmp[2];
									} elseif (explode("^", $original_name)[1]) {
										$tmp = explode("^", $original_name);
										$suboption["country_code"] = trim(strtoupper($tmp[0]));
										$suboption["option_name"] = trim($tmp[1]);
										$suboption["area"] = "";
										$suboption["area_zh"] = "";
									} else {
										$suboption["country_code"] = "";
										$suboption["option_name"] = "";
										$suboption["area"] = "";
										$suboption["area_zh"] = "";
									}
									$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
									if (!empty($pricings[0])) {
										foreach ($pricings as $pkey => $pricing) {
											if (!empty($pricing)) {
												if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
													$pricing = array_map(function ($value) use($product) {
														return $value * $product["upstream_price_value"] / 100;
													}, $pricing);
												}
												$suboption["pricing"][] = $pricing;
											}
										}
										$option["sub"][] = $suboption;
									}
								}
							}
						} else {
							foreach ($suboptions as $subkey => $suboption) {
								if (!empty($suboption)) {
									$subid = $suboption["id"];
									$suboption_name = $suboption["option_name"];
									$original_name = $suboption["option_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
									$suboption["option_name"] = explode("^", $suboption["option_name"])[1] ? explode("^", $suboption["option_name"])[1] : $suboption["option_name"];
									$suboption["option_name_first"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[0] : $suboption_name;
									$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
									if (!empty($pricings[0])) {
										foreach ($pricings as $pkey => $pricing) {
											if (!empty($pricing)) {
												if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
													$pricing = array_map(function ($value) use($product) {
														return $value * $product["upstream_price_value"] / 100;
													}, $pricing);
												}
												$suboption["pricing"][] = $pricing;
											}
										}
										$option["sub"][] = $suboption;
									}
								}
							}
						}
					}
					$alloption[] = $option;
				}
			}
		}
		return $alloption;
	}
}