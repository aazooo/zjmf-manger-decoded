<?php
namespace gateways\wx_pay;

use app\admin\lib\Plugin;
use cmf\phpqrcode\QRcode;
use gateways\wx_pay\validate\WxPayValidate;
use gateways\wx_pay\lib\WxPayConfig;
use gateways\wx_pay\lib\WxPayUnifiedOrder;
use gateways\wx_pay\lib\WxPayApi;
use think\Db;

require_once __DIR__ . '/lib/WxPayApi.php';
require_once __DIR__ . '/lib/WxPayData.php';
require_once __DIR__ . '/lib/WxPayConfig.php';

class WxPayPlugin extends Plugin
{
    // 验证失败是否抛出异常
    protected $failException = true;

    public $info = array(
        'name'        => 'WxPay',
        'title'       => '微信支付',
        'description' => '微信支付',
        'status'      => 1,
        'author'      => '顺戴网络',
        'version'     => '1.0',
        'module'        => 'gateways',
    );

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        // 在这里不要try catch数据库异常，直接抛出上层会处理异常后回滚的
        return true;//卸载成功返回true，失败false
    }

//
    public function wxpayHandle($param)
    {
        $validate = new WxPayValidate();
        if(!$validate->check($param)){
            return ['status'=>400,'msg'=>$validate->getError()];
        }
        $domain = configuration('domain');
        $data['product_id'] = $param['out_trade_no'];
        $data['total_fee'] = $param['total_fee']*100; //> 单位分
        $data['out_trade_no'] = 'shd_'.date('Ymd').$param['out_trade_no'];
        $data['notify_url'] =  "{$domain}/gateway/wx_pay/index/notifyHandle";
        $data['trade_type'] =  'NATIVE';
        $data['product_name'] =  $param['product_name'];
        $data['attach'] =  $param['attach'];
        $data['fee_type'] =  'CNY'; //> 境内商户仅支持人名币
//
        //获取订单号,如未设置获取默认自定义规则
        if(!array_key_exists('out_trade_no',$data))
        {
            $data['out_trade_no'] = rand(100,999).date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }
        //获取支付类型默认是Native
        if(!array_key_exists('trade_type',$data))
        {
            $data['trade_type'] = 'Native';
        }
        return $this->setPay($data);
    }

    public function setPay($param)
    {
//      rand(100,999).date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)
        $input = new WxPayUnifiedOrder();
        $input->SetBody($param['product_name']); //商品名称
        $input->SetOut_trade_no($param['out_trade_no']); //商品订单号
        $input->SetTotal_fee($param['total_fee']);  //商品价格以分为初始单位
        $input->SetNotify_url($param['notify_url']); //回调地址
        $input->SetTrade_type($param['trade_type']);  //支付方式
        $input->SetProduct_id($param['product_id']); //商品自定义id
        $input->SetAttach($param['attach']); //商品自定义id
        $result = $this->GetPayUrl($input);
        //var_dump($input);die;
        //> 生成二维码
        if($result){
            trace(json_encode($result));
            $reData = array(
                'type'=>'url',
                'data'  =>  $result['code_url'],
            );
            if(!$result['code_url']){
                $reData['error'] = $result['err_code_des'];
            }
            return $reData;
//            echo QRcode::png($result['code_url']);
            exit;
        }else{
            return ['status'=>400,'msg'=>'二维码制作失败'];
        }
    }

    /**
     * 生成直接支付url，支付url有效期为2小时,模式二
     * @param UnifiedOrderInput $input
     */
    public function GetPayUrl($input)
    {
        if($input->GetTrade_type() == "NATIVE")
        {
            try{
                $config = new WxPayConfig();
                $WxPayApi = new WxPayApi();
                $result = $WxPayApi->unifiedOrder($config, $input);
                return $result;
            } catch(\Exception $e) {
                \Log::ERROR(json_encode($e));
            }
        }
        return false;
    }
    //获取商品名称
    public function getProductName()
    {

    }

}