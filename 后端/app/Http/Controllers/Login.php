<?php


namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Exceptions\FrequentActionError;
use App\Repositories\LoginLogsRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Supports\Code;
use App\Supports\Helper;
use Illuminate\Support\Facades\Cache;

class Login extends BaseController
{
    // 登录
    public function handle(): array
    {
        $this->paramRules = [
            'username'  =>  'required|string|max:50',
            'password'  =>  'required|string|max:256',
        ];

        try {

            parent::_init();
            $account = $this->requestData['username'];

            $token = (new UserService())->login($account, $this->requestData['password']);

            if (!$token) {
                throw new Error(Code::LOGIN_FAILED);
            }

            $userRepo = new UserRepository();
            $user = $userRepo->fetchUserByToken($token);
            if ($user->is_delete == 1 || $user->role != 'USER') {
                throw new Error(Code::RESOURCE_NOT_FOUND);
            }

            $userRepo->loginCountAdd($user->uid);
            (new LoginLogsRepository())->addLogs($user->uid, $user->account);

            return $this->returnData([
                'uid' => $user->uid,
                'token' => $token,
                'nickname'  =>  $user->nickname,
                'roles' =>  [$user->role],
                'avatar'   =>  $user->avatar_url
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }


    public function adminLogin(): array
    {
        $this->paramRules = [
            'username'  =>  'required|string|max:50',
            'password'  =>  'required|string|max:256',
        ];

        try {
            parent::_init();
            $account = $this->requestData['username'];

            $token = (new UserService())->login($account, $this->requestData['password']);

            if (!$token) {
                throw new Error(Code::LOGIN_FAILED);
            }

            $userRepo = new UserRepository();
            $user = $userRepo->fetchUserByToken($token);
            if ($user->is_delete == 1 || $user->role != 'ADMIN') {
                throw new Error(Code::RESOURCE_NOT_FOUND);
            }


            $userRepo->loginCountAdd($user->uid);
            (new LoginLogsRepository())->addLogs($user->uid, $user->account);

            return $this->returnData([
                'uid' => $user->uid,
                'token' => $token,
                'nickname'  =>  $user->nickname,
                'roles' =>  [$user->role],
                'avatar'   =>  $user->avatar_url
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    // 注册
    public function register(): array
    {
        $this->paramRules = [
            'username'      =>  'required|string|max:20',
            'qq'            =>  'required|string|max:50',
            'password'      =>  'required|string|min:6|max:30',
            'password1'     =>  'required|string|min:6|max:30',
            'inviteUid'     =>  'nullable|int'
        ];

        try {
            parent::_init();
            $phone = $this->requestData['username'];

            if (!Helper::isPhoneNumber($phone)) {
                throw new Error(Code::REQUEST_PARAM_ERROR, '请填写有效的手机号码');
            }

            $qq = $this->requestData['qq'];
            $pw = $this->requestData['password'];
            $pw1 = $this->requestData['password1'];
            // 注册邀请码
            $inviterUid = $this->requestData['inviteUid'] ?? 0;

            if($pw !== $pw1) {
                throw new Error(Code::REQUEST_PARAM_ERROR, '密码不一致，请确认密码');
            }

            $lock = Cache::lock('register-start-' . $phone, 10);

            if ($lock->get()) {
                (new UserService())->registerByAccount($phone, $qq, $pw, $inviterUid);
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
}
