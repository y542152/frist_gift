<?php


namespace App\Repositories;

use App\Models\WechatChargeOrdersModel;
use Illuminate\Database\Eloquent\Builder;

class WechatChargeOrdersRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return WechatChargeOrdersModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }

    public function add(int $uid, string $nickname, float $money, string $orderNo)
    {
        $model = new WechatChargeOrdersModel();
        $model->uid = $uid;
        $model->nickname = $nickname;
        $model->total_amount = $money;
        $model->out_trade_no = $orderNo;
        $model->saveOrFail();
        return $model;
    }
}
