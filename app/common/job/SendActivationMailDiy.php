<?php

namespace app\common\job;

class SendActivationMailDiy
{
	/**
	 * fire方法是消息队列默认调用的方法
	 * @param Job $job 当前的任务对象
	 * @param $data .发布任务时自定义的数据
	 */
	public function fire(\think\queue\Job $job, $data)
	{
		$emailObject = new \app\common\logic\Email();
		$data = json_decode($data, true);
		$relid = $data["relid"];
		$subject = $data["subject"];
		$type = $data["type"];
		$message = $data["message"];
		$attachments = $data["attachments"];
		$isJobDone = $emailObject->sendEmailDiy($relid, $subject, $message, $attachments, $type, false);
		\think\facade\Log::log(2, "执行结果：" . $isJobDone);
		if ($isJobDone) {
			$job->delete();
			\think\facade\Log::log(2, date("Y-m-d H:i:s") . "任务执行成功,,已经删除!结果：" . $isJobDone);
		} else {
			\think\facade\Log::log(2, date("Y-m-d H:i:s") . "任务执行失败!");
			if ($job->attempts() > 3) {
				\think\facade\Log::log(2, date("Y-m-d H:i:s") . "删除任务!");
				$job->delete();
			} else {
				$job->release();
				\think\facade\Log::log(2, date("Y-m-d H:i:s") . "<info>重新执行!第" . $job->attempts() . "次重新执行!</info>\n");
			}
		}
	}
}