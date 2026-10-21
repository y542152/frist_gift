<?php


namespace App\Repositories;

use App\Models\ChargeOrdersModel;
use Illuminate\Database\Eloquent\Builder;

class ChargeOrdersRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return ChargeOrdersModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }

    public function fetchNotExpiredOrder (int $uid, float $amount)
    {
        return ChargeOrdersModel::query()->where([
            'uid'   =>  $uid,
            'total_amount'  =>  $amount
        ])
            ->where('expired_at', ">", date("Y-m-d H:i:s"))
            ->first();
    }
}
