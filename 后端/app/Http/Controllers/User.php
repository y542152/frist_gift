<?php


namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Exceptions\FrequentActionError;
use App\Mail\SendResetMail;
use App\Models\ChargeOrdersModel;
use App\Models\CoinFlowLogsModel;
use App\Models\LoginLogsModel;
use App\Models\PointFlowLogsModel;
use App\Models\UserModel;
use App\Models\WechatChargeOrdersModel;
use App\Repositories\ChargeOrdersRepository;
use App\Repositories\CoinFlowLogsRepository;
use App\Repositories\LoginLogsRepository;
use App\Repositories\PointFlowLogsRepository;
use App\Repositories\UserCoinRepository;
use App\Repositories\UserRepository;
use App\Repositories\WechatChargeOrdersRepository;
use App\Services\UserService;
use App\Supports\Code;
use App\Supports\Helper;
use App\Type\EmailResetType;
use App\Type\Models\SearchParams;
use App\Type\PagingListType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class User extends BaseController
{
    public const EMAIL_RESET_TIME = 3600 * 2; // 邮件有效期 邮箱重置密码

    public function handle(): array
    {
        try {
            parent::_init();

            $user = (new UserRepository())->fetchUserInfo($this->getUid());
            $coinInfo = (new UserCoinRepository())->initCoinPoint($this->getUid());

            $loginIfo = [];
            $lastLogin = LoginLogsModel::query()->where('uid', $this->getUid())->limit(2)->orderByDesc('last_login_at')->get()->toArray();
            if (count($lastLogin) == 2) {
                $loginIfo = $lastLogin[1];
            } else if (count($lastLogin) == 1) {
                $loginIfo = $lastLogin[0];
            }

            if (isset($loginIfo['ip'])) {
                $loginIfo['ip'] = long2ip($loginIfo['ip']);
            }

            return $this->returnData([
                'userId' => $user->uid,
                'nickname' => $user->nickname,
                'roles' => [$user->role],
                'avatar' => $user->avatar_url,
                'perms' => [],
                'reward_rate' => $user->reward_rate,
                'profit_rate' => $user->profit_rate,
                'is_xinfeng' => $user->is_xinfeng,
                'is_disabled' => $user->is_disabled,
                'coin' => $coinInfo->coin,
                'point' => $coinInfo->point,
                'point2coinRate' => Cache::get('point2coinRate'),
                'trust_levelStr' => WechatChargeOrdersModel::TRUST_LEVE_MAP[$user->trust_level] ?? '非信任',
                'trust_level' => $user->trust_level,
                'NOTICE1' => Cache::get('NOTICE1'),
                'NOTICE2' => Cache::get('NOTICE2'),
                'NOTICE3' => Cache::get('NOTICE3'),
                'WECHAT_PAY_QR' => Cache::get('WECHAT_PAY_QR'),
                'loginInfo' => $loginIfo
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function charge(float $amount): array
    {
        try {
            parent::_init();

            if ($amount >= 100000000) {
                throw new Error(Code::REQUEST_PARAM_ERROR, '总金额超过100000000');
            }

            $ret = (new UserService())->charge($this->getUid(), $amount);
            return $this->returnData($ret);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function chargeResult(string $orderNo, float $amount): array
    {
        try {
            parent::_init();

            if (
                !$orderNo ||
                !$amount
            ) {
                throw new Error(Code::REQUEST_PARAM_ERROR);
            }

            $lock = Cache::lock('aliNotifyUrl-handle-' . $orderNo, 10);
            if ($lock->get()) {
                (new UserService())->queryOrder($orderNo, $amount);
                $lock->release();
                return $this->returnData(true);
            } else {
                throw new FrequentActionError(Code::FREQUENT_OPERATION);
            }
        } catch (FrequentActionError $e) {
            return $this->handleException($e);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->returnData($e->getMessage());
        }
    }

    public function promotionList(): array
    {
        try {
            parent::_init();

            return $this->returnData((new UserService())->myPromotionList($this->getUid()));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function promotionUrl(): array
    {
        try {
            parent::_init();

            return $this->returnData((new UserService())->myPromotionUrl($this->getUid()));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function users(): array
    {
        $this->paramRules = [
            'searchWord' => 'nullable|string|max:100',
            'uid' => 'nullable|int',
            'page' => 'int|min:1|max:100',
            'pageSize' => 'int|min:1|max:50',
            'isDelete' => 'nullable|int|in:0,1',
            'is_disabled' => 'nullable|int|in:0,1'
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';
            $params->fields = [
                'uid',
                'account',
                'nickname',
                'qq',
                'profit_rate',
                'invite_uid',
                'created_at',
                'login_count',
                'reward_rate',
                'profit_rate',
                'trust_level',
                'is_xinfeng',
                'is_disabled',
                'is_delete',
                'qqemail'
            ];
            if (isset($this->requestData['isDelete'])) {
                $params->where['is_delete'] = $this->requestData['isDelete'];
            }

            if (isset($this->requestData['is_disabled'])) {
                $params->where['is_disabled'] = $this->requestData['is_disabled'];
            }

            if (isset($this->requestData['uid'])) {
                $params->where['uid'] = $this->requestData['uid'];
            }

            if (isset($this->requestData['searchWord'])) {
                $params->otherCondition['qqPhone'] = $this->requestData['searchWord'];
            }

            $pagination = (new UserRepository())->pagingList($params);

            $list = (new PagingListType($pagination))->toArray();

            $searchParams = new SearchParams();
            $searchParams->pagination = false;
            $searchParams->pageSize = 0;
            $searchParams->otherCondition['uidList'] = array_column($list['items'], 'uid');

            $userCoinList = (new UserCoinRepository())->pagingList($searchParams);
            $userCoinList = array_column($userCoinList, null, 'uid');

            foreach ($list['items'] as &$item) {
                $item['coin'] = $userCoinList[$item['uid']]['coin'] ?? 0;
                $item['point'] = $userCoinList[$item['uid']]['point'] ?? 0;
                $item['is_disabledStr'] = $item['is_disabled'] == 1 ? '是' : '否';
                $item['is_deleteStr'] = $item['is_delete'] == 1 ? '是' : '否';
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function loginLogs(): array
    {
        $this->paramRules = [
            'page' => 'int|min:1|max:100',
            'pageSize' => 'int|min:1|max:50',
            'uid' => 'nullable|int|min:1',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';
            $params->fields = [
                'uid',
                'account',
                'last_login_at',
                'ip'
            ];

            if (!empty($this->requestData['uid'])) {
                $params->where['uid'] = $this->requestData['uid'];
            }

            $pagination = (new LoginLogsRepository())->pagingList($params);

            $list = (new PagingListType($pagination))->toArray();

            foreach ($list['items'] as &$item) {
                $item['ip'] = long2ip($item['ip']);
                $item['account'] = Helper::mosaicString($item['account']);
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }


    public function update(): array
    {
        $this->paramRules = [
            'uid' => 'required|int',
            'reward_rate' => 'required|numeric|min:0|max:100',
            'profit_rate' => 'required|numeric|min:0|max:100',
            'is_xinfeng' => 'required|int|in:0,1',
            'is_disabled' => 'required|int|in:0,1',
            'trust_level' => 'required|int|in:0,1,2',
            'invite_uid' => 'int|min:0',
        ];

        try {
            parent::_init();

            $model = UserModel::query()->where('uid', $this->requestData['uid'])->firstOrFail();
            $model->reward_rate = $this->requestData['reward_rate'];
            $model->profit_rate = $this->requestData['profit_rate'];
            $model->is_disabled = $this->requestData['is_disabled'];
            $model->is_xinfeng = $this->requestData['is_xinfeng'];
            $model->trust_level = $this->requestData['trust_level'];

            $inviteUid = $this->requestData['invite_uid'] ?? 0;
            if ($inviteUid) {
                $model->invite_uid = $inviteUid;
            }
            $model->saveOrFail();

            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function updateCoin(): array
    {
        $this->paramRules = [
            'uid' => 'required|int',
            'type' => 'required|int|in:1,2',
            'coin' => 'required|numeric|min:1',
        ];

        try {
            parent::_init();

            $uid = $this->requestData['uid'];

            $lock = Cache::lock('updateCoin-handle-' . $uid, 10);
            if ($lock->get()) {
                $amount = round($this->requestData['coin'], 2);
                $type = $this->requestData['type'];
                (new UserService())->manualChangeCoinSuccess($uid, $this->getUid(), $amount, $type);

                $lock->release();
                return $this->returnData(true);
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


    public function updatePassword(): array
    {
        $this->paramRules = [
            'uid' => 'required|int',
            'password' => 'required|string',
        ];

        try {
            parent::_init();

            $uid = $this->requestData['uid'];
            $password = $this->requestData['password'];
            (new UserService())->changeUserPassword($uid, $password);

            return $this->returnData(true);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }

    public function deleteUser(int $uid): array
    {
        try {
            parent::_init();

            (new UserService())->deleteUser($uid);
            return $this->returnData(true);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }

    public function createUser(): array
    {
        $this->paramRules = [
            'account' => 'required|string|max:20',
            'qq' => 'required|string|max:50',
            'password' => 'required|string|min:6|max:30',
            'coin' => 'nullable|numeric|min:0',
        ];

        try {
            parent::_init();
            $phone = $this->requestData['account'];
            $coin = $this->requestData['coin'] ?? 0;

            if (!Helper::isPhoneNumber($phone)) {
                throw new Error(Code::REQUEST_PARAM_ERROR, '请填写有效的手机号码');
            }

            $qq = $this->requestData['qq'];
            $pw = $this->requestData['password'];

            $lock = Cache::lock('register-start-' . $phone, 10);

            if ($lock->get()) {
                (new UserService())->registerByAccount($phone, $qq, $pw);

                $user = UserModel::query()->where('account', $phone)->get()->firstOrFail();
                if ($coin > 0) {
                    (new UserService())->manualChangeCoinSuccess($user->uid, $this->getUid(), $coin, 1);
                }

                $lock->release();
                return $this->returnData(true);
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

    public function updateSetting(): array
    {
        $this->paramRules = [
            'point2coinRate' => 'required|int|min:1|max:100',
            'WECHAT_PAY_AUTO_COMPLETE' => 'required|int|in:1,0',
            'WECHAT_PAY_QR' => 'required|string',
            'ALIPAY_APPID' => 'required|string',
            'ALIPAY_PUBLIC_KEY' => 'required|string',
            'ALIPAY_PRIVATE_KEY' => 'required|string',
            'GIFT_API_URL' => 'required|string',
            'GIFT_API_ACCOUNT' => 'required|string',
            'GIFT_API_PW' => 'required|string',
            'KF_QQ' => 'required|string',
            'KF_WECHAT_QR' => 'required|string',
            'NOTICE1' => 'required|string',
            'NOTICE2' => 'required|string',
            'NOTICE3' => 'required|string',
        ];

        try {
            parent::_init();

            foreach ($this->requestData as $key => $item) {
                Cache::put($key, $item);
            }
            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function setting(): array
    {
        try {
            parent::_init();
            return $this->returnData([
                'point2coinRate' => Cache::get('point2coinRate'),
                'WECHAT_PAY_QR' => Cache::get('WECHAT_PAY_QR'),
                'ALIPAY_APPID' => Cache::get('ALIPAY_APPID'),
                'ALIPAY_PUBLIC_KEY' => Cache::get('ALIPAY_PUBLIC_KEY'),
                'ALIPAY_PRIVATE_KEY' => Cache::get('ALIPAY_PRIVATE_KEY'),
                'GIFT_API_URL' => Cache::get('GIFT_API_URL'),
                'GIFT_API_ACCOUNT' => Cache::get('GIFT_API_ACCOUNT'),
                'GIFT_API_PW' => Cache::get('GIFT_API_PW'),
                'WECHAT_PAY_AUTO_COMPLETE' => Cache::get('WECHAT_PAY_AUTO_COMPLETE'),
                'KF_QQ' => Cache::get('KF_QQ'),
                'KF_WECHAT_QR' => Cache::get('KF_WECHAT_QR'),
                'NOTICE1' => Cache::get('NOTICE1'),
                'NOTICE2' => Cache::get('NOTICE2'),
                'NOTICE3' => Cache::get('NOTICE3'),
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function settingInfo(): array
    {
        try {
            parent::_init();
            return $this->returnData([
                'KF_QQ' => Cache::get('KF_QQ'),
                'KF_WECHAT_QR' => Cache::get('KF_WECHAT_QR')
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function point2coin(): array
    {
        $this->paramRules = [
            'consumePoint' => 'required|numeric|min:1', // 需要兑换的积分数量
        ];

        try {
            parent::_init();
            (new UserService())->point2coin($this->getUid(), round($this->requestData['consumePoint'], 2));
            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function setPoint2coinRate(): array
    {
        $this->paramRules = [
            'rate' => 'required|int|min:1|max:100',
        ];

        try {
            parent::_init();

            Cache::put('point2coinRate', $this->requestData['rate']);
            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function menu(): array
    {
        try {
            parent::_init();

            $user = (new UserRepository())->fetchUserInfo($this->getUid());

            $prefix = '/admin';

            if ($user->role == 'ADMIN') {
                $menu = [
                    [
                        "path" => $prefix . "/send",
                        "component" => "Layout",
                        "name" => "address",
                        "perm" => "sys:user:add",
                        "meta" => [
                            "title" => "发货地址"
                        ],
                        "children" => [
                            [
                                "path" => "address",
                                "component" => "gift/address",
                                "name" => "address",
                                "perm" => "sys:user:add",
                                "meta" => [
                                    "title" => "发货地址",
                                    "icon" => "el-icon-Place"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/sendXingfeng",
                        "component" => "Layout",
                        "name" => "sendXingfeng",
                        "meta" => [
                            "title" => "发封信"
                        ],
                        "children" => [
                            [
                                "path" => "/sendXingfeng",
                                "component" => "gift/sendXingfeng",
                                "name" => "sendXingfeng",
                                "meta" => [
                                    "title" => "发封信",
                                    "icon" => "el-icon-Present"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/send",
                        "component" => "Layout",
                        "name" => "send",
                        "meta" => [
                            "title" => "发礼品"
                        ],
                        "children" => [
                            [
                                "path" => "/gift",
                                "component" => "gift/send",
                                "name" => "send",
                                "meta" => [
                                    "title" => "发礼品",
                                    "icon" => "el-icon-Present"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/gift",
                        "component" => "Layout",
                        "name" => "sendLogs",
                        "meta" => [
                            "title" => "已发记录"
                        ],
                        "children" => [
                            [
                                "path" => "sendLogs",
                                "component" => "gift/sendLogs",
                                "name" => "sendLogs",
                                "meta" => [
                                    "title" => "已发记录",
                                    "icon" => "el-icon-Notebook"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/promotion",
                        "component" => "Layout",
                        "name" => "promotion",
                        "meta" => [
                            "title" => "我的推广"
                        ],
                        "children" => [
                            [
                                "path" => "promotion",
                                "component" => "user/promotion",
                                "name" => "promotion",
                                "meta" => [
                                    "title" => "我的推广",
                                    "icon" => "el-icon-Promotion"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/users",
                        "component" => "Layout",
                        "name" => "users",
                        "meta" => [
                            "title" => "用户管理"
                        ],
                        "children" => [
                            [
                                "path" => "users",
                                "component" => "user/list",
                                "name" => "users",
                                "meta" => [
                                    "title" => "用户列表",
                                    "icon" => "el-icon-User"
                                ]
                            ],
                            [
                                "path" => "loginLogs",
                                "component" => "user/loginLogs",
                                "name" => "loginLogs",
                                "meta" => [
                                    "title" => "用户登录记录",
                                    "icon" => "el-icon-User"
                                ]
                            ],
                            [
                                "path" => "chargeOrders",
                                "component" => "user/chargeOrders",
                                "name" => "chargeOrders",
                                "meta" => [
                                    "title" => "用户充值订单",
                                    "icon" => "el-icon-User"
                                ]
                            ],
                            [
                                "path" => "coinLogs",
                                "component" => "user/coinLogs",
                                "name" => "coinLogs",
                                "meta" => [
                                    "title" => "用户金额流水",
                                    "icon" => "el-icon-User"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => $prefix . "/setting",
                        "component" => "Layout",
                        "name" => "userList",
                        "meta" => [
                            "title" => "设置"
                        ],
                        "children" => [
                            [
                                "path" => "list",
                                "component" => "setting",
                                "name" => "setting",
                                "meta" => [
                                    "title" => "设置",
                                    "icon" => "el-icon-Setting"
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $menu = [
                    [
                        "path" => "/send",
                        "component" => "Layout",
                        "name" => "发货地址管理",
                        "meta" => [
                            "title" => "发货地址"
                        ],
                        "children" => [
                            [
                                "path" => "/send/address",
                                "component" => "gift/address",
                                "name" => "发货地址管理",
                                "meta" => [
                                    "title" => "发货地址",
                                    "icon" => "el-icon-Place"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => "/sendXingfeng",
                        "component" => "Layout",
                        "name" => "sendXingfeng",
                        "meta" => [
                            "title" => "发封信"
                        ],
                        "children" => [
                            [
                                "path" => "/sendXingfeng",
                                "component" => "gift/sendXingfeng",
                                "name" => "sendXingfeng",
                                "meta" => [
                                    "title" => "发封信",
                                    "icon" => "el-icon-Present"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => "/send",
                        "component" => "Layout",
                        "name" => "send",
                        "meta" => [
                            "title" => "发礼品"
                        ],
                        "children" => [
                            [
                                "path" => "/gift",
                                "component" => "gift/send",
                                "name" => "send",
                                "meta" => [
                                    "title" => "发礼品",
                                    "icon" => "el-icon-Present"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => "/gift",
                        "component" => "Layout",
                        "name" => "sendLogs",
                        "meta" => [
                            "title" => "已发记录"
                        ],
                        "children" => [
                            [
                                "path" => "sendLogs",
                                "component" => "gift/sendLogs",
                                "name" => "sendLogs",
                                "meta" => [
                                    "title" => "已发记录",
                                    "icon" => "el-icon-Notebook"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => "/wechatPay",
                        "component" => "Layout",
                        "name" => "wechatPay",
                        "meta" => [
                            "title" => "我的充值申请"
                        ],
                        "children" => [
                            [
                                "path" => "audit",
                                "component" => "wechatPay/audit",
                                "name" => "audit",
                                "meta" => [
                                    "title" => "我的充值申请",
                                    "icon" => "el-icon-Setting"
                                ]
                            ]
                        ]
                    ],
                    [
                        "path" => "/promotion",
                        "component" => "Layout",
                        "name" => "promotion",
                        "meta" => [
                            "title" => "我的推广"
                        ],
                        "children" => [
                            [
                                "path" => "promotion",
                                "component" => "user/promotion",
                                "name" => "promotion",
                                "meta" => [
                                    "title" => "我的推广",
                                    "icon" => "el-icon-Promotion"
                                ]
                            ]
                        ]
                    ]
                ];
            }

            return $this->returnData($menu);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }

    }

    public function coinLogs(): array
    {
        $this->paramRules = [
            'page' => 'int|min:1',
            'pageSize' => 'int|min:1|max:50',
            'type' => 'string|in:in,out',
            'channel' => 'string|in:charge,ptc,buy',
            'uid' => 'nullable|int|min:1',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $user = (new UserRepository())->fetchUserInfo($this->getUid());
            $isAdmin = $user->role == 'ADMIN';
            if (!$isAdmin) {
                $params->where['uid'] = $this->getUid();
            }

            if (!empty($this->requestData['uid'])) {
                $params->where['uid'] = $this->requestData['uid'];
            }

            $params->fields = ['type', 'channel', 'desc', 'created_at', 'amount', 'uid', 'out_trade_no', 'old_amount', 'new_amount'];
            $params->sortDesc = 'desc';

            if (isset($this->requestData['type'])) {
                $params->where['type'] = $this->requestData['type'];
            }

            if (isset($this->requestData['channel'])) {
                $params->where['channel'] = $this->requestData['channel'];
            }

            $ret = (new CoinFlowLogsRepository())->pagingList($params);

            $list = (new PagingListType($ret))->toArray();

            foreach ($list['items'] as &$item) {
                $item['typeStr'] = CoinFlowLogsModel::TYPE_MAP[$item->type] ?? '';
                $item['channelStr'] = CoinFlowLogsModel::CHANNEL_MAP[$item->channel] ?? '';
                if ($isAdmin) {
                    $user = (new UserRepository())->fetchUserInfo($item['uid'], 'nickname');
                    $item['nickname'] = $user->nickname;
                }
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function pointLogs(): array
    {
        $this->paramRules = [
            'page' => 'int|min:1',
            'pageSize' => 'int|min:1|max:50',
            'type' => 'nullable|string|in:in,out,',
            'channel' => 'nullable|string|in:cr,ptc,',
            'uid' => 'int|min:1',
            'getOwn' => 'nullable|bool',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);

            $user = (new UserRepository())->fetchUserInfo($this->getUid());
            $isAdmin = $user->role == 'ADMIN';
            if (!$isAdmin) {
                // 非管理员 查询自己数据
                $params->where['uid'] = $this->getUid();
            } else {
                // 管理员 查询对应uid
                if (!empty($this->requestData['uid'])) {
                    $params->where['uid'] = $this->requestData['uid'];
                }
                // 管理员 查询自己数据
                if (isset($this->requestData['getOwn']) && $this->requestData['getOwn']) {
                    $params->where['uid'] = $this->getUid();
                    ;
                }
            }

            $params->fields = ['type', 'channel', 'desc', 'created_at', 'amount', 'uid', 'out_trade_no', 'old_amount', 'new_amount'];
            $params->sortDesc = 'desc';

            if (isset($this->requestData['type'])) {
                $params->where['type'] = $this->requestData['type'];
            }

            if (isset($this->requestData['channel'])) {
                $params->where['channel'] = $this->requestData['channel'];
            }

            $ret = (new PointFlowLogsRepository())->pagingList($params);

            $list = (new PagingListType($ret))->toArray();

            foreach ($list['items'] as &$item) {
                $item['typeStr'] = PointFlowLogsModel::TYPE_MAP[$item->type] ?? '';
                $item['channelStr'] = PointFlowLogsModel::CHANNEL_MAP[$item->channel] ?? '';
                if ($isAdmin) {
                    $user = (new UserRepository())->fetchUserInfo($item['uid'], 'nickname');
                    $item['nickname'] = $user->nickname;
                }
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function wechatPayOrders(): array
    {
        $this->paramRules = [
            'page' => 'int|min:1',
            'pageSize' => 'int|min:1|max:50',
            'uid' => 'nullable|int|min:1',
            'status' => 'nullable|int|in:0,1,2',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';
            $params->fields = ['out_trade_no', 'total_amount', 'trust_level', 'status', 'uid', 'created_at'];

            $user = (new UserRepository())->fetchUserInfo($this->getUid());
            if ($user->role != 'ADMIN') {
                $params->where['uid'] = $this->getUid();
            }

            if (!empty($this->requestData['uid'])) {
                $params->where['uid'] = $this->requestData['uid'];
            }

            if (isset($this->requestData['status'])) {
                $params->where['status'] = $this->requestData['status'];
            }

            $ret = (new WechatChargeOrdersRepository())->pagingList($params);

            $list = (new PagingListType($ret))->toArray();
            foreach ($list['items'] as &$item) {
                $item['trust_levelStr'] = WechatChargeOrdersModel::TRUST_LEVE_MAP[$item['trust_level']] ?? '非信任';
                $item['statusStr'] = WechatChargeOrdersModel::STATUS_MAP[$item['status']] ?? 'NA';
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function alipayOrders(): array
    {
        $this->paramRules = [
            'page' => 'int|min:1',
            'uid' => 'nullable|int|min:1',
            'pageSize' => 'int|min:1|max:50',
        ];

        try {
            parent::_init();

            $params = new SearchParams($this->requestData);
            $params->sortDesc = 'desc';
            $params->fields = ['out_trade_no', 'total_amount', 'status', 'uid', 'created_at'];
            if (!empty($this->requestData['uid'])) {
                $params->where['uid'] = $this->requestData['uid'];
            }

            $ret = (new ChargeOrdersRepository())->pagingList($params);
            $list = (new PagingListType($ret))->toArray();
            foreach ($list['items'] as &$item) {
                $item['statusStr'] = ChargeOrdersModel::STATUS_MAP[$item['status']] ?? 'NA';
            }

            return $this->returnData($list);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function reset(): array
    {
        $this->paramRules = [
            'password' => 'required|string|min:6|max:30',
            'password1' => 'required|string|min:6|max:30',
            'resetStr' => 'required|string',
        ];

        try {
            parent::_init();

            $resetStr = $this->requestData['resetStr'];
            $password = $this->requestData['password'];
            $password1 = $this->requestData['password1'];

            if ($password != $password1) {
                throw new Error(Code::OPERATE_FAILED, '2次输入的密码不一致');
            }

            $service = new UserService();

            $ret = Helper::decryptByPrivateKey(base64_decode($resetStr));

            if (!$ret || !$ret = json_decode($ret, true)) {
                throw new Error(Code::SYSTEM_FAILED, '重置密码解密失败');
            }
            $strHandler = new EmailResetType($ret);

            // 1. 邮箱是否有效
            $service->checkEmailResetAble($strHandler->email);

            $cacheData = Cache::get(sprintf(UserService::EMAIL_RESET_KEY, $strHandler->email));
            if (!$cacheData || !is_array($cacheData)) {
                throw new Error(Code::OPERATE_FAILED, '邮件过期');
            }
            $cacheHandler = new EmailResetType($cacheData);

            // 2. 对比一下缓存和传送过来的参数是否一致、邮件是否过期
            if (
                strtolower($strHandler->email) !== strtolower($cacheHandler->email) ||
                $strHandler->randomStr !== $cacheHandler->randomStr ||
                $cacheHandler->time !== $strHandler->time
            ) {
                throw new Error(Code::OPERATE_FAILED, '邮件信息失效');
            }

            $now = time();
            if ($now - $cacheHandler->time > self::EMAIL_RESET_TIME) {
                throw new Error(Code::OPERATE_FAILED, '邮件过期，请重新发送');
            }

            $userInfo = (new UserRepository())->fetchUserByEmail($strHandler->email);

            // 3. 更新密码
            $service->resetPasswd($userInfo->uid, $password);

            // 4. 清除缓存
            Cache::forget(sprintf(UserService::EMAIL_RESET_KEY, $strHandler->email));

            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function checkAccountExist(): array
    {
        $this->paramRules = [
            'account' => 'required|string',
        ];

        try {
            parent::_init();

            $account = $this->requestData['account'];
            $user = (new UserRepository())->fetchUserByAccount($account);
            if (!$user) {
                throw new Error(Code::RESOURCE_NOT_FOUND, '用户不存在');
            }

            return $this->returnData(true);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function sendResetMail(): array
    {
        $this->paramRules = [
            'email' => 'required|string',
            'account' => 'required|string',
        ];

        try {
            parent::_init();

            $account = $this->requestData['account'];
            $email = $this->requestData['email'];

            $user = (new UserRepository())->fetchUserByAccount($account);

            if (!$user) {
                throw new Error(Code::RESOURCE_NOT_FOUND, '用户名不存在');
            }

            if ($user->qqemail != $email) {
                throw new Error(Code::RESOURCE_NOT_FOUND, '邮箱输入错误或与用户名不匹配');
            }

            $lock = Cache::lock('reset-by-email-handle-' . $email, 10);
            if ($lock->get()) {

                $mail = new SendResetMail();
                $mail->address = $email;
                $mail->subject = '重置密码';
                $mail->view = 'mail.resetPwByEmail';
                $mail->data = [
                    'url' => (new UserService())->getResetPwByEmailUrl($email),
                ];

                Mail::send($mail);

                $failed = Mail::failures();
                if ($failed) {
                    throw new Error(Code::OPERATE_FAILED, '邮件发送失败，请稍后重试');
                }
                $lock->release();
                return $this->returnData(true);

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

    public function createWechatPay(): array
    {
        $this->paramRules = [
            'money' => 'required|numeric|min:1',
            'orderNo' => 'required|string|min:1|max:32',
        ];

        try {
            parent::_init();
            $orderNo = $this->requestData['orderNo'];
            $lock = Cache::lock('wechatpay-handle-' . $orderNo, 10);
            if ($lock->get()) {
                $autoComplete = (new UserService())->createWechatPay($this->getUid(), $this->requestData['money'], $this->requestData['orderNo']);

                $lock->release();
                return $this->returnData($autoComplete);
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

    public function wechatPayPass(string $orderNo): array
    {
        try {
            parent::_init();
            $lock = Cache::lock('wechatpay-handle-' . $orderNo, 10);
            if ($lock->get()) {

                // 审核通过
                $orderInfo = WechatChargeOrdersModel::query()->where([
                    'out_trade_no' => $orderNo
                ])->first();
                if (!$orderInfo || $orderInfo->status != WechatChargeOrdersModel::STATUS_WAIT) {
                    throw new Error(Code::STATUS_NOT_MATCH, '订单不存在或状态有误');
                }

                $user = (new UserRepository())->fetchUserInfo($orderInfo->uid);

                DB::beginTransaction();
                try {
                    (new UserService())->wechatPayChargeSuccess($user, $orderInfo, '微信充值审核通过');
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    logger()->warning(sprintf("微信充值审核失败:%s，错误信息: %s", $orderNo, $e->getMessage()));
                    throw new $e;
                }
                $lock->release();
                return $this->returnData(true);

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

    public function wechatPayReject(string $orderNo): array
    {
        try {
            parent::_init();
            $lock = Cache::lock('wechatpay-handle-' . $orderNo, 10);
            if ($lock->get()) {

                // 审核拒绝
                $orderInfo = WechatChargeOrdersModel::query()->where([
                    'out_trade_no' => $orderNo
                ])->first();
                if (!$orderInfo || $orderInfo->status != WechatChargeOrdersModel::STATUS_WAIT) {
                    throw new Error(Code::STATUS_NOT_MATCH, '订单不存在或状态有误');
                }

                $orderInfo->status = WechatChargeOrdersModel::STATUS_REJECTED;
                $orderInfo->saveOrFail();

                $lock->release();
                return $this->returnData(true);

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

    public function updateAvatar(): array
    {
        $this->paramRules = [
            'avatar' => 'required|string',
        ];

        try {
            parent::_init();

            $model = UserModel::find($this->getUid());
            $model->avatar_url = $this->requestData['avatar'];
            $model->save();

            return $this->returnData(true);
        } catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }


}
