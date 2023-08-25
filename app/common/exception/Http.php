<?php

namespace app\common\exception;

class Http extends \think\exception\Handle
{
	const PAGE_PATH = CMF_ROOT . "public/themes/clientarea/";
	const DEFAULT_THEMES = "default";
	const MAINTAIN_PAGE = "maintenance.tpl";
	const NOTFOUND_PAGE = "404.tpl";
	const ERROR_PAGE = "500.tpl";
	protected $layout = true;
	protected $themeDirName;
	protected $defaultThemes;
	public function render(\Exception $e)
	{
		try {
			if (APP_DEBUG) {
				exit($this->ajaxError($e));
				if (\think\Request::isAjax()) {
					exit($this->ajaxError($e));
				}
				return parent::render($e);
			}
			$this->getDefaultThemes();
			$this->getThemeDirName();
			if ($e instanceof \app\server\CustomExctption) {
				return $this->dbClientError($e);
			}
			if ($e instanceof \app\server\MaintainExctption) {
				return response($this->maintainError($e), 200);
			}
			if ($e instanceof \think\exception\RouteNotFoundException) {
				return response($this->routeError($e), 404);
			}
			if ($e instanceof \think\exception\HttpException) {
				return response($this->httpError($e), $e->getStatusCode());
			}
			return response($this->error($e), 500);
		} catch (\app\server\FileExistsExctption $e) {
			return $this->fileError($e);
		} catch (\Throwable $e) {
			return parent::render($e);
		}
	}
	private function ajaxError(\Exception $e)
	{
		return json_encode(["status" => 500, "msg" => $e->getMessage(), "data" => ["line" => $e->getLine(), "file" => $e->getFile()]]);
	}
	private function dbClientError(\Exception $e)
	{
		exit($e->getMessage());
	}
	private function httpError(\Exception $e)
	{
		$code = $e->getStatusCode();
		switch ($code) {
			case 404:
				$path = config("notfound_page") ?? self::NOTFOUND_PAGE;
				break;
			case 500:
				$path = config("error_page") ?? self::ERROR_PAGE;
				break;
			default:
				$path = self::ERROR_PAGE;
		}
		return $this->existsReturn($path, $e);
	}
	private function routeError(\Exception $e)
	{
		return $this->existsReturn(config("notfound_page") ?? self::NOTFOUND_PAGE, $e);
	}
	private function maintainError(\Exception $e)
	{
		return $this->existsReturn(config("maintain_page") ?? self::MAINTAIN_PAGE, $e);
	}
	private function error(\Exception $e)
	{
		return $this->existsReturn(self::ERROR_PAGE, $e);
	}
	private function fileError($e)
	{
		if (\think\Request::module() === config("database.admin_application")) {
			\think\Log::record(date("Y-m-d H:i:s") . ":" . json_encode(["msg" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine(), "code" => $e->getCode()]), "CUSTOM_ERROR");
			return jsons(["status" => 500, "msg" => "系统内部错误，请联系管理员！"], 200);
		}
		return parent::render($e);
	}
	private function getThemeDirName()
	{
		$this->themeDirName = configuration("clientarea_default_themes") ?: $this->defaultThemes;
	}
	private function getPagePath()
	{
		return config("http_page_path") ?: self::PAGE_PATH;
	}
	private function getDefaultThemes()
	{
		$this->defaultThemes = config("default_themes") ?: self::DEFAULT_THEMES;
	}
	private function getPage($path = "", $data = [])
	{
		$template = (new \think\View())->init("Think");
		return $template->fetch($path, $data);
	}
	private function existsReturn($path, $e)
	{
		$_path = $this->getPagePath() . $this->themeDirName . "/" . $path;
		if (!file_exists($_path)) {
			if ($this->themeDirName !== $this->defaultThemes) {
				$this->themeDirName = $this->defaultThemes;
				return $this->existsReturn($path, $e);
			}
			throw new \app\server\FileExistsExctption([$e->getCode(), $e->getMessage()]);
		}
		return $this->getPage($_path, ["msg" => $e->getMessage()]);
	}
}