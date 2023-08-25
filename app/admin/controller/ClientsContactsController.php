<?php

namespace app\admin\controller;

/**
 * @title 客户子账户管理
 * @description 接口说明
 */
class ClientsContactsController extends AdminBaseController
{
	/**
	 * @title 客户子账户管理页面
	 * @description 接口说明:客户子账户管理页面
	 * @author 萧十一郎
	 * @url /admin/client_contacts/page
	 * @method GET
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:contactid type:int require:0 default: other: desc:子账户id
	 * @return uid:用户id
	 * @return contact_list:子账户列表@
	 * @contact_list  id:子账户id
	 * @contact_list  username:用户名
	 * @contact_list  email:子账户邮箱账号
	 * @return contactid:子账户id
	 * @return contact_data:子账户数据@
	 * @contact_data  id:子账户id
	 * @contact_data  username:用户名
	 * @contact_data  sex:性别
	 * @contact_data  avatar:头像地址
	 * @contact_data  companyname:公司名
	 * @contact_data  email:邮箱
	 * @contact_data  wechat_id:微信id
	 * @contact_data  country:国家
	 * @contact_data  province:省份
	 * @contact_data  city:城市
	 * @contact_data  region:区
	 * @contact_data  address1:地址一
	 * @contact_data  address2:地址二
	 * @contact_data  postcode:邮编
	 * @contact_data  phonenumber:手机号
	 * @contact_data  permissions:权限（使用另一个permissions_arr字段）
	 * @contact_data  generalemails:接收通用邮件通知
	 * @contact_data  invoiceemails:接收账单通知
	 * @contact_data  productemails:接收产品邮件通知
	 * @contact_data  supportemails:接收工单邮件通知
	 * @contact_data  authmodule:
	 * @contact_data  authdata:
	 * @contact_data  lastlogin:
	 * @contact_data  status:
	 */
	public function getPage(\think\Request $request)
	{
		$param = $request->param();
		$uid = $param["uid"];
		$contactid = $param["contactid"];
		if (empty($uid)) {
			return jsonrule(["status" => 406, "msg" => "客户编号未找到"]);
		}
		$order = isset($param["order"]) ? trim($param["order"]) : "id";
		$sort = isset($param["sort"]) ? trim($param["sort"]) : "DESC";
		$contact_list = \think\Db::name("contacts")->field("id,username,email")->where("uid", $uid)->order($order, $sort)->select()->toArray();
		if (empty($contactid) && !empty($contact_list[0])) {
			$contactid = $contact_list[0]["id"];
		}
		$returndata = [];
		$returndata["uid"] = $uid;
		$returndata["contact_list"] = $contact_list;
		if (!empty($contactid)) {
			$contact_data = \think\Db::name("contacts")->where("id", $contactid)->where("uid", $uid)->find();
			$permissions = $contact_data["permissions"];
			if (!empty($permissions)) {
				$contact_data["permissions_arr"] = explode(",", $permissions);
			}
			$returndata["contact_data"] = $contact_data;
			$returndata["contactid"] = $contactid;
		}
		$returndata["permissions"] = config("contact_permissions");
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 保存/添加子账户
	 * @description 接口说明:保存/添加子账户
	 * @author 萧十一郎
	 * @url /admin/client_contacts/save
	 * @method POST
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:contactid type:int require:0 default: other: desc:子账户id
	 * @param .name:username type:string require:0 default: other: desc:用户名
	 * @param .name:sex type:int require:1 default:0 other: desc:性别(0未知，1男，2女)
	 * @param .name:avatar type:string require:0 default: other: desc:头像地址
	 * @param .name:companyname type:string require:0 default: other: desc:公司名
	 * @param .name:email type:string require:1 default: other: desc:邮箱
	 * @param .name:wechat_id type:string require:0 default: other: desc:微信id
	 * @param .name:country type:string require:0 default: other: desc:国家
	 * @param .name:province type:string require:0 default: other: desc:省份
	 * @param .name:city type:string require:0 default: other: desc:城市
	 * @param .name:region type:string require:0 default: other: desc:区
	 * @param .name:address1 type:string require:0 default: other: desc:地址一
	 * @param .name:address2 type:string require:0 default: other: desc:地址二
	 * @param .name:postcode type:number require:0 default: other: desc:邮编
	 * @param .name:phonenumber type:int require:0 default: other: desc:手机号
	 * @param .name:generalemails type:int require:0 default: other: desc:接收通用邮件通知(0,1)
	 * @param .name:invoiceemails type:int require:0 default: other: desc:接收账单通知
	 * @param .name:productemails type:int require:0 default: other: desc:接收产品邮件通知
	 * @param .name:supportemails type:int require:0 default: other: desc:接收工单邮件通知
	 * @param .name:status type:int require:0 default: other: desc:状态（1激活，0未激活，2关闭）
	 * @param .name:password type:string require:0 default: other: desc:设置的子账户密码
	 * @param .name:permissions type:array require:0 default: other: desc:权限数组
	 */
	public function postSave(\think\Request $request)
	{
		if ($request->isPost()) {
			$param = $request->param();
			$uid = $param["uid"];
			$contactid = $param["contactid"];
			$rule = ["uid" => "require|number", "contactid" => "number", "username" => "chsDash", "sex" => "in:0,1,2", "email" => "require|email", "postcode" => "number", "phonenumber" => "mobile", "generalemails" => "in:0,1", "invoiceemails" => "in:0,1", "productemails" => "in:0,1", "supportemails" => "in:0,1", "status" => "in:1,0,2", "permissions" => "array"];
			$msg = ["uid.require" => "用户id不能为空", "uid.number" => "用户id必须为数字", "contactid.number" => "子账户id必须为数字", "username.chsDash" => "用户名只能是汉字、字母、数字和下划线_及破折号-", "sex.in" => "性别错误", "email.require" => "邮箱不能为空", "email.email" => "邮箱格式错误", "postcode.number" => "邮编必须为数字", "phonenumber.mobile" => "手机号格式错误"];
			$validate = new \think\Validate($rule, $msg);
			$result = $validate->check($param);
			if (!$result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$user_data = \think\Db::name("clients")->field("id,username")->find($uid);
			if (empty($user_data)) {
				return jsonrule(["status" => 406, "msg" => "用户id错误"]);
			}
			$udata = [];
			$udata = ["uid" => $uid, "username" => $param["username"] ?: "", "sex" => $param["sex"] ?: 0, "avatar" => $param["avatar"] ?: "", "companyname" => $param["companyname"] ?: "", "email" => $param["email"], "wechat_id" => $param["wechat_id"], "country" => $param["country"] ?: "", "province" => $param["province"] ?: "", "city" => $param["city"] ?: "", "region" => $param["region"] ?: "", "address1" => $param["address1"] ?: "", "address2" => $param["address2"] ?: "", "postcode" => $param["postcode"] ?: 0, "phonenumber" => $param["phonenumber"] ?: "", "generalemails" => $param["generalemails"] ?: 0, "invoiceemails" => $param["invoiceemails"] ?: 0, "productemails" => $param["productemails"] ?: 0, "supportemails" => $param["supportemails"] ?: 0, "status" => $param["status"] ?: 0];
			$permissions = $param["permissions"];
			if (is_array($permissions) && !empty($permissions)) {
				$udata["permissions"] = implode(",", $permissions);
			}
			if (!empty($param["password"])) {
				$udata["password"] = cmf_password($param["password"]);
			}
			if (!empty($contactid)) {
				$contact_exists = \think\Db::name("contacts")->where("email", $param["email"])->find();
				$client_exists = \think\Db::name("clients")->where("email", $param["email"])->find();
				if (!empty($contact_exists) && $contact_exists["id"] != $contactid) {
					return jsonrule(["status" => 406, "msg" => "该邮箱已存在"]);
				}
				if (!empty($client_exists)) {
					return jsonrule(["status" => 406, "msg" => "该邮箱已存在"]);
				}
				$udata["update_time"] = time();
				\think\Db::name("contacts")->where("id", $contactid)->update($udata);
			} else {
				$contact_exists = \think\Db::name("contacts")->where("email", $param["email"])->find();
				$client_exists = \think\Db::name("clients")->where("email", $param["email"])->find();
				if (!empty($contact_exists) || !empty($client_exists)) {
					return jsonrule(["status" => 406, "msg" => "该邮箱已存在"]);
				}
				$udata["create_time"] = time();
				$iid = \think\Db::name("contacts")->insertGetId($udata);
				active_log("添加联系人 - Contacts ID:" . $iid, $uid);
			}
			return jsonrule(["status" => 200, "msg" => "保存成功"]);
		}
	}
	/**
	 * @title 删除子账户
	 * @description 接口说明:删除子账户
	 * @author 萧十一郎
	 * @url /admin/client_contacts/contact
	 * @method DELETE
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:contactid type:int require:1 default: other: desc:子账户id
	 */
	public function deleteContact(\think\Request $request)
	{
		$param = $request->param();
		$uid = $param["uid"];
		$contactid = $param["contactid"];
		if (empty($uid)) {
			return jsonrule(["status" => 406, "msg" => "用户未找到"]);
		}
		if (empty($contactid)) {
			return jsonrule(["status" => 406, "msg" => "子账户未找到"]);
		}
		$contact_data = \think\Db::name("contacts")->where("id", $contactid)->where("uid", $uid)->find();
		if (empty($contact_data)) {
			return jsonrule(["status" => 406, "msg" => "子账户未找到"]);
		}
		\think\Db::name("contacts")->where("id", $contactid)->where("uid", $uid)->delete();
		return jsonrule(["status" => 200, "msg" => "删除成功"]);
	}
}