<?php


namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Exceptions\FrequentActionError;
use App\Repositories\UserRepository;
use App\Services\GiftApiService;
use App\Supports\Code;
use App\Type\BuyGiftInputType;
use App\Type\Models\SearchParams;
use App\Type\PagingListType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Gift extends BaseController
{
    public function sendOptions(): array
    {
        try {
            parent::_init();
            $options = (new GiftApiService())->fetchGifts();
            return $this->returnData($options);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function buy(): array
    {
        $this->paramRules = [
            'kuaidiId'      =>  'required|int',
            'cangId'        =>  'required|int',
            'goodsId'       =>  'required|int',
            'goodsName'     =>  'required|string',
            'sendName'      =>  'required|string',
            'sendMobile'    =>  'required|string',
            'sendProvince'  =>  'required|string',
            'sendCity'      =>  'required|string',
            'sendDistrict'  =>  'required|string',
            'sendAddress'   =>  'required|string',
            'storageProvince'  =>  'required|string',
            'storageCity'      =>  'required|string',
            'storageDistrict'  =>  'required|string',
            'storageAddress'   =>  'required|string',
            'platform'      =>  'required|int|min:1|max:9',
            'platformName'  =>  'required|string',
            'recList'       =>  'required|array|max:200',
            'wuhuoyuan'     =>  'required|bool',
            'isXinfeng'     =>  'required|bool'
        ];

        try {
            parent::_init();
            $uid = $this->getUid();
            $lock = Cache::lock('buy-handle-' . $uid, 10);
            if ($lock->get()) {
                $params = new BuyGiftInputType($this->requestData);
                $ret    = (new GiftApiService())->buy($uid, $params);

                $lock->release();
                return $this->returnData($ret);
            } else {
                throw new FrequentActionError(Code::FREQUENT_OPERATION);
            }
        } catch (FrequentActionError $e) {
            return $this->handleException($e);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }

    public function logs(): array
    {
        $this->paramRules = [
            'page'          =>  'int|min:1',
            'pageSize'      =>  'int|min:1|max:50',
            'is_cancel'     =>  'nullable|int|in:0,1',
            'is_xinfeng'    =>  'nullable|bool',
            'created_at'    =>  'nullable|array',
            'created_at.0'  =>  'string',
            'created_at.1'  =>  'string',
            'ids'           =>  'nullable|string',
            'order_sn'      =>  'nullable|string',
            'kuaidi_sn'     =>  'nullable|string',
            'kuaidi_name'   =>  'nullable|string',
            'uid'           =>  'nullable|int|min:1',
        ];

        try {
            parent::_init();

            $user = (new UserRepository())->fetchUserInfo($this->getUid());

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';

            if (isset($this->requestData['is_cancel'])) {
                $params->otherCondition['is_cancel'] = $this->requestData['is_cancel'];
            }

            if (!empty($this->requestData['created_at']) && count($this->requestData['created_at']) == 2) {
                $dateStart = new \DateTime($this->requestData['created_at'][0]);
                $dateEnd = new \DateTime($this->requestData['created_at'][1]);
                $diff = $dateEnd->diff($dateStart);
                if ($diff->days > 7 && $user->role != 'ADMIN') {
                    throw new Error(Code::REQUEST_PARAM_ERROR, '起始日期不能超过7天');
                }

                $params->otherCondition['created_at'] = $this->requestData['created_at'];
            }

            if (!empty($this->requestData['ids'])) {
                $params->otherCondition['ids'] = explode(',', $this->requestData['ids']);
            }

            if (!empty($this->requestData['order_sn'])) {
                $params->otherCondition['order_sn'] = $this->requestData['order_sn'];
            }

            if (!empty($this->requestData['kuaidi_sn'])) {
                $params->where['kuaidi_sn'] = $this->requestData['kuaidi_sn'];
            }

            if (!empty($this->requestData['kuaidi_name'])) {
                $params->otherCondition['kuaidi_name'] = $this->requestData['kuaidi_name'];
            }

            if (isset($this->requestData['is_xinfeng'])) {
                $params->where['is_xinfeng'] = $this->requestData['is_xinfeng'] ? 1 : 0;
            }

            if ($user->role != 'ADMIN') {
                $params->where['operate_uid'] = $this->getUid();
            } else {
                if (!empty($this->requestData['uid'])) {
                    $params->where['operate_uid'] = $this->requestData['uid'];
                }
            }

            $ret = (new GiftApiService())->fechLogs($params);
            return $this->returnData((new PagingListType($ret))->toArray());
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function queryOrderStatus(string $orderSn): array
    {
        try {
            parent::_init();

            $lock = Cache::lock('queryOrderStatus-handle-' . $orderSn, 10);

            if ($lock->get()) {
                $ret = (new GiftApiService())->fetchOrderStatus($orderSn);

                $lock->release();
                return $this->returnData($ret);
            } else {
                throw new FrequentActionError(Code::FREQUENT_OPERATION);
            }
        } catch (FrequentActionError $e) {
            return $this->handleException($e);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }


    public function export()
    {
        $this->paramRules = [
            'page'          =>  'int|min:1',
            'pageSize'      =>  'int|min:1|max:50',
            'is_cancel'     =>  'nullable|int|in:0,1',
            'is_xinfeng'    =>  'nullable|bool',
            'created_at'    =>  'nullable|array',
            'created_at.0'  =>  'string',
            'created_at.1'  =>  'string',
            'ids'           =>  'nullable|string',
            'order_sn'      =>  'nullable|string',
            'kuaidi_sn'     =>  'nullable|string',
            'kuaidi_name'   =>  'nullable|string',
            'uid'           =>  'nullable|int|min:1',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';

            if (isset($this->requestData['is_cancel'])) {
                $params->otherCondition['is_cancel'] = $this->requestData['is_cancel'];
            }

            $user = (new UserRepository())->fetchUserInfo($this->getUid());
            if (!$user) {
                throw new Error(Code::QUERY_FAILED, '用户不存在');
            }

            if (!empty($this->requestData['created_at']) && count($this->requestData['created_at']) == 2) {
                $dateStart = new \DateTime($this->requestData['created_at'][0]);
                $dateEnd = new \DateTime($this->requestData['created_at'][1]);
                $diff = $dateEnd->diff($dateStart);
                if ($diff->days > 7 && $user->role != 'ADMIN') {
                    throw new Error(Code::REQUEST_PARAM_ERROR, '起始日期不能超过7天');
                }

                $params->otherCondition['created_at'] = $this->requestData['created_at'];
            }

            if (!empty($this->requestData['ids'])) {
                $params->otherCondition['ids'] = explode(',', $this->requestData['ids']);
            }

            if (!empty($this->requestData['order_sn'])) {
                $params->otherCondition['order_sn'] = $this->requestData['order_sn'];
            }

            if (!empty($this->requestData['kuaidi_sn'])) {
                $params->where['kuaidi_sn'] = $this->requestData['kuaidi_sn'];
            }

            if (!empty($this->requestData['kuaidi_name'])) {
                $params->otherCondition['kuaidi_name'] = $this->requestData['kuaidi_name'];
            }

            if (isset($this->requestData['is_xinfeng'])) {
                $params->where['is_xinfeng'] = $this->requestData['is_xinfeng'] ? 1 : 0;
            }

            if ($user->role != 'ADMIN') {
                $params->where['operate_uid'] = $this->getUid();
            } else {
                if (!empty($this->requestData['uid'])) {
                    $params->where['operate_uid'] = $this->requestData['uid'];
                }
            }

            $ret = (new GiftApiService())->fechLogs($params);

            if (!count($ret)) {
                throw new Error(Code::RESOURCE_NOT_FOUND, '无数据可导出');
            }

            $data = [];
            foreach ($ret as $item) {
                $item = $item->toArray();
                $data[] = [
                    $item['created_at'],
                    $item['kuaidi_name'],
                    $item['kuaidi_sn'],
                    $item['send_name'],
                    $item['send_mobile'],
                    sprintf('%s%s%s %s', $item['send_province'], $item['send_city'], $item['send_district'], $item['send_address']),
                    $item['receive_name'],
                    $item['receive_mobile'],
                    sprintf('%s%s%s %s', $item['receive_province'], $item['receive_city'], $item['receive_district'], $item['receive_address']),

                ];
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $header = ["下单时间", "快递公司", "快递单号", "发货人", '发货电话', '发货人地址', '收货名字', '收货电话', '收货地址'];

            // 设置表头
            foreach ($header as $key => $title) {
                // 设置列标题
                $sheet->setCellValue(chr(65 + $key) . '1', $title);

                // 对于快递单号、发货电话、收货电话列，设置为文本格式
                if ($key == 2 || $key == 4 || $key == 7) {
                    $sheet->getStyle(chr(65 + $key) . '1')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                }
            }

            // 插入数据
            $row = 2;
            foreach ($data as $dataRow) {
                foreach ($dataRow as $key => $value) {
                    // 如果是需要作为文本的列（比如快递单号、发货电话、收货电话），使用 setCellValueExplicit
                    if ($key == 2 || $key == 4 || $key == 7) {
                        $sheet->setCellValueExplicit(chr(65 + $key) . $row, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue(chr(65 + $key) . $row, $value);
                    }
                }
                $row++;
            }

            // 自动选择第一格
            $spreadsheet->getActiveSheet()->setSelectedCell('A1');

            $writer = new Xlsx($spreadsheet);
            $filePath = sprintf("购买记录%s.xls", date('Y-m-d'));
            $writer->save($filePath);

             return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
