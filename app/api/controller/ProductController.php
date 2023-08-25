<?php
namespace app\api\controller;

use app\common\logic\Cart;
use app\common\logic\Dcim;
use app\common\logic\Product;
use app\common\logic\Provision;
use think\Db;
use think\db\Query;

class ProductController
{
    /**
     * @time 2021-12-02
     * @title 获取所有(或者指定商品ID)商品的ID、名称、本地版本号,以及货币代码
     * @description 获取所有(或者指定商品ID)商品的ID、名称、本地版本号,以及货币代码
     * @url api/product/proinfo
     * @method  GET
     * @author wyh
     * @param .name:pids[] type:array require:0 default:1 other: desc:指定商品ID,数组(可选参数)
     * @return info:商品信息@
     * @info id:ID
     * @info name:名称
     * @info location_version:本地版本号
     */
    public function proInfo()
    {
        $param = request()->param();

        if (isset($param['pids'])){
            if (!is_array($param['pids'])){
                $pids = [$param['pids']];
            }else{
                $pids = $param['pids'];
            }
        }else{
            $pids = [];
        }

        # 全局永久缓存:添加/编辑/修改商品信息、商品配置项信息、价格时,会修改此值
        $logic = new Product();

        $infos = $logic->getInfoCache();

        if (empty($infos)){

            $logic->updateInfoCache();

            $infos = $logic->getInfoCache();
        }

        if (!empty($pids[0])){

            $infos = array_filter($infos,function ($value) use ($pids){
                if (!in_array($value['id'],$pids)){
                    return false;
                }
                return true;
            });

            $infos = array_values($infos);

            # TODO 解决目前新增商品未缓存数据的问题
            if (empty($infos)){

                $logic->updateInfoCache();

                $infos = $logic->getInfoCache();

                $infos = array_filter($infos,function ($value) use ($pids){
                    if (!in_array($value['id'],$pids)){
                        return false;
                    }
                    return true;
                });

                $infos = array_values($infos);

            }
        }

        $currency = Db::name('currencies')->where('default',1)->value('code');

        $data = [
            'info' => $infos,
            'currency' => $currency
        ];

        return json([
            'status' => 200,
            'msg' => '请求成功',
            'data' => $data
        ]);
    }

    /**
     * @time 2021-12-02
     * @title 获取所有(或者指定商品ID)商品的详细信息
     * @description 获取所有(或者指定商品ID)商品的详细信息
     * @url api/product/prodetail
     * @method  GET
     * @author wyh
     * @param .name:pids[] type:array require:0 default:1 other: desc:指定商品ID,数组(可选参数,尽量传输,否则会内存溢出)
     * @return detail:商品信息@
     */
    public function proDetail()
    {
        $param = request()->param();

        if (isset($param['pids'])){

            if (!is_array($param['pids'])){
                $pids = [$param['pids']];
            }else{
                $pids = $param['pids'];
            }

        }else{
            $pids = Db::name('products')->column('id')?:[];
        }

        $logic = new Product();

        $concurrent = $logic->concurrent;

        if (count($pids)>$concurrent){

            return json(['status'=>400,'msg'=>"商品数量过多,请分批请求,最大请求数量为{$concurrent}个"]);

        }

        $detail = [];

        foreach ($pids as $pid){

            $tmp = $logic->getDetailCache($pid);

            if (empty($tmp)){

                $logic->updateDetailCache([$pid]);

            }

            $tmp = $logic->getDetailCache($pid);

            $detail[$pid] = $tmp[$pid];

        }

        $data = [
            'detail' => $detail
        ];

        return json([
            'status' => 200,
            'msg' => '请求成功',
            'data' => $data
        ]);
    }

