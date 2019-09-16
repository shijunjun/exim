<?php
namespace shijunjun;

class Export {
    public function test(){
        return [__FILE__,__DIR__];
    }
    
    public function init(array $param) {
        return $param;
    }
}