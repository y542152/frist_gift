<?php


namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Supports\Code;
use Illuminate\Http\Request;

class Common extends BaseController
{
    protected $upload_items_config = array(
        // 需要抓取的列
        'format'	=>	array(
            // 收货人
            '收货人姓名' => 'receiver_name',
            '收货人' => 'receiver_name',
            '客户姓名' => 'receiver_name',
            '收件人' => 'receiver_name',

            // 收货人电话
            '联系手机' => 'receiver_phone',
            '联系电话' => 'receiver_phone',
            '收件电话' => 'receiver_phone',
            '手机' => 'receiver_phone',
            '手机号' => 'receiver_phone',
            '手机号/QQ/淘宝账号' => 'receiver_phone',

            // 快递单号
            '物流单号' => 'express_number',
            '快递单号' => 'express_number',
            '运单号' => 'express_number',

            // 收货人地址
            '收货地址' => 'receiver_address',
            '客户地址' => 'receiver_address',
            '收件地址' => 'receiver_address',
            '省' => 'receiver_address_province',
            '市' => 'receiver_address_city',
            '区' => 'receiver_address_area',
            '街道' => 'receiver_address_street',
            '详细地址' => 'receiver_address_street',

            // 订单号
            '订单编号' => 'order_number',
            '订单号' => 'order_number',
            '平台订单号' => 'order_number',
        ),
        // 排序方式
        'order'	=>	array(
            'receiver_name','receiver_phone','express_number','receiver_address','receiver_address_province','receiver_address_city','receiver_address_area','receiver_address_street','order_number'
        ),
    );

