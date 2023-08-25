<?php

namespace app\common\logic;

class Download
{
	public function getCatesDownload($cate_id)
	{
		$cate_data = \app\common\model\DownloadcatsModel::where("parentid", $cate_id)->order("sort", "asc")->select();
		$all_cate_data = \app\common\model\DownloadcatsModel::all();
		$downloads = \app\common\model\DownloadsModel::all();
		foreach ($cate_data as $key => $cate) {
			$id = $cate["id"];
			$file_count = \app\common\model\DownloadsModel::where("category", $id)->count();
			$cate_data[$key]["file_count"] = $file_count + $this->getDownFiles($all_cate_data, $id, $downloads);
		}
		return $cate_data;
	}
	public function getCatesDownload1($cate_id)
	{
		$cate_data = \app\common\model\DownloadcatsModel::where("parentid", $cate_id)->where("hidden", 0)->order("sort", "asc")->select()->toArray();
		return $cate_data;
	}
	public function getClassifiedDownloadRecords($cate_id, $uid = 0)
	{
		$cate_data = \app\common\model\DownloadcatsModel::getAllCatesHome();
		$downloads = \app\common\model\DownloadsModel::getAllDownlistHome($uid);
		foreach ($cate_data as $key => $cate) {
			$id = $cate["id"];
			$file_count = \app\common\model\DownloadsModel::where("category", $id)->where("hidden", 0)->count();
			$cate_data[$key]["file_count"] = $file_count + $this->getDownFiles($cate_data, $id, $downloads);
		}
		return $cate_data;
	}
	private function getDownFiles($data, $parentid, $downloads)
	{
		$file_count = 0;
		$all_count = 0;
		foreach ($data as $key => $val) {
			if ($val["parentid"] == $parentid) {
				$id = $val["id"];
				unset($data[$key]);
				foreach ($downloads as $k => $v) {
					if ($v["category"] == $id) {
						$file_count++;
					}
				}
				$all_count += $this->getDownFiles($data, $id, $downloads) + $file_count;
			}
		}
		return $all_count;
	}
	public function getHierarchyCats()
	{
		$cats_data = \think\Db::name("downloadcats")->field("id,parentid,name,description,hidden")->select()->toArray();
		if (!empty($cats_data)) {
			foreach ($cats_data as $key => $value) {
				$cats_data[$key]["type"] = "folder";
			}
			$download_data = \think\Db::name("downloads")->field("id,category,title as name")->select()->toArray();
			$download_file = [];
			foreach ($download_data as $key => $val) {
				$download_data[$key]["type"] = "file";
			}
			foreach ($download_data as $key => $val) {
				$category = $val["category"];
				$download_file[$category][] = $val;
			}
			$dep_cats = $this->getChild($cats_data, 0, $download_file);
		} else {
			$dep_cats = [];
		}
		return $dep_cats;
	}
	public function getCatsProduct($productid)
	{
		$cats_data = \think\Db::name("downloadcats")->field("id,parentid,name,description")->order("sort", asc)->select()->toArray();
		if (!empty($cats_data)) {
			$product_downloads = \think\Db::name("product_downloads")->field("download_id")->where("product_id", $productid)->select()->toArray();
			$str = "";
			foreach ($product_downloads as $k => $v) {
				if ($k == 0) {
					$str .= $v["download_id"];
				} else {
					$str .= "," . $v["download_id"];
				}
			}
			$download_data = \think\Db::name("downloads")->field("id,category,title as name")->where("id", "not in", $str)->select()->toArray();
			foreach ($cats_data as $k => $v) {
				foreach ($download_data as $key => $val) {
					if ($val["category"] == $v["id"]) {
						$cats_data[$k]["child"][] = $val;
					}
				}
			}
		} else {
			$cats_data = [];
		}
		return $cats_data;
	}
	public function getCatsProductselect($pid)
	{
		$product_downloads = \think\Db::name("product_downloads")->alias("p")->leftJoin("downloads d", "d.id=p.download_id")->field("d.*")->where("p.product_id", $pid)->select()->toArray();
		return $product_downloads;
	}
	public function getChildTwoDimensional($data, $id = 0)
	{
		static $child = [];
		foreach ($data as $key => $datum) {
			if ($datum["parentid"] == $id) {
				array_push($child, $datum);
				unset($data[$key]);
				$this->getChildTwoDimensional($data, $datum["id"]);
			}
		}
		return $child;
	}
	private function getChild($data, $id = 0, $file_data)
	{
		$child = [];
		$i = 0;
		if (!empty($file_data[$id])) {
			foreach ($file_data[$id] as $file) {
				$child[$i] = $file;
				$i++;
			}
			unset($file_data[$id]);
		}
		foreach ($data as $key => $datum) {
			if ($datum["parentid"] == $id) {
				$child[$i] = $datum;
				unset($data[$key]);
				$child[$i]["child"] = $this->getChild($data, $datum["id"], $file_data);
				$i++;
			}
		}
		return $child;
	}
	public function upload()
	{
		$validate = ["size" => 52428800, "ext" => "rar,zip,png,jpg,jpeg,gif,doc,docx,key,number,pages,pdf,ppt,pptx,txt,rtf,vcf,xls,xlsx"];
		$file = request()->file("uploadfile");
		if (empty($file)) {
			$result["status"] = 400;
			$result["msg"] = "uploadfile不能为空";
			return $result;
		}
		$name = iconv("utf-8", "gbk", $file->getInfo()["name"]);
		$info = $file->validate($validate)->move(UPLOAD_PATH_DWN . "support/", $name);
		if ($info) {
			$filename = $info->getFilename();
			$filename = iconv("gbk", "utf-8", $filename);
		} else {
			$result["status"] = 406;
			$result["msg"] = $file->getError();
			return $result;
		}
		$result["status"] = 200;
		$result["data"]["filename"] = $filename;
		return $result;
	}
	public function deleteFile1($fileArr = [])
	{
		if (!empty($fileArr) && is_array($fileArr)) {
			foreach ($fileArr as $file) {
				@unlink(UPLOAD_PATH_DWN . "clients/" . $file["uid"] . "/" . $file["url"]);
			}
		}
	}
	public function deleteFile($fileArr = [])
	{
		if (!empty($fileArr) && is_array($fileArr)) {
			foreach ($fileArr as $file) {
				@unlink(UPLOAD_PATH_DWN . "support/" . $file);
			}
		}
	}
	/**
	 *处理文件类型映射关系表*
	 *
	 * @param string $filename 文件类型
	 * @return string 文件类型，没有找到返回：other
	 */
	public function getFileType($filename)
	{
		$filetype = "other";
		if (!file_exists($filename)) {
			throw new Exception("no found file!");
		}
		$file = @fopen($filename, "rb");
		if (!$file) {
			throw new Exception("file refuse!");
		}
		$bin = fread($file, 15);
		fclose($file);
		$typelist = $this->getTypeList();
		foreach ($typelist as $v) {
			$blen = strlen(pack("H*", $v[0]));
			$tbin = substr($bin, 0, intval($blen));
			if (strtolower($v[0]) == strtolower(array_shift(unpack("H*", $tbin)))) {
				return $v[1];
			}
		}
		return $filetype;
	}
	/**
	 *得到文件头与文件类型映射表*
	 *
	 * @return array array(array('key',value)...)
	 */
	public function getTypeList()
	{
		return [["FFD8FFE1", "jpg"], ["89504E47", "png"], ["47494638", "gif"], ["49492A00", "tif"], ["424D", "bmp"], ["41433130", "dwg"], ["38425053", "psd"], ["7B5C727466", "rtf"], ["3C3F786D6C", "xml"], ["68746D6C3E", "html"], ["44656C69766572792D646174", "eml"], ["CFAD12FEC5FD746F", "dbx"], ["2142444E", "pst"], ["D0CF11E0", "xls/doc"], ["5374616E64617264204A", "mdb"], ["FF575043", "wpd"], ["252150532D41646F6265", "eps/ps"], ["255044462D312E", "pdf"], ["E3828596", "pwl"], ["504B0304", "zip"], ["52617221", "rar"], ["57415645", "wav"], ["41564920", "avi"], ["2E7261FD", "ram"], ["2E524D46", "rm"], ["000001BA", "mpg"], ["000001B3", "mpg"], ["6D6F6F76", "mov"], ["3026B2758E66CF11", "asf"], ["4D546864", "mid"]];
	}
}