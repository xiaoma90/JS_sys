<?php
namespace app\admin\controller;

use app\admin\model\OrderModel;
use app\index\controller\Index as Pro;

class Order extends Base
{
    //订单列表
    public function index()
    {
        $status = config('order_status');
        $payment = config('payment');
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            $where['status'] = ['in',[2,3,4]];
            $where['type'] = 0;
            if(isset($param['order_sn']) && !empty($param['order_sn'])){
                $where['order_num'] = $param['order_sn'];
            }
            if(isset($param['name']) && !empty($param['name'])){
                $uid = db('users')->where(['name'=>['like','%'.$param['name'].'%']])->column('id');
                if($uid){
                    $where['user_id'] = ['in',$uid];
                }
            }
            //下单时间段
            if (isset($param['start']) && !empty($param['start']) && empty($param['end'])) {
                $param['start'] = $param['start'].' 00:00:00';
                $where['created_at'] = ['>=',$param['start']];
            }
            if (isset($param['end']) && !empty($param['end']) && empty($param['start'])) {
                $param['end'] = $param['end'].' 23:59:59';
                $where['created_at'] = ['<=',$param['end']];
            }
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['created_at'] = ['between',"$time[0],$time[1]"];
            }
            $lun = $param['status'];
            $order = new OrderModel();
            if($lun != 'W'){
                $offset = 0;
                $limit  = 9999;
            }
            $selectResult = $order->getOrdersByWhere($where, $offset, $limit);
            
