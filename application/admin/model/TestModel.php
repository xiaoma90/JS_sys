<?php
namespace app\admin\model;

use think\Model;

class TestModel extends Model
{
    protected $table = 'test';

    /**
     * @return \think\model\relation\HasOne
     */
    public function orders(){
        return $this->hasOne(OrderModel::class,'oid');
    }
}