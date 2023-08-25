<?php

namespace app\common\job;

class SendSmsQueue
{
	/**
	 * fire方法是消息队列默认调用的方法
	 * @param Job $job 当前的任务对象
	 * @param array|mixed $data 发布任务时自定义的数据
	 */
	public function fire($job, $data)
	{
		$isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
		if (!$isJobStillNeedToBeDone) {
			$job->delete();
			return null;
		}
		$isJobDone = $this->doHelloJob($data);
		if ($isJobDone) {
			$job->delete();
			\think\facade\Log::record("sms_queue_info:" . $data, "info");
		} else {
			if ($job->attempts() > 3) {
				\think\facade\Log::record("sms_queue_error:" . json_encode($data), "error");
				$job->delete();
			}
		}
	}
	/**
	 * 有些消息在到达消费者时,可能已经不再需要执行了
	 * @param array|mixed $data 发布任务时自定义的数据
	 * @return boolean                 任务执行的结果
	 */
	private function checkDatabaseToSeeIfJobNeedToBeDone($data)
	{
		return true;
	}
	/**
	 * 根据消息中的数据进行实际的业务处理
	 * @param array|mixed $data 发布任务时自定义的数据
	 * @return boolean                 任务执行的结果
	 */
	private function doHelloJob($data)
	{
		$data = json_decode($data, true);
		$phone = $data["phone"];
		$msgid = $data["msgid"];
		$uid = $data["uid"];
		unset($data["phone"]);
		$class = new \app\common\logic\Sms();
		$result = $class->sendSmsForMarerking($phone, $msgid, $data, false, $uid);
		if ($result["status"] == 200) {
			return true;
		} else {
			\think\facade\Log::log(2, $result["msg"]);
			return false;
		}
	}
}