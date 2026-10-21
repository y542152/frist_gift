<?php

namespace App\Type\Models;

use App\Type\BaseType;

class AddAddressInputType extends BaseType
{
    public string $name;
    public string $phone;
    public int $cityId;
    public string $address;
}
