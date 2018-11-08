<?php
/**
 * 读取csv/zip文件返回arr 数组
 */

namespace arrcsv;
use SplFileObject;
use ZipArchive;

class FetchFile extends Pub{

    // 导入文件格式
    private $mimeType=[
        'csv' => 'text/plain',
        'zip' => 'application/zip'
    ];

    protected  $config=[
        'isDelFile'=>false, // 如果读取的是所有文件是否删除源文件
    ];

    private $dataTit;
    private $dataKey;
    private $dataIden;
    private $dataFetchType; // 读取文件的方式 索引还是文件名
    private $log_error=false;
    private $fetchFileAll=false; // 如果读取的是所有文件

    // 初始化类
    public function __construct($config=array())
    {
        $config=array_merge($this->config,$config);
        parent:: __construct($config);
    }


    /** 读取文件
     * @parem $filename 要读取的文件名
     * @parem bool 是否返回文件中的tit，默认false 不返回;
     * @parem bool 是否将 csv 文件中的tit 做为数组的 key ,默认false 返回索引数组
     * @parem int/string
     * int读取第几个文件的数据，默认0 读取所有文件，如果压缩文件中只有一个文件忽略此参数
     * 如果 $index=1,读取第一个文件;
     * string 要读取的文件名如果嵌套文件 请添加文件夹路径 例如 aa/aa.csv,$fileway 必须为true
     * @parem bool 读取压缩包中文件的方式 默认索引,索引方式读取ture,文件名读取文件false ,此参数仅对压缩文件起作用
     * @return array返回文件数据
     * */
    public function fetchFile($filename,$tit,$key,$iden,$fileway)
    {
        $this->isFileDir();// 判断文件存在目录

        // 拼接文件名称 文件编码统一
        $filename=$this->config['fileDir'].$this->fileNameCode($filename);

        // 判断文件是否存在
        if(!file_exists($filename))
        {
            // 输出的文件名转换编码 -- 与页面一致
            $filename=$this->outFileName($filename);
            exit($filename.'文件不存在');
        }

        // 文件大小
        if(filesize($filename)> $this->config['importFileMax']*1024*1024)
        {
            exit('文件过大，建议文件小于5MB,也可修改 importFileMax 属性（根据自己的内存设定）');
        }

        // 判断文件格式
        $file_ext=$this->fileType($filename);

        $this->dataTit=$tit;
        $this->dataKey=$key;

        if($file_ext=='zip')
        {
            $this->dataIden=$iden;
            $this->dataFetchType=$fileway;
        }else
        {
            $this->fetchFileAll=true;
        }

        //设置/获取内部字符编码
        mb_internal_encoding($this->config["webChar"]);
        // 设置区域 -- fgetcsv 读取中文数据读取不到
        setlocale(LC_ALL, 'US'); //setlocale( LC_ALL, "chs" )

        // 调用函数
        $fn_name=$file_ext.'Arr';
        $arr=$this->$fn_name($filename);

        // 输出日志信息
        $this->outLog($filename);

        // 输出数组数据
        echo "<pre>";
        var_dump($arr);
    }

    /** 判断目录是否存在
     * @parem $dirname
     * @reurn void 不是直接退出程序
     * */
    private function isFileDir()
    {
        $preg="/^[a-z0-9\.\/\-\_]+$/";

        if(!preg_match($preg,$this->config['fileDir']))
        {
            exit("请传入目录名,不得有特殊字符或中文");
        }
        if(!is_dir($this->config['fileDir']))
        {
           exit($this->config['fileDir']."目录不存在，请将要导入的文件放在".$this->config['fileDir'].'目录下');
        }
    }

    /** 输出带路径的文件名：文件名转为与网页一样的格式
     * 如果是英文或数字此步骤可以忽略
     * */
    private function outFileName($filename)
    {
        if(strlen($filename)!=mb_strlen($filename,$this->config["webChar"]))
        {
            $fn_arr=explode('/',$filename);
            $fn=array_pop($fn_arr);

            $fn=mb_convert_encoding($fn,$this->config["webChar"],$this->config["fileNameChar"]);
            //$fn=iconv($this->config["fileNameChar"],$this->config["webChar"],$fn);

            $filename=empty($fn_arr)?$fn:implode('/',$fn_arr).'/'.$fn;
        }
        return $filename;
    }

