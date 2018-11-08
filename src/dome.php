<?php
/**
 * 调用示例文件
 */
namespace arrcsv;

// 引入自动加载
include '../vendor/autoload.php';

$one = array ('a'=>'dsfsf','b'=>'中文','c'=>3,'d'=>4,'e'=>5);
$two=array(
    array("名称"=>"汉字","password"=>"123"),
    array("username"=>"test2","password"=>"456"),
    array("username"=>"test3","password"=>"789"),
    array("username"=>"test4","password"=>"111"),
    array("username"=>"test5","password"=>"222"),
    array("username"=>"test6","password"=>"222"),
    array("username"=>"test7","password"=>"999"),
);

// 修改 读写类公共 默认 配置参数
$config=[
    'webChar'       => 'UTF-8', // 文件、网页编码
    //'fileNameChar'  =>'GBK//IGNORE', // 文件名编码 支持中文 GBK//IGNORE
    'fileNameChar'  =>'GBK',
    'localTime'     => 'PRC', // 地区时间
    'fileDir'        => '../datafile/',  // 文件存在目录，文件夹名不得为中 . / 英文 数组 下划线
];

//$config 参数可以不传
//$exec=new ExecData($config);
$exec=new ExecData();

// 导出文件
/*$exec->writeData($two,null,'中文aa',1000,true);*/

// 从文件读取返回数组
$exec->fetchData("中文.zip",true,true,'中文_1.csv',false);