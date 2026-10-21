<?php


namespace App\Type;


use App\Supports\Code;
use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;

class Type implements ArrayAccess, Arrayable, JsonSerializable
{
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        if (!property_exists($this, $offset)) {
            throw new \Error(Code::SYSTEM_FAILED, 'Type has no attribute '. $offset);
        }
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        return;
    }

    public function toArray()
    {
        // ArticlesOrganizeType中有属性同时也是TYPE时获取toArray，type属性会是直接获取toString，改为同时toArray
        $wholeObject = get_object_vars($this);
        foreach ($wholeObject as $k => $prop) {
            if ($prop instanceof Type) {
                $wholeObject[$k] = $prop->toArray();
            }
        }

        return $wholeObject;
    }

    public function jsonSerialize()
    {
        return json_encode($this->toArray());
    }
}