    /** 导入文件 -- 判断文件格式
     * @parem $filename
     * @return file_ext
     * */
    private function fileType($filename)
    {
        // 判断扩展
        $this->extend('fileinfo');

        $handle=finfo_open(FILEINFO_MIME_TYPE);//This function opens a magic database and returns its resource.
        $fileInfo=finfo_file($handle,$filename);// Return information about a file
        finfo_close($handle);

        $old_file_ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        if(!isset($this->mimeType[$old_file_ext]) || ($this->mimeType[$old_file_ext]!=$fileInfo))
        {
            $filename=$this->outFileName($filename);
            $file_ext=implode('|',array_keys($this->mimeType));
            exit($filename."不是标准的".$file_ext.'文件');
        }
        return $old_file_ext;
    }

    /** 单个 csv 文件返回arr 数组
     * @parem  $filename csv 文件名
     * @return csv文件data arr
     * */
    private function csvArr($filename)
    {
        $file = new SplFileObject($filename);
        $tit=$file->fgetcsv();
        $file->fseek(count($tit[0])-1,SEEK_CUR);
        $arr=[];
        // 一维
        if(count($tit)==1)
        {
            while (!$file->eof()) {
              $temp_arr=$file->fgetcsv($this->csvLimiter);

              // 如果值为空 跳出
             if(empty($temp_arr))
                   continue;

              if($this->dataKey==true)  // 如果保留key,原来的一维数组会变成二维数组
              {
                  $temp_arr2[$tit[0]]=$temp_arr[0];
              }else
              {
                  $temp_arr2=$temp_arr[0];
              }
                array_push($arr,$temp_arr2);
            }
        }else
        {
            //二维数组
            while (!$file->eof()) {
                $temp_arr=$file->fgetcsv($this->csvLimiter);

                // 如果值为空 跳出
                if(empty($temp_arr))
                    continue;

                if($this->dataKey==true)
                {
                    foreach($temp_arr as $k=>$v)
                    {
                        $temp_arr2[$tit[$k]]=$v;
                    }
                    $temp_arr=$temp_arr2;
                }
                $arr[]=$temp_arr;
            }
        }
        // 返回数据
        return $this->returnArr($arr,$tit);
    }


    /** zip 文件嵌套文件夹(建议目录不要过深，只测试过二层，多层未测试) 读取数据 返回 arr
     * */
    private function zipArr($filename)
    {
        // 实例化 zip
        $zip=new ZipArchive();

        // 中文文件名编码处理
        $filename=$this->zipFileNameUnCode($filename);

        // 打开压缩文件
        if(!$zip->open($filename))
        {
            exit("打开压缩".$filename."文件失败");
        }
        $all_arr=[];

        // 文件读取方式  默认索引方式
        if($this->dataFetchType==true)
        {
            $f_index=max(0,intval($this->dataIden));
            // 文件个数
            $file_num=$zip->numFiles;
            if($file_num<1)
            {
                exit('压缩包中没有文件');
            }

            if($file_num==1)  // 如果压缩包中只有一个文件
            {
                $this->fetchFileAll=true;  // 标识读取所有文件
                $zip_file_name=$zip->getNameIndex(0);
                $all_arr=$this->zipFileOne($zip,$zip_file_name);
            }
            else if($f_index!==0 && $f_index<=$file_num) //// 输出指定文件
            {
                $f_index-=1; // 文件下标从0 开始
                // 按文件索引 找的文件名
                $zip_file_name=$zip->getNameIndex($f_index);
                $all_arr=$this->zipFileOne($zip,$zip_file_name);
            } else
            {
                $this->fetchFileAll=true;  // 标识读取所有文件
                for($i=0;$i<$file_num;$i++)
                {
                    $zip_file_name=$zip->getNameIndex($i);
                    $all_arr[]=$this->zipFileOne($zip,$zip_file_name);
                }
            }
        }else
        {
            // 文件名读取
            $all_arr=$this->zipFileOne($zip,$this->dataIden);
        }

        $zip->close(); // 关闭压缩文件
        // 多少个有内容的文件，就返回几个数组,如果都为空 没有文件或文件中没有数据
        if(!empty($all_arr))
        {
            return array_filter($all_arr);
        }
        return $all_arr;
    }

