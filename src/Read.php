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
    
    /**
     * 读取的文件中是否设置标题
     * @var string
     */
    private $isset_title = TRUE;
    
    /**
     * 字段属性
     * @var array
     */
    private $cloumns = [];
    
    private $file = null;
    
    /**
     * 文件类型
     * @var string
     */
    private $file_type = null;
    
    const FILE_TYPE = [
        'xlsx','xls','csv'
    ];
    
    public function __construct(array $config)
    {
        // 判断文件存放路径
        if( !(isset($config['file']) && is_file($config['file']) && $this->file=$config['file']) )
        {
            throw new ExImException("文件不存在");
        }
        $info = pathinfo($this->file); 
        // 文件路径 
        $this->path = $info['dirname'];
        // 文件名称
        $this->file_name = $info['basename'];

        // 获取文件类型
        try {
            $this->file_type = \PhpOffice\PhpSpreadsheet\IOFactory::identify($this->file);
        }catch (\Throwable $e){
            throw new ExImException("无法识别的文件或文件已损坏!");
        }
        
        // 判断文件类型
        if (!in_array(strtolower($this->file_type), self::FILE_TYPE)){
            throw new ExImException("文件类型错误,目前只支持csv,xls,xlsx!");
        }
        
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
     * @param boolean $isset 文件是否存在标题
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月25日 上午11:36:18
     */
    public function isSetTitle(bool $isset=TRUE)
    {
        $this->isset_title = $isset;
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
        switch (strtolower($this->file_type)){
            case 'xls':
                $iterator = $this->readXls();
                break;
            case 'xlsx':
                $iterator = $this->readXlsx();
                break;
            case 'csv':
                $iterator = $this->readCsv();
                break;
            default:
                throw new ExImException("文件类型错误,目前只支持csv,xls,xlsx!");
        }
        
        // 获取每次读取的行数
        $size = $this->size;
        $list = [];
        foreach ($iterator as $item)
        {
            array_push($list,$item);
            if ($size-- == 1){
                $size=$this->size;
                $_list = $list;$list = [];
                yield $_list;
            }
        }
        if ($list)
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
            if ($this->isset_title){
                $this->isset_title = false;
                continue;
            }
            yield $this->parseCloumns($row) ?: $row;
        }
    }
    
    /**
     * 读取xls文件
     * @return \Generator
     * @author shijunjun
     * @date Oct 12, 2019 12:14:09 AM
     */
    private function readXls() {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xls");
        $reader->setReadDataOnly(TRUE);
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $startRow = 0;
        
        $boolean = TRUE;
        while ($boolean)
        {
            $chunkFilter->setRows($startRow,$this->size);
            $spreadsheet = $reader->load($this->file);
            
            $worker = $spreadsheet->getActiveSheet()->toArray();
           
            if ($this->isset_title || $startRow>2 ) unset($worker[0]);
            
            $data = array_slice($worker, 0-$this->size);
            if (!$data) {
                $boolean = false;
            }
            
            $startRow += $this->size;
            
            foreach ($data as $row)
            {
                if (!$res = $this->parseCloumns($row)) {
                    continue;
                }
                yield $res;
            }
        }
    }
    
    /**
     * 读取Csv文件
     * @return \Generator
     * @author shijunjun
     * @date Oct 12, 2019 3:26:56 AM
     */
    private function readCsv()
    {
        $handle = fopen($this->file, 'rb');
        while (feof($handle)===false) {
            $row = fgetcsv($handle);
            if ($this->isset_title){
                $this->isset_title = false;
                continue;
            }
            if (!$res = $this->parseCloumns($row)){
                continue;
            }
            yield $res;
        }
        fclose($handle);
    }
    
    /**
     * 解析数据
     * @param array $row
     * @throws ExImException
     * @return array
     * @author shijunjun
     * @date Oct 12, 2019 3:27:17 AM
     */
    private function parseCloumns(array $row)
    {
        $_row = [];
        
        $flag = 0;
        array_walk($row, function(&$item)use(&$flag){
            if (is_null($item)){
                $item = '';
            }
            if ($item && !is_numeric($item)){
                $encode = mb_detect_encoding($item, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
                $item = mb_convert_encoding($item, 'UTF-8', $encode);
            }
            
            if (is_numeric($item)){
                $isfloat = is_float($item);
                $decimals = $isfloat ? 3 : 0;
                $dec_point = $isfloat ? "." : '';
                $thousands_sep = "" ;
                $item = number_format($item, $decimals, $dec_point, $thousands_sep);
            }
            $flag = $item?+1:+0;
        });
        if ($flag==0){
            return $_row;
        }
        
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
        return $_row;
    }
}

/**
 * 实现IReadFilter接口
 * 
 * @author shijunjun
 */
class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;
    
    /**
     * 设置开始开始位置
     * @param int $startRow
     * @author shijunjun
     * @date Oct 12, 2019 12:10:29 AM
     */
    public function initStartRow(int $startRow=0) {
        $this->startRow = $startRow<0 ? 0 : $startRow;
    }
    
    /**
     * 设置偏移量
     * @param int $startRow 开始位置
     * @param int $chunkSize 偏移量
     * @author shijunjun
     * @date Oct 12, 2019 12:11:10 AM
     */
    public function setRows(int $startRow, int $chunkSize) {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }
    
    /**
     * Should this cell be read
     * {@inheritDoc}
     * @see \PhpOffice\PhpSpreadsheet\Reader\IReadFilter::readCell()
     */
    public function readCell($column, $row, $worksheetName = '') {
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return TRUE;
        }
        return FALSE;
    }
}