    public function proList()
    {
        $filterproducts = Db::name('products')->field('id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty')
            ->whereIn('type',['dcim','dcimcloud'])
            ->select()->toArray();
        $currencyid = 1;
        $uid = !empty(request()->uid)?request()->uid:'';
        $newfilterproducts = [];
        foreach ($filterproducts as $key => $v){
            if (!empty($v)){
                $paytype = (array)json_decode($v['pay_type']);
                $pricing = Db::name('pricing')
                    ->where('type','product')
                    ->where('relid' , $v['id'])
                    ->where('currency',$currencyid)
                    ->find();
                if(!empty($paytype['pay_ontrial_status'])){
                    if ($pricing['ontrial'] >= 0){
                        $v['product_price'] = $pricing['ontrial'];
                        $v['setup_fee'] = $pricing['ontrialfee'];
                        $v['billingcycle'] = 'ontrial';
                        $v['billingcycle_zh'] = lang('ONTRIAL');
                    }else{
                        $v['product_price'] = 0;
                        $v['setup_fee'] = 0;
                        $v['billingcycle'] = '';
                        $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                    }
                    $v['ontrial'] = 1;
                    $v['ontrial_cycle'] = $paytype['pay_ontrial_cycle'];
                    $v['ontrial_cycle_type'] = $paytype['pay_ontrial_cycle_type']?:'day';
                    $v['ontrial_price'] = $pricing['ontrial'];
                    $v['ontrial_setup_fee'] = $pricing['ontrialfee'];
                }else{
                    $v['ontrial'] = 0;
                }
                if ($paytype['pay_type'] == 'free'){
                    $v['product_price'] = 0;
                    $v['setup_fee'] = 0;
                    $v['billingcycle'] = 'free';
                    $v['billingcycle_zh'] = lang('FREE');
                } elseif ($paytype['pay_type'] == 'onetime'){
                    if ($pricing['onetime'] >= 0){
                        $v['product_price'] = $pricing['onetime'];
                        $v['setup_fee'] = $pricing['osetupfee'];
                        $v['billingcycle'] = 'onetime';
                        $v['billingcycle_zh'] = lang('ONETIME');
                    }else{
                        $v['product_price'] = 0;
                        $v['setup_fee'] = 0;
                        $v['billingcycle'] = '';
                        $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                    }
                }
                else{
                    if (!empty($pricing) && $paytype['pay_type'] == 'recurring'){
                        if ($pricing['hour'] >= 0){
                            $v['product_price'] = $pricing['hour'];
                            $v['setup_fee'] = $pricing['hsetupfee'];
                            $v['billingcycle'] = 'hour';
                            $v['billingcycle_zh'] = lang('HOUR');
                        }
                        elseif ($pricing['day'] >= 0){
                            $v['product_price'] = $pricing['day'];
                            $v['setup_fee'] = $pricing['dsetupfee'];
                            $v['billingcycle'] = 'day';
                            $v['billingcycle_zh'] = lang('DAY');
                        }
                        elseif($pricing['monthly'] >= 0){
                            $v['product_price'] = $pricing['monthly'];
                            $v['setup_fee'] = $pricing['msetupfee'];
                            $v['billingcycle'] = 'monthly';
                            $v['billingcycle_zh'] = lang('MONTHLY');
                        }elseif ($pricing['quarterly'] >= 0){
                            $v['product_price'] = $pricing['quarterly'];
                            $v['setup_fee'] = $pricing['qsetupfee'];
                            $v['billingcycle'] = 'quarterly';
                            $v['billingcycle_zh'] = lang('QUARTERLY');
                        }elseif ($pricing['semiannually'] >= 0){
                            $v['product_price'] = $pricing['semiannually'];
                            $v['setup_fee'] = $pricing['ssetupfee'];
                            $v['billingcycle'] = 'semiannually';
                            $v['billingcycle_zh'] = lang('SEMIANNUALLY');
                        }elseif ($pricing['annually'] >= 0){
                            $v['product_price'] = $pricing['annually'];
                            $v['setup_fee'] = $pricing['asetupfee'];
                            $v['billingcycle'] = 'annually';
                            $v['billingcycle_zh'] = lang('ANNUALLY');
                        }elseif ($pricing['biennially'] >= 0){
                            $v['product_price'] = $pricing['biennially'];
                            $v['setup_fee'] = $pricing['bsetupfee'];
                            $v['billingcycle'] = 'biennially';
                            $v['billingcycle_zh'] = lang('BIENNIALLY');
                        }elseif ($pricing['triennially'] >= 0){
                            $v['product_price'] = $pricing['triennially'];
                            $v['setup_fee'] = $pricing['tsetupfee'];
                            $v['billingcycle'] = 'triennially';
                            $v['billingcycle_zh'] = lang('TRIENNIALLY');
                        }elseif ($pricing['fourly'] >= 0){
                            $v['product_price'] = $pricing['fourly'];
                            $v['setup_fee'] = $pricing['foursetupfee'];
                            $v['billingcycle'] = 'fourly';
                            $v['billingcycle_zh'] = lang('FOURLY');
                        }elseif ($pricing['fively'] >= 0){
                            $v['product_price'] = $pricing['fively'];
                            $v['setup_fee'] = $pricing['fivesetupfee'];
                            $v['billingcycle'] = 'fively';
                            $v['billingcycle_zh'] = lang('FIVELY');
                        }elseif ($pricing['sixly'] >= 0){
                            $v['product_price'] = $pricing['sixly'];
                            $v['setup_fee'] = $pricing['sixsetupfee'];
                            $v['billingcycle'] = 'sixly';
                            $v['billingcycle_zh'] = lang('SIXLY');
                        }elseif ($pricing['sevenly'] >= 0){
                            $v['product_price'] = $pricing['sevenly'];
                            $v['setup_fee'] = $pricing['sevensetupfee'];
                            $v['billingcycle'] = 'sevenly';
                            $v['billingcycle_zh'] = lang('SEVENLY');
                        }elseif ($pricing['eightly'] >= 0){
                            $v['product_price'] = $pricing['eightly'];
                            $v['setup_fee'] = $pricing['eightsetupfee'];
                            $v['billingcycle'] = 'eightly';
                            $v['billingcycle_zh'] = lang('EIGHTLY');
                        }elseif ($pricing['ninely'] >= 0){
                            $v['product_price'] = $pricing['ninely'];
                            $v['setup_fee'] = $pricing['ninesetupfee'];
                            $v['billingcycle'] = 'ninely';
                            $v['billingcycle_zh'] = lang('NINELY');
                        }elseif($pricing['tenly'] >= 0){
                            $v['product_price'] = $pricing['tenly'];
                            $v['setup_fee'] = $pricing['tensetupfee'];
                            $v['billingcycle'] = 'tenly';
                            $v['billingcycle_zh'] = lang('TENLY');
                        }else{
                            $v['product_price'] = 0;
                            $v['setup_fee'] = 0;
                            $v['billingcycle'] = '';
                            $v['billingcycle_zh'] = lang('PRICE_CONFIG_ERROR');
                        }
                    }else{
                        $v['product_price'] = 0;
                        $v['setup_fee'] = 0;
                        $v['billingcycle'] = '';
                        $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                    }
                }
                # 应用默认显示年付价格
                if ($paytype['pay_type'] == 'recurring' && in_array($v['type'],array_keys(config('developer_app_product_type')))){
                    if ($pricing['annually'] > 0){
                        $v['product_price'] = $pricing['annually'];
                        $v['setup_fee'] = $pricing['asetupfee'];
                        $v['billingcycle'] = 'annually';
                        $v['billingcycle_zh'] = lang('ANNUALLY');
                    }
                }
                $v['product_price']=bcadd($v['setup_fee'],$v['product_price'],2);

                $cart_logic = new Cart();
                $rebate_total = 0; # 配置项享受折扣的总价格
                $config_total = $cart_logic->getProductDefaultConfigPrice($v['id'],$currencyid,$v['billingcycle'],$rebate_total);
                $rebate_total = bcadd($v['product_price'],$rebate_total,2);

                $v['product_price'] = bcadd($v['product_price'],$config_total,2);



                if ($v['api_type'] == 'zjmf_api' && $v['upstream_version'] > 0 && $v['upstream_price_type'] == 'percent'){ # 上游产品
                    $v['product_price'] = bcmul($v['product_price'],$v['upstream_price_value'] / 100,2);

                    if ($v['ontrial'] == 1){
                        $v['ontrial_price'] = bcmul($v['ontrial_price'],$v['upstream_price_value'] / 100,2);
                        $v['ontrial_setup_fee'] = bcmul($v['ontrial_setup_fee'],$v['upstream_price_value'] / 100,2);
                    }

                    $rebate_total = bcmul($rebate_total,$v['upstream_price_value'] / 100,2);
                }

                if ($v['api_type'] == 'resource'){
                    $grade = resourceUserGradePercent($uid,$v['id']);
                    $v['product_price'] = bcmul($v['product_price'],$grade / 100,2);

                    if ($v['ontrial'] == 1){
                        $v['ontrial_price'] = bcmul($v['ontrial_price'],$grade / 100,2);
                        $v['ontrial_setup_fee'] = bcmul($v['ontrial_setup_fee'],$grade / 100,2);
                    }

                    $rebate_total = bcmul($rebate_total,$grade / 100,2);
                }

                $flag = getSaleProductUser($v['id'],$uid);
                $v['sale_price'] = $v['bates'] = 0;
                $v['has_bates'] = 0;
                if ($flag) {
                    if ($flag['type'] == 1){
                        $bates = bcdiv($flag['bates'],100,2);
                        $rebate = bcmul($rebate_total,1-$bates,2)<0?0:bcmul($rebate_total,1-$bates,2);
                        $v['sale_price'] = bcsub($v['product_price'],$rebate,2)<0?0:bcsub($v['product_price'],$rebate,2);
                        #$v['sale_price'] = bcmul($v['product_price'],$bates,2)<0?0:bcmul($v['product_price'],$bates,2);
                        $v['bates'] = bcmul($v['product_price'],1-$bates,2);
                    }elseif ($flag['type'] == 2){
                        $bates = $flag['bates'];
                        $rebate = $rebate_total<$bates?$rebate_total:$bates;
                        $v['sale_price'] = bcsub($v['product_price'],$rebate,2)<0?0:bcsub($v['product_price'],$rebate,2);
                        $v['bates'] = $bates;
                    }
                    $v['has_bates'] = 1;
                }
            }
            $payType =json_decode($v['pay_type'],true)['pay_type']??'';
            if ($payType=='recurring'){
                $v['pay_type'] = 'recurring_prepayment';
            }else{
                $v['pay_type'] = $payType;
            }
            $v['price'] = $v['product_price'];
            $v['cycle'] = $v['billingcycle_zh'];
            $newfilterproducts[$key] = $v;
            if( $v['billingcycle']==''){
                unset($newfilterproducts[$key]);
            }

        }
        $newfilterproducts=array_values($newfilterproducts);
        return json([
            'status' => 200,
            'msg' => '请求成功',
            'data' => [
                'list' => $newfilterproducts
            ]
        ]);
    }

