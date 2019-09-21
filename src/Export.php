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
     *      // int | 非必需 | 每页显示的条数        
     *      'limit'     => EXPORT::LIMIT, 
     *      // string | 非必需 | 导出的文件类型 EXPORT::FILE_TYPE_CSV->csv ,EXPORT::FILE_TYPE_EXCEL->xlsx
     *      'file_type' => EXPORT::FILE_TYPE_CSV,
     *  ]
     * @var array
     */
    private $_attributes = [];
    
    private $_sql = null;
    
    /**
     * 设置数据库连接属性
     * @param array $config 数据配置参数
     *  [ 
     *      'host'      , // 服务器地址 必需
     *      'user'      , // 用户名 必需
     *      'password'  , // 密码 必需
     *      'dbname'    , // 数据库实例名称 必需
     *      'charset'   , // 字符集 非必需 默认:utf8mb4
     *      'port'      , // 端口号 非必需 默认:3306
     *  ]
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午12:07:14
     */
    public function setDbConfig(array $config)
    {
        $this->settings = $config;
        $this->connect();
    }
    
    /**
     * 设置导出相关属性 
     * @param array $attributes
     *  [
     *      // array | 必需 |  要导出的字段以及文件标题对应信息
     *      'header'    =>[
     *          'title'=>['A'=>'ID','B'=>'手机号码','C'=>'姓名'],
     *          'column'=>['A'=>'id','B'=>'mobile','C'=>'name']
     *      ],
     *      // string | 必需 | sql语句
     *      'sql'       => ""       
     *      // int | 非必需 | 每页显示的条数        
     *      'limit'     => EXPORT::LIMIT, 
     *      // string | 非必需 | 导出的文件类型 EXPORT::FILE_TYPE_CSV->csv ,EXPORT::FILE_TYPE_EXCEL->xlsx
     *      'file_type' => EXPORT::FILE_TYPE_CSV,
     *  ]
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午3:18:58
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }
    
    /**
     * 求总数
     * @param string $sql
     * @return array|mixed
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午1:07:11
     */
    public function getTotal(string $sql,string $key=null)
    {
        $row = $this->row($sql);
        return $key && isset($row[$key]) ? $row[$key] : $row;
    }
    
    /**
     * 生成文件 
     * @param string $sql
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月17日 下午3:37:34
     */
    public function generateFile(){
        set_time_limit(0);
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');
        while (true){
            $res = $this->yieldList($sql);
            // 判断是否有数据
            if (!$res->current()){
                break;
            }
            // 设置CSV头部
            !$this->_header && fputcsv($fp, array_keys($res->current()));
            foreach ($res as $row){
                $this->_id = $row['id'];
                fputcsv($fp, $row);
            }
            // 刷新一下缓存区
            ob_flush(); flush();
        }
        // 关闭打开的文件句柄
        fclose($fp);
    }
    
    public function yieldList(string $sql)
    {
        $flag = true;
        $limit = $this->getLimit();
        $offset = 0;
        $i=0;
        while ($flag){
            $_sql = $sql . " limit {$limit} offset {$offset}";
            echo $_sql . PHP_EOL;
            if (++$i>10)break;
            $offset+=$limit;
        }
    }
    
    /**
     * 设置header属性
     * [
     *  'title'=>['A'=>'ID','B'=>'手机号码','C'=>'姓名']
     *  'column'=>['A'=>'id','B'=>'mobile','C'=>'name']
     * ]
     * 
     * @throws ExImException
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午5:01:42
     */
    protected function getHeader()
    {
        if (isset($this->_attributes['header']) && is_array($this->_attributes['header']) && isset($this->_attributes['header']['title']) && isset($this->_attributes['header']['column'])){
            return $this->_attributes['header'];
        }
        throw new ExImException("请正确设置header属性");
    }
    
    /**
     * 获取每页查询数 
     * @return number
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午3:24:22
     */
    protected function getLimit()
    {
        return isset($this->_attributes['limit']) && is_numeric($this->_attributes['limit']) && $this->_attributes['limit']>0 ? (int)$this->_attributes['limit'] : self::LIMIT; 
    }
    
    /**
     * 返回要导出的文件类型,目前只支持csv以及excel,即文件后缀名 
     * @return boolean|string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午3:29:15
     */
    protected function getFileType()
    {
        if (isset($this->_attributes['file_type']) && $type=$this->_attributes['file_type'] && in_array($type, [self::FILE_TYPE_CSV,self::FILE_TYPE_EXCEL]))
        {
            return $type;
        }
        return self::FILE_TYPE_CSV;
    }
    
    /**
     * 获取要执行的SQL语句 
     * @throws ExImException
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月19日 下午5:15:29
     */
    protected function getSql()
    {
        if (isset($this->_attributes['sql'])){
            return $this->_attributes['sql'];
        }
        throw new ExImException("缺少SQL语句!");
    }
    
    /**
     * 获取要导出的文件名 
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月21日 下午4:02:05
     */
    protected function getExportFileName()
    {
        if( !(isset($this->_attributes['export_file_name']) && $this->_attributes['export_file_name'])){
            $this->_attributes['export_file_name'] = \shijunjun\uniqid\Id::getUniqIdTo62();
        }
        return $this->_attributes['export_file_name'] . '.' . $this->getFileType();
    }
}