<?php

declare (strict_types=1);
namespace app\command;

/**
 * 用于安装本地版本的云
 */
class License extends \think\console\Command
{
	protected function configure()
	{
		$this->setName("license")->setDescription("the install command");
	}
	protected function execute(\think\console\Input $input, \think\console\Output $output)
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			\compareLicense();
		}
	}
}