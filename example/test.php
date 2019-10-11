<?php
/**
 * 
 * @author shijunjun
 * @email jun_5197@163.com
 * @date 2019年10月11日 下午3:12:34
 */
require_once __DIR__ . '/../vendor/autoload.php';
$startTime = microtime(true);
$mem = memory_get_usage();
$inputFileName = __DIR__ . '/file/test.csv';
$inputFileName = __DIR__ . '/file/test.xls';
//$inputFileName = __DIR__ . '/file/test.xlsx';


$read = (new \shijunjun\exim\Read([
    'file'=> $inputFileName, // 要导入的文件
    'size'=>2, // 每次获取读取行数
    'cloumns'=>[
        'A'=>'id' ,// 'ID',
        'B'=>'band_id' ,// '品牌ID',
        'C'=>'sku_uuid' ,// '商品规格ID',
        'D'=>'category_id' ,// '后端分类ID',
        'E'=>'create_at' ,// '添加时间',
        'F'=>'goods_sku_code' ,// 'SKU编码',
        'G'=>'goods_uuid' ,// '商品ID',
        'H'=>'img_url' ,// '封面图片',
        'I'=>'invoice_ord' ,// '普通发票',
        'J'=>'invoice_spe' ,// '增值税专票',
        'K'=>'invoice_spe_ord' ,// '增值税普票',
        'L'=>'is_default' ,// '是否默认展示',
        'M'=>'limit' ,// '最大购买量',
        'N'=>'price_in' ,// '进货价',
        'O'=>'sku_name' ,// '商品规格名称',
        'P'=>'spec_name' ,// '属性值拼接(,拼接)',
        'Q'=>'special_code' ,// '商品69码',
        'R'=>'status' ,// '状态'
        'S'=>'supplier_uuid' ,// '供应商ID',
        'T'=>'update_at' ,// '更新时间',
        'U'=>'volume' ,// '体积',
        'V'=>'weight' ,// '重量',
        'W'=>'zone' ,// '专区',
    ],
]));
// 文件是否有标题如果有标题那么请设置true(默认值,可以省略),否则为false
$read->isSetTitle(false); 

foreach ($read->list() as $item)
{
    var_export( $item );
}

echo PHP_EOL . "占用内存:". ((memory_get_usage()-$mem)/1024/1024) . "M,执行时间:".(microtime(true)-$startTime).'秒' . PHP_EOL;
