<?php


namespace App\Type;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 分页数据类型
 * Class ArticleInputType
 * @package App\Type\Article
 */

class PagingListType extends Type
{
    public array $items = [];
    public int $currentPage = 0;
    public int $totalPage = 0;
    public int $total = 0;
    public int $pageSize = 20;
    protected LengthAwarePaginator $data;

    public function __construct(?LengthAwarePaginator $data = null)
    {
        if ($data instanceof LengthAwarePaginator) {
            $this->items = $data->items();
            $this->currentPage = $data->currentPage();
            $this->totalPage = $data->total() == 0 ? 0 : ceil(($data->total() / $data->perPage()));
            $this->total = $data->total();
            $this->pageSize = $data->perPage();
        }
    }
}
