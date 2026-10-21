<?php


namespace App\Http\Controllers;

use App\Services\AddressService;
use App\Services\GiftApiService;
use App\Type\Models\AddAddressInputType;

class Address extends BaseController
{
    public function index(): array
    {
        try {
            parent::_init();
            $options = (new AddressService())->fetchPlaceOptions();
            return $this->returnData($options);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function add(): array
    {
        $this->paramRules = [
            'name'      =>  'required|string',
            'phone'      =>  'required|int',
            'cityId'      =>  'required|int',
            'address'      =>  'required|string',
        ];

        try {
            parent::_init();

            $inputType = new AddAddressInputType($this->requestData);
            (new AddressService())->addAddress($this->getUid(), $inputType);
            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function userAddress(): array
    {
        $this->paramRules = [
            'status'      =>  'int|in:0,1',
        ];

        try {
            parent::_init();
            $list = (new AddressService())->fetchUserAddressList($this->getUid(), $this->requestData['status'] ?? -1);
            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function parseAddress(): array
    {
        $this->paramRules = [
            'addressList'      =>  'required|array',
        ];

        try {
            parent::_init();

            $addressList = $this->requestData['addressList'];

            foreach ($addressList as $key => $address) {
                $address = trim($address);

                if($address != '') {
                    $address = $this->getJdaddress($address);
                    // 通用的地址整理
                    $address = $this->parse_common_address($address);
                }

                $addressList[$key] = $address;
            }

            return $this->returnData($addressList);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function setStatus(int $id, int $status): array
    {
        try {
            parent::_init();

            (new AddressService())->setStatus($this->getUid(), $id, $status);

            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    protected function getJdaddress($str)
    {
        $r_arr1 = array('省','市','县','区','镇','街道','州','旗','盟','管委会','生态园');

        for($i = 0 ; $i < count($r_arr1) ; $i++ ){
            if($r_arr1[$i] == '州'){
                $p = "州(?!市|省|区|县)";
            }else if($r_arr1[$i] == '镇'){
                $p = "镇(?!县|市|镇|康县|巴县|坪县|平县|安县|原县|海区|江市|远县|雄县|赉县|宁布|沅彝)";
            }else if($r_arr1[$i] == '市'){
                $p = "市(?!区|中区|南区|北区)";
            }else if($r_arr1[$i] == '区'){
                $p = "区(?!街道)";
            }else if($r_arr1[$i] == '旗'){
                $p = "旗(?!区|县)";
            }else if($r_arr1[$i] == '县'){
                $p = "县(?!市)";
            }else if($r_arr1[$i] == '省'){
                $p = "省(?!直辖)";
            }else{
                $p = $r_arr1[$i];
            }
            $str = preg_replace('/'.$p.'/', $r_arr1[$i].' ', $str);
        }

        if(substr_count($str, '台湾') > 2){
            $str = str_replace("台湾台湾", "台湾 台湾 " , $str);
        }

        $arr = explode(" ",$str);

        $arr[0] = $this->getOne($arr[0],$str);

        //last
        $last_key = count($arr)-1;
        $last = $arr[$last_key];

        $last = $this->replaceStr($last,$r_arr1);

        $arr[$last_key] = 	$last;

        $res = implode(" ", $arr);

        return $res;
    }

    protected function parse_common_address($address)
    {
        // 空格省 空格市这种格式去掉空格
        // 5个自治区和省的名称中间空格去除
        $matchs = array(
            array(" 省 ","省 "),
            array("  "," "),
            array("北京 市","北京市"),
            array("天津 市","天津市"),
            array("上海 市","上海市"),
            array("重庆 市","重庆市"),
            array(" 自治区","自治区"),
            array(" 壮族自治区","壮族自治区"),
            array(" 回族自治区","回族自治区"),
            array(" 维吾尔自治区","维吾尔自治区"),
            array("省直辖县 级行政区 划","省直辖县级行政区划 "),
            array("重庆 重庆市 县 ","重庆 重庆市 "),
            array("中山市南区","中山市 南区"),
            array(" 市 辖区 "," "),
            array(" null"," "),
        );
        foreach ($matchs as $m) {
            $address = preg_replace('/'.$m[0].'/',$m[1],$address);
        }
        return $address;
    }

    protected function getOne($one,$str = ''){
        $f = true;
        $s_arr = array('黑龙江','内蒙古','钓鱼岛');
        foreach($s_arr as $v){
            if(strpos( $one , $v ) !== false){
                $f = false;
                $one  = $v." ".str_replace($v, '', $one);
                break;
            }
        }

        if($f === true){
            $tmp = mb_substr($one, 0,2,'utf-8');
            //4个直辖市直接替换
            $zxs = array("北京",'天津','上海','重庆');
            if( in_array($tmp,$zxs) ){
                if(strpos($str, $tmp.$tmp) === false && strpos($str, $tmp.' '.$tmp) === false && strpos($str, $tmp.'市 '.$tmp) === false && strpos($str, $tmp.'市  '.$tmp) === false) {
                    $tmp .= ' '.$tmp;
                }
            }
            $one = $tmp." ".mb_substr($one, 2,(strlen($one)+1),'utf-8');
        }
        return $one;
    }

    protected function replaceStr($str,$arr){
        for($i = 0 ; $i < count($arr) ; $i++ ){
            if(strpos($str,$arr[$i]) !== false){
                $str = preg_replace('/'.$arr[$i].'/', $arr[$i].' ', $str);
            }
        }
        return $str;
    }
}
