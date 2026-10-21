<?php

namespace App\Type;

/**
 * 购买礼物请求
 */

class BuyGiftInputType extends BaseType
{
    public int $kuaidiId;
    public int $cangId;
    public int $goodsId;
    public string $goodsName;
    public string $sendName;
    public string $sendMobile; // 保存到库
    public string $telephone; // api使用

    public string $sendProvince;
    public string $sendCity;
    public string $sendDistrict;
    public string $sendAddress;

    public string $storageProvince;
    public string $storageCity;
    public string $storageDistrict;
    public string $storageAddress;

    public bool $auth = false;
    public bool $isXinfeng = false;
    public int $platform;
    public string $platformName;

    public bool $wuhuoyuan = false; // 无货源订单

    public array $recList = [];
}
