<?php


namespace App\Type;


class BaseType extends Type
{
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $newKey = '';
            // 如果key不存在则将下划线重组为驼峰
            if (strpos($key, '_') !== false && !$this->offsetExists($key)) {
                foreach (explode('_', $key) as $k => $v) {
                    if($k != 0) {
                        $newKey .= ucfirst($v);
                    } else{
                        $newKey .= $v;
                    }
                }
            }

            if ($newKey) {
                $key = $newKey;
            }

            // 给已有的属性赋值
            if ($this->offsetExists($key) && !is_null($value)) {
                $this->offsetSet($key, $value);
            }
        }
    }
}
