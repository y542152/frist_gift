<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/user/login', [\App\Http\Controllers\Login::class, 'handle']);// 登录
Route::post('/user/adminLogin', [\App\Http\Controllers\Login::class, 'adminLogin']);// 登录
Route::post('/user/register', [\App\Http\Controllers\Login::class, 'register']);// 注册
//Route::middleware(['needLogin'])->get('/menu', [\App\Http\Controllers\User::class, 'menu']);// 菜单

Route::middleware(['needLogin'])->get('/user/me', [\App\Http\Controllers\User::class, 'handle']);// 用户信息
Route::middleware(['needLogin'])->get('/user/settingInfo', [\App\Http\Controllers\User::class, 'settingInfo']);// 客服信息之类

Route::middleware(['needLogin'])->post('/user/point2coin', [\App\Http\Controllers\User::class, 'point2coin']);// 积分转金币
Route::middleware(['needLogin'])->post('/user/updateAvatar', [\App\Http\Controllers\User::class, 'updateAvatar']);// 修改头像

Route::middleware(['needLogin'])->get('/user/charge/{amount}', [\App\Http\Controllers\User::class, 'charge']);// 充值
Route::middleware(['needLogin'])->get('/user/chargeResult/{orderNo}/{amount}', [\App\Http\Controllers\User::class, 'chargeResult']);// 充值
Route::middleware(['needLogin'])->get('/user/promotion/list', [\App\Http\Controllers\User::class, 'promotionList']);// 我的推广
Route::middleware(['needLogin'])->get('/user/promotion/url', [\App\Http\Controllers\User::class, 'promotionUrl']);// 我的推广url

Route::middleware(['needLogin'])->post('/user/pointLogs', [\App\Http\Controllers\User::class, 'pointLogs']); // 流水
Route::middleware(['needLogin'])->post('/user/coinLogs', [\App\Http\Controllers\User::class, 'coinLogs']);

Route::middleware(['needLogin'])->post('/user/wechatPayOrders', [\App\Http\Controllers\User::class, 'wechatPayOrders']);

Route::middleware(['needLogin'])->get('/gift/sendOptions', [\App\Http\Controllers\Gift::class, 'sendOptions']);// 发货信息
Route::middleware(['needLogin'])->post('/gift/buy', [\App\Http\Controllers\Gift::class, 'buy']);// 购买礼品

Route::middleware(['needLogin'])->post('/gift/logs', [\App\Http\Controllers\Gift::class, 'logs']);// 记录
Route::middleware(['needLogin'])->get('/gift/queryOrderStatus/{orderSn}', [\App\Http\Controllers\Gift::class, 'queryOrderStatus']);// 查询订单状态
Route::middleware(['needLogin'])->post('/gift/export', [\App\Http\Controllers\Gift::class, 'export']);// 导出记录

Route::middleware(['needLogin'])->post('/gift/parseAddress', [\App\Http\Controllers\Address::class, 'parseAddress']);// 格式化地址
Route::middleware(['needLogin'])->post('/address/list', [\App\Http\Controllers\Address::class, 'userAddress']);// 用户所有地址
Route::middleware(['needLogin'])->get('/address/index', [\App\Http\Controllers\Address::class, 'index']);// 省市区信息
Route::middleware(['needLogin'])->post('/address/add', [\App\Http\Controllers\Address::class, 'add']);// add
Route::middleware(['needLogin'])->get('/address/setStatus/{id}/{status}', [\App\Http\Controllers\Address::class, 'setStatus']);// 修改状态

Route::middleware(['needLogin'])->post('/upload', [\App\Http\Controllers\Common::class, 'upload']);
Route::middleware(['needLogin'])->post('/up_csv', [\App\Http\Controllers\Common::class, 'up_csv']);

Route::middleware(['needLogin'])->post('/user/charge/createWechatPay', [\App\Http\Controllers\User::class, 'createWechatPay']); // 创建微信支付订单

Route::middleware(['needLogin', 'mustAdmin'])->post('/user/list', [\App\Http\Controllers\User::class, 'users']);// 用户列表
Route::middleware(['needLogin', 'mustAdmin'])->post('/user/loginLogs', [\App\Http\Controllers\User::class, 'loginLogs']);// 用户登录日志

Route::middleware(['needLogin', 'mustAdmin'])->post('/user/updateUserInfo', [\App\Http\Controllers\User::class, 'update']);// 设置用户充值抽成比例等信息
Route::middleware(['needLogin', 'mustAdmin'])->post('/user/updateUserCoin', [\App\Http\Controllers\User::class, 'updateCoin']);// 加减用户余额
Route::middleware(['needLogin', 'mustAdmin'])->post('/user/updatePassword', [\App\Http\Controllers\User::class, 'updatePassword']);// 更新用户密码
Route::middleware(['needLogin', 'mustAdmin'])->post('/user/setting/update', [\App\Http\Controllers\User::class, 'updateSetting']);// 设置支付类信息
Route::middleware(['needLogin', 'mustAdmin'])->get('/user/deleteUser/{uid}', [\App\Http\Controllers\User::class, 'deleteUser']);// 删除用户
Route::middleware(['needLogin', 'mustAdmin'])->post('/user/createUser', [\App\Http\Controllers\User::class, 'createUser']);// 创建新用户

Route::middleware(['needLogin', 'mustAdmin'])->post('/user/setting/point2coin', [\App\Http\Controllers\User::class, 'setPoint2coinRate']);// 设置积分转金币比例

Route::middleware(['needLogin', 'mustAdmin'])->post('/user/alipayOrders', [\App\Http\Controllers\User::class, 'alipayOrders']);

Route::middleware(['needLogin', 'mustAdmin'])->get('/user/charge/wechatPay/pass/{orderNo}', [\App\Http\Controllers\User::class, 'wechatPayPass']);
Route::middleware(['needLogin', 'mustAdmin'])->get('/user/charge/wechatPay/reject/{orderNo}', [\App\Http\Controllers\User::class, 'wechatPayReject']);

Route::middleware(['needLogin', 'mustAdmin'])->get('/user/setting/info', [\App\Http\Controllers\User::class, 'setting']);// 支付类信息

Route::post('/alipayNotify', [\App\Http\Controllers\Notify::class, 'ali']);// 支付回调
Route::post('/user/checkAccountExist', [\App\Http\Controllers\User::class, 'checkAccountExist']);// 账号是否存在
Route::post('/user/resetByEmail', [\App\Http\Controllers\User::class, 'sendResetMail']);// 邮箱重置密码
Route::post('/user/reset', [\App\Http\Controllers\User::class, 'reset']);// 邮箱重置密码
