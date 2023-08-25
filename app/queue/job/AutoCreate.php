<?php

namespace app\queue\job;

class AutoCreate extends \app\queue\common\JobCommon
{
	const QUEUE_NAME = "autoCreate";
	public function fire(\think\queue\Job $job, $data)
	{
		try {
			$job->delete();
			$this->handle($data);
		} catch (\Throwable $e) {
			self::later(10, $data);
		}
	}
	/**
	 * 逻辑处理
	 * @param $data
	 */
	public function handle($data)
	{
		parent::handle($data);
		$host_logic = new \app\common\logic\Host();
		$host_logic->is_admin = $data["is_admin"] ? true : false;
		$result = $host_logic->createFinal($data["hid"], $data["ip"]);
		$logic_run_map = new \app\common\logic\RunMap();
		$model_host = new \app\common\model\HostModel();
		$data_i = [];
		$data_i["host_id"] = $data["hid"];
		$data_i["active_type_param"] = [$data["hid"], $data["ip"]];
		$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
		if ($result["status"] == 200) {
			$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品成功";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 400, 1);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 300, 1);
			}
		} else {
			$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品失败。原因:{$result["msg"]}";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 400, 1);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 300, 1);
			}
		}
	}
	public function delaty(&$data)
	{
		$data["delaty"] = isset($data["delaty"]) ? $data["delaty"] + 1 : 1;
	}
}