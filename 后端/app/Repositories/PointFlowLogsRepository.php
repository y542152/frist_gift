<?php


namespace App\Repositories;

use App\Models\PointFlowLogsModel;
use Illuminate\Database\Eloquent\Builder;

class PointFlowLogsRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return PointFlowLogsModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }
}
