<?php


namespace App\Repositories;

use App\Models\PlaceModel;
use Illuminate\Database\Eloquent\Builder;

class PlaceRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return PlaceModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }
}
