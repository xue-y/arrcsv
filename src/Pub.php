<?php
/**
 * 公共父类
 */
namespace arrcsv;

class Pub {

    protected $config;
    protected $csvLimiter=',';    // 字段分割符

    // 初始化类
    public function __construct($config=array())
    {
        // 初始配置
        $this->config=$config;
        header("Content-type: text/html; charset=".$this->config['webChar']);
        // csv 分割符自动判断
        ini_set("auto_detect_line_endings", true);
        // 判断扩展是否开启
        $this->extend('mbstring');
        // 时区时间
        date_default_timezone_set($this->config['localTime']);
    }

    /** 判断扩展是否开启
     * */
    protected function extend($extendname)
    {
        if(!extension_loaded($extendname))
        {
            exit("请开启php $extendname 扩展");
        }
    }

    /** 文件编码处理 如果数据与文件名 为英文数字，不需要转换编码; 网页编码转为本地编码
     * @parem $filename 文件名 str or arr
     * @return filename  str or arr
     */
    protected function fileNameCode($filename)
    {
        if(!is_array($filename))
        {
            // 如果是多字节的字（中文） ，转换编码 gbk
            if(strlen($filename)!=mb_strlen($filename,$this->config['webChar']))
            {
                //$this->config['webChar']  转为  $this->config['fileNameChar'] 编码字符
                //   $filename=@iconv($this->config['webChar'],$this->config['fileNameChar'],$filename);
                $filename=mb_convert_encoding($filename,$this->config['fileNameChar'],$this->config['webChar']);
            }

            return $filename;
        }
        foreach($filename as $v)
        {
            $new_file_name[]=$this->fileNameCode($v);
        }
        return $new_file_name;
    }

    /**创建文件目录
     * @parem $filedir
     * @return 目录名称
     */
    protected function mkFileDir($filedir)
    {
        $preg="/^[a-z0-9\.\/\-\_]+$/";

        if(empty($filedir) || (!preg_match($preg,$filedir)))
        {
            exit("请传入目录名,不得有特殊字符或中文");
        }
        if(!is_dir($filedir) && !@mkdir($filedir,0777))
        {
            exit($filedir.'目录创建失败');
        }
        if(is_dir($filedir) && !is_writable($filedir))
        {
            exit($filedir.'目录不可写');
        }

        // 判断目录名是否有最后面的  /
        if(substr($filedir,-1,1)!='/')
        {
            $filedir.'/';
        }
        return $filedir;
    }

    /** 删除文件
     * @parem $filename str 单个文件 arr 多个文件
     * 失败写入日志，成功返回true
     * */
    protected function unFile($filename)
    {
        if(is_array($filename))
        {
            foreach ($filename as $v)
            {
                $this->unFile($v);
            }
        }else
        {
            if(!@unlink($filename))
            {
                $this->log($filename.'删除失败');
            }
        }
    }

    /** 警告信息写入日志
     * @parem $message 需要写入的log 日志信息
     * */
    protected function log($message)
    {
        $file_info=pathinfo($this->config["logFile"]);
        $this->mkFileDir($file_info["dirname"]);

        $type="[Notice] ";
        $data=date($this->config['logTimeFormat'],time());
        $br=PHP_EOL;
        $info=$type.$data.' [Message]：'.$message.$br;

        error_log($info,3,$this->config["logFile"]);
    }

    /** 其他编码转为本地编码（gbk）
     * */
    protected function zipFileNameCode($filename)
    {
        $encode = mb_detect_encoding($filename, array("ASCII",'EUC-CN','GB2312','GBK','BIG5','UTF-8'));
        if($encode!="ASCII") // 如果不是数字或英文 中文名转码
        {
            $filename=mb_convert_encoding($filename,$this->config['fileNameChar'],$encode);
        }
        return $filename;
    }

    /**本地编码转为网页编码 zip解压
     * */
    protected function zipFileNameUnCode($filename)
    {
        $encode = mb_detect_encoding($filename, array("ASCII",'EUC-CN','GB2312','GBK','BIG5','UTF-8'));
        if($encode!=$this->config['webChar'])
        {
            $filename=mb_convert_encoding($filename,$this->config['webChar'],$encode);
        }
        return $filename;
    }
} 