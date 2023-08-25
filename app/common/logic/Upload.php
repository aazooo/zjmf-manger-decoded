<?php

namespace app\common\logic;

/**
 * @title 文件上传公共类
 * @description 接口说明:文件上传公共类,
 */
class Upload
{
	private $fileSave;
	public function __construct($fileSave = "")
	{
		$this->fileSave = $fileSave && is_string($fileSave) ? $fileSave : UPLOAD_DEFAULT;
		if (!is_dir($this->fileSave)) {
			mkdir($this->fileSave);
		}
		if (!is_writable($this->fileSave)) {
			chmod($this->fileSave, 493);
		}
	}
	/**
	 * 单文件上传
	 * @param $file :文件
	 * @param $type :文件验证类型
	 * @param $is_file :是否是文件
	 * @param $origin :是否使用源文件名
	 * @param $split :文件名分隔符
	 * @return
	 * @author wyh
	 * @time 2020/04/03
	 */
	public function uploadHandle($file, $is_file = false, $origin = true, $split = "^")
	{
		$re = [];
		if (is_array($file)) {
			return false;
		}
		if ($is_file) {
			$type = "file";
			$data = ["file" => $file];
		} else {
			$type = "image";
			$data = ["image" => $file];
		}
		if ($is_file) {
			$validate = new \app\admin\validate\EmailTemplateValidate();
			if (!$validate->scene("upload")->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				return $re;
			}
		} else {
			$validate = new \app\common\validate\UploadValidate();
			if (!$validate->scene($type)->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				if ($re["msg"] === "image不是有效的图像文件") {
					$re["msg"] = "不支持的附件格式";
				}
				return $re;
			}
		}
		$originalName = $file->getInfo("name");
		if ($origin) {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time() . $split . $originalName);
		} else {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time());
		}
		if ($info) {
			$savename = $info->getSaveName();
			$re["status"] = 200;
			$re["savename"] = $savename;
			$re["origin_name"] = $originalName;
		} else {
			$re["status"] = 400;
			$re["msg"] = $file->getError();
		}
		return $re;
	}
	/**
	 * 单文件上传
	 * @param $file :文件
	 * @param $type :文件验证类型
	 * @param $is_file :是否是文件
	 * @param $origin :是否使用源文件名
	 * @param $split :文件名分隔符
	 * @return
	 * @author wyh
	 * @time 2020/04/03
	 */
	public function uploadHandles1($file, $is_file = false, $origin = true, $split = "^")
	{
		$re = [];
		if (is_array($file)) {
			return false;
		}
		if ($is_file) {
			$type = "file";
			$data = ["file" => $file];
		} else {
			$type = "image";
			$data = ["image" => $file];
		}
		if ($is_file) {
			$validate = ["size" => 52428800];
			$info = $file->validate($validate);
			if (!$info) {
				$result["status"] = 406;
				$result["msg"] = $file->getError();
				return $result;
			}
		} else {
			$validate = new \app\common\validate\UploadValidate();
			if (!$validate->scene($type)->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				if ($re["msg"] === "image不是有效的图像文件") {
					$re["msg"] = "不支持的附件格式";
				}
				return $re;
			}
		}
		$originalName = $file->getInfo("name");
		if ($origin) {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time() . $split . $originalName);
		} else {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time());
		}
		if ($info) {
			$savename = $info->getSaveName();
			$re["status"] = 200;
			$re["savename"] = $savename;
			$re["origin_name"] = $originalName;
		} else {
			$re["status"] = 400;
			$re["msg"] = $file->getError();
		}
		return $re;
	}
	/**
	 * 单文件上传
	 * @param $file :文件
	 * @param $type :文件验证类型
	 * @param $is_file :是否是文件
	 * @param $origin :是否使用源文件名
	 * @param $split :文件名分隔符
	 * @return
	 * @author wyh
	 * @time 2020/04/03
	 */
	public function uploadHandles($file, $is_file = false, $origin = true, $split = "^")
	{
		$re = [];
		if (is_array($file)) {
			return false;
		}
		if ($is_file) {
			$type = "file";
			$data = ["file" => $file];
		} else {
			$type = "image";
			$data = ["image" => $file];
		}
		if ($is_file) {
			$validate = ["size" => 52428800, "ext" => "rar,zip,png,jpg,jpeg,gif,doc,docx,key,number,pages,pdf,ppt,pptx,txt,rtf,vcf,xls,xlsx"];
			$info = $file->validate($validate);
			if (!$info) {
				$result["status"] = 406;
				$result["msg"] = $file->getError();
				return $result;
			}
		} else {
			$validate = new \app\common\validate\UploadValidate();
			if (!$validate->scene($type)->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				if ($re["msg"] === "image不是有效的图像文件") {
					$re["msg"] = "不支持的附件格式";
				}
				return $re;
			}
		}
		$originalName = $file->getInfo("name");
		if ($origin) {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time() . $split . $originalName);
		} else {
			$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time());
		}
		if ($info) {
			$savename = $info->getSaveName();
			$re["status"] = 200;
			$re["savename"] = $savename;
			$re["origin_name"] = $originalName;
		} else {
			$re["status"] = 400;
			$re["msg"] = $file->getError();
		}
		return $re;
	}
	/**
	 * 多文件上传
	 * @param $file :文件
	 * @param $type :文件验证类型
	 * @param $origin :是否使用源文件名
	 * @param $split :文件名分隔符
	 * @return
	 * @author wyh
	 * @time 2020/04/03
	 */
	public function uploadMultiHandle($files, $origin = true, $split = "^")
	{
		$re = [];
		if (!is_array($files)) {
			return false;
		}
		foreach ($files as $file) {
			$data = ["file" => $file];
			$validate = new \app\common\validate\UploadValidate();
			if (!$validate->scene("file")->check($data)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				if (!empty($re["savename"])) {
					$addresses = explode(",", $re["savename"]);
					foreach ($addresses as $address) {
						$path = $this->fileSave . $address;
						if (file_exists($path)) {
							unset($info);
							@unlink($path);
							unset($re["savename"]);
						}
					}
				}
				return $re;
			}
			if ($origin) {
				$originalName = $file->getInfo("name");
				$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time() . $split . $originalName);
			} else {
				$info = $file->rule("uniqid")->move($this->fileSave, md5(uniqid()) . time());
			}
			if ($info) {
				if (!isset($savename)) {
					$savename = $info->getSaveName();
				} else {
					$savename = $savename . "," . $info->getSaveName();
				}
				$re["status"] = 200;
				$re["savename"] = $savename;
			} else {
				$re["status"] = 400;
				$re["msg"] = $file->getError();
			}
		}
		return $re;
	}
	/**
	 * 时间 2020/4/27 15:42
	 * @param string $file - 要移动的文件
	 * @param string $path - 移动地址
	 * @return  array $res- 成功返回提交完整地址， 失败返回erorr地址
	 * @author liyongjun
	 */
	public function moveTo($file, $path)
	{
		if (is_array($file)) {
			$ret = [];
			foreach ($file as $v) {
				$tmp = $this->moveTo($v, $path);
				if (isset($tmp["error"])) {
					return $tmp;
				}
				$ret[] = $tmp;
			}
			return $ret;
		}
		$file = htmlspecialchars_decode($file);
		$filepath = UPLOAD_DEFAULT . $file;
		$newfile = $path . $file;
		if (file_exists($newfile)) {
			return $file;
		}
		if (!file_exists($filepath)) {
			return ["error" => "文件不存在"];
		}
		if (!file_exists($path)) {
			mkdir($path, 511, true);
		}
		try {
			if (copy($filepath, $newfile)) {
				unlink($filepath);
				return $file;
			}
		} catch (\Exception $e) {
			return ["error" => $e->getMessage()];
		}
		return ["error" => "移动失败"];
	}
}