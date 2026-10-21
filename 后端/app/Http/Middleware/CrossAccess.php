<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Config;

class CrossAccess
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
        $this->logger->warning('当前环境'.Config::get('app.env'));
        if (Config::get('app.env') == 'local') {
            $allow_origin = '*';
        } else {
            $allow_origin = [
                'http://api.138yc.com',
                'http://api.jisufahuo.com'
            ];
        }

        if ((is_array($allow_origin) && in_array($origin, $allow_origin)) || $allow_origin == '*') {
            $this->logger->warning('跨域成功啊');
            header('Access-Control-Allow-Origin', $origin);
            header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN');
            header('Access-Control-Expose-Headers', 'Authorization, authenticated');
            header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
            header('Access-Control-Allow-Credentials', 'true');
            header('Access-Control-Allow-Headers', 'token, content-type, requestId, x-requested-with');
        } else {
            if ($request->ajax()) {
                $this->logger->info('跨域失败 : '.$origin);

                $this->logger->info('跨域', [
                    'env'   =>  Config::get('app.env'),
                    'allow'=>   $allow_origin,
                    'result'    =>  'nopass ajax'
                ]);
            }
        }

        return $response;
    }
}
