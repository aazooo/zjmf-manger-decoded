<?php

namespace app\admin\model;

class TicketPrereplyCategoryModel extends \think\Model
{
	public $pre = "-";
	public $all = [];
	protected $not_allow = [];
	public function getAll($parentid = 0, $layer = -1)
	{
		$layer++;
		$children = $this->where("parentid", $parentid)->select()->toArray();
		if (!empty($children)) {
			foreach ($children as $k => $v) {
				if (!in_array($v["id"], $this->not_allow)) {
					$this->all[] = ["id" => $v["id"], "name" => str_repeat($this->pre, $layer) . $v["name"]];
				}
				$this->getAll($v["id"], $layer);
			}
		}
		return $this->all;
	}
	public function setNotAllow($data = [])
	{
		$this->not_allow = $data;
		return $this;
	}
}