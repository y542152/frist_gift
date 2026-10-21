<?php


namespace App\Repositories;

use App\Models\AddressModel;
use Illuminate\Database\Eloquent\Builder;

class AddressRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return AddressModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }
}
