<?php


namespace App\Services;


use App\Exceptions\Error;
use App\Models\ChargeOrdersModel;
use App\Models\CoinFlowLogsModel;
use App\Models\OrdersModel;
use App\Models\PointFlowLogsModel;
use App\Models\UserModel;
use App\Models\WechatChargeOrdersModel;
use App\Repositories\ChargeOrdersRepository;
use App\Repositories\OrdersRepository;
use App\Repositories\UserCoinRepository;
use App\Repositories\UserRepository;
use App\Repositories\WechatChargeOrdersRepository;
use App\Sdk\f2fpay\builder\AlipayTradePayContentBuilder;
use App\Sdk\f2fpay\builder\AlipayTradePrecreateContentBuilder;
use App\Sdk\f2fpay\builder\GoodsDetail;
use App\Sdk\f2fpay\service\AlipayTradeService;
use App\Supports\Code;
use App\Supports\Helper;
use App\Type\EmailResetType;
use App\Type\Models\CoinChangeType;
use App\Type\Models\PointChangeType;
use App\Type\Models\SearchParams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;


class UserService
{
    public const EMAIL_RESET_KEY = 'email-reset-%s';

    /**
     * 普通账号登录
     * @param $account
     * @param $password
     * @return string
     * @throws Error
     */
    public function login($account, $password): string
    {
        $builders = (new UserRepository())->fetchUserByAccount($account);
        if(!$builders) {
            throw new Error(Code::LOGIN_FAILED);
        }

        $pw = $builders->pw_encrypt;

        if (!$pw) {
            throw new Error(Code::LOGIN_FAILED);
        }

        $password = $this->encryptPw($password, $builders->salt);

        if ($password !== $pw) {
            throw new Error(Code::LOGIN_FAILED);
        }

        // 返回token
        return (new UserRepository())->updateTokenByUid($builders->uid);
    }

    /**
     * 账号注册
     * @param string $phone
     * @param string $qq
     * @param string $pw
     * @param int $inviteUid
     * @return void
     * @throws Error
     */
    public function registerByAccount(string $phone, string $qq, string $pw, int $inviteUid = 0): void
    {
        $salt = Helper::randomString(8);
        $encryptPasswd = $this->encryptPw($pw, $salt);

        if ((new UserRepository())->isAccountExist($phone)) {
            throw new Error(Code::USER_ACCOUNT_EXIST);
        }

        if ((new UserRepository())->isQQExist($qq)) {
            throw new Error(Code::USER_ACCOUNT_EXIST, 'qq号码已存在');
        }

        // 邀请id不存在
        if ($inviteUid > 0 && !(new UserRepository())->isUidExist($inviteUid)) {
            $inviteUid = 0;
        }

        $data = [
            'account'      =>  $phone,
            'nickname'     =>  $phone,
            'qq'           =>  $qq,
            'qqemail'      =>  $qq.'@qq.com',
            'avatar_url'   =>  '',
            'pw_encrypt'   =>  $encryptPasswd,
            'salt'         =>  $salt,
            'invite_uid'   =>  $inviteUid,
            'last_login_at' =>  date('Y-m-d H:i:s')
        ];

        // 添加到数据库
        (new UserRepository())->addUser($data);
    }

    /**
     * token是否有效
     * @param $tokenInfo
     * @return bool
     */
    public function isTokenValid($tokenInfo): bool
    {
        if(
            isset($tokenInfo['expired_at'])
            && strtotime($tokenInfo['expired_at']) > time()
            && isset($tokenInfo['token'])
            && $tokenInfo['token']
        ) {
            return true;
        }
        return false;
    }

    public function charge(int $uid, float $money)
    {
        $order = (new ChargeOrdersRepository())->fetchNotExpiredOrder($uid, $money);
        if ($order && !empty($order->qr_code)) {
            return [
                'qr'    =>  $order->qr_code,
                'orderNo'   =>  $order->out_trade_no,
                'amount'    =>  $order->total_amount
            ];
        }
        $chargeOrderSn = $this->getChargeOrder($uid, $money);

        $qr = $this->getAlipayQr($chargeOrderSn, $money, '充值');

        // 更新qr到订单信息
        ChargeOrdersModel::query()->where([
            'out_trade_no'  =>  $chargeOrderSn,
            'status'        =>  ChargeOrdersModel::STATUS_WAIT
        ])->update([
            'qr_code'   =>  $qr
        ]);

        return [
            'qr'    =>  $qr,
            'orderNo'   =>  $chargeOrderSn,
            'amount'    =>  $money
        ];
    }

