<?php

namespace app\common\logic;

/**
 * 系统消息
 * Class SystemMessage
 * @package app\common\logic
 */
class SystemMessage
{
	/**
	 * 发送站内信逻辑
	 * @param $client_ids 客户id组
	 * @param $info 发送相关信息
	 * @param bool $sync 是否同步发送
	 * @param int $delay_time 延迟时间
	 * @return bool|int|string
	 */
	public function sendAction($client_ids, $info, $sync = false, $delay_time = 0)
	{
		$effective_data["title"] = $info["system_subject"];
		$effective_data["attachment"] = $info["system_attachments"];
		$effective_data["content"] = $info["system_message"];
		$effective_data["obj"] = "";
		$effective_data["type"] = 3;
		$effective_data["is_market"] = $info["is_market"] ?? 0;
		$effective_data["create_time"] = time();
		if ($sync) {
			$param["client_ids"] = $client_ids;
			$param["info"] = $info;
			$param["queue_type"] = "system_queue";
			$isPushed = \think\Queue::later($delay_time, "app\\common\\job\\SendActivationMarketing", json_encode($param), "SendActivationMarketing");
			if ($isPushed !== false) {
				\think\facade\Log::log(2, "system_message_queue_start：" . json_encode($param));
			} else {
				\think\facade\Log::log(2, "system_message_queue_start：" . json_encode($param));
			}
			return true;
		}
		$insert_all = [];
		foreach ($client_ids as $client_id) {
			$insert_one = $effective_data;
			$insert_one["uid"] = $client_id;
			$insert_all[] = $insert_one;
		}
		return \think\Db::name("system_message")->insertAll($insert_all);
	}
}