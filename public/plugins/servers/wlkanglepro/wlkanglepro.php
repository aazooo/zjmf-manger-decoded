<?php

function wlkanglepro_MetaData()
{
    return ['DisplayName' => '未来kangle高级对接模块', 'APIVersion' => '1.0.3', 'HelpDoc' => 'https://blog.gd.cn/91.html'];
}
function wlkanglepro_idcsmartauthorizes()
{
}
function wlkanglepro_ConfigOptions()
{
    return [['type' => 'dropdown', 'name' => '开通方式', 'options' => ['0_自定义配置开通', '1_产品ID开通', '2_弹性配置开通'], 'description' => '弹性如何配置看帮助文档', 'key' => 'type'], ['type' => 'text', 'name' => '产品ID', 'description' => '弹性和自定义不用填', 'key' => 'product_id'], ['type' => 'dropdown', 'name' => '类型', 'options' => ['0_主机', '1_CDN'], 'description' => '弹性和自定义都要选择正确！', 'key' => 'cdn'], ['type' => 'text', 'name' => '默认绑定目录', 'description' => '默认wwwroot', 'default' => 'wwwroot', 'key' => 'subdir'], ['type' => 'text', 'name' => '空间大小', 'description' => '单位：MB', 'default' => '1024', 'key' => 'web_quota'], ['type' => 'text', 'name' => '数据库大小', 'description' => '单位：MB 输入0为不开通', 'default' => '1024', 'key' => 'db_quota'], ['type' => 'text', 'name' => '子目录数', 'description' => '输入0为不限制', 'default' => '0', 'key' => 'max_subdir'], ['type' => 'dropdown', 'name' => '绑定子目录', 'options' => ['1' => '1_允许', '0' => '0_不允许'], 'key' => 'subdir_flag'], ['type' => 'text', 'name' => '月流量限制', 'description' => '单位：GB 输入0为不限制', 'default' => '1024', 'key' => 'flow_limit'], ['type' => 'text', 'name' => '域名绑定数', 'description' => '输入-1为不限制', 'default' => '-1', 'key' => 'domain'], ['type' => 'text', 'name' => '限制带宽', 'description' => '单位：kb 输入0为不限制', 'key' => 'speed_limit'], ['type' => 'text', 'name' => '最大连接数', 'description' => '输入0为不限制', 'key' => 'max_connect'], ['type' => 'dropdown', 'name' => '是否启用自定义控制', 'options' => ['1' => '1_是', '0' => '0_否'], 'key' => 'access'], ['type' => 'dropdown', 'name' => '是否开启日志独立', 'options' => ['1' => '1_是', '0' => '0_否'], 'key' => 'log_file'], ['type' => 'dropdown', 'name' => '是否开启日志分析', 'options' => ['1' => '1_是', '0' => '0_否'], 'key' => 'log_handle'], ['type' => 'dropdown', 'name' => '是否开启ssi支持', 'options' => ['1' => '1_是', '0' => '0_否'], 'key' => 'ssi'], ['type' => 'dropdown', 'name' => '是否开启伪静态', 'options' => ['1' => '1_是', '0' => '0_否'], 'key' => 'htaccess'], ['type' => 'dropdown', 'name' => '是否开启SSL', 'options' => ['80,443s' => '是', '80' => '否'], 'key' => 'port']];
}
function wlkanglepro_CreateSign($a, $skey, $r)
{
    return md5($a . $skey . $r);
}
function wlkanglepro_GetUrl($params, $info, $skey, $r)
{
    $url = '';
    foreach ($info as $k => $v) {
        $url .= $k . '=' . $v . '&';
    }
    return 'http://' . $params['server_ip'] . ':' . $params['port'] . '/api/index.php?' . $url . 'r=' . $r . '&s=' . $skey . '&json=1';
}
function wlkanglepro_TestLink($params)
{
    $a = 'info';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $res = json_decode(file_get_contents($url), true);
    if ($res['result'] == 200) {
        $result['status'] = 200;
        $result['data']['server_status'] = 1;
    } else {
        $result['status'] = 200;
        $result['data']['server_status'] = 0;
        $result['data']['msg'] = '请检查安全码';
    }
    return $result;
}
function wlkanglepro_CreateAccount($params)
{
    $a = 'add_vh';
    $r = rand(100000, 999999);
    if ($params['configoptions']['type'] == 0) {
        $info = ['c' => 'whm', 'a' => $a, 'init' => 1, 'name' => $params['domain'], 'passwd' => $params['password'], 'cdn' => $params['configoptions']['cdn'], 'module' => 'php', 'web_quota' => $params['configoptions']['web_quota'], 'db_quota' => $params['configoptions']['db_quota'], 'db_type' => 'mysql', 'domain' => $params['configoptions']['domain'], 'subdir_flag' => $params['configoptions']['subdir_flag'], 'max_subdir' => $params['configoptions']['max_subdir'], 'flow_limit' => $params['configoptions']['flow_limit'], 'subdir' => $params['configoptions']['subdir'], 'speed_limit' => $params['configoptions']['speed_limit'], 'ftp' => '1', 'max_connect' => $params['configoptions']['max_connect'], 'access' => $params['configoptions']['access'], 'htaccess' => $params['configoptions']['htaccess'], 'log_file' => $params['configoptions']['log_file'], 'log_handle' => $params['configoptions']['log_handle'], 'ssi' => $params['configoptions']['ssi'], 'port' => $params['configoptions']['port']];
    } else {
        if ($params['configoptions']['type'] == 1) {
            $info = ['c' => 'whm', 'a' => $a, 'init' => 1, 'name' => $params['domain'], 'passwd' => $params['password'], 'product_id' => $params['configoptions']['product_id']];
        } else {
            $info = ['c' => 'whm', 'a' => $a, 'init' => 1, 'name' => $params['domain'], 'passwd' => $params['password'], 'cdn' => $params['configoptions']['cdn'], 'module' => 'php', 'web_quota' => $params['configoptions']['web_quota'], 'db_quota' => $params['configoptions']['db_quota'], 'db_type' => 'mysql', 'domain' => $params['configoptions']['domain'], 'subdir_flag' => $params['configoptions']['subdir_flag'], 'max_subdir' => $params['configoptions']['max_subdir'], 'flow_limit' => $params['configoptions']['flow_limit'], 'subdir' => $params['configoptions']['subdir'], 'speed_limit' => $params['configoptions']['speed_limit'], 'ftp' => '1', 'max_connect' => $params['configoptions']['max_connect'], 'access' => $params['configoptions']['access'], 'htaccess' => $params['configoptions']['htaccess'], 'log_file' => $params['configoptions']['log_file'], 'log_handle' => $params['configoptions']['log_handle'], 'ssi' => $params['configoptions']['ssi'], 'port' => $params['configoptions']['port']];
        }
    }
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        $update['dedicatedip'] = $params['server_ip'];
        $update['domainstatus'] = 'Active';
        $update['username'] = $params['domain'];
        $update['password'] = cmf_encrypt($params['password']);
        $update['domain'] = $params['domain'];
        think\Db::name('host')->where('id', $params['hostid'])->update($update);
        return 'success';
    }
    if ($result['result'] == 500) {
        return ['status' => 'error', 'msg' => '主机名重复'];
    }
    return ['status' => 'error', 'msg' => '未知错误'];
}
function wlkanglepro_ChangePackage($params)
{
    $a = 'add_vh';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'init' => 1, 'edit' => 1, 'name' => $params['domain'], 'passwd' => $params['password'], 'cdn' => $params['configoptions']['cdn'], 'module' => 'php', 'web_quota' => $params['configoptions']['web_quota'], 'db_quota' => $params['configoptions']['db_quota'], 'db_type' => 'mysql', 'domain' => $params['configoptions']['domain'], 'subdir_flag' => $params['configoptions']['subdir_flag'], 'max_subdir' => $params['configoptions']['max_subdir'], 'flow_limit' => $params['configoptions']['flow_limit'], 'subdir' => $params['configoptions']['subdir'], 'speed_limit' => $params['configoptions']['speed_limit'], 'ftp' => '1', 'max_connect' => $params['configoptions']['max_connect'], 'access' => $params['configoptions']['access'], 'htaccess' => $params['configoptions']['htaccess'], 'log_file' => $params['configoptions']['log_file'], 'log_handle' => $params['configoptions']['log_handle'], 'ssi' => $params['configoptions']['ssi'], 'port' => $params['configoptions']['port']];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if (isset($result['result']) && $result['result'] == 200) {
        $result['status'] = 'success';
        $result['msg'] = $result['msg'] ?: '修改配置成功';
    } else {
        $result['status'] = 'error';
        $result['msg'] = $result['msg'] ?: '修改配置失败';
    }
    return $result;
}
function wlkanglepro_SuspendAccount($params)
{
    $a = 'update_vh';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'name' => $params['domain'], 'status' => '1'];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        return 'success';
    }
    return ['status' => 'error'];
}
function wlkanglepro_UnsuspendAccount($params)
{
    $a = 'update_vh';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'name' => $params['domain'], 'status' => '0'];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        return 'success';
    }
    return ['status' => 'error'];
}
function wlkanglepro_TerminateAccount($params)
{
    $a = 'del_vh';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'name' => $params['domain']];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        return 'success';
    }
    return ['status' => 'error'];
}
function wlkanglepro_Renew($params)
{
    $res = wlkanglepro_unsuspendaccount($params);
    if ($res == 'success') {
        return ['status' => 'success', 'msg' => '续费成功'];
    }
    return ['status' => 'error', 'msg' => '续费失败'];
}
function wlkanglepro_GetHostInfo($params)
{
    $a = 'getvh';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'name' => $params['domain']];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        return $result;
    }
    return '获取失败';
}
function wlkanglepro_Status($params)
{
    $res = wlkanglepro_gethostinfo($params);
    if ($res['result'] == 200) {
        $result['status'] = 'success';
        if ($res['status'] == 0) {
            $result['data']['status'] = 'on';
            $result['data']['des'] = '运行中';
        } else {
            $result['data']['status'] = 'off';
            $result['data']['des'] = '暂停中';
        }
        return $result;
    }
    $result['data']['status'] = 'unknown';
    $result['data']['des'] = '未知';
    return $result;
}
function wlkanglepro_CrackPassword($params, $new_pass)
{
    $a = 'change_password';
    $r = rand(100000, 999999);
    $info = ['c' => 'whm', 'a' => $a, 'name' => $params['domain'], 'passwd' => $new_pass];
    $skey = wlkanglepro_createsign($a, $params['accesshash'], $r);
    $url = wlkanglepro_geturl($params, $info, $skey, $r);
    $result = json_decode(file_get_contents($url), true);
    if ($result['result'] == 200) {
        return ['status' => 'success', 'msg' => '密码重置成功'];
    }
    return ['status' => 'error', 'msg' => $result['msg'] ?: '密码重置失败'];
}
function wlkanglepro_ClientArea($params)
{
    return ['index' => ['name' => '主机信息']];
}
function wlkanglepro_ClientAreaOutput($params, $key)
{
    $result = wlkanglepro_gethostinfo($params);
    if ($key == 'index') {
        return ['template' => 'templates/information.html', 'vars' => ['params' => $params, 'info' => $result]];
    }
    if ($key == 'tips') {
        return ['template' => 'templates/tips.html', 'vars' => ['params' => $params, 'info' => $result]];
    }
}
