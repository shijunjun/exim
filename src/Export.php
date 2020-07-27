<?php
namespace shijunjun\exim;

/**
 * 根据SQL语句导出指定的内容
 * @Date 2019年9月17日 下午3:31:45
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
class Export extends DB
{
    use Common;
    /**
     * 要导出数据的属性
     *  [
     *      // array | 必需 |  要导出的字段以及文件标题对应信息
     *      'header'    =>[
     *          'title'=>['A'=>'ID','B'=>'手机号码','C'=>'姓名'],
     *          'column'=>['A'=>'id','B'=>'mobile','C'=>'name']
     *      ],
     *      // string | 必需 | sql语句
     *      'sql'       => ""       
     *      // string | 非必需 | 导出的文件类型 EXPORT::FILE_TYPE_CSV->csv ,EXPORT::FILE_TYPE_EXCEL->xlsx
     *  ]
     * @var array
     */
    private $_config = [];
    
    /**
     * excel文件列表
     * @var array
     */
    protected $filename = [];
    
    /**
     * 表格标题以及与之对应的字段列表
     * [
     *  'title' =>['A'=>'姓名']
     *  'cloumn'=>['A'=>'name']
     * ]
     * @var array
     */
    protected $header = [];
    
    /**
     * 当前脚本开始执行时间
     * @var integer
     */
    protected $beginTime = 0;
    
    /**
     * 当前脚本开始执行时分配给PHP的内存量
     * @var integer
     */
    protected $beginMem = 0;
    
    /**
     * 页码
     * @var integer
     */
    protected $page = 1;
    
    /**
     * 每次查询的条数
     * @var integer
     */
    protected $limit = 1000;
    
    /**
     * 开启压缩
     * @var string
     */
    protected $openZip = false;
    
    protected $zipName = null;
    
    protected $max_line = 0;
    
    public function __construct(){
        $extension = ['zip','xlswriter'];
        foreach ($extension as $item){
            if (!extension_loaded($item))
            {
                throw new ExImException("请先安装{$item}扩展!");
            }
        }
        $this->beginTime = microtime(true);
        $this->beginMem = memory_get_usage();
    }
    
    /**
     * 设置导出相关属性 
     * @param array $attributes
     *  [
     *      // string | 非必需 | 导出文件存放的路径
     *      'path'      => "",
     *      // string | 非必需 | 导出文件名称
     *      'filename'  => "",
     *      // array | 必需 |  要导出的字段以及文件标题对应信息
     *      'columns'    =>[
     *          id'=>'ID',
     *          'band_id'=>'品牌ID',
     *          'sku_uuid'=>'商品规格ID',
     *      ],
     *      'mysql'  => [ 
     *              'host'      , // 服务器地址 必需
     *              'user'      , // 用户名 必需
     *              'password'  , // 密码 必需
     *              'dbname'    , // 数据库实例名称 必需
     *              'charset'   , // 字符集 非必需 默认:utf8mb4
     *              'port'      , // 端口号 非必需 默认:3306
     *          ]
     *      // string | 必需 | sql语句
     *      'sql'       => "" 
     *  ]
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午3:18:58
     */
    public function config(array $config)
    {
        $this->_config = $config;
        
        // 设置MySQL链接
        if (!isset($config['mysql'])){
            throw new ExImException("请设置MySQL数据库的链接信息");
        }
        $this->settings = $config['mysql'];
        $this->connect();
        
        // 解析字段
        $this->parseColumns();
        
        return $this;
    }
    
    /**
     * 设置每次查询的数量
     * @param int $limit 查询数量
     * @return \shijunjun\exim\Export
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:51:49
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit>0 ? $limit : self::LIMIT;
        return $this;
    }
    
    /**
     * 开启zip压缩打包 
     * @param bool $open 是否压缩 true:压缩 false:不压缩
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:47:56
     */
    public function openZip(bool $open=true)
    {
        $this->openZip = $open;
        return $this;
    }
    
    /**
     * 设置单个Excel文件的最大行数 ,默认为0表示不分割
     * @param int $line 最大行数
     * @return \shijunjun\exim\Export
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:45:28
     */
    public function setMaxLine(int $line=0)
    {
        if ($line>0 && $line>$this->limit){
            $this->max_line = ceil($line/$this->limit)*$this->limit;
        }
        return $this;
    }
    
    /**
     * 返回导出成功后的文件名,如果开启压缩则filename为空
     * @return [] 返回excel文件名|压缩后的文件名
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:25:40
     */
    public function output()
    {
        $this->export();
        if ($this->openZip) $this->zipFile();
        return [
            'files'=>$this->filename,
            'zip_name' => $this->zipName,
            'time' => (microtime(true)-$this->beginTime) . '秒',
            'memory'=> sprintf("%.2f" ,(memory_get_usage()-$this->beginMem)/1024/1024) . 'M',
        ];
    }
    
    /**
     * 压缩文件 
     * @return string 返回压缩后的文件名的全路径
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:23:17
     */
    protected function zipFile()
    {
        // header("Cache-Control: max-age=0");
        // header("Content-Description: File Transfer");
        // header('Content-disposition: attachment; filename=' . $download_file_name . '.zip'); // 文件名
        //进行多个文件压缩
        $zip = new \ZipArchive();
        $storageDir = $this->getPath();
        $this->zipName = $storageDir . "/" . $this->getFileName(false) . ".zip";
        $zip->open($this->zipName, \ZipArchive::CREATE);   //打开压缩包
        foreach ($this->filename as $file){
            $zip->addFile($file,'csv/'.basename($file));   //向压缩包中添加文件
        }
        $zip->close(); // 关闭
        @array_map('unlink', $this->filename);
        $this->filename = [];
        return $this->zipName;
    }
    
    /**
     * 输出文件到指定目录 
     * @return string 文件名称全路径
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午12:03:24
     */
    protected function export()
    {
        // 设置表格标题
        $title = array_values($this->header['title']);
        // excel配置文件
        $config = ['path' => $this->getPath()];
        
        while (true){
            // 获取导出文件名称 fileName 会自动创建一个工作表，可以自定义该工作表名称，工作表名称为可选参数
            $excel  = new \Vtiful\Kernel\Excel($config);
            $filename = $this->getFileName();
            // 设置表格标题
            $sheetname = isset($this->_config['sheetname']) ? $this->_config['sheetname'] : 'sheet1';
            $excel->fileName($filename,$sheetname)->header($title);
            // 获取数据
            $list = $this->select();
            if (!$list->current()) break;
            foreach ($list as $item)
            {
                $excel->data($item);
                
                // 此处是分割文件的关键点
                if ($this->max_line>0 && $this->page*$this->limit % $this->max_line === 0){
                    break;
                }
            }
            // 将生成的文件名称全路径存放到一个数组中
            $this->filename[] = $excel->output();
        }
        
        return $this->filename;
    }
    
    /**
     * 获取数据列表 
     * @return \Generator
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午4:35:44
     */
    protected function select()
    {
        // 设置表格标题
        $header = $this->header;
        
        // 每次最大查询量
        $limit = $this->limit;
        
        while (true){
            // 设置偏移量
            $offset = ($this->page++-1)*$limit;
            //if ($offset>2000000) break;
            // 使用迭代器查询数据
            $list = $this->yield($this->getSql($offset));
            // 判断当前查询是否有数据,此处最关键,不能省略,不然会进入到死循环状态
            if (!$list->current()) break;
            
            // 初始化结果集变量
            $res = [];
            foreach ($list as $key=>$item){
                $tmp = [];
                // 聚合要导出的字段
                array_walk($header['column'], function($filed) use($key,$item,&$tmp){
                    isset($item[$filed]) && $tmp[$filed] = $item[$filed];
                });
                $res[] = array_values($tmp);
            }
            yield $res;            
        }
    }
    
    /**
     * 设置header属性
     * [
     *  'title'=>['A'=>'ID','B'=>'手机号码','C'=>'姓名']
     *  'column'=>['A'=>'id','B'=>'mobile','C'=>'name']
     * ]
     * @throws ExImException
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午5:01:42
     */
    protected function parseColumns()
    {
        if ($this->header && is_array($this->header['title']) && is_array($this->header['column']) && $this->header['column'] && $this->header['title']){
            return $this->header;
        }
        
        if( !(isset($this->_config['columns']) && $columns = $this->_config['columns']) )
        {
            throw new ExImException("请先设置要导出的字段名称以及与之对应的表格标题,如['name'=>'姓名','age'=>'年龄']");
        }
        
        $title = $column = [];
        $start = 1;
        foreach ($columns as $key=>$val){
            $chr = $this->number2char($start++);
            $title["{$chr}"] = $val;
            $column["{$chr}"] = $key;
        }
        return $this->header = ['title'=>$title,'column'=>$column];
    }
    
    /**
     * 获取要执行的SQL语句,并自动加上分页
     * @param number $offset SQl语句查询条件limit的起始位置
     * @throws ExImException
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 上午8:46:30
     */
    protected function getSql($offset=0)
    {
        if (isset($this->_config['sql']) && $sql = $this->_config['sql']){
            // 查找limit
            $limit = $this->limit;
            $pos = stripos($this->_config['sql'],"limit");
            
            return ($pos===false ? $sql : substr($sql, 0,$pos)) . " limit {$limit} offset {$offset}";
        }
        throw new ExImException("缺少要执行SQL语句!");
    }
    
    /**
     * 获取要导出的文件名 
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月21日 下午4:02:05
     */
    protected function getFileName(bool $ext=true)
    {
        if( !(isset($this->_config['filename']) && $this->_config['filename'])){
            $this->_config['filename'] = \shijunjun\uniqid\Id::getUniqIdTo62();
        }
        $filename = $this->_config['filename'];
        if ($ext){
            static $ext = 1;
            $filename .=  "_" . str_pad($ext++, 4,0,STR_PAD_LEFT) . '.xlsx';
        }
        return  $filename;
    }
    
    /**
     * 文件存放位置 
     * @throws ExImException
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 下午6:28:06
     */
    protected function getPath(){
        try {
            if (!isset($this->_config['path'])){
                throw new ExImException("请设置导出文件存放路径[path]!");
            }
            !file_exists($this->_config['path']) && mkdir($this->_config['path'],0777,true);
        }catch (\Throwable $e){
            throw new ExImException($e->getMessage());
        }
        return $this->_config['path'];
    }
}