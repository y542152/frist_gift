<?php

namespace App\Type\Models;

use App\Type\BaseType;

/**
 * 查询参数太多了 打个包统一传入
 * 查询数据入参打包
 */

class SearchParams extends BaseType
{
    public array $where = [];
    public int $page = 1;
    public int $pageSize = 20;
    // 查询的字段
    public array $fields = ['*'];

    // 是否使用分页
    public bool $pagination = true;

    // 排序 默认最新最前
    public string $sort = 'created_at';
    public string $sortDesc = 'asc';

    //['is_end','asc'],
    //['updated_at', 'desc']
    public array $sortMultiple = []; // 多个排序

    // 其他自定条件
    // 用来判断做逻辑，不能直接放到where里面
    public array $otherCondition = [];

    // sort是否添加前缀 用于连表时有多个相同字段sort报错 只能在初始化时加入
    public string $sortPrefix = '';

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->page = $data['current'] ?? ($data['page'] ?? 1);
        if ($this->sortPrefix) {
            if (is_array($this->sort)) {
                foreach($this->sort as &$sort) {
                    $sort = sprintf('%s.%s', $this->sortPrefix, $sort);
                }
            } else {
                $this->sort = sprintf('%s.%s', $this->sortPrefix, $this->sort);
            }
        }
    }
}