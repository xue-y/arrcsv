<?php
/**
 * 数组写入文件并导出
 */
namespace arrcsv;
use ZipArchive;

class WriteFile extends Pub{

    // 不对用户 开放的配置信息
    protected  $deconfig=[
        'minLimit'      => 1,       // 写入文件最少数据条数
        'maxLimit'      => 300,         // 写入文件最多数据条数
        'fileExt'        =>'.csv',  // 写入文件格式、压缩包中文件的格式 不建议更改，读取写入是按照 csv 的数据格式操作的
    ];

    // 写入文件 属性
    private $arrData;     //  数组数据
    private static $arrDataTit;  // 数据标题
    private $arrConut;    // 数组维度
    private $csvBr=PHP_EOL;  // csv 换行符
    private $arrC=0;   // 数组个数

    // 初始化类
    public function __construct($config=array())
    {
        parent:: __construct($config);
        $this->config['fileDir']=$this->mkFileDir($this->config['fileDir']);
    }

    /** arr 数组写入文件
     * @parem $arr 写入的数据
     * @parem $tit 文件tit
     * @return 直接下载文件
     * */
    public function writeFile($arr,$tit)
    {
        $this->fileName();// 编码后的文件名或默认文件名

        //判断数据是否合法---是否为数组
        if(!is_array($arr))
        {
            exit('数据不是个数组');
        }
        if(!empty($tit) && !is_array($tit))
        {
            $tit=explode($this->csvLimiter,$tit);
        }

        // 初始数据
        $this->arrData=$arr;
        self::$arrDataTit=$tit;  //如果为空取数组的下标[0]的kye ,如果传值使用数组下标[0]长度与 tit 数组长度一致

        // 判断数组维度
        $this->isArrConut();

        // 写入文件
        $file_name=$this->writeFileSub();

        // 文件名编码处理 zip EUC-CN ; csv CP936
        if(pathinfo($file_name,PATHINFO_EXTENSION)=='zip')
        {
            // 压缩文件处理中文编码
            $file_name=$this->fileNameCode($file_name);
        }
        // 判断文件是否创建成功
        $this->isFile($file_name);
    }

    /** 默认文件名处理
     * 用户不传文件名，或 iconv 处理后为空
     * @return filename
     * */
    private function deFileName()
    {
        return date('Y_m_d_his',time());
    }

    /** 判断用户是否传入文件名
     * @return void文件名的编码处理后或默认文件名
     * */
    private function fileName()
    {
        if(empty($this->config["fileName"]))
        {
            $this->config["fileName"]=$this->deFileName();
        }else
        {
            // 如果使用中文命名文件名，需要转码，如果文件名为 数字或英文不需要转码
            $this->config["fileName"]=$this->zipFileNameCode($this->config["fileName"]);
        }
    }

    /* 每个文件数据条数 */
    private function arrLimit()
    {
        $this->config['limit']=max($this->deconfig['minLimit'],$this->config['limit']);
        $this->config['limit']=min($this->deconfig['maxLimit'],$this->config['limit']);
    }

    /** 判断数组维度 赋值数组总条数
     * @return  void数组维度true一维false二维
     */
    private function isArrConut()
    {
        $this->arrC=count($this->arrData);
        if($this->arrC ==count($this->arrData,1))
        {
            $this->arrConut=true;
        }else
        {
            $this->arrConut=false;
        }
    }

    /** 数组转换csv格式
     * @return 单个文件带格式的csv数据
     * */
    private function arrCsv($arrdata=array())
    {
        if(empty($arrdata))
        {
            // 单卷文件不用传入数据，直接使用初始数据
            $file_data=$this->arrData;
        }else
        {
            // 多卷文件传入分割数组的数据
            $file_data=$arrdata;
        }

        // 一维
        if($this->arrConut==true)
        {
            $data=$this->csvBr;
            $head=chr(0xEF).chr(0xBB).chr(0xBF); // 防止 excle 打开乱码

            if(empty(self::$arrDataTit))
            {
                self::$arrDataTit=array_keys($file_data);
            }
            $head.=self::$arrDataTit[0];
            $data.=implode($this->csvBr,$file_data);
        }else
        {
            // 二维
            if(empty(self::$arrDataTit))
            {
                self::$arrDataTit=array_keys($file_data[0]);
            }
            $data='';
            $head=chr(0xEF).chr(0xBB).chr(0xBF); // 防止 excle 打开乱码
            $head.=implode($this->csvLimiter,self::$arrDataTit);
            foreach($file_data as $v)
            {
                $data.=$this->csvBr;
                $data.=implode($this->csvLimiter,$v);
            }
        }
        return   $head.$data;
    }

