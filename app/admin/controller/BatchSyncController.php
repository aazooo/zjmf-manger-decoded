<?php

namespace app\admin\controller;

class BatchsyncController extends AdminBaseController
{
	public function execute()
	{
		exit("za");
		session_write_close();
		$param = $this->request->param();
		if (!is_numeric($param["limit"])) {
			exit("limit不能为空");
		}
		$unhost = file_get_contents(CMF_ROOT . "data/success.txt");
		$unshost = [];
		if ($unhost) {
			$unhosta = explode("\r\n", $unhost);
			foreach ($unhosta as $h) {
				if ($h) {
					$unshost[$h] = $h;
				}
			}
		}
		$unshost2 = [];
		$unhost2 = file_get_contents(CMF_ROOT . "data/fail.txt");
		if ($unhost2) {
			$unhosta2 = explode("\r\n", $unhost2);
			foreach ($unhosta2 as $h) {
				if ($h) {
					$unshost2[$h] = $h;
				}
			}
		}
		$hostarr = array_merge($unshost, $unshost2);
		foreach ($hostarr as $h) {
			if ($h) {
				$unhostdata[$h] = $h;
			}
		}
		if (count($hostarr) > 0) {
			$dbhost = \think\Db::name("host")->alias("h")->field("h.id")->where("h.domainstatus='Active' AND serverid=1 AND  h.id not in (" . implode(",", $hostarr) . ")")->limit($param["limit"])->select()->toArray();
		} else {
			$dbhost = \think\Db::name("host")->alias("h")->field("h.id")->where("h.domainstatus='Active' AND serverid=1")->limit($param["limit"])->select()->toArray();
		}
		$dbhostcount = \think\Db::name("host")->alias("h")->where("h.domainstatus='Active' AND serverid=1")->count("h.id");
		$host = new \app\common\logic\Host();
		$host->is_admin = true;
		$faildata = file_get_contents(CMF_ROOT . "data/faildata.txt");
		$re = json_decode($faildata, true);
		$fileid = "";
		$fileid2 = "";
		$success = 0;
		$fail = 0;
		foreach ($dbhost as $v) {
			if (!$unhostdata[$v["id"]]) {
				$result = $host->sync($v["id"]);
				if ($result["status"] == 200) {
					$fileid .= $v["id"] . "\r\n";
					$success++;
				} else {
					$fileid2 .= $v["id"] . "\r\n";
					$fail++;
					$result["id"] = $v["id"];
					$re[] = $result;
				}
			}
		}
		if ($fileid) {
			file_put_contents(CMF_ROOT . "data/success.txt", $fileid, FILE_APPEND);
		}
		if ($fileid2) {
			file_put_contents(CMF_ROOT . "data/fail.txt", $fileid2, FILE_APPEND);
		}
		if ($re) {
			file_put_contents(CMF_ROOT . "data/faildata.txt", json_encode($re));
		}
		session_write_close();
		$faildata = file_get_contents(CMF_ROOT . "data/faildata.txt");
		$faildata = json_decode($faildata, true);
		echo "<pre>需要拉取的数据条数：" . $dbhostcount;
		echo "<br>";
		echo "<br>";
		echo "<pre>";
		echo "已经拉取总数据：" . (count($unshost) + $success + count($unshost2) + $fail);
		echo "<pre>";
		echo "已经拉取成功的数据：" . (count($unshost) + $success);
		echo "<pre>";
		echo "已经拉取失败的数据：" . (count($unshost2) + $fail);
		echo "<pre>";
		echo "本次同步数据：" . ($success + $fail);
		echo "<pre>";
		echo "本次同步成功：" . $success;
		echo "<pre>";
		echo "本次同步失败：" . $fail;
		echo "<pre>总拉取失败的数据:<pre>";
		var_dump($faildata);
		exit("over");
	}
	public function test()
	{
		echo "test success";
	}
}