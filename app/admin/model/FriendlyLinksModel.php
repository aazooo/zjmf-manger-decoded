<?php

namespace app\admin\model;

class FriendlyLinksModel extends \think\Model
{
	public function saveData($data)
	{
		$is_update_id = $data["id"];
		$data_ins["name"] = $data["name"];
		$data_ins["domain"] = $data["domain"];
		$data_ins["link_tag"] = $data["link_tag"];
		$data_ins["is_open"] = $data["is_open"];
		$is_update = $this->find(["id", $is_update_id]) ? true : false;
		if ($is_update) {
			$data_ins["id"] = $is_update_id;
			$data_ins["update_time"] = time();
			return $this->isUpdate(true)->save($data_ins);
		}
		$data_ins["create_time"] = time();
		return $this->save($data_ins);
	}
	public function getAllPage($data, $page, $limit, $order, $sorting)
	{
		$keywords = trim($data["keywords"]);
		$where = function (\think\db\Query $query) use($keywords) {
			if (isset($keywords)) {
				$query->where("name", "like", "%{$keywords}%");
				$query->whereOr("domain", "like", "%{$keywords}%");
				$query->whereOr("link_tag", "like", "%{$keywords}%");
			}
		};
		$count = $this->where($where)->count("id");
		$list = $this->where($where)->order($order, $sorting)->page($page)->limit($limit)->select()->toArray();
		return ["list" => $list, "count" => $count];
	}
	public function deleteData($id)
	{
		return $this->where("id", $id)->delete();
	}
}