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

class NeedAdmin
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
        $token = $request->header('token');
        $user = (new UserRepository())->fetchUserByToken($token, ['role']);

        $this->logger->warning('role', [
            'role'  =>$user->role
        ]);
        if ($user->role !== 'ADMIN') {
            return Error::returnException(new Error(Code::USER_ACCOUNT_NOT_MATH), 200);
        }
        return $next($request);
    }
}