    public function getChargeOrder(int $uid, float $money)
    {
        $model = new ChargeOrdersModel();
        $model->uid = $uid;
        $model->out_trade_no = $this->randomChargeOrderSn();
        $model->total_amount = $money;
        $model->expired_at = date("Y-m-d H:i:s", time() + ( 5 * 60 - 5 ));

        $model->saveOrFail();
        return $model->out_trade_no;
    }

    public function randomChargeOrderSn($prefix = 'CG')
    {
        $date = date('Ymd');
        [$microtime, $time] = explode(' ', microtime());
        return sprintf('%s%s%s%s', $prefix, $date, round($microtime * 1000000), Helper::randomString(6));
    }

    private function getAlipayConfig()
    {
        $appid = Cache::get('ALIPAY_APPID');
        $key = Cache::get('ALIPAY_PUBLIC_KEY');
        $pkey = Cache::get('ALIPAY_PRIVATE_KEY');

        if (!$appid || !$key || !$pkey) {
            throw new Error(Code::OPERATE_FAILED, '未配置支付宝支付信息');
        }

        return  array (
            //签名方式,默认为RSA2(RSA2048)
            'sign_type' => "RSA2",
            //支付宝公钥
            'alipay_public_key' => $key,
            //商户私钥
            'merchant_private_key' => $pkey,
            //编码格式
            'charset' => "UTF-8",
            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //应用ID
            'app_id' => $appid,
            //异步通知地址,只有扫码支付预下单可用
            'notify_url' => Config::get('config.API_URL'). '/api/alipayNotify',
            //最大查询重试次数
            'MaxQueryRetry' => "10",
            //查询间隔
            'QueryDuration' => "3"
        );
    }

    /**
     * @throws Error
     * @throws \Exception
     */
    public function getAlipayQr(string $outTradeNo, float $totalAmount, string $subject, string $body = '')
    {
        // 创建请求builder，设置请求参数
        $qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
        $qrPayRequestBuilder->setTotalAmount($totalAmount);
        $qrPayRequestBuilder->setTimeExpress("5m");
        $qrPayRequestBuilder->setSubject($subject);
        $qrPayRequestBuilder->setBody($body);

        // 调用qrPay方法获取当面付应答
        $qrPay = new AlipayTradeService($this->getAlipayConfig());
        $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);