    // 上传文件
    public function upload(Request $request): array
    {
        try {
            parent::_init();

            if (!isset($_FILES['file'])) {
                throw new Error(Code::UPLOAD_FAILED);
            }

            $file = $_FILES['file'];

            list($type, $suffix) = explode('/', $file['type']);

            if ($file['error'] != 0 || $file['size'] == 0 || $file['size'] / (1024 * 1024) > 2 || strpos($type, 'image') === false) {
                logger()->info('upload failed', [
                    'file'  =>  $file
                ]);
                throw new Error(Code::UPLOAD_FAILED);
            }

            $file = $request->file('file'); // 获取上传的文件
            $filename = uniqid() . '.' . $file->getClientOriginalExtension(); // 生成唯一文件名
            $destination = public_path('pic'); // 指定保存的目录
            $file->move($destination, $filename); // 将文件移动到 public/avatars

            return $this->returnData([
                'url'   =>  asset("pic/$filename")
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function up_csv(Request $request){
        try {
            parent::_init();

            if (!isset($_FILES['file'])) {
                throw new Error(Code::UPLOAD_FAILED);
            }

            $file = $_FILES['file'];
            list($type, $suffix) = explode('.', $file['name']);

            $file = $request->file('file'); // 获取上传的文件
            $filename = uniqid() . '.' . $file->getClientOriginalExtension(); // 生成唯一文件名
            $destination = public_path('address'); // 指定保存的目录
            $file->move($destination, $filename); // 将文件移动到 public/avatars

            $filename = $destination. '/' .$filename;

            switch ($suffix) {
                case 'xls':
                    $this->parse_xls($filename);
                    break;

                case 'csv':
                    $this->parse_csv($filename);
                    break;

                default:
                    @unlink ( $filename );
                    throw new Error(Code::OPERATE_FAILED, '暂不支持此类型文件');
            }
            @unlink ( $filename );

            $data = $_POST["addinputarr"];
            $str = '';

            // 快递单号不存在就删掉
            if(isset($data[0]['express_number']))
            {
                $exist_n = 0;
                $new_data = array();
                foreach ($data as $key => $value) {
                    if(!$value['express_number']) $exist_n++;
                    unset($value['express_number']);
                    $new_data[$key] = $value;
                }
                if($exist_n == count($data)) {
                    $data = $new_data;
                    unset($new_data);
                }
            }

            foreach ($data as $key => $value) {

                // 通用格式 拼接上000000
                if(count($value) == 3) {
                    $value['order_number'] = '000000';
                }

                // 一个是完整的地址 一个是拼接的地址
                if(isset($value['receiver_address']))
                {
                    if(strpos($value['receiver_address'], ' ') === false) {
                        $value['receiver_address'] = $this->getJdaddress($value['receiver_address']);
                    }

                    $value['receiver_address'] = str_replace(' -', ' ', $value['receiver_address']);
                }
                else if(isset($value['receiver_address_province']))
                {
                    $value['receiver_address'] = implode(' ', array(
                        $value['receiver_address_province'],
                        $value['receiver_address_city'],
                        $value['receiver_address_area'],
                        $value['receiver_address_street']
                    ));

                    unset($value['receiver_address_province']);
                    unset($value['receiver_address_city']);
                    unset($value['receiver_address_area']);
                    unset($value['receiver_address_street']);
                }

                // 通用的地址整理
                $value['receiver_address'] = $this->parse_common_address($value['receiver_address']);

                $value = $this->order_cols($value);
                $str .= implode('，', $value)."\r\n";
            }
            return $this->returnData([
                'address'   =>  $str
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function parse_xls($pFilename)
    {
        $addinputarr = [];

        $objReader = new \PHPExcel_Reader_Excel5();
        $excel = $objReader->load ( $pFilename );
        $currentSheet = $excel->getSheet ( 0 );
        $allColumn = count($this->upload_items_config['format']);
        $allRow = $currentSheet->getHighestRow ();

        $excel_cols = array();

        $col = 1;
        while ($currentSheet->cellExists("A{$col}") === true) {
            $item = array();
            if($col == 1 ) {
                for($c = 0; $c <= $allColumn; $c ++) {
                    $tempValue = ($currentSheet->getCellByColumnAndRow ( $c,1)->getValue ());
                    $itme_title = $this->isUtf8New($tempValue) ? $tempValue : (iconv ( "gbk", "utf-8//IGNORE", $tempValue));
                    $itme_title = trim($itme_title);

                    if (isset($this->upload_items_config['format'][$itme_title])) {
                        $xls_cols[$this->upload_items_config['format'][$itme_title]] = $c;
                    }
                }
            } else {
                // 从第二行开始 用$csv_cols获取数据
                if($xls_cols) {
                    // 调整一下顺序
                    $xls_cols = $this->order_cols($xls_cols);

                    foreach ($xls_cols as $key => $value) {
                        $tempValue = ($currentSheet->getCellByColumnAndRow ( $value,$col)->getValue ());
                        $tempValue = $this->isUtf8New($tempValue) ? $tempValue : iconv ( "gbk", "utf-8//IGNORE", $tempValue );
                        $filter = array(",",'"',"'",' ','，','  ','.');
                        $tempValue = str_replace($filter,'',$tempValue);
                        $item[$key] = trim($tempValue);
                    }
                    if($item )
                        $addinputarr [] = $item;
                }
            }
            $col++;
        }

        $_POST["addinputarr"] = $addinputarr;
    }

    private function parse_csv($pFilename)
    {
        $addinputarr = [];
        $handle = fopen ( $pFilename, "r" );
        $csv_cols = array();
        $row = 0;
        while ( $data = fgetcsv ( $handle, 800000, "," ) ) {
            if (!$this->isUtf8New($data[0])) {
                $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
            };
            $item = array();

            $temp = [];
            foreach ($data as $it) {
                if (strpos($it, ',')) {
                    $temp = array_merge($temp, explode(',', $it));
                } else {
                    $temp[] = $it;
                }
            }

            $num = count ( $data );

            // 取第一行获取到需要抓取数据的index $csv_cols
            if ($row == 0) {
                for($c = 0; $c < $num; $c ++) {
                    $itme_title = $this->isUtf8New($data[$c]) ? $data [$c] : (iconv ( "gbk", "utf-8//IGNORE", $data [$c] ));
                    $itme_title = trim($itme_title);

                    if($itme_title == 'ʡ') {
                        $itme_title = iconv ( "gb2312", "utf-8", $itme_title);
                    }

                    // 多了一个· 删掉
                    $divs = ['%EF%BB%BF'];
                    $itme_title = urldecode(str_replace($divs,'',urlencode($itme_title)));

                    if (isset($this->upload_items_config['format'][$itme_title])) {
                        $csv_cols[$this->upload_items_config['format'][$itme_title]] = $c;
                    }
                }
            } else {
                // 从第二行开始 用$csv_cols获取数据
                if($csv_cols) {
                    // 调整一下顺序
                    $csv_cols = $this->order_cols($csv_cols);

                    foreach ($csv_cols as $key => $value) {
                        $tempValue = $this->isUtf8New($data[$value]) ? $data[$value] : iconv ( "gbk", "utf-8//IGNORE", $data[$value]);
                        $filter = array(",",'"',"'",' ','，','  ','.');
                        $tempValue = str_replace($filter,'',$tempValue);
                        $item[$key] = trim($tempValue);
                    }
                }
            }

            if($item) {
                $addinputarr[] = $item;
            }
            $row ++;
        }
        fclose ( $handle );
        $_POST["addinputarr"] = $addinputarr;
    }

    /**
     * 判断文字是否为utf8
     *
     * @param  string $pstr 文字
     * @return boolean
     */
    private function isUtf8New($pstr)
    {
        $encode = mb_detect_encoding($pstr, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        return $encode === 'UTF-8';
    }

    private function order_cols($cols)
    {
        $new = array();

        foreach ($this->upload_items_config['order'] as $k) {
            if(isset($cols[$k])) {
                $new[$k] = $cols[$k];
            }
        }
        return $new;
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
    protected function replaceStr($str,$arr){
        for($i = 0 ; $i < count($arr) ; $i++ ){
            if(strpos($str,$arr[$i]) !== false){
                $str = preg_replace('/'.$arr[$i].'/', $arr[$i].' ', $str);
            }
        }
        return $str;
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
}
