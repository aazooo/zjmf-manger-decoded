<?php

function bthosts_idcsmartauthorizes()
{
}
function bthosts_MetaData()
{
    return ['DisplayName' => 'btHost对接模块', 'APIVersion' => '1.7.1', 'HelpDoc' => 'http://blog.hengsuyun.com/index.php/archives/15/'];
}
function bthosts_ConfigOptions()
{
    return [['type' => 'dropdown', 'name' => '开通方式', 'description' => '', 'options' => ['自定义配置', '套餐开通', '弹性开通'], 'key' => 'type'], ['type' => 'text', 'name' => '套餐ID', 'description' => '弹性和自定义开通请留空', 'key' => 'plans_id'], ['type' => 'text', 'name' => '分类ID', 'description' => '默认为1', 'default' => '1', 'key' => 'sort_id'], ['type' => 'text', 'name' => '站点端口', 'description' => '默认为80', 'default' => '80', 'key' => 'port'], ['type' => 'text', 'name' => '域名绑定数', 'description' => '输入0为不限制', 'key' => 'domain_num'], ['type' => 'text', 'name' => '网站备份数', 'description' => '输入0为不限制', 'key' => 'web_back_num'], ['type' => 'text', 'name' => '数据库备份数', 'description' => '输入0为不限制', 'key' => 'sql_back_num'], ['type' => 'text', 'name' => '域名池ID', 'description' => '不懂请勿修改', 'default' => '1', 'key' => 'domainpools_id'], ['type' => 'text', 'name' => 'IP池ID', 'description' => '不懂请留空', 'key' => 'ippools_id'], ['type' => 'text', 'name' => '赠送IP数', 'description' => '不懂请留空', 'key' => 'ip_num'], ['type' => 'text', 'name' => '默认PHP版本', 'description' => '格式：56，72，不懂请留空', 'key' => 'phpver'], ['type' => 'text', 'name' => '并发数', 'description' => '输入0为不限制', 'key' => 'perserver'], ['type' => 'text', 'name' => '限制网速(KB)', 'description' => '输入0为不限制', 'key' => 'limit_rate'], ['type' => 'text', 'name' => '站点大小(MB)', 'description' => '输入0为不限制', 'key' => 'site_max'], ['type' => 'text', 'name' => '数据库大小(MB)', 'description' => '输入0为不限制', 'key' => 'sql_max'], ['type' => 'text', 'name' => '月流量(MB)', 'description' => '输入0为不限制', 'key' => 'flow_max'], ['type' => 'text', 'name' => '并发数', 'description' => '输入0为不限制', 'key' => 'perserver'], ['type' => 'text', 'name' => '限制网速(KB)', 'description' => '输入0为不限制', 'key' => 'limit_rate'], ['type' => 'dropdown', 'name' => '绑定子目录', 'description' => '此为高危操作，不建议开启', 'options' => ['不允许', '允许'], 'key' => 'sub_bind']];
}
function bthosts_CreateSign($time, $random, $token)
{
    $data['time'] = $time;
    $data['random'] = $random;
    $data['token'] = $token;
    sort($data, SORT_STRING);
    $str = implode($data);
    $signature = md5($str);
    return strtoupper($signature);
}
function bthosts_GetUrl($params, $path = '/api/vhost/index', $query = [])
{
    $url = '';
    if ($params['secure']) {
        $url = 'https://';
    } else {
        $url = 'http://';
    }
    $url .= $params['server_ip'] ?: $params['server_host'];
    if (!empty($params['port'])) {
        $url .= ':' . $params['port'];
    }
    $url .= $path;
    $q = '';
    foreach ($query as $k => $v) {
        $q .= '&' . $k . '=' . $v;
    }
    if (!empty($q)) {
        $url = $url . '?' . ltrim($q, '&');
    }
    return $url;
}
function bthosts_Get($url = '')
{
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}
function bthosts_Post($url, $post_data = [])
{
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    if (empty($url) || empty($post_data)) {
        return false;
    }
    $o = '';
    foreach ($post_data as $k => $v) {
        $o .= $k . '=' . urlencode($v) . '&';
    }
    $post_data = substr($o, 0, -1);
    $postUrl = $url;
    $curlPost = $post_data;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function bthosts_GetHostid($params)
{
    return (int) $params['customfields']['host_id'];
}
function bthosts_HostInfo($params, $hostid)
{
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_info');
    $arr = json_decode(bthosts_post($url, $datas), true);
    $info = $arr['data'];
    if ($arr['data']['status'] == 'normal') {
        $info['status'] = '<button class="btn btn-block btn-success btn-sm"><i class="bx bx-loader-circle bx-spin"></i> 运行中</button>';
    } else {
        if ($arr['data']['status'] == 'stop') {
            $info['status'] = '<button class="btn btn-block btn-warning btn-sm"><i class="bx bx-lock-alt"></i> 关闭</button>';
        } else {
            if ($arr['data']['status'] == 'locked') {
                $info['status'] = '<button class="btn btn-block btn-warning btn-sm"><i class="bx bx-power-off"></i> 暂停中</button>';
            } else {
                if ($arr['data']['status'] == 'expired') {
                    $info['status'] = '<button class="btn btn-block btn-dark btn-sm"><i class="bx bx-error-alt"></i> 过期</button>';
                } else {
                    if ($arr['data']['status'] == 'excess') {
                        $info['status'] = '<button class="btn btn-block btn-dark btn-sm"><i class="bx bx-line-chart"></i> 超量</button>';
                    } else {
                        $info['status'] = '<button class="btn btn-block btn-danger btn-sm"><i class="bx bx-error-alt"></i> 异常</button>';
                    }
                }
            }
        }
    }
    return $info;
}
function bthosts_TestLink($params)
{
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $url = bthosts_geturl($params, '/api/vhost/index', $datas);
    $res = json_decode(bthosts_get($url), true);
    if ($res['code'] == 1) {
        $result['status'] = 200;
        $result['data']['server_status'] = 1;
    } else {
        $result['status'] = 200;
        $result['data']['server_status'] = 0;
        $result['data']['msg'] = $res['msg'];
    }
    return $result;
}
function bthosts_CreateAccount($params)
{
    $hostid = bthosts_gethostid($params);
    if (!empty($hostid)) {
        return '已开通,不能重复开通';
    }
    if (empty($params['password'])) {
        $sys_pwd = randStr(8);
    } else {
        $sys_pwd = $params['password'];
    }
    $info['time'] = time();
    $info['random'] = mt_rand();
    $info['token'] = $params['accesshash'];
    $infos = $info;
    unset($infos['token']);
    $infos['signature'] = bthosts_createsign($info['time'], $info['random'], $info['token']);
    $infos['username'] = $params['domain'];
    $infos['password'] = $sys_pwd;
    $url = bthosts_geturl($params, '/api/vhost/user_create');
    $arr = json_decode(bthosts_post($url, $infos), true);
    if ($arr['code'] !== 1) {
        return $arr['msg'];
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    if ($params['configoptions']['type'] == 1) {
        $datas['plans_id'] = $params['configoptions']['plans_id'];
    } else {
        if ($params['configoptions']['type'] == 0) {
            $datas['pack[ftp]'] = 1;
            $datas['pack[domain_audit]'] = 0;
            $datas['pack[session]'] = 0;
            $datas['pack[sql]'] = 'MySQL';
            $datas['pack[port]'] = $params['configoptions']['port'];
            $datas['pack[domain_num]'] = $params['configoptions']['domain_num'];
            $datas['pack[web_back_num]'] = $params['configoptions']['web_back_num'];
            $datas['pack[sql_back_num]'] = $params['configoptions']['sql_back_num'];
            $datas['pack[domainpools_id]'] = $params['configoptions']['domainpools_id'];
            $datas['pack[ippools_id]'] = $params['configoptions']['ippools_id'];
            $datas['pack[ip_num]'] = $params['configoptions']['ip_num'];
            $datas['pack[phpver]'] = $params['configoptions']['phpver'];
            $datas['pack[perserver]'] = $params['configoptions']['perserver'];
            $datas['pack[limit_rate]'] = $params['configoptions']['limit_rate'];
            $datas['pack[site_max]'] = $params['configoptions']['site_max'];
            $datas['pack[sql_max]'] = $params['configoptions']['sql_max'];
            $datas['pack[flow_max]'] = $params['configoptions']['flow_max'];
            $datas['pack[sub_bind]'] = $params['configoptions']['sub_bind'];
        } else {
            if ($params['configoptions']['type'] == 2) {
                $datas['pack[ftp]'] = 1;
                $datas['pack[domain_audit]'] = 0;
                $datas['pack[session]'] = 0;
                $datas['pack[sql]'] = 'MySQL';
                $datas['pack[port]'] = $params['configoptions']['port'];
                $datas['pack[domain_num]'] = $params['configoptions']['domain_num'];
                $datas['pack[web_back_num]'] = $params['configoptions']['web_back_num'];
                $datas['pack[sql_back_num]'] = $params['configoptions']['sql_back_num'];
                $datas['pack[domainpools_id]'] = $params['configoptions']['domainpools_id'];
                $datas['pack[ippools_id]'] = $params['configoptions']['ippools_id'];
                $datas['pack[ip_num]'] = $params['configoptions']['ip_num'];
                $datas['pack[phpver]'] = $params['configoptions']['phpver'];
                $datas['pack[perserver]'] = $params['configoptions']['perserver'];
                $datas['pack[limit_rate]'] = $params['configoptions']['limit_rate'];
                $datas['pack[site_max]'] = $params['configoptions']['site_max'];
                $datas['pack[sql_max]'] = $params['configoptions']['sql_max'];
                $datas['pack[flow_max]'] = $params['configoptions']['flow_max'];
                $datas['pack[sub_bind]'] = $params['configoptions']['sub_bind'];
            }
        }
    }
    $datas['endtime'] = date('Y-m-d', $params['nextduedate']);
    $datas['user_id'] = $arr['data']['id'];
    $datas['sort_id'] = $params['configoptions']['sort_id'];
    $url = bthosts_geturl($params, '/api/vhost/host_build');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        $customid = think\Db::name('customfields')->where('type', 'product')->where('relid', $params['productid'])->where('fieldname', 'host_id')->value('id');
        if (empty($customid)) {
            $customfields = ['type' => 'product', 'relid' => $params['productid'], 'fieldname' => 'host_id', 'fieldtype' => 'text', 'adminonly' => 1, 'create_time' => time()];
            $customid = think\Db::name('customfields')->insertGetId($customfields);
        }
        $exist = think\Db::name('customfieldsvalues')->where('fieldid', $customid)->where('relid', $params['hostid'])->find();
        if (empty($exist)) {
            $data = ['fieldid' => $customid, 'relid' => $params['hostid'], 'value' => $res['data']['site']['id'], 'create_time' => time()];
            think\Db::name('customfieldsvalues')->insert($data);
        } else {
            think\Db::name('customfieldsvalues')->where('id', $exist['id'])->update(['value' => $res['data']['site']['id']]);
        }
        $mainip = $params['server_ip'];
        $update['dedicatedip'] = $mainip;
        $update['domainstatus'] = 'Active';
        $update['username'] = $arr['data']['username'];
        $update['password'] = cmf_encrypt($sys_pwd);
        $update['domain'] = $arr['data']['username'];
        $update['bwlimit'] = (int) $datas['pack[flow_max]'];
        think\Db::name('host')->where('id', $params['hostid'])->update($update);
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_Speed($params, $perserver = 300, $limit_rate = 512)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $datas['perserver'] = $perserver;
    $datas['limit_rate'] = $limit_rate;
    $url = bthosts_geturl($params, '/api/vhost/host_speed');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_UnSpeed($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_speedoff');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_ChangePackage($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $post_data = $data;
    unset($post_data['token']);
    $post_data['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $post_data['id'] = $hostid;
    if ($params['configoptions']['type'] == 1) {
        $post_data['plan_id'] = $params['configoptions']['plans_id'];
        $url = bthosts_geturl($params, '/api/vhost/host_update');
        $res = json_decode(bthosts_post($url, $post_data), true);
    } else {
        if (isset($params['configoptions_upgrade']['site_max'])) {
            $post_data['site_max'] = $params['configoptions']['site_max'];
        }
        if (isset($params['configoptions_upgrade']['sql_max'])) {
            $post_data['sql_max'] = $params['configoptions']['sql_max'];
        }
        if (isset($params['configoptions_upgrade']['domain_num'])) {
            $post_data['domain_max'] = $params['configoptions']['domain_num'];
        }
        if (isset($params['configoptions_upgrade']['web_back_num'])) {
            $post_data['web_back_num'] = $params['configoptions']['web_back_num'];
        }
        if (isset($params['configoptions_upgrade']['flow_max'])) {
            $post_data['flow_max'] = $params['configoptions']['flow_max'];
        }
        if (isset($params['configoptions_upgrade']['sql_back_num'])) {
            $post_data['sql_back_num'] = $params['configoptions']['sql_back_num'];
        }
        if (isset($params['configoptions_upgrade']['sub_bind'])) {
            $post_data['sub_bind'] = $params['configoptions']['sub_bind'];
        }
        if ($params['configoptions']['perserver'] == 0 || $params['configoptions']['limit_rate'] == 0) {
            bthosts_unspeed($params);
        } else {
            bthosts_speed($params, $params['configoptions']['perserver'], $params['configoptions']['limit_rate']);
        }
        if (!empty($post_data)) {
            $url = bthosts_geturl($params, '/api/vhost/host_edit');
            $res = json_decode(bthosts_post($url, $post_data), true);
        }
    }
    if ($res['code'] == 1) {
        $result['status'] = 'success';
        $result['msg'] = $res['msg'] ?: '修改配置成功';
    } else {
        $result['status'] = 'error';
        $result['msg'] = $res['msg'] ?: '修改配置失败';
    }
    return $result;
}
function bthosts_SuspendAccount($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_locked');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_UnsuspendAccount($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_start');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_TerminateAccount($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_recycle');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return 'success';
    }
    return ['status' => 'error', 'msg' => $res['msg']];
}
function bthosts_Sync($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_sync');
    $res = json_decode(bthosts_post($url, $datas), true);
    $info = bthosts_hostinfo($params, $hostid);
    $datas['id'] = $info['user_id'];
    $url2 = bthosts_geturl($params, '/api/vhost/user_info');
    $result = json_decode(bthosts_post($url2, $datas), true);
    if ($res['code'] == 1 && $result['code'] == 1) {
        $update['domain'] = $result['data']['username'];
        $update['username'] = $result['data']['username'];
        $update['password'] = cmf_encrypt($result['data']['password']);
        think\Db::name('host')->where('id', $params['hostid'])->update($update);
        return ['status' => 'success', 'msg' => $res['msg']];
    }
    return ['status' => 'error', 'msg' => $res['msg'] ?: $result['msg'] ?: '同步失败'];
}
function bthosts_Status($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_status');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        $result['status'] = 'success';
        if ($res['data']['loca'] == 'normal') {
            $result['data']['status'] = 'on';
            $result['data']['des'] = '运行中';
        } else {
            if ($res['data']['loca'] == 'locked') {
                $result['data']['status'] = 'off';
                $result['data']['des'] = '暂停';
            } else {
                if ($res['data']['loca'] == 'expired') {
                    $result['data']['status'] = 'waiting';
                    $result['data']['des'] = '过期';
                } else {
                    if ($res['data']['loca'] == 'excess') {
                        $result['data']['status'] = 'off';
                        $result['data']['des'] = '超量';
                    } else {
                        if ($res['data']['loca'] == 'stop') {
                            $result['data']['status'] = 'off';
                            $result['data']['des'] = '关闭';
                        } else {
                            $result['data']['status'] = 'unknown';
                            $result['data']['des'] = '异常';
                        }
                    }
                }
            }
        }
        return $result;
    }
    $result['data']['status'] = 'unknown';
    $result['data']['des'] = '未知';
    return $result;
}
function bthosts_CrackPassword($params, $new_pass)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $datas['type'] = 'host';
    $datas['password'] = $new_pass;
    $url = bthosts_geturl($params, '/api/vhost/host_pass');
    $res = json_decode(bthosts_post($url, $datas), true);
    if (isset($res['code']) && $res['code'] == 1) {
        return ['status' => 'success', 'msg' => '密码重置成功'];
    }
    return ['status' => 'error', 'msg' => $res['msg'] ?: '密码重置失败'];
}
function bthosts_Recovery($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_recovery');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return ['status' => 'success', 'msg' => '恢复成功'];
    }
    return ['status' => 'error', 'msg' => $res['msg'] ?: '恢复失败'];
}
function bthosts_Renew($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    bthosts_recovery($params);
    bthosts_unsuspendaccount($params);
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $datas['endtime'] = date('Y-m-d', $params['nextduedate']);
    $url = bthosts_geturl($params, '/api/vhost/host_endtime');
    $res = json_decode(bthosts_post($url, $datas), true);
    if ($res['code'] == 1) {
        return ['status' => 'success', 'msg' => '续费成功'];
    }
    return ['status' => 'success', 'msg' => '续费失败：' . $res['msg']];
}
function bthosts_Resource($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_resource');
    $res = json_decode(bthosts_post($url, $datas), true);
    if (isset($res['code']) && $res['code'] == 1) {
        return ['status' => 'success', 'msg' => '请求成功'];
    }
    return ['status' => 'error', 'msg' => $res['msg'] ?: '请求失败'];
}
function bthosts_Stop($params)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '宝塔主机ID错误';
    }
    $data['time'] = time();
    $data['random'] = mt_rand();
    $data['token'] = $params['accesshash'];
    $datas = $data;
    unset($datas['token']);
    $datas['signature'] = bthosts_createsign($data['time'], $data['random'], $data['token']);
    $datas['id'] = $hostid;
    $url = bthosts_geturl($params, '/api/vhost/host_stop');
    $res = json_decode(bthosts_post($url, $datas), true);
    if (isset($res['code']) && $res['code'] == 1) {
        return ['status' => 'success', 'msg' => '主机停用成功'];
    }
    return ['status' => 'error', 'msg' => $res['msg'] ?: '请求失败'];
}
function bthosts_ClientArea($params)
{
    return ['index' => ['name' => '主机信息'], 'status' => ['name' => '使用情况']];
}
function bthosts_ClientAreaOutput($params, $key)
{
    $hostid = bthosts_gethostid($params);
    if (empty($hostid)) {
        return '';
    }
    $info = bthosts_hostinfo($params, $hostid);
    if ($key == 'index') {
        return ['template' => 'templates/information.html', 'vars' => ['params' => $params, 'info' => $info]];
    }
    if ($key == 'status') {
        return ['template' => 'templates/status.html', 'vars' => ['params' => $params, 'info' => $info]];
    }
}
function bthosts_AllowFunction()
{
    return ['client' => ['Sync'], 'admin' => ['Resource']];
}
function bthosts_AdminButton($params)
{
    $button = ['Resource' => '资源稽核', 'Stop' => '主机停用', 'UnsuspendAccount' => '主机开启', 'TerminateAccount' => '放入回收站', 'Recovery' => '从回收站恢复', 'UnSpeed' => '解除限速'];
    return $button;
}
function bthosts_ClientButton($params)
{
    $button = ['Sync' => ['place' => 'console', 'name' => '主机同步']];
    return $button;
}