            foreach($selectResult as $key=>$vo){
                if($lun != 'W'){
                    $test = new Pro();
                    $l = $test->process($vo['id']);
                    if($l[0] != $lun){
                        unset($selectResult[$key]);
                    }else{
                        $selectResult[$key]['status'] = $status[$vo['status']];
                        $selectResult[$key]['order_num'] = "<span onclick='tree(".$vo['id'].")'>".$vo['order_num']."</span>";
                        $selectResult[$key]['operate'] = "<span class='btn btn-primary' onclick='sees(".$vo['id'].")'>查看结算进度</span>";
                        //支付类型
                        if($vo['pay_type']){
                            $selectResult[$key]['pay_type'] = $payment[$vo['pay_type']];
                        }else{
                            $selectResult[$key]['pay_type'] = '未定义';
                        }
                    }  
                }else{
                    $selectResult[$key]['status'] = $status[$vo['status']];
                    $selectResult[$key]['order_num'] = "<span onclick='tree(".$vo['id'].")'>".$vo['order_num']."</span>";
                    $selectResult[$key]['operate'] = "<span class='btn btn-primary' onclick='sees(".$vo['id'].")'>查看结算进度</span>";
                    //支付类型
                    if($vo['pay_type']){
                        $selectResult[$key]['pay_type'] = $payment[$vo['pay_type']];
                    }else{
                        $selectResult[$key]['pay_type'] = '未定义';
                    }
                }   
            }
            $selectResult = array_values(objToArr($selectResult));
            $return['total'] = $order->getAllOrders($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        $this->assign([
            'status' => $status,
            'payment' => $payment
        ]);
        return $this->fetch();
    }
    private function inits(){
        $return[] = ['name'=>'0','phone'=>'0','order'=>'0','id'=>0];
        $data = db('test')->limit(0,12)->select();
        foreach ($data as $key => $value) {
            if($value['oid']){
               $d = db('orders')->where(['id'=>$value['oid']])->find();
            $return[] = ['name'=>$d['name'],'phone'=>$d['phone'],'order'=>$d['order_num'],'id'=>$value['id']];
            }      
        }
        return $return;
    }
    public function treedata(){
        if(request()->isAjax()){
            $return = [];
            $param = input('param.order_sn');
            if($param == 'oo'){
               $return = $this->inits();
            }else{
                $order = db('orders')->where(['order_num'=>$param])->find();
                $test  = db('test') ->where(['oid'=>$order['id'],'is_add'=>1])->find();
                if(!empty($test)){
                    $return[] = ['name'=>$order['name'],'phone'=>$order['phone'],'order'=>$order['order_num'],'id'=>$test['id']];
                    $one = db('test') ->where(['pid'=>$test['id']])->whereNotNull('oid')->order('id asc')->select();
                    $oneid = db('test') ->where(['pid'=>$test['id']])->whereNotNull('oid')->column('id');
                    if(!empty($one)){
                        foreach ($one as $k => $v) {
                           if($v['is_add'] == 1){
                                $d = db('orders')->where(['id'=>$v['oid']])->find();
                                $return[] = ['name'=>$d['name'],'phone'=>$d['phone'],'order'=>$d['order_num'],'id'=>$v['id']];
                           }else{
                            $return[] = ['name'=>'0','phone'=>'0','order'=>'0','id'=>$v['id']];
                           }
                        }
                        $two = db('test') ->where(['pid'=>['in',$oneid]])->whereNotNull('oid')->order('id asc')->select();
                        if(!empty($two)){
                            foreach ($two as $ks => $vo) {
                               if($vo['is_add'] == 1){
                                    $dd = db('orders')->where(['id'=>$vo['oid']])->find();
                                    $return[] = ['name'=>$dd['name'],'phone'=>$dd['phone'],'order'=>$dd['order_num'],'id'=>$vo['id']];
                               }else{
                                    $return[] = ['name'=>'0','phone'=>'0','order'=>'0','id'=>$vo['id']];
                               }
                            }
                        }
                    }
                 } 
            }    
            return json($return);  
        }
        return $this->fetch();
    }
    public function treedata1(){
        if(request()->isAjax()){
            $return = [];
            $param = input('param.id');

            if($param == 0){
               $return = $this->inits();
            }else{
                $test  = db('test') ->where(['id'=>$param])->find();
                if(input('type') =='1'){
                    if($test['pid'] ==0)return json($this->inits());
                    $test  = db('test') ->where(['id'=>$test['pid']])->find();
                }
                
                if($test['is_add'] == 2 ){
                    $order = ['name'=>'0','phone'=>'0','order_num'=>'0'];
                }else{
                    $order = db('orders')->where(['id'=>$test['oid']])->find(); 
                }                 
                if(!empty($test)){
                    $return[] = ['name'=>$order['name'],'phone'=>$order['phone'],'order'=>$order['order_num'],'id'=>$test['id']];
                    $one = db('test') ->where(['pid'=>$test['id']])->whereNotNull('oid')->order('id asc')->select();
                    $oneid = db('test') ->where(['pid'=>$test['id']])->whereNotNull('oid')->column('id');
                    if(!empty($one)){
                        foreach ($one as $k => $v) {
                           if($v['is_add'] == 1){
                                $d = db('orders')->where(['id'=>$v['oid']])->find();
                                $return[] = ['name'=>$d['name'],'phone'=>$d['phone'],'order'=>$d['order_num'],'id'=>$v['id']];
                           }else{
                            $return[] = ['name'=>'0','phone'=>'0','order'=>'0','id'=>$v['id']];
                           }
                        }
                        $two = db('test') ->where(['pid'=>['in',$oneid]])->whereNotNull('oid')->order('id asc')->select();
                        if(!empty($two)){
                            foreach ($two as $ks => $vo) {
                               if($vo['is_add'] == 1){
                                    $dd = db('orders')->where(['id'=>$vo['oid']])->find();
                                    $return[] = ['name'=>$dd['name'],'phone'=>$dd['phone'],'order'=>$dd['order_num'],'id'=>$vo['id']];
                               }else{
                                    $return[] = ['name'=>'0','phone'=>'0','order'=>'0','id'=>$vo['id']];
                               }
                            }
                        }
                    }
                 } 
            }    
            return json($return);  
        }
        return $this->fetch();
    }
    public function data(){
        #进入第一轮的单子
        $one = 0;
        #进入第二轮的单子
        $two = 0;
        #进入第三轮的单子
        $three = 0;
        #进入第四轮的单子
        $four = 0;
        #出局的单子
        $chu = 0;
        #累计中期红利
        $hls = 0;
        #累计招募薪资
        $zm = 0;
        #累计月度薪资
        $yx = 0;
        #累计月度
        $y = 0;
        #累计运营费用
        $yy = 0;
        $bb = 0;
        $sy = 0;
        $num_money = 0;
        $num = 0;
        #累计复投订单
        $ft =  db('test')->whereNotNull('oid')->where(['is_add'=>2])->count();
        $data = db('test')->whereNotNull('oid')->select();
        $test = new Pro();
        if(!empty($data)){
            foreach($data as $k=>$v){
                $res = $test->process1($v['id']);
                switch($res[0]){
                    case 'X':#第一轮
                        $one++;
                        break;
                    case 'V1':#第二轮
                        $two++;
                        $zm += 300*2;
                        $yx += 1000*1;
                        $yy += 370;
                        $y  += 1;
                        break;
                    case 'V2':#第三轮
                        $three++;
                        $zm += 300*2+500*2;
                        $yx += 1000*1+1000*3;
                        $yy += 370+14;
                        $y  += 1+3;
                        break;
                    case 'V3':#第四轮
                        $four++;
                        $zm += 300*2+500*2+500*2;
                        $yx += 1000*1+1000*3+1000*4;
                        $yy += 370+14+1014;
                        $y  += 1+3+4;
                        break;
                    case 'V4':#出局
                        $chu++;
                        $zm += 300*2+500*2;
                        $yx += 1000*1+1000*3+1000*4+500*6;
                        $yy += 370+14+1014+5014;
                        $y  += 1+3+4+6;
                        $hls += 14000;
                        break;
                    default:
                        break;
                }
            }
            #大礼包单子总收益
            $num = count($data)-$ft;
            $num_money = $num*3986;
            $sy = $num_money-$ft*3986-$hls-$zm-$yx-$yy;
            $bb = round(($num_money-$sy)/$num_money*100,2);
        }
        
        $this->assign([
            'one' => $one,
            'two' => $two,
            'three' =>$three,
            'four' => $four,
            'chu' => $chu,
            'num' => $num,
            'num_money' => $num_money,
            'hls' => $hls,
            'ft' => $ft,
            'zm' => $zm,
            'yy' => $yy,
            'bb' => $bb,
            'sy' => $sy,
            'yx' => $yx
        ]);
        return $this->fetch();
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '编辑' => [
                'auth' => 'order/process',
                'href' => url('order/process', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ]
        ];
    }
}
