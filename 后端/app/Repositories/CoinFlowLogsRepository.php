<?php


namespace App\Repositories;

use App\Models\CoinFlowLogsModel;
use Illuminate\Database\Eloquent\Builder;

class CoinFlowLogsRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return CoinFlowLogsModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }
}
