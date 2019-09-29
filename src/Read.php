<?php
namespace shijunjun\exim;

/**
 * 读取Excel表格文件
 * @Date 2019年9月25日 上午10:49:42
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
class Read implements IExIm
{
    use Common;
    /**
     * 每页每次读取行数
     * @var integer
     */
    private $size = 0;
    /**
     * 文件所在的路径
     * @var string
     */
    private $path = null;
    /**
     * 文件名称
     * @var string
     */
    private $file_name = null;
    
    private $del_title = true;
    
    private $cloumns = [];
    
    public function __construct(array $config)
    {
        // 判断文件存放路径
        if( !(isset($config['file']) && is_file($config['file']) && $file=$config['file']) )
        {
            throw new ExImException("文件不存在");
        }
        $info = pathinfo($file); 
        // 文件路径 
        $this->path = $info['dirname'];
        // 文件名称
        $this->file_name = $info['basename'];
        
        
        // 要读取的列与之对应的字段名称
        if (isset($config['cloumns']) && is_array($config['cloumns']) && $cloumns=$config['cloumns']){
            foreach ($cloumns as $key=>$item){
                $this->cloumns[$this->char2number($key)] = $item;   
            }
            ksort($this->cloumns);
        }
        // 设置每次循环读取的行数
        if (!(isset($config['size']) && ($size=$config['size'])>0 && $this->size=$size))
        {
            $this->size = self::LIMIT;
        }
        
    }
    
    /**
     * 删除表格标题 
     * @param boolean $del
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月25日 上午11:36:18
     */
    public function delTitle(bool $del=true)
    {
        $this->del_title = $del;
        return $this;
    }
    
    /**
     * 设置每次循环显示的行数 
     * @param int $size 行数
     * @return \shijunjun\exim\Read
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月25日 上午11:02:13
     */
    public function setSize(int $size)
    {
        $size>0 && $this->size = $size;
        return $this;
    }
    
    /**
     * 返回数据列表 ,此方法需用迭代器遍历
     * @return \Generator
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月25日 上午11:15:53
     */
    public function list()
    {
        // 获取每次读取的行数
        $size = $this->size;
        $list = [];
        foreach ($this->readXlsx() as $item)
        {
            array_push($list,$item);
            if ($size-- == 1){
                $size=$this->size;
                $_list = $list;$list = [];
                yield $_list;
            }
        }
        yield $list;
    }
    
    /**
     * 读取excel表格文件 
     * @return \Generator
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月25日 上午11:16:41
     */
    private function readXlsx()
    {
        // 打开文件并且读取文件
        $excel  = new \Vtiful\Kernel\Excel(['path' => $this->path]);
        $excel->openFile($this->file_name)->openSheet();
        while ($row = $excel->nextRow()) {
            if ($this->del_title){
                $this->del_title = false;
                continue;
            }
            $_row = [];
            if($this->cloumns){
                // 根据指定的列返回相应的数据
                foreach ($this->cloumns as $key=>$item)
                {
                    if (!isset($row[$key])){
                        throw new ExImException("给定的列名称与表格文件的列不匹配");
                    }
                    $_row[$item] = $row[$key];
                }
            }else{
                
                foreach ($row as $key=>$item)
                {
                    $_row[$this->number2char($key+1)] = $item;    
                }
            }
            yield $_row ?: $row;
        }
    }
}