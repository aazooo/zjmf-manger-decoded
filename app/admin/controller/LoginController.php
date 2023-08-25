<?php

namespace app\admin\controller;

class LoginController extends \think\Controller
{
	public function adminLogin()
	{
		if ($this->request->isPost()) {
			$a = $this->request->module();
			$a .= $this->request->controller();
			$a .= $this->request->action();
			return $a;
			$validate = new \app\admin\validate\AdminValidate();
			$data = $this->request->param();
			if (!$validate->check($data)) {
				return json(["status" => 406, "msg" => $validate->getError()]);
			}
			$adminuserModel = new \app\admin\model\AdminUserModel();
			$adminuser["username"] = trim($data["username"]);
			$adminuser["password"] = trim($data["password"]);
			$result = $adminuserModel->adminVerify($adminuser);
			return json($result);
		}
		return json(["status" => 400, "msg" => "请求错误"]);
	}
}