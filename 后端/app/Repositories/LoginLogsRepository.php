<?php


namespace App\Repositories;

use App\Models\LoginLogsModel;
use App\Supports\Helper;
use Illuminate\Database\Eloquent\Builder;

class LoginLogsRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return LoginLogsModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }

    public function addLogs(int $uid, string $account)
    {
        try {
            $model = new LoginLogsModel();
            $model->uid = $uid;
            $model->account = $account;
            $model->last_login_at = date('Y-m-d H:i:s');
            $model->ip = ip2long(Helper::getIp());
            $model->saveOrFail();
        } catch (\Throwable $e) {
            logger()->warning('记录登录日志失败:'. $e->getMessage());
        }
    }
}
