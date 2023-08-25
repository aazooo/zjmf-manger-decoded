<?php

namespace app\admin\model;

class InterflowChatRecordModel extends \think\Model
{
	protected $autoWriteTimestamp = true;
	protected $createTime = "create_time";
	protected $dateFormat = "Y/m/d H:i";
	protected $readonly = ["create_time"];
}