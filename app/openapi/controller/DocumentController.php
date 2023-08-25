<?php

namespace app\openapi\controller;

require_once CMF_ROOT . "app/openapi/documents/src/ApiDoc.php";
require_once CMF_ROOT . "app/openapi/documents/src/BootstrapApiDoc.php";
require_once CMF_ROOT . "app/openapi/documents/src/lib/Tools.php";
require_once CMF_ROOT . "app/openapi/documents/src/lib/ParseComment.php";
require_once CMF_ROOT . "app/openapi/documents/src/lib/ParseLine.php";
/**
 * @title API文档
 * @description 接口说明
 */
class DocumentController extends \cmf\controller\HomeBaseController
{
	public function index()
	{
		$config = [];
		$api = new \itxq\apidoc\BootstrapApiDoc($config);
		$doc = $api->getHtml();
		exit($doc);
	}
}