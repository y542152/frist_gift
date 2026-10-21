<?php


namespace App\Repositories;

use App\Models\BaseModel;
use App\Type\Models\SearchParams;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BaseRepository
{
    public function processOrderBy($model, $oderBy) {
        $orderBys = explode(',', $oderBy);
        foreach ($orderBys as $order) {
            $tmp = explode(' ', $order);
            if ($tmp) {
                $model->orderBy($tmp[0], $tmp[1] ?? 'asc');
            }
        }
        return $model;
    }


    /**
     * @param SearchParams $paramsType
     * @return array|LengthAwarePaginator
     */
    public function pagingList(SearchParams $paramsType)
    {
        $model = $this->newModel();

        if ($paramsType->where) {
            foreach ($paramsType->where as $k => $where) {
                if (is_array($where)) {
                    $model->whereIn($k, $where);
                } else {
                    $model->where($k, $where);
                }
            }
        }

        // 排序
        if (count($paramsType->sortMultiple) > 0) {
            // 多个排序
            foreach ($paramsType->sortMultiple as $item) {
                $model->orderBy($item[0], $item[1]);
            }
        } else if ($paramsType->sort) {
            $model->orderBy($paramsType->sort, $paramsType->sortDesc);
        }

        if ($paramsType->otherCondition) {
            $model = $this->processOtherCondition($model, $paramsType->otherCondition);
        }

        $model->select($paramsType->fields);

        // 不分页情况下是否返回固定数据还是全部
        if (!$paramsType->pagination) {
            // pageSize大于0是返回固定数据，否则返回全部
            if ($paramsType->pageSize > 0) {
                $model->limit($paramsType->pageSize);
            }
        }

        return $paramsType->pagination ? $model->paginate($paramsType->pageSize, ['*'], 'page', $paramsType->page) : $model->get()->all();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        return $model;
    }

    protected function newModel(): Builder
    {
        return BaseModel::query();
    }
}
