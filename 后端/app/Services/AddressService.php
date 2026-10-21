<?php


namespace App\Services;


use App\Exceptions\Error;
use App\Models\AddressModel;
use App\Repositories\AddressRepository;
use App\Repositories\PlaceRepository;
use App\Supports\Code;
use App\Type\Models\AddAddressInputType;
use App\Type\Models\SearchParams;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AddressService
{
    public function fetchPlaceOptions(): array
    {
        $paramsType = new SearchParams();
        $paramsType->pagination = false;
        $paramsType->sort = 'id';
        $paramsType->pageSize = 4000;

        $list = (array) (new PlaceRepository())->pagingList($paramsType);

        $data1 = [];
        $data2 = [];
        $data3 = [];

        foreach ($list as $item) {
            if ($item['ctype'] == 1) {
                $data1[$item['id']] =  $item->toArray();
            } else if ($item['ctype'] == 2) {
                $data2[$item['id']] = $item->toArray();
            } else if ($item['ctype'] == 3) {
                $data3[] =  $item->toArray();
            }
        }

        $data2 = $this->parseChild($data2, $data3);
        unset($data3);

        $data2 = array_values($data2);
        $data1 = $this->parseChild($data1, $data2);
        unset($data2);

        return array_values($data1);
    }

    public function addAddress(int $uid, AddAddressInputType $inputType): void
    {
        $addressStr = explode(' ', $inputType->address);
        if (count($addressStr) != 4) {
            throw new Error(Code::REQUEST_PARAM_ERROR, '地址信息有误');
        }

        $model = new AddressModel();
        $model->uid = $uid;
        $model->name = $inputType->name;
        $model->phone = $inputType->phone;
        $model->province = $addressStr[0];
        $model->city = $addressStr[1];
        $model->district = $addressStr[2];
        $model->address =$addressStr[3];
        $model->saveOrFail();
    }

    private function parseChild(array $firstArray, array $secArray): array
    {
        for ($i = 0; $i < count($secArray); $i++) {
            $parentId = $secArray[$i]['parent_id'];

            if(!array_key_exists('children', $firstArray[$parentId])) {
                $firstArray[$parentId]['children'] = [];
            }
            $firstArray[$parentId]['children'][] = $secArray[$i];
        }
        return $firstArray;
    }

    public function fetchUserAddressList(int $uid, int $status = -1)
    {
        $paramsType = new SearchParams();
        $paramsType->where = [
            'uid'   =>  $uid
        ];

        if ($status > -1) {
            $paramsType->where['status'] =  $status;
        }

        $paramsType->pagination = false;
        $paramsType->sortDesc = 'desc';

        return (new AddressRepository())->pagingList($paramsType);
    }

    public function setStatus($uid, int $id, int $status): void
    {
        $info = AddressModel::find($id);
        if ($info->uid != $uid) {
            throw new Error(Code::RESOURCE_NOT_FOUND);
        }

        AddressModel::query()->where([
            'id'    =>  $id
        ])->update([
            'status'    =>  $status
        ]);
    }
}
