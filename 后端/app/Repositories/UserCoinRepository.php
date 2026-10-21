<?php


namespace App\Repositories;

use App\Exceptions\Error;
use App\Models\CoinFlowLogsModel;
use App\Models\PointFlowLogsModel;
use App\Models\UserCoinModel;
use App\Supports\Code;
use App\Type\Models\CoinChangeType;
use App\Type\Models\PointChangeType;
use Illuminate\Database\Eloquent\Builder;

class UserCoinRepository extends BaseRepository
{
    protected function newModel(): Builder
    {
        return UserCoinModel::query();
    }

    protected function processOtherCondition(Builder $model, array $otherCondition): Builder
    {
        if (isset($otherCondition['uidList'])) {
            $model->whereIn('uid', $otherCondition['uidList']);
        }
        return $model;
    }

    public function initCoinPoint(int $uid)
    {
        $model = UserCoinModel::query()->where('uid', $uid)->first();
        if (!$model) {
            $model = new UserCoinModel();
            $model->uid = $uid;
            $model->point = 0;
            $model->coin = 0;
            $model->saveOrFail();
            return $this->initCoinPoint($uid);
        }
        return $model;
    }

    /**
     * @throws Error
     */
    public function coinIn(int $uid, CoinChangeType $type)
    {
        $this->changeCoinAction($uid, $type, PointFlowLogsModel::TYPE_IN);
    }

    /**
     * @throws Error
     */
    public function coinOut(int $uid, CoinChangeType $type)
    {
        $this->changeCoinAction($uid, $type, PointFlowLogsModel::TYPE_OUT);
    }

    /**
     * @throws Error
     * @throws \Throwable
     */
    public function pointIn(int $uid, int $fromUid, PointChangeType $type)
    {
        $this->changePointAction($uid, $fromUid, $type, PointFlowLogsModel::TYPE_IN);
    }

    /**
     * @throws Error
     * @throws \Throwable
     */
    public function pointOut(int $uid,int $fromUid, PointChangeType $type)
    {
        $this->changePointAction($uid, $fromUid, $type, PointFlowLogsModel::TYPE_OUT);
    }

    private function changeCoinAction(int $uid, CoinChangeType $type, string $changeType)
    {
        // 更新
        $coinModel = $this->initCoinPoint($uid);

        // 日志
        $model = new CoinFlowLogsModel();
        $model->type = $changeType;
        $model->channel = $type->channel;
        $model->amount = $type->amount;
        $model->out_trade_no = $type->orderNo;
        $model->old_amount = $coinModel->coin;

        if ($changeType === CoinFlowLogsModel::TYPE_IN) {
            $coinModel->coin = bcadd($type->amount, $coinModel->coin, 2);
        }

        if ($changeType === CoinFlowLogsModel::TYPE_OUT) {
            $coinModel->coin = bcsub($coinModel->coin, $type->amount, 2);
        }

        if ($coinModel->coin < 0) {
            throw new Error(Code::OPERATE_FAILED, '金币不足');
        }
        $coinModel->saveOrFail();

        $model->new_amount = $coinModel->coin;
        $model->desc = $type->desc;
        $model->uid = $uid;
        $model->saveOrFail();
    }

    /**
     * @param int $uid 上级
     * @param int $formUid 下级
     * @param PointChangeType $type
     * @param string $changeType
     * @return void
     * @throws Error
     * @throws \Throwable
     */
    private function changePointAction(int $uid, int $formUid, PointChangeType $type, string $changeType)
    {
        // 更新
        $pointModel = $this->initCoinPoint($uid);

        // 日志
        $model = new PointFlowLogsModel();
        $model->uid = $uid;
        $model->form_uid = $formUid;
        $model->type = $changeType;
        $model->out_trade_no = $type->orderNo;
        $model->channel = $type->channel;
        $model->old_amount = $pointModel->point;

        if ($changeType === PointFlowLogsModel::TYPE_IN) {
            $pointModel->point = bcadd($type->amount, $pointModel->point, 2);
        }

        if ($changeType === PointFlowLogsModel::TYPE_OUT) {
            $pointModel->point = bcsub($pointModel->point, $type->amount, 2);
        }

        if ($pointModel->point < 0) {
            throw new Error(Code::OPERATE_FAILED, '积分不足');
        }
        $pointModel->saveOrFail();

        $model->new_amount = $pointModel->point;
        $model->amount = $type->amount;
        $model->desc = $type->desc;
        $model->saveOrFail();
    }
}
