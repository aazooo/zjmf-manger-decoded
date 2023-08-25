<?php

namespace app\queue\job;

class SendSms extends \app\queue\common\JobCommon
{
	const QUEUE_NAME = "sendSms";
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
		$email = new \app\common\logic\Sms();
		list($type, $phone, $param, $sync, $uid, $delay_time, $is_market) = [$data["type"], $data["phone"] ?? "", $data["param"] ?? "", $data["sync"] ?? "false", $data["uid"] ?? "", $data["delay_time"] ?? 0, $data["is_market"] ?? false];
		return $email->sendSmsFinal($type, $phone, $param, $sync, $uid, $delay_time, $is_market);
	}
	public function delaty(&$data)
	{
		$data["delaty"] = isset($data["delaty"]) ? $data["delaty"] + 1 : 1;
	}
}