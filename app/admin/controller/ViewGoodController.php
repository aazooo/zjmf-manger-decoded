<?php

namespace app\admin\controller;

class ViewGoodController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 商品管理、编辑商品：详情，定价，试用，接口设置，产品配置，升级选项，自定义字段，商品推介计划，文件下载，链接
	 */
	public function configproducts(\think\Request $request)
	{
		$result["tip"] = "在这里管理您的所有商品和服务。每个商品都必须分配到一个在前台订购页面客户可见的或隐藏的分组里。商品也可以单独设为隐藏。隐藏的商品仍然可以直接使用链接地址访问和订购。";
		$result["titleList"] = ["商品名称", "类型", "定价", "库存", "已开通/总数量", "自动开通", "操作"];
		$result["btnText"] = "新增分组";
		return $this->display($result);
	}
	/**
	 * 添加配置项
	 */
	public function configproductoptionsedit(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 高级配置
	 */
	public function configproductoptionsaddon(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 流量包管理
	 */
	public function configtraffic(\think\Request $request)
	{
		$result["tip"] = "创建流量包，所有关联流量包并限制流量的产品均可以订购流量包";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "流量包名称", "流量(GB)", "价格", "启用", "已售/库存", "创建时间", "管理"];
		$result["btnText"] = "新增流量包";
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$page = $_GET["page"] ? $_GET["page"] : $page;
			$limit = $_GET["limit"] ? $_GET["limit"] : $limit;
			$orderby = $_GET["orderby"] ? $_GET["orderby"] : $orderby;
			$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->search = $_GET["search"];
		}
		$DcimManage = controller("dcim");
		$_GET["sort"] = $_GET["sort"] ? $_GET["sort"] : "desc";
		$_GET["orderby"] = $_GET["orderby"] ? $_GET["orderby"] : "id";
		$res = $DcimManage->listFlowPacket();
		$result["total"] = $res["data"]["sum"];
		$pageSize = \intval($res["data"]["sum"] / 10) + ($res["data"]["sum"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$res["pageSize"][$i - 1] = $i;
		}
		foreach ($res["data"]["list"] as &$item) {
			if ($item["create_time"]) {
				$item["create_time_format"] = date("Y-m-d H:i:s", $item["create_time"]);
			} else {
				$item["create_time_format"] = "-";
			}
		}
		$result["datalist"] = $res["data"]["list"];
		$result["search"] = $_GET["search"] ? $_GET["search"] : "";
		return $this->display($result);
	}
	/**
	 * 通用接口
	 */
	public function configservermodule(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 添加新接口
	 */
	public function configservermoduleedit(\think\Request $request)
	{
		$result["tip"] = "在这里配置与我们通信的所有接口。每个模块的默认接口都标有星号（*）。为保证接口自动设置正常运行，您必须选择一个默认的接口。";
		$result["btnText"] = "添加新接口";
		return $this->display($result);
	}
	/**
	 * 魔方DCIM
	 */
	public function configdcimmoodule(\think\Request $request)
	{
		$result["tip"] = "查看取消的订单，添加客户端取消原因。<a href='https://www.idcsmart.com/wiki_list/338.html' target='_blank'>帮助文档</a>";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "刷新", "名称", "IP", "服务器数量", "区域", "操作"];
		$result["btnText"] = "新增接口";
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$page = $_GET["page"] ? $_GET["page"] : $page;
			$limit = $_GET["limit"] ? $_GET["limit"] : $limit;
			$orderby = $_GET["orderby"] ? $_GET["orderby"] : $orderby;
			$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->search = $_GET["search"];
		}
		$DcimManage = controller("dcim");
		$_GET["sort"] = $_GET["sort"] ? $_GET["sort"] : "desc";
		$_GET["orderby"] = $_GET["orderby"] ? $_GET["orderby"] : "id";
		$res = $DcimManage->serverList();
		$result["total"] = $res["data"]["sum"];
		$pageSize = \intval($res["data"]["sum"] / 10) + ($res["data"]["sum"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$res["pageSize"][$i - 1] = $i;
		}
		$result["datalist"] = $res["data"]["list"];
		$result["datalistJSON"] = json_encode($res["data"]["list"]);
		$result["search"] = $_GET["search"] ? $_GET["search"] : "";
		return $this->display($result);
	}
	/**
	 * 编辑DCIM
	 */
	public function configdcimmooduleedit(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 魔方云
	 */
	public function configzjmfcloud(\think\Request $request)
	{
		$result["tip"] = "查看取消的订单，添加客户端取消原因。<a href='https://www.idcsmart.com/wiki_list/358.html' target='_blank'>帮助文档</a>";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "刷新", "名称", "IP", "服务器数量", "区域", "操作"];
		$result["btnText"] = "新增接口";
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
		}
		$DcimManage = controller("dcimCloud");
		$_GET["sort"] = $_GET["sort"] ? $_GET["sort"] : "desc";
		$_GET["orderby"] = $_GET["orderby"] ? $_GET["orderby"] : "id";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["limit"] = $_GET["limit"] ? $_GET["limit"] : 50;
		$res = $DcimManage->serverList();
		$result["total"] = $res["data"]["sum"];
		$pageSize = \intval($res["data"]["sum"] / 10) + ($res["data"]["sum"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$res["pageSize"][$i - 1] = $i;
		}
		$result["datalist"] = $res["data"]["list"];
		$result["datalistJSON"] = json_encode($res["data"]["list"]);
		$result["search"] = $_GET["search"] ? $_GET["search"] : "";
		return $this->display($result);
	}
	/**
	 * 全局可配置项
	 */
	public function configproductoptions(\think\Request $request)
	{
		$result["tip"] = "可配置选项允许您在产品中提供附加组件和自定义选项。将选项分配给组，然后可以将组应用于产品。";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "组名", "描述", "产品", "操作"];
		$result["btnText"] = "创建组";
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
		}
		$request->keywords = $_GET["keywords"];
		$_GET["keywords"] = $_GET["keywords"] ? $_GET["keywords"] : "";
		$ConfigOptionsManage = controller("ConfigOptions");
		$res = $ConfigOptionsManage->groupsList();
		$result["datalist"] = $res["list"];
		$result["datalistJSON"] = json_encode($res["list"]);
		$result["keywords"] = $_GET["keywords"] ? $_GET["keywords"] : "";
		return $this->display($result);
	}
	/**
	 * 创建组
	 */
	public function configproductoptionsgroup(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 产品分类
	 */
	public function configproduct(\think\Request $request)
	{
		$result["tip"] = "可设置在会员中心前台显示的商品分类";
		return $this->display($result);
	}
}