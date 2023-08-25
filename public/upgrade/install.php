<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>升级</title>
    <link rel="stylesheet" type="text/css" href="css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="css/htmleaf-demo.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="plugin/css/ladda-themeless.min.css">
    <link rel="stylesheet" href="plugin/css/prism.css">
    <!--[if IE]>
    <script src="http://libs.useso.com/js/html5shiv/3.7/html5shiv.min.js"></script>
    <![endif]-->
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .main_box {
            width: 500px;
            height: 200px;
            background-color: #fff;
            box-shadow: 0px 4px 24px rgb(230, 230, 230);
            color: #333;
            box-sizing: border-box;
            padding: 35px;
            text-align: center;
            border-radius: 15px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            height: 40px;
            border: 1px solid rgb(69, 92, 107);
            border-radius: 5px;
            margin: 25px auto 0;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn:hover {
            box-shadow: 0px 4px 20px #ddd;
            transition: all 0.3s;
        }
        /* 覆盖默认样式 */
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary.active,
        .open .dropdown-toggle.btn-primary,
        .btn-primary[disabled] {
            color: #000;
            background-color: #fff;
            border-color: rgb(69, 92, 107);
        }
    </style>
</head>

<body>
<div class="htmleaf-container">
    <header class="htmleaf-header">
        <?php
        $database = include "../../app/config/database.php";
        $host = $database['hostname'];
        $dbname = $database['database'];
        $prefix = $database['prefix'];
        $user = $database['username'];
        $pass = $database['password'];
        $defaultCharset = 'utf8mb4';
        $charset = $database['charset'];
        $port = $database['hostport'];
        $admin_application = $database['admin_application']??'admin';
        $defaultTablePre = 'shd_';
        try{
            $dbObject = new PDO("mysql:host={$host};port={$port};dbname={$dbname}",$user,$pass);
        }catch (PDOException $e){
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        $res = $dbObject->query('select*from ' . $prefix . "configuration where setting='update_last_version'")->fetchAll(PDO::FETCH_ASSOC);
        $version = $res[0]['value']??'1.0.0';

        # 内测版
        $system_version_type = $dbObject->query('select*from ' . $prefix . "configuration where setting='system_version_type'")->fetchAll(PDO::FETCH_ASSOC);
        if ($system_version_type[0]['value'] && $system_version_type[0]['value'] == 'beta'){
            $beta_version =  $dbObject->query('select*from ' . $prefix . "configuration where setting='beta_version'")->fetchAll(PDO::FETCH_ASSOC);
            $version = $beta_version[0]['value']??$version;
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
        $arr_last = explode(',',array_pop($arr));
        //获取最新记录
        $last_version = $arr_last[1];
        if (version_compare($last_version,$version,'>')){ //进入升级
            echo "<div class=\"main_box\">
				升级前，请自行备份好数据库，请确认备份无误后继续
				<button class=\"btn btn-primary ladda-button\" data-style=\"expand-left\" data-spinner-color=\"#999\" id='upgrade'>
          <span class=\"ladda-label\">立即升级</span>
        </button>
			</div>";
        }else{ //已完成更新
            echo "<div class=\"main_box\">
            您已经升级完成<br/>
            请勿重复访问，请删除public/upgrade访问本页面
            <a href= '../". $admin_application . "'><div class=\"btn\">我已删除，进入后台</div>
            </a>
        </div>";
        }
        ?>
    </header>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery.gradientify.min.js"></script>
<script src="plugin/js/spin.min.js"></script>
<script src="plugin/js/ladda.min.js"></script>
<script>
    $(document).ready(function () {
        $("body").gradientify({
            gradients: [{
                start: [49, 76, 172],
                stop: [242, 159, 191]
            },
                {
                    start: [255, 103, 69],
                    stop: [240, 154, 241]
                },
                {
                    start: [33, 229, 241],
                    stop: [235, 236, 117]
                }
            ]
        });
    });
</script>
<script>
    $("#upgrade").click(function (e) {
        e.preventDefault();
        var btnloading = Ladda.create(this);
        btnloading.start();

        $.get("upgrade.php", function (data, status) {
            btnloading.stop();
            alert(data);
            location.reload();
        });
    });
</script>
</body>

</html>
