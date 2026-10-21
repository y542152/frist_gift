<?php


namespace App\Services;

use App\Exceptions\Error;
use App\Models\ChargeOrdersModel;
use App\Models\CoinFlowLogsModel;
use App\Models\OrdersModel;
use App\Models\UserCoinModel;
use App\Repositories\OrdersRepository;
use App\Repositories\UserCoinRepository;
use App\Repositories\UserRepository;
use App\Supports\Code;
use App\Supports\Helper;
use App\Supports\Http;
use App\Type\BuyGiftInputType;
use App\Type\Models\CoinChangeType;
use App\Type\Models\SearchParams;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftApiService
{
    const BALANCE_API = '/api/user/index';
    const GIFT_API = '/api/lipin/listnew';
    const GIFT_STATUS = '/api/lipin/status';
    const BUY_API = '/api/lipin/buy';

    const GIFT_OPTIONS_CACHE = 'GIFT_OPTIONS_CACHE';

    /**
     * @throws Error
     * @throws \Throwable
     */
    public function buy(int $uid, BuyGiftInputType $inputType)
    {
        try {
            $orderAddressList = $inputType->recList;

            $mainOrderSN = $this->randomOrderSn('MAIN');
            $inputType->recList = $this->preOrder($uid, $orderAddressList, $inputType, true, $mainOrderSN);

            $params = $this->parsePostData();

            $params['kuaidi_id'] = $inputType->kuaidiId;
            $params['cang_id'] = $inputType->cangId;
            $params['goods_id'] = $inputType->goodsId;
            $params['send_name'] = $inputType->sendName;
            $params['send_mobile'] = $inputType->telephone;
            $params['send_province'] = $inputType->sendProvince;
            $params['send_city'] = $inputType->sendCity;
            $params['send_district'] = $inputType->sendDistrict;
            $params['send_address'] = $inputType->sendAddress;
            $params['auth'] = $inputType->auth;
            $params['platform'] = $inputType->platform;
            $params['rec_list'] = $inputType->recList;
            $ret = $this->buyApi($params);
//        $ret = [];
//        foreach ($inputType->recList as $key => $info) {
//            if ($key == 0) {
//                $ret[$info['order_sn']] = [
//                    'order_sn' => $info['order_sn'],
//                    'kuaidi_sn' => '',
//                    'status' => 2,
//                    'kuaidi_name' => '中铁智慧物流',
//                    'msg' => '无货源模式必须填写上家快递单号！'
//                ];
//            } else if ($key == 1) {
//                $ret[$info['order_sn']] = [
//                    'order_sn' => $info['order_sn'],
//                    'kuaidi_sn' => 'xf123',
//                    'status' => 3,
//                    'kuaidi_name' => '中铁智慧物流',
//                    'msg' => '无货源模式必须填写上家快递单号！'
//                ];
//            } else if ($key == 2){
//                $ret[$info['order_sn']] = [
//                    'order_sn' => $info['order_sn'],
//                    'kuaidi_sn' => '',
//                    'status' => 4,
//                    'kuaidi_name' => '中铁智慧物流',
//                    'msg' => '无货源模式必须填写上家快递单号！'
//                ];
//            }
//        }

            return $this->processApiReturnOrders($mainOrderSN, $inputType->kuaidiId, $ret);
        } catch (\Throwable $e) {
            return [
                'errorMsg'  =>  $e->getMessage()
            ];
        }
    }

    /**
     * @param string $mainOrderSN
     * @param int $kuaidiId
     * @param array $ret api返回结果
     * @param bool $canceled
     * @return array
     * @throws Error
     * @throws \Throwable
     */
    public function processApiReturnOrders(string $mainOrderSN, int $kuaidiId, array $ret, bool $canceled = false)
    {
        DB::beginTransaction();

        try {
            $errorData = [];
            // 取号失败订单
            $failedOrderSn = [];
            // 取号成功订单
            $successOrderSn2 = [];
            $successOrderSn3 = [];

            // 取号成功订单总金额 多个订单可能由不同人下单
            $totalAmountSortByUid = [];
            $expressCountByUid = []; // 下单数量

            $orderList = OrdersModel::query()->whereIn('order_sn', array_column($ret, 'order_sn'))->get()->toArray();
            $orderList = array_column($orderList, null,'order_sn');

            $options = $this->fetchGifts();
            $options = array_column($options, null, 'id');
            $kuaidiInfo = $options[$kuaidiId];

            foreach ($ret as $info) {
                $orderSn = $info['order_sn'];
                $kuaidi_sn = $info['kuaidi_sn'] ?? '';
                $orderInfo = $orderList[$orderSn] ?? [];
                if (!$orderInfo) {
                    // 订单不存在 不做后续操作
                    $errorData[] = sprintf("单号：%s 不存在", $orderSn);
                    continue;
                }

                // 已取号成功的跳过
                if ($orderInfo['status'] == OrdersModel::STATUS_COLLECTED || $orderInfo['status'] == OrdersModel::STATUS_WAIT_COLLECT) {
                    continue;
                }

                $id = $orderInfo['id'];

                $uid = $orderInfo['operate_uid'];
                // 单条订单售价
                $amount = $orderInfo['total_amount'];

                if (!isset($totalAmountSortByUid[$uid])) {
                    $totalAmountSortByUid[$uid] = 0;
                    $expressCountByUid[$uid] = 0;
                }

                // 取号失败
                if ($info['status'] == OrdersModel::STATUS_PREORDER_FAILED) {
                    $failedOrderSn[] = $id;
                    $errorData[] = sprintf("单号：%s 取号失败，原因：%s", $orderSn,  $info['msg']);

                    // 失败 记录错误信息
                    OrdersModel::query()->where('id', $id)->update([
                        'status'    =>  $canceled ? OrdersModel::STATUS_COLLECTED : OrdersModel::STATUS_PREORDER_FAILED,
                        'error'     =>  $info['msg']
                    ]);
                } elseif ($info['status'] == OrdersModel::STATUS_WAIT_COLLECT ) {
                    // 下单成功 已揽件|待揽件
                    $successOrderSn2[] = $id;
                    $totalAmountSortByUid[$uid] = bcadd($totalAmountSortByUid[$uid], $amount, 4);
                    $expressCountByUid[$uid] = $expressCountByUid[$uid] + 1;

                    OrdersModel::query()->where('id', $id)->update([
                        'status'    =>  OrdersModel::STATUS_WAIT_COLLECT,
                        'kuaidi_sn' =>  $kuaidi_sn
                    ]);

                } elseif ($info['status'] == OrdersModel::STATUS_COLLECTED ) {
                    // 下单成功 已揽件|待揽件
                    $successOrderSn3[] = $id;
                    $totalAmountSortByUid[$uid] = bcadd($totalAmountSortByUid[$uid], $amount, 4);
                    $expressCountByUid[$uid] = $expressCountByUid[$uid] + 1;

                    OrdersModel::query()->where('id', $id)->update([
                        'status'    =>  OrdersModel::STATUS_COLLECTED,
                        'kuaidi_sn' =>  $kuaidi_sn
                    ]);
                }
            }

            // 1. 扣除用户余额
            foreach ($totalAmountSortByUid as $_uid => $totalAmount) {
                if ($totalAmount) {
                    // 四舍五入 取2位小数
                    $totalAmount = floor($totalAmount * 100) / 100;
                    $coinType = new CoinChangeType();
                    $coinType->amount = $totalAmount;
                    $coinType->orderNo = $mainOrderSN;
                    $coinType->channel = CoinFlowLogsModel::CHANNEL_BUY;
                    $coinType->desc = sprintf("购买%s个%s快递", $expressCountByUid[$_uid], $kuaidiInfo['name']);
                    (new UserCoinRepository())->coinOut($_uid, $coinType);
                }
            }

            DB::commit();
            return [
                'mainOrderSn'   =>  $mainOrderSN,
                'errors' =>  $errorData,
                'failed' =>  $failedOrderSn,
                'success' => array_merge($successOrderSn2, $successOrderSn3)
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning('更新购买订单数据失败！错误：' . $e->getMessage(), [
                'ret'   =>  $ret
            ]);
            throw $e;
        }
    }

    /**
     * @throws Error
     */
    public function preOrder(int $uid, array $addressList, BuyGiftInputType $inputType, $repeat = true, $mainOrderSn = ''): array
    {
        $addressList = array_filter($addressList);
        $user = (new UserRepository())->fetchUserInfo($uid, ['profit_rate', 'is_disabled', 'is_xinfeng']);

        if ($user->is_disabled == 1) {
            throw new Error(Code::OPERATE_FAILED, '账户已被禁用，请联系管理员');
        }

        if ($user->is_xinfeng == 0 && $inputType->isXinfeng) {
            throw new Error(Code::OPERATE_FAILED, '无权限，请联系管理员开通');
        }

        // 利润 x元
        $profitRate = $user->profit_rate;

        $userCoin = UserCoinModel::query()->where('uid', $uid)->get()->first();
        $coin = $userCoin ? $userCoin->coin : 0;

        $options = $this->fetchGifts();
        $options = array_column($options, null, 'id');
        $kuaidiInfo = $options[$inputType->kuaidiId];
        $goodsList = array_column($kuaidiInfo['goods'], null, 'goods_id');
        $goodsInfo = $goodsList[$inputType->goodsId];

        // 每件售价 销售价=快递价格+商品价格+利润
        // 1. 快递价格+商品价格
        $eachGoodsSalePrice =  bcadd($kuaidiInfo['price'], $goodsInfo['price'], 4);

        // 2. (快递价格+商品价格) + 利润
        $eachGoodsSalePrice = bcadd($eachGoodsSalePrice, $profitRate, 4);
        // 3. 四舍五入取2位小数
        $eachGoodsSalePrice = floor($eachGoodsSalePrice * 100) / 100;

        // 余额是否足够
        $amount = bcmul($eachGoodsSalePrice, count($addressList), 4);
        $amount = floor($amount * 100) / 100;

        if ($coin < $amount) {
            throw new Error(Code::OPERATE_FAILED, '余额不足');
        }

        // 检查api余额
        $balance = $this->fetchBalance();
        if (empty($balance['money']) || $balance['money'] < $amount) {
            throw new Error(Code::OPERATE_FAILED, '没单啦，快提醒管理员加单再买！');
        }

        $data = [];
        try {
            $bathInsertData = [];
            foreach ($addressList as $info) {
                $temp = explode('，', $info);
                $name = $temp[0] ?? '';
                $phone = $temp[1] ?? '';
                $address = $temp[2] ?? '';
                $otherKuaidiSn = $temp[3] ?? ''; // 无货源订单的上家快递单号 | 邮编
                $orderSN = $this->randomOrderSn();

                $name = trim($name);
                $phone = trim($phone);
                $address = trim($address);
                $addressTemp =  array_values(array_filter(explode(' ', $address)));

                if (count($addressTemp) >= 4) {
                    $province = $addressTemp[0];
                    $city = $addressTemp[1];
                    $district = $addressTemp[2];
                    $addressStr = join(' ', array_slice($addressTemp, 3));
                    $orderInfo = [
                        'order_sn'  =>  $orderSN,
                        'rec_name'  =>  $name,
                        'rec_mobile'  => $phone,
                        'rec_province'  =>  $province,
                        'rec_city'  =>  $city,
                        'rec_district'  =>  $district,
                        'rec_address'  =>  $addressStr
                    ];
                    if ($inputType->wuhuoyuan) {
                        $orderInfo['other_kuaidi_sn'] = $otherKuaidiSn;
                    }

                    $data[] = $orderInfo;
                    $bathInsertData[] = [
                        'main_order_sn'  =>  $mainOrderSn ?: $this->randomOrderSn('MAIN'),
                        'order_sn'  =>  $orderSN,
                        'kuaidi_id'  =>  $inputType->kuaidiId,
                        'kuaidi_name'  =>  sprintf('%s（%s）', $kuaidiInfo['name'], $kuaidiInfo['cang']['cang_name'] ?? ''),
                        'platform_name'  =>  $inputType->platformName,
                        'goods_id'  =>  $inputType->goodsId,
                        'goods_name'  =>  $inputType->goodsName,
                        'price' =>  $goodsInfo['price'], // 商品价格
                        'express_price' => $kuaidiInfo['price'],
                        'profit_rate' => $profitRate,
                        'total_amount' => $eachGoodsSalePrice,

                        'send_name'  =>  $inputType->sendName,
                        'send_mobile'  => $inputType->sendMobile,
                        'send_province'  =>  $inputType->sendProvince,
                        'send_city'  =>  $inputType->sendCity,
                        'send_district'  =>  $inputType->sendDistrict,
                        'send_address'  =>  $inputType->sendAddress,

                        'storage_province'  =>  $inputType->storageProvince,
                        'storage_city'  =>  $inputType->storageCity,
                        'storage_district'  =>  $inputType->storageDistrict,
                        'storage_address'  =>  $inputType->storageAddress,

                        'receive_name'  =>  $name,
                        'receive_mobile'  => $phone,
                        'receive_province'  =>  $province,
                        'receive_city'  =>  $city,
                        'receive_district'  =>  $district,
                        'receive_address'  =>  $addressStr,

                        'operate_uid' => $uid,
                        'is_xinfeng'   =>  $inputType->isXinfeng ? 1 : 0,
                        'zip_code' => $inputType->wuhuoyuan ? '' : (string) substr($otherKuaidiSn, 0, 6),
                        'other_kuaidi_sn' => $inputType->wuhuoyuan ? $otherKuaidiSn : '',
                        'created_at'    =>  date('Y-m-d H:i:s', time()),
                        'updated_at'    =>  date('Y-m-d H:i:s', time()),
                    ];
                } else {
                    throw new Error(Code::REQUEST_PARAM_ERROR, '地址不正确');
                }
            }
            OrdersModel::query()->insert($bathInsertData);
        } catch (\Throwable $e) {
            if ($repeat) {
                $this->preOrder($uid, $addressList, $inputType, false);
            } else {
                throw new Error(Code::QUERY_FAILED, '下单失败:'. $e->getMessage());
            }
        }
        return $data;
    }

    public function buyApi(array $params)
    {
        $res = $this->requestApi($this->getAPIURL(self::BUY_API), $params);
        $data = $res['data'];
        $code = $res['code'];

        // 0代表购买成功，1代表购买失败，2代表购买故障
        if ($code == 1) {
            throw new Error(Code::OPERATE_FAILED, $res['msg'] ?? '购买失败，请稍后重试' );
        }

        if ($code == 2) {
            throw new Error(Code::OPERATE_FAILED, $res['msg'] ?? '购买故障，请稍后重试' );
        }

        if ($code != 0) {
            throw new Error(Code::OPERATE_FAILED, $res['msg'] ?? 'API请求失败' );
        }

        return $data;
    }

    /**
     * @throws Error
     */
    public function fetchGifts()
    {
        if (Cache::has(self::GIFT_OPTIONS_CACHE)) {
            return Cache::get(self::GIFT_OPTIONS_CACHE);
        }

        $res = $this->requestApi($this->getAPIURL(self::GIFT_API));

        $data = $res['data'];
        $code = $res['code'];
        if ($code != 0) {
            throw new Error(Code::OPERATE_FAILED, $res['msg'] ?? 'API请求失败' );
        }

        Cache::put(self::GIFT_OPTIONS_CACHE, $data, 10 * 60 * 3600);
        return $data;
    }

    private function getAPIURL($api)
    {
        $url = Cache::get('GIFT_API_URL');
        if (!$url) {
            throw new Error(Code::OPERATE_FAILED, '未配置API网址');
        }
        return sprintf('%s%s', Cache::get('GIFT_API_URL'), $api);
    }

    /**
     * @throws Error
     */
    public function fetchBalance()
    {
        $res = $this->requestApi($this->getAPIURL(self::BALANCE_API));
        $data = $res['data'];
        $code = $res['code'];
        if ($code != 0) {
            throw new Error(Code::OPERATE_FAILED, $res['msg'] ?? 'API请求失败' );
        }

        return $data;
    }

    /**
     * @throws \Throwable
     * @throws Error
     */
    public function fetchOrderStatus(string $orderSn): bool
    {
        $orderInfo = OrdersModel::query()->where([
            'order_sn' => $orderSn,
        ])->first();

        if (!$orderInfo) {
            throw new Error(Code::RESOURCE_NOT_FOUND);
        }

        if ($orderInfo->status != OrdersModel::STATUS_WAIT_PREORDER && $orderInfo->status != OrdersModel::STATUS_PREORDER_FAILED) {
            throw new Error(Code::STATUS_NOT_MATCH, '订单状态不匹配，需状态为待取号或取号失败');
        }

        $ret = $this->requestApi($this->getAPIURL(self::GIFT_STATUS), [
            'order_sn'  =>  [$orderSn]
        ]);

        $ret = [
            'code'      => 3,
            'data'      => [
                [
                    'order_sn' => $orderSn,
                    'kuaidi_sn' => '',
                    'status' => 2,
                    'kuaidi_name' => '中铁智慧物流',
                    'msg' => '无货源模式必须填写上家快递单号！'
                ]
            ]
        ];

        $data = $ret['data'];
        $code = (int) $ret['code']; // 0代表获取成功，1代表获取失败，2代表线路故障，3代表查询不到该订单

        switch ($code) {
            case 0:
                // 正常返回
                $res = $this->processApiReturnOrders($orderInfo->main_order_sn, $orderInfo->kuaidi_id, $data, true);
                $return = !($res['failed'] == 1);
                break;
            case 3:
                // 查询不到订单，直接将订单设置为已取消
                OrdersModel::query()->where('order_sn', $orderSn)->update([
                    'status'    =>  OrdersModel::STATUS_CANCELED,
                ]);
                $return = false;
                break;
            default:
                $errorMsg = $ret['msg'] ?? '';
                if (empty($errorMsg)) {
                    if ($code == 1) {
                        $errorMsg = 'API获取订单状态失败，请稍后重试';
                    } else if ($code == 2) {
                        $errorMsg = 'API线路故障，获取订单状态失败，请稍后重试';
                    } else {
                        $errorMsg = 'API请求失败，请稍后重试';
                    }
                }

                throw new Error(Code::OPERATE_FAILED, $errorMsg);
        }

        return $return;
    }

    public function randomOrderSn($prefix = 'OD', $len = 5)
    {
        $date = date('Ymd');
        [$microtime, $time] = explode(' ', microtime());
        return sprintf('%s%s%s%s', $prefix, $date, round($microtime * 100000), Helper::randomString($len));
    }

    private function requestApi(string $api, $params = [])
    {
        $postData = $this->parsePostData();

        if ($params) {
            $postData = array_merge($postData, $params);
        }

        $res = Http::curl_post($api, $postData);
        if (is_string($res)) {
            $res = json_decode($res, true);
        }

        if (
            !array_key_exists( 'code', (array) $res) ||
            !array_key_exists('data', (array) $res)
        ) {
            throw new Error(Code::HTTP_FAILED, $res['msg'] ?? 'API接口请求错误');
        }

        return $res;
    }

    private function parsePostData(): array
    {
        $userName = Cache::get('GIFT_API_ACCOUNT');
        $pw = Cache::get('GIFT_API_PW');

        if (!$userName || !$pw) {
            throw new Error(Code::OPERATE_FAILED, '未配置礼物API账号');
        }

        $sid = time();
        return [
            "username"  =>  $userName,
            "sid"       =>  $sid,
            "sign"      =>  $this->parseSignStr($userName, $pw, $sid)
        ];
    }

    private function parseSignStr($userName, $pw, $sid): string
    {
        $preSignStr = sprintf("%s%s%s", $userName, md5(md5($pw)), $sid);
        return md5($preSignStr);
    }

    public function fechLogs(SearchParams $params)
    {
        return (new OrdersRepository())->pagingList($params);
    }
}
