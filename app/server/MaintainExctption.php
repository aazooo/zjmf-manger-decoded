<?php

namespace app\server;

class MaintainExctption extends \Exception
{
	public function __construct($arr)
	{
		parent::__construct($arr[1], $arr[0]);
	}
}