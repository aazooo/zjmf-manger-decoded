<?php

namespace app\server;

class CustomExctption extends \Exception
{
	public function __construct($arr)
	{
		parent::__construct($arr[1], $arr[0]);
	}
}