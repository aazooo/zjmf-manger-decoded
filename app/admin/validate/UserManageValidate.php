<?php

namespace app\admin\validate;

class UserManageValidate extends \think\Validate
{
	protected $rule = ["username" => "require|max:50", "password" => "min:6|max:20", "sex" => "in:0,1,2|max:20", "avatar" => "image|fileExt:png,jpg,jpeg,gif|fileMime:image/jpeg,image/png,image/bmp,image/gif|fileSize:5242880", "profession" => "max:50", "status" => "require|in:0,1,2|max:20", "signature" => "max:200", "companyname" => "max:50", "email" => "email", "address1" => "max:100", "postcode" => "number|max:20", "country" => "max:50", "province" => "max:50", "city" => "max:50", "phonenumber" => "max:30", "auth_card_number" => "require|idCard", "auth_real_name" => "require|max:24", "company_name" => "require|max:50", "company_organ_code" => "require|number|max:100", "file" => "require|image|fileExt:png,jpg,jpeg|fileMime:image/jpeg,image/png,image/bmp|fileSize:5242880"];
	protected $message = ["username.require" => "{%USERMANGE_USERNAME_REQUIRE}", "username.max" => "{%USERMANGE_USERNAME_MAX}", "password.require" => "{%USERMANGE_PASSWORD_REQUIRE}", "sex.require" => "{%USERMANGE_SEX_REQUIRE}", "sex.max" => "{%USERMANGE_SEX_MAX}", "avatar.image" => "{%USERMANGE_AVATAR_IMAGE}", "avatar.fileExt" => "{%USERMANGE_AVATAR_FILEEXT}", "avatar.fileMime" => "{%USERMANGE_AVATAR_FILEMIME}", "avatar.fileSize" => "{%USERMANGE_AVATAR_FILESIZE}", "profession.max" => "{%USERMANGE_PROFESSION_MAX}", "signature.max" => "{%USERMANGE_SINGATURE_MAX}", "companyname.require" => "{%USERMANGE_COMPANYNAME_REQUIRE}", "companyname.max" => "{%USERMANGE_COMPANYNAME_MAX}", "email.require" => "{%USERMANGE_EMAIL_REQUIRE}", "email.email" => "{%USERMANGE_EMAIL}", "address1.max" => "{%USERMANGE_ADDRESS1_MAX}", "postcode.number" => "{%USERMANGE_POSTCODE_NUMBER}", "postcode.max" => "{%USERMANGE_POSTCODE_MAX}", "country.max" => "{%USERMANGE_COUNTRY_MAX}", "province.max" => "{%USERMANGE_PROVINCE_MAX}", "city.max" => "{%USERMANGE_CITY_MAX}", "phonenumber.require" => "{%USERMANGE_PHONENUMBER_REQUIRE}", "phonenumber.max" => "{%USERMANGE_PHONENUMBER_MAX}", "auth_card_number.require" => "身份证必填", "auth_card_number.idCard" => "无效身份证号码", "auth_real_name.require" => "姓名必填", "auth_real_name.max" => "姓名不能超过24个字符", "company_name.require" => "公司名称必填", "company_name.max" => "公司名称不超过50个字符", "company_organ_code.require" => "营业执照号码不能为空", "company_organ_code.number" => "营业执照号码为数字", "company_organ_code.max" => "营业执照号码不超过100个字符", "file.require" => "请上传文件", "file.image" => "请上传图片文件", "file.fileExt" => "请上传png,jpg,jpeg图片", "file.fileMime" => "请上传png,jpg,jpeg,bmp类型图片", "file.fileSize" => "文件大小不超过5M"];
	protected $scene = ["usermanage" => ["username", "password", "sex", "profession", "status", "signature", "companyname", "email", "address1", "postcode", "country", "province", "city", "phonenumber"], "personedit" => ["auth_card_number", "auth_real_name"], "companyedit" => ["auth_card_number", "auth_real_name", "company_name", "company_organ_code"], "upload" => ["file"]];
	public function scenePut()
	{
		return $this->only(["username", "password", "sex", "profession", "status", "signature", "companyname", "email", "address1", "postcode", "country", "province", "city", "phonenumber"])->remove("password", "require");
	}
	public function sceneGatRegion()
	{
		return $this->only(["auth_card_number", "auth_real_name"])->remove("auth_card_number", "idCard");
	}
	public function sceneCompanyGatRegion()
	{
		return $this->only(["auth_card_number", "auth_real_name", "company_name", "company_organ_code"])->remove("auth_card_number", "idCard");
	}
}