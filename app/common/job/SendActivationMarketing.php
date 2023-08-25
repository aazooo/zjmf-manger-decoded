<?php

namespace app\common\job;

/**
 * 营销信息队列任务处理
 * Class SendActivationMarketing
 * @package app\common\job
 */
class SendActivationMarketing
{
	/**
	 * fire方法是消息队列默认调用的方法
	 * @param Job $job 当前的任务对象
	 * @param $data .发布任务时自定义的数据
	 */
	public function fire(\think\queue\Job $job, $data)
	{
		$data = json_decode($data, true);
		$queue_type = $data["queue_type"];
		$queue_name = "";
		switch ($queue_type) {
			case "email_queue":
				$queue_name = "邮件队列";
				$emailObject = new \app\common\logic\Email();
				$relid = $data["relid"];
				$subject = $data["subject"];
				$type = $data["type"];
				$message = $data["message"];
				$attachments = $data["attachments"];
				$isJobDone = $emailObject->sendEmailDiy($relid, $subject, $message, $attachments, $type, false);
				break;
			case "system_queue":
				$queue_name = "站内信队列";
				$systemMessageObject = new \app\common\logic\SystemMessage();
				$client_ids = $data["client_ids"];
				$info = $data["info"];
				$isJobDone = $systemMessageObject->sendAction($client_ids, $info, false);
				break;
			case "sms_queue":
				$queue_name = "短信队列";
				$phone = $data["phone"];
				$msgid = $data["msgid"];
				$uid = $data["uid"];
				unset($data["phone"]);
				$class = new \app\common\logic\Sms();
				$result = $class->sendSmsForMarerking($phone, $msgid, $data, false, $uid);
				$isJobDone = $result["status"] == 200 ? true : false;
				break;
			default:
				break;
		}
		if ($isJobDone) {
			$job->delete();
			\think\facade\Log::log(2, date("Y-m-d H:i:s") . $queue_name . "任务执行成功,已经删除!结果：" . $isJobDone);
		} else {
			\think\facade\Log::log(2, date("Y-m-d H:i:s") . $queue_name . "任务执行失败!");
			if ($job->attempts() > 3) {
				\think\facade\Log::log(2, date("Y-m-d H:i:s") . $queue_name . "删除任务!");
				$job->delete();
			} else {
				$job->release();
				\think\facade\Log::log(2, date("Y-m-d H:i:s") . $queue_name . "<info>重新执行!第" . $job->attempts() . "次重新执行!</info>\n");
			}
		}
	}
}