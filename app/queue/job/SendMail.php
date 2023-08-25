<?php

namespace app\queue\job;

class SendMail extends \app\queue\common\JobCommon
{
	const QUEUE_NAME = "sendMail";
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
		$email = new \app\common\logic\Email();
		list($relid, $name, $type, $admin, $cc, $bcc, $msg, $attachments) = [$data["relid"] ?? "", $data["name"] ?? "", $data["type"] ?? "", $data["admin"] ?? "", $data["cc"] ?? "", $data["bcc"] ?? "", $data["message"] ?? "", $data["attachments"] ?? ""];
		return $email->sendEmailBaseFinal($relid, $name, $type, true, $admin, $cc, $bcc, $msg, $attachments);
	}
	public function delaty(&$data)
	{
		$data["delaty"] = isset($data["delaty"]) ? $data["delaty"] + 1 : 1;
	}
}