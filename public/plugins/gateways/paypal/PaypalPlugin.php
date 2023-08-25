<?php
namespace gateways\paypal;
use app\admin\lib\Plugin;
use gateways\paypal\controller\ConfigController;
use \PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use \PayPal\Rest\ApiContext;
use \PayPal\Api\Item;
use \PayPal\Api\ItemList;
use \PayPal\Api\Payment;
use \PayPal\Api\Payer;
use \PayPal\Api\Amount;
use \PayPal\Api\Transaction;
use \PayPal\Api\RedirectUrls;
use think\Exception;

/*
 * wyh 2020-09-26
 * 1340863150@qq.com
 */
class PaypalPlugin extends Plugin
{
    public $info = array(
        'name'        => 'Paypal',
        'title'       => 'Paypal支付',
        'description' => 'Paypal支付,不支持人民币CNY,注意使用',
        'status'      => 1,
        'author'      => '顺戴网络',
        'version'     => '1.0',
        'module'      => 'gateways'
    );

    public $hasAdmin = 0;

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function paypalHandle($param)
    {
        $data['currency'] = $param['fee_type'];
        $data['body'] = $param['product_name'];
        $data['out_trade_no'] = $param['out_trade_no'];
        $data['subject'] = $param['product_name'];
        $data['total_amount'] = $param['total_fee'];

        $Con = new ConfigController();
        $config = $Con->getConfig();
        //$currency = 'HKD'; # 不能使用人民币
        $currency = $data['currency'];
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($data['out_trade_no']);
        //订单标题，必填
        $subject = trim($data['subject']);
        //付款金额，必填
        $total_amount = trim($data['total_amount']);
        //商品描述，可空
        $body = trim($data['body']);

        $apiContext = new ApiContext(new OAuthTokenCredential($config['clientId'],$config['clientSecret']));
        $apiContext->setConfig(array('mode' => $config['mode'])); # 生产环境
        # 支付
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        # 设置支付项
        $item1 = new Item();
        $item1->setName($body)
            ->setCurrency($currency)
            ->setQuantity(1)
            ->setSku($out_trade_no) // Similar to `item_number` in Classic API  物品号
            ->setPrice($total_amount);
        $itemList = new ItemList();
        $itemList->setItems(array($item1));
        # 金额
        $amount = new Amount();
        $amount->setTotal($total_amount);
        $amount->setCurrency($currency);
        # 交易
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($subject)
            ->setInvoiceNumber($out_trade_no);
        # 设置回调地址/取消支付地址
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($config['notify_url'])
            ->setCancelUrl($config['cancel_url']);
        # 创建支付链接
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        try {
            $payment->create($apiContext);
            $approvalUrl = $payment->getApprovalLink();

        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            if (json_decode($ex->getData(),true)['message']) {
                throw new \Exception(json_decode($ex->getData(),true)['message']);
            }elseif (json_decode($ex->getData(),true)['error']) {
                throw new \Exception(json_decode($ex->getData(),true)['error']);
            }else{
                throw new \Exception('未知错误');
            }
        }
        # 输出表单
        $reData = array(
            'type'  => 'jump',
            'data'  =>  $approvalUrl,
        );
        return $reData;
    }

}