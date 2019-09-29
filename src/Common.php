<?php
namespace shijunjun\exim;
/**
 * 公共方法
 * @Date 2019年9月25日 下午12:16:04
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
trait Common {
    protected function char2number(string $str)
    {
        $len = strlen($str);
        if ($len<=0 || $len>2)
        {
            throw new ExImException("error");
        }
        
        $str = strtoupper($str);
        
        $num = function($char){
            $ascii = ord($char);
            if ($ascii>90 || $ascii<65)
            {
                throw new ExImException("error");
            }
            return $ascii-65;
        };
        
        if ($len==1){
            return $num($str);
        }
        return ($num($str[0])+1)*26+($num($str[1])+1);
    }
    
    /**
     * 将数字转化为excel表格中列名称即:A-Z
     * @param int $num 被转换的数字
     * @throws ExImException
     * @return string ascii码中的char
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月24日 上午8:38:09
     */
    protected function number2char(int $num){
        $num = $num>0?$num:($num+abs($num)+1);
        if (($n = ceil($num/26))==1){
            $chr = chr(($num>0?$num-1:$num)+65);
        }else{
            if( ($first = $n-1+64) > 90){
                throw new ExImException("超出列表最大列数!");
            }
            $chr = chr($first) . chr((($mod=$num%26)==0?26:$mod)+64);
        }
        return $chr;
    }
}