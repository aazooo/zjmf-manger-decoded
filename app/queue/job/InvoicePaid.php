<?php

namespace app\queue\job;

class InvoicePaid extends \app\queue\common\JobCommon
{
	const QUEUE_NAME = "invoicePaid";
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
		$invoice = new \app\common\logic\Invoices();
		list($invoiceid, $email) = [$data["invoiceid"] ?? 0, $data["email"] ?? true];
		$invoice->processPaidInvoiceFinal($invoiceid, $email);
	}
	public function delaty(&$data)
	{
		$data["delaty"] = isset($data["delaty"]) ? $data["delaty"] + 1 : 1;
	}
}