<?php
namespace shijunjun;
/**
 * 分布式 id 生成类     
 * 组成: <毫秒级时间戳+机器节点id+毫秒内自增ID序列号>
 * 默认情况下42bit的时间戳可以支持该算法使用到2109年05月15日，
 * 12bit的工作机器节点id可以支持4095台机器，
 * 序列号支持1毫秒产生1023个自增序列id
 * 
 * @Date 2019年9月20日 上午9:39:33
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
class UniqId
{
    /**
     * 62进制字典
     * @var string
     */
    const DICT62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /**
     * 开始时间,可以固定一个小于当前时间的毫秒数
     * @var integer
     */
    const EPOCH = 0;
    
    /**
     * 机器节点的二进制位数
     * @var integer
     */
    const LEN_NODE = 5;
    
    /**
     * 毫秒内自增ID的二进制位数
     * @var integer
     */
    const LEN_SEQ = 17;
    
    /**
     * 当前时间戳
     * @var integer
     */
    private static $time = null;
    
    /**
     * 毫秒内主键自增ID
     * @var integer
     */
    private static $sequence = 0;
    
    /**
     * 机器节点id 最大个数4095,也就是0-4095
     * @var integer
     */
    private static $nodeid = 0;
    
    /**
     * 设置节点ID ,最多4095个节点 [0-4095]
     * @param number $nid 节点ID
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 上午11:46:44
     */
    public static function setNodeId($nid = 0)
    {
        self::$nodeid = $nid;
    }
    
    /**
     * 获取唯一的ID
     * @param int $nodeid 节点ID
     * @return number
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 上午11:47:22
     */
    public static function getUniqId(int $nodeid=null)
    {
        // 二进制的 毫秒级时间戳
        $basetime = str_pad(decbin(self::getMicrotime()), 42,"0",STR_PAD_LEFT);
        
        // 机器id
        $nodeid = self::getNodeId($nodeid);
        
        // 序列数
        $sequence = self::getSequenceID();
        /* 
         * 拼接
         * 二进制时间戳|二进制机器节点|二进制毫秒内自增序列ID
         */
        $basetime = $basetime.$nodeid.$sequence;
        // 转化为 十进制 返回
        return bindec($basetime);
    }
    /**
     * 解析ID
     * @param number $number
     * @return array id信息 []
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午1:12:39
     */
    public static function parseId($number)
    {
        // 将ID转化为二进制
        $number_bin = decbin($number);
        
        // 解析十进制的毫秒级时间戳
        $len = strlen($number_bin)-(self::LEN_NODE+self::LEN_SEQ);
        $time = (bindec(substr($number_bin,0, $len))+self::EPOCH)/1000;
        
        // 解析节点ID 十二位
        $nodeid = bindec(substr($number_bin, $len,self::LEN_NODE));
        // 自增ID 
        $sequence = bindec(substr($number_bin, $len+self::LEN_NODE));
        return [
            // 当前ID生成的时间
            'date'  => date("Y-m-d H:i:s",$time),
            // 当前ID在毫秒内的自增ID
            'sequence'=>$sequence,
            // 当前ID生成时的时间戳
            'time'  =>$time,
            // 生成当前节点时的节点ID即服务器ID
            'nodeid'=>$nodeid,
        ];
    }
    
    /**
     * 获取62进制的唯一ID 
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午4:01:25
     */
    public static function getUniqIdTo62()
    {
        return from10to62(self::getUniqId());
    }
    
    /**
     * 将十进制数转化为62进制
     * @param number $dec 十进制数
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午1:10:04
     */
    public static function from10to62($dec) {
        $result = '';
        do {
            $result = self::DICT62[$dec % 62] . $result;
            $dec = intval($dec / 62);
        } while ($dec != 0);
        return $result;
    }
    
    /**
     * 将62进制字符串转为10进制
     * @param string $str
     * @return number
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午1:11:34
     */    
    public static function from62to10($str){
        $len = strlen($str);
        $dec = 0;
        for($i = 0;$i<$len;$i++){
            //找到对应字典的下标
            $pos = strpos(self::DICT62, $str[$i]);
            $dec += $pos*pow(62,$len-$i-1);
        }
        return $dec;
    }
    
    /**
     * 获取n位的二进制最大值
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午5:47:06
     */
    private static function getMaxVal(int $num)
    {
        return bindec(str_pad(1,$num,'1',STR_PAD_RIGHT))+1;
    }
    
    /**
     * 返回毫秒级的时间戳 
     * @return number
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 下午4:02:23
     */
    private static function getMicrotime()
    {
        // 时间戳42字节,当前时间 与 开始时间 差值
        $time = (floor(microtime(true) * 1000)) - self::EPOCH;
        if (self::$time != $time){
            self::$time = $time;
            self::$sequence = 0;
        }else{
            self::$sequence++;
        }
        return $time;
    }
    
    /**
     * 自增ID 12字节
     * @return string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 上午10:08:02
     */
    private static function getSequenceID()
    {
        return str_pad(decbin(self::$sequence), self::LEN_SEQ, "0", STR_PAD_LEFT);
    }
    
    /**
     * 生成机器id  10字节
     * @return number|string
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月20日 上午10:05:34
     */
    private static function getNodeId(int $nodeid=null){
        !is_null($nodeid) && self::$nodeid = $nodeid;
        if( !(is_numeric(self::$nodeid) && self::$nodeid>0 && self::$nodeid<=self::getMaxVal(self::LEN_NODE)) )
        {
            self::$nodeid = 0;
        }
        return str_pad(decbin(self::$nodeid), self::LEN_NODE, "0", STR_PAD_LEFT);
    }
    
}