    /** 读取压缩文件夹中单个文件
     * @parem $zip 压缩资源实例
     * @parem $zip_file_name 压缩文件名
     * @return 压缩文件数组数据
     * */
    private function zipFileOne($zip,$zip_file_name)
    {
        // 如果文件名为中文名  ["crc"] 这个值有误
        $file_info=$zip->statName($zip_file_name);

        // 因为文件名编码问题 读取不到信息， 压缩时压缩包的文件夹名为中文 $this->config['fileNameChar']，调用当前函数传参字符是 $this->config["webChar"]
        if(empty($file_info))
        {
            $zip_file_name=mb_convert_encoding($zip_file_name,$this->config["fileNameChar"],$this->config["webChar"]);
            $file_info=$zip->statName($zip_file_name);
            if(empty($file_info))
            {
                $this->log_error=true;
                // 输出前统一编码 utf8
                $temp_file_name=$this->outFileName($zip_file_name);
                $this->log("压缩包中不存在 $temp_file_name 文件 或 传入的文件名编码与压缩包中的文件编码不一致无法读取文件");
                return;
            }
        }
        if($file_info["size"]<1)
        {
           $this->log_error=true;
           $this->log($zip_file_name."没有数据");
           return;
        }

        // 读取文件流 资源
        $fp = $zip->getStream($zip_file_name);

        if(!$fp)
        {
            ob_clean();// 清除缓冲区
            exit($zip_file_name."读取文件失败");
        }

        $tit=fgetcsv($fp);
        fseek($fp,count($tit[0])-1,SEEK_CUR); // 文件指针定义数据位置

        $arr=[];
        // 判断数组维度
        if(count($tit)==1)
        {
            while (!feof($fp)) {
                $temp_arr=fgetcsv($fp,0,$this->csvLimiter);

                // 如果值为空 跳出
                if(empty($temp_arr))
                    continue;

                if($this->dataKey==true)  // 如果保留key,原来的一维数组会变成二维数组
                {
                    $temp_arr2[$tit[0]]=$temp_arr[0];
                }else
                {
                    $temp_arr2=$temp_arr[0];
                }
                array_push($arr,$temp_arr2);
            }
        }else
        {
            // 二维
            while (!feof($fp)) {
                $temp_arr=fgetcsv($fp,0,$this->csvLimiter);

                // 如果值为空 跳出
                if(empty($temp_arr))
                    continue;

                if($this->dataKey==true)
                {
                    foreach($temp_arr as $k=>$v)
                    {
                        $temp_arr2[$tit[$k]]=$v;
                    }
                    $temp_arr=$temp_arr2;
                }
                $arr[]=$temp_arr;
            }
        }
       // 返回数据
       return $this->returnArr($arr,$tit);
    }

    /** 返回从文件中读取的数据
     * @parem $data
     * @parem $tit
     * @return 组合的所有数据
     * */
    private function returnArr($data,$tit)
    {
        // 如果返回csv 文件中的tit ,返回数据 arr['data']数据，$arr['tit']标题
        // 如果不返回tit ,直接返回文件中的数据
        if($this->dataTit==true)
        {
            $arr_all['data']=$data;
            $arr_all['tit']=$tit;
        }else
        {
            $arr_all=$data;
        }
        return $arr_all;
    }

    /** 判断是否有错误
     * */
    private function outLog($filename='')
    {
        // 如果出现错误
        if($this->log_error==true)
        {
            echo '读取文件时有错误，请查看日志信息';
        }else if($this->fetchFileAll==true && $this->config["isDelFile"]==true)   // 如果读取所有文件  并设置删除源文件
        {
            // 如果没有错误，删除源文件
             $this->unFile($filename);
        }
    }

} 