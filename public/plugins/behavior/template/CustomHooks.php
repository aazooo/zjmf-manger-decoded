<?php
use think\Db;
/*
 *  @author wyh
 *  @time 2021-01-13
 *  @description 客户自定义实现钩子,可以在application或template或者新建目录下建任意文件,并调用hook_add()方法
 *  application 应用钩子; template 模板钩子,可引入css,js
 *
 *  hook_add($tag,$fun)
 *  @param  tag 钩子标签
 *  @param  fun 匿名函数
 *  @return mixed
 */
// 例子 记录日志
hook_add('shd_template_hook_test',function ($param){
    $id = $param['id'];
    return Db::name('clients')->where('id',$id)->find();
});