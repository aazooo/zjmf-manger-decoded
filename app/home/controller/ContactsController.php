<?php

namespace app\home\controller;

/**
 * @title 前台子账户管理
 * @description 接口说明: 子账户管理
 */
class ContactsController extends CommonController
{
	/**
	 * @title 客户子账户管理页面
	 * @description 接口说明:客户子账户管理页面
	 * @author 萧十一郎
	 * @url contacts/index
	 * @method GET
	 * @param .name:cid type:int require:0 default: other: desc:子账户id
	 * @return contact_list:子账户列表@
	 * @contact_list  id:子账户id
	 * @contact_list  username:用户名
	 * @contact_list  email:子账户邮箱账号
	 * @return cid:子账户id
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
	public function index(\think\Request $request)
	{
		$param = $request->param();
		$uid = $request->uid;
		$cid = $param["cid"];
		$contact_list = \think\Db::name("contacts")->field("id,username,email")->where("uid", $uid)->select()->toArray();
		if (empty($cid) && !empty($contact_list[0])) {
			$cid = $contact_list[0]["id"];
		}
		$returndata = [];
		$returndata["contact_list"] = $contact_list;
		if (!empty($cid)) {
			$contact_data = \think\Db::name("contacts")->field("password,create_time,update_time", true)->where("id", $cid)->where("uid", $uid)->find();
			$permissions = $contact_data["permissions"];
			if (!empty($permissions)) {
				$contact_data["permissions_arr"] = explode(",", $permissions);
			}
			$returndata["contact_data"] = $contact_data;
			$returndata["cid"] = $cid;
		}
		$returndata["permissions"] = config("contact_permissions");
		return json(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 保存/添加子账户
	 * @description 接口说明:保存/添加子账户
	 * @author 萧十一郎
	 * @url contacts/save
	 * @method POST
	 * @param .name:cid type:int require:0 default: other: desc:子账户id
	 * @param .name:username type:string require: default: other: desc:用户名
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
	 * @param .name:status type:int require:0 default: other: desc:状态（1激活，0未激活，2关闭）,激活代表该联系人成为子账户，可以登录管理
	 * @param .name:password type:string require:0 default: other: desc:设置的子账户密码
	 * @param .name:permissions type:array require:0 default: other: desc:权限数组
	 */
	public function save(\think\Request $request)
	{
		if ($request->isPost()) {
			$param = $request->param();
			$uid = $request->uid;
			$cid = $param["cid"];
			$rule = ["cid" => "number", "username" => "chsDash", "sex" => "in:0,1,2", "email" => "require|email", "postcode" => "number", "phonenumber" => "mobile", "generalemails" => "in:0,1", "invoiceemails" => "in:0,1", "productemails" => "in:0,1", "supportemails" => "in:0,1", "status" => "in:1,0,2", "permissions" => "array"];
			$msg = ["cid.number" => lang("CONTACTS_SAVE_VERIFY_CID_NUMBER"), "username.chsDash" => lang("CONTACTS_SAVE_VERIFY_UNAME_CHADASH"), "sex.in" => lang("CONTACTS_SAVE_VERIFY_SEX_IN"), "email.require" => lang("CONTACTS_SAVE_EMAIL_REQUIRE"), "email.email" => lang("CONTACTS_SAVE_EMAIL_EMAIL"), "postcode.number" => lang("CONTACTS_SAVE_POSTCODE_NUMBER"), "phonenumber.mobile" => lang("CONTACTS_SAVE_PNUM_MOBILE")];
			$validate = new \think\Validate($rule, $msg);
			$result = $validate->check($param);
			if (!$result) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			$user_data = \think\Db::name("clients")->field("id,username")->find($uid);
			if (empty($user_data)) {
				return json(["status" => 400, "msg" => "用户id错误"]);
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
			if (!empty($cid)) {
				$contact_exists = \think\Db::name("contacts")->where("email", $param["email"])->find();
				$client_exists = \think\Db::name("clients")->where("email", $param["email"])->find();
				if (!empty($contact_exists) && $contact_exists["id"] != $cid) {
					return json(["status" => 400, "msg" => lang("CONTACTS_EMAIL_IS_EXISTS")]);
				}
				if (!empty($client_exists)) {
					return json(["status" => 400, "msg" => lang("CONTACTS_EMAIL_IS_EXISTS")]);
				}
				$udata["update_time"] = time();
				\think\Db::name("contacts")->where("id", $cid)->update($udata);
			} else {
				$contact_exists = \think\Db::name("contacts")->where("email", $param["email"])->find();
				$client_exists = \think\Db::name("clients")->where("email", $param["email"])->find();
				if (!empty($contact_exists) || !empty($client_exists)) {
					return json(["status" => 400, "msg" => lang("CONTACTS_EMAIL_IS_EXISTS")]);
				}
				$udata["create_time"] = time();
				$iid = \think\Db::name("contacts")->insertGetId($udata);
				if ($request->subaccountid) {
					active_log("添加联系人 - Contacts ID:" . $iid);
				} else {
					active_log("添加联系人 - Contacts ID:" . $iid);
				}
			}
			return json(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
	}
	/**
	 * @title 删除子账户
	 * @description 接口说明:删除子账户
	 * @author 萧十一郎
	 * @url contacts/del
	 * @method DELETE
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:cid type:int require:1 default: other: desc:子账户id
	 */
	public function delete(\think\Request $request)
	{
		$param = $request->param();
		$uid = $request->uid;
		$cid = $param["cid"];
		if (empty($uid)) {
			return json(["status" => 400, "msg" => lang("CONTACTS_USER_NOT_FOUND")]);
		}
		if (empty($cid)) {
			return json(["status" => 400, "msg" => lang("CONTACTS_SON_USER_NOT_FOUND")]);
		}
		$contact_data = \think\Db::name("contacts")->where("id", $cid)->where("uid", $uid)->find();
		if (empty($contact_data)) {
			return json(["status" => 400, "msg" => lang("CONTACTS_SON_USER_NOT_FOUND")]);
		}
		\think\Db::name("contacts")->where("id", $cid)->where("uid", $uid)->delete();
		active_log("删除联系人 - Contacts ID:" . $cid);
		return json(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}