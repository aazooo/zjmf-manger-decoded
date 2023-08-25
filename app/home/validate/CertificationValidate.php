<?php

namespace app\home\validate;

class CertificationValidate extends \think\Validate
{
	protected $rule = ["auth_card_number" => "require|idCard", "auth_real_name" => "require|max:24", "company_name" => "require|max:50", "company_organ_code" => "require|alphaNum|max:100", "file" => "require|image|fileExt:png,jpg,jpeg|fileMime:image/jpeg,image/png,image/bmp|fileSize:5242880"];
	protected $message = ["auth_card_number.require" => "身份证必填", "auth_card_number.idCard" => "无效身份证号码", "auth_real_name.require" => "姓名必填", "auth_real_name.max" => "姓名不能超过24个字符", "company_name.require" => "公司名称必填", "company_name.max" => "公司名称不超过50个字符", "company_organ_code.require" => "营业执照号码不能为空", "company_organ_code.alphaNum" => "营业执照号码为数字或字母", "company_organ_code.max" => "营业执照号码不超过100个字符", "file.require" => "请上传文件", "file.image" => "请上传图片文件", "file.fileExt" => "请上传png,jpg,jpeg图片", "file.fileMime" => "请上传png,jpg,jpeg,bmp类型图片", "file.fileSize" => "文件大小不超过5M"];
	protected $scene = ["personedit" => ["auth_card_number", "auth_real_name"], "companyedit" => ["auth_card_number", "auth_real_name", "company_name", "company_organ_code"], "upload" => ["file"]];
	public function sceneGatRegion()
	{
		return $this->only(["auth_card_number", "auth_real_name"])->remove(["auth_card_number" => "idCard"]);
	}
	public function sceneCompanyGatRegion()
	{
		return $this->only(["auth_card_number", "auth_real_name", "company_name", "company_organ_code"])->remove(["auth_card_number" => "idCard"]);
	}
}