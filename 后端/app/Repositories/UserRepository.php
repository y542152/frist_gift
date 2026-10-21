<?php


namespace App\Repositories;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository
{
    /**
     * 通过TOKEN获取用户信息
     * @param String $token
     * @param string[] $fields
     * @return Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function fetchUserByToken(string $token, array $fields = ['*'])
    {
        $tableUser = (new UserModel())->getTable();
        $tableUserToken = (new UserTokenModel())->getTable();

        $info = DB::table($tableUserToken)
            ->select($fields)
            ->join($tableUser, $tableUserToken . '.uid', '=', $tableUser . '.uid')
            ->where('token', $token)
        ;

        return $info->first();
    }

    /**
     * 新增用户
     * @param array $data
     * @return bool
     */
    public function addUser(array $data): bool
    {
        $user = new UserModel();
        return $user->fill($data)->save();
    }

    /**
     * account是否已经存在
     * @param string $account
     * @return bool
     */
    public function isAccountExist(string $account): bool
    {
        return (bool) UserModel::query()->where('account', $account)->count();
    }

    /**
     * @param string $qq
     * @return bool
     */
    public function isQQExist(string $qq): bool
    {
        return (bool) UserModel::query()->where('qq', $qq)->count();
    }

    /**
     * uid是否已经存在
     * @param int $uid
     * @return bool
     */
    public function isUidExist(int $uid): bool
    {
        return (bool) UserModel::query()->where('uid', $uid)->count();
    }

    /**
     * email是否已经存在
     * @param string $email
     * @return bool
     */
    public function isEmailExist(string $email): bool
    {
        return (bool) UserModel::query()->where('qqemail', $email)->count();
    }

    /**
     * 通过email获取用户信息
     * @param $email
     * @return Builder|Model|object|null
     */
    public function fetchUserByEmail($email)
    {
        return UserModel::query()->where('qqemail', $email)->first();
    }

    /**
     * 修改密码
     * @param $uid
     * @param $passwd
     * @param $salt
     * @return bool
     */
    public function changeUserPasswd($uid, $passwd, $salt): bool
    {
        return (bool) UserModel::query()->where('uid', $uid)->update([
            'pw_encrypt' => $passwd,
            'salt' => $salt
        ]);
    }

    /**
     * 更新用户token
     * @param $uid
     *
     * @return string 返回 token
     */
    public function updateTokenByUid($uid): string
    {
        // 过期时间
        $expired_at = date('Y-m-d H:i:s', (time() + UserModel::TOKEN_EXPIRED_TIME));

        // 是否有旧的token信息
        $tokenInfo = $this->fetchTokenByUid($uid);

        // 创建新的token
        $token = $this->createToken();

        $data = [
            'token' => $token,
            'expired_at' => $expired_at
        ];

        if ($tokenInfo) {
            UserTokenModel::query()->where('uid', $uid)->update($data);
        } else {
            $data['uid'] = $uid;
            UserTokenModel::query()->insert($data);
        }

        return $token;
    }

    /**
     * 通过账户获取用户信息
     * @param string $account
     * @return Builder|Model|null
     */
    public function fetchUserByAccount(string $account)
    {
        return UserModel::query()->where('account', $account)->first();
    }

    public function fetchUserInfo($uid, $fields = ['*'])
    {
        return UserModel::query()->where('uid', $uid)->select($fields)->first();
    }

    private function createToken(): string
    {
        return md5(str_shuffle(uniqid() . time()));
    }

    /**
     * 获取token
     * @param $uid
     * @return Model|\Illuminate\Database\Query\Builder|null
     */
    public function fetchTokenByUid($uid)
    {
        return DB::table('user_token')->where('uid', $uid)->first();
    }

    public function loginCountAdd(int $uid)
    {
        $userModel = $this->fetchUserInfo($uid);
        $userModel->login_count = ($userModel->login_count ?? 0) + 1;
        $userModel->last_login_at = date('Y-m-d H:i:s');
        $userModel->save();
    }

    protected function newModel(): Builder
    {
        return UserModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        if (isset($otherCondition['qqPhone'])) {
            $result = UserModel::query()
                ->select('uid')
                ->where('qq', 'like', '%' . $otherCondition['qqPhone'] . '%')
                ->orWhere('account', 'like', '%' . $otherCondition['qqPhone'] . '%')
                ->get()->toArray();

            $model->whereIn('uid', array_column($result, 'uid'));
        }

        return $model;
    }
}