    public function detail()
    {
        $param = request()->param();

        $id = $param['id']??0;

        $v = Db::name('products')->field('id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty')
            ->where('id',$id)
            ->find();

        $currencyid = 1;
        $uid = !empty(request()->uid)?request()->uid:'';

        if (!empty($v)){
            $paytype = (array)json_decode($v['pay_type']);
            $pricing = Db::name('pricing')
                ->where('type','product')
                ->where('relid' , $v['id'])
                ->where('currency',$currencyid)
                ->find();
            if(!empty($paytype['pay_ontrial_status'])){
                if ($pricing['ontrial'] >= 0){
                    $v['product_price'] = $pricing['ontrial'];
                    $v['setup_fee'] = $pricing['ontrialfee'];
                    $v['billingcycle'] = 'ontrial';
                    $v['billingcycle_zh'] = lang('ONTRIAL');
                }else{
                    $v['product_price'] = 0;
                    $v['setup_fee'] = 0;
                    $v['billingcycle'] = '';
                    $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                }
                $v['ontrial'] = 1;
                $v['ontrial_cycle'] = $paytype['pay_ontrial_cycle'];
                $v['ontrial_cycle_type'] = $paytype['pay_ontrial_cycle_type']?:'day';
                $v['ontrial_price'] = $pricing['ontrial'];
                $v['ontrial_setup_fee'] = $pricing['ontrialfee'];
            }else{
                $v['ontrial'] = 0;
            }
            if ($paytype['pay_type'] == 'free'){
                $v['product_price'] = 0;
                $v['setup_fee'] = 0;
                $v['billingcycle'] = 'free';
                $v['billingcycle_zh'] = lang('FREE');
            } elseif ($paytype['pay_type'] == 'onetime'){
                if ($pricing['onetime'] >= 0){
                    $v['product_price'] = $pricing['onetime'];
                    $v['setup_fee'] = $pricing['osetupfee'];
                    $v['billingcycle'] = 'onetime';
                    $v['billingcycle_zh'] = lang('ONETIME');
                }else{
                    $v['product_price'] = 0;
                    $v['setup_fee'] = 0;
                    $v['billingcycle'] = '';
                    $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                }
            }
            else{
                if (!empty($pricing) && $paytype['pay_type'] == 'recurring'){
                    if ($pricing['hour'] >= 0){
                        $v['product_price'] = $pricing['hour'];
                        $v['setup_fee'] = $pricing['hsetupfee'];
                        $v['billingcycle'] = 'hour';
                        $v['billingcycle_zh'] = lang('HOUR');
                    }
                    elseif ($pricing['day'] >= 0){
                        $v['product_price'] = $pricing['day'];
                        $v['setup_fee'] = $pricing['dsetupfee'];
                        $v['billingcycle'] = 'day';
                        $v['billingcycle_zh'] = lang('DAY');
                    }
                    elseif($pricing['monthly'] >= 0){
                        $v['product_price'] = $pricing['monthly'];
                        $v['setup_fee'] = $pricing['msetupfee'];
                        $v['billingcycle'] = 'monthly';
                        $v['billingcycle_zh'] = lang('MONTHLY');
                    }elseif ($pricing['quarterly'] >= 0){
                        $v['product_price'] = $pricing['quarterly'];
                        $v['setup_fee'] = $pricing['qsetupfee'];
                        $v['billingcycle'] = 'quarterly';
                        $v['billingcycle_zh'] = lang('QUARTERLY');
                    }elseif ($pricing['semiannually'] >= 0){
                        $v['product_price'] = $pricing['semiannually'];
                        $v['setup_fee'] = $pricing['ssetupfee'];
                        $v['billingcycle'] = 'semiannually';
                        $v['billingcycle_zh'] = lang('SEMIANNUALLY');
                    }elseif ($pricing['annually'] >= 0){
                        $v['product_price'] = $pricing['annually'];
                        $v['setup_fee'] = $pricing['asetupfee'];
                        $v['billingcycle'] = 'annually';
                        $v['billingcycle_zh'] = lang('ANNUALLY');
                    }elseif ($pricing['biennially'] >= 0){
                        $v['product_price'] = $pricing['biennially'];
                        $v['setup_fee'] = $pricing['bsetupfee'];
                        $v['billingcycle'] = 'biennially';
                        $v['billingcycle_zh'] = lang('BIENNIALLY');
                    }elseif ($pricing['triennially'] >= 0){
                        $v['product_price'] = $pricing['triennially'];
                        $v['setup_fee'] = $pricing['tsetupfee'];
                        $v['billingcycle'] = 'triennially';
                        $v['billingcycle_zh'] = lang('TRIENNIALLY');
                    }elseif ($pricing['fourly'] >= 0){
                        $v['product_price'] = $pricing['fourly'];
                        $v['setup_fee'] = $pricing['foursetupfee'];
                        $v['billingcycle'] = 'fourly';
                        $v['billingcycle_zh'] = lang('FOURLY');
                    }elseif ($pricing['fively'] >= 0){
                        $v['product_price'] = $pricing['fively'];
                        $v['setup_fee'] = $pricing['fivesetupfee'];
                        $v['billingcycle'] = 'fively';
                        $v['billingcycle_zh'] = lang('FIVELY');
                    }elseif ($pricing['sixly'] >= 0){
                        $v['product_price'] = $pricing['sixly'];
                        $v['setup_fee'] = $pricing['sixsetupfee'];
                        $v['billingcycle'] = 'sixly';
                        $v['billingcycle_zh'] = lang('SIXLY');
                    }elseif ($pricing['sevenly'] >= 0){
                        $v['product_price'] = $pricing['sevenly'];
                        $v['setup_fee'] = $pricing['sevensetupfee'];
                        $v['billingcycle'] = 'sevenly';
                        $v['billingcycle_zh'] = lang('SEVENLY');
                    }elseif ($pricing['eightly'] >= 0){
                        $v['product_price'] = $pricing['eightly'];
                        $v['setup_fee'] = $pricing['eightsetupfee'];
                        $v['billingcycle'] = 'eightly';
                        $v['billingcycle_zh'] = lang('EIGHTLY');
                    }elseif ($pricing['ninely'] >= 0){
                        $v['product_price'] = $pricing['ninely'];
                        $v['setup_fee'] = $pricing['ninesetupfee'];
                        $v['billingcycle'] = 'ninely';
                        $v['billingcycle_zh'] = lang('NINELY');
                    }elseif($pricing['tenly'] >= 0){
                        $v['product_price'] = $pricing['tenly'];
                        $v['setup_fee'] = $pricing['tensetupfee'];
                        $v['billingcycle'] = 'tenly';
                        $v['billingcycle_zh'] = lang('TENLY');
                    }else{
                        $v['product_price'] = 0;
                        $v['setup_fee'] = 0;
                        $v['billingcycle'] = '';
                        $v['billingcycle_zh'] = lang('PRICE_CONFIG_ERROR');
                    }
                }else{
                    $v['product_price'] = 0;
                    $v['setup_fee'] = 0;
                    $v['billingcycle'] = '';
                    $v['billingcycle_zh'] = lang('PRICE_NO_CONFIG');
                }
            }
            # 应用默认显示年付价格
            if ($paytype['pay_type'] == 'recurring' && in_array($v['type'],array_keys(config('developer_app_product_type')))){
                if ($pricing['annually'] > 0){
                    $v['product_price'] = $pricing['annually'];
                    $v['setup_fee'] = $pricing['asetupfee'];
                    $v['billingcycle'] = 'annually';
                    $v['billingcycle_zh'] = lang('ANNUALLY');
                }
            }
            $v['product_price']=bcadd($v['setup_fee'],$v['product_price'],2);

            $cart_logic = new Cart();
            $rebate_total = 0; # 配置项享受折扣的总价格
            $config_total = $cart_logic->getProductDefaultConfigPrice($v['id'],$currencyid,$v['billingcycle'],$rebate_total);
            $rebate_total = bcadd($v['product_price'],$rebate_total,2);

            $v['product_price'] = bcadd($v['product_price'],$config_total,2);



            if ($v['api_type'] == 'zjmf_api' && $v['upstream_version'] > 0 && $v['upstream_price_type'] == 'percent'){ # 上游产品
                $v['product_price'] = bcmul($v['product_price'],$v['upstream_price_value'] / 100,2);

                if ($v['ontrial'] == 1){
                    $v['ontrial_price'] = bcmul($v['ontrial_price'],$v['upstream_price_value'] / 100,2);
                    $v['ontrial_setup_fee'] = bcmul($v['ontrial_setup_fee'],$v['upstream_price_value'] / 100,2);
                }

                $rebate_total = bcmul($rebate_total,$v['upstream_price_value'] / 100,2);
            }

            if ($v['api_type'] == 'resource'){
                $grade = resourceUserGradePercent($uid,$v['id']);
                $v['product_price'] = bcmul($v['product_price'],$grade / 100,2);

                if ($v['ontrial'] == 1){
                    $v['ontrial_price'] = bcmul($v['ontrial_price'],$grade / 100,2);
                    $v['ontrial_setup_fee'] = bcmul($v['ontrial_setup_fee'],$grade / 100,2);
                }

                $rebate_total = bcmul($rebate_total,$grade / 100,2);
            }

            $flag = getSaleProductUser($v['id'],$uid);
            $v['sale_price'] = $v['bates'] = 0;
            $v['has_bates'] = 0;
            if ($flag) {
                if ($flag['type'] == 1){
                    $bates = bcdiv($flag['bates'],100,2);
                    $rebate = bcmul($rebate_total,1-$bates,2)<0?0:bcmul($rebate_total,1-$bates,2);
                    $v['sale_price'] = bcsub($v['product_price'],$rebate,2)<0?0:bcsub($v['product_price'],$rebate,2);
                    #$v['sale_price'] = bcmul($v['product_price'],$bates,2)<0?0:bcmul($v['product_price'],$bates,2);
                    $v['bates'] = bcmul($v['product_price'],1-$bates,2);
                }elseif ($flag['type'] == 2){
                    $bates = $flag['bates'];
                    $rebate = $rebate_total<$bates?$rebate_total:$bates;
                    $v['sale_price'] = bcsub($v['product_price'],$rebate,2)<0?0:bcsub($v['product_price'],$rebate,2);
                    $v['bates'] = $bates;
                }
                $v['has_bates'] = 1;
            }
        }
        $payType =json_decode($v['pay_type'],true)['pay_type']??'';
        if ($payType=='recurring'){
            $v['pay_type'] = 'recurring_prepayment';
        }else{
            $v['pay_type'] = $payType;
        }
        $v['price'] = $v['product_price'];
        $v['cycle'] = $v['billingcycle_zh'];
        if( $v['billingcycle']==''){
            $v = [];
        }

        return json([
            'status' => 200,
            'msg' => '请求成功',
            'data' => [
                'product' => $v
            ]
        ]);
    }

    public function downloadResource(){
        $param = request()->param();

        $Provision = new Provision();
        $result = $Provision->downloadResource($param);
        return json($result);
    }

}