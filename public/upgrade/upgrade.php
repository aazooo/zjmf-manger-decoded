<?php
$database = include "../../app/config/database.php";
$host = $database['hostname'];
$dbname = $database['database'];
$prefix = $database['prefix']??"shd_";
$user = $database['username'];
$pass = $database['password'];
$defaultCharset = 'utf8mb4';
$charset = $database['charset'];
$defaultTablePre = 'shd_';
$port = $database['hostport'];
try{
    $opts_values = array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8');
    $dbObject = new PDO("mysql:host={$host};port={$port};dbname={$dbname}",$user,$pass,$opts_values);
}catch (PDOException $e){
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
$res = $dbObject->query('select*from ' . $prefix . "configuration where setting='update_last_version'")->fetchAll(PDO::FETCH_ASSOC);
$version = $res[0]['value'];
if(empty($version)){
	echo "Error!: 'update_last_version' not found<br/>";
    exit;
}
# 内测版
$system_version_type = $dbObject->query('select*from ' . $prefix . "configuration where setting='system_version_type'")->fetchAll(PDO::FETCH_ASSOC);
if ($system_version_type[0]['value'] && $system_version_type[0]['value'] == 'beta'){
    $beta_version =  $dbObject->query('select*from ' . $prefix . "configuration where setting='beta_version'")->fetchAll(PDO::FETCH_ASSOC);
    $version = $beta_version[0]['value']??$version;
}
if(empty($version)){
	echo "Error!: 'version' not found<br/>";
    exit;
}
$handle = fopen('upgrade.log', 'r');
$content = '';
while(!feof($handle)){
    $content .= fread($handle, 8080);
}
fclose($handle);
$arr = explode("\n",$content);
//过滤空值
$fun = function ($value){
    if (empty($value)){
        return false;
    }else{
        return true;
    }
};
$arr = array_filter($arr,$fun);
$arr_last_pop = array_pop($arr);
$arr_last = explode(',',$arr_last_pop);
//获取最新记录
$arr[] = $arr_last_pop;
$last_version = $arr_last[1];
if (version_compare($last_version,$version,'>')){
    foreach ($arr as $v){
        $v = explode(',',$v);
        $sql_version = $v[1];
        $sql_file = $v[1] . '.sql';
        if (version_compare($sql_version,$version,'>')){
            if (file_exists($sql_file)){
                //读取SQL文件
                $sql = file_get_contents($sql_file);
                $sql = str_replace("\r", "\n", $sql);
                $sql = str_replace("BEGIN;\n", '', $sql);//兼容 navicat 导出的 insert 语句
                $sql = str_replace("COMMIT;\n", '', $sql);//兼容 navicat 导出的 insert 语句
                $sql = str_replace($defaultCharset, $charset, $sql);
                $sql = trim($sql);
                //替换表前缀
                $sql  = str_replace(" `{$defaultTablePre}", " `{$prefix}", $sql);
                $sqls = explode(";\n", $sql);
                foreach ($sqls as $sql){
                    try{
                        $dbObject->query($sql);
                    }catch (PDOException $e){
                        echo "升级出错,错误sql:" . $sql . ";错误信息:".$e->getMessage();die;
                    }
                }
            }
        }
    }
}
if ($system_version_type[0]['value'] && $system_version_type[0]['value'] == 'beta'){ # 内测版
    $update_sql_beta = "update " . $prefix . "configuration set value='{$last_version}' where setting = 'beta_version'";
    $dbObject->query($update_sql_beta);
}
$update_sql = "update " . $prefix . "configuration set value='{$last_version}' where setting = 'update_last_version'";
$res = $dbObject->query($update_sql);
$executed_update = "update " . $prefix . "configuration set value=1 where setting = 'executed_update'";
$res = $dbObject->query($executed_update);
// 升级成功,注销登录
session_start();
$_SESSION = [];
if(isset($_COOKIE[session_name()])){
    setcookie(session_name(),'',time()-3600,'/');
}
session_destroy();
echo "恭喜你，升级完成\n系统升级已完成，请删除public/upgrade目录";

