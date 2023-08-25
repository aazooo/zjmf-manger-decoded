<?php

namespace app\admin\validate;

class KnowledgeBaseValidate extends \think\Validate
{
	protected $rule = ["title" => "require|max:50", "article" => "require", "categories" => "require", "views" => "integer", "useful" => "integer", "hidden" => "require|in:0,1", "login_view" => "require|in:0,1", "host_view" => "require|in:0,1", "order" => "integer", "tag" => "max:500", "public_by" => "require|max:20", "public_time" => "require|max:50", "name" => "require|max:50", "description" => "max:100", "file" => "require|image|fileExt:png,jpg,jpeg,gif|fileMime:image/jpeg,image/png,image/gif|fileSize:10485760"];
	protected $message = ["title.require" => "{%KNOWLEDGE_ARTICLE_EMPTY}", "title.max" => "{%KNOWLEDGE_TITLE_MAX}", "article.require" => "{%KNOWLEDGE_ARTICLE_REQUIRE}", "categories.require" => "{%KNOWLEDGE_CATEGORY_EMPTY}", "order.integer" => "{%KNOWLEDGE_ORDER_INTEGER}", "tag.max" => "{%KNOWLEDGE_TAG_MAX}", "public_by.require" => "{%KNOWLEDGE_PUBLIC_BY_REQUIRE}", "public_by.max" => "{%KNOWLEDGE_PUBLIC_BY_MAX}", "public_time.require" => "{%KNOWLEDGE_TIME_REUQIRE}", "public_time.max" => "{%KNOWLEDGE_TIME_MAX}", "name.require" => "{%KNOWLEDGE_CATEGORY_NAME_EMPTY}", "name.max" => "{%KNOWLEDGE_CATEGORY_NAME_MAX}", "description.max" => "{%KNOWLEDGE_DESCRIPTION_MAX}", "file.require" => "{%IMAGE_REQUIRE}", "file.image" => "{%IMAGE}", "file.fileExt" => "{%IMAGE_TYPE}", "file.fileMime" => "{%IMAGE_IMME}", "file.fileSize" => "{%IMAGE_MAX_10}"];
	protected $scene = ["edit_article" => ["title", "article", "categories", "views", "useful", "hidden", "login_view", "host_view", "order", "tag", "public_by", "public_time"], "edit_category" => ["name", "description", "hidden"], "upload" => ["file"]];
}