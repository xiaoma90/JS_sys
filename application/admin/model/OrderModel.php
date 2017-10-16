<?php
namespace app\admin\model;

use think\exception\PDOException;
use think\Model;

class OrderModel extends Model
{
    protected $table = 'orders';

    /**
     * 根据搜索条件获取用户列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getOrdersByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     * @return int|string
     */
    public function getAllOrders($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 编辑管理员信息
     * @param $param
     * @return array
     */
    public function editOrder($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '发货成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function getOneOrder($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除管理员
     * @param $id
     * @return array
     */
    public function delOrder($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除管理员成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /*********订单***********/
    /**
     * 添加订单
     * @param $data
     * @return int|string
     */
    public function orderAdd($data){
        $order = $this->insertGetId($data);
        if($order){
            return $order;
        }
    }


}
