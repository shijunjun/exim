# exim
基于[xlswriter](https://github.com/viest/php-ext-excel-export)扩展的导出导入,目前只支持xlsx文件的导入和导出

### 用法
```bash
composer require shijunjun/exim
```

### export(导出)
```php
$export = new \shijunjun\exim\Export();

$config = [
    // 导出的文件存放位置	
    'path' => __DIR__.'/xls',
    // 非必需,导出的文件名
    'filename'=> date('YmdHis'),
    // 导出的字段
    'columns'=>[
        'id'=>'ID',
        'band_id'=>'品牌ID',
        'sku_uuid'=>'商品规格ID',
        'category_id'=>'后端分类ID',
        'create_at'=>'添加时间',
        'goods_sku_code'=>'SKU编码',
        'goods_uuid'=>'商品ID',
        'img_url'=>'封面图片',
        'invoice_ord'=>'普通发票', //  0 不支持 1 支持
        'invoice_spe'=>'增值税专票',// 0 不支持 1 支持
        'invoice_spe_ord'=>'增值税普票', //  0 不支持 1 支持
        'is_default'=>'是否默认展示', //  1-是 2-否
        'limit'=>'最大购买量',
        'price_in'=>'进货价',
        'sku_name'=>'商品规格名称',
        'spec_name'=>'属性值拼接(,拼接)',
        'special_code'=>'商品69码',
        'status'=>'状态', // 1-正常 2-禁用 3-伪删除
        'supplier_uuid'=>'供应商ID',
        'update_at'=>'更新时间',
        'volume'=>'体积',
        'weight'=>'重量',
        'zone'=>'专区', //   1会员专区 2购物券专区 3大众好货专区
    ],
    // MySQL链接信息
    'mysql'=>['host'=>"192.168.56.190",'user'=>"root",'password'=>"MySQL123#","dbname"=>"tests"],
    // 执行sql
    'sql' => "select 
                *,
                (case `invoice_ord` when 0 then '不支持' else '支持' end) as `invoice_ord`,
                (case `invoice_spe` when 0 then '不支持' else '支持' end) as `invoice_spe`,
                (case `invoice_spe_ord` when 0 then '不支持' else '支持' end) as `invoice_spe_ord`,
                (case `status` when 1 then '正常' when 2 then '禁用'  else '删除' end) as `status`,
                (case `zone` when 1 then '会员专区' when 2 then '购物券专区'  else '大众好货专区' end) as `zone`
              from goods_sku",
];

$out = $export->config($config)->setLimit(3000)->setMaxLine(100000)->openZip()->output();

// 等价于

$export->config($config);		// 设置导出数据基本必需参数
$export->setLimit(3000);		// 设置每次查询条数
$export->setMaxLine(100000);		// 如果设置此项则会对导出文件进行分割,默认为0表示不分割,大于0表示分割并且每个excel文件的最大行数
$export->openZip();			// 开启压缩 boolean
$out = $export->output();		// 输出文件

// ----------------------------------------------------------
var_export($out);
array (
  // excel文件列表	
  'files' => array (),
  // 压缩后的文件全路径名称
  'zip_name' => '/data/www/io/app/xls/20190924191710.zip',
  // 执行时间
  'time' => '262.03791904449秒',
  // 占用内存
  'memory' => '0.09M',
)

```

### import(导入)
```php
$read = (new \shijunjun\exim\Read([
    'file'=> $file, // 要导入的文件
    'size'=>1000, // 每次获取读取行数
    'cloumn'=>[
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
]))->delTitle(false); // 显示标题

foreach ($read->list() as $item)
{
    var_export( $item );
}

// delTitle:是否删除标题 默认:true(是) 

```