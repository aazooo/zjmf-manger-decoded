<?php

namespace app\admin\controller;

/**
 * @title 文件上传
 * @description 接口说明
 */
class UploadController extends AdminBaseController
{
	/**
	 * @title 富文本框上传图片
	 * @description 用于富文本框上传图片
	 * @author wyh
	 * @url         admin/upload
	 * @method      POST
	 * @time        2020-02-05
	 * @param       .name:image type:file require:1 default:0 other: desc:文件
	 * @return      上传的文件路径
	 */
	public function upload()
	{
		$validate = ["size" => 2097152, "ext" => "jpg,jpeg,png,gif"];
		$file = request()->file("image");
		$info = $file->validate($validate)->rule(function () {
			return mt_rand(1000, 9999) . "_" . md5(microtime(true));
		})->move(config("attachment"));
		if ($info) {
			$res["status"] = 200;
			$res["msg"] = "上传成功";
			$res["data"] = request()->domain() . request()->rootUrl() . config("attachment_url") . $info->getFilename();
		} else {
			$res["status"] = 406;
			$res["msg"] = "上传失败";
		}
		return jsonrule($res);
	}
	/**
	 * @title 上传图片
	 * @description 一般图片上传
	 * @author wyh
	 * @url         admin/upload_image
	 * @method      POST
	 * @time        2020-02-05
	 * @param       .name:image|file type:file require:1 default:0 other: desc:图片
	 * @param       .name:type type:string require:1 default:0 other: desc:类型,如avatar,servers,attachment,contract(合同)富文本上传，直接获取地址展示
	 * @return      上传的文件路径
	 */
	public function uploadImage()
	{
		$type = $this->request->param("type");
		$file = request()->file("file");
		if (is_object($file)) {
			$is_file = true;
		}
		$image = request()->file("image");
		if (is_object($image)) {
			$is_file = false;
		}
		$save = $this->getUri($type);
		$upload = new \app\common\logic\Upload($save);
		$re = $upload->uploadHandle($image, $is_file, false);
		if (!$re) {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$url = "";
		if ($type === "attachment") {
			$url = $this->request->host() . config("attachment_url") . $re["savename"];
		}
		if ($re["status"] == 200) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "originname" => $re["origin_name"], "savename" => $re["savename"], "tmp" => base64EncodeImage($save . $re["savename"]), "url" => $url]);
		} else {
			return jsonrule(["status" => 400, "msg" => $re["msg"]]);
		}
	}
	/**
	 * 时间 2020/4/27 17:12
	 * @title 上传文件
	 * @desc 上传文件
	 * @url admin/upload_file
	 * @method  POST
	 * @param       .name:filename|file type:file require:1 default:0 other: desc:文件
	 * @param       .name:type type:string require:1 default:0 other: desc:类型,如avatar,servers
	 * @return      上传的文件路径
	 * @author liyongjun
	 * @version v1
	 */
	public function uploadFile()
	{
		$file = request()->file("file");
		$upload = new \app\common\logic\Upload(UPLOAD_DEFAULT);
		$re = $upload->uploadHandle($file, true);
		$re["tmp"] = base64EncodeImage(UPLOAD_DEFAULT . $re["savename"]);
		if ($re["status"] == 200 && is_file(UPLOAD_DEFAULT . $re["savename"])) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $re]);
		} else {
			return jsonrule(["status" => 400, "msg" => $re["msg"]]);
		}
	}
	/**
	 * 时间 2020/4/27 17:12
	 * @title 上传授权书
	 * @desc 上传授权书
	 * @url admin/upload_author
	 * @method  POST
	 * @param       .name:filename|file type:file require:1 default:0 other: desc:文件
	 * @return      上传的文件路径
	 * @author liyongjun
	 * @version v1
	 */
	public function uploadAuthor()
	{
		$file = request()->file("file");
		$upload = new \app\common\logic\Upload(config("author_attachments"));
		$re = $upload->uploadHandle($file, true);
		if ($re["status"] == 200 && is_file(config("author_attachments") . $re["savename"])) {
			updateConfiguration("certifi_business_author_path", $re["savename"]);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $re, "path" => config("author_attachments_url") . $re["savename"]]);
		} else {
			return jsonrule(["status" => 400, "msg" => $re["msg"]]);
		}
	}
	public function uploadCertificate()
	{
		$file = request()->file("file");
		$upload = new \app\common\logic\Upload(config("certificate"));
		$re = $upload->uploadHandle($file, true);
		if ($re["status"] == 200 && is_file(config("certificate") . $re["savename"])) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $re, "path" => config("certificate_url") . $re["savename"], "key" => request()->param("key") ?: ""]);
		} else {
			return jsonrule(["status" => 400, "msg" => $re["msg"]]);
		}
	}
	private function getUri($type)
	{
		switch ($type) {
			case "avatar":
				$uri = config("client_avatar");
				break;
			case "servers":
				$uri = config("servers");
				break;
			case "email":
				$uri = config("email_attachments");
				break;
			case "attachment":
				$uri = config("attachment");
				break;
			case "author":
				$uri = config("author_attachments");
				break;
			case "certifi":
				$uri = config("certificate");
				break;
			case "contract":
				$uri = config("contract");
				break;
			default:
				$uri = UPLOAD_DEFAULT;
				break;
		}
		return $uri;
	}
}