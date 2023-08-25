<?php

namespace app\common\logic;

require_once CMF_ROOT . "vendor/tcpdf/tcpdf.php";
require_once CMF_ROOT . "vendor/tcpdf/config/tcpdf_config.php";
class Pdf
{
	const PDF_LOGO = WEB_ROOT . "upload/common/contract/";
	const PDF_LOGO_WIDTH = 10;
	const PDF_TITLE = "";
	const PDF_HEAD = "浏览器信息 操作系统信息 时间 IP ：端口";
	const PDF_FONT = "stsongstdlight";
	const PDF_FONT_STYLE = "";
	const PDF_FONT_SIZE = 10;
	const PDF_FONT_MONOSPACED = "courier";
	const PDF_IMAGE_SCALE = "1.25";
	private $pdfLogo;
	protected $pdf;
	protected $pdf_head = "1233";
	/**
	 * 构造函数 引入插件并实例化
	 */
	public function __construct()
	{
		$this->pdfLogo = config("contract_get") . configuration("contract_company_logo");
		$this->pdf = new \TCPDF();
	}
	/**
	 * 设置文档信息
	 * @param  $user        string  文档作者
	 * @param  $title       string  文档标题
	 * @param  $subject     string  文档主题
	 * @param  $keywords    string  文档关键字
	 * @return null
	 */
	protected function setDocumentInfo($user = "", $title = "", $subject = "", $keywords = "")
	{
		if (empty($user) || empty($title)) {
			return false;
		}
		$this->pdf->SetCreator(PDF_CREATOR);
		$this->pdf->SetAuthor($user);
		$this->pdf->SetTitle($title);
		if (!empty($subject)) {
			$this->pdf->SetSubject($subject);
		}
		if (!empty($keywords)) {
			$this->pdf->SetKeywords($keywords);
		}
	}
	public function setPdfHead($pdf_head)
	{
		$this->pdf_head = $pdf_head;
	}
	/**
	 * 设置文档的页眉页脚信息
	 * @param  null
	 * @return null
	 */
	protected function setHeaderFooter()
	{
		$this->pdf->SetHeaderData(self::PDF_LOGO . $this->pdfLogo, self::PDF_LOGO_WIDTH, self::PDF_TITLE, $this->pdf_head, [35, 35, 35], [221, 221, 221]);
		$this->pdf->setFooterData([35, 35, 35], [221, 221, 221]);
		$this->pdf->setHeaderFont(["stsongstdlight", self::PDF_FONT_STYLE, self::PDF_FONT_SIZE]);
		$this->pdf->setFooterFont(["helvetica", self::PDF_FONT_STYLE, self::PDF_FONT_SIZE]);
	}
	/**
	 * 关闭页眉页脚
	 * @param  null
	 * @return null
	 */
	protected function closeHeaderFooter()
	{
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);
	}
	/**
	 * 设置间距 包括正文间距 页眉页脚间距
	 * @param  null
	 * @return null
	 */
	protected function setMargin()
	{
		$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$this->pdf->SetMargins(15, 15, 15);
		$this->pdf->SetHeaderMargin(1);
		$this->pdf->SetFooterMargin(10);
	}
	/**
	 * 正文设置 包括 分页 图片比例 正文字体
	 * @param  null
	 * @return null
	 */
	protected function setMainBody()
	{
		$this->pdf->SetAutoPageBreak(true, 25);
		$this->pdf->setImageScale(self::PDF_IMAGE_SCALE);
		$this->pdf->setFontSubsetting(true);
		$this->pdf->SetFont("stsongstdlight", "", 14, "", true);
		$this->pdf->AddPage();
	}
	/**
	 * 生成pdf
	 * @param  $info    array
	 *   array(
	 *          'user'=>'文档作者' ,
	 *          'title'=>'文档标题' ,
	 *          'subject'=>'文档主题' ,
	 *          'keywords'=>'文档关键字' ,
	 *          'content'=>'文档正文内容' ,
	 *          'HT'=>'是否开启页眉页脚' ,
	 *          'path'=>'文档保存路径',
	 *          ’html_align‘=>'生成pdf内容对齐方式');
	 * @return null
	 */
	public function createPDF($info = [])
	{
		if (empty($info) || !is_array($info)) {
			return false;
		}
		$this->setDocumentInfo($info["user"], $info["title"], $info["subject"], $info["keywords"]);
		if (!$info["HT"]) {
			$this->closeHeaderFooter();
		} else {
			$this->setHeaderFooter();
		}
		$this->setMargin();
		$this->setMainBody();
		$html_align = $info["html_align"] ?? "C";
		$this->pdf->writeHTML($info["content"], true, false, true, false, $html_align);
		$this->pdf->Output($info["path"], "F");
	}
	public function createPDFI($info = [])
	{
		if (empty($info) || !is_array($info)) {
			return false;
		}
		$this->setDocumentInfo($info["user"], $info["title"], $info["subject"], $info["keywords"]);
		if (!$info["HT"]) {
			$this->closeHeaderFooter();
		} else {
			$this->setHeaderFooter();
		}
		$this->setMargin();
		$this->setMainBody();
		$html_align = $info["html_align"] ?? "C";
		$this->pdf->writeHTML($info["content"], true, false, true, false, $html_align);
		$this->pdf->Output($info["path"], "I");
	}
	/**
	 * PDF基础配置注册
	 * @param  $info    array
	 *   array(
	 *          'user'=>'文档作者' ,
	 *          'title'=>'文档标题' ,
	 *          'subject'=>'文档主题' ,
	 *          'keywords'=>'文档关键字' ,
	 *          'HT'=>'是否开启页眉页脚' ,
	 * @return true
	 */
	public function createPDFConfig($info = [])
	{
		if (empty($info) || !is_array($info)) {
			return false;
		}
		$this->setDocumentInfo($info["user"], $info["title"], $info["subject"], $info["keywords"]);
		if (!$info["HT"]) {
			$this->closeHeaderFooter();
		} else {
			$this->setHeaderFooter();
		}
		$this->setMargin();
		$this->setMainBody();
		return true;
	}
	/** 当期那PDF对象
	 * @return \TCPDF|object
	 */
	public function getPdfObject()
	{
		return $this->pdf;
	}
	/**
	 * 创建印章-签名
	 * @param array $location_page
	 * $locator_data(
	 *      P => 指针跳转页码,
	 *      X => X坐标,
	 *      Y => Y坐标,
	 * @return true
	 */
	public function createSeal($filePath, $w = 50, $h = 50, $locator_data = [])
	{
		if (empty($locator_data)) {
			return false;
		}
		$tagvs = ["h1" => [["h" => 1, "n" => 3], ["h" => 1, "n" => 2]], "h2" => [["h" => 1, "n" => 2], ["h" => 1, "n" => 1]]];
		$this->pdf->setHtmlVSpace($tagvs);
		$this->pdf->setPage($locator_data["p"]);
		$this->pdf->Image($filePath, $locator_data["x"], $locator_data["y"], $w, $h);
		return true;
	}
}