        //	根据状态值进行业务处理
        switch ($qrPayResult->getTradeStatus()){
            case "SUCCESS":
                $response = $qrPayResult->getResponse();
                $qrcode = $response->qr_code;
                break;
            default:
                if(!empty($qrPayResult->getResponse())){
                    throw new Error(Code::OPERATE_FAILED, $qrPayResult->getResponse()->sub_msg ?? '创建订单失败');
                } else {
                    throw new Error(Code::HTTP_FAILED, "不支持的返回状态，创建订单二维码返回异常!!!");
                }
        }
        return $qrcode;
    }

    /**
     * @throws Error
     * @throws \Exception
     */
    public function queryOrder(string $outTradeNo, float $amount): void
    {
        $orderInfo = ChargeOrdersModel::query()->where([
            'out_trade_no' => $outTradeNo,
        ])->firstOrFail();

        if (!$orderInfo) {
            logger()->warning(sprintf('%s 订单不存在', $outTradeNo));
            throw new Error(Code::OPERATE_FAILED, sprintf('%s 订单不存在', $outTradeNo));
        }

        if ($orderInfo->status !== ChargeOrdersModel::STATUS_WAIT) {
            logger()->warning(sprintf('%s 订单状态不为待支付，当前状态：%s', $outTradeNo, $orderInfo->status));
            throw new Error(Code::OPERATE_FAILED, sprintf('%s 订单状态不为待支付，当前状态：%s', $outTradeNo, $orderInfo->status));
        }

        // 创建请求builder，设置请求参数
        $qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
        $qrPayRequestBuilder->setTotalAmount($amount);

        // 调用qrPay方法获取当面付应答
        $qrPay = new AlipayTradeService($this->getAlipayConfig());
        $qrPayResult = $qrPay->queryTradeResult($qrPayRequestBuilder);

        //	根据状态值进行业务处理
        switch ($qrPayResult->getTradeStatus()){
            case "SUCCESS":
                $response = $qrPayResult->getResponse();
                // 对比金额
                if ($response->total_amount != $amount) {
                    logger()->warning(sprintf('查询到订单金额：%s 与下单金额%s 不符', $response->total_amount, $amount));
                    throw new Error(Code::OPERATE_FAILED, sprintf('查询到订单金额：%s 与下单金额%s 不符', $response->total_amount, $amount));
                }
                $this->chargeSuccess($outTradeNo, $response->trade_no);
                // 支付成功 修改订单状态以及后续操作
                break;
            default:
                if(!empty($qrPayResult->getResponse())){
                    throw new Error(Code::OPERATE_FAILED, $qrPayResult->getResponse()->sub_msg ?? '支付失败');
                } else {
                    throw new Error(Code::HTTP_FAILED, "支付失败!!!");
                }
        }
    }

    /**
     * @param string $outTradeNo 系统订单号
     * @param string $tradeNo 支付宝交易订单号
     * @return void
     */
    public function chargeSuccess(string $outTradeNo, string $tradeNo): void
    {
        DB::beginTransaction();
        try {
            // 1. 修改订单状态
            $orderInfo = ChargeOrdersModel::query()->where('out_trade_no', $outTradeNo)->firstOrFail();
            $orderInfo->trade_no = $tradeNo;
            $orderInfo->status = ChargeOrdersModel::STATUS_PAYED;
            $orderInfo->saveOrFail();

            // 2. 金币到账
            $coinType = new CoinChangeType();
            $coinType->amount = $orderInfo->total_amount;
            $coinType->channel = CoinFlowLogsModel::CHANNEL_CHARGE;
            $coinType->orderNo = $tradeNo;
            $coinType->desc = '支付宝充值';
            (new UserCoinRepository())->coinIn($orderInfo->uid, $coinType);

            // 3. 是否有上线 上线分成积分点数
            $userInfo = (new UserRepository())->fetchUserInfo($orderInfo->uid);

            if ($userInfo->invite_uid > 0) {
                $inviter = (new UserRepository())->fetchUserInfo($userInfo->invite_uid);
                $rewardPoint = bcmul($orderInfo->total_amount, ($userInfo->reward_rate / 100), 2);
                if ($inviter && $userInfo->reward_rate && $rewardPoint > 0) {
                    $pointType = new PointChangeType();
                    $pointType->amount = $rewardPoint;
                    $pointType->channel = PointFlowLogsModel::CHANNEL_CHARGE_REWARD;
                    $pointType->desc = '下级充值奖励';
                    (new UserCoinRepository())->pointIn($userInfo->invite_uid, $orderInfo->uid, $pointType);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning(sprintf("订单：%s 状态更新失败，错误信息：%s", $outTradeNo, $e->getMessage()));
        }
    }

    /**
     * 登录密码加密
     * @param string $password
     * @param string $salt
     * @return string
     */
    private function encryptPw(string $password, string $salt = ''): string
    {
        return md5(implode('@@encrypt@@',[
            $password,
            $salt
        ]));
    }

    public function myPromotionList(int $uid)
    {
        $data = [];
        $mySubUsers = $this->mySubUsers($uid);

        if (!$mySubUsers) {
            return [];
        }

        $searchParams = new SearchParams();
        $searchParams->pagination = false;
        $searchParams->pageSize = 0;
        $searchParams->otherCondition = [
            'uidList'   =>  array_column($mySubUsers, 'uid')
        ];

        $userCoinList = (new UserCoinRepository())->pagingList($searchParams);
        $userCoinList = array_column($userCoinList, null, 'uid');

        foreach ($mySubUsers as $user) {
            $data[] = [
                'qq'            =>  $user->qq,
                'last_login_at' =>  $user->last_login_at,
                'account'       =>  Helper::mosaicString($user->account),
                'nickname'      =>  $user->nickname,
                'avatar'        =>  $user->avatar_url,
                'registerDate'  =>  date("Y-m-d H:i", strtotime($user->created_at)),
                'coin'          => $userCoinList[ $user->uid]['coin'] ?? 0,
                'point'         => $userCoinList[ $user->uid]['point'] ?? 0,
                'amount'        =>  PointFlowLogsModel::query()
                    ->where('form_uid', $user->uid)
                    ->sum('amount')
            ];
        }
        return $data;
    }

    public function myPromotionUrl(int $uid)
    {
        $url = Config::get('config.WEBSITE_URL');
        return sprintf('%s/#/login?inviteUid=%s', $url, $uid);
    }

    public function mySubUsers(int $uid)
    {
        $searchParams = new SearchParams();
        $searchParams->where = [
            'invite_uid'   =>  $uid
        ];
        $searchParams->pagination = false;
        $searchParams->pageSize = 0;

        return (new UserRepository())->pagingList($searchParams);
    }

    public function point2coin(int $uid, float $consumePoint): void
    {
        // point2coinRate百分比 积分：金币
        $translateRate = Cache::get('point2coinRate');
        if (!$translateRate) {
            throw new Error(Code::OPERATE_FAILED, '未配置兑换比例');
        }

        $gainCoin = bcmul($consumePoint, $translateRate / 100, 2);

        DB::beginTransaction();
        try {
            // 积分减少
            $pointType = new PointChangeType();
            $pointType->amount = $consumePoint;
            $pointType->channel = PointFlowLogsModel::CHANNEL_POINT2COIN;
            $pointType->desc = sprintf('积分转换为金币，比例百分之%s', $translateRate);
            (new UserCoinRepository())->pointOut($uid, $uid, $pointType);

            // 金币增加
            $coinType = new CoinChangeType();
            $coinType->amount = $gainCoin;
            $coinType->channel = CoinFlowLogsModel::CHANNEL_POINT2COIN;
            $coinType->desc = sprintf('积分转换为金币，比例百分之%s', $translateRate);
            (new UserCoinRepository())->coinIn($uid, $coinType);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning(sprintf("用户：%s 转换金币 %s 出错，错误信息：%s", $uid, $consumePoint, $e->getMessage()));
            throw new Error(Code::OPERATE_FAILED, $e->getMessage());
        }
    }

    /**
     * @throws Error
     */
    public function createWechatPay(int $uid, float $money, string $orderNo): bool
    {
        DB::beginTransaction();
        try {
            $userInfo = (new UserRepository())->fetchUserInfo($uid);

            $wechatPayAutoFinish = Cache::get('WECHAT_PAY_AUTO_COMPLETE') == 1;

            try {
                $orderInfo = (new WechatChargeOrdersRepository())->add($uid, $userInfo->nickname, $money, $orderNo);
            } catch (QueryException $e) {
                throw new Error(Code::OPERATE_FAILED, '该转账单号已提交过充值，请勿重复提交！');
            }

            // 是否自动到账
            $notNeedAudit = false;

            // 是否开启微信自动到账
            if ($wechatPayAutoFinish) {
                // 超级信任用户充值任意金额，直接充值成功，不需要后台审核！
                if ($userInfo->trust_level === WechatChargeOrdersModel::TRUST_LEVE_TRUST) {
                    $notNeedAudit = true;
                } else if ($userInfo->trust_level === WechatChargeOrdersModel::TRUST_LEVE_NORMAL) {
                    // 一般信任用户充值100元以内（含）的金额，直接充值成功，不需要后台审核，100元以上（不含）则生成一笔待审核充值订单，管理员后台确认后充值成功！
                    if ($money <= 100) {
                        $notNeedAudit = true;
                    }
                } else {
                    // 非信任用户充值任意金额都需要后台确认，管理员后台确认后充值成功！
                }
            }

            if ($notNeedAudit) {
                $this->wechatPayChargeSuccess($userInfo, $orderInfo, '微信充值自动到账');
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning(sprintf("用户：%s 审核微信支付订单：%s 出错，错误信息：%s", $uid, $orderNo, $e->getMessage()));
            throw new Error(Code::OPERATE_FAILED, $e->getMessage());
        }

        return $notNeedAudit;
    }

    /**
     * 手动变更余额
     * @param int $uid
     * @param int $operateUid
     * @param float $amount
     * @param int $type 1增加 | 2减少
     * @throws Error
     * @throws \Throwable
     */
    public function manualChangeCoinSuccess(int $uid, int $operateUid, float $amount, int $type): void
    {
        DB::beginTransaction();
        try {
            // 1. 金币到账与日志
            $coinType = new CoinChangeType();
            $coinType->amount = $amount;
            $coinType->channel = CoinFlowLogsModel::CHANNEL_MANUAL;
            $coinType->desc = sprintf('管理员【%s】后台操作', $operateUid);
            if ($type == 1) {
                (new UserCoinRepository())->coinIn($uid, $coinType);
            } else {
                (new UserCoinRepository())->coinOut($uid, $coinType);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning(sprintf("手动变更余额，错误信息：%s", $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 微信充值成功
     * @param $userInfo Model 充值用户信息
     * @param $orderInfo Model 充值订单信息
     * @throws Error
     */
    public function wechatPayChargeSuccess(Model $userInfo,  Model $orderInfo, string $desc = ''): void
    {
        // 1. 审核通过
        $orderInfo->status = 1; // 通过
        $orderInfo->trust_level = $userInfo->trust_level;
        $orderInfo->audited_at = date('Y-m-d H:i:s');
        $orderInfo->audit_uid = 0; // 系统审核
        $orderInfo->saveOrFail();

        // 2. 金币到账与日志
        $coinType = new CoinChangeType();
        $coinType->amount = $orderInfo->total_amount;
        $coinType->channel = CoinFlowLogsModel::CHANNEL_CHARGE;
        $coinType->desc = $desc;
        $coinType->orderNo = $orderInfo->out_trade_no;
        (new UserCoinRepository())->coinIn($orderInfo->uid, $coinType);

        // 3. 是否有上线 上线分成积分点数
        if ($userInfo->invite_uid > 0) {
            $inviter = (new UserRepository())->fetchUserInfo($userInfo->invite_uid);
            if ($inviter && $userInfo->reward_rate) {
                $rewardPoint = bcmul($orderInfo->total_amount, ($userInfo->reward_rate / 100), 2);
                if ($rewardPoint > 0) {
                    $pointType = new PointChangeType();
                    $pointType->amount = $rewardPoint;
                    $pointType->channel = PointFlowLogsModel::CHANNEL_CHARGE_REWARD;
                    $pointType->desc = '下级充值奖励';
                    (new UserCoinRepository())->pointIn($userInfo->invite_uid, $userInfo->uid, $pointType);
                }
            }
        }
    }

    public function changeUserPassword(int $uid, string $newPassword): void
    {
        $repo = new UserRepository();

        $model = $repo->fetchUserInfo($uid);
        $salt = Helper::randomString(8);
        $encryptPasswd = $this->encryptPw($newPassword, $salt);
        $model->salt = $salt;
        $model->pw_encrypt = $encryptPasswd;
        $model->saveOrFail();

        // 更新token
        $repo->updateTokenByUid($uid);
    }

    public function deleteUser(int $uid): void
    {
        $repo = new UserRepository();

        $model = $repo->fetchUserInfo($uid);
        if ($model->role == 'ADMIN') {
            throw new Error(Code::OPERATE_FAILED, '管理员账号无法删除');
        }
        $model->is_delete = 1;

        $model->saveOrFail();
    }

    /**
     * 重置用户密码
     * @param string $uid
     * @return string
     * @throws Error
     */
    public function resetPasswd(string $uid, string $password): string
    {
        // 重置用户密码
        $salt = Helper::randomString(5);
        $encryptPw = $this->encryptPw($password, $salt);
        $ret = (new UserRepository())->changeUserPasswd($uid, $encryptPw, $salt);
        if (!$ret) {
            throw new Error(Code::OPERATE_FAILED, '修改用户密码失败，请稍后重试');
        }

        // 更新token
        (new UserRepository())->updateTokenByUid($uid);
        return $password;
    }

    /**
     * 重置邮件密码url
     * @param $email
     * @return string
     * @throws Error
     */
    public function getResetPwByEmailUrl($email): string
    {
        $this->checkEmailResetAble($email);

        // 组装一下加密code拼接到url
        $handler = new EmailResetType([
            'email'     =>  $email,
            'time'      =>  time(),
            'randomStr' =>  Helper::randomString(8) // 随机字符串必须
        ]);

        // 添加缓存 缓存好data，等到接收参数时需要做对比
        Cache::put(sprintf(self::EMAIL_RESET_KEY, $email), $handler->toArray());
        $registerStr = Helper::encryptByPublicKey(json_encode($handler->toArray()));
        return Config::get('config.WEBSITE_URL') . '/#/emailReset/' . base64_encode($registerStr);
    }

    /**
     * 邮箱是否可以正常重置密码
     * @throws Error
     */
    public function checkEmailResetAble($email)
    {
        // 传送过来的邮箱格式是否正确
        if (!Helper::isEmail($email)) {
            throw new Error(Code::OPERATE_FAILED, '邮箱格式不正确');
        }

        // 传送过来的邮箱是否已经存在
        if (!(new UserRepository())->isEmailExist($email)) {
            throw new Error(Code::OPERATE_FAILED, "重置邮箱密码：邮箱 %s 不存在", $email);
        }
    }
}
