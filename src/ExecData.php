<?php
/**
 * arr 转csv ,csv 转 arr，调用类
 */
namespace arrcsv;

class ExecData {

    // 读写类公共配置参数
    private  $config=[
        'webChar'       => 'UTF-8', // 文件、网页编码
        //'fileNameChar'  =>'GBK//IGNORE', // 文件名编码 支持中文 GBK//IGNORE
        'fileNameChar'  =>'GBK',
        'localTime'     => 'PRC', // 地区时间
        'fileDir'       => '../datafile/',  // 文件存在目录，文件夹名不得为中文名，支持 . - / 英文 数字 下划线
    ];

    //TODO  构造函数
    public function __construct($config=array())
    {
        $this->config=array_merge($this->config,$config);
    }

    /**
     * writeData
     * @todo 数组数据导出
     * @param array $data 数组数据
     * @param bool $tit csv 文件tit,arr 或者 'aa,bb' 字符串类型,建议数组长度与数据每个二维数据长度一致
     * @param int $limit 每个文件数据条数
     * @param string  $filename 文件名，不加后缀，例如 aa,生成的文件是 aa.csv /aa.zip；默认文件名 WriteFile->defaultFileName()函数定义
     * @param bool $compr 单个文件是否压缩，默认false 不压缩
     * @return void 直接下载文件
     * */
    public function writeData($data,$tit=null,$filename='',$limit=100,$compr=false)
    {
          $w_config=[
            'limit'          => intval($limit),         // 写入数据条数
            'fileName'       => $filename,  			// 写入文件时的文件名，只要文件名，不需要后缀名
            'compr'    		 => $compr, 				// 导出的如果是单个文件是否压缩  默认不压缩
          ];
          $new_conf=array_merge($this->config,$w_config);
          $write_file=new WriteFile($new_conf);
          $write_file->writeFile($data,$tit);
    }

    /**
     * fetchData
     * @todo csv、zip 文件读取返回arr 数据
     * @param string $filename 要读取的文件名
     * @param bool $tit 是否返回文件中的tit，默认false 不返回;
     * @param bool $key 是否将 csv 文件中的tit 做为数组的 key ,默认false 返回索引数组
     * @param int/string $iden
     * int读取第几个文件的数据，默认0 读取所有文件，如果压缩文件中只有一个文件忽略此参数
     * 如果 $index=1,读取第一个文件;
     * string 要读取的文件名如果嵌套文件 请添加文件夹路径 例如 aa/aa.csv
     * @return array 返回文件数组数据
     * */
    public function fetchData($filename,$tit=false,$key=false,$iden=0)
    {
         $f_config=[
            'logFile'        => '../log/error.txt',  // 日志文件名，文件夹名不得为中文，支持 . - / 英文 数字 下划线
            'logTimeFormat'  => 'Y:m:d H:i:s',       // 日志时间格式
            'importFileMax'  => 5,           		 // 导入文件大小 5MB 5*1024*1024
            'isDelFile'      =>false, 				 // 如果读取的是所有文件是否删除源文件
            'logOut'         =>true  				 // 是否输出错误日志，默认输出
         ];
        $new_conf=array_merge($this->config,$f_config);
        $fetch_file=new FetchFile($new_conf);
        return $fetch_file->fetchFile($filename,$tit,$key,$iden);
    }
} 