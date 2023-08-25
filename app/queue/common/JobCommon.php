<?php

namespace app\queue\common;

class JobCommon
{
	const QUEUE_REQ = ["domain", "rootUrl"];
	const QUEUE_REQ_FIXED = "queue_";
	public static function push($data, $queue = "default")
	{
		$data["queue_req"] = ["domain" => getDomain(), "rootUrl" => getRootUrl()];
		\think\Queue::push(static::class, $data, $queue);
	}
	public static function later($data, $timer = 10, $queue = "default")
	{
		\think\Queue::later($timer, static::class, $data, $queue);
	}
	public function handle($data)
	{
		foreach (self::QUEUE_REQ as $k => $v) {
			if (isset($data["queue_req"][$v])) {
				request()->{self::QUEUE_REQ_FIXED . $v} = $data["queue_req"][$v];
			}
		}
	}
}