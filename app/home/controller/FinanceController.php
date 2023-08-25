<?php

namespace app\home\controller;

/**
 * @title 财务
 */
class FinanceController extends CommonController
{
	/**
	 * @title 充值中心
	 * @author wyh
	 * @url index
	 * @method GET
	 * @return  client:客户信息@
	 * @client  username:用户名
	 * @client  phonenumber:手机号
	 * @client  credit:余额
	 * @return  ticket_count:待处理工单
	 * @return  order_count:待支付订单
	 * @return  over_due:即将过期
	 * @return  intotal:收入
	 * @return  outtotal：消费
	 * @return  news:公告通知
	 */
	public function index()
	{
	}
}