    /** 数组数据写入文件
     * @return  filename
     */
    private function writeFileSub()
    {
        $this->arrLimit();      // 数据条数

        if($this->arrC > $this->config['limit'])
        {
            // 创建压缩包
            $zip_file_name=$this->config['fileDir'].$this->config["fileName"].'.zip';
            $zip=new ZipArchive();
            //php 7 以下 可以使用 overwrite
            //php 7 使用 overwrite  ZipArchive::close(): Invalid or uninitialized Zip object in
            if(!$zip->open($zip_file_name,ZipArchive::CREATE))
            {
                exit("创建压缩包失败");
            }

            $data=$this->arrData; // 所有数据
            $new_c=$this->arrC;
            $page=0;
            $star=0;
            while($new_c>0)
            {
                $new_data=array_slice($data,$star,$this->config['limit']);
                $file_data=$this->arrCsv($new_data);
                $page++;
                $star=$this->config['limit']*$page;
                $new_c-=$this->config['limit'];
                // 直接将数据写入，不占用磁盘空间，同时压缩完文件后不用删除源文件 压缩文件名为gbk 编码，否则中文乱码
                $zip->addFromString($this->config["fileName"].'_'.$page.$this->deconfig["fileExt"],$file_data);
                ob_clean();
            }
            $zip->close();
            return $zip_file_name;
        }
        // 取得单个文件数据
        $file_data=$this->arrCsv();

        // 是不是压缩----- 压缩
        if($this->config["compr"]==true)
        {
            // 创建压缩包
            $zip_file_name=$this->config["fileDir"].$this->config["fileName"].'.zip';
            $zip=new ZipArchive();
            if(!$zip->open($zip_file_name,ZipArchive::CREATE))
            {
                exit("创建压缩包失败");
            }
            $new_file_name=$this->config["fileName"].$this->deconfig["fileExt"];
            $zip->addFromString($new_file_name,$file_data);
            $zip->close();
            ob_clean();
            return $zip_file_name;
        }
        // 如果不压缩--- 单个文件
        $new_file_name=$this->config["fileDir"].$this->config["fileName"].$this->deconfig["fileExt"];
        file_put_contents($new_file_name,$file_data);
        ob_clean();  //---------------------------------写入完成后，清理缓冲区
        return $new_file_name;
    }

    /** 文件写入完成判断文件是否正常并且存在直接下载
     * */
    private function isFile($filename)
    {
        if(!file_exists($filename))
        {
            exit($filename.'文件创建失败');
        }
        // 下载文件
        $this->exctDownFile($filename);
    }

    /**判断用户是否下载完成或取消下载 删除本地文件
     * @pream 需要下载的文件名 全路径
     * @return void
     * */
    private function exctDownFile($filename)
    {
        $fp=fopen($filename,"r");
        $file_ext=pathinfo($filename,PATHINFO_EXTENSION);
        header("Content-type:application/".$file_ext);

        $f_size=filesize($filename);
        header("Accept-Ranges:bytes");
        header("Accept-Length:".$f_size);

        $f_arr=explode("/",$filename);
        $new_file_name=end($f_arr);
        header("Content-Disposition:attachment;filename=".$new_file_name);
        header("Content-Transfer-Encoding:binary");

        $buffer=1024; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）
        $file_count=0; //读取的总字节数
        //向浏览器返回数据
        while(!feof($fp) && $file_count<$f_size){
            $file_con=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_con;
        }
        fclose($fp);
        //下载完成后删除压缩包，临时文件夹
        if($file_count >= $f_size)
        {
            $this->unFile($filename);
        }
    }
} 