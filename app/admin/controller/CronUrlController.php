<?php

namespace app\admin\controller;

/**
 * @title 后台URL自动任务
 * @description 接口说明
 */
class CronUrlController extends \think\Controller
{
	public function index()
	{
		$output = \think\Console::call("cron");
		return $output->fetch();
	}
}