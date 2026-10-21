<?php

namespace App\Http\Middleware;

use App\Exceptions\Error;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Supports\Code;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Config;

class NeedLogin
{
    private Logger $logger;

    private bool $close = false;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->close) {
            header('HTTP/1.1 404 Not Found');exit();
        }
        $saved = null;
        $errorMsg = null; // 是否返回特定的错误信息
        $token = $request->header('token');

        if ($token) {
            $user = (new UserRepository())->fetchUserByToken($token, ['expired_at', 'token', 'users.uid', 'is_delete']);

            if ($user && $user->is_delete == 1) {
                return Error::returnException(new Error(Code::LOGIN_FAILED, $errorMsg ?? Code::MSG_MAP[Code::LOGIN_FAILED]), 200);
            }

            if ($user) {
                if ((new UserService())->isTokenValid((array) $user)) {
                    // 短暂保存用户信息
                    $request->user = $user;
                    $saved = true;
                }
            }
        }

        if (!$saved) {
            return Error::returnException(new Error(Code::UNAUTHORIZED, $errorMsg ?? Code::MSG_MAP[Code::UNAUTHORIZED]), 200);
        }

        return $next($request);
    }
}
