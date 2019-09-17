<?php
namespace shijunjun;
/**
 * 根据SQL语句导出指定的内容
 * @Date 2019年9月17日 下午3:31:45
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
class Export implements IExIm
{
    private $file_type = null;
    /**
     * 构造器
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月17日 下午3:33:29
     */
    public function __construct(string $file_type = self::FILE_TYPE_CSV,object $db){
        if( !($file_type = strtolower($file_type) && in_array(strtolower($file_type), [self::FILE_TYPE_CSV,self::FILE_TYPE_XLS])) ){
            throw new ExImException("不支持的文件类型");
        }
        
        
    }
    
    /**
     * 生成文件 
     * @param string $sql
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月17日 下午3:37:34
     */
    public function generateFile(string $sql){
        
    }
}