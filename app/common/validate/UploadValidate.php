<?php

namespace app\common\validate;

/**
 * @title 文件上传验证类
 * @description 接口说明:文件上传验证类
 */
class UploadValidate extends \think\Validate
{
	protected $rule = ["file" => "require|fileExt:png,jpg,jpeg,gif,doc,docx,key,numbers,pages,pdf,ppt,pptx,txt,rtf,vcf,xls,xlsx|fileMime:image/jpeg,image/png,image/gif|fileSize:67108864", "image" => "require|image|fileExt:png,jpg,jpeg,gif|fileMime:image/jpeg,image/png,image/gif|fileSize:67108864"];
	protected $message = ["file.fileMime" => "{%FILE_MIME_ERROR}", "file.fileSize" => "{%FILE_MAX_64}"];
	protected $scene = ["file" => ["file"], "image" => ["image"]];
}