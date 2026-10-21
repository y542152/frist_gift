<?php


namespace App\Repositories;

use App\Models\OrdersModel;
use Illuminate\Database\Eloquent\Builder;

class OrdersRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return OrdersModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        if (!empty($otherCondition['created_at']) && count($otherCondition['created_at']) == 2) {
            $model = $model->whereDate('created_at', '>=', $otherCondition['created_at'][0])
                ->whereDate('created_at', '<=', $otherCondition['created_at'][1]);
        }

        if (!empty($otherCondition['ids'])) {
            $model = $model->whereIn('id', $otherCondition['ids']);
        }

        if (!empty($otherCondition['order_sn'])) {
            $model = $model->where(function($query) use ($otherCondition) {
                $query->where('order_sn', $otherCondition['order_sn'])
                    ->orWhere('main_order_sn', $otherCondition['order_sn']);
            });
        }

        if (isset($otherCondition['is_cancel'])) {
            if ($otherCondition['is_cancel'] == 1) {
                $model = $model->where('status', OrdersModel::STATUS_CANCELED);
            } else {
                $model = $model->whereIn('status', [
                    OrdersModel::STATUS_WAIT_PREORDER,
                    OrdersModel::STATUS_WAIT_COLLECT,
                    OrdersModel::STATUS_COLLECTED,
                    OrdersModel::STATUS_PREORDER_FAILED,
                ]);
            }
        }

        if (!empty($otherCondition['kuaidi_name'])) {
            $model = $model->where('kuaidi_name', 'like', '%'.$otherCondition['kuaidi_name'].'%');
        }

        return $model;
    }
}
